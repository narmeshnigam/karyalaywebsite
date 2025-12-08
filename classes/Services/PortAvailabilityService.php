<?php

namespace Karyalay\Services;

use Karyalay\Models\Port;

/**
 * Port Availability Service
 * 
 * Handles port availability checks for plans
 */
class PortAvailabilityService
{
    private Port $portModel;

    public function __construct()
    {
        $this->portModel = new Port();
    }

    /**
     * Check if ports are available for a plan
     * 
     * @param string $planId Plan ID
     * @return array Returns array with 'available' boolean and 'count' integer
     */
    public function checkAvailability(string $planId): array
    {
        try {
            $count = $this->portModel->countAvailableByPlanId($planId);
            
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
     * Check if at least one port is available for a plan
     * 
     * @param string $planId Plan ID
     * @return bool Returns true if at least one port is available
     */
    public function hasAvailablePorts(string $planId): bool
    {
        $result = $this->checkAvailability($planId);
        return $result['available'];
    }

    /**
     * Get count of available ports for a plan
     * 
     * @param string $planId Plan ID
     * @return int Returns count of available ports
     */
    public function getAvailablePortsCount(string $planId): int
    {
        $result = $this->checkAvailability($planId);
        return $result['count'];
    }

    /**
     * Get available ports for a plan
     * 
     * @param string $planId Plan ID
     * @param int $limit Optional limit (default 10)
     * @return array Returns array of available ports
     */
    public function getAvailablePorts(string $planId, int $limit = 10): array
    {
        try {
            return $this->portModel->findAvailableByPlanId($planId, $limit);
        } catch (\Exception $e) {
            error_log('Get available ports failed: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Validate checkout can proceed for a plan
     * 
     * @param string $planId Plan ID
     * @return array Returns array with 'can_proceed' boolean and optional 'message'
     */
    public function validateCheckout(string $planId): array
    {
        $availability = $this->checkAvailability($planId);
        
        if (!$availability['available']) {
            return [
                'can_proceed' => false,
                'message' => 'No available ports for this plan. Please contact support or try a different plan.'
            ];
        }
        
        return [
            'can_proceed' => true,
            'available_ports' => $availability['count']
        ];
    }
}
