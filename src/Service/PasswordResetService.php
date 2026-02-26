<?php

namespace App\Service;

use App\Entity\User;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

class PasswordResetService
{
    private MailerInterface $mailer;
    private string $fromEmail;
    private string $appName;

    public function __construct(MailerInterface $mailer, string $fromEmail = 'noreply@gym-app.com', string $appName = 'Gym App')
    {
        $this->mailer = $mailer;
        $this->fromEmail = $fromEmail;
        $this->appName = $appName;
    }

    public function generateResetCode(): string
    {
        return strtoupper(bin2hex(random_bytes(4)));
    }

    public function sendPasswordResetEmail(User $user): void
    {
        $resetCode = $this->generateResetCode();
        $expiresAt = new \DateTime('+1 hour');

        $user->setPasswordResetCode($resetCode);
        $user->setPasswordResetCodeExpiresAt($expiresAt);

        $email = (new Email())
            ->from($this->fromEmail)
            ->to($user->getEmail())
            ->subject("Reset Your Password - {$this->appName}")
            ->html($this->getEmailTemplate($user, $resetCode));

        $this->mailer->send($email);
    }

    private function getEmailTemplate(User $user, string $code): string
    {
        return <<<HTML
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: Arial, sans-serif; background-color: #f5f5f5; margin: 0; padding: 0; }
        .container { max-width: 600px; margin: 20px auto; background-color: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .header { text-align: center; border-bottom: 2px solid #ef4444; padding-bottom: 20px; }
        .header h1 { color: #333; margin: 0; }
        .content { padding: 20px 0; }
        .content p { color: #666; line-height: 1.6; }
        .code-box { 
            background-color: #f9f9f9; 
            border: 2px dashed #ef4444; 
            padding: 20px; 
            text-align: center; 
            margin: 20px 0; 
            border-radius: 4px;
        }
        .code-box .code { 
            font-size: 32px; 
            font-weight: bold; 
            color: #ef4444; 
            letter-spacing: 5px;
            font-family: 'Courier New', monospace;
        }
        .warning { 
            background-color: #fff3cd; 
            border-left: 4px solid #ffc107; 
            padding: 15px; 
            margin: 15px 0; 
            border-radius: 4px;
        }
        .warning p { margin: 0; color: #856404; }
        .footer { 
            border-top: 1px solid #e0e0e0; 
            padding-top: 20px; 
            text-align: center; 
            color: #999; 
            font-size: 12px; 
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🔐 Password Reset Request</h1>
        </div>
        <div class="content">
            <p>Hello <strong>{$user->getName()}</strong>,</p>
            <p>We received a request to reset the password for your account. Please use the code below to reset your password:</p>
            
            <div class="code-box">
                <p>Your Reset Code:</p>
                <div class="code">{$code}</div>
            </div>
            
            <p>This code will expire in <strong>1 hour</strong>. After that, you'll need to request a new password reset.</p>
            
            <div class="warning">
                <p><strong>⚠️ Security Alert:</strong> If you didn't request this password reset, please ignore this email and your password will remain unchanged. For security reasons, do not share this code with anyone.</p>
            </div>
            
            <p>If you continue to have issues, please contact our support team.</p>
            
            <p>Best regards,<br><strong>The {$this->appName} Team</strong></p>
        </div>
        <div class="footer">
            <p>&copy; 2026 {$this->appName}. All rights reserved.</p>
        </div>
    </div>
</body>
</html>
HTML;
    }
}
