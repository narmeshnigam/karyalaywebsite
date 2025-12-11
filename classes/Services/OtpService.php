<?php

namespace Karyalay\Services;

use Karyalay\Database\Connection;
use PDO;

/**
 * OTP Service
 * 
 * Handles generation, storage, and verification of email OTP codes
 */
class OtpService
{
    private PDO $db;
    private const OTP_LENGTH = 6;
    private const OTP_EXPIRY_MINUTES = 10;
    private const MAX_ATTEMPTS = 5;
    private const RESEND_COOLDOWN_SECONDS = 60;

    public function __construct()
    {
        $this->db = Connection::getInstance();
    }

    /**
     * Generate and store a new OTP for an email
     * 
     * @param string $email Email address
     * @return array ['success' => bool, 'otp' => string|null, 'error' => string|null, 'cooldown' => int|null]
     */
    public function generateOtp(string $email): array
    {
        try {
            // Invalidate any existing OTPs for this email (no cooldown)
            $this->invalidateExistingOtps($email);

            // Generate new OTP
            $otp = $this->generateOtpCode();
            $id = $this->generateUuid();
            $expiresAt = date('Y-m-d H:i:s', strtotime('+' . self::OTP_EXPIRY_MINUTES . ' minutes'));

            $sql = "INSERT INTO email_otp (id, email, otp_code, expires_at) VALUES (:id, :email, :otp_code, :expires_at)";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                ':id' => $id,
                ':email' => $email,
                ':otp_code' => $otp,
                ':expires_at' => $expiresAt
            ]);

            return [
                'success' => true,
                'otp' => $otp,
                'expires_in' => self::OTP_EXPIRY_MINUTES * 60
            ];

        } catch (\Exception $e) {
            error_log('OTP generation failed: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => 'Failed to generate OTP'
            ];
        }
    }

    /**
     * Verify an OTP code
     * 
     * @param string $email Email address
     * @param string $otp OTP code to verify
     * @return array ['success' => bool, 'error' => string|null]
     */
    public function verifyOtp(string $email, string $otp): array
    {
        try {
            $sql = "SELECT * FROM email_otp WHERE email = :email AND verified = 0 ORDER BY created_at DESC LIMIT 1";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':email' => $email]);
            $record = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$record) {
                return [
                    'success' => false,
                    'error' => 'No OTP found. Please request a new code.'
                ];
            }

            // Check if expired
            if (strtotime($record['expires_at']) < time()) {
                return [
                    'success' => false,
                    'error' => 'OTP has expired. Please request a new code.'
                ];
            }

            // Check max attempts
            if ($record['attempts'] >= self::MAX_ATTEMPTS) {
                return [
                    'success' => false,
                    'error' => 'Too many attempts. Please request a new code.'
                ];
            }

            // Increment attempts
            $this->incrementAttempts($record['id']);

            // Verify OTP
            if ($record['otp_code'] !== $otp) {
                $remainingAttempts = self::MAX_ATTEMPTS - $record['attempts'] - 1;
                return [
                    'success' => false,
                    'error' => "Invalid OTP. {$remainingAttempts} attempts remaining."
                ];
            }

            // Mark as verified
            $this->markAsVerified($record['id']);

            return ['success' => true];

        } catch (\Exception $e) {
            error_log('OTP verification failed: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => 'Verification failed. Please try again.'
            ];
        }
    }

    /**
     * Check if email has a valid verified OTP
     * Verified OTPs are valid for 30 minutes after verification to allow form submission
     */
    public function isEmailVerified(string $email): bool
    {
        try {
            // Check for verified OTP within the last 30 minutes (gives time for form submission)
            $sql = "SELECT * FROM email_otp WHERE email = :email AND verified = 1 AND created_at > DATE_SUB(NOW(), INTERVAL 30 MINUTE) ORDER BY created_at DESC LIMIT 1";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':email' => $email]);
            return $stmt->fetch(PDO::FETCH_ASSOC) !== false;
        } catch (\Exception $e) {
            error_log('isEmailVerified error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Clean up verified OTP records after successful registration
     */
    public function cleanupVerifiedOtp(string $email): void
    {
        try {
            $sql = "DELETE FROM email_otp WHERE email = :email";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':email' => $email]);
        } catch (\Exception $e) {
            error_log('OTP cleanup failed: ' . $e->getMessage());
        }
    }

    private function getRecentOtp(string $email): ?array
    {
        $sql = "SELECT * FROM email_otp WHERE email = :email ORDER BY created_at DESC LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':email' => $email]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ?: null;
    }

    private function invalidateExistingOtps(string $email): void
    {
        // Only delete unverified OTPs, keep verified ones for the registration check
        $sql = "DELETE FROM email_otp WHERE email = :email AND verified = 0";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':email' => $email]);
    }

    private function incrementAttempts(string $id): void
    {
        $sql = "UPDATE email_otp SET attempts = attempts + 1 WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $id]);
    }

    private function markAsVerified(string $id): void
    {
        $sql = "UPDATE email_otp SET verified = 1 WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $id]);
    }

    private function generateOtpCode(): string
    {
        return str_pad((string)random_int(0, 999999), self::OTP_LENGTH, '0', STR_PAD_LEFT);
    }

    private function generateUuid(): string
    {
        $data = random_bytes(16);
        $data[6] = chr(ord($data[6]) & 0x0f | 0x40);
        $data[8] = chr(ord($data[8]) & 0x3f | 0x80);
        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }
}
