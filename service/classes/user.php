<?php
    include_once("mailer.php");
    class user {

        public function login($username, $password) {
            $response = [
                "status" => false
            ];
            try {
                $conn = initDb();
                $login = $conn->prepare("SELECT password, confirmed FROM users WHERE username = :username");
                $login->bindParam(":username", $username, PDO::PARAM_STR);
                $login->execute();
                $data = $login->fetch();
                if ($login->rowCount() > 0) {
                    if (password_verify($password, $data['password'])) {
                        if ($data['confirmed'] == "1") {
                            $result = [];
                            $getUserData = $conn->prepare("SELECT id, firstname, sex, birthyear, activity, lastname, username, email, role, profile_image FROM users WHERE username = :username");
                            $getUserData->bindParam(":username", $username, PDO::PARAM_STR);
                            $getUserData->execute();
                            $result['user'] = $getUserData->fetch(PDO::FETCH_ASSOC); 
                            $result['pagelist'] = $this->getNavigation($result['user']['role']);       
                            if($result['user']['profile_image'] != ""){
                                $result['user']['profile_image'] = $this->imageToBase64($result['user']['profile_image']);
                            }                     
                            $_SESSION['id'] = $result['user']['id'];
                            $_SESSION['role'] = $result['user']['role'];
                            $response["status"] = true;
                            $response["data"] = $result;
                        } else {
                            $response["error"] = "unconfirmed";
                        }
                    } else {
                        $response["error"] = "wrongpass";
                    }
                } else {
                    $response["error"] = "nouser";
                }
            }
            catch (PDOException $e) {
                $response['error'] = $e->getMessage();
            }
            echo json_encode($response);
        }

        public function uploadUserImage($user_id, $file){
            try {
                $new_image_name = $this->generateRandomString()."-".urldecode($file["file"]["name"]);
                $target_dir = "images/".$new_image_name;                    
                if(move_uploaded_file($file["file"]["tmp_name"], $target_dir)){
                    $this->updateUserImage($user_id, $new_image_name);                    
                } else {
                    return "failed";
                }            
            } catch (PDOException $e){
                echo '{"error":{"text":' . $e->getMessage() . '}}';
            }
        }
        
        public function getGroups($user_id){
            try {
                $conn = initDb();
                $getGroups = $conn->prepare("SELECT id, groups_name FROM groups WHERE groups_leader = :groups_leader");
                $getGroups->bindParam(":groups_leader", $user_id, PDO::PARAM_INT);
                $getGroups->execute();
                $groups = $getGroups->fetchAll(PDO::FETCH_ASSOC);
                return json_encode($groups);
            } catch(PDOException $e){
                echo '{"error":{"text":' . $e->getMessage() . '}}';
            }
        }

        public function register($email) {
            try {
                if($this->isEmailTaken($email)){
                    return "inuse";
                }
                $conn = initDb();
                $token = md5(rand(0, 1000));
                $register = $conn->prepare("INSERT INTO users (email, token) VALUES (:email, :token)");
                $register->bindParam(":email", $email, PDO::PARAM_STR);
                $register->bindParam(":token", $token, PDO::PARAM_STR);
                $register->execute();                                                                        
                $mailerClass = new mailer();
                $mailerClass->sendUserVerification($email, $token);
                return "registered";
            }
            catch (PDOException $e) {
                echo '{"error":{"text":' . $e->getMessage() . '}}';
            }
        }

        public function confirmAccount($email, $token){
            try {
                $conn = initDb();
                $confirmAccount = $conn->prepare("UPDATE users SET confirmed = 1 WHERE email = :email AND token = :token");
                $confirmAccount->bindParam(":email", $email, PDO::PARAM_STR);
                $confirmAccount->bindParam(":token", $token, PDO::PARAM_STR);
                $confirmAccount->execute();
                $this->createUser($email);
                return "confirmed";
            } catch (PDOException $e) {
                echo '{"error":{"text":' . $e->getMessage() . '}}';
            }
        }
        
        public function submitUserChanges($user) {
            return json_encode($user);
            try {
                $conn = initDb();
                $changeDetails = $conn->prepare("UPDATE users SET firstname = :firstname, lastname = :lastname, sex = :sex, birthyear = :birthyear, activity = :activity WHERE id = :user_id AND username = :username");                                                         
                $changeDetails->bindParam(":firstname", $user['firstname'], PDO::PARAM_STR);
                $changeDetails->bindParam(":lastname", $user['lastname'], PDO::PARAM_STR);
                $changeDetails->bindParam(":sex", $user['sex'], PDO::PARAM_INT);
                $changeDetails->bindParam(":birthyear", $user['birthyear'], PDO::PARAM_STR);
                $changeDetails->bindParam(":activity", $user['activity'], PDO::PARAM_INT);
                $changeDetails->bindParam(":user_id", $user['user_id'], PDO::PARAM_INT);
                $changeDetails->bindParam(":username", $user['username'], PDO::PARAM_STR);
                return $changeDetails->execute();
            }
            catch (PDOException $e) {
                echo '{"error":{"text":' . $e->getMessage() . '}}';
            }
        }

        public function getFullName($user_id){
            try {
                $conn = initDb();
                $getFullName = $conn->prepare("SELECT firstname, lastname FROM users WHERE id = :user_id");
                $getFullName->bindParam(":user_id", $user_id, PDO::PARAM_INT);
                $getFullName->execute();
                $result = $getFullName->fetch(PDO::FETCH_ASSOC);                
                return $result;
            } catch(PDOException $e) {
                echo '{"error":{"text":' . $e->getMessage() . '}}';
            }
        }

        public function getUserEmail($user_id){
            try {
                $conn = initDb();
                $getUserEmail = $conn->prepare("SELECT email FROM users WHERE id = :user_id");
                $getUserEmail->bindParam(":user_id", $user_id, PDO::PARAM_INT);
                $getUserEmail->execute();
                $email = $getUserEmail->fetchColumn();                
                return $email;
            } catch(PDOException $e) {
                echo '{"error":{"text":' . $e->getMessage() . '}}';
            }
        }

        public function getUserImage($user_id){
            try {
                $conn = initDb();
                $getUserImage = $conn->prepare("SELECT profile_image FROM users WHERE id = :user_id");
                $getUserImage->bindParam(":user_id", $user_id, PDO::PARAM_INT);
                $getUserImage->execute();
                $userImage = $getUserImage->fetchColumn();                
                return $this->imageToBase64($userImage);
            } catch(PDOException $e) {
                echo '{"error":{"text":' . $e->getMessage() . '}}';
            }
        }

        public function getRole($user_id) {
            try {
                $conn = initDb();
                $getRole = $conn->prepare("SELECT role FROM users WHERE id = :user_id");
                $getRole->bindParam(":user_id", $user_id, PDO::PARAM_INT);
                $getRole->execute();
                return $getRole->fetchColumn();                
            }
            catch (PDOException $e) {
                echo '{"error":{"text":' . $e->getMessage() . '}}';
            }
        }

        public function isUserInGroup($user_id){
            try {  
                $conn = initDb();
                $isUserInGroup = $conn->prepare("SELECT user_id FROM in_group WHERE user_id = :user_id");
                $isUserInGroup->bindParam(":user_id", $user_id, PDO::PARAM_INT);
                $isUserInGroup->execute();
                $result = $isUserInGroup->fetch(PDO::FETCH_ASSOC);
                if($result){
                    return true;
                } else {
                    return false;
                }
            } catch(PDOException $e){
                echo '{"error":{"text":' . $e->getMessage() . '}}';
            }
        }

        public function getOneSignalId($user_id) {
            try {  
                $db = initDb();
                $getOneSignalId = $db->prepare("SELECT onesignal_id FROM users WHERE id = :user_id");
                $getOneSignalId->bindParam(":user_id", $user_id, PDO::PARAM_INT);
                $getOneSignalId->execute();
                return $getOneSignalId->fetchColumn();
            } catch (PDOException $e){
                echo '{"error":{"text":'. $e->getMessage() .'}}'; 
            }
        }

        public function saveOneSignalId($user_id, $oneSignalId){
            try {
                $db = initDb();
                $saveOneSignalId = $db->prepare("UPDATE users SET onesignal_id = :onesignalid WHERE id = :user_id");
                $saveOneSignalId->bindParam(":onesignalid", $oneSignalId, PDO::PARAM_STR);
                $saveOneSignalId->bindParam(":user_id", $user_id, PDO::PARAM_INT);
                $saveOneSignalId->execute();                
            } catch (PDOException $e){
                echo '{"error":{"text":'. $e->getMessage() .'}}';  
            }
        }

        public function isAccountConfirmed($email) {
            try {
                $conn = initDb();
                $isAccountConfirmed = $conn->prepare("SELECT confirmed FROM users WHERE email = :email");
                $isAccountConfirmed->bindParam(":email", $email, PDO::PARAM_STR);
                $isAccountConfirmed->execute();
                $row  = $isAccountConfirmed->fetch();
                return $row['confirmed'];                
            }
            catch (PDOException $e) {
                echo '{"error":{"text":' . $e->getMessage() . '}}';
            }
        }

        public function getMessages($user_id){
            try {
                $conn = initDb();
                $getMessages = $conn->prepare("SELECT id, user_id, message, visited, posted FROM task_messages WHERE user_id = :user_id ORDER BY posted DESC");
                $getMessages->bindParam(":user_id", $user_id, PDO::PARAM_INT);
                $getMessages->execute();
                $messages = $getMessages->fetchAll(PDO::FETCH_ASSOC);
                return json_encode($messages);
            } catch (PDOException $e){
                echo '{"error":{"text":' . $e->getMessage() . '}}';
            }
        }

        private function getNavigation($role) {                    
            switch($role){
                case '0':
                case '1':
                    $navigation = array(                        
                        array('text' => 'Etusivu', 'title' => 'Etusivu', 'uri' => 'index.html','class' => 'fa-home'),
                        array('text' => 'Profiili', 'title' => 'Profiili', 'uri' => 'profile.html','class' => 'fa-user'),      
                        array('text' => 'Lähetä viesti', 'title' => 'Lähetä viesti', 'uri' => 'message.html', 'class' => 'fa-commenting-o'),     
                        array('text' => 'Kirjaudu ulos', 'title' => 'Kirjaudu ulos', 'onclick' => "logout()", 'class' => 'fa-sign-out')
                    );
                break;
                case '2':
                    $navigation = array(                        
                        array('text' => 'Etusivu', 'title' => 'Etusivu', 'uri' => 'index.html','class' => 'fa-home'),
                        array('text' => 'Profiili','title' => 'Profiili', 'uri' => 'profile.html','class' => 'fa-user'),         
                        array('text' => 'Hallintapaneeli', 'title' => 'Hallintapaneeli','uri' => 'admin.html', 'class' => 'fa-desktop'),
                        array('text' => 'Lähetä viesti', 'title' => 'Lähetä viesti', 'uri' => 'message.html', 'class' => 'fa-commenting-o'),
                        array('text' => 'Uusi tehtävä', 'title' => 'Uusi tehtävä','uri' => 'task.html', 'class' => 'fa-plus'),
                        array('text' => 'Viestit', 'title' => 'Viestit','uri' => 'messages.html', 'class' => 'fa-comments'),
                        array('text' => 'Kirjaudu ulos', 'title' => 'Kirjaudu ulos', 'onclick' => "logout()",'class' => 'fa-sign-out')
                    );
                break;
            }                
            return json_encode($navigation);                     
        }
        
        public function isEmailTaken($email) {
            try {
                $conn = initDb();
                $isEmailTaken = $conn->prepare("SELECT email FROM users WHERE email = :email");
                $isEmailTaken->bindParam(":email", $email, PDO::PARAM_STR);
                $isEmailTaken->execute();
                if ($row = $isEmailTaken->fetch(PDO::FETCH_ASSOC)) {
                    return true;
                } else {
                    return false;
                }            
            }
            catch (PDOException $e) {
                echo '{"error":{"text":' . $e->getMessage() . '}}';
            }
        }

        private function imageToBase64($imageUri){       
            $path = "images/".$imageUri;            
            $image = file_get_contents($path);
            $base64 = 'data:image/jpeg;base64,' . base64_encode($image);
            return $base64;
        }

        private function removeUserImage($imageUri){
            //to do 
        } 
        
        private function updateUserImage($user_id, $new_image_name){
            try {
                $conn = initDb();
                $updateUserImage = $conn->prepare("UPDATE users SET profile_image = :profile_image WHERE id = :user_id");
                $updateUserImage->bindParam(":profile_image", $new_image_name, PDO::PARAM_STR);
                $updateUserImage->bindParam(":user_id", $user_id, PDO::PARAM_INT);
                $updateUserImage->execute();
                return "uploaded";
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

        private function createUser($email){                                    
            $username = substr($email,0,3).$this->generateRandomString();
            $password = substr($email,0,3).$this->generateRandomString();
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            try {
                $conn = initDb();
                $createUser = $conn->prepare("UPDATE users SET username = :username, password = :password WHERE email = :email");
                $createUser->bindParam(":username", $username, PDO::PARAM_STR);
                $createUser->bindParam(":password", $hashed_password, PDO::PARAM_STR);
                $createUser->bindParam(":email", $email, PDO::PARAM_STR);
                $createUser->execute();                         
                $mailer = new mailer;
                $sendMail = $mailer->sendUserDetails($email, $username, $password);
                return "usercreated";
            } catch (PDOException $e){
                echo '{"error":{"text":' . $e->getMessage() . '}}';
            }
        }
        
    }
?>