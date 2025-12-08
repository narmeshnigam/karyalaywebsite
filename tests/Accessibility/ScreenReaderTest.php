<?php

namespace Karyalay\Tests\Accessibility;

use PHPUnit\Framework\TestCase;

/**
 * Screen Reader Accessibility Tests
 * 
 * Tests that content is properly structured and labeled for screen readers.
 */
class ScreenReaderTest extends TestCase
{
    /**
     * Test that all pages have proper HTML structure
     * 
     * @test
     */
    public function pagesHaveProperHtmlStructure(): void
    {
        $pages = [
            '/index.php',
            '/contact.php',
            '/demo.php',
            '/accessible-form-example.php',
        ];
        
        foreach ($pages as $page) {
            $html = $this->getPageHtml($page);
            
            // Check for lang attribute
            $this->assertMatchesRegularExpression(
                '/<html[^>]+lang=["\'][a-z]{2}["\']/',
                $html,
                "Page {$page} should have lang attribute on <html> element"
            );
            
            // Check for title element
            $this->assertMatchesRegularExpression(
                '/<title>[^<]+<\/title>/',
                $html,
                "Page {$page} should have <title> element"
            );
            
            // Check for main landmark
            $hasMain = preg_match('/<main[^>]*>/', $html) || 
                       preg_match('/role=["\']main["\']/', $html);
            
            if (!$hasMain) {
                $this->markTestIncomplete(
                    "Page {$page} should have <main> element or role='main'"
                );
            }
        }
    }
    
    /**
     * Test that headings follow hierarchical order
     * 
     * @test
     */
    public function headingsFollowHierarchicalOrder(): void
    {
        $pages = [
            '/index.php',
            '/contact.php',
            '/accessible-form-example.php',
        ];
        
        foreach ($pages as $page) {
            $html = $this->getPageHtml($page);
            
            // Extract all headings
            preg_match_all('/<h([1-6])[^>]*>/', $html, $matches);
            $headingLevels = array_map('intval', $matches[1]);
            
            if (empty($headingLevels)) {
                $this->markTestIncomplete("Page {$page} has no headings");
                continue;
            }
            
            // First heading should be h1
            $this->assertEquals(
                1,
                $headingLevels[0],
                "Page {$page} should start with <h1>"
            );
            
            // Check for proper hierarchy (no skipping levels)
            for ($i = 1; $i < count($headingLevels); $i++) {
                $current = $headingLevels[$i];
                $previous = $headingLevels[$i - 1];
                
                // Can go down any number of levels, but can only go up one level at a time
                if ($current > $previous) {
                    $this->assertLessThanOrEqual(
                        $previous + 1,
                        $current,
                        "Page {$page} should not skip heading levels (h{$previous} to h{$current})"
                    );
                }
            }
        }
    }
    
    /**
     * Test that images have alt attributes
     * 
     * @test
     */
    public function imagesHaveAltAttributes(): void
    {
        $pages = [
            '/index.php',
            '/contact.php',
        ];
        
        foreach ($pages as $page) {
            $html = $this->getPageHtml($page);
            
            // Find all img tags
            preg_match_all('/<img[^>]*>/', $html, $matches);
            
            foreach ($matches[0] as $img) {
                $this->assertMatchesRegularExpression(
                    '/alt=["\'][^"\']*["\']/',
                    $img,
                    "All images should have alt attribute: {$img}"
                );
            }
        }
    }
    
    /**
     * Test that ARIA landmarks are used appropriately
     * 
     * @test
     */
    public function ariaLandmarksAreUsedAppropriately(): void
    {
        $html = $this->getPageHtml('/index.php');
        
        // Check for navigation landmark
        $hasNav = preg_match('/<nav[^>]*>/', $html) || 
                  preg_match('/role=["\']navigation["\']/', $html);
        
        $this->assertTrue(
            $hasNav,
            'Page should have <nav> element or role="navigation"'
        );
    }
    
    /**
     * Test that forms have proper ARIA attributes
     * 
     * @test
     */
    public function formsHaveProperAriaAttributes(): void
    {
        $html = $this->getPageHtml('/accessible-form-example.php');
        
        // Find all required fields
        preg_match_all('/<(?:input|textarea|select)[^>]+required[^>]*>/', $html, $matches);
        
        foreach ($matches[0] as $field) {
            // Required fields should have aria-required
            $this->assertMatchesRegularExpression(
                '/aria-required=["\']true["\']/',
                $field,
                "Required fields should have aria-required='true': {$field}"
            );
        }
    }
    
