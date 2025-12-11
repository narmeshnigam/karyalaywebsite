<?php
/**
 * Test script for ticket notification emails
 * 
 * This script demonstrates how to send ticket notification emails
 * when a user creates a support ticket.
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

echo "=== Ticket Notification Email Test ===\n\n";

// Sample ticket data
$ticketData = [
    'ticket_id' => 'TKT-' . date('Ymd') . '-' . rand(1000, 9999),
    'customer_name' => 'John Doe',
    'customer_email' => 'customer@example.com', // Change this to your test email
    'customer_phone' => '+1 (555) 123-4567',
    'subject' => 'Unable to access my account',
    'description' => 'I am having trouble logging into my account. I have tried resetting my password but I am not receiving the reset email. Please help me resolve this issue as soon as possible.',
    'priority' => 'High', // Options: Low, Normal, Medium, High, Urgent
    'category' => 'Account Access', // Options: General, Technical, Billing, Account Access, etc.
];

echo "Ticket Details:\n";
echo "- Ticket ID: {$ticketData['ticket_id']}\n";
echo "- Customer: {$ticketData['customer_name']} ({$ticketData['customer_email']})\n";
echo "- Subject: {$ticketData['subject']}\n";
echo "- Priority: {$ticketData['priority']}\n";
echo "- Category: {$ticketData['category']}\n\n";

echo "Sending ticket notification emails...\n";

try {
    $emailService = EmailService::getInstance();
    $result = $emailService->sendTicketNotification($ticketData);
    
    if ($result) {
        echo "✅ SUCCESS: Ticket notification emails sent successfully!\n\n";
        echo "Two emails were sent:\n";
        echo "1. Customer confirmation to: {$ticketData['customer_email']}\n";
        echo "2. Admin notification to: Website notification email address\n\n";
        echo "The customer received:\n";
        echo "- Ticket confirmation with ticket number #{$ticketData['ticket_id']}\n";
        echo "- Information that they will be contacted via email or phone\n\n";
        echo "The admin received:\n";
        echo "- Full ticket details\n";
        echo "- Customer contact information (name, email, phone)\n";
    } else {
        echo "❌ ERROR: Failed to send ticket notification emails\n";
        echo "Check the error logs for more details\n";
    }
} catch (Exception $e) {
    echo "❌ EXCEPTION: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}

echo "\n=== Test Complete ===\n";
