<?php
/**
 * Test script for ticket reply notification emails
 * 
 * This script demonstrates how to send ticket reply notification emails
 * when an admin responds to a customer's ticket.
 */

require_once __DIR__ . '/vendor/autoload.php';

use Karyalay\Services\EmailService;

// Load environment variables
if (file_exists(__DIR__ . '/.env')) {
    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
    $dotenv->load();
}

// Initialize database connection
require_once __DIR__ . '/config/database.php';

echo "=== Ticket Reply Notification Email Test ===\n\n";

// Sample reply data
$replyData = [
    'ticket_id' => 'a1b2c3d4-e5f6-7890-abcd-ef1234567890', // Sample UUID
    'customer_name' => 'John Doe',
    'customer_email' => 'customer@example.com', // Change this to your test email
    'ticket_subject' => 'Unable to access my account',
    'ticket_url' => 'http://localhost/app/support/tickets/view.php?id=a1b2c3d4-e5f6-7890-abcd-ef1234567890',
];

echo "Reply Notification Details:\n";
echo "- Ticket ID: {$replyData['ticket_id']}\n";
echo "- Customer: {$replyData['customer_name']} ({$replyData['customer_email']})\n";
echo "- Ticket Subject: {$replyData['ticket_subject']}\n";
echo "- Ticket URL: {$replyData['ticket_url']}\n\n";

echo "Sending ticket reply notification email...\n";

try {
    $emailService = EmailService::getInstance();
    $result = $emailService->sendTicketReplyNotification($replyData);
    
    if ($result) {
        echo "✅ SUCCESS: Ticket reply notification email sent successfully!\n\n";
        echo "Email sent to: {$replyData['customer_email']}\n\n";
        echo "The customer received:\n";
        echo "- Notification that a response is available\n";
        echo "- Ticket details (ID and subject)\n";
        echo "- Link to view the response on the website\n";
        echo "- NO reply content (for privacy and to drive engagement)\n\n";
        echo "Note: The email does NOT include the actual reply content.\n";
        echo "Customer must log in to view the response.\n";
    } else {
        echo "❌ ERROR: Failed to send ticket reply notification email\n";
        echo "Check the error logs for more details\n";
    }
} catch (Exception $e) {
    echo "❌ EXCEPTION: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}

echo "\n=== Test Complete ===\n";
