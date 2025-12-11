<?php
/**
 * Admin Invoice View Page
 * Display invoice exactly as customer sees it
 */

require_once __DIR__ . '/../../config/bootstrap.php';
require_once __DIR__ . '/../../includes/auth_helpers.php';
require_once __DIR__ . '/../../includes/admin_helpers.php';
require_once __DIR__ . '/../../includes/template_helpers.php';

use Karyalay\Services\InvoiceService;

// Start secure session
startSecureSession();

// Require admin authentication and invoices.view_details permission
require_admin();
require_permission('invoices.view_details');

$orderId = $_GET['order_id'] ?? null;

if (!$orderId) {
    $_SESSION['admin_error'] = 'Order ID is required.';
    header('Location: ' . get_app_base_url() . '/admin/invoices.php');
    exit;
}

// Get invoice data
$invoiceService = new InvoiceService();
$invoice = $invoiceService->getInvoiceData($orderId);

if (!$invoice) {
    $_SESSION['admin_error'] = 'Invoice not found or order is not completed.';
    header('Location: ' . get_app_base_url() . '/admin/invoices.php');
    exit;
}

$pageTitle = 'Invoice ' . $invoice['invoice_number'];

// Check if download is requested
$isDownload = isset($_GET['download']) && $_GET['download'] === '1';

if ($isDownload) {
    header('Content-Type: text/html; charset=utf-8');
}

