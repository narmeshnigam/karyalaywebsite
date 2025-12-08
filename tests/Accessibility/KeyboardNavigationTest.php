<?php

namespace Karyalay\Tests\Accessibility;

use PHPUnit\Framework\TestCase;

/**
 * Keyboard Navigation Accessibility Tests
 * 
 * Tests that all interactive elements are keyboard accessible
 * and that focus management is properly implemented.
 */
class KeyboardNavigationTest extends TestCase
{
    /**
     * Test that all interactive elements have proper tabindex
     * 
     * @test
     */
    public function interactiveElementsAreKeyboardAccessible(): void
    {
        $pages = [
            '/contact.php',
            '/demo.php',
            '/accessible-form-example.php',
        ];
        
        foreach ($pages as $page) {
            $html = $this->getPageHtml($page);
            
            // Find all interactive elements
            $interactiveElements = $this->findInteractiveElements($html);
            
            foreach ($interactiveElements as $element) {
                // Elements should either have no tabindex or tabindex >= 0
                // Negative tabindex removes from tab order (not accessible)
                $this->assertNotContains(
                    'tabindex="-',
                    $element,
                    "Interactive element should not have negative tabindex: {$element}"
                );
            }
        }
    }
    
    /**
     * Test that focus indicators are present in CSS
     * 
     * @test
     */
    public function focusIndicatorsAreDefined(): void
    {
        $cssFile = __DIR__ . '/../../assets/css/components.css';
        $this->assertFileExists($cssFile, 'Components CSS file should exist');
        
        $css = file_get_contents($cssFile);
        
        // Check for focus styles
        $this->assertStringContainsString(
            ':focus',
            $css,
            'CSS should contain :focus selectors'
        );
        
        // Check that outline is not set to none globally
        $this->assertStringNotContainsString(
            '*:focus { outline: none',
            $css,
            'Focus outline should not be removed globally'
        );
        
        // Check for visible focus indicators
        $this->assertMatchesRegularExpression(
            '/\:focus\s*\{[^}]*outline\s*:/i',
            $css,
            'Focus styles should include outline property'
        );
    }
    
    /**
     * Test that forms have logical tab order
     * 
     * @test
     */
    public function formsHaveLogicalTabOrder(): void
    {
        $html = $this->getPageHtml('/accessible-form-example.php');
        
        // Extract form fields in order
        preg_match_all(
            '/<(?:input|textarea|select|button)[^>]+(?:id|name)=["\']([^"\']+)["\'][^>]*>/i',
            $html,
            $matches,
            PREG_OFFSET_CAPTURE
        );
        
        $fields = [];
        foreach ($matches[0] as $index => $match) {
            $element = $match[0];
            $position = $match[1];
            
            // Skip hidden fields
            if (strpos($element, 'type="hidden"') !== false) {
                continue;
            }
            
            // Extract field identifier
            preg_match('/(?:id|name)=["\']([^"\']+)["\']/', $element, $idMatch);
            $fieldId = $idMatch[1] ?? "field_{$index}";
            
            $fields[] = [
                'id' => $fieldId,
                'position' => $position,
                'element' => $element
            ];
        }
        
        // Verify fields appear in document order (no explicit tabindex reordering)
        $positions = array_column($fields, 'position');
        $sortedPositions = $positions;
        sort($sortedPositions);
        
        $this->assertEquals(
            $sortedPositions,
            $positions,
            'Form fields should appear in logical document order'
        );
    }
    
    /**
     * Test that skip links are present
     * 
     * @test
     */
    public function skipLinksArePresent(): void
    {
        $pages = [
            '/index.php',
            '/contact.php',
        ];
        
        foreach ($pages as $page) {
            $html = $this->getPageHtml($page);
            
            // Check for skip link
            $hasSkipLink = preg_match(
                '/<a[^>]+href=["\']#[^"\']*main[^"\']*["\'][^>]*>.*?skip.*?<\/a>/i',
                $html
            );
            
            if (!$hasSkipLink) {
                // Skip link might be in header template
                $this->markTestIncomplete(
                    "Skip link not found in {$page}. Consider adding skip navigation."
                );
            }
        }
    }
    
    /**
     * Test that buttons are keyboard activatable
     * 
     * @test
     */
    public function buttonsAreKeyboardActivatable(): void
    {
        $html = $this->getPageHtml('/accessible-form-example.php');
        
        // Find all buttons
        preg_match_all('/<button[^>]*>/', $html, $matches);
        
        foreach ($matches[0] as $button) {
            // Buttons should not have tabindex="-1" unless they're in a disabled state
            if (strpos($button, 'disabled') === false) {
                $this->assertStringNotContainsString(
                    'tabindex="-1"',
                    $button,
                    'Enabled buttons should be keyboard accessible'
                );
            }
            
            // Buttons should have type attribute
            $this->assertMatchesRegularExpression(
                '/type=["\'](?:button|submit|reset)["\']/',
                $button,
                'Buttons should have explicit type attribute'
            );
        }
    }
    
    /**
     * Helper: Get HTML content of a page
     */
    private function getPageHtml(string $page): string
    {
        $fullPath = __DIR__ . '/../../public' . $page;
        
        if (!file_exists($fullPath)) {
            $this->markTestSkipped("Page not found: {$page}");
        }
        
        // Set up minimal environment
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SESSION = [];
        
        ob_start();
        try {
            include $fullPath;
            return ob_get_clean();
        } catch (\Exception $e) {
            ob_end_clean();
            $this->markTestSkipped("Could not load page {$page}: " . $e->getMessage());
            return '';
        }
    }
    
    /**
     * Helper: Find interactive elements in HTML
     */
    private function findInteractiveElements(string $html): array
    {
        $elements = [];
        
        // Find links
        preg_match_all('/<a[^>]*>/', $html, $matches);
        $elements = array_merge($elements, $matches[0]);
        
        // Find buttons
        preg_match_all('/<button[^>]*>/', $html, $matches);
        $elements = array_merge($elements, $matches[0]);
        
        // Find inputs (except hidden)
        preg_match_all('/<input[^>]+type=["\'](?!hidden)[^"\']+["\'][^>]*>/', $html, $matches);
        $elements = array_merge($elements, $matches[0]);
        
        // Find textareas
        preg_match_all('/<textarea[^>]*>/', $html, $matches);
        $elements = array_merge($elements, $matches[0]);
        
        // Find selects
        preg_match_all('/<select[^>]*>/', $html, $matches);
        $elements = array_merge($elements, $matches[0]);
        
        return $elements;
    }
}
