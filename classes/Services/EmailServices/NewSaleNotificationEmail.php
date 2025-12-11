<?php

namespace Karyalay\Services\EmailServices;

/**
 * New Sale Notification Email Handler
 * 
 * Sends new sale notification to admin when a subscription is purchased.
 */
class NewSaleNotificationEmail extends AbstractEmailHandler
{
    /**
     * Send new sale notification to admin
     */
    public function sendEmail(array $saleData): bool
    {
        error_log("NewSaleNotificationEmail: sendEmail called");
        error_log("NewSaleNotificationEmail: Plan name: " . ($saleData['plan_name'] ?? 'NOT SET'));
        
        $notificationsEmail = $this->getNotificationsEmail();
        error_log("NewSaleNotificationEmail: Admin notification email: {$notificationsEmail}");
        
        $subject = "New Subscription Sale: {$saleData['plan_name']}";
        
        $body = $this->renderTemplate($saleData);
        $plainText = $this->renderPlainText($saleData);
        
        error_log("NewSaleNotificationEmail: Calling send method");
        return $this->send($notificationsEmail, $subject, $body, $plainText);
    }

    /**
     * Render HTML template
     */
    private function renderTemplate(array $saleData): string
    {
        $customerName = htmlspecialchars($saleData['customer_name'] ?? '');
        $customerEmail = htmlspecialchars($saleData['customer_email'] ?? '');
        $customerPhone = htmlspecialchars($saleData['customer_phone'] ?? 'Not provided');
        $planName = htmlspecialchars($saleData['plan_name'] ?? '');
        $planPrice = htmlspecialchars($saleData['plan_price'] ?? '0');
        $currency = htmlspecialchars($saleData['currency'] ?? 'USD');
        $orderId = htmlspecialchars($saleData['order_id'] ?? '');
        $subscriptionId = htmlspecialchars($saleData['subscription_id'] ?? '');
        $paymentId = htmlspecialchars($saleData['payment_id'] ?? '');
        $paymentMethod = htmlspecialchars($saleData['payment_method'] ?? 'Online Payment');
        $saleDate = date('F j, Y \a\t g:i A');
        
        return <<<HTML
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background-color: #059669; color: white; padding: 20px; text-align: center; border-radius: 8px 8px 0 0; }
        .header h2 { margin: 0; font-size: 24px; }
        .content { background-color: #f7fafc; padding: 20px; border: 1px solid #e2e8f0; border-top: none; }
        .highlight-box { background-color: #d1fae5; border-left: 4px solid #10b981; padding: 15px; margin: 20px 0; border-radius: 4px; }
        .section { margin-bottom: 25px; }
        .section-title { font-size: 16px; font-weight: bold; color: #2d3748; margin-bottom: 12px; padding-bottom: 8px; border-bottom: 2px solid #e2e8f0; }
        .field { margin-bottom: 12px; }
        .label { font-weight: bold; color: #4a5568; font-size: 14px; }
        .value { margin-top: 5px; padding: 10px; background-color: white; border-radius: 4px; font-size: 15px; }
        .amount-highlight { background-color: #d1fae5; color: #065f46; font-size: 24px; font-weight: bold; padding: 15px; text-align: center; border-radius: 8px; margin: 20px 0; }
        .footer { text-align: center; padding: 20px; color: #718096; font-size: 12px; background-color: #f7fafc; border-radius: 0 0 8px 8px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h2>💰 New Subscription Sale!</h2>
        </div>
        <div class="content">
            <div class="highlight-box">
                <strong>Great news! A new subscription has been purchased.</strong>
            </div>
            
            <div class="amount-highlight">
                {$currency} {$planPrice}
            </div>
            
            <div class="section">
                <div class="section-title">Customer Information</div>
                <div class="field">
                    <div class="label">Name:</div>
                    <div class="value">{$customerName}</div>
                </div>
                <div class="field">
                    <div class="label">Email:</div>
                    <div class="value"><a href="mailto:{$customerEmail}">{$customerEmail}</a></div>
                </div>
                <div class="field">
                    <div class="label">Phone:</div>
                    <div class="value">{$customerPhone}</div>
                </div>
            </div>
            
            <div class="section">
                <div class="section-title">Plan Details</div>
                <div class="field">
                    <div class="label">Plan Name:</div>
                    <div class="value">{$planName}</div>
                </div>
                <div class="field">
                    <div class="label">Plan Price:</div>
                    <div class="value">{$currency} {$planPrice}</div>
                </div>
            </div>
            
            <div class="section">
                <div class="section-title">Payment Information</div>
                <div class="field">
                    <div class="label">Order ID:</div>
                    <div class="value">#{$orderId}</div>
                </div>
                <div class="field">
                    <div class="label">Subscription ID:</div>
                    <div class="value">{$subscriptionId}</div>
                </div>
                <div class="field">
                    <div class="label">Payment ID:</div>
                    <div class="value">{$paymentId}</div>
                </div>
                <div class="field">
                    <div class="label">Payment Method:</div>
                    <div class="value">{$paymentMethod}</div>
                </div>
                <div class="field">
                    <div class="label">Sale Date:</div>
                    <div class="value">{$saleDate}</div>
                </div>
            </div>
        </div>
        <div class="footer">
            <p>This is an automated notification from your subscription sales system</p>
        </div>
    </div>
</body>
</html>
HTML;
    }

    /**
     * Render plain text version
     */
    private function renderPlainText(array $saleData): string
    {
        $customerName = $saleData['customer_name'] ?? '';
        $customerEmail = $saleData['customer_email'] ?? '';
        $customerPhone = $saleData['customer_phone'] ?? 'Not provided';
        $planName = $saleData['plan_name'] ?? '';
        $planPrice = $saleData['plan_price'] ?? '0';
        $currency = $saleData['currency'] ?? 'USD';
        $orderId = $saleData['order_id'] ?? '';
        $subscriptionId = $saleData['subscription_id'] ?? '';
        $paymentId = $saleData['payment_id'] ?? '';
        $paymentMethod = $saleData['payment_method'] ?? 'Online Payment';
        $saleDate = date('F j, Y \a\t g:i A');
        
        return <<<TEXT
NEW SUBSCRIPTION SALE!

Great news! A new subscription has been purchased.

AMOUNT: {$currency} {$planPrice}

CUSTOMER INFORMATION:
Name: {$customerName}
Email: {$customerEmail}
Phone: {$customerPhone}

PLAN DETAILS:
Plan Name: {$planName}
Plan Price: {$currency} {$planPrice}

PAYMENT INFORMATION:
Order ID: #{$orderId}
Subscription ID: {$subscriptionId}
Payment ID: {$paymentId}
Payment Method: {$paymentMethod}
Sale Date: {$saleDate}

---
This is an automated notification from your subscription sales system
TEXT;
    }
}
