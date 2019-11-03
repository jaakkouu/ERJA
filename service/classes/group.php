<?php
    class group {

        public function acceptRequest($user_id, $group_id){
            try {                
                if(!$this->requestExists($user_id, $group_id)){  
                    return "exists";         
                } else {
                    $this->addUser($user_id, $group_id);  
                    $this->deleteRequest($user_id, $group_id); 
                    return "added";
                }  
            } catch(PDOException $e){
                echo '{"error":{"text":' . $e->getMessage() . '}}';
            }
        }

        private function deleteRequest($user_id, $group_id){
            try {
                $conn = initDb();
                $deleteRequest = $conn->prepare("DELETE FROM requests WHERE user_id = :user_id AND group_id = :group_id");
                $deleteRequest->bindParam(":user_id", $user_id, PDO::PARAM_INT);
                $deleteRequest->bindParam(":group_id", $group_id, PDO::PARAM_INT);
                $deleteRequest->execute();
            } catch(PDOException $e){
                echo '{"error":{"text":' . $e->getMessage() . '}}';
            }
        }

        public function addUser($user_id, $group_id) {
            try {
                $conn = initDb();
                $addUserToGroup = $conn->prepare("INSERT INTO in_group (user_id, group_id) VALUES (:user_id, :group_id)");
                $addUserToGroup->bindParam(":user_id", $user_id, PDO::PARAM_INT);
                $addUserToGroup->bindParam(":group_id", $group_id, PDO::PARAM_INT);
                $addUserToGroup->execute();
            }
            catch (PDOException $e) {
                echo '{"error":{"text":' . $e->getMessage() . '}}';
            }
        }
        public function removeUser($user_id, $group_id) {
            try {
                $conn = initDb();
                $removeUserFromGroup = $conn->prepare("DELETE FROM in_group WHERE user_id = :user_id AND group_id = :group_id");
                $removeUserFromGroup->bindParam(":user_id", $user_id, PDO::PARAM_INT);
                $removeUserFromGroup->bindParam(":group_id", $group_id, PDO::PARAM_INT);
                $removeUserFromGroup->execute();
                return "deleted";                
            }
            catch (PDOException $e) {
                echo '{"error":{"text":' . $e->getMessage() . '}}';
            }
        }
        public function renameGroup($group_id, $groupName) {
            try {
                $conn = initDb();
                $renameGroup = $conn->prepare("UPDATE groups SET groups_name = :name WHERE id = :group_id");
                $renameGroup->bindParam(":name", $groupName, PDO::PARAM_STR);
                $renameGroup->bindParam(":group_id", $group_id, PDO::PARAM_INT);
                $renameGroup->execute();
            }
            catch (PDOException $e) {
                echo '{"error":{"text":' . $e->getMessage() . '}}';
            }
        }
        public function deleteGroup($group_id, $group_leader) {
            try {
                $conn = initDb();
                $deleteGroup = $conn->prepare("DELETE FROM groups WHERE id = :group_id AND groups_leader = :groups_leader");
                $deleteGroup->bindParam(":group_id", $group_id, PDO::PARAM_INT);
                $deleteGroup->bindParam(":groups_leader", $group_leader, PDO::PARAM_INT);
                $deleteGroup->execute();
                return "deleted";
            }
            catch (PDOException $e) {
                echo '{"error":{"text":' . $e->getMessage() . '}}';
            }
        }
        public function sendGroupRequest($user_id, $groups_name, $groups_password){
            try {                
                $groupId = $this->getGroupIdByName($groups_name);
                if(!$groupId){
                    return "nogroup";
                } else {
                    if($passwordOk = $this->checkForGroupPassword($groupId, $groups_password)){
                        if(!$this->requestExists($user_id, $groupId)){
                            $this->createGroupRequest($user_id, $groupId);
                            $group_leader = $this->getGroupLeader($groupId);                            
                            return "ok";
                        } else {
                            return "requestexists";
                        }
                    } else {
                        return "wrongpass";
                    }
                }            
            } catch (PDOException $e) {
                echo '{"error":{"text":' . $e->getMessage() . '}}';
            }
        }

        public function getGroupRequests($group_id){
            include("classes/user.php");
            $userClass = new user(); 
            try {
                $conn = initDb();
                $getGroupRequests = $conn->prepare("SELECT user_id FROM requests WHERE group_id = :group_id");                
                $getGroupRequests->bindParam(":group_id", $group_id, PDO::PARAM_INT);
                $getGroupRequests->execute();
                $requests = array();                   
                while($request = $getGroupRequests->fetch(PDO::FETCH_ASSOC)){                    
                    $request['email'] = $userClass->getUserEmail($request['user_id']);
                    array_push($requests, $request);
                }
                return json_encode($requests);                                                                
            } catch(PDOException $e){
                echo '{"error":{"text":' . $e->getMessage() . '}}';
            }
        }
        public function createGroupRequest($user_id, $group_id){
            try {
                $conn = initDb();
                $createGroupRequest = $conn->prepare("INSERT INTO requests (user_id, group_id) VALUES (:user_id, :group_id)");
                $createGroupRequest->bindParam(":user_id", $user_id, PDO::PARAM_INT);
                $createGroupRequest->bindParam(":group_id", $group_id, PDO::PARAM_INT);
                $createGroupRequest->execute();
            } catch(PDOException $e){
                echo '{"error":{"text":' . $e->getMessage() . '}}';
            }
        }        
        private function requestExists($user_id, $group_id){
            try {
                $conn = initDb();
                $requestExists = $conn->prepare("SELECT * FROM requests WHERE user_id = :user_id AND group_id = :group_id");
                $requestExists->bindParam(":user_id", $user_id, PDO::PARAM_INT);
                $requestExists->bindParam(":group_id", $group_id, PDO::PARAM_INT);
                $requestExists->execute();
                $result = $requestExists->fetch(PDO::FETCH_ASSOC);
                if($result){
                    return true;
                } else {
                    return false;
                }
            } catch(PDOException $e){
                echo '{"error":{"text":' . $e->getMessage() . '}}';
            }
        }

        private function checkForGroupPassword($groupId, $groups_password){
            try {
                $conn = initDb();
                $checkPass = $conn->prepare("SELECT groups_password FROM groups WHERE id = :groupId AND groups_password = :groups_password");
                $checkPass->bindParam(":groupId", $groupId, PDO::PARAM_INT);
                $checkPass->bindParam(":groups_password", $groups_password, PDO::PARAM_STR);
                $checkPass->execute();   
                $password = $checkPass->fetchColumn();
                if($password === $groups_password){
                    return true;
                } else {
                    return false;
                }
                
            } catch (PDOException $e){
                echo '{"error":{"text":' . $e->getMessage() . '}}';
            }
        }
        private function getGroupIdByName($groups_name){
            try {
                $conn = initDb();
                $getGroupId = $conn->prepare("SELECT id FROM groups WHERE groups_name = :groups_name");
                $getGroupId->bindParam(":groups_name", $groups_name, PDO::PARAM_STR);
                $getGroupId->execute();
                $groupId = $getGroupId->fetchColumn();
                return $groupId;
            } catch (PDOException $e) {
                echo '{"error":{"text":' . $e->getMessage() . '}}';
            }
        }

        public function getPlayerIdsByGroup($group_id){                        
            try {
                $users = $this->getGroupUserIds($group_id);
                $playerIds = array();
                $db = initDb();
                foreach($users as $id){
                    $getPlayerId = $db->prepare("SELECT onesignal_id FROM users WHERE id = :user_id");
                    $getPlayerId->bindParam(":user_id", $id, PDO::PARAM_INT);
                    $getPlayerId->execute();
                    $playerId = $getPlayerId->fetchColumn();
                    if($playerId){
                        array_push($playerIds, $playerId);
                    }
                }   
                return $playerIds;           
            } catch (PDOExcepction $e){
                echo '{"error":{"text":'. $e->getMessage() .'}}'; 
            }  
        }
        
        public function getGroupUserIds($group_id) {
            try {
                $conn = initDb();
                $getList = $conn->prepare("SELECT user_id FROM in_group WHERE group_id = :group_id");
                $getList->bindParam(":group_id", $group_id, PDO::PARAM_INT);
                $getList->execute();
                $IdList = $getList->fetch(PDO::FETCH_ASSOC);
                return $IdList;           
            }
            catch (PDOException $e) {
                echo '{"error":{"text":' . $e->getMessage() . '}}';
            }
        }

        public function listUsersByGroup($group_id) {
            try {
                $conn = initDb();
                $getList = $conn->prepare("SELECT user_id FROM in_group WHERE group_id = :group_id");
                $getList->bindParam(":group_id", $group_id, PDO::PARAM_INT);
                $getList->execute();
                $IdList = $getList->fetchAll(PDO::FETCH_ASSOC);
                $userList = array();
                foreach ($IdList as $item) {
                    $getGroupsUserList = $conn->prepare("SELECT id, firstname, lastname, email FROM users WHERE id = :user_id");
                    $getGroupsUserList->bindParam(":user_id", $item['user_id']);
                    $getGroupsUserList->execute();
                    array_push($userList, $result = $getGroupsUserList->fetch(PDO::FETCH_ASSOC));
                }
                return json_encode($userList);
            }
            catch (PDOException $e) {
                echo '{"error":{"text":' . $e->getMessage() . '}}';
            }
        }
        public function createGroup($user_id, $group_name, $group_password) {
            try {
                $conn = initDb();
                $createGroup = $conn->prepare("INSERT INTO groups (groups_name, groups_password, groups_leader) VALUES (:groups_name, :groups_password, :groups_leader)");
                $createGroup->bindParam(":groups_name", $group_name, PDO::PARAM_STR);
                $createGroup->bindParam(":groups_password", $group_password, PDO::PARAM_STR);
                $createGroup->bindParam(":groups_leader", $user_id, PDO::PARAM_INT);
                $createGroup->execute();
            }
            catch (PDOException $e) {
                echo '{"error":{"text":' . $e->getMessage() . '}}';
            }
        }
        
        public function getGroupIdByUser($user_id) {
            try {
                $conn = initDb();
                $getGroupId = $conn->prepare("SELECT group_id FROM in_group WHERE user_id = :user_id");
                $getGroupId->bindParam(":user_id", $user_id, PDO::PARAM_INT);
                $getGroupId->execute();
                return $getGroupId->fetchColumn();
            } catch (PDOException $e) {
                echo '{"error":{"text":' . $e->getMessage() . '}}';
            }
        }

        public function getGroupLeader($group_id){
            try {
                $conn = initDb();
                $getGroupLeaderId = $conn->prepare("SELECT groups_leader FROM groups WHERE id = :group_id");
                $getGroupLeaderId->bindParam(":group_id", $group_id, PDO::PARAM_INT);
                $getGroupLeaderId->execute();
                $groupLeaderId = $getGroupLeaderId->fetchColumn();
                return $groupLeaderId;
            } catch (PDOException $e) {
                echo '{"error":{"text":' . $e->getMessage() . '}}';
            }
        }

        public function getGroupLeaderImage($group_id){
            try {
                $conn = initDb();
                //get group leader id
                $groupLeaderId = $this->getGroupLeader($group_id);
                $getImage = $conn->prepare("SELECT profile_image FROM users WHERE id = :groups_leader");
                $getImage->bindParam(":groups_leader", $groupLeaderId, PDO::PARAM_STR);
                $getImage->execute();
                $image = $getImage->fetchColumn();
                return $image;
            } catch (PDOException $e) {
                echo '{"error":{"text":' . $e->getMessage() . '}}';
            }
        }

        public function getGroupName($group_id){
            try {
                $conn = initDb();
                $getGroupName = $conn->prepare("SELECT groups_name FROM groups WHERE id = :group_id");
                $getGroupName->bindParam(":group_id", $group_id, PDO::PARAM_INT);
                $getGroupName->execute();
                return $getGroupName->fetchColumn();
            }
            catch (PDOException $e) {
                echo '{"error":{"text":' . $e->getMessage() . '}}';
            }
        }

        public function getGroupsTasks($group_id) {
            try {
                $conn = initDb();
                $getGroupsTasks = $conn->prepare("SELECT id, task_name, task_desc, total, startdate, enddate FROM tasks WHERE group_id = :groups_id");
                $getGroupsTasks->bindParam(":groups_id", $group_id, PDO::PARAM_INT);
                $getGroupsTasks->execute();
                return json_encode($getGroupsTasks->fetchAll(PDO::FETCH_ASSOC));
            }
            catch (PDOException $e) {
                echo '{"error":{"text":' . $e->getMessage() . '}}';
            }
        }
       
    }
?>