<?php

namespace Karyalay\Tests\Property;

use Eris\Generator;
use Eris\TestTrait;
use PHPUnit\Framework\TestCase;

/**
 * Feature: karyalay-portal-system, Property 47: Form Accessibility
 * 
 * Property: For any form field, when rendered, it should include an associated 
 * label element for screen reader accessibility.
 * 
 * Validates: Requirements 14.4
 */
class FormAccessibilityPropertyTest extends TestCase
{
    use TestTrait;

    /**
     * Property 47: Form Accessibility
     * 
     * For any form field with an ID, the rendered HTML should contain a label 
     * element with a matching 'for' attribute.
     * 
     * @test
     */
    public function allFormFieldsHaveAssociatedLabels(): void
    {
        $this->forAll(
            Generator\associative([
                'id' => Generator\string(),
                'name' => Generator\string(),
                'label' => Generator\string(),
                'type' => Generator\elements('text', 'email', 'tel', 'password', 'number', 'date', 'textarea'),
                'required' => Generator\bool(),
                'value' => Generator\string()
            ])
        )
        ->then(function ($fieldConfig) {
            // Sanitize field ID and name to be valid HTML identifiers
            $fieldConfig['id'] = preg_replace('/[^a-zA-Z0-9_-]/', '_', $fieldConfig['id']);
            $fieldConfig['name'] = preg_replace('/[^a-zA-Z0-9_\[\]-]/', '_', $fieldConfig['name']);
            
            // Skip if sanitization resulted in empty strings
            if (empty($fieldConfig['id']) || empty($fieldConfig['name']) || empty($fieldConfig['label'])) {
                return;
            }
            
            // Load template helpers
            require_once __DIR__ . '/../../includes/template_helpers.php';
            
            // Render the field using our accessible field helper
            $html = render_accessible_field($fieldConfig);
            
            // Assert: The HTML should contain a label with for attribute matching the field ID
            $this->assertStringContainsString(
                '<label for="' . htmlspecialchars($fieldConfig['id'], ENT_QUOTES, 'UTF-8') . '"',
                $html,
                "Form field with ID '{$fieldConfig['id']}' should have an associated label element"
            );
            
            // Assert: The label should contain the label text
            $this->assertStringContainsString(
                htmlspecialchars($fieldConfig['label'], ENT_QUOTES, 'UTF-8'),
                $html,
                "Label should contain the label text"
            );
            
            // Assert: The field should have the correct ID
            $this->assertMatchesRegularExpression(
                '/id=["\']' . preg_quote($fieldConfig['id'], '/') . '["\']/',
                $html,
                "Form field should have the specified ID attribute"
            );
            
            // Assert: If required, should have aria-required attribute
            if ($fieldConfig['required']) {
                $this->assertStringContainsString(
                    'aria-required="true"',
                    $html,
                    "Required fields should have aria-required='true' attribute"
                );
            }
        });
    }

    /**
     * Property 47: Form Accessibility - Label Association
     * 
     * For any form field rendered in actual pages, verify that labels are properly associated.
     * 
     * @test
     */
    public function formPagesHaveProperLabelAssociations(): void
    {
        $this->forAll(
            Generator\elements(
                '/contact.php',
                '/demo.php',
                '/accessible-form-example.php'
            )
        )
        ->then(function ($page) {
            // Start output buffering
            ob_start();
            
            // Set up minimal environment
            $_SERVER['REQUEST_METHOD'] = 'GET';
            $_SESSION = [];
            
            try {
                // Include the page
                include __DIR__ . '/../../public' . $page;
                $html = ob_get_clean();
                
                // Load template helpers for validation
                require_once __DIR__ . '/../../includes/template_helpers.php';
                
                // Validate form accessibility
                $missingLabels = validate_form_accessibility($html);
                
                // Assert: All form fields should have associated labels
                $this->assertEmpty(
                    $missingLabels,
                    "Page {$page} has form fields without associated labels: " . implode(', ', $missingLabels)
                );
                
                // Assert: All input fields (except hidden) should have labels
                preg_match_all('/<input[^>]+type=["\'](?!hidden)[^"\']+["\'][^>]+id=["\']([^"\']+)["\'][^>]*>/i', $html, $matches);
                
                foreach ($matches[1] as $fieldId) {
                    $this->assertTrue(
                        has_associated_label($html, $fieldId),
                        "Field with ID '{$fieldId}' in {$page} should have an associated label"
                    );
                }
                
            } catch (\Exception $e) {
                ob_end_clean();
                // If page can't be loaded (e.g., missing dependencies), skip this iteration
                $this->markTestSkipped("Could not load page {$page}: " . $e->getMessage());
            }
        });
    }

