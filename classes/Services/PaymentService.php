<?php

namespace Karyalay\Services;

use Razorpay\Api\Api;
use Karyalay\Models\Setting;
use Exception;

/**
 * Payment Service
 * 
 * Handles payment gateway integration with Razorpay
 */
class PaymentService
{
    private Api $razorpay;
    private string $keyId;
    private string $keySecret;
    private string $webhookSecret;

    public function __construct()
    {
        // Load credentials from database settings
        $settingModel = new Setting();
        
        $this->keyId = $settingModel->get('razorpay_key_id', '');
        $this->keySecret = $settingModel->get('razorpay_key_secret', '');
        $this->webhookSecret = $settingModel->get('razorpay_webhook_secret', '');
        
        // Fallback to config file if not in database
        if (empty($this->keyId) || empty($this->keySecret)) {
            $config = require __DIR__ . '/../../config/app.php';
            $this->keyId = $this->keyId ?: ($config['razorpay_key_id'] ?? '');
            $this->keySecret = $this->keySecret ?: ($config['razorpay_key_secret'] ?? '');
            $this->webhookSecret = $this->webhookSecret ?: ($config['razorpay_webhook_secret'] ?? '');
        }
        
        if (empty($this->keyId) || empty($this->keySecret)) {
            throw new Exception('Razorpay credentials not configured. Please configure payment settings in admin panel.');
        }
        
        $this->razorpay = new Api($this->keyId, $this->keySecret);
    }

    /**
     * Create a payment order
     * 
     * @param array $orderData Order data (amount, currency, receipt, notes)
     * @return array Payment order details
     * @throws Exception
     */
    public function createPaymentOrder(array $orderData): array
    {
        try {
            // Amount should be in smallest currency unit (paise for INR, cents for USD)
            $amount = $orderData['amount'] * 100;
            
            $razorpayOrder = $this->razorpay->order->create([
                'amount' => $amount,
                'currency' => $orderData['currency'] ?? 'INR',
                'receipt' => $orderData['receipt'] ?? 'order_' . time(),
                'notes' => $orderData['notes'] ?? []
            ]);
            
            return [
                'success' => true,
                'order_id' => $razorpayOrder['id'],
                'amount' => $razorpayOrder['amount'],
                'currency' => $razorpayOrder['currency'],
                'receipt' => $razorpayOrder['receipt']
            ];
        } catch (Exception $e) {
            error_log('Payment order creation failed: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Verify payment signature
     * 
     * @param array $attributes Payment attributes (razorpay_order_id, razorpay_payment_id, razorpay_signature)
     * @return bool True if signature is valid
     */
    public function verifyPaymentSignature(array $attributes): bool
    {
        try {
            $this->razorpay->utility->verifyPaymentSignature($attributes);
            return true;
        } catch (Exception $e) {
            error_log('Payment signature verification failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Verify webhook signature
     * 
     * @param string $payload Webhook payload
     * @param string $signature Webhook signature from header
     * @return bool True if signature is valid
     */
    public function verifyWebhookSignature(string $payload, string $signature): bool
    {
        try {
            $this->razorpay->utility->verifyWebhookSignature(
                $payload,
                $signature,
                $this->webhookSecret
            );
            return true;
        } catch (Exception $e) {
            error_log('Webhook signature verification failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Fetch payment details
     * 
     * @param string $paymentId Razorpay payment ID
     * @return array|false Payment details or false on failure
     */
    public function fetchPayment(string $paymentId)
    {
        try {
            $payment = $this->razorpay->payment->fetch($paymentId);
            
            return [
                'id' => $payment['id'],
                'order_id' => $payment['order_id'] ?? null,
                'amount' => $payment['amount'] / 100, // Convert back to main currency unit
                'currency' => $payment['currency'],
                'status' => $payment['status'],
                'method' => $payment['method'] ?? null,
                'email' => $payment['email'] ?? null,
                'contact' => $payment['contact'] ?? null,
                'created_at' => $payment['created_at'] ?? null
            ];
        } catch (Exception $e) {
            error_log('Fetch payment failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Get Razorpay key ID for frontend
     * 
     * @return string Razorpay key ID
     */
    public function getKeyId(): string
    {
        return $this->keyId;
    }

    /**
     * Process refund
     * 
     * @param string $paymentId Razorpay payment ID
     * @param float $amount Amount to refund (optional, full refund if not specified)
     * @return array Refund result
     */
    public function processRefund(string $paymentId, ?float $amount = null): array
    {
        try {
            $refundData = ['payment_id' => $paymentId];
            
            if ($amount !== null) {
                $refundData['amount'] = $amount * 100; // Convert to smallest unit
            }
            
            $refund = $this->razorpay->refund->create($refundData);
            
            return [
                'success' => true,
                'refund_id' => $refund['id'],
                'amount' => $refund['amount'] / 100,
                'status' => $refund['status']
            ];
        } catch (Exception $e) {
            error_log('Refund processing failed: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
}
