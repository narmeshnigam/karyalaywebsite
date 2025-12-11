# Invoice System Implementation

## Overview
A professional invoice/receipt system has been implemented for completed orders, similar to OpenAI's invoice format. Customers can view and download invoices for all successful payments.

## Features

### Invoice Details
- **Invoice Number**: Auto-generated format `YYYY-MM-SHORTID` (e.g., 2025-12-A1B2C3)
- **Company Information**: Seller details (Karyalay)
- **Customer Information**: Bill-to details from billing address
- **Line Items**: Subscription details with billing period
- **Payment Information**: Amount, date, payment method, receipt number
- **Payment History**: Complete transaction history

### Design
- Clean, professional layout inspired by OpenAI invoices
- Company logo in header
- Two-column layout for From/Bill To addresses
- Prominent "Amount Paid" banner
- Detailed line items table with description, quantity, price, tax
- Payment history section
- Print-friendly styling
- Mobile responsive

## Files Created

### 1. InvoiceService (`classes/Services/InvoiceService.php`)
Service class that generates invoice data from orders:
- `getInvoiceData($orderId)` - Fetches and formats all invoice data
- Combines data from Order, Plan, and User models
- Formats currency, dates, payment methods
- Generates invoice numbers
- Calculates totals and tax

### 2. Invoice Page (`app/invoice.php`)
Customer-facing invoice view:
- Displays formatted invoice
- Print functionality
- Download as PDF (via browser print)
- Access control (customers can only view their own invoices)
- Professional styling with embedded CSS

### 3. Updated Billing History (`app/billing/history.php`)
- Added "View Invoice" button for successful orders
- Opens invoice in new tab
- Only shows for completed payments

## Usage

### Viewing Invoices
1. Customer logs in
2. Goes to Billing History
3. Clicks "📄 View Invoice" for any successful order
4. Invoice opens in new tab

### Downloading Invoices
From the invoice page:
- Click "🖨️ Print" to print directly
- Click "📥 Download PDF" to save as PDF (uses browser print-to-PDF)

### Direct Access
Invoices can be accessed directly via URL:
```
/app/invoice.php?order_id={ORDER_ID}
```

## Data Structure

### Invoice Data Array
```php
[
    'invoice_number' => '2025-12-A1B2C3',
    'invoice_date' => 'December 11, 2025',
    'order_id' => 'uuid',
    'payment_id' => 'pay_xxx',
    'payment_method' => 'Razorpay',
    
    'company' => [
        'name' => 'Karyalay',
        'address_line1' => '548 Market Street',
        'address_line2' => 'PMB 94723',
        'city' => 'San Francisco',
        'state' => 'California',
        'postal_code' => '94104-5401',
        'country' => 'United States',
        'email' => 'support@karyalay.com',
        'phone' => '+1-631-784-8744-157',
        'website' => 'karyalay.com',
        'tax_id' => null
    ],
    
    'customer' => [
        'name' => 'Customer Name',
        'business_name' => 'Business Name',
        'email' => 'customer@example.com',
        'phone' => '+1234567890',
        'address_line1' => 'Address',
        'city' => 'City',
        'state' => 'State',
        'postal_code' => '12345',
        'country' => 'Country',
        'tax_id' => 'TAX123'
    ],
    
    'items' => [
        [
            'description' => 'Plan Name Subscription (1 month)',
            'period' => 'Dec 11, 2025 - Jan 11, 2026',
            'quantity' => 1,
            'unit_price' => 999.00,
            'amount' => 999.00
        ]
    ],
    
    'subtotal' => 999.00,
    'tax' => 0.00,
    'total' => 999.00,
    'amount_paid' => 999.00,
    'currency' => 'INR',
    'currency_symbol' => '₹',
    
    'payment_history' => [
        [
            'method' => 'Razorpay',
            'date' => 'December 11, 2025',
            'amount' => 999.00,
            'receipt_url' => null
        ]
    ]
]
```

## Customization

### Company Details
Update in `InvoiceService.php` line 60-72:
```php
'company' => [
    'name' => 'Your Company',
    'address_line1' => 'Your Address',
    // ... etc
]
```

### Invoice Number Format
Modify `generateInvoiceNumber()` method in `InvoiceService.php`:
```php
private function generateInvoiceNumber(array $order): string
{
    // Current format: 2025-12-A1B2C3
    // Customize as needed
}
```

### Tax Calculation
Add tax logic in `getInvoiceData()` method:
```php
$subtotal = (float)$order['amount'];
$tax = $subtotal * 0.18; // 18% GST example
$total = $subtotal + $tax;
```

### Styling
All styles are embedded in `app/invoice.php` for easy customization:
- Colors: CSS variables at top of `<style>` section
- Layout: Grid and flexbox classes
- Print styles: `@media print` section

## Security

- **Authentication Required**: Users must be logged in
- **Authorization**: Users can only view their own invoices
- **Order Validation**: Only SUCCESS status orders show invoices
- **Input Sanitization**: All output is escaped with `htmlspecialchars()`

## Future Enhancements

1. **PDF Generation**: Use library like TCPDF or mPDF for server-side PDF generation
2. **Email Invoices**: Automatically email invoice on successful payment
3. **Tax Support**: Add GST/VAT calculation based on location
4. **Multi-currency**: Enhanced currency formatting
5. **Invoice Templates**: Multiple template options
6. **Bulk Download**: Download multiple invoices as ZIP
7. **Invoice History**: Track invoice views and downloads

## Testing

Test the invoice system:
1. Complete a test order
2. Go to Billing History
3. Click "View Invoice" on a successful order
4. Verify all details are correct
5. Test print functionality
6. Test download functionality
7. Verify mobile responsiveness

## Notes

- Invoices are generated on-the-fly from order data
- No separate invoice storage needed
- Invoice numbers are deterministic (same order = same invoice number)
- Print-to-PDF works in all modern browsers
- Mobile responsive design included
