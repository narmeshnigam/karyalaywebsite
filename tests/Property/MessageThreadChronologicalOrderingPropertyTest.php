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
 * Property-based tests for message thread chronological ordering
 * 
 * Feature: karyalay-portal-system, Property 24: Message Thread Chronological Ordering
 * Validates: Requirements 7.3
 */
class MessageThreadChronologicalOrderingPropertyTest extends TestCase
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
     * Property 24: Message Thread Chronological Ordering
     * 
     * For any ticket with multiple messages, when the ticket detail page is 
     * accessed, all messages should be displayed in chronological order by 
     * creation timestamp.
     * 
     * Validates: Requirements 7.3
     * 
     * @test
     */
    public function messagesAreReturnedInChronologicalOrder(): void
    {
        $this->forAll(
            Generator\choose(2, 10)  // number of messages
        )
        ->then(function ($messageCount) {
            // Create test customer
            $customer = $this->createTestCustomer();
            $this->testCustomers[] = $customer['id'];
            
            // Create test ticket
            $ticket = $this->createTestTicket($customer['id']);
            $this->testTickets[] = $ticket['id'];
            
            // Create multiple messages with slight delays to ensure different timestamps
            $createdMessages = [];
            for ($i = 0; $i < $messageCount; $i++) {
                $messageData = [
                    'ticket_id' => $ticket['id'],
                    'author_id' => $customer['id'],
                    'author_type' => 'CUSTOMER',
                    'content' => "Message number $i",
                    'is_internal' => false,
                    'attachments' => []
                ];
                
                $result = $this->ticketService->createMessage($messageData);
                $this->assertTrue($result['success'], 'Message creation should succeed');
                
                $createdMessages[] = $result['message'];
                $this->testMessages[] = $result['message']['id'];
                
                // Small delay to ensure different timestamps
                usleep(10000); // 10ms
            }
            
            // Act: Get ticket messages
            $result = $this->ticketService->getTicketMessages($ticket['id']);
            
            // Assert: Messages retrieved successfully
            $this->assertTrue($result['success'], 'Message retrieval should succeed');
            $this->assertArrayHasKey('messages', $result);
            
            $messages = $result['messages'];
            
            // Assert: All messages are returned
            $this->assertCount(
                $messageCount,
                $messages,
                "Should return all $messageCount messages"
            );
            
            // Assert: Messages are in chronological order by timestamp
            for ($i = 0; $i < count($messages) - 1; $i++) {
                $currentTimestamp = strtotime($messages[$i]['created_at']);
                $nextTimestamp = strtotime($messages[$i + 1]['created_at']);
                
                $this->assertLessThanOrEqual(
                    $nextTimestamp,
                    $currentTimestamp,
                    "Message at index $i should have timestamp <= message at index " . ($i + 1) . 
                    " (current: {$messages[$i]['created_at']}, next: {$messages[$i + 1]['created_at']})"
                );
            }
            
            // Assert: All created messages are present in the result
            $createdIds = array_column($createdMessages, 'id');
            $returnedIds = array_column($messages, 'id');
            
            sort($createdIds);
            sort($returnedIds);
            
            $this->assertEquals(
                $createdIds,
                $returnedIds,
                "All created messages should be present in the returned messages"
            );
            
            // Assert: Messages are ordered by their actual creation timestamps
            // Build a map of message ID to creation timestamp
            $timestampMap = [];
            foreach ($createdMessages as $msg) {
                $timestampMap[$msg['id']] = strtotime($msg['created_at']);
            }
            
            // Verify returned messages respect chronological order
            for ($i = 0; $i < count($messages) - 1; $i++) {
                $currentId = $messages[$i]['id'];
                $nextId = $messages[$i + 1]['id'];
                
                $this->assertLessThanOrEqual(
                    $timestampMap[$nextId],
                    $timestampMap[$currentId],
                    "Messages should be ordered by their creation timestamp"
                );
            }
        });
    }

    /**
     * Property: Messages with same timestamp are ordered consistently
     * 
     * @test
     */
    public function messagesWithSameTimestampAreOrderedConsistently(): void
    {
        // Create test customer
        $customer = $this->createTestCustomer();
        $this->testCustomers[] = $customer['id'];
        
        // Create test ticket
        $ticket = $this->createTestTicket($customer['id']);
        $this->testTickets[] = $ticket['id'];
        
        // Create messages rapidly (may have same timestamp)
        $messageIds = [];
        for ($i = 0; $i < 5; $i++) {
            $result = $this->ticketService->createMessage([
                'ticket_id' => $ticket['id'],
                'author_id' => $customer['id'],
                'author_type' => 'CUSTOMER',
                'content' => "Rapid message $i",
                'is_internal' => false,
                'attachments' => []
            ]);
            
            $this->assertTrue($result['success']);
            $messageIds[] = $result['message']['id'];
            $this->testMessages[] = $result['message']['id'];
        }
        
        // Get messages multiple times
        $result1 = $this->ticketService->getTicketMessages($ticket['id']);
        $result2 = $this->ticketService->getTicketMessages($ticket['id']);
        
        $this->assertTrue($result1['success']);
        $this->assertTrue($result2['success']);
        
        // Assert: Order is consistent across multiple retrievals
        $this->assertEquals(
            array_column($result1['messages'], 'id'),
            array_column($result2['messages'], 'id'),
            'Message order should be consistent across multiple retrievals'
        );
    }

    /**
     * Property: Empty ticket returns empty message array
     * 
     * @test
     */
    public function emptyTicketReturnsEmptyMessageArray(): void
    {
        // Create test customer
        $customer = $this->createTestCustomer();
        $this->testCustomers[] = $customer['id'];
        
        // Create test ticket without messages
        $ticket = $this->createTestTicket($customer['id']);
        $this->testTickets[] = $ticket['id'];
        
        // Act: Get ticket messages
        $result = $this->ticketService->getTicketMessages($ticket['id']);
        
        // Assert: Returns empty array
        $this->assertTrue($result['success']);
        $this->assertIsArray($result['messages']);
        $this->assertEmpty($result['messages'], 'Ticket without messages should return empty array');
    }

    /**
     * Property: Messages from different authors maintain chronological order
     * 
     * @test
     */
    public function messagesFromDifferentAuthorsMaintainChronologicalOrder(): void
    {
        // Create test customer and admin
        $customer = $this->createTestCustomer();
        $this->testCustomers[] = $customer['id'];
        
        $admin = $this->createTestAdmin();
        $this->testCustomers[] = $admin['id'];
        
        // Create test ticket
        $ticket = $this->createTestTicket($customer['id']);
        $this->testTickets[] = $ticket['id'];
        
        // Create alternating messages from customer and admin
        $createdMessages = [];
        for ($i = 0; $i < 6; $i++) {
            $isCustomer = $i % 2 === 0;
            $authorId = $isCustomer ? $customer['id'] : $admin['id'];
            $authorType = $isCustomer ? 'CUSTOMER' : 'ADMIN';
            
            $result = $this->ticketService->createMessage([
                'ticket_id' => $ticket['id'],
                'author_id' => $authorId,
                'author_type' => $authorType,
                'content' => "Message $i from " . ($isCustomer ? 'customer' : 'admin'),
                'is_internal' => false,
                'attachments' => []
            ]);
            
            $this->assertTrue($result['success']);
            $createdMessages[] = $result['message'];
            $this->testMessages[] = $result['message']['id'];
            
            usleep(10000); // 10ms delay
        }
        
        // Act: Get ticket messages
        $result = $this->ticketService->getTicketMessages($ticket['id']);
        
        // Assert: Messages are in chronological order regardless of author
        $this->assertTrue($result['success']);
        $messages = $result['messages'];
        
        // Assert: All messages returned
        $this->assertCount(6, $messages);
        
        // Assert: Messages are ordered chronologically by timestamp
        for ($i = 0; $i < count($messages) - 1; $i++) {
            $currentTimestamp = strtotime($messages[$i]['created_at']);
            $nextTimestamp = strtotime($messages[$i + 1]['created_at']);
            
            $this->assertLessThanOrEqual(
                $nextTimestamp,
                $currentTimestamp,
                'Messages should be in chronological order regardless of author type'
            );
        }
        
        // Assert: Messages from both customer and admin are present
        $authorTypes = array_unique(array_column($messages, 'author_type'));
        $this->assertContains('CUSTOMER', $authorTypes);
        $this->assertContains('ADMIN', $authorTypes);
    }

    /**
     * Property: Internal notes are included when requested
     * 
     * @test
     */
    public function internalNotesAreIncludedWhenRequested(): void
    {
        // Create test customer and admin
        $customer = $this->createTestCustomer();
        $this->testCustomers[] = $customer['id'];
        
        $admin = $this->createTestAdmin();
        $this->testCustomers[] = $admin['id'];
        
        // Create test ticket
        $ticket = $this->createTestTicket($customer['id']);
        $this->testTickets[] = $ticket['id'];
        
        // Create mix of regular and internal messages
        $regularCount = 0;
        $internalCount = 0;
        
        for ($i = 0; $i < 5; $i++) {
            $isInternal = $i % 2 === 1;
            
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
                $internalCount++;
            } else {
                $regularCount++;
            }
            
            usleep(10000);
        }
        
        // Act: Get all messages (including internal)
        $resultAll = $this->ticketService->getTicketMessages($ticket['id'], true);
        
        // Assert: All messages returned
        $this->assertTrue($resultAll['success']);
        $this->assertCount(5, $resultAll['messages']);
        
        // Act: Get only customer-visible messages
        $resultPublic = $this->ticketService->getCustomerVisibleMessages($ticket['id']);
        
        // Assert: Only non-internal messages returned
        $this->assertTrue($resultPublic['success']);
        $this->assertCount($regularCount, $resultPublic['messages']);
        
        // Assert: All returned messages are non-internal
        foreach ($resultPublic['messages'] as $message) {
            $this->assertFalse(
                (bool)$message['is_internal'],
                'Customer-visible messages should not be internal'
            );
        }
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
            'subject' => 'Test Ticket for Messages',
            'category' => 'TECHNICAL',
            'priority' => 'MEDIUM'
        ];
        
        $result = $this->ticketService->createTicket($ticketData);
        return $result['ticket'];
    }
}