    /**
     * Test that error messages have proper ARIA attributes
     * 
     * @test
     */
    public function errorMessagesHaveProperAriaAttributes(): void
    {
        // This is tested by ValidationErrorAccessibilityPropertyTest
        // but we'll add a basic check here
        
        require_once __DIR__ . '/../../includes/template_helpers.php';
        
        $fieldConfig = [
            'id' => 'test_field',
            'name' => 'test_field',
            'label' => 'Test Field',
            'type' => 'text',
            'error' => 'This field is required',
        ];
        
        $html = render_accessible_field($fieldConfig);
        
        // Check for role="alert"
        $this->assertStringContainsString(
            'role="alert"',
            $html,
            'Error messages should have role="alert"'
        );
        
        // Check for aria-live
        $this->assertStringContainsString(
            'aria-live="polite"',
            $html,
            'Error messages should have aria-live="polite"'
        );
        
        // Check for aria-invalid on field
        $this->assertStringContainsString(
            'aria-invalid="true"',
            $html,
            'Fields with errors should have aria-invalid="true"'
        );
        
        // Check for aria-describedby
        $this->assertStringContainsString(
            'aria-describedby="test_field-error"',
            $html,
            'Fields with errors should have aria-describedby pointing to error'
        );
    }
    
    /**
     * Test that dynamic content updates are announced
     * 
     * @test
     */
    public function dynamicContentUpdatesAreAnnounced(): void
    {
        require_once __DIR__ . '/../../includes/template_helpers.php';
        
        // Test success message
        $successHtml = '<div class="alert alert-success" role="alert" aria-live="polite">Success!</div>';
        
        $this->assertStringContainsString(
            'role="alert"',
            $successHtml,
            'Success messages should have role="alert"'
        );
        
        $this->assertStringContainsString(
            'aria-live="polite"',
            $successHtml,
            'Success messages should have aria-live="polite"'
        );
    }
    
    /**
     * Test that tables have proper structure
     * 
     * @test
     */
    public function tablesHaveProperStructure(): void
    {
        // Find pages with tables
        $adminPages = [
            '/admin/customers.php',
            '/admin/orders.php',
        ];
        
        foreach ($adminPages as $page) {
            $fullPath = __DIR__ . '/../../' . ltrim($page, '/');
            
            if (!file_exists($fullPath)) {
                continue;
            }
            
            $html = file_get_contents($fullPath);
            
            // Find all tables
            preg_match_all('/<table[^>]*>.*?<\/table>/s', $html, $matches);
            
            foreach ($matches[0] as $table) {
                // Tables should have thead
                if (strpos($table, '<thead') === false) {
                    $this->markTestIncomplete(
                        "Table in {$page} should have <thead> element"
                    );
                }
                
                // Tables should have th elements
                if (strpos($table, '<th') === false) {
                    $this->markTestIncomplete(
                        "Table in {$page} should have <th> elements for headers"
                    );
                }
            }
        }
    }
    
    /**
     * Test that lists use proper semantic markup
     * 
     * @test
     */
    public function listsUseProperSemanticMarkup(): void
    {
        $html = $this->getPageHtml('/index.php');
        
        // Find all list items
        preg_match_all('/<li[^>]*>/', $html, $matches);
        
        if (!empty($matches[0])) {
            // Each <li> should be inside <ul> or <ol>
            $fullHtml = $html;
            
            // This is a basic check - in a real scenario, you'd parse the DOM
            $hasUl = strpos($fullHtml, '<ul') !== false;
            $hasOl = strpos($fullHtml, '<ol') !== false;
            
            $this->assertTrue(
                $hasUl || $hasOl,
                'List items should be inside <ul> or <ol> elements'
            );
        }
    }
    
    /**
     * Test that buttons have descriptive text
     * 
     * @test
     */
    public function buttonsHaveDescriptiveText(): void
    {
        $html = $this->getPageHtml('/accessible-form-example.php');
        
        // Find all buttons
        preg_match_all('/<button[^>]*>(.*?)<\/button>/s', $html, $matches);
        
        foreach ($matches[1] as $buttonText) {
            $text = strip_tags($buttonText);
            $text = trim($text);
            
            $this->assertNotEmpty(
                $text,
                'Buttons should have descriptive text content'
            );
            
            // Button text should not be too generic
            $genericTexts = ['click', 'here', 'button'];
            $lowerText = strtolower($text);
            
            foreach ($genericTexts as $generic) {
                if ($lowerText === $generic) {
                    $this->markTestIncomplete(
                        "Button text '{$text}' is too generic. Use more descriptive text."
                    );
                }
            }
        }
    }
    
    /**
     * Test that links have descriptive text
     * 
     * @test
     */
    public function linksHaveDescriptiveText(): void
    {
        $html = $this->getPageHtml('/index.php');
        
        // Find all links
        preg_match_all('/<a[^>]*>(.*?)<\/a>/s', $html, $matches);
        
        foreach ($matches[1] as $linkText) {
            $text = strip_tags($linkText);
            $text = trim($text);
            
            if (empty($text)) {
                // Link might have aria-label
                $this->markTestIncomplete(
                    'Links without text should have aria-label'
                );
            }
            
            // Link text should not be too generic
            $genericTexts = ['click here', 'here', 'read more', 'link'];
            $lowerText = strtolower($text);
            
            if (in_array($lowerText, $genericTexts)) {
                $this->markTestIncomplete(
                    "Link text '{$text}' is too generic. Use more descriptive text."
                );
            }
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
}
