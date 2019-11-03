<?php

    class message{
                
        public function listMessages($group_id){ 
            $db = initDb(); 
            $getGroupUsers = $db->prepare("SELECT user_id FROM in_group WHERE group_id = :group_id");
            $getGroupUsers->bindParam(":group_id", $group_id, PDO::PARAM_INT);
            $getGroupUsers->execute();
            $users = $getGroupUsers->fetchAll(PDO::FETCH_ASSOC);
            $messages = array();
            foreach ($users as $item) {
                $getMessage = $db->prepare("SELECT * FROM task_messages WHERE user_id = :user_id ORDER BY visited ASC");
                $getMessage->bindParam(":user_id", $item['user_id'], PDO::PARAM_INT);
                $getMessage->execute();                
                if($getMessage->rowCount() > 0){                                        
                    while($row = $getMessage->fetchObject()){
                        array_push($messages, $row);   
                    }                                 
                }                          
            }
            return json_encode($messages); 
        }

        public function setVisited($message_id){
            try {
                $db = initDb();
                $updateVisited = $db->prepare("UPDATE task_messages SET visited = 1 WHERE id = :message_id");
                $updateVisited->bindParam(":message_id", $message_id, PDO::PARAM_INT);
                $updateVisited->execute();
            } catch (PDOException $e){
                echo '{"error":{"text":' . $e->getMessage() . '}}';
            }
        }

        public function setUnvisited($message_id){
            try {
                $db = initDb();
                $updateVisited = $db->prepare("UPDATE task_messages SET visited = 0 WHERE id = :message_id");
                $updateVisited->bindParam(":message_id", $message_id, PDO::PARAM_INT);
                $updateVisited->execute();
            } catch (PDOException $e){
                echo '{"error":{"text":' . $e->getMessage() . '}}';
            }
        }
      
        public function createMessage($user_id, $message){
            try {
                $db = initDb();
                $createMessage = $db->prepare("INSERT INTO task_messages (user_id, message) VALUES (:user_id, :message)");
                $createMessage->bindParam(":user_id", $user_id, PDO::PARAM_INT);
                $createMessage->bindParam(":message", $message, PDO::PARAM_STR);
                $createMessage->execute();
                return "created";
            } catch (PDOException $e){
                echo '{"error":{"text":' . $e->getMessage() . '}}';
            }
        }

        public function updateMessageTimestamp($message_id){
            try {
                $db = initDb();
                $updateTimestamp = $db->prepare("UPDATE task_messages SET posted = now() WHERE id = :message_id");
                $updateTimestamp->bindParam(":message_id", $message_id, PDO::PARAM_INT);
                $updateTimestamp->execute();
            } catch (PDOException $e){
                echo '{"error":{"text":' . $e->getMessage() . '}}';
            }
        }

        public function getReplies($message_id){
            include_once("user.php");
            $userClass = new user();
            try {
                $db = initDb();
                $getReplies = $db->prepare("SELECT id, user_id, message, posted FROM task_messages_replies WHERE message_id = :message_id");
                $getReplies->bindParam(":message_id", $message_id, PDO::PARAM_INT);
                $getReplies->execute();
                $replies = array();
                while($reply = $getReplies->fetch(PDO::FETCH_ASSOC)){
                    $reply['fullname'] = $userClass->getFullName($reply['user_id']);
                    array_push($replies, $reply);
                }            
                return json_encode($replies);
            } catch (PDOException $e){
                echo '{"error":{"text":' . $e->getMessage() . '}}';
            }
        }

        private function getMessageOwnerId($message_id){
            try {
                $db = initDb();
                $getMessageOwnerId = $db->prepare("SELECT user_id FROM task_messages WHERE id = :message_id");
                $getMessageOwnerId->bindParam(":message_id", $message_id, PDO::PARAM_INT);
                $getMessageOwnerId->execute();
                return $getMessageOwnerId->fetchColumn();
            } catch (PDOException $e){
                echo '{"error":{"text":' . $e->getMessage() . '}}';
            }
        }
        
        public function reply($message_id, $user_id, $message){
            try {
                $db = initDb();
                $reply = $db->prepare("INSERT INTO task_messages_replies (message_id, user_id, message) VALUES (:message_id, :user_id, :message)");
                $reply->bindParam(":message_id", $message_id, PDO::PARAM_INT);
                $reply->bindParam(":user_id", $user_id, PDO::PARAM_INT);
                $reply->bindParam(":message", $message, PDO::PARAM_STR);
                $reply->execute();
                $id = $db->lastInsertId();
                $this->updateMessageTimestamp($message_id);
                include_once("push.php");
                include_once("user.php");
                $pushClass = new push();  
                $userClass = new user();                  
                $messageOwner = $this->getMessageOwnerId($message_id);

                if($messageOwner != $user_id){
                    $sendMessage = $pushClass->compilePush($this->getMessageOwnerId($message_id), "user", "newmessage", false, false);                                               
                }
                
                return json_encode(array(
                    "id" => $id,
                    "user_id" => $user_id,
                    "message" => $message,
                    "fullname" => $userClass->getFullName($user_id)
                ));
            } catch (PDOException $e){
                echo '{"error":{"text":' . $e->getMessage() . '}}';
            }
        }
    }

?>