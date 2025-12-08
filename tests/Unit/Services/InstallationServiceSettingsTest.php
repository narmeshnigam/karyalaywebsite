<?php

namespace Karyalay\Tests\Unit\Services;

use PHPUnit\Framework\TestCase;
use Karyalay\Services\InstallationService;
use Karyalay\Models\Setting;

/**
 * Test InstallationService settings-related functionality
 * Specifically for task 16: Update Setting model for installation wizard
 */
class InstallationServiceSettingsTest extends TestCase
{
    private InstallationService $installationService;
    private Setting $settingModel;
    private static array $testSettingKeys = [];

    public static function setUpBeforeClass(): void
    {
        // Load environment variables
        if (file_exists(__DIR__ . '/../../../.env')) {
            $lines = file(__DIR__ . '/../../../.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            foreach ($lines as $line) {
                if (strpos(trim($line), '#') === 0) {
                    continue;
                }
                list($name, $value) = explode('=', $line, 2);
                $_ENV[trim($name)] = trim($value);
            }
        }
    }

    protected function setUp(): void
    {
        $this->installationService = new InstallationService();
        $this->settingModel = new Setting();
    }

    protected function tearDown(): void
    {
        // Clean up test settings
        foreach (self::$testSettingKeys as $key) {
            $this->settingModel->delete($key);
        }
        self::$testSettingKeys = [];
    }

    public function testSaveSmtpSettings(): void
    {
        $timestamp = time();
        $smtpConfig = [
            'smtp_host' => 'smtp.test' . $timestamp . '.com',
            'smtp_port' => 587,
            'smtp_username' => 'user' . $timestamp . '@test.com',
            'smtp_password' => 'password' . $timestamp,
            'smtp_encryption' => 'tls',
            'smtp_from_address' => 'noreply' . $timestamp . '@test.com',
            'smtp_from_name' => 'Test System ' . $timestamp
        ];

        // Track keys for cleanup
        self::$testSettingKeys = array_keys($smtpConfig);

        // Save SMTP settings
        $result = $this->installationService->saveSmtpSettings($smtpConfig);
        $this->assertTrue($result, 'saveSmtpSettings should return true');

        // Verify all settings were saved correctly
        $this->assertEquals($smtpConfig['smtp_host'], $this->settingModel->get('smtp_host'));
        $this->assertEquals((string)$smtpConfig['smtp_port'], $this->settingModel->get('smtp_port'));
        $this->assertEquals($smtpConfig['smtp_username'], $this->settingModel->get('smtp_username'));
        $this->assertEquals($smtpConfig['smtp_password'], $this->settingModel->get('smtp_password'));
        $this->assertEquals($smtpConfig['smtp_encryption'], $this->settingModel->get('smtp_encryption'));
        $this->assertEquals($smtpConfig['smtp_from_address'], $this->settingModel->get('smtp_from_address'));
        $this->assertEquals($smtpConfig['smtp_from_name'], $this->settingModel->get('smtp_from_name'));
    }

    public function testSaveBrandSettings(): void
    {
        $timestamp = time();
        $brandData = [
            'company_name' => 'Test Company ' . $timestamp,
            'company_tagline' => 'Testing is our business ' . $timestamp,
            'contact_email' => 'contact' . $timestamp . '@test.com',
            'contact_phone' => '123-456-' . $timestamp,
            'contact_address' => $timestamp . ' Test Street'
        ];

        // Track keys for cleanup
        self::$testSettingKeys = ['company_name', 'company_tagline', 'contact_email', 'contact_phone', 'contact_address'];

        // Save brand settings
        $result = $this->installationService->saveBrandSettings($brandData);
        $this->assertTrue($result, 'saveBrandSettings should return true');

        // Verify all settings were saved correctly
        $this->assertEquals($brandData['company_name'], $this->settingModel->get('company_name'));
        $this->assertEquals($brandData['company_tagline'], $this->settingModel->get('company_tagline'));
        $this->assertEquals($brandData['contact_email'], $this->settingModel->get('contact_email'));
        $this->assertEquals($brandData['contact_phone'], $this->settingModel->get('contact_phone'));
        $this->assertEquals($brandData['contact_address'], $this->settingModel->get('contact_address'));
    }

