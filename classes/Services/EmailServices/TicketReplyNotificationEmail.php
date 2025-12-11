<?php

namespace Karyalay\Services\EmailServices;

/**
 * Ticket Reply Notification Email Handler
 * 
 * Sends notification emails to customers when an admin replies to their ticket.
 * Does not include the reply content - only notifies that a response is available.
 */
class TicketReplyNotificationEmail extends AbstractEmailHandler
{
    /**
     * Send ticket reply notification to customer
     */
    public function sendEmail(array $replyData): bool
    {
        $customerEmail = $replyData['customer_email'] ?? '';
        
        if (empty($customerEmail)) {
            error_log("TicketReplyNotificationEmail: Customer email not provided");
            return false;
        }

        $ticketId = $this->formatTicketId($replyData['ticket_id'] ?? 'N/A');
        $subject = "New Response on Ticket #{$ticketId}";
        
        $body = $this->renderTemplate($replyData);
        $plainText = $this->renderPlainText($replyData);
        
        return $this->send($customerEmail, $subject, $body, $plainText);
    }

    /**
     * Render HTML template
     */
    private function renderTemplate(array $replyData): string
    {
        $siteName = $this->getSiteName();
        $ticketId = htmlspecialchars($this->formatTicketId($replyData['ticket_id'] ?? 'N/A'));
        $customerName = htmlspecialchars($replyData['customer_name'] ?? 'Valued Customer');
        $ticketSubject = htmlspecialchars($replyData['ticket_subject'] ?? 'Your Support Request');
        $ticketUrl = htmlspecialchars($replyData['ticket_url'] ?? '#');
        $repliedAt = date('F j, Y \a\t g:i A');

        return <<<HTML
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background-color: #10b981; color: white; padding: 30px 20px; text-align: center; border-radius: 8px 8px 0 0; }
        .header h1 { margin: 0; font-size: 24px; }
        .content { background-color: #ffffff; padding: 30px 20px; border: 1px solid #e2e8f0; }
        .ticket-box { background-color: #f0fdf4; padding: 20px; border-left: 4px solid #10b981; margin: 20px 0; border-radius: 4px; }
        .ticket-id { font-size: 18px; font-weight: bold; color: #059669; margin-bottom: 10px; }
        .ticket-subject { font-size: 16px; color: #1a202c; margin-bottom: 5px; }
        .info-box { background-color: #eff6ff; border: 1px solid #bfdbfe; padding: 15px; border-radius: 4px; margin: 20px 0; }
        .btn { display: inline-block; padding: 14px 28px; background-color: #3b82f6; color: white; text-decoration: none; border-radius: 8px; font-weight: 600; margin: 20px 0; }
        .btn:hover { background-color: #2563eb; }
        .footer { text-align: center; padding: 20px; color: #718096; font-size: 12px; background-color: #f7fafc; border-radius: 0 0 8px 8px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>💬 New Response Available</h1>
        </div>
        <div class="content">
            <p>Dear {$customerName},</p>
            
            <p>Good news! Our support team has responded to your ticket. A new reply is now available for you to review.</p>
            
            <div class="ticket-box">
                <div class="ticket-id">Ticket #{$ticketId}</div>
                <div class="ticket-subject">{$ticketSubject}</div>
            </div>

            <div class="info-box">
                <strong>📋 What's Next?</strong>
                <p style="margin: 10px 0 0 0;">Click the button below to view the response and continue the conversation. Our team is here to help resolve your issue.</p>
            </div>

            <div style="text-align: center;">
                <a href="{$ticketUrl}" class="btn">View Response</a>
            </div>

            <p style="color: #6b7280; font-size: 14px; margin-top: 20px;">
                <strong>Note:</strong> If you have any additional questions or need further assistance, you can reply directly through the ticket page.
            </p>

            <p>Best regards,<br>
            <strong>{$siteName} Support Team</strong></p>
        </div>
        <div class="footer">
            <p>This is an automated notification. Please do not reply to this email.</p>
            <p>To view and respond to your ticket, please log in to your account.</p>
        </div>
    </div>
</body>
</html>
HTML;
    }

    /**
     * Render plain text version
     */
    private function renderPlainText(array $replyData): string
    {
        $siteName = $this->getSiteName();
        $ticketId = $this->formatTicketId($replyData['ticket_id'] ?? 'N/A');
        $customerName = $replyData['customer_name'] ?? 'Valued Customer';
        $ticketSubject = $replyData['ticket_subject'] ?? 'Your Support Request';
        $ticketUrl = $replyData['ticket_url'] ?? '#';
        $repliedAt = date('F j, Y \a\t g:i A');

        return <<<TEXT
NEW RESPONSE AVAILABLE

Dear {$customerName},

Good news! Our support team has responded to your ticket. A new reply is now available for you to review.

TICKET DETAILS
--------------
Ticket #: {$ticketId}
Subject: {$ticketSubject}

WHAT'S NEXT?
View the response and continue the conversation by visiting:
{$ticketUrl}

Our team is here to help resolve your issue.

Note: If you have any additional questions or need further assistance, you can reply directly through the ticket page.

Best regards,
{$siteName} Support Team

---
This is an automated notification. Please do not reply to this email.
To view and respond to your ticket, please log in to your account.
TEXT;
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
}
