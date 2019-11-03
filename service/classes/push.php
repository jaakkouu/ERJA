<?php 
    
    class push {
        
        private function getAppId(){
            $app_id = "794eb6bc-6a5a-4073-990d-a98c59b1bda8";
            return $app_id;
        }

        public function compilePush($id, $type, $message, $scheduler = null, $scheduledTime = null){

            $fields = array(
                'app_id' => $this->getAppId(),
                'include_player_ids' => $this->getPlayerIds($id, $type),
                'data' => array("foo" => "bar"),
                'contents' => $this->getContents($type, $message)
            );

            if ($scheduler === null || $scheduledTime === null) {
                $scheduler = false;
            }

            if($scheduler){ 
                $fields['send_after'] = $scheduledTime;
                $fields['delayed_option'] = "timezone";
                $fields['delivery_time_of_day'] = "9:00AM";                               
            }

            return $this->sendPush($fields);
        }

        private function getScheduledTime(){
            
        }
        
        private function getPlayerIds($id, $type){
            include_once($type.".php");
            switch($type){
                case 'group':
                    $groupClass = new group();
                    $data = $groupClass->getPlayerIdsByGroup($id);
                break;
                case 'user':
                    $userClass = new user();
                    $data = array($userClass->getOneSignalId($id));
                break;
            }
            return $data;
        }

        private function getContents($type, $message){
            $messages = array( 
                "group" => array(                    
                    "newtask" => "Olet saanut uuden tehtävän!",
                    "taskends" => "Tehtäväsi umpeutuu pian!",
                ),
                "user" => array(
                    "newmessage" => "Sinulla on uusi viesti!",  
                )
            );
            return array("en" => $messages[$type][$message]);
        }

        private function sendPush($fields){ 
            $fields = json_encode($fields);
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, "https://onesignal.com/api/v1/notifications");
            curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json; charset=utf-8',
                                                       'Authorization: Basic NGEwMGZmMjItY2NkNy0xMWUzLTk5ZDUtMDAwYzI5NDBlNjJj'));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
            curl_setopt($ch, CURLOPT_HEADER, FALSE);
            curl_setopt($ch, CURLOPT_POST, TRUE);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $fields);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
            $response = curl_exec($ch);
            curl_close($ch);
            return $response;
        }
          
    }

?>