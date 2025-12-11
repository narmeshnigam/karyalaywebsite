<?php
/**
 * Localisation Helper Class
 * Centralized management of locale settings including currency, timezone, and country
 */

namespace Karyalay\Helpers;

use Karyalay\Models\Setting;

class Localisation
{
    private static ?self $instance = null;
    private Setting $settingModel;
    private array $cache = [];
    
    // Country ISD codes mapping (ISO 3166-1 alpha-2 => ISD code)
    private const COUNTRY_ISD_CODES = [
        'AF' => '+93',   // Afghanistan
        'AL' => '+355',  // Albania
        'DZ' => '+213',  // Algeria
        'AD' => '+376',  // Andorra
        'AO' => '+244',  // Angola
        'AR' => '+54',   // Argentina
        'AM' => '+374',  // Armenia
        'AU' => '+61',   // Australia
        'AT' => '+43',   // Austria
        'AZ' => '+994',  // Azerbaijan
        'BH' => '+973',  // Bahrain
        'BD' => '+880',  // Bangladesh
        'BY' => '+375',  // Belarus
        'BE' => '+32',   // Belgium
        'BZ' => '+501',  // Belize
        'BJ' => '+229',  // Benin
        'BT' => '+975',  // Bhutan
        'BO' => '+591',  // Bolivia
        'BA' => '+387',  // Bosnia and Herzegovina
        'BW' => '+267',  // Botswana
        'BR' => '+55',   // Brazil
        'BN' => '+673',  // Brunei
        'BG' => '+359',  // Bulgaria
        'BF' => '+226',  // Burkina Faso
        'BI' => '+257',  // Burundi
        'KH' => '+855',  // Cambodia
        'CM' => '+237',  // Cameroon
        'CA' => '+1',    // Canada
        'CV' => '+238',  // Cape Verde
        'CF' => '+236',  // Central African Republic
        'TD' => '+235',  // Chad
        'CL' => '+56',   // Chile
        'CN' => '+86',   // China
        'CO' => '+57',   // Colombia
        'KM' => '+269',  // Comoros
        'CG' => '+242',  // Congo
        'CD' => '+243',  // Congo (DRC)
        'CR' => '+506',  // Costa Rica
        'HR' => '+385',  // Croatia
        'CU' => '+53',   // Cuba
        'CY' => '+357',  // Cyprus
        'CZ' => '+420',  // Czech Republic
        'DK' => '+45',   // Denmark
        'DJ' => '+253',  // Djibouti
        'DO' => '+1',    // Dominican Republic
        'EC' => '+593',  // Ecuador
        'EG' => '+20',   // Egypt
        'SV' => '+503',  // El Salvador
        'GQ' => '+240',  // Equatorial Guinea
        'ER' => '+291',  // Eritrea
        'EE' => '+372',  // Estonia
        'ET' => '+251',  // Ethiopia
        'FJ' => '+679',  // Fiji
        'FI' => '+358',  // Finland
        'FR' => '+33',   // France
        'GA' => '+241',  // Gabon
        'GM' => '+220',  // Gambia
        'GE' => '+995',  // Georgia
        'DE' => '+49',   // Germany
        'GH' => '+233',  // Ghana
        'GR' => '+30',   // Greece
        'GT' => '+502',  // Guatemala
        'GN' => '+224',  // Guinea
        'GW' => '+245',  // Guinea-Bissau
        'GY' => '+592',  // Guyana
        'HT' => '+509',  // Haiti
        'HN' => '+504',  // Honduras
        'HK' => '+852',  // Hong Kong
        'HU' => '+36',   // Hungary
        'IS' => '+354',  // Iceland
        'IN' => '+91',   // India
        'ID' => '+62',   // Indonesia
        'IR' => '+98',   // Iran
        'IQ' => '+964',  // Iraq
        'IE' => '+353',  // Ireland
        'IL' => '+972',  // Israel
        'IT' => '+39',   // Italy
        'CI' => '+225',  // Ivory Coast
        'JM' => '+1',    // Jamaica
        'JP' => '+81',   // Japan
        'JO' => '+962',  // Jordan
        'KZ' => '+7',    // Kazakhstan
        'KE' => '+254',  // Kenya
        'KI' => '+686',  // Kiribati
        'KP' => '+850',  // North Korea
        'KR' => '+82',   // South Korea
        'KW' => '+965',  // Kuwait
        'KG' => '+996',  // Kyrgyzstan
        'LA' => '+856',  // Laos
        'LV' => '+371',  // Latvia
        'LB' => '+961',  // Lebanon
        'LS' => '+266',  // Lesotho
        'LR' => '+231',  // Liberia
        'LY' => '+218',  // Libya
        'LI' => '+423',  // Liechtenstein
        'LT' => '+370',  // Lithuania
        'LU' => '+352',  // Luxembourg
        'MO' => '+853',  // Macau
        'MK' => '+389',  // North Macedonia
        'MG' => '+261',  // Madagascar
        'MW' => '+265',  // Malawi
        'MY' => '+60',   // Malaysia
        'MV' => '+960',  // Maldives
        'ML' => '+223',  // Mali
        'MT' => '+356',  // Malta
        'MH' => '+692',  // Marshall Islands
        'MR' => '+222',  // Mauritania
        'MU' => '+230',  // Mauritius
        'MX' => '+52',   // Mexico
        'FM' => '+691',  // Micronesia
        'MD' => '+373',  // Moldova
        'MC' => '+377',  // Monaco
        'MN' => '+976',  // Mongolia
        'ME' => '+382',  // Montenegro
        'MA' => '+212',  // Morocco
        'MZ' => '+258',  // Mozambique
        'MM' => '+95',   // Myanmar
        'NA' => '+264',  // Namibia
        'NR' => '+674',  // Nauru
        'NP' => '+977',  // Nepal
        'NL' => '+31',   // Netherlands
        'NZ' => '+64',   // New Zealand
        'NI' => '+505',  // Nicaragua
        'NE' => '+227',  // Niger
        'NG' => '+234',  // Nigeria
        'NO' => '+47',   // Norway
        'OM' => '+968',  // Oman
        'PK' => '+92',   // Pakistan
        'PW' => '+680',  // Palau
        'PS' => '+970',  // Palestine
        'PA' => '+507',  // Panama
        'PG' => '+675',  // Papua New Guinea
        'PY' => '+595',  // Paraguay
        'PE' => '+51',   // Peru
        'PH' => '+63',   // Philippines
        'PL' => '+48',   // Poland
        'PT' => '+351',  // Portugal
        'PR' => '+1',    // Puerto Rico
        'QA' => '+974',  // Qatar
        'RO' => '+40',   // Romania
        'RU' => '+7',    // Russia
        'RW' => '+250',  // Rwanda
        'SA' => '+966',  // Saudi Arabia
        'SN' => '+221',  // Senegal
        'RS' => '+381',  // Serbia
        'SC' => '+248',  // Seychelles
        'SL' => '+232',  // Sierra Leone
        'SG' => '+65',   // Singapore
        'SK' => '+421',  // Slovakia
        'SI' => '+386',  // Slovenia
        'SB' => '+677',  // Solomon Islands
        'SO' => '+252',  // Somalia
        'ZA' => '+27',   // South Africa
        'SS' => '+211',  // South Sudan
        'ES' => '+34',   // Spain
        'LK' => '+94',   // Sri Lanka
        'SD' => '+249',  // Sudan
        'SR' => '+597',  // Suriname
        'SZ' => '+268',  // Eswatini
        'SE' => '+46',   // Sweden
        'CH' => '+41',   // Switzerland
        'SY' => '+963',  // Syria
        'TW' => '+886',  // Taiwan
        'TJ' => '+992',  // Tajikistan
        'TZ' => '+255',  // Tanzania
        'TH' => '+66',   // Thailand
        'TL' => '+670',  // Timor-Leste
        'TG' => '+228',  // Togo
        'TO' => '+676',  // Tonga
        'TT' => '+1',    // Trinidad and Tobago
        'TN' => '+216',  // Tunisia
        'TR' => '+90',   // Turkey
        'TM' => '+993',  // Turkmenistan
        'TV' => '+688',  // Tuvalu
        'UG' => '+256',  // Uganda
        'UA' => '+380',  // Ukraine
        'AE' => '+971',  // United Arab Emirates
        'GB' => '+44',   // United Kingdom
        'US' => '+1',    // United States
        'UY' => '+598',  // Uruguay
        'UZ' => '+998',  // Uzbekistan
        'VU' => '+678',  // Vanuatu
        'VA' => '+379',  // Vatican City
        'VE' => '+58',   // Venezuela
        'VN' => '+84',   // Vietnam
        'YE' => '+967',  // Yemen
        'ZM' => '+260',  // Zambia
        'ZW' => '+263',  // Zimbabwe
    ];
    
