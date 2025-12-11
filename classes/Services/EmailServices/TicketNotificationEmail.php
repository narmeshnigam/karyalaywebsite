<?php

namespace Karyalay\Services\EmailServices;

/**
 * Ticket Notification Email Handler
 * 
 * Sends ticket confirmation emails to customers and notification emails to admin
 * when a new support ticket is created.
 */
class TicketNotificationEmail extends AbstractEmailHandler
{
    /**
     * Send ticket confirmation to customer and notification to admin
     */
    public function sendEmail(array $ticketData): bool
    {
        $customerEmailSent = $this->sendCustomerConfirmation($ticketData);
        $adminEmailSent = $this->sendAdminNotification($ticketData);
        
        return $customerEmailSent && $adminEmailSent;
    }

    /**
     * Send ticket confirmation to customer
     */
    private function sendCustomerConfirmation(array $ticketData): bool
    {
        $customerEmail = $ticketData['customer_email'] ?? '';
        
        if (empty($customerEmail)) {
            error_log("TicketNotificationEmail: Customer email not provided");
            return false;
        }

        $ticketId = $this->formatTicketId($ticketData['ticket_id'] ?? 'N/A');
        $subject = "Ticket #{$ticketId} - We've Received Your Support Request";
        
        $body = $this->renderCustomerTemplate($ticketData);
        $plainText = $this->renderCustomerPlainText($ticketData);
        
        return $this->send($customerEmail, $subject, $body, $plainText);
    }

    /**
     * Send ticket notification to admin
     */
    private function sendAdminNotification(array $ticketData): bool
    {
        $notificationsEmail = $this->getNotificationsEmail();
        $ticketId = $this->formatTicketId($ticketData['ticket_id'] ?? 'N/A');
        $subject = "New Support Ticket #{$ticketId} - {$ticketData['subject']}";
        
        $body = $this->renderAdminTemplate($ticketData);
        $plainText = $this->renderAdminPlainText($ticketData);
        
        return $this->send($notificationsEmail, $subject, $body, $plainText);
    }

    /**
     * Format ticket ID for display (show first 8 characters of UUID)
     */
    private function formatTicketId(string $ticketId): string
    {
        if (strlen($ticketId) > 8) {
            return strtoupper(substr($ticketId, 0, 8));
        }
        return strtoupper($ticketId);
    }

