<?php

namespace Karyalay\Services;

use Karyalay\Models\Ticket;
use Karyalay\Models\TicketMessage;
use Karyalay\Models\User;
use Karyalay\Database\Connection;
use PDO;
use PDOException;

/**
 * Ticket Service
 * 
 * Handles ticket lifecycle management including creation, status updates,
 * and assignment operations
 */
class TicketService
{
    private Ticket $ticketModel;
    private TicketMessage $messageModel;
    private User $userModel;
    private PDO $db;

    public function __construct()
    {
        $this->ticketModel = new Ticket();
        $this->messageModel = new TicketMessage();
        $this->userModel = new User();
        $this->db = Connection::getInstance();
    }

    /**
     * Create a new ticket with status OPEN
     * 
     * @param array $data Ticket data (customer_id, subscription_id, subject, category, priority, description)
     * @return array Returns array with 'success' boolean and 'ticket' or 'error'
     */
    public function createTicket(array $data): array
    {
        try {
            // Validate required fields
            if (empty($data['customer_id']) || empty($data['subject'])) {
                return [
                    'success' => false,
                    'error' => 'customer_id and subject are required'
                ];
            }

            // Set default status to OPEN
            $data['status'] = 'OPEN';

            // Create ticket
            $ticket = $this->ticketModel->create($data);

            if (!$ticket) {
                return [
                    'success' => false,
                    'error' => 'Failed to create ticket'
                ];
            }

            // Send email notifications
            $this->sendTicketNotifications($ticket, $data['description'] ?? '');

            return [
                'success' => true,
                'ticket' => $ticket
            ];
        } catch (\Exception $e) {
            error_log('TicketService::createTicket failed: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => 'An error occurred while creating ticket'
            ];
        }
    }

    /**
     * Send ticket notification emails to customer and admin
     * 
     * @param array $ticket Ticket data
     * @param string $description Ticket description
     * @return void
     */
    private function sendTicketNotifications(array $ticket, string $description): void
    {
        try {
            // Get customer details
            $customer = $this->userModel->findById($ticket['customer_id']);
            
            if (!$customer) {
                error_log('TicketService: Cannot send notification - customer not found');
                return;
            }

            // Prepare ticket data for email
            $ticketData = [
                'ticket_id' => $ticket['id'],
                'customer_name' => $customer['name'] ?? 'Valued Customer',
                'customer_email' => $customer['email'],
                'customer_phone' => $customer['phone'] ?? 'Not provided',
                'subject' => $ticket['subject'],
                'description' => $description,
                'priority' => strtoupper($ticket['priority'] ?? 'MEDIUM'),
                'category' => $ticket['category'] ?? 'General',
            ];

            // Send email notifications
            $emailService = EmailService::getInstance();
            $emailSent = $emailService->sendTicketNotification($ticketData);

            if ($emailSent) {
                error_log("TicketService: Notification emails sent for ticket #{$ticket['id']}");
            } else {
                error_log("TicketService: Failed to send notification emails for ticket #{$ticket['id']}");
            }
        } catch (\Exception $e) {
            error_log('TicketService: Error sending ticket notifications: ' . $e->getMessage());
        }
    }

    /**
     * Update ticket status
     * 
     * Records the change timestamp automatically via database updated_at field.
     * 
     * @param string $ticketId Ticket ID
     * @param string $status New status (OPEN, IN_PROGRESS, WAITING_ON_CUSTOMER, RESOLVED, CLOSED)
     * @return array Returns array with 'success' boolean and optional 'error'
     */
    public function updateTicketStatus(string $ticketId, string $status): array
    {
        try {
            // Validate status
            $validStatuses = ['OPEN', 'IN_PROGRESS', 'WAITING_ON_CUSTOMER', 'RESOLVED', 'CLOSED'];
            if (!in_array($status, $validStatuses)) {
                return [
                    'success' => false,
                    'error' => 'Invalid status. Must be one of: ' . implode(', ', $validStatuses)
                ];
            }

            // Check if ticket exists
            $ticket = $this->ticketModel->findById($ticketId);
            if (!$ticket) {
                return [
                    'success' => false,
                    'error' => 'Ticket not found'
                ];
            }

            // Update status
            $success = $this->ticketModel->updateStatus($ticketId, $status);

            if (!$success) {
                return [
                    'success' => false,
                    'error' => 'Failed to update ticket status'
                ];
            }

            // Get updated ticket
            $updatedTicket = $this->ticketModel->findById($ticketId);

            return [
                'success' => true,
                'ticket' => $updatedTicket
            ];
        } catch (\Exception $e) {
            error_log('TicketService::updateTicketStatus failed: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => 'An error occurred while updating ticket status'
            ];
        }
    }