    // Country names mapping (ISO 3166-1 alpha-2 => Country Name)
    private const COUNTRY_NAMES = [
        'AF' => 'Afghanistan',
        'AL' => 'Albania',
        'DZ' => 'Algeria',
        'AD' => 'Andorra',
        'AO' => 'Angola',
        'AR' => 'Argentina',
        'AM' => 'Armenia',
        'AU' => 'Australia',
        'AT' => 'Austria',
        'AZ' => 'Azerbaijan',
        'BH' => 'Bahrain',
        'BD' => 'Bangladesh',
        'BY' => 'Belarus',
        'BE' => 'Belgium',
        'BZ' => 'Belize',
        'BJ' => 'Benin',
        'BT' => 'Bhutan',
        'BO' => 'Bolivia',
        'BA' => 'Bosnia and Herzegovina',
        'BW' => 'Botswana',
        'BR' => 'Brazil',
        'BN' => 'Brunei',
        'BG' => 'Bulgaria',
        'BF' => 'Burkina Faso',
        'BI' => 'Burundi',
        'KH' => 'Cambodia',
        'CM' => 'Cameroon',
        'CA' => 'Canada',
        'CV' => 'Cape Verde',
        'CF' => 'Central African Republic',
        'TD' => 'Chad',
        'CL' => 'Chile',
        'CN' => 'China',
        'CO' => 'Colombia',
        'KM' => 'Comoros',
        'CG' => 'Congo',
        'CD' => 'Congo (DRC)',
        'CR' => 'Costa Rica',
        'HR' => 'Croatia',
        'CU' => 'Cuba',
        'CY' => 'Cyprus',
        'CZ' => 'Czech Republic',
        'DK' => 'Denmark',
        'DJ' => 'Djibouti',
        'DO' => 'Dominican Republic',
        'EC' => 'Ecuador',
        'EG' => 'Egypt',
        'SV' => 'El Salvador',
        'GQ' => 'Equatorial Guinea',
        'ER' => 'Eritrea',
        'EE' => 'Estonia',
        'ET' => 'Ethiopia',
        'FJ' => 'Fiji',
        'FI' => 'Finland',
        'FR' => 'France',
        'GA' => 'Gabon',
        'GM' => 'Gambia',
        'GE' => 'Georgia',
        'DE' => 'Germany',
        'GH' => 'Ghana',
        'GR' => 'Greece',
        'GT' => 'Guatemala',
        'GN' => 'Guinea',
        'GW' => 'Guinea-Bissau',
        'GY' => 'Guyana',
        'HT' => 'Haiti',
        'HN' => 'Honduras',
        'HK' => 'Hong Kong',
        'HU' => 'Hungary',
        'IS' => 'Iceland',
        'IN' => 'India',
        'ID' => 'Indonesia',
        'IR' => 'Iran',
        'IQ' => 'Iraq',
        'IE' => 'Ireland',
        'IL' => 'Israel',
        'IT' => 'Italy',
        'CI' => 'Ivory Coast',
        'JM' => 'Jamaica',
        'JP' => 'Japan',
        'JO' => 'Jordan',
        'KZ' => 'Kazakhstan',
        'KE' => 'Kenya',
        'KI' => 'Kiribati',
        'KP' => 'North Korea',
        'KR' => 'South Korea',
        'KW' => 'Kuwait',
        'KG' => 'Kyrgyzstan',
        'LA' => 'Laos',
        'LV' => 'Latvia',
        'LB' => 'Lebanon',
        'LS' => 'Lesotho',
        'LR' => 'Liberia',
        'LY' => 'Libya',
        'LI' => 'Liechtenstein',
        'LT' => 'Lithuania',
        'LU' => 'Luxembourg',
        'MO' => 'Macau',
        'MK' => 'North Macedonia',
        'MG' => 'Madagascar',
        'MW' => 'Malawi',
        'MY' => 'Malaysia',
        'MV' => 'Maldives',
        'ML' => 'Mali',
        'MT' => 'Malta',
        'MH' => 'Marshall Islands',
        'MR' => 'Mauritania',
        'MU' => 'Mauritius',
        'MX' => 'Mexico',
        'FM' => 'Micronesia',
        'MD' => 'Moldova',
        'MC' => 'Monaco',
        'MN' => 'Mongolia',
        'ME' => 'Montenegro',
        'MA' => 'Morocco',
        'MZ' => 'Mozambique',
        'MM' => 'Myanmar',
        'NA' => 'Namibia',
        'NR' => 'Nauru',
        'NP' => 'Nepal',
        'NL' => 'Netherlands',
        'NZ' => 'New Zealand',
        'NI' => 'Nicaragua',
        'NE' => 'Niger',
        'NG' => 'Nigeria',
        'NO' => 'Norway',
        'OM' => 'Oman',
        'PK' => 'Pakistan',
        'PW' => 'Palau',
        'PS' => 'Palestine',
        'PA' => 'Panama',
        'PG' => 'Papua New Guinea',
        'PY' => 'Paraguay',
        'PE' => 'Peru',
        'PH' => 'Philippines',
        'PL' => 'Poland',
        'PT' => 'Portugal',
        'PR' => 'Puerto Rico',
        'QA' => 'Qatar',
        'RO' => 'Romania',
        'RU' => 'Russia',
        'RW' => 'Rwanda',
        'SA' => 'Saudi Arabia',
        'SN' => 'Senegal',
        'RS' => 'Serbia',
        'SC' => 'Seychelles',
        'SL' => 'Sierra Leone',
        'SG' => 'Singapore',
        'SK' => 'Slovakia',
        'SI' => 'Slovenia',
        'SB' => 'Solomon Islands',
        'SO' => 'Somalia',
        'ZA' => 'South Africa',
        'SS' => 'South Sudan',
        'ES' => 'Spain',
        'LK' => 'Sri Lanka',
        'SD' => 'Sudan',
        'SR' => 'Suriname',
        'SZ' => 'Eswatini',
        'SE' => 'Sweden',
        'CH' => 'Switzerland',
        'SY' => 'Syria',
        'TW' => 'Taiwan',
        'TJ' => 'Tajikistan',
        'TZ' => 'Tanzania',
        'TH' => 'Thailand',
        'TL' => 'Timor-Leste',
        'TG' => 'Togo',
        'TO' => 'Tonga',
        'TT' => 'Trinidad and Tobago',
        'TN' => 'Tunisia',
        'TR' => 'Turkey',
        'TM' => 'Turkmenistan',
        'TV' => 'Tuvalu',
        'UG' => 'Uganda',
        'UA' => 'Ukraine',
        'AE' => 'United Arab Emirates',
        'GB' => 'United Kingdom',
        'US' => 'United States',
        'UY' => 'Uruguay',
        'UZ' => 'Uzbekistan',
        'VU' => 'Vanuatu',
        'VA' => 'Vatican City',
        'VE' => 'Venezuela',
        'VN' => 'Vietnam',
        'YE' => 'Yemen',
        'ZM' => 'Zambia',
        'ZW' => 'Zimbabwe',
    ];
    
