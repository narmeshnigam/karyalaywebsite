<?php

namespace Karyalay\Tests\Property;

use Eris\Generator;
use Eris\TestTrait;
use PHPUnit\Framework\TestCase;

/**
 * Feature: karyalay-portal-system, Property 48: Validation Error Accessibility
 * 
 * Property: For any form validation error, when displayed, it should include an 
 * error message adjacent to the field with appropriate ARIA attributes.
 * 
 * Validates: Requirements 14.5
 */
class ValidationErrorAccessibilityPropertyTest extends TestCase
{
    use TestTrait;

    /**
     * Property 48: Validation Error Accessibility
     * 
     * For any form field with a validation error, the error message should have:
     * - role="alert"
     * - aria-live="polite"
     * - ID that matches the field's aria-describedby
     * 
     * @test
     */
    public function validationErrorsHaveProperAriaAttributes(): void
    {
        $this->forAll(
            Generator\associative([
                'id' => Generator\string(),
                'name' => Generator\string(),
                'label' => Generator\string(),
                'type' => Generator\elements('text', 'email', 'tel', 'password'),
                'error' => Generator\string(),
                'required' => Generator\bool()
            ])
        )
        ->when(function ($fieldConfig) {
            // Filter out empty strings after sanitization
            $fieldConfig['id'] = preg_replace('/[^a-zA-Z0-9_-]/', '_', $fieldConfig['id']);
            $fieldConfig['name'] = preg_replace('/[^a-zA-Z0-9_\[\]-]/', '_', $fieldConfig['name']);
            
            return !empty($fieldConfig['id']) && 
                   !empty($fieldConfig['name']) && 
                   !empty($fieldConfig['label']) &&
                   !empty($fieldConfig['error']);
        })
        ->then(function ($fieldConfig) {
            require_once __DIR__ . '/../../includes/template_helpers.php';
            
            $html = render_accessible_field($fieldConfig);
            
            // HTML-encode the field ID for matching in HTML output
            $encodedId = htmlspecialchars($fieldConfig['id'], ENT_QUOTES, 'UTF-8');
            
            // Assert: Error message should have role="alert"
            $this->assertMatchesRegularExpression(
                '/<div[^>]+id=["\']' . preg_quote($encodedId, '/') . '-error["\'][^>]+role=["\']alert["\'][^>]*>/',
                $html,
                "Error message should have role='alert' attribute"
            );
            
            // Assert: Error message should have aria-live="polite"
            $this->assertMatchesRegularExpression(
                '/<div[^>]+id=["\']' . preg_quote($encodedId, '/') . '-error["\'][^>]+aria-live=["\']polite["\'][^>]*>/',
                $html,
                "Error message should have aria-live='polite' attribute"
            );
            
            // Assert: Field should have aria-invalid="true"
            $this->assertMatchesRegularExpression(
                '/aria-invalid=["\']true["\']/',
                $html,
                "Field with error should have aria-invalid='true'"
            );
            
            // Assert: Field should have aria-describedby pointing to error
            $this->assertMatchesRegularExpression(
                '/aria-describedby=["\'][^"\']*' . preg_quote($encodedId, '/') . '-error[^"\']*["\']/',
                $html,
                "Field should have aria-describedby pointing to error message"
            );
            
            // Assert: Error message should contain the error text
            $this->assertStringContainsString(
                htmlspecialchars($fieldConfig['error'], ENT_QUOTES, 'UTF-8'),
                $html,
                "Error message should contain the error text"
            );
        });
    }

