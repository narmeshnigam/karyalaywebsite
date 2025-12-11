# Orders Payment Columns - Quick Reference

## Database Columns

| Old Column Name | New Column Name | Purpose | Example Value |
|----------------|-----------------|---------|---------------|
| `payment_gateway_id` | `pg_order_id` | Payment gateway's order ID | `order_RqDcRwX1XOWhT7` |
| *(new)* | `pg_payment_id` | Payment gateway's payment ID | `pay_RqDdE5xYz9AbCd` |

## When Each ID is Set

### pg_order_id
- **Set in:** `public/process-payment.php`
- **When:** After creating payment order with Razorpay
- **Before:** User is redirected to payment gateway

### pg_payment_id
- **Set in:** `public/verify-payment.php`
- **When:** After user completes payment and signature is verified
- **Value:** The `razorpay_payment_id` from the payment response

## Code Usage Examples

### Creating an Order
```php
$orderData = [
    'customer_id' => $customerId,
    'plan_id' => $planId,
    'amount' => $amount,
    'currency' => 'INR',
    'status' => 'PENDING',
    'payment_method' => 'card'
];
$order = $orderModel->create($orderData);
```

### Updating with PG Order ID
```php
$orderModel->update($orderId, [
    'pg_order_id' => $razorpayOrderId
]);
```

### Updating with PG Payment ID
```php
$orderModel->update($orderId, [
    'pg_payment_id' => $razorpayPaymentId
]);
```

### Finding Orders
```php
// By PG order ID
$order = $orderModel->findByPgOrderId($pgOrderId);

// By PG payment ID
$order = $orderModel->findByPgPaymentId($pgPaymentId);

// Legacy method (still works)
$order = $orderModel->findByPaymentGatewayId($pgOrderId);
```

## Display Locations

### Admin Panel
- **Orders List:** Shows both IDs under "PG Details" column
- **Order View:** Separate fields for each ID

### Customer Portal
- **Billing History:** Shows `pg_payment_id` in "Payment ID" column
- **Invoice:** Shows `pg_payment_id` as "Payment ID: xxx"

### Emails
- **Payment Success Email:** Shows `pg_payment_id`
- **New Sale Notification:** Shows `pg_payment_id`

## Migration

Run the migration:
```bash
php database/run_migration_040.php
```

Or manually:
```sql
ALTER TABLE orders CHANGE COLUMN payment_gateway_id pg_order_id VARCHAR(255);
ALTER TABLE orders ADD COLUMN pg_payment_id VARCHAR(255) AFTER pg_order_id;
ALTER TABLE orders ADD INDEX idx_pg_payment_id (pg_payment_id);
```

## Search Functionality

Admin orders search now includes both columns:
- Search by order ID
- Search by customer name/email
- Search by `pg_order_id`
- Search by `pg_payment_id`

## Backward Compatibility

Legacy methods are maintained:
- `Order::findByPaymentGatewayId()` → calls `findByPgOrderId()`
- `OrderService::updatePaymentGatewayId()` → calls `updatePgOrderId()`
- `OrderService::getOrderByPaymentGatewayId()` → calls `getOrderByPgOrderId()`