    // Currency symbols mapping
    private const CURRENCY_SYMBOLS = [
        'USD' => '$',
        'EUR' => '€',
        'GBP' => '£',
        'INR' => '₹',
        'JPY' => '¥',
        'CNY' => '¥',
        'AUD' => 'A$',
        'CAD' => 'C$',
        'CHF' => 'CHF',
        'NZD' => 'NZ$',
        'SGD' => 'S$',
        'HKD' => 'HK$',
        'KRW' => '₩',
        'MXN' => 'MX$',
        'BRL' => 'R$',
        'ZAR' => 'R',
        'RUB' => '₽',
        'AED' => 'د.إ',
        'SAR' => '﷼',
        'THB' => '฿',
        'MYR' => 'RM',
        'PHP' => '₱',
        'IDR' => 'Rp',
        'VND' => '₫',
        'PKR' => '₨',
        'BDT' => '৳',
        'NGN' => '₦',
        'EGP' => 'E£',
        'TRY' => '₺',
        'PLN' => 'zł',
        'SEK' => 'kr',
        'NOK' => 'kr',
        'DKK' => 'kr',
        'CZK' => 'Kč',
        'HUF' => 'Ft',
        'ILS' => '₪',
        'CLP' => 'CLP$',
        'COP' => 'COL$',
        'ARS' => 'AR$',
        'PEN' => 'S/',
    ];
    
