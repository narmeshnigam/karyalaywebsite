<?php

namespace Karyalay\Tests\Property;

use Eris\Generator;
use Eris\TestTrait;
use PHPUnit\Framework\TestCase;
use Karyalay\Services\TicketService;
use Karyalay\Models\Ticket;
use Karyalay\Models\User;
use Karyalay\Models\Subscription;

/**
 * Property-based tests for ticket creation
 * 
 * Feature: karyalay-portal-system, Property 23: Ticket Creation
 * Validates: Requirements 7.1
 */
class TicketCreationPropertyTest extends TestCase
{
    use TestTrait;

    private TicketService $ticketService;
    private Ticket $ticketModel;
    private User $userModel;
    private Subscription $subscriptionModel;
    private array $testCustomers = [];
    private array $testTickets = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->ticketService = new TicketService();
        $this->ticketModel = new Ticket();
        $this->userModel = new User();
        $this->subscriptionModel = new Subscription();
    }

    protected function tearDown(): void
    {
        // Clean up test tickets
        foreach ($this->testTickets as $ticketId) {
            $this->ticketModel->delete($ticketId);
        }
        
        // Clean up test customers
        foreach ($this->testCustomers as $customerId) {
            $this->userModel->delete($customerId);
        }
        
        parent::tearDown();
    }

    /**
     * Property 23: Ticket Creation
     * 
     * For any valid ticket submission data, when a customer submits a new ticket 
     * form, a ticket record with status OPEN should be created and linked to the 
     * customer account.
     * 
     * Validates: Requirements 7.1
     * 
     * @test
     */
    public function ticketCreationCreatesOpenTicketLinkedToCustomer(): void
    {
        $this->forAll(
            Generator\choose(10, 200),  // subject length
            Generator\elements(['TECHNICAL', 'BILLING', 'GENERAL', 'FEATURE_REQUEST', 'BUG_REPORT']),
            Generator\elements(['LOW', 'MEDIUM', 'HIGH', 'URGENT'])
        )
        ->then(function ($subjectLen, $category, $priority) {
            // Create a test customer
            $customer = $this->createTestCustomer();
            $this->testCustomers[] = $customer['id'];
            
            // Generate ticket data
            $subject = 'Test Ticket ' . str_repeat('X', max(0, $subjectLen - 12));
            $ticketData = [
                'customer_id' => $customer['id'],
                'subject' => $subject,
                'category' => $category,
                'priority' => $priority
            ];
            
            // Act: Create ticket
            $result = $this->ticketService->createTicket($ticketData);
            
            // Assert: Ticket creation succeeded
            $this->assertTrue(
                $result['success'],
                'Ticket creation should succeed with valid data'
            );
            $this->assertArrayNotHasKey('error', $result, 'No error should be returned');
            $this->assertNotNull($result['ticket'], 'Ticket data should be returned');
            
            $ticket = $result['ticket'];
            $this->testTickets[] = $ticket['id'];
            
            // Assert: Ticket has status OPEN
            $this->assertEquals(
                'OPEN',
                $ticket['status'],
                'Newly created ticket should have status OPEN'
            );
            
            // Assert: Ticket is linked to customer account
            $this->assertEquals(
                $customer['id'],
                $ticket['customer_id'],
                'Ticket should be linked to the customer who created it'
            );
            
            // Assert: Ticket has correct subject
            $this->assertEquals($subject, $ticket['subject']);
            
            // Assert: Ticket has correct category
            $this->assertEquals($category, $ticket['category']);
            
            // Assert: Ticket has correct priority
            $this->assertEquals($priority, $ticket['priority']);
            
            // Assert: Ticket exists in database
            $dbTicket = $this->ticketModel->findById($ticket['id']);
            $this->assertNotFalse($dbTicket, 'Ticket should exist in database');
            $this->assertEquals($ticket['id'], $dbTicket['id']);
            $this->assertEquals('OPEN', $dbTicket['status']);
            $this->assertEquals($customer['id'], $dbTicket['customer_id']);
            
            // Assert: Ticket has timestamps
            $this->assertNotNull($ticket['created_at']);
            $this->assertNotNull($ticket['updated_at']);
        });
    }

    /**
     * Property: Ticket creation with subscription ID links to subscription
     * 
     * @test
     */
    public function ticketCreationWithSubscriptionLinksToSubscription(): void
    {
        // Create test customer
        $customer = $this->createTestCustomer();
        $this->testCustomers[] = $customer['id'];
        
        // Create ticket without subscription (subscription is optional)
        $ticketData = [
            'customer_id' => $customer['id'],
            'subscription_id' => null,
            'subject' => 'Test Ticket without Subscription',
            'category' => 'TECHNICAL',
            'priority' => 'MEDIUM'
        ];
        
        $result = $this->ticketService->createTicket($ticketData);
        
        $this->assertTrue($result['success']);
        $ticket = $result['ticket'];
        $this->testTickets[] = $ticket['id'];
        
        // Assert: Ticket can be created without subscription
        $this->assertNull(
            $ticket['subscription_id'],
            'Ticket subscription_id should be null when not provided'
        );
    }

    /**
     * Property: Ticket creation without required fields should fail
     * 
     * @test
     */
    public function ticketCreationWithoutRequiredFieldsFails(): void
    {
        // Test missing customer_id
        $result = $this->ticketService->createTicket([
            'subject' => 'Test Ticket'
        ]);
        $this->assertFalse($result['success']);
        $this->assertNotNull($result['error']);
        
        // Test missing subject
        $customer = $this->createTestCustomer();
        $this->testCustomers[] = $customer['id'];
        
        $result = $this->ticketService->createTicket([
            'customer_id' => $customer['id']
        ]);
        $this->assertFalse($result['success']);
        $this->assertNotNull($result['error']);
    }

    /**
     * Property: Multiple tickets can be created for same customer
     * 
     * @test
     */
    public function multipleTicketsCanBeCreatedForSameCustomer(): void
    {
        $customer = $this->createTestCustomer();
        $this->testCustomers[] = $customer['id'];
        
        // Create first ticket
        $result1 = $this->ticketService->createTicket([
            'customer_id' => $customer['id'],
            'subject' => 'First Ticket',
            'category' => 'TECHNICAL',
            'priority' => 'MEDIUM'
        ]);
        
        $this->assertTrue($result1['success']);
        $this->testTickets[] = $result1['ticket']['id'];
        
        // Create second ticket
        $result2 = $this->ticketService->createTicket([
            'customer_id' => $customer['id'],
            'subject' => 'Second Ticket',
            'category' => 'BILLING',
            'priority' => 'HIGH'
        ]);
        
        $this->assertTrue($result2['success']);
        $this->testTickets[] = $result2['ticket']['id'];
        
        // Assert: Both tickets exist and are different
        $this->assertNotEquals(
            $result1['ticket']['id'],
            $result2['ticket']['id'],
            'Each ticket should have a unique ID'
        );
        
        // Assert: Both tickets belong to same customer
        $this->assertEquals($customer['id'], $result1['ticket']['customer_id']);
        $this->assertEquals($customer['id'], $result2['ticket']['customer_id']);
    }

    /**
     * Property: Ticket creation sets default priority if not provided
     * 
     * @test
     */
    public function ticketCreationSetsDefaultPriorityIfNotProvided(): void
    {
        $customer = $this->createTestCustomer();
        $this->testCustomers[] = $customer['id'];
        
        $result = $this->ticketService->createTicket([
            'customer_id' => $customer['id'],
            'subject' => 'Test Ticket Without Priority'
        ]);
        
        $this->assertTrue($result['success']);
        $ticket = $result['ticket'];
        $this->testTickets[] = $ticket['id'];
        
        // Assert: Default priority is set
        $this->assertNotNull($ticket['priority']);
        $this->assertEquals('MEDIUM', $ticket['priority']);
    }

    /**
     * Helper: Create test customer
     */
    private function createTestCustomer(): array
    {
        $email = 'test_customer_' . bin2hex(random_bytes(8)) . '@example.com';
        
        $userData = [
            'email' => $email,
            'password_hash' => password_hash('password123', PASSWORD_BCRYPT),
            'name' => 'Test Customer',
            'role' => 'CUSTOMER'
        ];
        
        $user = $this->userModel->create($userData);
        return $user;
    }


}

