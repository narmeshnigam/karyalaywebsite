# Design Document: Dynamic Brand Name

## Overview

This feature replaces hardcoded "Karyalay" brand name references with dynamic values from the admin settings. The system already has a `site_name` setting in the database - this design focuses on creating a helper function to retrieve it efficiently and updating all templates to use it.

## Architecture

The solution follows the existing architecture pattern:
1. **Setting Model** - Already exists (`classes/Models/Setting.php`) for database access
2. **Template Helpers** - Add a new `get_brand_name()` function to `includes/template_helpers.php`
3. **Templates** - Update header/footer templates to use the helper function

```
┌─────────────────┐     ┌──────────────────┐     ┌─────────────────┐
│   Templates     │────▶│  Template Helper │────▶│  Setting Model  │
│  (header.php,   │     │ get_brand_name() │     │    get()        │
│   footer.php)   │     │   (with cache)   │     │                 │
└─────────────────┘     └──────────────────┘     └─────────────────┘
                                                          │
                                                          ▼
                                                 ┌─────────────────┐
                                                 │    Database     │
                                                 │ settings table  │
                                                 └─────────────────┘
```

## Components and Interfaces

### 1. Template Helper Function

**File:** `includes/template_helpers.php`

```php
/**
 * Get the configured brand name from settings
 * Uses static caching to avoid multiple database queries per request
 * 
 * @return string Brand name or default fallback
 */
function get_brand_name(): string
```

**Behavior:**
- Returns the `site_name` setting value from the database
- Caches the value in a static variable for the duration of the request
- Returns "Portal" as the default fallback if setting is empty or not found

### 2. Template Updates

The following templates will be updated to use `get_brand_name()`:

| Template | Locations to Update |
|----------|---------------------|
| `templates/header.php` | Page title, logo text, meta description, aria-label |
| `templates/footer.php` | Footer logo, copyright text |
| `templates/admin-header.php` | Page title, admin logo text |
| `templates/admin-footer.php` | Copyright text |
| `templates/customer-header.php` | Page title, portal logo text |
| `templates/customer-footer.php` | Copyright text |
| `templates/cta-form.php` | Default subtitle text |

## Data Models

No new data models required. Uses existing:

**Settings Table** (already exists):
| Column | Type | Description |
|--------|------|-------------|
| id | CHAR(36) | UUID primary key |
| setting_key | VARCHAR(255) | Setting identifier (e.g., 'site_name') |
| setting_value | TEXT | Setting value |
| setting_type | VARCHAR(50) | Type hint (string, integer, etc.) |

**Existing Setting:** `site_name` with default value "SellerPortal"

## Correctness Properties

*A property is a characteristic or behavior that should hold true across all valid executions of a system-essentially, a formal statement about what the system should do. Properties serve as the bridge between human-readable specifications and machine-verifiable correctness guarantees.*

### Property 1: Setting persistence round-trip
*For any* valid brand name string, saving it via the Setting model and then retrieving it should return the exact same string value.
**Validates: Requirements 1.1**

### Property 2: Brand name helper returns configured value
*For any* configured site_name setting value, the `get_brand_name()` helper function should return that exact value.
**Validates: Requirements 5.1, 5.3**

### Property 3: Brand name helper returns fallback for empty values
*For any* empty or whitespace-only site_name setting (including when the setting doesn't exist), the `get_brand_name()` helper function should return the default fallback value "Portal".
**Validates: Requirements 1.2**

## Error Handling

| Scenario | Handling |
|----------|----------|
| Database connection failure | Helper catches exception, returns fallback value, logs error |
| Setting not found in database | Helper returns fallback value "Portal" |
| Empty string stored as setting | Helper treats as missing, returns fallback |
| Null value in database | Helper treats as missing, returns fallback |

## Testing Strategy

### Property-Based Testing

The project uses **Eris** (PHP property-based testing library) as specified in `composer.json`.

**Property tests will verify:**
1. Round-trip persistence of brand name values
2. Helper function returns correct values for any valid input
3. Fallback behavior for edge cases (empty, null, missing)

Each property test will:
- Run a minimum of 100 iterations
- Generate random valid brand name strings
- Tag tests with the corresponding correctness property reference

### Unit Tests

Unit tests will cover:
- Helper function basic functionality
- Caching behavior (single database query per request)
- Integration with Setting model

### Test File Location

Property tests: `tests/Property/BrandNameHelperPropertyTest.php`
