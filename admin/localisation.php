<?php
/**
 * Localisation Settings Management
 * Admin page to configure currency, timezone, and regional settings
 */

require_once __DIR__ . '/../config/bootstrap.php';
require_once __DIR__ . '/../includes/auth_helpers.php';
require_once __DIR__ . '/../includes/admin_helpers.php';
require_once __DIR__ . '/../includes/template_helpers.php';

use Karyalay\Models\Setting;
use Karyalay\Helpers\Localisation;

// Start session and check admin authentication
startSecureSession();
require_admin();
require_permission('settings.localisation');

$settingModel = new Setting();
$success = null;
$error = null;

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Validate CSRF token
        if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
            throw new Exception('Invalid security token. Please try again.');
        }
        
        // Get form data
        $currencyCode = strtoupper(trim($_POST['currency_code'] ?? 'INR'));
        $currencySymbol = trim($_POST['currency_symbol'] ?? '');
        $currencyPosition = $_POST['currency_position'] ?? 'before';
        $currencyDecimalPlaces = (int)($_POST['currency_decimal_places'] ?? 2);
        $currencyDecimalSeparator = $_POST['currency_decimal_separator'] ?? '.';
        $currencyThousandSeparator = $_POST['currency_thousand_separator'] ?? ',';
        $timezone = $_POST['timezone'] ?? 'Asia/Kolkata';
        $countryCode = strtoupper(trim($_POST['country_code'] ?? 'IN'));
        $dateFormat = $_POST['date_format'] ?? 'd/m/Y';
        $timeFormat = $_POST['time_format'] ?? 'h:i A';
        
        // Validate required fields
        if (empty($currencyCode)) {
            throw new Exception('Currency code is required.');
        }
        
        // Auto-set currency symbol if not provided
        if (empty($currencySymbol)) {
            $currencySymbol = Localisation::getSymbolForCurrency($currencyCode);
        }
        
        // Validate timezone
        if (!in_array($timezone, array_keys(Localisation::getAvailableTimezones()))) {
            throw new Exception('Invalid timezone selected.');
        }
        
        // Prepare settings array
        $settings = [
            'locale_currency_code' => $currencyCode,
            'locale_currency_symbol' => $currencySymbol,
            'locale_currency_position' => $currencyPosition,
            'locale_currency_decimal_places' => (string)$currencyDecimalPlaces,
            'locale_currency_decimal_separator' => $currencyDecimalSeparator,
            'locale_currency_thousand_separator' => $currencyThousandSeparator,
            'locale_timezone' => $timezone,
            'locale_country_code' => $countryCode,
            'locale_date_format' => $dateFormat,
            'locale_time_format' => $timeFormat,
        ];
        
        // Save settings
        if ($settingModel->setMultiple($settings)) {
            $success = 'Localisation settings saved successfully!';
            
            // Refresh the localisation cache
            Localisation::getInstance()->refresh();
            
            // Log the action
            $currentUser = getCurrentUser();
            if ($currentUser) {
                error_log('Localisation settings updated by admin: ' . $currentUser['email']);
            }
        } else {
            throw new Exception('Failed to save localisation settings. Please try again.');
        }
        
    } catch (Exception $e) {
        $error = $e->getMessage();
        error_log('Localisation settings error: ' . $e->getMessage());
    }
}

// Generate new CSRF token
$_SESSION['csrf_token'] = bin2hex(random_bytes(32));

// Get current settings
$currentSettings = $settingModel->getMultiple([
    'locale_currency_code',
    'locale_currency_symbol',
    'locale_currency_position',
    'locale_currency_decimal_places',
    'locale_currency_decimal_separator',
    'locale_currency_thousand_separator',
    'locale_timezone',
    'locale_country_code',
    'locale_date_format',
    'locale_time_format',
]);

// Set defaults if not found
$currencyCode = $currentSettings['locale_currency_code'] ?? 'INR';
$currencySymbol = $currentSettings['locale_currency_symbol'] ?? '₹';
$currencyPosition = $currentSettings['locale_currency_position'] ?? 'before';
$currencyDecimalPlaces = $currentSettings['locale_currency_decimal_places'] ?? '2';
$currencyDecimalSeparator = $currentSettings['locale_currency_decimal_separator'] ?? '.';
$currencyThousandSeparator = $currentSettings['locale_currency_thousand_separator'] ?? ',';
$timezone = $currentSettings['locale_timezone'] ?? 'Asia/Kolkata';
$countryCode = $currentSettings['locale_country_code'] ?? 'IN';
$dateFormat = $currentSettings['locale_date_format'] ?? 'd/m/Y';
$timeFormat = $currentSettings['locale_time_format'] ?? 'h:i A';