    /**
     * Assign ticket to an admin user
     * 
     * @param string $ticketId Ticket ID
     * @param string $adminUserId Admin user ID
     * @return array Returns array with 'success' boolean and optional 'error'
     */
    public function assignTicket(string $ticketId, string $adminUserId): array
    {
        try {
            // Check if ticket exists
            $ticket = $this->ticketModel->findById($ticketId);
            if (!$ticket) {
                return [
                    'success' => false,
                    'error' => 'Ticket not found'
                ];
            }

            // Assign ticket
            $success = $this->ticketModel->assignTo($ticketId, $adminUserId);

            if (!$success) {
                return [
                    'success' => false,
                    'error' => 'Failed to assign ticket'
                ];
            }

            // Get updated ticket
            $updatedTicket = $this->ticketModel->findById($ticketId);

            return [
                'success' => true,
                'ticket' => $updatedTicket
            ];
        } catch (\Exception $e) {
            error_log('TicketService::assignTicket failed: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => 'An error occurred while assigning ticket'
            ];
        }
    }

    /**
     * Get ticket by ID
     * 
     * @param string $ticketId Ticket ID
     * @return array Returns array with 'success' boolean and 'ticket' or 'error'
     */
    public function getTicket(string $ticketId): array
    {
        try {
            $ticket = $this->ticketModel->findById($ticketId);

            if (!$ticket) {
                return [
                    'success' => false,
                    'error' => 'Ticket not found'
                ];
            }

            return [
                'success' => true,
                'ticket' => $ticket
            ];
        } catch (\Exception $e) {
            error_log('TicketService::getTicket failed: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => 'An error occurred while retrieving ticket'
            ];
        }
    }

    /**
     * Get tickets for a customer
     * 
     * @param string $customerId Customer ID
     * @param int $limit Optional limit
     * @param int $offset Optional offset
     * @return array Returns array with 'success' boolean and 'tickets' or 'error'
     */
    public function getCustomerTickets(string $customerId, int $limit = 100, int $offset = 0): array
    {
        try {
            $tickets = $this->ticketModel->findByCustomerId($customerId, $limit, $offset);

            return [
                'success' => true,
                'tickets' => $tickets
            ];
        } catch (\Exception $e) {
            error_log('TicketService::getCustomerTickets failed: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => 'An error occurred while retrieving customer tickets'
            ];
        }
    }

    /**
     * Get all tickets with optional filters
     * 
     * @param array $filters Optional filters (customer_id, status, priority, assigned_to, category)
     * @param int $limit Optional limit
     * @param int $offset Optional offset
     * @return array Returns array with 'success' boolean and 'tickets' or 'error'
     */
    public function getAllTickets(array $filters = [], int $limit = 100, int $offset = 0): array
    {
        try {
            $tickets = $this->ticketModel->findAll($filters, $limit, $offset);

            return [
                'success' => true,
                'tickets' => $tickets
            ];
        } catch (\Exception $e) {
            error_log('TicketService::getAllTickets failed: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => 'An error occurred while retrieving tickets'
            ];
        }
    }

    /**
     * Update ticket data
     * 
     * @param string $ticketId Ticket ID
     * @param array $data Data to update
     * @return array Returns array with 'success' boolean and optional 'error'
     */
    public function updateTicket(string $ticketId, array $data): array
    {
        try {
            // Check if ticket exists
            $ticket = $this->ticketModel->findById($ticketId);
            if (!$ticket) {
                return [
                    'success' => false,
                    'error' => 'Ticket not found'
                ];
            }

            // Update ticket
            $success = $this->ticketModel->update($ticketId, $data);

            if (!$success) {
                return [
                    'success' => false,
                    'error' => 'Failed to update ticket'
                ];
            }

            // Get updated ticket
            $updatedTicket = $this->ticketModel->findById($ticketId);

            return [
                'success' => true,
                'ticket' => $updatedTicket
            ];
        } catch (\Exception $e) {
            error_log('TicketService::updateTicket failed: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => 'An error occurred while updating ticket'
            ];
        }
    }

