<?php

namespace Karyalay\Tests\Property;

use Eris\Generator;
use Eris\TestTrait;
use PHPUnit\Framework\TestCase;
use Karyalay\Services\ContentService;
use Karyalay\Models\Module;
use Karyalay\Models\Feature;
use Karyalay\Models\BlogPost;
use Karyalay\Models\CaseStudy;
use Karyalay\Models\User;

/**
 * Property-based tests for content CRUD persistence
 * 
 * Feature: karyalay-portal-system, Property 27: Content CRUD Persistence
 * Validates: Requirements 8.1, 8.2, 8.3
 */
class ContentCrudPersistencePropertyTest extends TestCase
{
    use TestTrait;

    private ContentService $contentService;
    private Module $moduleModel;
    private Feature $featureModel;
    private BlogPost $blogPostModel;
    private CaseStudy $caseStudyModel;
    private User $userModel;
    private ?string $testAuthorId = null;

    protected function setUp(): void
    {
        parent::setUp();
        $this->contentService = new ContentService();
        $this->moduleModel = new Module();
        $this->featureModel = new Feature();
        $this->blogPostModel = new BlogPost();
        $this->caseStudyModel = new CaseStudy();
        $this->userModel = new User();
        
        // Create a test author for blog posts
        $this->testAuthorId = $this->createTestAuthor();
    }

    protected function tearDown(): void
    {
        // Clean up test author
        if ($this->testAuthorId) {
            $this->userModel->delete($this->testAuthorId);
        }
        parent::tearDown();
    }

    /**
     * Property 27: Content CRUD Persistence - Module
     * 
     * For any content entity (module), when an admin creates or updates the entity,
     * the changes should be persisted to the database and immediately reflected.
     * 
     * Validates: Requirements 8.1, 8.2
     * 
     * @test
     */
    public function moduleCreateAndUpdatePersistsToDatabase(): void
    {
        $this->forAll(
            Generator\string(),
            Generator\string(),
            Generator\elements(['DRAFT', 'PUBLISHED', 'ARCHIVED'])
        )
        ->when(function ($name, $description, $status) {
            return strlen($name) >= 1 && strlen($name) <= 255;
        })
        ->then(function ($name, $description, $status) {
            // Create module
            $moduleData = [
                'name' => $name,
                'description' => $description,
                'status' => $status,
                'features' => ['Feature 1', 'Feature 2'],
                'screenshots' => ['screenshot1.jpg'],
                'faqs' => [['question' => 'Q1', 'answer' => 'A1']],
                'display_order' => 1
            ];
            
            $created = $this->contentService->create('module', $moduleData);
            
            // Assert: Creation succeeded
            $this->assertNotFalse($created, 'Module creation should succeed');
            $this->assertIsArray($created);
            $this->assertArrayHasKey('id', $created);
            $this->assertArrayHasKey('slug', $created);
            
            // Assert: Data persisted correctly
            $retrieved = $this->contentService->read('module', $created['id']);
            $this->assertNotFalse($retrieved, 'Module should be retrievable after creation');
            $this->assertEquals($name, $retrieved['name']);
            $this->assertEquals($description, $retrieved['description']);
            $this->assertEquals($status, $retrieved['status']);
            
            // Update module
            $newName = $name . ' Updated';
            $newStatus = $status === 'DRAFT' ? 'PUBLISHED' : 'DRAFT';
            $updateResult = $this->contentService->update('module', $created['id'], [
                'name' => $newName,
                'status' => $newStatus
            ]);
            
            // Assert: Update succeeded
            $this->assertTrue($updateResult, 'Module update should succeed');
            
            // Assert: Changes persisted
            $updated = $this->contentService->read('module', $created['id']);
            $this->assertNotFalse($updated, 'Module should be retrievable after update');
            $this->assertEquals($newName, $updated['name']);
            $this->assertEquals($newStatus, $updated['status']);
            
            // Cleanup
            $this->contentService->delete('module', $created['id']);
        });
    }

