<?php

namespace Karyalay\Tests\Property;

use Eris\Generator;
use Eris\TestTrait;
use PHPUnit\Framework\TestCase;
use Karyalay\Models\User;
use Karyalay\Models\Subscription;
use Karyalay\Models\Order;
use Karyalay\Models\Ticket;
use Karyalay\Models\Plan;
use Karyalay\Models\Port;

/**
 * Property-based tests for customer detail aggregation
 * 
 * Feature: karyalay-portal-system, Property 33: Customer Detail Aggregation
 * Validates: Requirements 10.2
 */
class CustomerDetailAggregationPropertyTest extends TestCase
{
    use TestTrait;

    private User $userModel;
    private Subscription $subscriptionModel;
    private Order $orderModel;
    private Ticket $ticketModel;
    private Plan $planModel;
    private Port $portModel;

    protected function setUp(): void
    {
        parent::setUp();
        $this->userModel = new User();
        $this->subscriptionModel = new Subscription();
        $this->orderModel = new Order();
        $this->ticketModel = new Ticket();
        $this->planModel = new Plan();
        $this->portModel = new Port();
    }

    protected function tearDown(): void
    {
        parent::tearDown();
    }

    /**
     * Property 33: Customer Detail Aggregation
     * 
     * For any customer, when the customer detail page is accessed,
     * all related subscriptions, orders, and tickets should be displayed.
     * 
     * Validates: Requirements 10.2
     * 
     * @test
     */
    public function customerDetailPageDisplaysAllRelatedData(): void
    {
        $this->forAll(
            Generator\choose(1, 5), // Number of subscriptions
            Generator\choose(1, 5), // Number of orders
            Generator\choose(0, 5)  // Number of tickets
        )
        ->then(function ($numSubscriptions, $numOrders, $numTickets) {
            // Arrange: Create test customer
            $testCustomer = $this->createTestCustomer();
            
            // Arrange: Create test plan
            $testPlan = $this->createTestPlan();
            
            // Arrange: Create test port
            $testPort = $this->createTestPort($testPlan['id']);
            
            // Arrange: Create subscriptions for the customer
            $createdSubscriptions = [];
            for ($i = 0; $i < $numSubscriptions; $i++) {
                $order = $this->createTestOrder($testCustomer['id'], $testPlan['id']);
                $subscription = $this->createTestSubscription(
                    $testCustomer['id'],
                    $testPlan['id'],
                    $order['id'],
                    $testPort['id']
                );
                $createdSubscriptions[] = $subscription;
            }
            
            // Arrange: Create additional orders for the customer
            $createdOrders = [];
            for ($i = 0; $i < $numOrders; $i++) {
                $order = $this->createTestOrder($testCustomer['id'], $testPlan['id']);
                $createdOrders[] = $order;
            }
            
            // Arrange: Create tickets for the customer
            $createdTickets = [];
            for ($i = 0; $i < $numTickets; $i++) {
                $ticket = $this->createTestTicket($testCustomer['id']);
                $createdTickets[] = $ticket;
            }
            
            // Act: Fetch customer profile (as detail page would)
            $customer = $this->userModel->findById($testCustomer['id']);
            
            // Assert: Customer profile is retrieved
            $this->assertNotFalse(
                $customer,
                'Customer detail page should retrieve customer profile'
            );
            
            // Assert: Customer profile contains required fields
            $requiredProfileFields = ['id', 'name', 'email', 'phone', 'business_name', 
                                     'role', 'email_verified', 'created_at'];
            foreach ($requiredProfileFields as $field) {
                $this->assertArrayHasKey(
                    $field,
                    $customer,
                    "Customer profile should include {$field} field"
                );
            }
            
            // Act: Fetch all subscriptions for this customer (as detail page would)
            $db = \Karyalay\Database\Connection::getInstance();
            $subscriptions_sql = "SELECT s.*, p.name as plan_name 
                                 FROM subscriptions s
                                 LEFT JOIN plans p ON s.plan_id = p.id
                                 WHERE s.customer_id = :customer_id
                                 ORDER BY s.created_at DESC";
            $subscriptions_stmt = $db->prepare($subscriptions_sql);
            $subscriptions_stmt->execute([':customer_id' => $testCustomer['id']]);
            $subscriptions = $subscriptions_stmt->fetchAll(\PDO::FETCH_ASSOC);
            
            // Assert: All subscriptions are retrieved
            $this->assertCount(
                $numSubscriptions,
                $subscriptions,
                'Customer detail page should display all customer subscriptions'
            );
            
            // Assert: Each subscription has required fields
            foreach ($subscriptions as $subscription) {
                $this->assertArrayHasKey('id', $subscription);
                $this->assertArrayHasKey('plan_id', $subscription);
                $this->assertArrayHasKey('status', $subscription);
                $this->assertArrayHasKey('start_date', $subscription);
                $this->assertArrayHasKey('end_date', $subscription);
                $this->assertArrayHasKey('plan_name', $subscription);
                $this->assertEquals(
                    $testCustomer['id'],
                    $subscription['customer_id'],
                    'All subscriptions should belong to the customer'
                );
            }
            
            // Act: Fetch all orders for this customer (as detail page would)
            $orders_sql = "SELECT o.*, p.name as plan_name 
                          FROM orders o
                          LEFT JOIN plans p ON o.plan_id = p.id
                          WHERE o.customer_id = :customer_id
                          ORDER BY o.created_at DESC";
            $orders_stmt = $db->prepare($orders_sql);
            $orders_stmt->execute([':customer_id' => $testCustomer['id']]);
            $orders = $orders_stmt->fetchAll(\PDO::FETCH_ASSOC);
            
            // Assert: All orders are retrieved (including those from subscriptions)
            $totalExpectedOrders = $numSubscriptions + $numOrders;
            $this->assertCount(
                $totalExpectedOrders,
                $orders,
                'Customer detail page should display all customer orders'
            );
            
            // Assert: Each order has required fields
            foreach ($orders as $order) {
                $this->assertArrayHasKey('id', $order);
                $this->assertArrayHasKey('plan_id', $order);
                $this->assertArrayHasKey('amount', $order);
                $this->assertArrayHasKey('currency', $order);
                $this->assertArrayHasKey('status', $order);
                $this->assertArrayHasKey('created_at', $order);
                $this->assertEquals(
                    $testCustomer['id'],
                    $order['customer_id'],
                    'All orders should belong to the customer'
                );
            }
            
            // Act: Fetch all tickets for this customer (as detail page would)
            $tickets_sql = "SELECT t.*, a.name as assignee_name 
                           FROM tickets t
                           LEFT JOIN users a ON t.assigned_to = a.id
                           WHERE t.customer_id = :customer_id
                           ORDER BY t.created_at DESC";
            $tickets_stmt = $db->prepare($tickets_sql);
            $tickets_stmt->execute([':customer_id' => $testCustomer['id']]);
            $tickets = $tickets_stmt->fetchAll(\PDO::FETCH_ASSOC);
            
            // Assert: All tickets are retrieved
            $this->assertCount(
                $numTickets,
                $tickets,
                'Customer detail page should display all customer tickets'
            );
            
            // Assert: Each ticket has required fields
            foreach ($tickets as $ticket) {
                $this->assertArrayHasKey('id', $ticket);
                $this->assertArrayHasKey('subject', $ticket);
                $this->assertArrayHasKey('status', $ticket);
                $this->assertArrayHasKey('priority', $ticket);
                $this->assertArrayHasKey('category', $ticket);
                $this->assertArrayHasKey('created_at', $ticket);
                $this->assertEquals(
                    $testCustomer['id'],
                    $ticket['customer_id'],
                    'All tickets should belong to the customer'
                );
            }
            
            // Assert: All three data types are aggregated correctly
            $this->assertTrue(
                count($subscriptions) === $numSubscriptions &&
                count($orders) === $totalExpectedOrders &&
                count($tickets) === $numTickets,
                'Customer detail page should aggregate all subscriptions, orders, and tickets'
            );
            
            // Cleanup
            foreach ($createdTickets as $ticket) {
                $this->cleanupTicket($ticket['id']);
            }
            foreach ($createdOrders as $order) {
                $this->cleanupOrder($order['id']);
            }
            foreach ($createdSubscriptions as $subscription) {
                $this->cleanupOrder($subscription['order_id']);
                $this->cleanupSubscription($subscription['id']);
            }
            $this->cleanupPort($testPort['id']);
            $this->cleanupPlan($testPlan['id']);
            $this->cleanupCustomer($testCustomer['id']);
        });
    }

