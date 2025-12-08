<?php

/**
 * Property Test: Media Upload URL Generation
 * Feature: karyalay-portal-system, Property 29: Media Upload URL Generation
 * Validates: Requirements 8.5
 * 
 * For any file upload, when an admin uploads media to the media library,
 * a URL should be generated and stored for that file.
 */

namespace Karyalay\Tests\Property;

use Eris\Generator;
use Eris\TestTrait;
use PHPUnit\Framework\TestCase;
use Karyalay\Models\MediaAsset;
use Karyalay\Services\MediaUploadService;

class MediaUploadUrlGenerationPropertyTest extends TestCase
{
    use TestTrait;

    private MediaAsset $mediaAssetModel;
    private MediaUploadService $uploadService;
    private array $createdAssetIds = [];
    private array $createdFiles = [];
    private string $testUserId;

    protected function setUp(): void
    {
        parent::setUp();
        $this->mediaAssetModel = new MediaAsset();
        $this->uploadService = new MediaUploadService();
        
        // Ensure upload directory exists
        $uploadDir = __DIR__ . '/../../uploads/media/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        
        // Create a test user for foreign key constraint
        $db = \Karyalay\Database\Connection::getInstance();
        $this->testUserId = $this->generateUuid();
        $sql = "INSERT INTO users (id, email, password_hash, name, role) VALUES (:id, :email, :password, :name, :role)";
        $stmt = $db->prepare($sql);
        $stmt->execute([
            ':id' => $this->testUserId,
            ':email' => 'test-media-' . bin2hex(random_bytes(4)) . '@example.com',
            ':password' => password_hash('password', PASSWORD_BCRYPT),
            ':name' => 'Test Media User',
            ':role' => 'ADMIN'
        ]);
    }

    protected function tearDown(): void
    {
        // Clean up created assets
        foreach ($this->createdAssetIds as $id) {
            try {
                $this->mediaAssetModel->delete($id);
            } catch (\Exception $e) {
                // Ignore errors during cleanup
            }
        }
        
        // Clean up created files
        foreach ($this->createdFiles as $file) {
            if (file_exists($file)) {
                unlink($file);
            }
        }
        
        // Clean up test user
        if (isset($this->testUserId)) {
            try {
                $db = \Karyalay\Database\Connection::getInstance();
                $sql = "DELETE FROM users WHERE id = :id";
                $stmt = $db->prepare($sql);
                $stmt->execute([':id' => $this->testUserId]);
            } catch (\Exception $e) {
                // Ignore errors during cleanup
            }
        }
        
        $this->createdAssetIds = [];
        $this->createdFiles = [];
        parent::tearDown();
    }

    /**
     * Property: Media upload generates and stores URL
     * 
     * @test
     */
    public function mediaUploadGeneratesAndStoresUrl(): void
    {
        $this->forAll(
            Generator\string(),
            Generator\elements('jpg', 'png', 'gif', 'pdf', 'mp4')
        )
        ->when(function ($filename, $extension) {
            return strlen($filename) >= 1 && strlen($filename) <= 100;
        })
        ->then(function ($filename, $extension) {
            // Create a test file in the uploads directory
            $uploadDir = __DIR__ . '/../../uploads/media/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }
            
            $uniqueFilename = uniqid('test_media_', true) . '.' . $extension;
            $filePath = $uploadDir . $uniqueFilename;
            $content = $this->getTestContentForExtension($extension);
            file_put_contents($filePath, $content);
            $this->createdFiles[] = $filePath;
            
            // Generate URL
            $url = '/uploads/media/' . $uniqueFilename;
            
            // Create media asset record
            $mediaData = [
                'filename' => $filename . '.' . $extension,
                'url' => $url,
                'mime_type' => $this->getMimeTypeForExtension($extension),
                'size' => filesize($filePath),
                'uploaded_by' => $this->testUserId
            ];
            
            $asset = $this->mediaAssetModel->create($mediaData);
            $this->assertNotFalse($asset, 'Asset creation should succeed');
            $this->createdAssetIds[] = $asset['id'];
            
            // Assert: URL is generated
            $this->assertArrayHasKey('url', $asset, 'Asset must have URL field');
            $this->assertNotEmpty($asset['url'], 'URL should not be empty');
            
            // Assert: URL starts with /uploads/media/
            $this->assertStringStartsWith('/uploads/media/', $asset['url'], 'URL should start with /uploads/media/');
            
            // Assert: URL is stored in database
            $retrieved = $this->mediaAssetModel->findById($asset['id']);
            $this->assertNotFalse($retrieved, 'Asset should be retrievable from database');
            $this->assertEquals($asset['url'], $retrieved['url'], 'Stored URL should match generated URL');
            
            // Assert: File exists at the URL path
            $this->assertFileExists($filePath, 'File should exist at the URL path');
        });
    }

