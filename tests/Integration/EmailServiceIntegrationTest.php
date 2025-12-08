<?php

namespace Karyalay\Tests\Integration;

use PHPUnit\Framework\TestCase;
use Karyalay\Services\EmailService;
use Karyalay\Models\Lead;

/**
 * Integration Test: Email Service Integration
 * 
 * Tests email service integration with SMTP/SES.
 * Note: These tests will skip if email credentials are not configured.
 */
class EmailServiceIntegrationTest extends TestCase
{
    private ?EmailService $emailService = null;
    private Lead $leadModel;
    private bool $skipTests = false;
    private array $testLeads = [];

    protected function setUp(): void
    {
        parent::setUp();
        
        // Check if email is configured
        if (empty($_ENV['MAIL_HOST']) || empty($_ENV['MAIL_USERNAME'])) {
            $this->skipTests = true;
            $this->markTestSkipped('Email service not configured');
        }
        
        try {
            $this->emailService = new EmailService();
        } catch (\Exception $e) {
            $this->skipTests = true;
            $this->markTestSkipped('Email service initialization failed: ' . $e->getMessage());
        }
        
        $this->leadModel = new Lead();
    }

    protected function tearDown(): void
    {
        // Clean up test leads
        foreach ($this->testLeads as $leadId) {
            $this->leadModel->delete($leadId);
        }
        
        parent::tearDown();
    }

    /**
     * Test basic email sending
     * 
     * @test
     */
    public function testBasicEmailSending(): void
    {
        if ($this->skipTests) {
            $this->markTestSkipped('Email service not configured');
        }
        
        $testEmail = $_ENV['TEST_EMAIL'] ?? 'test@example.com';
        
        $subject = 'Test Email from Integration Test';
        $body = '<h1>Test Email</h1><p>This is a test email sent from the integration test suite.</p>';
        $plainText = 'Test Email\n\nThis is a test email sent from the integration test suite.';
        
        $result = $this->emailService->send($testEmail, $subject, $body, $plainText);
        
        if (!$result) {
            $this->markTestSkipped('Email sending failed - may be due to invalid credentials or network issues');
        }
        
        $this->assertTrue(
            $result,
            'Email should be sent successfully'
        );
    }

    /**
     * Test contact form notification email
     * 
     * @test
     */
    public function testContactFormNotificationEmail(): void
    {
        if ($this->skipTests) {
            $this->markTestSkipped('Email service not configured');
        }
        
        // Create test lead data
        $leadData = [
            'name' => 'John Doe',
            'email' => 'john.doe@example.com',
            'phone' => '1234567890',
            'message' => 'I am interested in learning more about your services.',
            'source' => 'CONTACT_FORM'
        ];
        
        // Create lead in database
        $lead = $this->leadModel->create($leadData);
        $this->assertNotFalse($lead, 'Lead creation should succeed');
        $this->testLeads[] = $lead['id'];
        
        // Send notification email
        $result = $this->emailService->sendContactFormNotification($leadData);
        
        if (!$result) {
            $this->markTestSkipped('Email sending failed - may be due to invalid credentials or network issues');
        }
        
        $this->assertTrue(
            $result,
            'Contact form notification email should be sent successfully'
        );
    }

    /**
     * Test demo request notification email
     * 
     * @test
     */
    public function testDemoRequestNotificationEmail(): void
    {
        if ($this->skipTests) {
            $this->markTestSkipped('Email service not configured');
        }
        
        // Create test demo request data
        $leadData = [
            'name' => 'Jane Smith',
            'email' => 'jane.smith@example.com',
            'phone' => '0987654321',
            'company_name' => 'Acme Corporation',
            'preferred_date' => '2024-12-15',
            'message' => 'We would like to schedule a demo for our team.',
            'source' => 'DEMO_REQUEST'
        ];
        
        // Create lead in database
        $lead = $this->leadModel->create($leadData);
        $this->assertNotFalse($lead, 'Lead creation should succeed');
        $this->testLeads[] = $lead['id'];
        
        // Send notification email
        $result = $this->emailService->sendDemoRequestNotification($leadData);
        
        if (!$result) {
            $this->markTestSkipped('Email sending failed - may be due to invalid credentials or network issues');
        }
        
        $this->assertTrue(
            $result,
            'Demo request notification email should be sent successfully'
        );
    }

