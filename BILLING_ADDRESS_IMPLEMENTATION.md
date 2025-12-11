# Billing Address Implementation

## Overview
Implemented comprehensive billing address functionality on the checkout page with Razorpay branding and trust factors. The system supports one billing address per customer with full CRUD operations.

## Features Implemented

### 1. Checkout Page Updates
- **Razorpay Branding**: Added Razorpay logo to payment method selection
- **Payment Method Description**: Updated from "Credit/Debit Card" to "Razorpay - Cards, UPI, NetBanking, Wallets & More"
- **Billing Address Section**: New section (Section 2) for collecting billing information
- **Form Fields**:
  - Full Name (required)
  - Phone Number (required)
  - Business Name (optional)
  - Business Tax ID / GSTIN (optional)
  - Address Line 1 (required)
  - Address Line 2 (optional)
  - City (required)
  - State (required)
  - Postal Code (required)
  - Country (required, defaults to "India")

### 2. Database Schema

#### New Table: `billing_addresses`
```sql
- id (CHAR(36), PRIMARY KEY)
- customer_id (CHAR(36), UNIQUE, FOREIGN KEY to users)
- full_name (VARCHAR(255), NOT NULL)
- business_name (VARCHAR(255))
- business_tax_id (VARCHAR(100))
- address_line1 (VARCHAR(255), NOT NULL)
- address_line2 (VARCHAR(255))
- city (VARCHAR(100), NOT NULL)
- state (VARCHAR(100), NOT NULL)
- postal_code (VARCHAR(20), NOT NULL)
- country (VARCHAR(100), DEFAULT 'India')
- phone (VARCHAR(20))
- created_at (TIMESTAMP)
- updated_at (TIMESTAMP)
```

**Constraint**: One customer, one billing address (enforced by UNIQUE constraint on customer_id)

#### Updated Table: `orders`
Added billing address columns:
- billing_full_name
- billing_business_name
- billing_business_tax_id
- billing_address_line1
- billing_address_line2
- billing_city
- billing_state
- billing_postal_code
- billing_country
- billing_phone

### 3. Backend Implementation

#### New Model: `BillingAddress`
**Location**: `classes/Models/BillingAddress.php`

**Methods**:
- `findByCustomerId($customerId)` - Get billing address for a customer
- `createOrUpdate($customerId, $data)` - Create or update billing address
- `delete($customerId)` - Delete billing address

#### Updated Model: `Order`
**Location**: `classes/Models/Order.php`

**Changes**:
- Updated `create()` method to accept billing address fields
- Billing address data is stored with each order for historical record

#### Updated Process Payment
**Location**: `public/process-payment.php`

**Changes**:
- Validates billing address fields (required fields)
- Saves/updates billing address in `billing_addresses` table
- Includes billing address in order creation
- Billing address is passed to order details

### 4. Profile Page Updates
**Location**: `app/profile.php`

**New Section**: Billing Address Management
- Displays existing billing address if available
- Allows customers to edit billing address
- Form validation for required fields
- Separate form submission for billing address updates

### 5. UI/UX Enhancements

#### Razorpay Logo
**Location**: `assets/images/razorpay-logo.svg`
- Added Razorpay logo for trust factor
- Displayed on payment method selection

#### CSS Updates
**Location**: `assets/css/checkout.css`
- `.checkout-payment-logo` - Styles for Razorpay logo
- `.checkout-form-grid-3` - 3-column grid for city, state, postal code
- Responsive design for mobile devices

## User Flow

### Checkout Flow
1. Customer selects a plan
2. Fills in customer information (Section 1)
3. Fills in billing address (Section 2)
   - If existing billing address found, fields are pre-filled
   - Customer can edit the information
4. Selects payment method - Razorpay (Section 3)
5. Accepts terms and conditions (Section 4)
6. Proceeds to payment
7. Billing address is saved/updated in database
8. Order is created with billing address details

### Profile Management Flow
1. Customer navigates to Profile page
2. Scrolls to "Billing Address" section
3. Fills in or updates billing address
4. Saves changes
5. Billing address is updated in database
6. Next checkout will pre-fill with saved address

## Data Flow

### Checkout Process
```
Checkout Form → process-payment.php
    ↓
1. Validate billing address fields
2. Save/Update billing_addresses table (one per customer)
3. Create order with billing address columns
4. Proceed to Razorpay payment
```

### Profile Update
```
Profile Form → app/profile.php
    ↓
1. Validate billing address fields
2. Update billing_addresses table
3. Show success message
```

## Files Modified

### New Files
1. `database/migrations/041_create_billing_addresses_table.sql`
2. `database/migrations/042_add_billing_address_to_orders.sql`
3. `classes/Models/BillingAddress.php`
4. `assets/images/razorpay-logo.svg`

### Modified Files
1. `public/checkout.php` - Added billing address section and Razorpay branding
2. `public/process-payment.php` - Added billing address handling
3. `classes/Models/Order.php` - Updated create method for billing fields
4. `app/profile.php` - Added billing address management
5. `assets/css/checkout.css` - Added styles for Razorpay logo and 3-column grid

## Database Migrations

### Migration 041: Create billing_addresses table
```bash
php bin/migrate.php
```

### Migration 042: Add billing columns to orders table
```bash
php bin/migrate.php
```

Both migrations have been executed successfully.

## Testing Checklist

### Checkout Page
- [ ] Billing address section displays correctly
- [ ] Razorpay logo appears on payment method
- [ ] Payment method shows "Razorpay - Cards, UPI, NetBanking, Wallets & More"
- [ ] Required fields are validated
- [ ] Existing billing address pre-fills form
- [ ] New billing address saves correctly
- [ ] Updated billing address saves correctly
- [ ] Billing address is included in order

### Profile Page
- [ ] Billing address section displays
- [ ] Existing billing address loads correctly
- [ ] Can update billing address
- [ ] Validation works for required fields
- [ ] Success message displays after save
- [ ] Updated address appears on next checkout

### Database
- [ ] billing_addresses table created
- [ ] One customer can have only one billing address
- [ ] orders table has billing columns
- [ ] Billing data saves correctly in orders
- [ ] Foreign key constraints work

## Security Considerations

1. **Input Validation**: All billing address fields are sanitized using `sanitizeString()`
2. **CSRF Protection**: Forms use CSRF tokens
3. **Authentication**: Checkout and profile pages require authentication
4. **Data Integrity**: Foreign key constraints ensure data consistency
5. **One-to-One Relationship**: UNIQUE constraint on customer_id prevents duplicate billing addresses

## Future Enhancements

1. **Multiple Billing Addresses**: Allow customers to save multiple billing addresses
2. **Address Validation**: Integrate with address validation API
3. **Auto-complete**: Add address auto-complete functionality
4. **Invoice Generation**: Use billing address for invoice generation
5. **Tax Calculation**: Use business tax ID for tax calculations
6. **International Support**: Add country-specific address formats

## Support

For issues or questions:
- Check error logs in `storage/logs/`
- Verify database migrations are executed
- Ensure all required fields are filled
- Contact development team for assistance
