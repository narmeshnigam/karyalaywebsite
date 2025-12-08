<?php

/**
 * Property Test: Content Detail Rendering Completeness
 * Feature: karyalay-portal-system, Property 2: Content Detail Rendering Completeness
 * Validates: Requirements 1.4, 5.2, 5.4
 * 
 * For any content entity (module, feature, blog post, case study) with a slug,
 * when the detail page is accessed, all required fields for that entity type
 * should be present in the rendered output.
 */

namespace Karyalay\Tests\Property;

use Eris\Generator;
use Eris\TestTrait;
use PHPUnit\Framework\TestCase;
use Karyalay\Models\Module;
use Karyalay\Models\Feature;
use Karyalay\Models\BlogPost;
use Karyalay\Models\CaseStudy;
use Karyalay\Models\User;

class ContentDetailRenderingPropertyTest extends TestCase
{
    use TestTrait;

    private Module $moduleModel;
    private Feature $featureModel;
    private BlogPost $blogPostModel;
    private CaseStudy $caseStudyModel;
    private User $userModel;
    private array $createdIds = [];
    private ?string $testAuthorId = null;

    protected function setUp(): void
    {
        parent::setUp();
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
        // Clean up created content
        foreach ($this->createdIds as $type => $ids) {
            foreach ($ids as $id) {
                try {
                    switch ($type) {
                        case 'module':
                            $this->moduleModel->delete($id);
                            break;
                        case 'feature':
                            $this->featureModel->delete($id);
                            break;
                        case 'blog_post':
                            $this->blogPostModel->delete($id);
                            break;
                        case 'case_study':
                            $this->caseStudyModel->delete($id);
                            break;
                    }
                } catch (\Exception $e) {
                    // Ignore errors during cleanup
                }
            }
        }
        
        // Clean up test author
        if ($this->testAuthorId) {
            try {
                $this->userModel->delete($this->testAuthorId);
            } catch (\Exception $e) {
                // Ignore errors during cleanup
            }
        }
        
        parent::tearDown();
    }

    /**
     * Property: Module detail page contains all required fields
     * 
     * @test
     */
    public function moduleDetailPageContainsAllRequiredFields(): void
    {
        $this->forAll(
            Generator\string()
        )
        ->when(function ($name) {
            return strlen($name) >= 1 && strlen($name) <= 255;
        })
        ->then(function ($name) {
            // Create a module with all fields
            $moduleData = $this->generateModuleData($name);
            $created = $this->moduleModel->create($moduleData);
            $this->assertNotFalse($created, 'Module creation should succeed');
            $this->createdIds['module'][] = $created['id'];
            
            // Retrieve the module by slug (simulating detail page access)
            $retrieved = $this->moduleModel->findBySlug($created['slug']);
            $this->assertNotFalse($retrieved, 'Module should be retrievable by slug');
            
            // Assert: All required fields are present
            $this->assertArrayHasKey('name', $retrieved, 'Module must have name field');
            $this->assertArrayHasKey('description', $retrieved, 'Module must have description field');
            $this->assertArrayHasKey('slug', $retrieved, 'Module must have slug field');
            $this->assertArrayHasKey('features', $retrieved, 'Module must have features field');
            $this->assertArrayHasKey('screenshots', $retrieved, 'Module must have screenshots field');
            $this->assertArrayHasKey('faqs', $retrieved, 'Module must have faqs field');
            
            // Assert: Field values match what was created
            $this->assertEquals($moduleData['name'], $retrieved['name']);
            $this->assertEquals($moduleData['description'], $retrieved['description']);
            $this->assertEquals($moduleData['slug'], $retrieved['slug']);
            
            // Clean up for next iteration
            $this->moduleModel->delete($created['id']);
            $this->createdIds['module'] = array_diff($this->createdIds['module'], [$created['id']]);
        });
    }

