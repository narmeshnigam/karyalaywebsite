<?php
/**
 * Invoice View Page
 * Professional A4 invoice layout for viewing, printing, and PDF download
 */

// Load Composer autoloader
require_once __DIR__ . '/../../vendor/autoload.php';

// Include required files
require_once __DIR__ . '/../../includes/auth_helpers.php';
require_once __DIR__ . '/../../includes/template_helpers.php';

use Karyalay\Models\Order;
use Karyalay\Models\Plan;
use Karyalay\Models\User;
use Karyalay\Models\Setting;

// Guard customer portal - requires authentication
$user = guardCustomerPortal();
$userId = $user['id'];

// Get order ID from query parameter
$orderId = $_GET['id'] ?? '';

if (empty($orderId)) {
    $_SESSION['flash_message'] = 'Invalid order ID.';
    $_SESSION['flash_type'] = 'danger';
    header('Location: ' . get_app_base_url() . '/app/billing/history.php');
    exit;
}

// Fetch order
$orderModel = new Order();
$order = $orderModel->findById($orderId);

// Verify order exists and belongs to user
if (!$order || $order['customer_id'] !== $userId) {
    $_SESSION['flash_message'] = 'Order not found.';
    $_SESSION['flash_type'] = 'danger';
    header('Location: ' . get_app_base_url() . '/app/billing/history.php');
    exit;
}

// Only show invoice for successful orders
if (strtoupper($order['status']) !== 'SUCCESS') {
    $_SESSION['flash_message'] = 'Invoice is only available for successful orders.';
    $_SESSION['flash_type'] = 'warning';
    header('Location: ' . get_app_base_url() . '/app/billing/history.php');
    exit;
}

// Fetch plan details
$planModel = new Plan();
$plan = $planModel->findById($order['plan_id']);

// Fetch user details
$userModel = new User();
$customer = $userModel->findById($order['customer_id']);

// Fetch company settings
$settingModel = new Setting();
$companyName = $settingModel->get('brand_name', 'SellerPortal');
$companyAddress = $settingModel->get('company_address', '');
$companyEmail = $settingModel->get('contact_email', '');
$companyPhone = $settingModel->get('contact_phone', '');
$companyGst = $settingModel->get('company_gst', '');
$companyLogo = $settingModel->get('site_logo', '');

// Format data
$invoiceNumber = 'INV-' . strtoupper(substr($order['id'], 0, 8));
$orderDate = date('F d, Y', strtotime($order['created_at']));
$symbol = get_currency_symbol();
$amount = $order['amount'];
$planName = $plan ? $plan['name'] : 'Subscription Plan';
$planDescription = $plan ? ($plan['description'] ?? '') : '';
$billingPeriod = $plan ? ($plan['billing_period_months'] ?? 1) : 1;

