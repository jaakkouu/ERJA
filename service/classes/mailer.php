<?php

require 'includes/phpmailer/PHPMailerAutoload.php';

class mailer {

    private $mail;

    function __construct(){
        $this->mail = new PHPMailer;
        $this->mail->isSMTP();        
        $this->mail->SMTPAuth = true;
        $this->mail->SMTPSecure = 'ssl';
        $this->mail->SMTPAutoTLS = false;
        $this->mail->Host = 'ssl0.ovh.net';
        $this->mail->Port = 465;
        $this->mail->Username = 'tilit@erja.net';
        $this->mail->Password = 'Gas7sad8asd';        
        $this->mail->WordWrap = 50;
        $this->mail->AddEmbeddedImage('images/app/logo.jpg', 'logo');
        $this->mail->CharSet = 'UTF-8';
        $this->mail->IsHTML(true);
    }

    private function createMail($message_subject, $message_content){
        $message = file_get_contents('email-templates/email-header.php');        
        $message .= file_get_contents('email-templates/email-body.php');       
        $message .= file_get_contents('email-templates/email-footer.php');
        $replacements = array(
            '({message_subject})' => $message_subject,
            '({message_body})' => nl2br(stripslashes($message_content))
        );
        $message = preg_replace(array_keys($replacements), array_values($replacements), $message);
        return stripslashes($message);
    }
    
    public function sendUserVerification($to, $token){                  
        $url = "<a href='https://api.erja.net/confirm.php?email=".$to."&token=".$token."'>erja.net/vahvista</a>";                
        $this->mail->setFrom('tilit@erja.net', 'ERJA');
        $this->mail->AddAddress($to);
        $this->mail->Subject = 'Vahvista tilisi';
        $this->mail->Body = $this->createMail('Vahvista tilisi', '<p>Vahvista tili osoitteesta: '.$url.'</p>');
        if(!$this->mail->send()) {
            echo "Viestin lähetys epäonnistui. Kokeile hetken kuluttua uudelleen.";         
            echo 'Mailer error: ' . $this->mail->ErrorInfo;
        }
    }

    public function sendUserDetails($to, $username, $password){        
        $this->mail->setFrom('tilit@erja.net', 'ERJA');
        $this->mail->AddAddress($to);
        $this->mail->Subject = 'Tilisi on vahvistettu';
        $this->mail->Body = $this->createMail('Tilisi on vahvistettu!', '<p>Voit kirjautua sovellukseesi mobiililaitteella käyttäen näitä tunnuksia:</p><p>Käyttäjänimi: '.$username.'</p><p>Salasana: '.$password.'</p>');
        if(!$this->mail->send()) {
            echo "Viestin lähetys epäonnistui. Kokeile hetken kuluttua uudelleen.";         
            echo 'Mailer error: ' . $this->mail->ErrorInfo;
        }
    }

}

?>