    /**
     * Property: Customer detail page handles customer with no related data
     * 
     * @test
     */
    public function customerDetailPageHandlesCustomerWithNoRelatedData(): void
    {
        // Arrange: Create customer with no subscriptions, orders, or tickets
        $testCustomer = $this->createTestCustomer();
        
        // Act: Fetch customer profile
        $customer = $this->userModel->findById($testCustomer['id']);
        
        // Assert: Customer profile is retrieved
        $this->assertNotFalse($customer);
        
        // Act: Fetch subscriptions, orders, and tickets
        $db = \Karyalay\Database\Connection::getInstance();
        
        $subscriptions_stmt = $db->prepare("SELECT * FROM subscriptions WHERE customer_id = :customer_id");
        $subscriptions_stmt->execute([':customer_id' => $testCustomer['id']]);
        $subscriptions = $subscriptions_stmt->fetchAll(\PDO::FETCH_ASSOC);
        
        $orders_stmt = $db->prepare("SELECT * FROM orders WHERE customer_id = :customer_id");
        $orders_stmt->execute([':customer_id' => $testCustomer['id']]);
        $orders = $orders_stmt->fetchAll(\PDO::FETCH_ASSOC);
        
        $tickets_stmt = $db->prepare("SELECT * FROM tickets WHERE customer_id = :customer_id");
        $tickets_stmt->execute([':customer_id' => $testCustomer['id']]);
        $tickets = $tickets_stmt->fetchAll(\PDO::FETCH_ASSOC);
        
        // Assert: All collections are empty
        $this->assertEmpty($subscriptions, 'Customer with no subscriptions should return empty array');
        $this->assertEmpty($orders, 'Customer with no orders should return empty array');
        $this->assertEmpty($tickets, 'Customer with no tickets should return empty array');
        
        // Cleanup
        $this->cleanupCustomer($testCustomer['id']);
    }

