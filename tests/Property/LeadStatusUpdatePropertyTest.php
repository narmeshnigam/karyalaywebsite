<?php

/**
 * Property Test: Lead Status Update with Timestamp
 * Feature: karyalay-portal-system, Property 39: Lead Status Update with Timestamp
 * Validates: Requirements 12.4
 * 
 * For any lead, when an admin marks it as contacted, the lead status should be updated
 * and an action timestamp should be recorded.
 */

namespace Karyalay\Tests\Property;

use Eris\Generator;
use Eris\TestTrait;
use PHPUnit\Framework\TestCase;
use Karyalay\Models\Lead;

class LeadStatusUpdatePropertyTest extends TestCase
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
     * Property: Marking lead as contacted updates status and records timestamp
     * 
     * @test
     */
    public function markingLeadAsContactedUpdatesStatusAndRecordsTimestamp(): void
    {
        $this->forAll(
            Generator\string(),
            Generator\string()
        )
        ->when(function ($name, $email) {
            return strlen($name) >= 1 && strlen($name) <= 255
                && strlen($email) >= 5 && strlen($email) <= 255;
        })
        ->then(function ($name, $email) {
            // Create a lead with NEW status
            $leadData = [
                'name' => $name,
                'email' => $email,
                'phone' => '555-1234',
                'message' => 'Test message',
                'source' => 'CONTACT_FORM',
                'status' => 'NEW'
            ];
            
            $created = $this->leadModel->create($leadData);
            $this->assertNotFalse($created, 'Lead creation should succeed');
            $this->createdLeadIds[] = $created['id'];
            
            // Verify initial state
            $beforeUpdate = $this->leadModel->findById($created['id']);
            $this->assertEquals('NEW', $beforeUpdate['status'], 'Initial status should be NEW');
            $this->assertNull($beforeUpdate['contacted_at'], 'Initial contacted_at should be null');
            
            // Mark lead as contacted
            $result = $this->leadModel->markAsContacted($created['id']);
            $this->assertTrue($result, 'markAsContacted should succeed');
            
            // Retrieve updated lead
            $afterUpdate = $this->leadModel->findById($created['id']);
            $this->assertNotFalse($afterUpdate, 'Lead should be retrievable after update');
            
            // Assert: Status is updated to CONTACTED
            $this->assertEquals('CONTACTED', $afterUpdate['status'], 
                'Status should be updated to CONTACTED');
            
            // Assert: contacted_at timestamp is set
            $this->assertNotNull($afterUpdate['contacted_at'], 
                'contacted_at timestamp should be set');
            $this->assertNotEmpty($afterUpdate['contacted_at'], 
                'contacted_at timestamp should not be empty');
            
            // Assert: contacted_at is a valid datetime
            $contactedTimestamp = strtotime($afterUpdate['contacted_at']);
            $this->assertNotFalse($contactedTimestamp, 
                'contacted_at should be a valid datetime');
            $this->assertGreaterThan(0, $contactedTimestamp, 
                'contacted_at timestamp should be positive');
            
            // Assert: contacted_at is recent (within last minute)
            $now = time();
            $this->assertLessThanOrEqual($now, $contactedTimestamp, 
                'contacted_at should not be in the future');
            $this->assertGreaterThan($now - 60, $contactedTimestamp, 
                'contacted_at should be recent (within last minute)');
            
            // Clean up
            $this->leadModel->delete($created['id']);
            $this->createdLeadIds = array_diff($this->createdLeadIds, [$created['id']]);
        });
    }

    /**
     * Property: Marking lead as contacted with notes stores notes
     * 
     * @test
     */
    public function markingLeadAsContactedWithNotesStoresNotes(): void
    {
        $this->forAll(
            Generator\string()
        )
        ->when(function ($notes) {
            return strlen($notes) >= 1 && strlen($notes) <= 1000;
        })
        ->then(function ($notes) {
            // Create a lead
            $leadData = [
                'name' => 'Test Lead ' . bin2hex(random_bytes(4)),
                'email' => 'test' . bin2hex(random_bytes(4)) . '@example.com',
                'phone' => '555-1234',
                'message' => 'Test message',
                'source' => 'CONTACT_FORM',
                'status' => 'NEW'
            ];
            
            $created = $this->leadModel->create($leadData);
            $this->assertNotFalse($created, 'Lead creation should succeed');
            $this->createdLeadIds[] = $created['id'];
            
            // Mark lead as contacted with notes
            $result = $this->leadModel->markAsContacted($created['id'], $notes);
            $this->assertTrue($result, 'markAsContacted with notes should succeed');
            
            // Retrieve updated lead
            $afterUpdate = $this->leadModel->findById($created['id']);
            
            // Assert: Notes are stored
            $this->assertNotNull($afterUpdate['notes'], 'Notes should be stored');
            $this->assertEquals($notes, $afterUpdate['notes'], 'Notes should match input');
            
            // Assert: Status and timestamp are also updated
            $this->assertEquals('CONTACTED', $afterUpdate['status']);
            $this->assertNotNull($afterUpdate['contacted_at']);
            
            // Clean up
            $this->leadModel->delete($created['id']);
            $this->createdLeadIds = array_diff($this->createdLeadIds, [$created['id']]);
        });
    }

    /**
     * Property: Multiple leads can be marked as contacted independently
     * 
     * @test
     */
    public function multipleLeadsCanBeMarkedAsContactedIndependently(): void
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
        
        // Mark each lead as contacted at different times
        foreach ($createdLeads as $index => $lead) {
            $result = $this->leadModel->markAsContacted($lead['id'], "Notes for lead $index");
            $this->assertTrue($result, "Marking lead $index as contacted should succeed");
            
            // Small delay to ensure different timestamps
            usleep(10000); // 10ms delay
        }
        
        // Verify all leads are marked as contacted with timestamps
        foreach ($createdLeads as $index => $lead) {
            $retrieved = $this->leadModel->findById($lead['id']);
            
            $this->assertEquals('CONTACTED', $retrieved['status'], 
                "Lead $index status should be CONTACTED");
            $this->assertNotNull($retrieved['contacted_at'], 
                "Lead $index should have contacted_at timestamp");
            $this->assertEquals("Notes for lead $index", $retrieved['notes'], 
                "Lead $index should have correct notes");
        }
        
        // Clean up
        foreach ($createdLeads as $lead) {
            $this->leadModel->delete($lead['id']);
        }
        $this->createdLeadIds = [];
    }

    /**
     * Property: Marking already contacted lead updates timestamp
     * 
     * @test
     */
    public function markingAlreadyContactedLeadUpdatesTimestamp(): void
    {
        // Create a lead
        $leadData = [
            'name' => 'Test Lead ' . bin2hex(random_bytes(4)),
            'email' => 'test' . bin2hex(random_bytes(4)) . '@example.com',
            'phone' => '555-1234',
            'message' => 'Test message',
            'source' => 'CONTACT_FORM',
            'status' => 'NEW'
        ];
        
        $created = $this->leadModel->create($leadData);
        $this->assertNotFalse($created);
        $this->createdLeadIds[] = $created['id'];
        
        // Mark as contacted first time
        $result1 = $this->leadModel->markAsContacted($created['id'], 'First contact');
        $this->assertTrue($result1);
        
        $afterFirstContact = $this->leadModel->findById($created['id']);
        $firstContactedAt = $afterFirstContact['contacted_at'];
        
        // Wait a moment
        sleep(1);
        
        // Mark as contacted second time
        $result2 = $this->leadModel->markAsContacted($created['id'], 'Second contact');
        $this->assertTrue($result2);
        
        $afterSecondContact = $this->leadModel->findById($created['id']);
        $secondContactedAt = $afterSecondContact['contacted_at'];
        
        // Assert: Status remains CONTACTED
        $this->assertEquals('CONTACTED', $afterSecondContact['status']);
        
        // Assert: Timestamp is updated (second timestamp should be later)
        $this->assertNotEquals($firstContactedAt, $secondContactedAt, 
            'contacted_at timestamp should be updated on second contact');
        $this->assertGreaterThan(strtotime($firstContactedAt), strtotime($secondContactedAt), 
            'Second contacted_at should be later than first');
        
        // Assert: Notes are updated
        $this->assertEquals('Second contact', $afterSecondContact['notes']);
        
        // Clean up
        $this->leadModel->delete($created['id']);
        $this->createdLeadIds = array_diff($this->createdLeadIds, [$created['id']]);
    }

    /**
     * Property: Lead status update preserves other fields
     * 
     * @test
     */
    public function leadStatusUpdatePreservesOtherFields(): void
    {
        // Create a lead with all fields
        $leadData = [
            'name' => 'Test Lead ' . bin2hex(random_bytes(4)),
            'email' => 'test' . bin2hex(random_bytes(4)) . '@example.com',
            'phone' => '555-1234',
            'message' => 'Original message',
            'company_name' => 'Test Company',
            'preferred_date' => date('Y-m-d', strtotime('+7 days')),
            'source' => 'DEMO_REQUEST',
            'status' => 'NEW'
        ];
        
        $created = $this->leadModel->create($leadData);
        $this->assertNotFalse($created);
        $this->createdLeadIds[] = $created['id'];
        
        // Mark as contacted
        $result = $this->leadModel->markAsContacted($created['id'], 'Contact notes');
        $this->assertTrue($result);
        
        // Retrieve updated lead
        $afterUpdate = $this->leadModel->findById($created['id']);
        
        // Assert: Status and contacted_at are updated
        $this->assertEquals('CONTACTED', $afterUpdate['status']);
        $this->assertNotNull($afterUpdate['contacted_at']);
        $this->assertEquals('Contact notes', $afterUpdate['notes']);
        
        // Assert: Other fields are preserved
        $this->assertEquals($leadData['name'], $afterUpdate['name'], 
            'Name should be preserved');
        $this->assertEquals($leadData['email'], $afterUpdate['email'], 
            'Email should be preserved');
        $this->assertEquals($leadData['phone'], $afterUpdate['phone'], 
            'Phone should be preserved');
        $this->assertEquals($leadData['message'], $afterUpdate['message'], 
            'Message should be preserved');
        $this->assertEquals($leadData['company_name'], $afterUpdate['company_name'], 
            'Company name should be preserved');
        $this->assertEquals($leadData['preferred_date'], $afterUpdate['preferred_date'], 
            'Preferred date should be preserved');
        $this->assertEquals($leadData['source'], $afterUpdate['source'], 
            'Source should be preserved');
        
        // Clean up
        $this->leadModel->delete($created['id']);
        $this->createdLeadIds = array_diff($this->createdLeadIds, [$created['id']]);
    }
}