// Check if print mode (standalone printable page)
$isPrintMode = isset($_GET['print']) && $_GET['print'] === '1';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice <?php echo htmlspecialchars($invoiceNumber); ?> - <?php echo htmlspecialchars($companyName); ?></title>
    <style>
        /* Reset and Base */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', -apple-system, BlinkMacSystemFont, Roboto, 'Helvetica Neue', Arial, sans-serif;
            font-size: 14px;
            line-height: 1.5;
            color: #333;
            background: #f5f5f5;
        }
        
        /* Page Controls - Hidden in print */
        .page-controls {
            background: #fff;
            padding: 16px 24px;
            border-bottom: 1px solid #e0e0e0;
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: sticky;
            top: 0;
            z-index: 100;
        }
        
        .page-controls .back-link {
            color: #666;
            text-decoration: none;
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 6px;
        }
        
        .page-controls .back-link:hover {
            color: #333;
        }
        
        .page-controls .actions {
            display: flex;
            gap: 12px;
        }
        
        .btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 20px;
            border-radius: 6px;
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            border: none;
            text-decoration: none;
            transition: all 0.2s;
        }
        
        .btn-outline {
            background: #fff;
            border: 1px solid #d0d0d0;
            color: #333;
        }
        
        .btn-outline:hover {
            background: #f5f5f5;
            border-color: #999;
        }
        
        .btn-primary {
            background: #2563eb;
            color: #fff;
        }
        
        .btn-primary:hover {
            background: #1d4ed8;
        }
        
        /* A4 Paper Container */
        .invoice-container {
            max-width: 210mm;
            margin: 40px auto;
            padding: 0 20px;
        }
        
        .invoice-paper {
            background: #fff;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            padding: 48px 56px;
            min-height: 297mm;
        }
        
        /* Invoice Header */
        .invoice-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 48px;
            padding-bottom: 32px;
            border-bottom: 2px solid #f0f0f0;
        }
        
        .company-info {
            flex: 1;
        }
        
        .company-logo {
            max-height: 48px;
            max-width: 180px;
            margin-bottom: 12px;
        }
        
        .company-name {
            font-size: 22px;
            font-weight: 700;
            color: #1a1a1a;
            margin-bottom: 8px;
        }
        
        .company-details {
            font-size: 13px;
            color: #666;
            line-height: 1.7;
        }
        
        .invoice-title-section {
            text-align: right;
        }
        
        .invoice-title {
            font-size: 36px;
            font-weight: 700;
            color: #2563eb;
            letter-spacing: -1px;
            margin-bottom: 16px;
        }
        
        .invoice-meta {
            font-size: 13px;
            color: #666;
        }
        
        .invoice-meta-row {
            margin-bottom: 6px;
        }
        
        .invoice-meta-label {
            color: #999;
        }
        
        .invoice-meta-value {
            font-weight: 600;
            color: #333;
            margin-left: 8px;
        }
        
        .status-badge {
            display: inline-block;
            padding: 4px 14px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-top: 8px;
        }
        
        .status-paid {
            background: #dcfce7;
            color: #166534;
        }
        
        /* Billing Section */
        .billing-section {
            display: flex;
            justify-content: space-between;
            margin-bottom: 48px;
        }
        
        .billing-block {
            flex: 1;
        }
        
        .billing-block-right {
            text-align: right;
        }
        
        .billing-label {
            font-size: 11px;
            font-weight: 600;
            color: #999;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 10px;
        }
        
        .billing-name {
            font-size: 16px;
            font-weight: 600;
            color: #1a1a1a;
            margin-bottom: 6px;
        }
        
        .billing-detail {
            font-size: 13px;
            color: #666;
            line-height: 1.7;
        }
        
        /* Items Table */
        .items-section {
            margin-bottom: 32px;
        }
        
        .items-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .items-table thead th {
            background: #f8fafc;
            padding: 14px 16px;
            text-align: left;
            font-size: 11px;
            font-weight: 600;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            border-top: 1px solid #e2e8f0;
            border-bottom: 1px solid #e2e8f0;
        }
        
        .items-table thead th:last-child {
            text-align: right;
        }
        
        .items-table thead th.text-center {
            text-align: center;
        }
        
        .items-table tbody td {
            padding: 20px 16px;
            border-bottom: 1px solid #f1f5f9;
            vertical-align: top;
        }
        
        .items-table tbody td:last-child {
            text-align: right;
        }
        
        .items-table tbody td.text-center {
            text-align: center;
        }
        
        .item-name {
            font-weight: 600;
            color: #1a1a1a;
            margin-bottom: 4px;
        }
        
        .item-description {
            font-size: 12px;
            color: #64748b;
        }
        
        /* Totals Section */
        .totals-section {
            display: flex;
            justify-content: flex-end;
            margin-bottom: 48px;
        }
        
        .totals-table {
            width: 280px;
        }
        
        .totals-row {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            font-size: 14px;
        }
        
        .totals-label {
            color: #64748b;
        }
        
        .totals-value {
            font-weight: 500;
            color: #333;
        }
        
        .totals-row.subtotal {
            border-bottom: 1px solid #e2e8f0;
        }
        
        .totals-row.total {
            border-top: 2px solid #1a1a1a;
            margin-top: 8px;
            padding-top: 16px;
        }
        
        .totals-row.total .totals-label,
        .totals-row.total .totals-value {
            font-size: 18px;
            font-weight: 700;
            color: #1a1a1a;
        }
        
        .totals-row.paid {
            color: #166534;
        }
        
        .totals-row.paid .totals-label,
        .totals-row.paid .totals-value {
            color: #166534;
            font-weight: 600;
        }
        
        /* Payment Info */
        .payment-info {
            background: #f8fafc;
            border-radius: 8px;
            padding: 20px 24px;
            margin-bottom: 48px;
        }
        
        .payment-info-title {
            font-size: 12px;
            font-weight: 600;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 12px;
        }
        
        .payment-info-row {
            display: flex;
            justify-content: space-between;
            font-size: 13px;
            padding: 6px 0;
        }
        
        .payment-info-label {
            color: #64748b;
        }
        
        .payment-info-value {
            color: #333;
            font-weight: 500;
        }
        
        /* Footer */
        .invoice-footer {
            text-align: center;
            padding-top: 32px;
            border-top: 1px solid #e2e8f0;
        }
        
        .footer-thanks {
            font-size: 18px;
            font-weight: 600;
            color: #1a1a1a;
            margin-bottom: 8px;
        }
        
        .footer-note {
            font-size: 12px;
            color: #94a3b8;
            margin-bottom: 16px;
        }
        
        .footer-contact {
            font-size: 12px;
            color: #64748b;
        }
        
        .footer-contact a {
            color: #2563eb;
            text-decoration: none;
        }
        
        /* Print Styles */
        @media print {
            body {
                background: #fff;
            }
            
            .page-controls {
                display: none !important;
            }
            
            .invoice-container {
                margin: 0;
                padding: 0;
                max-width: none;
            }
            
            .invoice-paper {
                box-shadow: none;
                padding: 20mm 15mm;
                min-height: auto;
            }
            
            @page {
                size: A4;
                margin: 0;
            }
        }
        
        /* Print-only mode */
        <?php if ($isPrintMode): ?>
        .page-controls {
            display: none !important;
        }
        
        body {
            background: #fff;
        }
        
        .invoice-container {
            margin: 0;
            padding: 0;
            max-width: none;
        }
        
        .invoice-paper {
            box-shadow: none;
            padding: 20mm 15mm;
        }
        <?php endif; ?>
    </style>