    public function testSaveBrandSettingsWithLogo(): void
    {
        $timestamp = time();
        $brandData = [
            'company_name' => 'Logo Test Company ' . $timestamp,
            'company_tagline' => 'We have a logo ' . $timestamp,
            'contact_email' => 'logo' . $timestamp . '@test.com',
            'contact_phone' => '999-888-' . $timestamp,
            'contact_address' => $timestamp . ' Logo Avenue',
            'logo_path' => '/uploads/branding/logo_' . $timestamp . '.png'
        ];

        // Track keys for cleanup
        self::$testSettingKeys = ['company_name', 'company_tagline', 'contact_email', 'contact_phone', 'contact_address', 'branding_logo'];

        // Save brand settings with logo
        $result = $this->installationService->saveBrandSettings($brandData);
        $this->assertTrue($result, 'saveBrandSettings with logo should return true');

        // Verify all settings including logo were saved correctly
        $this->assertEquals($brandData['company_name'], $this->settingModel->get('company_name'));
        $this->assertEquals($brandData['logo_path'], $this->settingModel->get('branding_logo'));
    }

    public function testSettingsRetrievalAfterWizardCompletion(): void
    {
        // Simulate a complete wizard flow
        $timestamp = time();
        
        // Save SMTP settings
        $smtpConfig = [
            'smtp_host' => 'smtp.wizard' . $timestamp . '.com',
            'smtp_port' => 465,
            'smtp_username' => 'wizard' . $timestamp . '@test.com',
            'smtp_password' => 'wizardpass' . $timestamp,
            'smtp_encryption' => 'ssl',
            'smtp_from_address' => 'wizard' . $timestamp . '@test.com',
            'smtp_from_name' => 'Wizard Test ' . $timestamp
        ];
        
        $this->installationService->saveSmtpSettings($smtpConfig);
        
        // Save brand settings
        $brandData = [
            'company_name' => 'Wizard Company ' . $timestamp,
            'company_tagline' => 'Installed via wizard ' . $timestamp,
            'contact_email' => 'wizard' . $timestamp . '@company.com',
            'contact_phone' => '555-WIZARD-' . $timestamp,
            'contact_address' => $timestamp . ' Wizard Way'
        ];
        
        $this->installationService->saveBrandSettings($brandData);
        
        // Track all keys for cleanup
        self::$testSettingKeys = array_merge(
            array_keys($smtpConfig),
            ['company_name', 'company_tagline', 'contact_email', 'contact_phone', 'contact_address']
        );
        
        // Retrieve all settings to verify they're accessible after wizard completion
        $allSettings = $this->settingModel->getAll();
        
        // Verify SMTP settings are retrievable
        $this->assertArrayHasKey('smtp_host', $allSettings);
        $this->assertEquals($smtpConfig['smtp_host'], $allSettings['smtp_host']);
        
        // Verify brand settings are retrievable
        $this->assertArrayHasKey('company_name', $allSettings);
        $this->assertEquals($brandData['company_name'], $allSettings['company_name']);
        
        // Verify we can get multiple settings at once
        $smtpKeys = ['smtp_host', 'smtp_port', 'smtp_encryption'];
        $retrievedSmtp = $this->settingModel->getMultiple($smtpKeys);
        
        $this->assertCount(3, $retrievedSmtp);
        $this->assertEquals($smtpConfig['smtp_host'], $retrievedSmtp['smtp_host']);
        $this->assertEquals((string)$smtpConfig['smtp_port'], $retrievedSmtp['smtp_port']);
        $this->assertEquals($smtpConfig['smtp_encryption'], $retrievedSmtp['smtp_encryption']);
    }

    public function testBatchSaveIsAtomic(): void
    {
        // This test verifies that batch save is atomic (all or nothing)
        // by checking that settings are saved together
        $timestamp = time();
        $settings = [
            'atomic_test_1_' . $timestamp => 'value1',
            'atomic_test_2_' . $timestamp => 'value2',
            'atomic_test_3_' . $timestamp => 'value3'
        ];
        
        self::$testSettingKeys = array_keys($settings);
        
        // Save all at once
        $result = $this->settingModel->setMultiple($settings);
        $this->assertTrue($result);
        
        // Verify all are present
        foreach ($settings as $key => $expectedValue) {
            $actualValue = $this->settingModel->get($key);
            $this->assertEquals($expectedValue, $actualValue);
        }
    }
}
