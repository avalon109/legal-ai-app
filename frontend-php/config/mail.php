<?php
require_once __DIR__ . '/../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

class Mailer {
    private $mail;
    
    public function __construct() {
        $this->mail = new PHPMailer(true);
        
        // Server settings
        $this->mail->isSMTP();
        $this->mail->Host = 'smtp.gmail.com'; // Replace with your SMTP host
        $this->mail->SMTPAuth = true;
        $this->mail->Username = 'your-email@gmail.com'; // Replace with your email
        $this->mail->Password = 'your-app-specific-password'; // Replace with your password
        $this->mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $this->mail->Port = 587;
        
        // Default sender
        $this->mail->setFrom('noreply@legal-ai.com', 'Legal AI System');
    }
    
    public function send($to, $subject, $body) {
        try {
            $this->mail->clearAddresses();
            $this->mail->addAddress($to);
            $this->mail->isHTML(true);
            $this->mail->Subject = $subject;
            $this->mail->Body = $body;
            $this->mail->AltBody = strip_tags($body);
            
            return $this->mail->send();
        } catch (Exception $e) {
            throw new Exception("Email could not be sent. Mailer Error: {$this->mail->ErrorInfo}");
        }
    }
} 