    /**
     * Property 27: Content CRUD Persistence - Feature
     * 
     * For any content entity (feature), when an admin creates or updates the entity,
     * the changes should be persisted to the database and immediately reflected.
     * 
     * Validates: Requirements 8.1, 8.2
     * 
     * @test
     */
    public function featureCreateAndUpdatePersistsToDatabase(): void
    {
        $this->forAll(
            Generator\string(),
            Generator\string(),
            Generator\elements(['DRAFT', 'PUBLISHED', 'ARCHIVED'])
        )
        ->when(function ($name, $description, $status) {
            return strlen($name) >= 1 && strlen($name) <= 255;
        })
        ->then(function ($name, $description, $status) {
            // Create feature
            $featureData = [
                'name' => $name,
                'description' => $description,
                'status' => $status,
                'benefits' => ['Benefit 1', 'Benefit 2'],
                'related_modules' => ['module-1'],
                'screenshots' => ['screenshot1.jpg'],
                'display_order' => 1
            ];
            
            $created = $this->contentService->create('feature', $featureData);
            
            // Assert: Creation succeeded
            $this->assertNotFalse($created, 'Feature creation should succeed');
            $this->assertIsArray($created);
            $this->assertArrayHasKey('id', $created);
            
            // Assert: Data persisted correctly
            $retrieved = $this->contentService->read('feature', $created['id']);
            $this->assertNotFalse($retrieved, 'Feature should be retrievable after creation');
            $this->assertEquals($name, $retrieved['name']);
            $this->assertEquals($status, $retrieved['status']);
            
            // Update feature
            $newDescription = $description . ' Updated';
            $updateResult = $this->contentService->update('feature', $created['id'], [
                'description' => $newDescription
            ]);
            
            // Assert: Update succeeded
            $this->assertTrue($updateResult, 'Feature update should succeed');
            
            // Assert: Changes persisted
            $updated = $this->contentService->read('feature', $created['id']);
            $this->assertEquals($newDescription, $updated['description']);
            
            // Cleanup
            $this->contentService->delete('feature', $created['id']);
        });
    }

    /**
     * Property 27: Content CRUD Persistence - Blog Post
     * 
     * For any content entity (blog post), when an admin creates or updates the entity,
     * the changes should be persisted to the database and immediately reflected.
     * 
     * Validates: Requirements 8.1, 8.2, 8.3
     * 
     * @test
     */
    public function blogPostCreateAndUpdatePersistsToDatabase(): void
    {
        $this->forAll(
            Generator\string(),
            Generator\string(),
            Generator\elements(['DRAFT', 'PUBLISHED', 'ARCHIVED'])
        )
        ->when(function ($title, $content, $status) {
            return strlen($title) >= 1 && strlen($title) <= 255 && strlen($content) >= 1;
        })
        ->then(function ($title, $content, $status) {
            // Create blog post
            $blogPostData = [
                'title' => $title,
                'content' => $content,
                'excerpt' => substr($content, 0, 100),
                'author_id' => $this->testAuthorId,
                'status' => $status,
                'tags' => ['tag1', 'tag2']
            ];
            
            $created = $this->contentService->create('blog_post', $blogPostData);
            
            // Assert: Creation succeeded
            $this->assertNotFalse($created, 'Blog post creation should succeed');
            $this->assertIsArray($created);
            $this->assertArrayHasKey('id', $created);
            
            // Assert: Data persisted correctly
            $retrieved = $this->contentService->read('blog_post', $created['id']);
            $this->assertNotFalse($retrieved, 'Blog post should be retrievable after creation');
            $this->assertEquals($title, $retrieved['title']);
            $this->assertEquals($content, $retrieved['content']);
            $this->assertEquals($status, $retrieved['status']);
            
            // Update blog post
            $newContent = $content . ' Updated';
            $updateResult = $this->contentService->update('blog_post', $created['id'], [
                'content' => $newContent
            ]);
            
            // Assert: Update succeeded
            $this->assertTrue($updateResult, 'Blog post update should succeed');
            
            // Assert: Changes persisted
            $updated = $this->contentService->read('blog_post', $created['id']);
            $this->assertEquals($newContent, $updated['content']);
            
            // Cleanup
            $this->contentService->delete('blog_post', $created['id']);
        });
    }

