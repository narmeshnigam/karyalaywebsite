# Pagination Usage Guide

This document provides examples of how to use the pagination system in the SellerPortal System.

## Overview

The `PaginationService` class provides a complete pagination solution for large data sets, including:

- Automatic page calculation
- Database query helpers (LIMIT/OFFSET)
- HTML rendering with accessibility support
- Customizable appearance and behavior

## Basic Usage

### 1. Simple Pagination

```php
<?php
require_once __DIR__ . '/includes/template_helpers.php';
require_once __DIR__ . '/classes/Database/Connection.php';

use Karyalay\Database\Connection;
use Karyalay\Services\PaginationService;

// Get database connection
$db = Connection::getInstance();

// Count total items
$totalCustomers = $db->query("SELECT COUNT(*) FROM users WHERE role = 'CUSTOMER'")->fetchColumn();

// Create pagination instance
$pagination = new PaginationService($totalCustomers, 20); // 20 items per page

// Get paginated data
$stmt = $db->prepare("
    SELECT * FROM users 
    WHERE role = 'CUSTOMER' 
    ORDER BY created_at DESC 
    LIMIT ? OFFSET ?
");
$stmt->execute([$pagination->getLimit(), $pagination->getOffset()]);
$customers = $stmt->fetchAll();

// Display customers
foreach ($customers as $customer) {
    echo '<div>' . htmlspecialchars($customer['name']) . '</div>';
}

// Render pagination
echo $pagination->render();
?>
```

### 2. Using Helper Functions

```php
<?php
require_once __DIR__ . '/includes/template_helpers.php';

// Get pagination instance
$pagination = get_pagination($totalItems, 20);

// Render pagination HTML
echo render_pagination($totalItems, 20);
?>
```

### 3. From Database Query

```php
<?php
use Karyalay\Services\PaginationService;

// Create pagination from count query
$pagination = PaginationService::fromQuery(
    "SELECT COUNT(*) FROM orders WHERE status = 'SUCCESS'",
    $db,
    20
);

// Get paginated results
$stmt = $db->prepare("
    SELECT * FROM orders 
    WHERE status = 'SUCCESS' 
    ORDER BY created_at DESC 
    LIMIT ? OFFSET ?
");
$stmt->execute([$pagination->getLimit(), $pagination->getOffset()]);
$orders = $stmt->fetchAll();
?>
```

## Advanced Usage

### Custom Rendering Options

```php
<?php
// Render with custom options
echo $pagination->render([
    'show_info' => true,           // Show "Showing 1-20 of 100"
    'show_first_last' => true,     // Show first/last page links
    'range' => 3,                  // Show 3 pages on each side of current
    'class' => 'custom-pagination' // Custom CSS class
]);
?>
```

### Manual Pagination HTML

```php
<?php
// Build custom pagination HTML
if ($pagination->getTotalPages() > 1): ?>
<nav class="pagination">
    <div class="pagination-info">
        <?php echo $pagination->getInfoText(); ?>
    </div>
    
    <ul class="pagination-list">
        <?php if ($pagination->hasPreviousPage()): ?>
        <li>
            <a href="<?php echo $pagination->getPageUrl($pagination->getPreviousPage()); ?>">
                Previous
            </a>
        </li>
        <?php endif; ?>
        
        <?php foreach ($pagination->getPageRange(2) as $page): ?>
        <li class="<?php echo $page === $pagination->getCurrentPage() ? 'active' : ''; ?>">
            <a href="<?php echo $pagination->getPageUrl($page); ?>">
                <?php echo $page; ?>
            </a>
        </li>
        <?php endforeach; ?>
        
        <?php if ($pagination->hasNextPage()): ?>
        <li>
            <a href="<?php echo $pagination->getPageUrl($pagination->getNextPage()); ?>">
                Next
            </a>
        </li>
        <?php endif; ?>
    </ul>
</nav>
<?php endif; ?>
```

## Real-World Examples

### Example 1: Customer List Page