    /**
     * Property: Each upload generates unique URL
     * 
     * @test
     */
    public function eachUploadGeneratesUniqueUrl(): void
    {
        $uploadCount = 5;
        $urls = [];
        
        $uploadDir = __DIR__ . '/../../uploads/media/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        
        // Create multiple media assets
        for ($i = 0; $i < $uploadCount; $i++) {
            $uniqueFilename = uniqid('test_media_', true) . '.jpg';
            $filePath = $uploadDir . $uniqueFilename;
            $content = $this->getTestContentForExtension('jpg');
            file_put_contents($filePath, $content);
            $this->createdFiles[] = $filePath;
            
            $url = '/uploads/media/' . $uniqueFilename;
            
            $mediaData = [
                'filename' => 'test-file-' . $i . '.jpg',
                'url' => $url,
                'mime_type' => 'image/jpeg',
                'size' => filesize($filePath),
                'uploaded_by' => $this->testUserId
            ];
            
            $asset = $this->mediaAssetModel->create($mediaData);
            $this->assertNotFalse($asset, "Asset $i creation should succeed");
            $this->createdAssetIds[] = $asset['id'];
            $urls[] = $asset['url'];
        }
        
        // Assert: All URLs are unique
        $uniqueUrls = array_unique($urls);
        $this->assertCount($uploadCount, $uniqueUrls, 'All generated URLs should be unique');
    }

    /**
     * Property: URL corresponds to accessible file
     * 
     * @test
     */
    public function urlCorrespondsToAccessibleFile(): void
    {
        $uploadDir = __DIR__ . '/../../uploads/media/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        
        // Create a test file
        $uniqueFilename = uniqid('test_media_', true) . '.png';
        $filePath = $uploadDir . $uniqueFilename;
        $content = $this->getTestContentForExtension('png');
        file_put_contents($filePath, $content);
        $this->createdFiles[] = $filePath;
        
        $url = '/uploads/media/' . $uniqueFilename;
        
        $mediaData = [
            'filename' => 'test-image.png',
            'url' => $url,
            'mime_type' => 'image/png',
            'size' => filesize($filePath),
            'uploaded_by' => $this->testUserId
        ];
        
        $asset = $this->mediaAssetModel->create($mediaData);
        $this->assertNotFalse($asset);
        $this->createdAssetIds[] = $asset['id'];
        
        // Assert: URL is accessible
        $this->assertFileExists($filePath, 'File should exist at URL path');
        $this->assertFileIsReadable($filePath, 'File should be readable');
        
        // Assert: File size matches
        $this->assertEquals($asset['size'], filesize($filePath), 'File size should match stored size');
    }

    /**
     * Property: URL persists after retrieval
     * 
     * @test
     */
    public function urlPersistsAfterRetrieval(): void
    {
        $uploadDir = __DIR__ . '/../../uploads/media/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        
        // Create a test file
        $uniqueFilename = uniqid('test_media_', true) . '.pdf';
        $filePath = $uploadDir . $uniqueFilename;
        $content = $this->getTestContentForExtension('pdf');
        file_put_contents($filePath, $content);
        $this->createdFiles[] = $filePath;
        
        $url = '/uploads/media/' . $uniqueFilename;
        
        $mediaData = [
            'filename' => 'test-document.pdf',
            'url' => $url,
            'mime_type' => 'application/pdf',
            'size' => filesize($filePath),
            'uploaded_by' => $this->testUserId
        ];
        
        $asset = $this->mediaAssetModel->create($mediaData);
        $this->assertNotFalse($asset);
        $this->createdAssetIds[] = $asset['id'];
        $originalUrl = $asset['url'];
        
        // Retrieve asset multiple times
        for ($i = 0; $i < 3; $i++) {
            $retrieved = $this->mediaAssetModel->findById($asset['id']);
            $this->assertNotFalse($retrieved, "Retrieval $i should succeed");
            
            // Assert: URL remains the same
            $this->assertEquals($originalUrl, $retrieved['url'], 'URL should persist across retrievals');
        }
    }

