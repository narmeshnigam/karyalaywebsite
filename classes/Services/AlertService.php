<?php

namespace Karyalay\Services;

/**
 * Alert Service
 * Sends alerts for critical issues via email, SMS, or webhooks
 */
class AlertService
{
    private static ?AlertService $instance = null;
    private bool $enabled;
    private array $alertChannels = [];
    private array $alertHistory = [];
    private int $rateLimitWindow = 300; // 5 minutes
    
    // Alert severity levels
    const SEVERITY_INFO = 'info';
    const SEVERITY_WARNING = 'warning';
    const SEVERITY_ERROR = 'error';
    const SEVERITY_CRITICAL = 'critical';
    
    private function __construct()
    {
        $this->enabled = getenv('ALERTS_ENABLED') === 'true';
        $this->loadAlertChannels();
    }
    
    public static function getInstance(): AlertService
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Load alert channels from configuration
     */
    private function loadAlertChannels(): void
    {
        // Email channel
        if ($email = getenv('ALERT_EMAIL')) {
            $this->alertChannels['email'] = [
                'type' => 'email',
                'recipients' => explode(',', $email),
                'enabled' => true,
            ];
        }
        
        // Slack webhook
        if ($slackWebhook = getenv('SLACK_WEBHOOK_URL')) {
            $this->alertChannels['slack'] = [
                'type' => 'slack',
                'webhook_url' => $slackWebhook,
                'enabled' => true,
            ];
        }
        
        // PagerDuty
        if ($pagerDutyKey = getenv('PAGERDUTY_INTEGRATION_KEY')) {
            $this->alertChannels['pagerduty'] = [
                'type' => 'pagerduty',
                'integration_key' => $pagerDutyKey,
                'enabled' => true,
            ];
        }
    }
    
    /**
     * Send an alert
     */
    public function sendAlert(
        string $title,
        string $message,
        string $severity = self::SEVERITY_ERROR,
        array $context = []
    ): bool {
        if (!$this->enabled) {
            return false;
        }
        
        // Check rate limiting
        if ($this->isRateLimited($title)) {
            $logger = LoggerService::getInstance();
            $logger->debug("Alert rate limited: {$title}");
            return false;
        }
        
        $alert = [
            'title' => $title,
            'message' => $message,
            'severity' => $severity,
            'context' => $context,
            'timestamp' => date('c'),
            'environment' => getenv('APP_ENV') ?: 'development',
        ];
        
        $success = false;
        
        // Send to all enabled channels
        foreach ($this->alertChannels as $channel) {
            if (!$channel['enabled']) {
                continue;
            }
            
            try {
                switch ($channel['type']) {
                    case 'email':
                        $this->sendEmailAlert($alert, $channel);
                        $success = true;
                        break;
                    
                    case 'slack':
                        $this->sendSlackAlert($alert, $channel);
                        $success = true;
                        break;
                    
                    case 'pagerduty':
                        $this->sendPagerDutyAlert($alert, $channel);
                        $success = true;
                        break;
                }
            } catch (\Exception $e) {
                $logger = LoggerService::getInstance();
                $logger->error("Failed to send alert via {$channel['type']}", [
                    'error' => $e->getMessage(),
                    'alert' => $alert,
                ]);
            }
        }
        
        // Record alert in history
        $this->recordAlert($title);
        
        // Log the alert
        $logger = LoggerService::getInstance();
        $logger->log($severity, "Alert: {$title}", array_merge(['message' => $message], $context));
        
        return $success;
    }
    
    /**
     * Send alert via email
     */
    private function sendEmailAlert(array $alert, array $channel): void
    {
        $emailService = EmailService::getInstance();
        
        $subject = "[{$alert['severity']}] {$alert['title']} - {$alert['environment']}";
        
        $body = "Alert: {$alert['title']}\n\n";
        $body .= "Severity: {$alert['severity']}\n";
        $body .= "Environment: {$alert['environment']}\n";
        $body .= "Time: {$alert['timestamp']}\n\n";
        $body .= "Message:\n{$alert['message']}\n\n";
        
        if (!empty($alert['context'])) {
            $body .= "Context:\n" . json_encode($alert['context'], JSON_PRETTY_PRINT) . "\n";
        }
        
        foreach ($channel['recipients'] as $recipient) {
            $emailService->sendEmail(
                trim($recipient),
                $subject,
                $body
            );
        }
    }
    
