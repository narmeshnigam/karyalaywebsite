<?php

namespace Karyalay\Services;

use Karyalay\Models\Port;
use Karyalay\Models\PortAllocationLog;
use Karyalay\Models\Subscription;
use Karyalay\Database\Connection;
use PDO;

/**
 * Port Service
 * 
 * Handles port management operations including CRUD and status management
 */
class PortService
{
    private Port $portModel;
    private PortAllocationLog $logModel;
    private PDO $db;

    public function __construct()
    {
        $this->portModel = new Port();
        $this->logModel = new PortAllocationLog();
        $this->db = Connection::getInstance();
    }

    /**
     * Create a new port
     * 
     * @param array $data Port data
     * @return array Returns array with 'success' boolean and 'port' or 'error'
     */
    public function createPort(array $data): array
    {
        try {
            // Validate required fields
            if (empty($data['instance_url'])) {
                return [
                    'success' => false,
                    'error' => 'Missing required field: instance_url is required'
                ];
            }

            // Check for duplicate port
            if ($this->portModel->portExists($data['instance_url'])) {
                return [
                    'success' => false,
                    'error' => 'Port with this instance URL already exists',
                    'error_code' => 'DUPLICATE_INSTANCE'
                ];
            }

            // Set default status if not provided
            if (!isset($data['status'])) {
                $data['status'] = 'AVAILABLE';
            }

            // Validate status
            $validStatuses = ['AVAILABLE', 'RESERVED', 'ASSIGNED', 'DISABLED'];
            if (!in_array($data['status'], $validStatuses)) {
                return [
                    'success' => false,
                    'error' => 'Invalid status. Must be one of: ' . implode(', ', $validStatuses)
                ];
            }

            error_log('PortService::createPort - Calling portModel->create with data: ' . json_encode($data));
            $port = $this->portModel->create($data);
            error_log('PortService::createPort - portModel->create returned: ' . ($port ? json_encode($port) : 'false'));

            if (!$port) {
                error_log('PortService::createPort - Port creation failed, portModel->create returned false');
                return [
                    'success' => false,
                    'error' => 'Failed to create port - database operation failed. Check error logs for details.',
                    'error_code' => 'DB_CREATE_FAILED'
                ];
            }

            // Log port creation
            $this->logModel->logCreation($port['id'], $data['created_by'] ?? null);

            return [
                'success' => true,
                'port' => $port
            ];
        } catch (\Exception $e) {
            error_log('PortService::createPort failed: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => 'An error occurred while creating the port'
            ];
        }
    }

    /**
     * Get port by ID
     * 
     * @param string $portId Port ID
     * @return array Returns array with 'success' boolean and 'port' or 'error'
     */
    public function getPort(string $portId): array
    {
        try {
            $port = $this->portModel->findById($portId);

            if (!$port) {
                return [
                    'success' => false,
                    'error' => 'Port not found'
                ];
            }

            return [
                'success' => true,
                'port' => $port
            ];
        } catch (\Exception $e) {
            error_log('PortService::getPort failed: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => 'An error occurred while retrieving the port'
            ];
        }
    }

    /**
     * Update port
     * 
     * @param string $portId Port ID
     * @param array $data Data to update
     * @return array Returns array with 'success' boolean and 'port' or 'error'
     */
    public function updatePort(string $portId, array $data): array
    {
        try {
            // Check if port exists
            $existingPort = $this->portModel->findById($portId);
            if (!$existingPort) {
                return [
                    'success' => false,
                    'error' => 'Port not found'
                ];
            }

            // Validate status if provided
            if (isset($data['status'])) {
                $validStatuses = ['AVAILABLE', 'RESERVED', 'ASSIGNED', 'DISABLED'];
                if (!in_array($data['status'], $validStatuses)) {
                    return [
                        'success' => false,
                        'error' => 'Invalid status. Must be one of: ' . implode(', ', $validStatuses)
                    ];
                }
            }

            // Check for duplicate if instance_url is being updated
            if (isset($data['instance_url'])) {
                $instanceUrl = $data['instance_url'];
                
                if ($this->portModel->portExists($instanceUrl, $portId)) {
                    return [
                        'success' => false,
                        'error' => 'Port with this instance URL already exists',
                        'error_code' => 'DUPLICATE_INSTANCE'
                    ];
                }
            }

            $success = $this->portModel->update($portId, $data);

            if (!$success) {
                return [
                    'success' => false,
                    'error' => 'Failed to update port'
                ];
            }

            $updatedPort = $this->portModel->findById($portId);

            return [
                'success' => true,
                'port' => $updatedPort
            ];
        } catch (\Exception $e) {
            error_log('PortService::updatePort failed: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => 'An error occurred while updating the port'
            ];
        }
    }