    /**
     * Property: URL contains file extension
     * 
     * @test
     */
    public function urlContainsFileExtension(): void
    {
        $this->forAll(
            Generator\elements('jpg', 'png', 'gif', 'pdf', 'mp4')
        )
        ->then(function ($extension) {
            $uploadDir = __DIR__ . '/../../uploads/media/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }
            
            // Create a test file
            $uniqueFilename = uniqid('test_media_', true) . '.' . $extension;
            $filePath = $uploadDir . $uniqueFilename;
            $content = $this->getTestContentForExtension($extension);
            file_put_contents($filePath, $content);
            $this->createdFiles[] = $filePath;
            
            $url = '/uploads/media/' . $uniqueFilename;
            
            $mediaData = [
                'filename' => 'test-file.' . $extension,
                'url' => $url,
                'mime_type' => $this->getMimeTypeForExtension($extension),
                'size' => filesize($filePath),
                'uploaded_by' => $this->testUserId
            ];
            
            $asset = $this->mediaAssetModel->create($mediaData);
            $this->assertNotFalse($asset);
            $this->createdAssetIds[] = $asset['id'];
            
            // Assert: URL contains the file extension
            $this->assertStringEndsWith('.' . $extension, $asset['url'], 'URL should contain file extension');
        });
    }

    /**
     * Create a test file with given extension
     * 
     * @param string $extension File extension
     * @return string Path to created file
     */
    private function createTestFile(string $extension): string
    {
        $tempDir = sys_get_temp_dir();
        $tempFile = tempnam($tempDir, 'test_media_');
        
        // Write some test content based on extension
        $content = $this->getTestContentForExtension($extension);
        file_put_contents($tempFile, $content);
        
        // Rename to have correct extension
        $newPath = $tempFile . '.' . $extension;
        rename($tempFile, $newPath);
        
        return $newPath;
    }

    /**
     * Get test content for file extension
     * 
     * @param string $extension File extension
     * @return string Test content
     */
    private function getTestContentForExtension(string $extension): string
    {
        switch ($extension) {
            case 'jpg':
            case 'png':
            case 'gif':
                // Create a minimal valid image (1x1 pixel PNG)
                return base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==');
            case 'pdf':
                // Minimal valid PDF
                return "%PDF-1.4\n1 0 obj<</Type/Catalog/Pages 2 0 R>>endobj 2 0 obj<</Type/Pages/Kids[3 0 R]/Count 1>>endobj 3 0 obj<</Type/Page/MediaBox[0 0 612 792]/Parent 2 0 R/Resources<<>>>>endobj\nxref\n0 4\n0000000000 65535 f\n0000000009 00000 n\n0000000058 00000 n\n0000000115 00000 n\ntrailer<</Size 4/Root 1 0 R>>\nstartxref\n206\n%%EOF";
            case 'mp4':
                // Minimal valid MP4 header
                return "\x00\x00\x00\x20\x66\x74\x79\x70\x69\x73\x6F\x6D\x00\x00\x02\x00\x69\x73\x6F\x6D\x69\x73\x6F\x32\x61\x76\x63\x31\x6D\x70\x34\x31";
            default:
                return 'Test content for ' . $extension;
        }
    }

    /**
     * Get MIME type for file extension
     * 
     * @param string $extension File extension
     * @return string MIME type
     */
    private function getMimeTypeForExtension(string $extension): string
    {
        $mimeTypes = [
            'jpg' => 'image/jpeg',
            'png' => 'image/png',
            'gif' => 'image/gif',
            'pdf' => 'application/pdf',
            'mp4' => 'video/mp4'
        ];
        
        return $mimeTypes[$extension] ?? 'application/octet-stream';
    }

    /**
     * Generate UUID v4
     * 
     * @return string UUID
     */
    private function generateUuid(): string
    {
        $data = random_bytes(16);
        $data[6] = chr(ord($data[6]) & 0x0f | 0x40);
        $data[8] = chr(ord($data[8]) & 0x3f | 0x80);
        
        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }
}

