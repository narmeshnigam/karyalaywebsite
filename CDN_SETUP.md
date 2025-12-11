# CDN Setup Guide

This document provides instructions for setting up a Content Delivery Network (CDN) for static assets to improve performance.

## Overview

The SellerPortal System supports CDN integration for serving static assets (CSS, JavaScript, images, fonts) to improve page load times and reduce server load.

## Benefits of Using a CDN

- **Faster Load Times**: Assets are served from geographically distributed servers closer to users
- **Reduced Server Load**: Static assets are offloaded from the application server
- **Better Caching**: CDN providers offer optimized caching strategies
- **Improved Reliability**: CDN provides redundancy and failover capabilities
- **Bandwidth Savings**: Reduces bandwidth usage on the origin server

## Configuration

### 1. Local Caching (Default)

By default, the system uses local caching with proper cache headers configured in `assets/.htaccess`:

- CSS and JavaScript files: 1 year cache
- Images: 1 year cache
- Fonts: 1 year cache
- Cache-Control headers with `immutable` flag
- Gzip compression enabled

### 2. CDN Integration

To enable CDN, configure the following environment variables in your `.env` file:

```env
# Enable CDN
CDN_ENABLED=true

# CDN Base URL (your CDN domain)
CDN_BASE_URL=https://cdn.yourdomain.com
```

### 3. CDN Provider Setup

#### Option A: CloudFront (AWS)

1. Create a CloudFront distribution:
   - Origin Domain: Your website domain
   - Origin Path: `/assets`
   - Viewer Protocol Policy: Redirect HTTP to HTTPS
   - Allowed HTTP Methods: GET, HEAD, OPTIONS
   - Cache Policy: CachingOptimized

2. Configure environment variables:
```env
CDN_ENABLED=true
CDN_BASE_URL=https://d1234567890.cloudfront.net
CLOUDFRONT_DISTRIBUTION_ID=E1234567890ABC
CLOUDFRONT_DOMAIN=d1234567890.cloudfront.net
```

3. Update DNS (optional):
   - Create CNAME record: `cdn.yourdomain.com` → CloudFront domain
   - Update `CDN_BASE_URL` to use your custom domain

#### Option B: Cloudflare

1. Add your domain to Cloudflare
2. Enable Cloudflare CDN (automatic for all assets)
3. Configure caching rules:
   - Go to Rules → Page Rules
   - Create rule for `/assets/*`
   - Cache Level: Cache Everything
   - Edge Cache TTL: 1 year

4. Configure environment variables:
```env
CDN_ENABLED=true
CDN_BASE_URL=https://yourdomain.com
CLOUDFLARE_ZONE_ID=your-zone-id
```

#### Option C: Custom CDN

For other CDN providers (Fastly, KeyCDN, BunnyCDN, etc.):

1. Configure your CDN to pull from your origin server
2. Set origin path to `/assets`
3. Configure cache rules for static assets
4. Update environment variables:

```env
CDN_ENABLED=true
CDN_BASE_URL=https://your-cdn-domain.com
CUSTOM_CDN_DOMAIN=your-cdn-domain.com
```

## Asset Versioning

The system automatically appends version query parameters to asset URLs based on file modification time:

```php
// Example output: /assets/css/main.css?v=1638360000
asset_url('css/main.css');
```

This ensures that when assets are updated, browsers fetch the new version instead of using cached versions.

## Cache Headers

The `.htaccess` file in the `assets` directory configures the following cache headers:

```apache
Cache-Control: public, max-age=31536000, immutable
Expires: 1 year from access
Vary: Accept-Encoding
```

These headers tell browsers and CDNs to cache assets for 1 year and mark them as immutable (won't change).

## Testing CDN Setup

### 1. Verify Cache Headers

```bash
curl -I https://yourdomain.com/assets/css/main.css
```

Expected headers:
```
Cache-Control: public, max-age=31536000, immutable
Expires: [date 1 year in future]
```

### 2. Verify CDN Serving

```bash
curl -I https://cdn.yourdomain.com/assets/css/main.css
```

Check for CDN-specific headers (e.g., `X-Cache: Hit from cloudfront`)

### 3. Test Asset Loading

1. Open your website in a browser
2. Open Developer Tools → Network tab
3. Reload the page
4. Check that assets are loaded from CDN URL
5. Verify cache status (should show "from disk cache" on subsequent loads)

## Excluding Assets from CDN

To exclude certain assets from CDN (e.g., admin assets), edit `config/cdn.php`:

```php
'exclude_patterns' => [
    '/admin/*',  // Exclude all admin assets
    '/uploads/*', // Exclude user uploads
],
```

## Troubleshooting

### Assets Not Loading from CDN

1. Check `CDN_ENABLED` is set to `true`
2. Verify `CDN_BASE_URL` is correct
3. Check CDN configuration allows access to `/assets/*`
4. Verify CORS headers if needed

### Cache Not Working

1. Check `.htaccess` file exists in `assets` directory
2. Verify `mod_expires` and `mod_headers` are enabled in Apache
3. Check CDN cache rules are configured correctly

### Stale Assets After Update

1. Clear CDN cache (invalidate/purge)
2. For CloudFront: Create invalidation for `/assets/*`
3. For Cloudflare: Purge cache for asset URLs
4. Version query parameters should handle this automatically

## Performance Monitoring

Monitor CDN performance using:

1. **CDN Provider Dashboard**: Check hit rates, bandwidth usage
2. **Browser DevTools**: Verify cache headers and load times
3. **Google PageSpeed Insights**: Test overall page performance
4. **WebPageTest**: Detailed performance analysis

## Best Practices

1. **Always use versioned URLs** for cache busting
2. **Set long cache times** (1 year) for immutable assets
3. **Use CDN for all static assets** (CSS, JS, images, fonts)
4. **Monitor CDN costs** and optimize asset sizes
5. **Compress assets** before uploading (gzip/brotli)
6. **Use WebP images** for better compression
7. **Implement lazy loading** for images below the fold

## Cost Optimization

1. **Minimize asset sizes**: Minify CSS/JS, optimize images
2. **Use appropriate cache times**: Longer = fewer origin requests
3. **Monitor bandwidth usage**: Set up alerts for unusual spikes
4. **Use CDN analytics**: Identify most-requested assets
5. **Consider CDN pricing tiers**: Some offer free tiers for small sites

## Security Considerations

1. **Use HTTPS**: Always serve assets over HTTPS
2. **Configure CORS**: If assets are on different domain
3. **Protect sensitive assets**: Don't serve private files via CDN
4. **Monitor access logs**: Watch for unusual patterns
5. **Use signed URLs**: For private/premium content

## Additional Resources

- [Apache mod_expires Documentation](https://httpd.apache.org/docs/current/mod/mod_expires.html)
- [CloudFront Documentation](https://docs.aws.amazon.com/cloudfront/)
- [Cloudflare CDN Documentation](https://developers.cloudflare.com/cache/)
- [Web Performance Best Practices](https://web.dev/performance/)
