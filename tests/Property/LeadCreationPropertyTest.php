<?php

/**
 * Property Test: Lead Creation from Forms
 * Feature: karyalay-portal-system, Property 38: Lead Creation from Forms
 * Validates: Requirements 12.1, 12.2
 * 
 * For any valid contact form or demo request submission, when the form is submitted,
 * a lead or demo request record should be created with all form fields and a submission timestamp.
 */

namespace Karyalay\Tests\Property;

use Eris\Generator;
use Eris\TestTrait;
use PHPUnit\Framework\TestCase;
use Karyalay\Models\Lead;

class LeadCreationPropertyTest extends TestCase
{
    use TestTrait;

    private Lead $leadModel;
    private array $createdLeadIds = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->leadModel = new Lead();
    }

    protected function tearDown(): void
    {
        // Clean up created leads
        foreach ($this->createdLeadIds as $id) {
            try {
                $this->leadModel->delete($id);
            } catch (\Exception $e) {
                // Ignore errors during cleanup
            }
        }
        $this->createdLeadIds = [];
        parent::tearDown();
    }

    /**
     * Property: Contact form submission creates lead with all fields
     * 
     * @test
     */
    public function contactFormSubmissionCreatesLeadWithAllFields(): void
    {
        $this->forAll(
            Generator\string(),
            Generator\string(),
            Generator\string()
        )
        ->when(function ($name, $email, $message) {
            return strlen($name) >= 1 && strlen($name) <= 255
                && strlen($email) >= 5 && strlen($email) <= 255
                && strlen($message) >= 1;
        })
        ->then(function ($name, $email, $message) {
            // Create a lead from contact form data
            $leadData = [
                'name' => $name,
                'email' => $email,
                'phone' => '555-1234',
                'message' => $message,
                'source' => 'CONTACT_FORM',
                'status' => 'NEW'
            ];
            
            $created = $this->leadModel->create($leadData);
            $this->assertNotFalse($created, 'Lead creation should succeed');
            $this->createdLeadIds[] = $created['id'];
            
            // Retrieve the lead
            $retrieved = $this->leadModel->findById($created['id']);
            $this->assertNotFalse($retrieved, 'Lead should be retrievable');
            
            // Assert: All form fields are present
            $this->assertArrayHasKey('name', $retrieved, 'Lead must have name field');
            $this->assertArrayHasKey('email', $retrieved, 'Lead must have email field');
            $this->assertArrayHasKey('phone', $retrieved, 'Lead must have phone field');
            $this->assertArrayHasKey('message', $retrieved, 'Lead must have message field');
            $this->assertArrayHasKey('source', $retrieved, 'Lead must have source field');
            $this->assertArrayHasKey('status', $retrieved, 'Lead must have status field');
            $this->assertArrayHasKey('created_at', $retrieved, 'Lead must have created_at timestamp');
            
            // Assert: Field values match
            $this->assertEquals($name, $retrieved['name']);
            $this->assertEquals($email, $retrieved['email']);
            $this->assertEquals($message, $retrieved['message']);
            $this->assertEquals('CONTACT_FORM', $retrieved['source']);
            $this->assertEquals('NEW', $retrieved['status']);
            
            // Assert: Timestamp is set
            $this->assertNotNull($retrieved['created_at']);
            
            // Clean up
            $this->leadModel->delete($created['id']);
            $this->createdLeadIds = array_diff($this->createdLeadIds, [$created['id']]);
        });
    }

    /**
     * Property: Demo request submission creates lead with demo-specific fields
     * 
     * @test
     */
    public function demoRequestSubmissionCreatesLeadWithDemoFields(): void
    {
        $this->forAll(
            Generator\string(),
            Generator\string()
        )
        ->when(function ($name, $companyName) {
            return strlen($name) >= 1 && strlen($name) <= 255
                && strlen($companyName) >= 1 && strlen($companyName) <= 255;
        })
        ->then(function ($name, $companyName) {
            // Create a lead from demo request form data
            $leadData = [
                'name' => $name,
                'email' => 'demo@example.com',
                'phone' => '555-5678',
                'message' => 'Demo request',
                'company_name' => $companyName,
                'preferred_date' => date('Y-m-d', strtotime('+7 days')),
                'source' => 'DEMO_REQUEST',
                'status' => 'NEW'
            ];
            
            $created = $this->leadModel->create($leadData);
            $this->assertNotFalse($created, 'Demo request creation should succeed');
            $this->createdLeadIds[] = $created['id'];
            
            // Retrieve the lead
            $retrieved = $this->leadModel->findById($created['id']);
            $this->assertNotFalse($retrieved, 'Demo request should be retrievable');
            
            // Assert: All fields including demo-specific ones are present
            $this->assertArrayHasKey('name', $retrieved);
            $this->assertArrayHasKey('company_name', $retrieved);
            $this->assertArrayHasKey('preferred_date', $retrieved);
            $this->assertArrayHasKey('source', $retrieved);
            $this->assertArrayHasKey('created_at', $retrieved);
            
            // Assert: Field values match
            $this->assertEquals($name, $retrieved['name']);
            $this->assertEquals($companyName, $retrieved['company_name']);
            $this->assertEquals('DEMO_REQUEST', $retrieved['source']);
            
            // Clean up
            $this->leadModel->delete($created['id']);
            $this->createdLeadIds = array_diff($this->createdLeadIds, [$created['id']]);
        });
    }

    /**
     * Property: Lead creation timestamp is set
     * 
     * @test
     */
    public function leadCreationTimestampIsSet(): void
    {
        // Create a lead
        $leadData = [
            'name' => 'Test Lead ' . bin2hex(random_bytes(4)),
            'email' => 'test' . bin2hex(random_bytes(4)) . '@example.com',
            'phone' => '555-0000',
            'message' => 'Test message',
            'source' => 'CONTACT_FORM',
            'status' => 'NEW'
        ];
        
        $created = $this->leadModel->create($leadData);
        $this->assertNotFalse($created);
        $this->createdLeadIds[] = $created['id'];
        
        // Retrieve and check timestamp
        $retrieved = $this->leadModel->findById($created['id']);
        
        // Assert: Timestamp is set and is a valid datetime
        $this->assertNotNull($retrieved['created_at'], 'Created timestamp should be set');
        $this->assertNotEmpty($retrieved['created_at'], 'Created timestamp should not be empty');
        
        // Assert: Timestamp can be parsed as a valid date
        $createdTimestamp = strtotime($retrieved['created_at']);
        $this->assertNotFalse($createdTimestamp, 'Created timestamp should be a valid datetime');
        $this->assertGreaterThan(0, $createdTimestamp, 'Created timestamp should be positive');
        
        // Clean up
        $this->leadModel->delete($created['id']);
        $this->createdLeadIds = array_diff($this->createdLeadIds, [$created['id']]);
    }

    /**
     * Property: Multiple leads can be created without conflicts
     * 
     * @test
     */
    public function multipleLeadsCanBeCreatedWithoutConflicts(): void
    {
        $leadCount = 5;
        $createdLeads = [];
        
        // Create multiple leads
        for ($i = 0; $i < $leadCount; $i++) {
            $leadData = [
                'name' => 'Test Lead ' . $i . ' ' . bin2hex(random_bytes(4)),
                'email' => 'test' . $i . bin2hex(random_bytes(4)) . '@example.com',
                'phone' => '555-' . str_pad($i, 4, '0', STR_PAD_LEFT),
                'message' => 'Test message ' . $i,
                'source' => 'CONTACT_FORM',
                'status' => 'NEW'
            ];
            
            $created = $this->leadModel->create($leadData);
            $this->assertNotFalse($created, "Lead $i creation should succeed");
            $this->createdLeadIds[] = $created['id'];
            $createdLeads[] = $created;
        }
        
        // Assert: All leads were created with unique IDs
        $ids = array_column($createdLeads, 'id');
        $uniqueIds = array_unique($ids);
        $this->assertCount($leadCount, $uniqueIds, 'All leads should have unique IDs');
        
        // Assert: All leads can be retrieved
        foreach ($createdLeads as $lead) {
            $retrieved = $this->leadModel->findById($lead['id']);
            $this->assertNotFalse($retrieved, "Lead {$lead['id']} should be retrievable");
        }
        
        // Clean up
        foreach ($createdLeads as $lead) {
            $this->leadModel->delete($lead['id']);
        }
        $this->createdLeadIds = [];
    }
}
