<?php

namespace Karyalay\Services;

use Karyalay\Models\Port;

/**
 * Port Availability Service
 * 
 * Handles port availability checks (plan-agnostic)
 */
class PortAvailabilityService
{
    private Port $portModel;

    public function __construct()
    {
        $this->portModel = new Port();
    }

    /**
     * Check if ports are available (plan-agnostic)
     * 
     * @return array Returns array with 'available' boolean and 'count' integer
     */
    public function checkAvailability(): array
    {
        try {
            $count = $this->portModel->countAvailable();
            
            return [
                'available' => $count > 0,
                'count' => $count
            ];
        } catch (\Exception $e) {
            error_log('Port availability check failed: ' . $e->getMessage());
            return [
                'available' => false,
                'count' => 0,
                'error' => 'Failed to check port availability'
            ];
        }
    }

    /**
     * Check if at least one port is available (plan-agnostic)
     * 
     * @return bool Returns true if at least one port is available
     */
    public function hasAvailablePorts(): bool
    {
        $result = $this->checkAvailability();
        return $result['available'];
    }

    /**
     * Get count of available ports (plan-agnostic)
     * 
     * @return int Returns count of available ports
     */
    public function getAvailablePortsCount(): int
    {
        $result = $this->checkAvailability();
        return $result['count'];
    }

    /**
     * Get available ports (plan-agnostic)
     * 
     * @param int $limit Optional limit (default 10)
     * @return array Returns array of available ports
     */
    public function getAvailablePorts(int $limit = 10): array
    {
        try {
            return $this->portModel->findAvailable($limit);
        } catch (\Exception $e) {
            error_log('Get available ports failed: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Validate checkout can proceed (plan-agnostic)
     * 
     * @return array Returns array with 'can_proceed' boolean and optional 'message'
     */
    public function validateCheckout(): array
    {
        $availability = $this->checkAvailability();
        
        if (!$availability['available']) {
            return [
                'can_proceed' => false,
                'message' => 'No available ports. Please contact support.'
            ];
        }
        
        return [
            'can_proceed' => true,
            'available_ports' => $availability['count']
        ];
    }
}
