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
 * Property-based tests for closed ticket reply prevention
 * 
 * Feature: karyalay-portal-system, Property 26: Closed Ticket Reply Prevention
 * Validates: Requirements 7.5
 */
class ClosedTicketReplyPreventionPropertyTest extends TestCase
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
     * Property 26: Closed Ticket Reply Prevention
     * 
     * For any ticket with status CLOSED, when a customer attempts to submit a 
     * reply, the reply should be rejected.
     * 
     * Validates: Requirements 7.5
     * 
     * @test
     */
    public function closedTicketsRejectReplies(): void
    {
        $this->forAll(
            Generator\choose(10, 200),  // content length
            Generator\elements(['CUSTOMER', 'ADMIN'])  // author type
        )
        ->then(function ($contentLen, $authorType) {
            // Create test customer and admin
            $customer = $this->createTestCustomer();
            $this->testCustomers[] = $customer['id'];
            
            $admin = $this->createTestAdmin();
            $this->testCustomers[] = $admin['id'];
            
            // Create test ticket
            $ticket = $this->createTestTicket($customer['id']);
            $this->testTickets[] = $ticket['id'];
            
            // Close the ticket
            $closeResult = $this->ticketService->updateTicketStatus($ticket['id'], 'CLOSED');
            $this->assertTrue($closeResult['success'], 'Ticket should be closed successfully');
            
            // Verify ticket is closed
            $this->assertTrue(
                $this->ticketService->isTicketClosed($ticket['id']),
                'Ticket should be marked as closed'
            );
            
            // Get initial message count
            $initialCount = $this->ticketService->countTicketMessages($ticket['id']);
            
            // Generate reply content
            $content = 'Reply attempt ' . str_repeat('X', max(0, $contentLen - 14));
            $authorId = $authorType === 'CUSTOMER' ? $customer['id'] : $admin['id'];
            
            // Act: Attempt to add reply to closed ticket
            $result = $this->ticketService->addReply(
                $ticket['id'],
                $authorId,
                $authorType,
                $content,
                false,
                []
            );
            
            // Assert: Reply should be rejected
            $this->assertFalse(
                $result['success'],
                'Reply to closed ticket should be rejected'
            );
            
            // Assert: Error message is provided
            $this->assertArrayHasKey('error', $result);
            $this->assertNotEmpty($result['error']);
            $this->assertStringContainsString(
                'closed',
                strtolower($result['error']),
                'Error message should mention that ticket is closed'
            );
            
            // Assert: No message was created
            $newCount = $this->ticketService->countTicketMessages($ticket['id']);
            $this->assertEquals(
                $initialCount,
                $newCount,
                'Message count should not increase after rejected reply'
            );
            
            // Assert: No message key in result
            $this->assertArrayNotHasKey(
                'message',
                $result,
                'No message should be returned when reply is rejected'
            );
        });
    }

    /**
     * Property: Open tickets accept replies
     * 
     * @test
     */
    public function openTicketsAcceptReplies(): void
    {
        // Create test customer
        $customer = $this->createTestCustomer();
        $this->testCustomers[] = $customer['id'];
        
        // Create test ticket (status OPEN by default)
        $ticket = $this->createTestTicket($customer['id']);
        $this->testTickets[] = $ticket['id'];
        
        // Verify ticket is open
        $ticketData = $this->ticketModel->findById($ticket['id']);
        $this->assertEquals('OPEN', $ticketData['status']);
        
        // Attempt to add reply
        $result = $this->ticketService->addReply(
            $ticket['id'],
            $customer['id'],
            'CUSTOMER',
            'Reply to open ticket',
            false,
            []
        );
        
        // Assert: Reply should succeed
        $this->assertTrue($result['success'], 'Reply to open ticket should succeed');
        $this->assertArrayHasKey('message', $result);
        $this->testMessages[] = $result['message']['id'];
    }

    /**
     * Property: Non-closed statuses accept replies
     * 
     * @test
     */
    public function nonClosedStatusesAcceptReplies(): void
    {
        $nonClosedStatuses = ['OPEN', 'IN_PROGRESS', 'WAITING_ON_CUSTOMER', 'RESOLVED'];
        
        foreach ($nonClosedStatuses as $status) {
            // Create test customer
            $customer = $this->createTestCustomer();
            $this->testCustomers[] = $customer['id'];
            
            // Create test ticket
            $ticket = $this->createTestTicket($customer['id']);
            $this->testTickets[] = $ticket['id'];
            
            // Set ticket to non-closed status
            $updateResult = $this->ticketService->updateTicketStatus($ticket['id'], $status);
            $this->assertTrue($updateResult['success']);
            
            // Attempt to add reply
            $result = $this->ticketService->addReply(
                $ticket['id'],
                $customer['id'],
                'CUSTOMER',
                "Reply to ticket with status $status",
                false,
                []
            );
            
            // Assert: Reply should succeed
            $this->assertTrue(
                $result['success'],
                "Reply to ticket with status $status should succeed"
            );
            $this->assertArrayHasKey('message', $result);
            $this->testMessages[] = $result['message']['id'];
        }
    }

    /**
     * Property: Ticket closed after reply prevents subsequent replies
     * 
     * @test
     */
    public function ticketClosedAfterReplyPreventsSubsequentReplies(): void
    {
        // Create test customer
        $customer = $this->createTestCustomer();
        $this->testCustomers[] = $customer['id'];
        
        // Create test ticket
        $ticket = $this->createTestTicket($customer['id']);
        $this->testTickets[] = $ticket['id'];
        
        // Add first reply (should succeed)
        $result1 = $this->ticketService->addReply(
            $ticket['id'],
            $customer['id'],
            'CUSTOMER',
            'First reply',
            false,
            []
        );
        
        $this->assertTrue($result1['success']);
        $this->testMessages[] = $result1['message']['id'];
        
        // Close the ticket
        $closeResult = $this->ticketService->updateTicketStatus($ticket['id'], 'CLOSED');
        $this->assertTrue($closeResult['success']);
        
        // Attempt second reply (should fail)
        $result2 = $this->ticketService->addReply(
            $ticket['id'],
            $customer['id'],
            'CUSTOMER',
            'Second reply after close',
            false,
            []
        );
        
        $this->assertFalse(
            $result2['success'],
            'Reply after ticket is closed should be rejected'
        );
        $this->assertArrayHasKey('error', $result2);
    }

    /**
     * Property: Both customers and admins cannot reply to closed tickets
     * 
     * @test
     */
    public function bothCustomersAndAdminsCannotReplyToClosedTickets(): void
    {
        // Create test customer and admin
        $customer = $this->createTestCustomer();
        $this->testCustomers[] = $customer['id'];
        
        $admin = $this->createTestAdmin();
        $this->testCustomers[] = $admin['id'];
        
        // Create test ticket
        $ticket = $this->createTestTicket($customer['id']);
        $this->testTickets[] = $ticket['id'];
        
        // Close the ticket
        $closeResult = $this->ticketService->updateTicketStatus($ticket['id'], 'CLOSED');
        $this->assertTrue($closeResult['success']);
        
        // Customer attempts to reply
        $customerResult = $this->ticketService->addReply(
            $ticket['id'],
            $customer['id'],
            'CUSTOMER',
            'Customer reply to closed ticket',
            false,
            []
        );
        
        $this->assertFalse(
            $customerResult['success'],
            'Customer should not be able to reply to closed ticket'
        );
        
        // Admin attempts to reply
        $adminResult = $this->ticketService->addReply(
            $ticket['id'],
            $admin['id'],
            'ADMIN',
            'Admin reply to closed ticket',
            false,
            []
        );
        
        $this->assertFalse(
            $adminResult['success'],
            'Admin should not be able to reply to closed ticket'
        );
    }

    /**
     * Property: Internal notes also cannot be added to closed tickets
     * 
     * @test
     */
    public function internalNotesCannotBeAddedToClosedTickets(): void
    {
        // Create test customer and admin
        $customer = $this->createTestCustomer();
        $this->testCustomers[] = $customer['id'];
        
        $admin = $this->createTestAdmin();
        $this->testCustomers[] = $admin['id'];
        
        // Create test ticket
        $ticket = $this->createTestTicket($customer['id']);
        $this->testTickets[] = $ticket['id'];
        
        // Close the ticket
        $closeResult = $this->ticketService->updateTicketStatus($ticket['id'], 'CLOSED');
        $this->assertTrue($closeResult['success']);
        
        // Attempt to add internal note
        $result = $this->ticketService->addReply(
            $ticket['id'],
            $admin['id'],
            'ADMIN',
            'Internal note on closed ticket',
            true,  // is_internal
            []
        );
        
        $this->assertFalse(
            $result['success'],
            'Internal notes should not be allowed on closed tickets'
        );
        $this->assertArrayHasKey('error', $result);
    }

    /**
     * Property: Reopening a closed ticket allows replies again
     * 
     * @test
     */
    public function reopeningClosedTicketAllowsRepliesAgain(): void
    {
        // Create test customer
        $customer = $this->createTestCustomer();
        $this->testCustomers[] = $customer['id'];
        
        // Create test ticket
        $ticket = $this->createTestTicket($customer['id']);
        $this->testTickets[] = $ticket['id'];
        
        // Close the ticket
        $closeResult = $this->ticketService->updateTicketStatus($ticket['id'], 'CLOSED');
        $this->assertTrue($closeResult['success']);
        
        // Verify replies are rejected
        $closedReply = $this->ticketService->addReply(
            $ticket['id'],
            $customer['id'],
            'CUSTOMER',
            'Reply while closed',
            false,
            []
        );
        $this->assertFalse($closedReply['success']);
        
        // Reopen the ticket
        $reopenResult = $this->ticketService->updateTicketStatus($ticket['id'], 'OPEN');
        $this->assertTrue($reopenResult['success']);
        
        // Verify replies are now accepted
        $reopenedReply = $this->ticketService->addReply(
            $ticket['id'],
            $customer['id'],
            'CUSTOMER',
            'Reply after reopening',
            false,
            []
        );
        
        $this->assertTrue(
            $reopenedReply['success'],
            'Replies should be accepted after ticket is reopened'
        );
        $this->assertArrayHasKey('message', $reopenedReply);
        $this->testMessages[] = $reopenedReply['message']['id'];
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
            'subject' => 'Test Ticket for Closed Reply Prevention',
            'category' => 'TECHNICAL',
            'priority' => 'MEDIUM'
        ];
        
        $result = $this->ticketService->createTicket($ticketData);
        return $result['ticket'];
    }
}
