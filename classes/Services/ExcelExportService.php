<?php

namespace Karyalay\Services;

use Karyalay\Models\Ticket;
use Karyalay\Models\User;
use Karyalay\Models\Order;
use Karyalay\Models\Subscription;
use Karyalay\Models\Lead;
use Karyalay\Models\Plan;
use Karyalay\Models\Port;

/**
 * Excel Export Service
 * 
 * Handles Excel export functionality for various admin list pages.
 * Uses CSV format for simplicity and universal compatibility.
 */
class ExcelExportService
{
    private User $userModel;
    private Ticket $ticketModel;
    private Order $orderModel;
    private Subscription $subscriptionModel;
    private Lead $leadModel;
    private Plan $planModel;
    private Port $portModel;

    public function __construct()
    {
        $this->userModel = new User();
        $this->ticketModel = new Ticket();
        $this->orderModel = new Order();
        $this->subscriptionModel = new Subscription();
        $this->leadModel = new Lead();
        $this->planModel = new Plan();
        $this->portModel = new Port();
    }

    /**
     * Export tickets to CSV
     */
    public function exportTickets(array $filters = []): void
    {
        $tickets = $this->ticketModel->findAll($filters, 10000, 0);
        
        $filename = 'tickets_export_' . date('Y-m-d_His') . '.csv';
        
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Pragma: no-cache');
        header('Expires: 0');
        
        $output = fopen('php://output', 'w');
        
        // Add BOM for Excel UTF-8 support
        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
        
        // Headers
        fputcsv($output, [
            'Ticket ID',
            'Subject',
            'Status',
            'Priority',
            'Category',
            'Customer Name',
            'Customer Email',
            'Customer Phone',
            'Assigned To',
            'Created At',
            'Updated At'
        ]);
        
        // Data rows
        foreach ($tickets as $ticket) {
            $customer = $this->userModel->findById($ticket['customer_id']);
            $assignedAdmin = !empty($ticket['assigned_to']) ? $this->userModel->findById($ticket['assigned_to']) : null;
            
            fputcsv($output, [
                strtoupper(substr($ticket['id'], 0, 8)),
                $ticket['subject'] ?? '',
                $ticket['status'] ?? '',
                $ticket['priority'] ?? '',
                $ticket['category'] ?? '',
                $customer['name'] ?? 'N/A',
                $customer['email'] ?? 'N/A',
                $customer['phone'] ?? 'N/A',
                $assignedAdmin ? $assignedAdmin['name'] : 'Unassigned',
                $ticket['created_at'] ?? '',
                $ticket['updated_at'] ?? ''
            ]);
        }
        
        fclose($output);
        exit;
    }

    /**
     * Export customers to CSV
     */
    public function exportCustomers(array $filters = []): void
    {
        // Build query with filters
        $customers = $this->userModel->findAll(['role' => 'CUSTOMER'], 10000, 0);
        
        $filename = 'customers_export_' . date('Y-m-d_His') . '.csv';
        
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Pragma: no-cache');
        header('Expires: 0');
        
        $output = fopen('php://output', 'w');
        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
        
        // Headers
        fputcsv($output, [
            'Customer ID',
            'Name',
            'Email',
            'Phone',
            'Business Name',
            'Email Verified',
            'Active Subscriptions',
            'Total Orders',
            'Total Spent',
            'Registered At'
        ]);
        
        // Data rows
        foreach ($customers as $customer) {
            // Get subscription count
            $subscriptions = $this->subscriptionModel->findByCustomerId($customer['id']);
            $activeSubscriptions = count(array_filter($subscriptions, fn($s) => $s['status'] === 'ACTIVE'));
            
            // Get order stats
            $orders = $this->orderModel->findByCustomerId($customer['id']);
            $totalSpent = array_sum(array_map(fn($o) => $o['amount'] ?? 0, $orders));
            
            fputcsv($output, [
                strtoupper(substr($customer['id'], 0, 8)),
                $customer['name'] ?? '',
                $customer['email'] ?? '',
                $customer['phone'] ?? 'N/A',
                $customer['business_name'] ?? 'N/A',
                $customer['email_verified'] ? 'Yes' : 'No',
                $activeSubscriptions,
                count($orders),
                number_format($totalSpent, 2),
                $customer['created_at'] ?? ''
            ]);
        }
        
        fclose($output);
        exit;
    }

