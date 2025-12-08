<?php

/**
 * Property Test: Published Content Visibility
 * Feature: karyalay-portal-system, Property 28: Published Content Visibility
 * Validates: Requirements 8.4
 * 
 * For any blog post, when the status is set to PUBLISHED, the post should appear
 * on the public blog index page.
 */

namespace Karyalay\Tests\Property;

use Eris\Generator;
use Eris\TestTrait;
use PHPUnit\Framework\TestCase;
use Karyalay\Models\BlogPost;
use Karyalay\Models\User;

class PublishedContentVisibilityPropertyTest extends TestCase
{
    use TestTrait;

    private BlogPost $blogPostModel;
    private User $userModel;
    private array $createdIds = [];
    private ?string $testAuthorId = null;

    protected function setUp(): void
    {
        parent::setUp();
        $this->blogPostModel = new BlogPost();
        $this->userModel = new User();
        
        // Create a test author for blog posts
        $this->testAuthorId = $this->createTestAuthor();
    }

    protected function tearDown(): void
    {
        // Clean up created blog posts
        foreach ($this->createdIds as $id) {
            try {
                $this->blogPostModel->delete($id);
            } catch (\Exception $e) {
                // Ignore errors during cleanup
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
     * Property: Published blog posts appear in public index
     * 
     * @test
     */
    public function publishedBlogPostsAppearInPublicIndex(): void
    {
        $this->forAll(
            Generator\string()
        )
        ->when(function ($title) {
            return strlen($title) >= 1 && strlen($title) <= 255;
        })
        ->then(function ($title) {
            // Create a blog post with PUBLISHED status
            $blogPostData = [
                'title' => $title,
                'slug' => 'test-blog-' . bin2hex(random_bytes(8)),
                'content' => 'Test content for ' . $title,
                'excerpt' => 'Test excerpt',
                'author_id' => $this->testAuthorId,
                'tags' => ['test'],
                'status' => 'PUBLISHED'
            ];
            
            $created = $this->blogPostModel->create($blogPostData);
            $this->assertNotFalse($created, 'Blog post creation should succeed');
            $this->createdIds[] = $created['id'];
            
            // Act: Retrieve all published blog posts (simulating public index page)
            $publishedPosts = $this->blogPostModel->findAll(['status' => 'PUBLISHED']);
            
            // Assert: The created post should appear in the published posts
            $found = false;
            foreach ($publishedPosts as $post) {
                if ($post['id'] === $created['id']) {
                    $found = true;
                    $this->assertEquals('PUBLISHED', $post['status']);
                    $this->assertEquals($title, $post['title']);
                    break;
                }
            }
            
            $this->assertTrue($found, 
                "Published blog post '{$title}' should appear in public index");
            
            // Clean up
            $this->blogPostModel->delete($created['id']);
            $this->createdIds = array_diff($this->createdIds, [$created['id']]);
        });
    }

    /**
     * Property: Draft blog posts do NOT appear in public index
     * 
     * @test
     */
    public function draftBlogPostsDoNotAppearInPublicIndex(): void
    {
        $this->forAll(
            Generator\string()
        )
        ->when(function ($title) {
            return strlen($title) >= 1 && strlen($title) <= 255;
        })
        ->then(function ($title) {
            // Create a blog post with DRAFT status
            $blogPostData = [
                'title' => $title,
                'slug' => 'test-blog-' . bin2hex(random_bytes(8)),
                'content' => 'Test content for ' . $title,
                'excerpt' => 'Test excerpt',
                'author_id' => $this->testAuthorId,
                'tags' => ['test'],
                'status' => 'DRAFT'
            ];
            
            $created = $this->blogPostModel->create($blogPostData);
            $this->assertNotFalse($created, 'Blog post creation should succeed');
            $this->createdIds[] = $created['id'];
            
            // Act: Retrieve all published blog posts (simulating public index page)
            $publishedPosts = $this->blogPostModel->findAll(['status' => 'PUBLISHED']);
            
            // Assert: The draft post should NOT appear in the published posts
            $found = false;
            foreach ($publishedPosts as $post) {
                if ($post['id'] === $created['id']) {
                    $found = true;
                    break;
                }
            }
            
            $this->assertFalse($found, 
                "Draft blog post '{$title}' should NOT appear in public index");
            
            // Clean up
            $this->blogPostModel->delete($created['id']);
            $this->createdIds = array_diff($this->createdIds, [$created['id']]);
        });
    }

    /**
     * Property: Status change from DRAFT to PUBLISHED makes post visible
     * 
     * @test
     */
    public function statusChangeFromDraftToPublishedMakesPostVisible(): void
    {
        $this->forAll(
            Generator\string()
        )
        ->when(function ($title) {
            return strlen($title) >= 1 && strlen($title) <= 255;
        })
        ->then(function ($title) {
            // Create a blog post with DRAFT status
            $blogPostData = [
                'title' => $title,
                'slug' => 'test-blog-' . bin2hex(random_bytes(8)),
                'content' => 'Test content',
                'excerpt' => 'Test excerpt',
                'author_id' => $this->testAuthorId,
                'tags' => ['test'],
                'status' => 'DRAFT'
            ];
            
            $created = $this->blogPostModel->create($blogPostData);
            $this->assertNotFalse($created);
            $this->createdIds[] = $created['id'];
            
            // Verify it's not in published list
            $publishedBefore = $this->blogPostModel->findAll(['status' => 'PUBLISHED']);
            $foundBefore = false;
            foreach ($publishedBefore as $post) {
                if ($post['id'] === $created['id']) {
                    $foundBefore = true;
                    break;
                }
            }
            $this->assertFalse($foundBefore, 'Draft post should not be visible initially');
            
            // Update status to PUBLISHED
            $updateResult = $this->blogPostModel->update($created['id'], [
                'status' => 'PUBLISHED'
            ]);
            $this->assertTrue($updateResult, 'Status update should succeed');
            
            // Verify it now appears in published list
            $publishedAfter = $this->blogPostModel->findAll(['status' => 'PUBLISHED']);
            $foundAfter = false;
            foreach ($publishedAfter as $post) {
                if ($post['id'] === $created['id']) {
                    $foundAfter = true;
                    $this->assertEquals('PUBLISHED', $post['status']);
                    break;
                }
            }
            $this->assertTrue($foundAfter, 
                'Post should appear in public index after status change to PUBLISHED');
            
            // Clean up
            $this->blogPostModel->delete($created['id']);
            $this->createdIds = array_diff($this->createdIds, [$created['id']]);
        });
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
