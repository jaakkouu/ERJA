<?php
    header("Access-Control-Allow-Origin: *");
    header("Access-Control-Allow-Methods: POST, GET");
    $f = $_SERVER['REQUEST_METHOD'] === 'POST' ? $_POST['function'] : $_GET['function'];
    $a = $_SERVER['REQUEST_METHOD'] === 'POST' ? $_POST['action'] : $_GET['action'];
    $r = $_SERVER['REQUEST_METHOD'] === 'POST' ? $_POST : $_GET;
    include("db.php");
    include("classes/" . $f . ".php");
    switch($f){
        case 'task': $class = new task(); break;
        case 'group': $class = new group(); break;
        case 'user': $class = new user(); break;
        case 'message': $class = new message(); break;
        default: die('Error');
    }
    if ($f === "task") {
        switch ($a) {
            case 'getTask':
                $data = $class->getTasks($r['user_id'], $r['type']);
                break;
            case 'completeTask':
                $data = $class->completeTask($r['user_id'], $r['task_id'], $r['message'], $r['feedback'], $r['visibility']);
                break;
            case 'getTaskAnswers':
                $data = $class->getTaskAnswers($r['user_id'], $r['task_id']);
                break;
            case 'getTaskDates':
                $data = $class->getTaskDates($r['group_id']);
                break;
            case 'getTaskImage':
                $data = $class->getTaskImage($r['imagePath']);                
                break;
            case 'submitLike':
                $data = $class->submitLike($r['answer_id'], $r['user_id']);
                break;
            case 'create':
                $data = $class->createTask($r['group_id'], $r['name'], $r['desc'], $r['amount'], $r['startdate'], $r['enddate'], $r['image']);
                break;                
            case 'delete':
                $data = $class->deleteTask($r['task_id']);
                break;
            case 'uploadTaskImage':
                $data = $class->uploadTaskImage($r['myRandomId'], $_FILES);
                break;
        }
    } else if ($f === "group") {
        switch ($a) {
            case 'getGroupsTasks':
                $data = $class->getGroupsTasks($r['group_id']);
                break; 
            case 'getGroupNameByUserId':
                $data = $class->getGroupName($class->getGroupIdByUser($r['user_id']));
                break;                                               
            case 'createGroup':
                $data = $class->createGroup($r['id'], $r['group_name'], $r['group_password']);
                break;
            case 'addUser':
                $data = $class->addUser($r['user_id'], $r['group_id']);
                break;
            case 'removeUser':
                $data = $class->removeUser($r['user_id'], $r['group_id']);                    
                break;
            case 'listUsersByGroup':
                $data = $class->listUsersByGroup($r['group_id']);
                break;
            case 'deleteGroup':                                        
                $data = $class->deleteGroup($r['group_id'], $r['user_id']);
                break;
            case 'renameGroup':
                $data = $class->renameGroup($r['group_id']);
                break;
            case 'acceptRequest':
                $data = $class->acceptRequest($r['user_id'], $r['group_id']);
                break;
            case 'getGroupRequests':
                $data = $class->getGroupRequests($r['group_id']);
                break;
            case 'sendGroupRequest':
                $data = $class->sendGroupRequest($r['user_id'], $r['groups_name'], $r['groups_password']);
                break;
        }
    } else if ($f === "user") {
        switch ($a) {
            case 'navigation':
                $data = $class->getNavigation($r['account']);
                break;
            case 'login':
                $data = $class->login($r['username'], $r['password']);                    
                break;
            case 'uploadUserImage':
                $data = $class->uploadUserImage($r['user_id'], $_FILES);
                break;
            case 'saveOneSignalId': 
                $data = $class->saveOneSignalId($r['user_id'], $r['oneSignalId']);
                break;
            case 'getMessages': 
                $data = $class->getMessages($r['user_id']);
                break;
            case 'register':                                        
                $data = $class->register($r['email']);                                                            
                break;
            case 'getFullName':
                $data = $class->getFullName($r['user_id']);
                break;
            case 'getUserImage':
                $data = $class->getUserImage($r['user_id']);
                break;
            case 'submitUserChanges':                
                $data = $class->submitUserChanges($r['user']);
                break;
            case 'getGroups':
                $data = $class->getGroups($r['user_id']);
                break;
            case 'isUserInGroup':
                $data = $class->isUserInGroup($r['user_id']);
                break;
            case 'isAccountConfirmed':
                $data = $class->isAccountConfirmed($r['email']);
                break;
            case 'getRole':
                $data = $class->getRole($r['user_id']);
                break;
            case 'isConfirmed':
                $data = $class->isUserConfirmned($r['email']) ? 1 : 0;
                break;
        }
    } else if ($f === "message"){
        switch($a){
            case 'listMessagesByGroupId':
                $data = $class->listMessages($r['group_id']);
                break;                    
            case 'getReplies':
                $data = $class->getReplies($r['message_id']);
                break;
            case 'setVisited':
                $data = $class->setVisited($r['message_id']);
                break;
            case 'setUnvisited':
                $data = $class->setUnvisited($r['message_id']);
                break;
            case 'createMessage':
                $data = $class->createMessage($r['user_id'], $r['message']);
                break;
            case 'reply':
                $data = $class->reply($r['message_id'], $r['user_id'], $r['message']);
                break;
        }
    }
    echo $data;