    // Common timezones grouped by region
    private const TIMEZONES = [
        'UTC' => 'UTC',
        'America/New_York' => 'Eastern Time (US & Canada)',
        'America/Chicago' => 'Central Time (US & Canada)',
        'America/Denver' => 'Mountain Time (US & Canada)',
        'America/Los_Angeles' => 'Pacific Time (US & Canada)',
        'America/Toronto' => 'Toronto',
        'America/Vancouver' => 'Vancouver',
        'America/Mexico_City' => 'Mexico City',
        'America/Sao_Paulo' => 'Sao Paulo',
        'America/Buenos_Aires' => 'Buenos Aires',
        'Europe/London' => 'London',
        'Europe/Paris' => 'Paris',
        'Europe/Berlin' => 'Berlin',
        'Europe/Rome' => 'Rome',
        'Europe/Madrid' => 'Madrid',
        'Europe/Amsterdam' => 'Amsterdam',
        'Europe/Moscow' => 'Moscow',
        'Asia/Dubai' => 'Dubai',
        'Asia/Kolkata' => 'India (Kolkata)',
        'Asia/Singapore' => 'Singapore',
        'Asia/Hong_Kong' => 'Hong Kong',
        'Asia/Tokyo' => 'Tokyo',
        'Asia/Seoul' => 'Seoul',
        'Asia/Shanghai' => 'Shanghai',
        'Asia/Bangkok' => 'Bangkok',
        'Asia/Jakarta' => 'Jakarta',
        'Asia/Manila' => 'Manila',
        'Australia/Sydney' => 'Sydney',
        'Australia/Melbourne' => 'Melbourne',
        'Australia/Perth' => 'Perth',
        'Pacific/Auckland' => 'Auckland',
        'Africa/Cairo' => 'Cairo',
        'Africa/Johannesburg' => 'Johannesburg',
        'Africa/Lagos' => 'Lagos',
    ];
    
