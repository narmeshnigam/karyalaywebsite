<?php

namespace Karyalay\Services\EmailServices;

/**
 * Payment Success Email Handler
 * 
 * Sends payment success confirmation email to customers.
 */
class PaymentSuccessEmail extends AbstractEmailHandler
{
    /**
     * Send payment success email to customer
     */
    public function sendEmail(array $paymentData): bool
    {
        error_log("PaymentSuccessEmail: sendEmail called");
        error_log("PaymentSuccessEmail: Customer email: " . ($paymentData['customer_email'] ?? 'NOT SET'));
        
        $subject = "Payment Successful - " . $this->getSiteName();
        $body = $this->renderTemplate($paymentData);
        $plainText = $this->renderPlainText($paymentData);
        
        error_log("PaymentSuccessEmail: Calling send method");
        return $this->send($paymentData['customer_email'], $subject, $body, $plainText);
    }

    /**
     * Render HTML template
     */
    private function renderTemplate(array $paymentData): string
    {
        $customerName = htmlspecialchars($paymentData['customer_name'] ?? '');
        $planName = htmlspecialchars($paymentData['plan_name'] ?? '');
        $amount = htmlspecialchars($paymentData['amount'] ?? '0');
        $currency = htmlspecialchars($paymentData['currency'] ?? 'USD');
        $orderId = htmlspecialchars($paymentData['order_id'] ?? '');
        $paymentId = htmlspecialchars($paymentData['payment_id'] ?? '');
        $paymentDate = date('F j, Y \a\t g:i A');
        $invoiceUrl = htmlspecialchars($paymentData['invoice_url'] ?? '');
        $siteName = htmlspecialchars($this->getSiteName());
        
        return <<<HTML
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; margin: 0; padding: 0; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background: linear-gradient(135deg, #10b981 0%, #059669 100%); color: white; padding: 40px 30px; text-align: center; border-radius: 8px 8px 0 0; }
        .header h1 { margin: 0; font-size: 28px; }
        .header p { margin: 10px 0 0 0; font-size: 16px; opacity: 0.95; }
        .content { background-color: #ffffff; padding: 40px 30px; border: 1px solid #e2e8f0; border-top: none; }
        .amount-box { background: linear-gradient(135deg, #f0fdf4 0%, #dcfce7 100%); border: 2px solid #86efac; border-radius: 8px; padding: 20px; text-align: center; margin: 25px 0; }
        .amount-label { font-size: 14px; color: #166534; font-weight: 600; margin-bottom: 8px; }
        .amount-value { font-size: 36px; font-weight: bold; color: #15803d; }
        .details-box { background-color: #f7fafc; border-radius: 8px; padding: 20px; margin: 25px 0; }
        .detail-row { display: flex; justify-content: space-between; padding: 10px 0; border-bottom: 1px solid #e2e8f0; }
        .detail-row:last-child { border-bottom: none; }
        .detail-label { color: #64748b; font-size: 14px; }
        .detail-value { color: #1e293b; font-weight: 600; font-size: 14px; }
        .cta-button { display: inline-block; padding: 14px 32px; background: linear-gradient(135deg, #10b981 0%, #059669 100%); color: white; text-decoration: none; border-radius: 6px; font-weight: 600; margin: 25px 0; font-size: 16px; }
        .info-box { background-color: #dbeafe; border-left: 4px solid #3b82f6; padding: 15px; margin: 25px 0; border-radius: 4px; }
        .footer { text-align: center; padding: 30px 20px; color: #718096; font-size: 13px; background-color: #f7fafc; border-radius: 0 0 8px 8px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>✅ Payment Successful!</h1>
            <p>Thank you for your purchase</p>
        </div>
        <div class="content">
            <p style="font-size: 18px; color: #2d3748; margin-bottom: 20px;">Hello {$customerName},</p>
            
            <p>Your payment has been successfully processed. Your subscription is now active!</p>
            
            <div class="amount-box">
                <div class="amount-label">AMOUNT PAID</div>
                <div class="amount-value">{$currency} {$amount}</div>
            </div>
            
            <div class="details-box">
                <h3 style="margin: 0 0 15px 0; color: #2d3748; font-size: 16px;">Payment Details</h3>
                <div class="detail-row">
                    <span class="detail-label">Plan</span>
                    <span class="detail-value">{$planName}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Order ID</span>
                    <span class="detail-value">#{$orderId}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Payment ID</span>
                    <span class="detail-value">{$paymentId}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Payment Date</span>
                    <span class="detail-value">{$paymentDate}</span>
                </div>
            </div>
            
            <center>
                <a href="{$invoiceUrl}" class="cta-button">View Invoice</a>
            </center>
            
            <div class="info-box">
                <strong>What's Next?</strong><br>
                Your instance is being provisioned. You will receive an email with setup instructions shortly.
            </div>
            
            <p style="margin-top: 30px; color: #4a5568;">If you have any questions, please don't hesitate to contact our support team.</p>
            
            <p style="margin-top: 20px; color: #4a5568;">
                Best regards,<br>
                <strong>The {$siteName} Team</strong>
            </p>
        </div>
        <div class="footer">
            <p>This is an automated payment confirmation from {$siteName}</p>
            <p style="margin-top: 10px;">© {$siteName}. All rights reserved.</p>
        </div>
    </div>
</body>
</html>
HTML;
    }

    /**
     * Render plain text version
     */
    private function renderPlainText(array $paymentData): string
    {
        $customerName = $paymentData['customer_name'] ?? '';
        $planName = $paymentData['plan_name'] ?? '';
        $amount = $paymentData['amount'] ?? '0';
        $currency = $paymentData['currency'] ?? 'USD';
        $orderId = $paymentData['order_id'] ?? '';
        $paymentId = $paymentData['payment_id'] ?? '';
        $paymentDate = date('F j, Y \a\t g:i A');
        $invoiceUrl = $paymentData['invoice_url'] ?? '';
        $siteName = $this->getSiteName();
        
        return <<<TEXT
PAYMENT SUCCESSFUL!

Hello {$customerName},

Your payment has been successfully processed. Your subscription is now active!

AMOUNT PAID: {$currency} {$amount}

PAYMENT DETAILS:
Plan: {$planName}
Order ID: #{$orderId}
Payment ID: {$paymentId}
Payment Date: {$paymentDate}

View Invoice: {$invoiceUrl}

WHAT'S NEXT?
Your instance is being provisioned. You will receive an email with setup instructions shortly.

If you have any questions, please don't hesitate to contact our support team.

Best regards,
The {$siteName} Team

---
This is an automated payment confirmation from {$siteName}
© {$siteName}. All rights reserved.
TEXT;
    }
}
