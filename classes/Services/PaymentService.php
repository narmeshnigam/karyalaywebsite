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
        
        // Debug logging
        error_log('PaymentService: Loading credentials from database');
        error_log('PaymentService: Key ID from DB: ' . (empty($this->keyId) ? 'EMPTY' : substr($this->keyId, 0, 15) . '...'));
        error_log('PaymentService: Key Secret from DB: ' . (empty($this->keySecret) ? 'EMPTY' : 'SET (length: ' . strlen($this->keySecret) . ')'));
        
        // Fallback to config file if not in database
        if (empty($this->keyId) || empty($this->keySecret)) {
            error_log('PaymentService: Credentials not in DB, trying config file fallback');
            $config = require __DIR__ . '/../../config/app.php';
            $this->keyId = $this->keyId ?: ($config['razorpay_key_id'] ?? '');
            $this->keySecret = $this->keySecret ?: ($config['razorpay_key_secret'] ?? '');
            $this->webhookSecret = $this->webhookSecret ?: ($config['razorpay_webhook_secret'] ?? '');
            
            error_log('PaymentService: Key ID from config: ' . (empty($this->keyId) ? 'EMPTY' : substr($this->keyId, 0, 15) . '...'));
            error_log('PaymentService: Key Secret from config: ' . (empty($this->keySecret) ? 'EMPTY' : 'SET'));
        }
        
        if (empty($this->keyId) || empty($this->keySecret)) {
            error_log('PaymentService: FATAL - No credentials found in DB or config');
            throw new Exception('Razorpay credentials not configured. Please configure payment settings in admin panel.');
        }
        
        error_log('PaymentService: Initializing Razorpay API with key: ' . substr($this->keyId, 0, 15) . '...');
        $this->razorpay = new Api($this->keyId, $this->keySecret);
        error_log('PaymentService: Razorpay API initialized successfully');
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
            $amount = (int)($orderData['amount'] * 100);
            $currency = $orderData['currency'] ?? 'INR';
            $receipt = $orderData['receipt'] ?? 'order_' . time();
            
            error_log('PaymentService::createPaymentOrder - Amount: ' . $amount . ' (original: ' . $orderData['amount'] . ')');
            error_log('PaymentService::createPaymentOrder - Currency: ' . $currency);
            error_log('PaymentService::createPaymentOrder - Receipt: ' . $receipt);
            
            $razorpayOrder = $this->razorpay->order->create([
                'amount' => $amount,
                'currency' => $currency,
                'receipt' => $receipt,
                'notes' => $orderData['notes'] ?? []
            ]);
            
            error_log('PaymentService::createPaymentOrder - Success! Order ID: ' . $razorpayOrder['id']);
            
            return [
                'success' => true,
                'order_id' => $razorpayOrder['id'],
                'amount' => $razorpayOrder['amount'],
                'currency' => $razorpayOrder['currency'],
                'receipt' => $razorpayOrder['receipt']
            ];
        } catch (Exception $e) {
            error_log('PaymentService::createPaymentOrder - FAILED: ' . $e->getMessage());
            error_log('PaymentService::createPaymentOrder - Error class: ' . get_class($e));
            error_log('PaymentService::createPaymentOrder - Stack trace: ' . $e->getTraceAsString());
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

    /**
     * Test Razorpay credentials without full initialization
     * 
     * @param string $keyId Razorpay Key ID
     * @param string $keySecret Razorpay Key Secret
     * @return array Test result with success status and message
     */
    public static function testCredentials(string $keyId, string $keySecret): array
    {
        try {
            // Validate key format
            if (!preg_match('/^rzp_(test|live)_[a-zA-Z0-9]+$/', $keyId)) {
                return [
                    'success' => false,
                    'message' => 'Invalid Razorpay Key ID format. It should start with rzp_test_ or rzp_live_'
                ];
            }

            // Create API instance and test connection
            $api = new Api($keyId, $keySecret);
            
            // Try to fetch orders (empty list is fine, we just want to verify credentials)
            $api->order->all(['count' => 1]);
            
            return [
                'success' => true,
                'message' => 'Razorpay credentials verified successfully!',
                'mode' => strpos($keyId, 'rzp_test_') === 0 ? 'test' : 'live'
            ];
        } catch (Exception $e) {
            $errorMessage = $e->getMessage();
            
            // Provide more helpful error messages
            if (strpos($errorMessage, 'Authentication failed') !== false || 
                strpos($errorMessage, '401') !== false) {
                $errorMessage = 'Authentication failed. Please check your Razorpay Key ID and Key Secret.';
            } elseif (strpos($errorMessage, 'curl') !== false || 
                      strpos($errorMessage, 'Could not resolve') !== false) {
                $errorMessage = 'Network error. Unable to connect to Razorpay servers.';
            }
            
            return [
                'success' => false,
                'message' => $errorMessage
            ];
        }
    }

    /**
     * Create a test payment order
     * 
     * @param string $keyId Razorpay Key ID
     * @param string $keySecret Razorpay Key Secret
     * @param float $amount Amount in INR (default 1.00)
     * @return array Order creation result
     */
    public static function createTestOrder(string $keyId, string $keySecret, float $amount = 1.00): array
    {
        try {
            $api = new Api($keyId, $keySecret);
            
            $order = $api->order->create([
                'amount' => (int)($amount * 100), // Convert to paise
                'currency' => 'INR',
                'receipt' => 'test_' . time() . '_' . bin2hex(random_bytes(4)),
                'notes' => [
                    'purpose' => 'Payment Gateway Test',
                    'admin_test' => 'true'
                ]
            ]);
            
            return [
                'success' => true,
                'order_id' => $order['id'],
                'amount' => $order['amount'],
                'currency' => $order['currency'],
                'key_id' => $keyId
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Failed to create test order: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Verify and refund a test payment
     * 
     * @param string $keyId Razorpay Key ID
     * @param string $keySecret Razorpay Key Secret
     * @param string $orderId Razorpay Order ID
     * @param string $paymentId Razorpay Payment ID
     * @param string $signature Razorpay Signature
     * @return array Verification and refund result
     */
    public static function verifyAndRefundTestPayment(
        string $keyId, 
        string $keySecret, 
        string $orderId, 
        string $paymentId, 
        string $signature
    ): array {
        try {
            $api = new Api($keyId, $keySecret);
            
            // Verify signature
            $attributes = [
                'razorpay_order_id' => $orderId,
                'razorpay_payment_id' => $paymentId,
                'razorpay_signature' => $signature
            ];
            
            $api->utility->verifyPaymentSignature($attributes);
            
            // Fetch payment details
            $payment = $api->payment->fetch($paymentId);
            
            // Initiate refund
            $refundResult = null;
            try {
                $refund = $api->refund->create([
                    'payment_id' => $paymentId,
                    'amount' => $payment['amount'],
                    'notes' => [
                        'reason' => 'Test payment auto-refund',
                        'admin_test' => 'true'
                    ]
                ]);
                $refundResult = [
                    'refund_id' => $refund['id'],
                    'status' => $refund['status'],
                    'amount' => $refund['amount'] / 100
                ];
            } catch (Exception $refundError) {
                $refundResult = [
                    'error' => 'Auto-refund failed: ' . $refundError->getMessage()
                ];
            }
            
            return [
                'success' => true,
                'message' => 'Payment verified successfully!',
                'payment' => [
                    'id' => $payment['id'],
                    'amount' => $payment['amount'] / 100,
                    'currency' => $payment['currency'],
                    'status' => $payment['status'],
                    'method' => $payment['method'] ?? 'N/A'
                ],
                'refund' => $refundResult
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Payment verification failed: ' . $e->getMessage()
            ];
        }
    }
}