    /**
     * Property 48: Validation Error Accessibility - Error ID Format
     * 
     * For any field with an error, the error message ID should follow the pattern {fieldId}-error
     * 
     * @test
     */
    public function errorMessageIdsFollowNamingConvention(): void
    {
        $this->forAll(
            Generator\associative([
                'id' => Generator\string(),
                'name' => Generator\string(),
                'label' => Generator\string(),
                'type' => Generator\elements('text', 'email'),
                'error' => Generator\string()
            ])
        )
        ->when(function ($fieldConfig) {
            $fieldConfig['id'] = preg_replace('/[^a-zA-Z0-9_-]/', '_', $fieldConfig['id']);
            $fieldConfig['name'] = preg_replace('/[^a-zA-Z0-9_\[\]-]/', '_', $fieldConfig['name']);
            
            return !empty($fieldConfig['id']) && 
                   !empty($fieldConfig['name']) && 
                   !empty($fieldConfig['label']) &&
                   !empty($fieldConfig['error']);
        })
        ->then(function ($fieldConfig) {
            require_once __DIR__ . '/../../includes/template_helpers.php';
            
            $html = render_accessible_field($fieldConfig);
            
            $expectedErrorId = $fieldConfig['id'] . '-error';
            
            // Assert: Error message should have ID following the pattern
            $this->assertStringContainsString(
                'id="' . htmlspecialchars($expectedErrorId, ENT_QUOTES, 'UTF-8') . '"',
                $html,
                "Error message ID should follow the pattern {fieldId}-error"
            );
        });
    }

    /**
     * Property 48: Validation Error Accessibility - Field with Error and Help Text
     * 
     * For any field with both error and help text, aria-describedby should reference both
     * 
     * @test
     */
    public function fieldsWithErrorAndHelpTextReferencesBoth(): void
    {
        $this->forAll(
            Generator\associative([
                'id' => Generator\string(),
                'name' => Generator\string(),
                'label' => Generator\string(),
                'type' => Generator\elements('text', 'email', 'tel'),
                'error' => Generator\string(),
                'help' => Generator\string()
            ])
        )
        ->when(function ($fieldConfig) {
            $fieldConfig['id'] = preg_replace('/[^a-zA-Z0-9_-]/', '_', $fieldConfig['id']);
            $fieldConfig['name'] = preg_replace('/[^a-zA-Z0-9_\[\]-]/', '_', $fieldConfig['name']);
            
            return !empty($fieldConfig['id']) && 
                   !empty($fieldConfig['name']) && 
                   !empty($fieldConfig['label']) &&
                   !empty($fieldConfig['error']) &&
                   !empty($fieldConfig['help']);
        })
        ->then(function ($fieldConfig) {
            require_once __DIR__ . '/../../includes/template_helpers.php';
            
            $html = render_accessible_field($fieldConfig);
            
            // HTML-encode the field ID for matching in HTML output
            $encodedId = htmlspecialchars($fieldConfig['id'], ENT_QUOTES, 'UTF-8');
            $errorId = $encodedId . '-error';
            $helpId = $encodedId . '-help';
            
            // Assert: aria-describedby should contain both error and help IDs
            $this->assertMatchesRegularExpression(
                '/aria-describedby=["\'][^"\']*' . preg_quote($errorId, '/') . '[^"\']*["\']/',
                $html,
                "aria-describedby should reference error ID"
            );
            
            $this->assertMatchesRegularExpression(
                '/aria-describedby=["\'][^"\']*' . preg_quote($helpId, '/') . '[^"\']*["\']/',
                $html,
                "aria-describedby should reference help ID"
            );
        });
    }

    /**
     * Property 48: Validation Error Accessibility - Error Summary
     * 
     * For any set of validation errors, the error summary should have proper ARIA attributes
     * 
     * @test
     */
    public function errorSummaryHasProperAriaAttributes(): void
    {
        $this->forAll(
            Generator\associative([
                'field1' => Generator\string(),
                'field2' => Generator\string(),
                'field3' => Generator\string()
            ])
        )
        ->when(function ($errors) {
            return !empty($errors['field1']) && !empty($errors['field2']);
        })
        ->then(function ($errors) {
            require_once __DIR__ . '/../../includes/template_helpers.php';
            
            $html = render_form_errors($errors);
            
            // Assert: Error summary should have role="alert"
            $this->assertMatchesRegularExpression(
                '/<div[^>]+role=["\']alert["\'][^>]*>/',
                $html,
                "Error summary should have role='alert'"
            );
            
            // Assert: Error summary should have aria-live="assertive"
            $this->assertMatchesRegularExpression(
                '/<div[^>]+aria-live=["\']assertive["\'][^>]*>/',
                $html,
                "Error summary should have aria-live='assertive' for immediate announcement"
            );
            
            // Assert: Each error should link to its field
            foreach ($errors as $fieldId => $errorMessage) {
                if (!empty($errorMessage)) {
                    $this->assertStringContainsString(
                        'href="#' . htmlspecialchars($fieldId, ENT_QUOTES, 'UTF-8') . '"',
                        $html,
                        "Error summary should link to field {$fieldId}"
                    );
                }
            }
        });
    }

