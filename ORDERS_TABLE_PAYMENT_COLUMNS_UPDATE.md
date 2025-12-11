# Orders Table Payment Columns Update

## Overview
Updated the orders table structure to use more logical and descriptive column names for payment gateway information, and added a new column to store the payment ID returned by the payment gateway.

## Database Changes

### Migration: 040_update_orders_payment_columns.sql

**Changes Made:**
1. Renamed `payment_gateway_id` → `pg_order_id` (stores the payment gateway's order ID)
2. Added `pg_payment_id` column (stores the payment gateway's payment ID when transaction completes)
3. Added index on `pg_payment_id` for faster lookups

**To Run Migration:**
```bash
php database/run_migration_040.php
```

## Code Changes

### 1. Order Model (`classes/Models/Order.php`)
- Updated `create()` method to use `pg_order_id` and `pg_payment_id`
- Updated `update()` method to allow updating both new columns
- Renamed `findByPaymentGatewayId()` → `findByPgOrderId()`
- Added new method `findByPgPaymentId()` to find orders by payment ID
- Added legacy method for backward compatibility

### 2. Order Service (`classes/Services/OrderService.php`)
- Renamed `updatePaymentGatewayId()` → `updatePgOrderId()`
- Added new method `updatePgPaymentId()`
- Renamed `getOrderByPaymentGatewayId()` → `getOrderByPgOrderId()`
- Added new method `getOrderByPgPaymentId()`
- Added legacy methods for backward compatibility

### 3. Payment Processing (`public/process-payment.php`)
- Updated to store payment gateway order ID in `pg_order_id` column

### 4. Payment Verification (`public/verify-payment.php`)
- Updated to use `findByPgOrderId()` method
- Added code to store `pg_payment_id` when payment is verified
- Now captures and stores the Razorpay payment ID (`razorpay_payment_id`)

### 5. Admin Orders List (`admin/orders.php`)
- Updated search to include both `pg_order_id` and `pg_payment_id`
- Changed column header from "Payment ID" to "PG Details"
- Updated display to show both order ID and payment ID:
  - Order: [pg_order_id]
  - Payment: [pg_payment_id]
- Added CSS styling for `.pg-detail-label`

### 6. Admin Order View (`admin/orders/view.php`)
- Split single "Payment Gateway ID" field into two fields:
  - "Payment Gateway Order ID" (`pg_order_id`)
  - "Payment Gateway Payment ID" (`pg_payment_id`)
- Updated form submission to handle both fields

### 7. Customer Billing History (`app/billing/history.php`)
- Added new "Payment ID" column to the table
- Displays `pg_payment_id` (truncated to 20 characters)

### 8. Invoice Page (`app/billing/invoice.php`)
- Updated to show `pg_payment_id` instead of `pg_order_id`
- Changed label from "Ref:" to "Payment ID:"

### 9. Tests (`tests/Integration/PaymentGatewayIntegrationTest.php`)
- Updated assertions to use `pg_order_id` instead of `payment_gateway_id`

## Data Flow

### Order Creation Flow:
1. User selects plan and proceeds to checkout
2. Order created with `PENDING` status
3. Payment gateway order created → `pg_order_id` stored
4. User completes payment on gateway
5. Payment verification receives `razorpay_payment_id`
6. Order updated with `pg_payment_id` and status changed to `SUCCESS`

### Where Each ID is Used:

**pg_order_id (Payment Gateway Order ID):**
- Created when initiating payment with Razorpay
- Used to find the order during payment verification
- Displayed in admin orders list and order view
- Format: `order_xxxxxxxxxxxxx`

**pg_payment_id (Payment Gateway Payment ID):**
- Received when user completes payment
- Stored during payment verification
- Displayed in:
  - Customer billing history
  - Customer invoices
  - Admin orders list
  - Admin order view
  - Payment confirmation emails
- Format: `pay_xxxxxxxxxxxxx`

## Email Templates
No changes required to email templates as they already use the `payment_id` field from the data array passed to them.

## Backward Compatibility
Legacy methods have been added to maintain backward compatibility:
- `Order::findByPaymentGatewayId()` → calls `findByPgOrderId()`
- `OrderService::updatePaymentGatewayId()` → calls `updatePgOrderId()`
- `OrderService::getOrderByPaymentGatewayId()` → calls `getOrderByPgOrderId()`

## Testing Checklist
- [ ] Run migration successfully
- [ ] Create a new order and verify `pg_order_id` is stored
- [ ] Complete a payment and verify `pg_payment_id` is stored
- [ ] Check admin orders list shows both IDs correctly
- [ ] Check admin order view displays both fields
- [ ] Check customer billing history shows payment ID
- [ ] Check invoice shows payment ID
- [ ] Verify search works with both IDs in admin
- [ ] Run existing tests to ensure no regressions

## Benefits
1. **Clearer naming**: `pg_order_id` and `pg_payment_id` are more descriptive
2. **Complete tracking**: Both order and payment IDs are now stored
3. **Better customer service**: Payment ID visible to customers in billing history
4. **Improved admin tools**: Both IDs searchable and visible in admin panel
5. **Audit trail**: Complete payment gateway transaction information preserved
