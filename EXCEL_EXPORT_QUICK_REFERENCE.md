# Excel Export - Quick Reference

## What's Exported

### 7 Admin List Pages with Export:

1. **Tickets** - Includes customer name, email, phone, assigned admin
2. **Customers** - Includes subscription count, order count, total spent
3. **Orders** - Includes customer details, plan name
4. **Subscriptions** - Includes customer details, plan details
5. **Leads** - All lead information
6. **Plans** - Includes active subscription count, features
7. **Ports** - Includes customer details, subscription, plan name

## How to Export

1. Go to any list page
2. Apply filters (optional)
3. Click green "Export to Excel" button
4. File downloads automatically

## File Format

- **Format:** CSV (Excel-compatible)
- **Encoding:** UTF-8 with BOM
- **Filename:** `{entity}_export_{date}_{time}.csv`
- **Example:** `customers_export_2024-12-11_143052.csv`

## Key Features

✅ **Filter Preservation** - Exports match your current view  
✅ **Foreign Keys Resolved** - Names, emails, phones included  
✅ **UTF-8 Support** - International characters work  
✅ **Excel Compatible** - Opens directly in Excel  
✅ **Timestamped** - Unique filename each time  

## What's Included

### Tickets Export
- Ticket details + Customer (name, email, phone) + Assigned admin

### Customers Export
- Customer details + Active subscriptions + Total orders + Total spent

### Orders Export
- Order details + Customer (name, email, phone) + Plan name

### Subscriptions Export
- Subscription details + Customer (name, email, phone) + Plan (name, price)

### Leads Export
- All lead information (no foreign keys)

### Plans Export
- Plan details + Features + Active subscription count

### Ports Export
- Port details + Customer (name, email, phone) + Subscription + Plan name

## Limits

- **Max Records:** 10,000 per export
- **Format:** CSV only (not XLSX)
- **No Styling:** Plain data, no colors/formatting

## Troubleshooting

**Button not visible?**
- Check you're logged in as admin

**Empty file?**
- Check your filters aren't too restrictive

**Encoding issues?**
- Open in Excel using "Data → From Text/CSV"

## Full Documentation

See `EXCEL_EXPORT_IMPLEMENTATION.md` for complete details.
