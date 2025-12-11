-- Add localisation settings to the settings table
-- Migration: 037_add_localisation_settings.sql

-- Insert default localisation settings
INSERT INTO settings (id, setting_key, setting_value, setting_type) VALUES
(UUID(), 'locale_currency_code', 'INR', 'string'),
(UUID(), 'locale_currency_symbol', '₹', 'string'),
(UUID(), 'locale_currency_position', 'before', 'string'),
(UUID(), 'locale_currency_decimal_places', '2', 'integer'),
(UUID(), 'locale_currency_decimal_separator', '.', 'string'),
(UUID(), 'locale_currency_thousand_separator', ',', 'string'),
(UUID(), 'locale_timezone', 'Asia/Kolkata', 'string'),
(UUID(), 'locale_country_code', 'IN', 'string'),
(UUID(), 'locale_date_format', 'd/m/Y', 'string'),
(UUID(), 'locale_time_format', 'h:i A', 'string'),
(UUID(), 'locale_locale', 'en_IN', 'string')
ON DUPLICATE KEY UPDATE setting_key = setting_key;