```php
<?php
// admin/customers.php
require_once __DIR__ . '/../includes/auth_helpers.php';
require_once __DIR__ . '/../includes/template_helpers.php';
require_once __DIR__ . '/../classes/Database/Connection.php';

use Karyalay\Database\Connection;
use Karyalay\Services\PaginationService;

// Check authentication
require_admin_auth();

$db = Connection::getInstance();

// Get filters from query string
$search = $_GET['search'] ?? '';
$role = $_GET['role'] ?? '';

// Build WHERE clause
$where = ["1=1"];
$params = [];

if (!empty($search)) {
    $where[] = "(name LIKE ? OR email LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if (!empty($role)) {
    $where[] = "role = ?";
    $params[] = $role;
}

$whereClause = implode(' AND ', $where);

// Count total items
$countStmt = $db->prepare("SELECT COUNT(*) FROM users WHERE $whereClause");
$countStmt->execute($params);
$totalCustomers = $countStmt->fetchColumn();

// Create pagination
$pagination = new PaginationService($totalCustomers, 20);

// Get paginated results
$stmt = $db->prepare("
    SELECT * FROM users 
    WHERE $whereClause 
    ORDER BY created_at DESC 
    LIMIT ? OFFSET ?
");
$stmt->execute(array_merge($params, [$pagination->getLimit(), $pagination->getOffset()]));
$customers = $stmt->fetchAll();

include_header('Customers');
?>

<div class="admin-content">
    <h1>Customers</h1>
    
    <!-- Search and filters -->
    <form method="GET" class="filters">
        <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Search...">
        <select name="role">
            <option value="">All Roles</option>
            <option value="CUSTOMER" <?php echo $role === 'CUSTOMER' ? 'selected' : ''; ?>>Customer</option>
            <option value="ADMIN" <?php echo $role === 'ADMIN' ? 'selected' : ''; ?>>Admin</option>
        </select>
        <button type="submit">Filter</button>
    </form>
    
    <!-- Customer table -->
    <table class="data-table">
        <thead>
            <tr>
                <th>Name</th>
                <th>Email</th>
                <th>Role</th>
                <th>Created</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($customers as $customer): ?>
            <tr>
                <td><?php echo htmlspecialchars($customer['name']); ?></td>
                <td><?php echo htmlspecialchars($customer['email']); ?></td>
                <td><?php echo htmlspecialchars($customer['role']); ?></td>
                <td><?php echo format_date($customer['created_at']); ?></td>
                <td>
                    <a href="/admin/customers/view.php?id=<?php echo $customer['id']; ?>">View</a>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    
    <!-- Pagination -->
    <?php echo $pagination->render(); ?>
</div>

<?php include_footer(); ?>
```

### Example 2: Blog Posts with Pagination

```php
<?php
// public/blog.php
require_once __DIR__ . '/../includes/template_helpers.php';
require_once __DIR__ . '/../classes/Database/Connection.php';

use Karyalay\Database\Connection;
use Karyalay\Services\PaginationService;

$db = Connection::getInstance();

// Count published posts
$totalPosts = $db->query("
    SELECT COUNT(*) FROM blog_posts 
    WHERE status = 'PUBLISHED'
")->fetchColumn();

// Create pagination (10 posts per page)
$pagination = new PaginationService($totalPosts, 10);

// Get paginated posts
$stmt = $db->prepare("
    SELECT * FROM blog_posts 
    WHERE status = 'PUBLISHED' 
    ORDER BY published_at DESC 
    LIMIT ? OFFSET ?
");
$stmt->execute([$pagination->getLimit(), $pagination->getOffset()]);
$posts = $stmt->fetchAll();

include_header('Blog');
?>

<div class="blog-container">
    <h1>Blog</h1>
    
    <div class="blog-posts">
        <?php foreach ($posts as $post): ?>
        <article class="blog-post-card">
            <h2>
                <a href="/blog/<?php echo $post['slug']; ?>">
                    <?php echo htmlspecialchars($post['title']); ?>
                </a>
            </h2>
            <div class="post-meta">
                <time datetime="<?php echo $post['published_at']; ?>">
                    <?php echo format_date($post['published_at']); ?>
                </time>
            </div>
            <p><?php echo htmlspecialchars($post['excerpt']); ?></p>
            <a href="/blog/<?php echo $post['slug']; ?>" class="read-more">Read More</a>
        </article>
        <?php endforeach; ?>
    </div>
    
    <!-- Pagination -->
    <?php echo $pagination->render(['range' => 3]); ?>
</div>

<?php include_footer(); ?>
```

### Example 3: API Endpoint with Pagination

```php
<?php
// api/orders.php
require_once __DIR__ . '/../includes/auth_helpers.php';
require_once __DIR__ . '/../classes/Database/Connection.php';

use Karyalay\Database\Connection;
use Karyalay\Services\PaginationService;

// Check authentication
require_api_auth();

$db = Connection::getInstance();

// Get pagination parameters
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$perPage = isset($_GET['per_page']) ? min((int)$_GET['per_page'], 100) : 20;

// Count total orders
$totalOrders = $db->query("SELECT COUNT(*) FROM orders")->fetchColumn();

// Create pagination
$pagination = new PaginationService($totalOrders, $perPage, $page);

// Get paginated orders
$stmt = $db->prepare("
    SELECT * FROM orders 
    ORDER BY created_at DESC 
    LIMIT ? OFFSET ?
");
$stmt->execute([$pagination->getLimit(), $pagination->getOffset()]);
$orders = $stmt->fetchAll();

// Return JSON response
header('Content-Type: application/json');
echo json_encode([
    'data' => $orders,
    'pagination' => [
        'current_page' => $pagination->getCurrentPage(),
        'per_page' => $pagination->getPerPage(),
        'total_items' => $pagination->getTotalItems(),
        'total_pages' => $pagination->getTotalPages(),
        'has_previous' => $pagination->hasPreviousPage(),
        'has_next' => $pagination->hasNextPage(),
    ]
]);
?>
```