// Get available options
$availableCurrencies = Localisation::getAvailableCurrencies();
$availableTimezones = Localisation::getAvailableTimezonesWithOffset();

// Page title
$page_title = 'Localisation';

// Include admin header
include __DIR__ . '/../templates/admin-header.php';
?>

<div class="admin-page-header">
    <div class="admin-page-header-content">
        <h1 class="admin-page-title">Localisation Settings</h1>
        <p class="admin-page-description">Configure currency, timezone, and regional display settings for your portal</p>
    </div>
    <div class="admin-page-header-actions">
        <a href="<?php echo get_app_base_url(); ?>/admin/dashboard.php" class="btn btn-secondary">← Back to Dashboard</a>
    </div>
</div>

<?php if ($success): ?>
    <div class="alert alert-success">
        <svg class="alert-icon" width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
        </svg>
        <span><?php echo htmlspecialchars($success); ?></span>
    </div>
<?php endif; ?>

<?php if ($error): ?>
    <div class="alert alert-danger">
        <svg class="alert-icon" width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
        </svg>
        <span><?php echo htmlspecialchars($error); ?></span>
    </div>
<?php endif; ?>

<div class="admin-card">
    <form method="POST" action="" class="admin-form">
        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
        
        <!-- Currency Settings -->
        <div class="form-section">
            <h2 class="form-section-title">Currency Settings</h2>
            <p class="form-section-description">Configure how prices and currency are displayed across your portal. The portal accepts payment in one currency at a time.</p>

            <div class="form-subsection">
                <h3 class="form-subsection-title">Primary Currency</h3>
                
                <div class="form-group">
                    <div class="form-row">
                        <div class="form-group form-group-half">
                            <label for="currency_code" class="form-label">Currency Code *</label>
                            <select id="currency_code" name="currency_code" class="form-input" required onchange="updateCurrencySymbol()">
                                <?php foreach ($availableCurrencies as $code => $symbol): ?>
                                    <option value="<?php echo htmlspecialchars($code); ?>" 
                                            data-symbol="<?php echo htmlspecialchars($symbol); ?>"
                                            <?php echo $currencyCode === $code ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($code); ?> (<?php echo htmlspecialchars($symbol); ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <span class="form-help">Select your primary currency for all transactions</span>
                        </div>
                        
                        <div class="form-group form-group-half">
                            <label for="currency_symbol" class="form-label">Currency Symbol *</label>
                            <input 
                                type="text" 
                                id="currency_symbol" 
                                name="currency_symbol" 
                                class="form-input" 
                                value="<?php echo htmlspecialchars($currencySymbol); ?>"
                                required
                                maxlength="10"
                            >
                            <span class="form-help">Symbol displayed with prices (e.g., ₹, $, €)</span>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="form-subsection">
                <h3 class="form-subsection-title">Display Format</h3>
                
                <div class="form-group">
                    <div class="form-row">
                        <div class="form-group form-group-half">
                            <label for="currency_position" class="form-label">Symbol Position</label>
                            <select id="currency_position" name="currency_position" class="form-input" onchange="updatePreview()">
                                <option value="before" <?php echo $currencyPosition === 'before' ? 'selected' : ''; ?>>Before amount (₹ 1,000)</option>
                                <option value="after" <?php echo $currencyPosition === 'after' ? 'selected' : ''; ?>>After amount (1,000 ₹)</option>
                            </select>
                        </div>
                        
                        <div class="form-group form-group-half">
                            <label for="currency_decimal_places" class="form-label">Decimal Places</label>
                            <select id="currency_decimal_places" name="currency_decimal_places" class="form-input" onchange="updatePreview()">
                                <option value="0" <?php echo $currencyDecimalPlaces === '0' ? 'selected' : ''; ?>>0 (1,000)</option>
                                <option value="2" <?php echo $currencyDecimalPlaces === '2' ? 'selected' : ''; ?>>2 (1,000.00)</option>
                                <option value="3" <?php echo $currencyDecimalPlaces === '3' ? 'selected' : ''; ?>>3 (1,000.000)</option>
                            </select>
                        </div>
                    </div>
                </div>
                
                <div class="form-group">
                    <div class="form-row">
                        <div class="form-group form-group-half">
                            <label for="currency_decimal_separator" class="form-label">Decimal Separator</label>
                            <select id="currency_decimal_separator" name="currency_decimal_separator" class="form-input" onchange="updatePreview()">
                                <option value="." <?php echo $currencyDecimalSeparator === '.' ? 'selected' : ''; ?>>Period (.)</option>
                                <option value="," <?php echo $currencyDecimalSeparator === ',' ? 'selected' : ''; ?>>Comma (,)</option>
                            </select>
                        </div>
                        
                        <div class="form-group form-group-half">
                            <label for="currency_thousand_separator" class="form-label">Thousand Separator</label>
                            <select id="currency_thousand_separator" name="currency_thousand_separator" class="form-input" onchange="updatePreview()">
                                <option value="," <?php echo $currencyThousandSeparator === ',' ? 'selected' : ''; ?>>Comma (,)</option>
                                <option value="." <?php echo $currencyThousandSeparator === '.' ? 'selected' : ''; ?>>Period (.)</option>
                                <option value=" " <?php echo $currencyThousandSeparator === ' ' ? 'selected' : ''; ?>>Space ( )</option>
                                <option value="" <?php echo $currencyThousandSeparator === '' ? 'selected' : ''; ?>>None</option>
                            </select>
                        </div>
                    </div>
                </div>
                
                <!-- Price Preview -->
                <div class="preview-box">
                    <span class="preview-label">Price Preview:</span>
                    <span class="preview-value" id="price-preview">₹ 1,234.56</span>
                </div>
            </div>
        </div>
        
        <!-- Regional Settings -->
        <div class="form-section">
            <h2 class="form-section-title">Regional Settings</h2>
            <p class="form-section-description">Configure timezone and country settings for your portal</p>
            
            <div class="form-subsection">
                <h3 class="form-subsection-title">Location Settings</h3>
                
                <div class="form-group">
                    <div class="form-row">
                        <div class="form-group form-group-half">
                            <label for="timezone" class="form-label">Timezone *</label>
                            <select id="timezone" name="timezone" class="form-input" required>
                                <?php foreach ($availableTimezones as $tz => $label): ?>
                                    <option value="<?php echo htmlspecialchars($tz); ?>" <?php echo $timezone === $tz ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($label); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <span class="form-help">Used for displaying dates and times</span>
                        </div>
                        
                        <div class="form-group form-group-half">
                            <label for="country_code" class="form-label">Country</label>
                            <select id="country_code" name="country_code" class="form-input" onchange="updateIsdCode()">
                                <?php 
                                $countries = Localisation::getCountriesWithIsdCodes();
                                foreach ($countries as $code => $label): 
                                ?>
                                    <option value="<?php echo htmlspecialchars($code); ?>" <?php echo $countryCode === $code ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($label); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <span class="form-help">Country for phone number ISD code prefix</span>
                        </div>
                    </div>
                </div>
                
                <!-- ISD Code Preview -->
                <div class="preview-box isd-preview-box">
                    <div class="isd-preview-content">
                        <span class="preview-label">Phone Number Format:</span>
                        <div class="isd-preview-demo">
                            <span class="isd-flag" id="isd-flag"><?php echo get_country_flag_emoji($countryCode); ?></span>
                            <span class="isd-code-display" id="isd-code-display"><?php echo htmlspecialchars(Localisation::getAvailableIsdCodes()[$countryCode] ?? '+91'); ?></span>
                            <span class="isd-number-placeholder">XXXXXXXXXX</span>
                        </div>
                    </div>
                    <p class="isd-preview-note">This ISD code will be auto-selected and locked for all phone number inputs across the website.</p>
                </div>
            </div>
        </div>
        
        <!-- Date & Time Format -->
        <div class="form-section">
            <h2 class="form-section-title">Date & Time Format</h2>
            <p class="form-section-description">Configure how dates and times are displayed</p>
            
            <div class="form-subsection">
                <h3 class="form-subsection-title">Format Options</h3>
                
                <div class="form-group">
                    <div class="form-row">
                        <div class="form-group form-group-half">
                            <label for="date_format" class="form-label">Date Format</label>
                            <select id="date_format" name="date_format" class="form-input" onchange="updateDatePreview()">
                                <option value="d/m/Y" <?php echo $dateFormat === 'd/m/Y' ? 'selected' : ''; ?>>DD/MM/YYYY (31/12/2024)</option>
                                <option value="m/d/Y" <?php echo $dateFormat === 'm/d/Y' ? 'selected' : ''; ?>>MM/DD/YYYY (12/31/2024)</option>
                                <option value="Y-m-d" <?php echo $dateFormat === 'Y-m-d' ? 'selected' : ''; ?>>YYYY-MM-DD (2024-12-31)</option>
                                <option value="d-m-Y" <?php echo $dateFormat === 'd-m-Y' ? 'selected' : ''; ?>>DD-MM-YYYY (31-12-2024)</option>
                                <option value="d M Y" <?php echo $dateFormat === 'd M Y' ? 'selected' : ''; ?>>DD Mon YYYY (31 Dec 2024)</option>
                                <option value="M d, Y" <?php echo $dateFormat === 'M d, Y' ? 'selected' : ''; ?>>Mon DD, YYYY (Dec 31, 2024)</option>
                                <option value="F d, Y" <?php echo $dateFormat === 'F d, Y' ? 'selected' : ''; ?>>Month DD, YYYY (December 31, 2024)</option>
                            </select>
                        </div>
                        
                        <div class="form-group form-group-half">
                            <label for="time_format" class="form-label">Time Format</label>
                            <select id="time_format" name="time_format" class="form-input" onchange="updateDatePreview()">
                                <option value="h:i A" <?php echo $timeFormat === 'h:i A' ? 'selected' : ''; ?>>12-hour (02:30 PM)</option>
                                <option value="H:i" <?php echo $timeFormat === 'H:i' ? 'selected' : ''; ?>>24-hour (14:30)</option>
                                <option value="h:i:s A" <?php echo $timeFormat === 'h:i:s A' ? 'selected' : ''; ?>>12-hour with seconds (02:30:45 PM)</option>
                                <option value="H:i:s" <?php echo $timeFormat === 'H:i:s' ? 'selected' : ''; ?>>24-hour with seconds (14:30:45)</option>
                            </select>
                        </div>
                    </div>
                </div>
                
                <!-- Date/Time Preview -->
                <div class="preview-box">
                    <span class="preview-label">Date/Time Preview:</span>
                    <span class="preview-value" id="datetime-preview"><?php echo date($dateFormat . ' ' . $timeFormat); ?></span>
                </div>
            </div>
        </div>
        
        <!-- Form Actions -->
        <div class="form-actions">
            <button type="submit" class="btn btn-primary">
                <svg class="btn-icon" width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                </svg>
                Save Settings
            </button>
            <a href="<?php echo get_app_base_url(); ?>/admin/dashboard.php" class="btn btn-secondary">Cancel</a>
        </div>
    </form>
</div>

<!-- Info Notice -->
<div class="alert alert-info" style="margin-top: 1.5rem;">
    <svg class="alert-icon" width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
    </svg>
    <div>
        <strong>Note:</strong> These settings affect how currency and dates are displayed across the entire portal, including pricing pages, checkout, orders, and invoices. Changes take effect immediately.
    </div>
</div>

<style>
.admin-page-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: var(--spacing-6);
    gap: var(--spacing-4);
}