</head>
<body>
    <!-- Page Controls -->
    <div class="page-controls">
        <a href="<?php echo get_app_base_url(); ?>/app/billing/history.php" class="back-link">
            ← Back to Billing History
        </a>
        <div class="actions">
            <button onclick="window.print()" class="btn btn-outline">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M6 9V2h12v7M6 18H4a2 2 0 01-2-2v-5a2 2 0 012-2h16a2 2 0 012 2v5a2 2 0 01-2 2h-2"/>
                    <rect x="6" y="14" width="12" height="8"/>
                </svg>
                Print Invoice
            </button>
            <button onclick="downloadPDF()" class="btn btn-primary">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4M7 10l5 5 5-5M12 15V3"/>
                </svg>
                Download PDF
            </button>
        </div>
    </div>

    <!-- Invoice Paper -->
    <div class="invoice-container">
        <div class="invoice-paper">
            <!-- Header -->
            <div class="invoice-header">
                <div class="company-info">
                    <?php if ($companyLogo): ?>
                        <img src="<?php echo htmlspecialchars($companyLogo); ?>" alt="<?php echo htmlspecialchars($companyName); ?>" class="company-logo">
                    <?php endif; ?>
                    <div class="company-name"><?php echo htmlspecialchars($companyName); ?></div>
                    <div class="company-details">
                        <?php if ($companyAddress): ?>
                            <?php echo nl2br(htmlspecialchars($companyAddress)); ?><br>
                        <?php endif; ?>
                        <?php if ($companyEmail): ?>
                            <?php echo htmlspecialchars($companyEmail); ?><br>
                        <?php endif; ?>
                        <?php if ($companyPhone): ?>
                            <?php echo htmlspecialchars($companyPhone); ?><br>
                        <?php endif; ?>
                        <?php if ($companyGst): ?>
                            GST: <?php echo htmlspecialchars($companyGst); ?>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="invoice-title-section">
                    <div class="invoice-title">INVOICE</div>
                    <div class="invoice-meta">
                        <div class="invoice-meta-row">
                            <span class="invoice-meta-label">Invoice No:</span>
                            <span class="invoice-meta-value"><?php echo htmlspecialchars($invoiceNumber); ?></span>
                        </div>
                        <div class="invoice-meta-row">
                            <span class="invoice-meta-label">Date:</span>
                            <span class="invoice-meta-value"><?php echo htmlspecialchars($orderDate); ?></span>
                        </div>
                        <div class="invoice-meta-row">
                            <span class="invoice-meta-label">Order ID:</span>
                            <span class="invoice-meta-value">#<?php echo strtoupper(substr($order['id'], 0, 8)); ?></span>
                        </div>
                    </div>
                    <span class="status-badge status-paid">Paid</span>
                </div>
            </div>

            <!-- Billing Section -->
            <div class="billing-section">
                <div class="billing-block">
                    <div class="billing-label">Bill To</div>
                    <div class="billing-name"><?php echo htmlspecialchars($customer['name'] ?? 'Customer'); ?></div>
                    <div class="billing-detail">
                        <?php echo htmlspecialchars($customer['email'] ?? ''); ?>
                        <?php if (!empty($customer['phone'])): ?>
                            <br><?php echo htmlspecialchars($customer['phone']); ?>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="billing-block billing-block-right">
                    <div class="billing-label">Payment Method</div>
                    <div class="billing-detail">
                        <?php echo htmlspecialchars(ucfirst($order['payment_method'] ?? 'Online Payment')); ?>
                        <?php if (!empty($order['pg_payment_id'])): ?>
                            <br><span style="font-size: 11px; color: #999;">Payment ID: <?php echo htmlspecialchars(substr($order['pg_payment_id'], 0, 20)); ?></span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Items Table -->
            <div class="items-section">
                <table class="items-table">
                    <thead>
                        <tr>
                            <th style="width: 50%;">Description</th>
                            <th class="text-center" style="width: 20%;">Period</th>
                            <th class="text-center" style="width: 10%;">Qty</th>
                            <th style="width: 20%;">Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>
                                <div class="item-name"><?php echo htmlspecialchars($planName); ?></div>
                                <?php if ($planDescription): ?>
                                    <div class="item-description"><?php echo htmlspecialchars($planDescription); ?></div>
                                <?php endif; ?>
                            </td>
                            <td class="text-center"><?php echo $billingPeriod; ?> month<?php echo $billingPeriod > 1 ? 's' : ''; ?></td>
                            <td class="text-center">1</td>
                            <td><?php echo $symbol . number_format($amount, 2); ?></td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Totals -->
            <div class="totals-section">
                <div class="totals-table">
                    <div class="totals-row subtotal">
                        <span class="totals-label">Subtotal</span>
                        <span class="totals-value"><?php echo $symbol . number_format($amount, 2); ?></span>
                    </div>
                    <div class="totals-row">
                        <span class="totals-label">Tax (0%)</span>
                        <span class="totals-value"><?php echo $symbol . '0.00'; ?></span>
                    </div>
                    <div class="totals-row total">
                        <span class="totals-label">Total</span>
                        <span class="totals-value"><?php echo $symbol . number_format($amount, 2); ?> <?php echo $currency; ?></span>
                    </div>
                    <div class="totals-row paid">
                        <span class="totals-label">Amount Paid</span>
                        <span class="totals-value"><?php echo $symbol . number_format($amount, 2); ?></span>
                    </div>
                    <div class="totals-row">
                        <span class="totals-label">Balance Due</span>
                        <span class="totals-value"><?php echo $symbol . '0.00'; ?></span>
                    </div>
                </div>
            </div>

            <!-- Payment Info Box -->
            <div class="payment-info">
                <div class="payment-info-title">Payment Information</div>
                <div class="payment-info-row">
                    <span class="payment-info-label">Payment Status</span>
                    <span class="payment-info-value" style="color: #166534;">Paid in Full</span>
                </div>
                <div class="payment-info-row">
                    <span class="payment-info-label">Payment Date</span>
                    <span class="payment-info-value"><?php echo htmlspecialchars($orderDate); ?></span>
                </div>
                <div class="payment-info-row">
                    <span class="payment-info-label">Currency</span>
                    <span class="payment-info-value"><?php echo $currency; ?></span>
                </div>
            </div>

            <!-- Footer -->
            <div class="invoice-footer">
                <div class="footer-thanks">Thank you for your business!</div>
                <div class="footer-note">This is a computer-generated invoice and does not require a signature.</div>
                <?php if ($companyEmail || $companyPhone): ?>
                    <div class="footer-contact">
                        Questions? Contact us at 
                        <?php if ($companyEmail): ?>
                            <a href="mailto:<?php echo htmlspecialchars($companyEmail); ?>"><?php echo htmlspecialchars($companyEmail); ?></a>
                        <?php endif; ?>
                        <?php if ($companyEmail && $companyPhone): ?> or <?php endif; ?>
                        <?php if ($companyPhone): ?>
                            <?php echo htmlspecialchars($companyPhone); ?>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
    function downloadPDF() {
        // Show instruction then trigger print
        const userAgent = navigator.userAgent.toLowerCase();
        const isChrome = userAgent.indexOf('chrome') > -1 && userAgent.indexOf('edge') === -1;
        const isSafari = userAgent.indexOf('safari') > -1 && userAgent.indexOf('chrome') === -1;
        
        if (isChrome) {
            alert('To save as PDF:\n\n1. In the print dialog, change "Destination" to "Save as PDF"\n2. Click "Save"\n\nThe print dialog will open now.');
        } else if (isSafari) {
            alert('To save as PDF:\n\n1. In the print dialog, click the "PDF" dropdown at the bottom left\n2. Select "Save as PDF"\n\nThe print dialog will open now.');
        } else {
            alert('To save as PDF:\n\n1. In the print dialog, select "Save as PDF" or "Microsoft Print to PDF"\n2. Click "Print" or "Save"\n\nThe print dialog will open now.');
        }
        
        window.print();
    }
    
    <?php if ($isPrintMode): ?>
    // Auto-print in print mode
    window.onload = function() {
        setTimeout(function() {
            window.print();
        }, 500);
    };
    <?php endif; ?>
    </script>
</body>
</html>