    /**
     * Export orders to CSV
     */
    public function exportOrders(array $filters = []): void
    {
        $orders = $this->orderModel->findAll($filters, 10000, 0);
        
        $filename = 'orders_export_' . date('Y-m-d_His') . '.csv';
        
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Pragma: no-cache');
        header('Expires: 0');
        
        $output = fopen('php://output', 'w');
        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
        
        // Headers
        fputcsv($output, [
            'Order ID',
            'Customer Name',
            'Customer Email',
            'Customer Phone',
            'Plan Name',
            'Amount',
            'Currency',
            'Status',
            'Payment Method',
            'Payment ID',
            'Created At',
            'Paid At'
        ]);
        
        // Data rows
        foreach ($orders as $order) {
            $customer = $this->userModel->findById($order['customer_id']);
            $plan = !empty($order['plan_id']) ? $this->planModel->findById($order['plan_id']) : null;
            
            fputcsv($output, [
                strtoupper(substr($order['id'], 0, 8)),
                $customer['name'] ?? 'N/A',
                $customer['email'] ?? 'N/A',
                $customer['phone'] ?? 'N/A',
                $plan ? $plan['name'] : 'N/A',
                $order['amount'] ?? 0,
                $order['currency'] ?? 'INR',
                $order['status'] ?? '',
                $order['payment_method'] ?? 'N/A',
                $order['payment_id'] ?? 'N/A',
                $order['created_at'] ?? '',
                $order['paid_at'] ?? 'N/A'
            ]);
        }
        
        fclose($output);
        exit;
    }

    /**
     * Export subscriptions to CSV
     */
    public function exportSubscriptions(array $filters = []): void
    {
        $subscriptions = $this->subscriptionModel->findAll($filters, 10000, 0);
        
        $filename = 'subscriptions_export_' . date('Y-m-d_His') . '.csv';
        
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Pragma: no-cache');
        header('Expires: 0');
        
        $output = fopen('php://output', 'w');
        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
        
        // Headers
        fputcsv($output, [
            'Subscription ID',
            'Customer Name',
            'Customer Email',
            'Customer Phone',
            'Plan Name',
            'Plan Price',
            'Status',
            'Start Date',
            'End Date',
            'Auto Renew',
            'Created At'
        ]);
        
        // Data rows
        foreach ($subscriptions as $subscription) {
            $customer = $this->userModel->findById($subscription['customer_id']);
            $plan = $this->planModel->findById($subscription['plan_id']);
            
            fputcsv($output, [
                strtoupper(substr($subscription['id'], 0, 8)),
                $customer['name'] ?? 'N/A',
                $customer['email'] ?? 'N/A',
                $customer['phone'] ?? 'N/A',
                $plan ? $plan['name'] : 'N/A',
                $plan ? (!empty($plan['discounted_price']) ? $plan['discounted_price'] : $plan['mrp']) : 'N/A',
                $subscription['status'] ?? '',
                $subscription['start_date'] ?? 'N/A',
                $subscription['end_date'] ?? 'N/A',
                $subscription['auto_renew'] ? 'Yes' : 'No',
                $subscription['created_at'] ?? ''
            ]);
        }
        
        fclose($output);
        exit;
    }