    /**
     * Property: Customer detail aggregation includes correct customer ID
     * 
     * @test
     */
    public function customerDetailAggregationIncludesCorrectCustomerId(): void
    {
        // Arrange: Create two customers
        $customer1 = $this->createTestCustomer();
        $customer2 = $this->createTestCustomer();
        
        $testPlan = $this->createTestPlan();
        $testPort = $this->createTestPort($testPlan['id']);
        
        // Arrange: Create data for customer 1
        $order1 = $this->createTestOrder($customer1['id'], $testPlan['id']);
        $subscription1 = $this->createTestSubscription($customer1['id'], $testPlan['id'], $order1['id'], $testPort['id']);
        $ticket1 = $this->createTestTicket($customer1['id']);
        
        // Arrange: Create data for customer 2
        $order2 = $this->createTestOrder($customer2['id'], $testPlan['id']);
        $subscription2 = $this->createTestSubscription($customer2['id'], $testPlan['id'], $order2['id'], $testPort['id']);
        $ticket2 = $this->createTestTicket($customer2['id']);
        
        // Act: Fetch data for customer 1
        $db = \Karyalay\Database\Connection::getInstance();
        
        $subscriptions_stmt = $db->prepare("SELECT * FROM subscriptions WHERE customer_id = :customer_id");
        $subscriptions_stmt->execute([':customer_id' => $customer1['id']]);
        $customer1_subscriptions = $subscriptions_stmt->fetchAll(\PDO::FETCH_ASSOC);
        
        $orders_stmt = $db->prepare("SELECT * FROM orders WHERE customer_id = :customer_id");
        $orders_stmt->execute([':customer_id' => $customer1['id']]);
        $customer1_orders = $orders_stmt->fetchAll(\PDO::FETCH_ASSOC);
        
        $tickets_stmt = $db->prepare("SELECT * FROM tickets WHERE customer_id = :customer_id");
        $tickets_stmt->execute([':customer_id' => $customer1['id']]);
        $customer1_tickets = $tickets_stmt->fetchAll(\PDO::FETCH_ASSOC);
        
        // Assert: Customer 1 data only includes customer 1's records
        $this->assertCount(1, $customer1_subscriptions);
        $this->assertEquals($customer1['id'], $customer1_subscriptions[0]['customer_id']);
        
        $this->assertCount(1, $customer1_orders);
        $this->assertEquals($customer1['id'], $customer1_orders[0]['customer_id']);
        
        $this->assertCount(1, $customer1_tickets);
        $this->assertEquals($customer1['id'], $customer1_tickets[0]['customer_id']);
        
        // Assert: Customer 1 data does not include customer 2's records
        foreach ($customer1_subscriptions as $sub) {
            $this->assertNotEquals($customer2['id'], $sub['customer_id']);
        }
        foreach ($customer1_orders as $order) {
            $this->assertNotEquals($customer2['id'], $order['customer_id']);
        }
        foreach ($customer1_tickets as $ticket) {
            $this->assertNotEquals($customer2['id'], $ticket['customer_id']);
        }
        
        // Cleanup
        $this->cleanupTicket($ticket1['id']);
        $this->cleanupTicket($ticket2['id']);
        $this->cleanupOrder($order1['id']);
        $this->cleanupOrder($order2['id']);
        $this->cleanupSubscription($subscription1['id']);
        $this->cleanupSubscription($subscription2['id']);
        $this->cleanupPort($testPort['id']);
        $this->cleanupPlan($testPlan['id']);
        $this->cleanupCustomer($customer1['id']);
        $this->cleanupCustomer($customer2['id']);
    }