## Pagination Methods

### Query Methods

```php
// Get current page number
$page = $pagination->getCurrentPage(); // e.g., 2

// Get items per page
$perPage = $pagination->getPerPage(); // e.g., 20

// Get total items
$total = $pagination->getTotalItems(); // e.g., 150

// Get total pages
$totalPages = $pagination->getTotalPages(); // e.g., 8

// Get database OFFSET
$offset = $pagination->getOffset(); // e.g., 20 (for page 2)

// Get database LIMIT
$limit = $pagination->getLimit(); // e.g., 20
```

### Navigation Methods

```php
// Check if there's a previous page
if ($pagination->hasPreviousPage()) {
    // Show previous button
}

// Check if there's a next page
if ($pagination->hasNextPage()) {
    // Show next button
}

// Get previous page number
$prevPage = $pagination->getPreviousPage(); // e.g., 1 or null

// Get next page number
$nextPage = $pagination->getNextPage(); // e.g., 3 or null

// Get page URL
$url = $pagination->getPageUrl(3); // e.g., "/customers?page=3"

// Get page range for display
$pages = $pagination->getPageRange(2); // e.g., [1, 2, 3, 4, 5]
```

### Display Methods

```php
// Get info text
$info = $pagination->getInfoText(); // e.g., "Showing 21-40 of 150"

// Render complete pagination HTML
echo $pagination->render();
```

## Styling Pagination

The pagination HTML uses the following CSS classes:

```css
.pagination              /* Container */
.pagination-info         /* Info text */
.pagination-list         /* List of page links */
.pagination-item         /* Individual page item */
.pagination-link         /* Page link */
.pagination-active       /* Active page */
.pagination-disabled     /* Disabled prev/next */
.pagination-ellipsis     /* Ellipsis (...) */
```

### Custom Styles Example

```css
/* Custom pagination theme */
.pagination {
    margin: 2rem 0;
}

.pagination-link {
    background: #f0f0f0;
    color: #333;
    border-radius: 4px;
}

.pagination-link:hover {
    background: #e0e0e0;
}

.pagination-active .pagination-link {
    background: #007bff;
    color: white;
}
```

## Best Practices

1. **Always use pagination for large lists** (> 50 items)
2. **Set reasonable per-page limits** (10-50 items)
3. **Cache total count queries** for better performance
4. **Preserve filters in pagination links** (handled automatically)
5. **Use LIMIT/OFFSET efficiently** with proper indexes
6. **Show info text** to help users understand their position
7. **Make pagination accessible** with proper ARIA attributes
8. **Test with different data sizes** (empty, 1 page, many pages)

## Performance Tips

### Cache Total Count

```php
use Karyalay\Services\CacheService;

// Cache total count for 5 minutes
$totalItems = CacheService::remember('customers_count', function() use ($db) {
    return $db->query("SELECT COUNT(*) FROM users WHERE role = 'CUSTOMER'")->fetchColumn();
}, 300);

$pagination = new PaginationService($totalItems, 20);
```

### Use Covering Indexes

```sql
-- Create index for pagination query
CREATE INDEX idx_users_role_created ON users(role, created_at);

-- Query will use index efficiently
SELECT * FROM users 
WHERE role = 'CUSTOMER' 
ORDER BY created_at DESC 
LIMIT 20 OFFSET 40;
```

### Avoid Large Offsets

For very large datasets, consider cursor-based pagination:

```php
// Instead of OFFSET, use WHERE with last seen ID
$lastId = $_GET['last_id'] ?? 0;

$stmt = $db->prepare("
    SELECT * FROM orders 
    WHERE id > ? 
    ORDER BY id ASC 
    LIMIT 20
");
$stmt->execute([$lastId]);
```

## Troubleshooting

### Pagination Not Working

1. Check that `$_GET['page']` is being passed correctly
2. Verify total items count is accurate
3. Ensure LIMIT/OFFSET values are correct
4. Check for JavaScript errors preventing link clicks

### Wrong Page Count

1. Verify total items query is correct
2. Check for filters affecting count
3. Ensure per-page value is positive

### Styling Issues

1. Check CSS classes are correct
2. Verify CSS file is loaded
3. Check for CSS conflicts with other styles

## Additional Resources

- [MySQL LIMIT Optimization](https://dev.mysql.com/doc/refman/8.0/en/limit-optimization.html)
- [Pagination Best Practices](https://www.smashingmagazine.com/2016/03/pagination-infinite-scrolling-load-more-buttons/)
- [Accessible Pagination](https://www.a11ymatters.com/pattern/pagination/)