    /**
     * Property 48: Validation Error Accessibility - Error Visibility
     * 
     * For any field with an error, the error message should be visually adjacent to the field
     * 
     * @test
     */
    public function errorMessagesAreAdjacentToFields(): void
    {
        $this->forAll(
            Generator\associative([
                'id' => Generator\string(),
                'name' => Generator\string(),
                'label' => Generator\string(),
                'type' => Generator\elements('text', 'email', 'textarea'),
                'error' => Generator\string()
            ])
        )
        ->when(function ($fieldConfig) {
            $fieldConfig['id'] = preg_replace('/[^a-zA-Z0-9_-]/', '_', $fieldConfig['id']);
            $fieldConfig['name'] = preg_replace('/[^a-zA-Z0-9_\[\]-]/', '_', $fieldConfig['name']);
            
            return !empty($fieldConfig['id']) && 
                   !empty($fieldConfig['name']) && 
                   !empty($fieldConfig['label']) &&
                   !empty($fieldConfig['error']);
        })
        ->then(function ($fieldConfig) {
            require_once __DIR__ . '/../../includes/template_helpers.php';
            
            $html = render_accessible_field($fieldConfig);
            
            // Assert: Error message should appear after the input field
            $inputPattern = '/<(?:input|textarea)[^>]+id=["\']' . preg_quote($fieldConfig['id'], '/') . '["\'][^>]*>/';
            $errorPattern = '/<div[^>]+id=["\']' . preg_quote($fieldConfig['id'], '/') . '-error["\'][^>]*>/';
            
            preg_match($inputPattern, $html, $inputMatch, PREG_OFFSET_CAPTURE);
            preg_match($errorPattern, $html, $errorMatch, PREG_OFFSET_CAPTURE);
            
            if (!empty($inputMatch) && !empty($errorMatch)) {
                $this->assertLessThan(
                    $errorMatch[0][1],
                    $inputMatch[0][1],
                    "Error message should appear after the input field in the HTML"
                );
            }
        });
    }

    /**
     * Property 48: Validation Error Accessibility - CSS Classes
     * 
     * For any field with an error, proper CSS classes should be applied
     * 
     * @test
     */
    public function fieldsWithErrorsHaveProperCssClasses(): void
    {
        $this->forAll(
            Generator\associative([
                'id' => Generator\string(),
                'name' => Generator\string(),
                'label' => Generator\string(),
                'type' => Generator\elements('text', 'email'),
                'error' => Generator\string()
            ])
        )
        ->when(function ($fieldConfig) {
            $fieldConfig['id'] = preg_replace('/[^a-zA-Z0-9_-]/', '_', $fieldConfig['id']);
            $fieldConfig['name'] = preg_replace('/[^a-zA-Z0-9_\[\]-]/', '_', $fieldConfig['name']);
            
            return !empty($fieldConfig['id']) && 
                   !empty($fieldConfig['name']) && 
                   !empty($fieldConfig['label']) &&
                   !empty($fieldConfig['error']);
        })
        ->then(function ($fieldConfig) {
            require_once __DIR__ . '/../../includes/template_helpers.php';
            
            $html = render_accessible_field($fieldConfig);
            
            // Assert: Form group should have 'has-error' class
            $this->assertMatchesRegularExpression(
                '/<div[^>]+class=["\'][^"\']*has-error[^"\']*["\'][^>]*>/',
                $html,
                "Form group should have 'has-error' class"
            );
            
            // Assert: Input should have 'is-invalid' class
            $this->assertMatchesRegularExpression(
                '/<(?:input|textarea)[^>]+class=["\'][^"\']*is-invalid[^"\']*["\'][^>]*>/',
                $html,
                "Input field should have 'is-invalid' class"
            );
            
            // Assert: Error message should have 'form-error' class
            $this->assertMatchesRegularExpression(
                '/<div[^>]+class=["\'][^"\']*form-error[^"\']*["\'][^>]*>/',
                $html,
                "Error message should have 'form-error' class"
            );
        });
    }
}
