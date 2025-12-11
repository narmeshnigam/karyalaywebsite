# Performance Optimization Guide

This document describes the performance optimization techniques implemented in the SellerPortal System.

## Overview

The system implements several performance optimization strategies:

1. **Static Asset Caching** - Browser caching with long expiration times
2. **CDN Integration** - Content delivery network support for global distribution
3. **Lazy Loading** - Deferred loading of images below the fold
4. **Code Splitting** - Modular JavaScript loading
5. **Database Optimization** - Indexes and query optimization
6. **Pagination** - Efficient handling of large data sets

## 1. Static Asset Caching

### Implementation

Static assets (CSS, JavaScript, images, fonts) are configured with long cache times (1 year) using Apache `.htaccess` directives.

**Location:** `assets/.htaccess`

**Cache Headers:**
- `Cache-Control: public, max-age=31536000, immutable`
- `Expires: 1 year from access`
- Gzip compression enabled

**Asset Versioning:**

The system automatically appends version query parameters based on file modification time:

```php
// Usage in templates
<link rel="stylesheet" href="<?php echo css_url('main.css'); ?>">
// Output: /assets/css/main.css?v=1638360000
```

This ensures browsers fetch new versions when files are updated.

### Benefits

- Reduces server requests for repeat visitors
- Decreases bandwidth usage
- Improves page load times
- Better user experience

## 2. Lazy Loading Images

### Implementation

Images are loaded only when they enter or are about to enter the viewport, reducing initial page load time.

**Native Lazy Loading:**

Modern browsers support native lazy loading:

```php
// Using helper function
echo render_image('/assets/images/photo.jpg', 'Photo description', [
    'lazy' => true,
    'width' => 800,
    'height' => 600
]);

// Output:
// <img src="data:image/svg+xml,..." data-src="/assets/images/photo.jpg" 
//      loading="lazy" alt="Photo description" width="800" height="600">
```

**JavaScript Fallback:**

For older browsers, the `lazy-load.js` script uses Intersection Observer API:

```javascript
// Automatically loads images when they enter viewport
// Fallback for browsers without Intersection Observer support
```

### Usage Examples

**Basic Lazy Loading:**

```php
<?php echo render_image(
    image_url('hero-banner.jpg'),
    'Hero banner',
    ['lazy' => true]
); ?>
```

**Responsive Images with Lazy Loading:**

```php
<?php echo render_responsive_image(
    image_url('product.jpg'),
    'Product image',
    [
        '1x' => image_url('product.jpg'),
        '2x' => image_url('product@2x.jpg')
    ],
    ['lazy' => true, 'width' => 400, 'height' => 300]
); ?>
```

**Disable Lazy Loading (Above the Fold):**

```php
<?php echo render_image(
    image_url('logo.png'),
    'Company logo',
    ['lazy' => false]  // Load immediately
); ?>
```

### Best Practices

1. **Disable lazy loading for above-the-fold images** - These should load immediately
2. **Always provide width and height** - Prevents layout shift
3. **Use appropriate alt text** - For accessibility
4. **Optimize image sizes** - Compress before uploading
5. **Use responsive images** - Serve appropriate sizes for different screens

## 3. Code Splitting

### Route-Based Loading

JavaScript files are loaded based on the current page/route to reduce initial bundle size.

**Implementation:**

```php
// In footer template
<?php
$page = get_current_page();
$scripts = ['navigation.js'];

// Load page-specific scripts
if (is_admin()) {
    $scripts[] = 'admin.js';
} elseif (strpos($_SERVER['REQUEST_URI'], '/app/') === 0) {
    $scripts[] = 'customer-portal.js';
}

foreach ($scripts as $script) {
    echo '<script src="' . js_url($script) . '"></script>';
}
?>
```

### Module-Based Loading

Load JavaScript modules only when needed:

```html
<!-- Load form validation only on pages with forms -->
<?php if ($has_form): ?>
<script src="<?php echo js_url('form-validation.js'); ?>"></script>
<?php endif; ?>
```

### Benefits

- Smaller initial JavaScript bundle
- Faster initial page load
- Better caching (unchanged modules stay cached)
- Reduced bandwidth usage

## 4. Database Query Optimization

### Indexes

Indexes are created on frequently queried fields to improve query performance.

**Implemented Indexes:**

```sql
-- Users table
CREATE INDEX idx_users_email ON users(email);
CREATE INDEX idx_users_role ON users(role);

-- Subscriptions table
CREATE INDEX idx_subscriptions_customer ON subscriptions(customer_id);
CREATE INDEX idx_subscriptions_status ON subscriptions(status);
CREATE INDEX idx_subscriptions_end_date ON subscriptions(end_date);

-- Orders table
CREATE INDEX idx_orders_customer ON orders(customer_id);
CREATE INDEX idx_orders_status ON orders(status);

-- Ports table
CREATE INDEX idx_ports_status ON ports(status);
CREATE INDEX idx_ports_plan ON ports(plan_id);

-- Tickets table
CREATE INDEX idx_tickets_customer ON tickets(customer_id);
CREATE INDEX idx_tickets_status ON tickets(status);
```

### Connection Pooling

PHP PDO persistent connections are used to reduce connection overhead:

```php
// In database configuration
$options = [
    PDO::ATTR_PERSISTENT => true,
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
];
```

### Query Result Caching

Frequently accessed data is cached to reduce database load:

```php
// Example: Cache active plans
$cacheKey = 'active_plans';
$plans = apcu_fetch($cacheKey);

if ($plans === false) {
    $plans = $planService->getActivePlans();
    apcu_store($cacheKey, $plans, 3600); // Cache for 1 hour
}
```