    /**
     * Property: Feature detail page contains all required fields
     * 
     * @test
     */
    public function featureDetailPageContainsAllRequiredFields(): void
    {
        $this->forAll(
            Generator\string()
        )
        ->when(function ($name) {
            return strlen($name) >= 1 && strlen($name) <= 255;
        })
        ->then(function ($name) {
            // Create a feature with all fields
            $featureData = $this->generateFeatureData($name);
            $created = $this->featureModel->create($featureData);
            $this->assertNotFalse($created, 'Feature creation should succeed');
            $this->createdIds['feature'][] = $created['id'];
            
            // Retrieve the feature by slug
            $retrieved = $this->featureModel->findBySlug($created['slug']);
            $this->assertNotFalse($retrieved, 'Feature should be retrievable by slug');
            
            // Assert: All required fields are present
            $this->assertArrayHasKey('name', $retrieved, 'Feature must have name field');
            $this->assertArrayHasKey('description', $retrieved, 'Feature must have description field');
            $this->assertArrayHasKey('slug', $retrieved, 'Feature must have slug field');
            $this->assertArrayHasKey('benefits', $retrieved, 'Feature must have benefits field');
            $this->assertArrayHasKey('related_modules', $retrieved, 'Feature must have related_modules field');
            $this->assertArrayHasKey('screenshots', $retrieved, 'Feature must have screenshots field');
            
            // Assert: Field values match
            $this->assertEquals($featureData['name'], $retrieved['name']);
            $this->assertEquals($featureData['description'], $retrieved['description']);
            
            // Clean up
            $this->featureModel->delete($created['id']);
            $this->createdIds['feature'] = array_diff($this->createdIds['feature'], [$created['id']]);
        });
    }

    /**
     * Property: Blog post detail page contains all required fields
     * 
     * @test
     */
    public function blogPostDetailPageContainsAllRequiredFields(): void
    {
        $this->forAll(
            Generator\string()
        )
        ->when(function ($title) {
            return strlen($title) >= 1 && strlen($title) <= 255;
        })
        ->then(function ($title) {
            // Create a blog post with all fields
            $blogPostData = $this->generateBlogPostData($title);
            $created = $this->blogPostModel->create($blogPostData);
            $this->assertNotFalse($created, 'Blog post creation should succeed');
            $this->createdIds['blog_post'][] = $created['id'];
            
            // Retrieve the blog post by slug
            $retrieved = $this->blogPostModel->findBySlug($created['slug']);
            $this->assertNotFalse($retrieved, 'Blog post should be retrievable by slug');
            
            // Assert: All required fields are present
            $this->assertArrayHasKey('title', $retrieved, 'Blog post must have title field');
            $this->assertArrayHasKey('content', $retrieved, 'Blog post must have content field');
            $this->assertArrayHasKey('slug', $retrieved, 'Blog post must have slug field');
            $this->assertArrayHasKey('excerpt', $retrieved, 'Blog post must have excerpt field');
            $this->assertArrayHasKey('author_id', $retrieved, 'Blog post must have author_id field');
            $this->assertArrayHasKey('tags', $retrieved, 'Blog post must have tags field');
            
            // Assert: Field values match
            $this->assertEquals($blogPostData['title'], $retrieved['title']);
            $this->assertEquals($blogPostData['content'], $retrieved['content']);
            
            // Clean up
            $this->blogPostModel->delete($created['id']);
            $this->createdIds['blog_post'] = array_diff($this->createdIds['blog_post'], [$created['id']]);
        });
    }

