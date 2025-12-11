<?php

namespace Karyalay\Services\EmailServices;

/**
 * Generic Email Handler
 * 
 * Handles sending generic emails with custom subject and body.
 * Used for ad-hoc email sending needs.
 */
class GenericEmail extends AbstractEmailHandler
{
    /**
     * Send a generic email
     */
    public function sendEmail(string $to, string $subject, string $body, ?string $plainTextBody = null): bool
    {
        return $this->send($to, $subject, $body, $plainTextBody);
    }
}