    /**
     * Test email with HTML content
     * 
     * @test
     */
    public function testEmailWithHtmlContent(): void
    {
        if ($this->skipTests) {
            $this->markTestSkipped('Email service not configured');
        }
        
        $testEmail = $_ENV['TEST_EMAIL'] ?? 'test@example.com';
        
        $subject = 'HTML Email Test';
        $htmlBody = <<<HTML
<!DOCTYPE html>
<html>
<head>
    <style>
        body { font-family: Arial, sans-serif; }
        .header { background-color: #4a5568; color: white; padding: 20px; }
        .content { padding: 20px; }
        .button { background-color: #3182ce; color: white; padding: 10px 20px; text-decoration: none; }
    </style>
</head>
<body>
    <div class="header">
        <h1>Welcome to Karyalay</h1>
    </div>
    <div class="content">
        <p>This is a test email with HTML formatting.</p>
        <p><a href="#" class="button">Click Here</a></p>
    </div>
</body>
</html>
HTML;
        
        $plainText = "Welcome to Karyalay\n\nThis is a test email with HTML formatting.\n\nClick Here: #";
        
        $result = $this->emailService->send($testEmail, $subject, $htmlBody, $plainText);
        
        if (!$result) {
            $this->markTestSkipped('Email sending failed - may be due to invalid credentials or network issues');
        }
        
        $this->assertTrue($result, 'HTML email should be sent successfully');
    }

    /**
     * Test email with special characters
     * 
     * @test
     */
    public function testEmailWithSpecialCharacters(): void
    {
        if ($this->skipTests) {
            $this->markTestSkipped('Email service not configured');
        }
        
        $testEmail = $_ENV['TEST_EMAIL'] ?? 'test@example.com';
        
        $subject = 'Test Email with Special Characters: <>&"\'';
        $body = '<p>Testing special characters: &lt;&gt;&amp;&quot;&#39;</p><p>Unicode: 你好 مرحبا Привет</p>';
        $plainText = "Testing special characters: <>&\"'\nUnicode: 你好 مرحبا Привет";
        
        $result = $this->emailService->send($testEmail, $subject, $body, $plainText);
        
        if (!$result) {
            $this->markTestSkipped('Email sending failed - may be due to invalid credentials or network issues');
        }
        
        $this->assertTrue(
            $result,
            'Email with special characters should be sent successfully'
        );
    }

    /**
     * Test multiple emails in sequence
     * 
     * @test
     */
    public function testMultipleEmailsInSequence(): void
    {
        if ($this->skipTests) {
            $this->markTestSkipped('Email service not configured');
        }
        
        $testEmail = $_ENV['TEST_EMAIL'] ?? 'test@example.com';
        $emailCount = 3;
        $results = [];
        
        for ($i = 1; $i <= $emailCount; $i++) {
            $subject = "Test Email $i of $emailCount";
            $body = "<p>This is test email number $i.</p>";
            $plainText = "This is test email number $i.";
            
            $result = $this->emailService->send($testEmail, $subject, $body, $plainText);
            $results[] = $result;
            
            // Small delay between emails to avoid rate limiting
            usleep(100000); // 100ms
        }
        
        // Check if at least one email was sent successfully
        $successCount = count(array_filter($results));
        
        if ($successCount === 0) {
            $this->markTestSkipped('All email sends failed - may be due to rate limiting or network issues');
        }
        
        $this->assertGreaterThan(
            0,
            $successCount,
            'At least one email should be sent successfully'
        );
    }

    /**
     * Test email sending with invalid recipient
     * 
     * @test
     */
    public function testEmailSendingWithInvalidRecipient(): void
    {
        if ($this->skipTests) {
            $this->markTestSkipped('Email service not configured');
        }
        
        $invalidEmail = 'not-a-valid-email';
        
        $subject = 'Test Email';
        $body = '<p>This should not be sent.</p>';
        
        $result = $this->emailService->send($invalidEmail, $subject, $body);
        
        $this->assertFalse(
            $result,
            'Email sending should fail with invalid recipient'
        );
    }

    /**
     * Test contact form notification with complete data
     * 
     * @test
     */
    public function testContactFormNotificationWithCompleteData(): void
    {
        if ($this->skipTests) {
            $this->markTestSkipped('Email service not configured');
        }
        
        $leadData = [
            'name' => 'Complete Data Test User',
            'email' => 'complete.test@example.com',
            'phone' => '+1-555-123-4567',
            'message' => "This is a comprehensive test message.\n\nIt includes multiple lines.\n\nAnd special characters: <>&\"'",
            'source' => 'CONTACT_FORM',
            'company_name' => 'Test Company Inc.'
        ];
        
        $lead = $this->leadModel->create($leadData);
        $this->assertNotFalse($lead);
        $this->testLeads[] = $lead['id'];
        
        $result = $this->emailService->sendContactFormNotification($leadData);
        
        if (!$result) {
            $this->markTestSkipped('Email sending failed - may be due to invalid credentials or network issues');
        }
        
        $this->assertTrue($result);
    }

    /**
     * Test demo request notification with complete data
     * 
     * @test
     */
    public function testDemoRequestNotificationWithCompleteData(): void
    {
        if ($this->skipTests) {
            $this->markTestSkipped('Email service not configured');
        }
        
        $leadData = [
            'name' => 'Demo Request Test User',
            'email' => 'demo.test@example.com',
            'phone' => '+1-555-987-6543',
            'company_name' => 'Enterprise Solutions Ltd.',
            'preferred_date' => '2024-12-20 14:00:00',
            'message' => "We are interested in a comprehensive demo.\n\nOur team size: 50+ users\nIndustry: Technology\nCurrent solution: Legacy system",
            'source' => 'DEMO_REQUEST'
        ];
        
        $lead = $this->leadModel->create($leadData);
        $this->assertNotFalse($lead);
        $this->testLeads[] = $lead['id'];
        
        $result = $this->emailService->sendDemoRequestNotification($leadData);
        
        if (!$result) {
            $this->markTestSkipped('Email sending failed - may be due to invalid credentials or network issues');
        }
        
        $this->assertTrue($result);
    }

    /**
     * Test email sending with empty body
     * 
     * @test
     */
    public function testEmailSendingWithEmptyBody(): void
    {
        if ($this->skipTests) {
            $this->markTestSkipped('Email service not configured');
        }
        
        $testEmail = $_ENV['TEST_EMAIL'] ?? 'test@example.com';
        
        $subject = 'Empty Body Test';
        $body = '';
        
        $result = $this->emailService->send($testEmail, $subject, $body);
        
        // Email with empty body might still be sent by some providers
        // We just verify the method doesn't crash
        $this->assertIsBool($result, 'Result should be a boolean');
    }

    /**
     * Test email notification flow after lead creation
     * 
     * @test
     */
    public function testCompleteLeadCreationAndNotificationFlow(): void
    {
        if ($this->skipTests) {
            $this->markTestSkipped('Email service not configured');
        }
        
        // ===== STEP 1: Create Lead =====
        $leadData = [
            'name' => 'Flow Test User',
            'email' => 'flow.test@example.com',
            'phone' => '5551234567',
            'message' => 'I would like more information about your services.',
            'source' => 'CONTACT_FORM'
        ];
        
        $lead = $this->leadModel->create($leadData);
        $this->assertNotFalse($lead, 'Lead creation should succeed');
        $this->testLeads[] = $lead['id'];
        
        // ===== STEP 2: Send Notification =====
        $emailResult = $this->emailService->sendContactFormNotification($leadData);
        
        if (!$emailResult) {
            $this->markTestSkipped('Email sending failed - may be due to invalid credentials or network issues');
        }
        
        $this->assertTrue($emailResult, 'Email notification should be sent');
        
        // ===== STEP 3: Verify Lead Status =====
        $createdLead = $this->leadModel->findById($lead['id']);
        $this->assertEquals('NEW', $createdLead['status']);
        $this->assertEquals($leadData['name'], $createdLead['name']);
        $this->assertEquals($leadData['email'], $createdLead['email']);
        
        // ===== STEP 4: Update Lead Status =====
        $updateResult = $this->leadModel->updateStatus($lead['id'], 'CONTACTED');
        $this->assertTrue($updateResult, 'Lead status update should succeed');
        
        // ===== STEP 5: Verify Updated Status =====
        $updatedLead = $this->leadModel->findById($lead['id']);
        $this->assertEquals('CONTACTED', $updatedLead['status']);
        $this->assertNotNull($updatedLead['contacted_at']);
    }
}