    /**
     * Property: Case study detail page contains all required fields
     * 
     * @test
     */
    public function caseStudyDetailPageContainsAllRequiredFields(): void
    {
        $this->forAll(
            Generator\string()
        )
        ->when(function ($title) {
            return strlen($title) >= 1 && strlen($title) <= 255;
        })
        ->then(function ($title) {
            // Create a case study with all fields
            $caseStudyData = $this->generateCaseStudyData($title);
            $created = $this->caseStudyModel->create($caseStudyData);
            $this->assertNotFalse($created, 'Case study creation should succeed');
            $this->createdIds['case_study'][] = $created['id'];
            
            // Retrieve the case study by slug
            $retrieved = $this->caseStudyModel->findBySlug($created['slug']);
            $this->assertNotFalse($retrieved, 'Case study should be retrievable by slug');
            
            // Assert: All required fields are present
            $this->assertArrayHasKey('title', $retrieved, 'Case study must have title field');
            $this->assertArrayHasKey('client_name', $retrieved, 'Case study must have client_name field');
            $this->assertArrayHasKey('slug', $retrieved, 'Case study must have slug field');
            $this->assertArrayHasKey('industry', $retrieved, 'Case study must have industry field');
            $this->assertArrayHasKey('challenge', $retrieved, 'Case study must have challenge field');
            $this->assertArrayHasKey('solution', $retrieved, 'Case study must have solution field');
            $this->assertArrayHasKey('results', $retrieved, 'Case study must have results field');
            $this->assertArrayHasKey('modules_used', $retrieved, 'Case study must have modules_used field');
            
            // Assert: Field values match
            $this->assertEquals($caseStudyData['title'], $retrieved['title']);
            $this->assertEquals($caseStudyData['client_name'], $retrieved['client_name']);
            
            // Clean up
            $this->caseStudyModel->delete($created['id']);
            $this->createdIds['case_study'] = array_diff($this->createdIds['case_study'], [$created['id']]);
        });
    }

    /**
     * Helper: Generate module data
     */
    private function generateModuleData(string $name): array
    {
        $randomId = bin2hex(random_bytes(8));
        $slug = "test-module-" . $randomId;
        
        return [
            'name' => $name,
            'slug' => $slug,
            'description' => "Description for " . $name,
            'features' => ['Feature 1', 'Feature 2', 'Feature 3'],
            'screenshots' => ['/images/screenshot1.png', '/images/screenshot2.png'],
            'faqs' => [
                ['question' => 'Question 1', 'answer' => 'Answer 1'],
                ['question' => 'Question 2', 'answer' => 'Answer 2']
            ],
            'display_order' => 1,
            'status' => 'PUBLISHED'
        ];
    }

    /**
     * Helper: Generate feature data
     */
    private function generateFeatureData(string $name): array
    {
        $randomId = bin2hex(random_bytes(8));
        $slug = "test-feature-" . $randomId;
        
        return [
            'name' => $name,
            'slug' => $slug,
            'description' => "Description for " . $name,
            'benefits' => ['Benefit 1', 'Benefit 2'],
            'related_modules' => ['module-1'],
            'screenshots' => ['/images/screenshot.png'],
            'display_order' => 1,
            'status' => 'PUBLISHED'
        ];
    }

    /**
     * Helper: Generate blog post data
     */
    private function generateBlogPostData(string $title): array
    {
        $randomId = bin2hex(random_bytes(8));
        $slug = "test-blog-" . $randomId;
        $content = "Content for " . $title;
        
        return [
            'title' => $title,
            'slug' => $slug,
            'content' => $content,
            'excerpt' => substr($content, 0, 100),
            'author_id' => $this->testAuthorId,
            'tags' => ['tag1', 'tag2'],
            'status' => 'PUBLISHED'
        ];
    }

    /**
     * Helper: Generate case study data
     */
    private function generateCaseStudyData(string $title): array
    {
        $randomId = bin2hex(random_bytes(8));
        $slug = "test-case-study-" . $randomId;
        
        return [
            'title' => $title,
            'slug' => $slug,
            'client_name' => 'Test Client',
            'industry' => 'Technology',
            'challenge' => 'Challenge description',
            'solution' => 'Solution description',
            'results' => 'Results description',
            'modules_used' => ['module-1', 'module-2'],
            'status' => 'PUBLISHED'
        ];
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
