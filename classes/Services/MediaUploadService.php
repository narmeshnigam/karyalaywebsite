<?php

namespace Karyalay\Services;

use Karyalay\Models\MediaAsset;

/**
 * Media Upload Service
 * Handles file uploads and media asset management
 */
class MediaUploadService
{
    private MediaAsset $mediaAssetModel;
    private string $uploadDir;
    private array $allowedMimeTypes;
    private int $maxFileSize;

    public function __construct()
    {
        $this->mediaAssetModel = new MediaAsset();
        $this->uploadDir = __DIR__ . '/../../uploads/media/';
        $this->allowedMimeTypes = [
            'image/jpeg',
            'image/jpg',
            'image/png',
            'image/gif',
            'image/webp',
            'application/pdf',
            'video/mp4',
            'video/webm'
        ];
        $this->maxFileSize = 10 * 1024 * 1024; // 10MB
    }

    /**
     * Upload a file and create media asset record
     * 
     * @param array $file File from $_FILES array
     * @param string $uploadedBy User ID who uploaded the file
     * @return array Result with success status and data or error message
     */
    public function uploadFile(array $file, string $uploadedBy): array
    {
        // Validate file upload
        if ($file['error'] !== UPLOAD_ERR_OK) {
            return [
                'success' => false,
                'error' => 'File upload error: ' . $this->getUploadErrorMessage($file['error'])
            ];
        }

        // Validate file size
        if ($file['size'] > $this->maxFileSize) {
            return [
                'success' => false,
                'error' => 'File size exceeds ' . $this->formatFileSize($this->maxFileSize) . ' limit'
            ];
        }

        // Validate file type
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $file['tmp_name']);

        if (!in_array($mimeType, $this->allowedMimeTypes)) {
            return [
                'success' => false,
                'error' => 'Invalid file type. Allowed: images (JPEG, PNG, GIF, WebP), videos (MP4, WebM), PDFs'
            ];
        }

        // Generate unique filename
        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $uniqueFilename = uniqid('media_', true) . '.' . $extension;

        // Create upload directory if it doesn't exist
        if (!is_dir($this->uploadDir)) {
            if (!mkdir($this->uploadDir, 0755, true)) {
                return [
                    'success' => false,
                    'error' => 'Failed to create upload directory'
                ];
            }
        }

        $uploadPath = $this->uploadDir . $uniqueFilename;

        // Move uploaded file
        if (!move_uploaded_file($file['tmp_name'], $uploadPath)) {
            return [
                'success' => false,
                'error' => 'Failed to move uploaded file'
            ];
        }

        // Generate URL
        $url = '/uploads/media/' . $uniqueFilename;

        // Save to database
        $mediaData = [
            'filename' => $file['name'],
            'url' => $url,
            'mime_type' => $mimeType,
            'size' => $file['size'],
            'uploaded_by' => $uploadedBy
        ];

        $result = $this->mediaAssetModel->create($mediaData);

        if (!$result) {
            // Clean up uploaded file if database insert fails
            unlink($uploadPath);
            return [
                'success' => false,
                'error' => 'Failed to save media asset to database'
            ];
        }

        return [
            'success' => true,
            'data' => $result
        ];
    }

    /**
     * Delete a media asset and its file
     * 
     * @param string $assetId Media asset ID
     * @return array Result with success status and error message if failed
     */
    public function deleteMediaAsset(string $assetId): array
    {
        // Get media asset
        $asset = $this->mediaAssetModel->findById($assetId);

        if (!$asset) {
            return [
                'success' => false,
                'error' => 'Media asset not found'
            ];
        }

        // Delete file from filesystem
        $filePath = __DIR__ . '/../../' . ltrim($asset['url'], '/');
        if (file_exists($filePath)) {
            if (!unlink($filePath)) {
                return [
                    'success' => false,
                    'error' => 'Failed to delete file from filesystem'
                ];
            }
        }

        // Delete from database
        if (!$this->mediaAssetModel->delete($assetId)) {
            return [
                'success' => false,
                'error' => 'Failed to delete media asset from database'
            ];
        }

        return [
            'success' => true
        ];
    }

    /**
     * Get upload error message
     * 
     * @param int $errorCode Upload error code
     * @return string Error message
     */
    private function getUploadErrorMessage(int $errorCode): string
    {
        $errors = [
            UPLOAD_ERR_INI_SIZE => 'File exceeds upload_max_filesize directive',
            UPLOAD_ERR_FORM_SIZE => 'File exceeds MAX_FILE_SIZE directive',
            UPLOAD_ERR_PARTIAL => 'File was only partially uploaded',
            UPLOAD_ERR_NO_FILE => 'No file was uploaded',
            UPLOAD_ERR_NO_TMP_DIR => 'Missing temporary folder',
            UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk',
            UPLOAD_ERR_EXTENSION => 'File upload stopped by extension'
        ];

        return $errors[$errorCode] ?? 'Unknown upload error';
    }

    /**
     * Format file size for display
     * 
     * @param int $bytes File size in bytes
     * @return string Formatted file size
     */
    private function formatFileSize(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= pow(1024, $pow);

        return round($bytes, 2) . ' ' . $units[$pow];
    }

    /**
     * Get allowed MIME types
     * 
     * @return array Allowed MIME types
     */
    public function getAllowedMimeTypes(): array
    {
        return $this->allowedMimeTypes;
    }

    /**
     * Get max file size
     * 
     * @return int Max file size in bytes
     */
    public function getMaxFileSize(): int
    {
        return $this->maxFileSize;
    }
}