    /**
     * Helper: Create test customer
     */
    private function createTestCustomer(): array
    {
        $email = 'customer_' . bin2hex(random_bytes(8)) . '@example.com';
        $userData = [
            'email' => $email,
            'password_hash' => password_hash('password123', PASSWORD_BCRYPT),
            'name' => 'Test Customer ' . bin2hex(random_bytes(4)),
            'phone' => '555' . rand(1000000, 9999999),
            'business_name' => 'Test Business ' . bin2hex(random_bytes(4)),
            'role' => 'CUSTOMER',
            'email_verified' => true
        ];
        
        return $this->userModel->create($userData);
    }

    /**
     * Helper: Create test plan
     */
    private function createTestPlan(): array
    {
        $slug = 'test-plan-' . bin2hex(random_bytes(8));
        $planData = [
            'name' => 'Test Plan ' . bin2hex(random_bytes(4)),
            'slug' => $slug,
            'description' => 'Test plan for customer detail testing',
            'price' => rand(50, 500),
            'currency' => 'USD',
            'billing_period_months' => rand(1, 12),
            'features' => json_encode(['Feature 1', 'Feature 2']),
            'status' => 'ACTIVE'
        ];
        
        return $this->planModel->create($planData);
    }

    /**
     * Helper: Create test port
     */
    private function createTestPort(string $planId): array
    {
        $instanceUrl = 'https://test-' . bin2hex(random_bytes(4)) . '.karyalay.com';
        $portData = [
            'instance_url' => $instanceUrl,
            'port_number' => rand(8000, 9000),
            'plan_id' => $planId,
            'status' => 'AVAILABLE'
        ];
        
        return $this->portModel->create($portData);
    }

    /**
     * Helper: Create test order
     */
    private function createTestOrder(string $customerId, string $planId): array
    {
        $plan = $this->planModel->findById($planId);
        
        $orderData = [
            'customer_id' => $customerId,
            'plan_id' => $planId,
            'amount' => !empty($plan['discounted_price']) ? $plan['discounted_price'] : $plan['mrp'],
            'currency' => $plan['currency'],
            'status' => 'SUCCESS',
            'payment_method' => 'card'
        ];
        
        return $this->orderModel->create($orderData);
    }

    /**
     * Helper: Create test subscription
     */
    private function createTestSubscription(
        string $customerId,
        string $planId,
        string $orderId,
        string $portId
    ): array {
        $subscriptionData = [
            'customer_id' => $customerId,
            'plan_id' => $planId,
            'order_id' => $orderId,
            'status' => 'ACTIVE',
            'assigned_port_id' => $portId
        ];
        
        return $this->subscriptionModel->create($subscriptionData);
    }

    /**
     * Helper: Create test ticket
     */
    private function createTestTicket(string $customerId): array
    {
        $ticketData = [
            'customer_id' => $customerId,
            'subject' => 'Test Ticket ' . bin2hex(random_bytes(4)),
            'category' => 'Technical',
            'priority' => 'MEDIUM',
            'status' => 'OPEN'
        ];
        
        return $this->ticketModel->create($ticketData);
    }

    /**
     * Helper: Clean up customer
     */
    private function cleanupCustomer(string $customerId): void
    {
        $this->userModel->delete($customerId);
    }

    /**
     * Helper: Clean up plan
     */
    private function cleanupPlan(string $planId): void
    {
        $this->planModel->delete($planId);
    }

    /**
     * Helper: Clean up port
     */
    private function cleanupPort(string $portId): void
    {
        $this->portModel->delete($portId);
    }

    /**
     * Helper: Clean up order
     */
    private function cleanupOrder(string $orderId): void
    {
        $this->orderModel->delete($orderId);
    }

    /**
     * Helper: Clean up subscription
     */
    private function cleanupSubscription(string $subscriptionId): void
    {
        $this->subscriptionModel->delete($subscriptionId);
    }

    /**
     * Helper: Clean up ticket
     */
    private function cleanupTicket(string $ticketId): void
    {
        $this->ticketModel->delete($ticketId);
    }
}
