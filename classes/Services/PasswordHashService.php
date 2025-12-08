<?php

namespace Karyalay\Services;

/**
 * Password Hashing Service
 * 
 * Provides secure password hashing and verification using bcrypt
 */
class PasswordHashService
{
    private int $cost;

    /**
     * Constructor
     * 
     * @param int $cost Bcrypt cost factor (default: 12)
     */
    public function __construct(int $cost = 12)
    {
        $this->cost = $cost;
    }

    /**
     * Hash a password using bcrypt
     * 
     * @param string $password Plain text password
     * @return string|false Returns hashed password or false on failure
     */
    public function hash(string $password)
    {
        return password_hash($password, PASSWORD_BCRYPT, ['cost' => $this->cost]);
    }

    /**
     * Verify a password against a hash
     * 
     * @param string $password Plain text password
     * @param string $hash Hashed password
     * @return bool Returns true if password matches hash, false otherwise
     */
    public function verify(string $password, string $hash): bool
    {
        return password_verify($password, $hash);
    }

    /**
     * Check if a hash needs to be rehashed (e.g., if cost factor changed)
     * 
     * @param string $hash Hashed password
     * @return bool Returns true if hash needs rehashing, false otherwise
     */
    public function needsRehash(string $hash): bool
    {
        return password_needs_rehash($hash, PASSWORD_BCRYPT, ['cost' => $this->cost]);
    }

    /**
     * Get password hash information
     * 
     * @param string $hash Hashed password
     * @return array Returns array with algo, algoName, and options
     */
    public function getInfo(string $hash): array
    {
        return password_get_info($hash);
    }
}

