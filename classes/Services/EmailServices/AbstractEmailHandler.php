<?php

namespace Karyalay\Services\EmailServices;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use Karyalay\Models\Setting;

/**
 * Abstract Email Handler
 * 
 * Base class for all email handlers providing common SMTP configuration
 * and email sending functionality.
 */
abstract class AbstractEmailHandler
{
    protected PHPMailer $mailer;
    protected string $fromAddress;
    protected string $fromName;

    /**
     * SMTP setting keys used in the database
     */
    private const SMTP_KEYS = [
        'smtp_host',
        'smtp_port',
        'smtp_username',
        'smtp_password',
        'smtp_encryption',
        'smtp_from_address',
        'smtp_from_name'
    ];

    public function __construct()
    {
        $this->mailer = new PHPMailer(true);
        $this->configure();
    }

    /**
     * Load SMTP settings from the database
     */
    private function loadSettingsFromDatabase(): ?array
    {
        try {
            $settingModel = new Setting();
            $settings = $settingModel->getMultiple(self::SMTP_KEYS);
            
            if (empty($settings) || !isset($settings['smtp_host']) || empty($settings['smtp_host'])) {
                return null;
            }
            
            return $settings;
        } catch (\Exception $e) {
            error_log("Error loading SMTP settings from database: " . $e->getMessage());
            return null;
        }
    }


    /**
     * Configure PHPMailer with SMTP settings
     */
    private function configure(): void
    {
        try {
            $dbSettings = $this->loadSettingsFromDatabase();
            
            $this->mailer->isSMTP();
            
            if ($dbSettings !== null) {
                $this->mailer->Host = $dbSettings['smtp_host'];
                $this->mailer->SMTPAuth = true;
                $this->mailer->Username = $dbSettings['smtp_username'] ?? '';
                $this->mailer->Password = $dbSettings['smtp_password'] ?? '';
                $this->mailer->Port = (int)($dbSettings['smtp_port'] ?? 587);
                
                $encryption = $dbSettings['smtp_encryption'] ?? 'tls';
                $this->setEncryption($encryption);
                
                $this->fromAddress = $dbSettings['smtp_from_address'] ?? 'noreply@example.com';
                $this->fromName = $dbSettings['smtp_from_name'] ?? 'SellerPortal';
            } else {
                $this->mailer->Host = $_ENV['MAIL_HOST'] ?? 'localhost';
                $this->mailer->SMTPAuth = true;
                $this->mailer->Username = $_ENV['MAIL_USERNAME'] ?? '';
                $this->mailer->Password = $_ENV['MAIL_PASSWORD'] ?? '';
                $this->mailer->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                $this->mailer->Port = (int)($_ENV['MAIL_PORT'] ?? 587);
                
                $this->fromAddress = $_ENV['MAIL_FROM_ADDRESS'] ?? 'noreply@example.com';
                $this->fromName = $_ENV['MAIL_FROM_NAME'] ?? 'SellerPortal';
            }
            
            $this->mailer->setFrom($this->fromAddress, $this->fromName);
        } catch (Exception $e) {
            error_log("Email configuration error: " . $e->getMessage());
        }
    }

    /**
     * Set SMTP encryption based on configuration value
     */
    private function setEncryption(string $encryption): void
    {
        switch (strtolower($encryption)) {
            case 'ssl':
                $this->mailer->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
                break;
            case 'tls':
                $this->mailer->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                break;
            case 'none':
            default:
                $this->mailer->SMTPSecure = '';
                $this->mailer->SMTPAutoTLS = false;
                break;
        }
    }

    /**
     * Send an email
     */
    protected function send(string $to, string $subject, string $body, ?string $plainTextBody = null): bool
    {
        try {
            error_log("EmailService: Attempting to send email to: {$to}");
            error_log("EmailService: Subject: {$subject}");
            
            $this->mailer->clearAddresses();
            $this->mailer->clearAttachments();
            
            $this->mailer->addAddress($to);
            $this->mailer->isHTML(true);
            $this->mailer->Subject = $subject;
            $this->mailer->Body = $body;
            
            if ($plainTextBody) {
                $this->mailer->AltBody = $plainTextBody;
            }

            error_log("EmailService: Sending email via SMTP...");
            $this->mailer->send();
            error_log("EmailService: Email sent successfully to: {$to}");
            return true;
        } catch (Exception $e) {
            error_log("EmailService: Email sending error to {$to}: " . $this->mailer->ErrorInfo);
            error_log("EmailService: Exception message: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get site name from settings
     */
    protected function getSiteName(): string
    {
        try {
            $settingModel = new Setting();
            $siteName = $settingModel->get('site_name');
            return $siteName ?: 'SellerPortal';
        } catch (\Exception $e) {
            return 'SellerPortal';
        }
    }

    /**
     * Get notifications email from settings
     */
    protected function getNotificationsEmail(): string
    {
        try {
            $settingModel = new Setting();
            $notificationsEmail = $settingModel->get('notifications_email');
            
            if (empty($notificationsEmail)) {
                $notificationsEmail = $settingModel->get('contact_email');
            }
            
            if (empty($notificationsEmail)) {
                $notificationsEmail = $_ENV['ADMIN_EMAIL'] ?? 'admin@karyalay.com';
            }
            
            return $notificationsEmail;
        } catch (\Exception $e) {
            error_log("Error loading notifications email: " . $e->getMessage());
            return $_ENV['ADMIN_EMAIL'] ?? 'admin@karyalay.com';
        }
    }
}
