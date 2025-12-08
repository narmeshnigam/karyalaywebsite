<?php

namespace Karyalay\Tests\Property;

use Eris\Generator;
use Eris\TestTrait;
use PHPUnit\Framework\TestCase;
use Karyalay\Services\TicketService;
use Karyalay\Models\Ticket;
use Karyalay\Models\TicketMessage;
use Karyalay\Models\User;

/**
 * Property-based tests for internal note visibility restriction
 * 
 * Feature: karyalay-portal-system, Property 37: Internal Note Visibility Restriction
 * Validates: Requirements 11.5
 */
class InternalNoteVisibilityRestrictionPropertyTest extends TestCase
{
    use TestTrait;

    private TicketService $ticketService;
    private Ticket $ticketModel;
    private TicketMessage $messageModel;
    private User $userModel;
    private array $testCustomers = [];
    private array $testTickets = [];
    private array $testMessages = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->ticketService = new TicketService();
        $this->ticketModel = new Ticket();
        $this->messageModel = new TicketMessage();
        $this->userModel = new User();
    }

    protected function tearDown(): void
    {
        // Clean up test messages
        foreach ($this->testMessages as $messageId) {
            $this->messageModel->delete($messageId);
        }
        
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
     * Property 37: Internal Note Visibility Restriction
     * 
     * For any internal note added to a ticket, when the note is stored, it should 
     * be excluded from customer-visible content but visible to admins.
     * 
     * Validates: Requirements 11.5
     * 
     * @test
     */
    public function internalNotesAreExcludedFromCustomerVisibleContent(): void
    {
        $this->forAll(
            Generator\choose(1, 10),  // number of internal notes
            Generator\choose(1, 10)   // number of public messages
        )
        ->then(function ($internalCount, $publicCount) {
            // Create test customer and admin
            $customer = $this->createTestCustomer();
            $this->testCustomers[] = $customer['id'];
            
            $admin = $this->createTestAdmin();
            $this->testCustomers[] = $admin['id'];
            
            // Create test ticket
            $ticket = $this->createTestTicket($customer['id']);
            $this->testTickets[] = $ticket['id'];
            
            // Create internal notes
            $internalNoteIds = [];
            for ($i = 0; $i < $internalCount; $i++) {
                $result = $this->ticketService->createMessage([
                    'ticket_id' => $ticket['id'],
                    'author_id' => $admin['id'],
                    'author_type' => 'ADMIN',
                    'content' => "Internal note $i",
                    'is_internal' => true,
                    'attachments' => []
                ]);
                
                $this->assertTrue($result['success'], 'Internal note creation should succeed');
                $internalNoteIds[] = $result['message']['id'];
                $this->testMessages[] = $result['message']['id'];
                
                usleep(5000); // 5ms delay
            }
            
            // Create public messages
            $publicMessageIds = [];
            for ($i = 0; $i < $publicCount; $i++) {
                $result = $this->ticketService->createMessage([
                    'ticket_id' => $ticket['id'],
                    'author_id' => $admin['id'],
                    'author_type' => 'ADMIN',
                    'content' => "Public message $i",
                    'is_internal' => false,
                    'attachments' => []
                ]);
                
                $this->assertTrue($result['success'], 'Public message creation should succeed');
                $publicMessageIds[] = $result['message']['id'];
                $this->testMessages[] = $result['message']['id'];
                
                usleep(5000); // 5ms delay
            }
            
            // Act: Get customer-visible messages
            $customerResult = $this->ticketService->getCustomerVisibleMessages($ticket['id']);
            
            // Assert: Customer-visible messages retrieved successfully
            $this->assertTrue($customerResult['success']);
            $customerMessages = $customerResult['messages'];
            
            // Assert: Only public messages are returned
            $this->assertCount(
                $publicCount,
                $customerMessages,
                "Customer should see only $publicCount public messages, not internal notes"
            );
            
            // Assert: No internal notes in customer-visible messages
            $customerMessageIds = array_column($customerMessages, 'id');
            foreach ($internalNoteIds as $internalId) {
                $this->assertNotContains(
                    $internalId,
                    $customerMessageIds,
                    'Internal notes should not be visible to customers'
                );
            }
            
            // Assert: All public messages are in customer-visible messages
            foreach ($publicMessageIds as $publicId) {
                $this->assertContains(
                    $publicId,
                    $customerMessageIds,
                    'Public messages should be visible to customers'
                );
            }
            
            // Assert: All customer-visible messages have is_internal = false
            foreach ($customerMessages as $message) {
                $this->assertFalse(
                    (bool)$message['is_internal'],
                    'All customer-visible messages should have is_internal = false'
                );
            }
            
            // Act: Get all messages (admin view)
            $adminResult = $this->ticketService->getTicketMessages($ticket['id'], true);
            
            // Assert: Admin can see all messages
            $this->assertTrue($adminResult['success']);
            $adminMessages = $adminResult['messages'];
            
            $this->assertCount(
                $internalCount + $publicCount,
                $adminMessages,
                'Admin should see all messages including internal notes'
            );
            
            // Assert: All internal notes are in admin view
            $adminMessageIds = array_column($adminMessages, 'id');
            foreach ($internalNoteIds as $internalId) {
                $this->assertContains(
                    $internalId,
                    $adminMessageIds,
                    'Internal notes should be visible to admins'
                );
            }
            
            // Assert: All public messages are in admin view
            foreach ($publicMessageIds as $publicId) {
                $this->assertContains(
                    $publicId,
                    $adminMessageIds,
                    'Public messages should be visible to admins'
                );
            }
        });
    }

    /**
     * Property: Internal notes are marked with is_internal flag
     * 
     * @test
     */
    public function internalNotesHaveInternalFlagSet(): void
    {
        // Create test customer and admin
        $customer = $this->createTestCustomer();
        $this->testCustomers[] = $customer['id'];
        
        $admin = $this->createTestAdmin();
        $this->testCustomers[] = $admin['id'];
        
        // Create test ticket
        $ticket = $this->createTestTicket($customer['id']);
        $this->testTickets[] = $ticket['id'];
        
        // Create internal note
        $result = $this->ticketService->createMessage([
            'ticket_id' => $ticket['id'],
            'author_id' => $admin['id'],
            'author_type' => 'ADMIN',
            'content' => 'This is an internal note',
            'is_internal' => true,
            'attachments' => []
        ]);
        
        $this->assertTrue($result['success']);
        $message = $result['message'];
        $this->testMessages[] = $message['id'];
        
        // Assert: Message has is_internal flag set
        $this->assertTrue(
            (bool)$message['is_internal'],
            'Internal note should have is_internal flag set to true'
        );
        
        // Assert: Message is stored correctly in database
        $dbMessage = $this->messageModel->findById($message['id']);
        $this->assertNotFalse($dbMessage);
        $this->assertTrue(
            (bool)$dbMessage['is_internal'],
            'Internal note should be stored with is_internal = true in database'
        );
    }

    /**
     * Property: Public messages are not marked as internal
     * 
     * @test
     */
    public function publicMessagesAreNotMarkedAsInternal(): void
    {
        // Create test customer and admin
        $customer = $this->createTestCustomer();
        $this->testCustomers[] = $customer['id'];
        
        $admin = $this->createTestAdmin();
        $this->testCustomers[] = $admin['id'];
        
        // Create test ticket
        $ticket = $this->createTestTicket($customer['id']);
        $this->testTickets[] = $ticket['id'];
        
        // Create public message
        $result = $this->ticketService->createMessage([
            'ticket_id' => $ticket['id'],
            'author_id' => $admin['id'],
            'author_type' => 'ADMIN',
            'content' => 'This is a public message',
            'is_internal' => false,
            'attachments' => []
        ]);
        
        $this->assertTrue($result['success']);
        $message = $result['message'];
        $this->testMessages[] = $message['id'];
        
        // Assert: Message has is_internal flag set to false
        $this->assertFalse(
            (bool)$message['is_internal'],
            'Public message should have is_internal flag set to false'
        );
        
        // Assert: Message is visible to customers
        $customerMessages = $this->ticketService->getCustomerVisibleMessages($ticket['id']);
        $this->assertTrue($customerMessages['success']);
        
        $customerMessageIds = array_column($customerMessages['messages'], 'id');
        $this->assertContains(
            $message['id'],
            $customerMessageIds,
            'Public message should be visible to customers'
        );
    }

    /**
     * Property: Customers cannot see internal notes even if they know the message ID
     * 
     * @test
     */
    public function customersCannotAccessInternalNotesByMessageId(): void
    {
        // Create test customer and admin
        $customer = $this->createTestCustomer();
        $this->testCustomers[] = $customer['id'];
        
        $admin = $this->createTestAdmin();
        $this->testCustomers[] = $admin['id'];
        
        // Create test ticket
        $ticket = $this->createTestTicket($customer['id']);
        $this->testTickets[] = $ticket['id'];
        
        // Create internal note
        $result = $this->ticketService->createMessage([
            'ticket_id' => $ticket['id'],
            'author_id' => $admin['id'],
            'author_type' => 'ADMIN',
            'content' => 'Secret internal note',
            'is_internal' => true,
            'attachments' => []
        ]);
        
        $this->assertTrue($result['success']);
        $internalNote = $result['message'];
        $this->testMessages[] = $internalNote['id'];
        
        // Get customer-visible messages
        $customerMessages = $this->ticketService->getCustomerVisibleMessages($ticket['id']);
        $this->assertTrue($customerMessages['success']);
        
        // Assert: Internal note is not in customer-visible list
        $customerMessageIds = array_column($customerMessages['messages'], 'id');
        $this->assertNotContains(
            $internalNote['id'],
            $customerMessageIds,
            'Internal note should not be accessible to customers'
        );
        
        // Assert: Internal note is marked as internal in database
        $dbMessage = $this->messageModel->findById($internalNote['id']);
        $this->assertTrue((bool)$dbMessage['is_internal']);
    }

    /**
     * Property: Mix of internal and public messages maintains correct visibility
     * 
     * @test
     */
    public function mixOfInternalAndPublicMessagesMaintainsCorrectVisibility(): void
    {
        // Create test customer and admin
        $customer = $this->createTestCustomer();
        $this->testCustomers[] = $customer['id'];
        
        $admin = $this->createTestAdmin();
        $this->testCustomers[] = $admin['id'];
        
        // Create test ticket
        $ticket = $this->createTestTicket($customer['id']);
        $this->testTickets[] = $ticket['id'];
        
        // Create alternating internal and public messages
        $internalIds = [];
        $publicIds = [];
        
        for ($i = 0; $i < 10; $i++) {
            $isInternal = $i % 2 === 0;
            
            $result = $this->ticketService->createMessage([
                'ticket_id' => $ticket['id'],
                'author_id' => $admin['id'],
                'author_type' => 'ADMIN',
                'content' => "Message $i " . ($isInternal ? '(internal)' : '(public)'),
                'is_internal' => $isInternal,
                'attachments' => []
            ]);
            
            $this->assertTrue($result['success']);
            $this->testMessages[] = $result['message']['id'];
            
            if ($isInternal) {
                $internalIds[] = $result['message']['id'];
            } else {
                $publicIds[] = $result['message']['id'];
            }
            
            usleep(5000);
        }
        
        // Get customer-visible messages
        $customerMessages = $this->ticketService->getCustomerVisibleMessages($ticket['id']);
        $this->assertTrue($customerMessages['success']);
        
        $customerMessageIds = array_column($customerMessages['messages'], 'id');
        
        // Assert: Only public messages are visible to customers
        $this->assertCount(5, $customerMessages['messages']);
        
        foreach ($publicIds as $publicId) {
            $this->assertContains($publicId, $customerMessageIds);
        }
        
        foreach ($internalIds as $internalId) {
            $this->assertNotContains($internalId, $customerMessageIds);
        }
        
        // Get admin messages
        $adminMessages = $this->ticketService->getTicketMessages($ticket['id'], true);
        $this->assertTrue($adminMessages['success']);
        
        // Assert: All messages are visible to admins
        $this->assertCount(10, $adminMessages['messages']);
    }

    /**
     * Property: Default is_internal value is false
     * 
     * @test
     */
    public function defaultIsInternalValueIsFalse(): void
    {
        // Create test customer
        $customer = $this->createTestCustomer();
        $this->testCustomers[] = $customer['id'];
        
        // Create test ticket
        $ticket = $this->createTestTicket($customer['id']);
        $this->testTickets[] = $ticket['id'];
        
        // Create message without specifying is_internal
        $result = $this->ticketService->createMessage([
            'ticket_id' => $ticket['id'],
            'author_id' => $customer['id'],
            'author_type' => 'CUSTOMER',
            'content' => 'Message without is_internal specified',
            'attachments' => []
        ]);
        
        $this->assertTrue($result['success']);
        $message = $result['message'];
        $this->testMessages[] = $message['id'];
        
        // Assert: Default is_internal is false
        $this->assertFalse(
            (bool)$message['is_internal'],
            'Default is_internal value should be false'
        );
        
        // Assert: Message is visible to customers
        $customerMessages = $this->ticketService->getCustomerVisibleMessages($ticket['id']);
        $this->assertTrue($customerMessages['success']);
        
        $customerMessageIds = array_column($customerMessages['messages'], 'id');
        $this->assertContains($message['id'], $customerMessageIds);
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
        
        return $this->userModel->create($userData);
    }

    /**
     * Helper: Create test admin
     */
    private function createTestAdmin(): array
    {
        $email = 'test_admin_' . bin2hex(random_bytes(8)) . '@example.com';
        
        $userData = [
            'email' => $email,
            'password_hash' => password_hash('password123', PASSWORD_BCRYPT),
            'name' => 'Test Admin',
            'role' => 'ADMIN'
        ];
        
        return $this->userModel->create($userData);
    }

    /**
     * Helper: Create test ticket
     */
    private function createTestTicket(string $customerId): array
    {
        $ticketData = [
            'customer_id' => $customerId,
            'subject' => 'Test Ticket for Internal Notes',
            'category' => 'TECHNICAL',
            'priority' => 'MEDIUM'
        ];
        
        $result = $this->ticketService->createTicket($ticketData);
        return $result['ticket'];
    }
}
