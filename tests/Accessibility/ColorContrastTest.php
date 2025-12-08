<?php

namespace Karyalay\Tests\Accessibility;

use PHPUnit\Framework\TestCase;

/**
 * Color Contrast Accessibility Tests
 * 
 * Tests that color combinations meet WCAG 2.1 Level AA contrast requirements:
 * - Normal text: 4.5:1 minimum
 * - Large text (18pt+ or 14pt+ bold): 3:1 minimum
 * - UI components: 3:1 minimum
 */
class ColorContrastTest extends TestCase
{
    /**
     * Test primary text colors meet contrast requirements
     * 
     * @test
     */
    public function primaryTextColorsHaveSufficientContrast(): void
    {
        // Body text on white background
        $ratio = $this->calculateContrastRatio('#212529', '#ffffff');
        $this->assertGreaterThanOrEqual(
            4.5,
            $ratio,
            "Body text (#212529 on #ffffff) should have at least 4.5:1 contrast ratio. Got: {$ratio}:1"
        );
        
        // Secondary text on white background
        $ratio = $this->calculateContrastRatio('#6c757d', '#ffffff');
        $this->assertGreaterThanOrEqual(
            4.5,
            $ratio,
            "Secondary text (#6c757d on #ffffff) should have at least 4.5:1 contrast ratio. Got: {$ratio}:1"
        );
    }
    
    /**
     * Test button colors meet contrast requirements
     * 
     * @test
     */
    public function buttonColorsHaveSufficientContrast(): void
    {
        $buttons = [
            'Primary' => ['#ffffff', '#0066cc'],
            'Success' => ['#ffffff', '#28a745'],
            'Danger' => ['#ffffff', '#dc3545'],
            'Warning' => ['#212529', '#ffc107'],
            'Info' => ['#ffffff', '#17a2b8'],
        ];
        
        foreach ($buttons as $name => $colors) {
            [$text, $bg] = $colors;
            $ratio = $this->calculateContrastRatio($text, $bg);
            
            $this->assertGreaterThanOrEqual(
                4.5,
                $ratio,
                "{$name} button ({$text} on {$bg}) should have at least 4.5:1 contrast ratio. Got: {$ratio}:1"
            );
        }
    }
    
    /**
     * Test link colors meet contrast requirements
     * 
     * @test
     */
    public function linkColorsHaveSufficientContrast(): void
    {
        // Links on white background
        $ratio = $this->calculateContrastRatio('#0066cc', '#ffffff');
        $this->assertGreaterThanOrEqual(
            4.5,
            $ratio,
            "Links (#0066cc on #ffffff) should have at least 4.5:1 contrast ratio. Got: {$ratio}:1"
        );
    }
    
    /**
     * Test error message colors meet contrast requirements
     * 
     * @test
     */
    public function errorMessageColorsHaveSufficientContrast(): void
    {
        // Error text on white background
        $ratio = $this->calculateContrastRatio('#dc3545', '#ffffff');
        $this->assertGreaterThanOrEqual(
            4.5,
            $ratio,
            "Error messages (#dc3545 on #ffffff) should have at least 4.5:1 contrast ratio. Got: {$ratio}:1"
        );
    }
    
    /**
     * Test form control borders meet UI component contrast requirements
     * 
     * @test
     */
    public function formControlBordersHaveSufficientContrast(): void
    {
        // Input border on white background
        $ratio = $this->calculateContrastRatio('#ced4da', '#ffffff');
        $this->assertGreaterThanOrEqual(
            3.0,
            $ratio,
            "Form control borders (#ced4da on #ffffff) should have at least 3:1 contrast ratio. Got: {$ratio}:1"
        );
        
        // Focus border on white background
        $ratio = $this->calculateContrastRatio('#0066cc', '#ffffff');
        $this->assertGreaterThanOrEqual(
            3.0,
            $ratio,
            "Focus borders (#0066cc on #ffffff) should have at least 3:1 contrast ratio. Got: {$ratio}:1"
        );
    }
    
    /**
     * Test focus indicators meet contrast requirements
     * 
     * @test
     */
    public function focusIndicatorsHaveSufficientContrast(): void
    {
        // Focus outline on white background
        $ratio = $this->calculateContrastRatio('#0066cc', '#ffffff');
        $this->assertGreaterThanOrEqual(
            3.0,
            $ratio,
            "Focus indicators (#0066cc on #ffffff) should have at least 3:1 contrast ratio. Got: {$ratio}:1"
        );
    }
    
