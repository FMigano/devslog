<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/../../vendor/autoload.php';

// YOUR APP'S EMAIL CONFIGURATION (One email for the whole app)
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_USERNAME', 'redevsapp@gmail.com');  // Replace with YOUR app email
define('SMTP_PASSWORD', 'fvqf jqci puwh eogt');  // Replace with YOUR app password
define('SMTP_FROM_EMAIL', 'redevsapp@gmail.com'); // Replace with YOUR app email
define('SMTP_FROM_NAME', 'Devslog');

function sendVerificationEmail($email, $token, $username) {
    $mail = new PHPMailer(true);
    
    try {
        // Server settings
        $mail->isSMTP();
        $mail->Host = SMTP_HOST;
        $mail->SMTPAuth = true;
        $mail->Username = SMTP_USERNAME;  // YOUR app email
        $mail->Password = SMTP_PASSWORD;   // YOUR app password
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;

        // Recipients
        $mail->setFrom(SMTP_FROM_EMAIL, SMTP_FROM_NAME);  // FROM: Your app email
        $mail->addAddress($email, $username);              // TO: User's email (fred@gmail.com, john@gmail.com, etc.)

        // Content
        $mail->isHTML(true);
        $mail->Subject = 'Verify Your Email - Devslog';
        
        $verificationLink = "http://localhost:5173/verify-email?token=" . $token;
        
        $mail->Body = "
            <h2>Welcome to Devslog, {$username}!</h2>
            <p>Please click the link below to verify your email address:</p>
            <a href='{$verificationLink}' style='padding: 10px 20px; background-color: #10b981; color: white; text-decoration: none; border-radius: 5px;'>Verify Email</a>
            <p>Or copy this link to your browser:</p>
            <p>{$verificationLink}</p>
            <p>This link will expire in 24 hours.</p>
        ";
        
        $mail->AltBody = "Welcome to Devslog! Please verify your email by visiting: {$verificationLink}";

        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Email Error: " . $mail->ErrorInfo);
        return false;
    }
}

function sendPasswordResetEmail($email, $token, $username) {
    $mail = new PHPMailer(true);
    
    try {
        // Server settings
        $mail->isSMTP();
        $mail->Host = SMTP_HOST;
        $mail->SMTPAuth = true;
        $mail->Username = SMTP_USERNAME;   // YOUR app email
        $mail->Password = SMTP_PASSWORD;    // YOUR app password
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;

        // Recipients
        $mail->setFrom(SMTP_FROM_EMAIL, SMTP_FROM_NAME);  // FROM: Your app email
        $mail->addAddress($email, $username);              // TO: User's email

        // Content
        $mail->isHTML(true);
        $mail->Subject = 'Reset Your Password - Devslog';
        
        $resetLink = "http://localhost:5173/reset-password?token=" . $token;
        
        $mail->Body = "
            <h2>Password Reset Request</h2>
            <p>Hi {$username},</p>
            <p>Click the link below to reset your password:</p>
            <a href='{$resetLink}' style='padding: 10px 20px; background-color: #10b981; color: white; text-decoration: none; border-radius: 5px;'>Reset Password</a>
            <p>Or copy this link to your browser:</p>
            <p>{$resetLink}</p>
            <p>This link will expire in 1 hour.</p>
        ";
        
        $mail->AltBody = "Password reset link: {$resetLink}";

        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Email Error: " . $mail->ErrorInfo);
        return false;
    }
}