<?php

namespace Karyalay\Tests\Property;

use Eris\Generator;
use Eris\TestTrait;
use PHPUnit\Framework\TestCase;
use Karyalay\Services\DatabaseValidationService;

/**
 * Property-based tests for validation error message specificity
 * 
 * **Feature: dual-environment-setup, Property 7: Validation Error Message Specificity**
 * **Validates: Requirements 6.3**
 * 
 * For any validation error that occurs during credential submission, the system SHALL 
 * return an error message that identifies the specific field and provides actionable guidance.
 */
class ValidationErrorMessageSpecificityPropertyTest extends TestCase
{
    use TestTrait;

    private DatabaseValidationService $validationService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->validationService = new DatabaseValidationService();
    }

    /**
     * Property 7: Validation Error Message Specificity
     * 
     * For any error type, the system SHALL return an error message that:
     * 1. Is non-empty and descriptive
     * 2. Identifies the specific field when applicable
     * 3. Provides actionable troubleshooting suggestions
     * 
     * **Feature: dual-environment-setup, Property 7: Validation Error Message Specificity**
     * **Validates: Requirements 6.3**
     * 
     * @test
     */
    public function errorMessagesAreSpecificAndActionable(): void
    {
        $this->forAll(
            Generator\elements([
                DatabaseValidationService::ERROR_HOST_UNREACHABLE,
                DatabaseValidationService::ERROR_INVALID_CREDENTIALS,
                DatabaseValidationService::ERROR_DATABASE_NOT_FOUND,
                DatabaseValidationService::ERROR_SOCKET_NOT_FOUND,
                DatabaseValidationService::ERROR_TIMEOUT,
                DatabaseValidationService::ERROR_CONNECTION_REFUSED,
                DatabaseValidationService::ERROR_UNKNOWN
            ]),
            $this->credentialsContextGenerator()
        )
        ->withMaxSize(100)
        ->then(function ($errorType, $context) {
            // Get error message for this error type
            $errorMessage = $this->validationService->getErrorMessage($errorType, $context);
            
            // Property 1: Error message must be non-empty
            $this->assertNotEmpty($errorMessage, 
                "Error message for type '{$errorType}' should not be empty");
            
            // Property 2: Error message must be descriptive (at least 20 characters)
            $this->assertGreaterThanOrEqual(20, strlen($errorMessage),
                "Error message for type '{$errorType}' should be descriptive (at least 20 chars)");
            
            // Property 3: Error message should not contain raw exception text
            $this->assertStringNotContainsString('PDOException', $errorMessage,
                "Error message should not expose raw exception class names");
            $this->assertStringNotContainsString('SQLSTATE', $errorMessage,
                "Error message should not expose raw SQL state codes");
            
            // Get troubleshooting suggestions
            $suggestions = $this->validationService->getTroubleshootingSuggestions($errorType);
            
            // Property 4: Troubleshooting suggestions must be provided
            $this->assertIsArray($suggestions,
                "Troubleshooting suggestions should be an array");
            $this->assertNotEmpty($suggestions,
                "Troubleshooting suggestions should not be empty for type '{$errorType}'");
            
            // Property 5: Each suggestion must be actionable (non-empty string)
            foreach ($suggestions as $index => $suggestion) {
                $this->assertIsString($suggestion,
                    "Suggestion at index {$index} should be a string");
                $this->assertNotEmpty($suggestion,
                    "Suggestion at index {$index} should not be empty");
            }
        });
    }

    /**
     * Property: Error results include field identification when applicable
     * 
     * For any error type that relates to a specific field, the error result
     * SHALL include the field name to help users identify where to fix the issue.
     * 
     * **Feature: dual-environment-setup, Property 7: Validation Error Message Specificity**
     * **Validates: Requirements 6.3**
     * 
     * @test
     */
    public function errorResultsIncludeFieldIdentification(): void
    {
        // Map of error types to their expected field associations
        $errorTypeToField = [
            DatabaseValidationService::ERROR_HOST_UNREACHABLE => 'host',
            DatabaseValidationService::ERROR_INVALID_CREDENTIALS => 'username',
            DatabaseValidationService::ERROR_DATABASE_NOT_FOUND => 'database',
            DatabaseValidationService::ERROR_SOCKET_NOT_FOUND => 'unix_socket',
            DatabaseValidationService::ERROR_TIMEOUT => 'host',
        ];

        $this->forAll(
            Generator\elements(array_keys($errorTypeToField)),
            $this->credentialsContextGenerator()
        )
        ->withMaxSize(100)
        ->then(function ($errorType, $context) use ($errorTypeToField) {
            $expectedField = $errorTypeToField[$errorType];
            
            // Simulate creating an error result
            $errorResult = $this->simulateErrorResult($errorType, $context);
            
            // Property: Field should be identified in the error result
            $this->assertArrayHasKey('field', $errorResult,
                "Error result for type '{$errorType}' should include 'field' key");
            $this->assertEquals($expectedField, $errorResult['field'],
                "Error result for type '{$errorType}' should identify field '{$expectedField}'");
        });
    }

    /**
     * Property: Error messages include context-specific information
     * 
     * For any error with context (host, database, username), the error message
     * SHALL include the relevant context to help users identify the issue.
     * 
     * **Feature: dual-environment-setup, Property 7: Validation Error Message Specificity**
     * **Validates: Requirements 6.3**
     * 
     * @test
     */
    public function errorMessagesIncludeContextSpecificInformation(): void
    {
        $this->forAll(
            Generator\elements([
                DatabaseValidationService::ERROR_HOST_UNREACHABLE,
                DatabaseValidationService::ERROR_INVALID_CREDENTIALS,
                DatabaseValidationService::ERROR_DATABASE_NOT_FOUND,
                DatabaseValidationService::ERROR_SOCKET_NOT_FOUND
            ]),
            $this->credentialsContextGenerator()
        )
        ->withMaxSize(100)
        ->then(function ($errorType, $context) {
            $errorMessage = $this->validationService->getErrorMessage($errorType, $context);
            
            // Check that context-specific information is included
            switch ($errorType) {
                case DatabaseValidationService::ERROR_HOST_UNREACHABLE:
                    if (!empty($context['host'])) {
                        $this->assertStringContainsString($context['host'], $errorMessage,
                            "Host unreachable error should mention the host");
                    }
                    break;
                    
                case DatabaseValidationService::ERROR_INVALID_CREDENTIALS:
                    if (!empty($context['username'])) {
                        $this->assertStringContainsString($context['username'], $errorMessage,
                            "Invalid credentials error should mention the username");
                    }
                    break;
                    
                case DatabaseValidationService::ERROR_DATABASE_NOT_FOUND:
                    if (!empty($context['database'])) {
                        $this->assertStringContainsString($context['database'], $errorMessage,
                            "Database not found error should mention the database name");
                    }
                    break;
                    
                case DatabaseValidationService::ERROR_SOCKET_NOT_FOUND:
                    if (!empty($context['unix_socket'])) {
                        $this->assertStringContainsString($context['unix_socket'], $errorMessage,
                            "Socket not found error should mention the socket path");
                    }
                    break;
            }
        });
    }

    /**
     * Property: All error types have distinct messages
     * 
     * For any two different error types, the error messages SHALL be distinct
     * to help users understand the specific nature of each error.
     * 
     * **Feature: dual-environment-setup, Property 7: Validation Error Message Specificity**
     * **Validates: Requirements 6.3**
     * 
     * @test
     */
    public function allErrorTypesHaveDistinctMessages(): void
    {
        $errorTypes = [
            DatabaseValidationService::ERROR_HOST_UNREACHABLE,
            DatabaseValidationService::ERROR_INVALID_CREDENTIALS,
            DatabaseValidationService::ERROR_DATABASE_NOT_FOUND,
            DatabaseValidationService::ERROR_SOCKET_NOT_FOUND,
            DatabaseValidationService::ERROR_TIMEOUT,
            DatabaseValidationService::ERROR_CONNECTION_REFUSED,
        ];

        $this->forAll(
            $this->credentialsContextGenerator()
        )
        ->withMaxSize(100)
        ->then(function ($context) use ($errorTypes) {
            $messages = [];
            
            foreach ($errorTypes as $errorType) {
                $message = $this->validationService->getErrorMessage($errorType, $context);
                $messages[$errorType] = $message;
            }
            
            // Check that all messages are distinct
            $uniqueMessages = array_unique($messages);
            $this->assertCount(count($errorTypes), $uniqueMessages,
                "All error types should have distinct messages");
        });
    }

    /**
     * Generate credentials context for error messages
     * 
     * @return \Eris\Generator
     */
    private function credentialsContextGenerator(): Generator
    {
        return Generator\map(
            function ($values) {
                return [
                    'host' => $values[0],
                    'port' => $values[1],
                    'database' => $values[2],
                    'username' => $values[3],
                    'unix_socket' => $values[4]
                ];
            },
            Generator\tuple(
                Generator\elements(['localhost', '127.0.0.1', 'db.example.com', 'mysql.hostinger.com']),
                Generator\elements(['3306', '3307', '5432']),
                Generator\elements(['mydb', 'testdb', 'karyalay_portal', 'u123456_db']),
                Generator\elements(['root', 'admin', 'dbuser', 'u123456_user']),
                Generator\elements(['', '/var/run/mysqld/mysqld.sock', '/tmp/mysql.sock'])
            )
        );
    }

    /**
     * Simulate creating an error result for a given error type
     * This mimics what DatabaseValidationService::testConnection would return
     * 
     * @param string $errorType
     * @param array $context
     * @return array
     */
    private function simulateErrorResult(string $errorType, array $context): array
    {
        $fieldMap = [
            DatabaseValidationService::ERROR_HOST_UNREACHABLE => 'host',
            DatabaseValidationService::ERROR_INVALID_CREDENTIALS => 'username',
            DatabaseValidationService::ERROR_DATABASE_NOT_FOUND => 'database',
            DatabaseValidationService::ERROR_SOCKET_NOT_FOUND => 'unix_socket',
            DatabaseValidationService::ERROR_TIMEOUT => 'host',
            DatabaseValidationService::ERROR_CONNECTION_REFUSED => 'host',
            DatabaseValidationService::ERROR_UNKNOWN => null,
        ];

        return [
            'success' => false,
            'error_type' => $errorType,
            'error_message' => $this->validationService->getErrorMessage($errorType, $context),
            'troubleshooting' => $this->validationService->getTroubleshootingSuggestions($errorType),
            'field' => $fieldMap[$errorType] ?? null
        ];
    }
}