    // Default settings
    private const DEFAULTS = [
        'currency_code' => 'INR',
        'currency_symbol' => '₹',
        'currency_position' => 'before', // before or after
        'currency_decimal_places' => 2,
        'currency_decimal_separator' => '.',
        'currency_thousand_separator' => ',',
        'timezone' => 'Asia/Kolkata',
        'country_code' => 'IN',
        'date_format' => 'd/m/Y',
        'time_format' => 'h:i A',
        'locale' => 'en_IN',
    ];
    
    private function __construct()
    {
        $this->settingModel = new Setting();
        $this->loadSettings();
    }
    
    /**
     * Get singleton instance
     */
    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Load all localisation settings from database
     */
    private function loadSettings(): void
    {
        $keys = array_keys(self::DEFAULTS);
        $prefixedKeys = array_map(fn($k) => 'locale_' . $k, $keys);
        $settings = $this->settingModel->getMultiple($prefixedKeys);
        
        foreach ($keys as $key) {
            $dbKey = 'locale_' . $key;
            $this->cache[$key] = $settings[$dbKey] ?? self::DEFAULTS[$key];
        }
    }
    
    /**
     * Clear cache and reload settings
     */
    public function refresh(): void
    {
        $this->cache = [];
        $this->loadSettings();
    }
    
    /**
     * Get a localisation setting
     */
    public function get(string $key, $default = null)
    {
        return $this->cache[$key] ?? $default ?? (self::DEFAULTS[$key] ?? null);
    }
    