.admin-page-header-content {
    flex: 1;
}

.admin-page-title {
    font-size: var(--font-size-2xl);
    font-weight: var(--font-weight-bold);
    color: var(--color-gray-900);
    margin: 0 0 var(--spacing-2) 0;
}

.admin-page-description {
    font-size: var(--font-size-base);
    color: var(--color-gray-600);
    margin: 0;
}

.admin-page-header-actions {
    display: flex;
    gap: var(--spacing-3);
}

.admin-form {
    padding: var(--spacing-6);
}

.form-section {
    margin-bottom: var(--spacing-8);
}

.form-section:last-child {
    margin-bottom: 0;
}

.form-section-title {
    font-size: var(--font-size-lg);
    font-weight: var(--font-weight-semibold);
    color: var(--color-gray-900);
    margin: 0 0 var(--spacing-4) 0;
    padding-bottom: var(--spacing-3);
    border-bottom: 1px solid var(--color-gray-200);
}

.form-section-description {
    font-size: var(--font-size-sm);
    color: var(--color-gray-600);
    margin: -0.5rem 0 1.5rem 0;
}

.form-subsection {
    margin-bottom: var(--spacing-6);
}

.form-subsection:last-child {
    margin-bottom: 0;
}

.form-subsection-title {
    font-size: var(--font-size-base);
    font-weight: var(--font-weight-semibold);
    color: var(--color-gray-800);
    margin: 0 0 var(--spacing-4) 0;
}

