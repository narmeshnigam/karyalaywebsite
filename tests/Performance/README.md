# Performance Testing Suite

This directory contains performance tests for the SellerPortal System. These tests measure page load times, database query performance, and system behavior with large datasets.

## Test Files

### 1. PageLoadTimeTest.php
Tests page load times for public pages to ensure they meet performance targets.

**Targets:**
- Public pages: < 2.5 seconds
- Admin pages: < 3.0 seconds
- Static assets: < 1.0 second

**Tests:**
- Home page load time
- Pricing page load time
- Modules page load time
- Features page load time
- Blog page load time
- Contact page load time
- Static asset load time
- Concurrent request performance

### 2. DatabaseQueryPerformanceTest.php
Tests database query performance to identify slow queries.

**Targets:**
- Simple queries: < 100ms
- Complex queries: < 500ms

**Tests:**
- User lookup by email
- Active subscriptions query
- Available ports query
- Customer orders query
- Ticket list query
- Complex join queries
- Pagination queries
- Search queries
- Index usage verification

### 3. LargeDatasetTest.php
Tests system performance with large datasets to ensure pagination and memory management work correctly.

**Targets:**
- Memory usage: < 128MB
- Query time: < 500ms

**Tests:**
- Large user list with pagination
- Large subscription list with filters
- Large order history
- Ticket list with message counts
- Port list with assignments
- Blog post list
- Memory usage across multiple pages
- Count query performance

### 4. PerformanceReportTest.php
Generates a comprehensive performance report with metrics and recommendations.

**Report Includes:**
- Database statistics
- Table sizes and row counts
- Index usage
- Query performance analysis
- Cache statistics
- Performance recommendations

## Running Performance Tests

### Run All Performance Tests

```bash
./vendor/bin/phpunit tests/Performance/
```

### Run Specific Test Suite

```bash
# Page load time tests
./vendor/bin/phpunit tests/Performance/PageLoadTimeTest.php

# Database query performance tests
./vendor/bin/phpunit tests/Performance/DatabaseQueryPerformanceTest.php

# Large dataset tests
./vendor/bin/phpunit tests/Performance/LargeDatasetTest.php

# Generate performance report
./vendor/bin/phpunit tests/Performance/PerformanceReportTest.php
```

### Run with Verbose Output

```bash
./vendor/bin/phpunit --testdox tests/Performance/
```

## Prerequisites

### Environment Setup

1. **Database with Test Data**
   - Ensure your test database has sufficient data for meaningful tests
   - Recommended: At least 1000 users, 500 subscriptions, 200 orders

2. **Environment Variables**
   - Set `APP_URL` environment variable for page load tests
   - Example: `export APP_URL=http://localhost`

3. **Web Server Running**
   - Ensure Apache/Nginx is running for page load tests
   - Application should be accessible at the configured URL

4. **Cache Extension (Optional)**
   - Install APCu for cache statistics
   - `sudo apt-get install php-apcu` (Ubuntu/Debian)
   - `brew install php-apcu` (macOS)

## Interpreting Results

### Page Load Times

✓ **Good**: < 2.5 seconds
⚠ **Warning**: 2.5 - 4.0 seconds
❌ **Poor**: > 4.0 seconds

**If slow:**
- Check server resources (CPU, memory)
- Review database query performance
- Verify cache headers are set
- Check for large unoptimized images
- Review JavaScript bundle sizes

### Database Query Performance

✓ **Good**: < 100ms for simple queries, < 500ms for complex
⚠ **Warning**: 100-200ms for simple, 500-1000ms for complex
❌ **Poor**: > 200ms for simple, > 1000ms for complex

**If slow:**
- Run EXPLAIN on slow queries
- Add missing indexes
- Optimize JOIN operations
- Consider query result caching
- Review WHERE clause conditions

### Memory Usage

✓ **Good**: < 64MB
⚠ **Warning**: 64-128MB
❌ **Poor**: > 128MB

**If high:**
- Implement pagination
- Use LIMIT clauses
- Clear large arrays after use
- Review memory-intensive operations
- Consider streaming large results

### Cache Hit Rate

✓ **Good**: > 90%
⚠ **Warning**: 80-90%
❌ **Poor**: < 80%

**If low:**
- Increase cache TTL
- Review cache key strategy
- Ensure cache is properly warmed
- Check cache size limits
- Review cache invalidation logic

## Performance Optimization Checklist

### Database
- [ ] Indexes on frequently queried columns
- [ ] Composite indexes for common filter combinations
- [ ] Avoid SELECT * queries
- [ ] Use LIMIT clauses
- [ ] Optimize JOIN operations
- [ ] Use prepared statements
- [ ] Enable query result caching

### Application
- [ ] Implement pagination for large lists
- [ ] Use persistent database connections
- [ ] Enable APCu/Redis caching
- [ ] Optimize session handling
- [ ] Minimize external API calls
- [ ] Use lazy loading for images
- [ ] Implement code splitting

### Frontend
- [ ] Minify CSS and JavaScript
- [ ] Enable Gzip/Brotli compression
- [ ] Set proper cache headers
- [ ] Optimize images (compress, WebP)
- [ ] Use CDN for static assets
- [ ] Implement lazy loading
- [ ] Reduce HTTP requests

### Infrastructure
- [ ] Enable OPcache for PHP
- [ ] Configure PHP-FPM properly
- [ ] Tune MySQL/MariaDB settings
- [ ] Use HTTP/2
- [ ] Enable keep-alive connections
- [ ] Configure proper server resources

## Continuous Performance Monitoring

### Automated Testing
Add performance tests to your CI/CD pipeline:

```yaml
# Example GitHub Actions workflow
- name: Run Performance Tests
  run: ./vendor/bin/phpunit tests/Performance/
  
- name: Check Performance Thresholds
  run: |
    if grep -q "SLOW" performance-report.txt; then
      echo "Performance degradation detected"
      exit 1
    fi
```

### Regular Monitoring
- Run performance tests weekly
- Compare results over time
- Track performance trends
- Set up alerts for degradation

### Production Monitoring
- Use APM tools (New Relic, Datadog)
- Monitor real user metrics (RUM)
- Track Core Web Vitals
- Set up performance budgets

## Troubleshooting

### Tests Timing Out
- Increase PHP max_execution_time
- Check database connection
- Verify web server is running
- Review test data volume

### Inconsistent Results
- Run tests multiple times
- Check server load during tests
- Ensure consistent test data
- Clear caches between runs

### High Memory Usage
- Check for memory leaks
- Review large array operations
- Verify proper resource cleanup
- Monitor PHP memory_limit

## Additional Resources

- [Performance Optimization Guide](../../PERFORMANCE_OPTIMIZATION.md)
- [Database Optimization Guide](../../DATABASE_OPTIMIZATION.md)
- [CDN Setup Guide](../../CDN_SETUP.md)
- [Pagination Usage Guide](../../PAGINATION_USAGE.md)

## Contributing

When adding new performance tests:

1. Follow existing test structure
2. Set appropriate performance targets
3. Include helpful output messages
4. Document test purpose and targets
5. Update this README

## Support

For questions or issues with performance tests:
- Review existing documentation
- Check test output for specific errors
- Consult performance optimization guides
- Contact the development team
