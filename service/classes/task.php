<?php
    include_once("group.php");
    include_once("push.php");
    class task {
        public function createTask($group_id, $name, $desc, $amount, $startdate, $enddate, $image){
            $enddate = date('Y-m-d H:i:s',strtotime('+23 hour +59 minutes +59 seconds',strtotime($enddate)));
            try {
                $conn = initDb();
                $createTask = $conn->prepare("INSERT INTO tasks (task_name, task_desc, total, group_id, startdate, enddate, image) VALUES (:task_name, :task_desc, :total, :group_id, :startdate, :enddate, :image)");
                $createTask->bindParam(":task_name", $name, PDO::PARAM_STR);
                $createTask->bindParam(":task_desc", $desc, PDO::PARAM_STR);
                $createTask->bindParam(":total", $amount, PDO::PARAM_INT);
                $createTask->bindParam(":group_id", $group_id, PDO::PARAM_INT);
                $createTask->bindParam(":startdate", $startdate, PDO::PARAM_STR);
                $createTask->bindParam(":enddate", $enddate, PDO::PARAM_STR);
                $createTask->bindParam(":image", $image, PDO::PARAM_STR);
                $createTask->execute();                  
                $pushClass = new push();                               
                $pushClass->compilePush($group_id, "group", "newtask", true, $startdate);             
                return "created";                
            } catch(PDOException $e) {
                echo '{"error":{"text":'. $e->getMessage() .'}}'; 
            }
        }

        public function getTaskResultsInExcelFormat($group_id, $task_name){
            $fileName = $task_name.".xlsx";
            header("Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet");
            header("Content-Disposition: attachment; filename=\"$fileName\"");
            header("Cache-Control: max-age=0");
            require "includes/PHPExcel/PHPExcel.php";
            require_once "includes/PHPExcel/PHPExcel/IOFactory.php";
            $file1 = "";    //GET JSON HERE
            $objXLS = new PHPExcel();
            $value = 1;
            $array = json_decode($file1);
            $man_val = array();
        
            //set the heading for first time
        
            foreach ($array as $key => $jsons) { 
                foreach($jsons as $key => $value1) {
                    array_push($man_val,$key);
                }
                break;
            }

            $objXLS->getSheet(0)->fromArray($man_val, null, "A".$value);
            $man_val = array();
            $value = 2;

            foreach ($array as $key => $jsons) { 
                foreach($jsons as $key => $value1) {
                    array_push($man_val,$value1);
                }
                $objXLS->getSheet(0)->fromArray($man_val, null, "A".$value);
                $value = $value+1;
                $man_val = array();
            }
        
            $fileType = 'Excel2007';            
            $objWriter = PHPExcel_IOFactory::createWriter($objXLS, $fileType);
            $objWriter->save("php://output");
            $objXLS->disconnectWorksheets();
            unset($objXLS);        
        }

        public function uploadTaskImage($myRandomId, $file){
            try {
                $new_image_name = $myRandomId.urldecode($file["file"]["name"]);
                $target_dir = "images/tasks/".$new_image_name;                    
                if(move_uploaded_file($file["file"]["tmp_name"], $target_dir)){
                    return "success";
                } else {
                    return "failed";
                }            
            } catch (PDOException $e){
                echo '{"error":{"text":' . $e->getMessage() . '}}';
            }
        }

        private function generateRandomString($length = 4) {
            $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
            $charactersLength = strlen($characters);
            $randomString = '';
            for ($i = 0; $i < $length; $i++) {
                $randomString .= $characters[rand(0, $charactersLength - 1)];
            }
            return $randomString;
        }
        
        public function getTaskIds($group_id){
            try {
                $db = initDb();
                $getIds = $db->prepare("SELECT id FROM tasks WHERE group_id = :group_id");
                $getIds->bindParam(":group_id", $group_id, PDO::PARAM_INT);
                $getIds->execute();
                $ids = $getIds->fetchAll(PDO::FETCH_ASSOC);
                return $ids;
            } catch(PDOException $e){
                echo '{"error":{"text":'. $e->getMessage() .'}}'; 
            }
        }

        public function getTasks($user_id, $type){
            $groupClass = new group();
            $group_id = $groupClass->getGroupIdByUser($user_id);
            switch($type){
                case 'current':
                    $tasks = $this->getCurrentTask($user_id, $group_id);
                break;
                case 'past':
                    $tasks = $this->getPastTasks($user_id, $group_id);
                break;
            }
            return json_encode($tasks);
        }

        public function getTaskAnswers($user_id, $task_id){
            include_once("user.php");
            $userClass = new user();
            $role = $userClass->getRole($user_id);
            try {                
                $db = initDb();             
                $sql = "SELECT id, user_id, answer, likes, posted, visibility FROM task_answers WHERE task_id = :task_id";         
                if($role == 2){
                    $sql = $sql." ORDER BY posted DESC";                                                                  
                } else {
                    $sql = $sql." ORDER BY likes DESC";
                }  
                $getTaskAnswers = $db->prepare($sql);              
                $getTaskAnswers->bindParam(":task_id", $task_id, PDO::PARAM_INT);
                $getTaskAnswers->execute();             
                $answers = array();
                while($answer = $getTaskAnswers->fetch(PDO::FETCH_ASSOC)){
                    if(($this->isLiked($answer['id'], $user_id)) || ($answer['user_id'] == $user_id)){
                        $answer['likebutton'] = false;
                    } else {                        
                        $answer['likebutton'] = true;
                    }                                                                                    
                    if($role == 1){                            
                        if(($answer['visibility'] == 1) || $answer['user_id'] == $user_id){                            
                            array_push($answers, $answer);
                        }               
                    } else {
                        array_push($answers, $answer);
                    }
                }
                return json_encode($answers);
            } catch(PDOException $e){
                echo '{"error":{"text":'. $e->getMessage() .'}}'; 
            }
        }

        private function isLiked($answer_id, $user_id){
            try {
                $db = initDb();
                $isLiked = $db->prepare("SELECT id FROM task_answers_likes WHERE answer_id = :answer_id AND user_id = :user_id");
                $isLiked->bindParam(":answer_id", $answer_id, PDO::PARAM_INT);
                $isLiked->bindParam(":user_id", $user_id, PDO::PARAM_INT);
                $isLiked->execute();
                $result = $isLiked->fetchColumn();
                if($result){
                    return true;
                } else {
                    return false;
                }
            } catch (PDOException $e){
                echo '{"error":{"text":'. $e->getMessage() .'}}'; 
            }
        }

        public function submitLike($answer_id, $user_id){
            try {
                $db = initDb();
                $addLike = $db->prepare("INSERT INTO task_answers_likes (answer_id, user_id) VALUES (:answer_id, :user_id)");
                $addLike->bindParam(":answer_id", $answer_id, PDO::PARAM_INT);
                $addLike->bindParam(":user_id", $user_id, PDO::PARAM_INT);
                $addLike->execute();                
                return $this->addLike($answer_id);                                
            } catch(PDOException $e){
                echo '{"error":{"text":'. $e->getMessage() .'}}'; 
            }
        }

        public function getTaskImage($imagePath){
            $path = "images/tasks/".$imagePath;            
            $image = file_get_contents($path);
            return $image ? 'data:image/jpeg;base64,' . base64_encode($image) : "noimage";     
        }

        private function addLike($answer_id){
            try {   
                $db = initDb();
                $getLikes = $db->prepare("SELECT likes FROM task_answers WHERE id = :answer_id");
                $getLikes->bindParam(":answer_id", $answer_id, PDO::PARAM_INT);
                $getLikes->execute();
                $oldValue = $getLikes->fetchColumn();
                $newValue = $oldValue + 1;
                $updateLikes = $db->prepare("UPDATE task_answers SET likes = :likes WHERE id = :answer_id");
                $updateLikes->bindParam(":likes", $newValue, PDO::PARAM_INT);
                $updateLikes->bindParam(":answer_id", $answer_id, PDO::PARAM_INT);
                $updateLikes->execute();
                return $newValue;   
            } catch (PDOException $e){
                echo '{"error":{"text":'. $e->getMessage() .'}}'; 
            }
        }
        
        private function getCurrentTask($user_id, $group_id){
            try {
                date_default_timezone_set('Europe/Helsinki');                                
                $db = initDb();                
                $getCurrentTask = $db->prepare("SELECT id, task_name, task_desc, startdate, enddate, image FROM tasks WHERE group_id = :group_id AND active = 1 AND NOW() between startdate and enddate");
                $getCurrentTask->bindParam(":group_id", $group_id, PDO::PARAM_INT);
                $getCurrentTask->execute();
                $currentTask = $getCurrentTask->fetch(PDO::FETCH_ASSOC);
                if(!$currentTask){
                    return [];
                }
                if($this->isTaskCompleted($currentTask['id'], $user_id)){
                    return [
                        "id" => $currentTask['id'],
                        "completed" => true
                    ];
                } else {
                    $currentTask['completed'] = false;
                    return [$currentTask];
                }
            } catch(PDOException $e){
                echo '{"error":{"text":'. $e->getMessage() .'}}'; 
            }
        }

        private function getPastTasks($user_id, $group_id){
            try {
                $db = initDb();
                $getTasks = $db->prepare("SELECT id, task_name, task_desc, startdate, enddate FROM tasks WHERE group_id = :group_id AND active = 1 AND NOW() > enddate ORDER BY enddate ASC");
                $getTasks->bindParam(":group_id", $group_id, PDO::PARAM_INT);
                $getTasks->execute();
                $tasks = array();
                while($task = $getTasks->fetch(PDO::FETCH_ASSOC)){
                    if($this->isTaskCompleted($task['id'], $user_id)){
                        $task['completed'] = true;
                    } else {
                        $task['completed'] = false;
                    }
                    array_push($tasks, $task);
                }                
                return $tasks;
            } catch(PDOException $e){
                echo '{"error":{"text":'. $e->getMessage() .'}}'; 
            }
        }

        private function isTaskCompleted($task_id, $user_id){
            try {
                $db = initDb();
                $isTaskCompleted = $db->prepare("SELECT task_id, user_id FROM task_answers WHERE task_id = :task_id AND user_id = :user_id");
                $isTaskCompleted->bindParam(":task_id", $task_id, PDO::PARAM_INT);
                $isTaskCompleted->bindParam(":user_id", $user_id, PDO::PARAM_INT);
                $isTaskCompleted->execute();
                if ($isTaskCompleted->rowCount() > 0){
                    return true;
                } else {
                    return false;
                }                
            } catch(PDOException $e){
                echo '{"error":{"text":'. $e->getMessage() .'}}'; 
            }
        }

        public function completeTask($user_id, $task_id, $message, $feedback, $visibility){
            try {
                $db = initDb();
                $completeTask = $db->prepare("INSERT INTO task_answers (task_id, user_id, answer, feedback, visibility) VALUES (:task_id, :user_id, :answer, :feedback, :visibility)");
                $completeTask->bindParam(":task_id", $task_id, PDO::PARAM_INT);
                $completeTask->bindParam(":user_id", $user_id, PDO::PARAM_INT);
                $completeTask->bindParam(":answer", $message, PDO::PARAM_STR);
                $completeTask->bindParam(":feedback", $feedback, PDO::PARAM_STR);
                $completeTask->bindParam(":visibility", $visibility, PDO::PARAM_INT);
                $completeTask->execute();
                return "completed";
            } catch(PDOException $e){
                echo '{"error":{"text":'. $e->getMessage() .'}}'; 
            }
        }

        public function deleteTask($task_id){
            try {
                $db = initDb();
                $deleteTask = $db->prepare("DELETE FROM tasks WHERE id = :task_id");
                $deleteTask->bindParam(":task_id", $task_id, PDO::PARAM_INT);
                $deleteTask->execute();
            } catch (PDOException $e){
                echo '{"error":{"text":'. $e->getMessage() .'}}'; 
            }
        }

        public function getTaskDates($group_id){
            try {                
                $db = initDb();
                date_default_timezone_set('Europe/Helsinki');
                $getTaskDates = $db->prepare("SELECT startdate, enddate FROM tasks WHERE group_id = :group_id AND active = 1");
                $getTaskDates->bindParam(":group_id", $group_id, PDO::PARAM_INT);
                $getTaskDates->execute();
                $dates = array();
                while($result = $getTaskDates->fetch(PDO::FETCH_ASSOC)){
                    $date = array();
                    $date['from'] = (new \DateTime($result['startdate']))->format('d.m.Y');                                                                               
                    $date['to'] = (new \DateTime($result['enddate']))->format('d.m.Y');            
                    array_push($dates, $date);                                        
                }
                return json_encode($dates);
            } catch (PDOException $e){
                echo '{"error":{"text":'. $e->getMessage() .'}}'; 
            }
        }

    }
?>