.form-row {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: var(--spacing-4);
}

.form-group {
    margin-bottom: var(--spacing-4);
}

.form-group:last-child {
    margin-bottom: 0;
}

.form-group-half {
    flex: 1;
}

.form-label {
    display: block;
    font-size: var(--font-size-sm);
    font-weight: var(--font-weight-semibold);
    color: var(--color-gray-700);
    margin-bottom: var(--spacing-2);
}

.form-help {
    font-size: var(--font-size-sm);
    color: var(--color-gray-600);
    margin: var(--spacing-1) 0 0 0;
}

.preview-box {
    background: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 100%);
    border: 2px solid var(--color-primary);
    border-radius: var(--radius-lg);
    padding: var(--spacing-4) var(--spacing-6);
    display: flex;
    align-items: center;
    gap: var(--spacing-4);
    margin-top: var(--spacing-4);
}

.isd-preview-box {
    flex-direction: column;
    align-items: flex-start;
}

.isd-preview-content {
    display: flex;
    align-items: center;
    gap: var(--spacing-4);
    width: 100%;
}

.isd-preview-demo {
    display: flex;
    align-items: center;
    gap: var(--spacing-2);
    background: var(--color-white);
    padding: var(--spacing-3) var(--spacing-4);
    border-radius: var(--radius-md);
    border: 1px solid var(--color-gray-200);
}