    /**
     * Get currency code (e.g., 'INR', 'USD')
     */
    public function getCurrencyCode(): string
    {
        return $this->get('currency_code', 'INR');
    }
    
    /**
     * Get currency symbol (e.g., '₹', '$')
     */
    public function getCurrencySymbol(): string
    {
        return $this->get('currency_symbol', '₹');
    }
    
    /**
     * Get currency position ('before' or 'after')
     */
    public function getCurrencyPosition(): string
    {
        return $this->get('currency_position', 'before');
    }
    
    /**
     * Get timezone
     */
    public function getTimezone(): string
    {
        return $this->get('timezone', 'Asia/Kolkata');
    }
    
    /**
     * Get country code
     */
    public function getCountryCode(): string
    {
        return $this->get('country_code', 'IN');
    }
    
    /**
     * Get date format
     */
    public function getDateFormat(): string
    {
        return $this->get('date_format', 'd/m/Y');
    }
    
    /**
     * Get time format
     */
    public function getTimeFormat(): string
    {
        return $this->get('time_format', 'h:i A');
    }
    
    /**
     * Format a price with currency symbol
     * 
     * @param float|int $amount The amount to format
     * @param bool $showDecimals Whether to show decimal places
     * @return string Formatted price with currency symbol
     */
    public function formatPrice($amount, bool $showDecimals = true): string
    {
        $symbol = $this->getCurrencySymbol();
        $position = $this->getCurrencyPosition();
        $decimals = $showDecimals ? (int)$this->get('currency_decimal_places', 2) : 0;
        $decSep = $this->get('currency_decimal_separator', '.');
        $thousandSep = $this->get('currency_thousand_separator', ',');
        
        $formatted = number_format((float)$amount, $decimals, $decSep, $thousandSep);
        
        if ($position === 'after') {
            return $formatted . ' ' . $symbol;
        }
        
        return $symbol . ' ' . $formatted;
    }
    
    /**
     * Format a date according to locale settings
     */
    public function formatDate($date, ?string $format = null): string
    {
        $format = $format ?? $this->getDateFormat();
        
        if ($date instanceof \DateTime) {
            return $date->format($format);
        }
        
        if (is_string($date)) {
            $dateObj = new \DateTime($date, new \DateTimeZone($this->getTimezone()));
            return $dateObj->format($format);
        }
        
        return '';
    }
    
    /**
     * Format a datetime according to locale settings
     */
    public function formatDateTime($datetime, ?string $dateFormat = null, ?string $timeFormat = null): string
    {
        $dateFormat = $dateFormat ?? $this->getDateFormat();
        $timeFormat = $timeFormat ?? $this->getTimeFormat();
        
        if ($datetime instanceof \DateTime) {
            return $datetime->format($dateFormat . ' ' . $timeFormat);
        }
        
        if (is_string($datetime)) {
            $dateObj = new \DateTime($datetime, new \DateTimeZone($this->getTimezone()));
            return $dateObj->format($dateFormat . ' ' . $timeFormat);
        }
        
        return '';
    }
    
    /**
     * Get all available currency codes with symbols
     */
    public static function getAvailableCurrencies(): array
    {
        return self::CURRENCY_SYMBOLS;
    }
    
    /**
     * Get all available timezones
     */
    public static function getAvailableTimezones(): array
    {
        return self::TIMEZONES;
    }
    