// Build logo URL
$logoUrl = '';
if (!empty($invoice['company']['logo'])) {
    $logoUrl = get_app_base_url() . $invoice['company']['logo'];
} else {
    $logoUrl = get_app_base_url() . '/public/assets/images/logo.svg';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($pageTitle); ?> - Admin</title>
    <style>
        :root {
            --invoice-primary: #667eea;
            --invoice-text: #1f2937;
            --invoice-text-light: #6b7280;
            --invoice-border: #e5e7eb;
            --invoice-bg: #ffffff;
            --invoice-bg-light: #f9fafb;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            background: #525659;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            font-size: 14px;
            line-height: 1.5;
            color: var(--invoice-text);
        }

        .invoice-wrapper {
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 20px;
            min-height: 100vh;
        }

        .invoice-actions {
            width: 210mm;
            max-width: 100%;
            padding: 15px 20px;
            background: #3d4043;
            border-radius: 8px 8px 0 0;
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 0;
        }

        .invoice-actions .btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 20px;
            border-radius: 6px;
            font-size: 14px;
            font-weight: 500;
            text-decoration: none;
            transition: all 0.2s;
            border: none;
            cursor: pointer;
        }

        .invoice-actions .btn-secondary {
            background: #5f6368;
            color: white;
        }

        .invoice-actions .btn-secondary:hover {
            background: #6e7378;
        }

        .invoice-actions .btn-primary {
            background: var(--invoice-primary);
            color: white;
        }

        .invoice-actions .btn-primary:hover {
            background: #5568d3;
        }

        .invoice-actions .btn-group {
            display: flex;
            gap: 10px;
        }

        .invoice-paper {
            width: 210mm;
            min-height: 297mm;
            background: var(--invoice-bg);
            box-shadow: 0 0 20px rgba(0,0,0,0.3);
            padding: 12mm 15mm;
            margin-bottom: 20px;
        }

        .invoice-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px solid var(--invoice-border);
        }

        .invoice-logo img {
            height: 38px;
            width: auto;
            max-width: 160px;
            object-fit: contain;
        }

        .invoice-title {
            text-align: right;
        }

        .invoice-title h1 {
            font-size: 24px;
            font-weight: 700;
            color: var(--invoice-text);
            margin: 0 0 6px 0;
            letter-spacing: -0.5px;
        }

        .invoice-number {
            font-size: 12px;
            color: var(--invoice-text-light);
            line-height: 1.5;
        }

        .invoice-parties {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
            margin-bottom: 20px;
        }

        .invoice-party h3 {
            font-size: 10px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            color: var(--invoice-text-light);
            margin: 0 0 8px 0;
        }

        .invoice-party-name {
            font-weight: 600;
            font-size: 13px;
            color: var(--invoice-text);
            margin-bottom: 3px;
        }

        .invoice-party-business {
            font-weight: 500;
            font-size: 13px;
            color: var(--invoice-text);
            margin-bottom: 6px;
        }

        .invoice-party-details {
            font-size: 12px;
            color: var(--invoice-text-light);
            line-height: 1.6;
        }

        .invoice-party-tax {
            margin-top: 6px;
            font-size: 11px;
            color: var(--invoice-text-light);
        }

        .invoice-paid-section {
            padding: 12px 0;
            margin-bottom: 18px;
            text-align: center;
            border-top: 1px solid var(--invoice-border);
            border-bottom: 1px solid var(--invoice-border);
        }

        .invoice-paid-amount {
            font-size: 18px;
            font-weight: 700;
            color: var(--invoice-text);
            margin-bottom: 3px;
        }

        .invoice-paid-date {
            font-size: 12px;
            color: var(--invoice-text-light);
        }

        .invoice-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 18px;
        }

        .invoice-table thead {
            background: var(--invoice-bg-light);
        }

        .invoice-table th {
            padding: 10px 12px;
            text-align: left;
            font-size: 10px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: var(--invoice-text-light);
            border-bottom: 2px solid var(--invoice-border);
        }

        .invoice-table th:last-child,
        .invoice-table td:last-child {
            text-align: right;
        }

        .invoice-table td {
            padding: 12px;
            border-bottom: 1px solid var(--invoice-border);
            color: var(--invoice-text);
            font-size: 12px;
        }

        .invoice-item-desc {
            font-weight: 500;
            margin-bottom: 3px;
        }

        .invoice-item-period {
            font-size: 11px;
            color: var(--invoice-text-light);
        }

        .invoice-totals {
            margin-left: auto;
            width: 250px;
        }

        .invoice-total-row {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            border-bottom: 1px solid var(--invoice-border);
            font-size: 13px;
        }

        .invoice-total-row:last-child {
            border-bottom: none;
            border-top: 2px solid var(--invoice-text);
            padding-top: 10px;
            font-weight: 700;
            font-size: 14px;
        }

        .invoice-total-label {
            color: var(--invoice-text-light);
        }

        .invoice-total-row:last-child .invoice-total-label {
            color: var(--invoice-text);
        }

        .invoice-payment-details {
            margin-top: 20px;
            padding-top: 15px;
            border-top: 2px solid var(--invoice-border);
        }

        .invoice-payment-details h3 {
            font-size: 13px;
            font-weight: 600;
            color: var(--invoice-text);
            margin-bottom: 12px;
        }

        .payment-details-table {
            width: 100%;
            border-collapse: collapse;
        }

        .payment-details-table th {
            padding: 10px 12px;
            text-align: left;
            font-size: 10px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: var(--invoice-text-light);
            background: var(--invoice-bg-light);
            border-bottom: 1px solid var(--invoice-border);
        }

        .payment-details-table td {
            padding: 10px 12px;
            font-size: 12px;
            color: var(--invoice-text);
            border-bottom: 1px solid var(--invoice-border);
        }

        .invoice-thanks {
            margin-top: 25px;
            padding: 15px;
            background: var(--invoice-bg-light);
            border-radius: 6px;
            text-align: center;
        }

        .invoice-thanks p {
            font-size: 13px;
            color: var(--invoice-text);
            margin: 0;
        }

        .invoice-thanks .emoji {
            font-size: 16px;
            margin-right: 6px;
        }

        .invoice-footer {
            margin-top: 20px;
            padding-top: 12px;
            border-top: 1px solid var(--invoice-border);
            text-align: center;
            font-size: 10px;
            color: var(--invoice-text-light);
        }

        @media print {
            @page {
                size: A4;
                margin: 0;
            }

            body {
                background: white;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }

            .invoice-wrapper {
                padding: 0;
            }

            .invoice-actions {
                display: none !important;
            }

            .invoice-paper {
                box-shadow: none;
                margin: 0;
                width: 100%;
                min-height: auto;
                padding: 12mm 15mm;
            }

            .invoice-header {
                display: flex !important;
                justify-content: space-between !important;
                align-items: flex-start !important;
                flex-direction: row !important;
            }

            .invoice-parties {
                display: grid !important;
                grid-template-columns: 1fr 1fr !important;
                gap: 30px !important;
            }

            .invoice-thanks {
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }

            .invoice-table,
            .payment-details-table {
                page-break-inside: avoid;
            }

            .invoice-totals {
                margin-left: auto !important;
                width: 250px !important;
            }
        }

        @media (max-width: 800px) {
            .invoice-wrapper {
                padding: 10px;
            }

            .invoice-paper {
                width: 100%;
                min-height: auto;
                padding: 20px;
            }

            .invoice-actions {
                width: 100%;
                flex-direction: column;
                gap: 10px;
            }

            .invoice-header {
                flex-direction: column;
                gap: 20px;
            }

            .invoice-title {
                text-align: left;
            }

            .invoice-parties {
                grid-template-columns: 1fr;
                gap: 25px;
            }

            .invoice-totals {
                width: 100%;
            }

            .payment-details-table {
                display: block;
                overflow-x: auto;
            }
            
            .payment-details-table th,
            .payment-details-table td {
                white-space: nowrap;
            }
        }
    </style>