.isd-flag {
    font-size: 1.5rem;
    line-height: 1;
}

.isd-code-display {
    font-family: var(--font-mono, monospace);
    font-weight: var(--font-weight-bold);
    font-size: var(--font-size-lg);
    color: var(--color-primary);
    background: var(--color-gray-100);
    padding: var(--spacing-1) var(--spacing-2);
    border-radius: var(--radius-sm);
}

.isd-number-placeholder {
    color: var(--color-gray-400);
    font-family: var(--font-mono, monospace);
    letter-spacing: 0.1em;
}

.isd-preview-note {
    font-size: var(--font-size-sm);
    color: var(--color-gray-600);
    margin: var(--spacing-2) 0 0 0;
    font-style: italic;
}

.preview-label {
    font-size: var(--font-size-sm);
    font-weight: var(--font-weight-medium);
    color: var(--color-gray-600);
}

.preview-value {
    font-size: var(--font-size-xl);
    font-weight: var(--font-weight-bold);
    color: var(--color-primary);
}

.form-actions {
    display: flex;
    gap: var(--spacing-3);
    padding-top: var(--spacing-6);
    border-top: 1px solid var(--color-gray-200);
}

@media (max-width: 768px) {
    .admin-page-header {
        flex-direction: column;
    }
    
    .form-row {
        grid-template-columns: 1fr;
    }
}
</style>

<script>
// ISD codes mapping for JavaScript
const isdCodes = <?php echo json_encode(Localisation::getAvailableIsdCodes()); ?>;

function updateCurrencySymbol() {
    const select = document.getElementById('currency_code');
    const symbolInput = document.getElementById('currency_symbol');
    const selectedOption = select.options[select.selectedIndex];
    const symbol = selectedOption.getAttribute('data-symbol');
    
    if (symbol) {
        symbolInput.value = symbol;
    }
    
    updatePreview();
}

function updateIsdCode() {
    const select = document.getElementById('country_code');
    const countryCode = select.value;
    const isdCode = isdCodes[countryCode] || '+1';
    
    // Update ISD code display
    const isdDisplay = document.getElementById('isd-code-display');
    if (isdDisplay) {
        isdDisplay.textContent = isdCode;
    }
    
    // Update flag emoji
    const flagDisplay = document.getElementById('isd-flag');
    if (flagDisplay) {
        flagDisplay.textContent = getCountryFlagEmoji(countryCode);
    }
}