    /**
     * Delete ticket
     * 
     * @param string $ticketId Ticket ID
     * @return array Returns array with 'success' boolean and optional 'error'
     */
    public function deleteTicket(string $ticketId): array
    {
        try {
            // Check if ticket exists
            $ticket = $this->ticketModel->findById($ticketId);
            if (!$ticket) {
                return [
                    'success' => false,
                    'error' => 'Ticket not found'
                ];
            }

            // Delete ticket
            $success = $this->ticketModel->delete($ticketId);

            if (!$success) {
                return [
                    'success' => false,
                    'error' => 'Failed to delete ticket'
                ];
            }

            return [
                'success' => true
            ];
        } catch (\Exception $e) {
            error_log('TicketService::deleteTicket failed: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => 'An error occurred while deleting ticket'
            ];
        }
    }

    /**
     * Check if ticket is closed
     * 
     * @param string $ticketId Ticket ID
     * @return bool Returns true if ticket is closed
     */
    public function isTicketClosed(string $ticketId): bool
    {
        return $this->ticketModel->isClosed($ticketId);
    }

    /**
     * Create a ticket message
     * 
     * Supports internal notes (visible only to admins) and file attachments.
     * 
     * @param array $data Message data (ticket_id, author_id, author_type, content, is_internal, attachments)
     * @return array Returns array with 'success' boolean and 'message' or 'error'
     */
    public function createMessage(array $data): array
    {
        try {
            // Validate required fields
            if (empty($data['ticket_id']) || empty($data['author_id']) || empty($data['author_type']) || empty($data['content'])) {
                return [
                    'success' => false,
                    'error' => 'ticket_id, author_id, author_type, and content are required'
                ];
            }

            // Validate author_type
            $validAuthorTypes = ['CUSTOMER', 'ADMIN'];
            if (!in_array($data['author_type'], $validAuthorTypes)) {
                return [
                    'success' => false,
                    'error' => 'Invalid author_type. Must be CUSTOMER or ADMIN'
                ];
            }

            // Check if ticket exists
            $ticket = $this->ticketModel->findById($data['ticket_id']);
            if (!$ticket) {
                return [
                    'success' => false,
                    'error' => 'Ticket not found'
                ];
            }

            // Set default is_internal to false if not provided
            if (!isset($data['is_internal'])) {
                $data['is_internal'] = false;
            }

            // Set default attachments to empty array if not provided
            if (!isset($data['attachments'])) {
                $data['attachments'] = [];
            }

            // Create message
            $message = $this->messageModel->create($data);

            if (!$message) {
                return [
                    'success' => false,
                    'error' => 'Failed to create message'
                ];
            }

            return [
                'success' => true,
                'message' => $message
            ];
        } catch (\Exception $e) {
            error_log('TicketService::createMessage failed: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => 'An error occurred while creating message'
            ];
        }
    }

    /**
     * Get messages for a ticket
     * 
     * Returns messages in chronological order. Can optionally exclude internal notes
     * for customer-facing views.
     * 
     * @param string $ticketId Ticket ID
     * @param bool $includeInternal Whether to include internal notes (default: true)
     * @param int $limit Optional limit
     * @param int $offset Optional offset
     * @return array Returns array with 'success' boolean and 'messages' or 'error'
     */
    public function getTicketMessages(string $ticketId, bool $includeInternal = true, int $limit = 1000, int $offset = 0): array
    {
        try {
            // Check if ticket exists
            $ticket = $this->ticketModel->findById($ticketId);
            if (!$ticket) {
                return [
                    'success' => false,
                    'error' => 'Ticket not found'
                ];
            }

            // Get messages in chronological order
            $messages = $this->messageModel->findByTicketId($ticketId, $includeInternal, $limit, $offset);

            return [
                'success' => true,
                'messages' => $messages
            ];
        } catch (\Exception $e) {
            error_log('TicketService::getTicketMessages failed: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => 'An error occurred while retrieving messages'
            ];
        }
    }

    /**
     * Get customer-visible messages for a ticket
     * 
     * Excludes internal notes. Used for customer-facing ticket views.
     * 
     * @param string $ticketId Ticket ID
     * @return array Returns array with 'success' boolean and 'messages' or 'error'
     */
    public function getCustomerVisibleMessages(string $ticketId): array
    {
        return $this->getTicketMessages($ticketId, false);
    }