    /**
     * Property 27: Content CRUD Persistence - Case Study
     * 
     * For any content entity (case study), when an admin creates or updates the entity,
     * the changes should be persisted to the database and immediately reflected.
     * 
     * Validates: Requirements 8.1, 8.2
     * 
     * @test
     */
    public function caseStudyCreateAndUpdatePersistsToDatabase(): void
    {
        $this->forAll(
            Generator\string(),
            Generator\string(),
            Generator\elements(['DRAFT', 'PUBLISHED', 'ARCHIVED'])
        )
        ->when(function ($title, $clientName, $status) {
            return strlen($title) >= 1 && strlen($title) <= 255 && strlen($clientName) >= 1;
        })
        ->then(function ($title, $clientName, $status) {
            // Create case study
            $caseStudyData = [
                'title' => $title,
                'client_name' => $clientName,
                'industry' => 'Technology',
                'challenge' => 'Challenge description',
                'solution' => 'Solution description',
                'results' => 'Results description',
                'status' => $status,
                'modules_used' => ['module-1', 'module-2']
            ];
            
            $created = $this->contentService->create('case_study', $caseStudyData);
            
            // Assert: Creation succeeded
            $this->assertNotFalse($created, 'Case study creation should succeed');
            $this->assertIsArray($created);
            $this->assertArrayHasKey('id', $created);
            
            // Assert: Data persisted correctly
            $retrieved = $this->contentService->read('case_study', $created['id']);
            $this->assertNotFalse($retrieved, 'Case study should be retrievable after creation');
            $this->assertEquals($title, $retrieved['title']);
            $this->assertEquals($clientName, $retrieved['client_name']);
            $this->assertEquals($status, $retrieved['status']);
            
            // Update case study
            $newIndustry = 'Healthcare';
            $updateResult = $this->contentService->update('case_study', $created['id'], [
                'industry' => $newIndustry
            ]);
            
            // Assert: Update succeeded
            $this->assertTrue($updateResult, 'Case study update should succeed');
            
            // Assert: Changes persisted
            $updated = $this->contentService->read('case_study', $created['id']);
            $this->assertEquals($newIndustry, $updated['industry']);
            
            // Cleanup
            $this->contentService->delete('case_study', $created['id']);
        });
    }

    /**
     * Property: Slug generation creates unique slugs
     * 
     * @test
     */
    public function slugGenerationCreatesUniqueSlugs(): void
    {
        $this->forAll(
            Generator\string()
        )
        ->when(function ($name) {
            // Only test with names that will produce valid slugs
            // Must have at least one alphanumeric character
            return strlen($name) >= 1 
                && strlen($name) <= 255 
                && preg_match('/[a-zA-Z0-9]/', $name);
        })
        ->then(function ($name) {
            $moduleData = [
                'name' => $name,
                'description' => 'Test description',
                'status' => 'DRAFT'
            ];
            
            // Create first module (slug auto-generated)
            $module1 = $this->contentService->create('module', $moduleData);
            $this->assertNotFalse($module1);
            $this->assertNotEmpty($module1['slug']);
            
            // Create second module with same name (should get different slug)
            $module2 = $this->contentService->create('module', $moduleData);
            $this->assertNotFalse($module2);
            $this->assertNotEmpty($module2['slug']);
            
            // Assert: Slugs are different
            $this->assertNotEquals(
                $module1['slug'],
                $module2['slug'],
                'Duplicate names should generate unique slugs'
            );
            
            // Cleanup
            $this->contentService->delete('module', $module1['id']);
            $this->contentService->delete('module', $module2['id']);
        });
    }

