<?php

namespace Karyalay\Tests\Unit\Models;

use PHPUnit\Framework\TestCase;
use Karyalay\Models\Setting;
use Karyalay\Database\Connection;

class SettingTest extends TestCase
{
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

    public function testSetAndGetSetting(): void
    {
        $key = 'test_setting_' . time();
        $value = 'test_value_' . time();
        
        self::$testSettingKeys[] = $key;

        // Set the setting
        $result = $this->settingModel->set($key, $value);
        $this->assertTrue($result);

        // Get the setting
        $retrievedValue = $this->settingModel->get($key);
        $this->assertEquals($value, $retrievedValue);
    }

    public function testGetNonExistentSetting(): void
    {
        $key = 'nonexistent_setting_' . time();
        $default = 'default_value';

        // Get non-existent setting with default
        $value = $this->settingModel->get($key, $default);
        $this->assertEquals($default, $value);
    }

    public function testUpdateExistingSetting(): void
    {
        $key = 'update_test_' . time();
        $initialValue = 'initial_value';
        $updatedValue = 'updated_value';
        
        self::$testSettingKeys[] = $key;

        // Set initial value
        $this->settingModel->set($key, $initialValue);
        $this->assertEquals($initialValue, $this->settingModel->get($key));

        // Update the value
        $this->settingModel->set($key, $updatedValue);
        $this->assertEquals($updatedValue, $this->settingModel->get($key));
    }

    public function testSetMultipleSettings(): void
    {
        $timestamp = time();
        $settings = [
            'batch_test_1_' . $timestamp => 'value1',
            'batch_test_2_' . $timestamp => 'value2',
            'batch_test_3_' . $timestamp => 'value3'
        ];
        
        foreach (array_keys($settings) as $key) {
            self::$testSettingKeys[] = $key;
        }

        // Set multiple settings at once
        $result = $this->settingModel->setMultiple($settings);
        $this->assertTrue($result);

        // Verify all settings were saved
        foreach ($settings as $key => $expectedValue) {
            $actualValue = $this->settingModel->get($key);
            $this->assertEquals($expectedValue, $actualValue);
        }
    }

    public function testSetMultipleWithEmptyArray(): void
    {
        // Should return true for empty array
        $result = $this->settingModel->setMultiple([]);
        $this->assertTrue($result);
    }

    public function testGetMultipleSettings(): void
    {
        $timestamp = time();
        $settings = [
            'multi_get_1_' . $timestamp => 'value1',
            'multi_get_2_' . $timestamp => 'value2',
            'multi_get_3_' . $timestamp => 'value3'
        ];
        
        foreach (array_keys($settings) as $key) {
            self::$testSettingKeys[] = $key;
        }

        // Set the settings
        foreach ($settings as $key => $value) {
            $this->settingModel->set($key, $value);
        }

        // Get multiple settings
        $keys = array_keys($settings);
        $retrieved = $this->settingModel->getMultiple($keys);

        $this->assertIsArray($retrieved);
        $this->assertCount(count($settings), $retrieved);
        
        foreach ($settings as $key => $expectedValue) {
            $this->assertArrayHasKey($key, $retrieved);
            $this->assertEquals($expectedValue, $retrieved[$key]);
        }
    }

    public function testDeleteSetting(): void
    {
        $key = 'delete_test_' . time();
        $value = 'delete_value';

        // Set the setting
        $this->settingModel->set($key, $value);
        $this->assertEquals($value, $this->settingModel->get($key));

        // Delete the setting
        $result = $this->settingModel->delete($key);
        $this->assertTrue($result);

        // Verify deletion
        $retrievedValue = $this->settingModel->get($key, 'default');
        $this->assertEquals('default', $retrievedValue);
    }

    public function testGetAllSettings(): void
    {
        $timestamp = time();
        $testSettings = [
            'getall_test_1_' . $timestamp => 'value1',
            'getall_test_2_' . $timestamp => 'value2'
        ];
        
        foreach (array_keys($testSettings) as $key) {
            self::$testSettingKeys[] = $key;
        }

        // Set test settings
        foreach ($testSettings as $key => $value) {
            $this->settingModel->set($key, $value);
        }

        // Get all settings
        $allSettings = $this->settingModel->getAll();
        
        $this->assertIsArray($allSettings);
        
        // Verify our test settings are in the result
        foreach ($testSettings as $key => $expectedValue) {
            $this->assertArrayHasKey($key, $allSettings);
            $this->assertEquals($expectedValue, $allSettings[$key]);
        }
    }

    public function testBatchSaveForWizard(): void
    {
        // Simulate SMTP settings save during wizard
        $timestamp = time();
        $smtpSettings = [
            'smtp_host_test_' . $timestamp => 'smtp.example.com',
            'smtp_port_test_' . $timestamp => '587',
            'smtp_username_test_' . $timestamp => 'user@example.com',
            'smtp_password_test_' . $timestamp => 'password123',
            'smtp_encryption_test_' . $timestamp => 'tls',
            'smtp_from_address_test_' . $timestamp => 'noreply@example.com',
            'smtp_from_name_test_' . $timestamp => 'Test System'
        ];
        
        foreach (array_keys($smtpSettings) as $key) {
            self::$testSettingKeys[] = $key;
        }

        // Save all settings at once
        $result = $this->settingModel->setMultiple($smtpSettings);
        $this->assertTrue($result);

        // Verify all settings were saved correctly
        foreach ($smtpSettings as $key => $expectedValue) {
            $actualValue = $this->settingModel->get($key);
            $this->assertEquals($expectedValue, $actualValue, "Setting $key was not saved correctly");
        }
    }

    public function testBrandSettingsSave(): void
    {
        // Simulate brand settings save during wizard
        $timestamp = time();
        $brandSettings = [
            'company_name_test_' . $timestamp => 'Test Company',
            'company_tagline_test_' . $timestamp => 'We Test Things',
            'contact_email_test_' . $timestamp => 'contact@test.com',
            'contact_phone_test_' . $timestamp => '1234567890',
            'contact_address_test_' . $timestamp => '123 Test St',
            'branding_logo_test_' . $timestamp => '/uploads/branding/logo.png'
        ];
        
        foreach (array_keys($brandSettings) as $key) {
            self::$testSettingKeys[] = $key;
        }

        // Save all settings at once
        $result = $this->settingModel->setMultiple($brandSettings);
        $this->assertTrue($result);

        // Verify all settings were saved correctly
        foreach ($brandSettings as $key => $expectedValue) {
            $actualValue = $this->settingModel->get($key);
            $this->assertEquals($expectedValue, $actualValue, "Setting $key was not saved correctly");
        }
    }
}