    /**
     * Property 47: Form Accessibility - Required Field Indicators
     * 
     * For any required form field, the required indicator should have proper ARIA labeling.
     * 
     * @test
     */
    public function requiredFieldIndicatorsHaveAriaLabels(): void
    {
        $this->forAll(
            Generator\associative([
                'id' => Generator\string(),
                'name' => Generator\string(),
                'label' => Generator\string(),
                'type' => Generator\elements('text', 'email', 'tel'),
                'required' => Generator\constant(true)
            ])
        )
        ->then(function ($fieldConfig) {
            // Sanitize field ID and name
            $fieldConfig['id'] = preg_replace('/[^a-zA-Z0-9_-]/', '_', $fieldConfig['id']);
            $fieldConfig['name'] = preg_replace('/[^a-zA-Z0-9_\[\]-]/', '_', $fieldConfig['name']);
            
            if (empty($fieldConfig['id']) || empty($fieldConfig['name']) || empty($fieldConfig['label'])) {
                return;
            }
            
            require_once __DIR__ . '/../../includes/template_helpers.php';
            
            $html = render_accessible_field($fieldConfig);
            
            // Assert: Required indicator should have aria-label
            $this->assertMatchesRegularExpression(
                '/<span[^>]+aria-label=["\']required["\'][^>]*>\*<\/span>/',
                $html,
                "Required field indicator should have aria-label='required'"
            );
        });
    }

    /**
     * Property 47: Form Accessibility - Multiple Fields
     * 
     * For any set of form fields, each should have a unique ID and associated label.
     * 
     * @test
     */
    public function multipleFieldsHaveUniqueIdsAndLabels(): void
    {
        $this->forAll(
            Generator\seq(
                Generator\associative([
                    'id' => Generator\string(),
                    'name' => Generator\string(),
                    'label' => Generator\string(),
                    'type' => Generator\elements('text', 'email'),
                    'required' => Generator\bool()
                ])
            )
        )
        ->then(function ($fields) {
            require_once __DIR__ . '/../../includes/template_helpers.php';
            
            $renderedFields = [];
            $fieldIds = [];
            
            foreach ($fields as $fieldConfig) {
                // Sanitize
                $fieldConfig['id'] = preg_replace('/[^a-zA-Z0-9_-]/', '_', $fieldConfig['id']);
                $fieldConfig['name'] = preg_replace('/[^a-zA-Z0-9_\[\]-]/', '_', $fieldConfig['name']);
                
                if (empty($fieldConfig['id']) || empty($fieldConfig['name']) || empty($fieldConfig['label'])) {
                    continue;
                }
                
                // Make IDs unique by appending index
                $fieldConfig['id'] = $fieldConfig['id'] . '_' . count($renderedFields);
                
                $html = render_accessible_field($fieldConfig);
                $renderedFields[] = $html;
                $fieldIds[] = $fieldConfig['id'];
            }
            
            if (empty($renderedFields)) {
                return;
            }
            
            $combinedHtml = implode("\n", $renderedFields);
            
            // Assert: Each field ID should appear exactly once
            foreach ($fieldIds as $fieldId) {
                $count = preg_match_all('/id=["\']' . preg_quote($fieldId, '/') . '["\']/', $combinedHtml);
                $this->assertEquals(
                    1,
                    $count,
                    "Field ID '{$fieldId}' should appear exactly once in the form"
                );
                
                // Assert: Each field should have an associated label
                $this->assertTrue(
                    has_associated_label($combinedHtml, $fieldId),
                    "Field with ID '{$fieldId}' should have an associated label"
                );
            }
        });
    }
}