    /**
     * Delete port
     * 
     * @param string $portId Port ID
     * @return array Returns array with 'success' boolean and optional 'error'
     */
    public function deletePort(string $portId): array
    {
        try {
            // Check if port exists
            $port = $this->portModel->findById($portId);
            if (!$port) {
                return [
                    'success' => false,
                    'error' => 'Port not found'
                ];
            }

            // Check if port is assigned
            if ($port['status'] === 'ASSIGNED' && $port['assigned_subscription_id']) {
                return [
                    'success' => false,
                    'error' => 'Cannot delete port that is currently assigned to a subscription'
                ];
            }

            $success = $this->portModel->delete($portId);

            if (!$success) {
                return [
                    'success' => false,
                    'error' => 'Failed to delete port'
                ];
            }

            return [
                'success' => true
            ];
        } catch (\Exception $e) {
            error_log('PortService::deletePort failed: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => 'An error occurred while deleting the port'
            ];
        }
    }

    /**
     * Get all ports with filters
     * 
     * @param array $filters Optional filters
     * @param int $limit Optional limit
     * @param int $offset Optional offset
     * @return array Returns array with 'success' boolean and 'ports' or 'error'
     */
    public function getPorts(array $filters = [], int $limit = 100, int $offset = 0): array
    {
        try {
            $ports = $this->portModel->findAll($filters, $limit, $offset);

            return [
                'success' => true,
                'ports' => $ports,
                'count' => count($ports)
            ];
        } catch (\Exception $e) {
            error_log('PortService::getPorts failed: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => 'An error occurred while retrieving ports'
            ];
        }
    }

    /**
     * Update port status
     * 
     * @param string $portId Port ID
     * @param string $status New status
     * @return array Returns array with 'success' boolean and optional 'error'
     */
    public function updatePortStatus(string $portId, string $status): array
    {
        $validStatuses = ['AVAILABLE', 'RESERVED', 'ASSIGNED', 'DISABLED'];
        
        if (!in_array($status, $validStatuses)) {
            return [
                'success' => false,
                'error' => 'Invalid status. Must be one of: ' . implode(', ', $validStatuses)
            ];
        }

        return $this->updatePort($portId, ['status' => $status]);
    }

    /**
     * Get available ports (plan-agnostic)
     * 
     * @param int $limit Optional limit
     * @return array Returns array with 'success' boolean and 'ports' or 'error'
     */
    public function getAvailablePorts(int $limit = 10): array
    {
        try {
            $ports = $this->portModel->findAvailable($limit);

            return [
                'success' => true,
                'ports' => $ports,
                'count' => count($ports)
            ];
        } catch (\Exception $e) {
            error_log('PortService::getAvailablePorts failed: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => 'An error occurred while retrieving available ports'
            ];
        }
    }

    /**
     * Count available ports (plan-agnostic)
     * 
     * @return int Returns count of available ports
     */
    public function countAvailablePorts(): int
    {
        try {
            return $this->portModel->countAvailable();
        } catch (\Exception $e) {
            error_log('PortService::countAvailablePorts failed: ' . $e->getMessage());
            return 0;
        }
    }

    /**
     * Bulk import ports from array
     * 
     * @param array $portsData Array of port data
     * @return array Returns array with 'success' boolean, 'imported', 'failed', and 'errors'
     */
    public function bulkImportPorts(array $portsData): array
    {
        $imported = [];
        $failed = [];
        $errors = [];

        foreach ($portsData as $index => $portData) {
            $result = $this->createPort($portData);
            
            if ($result['success']) {
                $imported[] = $result['port'];
            } else {
                $failed[] = $portData;
                $errors[$index] = $result['error'];
            }
        }

        return [
            'success' => true,
            'imported' => count($imported),
            'failed' => count($failed),
            'imported_ports' => $imported,
            'failed_ports' => $failed,
            'errors' => $errors
        ];
    }
}