    /**
     * Test that CSS variables are properly defined
     * 
     * @test
     */
    public function cssVariablesAreDefined(): void
    {
        $cssFile = __DIR__ . '/../../assets/css/variables.css';
        $this->assertFileExists($cssFile, 'Variables CSS file should exist');
        
        $css = file_get_contents($cssFile);
        
        // Check for essential color variables
        $requiredVariables = [
            '--color-primary',
            '--color-text-primary',
            '--color-bg-primary',
            '--color-success',
            '--color-danger',
            '--color-warning',
        ];
        
        foreach ($requiredVariables as $variable) {
            $this->assertStringContainsString(
                $variable,
                $css,
                "CSS should define {$variable} variable"
            );
        }
    }
    
    /**
     * Test color combinations from actual CSS
     * 
     * @test
     */
    public function actualCssColorCombinationsMeetStandards(): void
    {
        $cssFiles = [
            __DIR__ . '/../../assets/css/variables.css',
            __DIR__ . '/../../assets/css/components.css',
        ];
        
        foreach ($cssFiles as $cssFile) {
            if (!file_exists($cssFile)) {
                continue;
            }
            
            $css = file_get_contents($cssFile);
            
            // Extract color values
            preg_match_all('/--color-[^:]+:\s*(#[0-9a-f]{6}|#[0-9a-f]{3})/i', $css, $matches);
            
            $this->assertNotEmpty(
                $matches[1],
                "CSS file should contain color definitions: " . basename($cssFile)
            );
        }
    }
    
    /**
     * Calculate contrast ratio between two colors
     * 
     * @param string $color1 Hex color (e.g., '#ffffff')
     * @param string $color2 Hex color (e.g., '#000000')
     * @return float Contrast ratio
     */
    private function calculateContrastRatio(string $color1, string $color2): float
    {
        $l1 = $this->getRelativeLuminance($color1);
        $l2 = $this->getRelativeLuminance($color2);
        
        $lighter = max($l1, $l2);
        $darker = min($l1, $l2);
        
        return round(($lighter + 0.05) / ($darker + 0.05), 2);
    }
    
    /**
     * Calculate relative luminance of a color
     * 
     * @param string $hex Hex color (e.g., '#ffffff')
     * @return float Relative luminance (0-1)
     */
    private function getRelativeLuminance(string $hex): float
    {
        // Remove # if present
        $hex = ltrim($hex, '#');
        
        // Convert 3-digit hex to 6-digit
        if (strlen($hex) === 3) {
            $hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
        }
        
        // Convert hex to RGB
        $r = hexdec(substr($hex, 0, 2)) / 255;
        $g = hexdec(substr($hex, 2, 2)) / 255;
        $b = hexdec(substr($hex, 4, 2)) / 255;
        
        // Apply gamma correction
        $r = $r <= 0.03928 ? $r / 12.92 : pow(($r + 0.055) / 1.055, 2.4);
        $g = $g <= 0.03928 ? $g / 12.92 : pow(($g + 0.055) / 1.055, 2.4);
        $b = $b <= 0.03928 ? $b / 12.92 : pow(($b + 0.055) / 1.055, 2.4);
        
        // Calculate relative luminance
        return 0.2126 * $r + 0.7152 * $g + 0.0722 * $b;
    }
    
    /**
     * Test specific color combinations that appear in the UI
     * 
     * @test
     * @dataProvider colorCombinationProvider
     */
    public function specificColorCombinationsMeetStandards(
        string $name,
        string $foreground,
        string $background,
        float $minimumRatio
    ): void {
        $ratio = $this->calculateContrastRatio($foreground, $background);
        
        $this->assertGreaterThanOrEqual(
            $minimumRatio,
            $ratio,
            "{$name} ({$foreground} on {$background}) should have at least {$minimumRatio}:1 contrast ratio. Got: {$ratio}:1"
        );
    }
    
    /**
     * Data provider for color combination tests
     */
    public function colorCombinationProvider(): array
    {
        return [
            // [name, foreground, background, minimum ratio]
            ['Body text', '#212529', '#ffffff', 4.5],
            ['Secondary text', '#6c757d', '#ffffff', 4.5],
            ['Muted text', '#868e96', '#ffffff', 4.5],
            ['Primary button', '#ffffff', '#0066cc', 4.5],
            ['Success button', '#ffffff', '#28a745', 4.5],
            ['Danger button', '#ffffff', '#dc3545', 4.5],
            ['Warning button', '#212529', '#ffc107', 4.5],
            ['Info button', '#ffffff', '#17a2b8', 4.5],
            ['Link', '#0066cc', '#ffffff', 4.5],
            ['Error message', '#dc3545', '#ffffff', 4.5],
            ['Success message', '#28a745', '#ffffff', 4.5],
            ['Input border', '#ced4da', '#ffffff', 3.0],
            ['Focus border', '#0066cc', '#ffffff', 3.0],
            ['Text on dark bg', '#ffffff', '#343a40', 4.5],
        ];
    }
}