    /**
     * Get all available timezones with UTC offset
     * Returns array with timezone identifier as key and formatted label with UTC offset as value
     */
    public static function getAvailableTimezonesWithOffset(): array
    {
        $result = [];
        $now = new \DateTime('now', new \DateTimeZone('UTC'));
        
        foreach (self::TIMEZONES as $tz => $label) {
            try {
                $timezone = new \DateTimeZone($tz);
                $offset = $timezone->getOffset($now);
                $offsetHours = $offset / 3600;
                $offsetMinutes = abs(($offset % 3600) / 60);
                
                // Format offset as +HH:MM or -HH:MM
                $sign = $offsetHours >= 0 ? '+' : '-';
                $offsetStr = sprintf('%s%02d:%02d', $sign, abs(floor($offsetHours)), $offsetMinutes);
                
                $result[$tz] = sprintf('(UTC%s) %s', $offsetStr, $label);
            } catch (\Exception $e) {
                // Fallback if timezone is invalid
                $result[$tz] = $label;
            }
        }
        
        // Sort by UTC offset
        uasort($result, function($a, $b) {
            // Extract offset from the label for sorting
            preg_match('/\(UTC([+-]\d{2}:\d{2})\)/', $a, $matchA);
            preg_match('/\(UTC([+-]\d{2}:\d{2})\)/', $b, $matchB);
            
            $offsetA = $matchA[1] ?? '+00:00';
            $offsetB = $matchB[1] ?? '+00:00';
            
            return strcmp($offsetA, $offsetB);
        });
        
        return $result;
    }
    
    /**
     * Get symbol for a currency code
     */
    public static function getSymbolForCurrency(string $code): string
    {
        return self::CURRENCY_SYMBOLS[strtoupper($code)] ?? $code;
    }
    
    /**
     * Get all current settings as array
     */
    public function getAllSettings(): array
    {
        return $this->cache;
    }
    
    /**
     * Get ISD code for a country
     * 
     * @param string|null $countryCode ISO 3166-1 alpha-2 country code (uses locale setting if null)
     * @return string ISD code with + prefix (e.g., '+91', '+1')
     */
    public function getIsdCode(?string $countryCode = null): string
    {
        $code = $countryCode ?? $this->getCountryCode();
        return self::COUNTRY_ISD_CODES[strtoupper($code)] ?? '+1';
    }
    
    /**
     * Get country name for a country code
     * 
     * @param string|null $countryCode ISO 3166-1 alpha-2 country code (uses locale setting if null)
     * @return string Country name
     */
    public function getCountryName(?string $countryCode = null): string
    {
        $code = $countryCode ?? $this->getCountryCode();
        return self::COUNTRY_NAMES[strtoupper($code)] ?? $code;
    }
    
    /**
     * Get all available country ISD codes
     * 
     * @return array Associative array of country code => ISD code
     */
    public static function getAvailableIsdCodes(): array
    {
        return self::COUNTRY_ISD_CODES;
    }
    
    /**
     * Get all available country names
     * 
     * @return array Associative array of country code => country name
     */
    public static function getAvailableCountryNames(): array
    {
        return self::COUNTRY_NAMES;
    }
    
    /**
     * Get countries with ISD codes formatted for display
     * Returns array sorted by country name with format: "Country Name (+XX)"
     * 
     * @return array Associative array of country code => "Country Name (+XX)"
     */
    public static function getCountriesWithIsdCodes(): array
    {
        $result = [];
        foreach (self::COUNTRY_NAMES as $code => $name) {
            $isd = self::COUNTRY_ISD_CODES[$code] ?? '';
            $result[$code] = $name . ' (' . $isd . ')';
        }
        asort($result);
        return $result;
    }
    
    /**
     * Format a phone number with the locale's ISD code
     * Strips any existing country code and prepends the locale's ISD code
     * 
     * @param string $phone Phone number (may or may not include country code)
     * @return string Formatted phone number with ISD code
     */
    public function formatPhoneWithIsd(string $phone): string
    {
        // Remove all non-digit characters except +
        $cleaned = preg_replace('/[^\d+]/', '', $phone);
        
        // If already has a + prefix, return as-is (already formatted)
        if (strpos($cleaned, '+') === 0) {
            return $cleaned;
        }
        
        // Remove leading zeros
        $cleaned = ltrim($cleaned, '0');
        
        // Prepend the locale's ISD code
        return $this->getIsdCode() . $cleaned;
    }
    
    /**
     * Get phone input configuration for JavaScript
     * Returns JSON-encodable array with ISD code settings
     * 
     * @return array Configuration array for phone input
     */
    public function getPhoneInputConfig(): array
    {
        return [
            'countryCode' => $this->getCountryCode(),
            'countryName' => $this->getCountryName(),
            'isdCode' => $this->getIsdCode(),
            'placeholder' => $this->getIsdCode() . ' XXXXXXXXXX',
        ];
    }
}
