<?php

namespace Karyalay\Services;

use Karyalay\Models\Order;
use Karyalay\Models\Plan;
use Karyalay\Models\User;
use Karyalay\Models\Setting;

/**
 * Invoice Service
 * Generates invoice data and PDFs for orders
 */
class InvoiceService
{
    private Order $orderModel;
    private Plan $planModel;
    private User $userModel;
    private Setting $settingModel;

    public function __construct()
    {
        $this->orderModel = new Order();
        $this->planModel = new Plan();
        $this->userModel = new User();
        $this->settingModel = new Setting();
    }

    /**
     * Get invoice data for an order
     * 
     * @param string $orderId Order ID
     * @return array|false Invoice data or false if order not found
     */
    public function getInvoiceData(string $orderId)
    {
        $order = $this->orderModel->findById($orderId);
        
        if (!$order || $order['status'] !== 'SUCCESS') {
            return false;
        }

        $plan = $this->planModel->findById($order['plan_id']);
        $customer = $this->userModel->findById($order['customer_id']);

        if (!$plan || !$customer) {
            return false;
        }

        // Get the total amount paid (this is the selling price - tax inclusive)
        $total = (float)$order['amount'];
        
        // Calculate tax breakdown from plan settings
        $taxPercent = !empty($plan['tax_percent']) ? (float)$plan['tax_percent'] : 0;
        $taxName = $plan['tax_name'] ?? null;
        $taxDescription = $plan['tax_description'] ?? null;
        
        // Calculate net price (subtotal before tax) and tax amount
        // The order amount is tax-inclusive, so we need to extract the tax
        if ($taxPercent > 0) {
            // net_price = total / (1 + tax_percent/100)
            $subtotal = $total / (1 + $taxPercent / 100);
            $tax = $total - $subtotal;
        } else {
            $subtotal = $total;
            $tax = 0;
        }
        
        // Round to 2 decimal places
        $subtotal = round($subtotal, 2);
        $tax = round($tax, 2);

        // Get invoice number (use stored invoice_id or generate one)
        $invoiceNumber = $order['invoice_id'] ?? $this->generateInvoiceNumber($order);

        // Fetch business details from settings
        $businessSettings = $this->settingModel->getMultiple([
            'legal_business_name',
            'legal_address_line1',
            'legal_address_line2',
            'legal_city',
            'legal_state',
            'legal_postal_code',
            'legal_country',
            'billing_email',
            'billing_phone',
            'business_tax_id',
            'logo_light_bg'
        ]);

        return [
            'invoice_number' => $invoiceNumber,
            'invoice_date' => date('F j, Y', strtotime($order['created_at'])),
            'order_id' => $order['id'],
            'payment_id' => $order['pg_payment_id'],
            'payment_method' => $this->formatPaymentMethod($order['payment_method']),
            
            // Company details (seller) - fetched from settings
            'company' => [
                'name' => $businessSettings['legal_business_name'] ?? 'Your Company Name',
                'address_line1' => $businessSettings['legal_address_line1'] ?? '',
                'address_line2' => $businessSettings['legal_address_line2'] ?? '',
                'city' => $businessSettings['legal_city'] ?? '',
                'state' => $businessSettings['legal_state'] ?? '',
                'postal_code' => $businessSettings['legal_postal_code'] ?? '',
                'country' => $businessSettings['legal_country'] ?? '',
                'email' => $businessSettings['billing_email'] ?? '',
                'phone' => $businessSettings['billing_phone'] ?? '',
                'tax_id' => $businessSettings['business_tax_id'] ?? null,
                'logo' => $businessSettings['logo_light_bg'] ?? ''
            ],
            
            // Customer details (bill to) - ordered: name, business, address, contact, tax_id
            'customer' => [
                'name' => $order['billing_full_name'] ?: $customer['name'],
                'business_name' => $order['billing_business_name'] ?: ($customer['business_name'] ?? ''),
                'address_line1' => $order['billing_address_line1'] ?? '',
                'address_line2' => $order['billing_address_line2'] ?? '',
                'city' => $order['billing_city'] ?? '',
                'state' => $order['billing_state'] ?? '',
                'postal_code' => $order['billing_postal_code'] ?? '',
                'country' => $order['billing_country'] ?? '',
                'email' => $customer['email'],
                'phone' => $order['billing_phone'] ?: ($customer['phone'] ?? ''),
                'tax_id' => $order['billing_business_tax_id'] ?? ''
            ],
            
            // Line items
            'items' => [
                [
                    'description' => $plan['name'] . ' Subscription (' . $this->formatBillingPeriod($plan['billing_period_months']) . ')',
                    'period' => date('M j, Y', strtotime($order['created_at'])) . ' - ' . 
                               date('M j, Y', strtotime('+' . $plan['billing_period_months'] . ' months', strtotime($order['created_at']))),
                    'quantity' => 1,
                    'unit_price' => $subtotal,
                    'amount' => $subtotal,
                    'tax_percent' => $taxPercent,
                    'tax_amount' => $tax
                ]
            ],
            
            // Amounts
            'subtotal' => $subtotal,
            'tax' => $tax,
            'tax_percent' => $taxPercent,
            'tax_name' => $taxName,
            'tax_description' => $taxDescription,
            'total' => $total,
            'amount_paid' => $total,
            'currency' => $order['currency'],
            'currency_symbol' => $this->getCurrencySymbol($order['currency']),
            
            // Payment details (single transaction info)
            'payment_details' => [
                'method' => $this->formatPaymentMethod($order['payment_method']),
                'date' => date('F j, Y', strtotime($order['created_at'])),
                'amount' => $total,
                'transaction_id' => $order['pg_payment_id'] ?: 'N/A'
            ]
        ];
    }

