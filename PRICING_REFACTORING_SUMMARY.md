# Pricing Structure Refactoring Summary

## Overview
Refactored the plans table pricing structure to use only `mrp` (Maximum Retail Price) and `discounted_price` fields, removing the legacy `price` field.

## Changes Made

### 1. Database Migration
- **File**: `database/migrations/039_drop_price_column_from_plans.sql`
- **Actions**:
  - Migrated existing `price` values to `mrp` where `mrp` was NULL
  - Made `mrp` NOT NULL (required field)
  - Dropped the `price` column

### 2. Core Services Updated

#### PlanService (`classes/Services/PlanService.php`)
- Updated `validateRequiredFields()` to require `mrp` instead of `price`
- Updated `getEffectivePrice()` to return `discounted_price` if available, otherwise `mrp`
- Removed `price` validation logic
- Made `mrp` a required field for plan creation

#### OrderService (`classes/Services/OrderService.php`)
- Updated `createOrder()` to calculate effective price:
  - Uses `discounted_price` if available and > 0
  - Falls back to `mrp` if no discount
- Order amount now reflects the actual customer-facing price

### 3. Admin Pages Updated

#### Plans Management
- **admin/plans/new.php**: 
  - Removed `price` field from form
  - Made `mrp` required
  - Updated validation to require `mrp`
  
- **admin/plans/edit.php**:
  - Removed `price` field from form
  - Made `mrp` required
  - Updated form data handling

- **admin/plans.php**:
  - Removed `price` from SQL queries
  - Updated effective price calculation

#### Orders & Subscriptions
- **admin/orders.php**: Updated to use `mrp` and `discounted_price`
- **admin/orders/view.php**: Calculate plan price from `mrp`/`discounted_price`
- **admin/subscriptions/new.php**: Use effective price for manual order creation
- **admin/api/search-plans.php**: Return `mrp` and `discounted_price` instead of `price`

### 4. Public Pages Updated

#### Payment Flow
- **public/process-payment.php**:
  - Calculate effective price before creating order
  - Use effective price in Razorpay payment order

- **public/checkout.php**:
  - Display effective price (discounted or MRP)
  - Updated debug logging

#### Pricing Display
- **public/pricing.php**: Already using `PlanService::getEffectivePrice()`

### 5. App Pages Updated
- **app/subscription.php**: Calculate and display effective price
- **app/plans.php**: Already using `PlanService::getEffectivePrice()`

### 6. Database Seeder Updated
- **classes/Database/Seeder.php**: 
  - Updated to use `mrp` and `discounted_price` fields
  - Removed `price` field from INSERT statements

### 7. Test Files Updated
Updated all test files to use effective price calculation:
- `tests/Integration/PaymentGatewayIntegrationTest.php`
- `tests/Property/OrderCreationPropertyTest.php`
- `tests/Property/PlanDisplayCompletenessPropertyTest.php`
- `tests/Property/SubscriptionExpirationPropertyTest.php`
- `tests/Property/SubscriptionRenewalDateExtensionPropertyTest.php`
- `tests/Property/CustomerDetailAggregationPropertyTest.php`
- `tests/Property/PortPreservationOnRenewalPropertyTest.php`
- `tests/Property/DashboardDataCompletenessPropertyTest.php`
- `tests/Property/SubscriptionCreationPropertyTest.php`
- `tests/Property/BillingHistoryListDisplayPropertyTest.php`
- `tests/Property/FailedRenewalImmutabilityPropertyTest.php`

### 8. Debug Tools Updated
- **debug_plans.php**: Removed `price` column from display

## Pricing Logic

### Effective Price Calculation
The system now uses a consistent formula across all components:

```php
$effectivePrice = !empty($plan['discounted_price']) && $plan['discounted_price'] > 0 
    ? $plan['discounted_price'] 
    : $plan['mrp'];
```

### Order Creation
- New orders use the effective price (discounted if available, otherwise MRP)
- Renewals use the same logic
- Manual admin orders use the same logic

### Display Logic
- If `discounted_price` is set and less than `mrp`, show both with strikethrough on MRP
- Otherwise, show only MRP
- Final price is always the effective price

## Migration Instructions

1. **Backup Database**: Always backup before running migrations
2. **Run Migration**: 
   ```bash
   php database/run_migration_039.php
   ```
3. **Verify**: Check that all existing plans have `mrp` populated
4. **Test**: Run test suite to ensure all functionality works

## Breaking Changes

### Database Schema
- `plans.price` column is removed
- `plans.mrp` is now NOT NULL and required
- `plans.features` column references removed (only `features_html` exists)

### API/Code Changes
- Any code referencing `$plan['price']` must be updated to use effective price logic
- Plan creation now requires `mrp` field
- Plan creation/update no longer accepts `features` field (use `features_html` instead)

## Fixed Issues

### Plan Creation/Update Failures
**Issue**: Admin plan creation and editing was failing with "Failed to create plan" error.

**Root Cause**: The Plan model was trying to insert/update non-existent columns:
- `price` column (removed in migration)
- `features` column (doesn't exist, only `features_html` exists)

**Files Fixed**:
- `classes/Models/Plan.php`: Removed references to `price` and `features` columns
- `admin/plans/new.php`: Removed `features` field from plan data
- `admin/plans/edit.php`: Removed `features` field from update data
- `classes/Database/Seeder.php`: Updated to use `features_html` instead of `features`

**Resolution**: Plan creation and editing now work correctly through the admin interface.

## Benefits

1. **Clearer Pricing Model**: Two fields with clear purposes (MRP and discount)
2. **Consistent Logic**: Single source of truth for effective price calculation
3. **Better Discount Support**: Native support for showing original vs. discounted prices
4. **Simplified Maintenance**: No confusion between `price`, `mrp`, and `discounted_price`

## Notes

- All existing `price` values are migrated to `mrp` during migration
- The effective price logic is centralized in `PlanService::getEffectivePrice()`
- Order amounts always reflect the customer-facing price (with discount if applicable)
- Renewals use the same pricing logic as new subscriptions

## Test Files

Many test files still reference `'price'` in their `createTestPlan()` helper methods. These need to be updated to use `'mrp'` instead. The pattern to follow:

**Before:**
```php
'price' => 99.99,
```

**After:**
```php
'mrp' => 99.99,
'discounted_price' => null,
```

Test files that need updating include (but are not limited to):
- `tests/Property/SubscriptionFilteringByDateRangePropertyTest.php`
- `tests/Property/PortAllocationAtomicityPropertyTest.php`
- `tests/Property/BillingHistoryListDisplayPropertyTest.php`
- `tests/Property/DashboardDataCompletenessPropertyTest.php`
- `tests/Property/SubscriptionExpirationPropertyTest.php`
- `tests/Property/PortPreservationOnRenewalPropertyTest.php`
- `tests/Property/SubscriptionRenewalDateExtensionPropertyTest.php`
- `tests/Property/SubscriptionCreationPropertyTest.php`
- `tests/Property/PortCrudPersistencePropertyTest.php`
- `tests/Property/CustomerDetailAggregationPropertyTest.php`
- `tests/Property/PlanDisplayCompletenessPropertyTest.php`
- `tests/Property/CsvImportValidationPropertyTest.php`
- `tests/Property/PortAvailabilityCheckPropertyTest.php`
- `tests/Property/PortAllocationQueryCorrectnessPropertyTest.php`

You can use a find/replace across these files to update `'price'` to `'mrp'` in plan creation arrays.
