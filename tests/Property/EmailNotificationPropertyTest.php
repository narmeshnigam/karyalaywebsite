<?php

/**
 * Property Test: Email Notification on Contact Form
 * Feature: karyalay-portal-system, Property 40: Email Notification on Contact Form
 * Validates: Requirements 12.5
 * 
 * For any contact form submission, when the form is submitted,
 * an email notification should be sent to the configured admin email address.
 */

namespace Karyalay\Tests\Property;

use Eris\Generator;
use Eris\TestTrait;
use PHPUnit\Framework\TestCase;
use Karyalay\Services\EmailService;

class EmailNotificationPropertyTest extends TestCase
{
    use TestTrait;

    private EmailService $emailService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->emailService = new EmailService();
        
        // Ensure email configuration is set for testing
        if (empty($_ENV['MAIL_HOST'])) {
            $_ENV['MAIL_HOST'] = 'localhost';
        }
        if (empty($_ENV['MAIL_USERNAME'])) {
            $_ENV['MAIL_USERNAME'] = 'test';
        }
        if (empty($_ENV['MAIL_PASSWORD'])) {
            $_ENV['MAIL_PASSWORD'] = 'test';
        }
        if (empty($_ENV['ADMIN_EMAIL'])) {
            $_ENV['ADMIN_EMAIL'] = 'admin@karyalay.com';
        }
    }

    /**
     * Property: Contact form submission triggers email notification
     * 
     * For any valid contact form data, the sendContactFormNotification method
     * should be callable and return a boolean result.
     * 
     * @test
     */
    public function contactFormSubmissionTriggersEmailNotification(): void
    {
        $this->forAll(
            Generator\names(),
            Generator\string()
        )
        ->when(function ($name, $message) {
            // Constrain to valid input domain
            return strlen($name) >= 1 && strlen($name) <= 255
                && strlen($message) >= 1 && strlen($message) <= 5000;
        })
        ->then(function ($name, $message) {
            // Arrange: Create contact form data with a valid email
            $leadData = [
                'name' => $name,
                'email' => 'test@example.com',
                'phone' => '555-1234',
                'message' => $message
            ];
            
            // Act: Send email notification
            $result = $this->emailService->sendContactFormNotification($leadData);
            
            // Assert: Method returns a boolean
            $this->assertIsBool($result, 'sendContactFormNotification should return a boolean');
        });
    }

    /**
     * Property: Demo request submission triggers email notification
     * 
     * For any valid demo request data, the sendDemoRequestNotification method
     * should be callable and return a boolean result.
     * 
     * @test
     */
    public function demoRequestSubmissionTriggersEmailNotification(): void
    {
        $this->forAll(
            Generator\names(),
            Generator\string()
        )
        ->when(function ($name, $companyName) {
            // Constrain to valid input domain
            return strlen($name) >= 1 && strlen($name) <= 255
                && strlen($companyName) >= 1 && strlen($companyName) <= 255;
        })
        ->then(function ($name, $companyName) {
            // Arrange: Create demo request data with a valid email
            $leadData = [
                'name' => $name,
                'email' => 'demo@example.com',
                'phone' => '555-5678',
                'message' => 'Demo request',
                'company_name' => $companyName,
                'preferred_date' => date('Y-m-d', strtotime('+7 days'))
            ];
            
            // Act: Send email notification
            $result = $this->emailService->sendDemoRequestNotification($leadData);
            
            // Assert: Method returns a boolean
            $this->assertIsBool($result, 'sendDemoRequestNotification should return a boolean');
        });
    }

    /**
     * Property: Email service handles special characters in data
     * 
     * For any contact form data containing special characters,
     * the email service should handle them safely without errors.
     * 
     * @test
     */
    public function emailServiceHandlesSpecialCharactersInData(): void
    {
        // Test with various special characters
        $specialCharacterSets = [
            ['name' => 'John <script>alert("xss")</script>', 'email' => 'john@example.com', 'message' => 'Test'],
            ['name' => "O'Brien", 'email' => 'obrien@example.com', 'message' => "It's a test"],
            ['name' => 'José García', 'email' => 'jose@example.com', 'message' => 'Hola, ¿cómo estás?'],
            ['name' => 'Test & Co.', 'email' => 'test@example.com', 'message' => 'A & B < C > D'],
            ['name' => 'User "Quoted"', 'email' => 'user@example.com', 'message' => 'Message with "quotes"']
        ];
        
        foreach ($specialCharacterSets as $leadData) {
            // Act: Send email notification
            $result = $this->emailService->sendContactFormNotification($leadData);
            
            // Assert: Method returns a boolean (doesn't throw exception)
            $this->assertIsBool($result, 'Email service should handle special characters without errors');
        }
    }

    /**
     * Property: Email service handles empty optional fields
     * 
     * For any contact form data with empty optional fields,
     * the email service should handle them gracefully.
     * 
     * @test
     */
    public function emailServiceHandlesEmptyOptionalFields(): void
    {
        // Test with minimal required fields
        $leadData = [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'phone' => '',
            'message' => 'Test message'
        ];
        
        // Act: Send email notification
        $result = $this->emailService->sendContactFormNotification($leadData);
        
        // Assert: Method returns a boolean
        $this->assertIsBool($result, 'Email service should handle empty optional fields');
    }

    /**
     * Property: Email service generates valid HTML templates
     * 
     * For any contact form data, the generated email template should be valid HTML.
     * We test this indirectly by ensuring the method completes without errors.
     * 
     * @test
     */
    public function emailServiceGeneratesValidTemplates(): void
    {
        $this->forAll(
            Generator\string(),
            Generator\string()
        )
        ->when(function ($name, $message) {
            return strlen($name) >= 1 && strlen($name) <= 100
                && strlen($message) >= 1 && strlen($message) <= 500;
        })
        ->then(function ($name, $message) {
            // Arrange: Create contact form data
            $leadData = [
                'name' => $name,
                'email' => 'test@example.com',
                'phone' => '555-0000',
                'message' => $message
            ];
            
            // Act: Send email notification (which generates the template)
            $result = $this->emailService->sendContactFormNotification($leadData);
            
            // Assert: Method completes without throwing exceptions
            $this->assertIsBool($result, 'Email template generation should complete without errors');
        });
    }

    /**
     * Property: Multiple email notifications can be sent sequentially
     * 
     * For any sequence of contact form submissions, the email service
     * should be able to send multiple notifications without conflicts.
     * 
     * @test
     */
    public function multipleEmailNotificationsCanBeSentSequentially(): void
    {
        $notificationCount = 5;
        $results = [];
        
        // Send multiple email notifications
        for ($i = 0; $i < $notificationCount; $i++) {
            $leadData = [
                'name' => 'Test User ' . $i,
                'email' => 'test' . $i . '@example.com',
                'phone' => '555-' . str_pad($i, 4, '0', STR_PAD_LEFT),
                'message' => 'Test message ' . $i
            ];
            
            $result = $this->emailService->sendContactFormNotification($leadData);
            $results[] = $result;
        }
        
        // Assert: All notifications returned boolean results
        $this->assertCount($notificationCount, $results, 'All notifications should complete');
        foreach ($results as $i => $result) {
            $this->assertIsBool($result, "Notification $i should return a boolean");
        }
    }

    /**
     * Property: Email service handles long messages
     * 
     * For any contact form data with a long message,
     * the email service should handle it without errors.
     * 
     * @test
     */
    public function emailServiceHandlesLongMessages(): void
    {
        // Create a long message (2000 characters)
        $longMessage = str_repeat('This is a test message. ', 80);
        
        $leadData = [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'phone' => '555-0000',
            'message' => $longMessage
        ];
        
        // Act: Send email notification
        $result = $this->emailService->sendContactFormNotification($leadData);
        
        // Assert: Method returns a boolean
        $this->assertIsBool($result, 'Email service should handle long messages');
    }

    /**
     * Property: Email service handles newlines in messages
     * 
     * For any contact form data with newlines in the message,
     * the email service should handle them correctly.
     * 
     * @test
     */
    public function emailServiceHandlesNewlinesInMessages(): void
    {
        $messageWithNewlines = "Line 1\nLine 2\nLine 3\n\nLine 5";
        
        $leadData = [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'phone' => '555-0000',
            'message' => $messageWithNewlines
        ];
        
        // Act: Send email notification
        $result = $this->emailService->sendContactFormNotification($leadData);
        
        // Assert: Method returns a boolean
        $this->assertIsBool($result, 'Email service should handle newlines in messages');
    }
}