    /**
     * Send alert to Slack
     */
    private function sendSlackAlert(array $alert, array $channel): void
    {
        $color = $this->getSeverityColor($alert['severity']);
        
        $payload = [
            'attachments' => [
                [
                    'color' => $color,
                    'title' => $alert['title'],
                    'text' => $alert['message'],
                    'fields' => [
                        [
                            'title' => 'Severity',
                            'value' => strtoupper($alert['severity']),
                            'short' => true,
                        ],
                        [
                            'title' => 'Environment',
                            'value' => $alert['environment'],
                            'short' => true,
                        ],
                    ],
                    'footer' => 'Portal System',
                    'ts' => time(),
                ],
            ],
        ];
        
        $ch = curl_init($channel['webhook_url']);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode !== 200) {
            throw new \Exception("Slack webhook returned status {$httpCode}");
        }
    }
    
    /**
     * Send alert to PagerDuty
     */
    private function sendPagerDutyAlert(array $alert, array $channel): void
    {
        $payload = [
            'routing_key' => $channel['integration_key'],
            'event_action' => 'trigger',
            'payload' => [
                'summary' => $alert['title'],
                'severity' => $this->mapSeverityToPagerDuty($alert['severity']),
                'source' => 'karyalay-portal',
                'custom_details' => [
                    'message' => $alert['message'],
                    'environment' => $alert['environment'],
                    'context' => $alert['context'],
                ],
            ],
        ];
        
        $ch = curl_init('https://events.pagerduty.com/v2/enqueue');
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode !== 202) {
            throw new \Exception("PagerDuty API returned status {$httpCode}");
        }
    }
    
    /**
     * Check if alert is rate limited
     */
    private function isRateLimited(string $title): bool
    {
        $key = md5($title);
        $now = time();
        
        if (isset($this->alertHistory[$key])) {
            $lastSent = $this->alertHistory[$key];
            if (($now - $lastSent) < $this->rateLimitWindow) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Record alert in history for rate limiting
     */
    private function recordAlert(string $title): void
    {
        $key = md5($title);
        $this->alertHistory[$key] = time();
    }
    
    /**
     * Get color for Slack based on severity
     */
    private function getSeverityColor(string $severity): string
    {
        $colors = [
            self::SEVERITY_INFO => '#36a64f',
            self::SEVERITY_WARNING => '#ff9900',
            self::SEVERITY_ERROR => '#ff0000',
            self::SEVERITY_CRITICAL => '#8b0000',
        ];
        
        return $colors[$severity] ?? '#cccccc';
    }
    
    /**
     * Map severity to PagerDuty severity
     */
    private function mapSeverityToPagerDuty(string $severity): string
    {
        $mapping = [
            self::SEVERITY_INFO => 'info',
            self::SEVERITY_WARNING => 'warning',
            self::SEVERITY_ERROR => 'error',
            self::SEVERITY_CRITICAL => 'critical',
        ];
        
        return $mapping[$severity] ?? 'error';
    }
    
    /**
     * Convenience methods for different severity levels
     */
    
    public function info(string $title, string $message, array $context = []): bool
    {
        return $this->sendAlert($title, $message, self::SEVERITY_INFO, $context);
    }
    
    public function warning(string $title, string $message, array $context = []): bool
    {
        return $this->sendAlert($title, $message, self::SEVERITY_WARNING, $context);
    }
    
    public function error(string $title, string $message, array $context = []): bool
    {
        return $this->sendAlert($title, $message, self::SEVERITY_ERROR, $context);
    }
    
    public function critical(string $title, string $message, array $context = []): bool
    {
        return $this->sendAlert($title, $message, self::SEVERITY_CRITICAL, $context);
    }
}