    /**
     * Export leads to CSV
     */
    public function exportLeads(array $filters = []): void
    {
        $leads = $this->leadModel->getAll($filters, 10000, 0);
        
        $filename = 'leads_export_' . date('Y-m-d_His') . '.csv';
        
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Pragma: no-cache');
        header('Expires: 0');
        
        $output = fopen('php://output', 'w');
        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
        
        // Headers
        fputcsv($output, [
            'Lead ID',
            'Name',
            'Email',
            'Phone',
            'Company',
            'Message',
            'Source',
            'Status',
            'Created At'
        ]);
        
        // Data rows
        foreach ($leads as $lead) {
            fputcsv($output, [
                strtoupper(substr($lead['id'], 0, 8)),
                $lead['name'] ?? '',
                $lead['email'] ?? '',
                $lead['phone'] ?? 'N/A',
                $lead['company'] ?? 'N/A',
                $lead['message'] ?? 'N/A',
                $lead['source'] ?? 'N/A',
                $lead['status'] ?? 'NEW',
                $lead['created_at'] ?? ''
            ]);
        }
        
        fclose($output);
        exit;
    }

    /**
     * Export plans to CSV
     */
    public function exportPlans(array $filters = []): void
    {
        $plans = $this->planModel->findAll($filters, 10000, 0);
        
        $filename = 'plans_export_' . date('Y-m-d_His') . '.csv';
        
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Pragma: no-cache');
        header('Expires: 0');
        
        $output = fopen('php://output', 'w');
        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
        
        // Headers
        fputcsv($output, [
            'Plan ID',
            'Name',
            'Description',
            'Price',
            'Currency',
            'Billing Cycle',
            'Status',
            'Features',
            'Active Subscriptions',
            'Created At'
        ]);
        
        // Data rows
        foreach ($plans as $plan) {
            // Count active subscriptions for this plan
            $subscriptions = $this->subscriptionModel->findAll(['plan_id' => $plan['id']], 10000, 0);
            $activeCount = count(array_filter($subscriptions, fn($s) => $s['status'] === 'ACTIVE'));
            
            // Parse features
            $features = !empty($plan['features']) ? json_decode($plan['features'], true) : [];
            $featuresText = is_array($features) ? implode('; ', $features) : '';
            
            fputcsv($output, [
                strtoupper(substr($plan['id'], 0, 8)),
                $plan['name'] ?? '',
                $plan['description'] ?? '',
                !empty($plan['discounted_price']) ? $plan['discounted_price'] : ($plan['mrp'] ?? 0),
                $plan['currency'] ?? 'INR',
                $plan['billing_cycle'] ?? 'MONTHLY',
                $plan['status'] ?? 'ACTIVE',
                $featuresText,
                $activeCount,
                $plan['created_at'] ?? ''
            ]);
        }
        
        fclose($output);
        exit;
    }

    /**
     * Export ports to CSV
     */
    public function exportPorts(array $filters = []): void
    {
        $ports = $this->portModel->findAll($filters, 10000, 0);
        
        $filename = 'ports_export_' . date('Y-m-d_His') . '.csv';
        
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Pragma: no-cache');
        header('Expires: 0');
        
        $output = fopen('php://output', 'w');
        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
        
        // Headers
        fputcsv($output, [
            'Port ID',
            'Port Number',
            'Customer Name',
            'Customer Email',
            'Customer Phone',
            'Subscription ID',
            'Plan Name',
            'Status',
            'Assigned At',
            'Created At'
        ]);
        
        // Data rows
        foreach ($ports as $port) {
            $customer = !empty($port['customer_id']) ? $this->userModel->findById($port['customer_id']) : null;
            $subscription = !empty($port['subscription_id']) ? $this->subscriptionModel->findById($port['subscription_id']) : null;
            $plan = $subscription && !empty($subscription['plan_id']) ? $this->planModel->findById($subscription['plan_id']) : null;
            
            fputcsv($output, [
                strtoupper(substr($port['id'], 0, 8)),
                $port['port_number'] ?? '',
                $customer ? $customer['name'] : 'Unassigned',
                $customer ? $customer['email'] : 'N/A',
                $customer ? ($customer['phone'] ?? 'N/A') : 'N/A',
                $subscription ? strtoupper(substr($subscription['id'], 0, 8)) : 'N/A',
                $plan ? $plan['name'] : 'N/A',
                $port['status'] ?? '',
                $port['assigned_at'] ?? 'N/A',
                $port['created_at'] ?? ''
            ]);
        }
        
        fclose($output);
        exit;
    }
}