    /**
     * Property: Status filtering returns only matching entities
     * 
     * @test
     */
    public function statusFilteringReturnsOnlyMatchingEntities(): void
    {
        // Create modules with different statuses
        $draftModule = $this->contentService->create('module', [
            'name' => 'Draft Module ' . bin2hex(random_bytes(4)),
            'status' => 'DRAFT'
        ]);
        
        $publishedModule = $this->contentService->create('module', [
            'name' => 'Published Module ' . bin2hex(random_bytes(4)),
            'status' => 'PUBLISHED'
        ]);
        
        $archivedModule = $this->contentService->create('module', [
            'name' => 'Archived Module ' . bin2hex(random_bytes(4)),
            'status' => 'ARCHIVED'
        ]);
        
        // Test filtering by DRAFT
        $draftModules = $this->contentService->findAll('module', ['status' => 'DRAFT']);
        $draftIds = array_column($draftModules, 'id');
        $this->assertContains($draftModule['id'], $draftIds);
        $this->assertNotContains($publishedModule['id'], $draftIds);
        $this->assertNotContains($archivedModule['id'], $draftIds);
        
        // Test filtering by PUBLISHED
        $publishedModules = $this->contentService->findAll('module', ['status' => 'PUBLISHED']);
        $publishedIds = array_column($publishedModules, 'id');
        $this->assertContains($publishedModule['id'], $publishedIds);
        $this->assertNotContains($draftModule['id'], $publishedIds);
        $this->assertNotContains($archivedModule['id'], $publishedIds);
        
        // Test filtering by ARCHIVED
        $archivedModules = $this->contentService->findAll('module', ['status' => 'ARCHIVED']);
        $archivedIds = array_column($archivedModules, 'id');
        $this->assertContains($archivedModule['id'], $archivedIds);
        $this->assertNotContains($draftModule['id'], $archivedIds);
        $this->assertNotContains($publishedModule['id'], $archivedIds);
        
        // Cleanup
        $this->contentService->delete('module', $draftModule['id']);
        $this->contentService->delete('module', $publishedModule['id']);
        $this->contentService->delete('module', $archivedModule['id']);
    }

    /**
     * Property: Invalid status values are rejected
     * 
     * @test
     */
    public function invalidStatusValuesAreRejected(): void
    {
        $invalidStatuses = ['INVALID', 'pending', 'active', '', 'null'];
        
        foreach ($invalidStatuses as $invalidStatus) {
            $result = $this->contentService->create('module', [
                'name' => 'Test Module',
                'status' => $invalidStatus
            ]);
            
            $this->assertFalse(
                $result,
                "Creation should fail with invalid status: $invalidStatus"
            );
        }
    }

    /**
     * Property: Duplicate slugs are prevented
     * 
     * @test
     */
    public function duplicateSlugsArePrevented(): void
    {
        $slug = 'test-slug-' . bin2hex(random_bytes(4));
        
        // Create first module with specific slug
        $module1 = $this->contentService->create('module', [
            'name' => 'Test Module 1',
            'slug' => $slug,
            'status' => 'DRAFT'
        ]);
        
        $this->assertNotFalse($module1);
        $this->assertEquals($slug, $module1['slug']);
        
        // Try to create second module with same slug
        $module2 = $this->contentService->create('module', [
            'name' => 'Test Module 2',
            'slug' => $slug,
            'status' => 'DRAFT'
        ]);
        
        // Assert: Second creation should fail
        $this->assertFalse(
            $module2,
            'Creation should fail when slug already exists'
        );
        
        // Cleanup
        $this->contentService->delete('module', $module1['id']);
    }

    /**
     * Property: Deleted content is not retrievable
     * 
     * @test
     */
    public function deletedContentIsNotRetrievable(): void
    {
        // Create module
        $module = $this->contentService->create('module', [
            'name' => 'Test Module ' . bin2hex(random_bytes(4)),
            'status' => 'DRAFT'
        ]);
        
        $this->assertNotFalse($module);
        $moduleId = $module['id'];
        
        // Verify it exists
        $retrieved = $this->contentService->read('module', $moduleId);
        $this->assertNotFalse($retrieved);
        
        // Delete it
        $deleteResult = $this->contentService->delete('module', $moduleId);
        $this->assertTrue($deleteResult);
        
        // Verify it no longer exists
        $afterDelete = $this->contentService->read('module', $moduleId);
        $this->assertFalse($afterDelete, 'Deleted content should not be retrievable');
    }

    /**
     * Helper: Create test author for blog posts
     */
    private function createTestAuthor(): string
    {
        $email = 'test_author_' . bin2hex(random_bytes(8)) . '@example.com';
        $userData = [
            'email' => $email,
            'password_hash' => password_hash('password123', PASSWORD_BCRYPT),
            'name' => 'Test Author',
            'role' => 'ADMIN'
        ];
        
        $user = $this->userModel->create($userData);
        return $user['id'];
    }
}

