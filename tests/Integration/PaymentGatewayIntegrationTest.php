<?php

namespace Karyalay\Tests\Integration;

use PHPUnit\Framework\TestCase;
use Karyalay\Services\PaymentService;
use Karyalay\Services\OrderService;
use Karyalay\Models\User;
use Karyalay\Models\Plan;
use Karyalay\Models\Order;

/**
 * Integration Test: Payment Gateway Integration
 * 
 * Tests payment gateway integration with Razorpay.
 * Note: These tests will skip if live credentials are not configured.
 */
class PaymentGatewayIntegrationTest extends TestCase
{
    private ?PaymentService $paymentService = null;
    private OrderService $orderService;
    private User $userModel;
    private Plan $planModel;
    private Order $orderModel;
    
    private array $testUsers = [];
    private array $testOrders = [];
    private bool $skipTests = false;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Try to initialize payment service
        try {
            $this->paymentService = new PaymentService();
        } catch (\Exception $e) {
            $this->skipTests = true;
            $this->markTestSkipped('Payment gateway credentials not configured: ' . $e->getMessage());
        }
        
        $this->orderService = new OrderService();
        $this->userModel = new User();
        $this->planModel = new Plan();
        $this->orderModel = new Order();
    }

    protected function tearDown(): void
    {
        // Clean up test data
        foreach ($this->testOrders as $orderId) {
            $this->orderModel->delete($orderId);
        }
        
        foreach ($this->testUsers as $userId) {
            $this->userModel->delete($userId);
        }
        
        parent::tearDown();
    }

    /**
     * Test payment order creation
     * 
     * @test
     */
    public function testPaymentOrderCreation(): void
    {
        if ($this->skipTests) {
            $this->markTestSkipped('Payment gateway not configured');
        }
        
        // Create test user
        $user = $this->createTestUser();
        $this->testUsers[] = $user['id'];
        
        // Get active plan
        $plans = $this->planModel->findAll(['status' => 'ACTIVE'], 1, 0);
        $this->assertNotEmpty($plans, 'At least one active plan should exist');
        $plan = $plans[0];
        
        // Create order in our system
        $order = $this->orderService->createOrder($user['id'], $plan['id']);
        $this->assertNotFalse($order, 'Order creation should succeed');
        $this->testOrders[] = $order['id'];
        
        // Create payment order with gateway
        $paymentOrderData = [
            'amount' => $order['amount'],
            'currency' => $order['currency'],
            'receipt' => 'order_' . $order['id'],
            'notes' => [
                'order_id' => $order['id'],
                'customer_id' => $user['id'],
                'plan_id' => $plan['id']
            ]
        ];
        
        $paymentResult = $this->paymentService->createPaymentOrder($paymentOrderData);
        
        $this->assertTrue(
            $paymentResult['success'],
            'Payment order creation should succeed'
        );
        $this->assertNotEmpty($paymentResult['order_id']);
        $this->assertEquals($order['amount'] * 100, $paymentResult['amount']); // Amount in smallest unit
        $this->assertEquals($order['currency'], $paymentResult['currency']);
        
        // Update our order with payment gateway ID
        $updateResult = $this->orderService->updatePaymentGatewayId(
            $order['id'],
            $paymentResult['order_id']
        );
        
        $this->assertTrue($updateResult, 'Payment gateway ID update should succeed');
        
        // Verify order has payment gateway order ID
        $updatedOrder = $this->orderService->getOrder($order['id']);
        $this->assertEquals($paymentResult['order_id'], $updatedOrder['pg_order_id']);
    }

    /**
     * Test payment signature verification
     * 
     * @test
     */
    public function testPaymentSignatureVerification(): void
    {
        if ($this->skipTests) {
            $this->markTestSkipped('Payment gateway not configured');
        }
        
        // Note: This test uses mock data since we can't actually complete a payment
        // In a real scenario, you would use test mode credentials and complete a test payment
        
        // Create test signature data (this would come from Razorpay callback)
        $mockAttributes = [
            'razorpay_order_id' => 'order_test_' . bin2hex(random_bytes(8)),
            'razorpay_payment_id' => 'pay_test_' . bin2hex(random_bytes(8)),
            'razorpay_signature' => 'mock_signature_' . bin2hex(random_bytes(16))
        ];
        
        // This will fail with mock data, which is expected
        $isValid = $this->paymentService->verifyPaymentSignature($mockAttributes);
        
        $this->assertFalse(
            $isValid,
            'Mock signature should not be valid (expected behavior)'
        );
        
        // In a real test environment with test mode, you would:
        // 1. Create a payment order
        // 2. Complete payment using test card
        // 3. Receive callback with real signature
        // 4. Verify that signature
    }

    /**
     * Test fetching payment details
     * 
     * @test
     */
    public function testFetchPaymentDetails(): void
    {
        if ($this->skipTests) {
            $this->markTestSkipped('Payment gateway not configured');
        }
        
        // Try to fetch a non-existent payment (will fail, which is expected)
        $mockPaymentId = 'pay_test_nonexistent';
        
        $paymentDetails = $this->paymentService->fetchPayment($mockPaymentId);
        
        $this->assertFalse(
            $paymentDetails,
            'Fetching non-existent payment should return false'
        );
        
        // In a real test environment, you would:
        // 1. Create and complete a test payment
        // 2. Fetch the payment details
        // 3. Verify the details match what was sent
    }

    /**
     * Test webhook signature verification
     * 
     * @test
     */
    public function testWebhookSignatureVerification(): void
    {
        if ($this->skipTests) {
            $this->markTestSkipped('Payment gateway not configured');
        }
        
        // Mock webhook payload
        $mockPayload = json_encode([
            'event' => 'payment.captured',
            'payload' => [
                'payment' => [
                    'entity' => [
                        'id' => 'pay_test_' . bin2hex(random_bytes(8)),
                        'amount' => 50000,
                        'currency' => 'INR',
                        'status' => 'captured'
                    ]
                ]
            ]
        ]);
        
        $mockSignature = 'mock_webhook_signature_' . bin2hex(random_bytes(16));
        
        // This will fail with mock data, which is expected
        $isValid = $this->paymentService->verifyWebhookSignature($mockPayload, $mockSignature);
        
        $this->assertFalse(
            $isValid,
            'Mock webhook signature should not be valid (expected behavior)'
        );
    }

    /**
     * Test complete payment flow (mocked)
     * 
     * @test
     */
    public function testCompletePaymentFlowWithMockData(): void
    {
        if ($this->skipTests) {
            $this->markTestSkipped('Payment gateway not configured');
        }
        
        // ===== STEP 1: Create User and Order =====
        $user = $this->createTestUser();
        $this->testUsers[] = $user['id'];
        
        $plans = $this->planModel->findAll(['status' => 'ACTIVE'], 1, 0);
        $this->assertNotEmpty($plans);
        $plan = $plans[0];
        
        $order = $this->orderService->createOrder($user['id'], $plan['id']);
        $this->assertNotFalse($order);
        $this->testOrders[] = $order['id'];
        
        // ===== STEP 2: Create Payment Order =====
        $paymentOrderData = [
            'amount' => $order['amount'],
            'currency' => $order['currency'],
            'receipt' => 'order_' . $order['id']
        ];
        
        $paymentResult = $this->paymentService->createPaymentOrder($paymentOrderData);
        
        if (!$paymentResult['success']) {
            $this->markTestSkipped('Payment order creation failed: ' . ($paymentResult['error'] ?? 'Unknown error'));
        }
        
        $this->assertTrue($paymentResult['success']);
        
        // ===== STEP 3: Update Order with Payment Gateway ID =====
        $this->orderService->updatePaymentGatewayId(
            $order['id'],
            $paymentResult['order_id']
        );
        
        // ===== STEP 4: Simulate Payment Success =====
        // In a real scenario, payment would be completed via Razorpay UI
        // and webhook would be called. Here we simulate the success.
        
        $paymentSuccess = $this->orderService->updateOrderStatus($order['id'], 'SUCCESS');
        $this->assertTrue($paymentSuccess);
        
        // ===== STEP 5: Verify Order Status =====
        $finalOrder = $this->orderService->getOrder($order['id']);
        $this->assertEquals('SUCCESS', $finalOrder['status']);
        $this->assertEquals($paymentResult['order_id'], $finalOrder['pg_order_id']);
    }

    /**
     * Test payment failure handling
     * 
     * @test
     */
    public function testPaymentFailureHandling(): void
    {
        // Create user and order
        $user = $this->createTestUser();
        $this->testUsers[] = $user['id'];
        
        $plans = $this->planModel->findAll(['status' => 'ACTIVE'], 1, 0);
        $this->assertNotEmpty($plans);
        $plan = $plans[0];
        
        $order = $this->orderService->createOrder($user['id'], $plan['id']);
        $this->assertNotFalse($order);
        $this->testOrders[] = $order['id'];
        
        // Simulate payment failure
        $failureResult = $this->orderService->updateOrderStatus($order['id'], 'FAILED');
        $this->assertTrue($failureResult);
        
        // Verify order status
        $failedOrder = $this->orderService->getOrder($order['id']);
        $this->assertEquals('FAILED', $failedOrder['status']);
        
        // Verify no subscription was created for failed payment
        // (This would be checked in the actual payment webhook handler)
    }

    /**
     * Test refund processing (if credentials available)
     * 
     * @test
     */
    public function testRefundProcessing(): void
    {
        if ($this->skipTests) {
            $this->markTestSkipped('Payment gateway not configured');
        }
        
        // Try to process refund for non-existent payment
        $mockPaymentId = 'pay_test_nonexistent';
        
        $refundResult = $this->paymentService->processRefund($mockPaymentId, 100.00);
        
        $this->assertFalse(
            $refundResult['success'],
            'Refund for non-existent payment should fail'
        );
        $this->assertNotEmpty($refundResult['error']);
        
        // In a real test environment, you would:
        // 1. Create and complete a test payment
        // 2. Process a refund for that payment
        // 3. Verify refund was successful
    }

    /**
     * Test getting Razorpay key ID for frontend
     * 
     * @test
     */
    public function testGetKeyIdForFrontend(): void
    {
        if ($this->skipTests) {
            $this->markTestSkipped('Payment gateway not configured');
        }
        
        $keyId = $this->paymentService->getKeyId();
        
        $this->assertNotEmpty($keyId, 'Key ID should not be empty');
        $this->assertIsString($keyId, 'Key ID should be a string');
    }

    /**
     * Test order statistics after multiple payments
     * 
     * @test
     */
    public function testOrderStatisticsAfterMultiplePayments(): void
    {
        // Create user
        $user = $this->createTestUser();
        $this->testUsers[] = $user['id'];
        
        $plans = $this->planModel->findAll(['status' => 'ACTIVE'], 1, 0);
        $this->assertNotEmpty($plans);
        $plan = $plans[0];
        
        // Create multiple orders with different statuses
        $successOrder = $this->orderService->createOrder($user['id'], $plan['id']);
        $this->testOrders[] = $successOrder['id'];
        $this->orderService->updateOrderStatus($successOrder['id'], 'SUCCESS');
        
        $failedOrder = $this->orderService->createOrder($user['id'], $plan['id']);
        $this->testOrders[] = $failedOrder['id'];
        $this->orderService->updateOrderStatus($failedOrder['id'], 'FAILED');
        
        $pendingOrder = $this->orderService->createOrder($user['id'], $plan['id']);
        $this->testOrders[] = $pendingOrder['id'];
        // Leave as PENDING
        
        // Get statistics
        $stats = $this->orderService->getCustomerOrderStatistics($user['id']);
        
        $this->assertEquals(3, $stats['total_orders']);
        $this->assertEquals(1, $stats['successful_orders']);
        $this->assertEquals(1, $stats['failed_orders']);
        $this->assertEquals(1, $stats['pending_orders']);
        $effectivePrice = !empty($plan['discounted_price']) ? $plan['discounted_price'] : $plan['mrp'];
        $this->assertEquals($effectivePrice, $stats['total_spent']);
    }

    /**
     * Helper: Create test user
     */
    private function createTestUser(): array
    {
        $email = 'payment_test_' . bin2hex(random_bytes(8)) . '@example.com';
        
        return $this->userModel->create([
            'email' => $email,
            'password' => 'Password123!',
            'name' => 'Payment Test User',
            'role' => 'CUSTOMER',
            'email_verified' => false
        ]);
    }
}