    /**
     * Add a reply to a ticket
     * 
     * Convenience method that creates a message and updates the ticket's updated_at timestamp.
     * Prevents replies to closed tickets.
     * 
     * @param string $ticketId Ticket ID
     * @param string $authorId Author ID
     * @param string $authorType Author type (CUSTOMER or ADMIN)
     * @param string $content Message content
     * @param bool $isInternal Whether this is an internal note (default: false)
     * @param array $attachments Optional file attachments
     * @return array Returns array with 'success' boolean and 'message' or 'error'
     */
    public function addReply(string $ticketId, string $authorId, string $authorType, string $content, bool $isInternal = false, array $attachments = []): array
    {
        try {
            // Check if ticket is closed
            if ($this->isTicketClosed($ticketId)) {
                return [
                    'success' => false,
                    'error' => 'Cannot reply to a closed ticket'
                ];
            }

            // Create message
            $result = $this->createMessage([
                'ticket_id' => $ticketId,
                'author_id' => $authorId,
                'author_type' => $authorType,
                'content' => $content,
                'is_internal' => $isInternal,
                'attachments' => $attachments
            ]);

            if (!$result['success']) {
                return $result;
            }

            // Touch the ticket to update its updated_at timestamp
            // We update a field to itself to trigger the updated_at timestamp update
            $ticket = $this->ticketModel->findById($ticketId);
            if ($ticket) {
                $this->ticketModel->update($ticketId, ['status' => $ticket['status']]);
            }

            return $result;
        } catch (\Exception $e) {
            error_log('TicketService::addReply failed: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => 'An error occurred while adding reply'
            ];
        }
    }

    /**
     * Get message by ID
     * 
     * @param string $messageId Message ID
     * @return array Returns array with 'success' boolean and 'message' or 'error'
     */
    public function getMessage(string $messageId): array
    {
        try {
            $message = $this->messageModel->findById($messageId);

            if (!$message) {
                return [
                    'success' => false,
                    'error' => 'Message not found'
                ];
            }

            return [
                'success' => true,
                'message' => $message
            ];
        } catch (\Exception $e) {
            error_log('TicketService::getMessage failed: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => 'An error occurred while retrieving message'
            ];
        }
    }

    /**
     * Count messages for a ticket
     * 
     * @param string $ticketId Ticket ID
     * @param bool $includeInternal Whether to include internal notes
     * @return int Returns count of messages
     */
    public function countTicketMessages(string $ticketId, bool $includeInternal = true): int
    {
        try {
            return $this->messageModel->countByTicketId($ticketId, $includeInternal);
        } catch (\Exception $e) {
            error_log('TicketService::countTicketMessages failed: ' . $e->getMessage());
            return 0;
        }
    }

    /**
     * Add admin reply and send notification to customer
     * 
     * @param string $ticketId Ticket ID
     * @param string $adminId Admin user ID
     * @param string $content Reply content
     * @param bool $isInternal Whether this is an internal note (default: false)
     * @return array Returns array with 'success' boolean and 'message' or 'error'
     */
    public function addAdminReply(string $ticketId, string $adminId, string $content, bool $isInternal = false): array
    {
        // Add the reply
        $result = $this->addReply($ticketId, $adminId, 'ADMIN', $content, $isInternal);

        if (!$result['success']) {
            return $result;
        }

        // Send notification email to customer (only for non-internal replies)
        if (!$isInternal) {
            $this->sendReplyNotification($ticketId);
        }

        return $result;
    }

    /**
     * Send reply notification email to customer
     * 
     * @param string $ticketId Ticket ID
     * @return void
     */
    private function sendReplyNotification(string $ticketId): void
    {
        try {
            // Get ticket details
            $ticket = $this->ticketModel->findById($ticketId);
            
            if (!$ticket) {
                error_log('TicketService: Cannot send reply notification - ticket not found');
                return;
            }

            // Get customer details
            $customer = $this->userModel->findById($ticket['customer_id']);
            
            if (!$customer) {
                error_log('TicketService: Cannot send reply notification - customer not found');
                return;
            }

            // Build ticket URL
            $baseUrl = $_ENV['APP_URL'] ?? 'http://localhost';
            $ticketUrl = rtrim($baseUrl, '/') . '/app/support/tickets/view.php?id=' . urlencode($ticket['id']);

            // Prepare reply data for email
            $replyData = [
                'ticket_id' => $ticket['id'],
                'customer_name' => $customer['name'] ?? 'Valued Customer',
                'customer_email' => $customer['email'],
                'ticket_subject' => $ticket['subject'],
                'ticket_url' => $ticketUrl,
            ];

            // Send email notification
            $emailService = EmailService::getInstance();
            $emailSent = $emailService->sendTicketReplyNotification($replyData);

            if ($emailSent) {
                error_log("TicketService: Reply notification email sent for ticket #{$ticket['id']}");
            } else {
                error_log("TicketService: Failed to send reply notification email for ticket #{$ticket['id']}");
            }
        } catch (\Exception $e) {
            error_log('TicketService: Error sending reply notification: ' . $e->getMessage());
        }
    }
}

