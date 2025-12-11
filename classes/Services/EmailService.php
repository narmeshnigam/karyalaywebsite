<?php

namespace Karyalay\Services;

use Karyalay\Services\EmailServices\GenericEmail;
use Karyalay\Services\EmailServices\LeadThankYouEmail;
use Karyalay\Services\EmailServices\LeadNotificationEmail;
use Karyalay\Services\EmailServices\DemoRequestEmail;
use Karyalay\Services\EmailServices\WelcomeEmail;
use Karyalay\Services\EmailServices\NewUserNotificationEmail;
use Karyalay\Services\EmailServices\PaymentSuccessEmail;
use Karyalay\Services\EmailServices\NewSaleNotificationEmail;
use Karyalay\Services\EmailServices\OtpEmail;
use Karyalay\Services\EmailServices\PasswordResetConfirmationEmail;
use Karyalay\Services\EmailServices\ContactFormEmail;
use Karyalay\Services\EmailServices\InstanceProvisionedEmail;
use Karyalay\Services\EmailServices\TicketNotificationEmail;
use Karyalay\Services\EmailServices\TicketReplyNotificationEmail;

/**
 * Email Service Facade
 * 
 * Provides a unified interface for all email operations while delegating
 * to specialized email handlers. Maintains backward compatibility with
 * existing code that uses EmailService directly.
 * 
 * Each email type is handled by its own dedicated class in the EmailServices folder:
 * - GenericEmail: For ad-hoc email sending
 * - LeadThankYouEmail: Thank you emails to lead submitters
 * - LeadNotificationEmail: Admin notifications for new leads
 * - DemoRequestEmail: Admin notifications for demo requests
 * - WelcomeEmail: Welcome emails to new users
 * - NewUserNotificationEmail: Admin notifications for new registrations
 * - PaymentSuccessEmail: Payment confirmation to customers
 * - NewSaleNotificationEmail: Admin notifications for new sales
 * - OtpEmail: OTP verification emails
 * - PasswordResetConfirmationEmail: Password reset confirmations
 * - ContactFormEmail: Contact form notifications (deprecated)
 * - TicketNotificationEmail: Support ticket notifications to customer and admin
 * - TicketReplyNotificationEmail: Ticket reply notifications to customer
 */
class EmailService
{
    private static ?EmailService $instance = null;

    /**
     * Get singleton instance
     */
    public static function getInstance(): EmailService
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Send a generic email
     */
    public function send(string $to, string $subject, string $body, ?string $plainTextBody = null): bool
    {
        return (new GenericEmail())->sendEmail($to, $subject, $body, $plainTextBody);
    }

    /**
     * Send an email (alias for send method)
     */
    public function sendEmail(string $to, string $subject, string $body, ?string $plainTextBody = null): bool
    {
        return $this->send($to, $subject, $body, $plainTextBody);
    }


    /**
     * Send thank you email to lead submitter
     */
    public function sendLeadThankYouEmail(string $to, string $name): bool
    {
        return (new LeadThankYouEmail())->sendEmail($to, $name);
    }

    /**
     * Send lead notification to admin
     */
    public function sendLeadNotification(array $leadData): bool
    {
        return (new LeadNotificationEmail())->sendEmail($leadData);
    }

    /**
     * Send contact form notification to admin
     * @deprecated Use sendLeadNotification instead
     */
    public function sendContactFormNotification(array $leadData): bool
    {
        return (new ContactFormEmail())->sendEmail($leadData);
    }

    /**
     * Send demo request notification to admin
     */
    public function sendDemoRequestNotification(array $leadData): bool
    {
        return (new DemoRequestEmail())->sendEmail($leadData);
    }

    /**
     * Send welcome email to newly registered user
     */
    public function sendWelcomeEmail(string $to, string $name): bool
    {
        return (new WelcomeEmail())->sendEmail($to, $name);
    }

    /**
     * Send new user registration notification to admin
     */
    public function sendNewUserNotification(array $userData): bool
    {
        return (new NewUserNotificationEmail())->sendEmail($userData);
    }

    /**
     * Send payment success email to customer
     */
    public function sendPaymentSuccessEmail(array $paymentData): bool
    {
        error_log("EmailService: sendPaymentSuccessEmail called");
        error_log("EmailService: Customer email: " . ($paymentData['customer_email'] ?? 'NOT SET'));
        
        error_log("EmailService: Delegating to PaymentSuccessEmail handler");
        return (new PaymentSuccessEmail())->sendEmail($paymentData);
    }

    /**
     * Send new sale notification to admin
     */
    public function sendNewSaleNotification(array $saleData): bool
    {
        error_log("EmailService: sendNewSaleNotification called");
        error_log("EmailService: Plan name: " . ($saleData['plan_name'] ?? 'NOT SET'));
        
        error_log("EmailService: Delegating to NewSaleNotificationEmail handler");
        return (new NewSaleNotificationEmail())->sendEmail($saleData);
    }

    /**
     * Send OTP verification email
     */
    public function sendOtpEmail(string $to, string $otp, int $expiryMinutes = 10): bool
    {
        return (new OtpEmail())->sendEmail($to, $otp, $expiryMinutes);
    }

    /**
     * Send password reset confirmation email
     */
    public function sendPasswordResetConfirmation(string $to, string $name): bool
    {
        return (new PasswordResetConfirmationEmail())->sendEmail($to, $name);
    }

    /**
     * Send instance provisioned email with setup instructions
     */
    public function sendInstanceProvisionedEmail(array $instanceData): bool
    {
        return (new InstanceProvisionedEmail())->sendEmail($instanceData);
    }

    /**
     * Send ticket notification emails (customer confirmation + admin notification)
     */
    public function sendTicketNotification(array $ticketData): bool
    {
        return (new TicketNotificationEmail())->sendEmail($ticketData);
    }

    /**
     * Send ticket reply notification to customer
     */
    public function sendTicketReplyNotification(array $replyData): bool
    {
        return (new TicketReplyNotificationEmail())->sendEmail($replyData);
    }
}