</head>
<body>
    <div class="invoice-wrapper">
        <?php if (!$isDownload): ?>
        <div class="invoice-actions">
            <a href="<?php echo get_app_base_url(); ?>/admin/invoices.php" class="btn btn-secondary">
                ← Back to Invoices
            </a>
            <div class="btn-group">
                <a href="<?php echo get_app_base_url(); ?>/admin/orders/view.php?id=<?php echo urlencode($orderId); ?>" class="btn btn-secondary">
                    View Order
                </a>
                <button onclick="window.print()" class="btn btn-secondary">
                    🖨️ Print
                </button>
                <a href="?order_id=<?php echo urlencode($orderId); ?>&download=1" target="_blank" class="btn btn-primary">
                    📥 Download PDF
                </a>
            </div>
        </div>
        <?php endif; ?>

        <div class="invoice-paper">
            <!-- Header -->
            <div class="invoice-header">
                <div class="invoice-logo">
                    <img src="<?php echo htmlspecialchars($logoUrl); ?>" alt="<?php echo htmlspecialchars($invoice['company']['name']); ?>">
                </div>
                <div class="invoice-title">
                    <h1>Invoice</h1>
                    <div class="invoice-number">
                        Invoice #: <?php echo htmlspecialchars($invoice['invoice_number']); ?><br>
                        Date: <?php echo htmlspecialchars($invoice['invoice_date']); ?>
                    </div>
                </div>
            </div>

            <!-- Parties -->
            <div class="invoice-parties">
                <div class="invoice-party">
                    <h3>From</h3>
                    <div class="invoice-party-name"><?php echo htmlspecialchars($invoice['company']['name']); ?></div>
                    <div class="invoice-party-details">
                        <?php if ($invoice['company']['address_line1']): ?>
                            <?php echo htmlspecialchars($invoice['company']['address_line1']); ?><br>
                        <?php endif; ?>
                        <?php if ($invoice['company']['address_line2']): ?>
                            <?php echo htmlspecialchars($invoice['company']['address_line2']); ?><br>
                        <?php endif; ?>
                        <?php if ($invoice['company']['city'] || $invoice['company']['state'] || $invoice['company']['postal_code']): ?>
                            <?php echo htmlspecialchars(trim($invoice['company']['city'] . ', ' . $invoice['company']['state'] . ' ' . $invoice['company']['postal_code'], ', ')); ?><br>
                        <?php endif; ?>
                        <?php if ($invoice['company']['country']): ?>
                            <?php echo htmlspecialchars($invoice['company']['country']); ?><br>
                        <?php endif; ?>
                        <?php if ($invoice['company']['email']): ?>
                            <?php echo htmlspecialchars($invoice['company']['email']); ?><br>
                        <?php endif; ?>
                        <?php if ($invoice['company']['phone']): ?>
                            Tel: <?php echo htmlspecialchars($invoice['company']['phone']); ?>
                        <?php endif; ?>
                    </div>
                    <?php if ($invoice['company']['tax_id']): ?>
                        <div class="invoice-party-tax">Tax ID: <?php echo htmlspecialchars($invoice['company']['tax_id']); ?></div>
                    <?php endif; ?>
                </div>

                <div class="invoice-party">
                    <h3>Billed To</h3>
                    <div class="invoice-party-name"><?php echo htmlspecialchars($invoice['customer']['name']); ?></div>
                    <?php if ($invoice['customer']['business_name']): ?>
                        <div class="invoice-party-business"><?php echo htmlspecialchars($invoice['customer']['business_name']); ?></div>
                    <?php endif; ?>
                    <div class="invoice-party-details">
                        <?php if ($invoice['customer']['address_line1']): ?>
                            <?php echo htmlspecialchars($invoice['customer']['address_line1']); ?><br>
                            <?php if ($invoice['customer']['address_line2']): ?>
                                <?php echo htmlspecialchars($invoice['customer']['address_line2']); ?><br>
                            <?php endif; ?>
                            <?php if ($invoice['customer']['city'] || $invoice['customer']['state'] || $invoice['customer']['postal_code']): ?>
                                <?php echo htmlspecialchars(trim($invoice['customer']['city'] . ', ' . $invoice['customer']['state'] . ' ' . $invoice['customer']['postal_code'], ', ')); ?><br>
                            <?php endif; ?>
                            <?php if ($invoice['customer']['country']): ?>
                                <?php echo htmlspecialchars($invoice['customer']['country']); ?><br>
                            <?php endif; ?>
                        <?php endif; ?>
                        <?php echo htmlspecialchars($invoice['customer']['email']); ?><br>
                        <?php if ($invoice['customer']['phone']): ?>
                            Tel: <?php echo htmlspecialchars($invoice['customer']['phone']); ?>
                        <?php endif; ?>
                    </div>
                    <?php if ($invoice['customer']['tax_id']): ?>
                        <div class="invoice-party-tax">Tax ID: <?php echo htmlspecialchars($invoice['customer']['tax_id']); ?></div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Amount Paid -->
            <div class="invoice-paid-section">
                <div class="invoice-paid-amount">
                    Amount Paid: <?php echo htmlspecialchars($invoice['currency_symbol']); ?><?php echo number_format($invoice['amount_paid'], 2); ?>
                </div>
                <div class="invoice-paid-date">
                    Payment Date: <?php echo htmlspecialchars($invoice['invoice_date']); ?>
                </div>
            </div>

            <!-- Items -->
            <table class="invoice-table">
                <thead>
                    <tr>
                        <th>Description</th>
                        <th>Qty</th>
                        <th>Unit Price</th>
                        <th>Tax</th>
                        <th>Amount</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($invoice['items'] as $item): ?>
                    <tr>
                        <td>
                            <div class="invoice-item-desc"><?php echo htmlspecialchars($item['description']); ?></div>
                            <div class="invoice-item-period"><?php echo htmlspecialchars($item['period']); ?></div>
                        </td>
                        <td><?php echo $item['quantity']; ?></td>
                        <td><?php echo htmlspecialchars($invoice['currency_symbol']); ?><?php echo number_format($item['unit_price'], 2); ?></td>
                        <td><?php echo isset($item['tax_percent']) && $item['tax_percent'] > 0 ? number_format($item['tax_percent'], 2) . '%' : '0%'; ?></td>
                        <td><?php echo htmlspecialchars($invoice['currency_symbol']); ?><?php echo number_format($item['amount'], 2); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <!-- Totals -->
            <div class="invoice-totals">
                <div class="invoice-total-row">
                    <span class="invoice-total-label">Subtotal (Net)</span>
                    <span><?php echo htmlspecialchars($invoice['currency_symbol']); ?><?php echo number_format($invoice['subtotal'], 2); ?></span>
                </div>
                <div class="invoice-total-row">
                    <span class="invoice-total-label">
                        <?php if (!empty($invoice['tax_name'])): ?>
                            <?php echo htmlspecialchars($invoice['tax_name']); ?>
                            <?php if ($invoice['tax_percent'] > 0): ?> (<?php echo number_format($invoice['tax_percent'], 2); ?>%)<?php endif; ?>
                        <?php else: ?>
                            Tax<?php if ($invoice['tax_percent'] > 0): ?> (<?php echo number_format($invoice['tax_percent'], 2); ?>%)<?php endif; ?>
                        <?php endif; ?>
                    </span>
                    <span><?php echo htmlspecialchars($invoice['currency_symbol']); ?><?php echo number_format($invoice['tax'], 2); ?></span>
                </div>
                <div class="invoice-total-row">
                    <span class="invoice-total-label">Total Paid</span>
                    <span><?php echo htmlspecialchars($invoice['currency_symbol']); ?><?php echo number_format($invoice['amount_paid'], 2); ?></span>
                </div>
            </div>

            <!-- Payment Details -->
            <div class="invoice-payment-details">
                <h3>Payment Details</h3>
                <table class="payment-details-table">
                    <thead>
                        <tr>
                            <th>Payment Method</th>
                            <th>Payment Date</th>
                            <th>Amount Paid</th>
                            <th>Transaction ID</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><?php echo htmlspecialchars($invoice['payment_details']['method']); ?></td>
                            <td><?php echo htmlspecialchars($invoice['payment_details']['date']); ?></td>
                            <td><?php echo htmlspecialchars($invoice['currency_symbol']); ?><?php echo number_format($invoice['payment_details']['amount'], 2); ?></td>
                            <td><?php echo htmlspecialchars($invoice['payment_details']['transaction_id']); ?></td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Thanks Note -->
            <div class="invoice-thanks">
                <p><span class="emoji">🙏</span>Thank you for your business!</p>
            </div>

            <!-- Footer -->
            <div class="invoice-footer">
                This is a computer-generated invoice and does not require a signature.
            </div>
        </div>
    </div>

    <?php if ($isDownload): ?>
    <script>
        window.onload = function() {
            window.print();
        };
    </script>
    <?php endif; ?>
</body>
</html>