function getCountryFlagEmoji(countryCode) {
    if (!countryCode || countryCode.length !== 2) return '🏳️';
    const firstChar = countryCode.charCodeAt(0) - 65 + 0x1F1E6;
    const secondChar = countryCode.charCodeAt(1) - 65 + 0x1F1E6;
    return String.fromCodePoint(firstChar) + String.fromCodePoint(secondChar);
}

function updatePreview() {
    const symbol = document.getElementById('currency_symbol').value || '₹';
    const position = document.getElementById('currency_position').value;
    const decimals = parseInt(document.getElementById('currency_decimal_places').value) || 0;
    const decSep = document.getElementById('currency_decimal_separator').value;
    const thousandSep = document.getElementById('currency_thousand_separator').value;
    
    // Format sample number
    let amount = 1234.56;
    let formatted = amount.toFixed(decimals);
    
    // Split into integer and decimal parts
    let parts = formatted.split('.');
    let intPart = parts[0];
    let decPart = parts[1] || '';
    
    // Add thousand separators
    if (thousandSep) {
        intPart = intPart.replace(/\B(?=(\d{3})+(?!\d))/g, thousandSep);
    }
    
    // Combine with decimal separator
    formatted = decPart ? intPart + decSep + decPart : intPart;
    
    // Add currency symbol
    let preview = position === 'after' ? formatted + ' ' + symbol : symbol + ' ' + formatted;
    
    document.getElementById('price-preview').textContent = preview;
}

function updateDatePreview() {
    const dateFormat = document.getElementById('date_format').value;
    const timeFormat = document.getElementById('time_format').value;
    
    // Create sample date/time strings based on format
    const now = new Date();
    const day = now.getDate().toString().padStart(2, '0');
    const month = (now.getMonth() + 1).toString().padStart(2, '0');
    const year = now.getFullYear();
    const hours24 = now.getHours();
    const hours12 = hours24 % 12 || 12;
    const minutes = now.getMinutes().toString().padStart(2, '0');
    const seconds = now.getSeconds().toString().padStart(2, '0');
    const ampm = hours24 >= 12 ? 'PM' : 'AM';
    
    const monthNames = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
    const monthNamesFull = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];
    
    let dateStr = '';
    switch (dateFormat) {
        case 'd/m/Y': dateStr = `${day}/${month}/${year}`; break;
        case 'm/d/Y': dateStr = `${month}/${day}/${year}`; break;
        case 'Y-m-d': dateStr = `${year}-${month}-${day}`; break;
        case 'd-m-Y': dateStr = `${day}-${month}-${year}`; break;
        case 'd M Y': dateStr = `${day} ${monthNames[now.getMonth()]} ${year}`; break;
        case 'M d, Y': dateStr = `${monthNames[now.getMonth()]} ${day}, ${year}`; break;
        case 'F d, Y': dateStr = `${monthNamesFull[now.getMonth()]} ${day}, ${year}`; break;
        default: dateStr = `${day}/${month}/${year}`;
    }
    
    let timeStr = '';
    switch (timeFormat) {
        case 'h:i A': timeStr = `${hours12.toString().padStart(2, '0')}:${minutes} ${ampm}`; break;
        case 'H:i': timeStr = `${hours24.toString().padStart(2, '0')}:${minutes}`; break;
        case 'h:i:s A': timeStr = `${hours12.toString().padStart(2, '0')}:${minutes}:${seconds} ${ampm}`; break;
        case 'H:i:s': timeStr = `${hours24.toString().padStart(2, '0')}:${minutes}:${seconds}`; break;
        default: timeStr = `${hours12.toString().padStart(2, '0')}:${minutes} ${ampm}`;
    }
    
    document.getElementById('datetime-preview').textContent = dateStr + ' ' + timeStr;
}

// Initialize previews on page load
document.addEventListener('DOMContentLoaded', function() {
    updatePreview();
    updateDatePreview();
    
    // Update symbol when typing
    document.getElementById('currency_symbol').addEventListener('input', updatePreview);
});
</script>

<?php include __DIR__ . '/../templates/admin-footer.php'; ?>