    /**
     * Render customer confirmation HTML template
     */
    private function renderCustomerTemplate(array $ticketData): string
    {
        $siteName = $this->getSiteName();
        $ticketId = htmlspecialchars($this->formatTicketId($ticketData['ticket_id'] ?? 'N/A'));
        $customerName = htmlspecialchars($ticketData['customer_name'] ?? 'Valued Customer');
        $subject = htmlspecialchars($ticketData['subject'] ?? '');
        $description = nl2br(htmlspecialchars($ticketData['description'] ?? ''));
        $priority = htmlspecialchars($ticketData['priority'] ?? 'Normal');
        $category = htmlspecialchars($ticketData['category'] ?? 'General');
        $createdAt = date('F j, Y \a\t g:i A');

        return <<<HTML
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background-color: #3b82f6; color: white; padding: 30px 20px; text-align: center; border-radius: 8px 8px 0 0; }
        .header h1 { margin: 0; font-size: 24px; }
        .content { background-color: #ffffff; padding: 30px 20px; border: 1px solid #e2e8f0; }
        .ticket-box { background-color: #f7fafc; padding: 20px; border-left: 4px solid #3b82f6; margin: 20px 0; border-radius: 4px; }
        .field { margin-bottom: 15px; }
        .label { font-weight: bold; color: #4a5568; font-size: 14px; }
        .value { margin-top: 5px; color: #1a202c; }
        .ticket-id { font-size: 28px; font-weight: bold; color: #3b82f6; margin: 10px 0; }
        .info-box { background-color: #eff6ff; border: 1px solid #bfdbfe; padding: 15px; border-radius: 4px; margin: 20px 0; }
        .footer { text-align: center; padding: 20px; color: #718096; font-size: 12px; background-color: #f7fafc; border-radius: 0 0 8px 8px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>✅ Ticket Received</h1>
        </div>
        <div class="content">
            <p>Dear {$customerName},</p>
            
            <p>Thank you for contacting {$siteName} support. We have successfully received your support ticket and our team will review it shortly.</p>
            
            <div class="ticket-box">
                <div style="text-align: center;">
                    <div style="font-size: 14px; color: #718096;">Your Ticket Number</div>
                    <div class="ticket-id">#{$ticketId}</div>
                </div>
            </div>

            <div class="field">
                <div class="label">Subject:</div>
                <div class="value">{$subject}</div>
            </div>

            <div class="field">
                <div class="label">Category:</div>
                <div class="value">{$category}</div>
            </div>

            <div class="field">
                <div class="label">Priority:</div>
                <div class="value">{$priority}</div>
            </div>

            <div class="field">
                <div class="label">Description:</div>
                <div class="value">{$description}</div>
            </div>

            <div class="field">
                <div class="label">Submitted:</div>
                <div class="value">{$createdAt}</div>
            </div>

            <div class="info-box">
                <strong>📞 What happens next?</strong>
                <p style="margin: 10px 0 0 0;">Our support team will review your ticket and reach out to you via email or your registered mobile number for resolution. We typically respond within 24 hours during business days.</p>
            </div>

            <p>Please keep your ticket number for reference. If you need to follow up, mention this ticket number in your communication.</p>

            <p>Best regards,<br>
            <strong>{$siteName} Support Team</strong></p>
        </div>
        <div class="footer">
            <p>This is an automated confirmation email. Please do not reply to this email.</p>
        </div>
    </div>
</body>
</html>
HTML;
    }

    /**
     * Render customer confirmation plain text version
     */
    private function renderCustomerPlainText(array $ticketData): string
    {
        $siteName = $this->getSiteName();
        $ticketId = $this->formatTicketId($ticketData['ticket_id'] ?? 'N/A');
        $customerName = $ticketData['customer_name'] ?? 'Valued Customer';
        $subject = $ticketData['subject'] ?? '';
        $description = $ticketData['description'] ?? '';
        $priority = $ticketData['priority'] ?? 'Normal';
        $category = $ticketData['category'] ?? 'General';
        $createdAt = date('F j, Y \a\t g:i A');

        return <<<TEXT
TICKET RECEIVED

Dear {$customerName},

Thank you for contacting {$siteName} support. We have successfully received your support ticket and our team will review it shortly.

YOUR TICKET NUMBER: #{$ticketId}

Subject: {$subject}
Category: {$category}
Priority: {$priority}

Description:
{$description}

Submitted: {$createdAt}

WHAT HAPPENS NEXT?
Our support team will review your ticket and reach out to you via email or your registered mobile number for resolution. We typically respond within 24 hours during business days.

Please keep your ticket number for reference. If you need to follow up, mention this ticket number in your communication.

Best regards,
{$siteName} Support Team

---
This is an automated confirmation email. Please do not reply to this email.
TEXT;
    }

    /**
     * Render admin notification HTML template
     */
    private function renderAdminTemplate(array $ticketData): string
    {
        $ticketId = htmlspecialchars($this->formatTicketId($ticketData['ticket_id'] ?? 'N/A'));
        $customerName = htmlspecialchars($ticketData['customer_name'] ?? 'Unknown');
        $customerEmail = htmlspecialchars($ticketData['customer_email'] ?? 'Not provided');
        $customerPhone = htmlspecialchars($ticketData['customer_phone'] ?? 'Not provided');
        $subject = htmlspecialchars($ticketData['subject'] ?? '');
        $description = nl2br(htmlspecialchars($ticketData['description'] ?? ''));
        $priority = htmlspecialchars($ticketData['priority'] ?? 'Normal');
        $category = htmlspecialchars($ticketData['category'] ?? 'General');
        $createdAt = date('F j, Y \a\t g:i A');

        $priorityColor = match(strtolower($priority)) {
            'high', 'urgent' => '#ef4444',
            'medium' => '#f59e0b',
            default => '#10b981'
        };

        return <<<HTML
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background-color: #4a5568; color: white; padding: 20px; text-align: center; border-radius: 8px 8px 0 0; }
        .content { background-color: #f7fafc; padding: 20px; border: 1px solid #e2e8f0; border-top: none; }
        .field { margin-bottom: 15px; }
        .label { font-weight: bold; color: #4a5568; }
        .value { margin-top: 5px; padding: 10px; background-color: white; border-radius: 4px; }
        .priority-badge { display: inline-block; padding: 4px 12px; color: white; border-radius: 12px; font-size: 12px; font-weight: bold; background-color: {$priorityColor}; }
        .category-badge { display: inline-block; padding: 4px 12px; background-color: #6366f1; color: white; border-radius: 12px; font-size: 12px; font-weight: bold; }
        .contact-section { background-color: #fef3c7; border: 1px solid #fbbf24; padding: 15px; border-radius: 4px; margin: 15px 0; }
        .footer { text-align: center; padding: 20px; color: #718096; font-size: 12px; background-color: #f7fafc; border-radius: 0 0 8px 8px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h2>🎫 New Support Ticket #{$ticketId}</h2>
        </div>
        <div class="content">
            <div class="field">
                <div class="label">Priority:</div>
                <div class="value"><span class="priority-badge">{$priority}</span></div>
            </div>
            <div class="field">
                <div class="label">Category:</div>
                <div class="value"><span class="category-badge">{$category}</span></div>
            </div>
            <div class="field">
                <div class="label">Subject:</div>
                <div class="value">{$subject}</div>
            </div>
            <div class="field">
                <div class="label">Description:</div>
                <div class="value">{$description}</div>
            </div>
            
            <div class="contact-section">
                <strong>📋 Customer Contact Details</strong>
                <div style="margin-top: 10px;">
                    <div><strong>Name:</strong> {$customerName}</div>
                    <div><strong>Email:</strong> <a href="mailto:{$customerEmail}">{$customerEmail}</a></div>
                    <div><strong>Phone:</strong> {$customerPhone}</div>
                </div>
            </div>

            <div class="field">
                <div class="label">Submitted At:</div>
                <div class="value">{$createdAt}</div>
            </div>
        </div>
        <div class="footer">
            <p>This is an automated notification from your support ticket system</p>
        </div>
    </div>
</body>
</html>
HTML;
    }

    /**
     * Render admin notification plain text version
     */
    private function renderAdminPlainText(array $ticketData): string
    {
        $ticketId = $this->formatTicketId($ticketData['ticket_id'] ?? 'N/A');
        $customerName = $ticketData['customer_name'] ?? 'Unknown';
        $customerEmail = $ticketData['customer_email'] ?? 'Not provided';
        $customerPhone = $ticketData['customer_phone'] ?? 'Not provided';
        $subject = $ticketData['subject'] ?? '';
        $description = $ticketData['description'] ?? '';
        $priority = $ticketData['priority'] ?? 'Normal';
        $category = $ticketData['category'] ?? 'General';
        $createdAt = date('F j, Y \a\t g:i A');

        return <<<TEXT
NEW SUPPORT TICKET #{$ticketId}

Priority: {$priority}
Category: {$category}

Subject: {$subject}

Description:
{$description}

CUSTOMER CONTACT DETAILS
-------------------------
Name: {$customerName}
Email: {$customerEmail}
Phone: {$customerPhone}

Submitted At: {$createdAt}

---
This is an automated notification from your support ticket system
TEXT;
    }
}
