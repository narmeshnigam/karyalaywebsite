<?php

namespace Karyalay\Tests\Unit\Services;

use PHPUnit\Framework\TestCase;
use Karyalay\Services\LoggerService;

class LoggerServiceTest extends TestCase
{
    private string $logDir;
    
    protected function setUp(): void
    {
        parent::setUp();
        $this->logDir = __DIR__ . '/../../../storage/logs';
        
        // Ensure log directory exists
        if (!is_dir($this->logDir)) {
            mkdir($this->logDir, 0755, true);
        }
        
        // Set environment for testing
        putenv('LOGGING_ENABLED=true');
        putenv('APP_ENV=test');
    }
    
    protected function tearDown(): void
    {
        parent::tearDown();
        
        // Clean up test log files
        $files = glob($this->logDir . '/test-*.log');
        foreach ($files as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }
    }
    
    public function testLoggerServiceIsSingleton(): void
    {
        $logger1 = LoggerService::getInstance();
        $logger2 = LoggerService::getInstance();
        
        $this->assertSame($logger1, $logger2);
    }
    
    public function testLoggerCreatesLogFile(): void
    {
        $logger = LoggerService::getInstance();
        $logger->info('Test message');
        
        $date = date('Y-m-d');
        $logFile = $this->logDir . "/test-{$date}.log";
        
        $this->assertFileExists($logFile);
    }
    
    public function testLoggerWritesStructuredJson(): void
    {
        $logger = LoggerService::getInstance();
        $logger->info('Test message', ['key' => 'value']);
        
        $date = date('Y-m-d');
        $logFile = $this->logDir . "/test-{$date}.log";
        
        $this->assertFileExists($logFile);
        
        $content = file_get_contents($logFile);
        $lines = explode("\n", trim($content));
        $lastLine = end($lines);
        
        $logEntry = json_decode($lastLine, true);
        
        $this->assertIsArray($logEntry);
        $this->assertEquals('info', $logEntry['level']);
        $this->assertEquals('Test message', $logEntry['message']);
        $this->assertEquals('test', $logEntry['environment']);
        $this->assertArrayHasKey('timestamp', $logEntry);
        $this->assertArrayHasKey('context', $logEntry);
        $this->assertEquals('value', $logEntry['context']['key']);
    }
    
    public function testLoggerSupportsAllLevels(): void
    {
        $logger = LoggerService::getInstance();
        
        $logger->emergency('Emergency message');
        $logger->alert('Alert message');
        $logger->critical('Critical message');
        $logger->error('Error message');
        $logger->warning('Warning message');
        $logger->notice('Notice message');
        $logger->info('Info message');
        $logger->debug('Debug message');
        
        $date = date('Y-m-d');
        $logFile = $this->logDir . "/test-{$date}.log";
        
        $this->assertFileExists($logFile);
        
        $content = file_get_contents($logFile);
        $this->assertStringContainsString('emergency', $content);
        $this->assertStringContainsString('alert', $content);
        $this->assertStringContainsString('critical', $content);
        $this->assertStringContainsString('error', $content);
        $this->assertStringContainsString('warning', $content);
        $this->assertStringContainsString('notice', $content);
        $this->assertStringContainsString('info', $content);
        $this->assertStringContainsString('debug', $content);
    }
    
    public function testLoggerCreatesErrorLogForCriticalLevels(): void
    {
        $logger = LoggerService::getInstance();
        $logger->error('Error message');
        
        $date = date('Y-m-d');
        $errorLogFile = $this->logDir . "/errors-{$date}.log";
        
        $this->assertFileExists($errorLogFile);
        
        $content = file_get_contents($errorLogFile);
        $this->assertStringContainsString('Error message', $content);
    }
    
    public function testLoggerCanLogExceptions(): void
    {
        $logger = LoggerService::getInstance();
        
        $exception = new \Exception('Test exception', 123);
        $logger->logException($exception);
        
        $date = date('Y-m-d');
        $logFile = $this->logDir . "/test-{$date}.log";
        
        $this->assertFileExists($logFile);
        
        $content = file_get_contents($logFile);
        $this->assertStringContainsString('Test exception', $content);
        $this->assertStringContainsString('Exception', $content);
    }
}

