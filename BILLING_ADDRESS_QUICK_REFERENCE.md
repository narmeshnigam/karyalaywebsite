# Billing Address - Quick Reference

## Summary
✅ Billing address functionality implemented on checkout page with Razorpay branding
✅ One customer, one billing address (enforced by database constraint)
✅ Billing address can be edited from profile page
✅ Billing address is saved with each order for historical record

## Key Changes

### 1. Checkout Page (`public/checkout.php`)
- **Section 2**: New billing address form with all required fields
- **Section 3**: Payment method updated to show "Razorpay - Cards, UPI, NetBanking, Wallets & More"
- **Razorpay Logo**: Added for trust factor
- **Pre-fill**: Existing billing address automatically loads if available

### 2. Database Tables

#### `billing_addresses` (NEW)
- Stores one billing address per customer
- Fields: full_name, business_name, business_tax_id, address_line1, address_line2, city, state, postal_code, country, phone
- Unique constraint on `customer_id`

#### `orders` (UPDATED)
- Added 10 billing columns: billing_full_name, billing_business_name, billing_business_tax_id, billing_address_line1, billing_address_line2, billing_city, billing_state, billing_postal_code, billing_country, billing_phone
- Billing address snapshot saved with each order

### 3. Profile Page (`app/profile.php`)
- New "Billing Address" section
- Customers can view and edit their billing address
- Separate form submission for billing updates

### 4. Payment Processing (`public/process-payment.php`)
- Validates billing address fields
- Saves/updates billing address in `billing_addresses` table
- Includes billing address in order creation

## Required Fields

### Billing Address
- ✅ Full Name
- ✅ Phone Number
- ✅ Address Line 1
- ✅ City
- ✅ State
- ✅ Postal Code
- ✅ Country
- ⭕ Business Name (optional)
- ⭕ Business Tax ID / GSTIN (optional)
- ⭕ Address Line 2 (optional)

## User Flow

### First-Time Checkout
1. Customer fills in customer information
2. Customer fills in billing address (all fields empty)
3. Selects Razorpay payment method
4. Proceeds to payment
5. Billing address is saved to database

### Returning Customer Checkout
1. Customer information pre-filled
2. Billing address pre-filled from database
3. Customer can edit billing address if needed
4. Updated billing address is saved
5. Proceeds to payment

### Profile Management
1. Navigate to Profile page
2. Scroll to "Billing Address" section
3. Edit billing address fields
4. Click "Save Billing Address"
5. Success message displayed
6. Next checkout will use updated address

## Files Created/Modified

### New Files
- `database/migrations/041_create_billing_addresses_table.sql`
- `database/migrations/042_add_billing_address_to_orders.sql`
- `classes/Models/BillingAddress.php`
- `assets/images/razorpay-logo.svg`
- `test-billing-address.php`
- `BILLING_ADDRESS_IMPLEMENTATION.md`
- `BILLING_ADDRESS_QUICK_REFERENCE.md`

### Modified Files
- `public/checkout.php` - Added billing address section and Razorpay branding
- `public/process-payment.php` - Added billing address handling
- `classes/Models/Order.php` - Updated create method
- `app/profile.php` - Added billing address management
- `assets/css/checkout.css` - Added styles

## Testing

Run the test script:
```bash
php test-billing-address.php
```

Expected output:
- ✓ Database schema is correct
- ✓ BillingAddress model works
- ✓ Constraints are in place

## Razorpay Payment Method

### Before
```
Payment Method: Credit/Debit Card
Description: Visa, Mastercard, Amex
```

### After
```
Payment Method: Razorpay (with logo)
Description: Cards, UPI, NetBanking, Wallets & More
```

## Database Constraints

1. **One-to-One**: One customer can have only one billing address (UNIQUE constraint on customer_id)
2. **Foreign Key**: billing_addresses.customer_id → users.id (CASCADE on delete)
3. **Required Fields**: full_name, address_line1, city, state, postal_code, country, phone (NOT NULL)

## API Reference

### BillingAddress Model

```php
use Karyalay\Models\BillingAddress;

$billingAddressModel = new BillingAddress();

// Get billing address for customer
$address = $billingAddressModel->findByCustomerId($customerId);

// Create or update billing address
$data = [
    'full_name' => 'John Doe',
    'business_name' => 'Acme Corp',
    'business_tax_id' => 'GST123456',
    'address_line1' => '123 Main St',
    'address_line2' => 'Suite 100',
    'city' => 'Mumbai',
    'state' => 'Maharashtra',
    'postal_code' => '400001',
    'country' => 'India',
    'phone' => '+91 9876543210'
];
$result = $billingAddressModel->createOrUpdate($customerId, $data);

// Delete billing address
$result = $billingAddressModel->delete($customerId);
```

## Troubleshooting

### Billing address not pre-filling
- Check if customer has a billing address: `SELECT * FROM billing_addresses WHERE customer_id = ?`
- Verify BillingAddress model is imported in checkout.php

### Order creation fails
- Ensure all required billing fields are provided
- Check error logs in `storage/logs/`
- Verify orders table has billing columns: `SHOW COLUMNS FROM orders LIKE 'billing_%'`

### Profile page not showing billing section
- Clear browser cache
- Check if BillingAddress model is imported
- Verify billing_addresses table exists

## Security Notes

- ✅ All inputs are sanitized using `sanitizeString()`
- ✅ CSRF tokens protect form submissions
- ✅ Authentication required for checkout and profile pages
- ✅ Foreign key constraints ensure data integrity
- ✅ Unique constraint prevents duplicate billing addresses

## Next Steps

1. Test checkout flow with real payment
2. Verify billing address appears in order details
3. Test profile page billing address editing
4. Check admin order view shows billing address
5. Generate invoices using billing address

## Support

For issues:
1. Run test script: `php test-billing-address.php`
2. Check error logs
3. Verify database migrations executed
4. Contact development team