### Query Optimization Tips

1. **Use LIMIT clauses** - Don't fetch more data than needed
2. **Select specific columns** - Avoid `SELECT *`
3. **Use prepared statements** - Better performance and security
4. **Avoid N+1 queries** - Use JOINs or batch queries
5. **Use EXPLAIN** - Analyze query performance

## 5. Pagination

### Implementation

Large lists are paginated to reduce memory usage and improve load times.

**Example:**

```php
// Get page number from query string
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$perPage = 20;
$offset = ($page - 1) * $perPage;

// Fetch paginated results
$customers = $customerService->getCustomers($perPage, $offset);
$totalCustomers = $customerService->getTotalCustomers();
$totalPages = ceil($totalCustomers / $perPage);
```

**Pagination UI:**

```php
<nav aria-label="Pagination">
    <ul class="pagination">
        <?php if ($page > 1): ?>
        <li><a href="?page=<?php echo $page - 1; ?>">Previous</a></li>
        <?php endif; ?>
        
        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
        <li class="<?php echo $i === $page ? 'active' : ''; ?>">
            <a href="?page=<?php echo $i; ?>"><?php echo $i; ?></a>
        </li>
        <?php endfor; ?>
        
        <?php if ($page < $totalPages): ?>
        <li><a href="?page=<?php echo $page + 1; ?>">Next</a></li>
        <?php endif; ?>
    </ul>
</nav>
```

### Benefits

- Reduced memory usage
- Faster page loads
- Better user experience for large datasets
- Lower database load

## 6. Additional Optimizations

### Minification

Minify CSS and JavaScript files in production:

```bash
# Using online tools or build tools
npm install -g clean-css-cli uglify-js

# Minify CSS
cleancss -o assets/css/main.min.css assets/css/main.css

# Minify JavaScript
uglifyjs assets/js/main.js -o assets/js/main.min.js
```

### Compression

Enable Gzip/Brotli compression in Apache:

```apache
# In .htaccess
<IfModule mod_deflate.c>
    AddOutputFilterByType DEFLATE text/html text/plain text/xml text/css text/javascript application/javascript application/json
</IfModule>
```

### Image Optimization

Optimize images before uploading:

```bash
# Using ImageMagick
convert input.jpg -quality 85 -strip output.jpg

# Using WebP format
cwebp -q 80 input.jpg -o output.webp
```

### DNS Prefetching

Prefetch DNS for external resources:

```html
<link rel="dns-prefetch" href="//cdn.example.com">
<link rel="dns-prefetch" href="//fonts.googleapis.com">
```

### Preconnect

Establish early connections to important third-party origins:

```html
<link rel="preconnect" href="https://cdn.example.com">
<link rel="preconnect" href="https://fonts.googleapis.com">
```

## Performance Monitoring

### Tools

1. **Google PageSpeed Insights** - Overall performance score
2. **WebPageTest** - Detailed performance analysis
3. **Chrome DevTools** - Network and performance profiling
4. **GTmetrix** - Performance and optimization recommendations

### Metrics to Monitor

- **First Contentful Paint (FCP)** - Time to first content render
- **Largest Contentful Paint (LCP)** - Time to largest content render
- **Time to Interactive (TTI)** - Time until page is fully interactive
- **Total Blocking Time (TBT)** - Time page is blocked from user input
- **Cumulative Layout Shift (CLS)** - Visual stability

### Performance Targets

- **LCP** < 2.5 seconds
- **FID** < 100 milliseconds
- **CLS** < 0.1
- **Page Load Time** < 3 seconds on 4G connection

## Testing Performance Improvements

### Before and After Comparison

1. **Measure baseline performance** using PageSpeed Insights
2. **Implement optimizations** one at a time
3. **Measure again** to verify improvements
4. **Document results** for future reference

### Load Testing

Test performance under load:

```bash
# Using Apache Bench
ab -n 1000 -c 10 https://yourdomain.com/

# Using wrk
wrk -t12 -c400 -d30s https://yourdomain.com/
```

## Troubleshooting

### Slow Page Loads

1. Check database query performance with EXPLAIN
2. Verify cache headers are set correctly
3. Check for large unoptimized images
4. Review JavaScript bundle sizes
5. Check for blocking resources

### High Server Load

1. Enable caching (APCu, Redis)
2. Optimize database queries
3. Implement pagination
4. Use CDN for static assets
5. Enable compression

### Cache Issues

1. Clear browser cache
2. Verify cache headers
3. Check .htaccess configuration
4. Verify CDN cache settings
5. Use versioned asset URLs

## Best Practices Summary

1. ✅ Use long cache times for static assets
2. ✅ Implement lazy loading for images
3. ✅ Split JavaScript by route/module
4. ✅ Add database indexes on frequently queried fields
5. ✅ Paginate large lists
6. ✅ Minify and compress assets
7. ✅ Optimize images before uploading
8. ✅ Use CDN for static assets
9. ✅ Monitor performance regularly
10. ✅ Test on real devices and connections

## Additional Resources

- [Web.dev Performance](https://web.dev/performance/)
- [MDN Web Performance](https://developer.mozilla.org/en-US/docs/Web/Performance)
- [Google PageSpeed Insights](https://pagespeed.web.dev/)
- [WebPageTest](https://www.webpagetest.org/)
- [Apache Performance Tuning](https://httpd.apache.org/docs/2.4/misc/perf-tuning.html)