    /**
     * Generate invoice number from order
     * Format: billingYear-billingMonth-first8charOfOrderId
     */
    public function generateInvoiceNumber(array $order): string
    {
        $year = date('Y', strtotime($order['created_at']));
        $month = date('m', strtotime($order['created_at']));
        $shortId = strtoupper(substr($order['id'], 0, 8));
        
        return $year . '-' . $month . '-' . $shortId;
    }

    /**
     * Generate and store invoice_id for a successful order
     * 
     * @param string $orderId Order ID
     * @return string|false Invoice ID or false on failure
     */
    public function createInvoiceId(string $orderId)
    {
        $order = $this->orderModel->findById($orderId);
        
        if (!$order || $order['status'] !== 'SUCCESS') {
            return false;
        }

        // Don't regenerate if already exists
        if (!empty($order['invoice_id'])) {
            return $order['invoice_id'];
        }

        $invoiceId = $this->generateInvoiceNumber($order);
        
        // Update order with invoice_id
        $this->orderModel->updateInvoiceId($orderId, $invoiceId);
        
        return $invoiceId;
    }

    /**
     * Format payment method for display
     */
    private function formatPaymentMethod(?string $method): string
    {
        if (!$method) {
            return 'Online Payment';
        }
        
        return match(strtolower($method)) {
            'razorpay' => 'Razorpay',
            'card' => 'Credit/Debit Card',
            'upi' => 'UPI',
            'netbanking' => 'Net Banking',
            'wallet' => 'Wallet',
            default => ucfirst($method)
        };
    }

    /**
     * Format billing period for display
     */
    private function formatBillingPeriod(int $months): string
    {
        return match($months) {
            1 => '1 month',
            3 => '3 months',
            6 => '6 months',
            12 => '1 year',
            default => $months . ' months'
        };
    }

    /**
     * Get currency symbol
     */
    private function getCurrencySymbol(string $currency): string
    {
        return match(strtoupper($currency)) {
            'USD' => '$',
            'INR' => '₹',
            'EUR' => '€',
            'GBP' => '£',
            default => $currency . ' '
        };
    }
}
