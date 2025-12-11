# Performance Testing Guide

This guide explains how to run and interpret performance tests for the SellerPortal System.

## Quick Start

### Run All Performance Tests

```bash
# Make script executable (first time only)
chmod +x run-performance-tests.sh

# Run all tests
./run-performance-tests.sh

# Run with page load tests (requires web server)
./run-performance-tests.sh --with-page-load
```

### Run Individual Test Suites

```bash
# Database query performance
./vendor/bin/phpunit tests/Performance/DatabaseQueryPerformanceTest.php

# Large dataset handling
./vendor/bin/phpunit tests/Performance/LargeDatasetTest.php

# Performance report
./vendor/bin/phpunit tests/Performance/PerformanceReportTest.php

# Page load times (requires web server)
./vendor/bin/phpunit tests/Performance/PageLoadTimeTest.php
```

## Test Suites

### 1. Database Query Performance Tests

**Purpose:** Measure execution time of common database queries

**What it tests:**
- User lookup by email
- Active subscriptions query
- Available ports query
- Customer orders query
- Ticket list query
- Complex JOIN queries
- Pagination queries
- Search queries
- Index usage verification

**Performance Targets:**
- Simple queries: < 100ms
- Complex queries: < 500ms

**How to interpret results:**

✅ **Good:** Query time < target threshold  
⚠️ **Warning:** Query time 50-100% of target  
❌ **Poor:** Query time > target threshold

**If tests fail:**
1. Run `EXPLAIN` on the slow query
2. Check if indexes are being used
3. Review WHERE clause conditions
4. Consider adding composite indexes
5. Check for N+1 query problems

### 2. Large Dataset Handling Tests

**Purpose:** Ensure system handles large datasets efficiently

**What it tests:**
- Paginated list queries
- Memory usage with large result sets
- JOIN operations with multiple tables
- Aggregation queries
- Count queries for pagination
- Memory usage across multiple pages

**Performance Targets:**
- Memory usage: < 128MB
- Query time: < 500ms
- No memory leaks

**How to interpret results:**

✅ **Good:** Memory < 64MB, queries < 200ms  
⚠️ **Warning:** Memory 64-128MB, queries 200-500ms  
❌ **Poor:** Memory > 128MB, queries > 500ms

**If tests fail:**
1. Verify pagination is implemented
2. Check for large arrays in memory
3. Review LIMIT clauses
4. Ensure proper resource cleanup
5. Consider streaming large results

### 3. Performance Report Test

**Purpose:** Generate comprehensive performance report

**What it includes:**
- Database statistics (size, connections, uptime)
- Table sizes and row counts
- Index usage per table
- Query performance analysis
- Cache statistics (if APCu enabled)
- Performance recommendations

**How to use:**
- Run regularly to track performance trends
- Compare reports over time
- Use recommendations to guide optimization
- Share with team for performance reviews

### 4. Page Load Time Tests

**Purpose:** Measure actual page load times

**Prerequisites:**
- Web server must be running (Apache/Nginx)
- Application must be accessible
- Set `APP_URL` environment variable

**What it tests:**
- Home page load time
- Pricing page load time
- Modules page load time
- Features page load time
- Blog page load time
- Contact page load time
- Static asset load time
- Concurrent request handling

**Performance Targets:**
- Public pages: < 2.5 seconds
- Admin pages: < 3.0 seconds
- Static assets: < 1.0 second

**Setup:**

```bash
# Set application URL
export APP_URL=http://localhost

# Or add to .env file
echo "APP_URL=http://localhost" >> .env
```

**If tests fail:**
1. Check server resources (CPU, memory)
2. Review database query performance
3. Verify cache headers are set
4. Check for large unoptimized images
5. Review JavaScript bundle sizes
6. Enable compression (Gzip/Brotli)

## Performance Metrics Explained

### Query Execution Time

**What it measures:** Time from query start to results returned

**Good values:**
- Simple SELECT: < 10ms
- SELECT with WHERE: < 50ms
- SELECT with JOIN: < 100ms
- Complex aggregations: < 500ms

**How to improve:**
- Add indexes on WHERE columns
- Use composite indexes for multiple columns
- Avoid SELECT *
- Use LIMIT clauses
- Optimize JOIN conditions

### Memory Usage

**What it measures:** RAM consumed during operation

**Good values:**
- Per request: < 10MB
- Peak usage: < 64MB
- Sustained usage: < 32MB

**How to improve:**
- Implement pagination
- Clear large arrays after use
- Use generators for large datasets
- Avoid loading entire tables
- Stream large files

### Database Size

**What it measures:** Total storage used by database

**Good values:**
- Development: < 100MB
- Small production: < 1GB
- Medium production: < 10GB
- Large production: < 100GB

**How to improve:**
- Archive old data
- Compress large text fields
- Optimize image storage
- Use external storage for files
- Implement data retention policies

### Index Usage

**What it measures:** Whether queries use indexes

**Good values:**
- All frequent queries use indexes
- No full table scans on large tables
- Composite indexes for common filters

**How to improve:**
- Add indexes on foreign keys
- Create composite indexes for common WHERE combinations
- Use EXPLAIN to verify index usage
- Remove unused indexes
- Monitor index size vs table size

### Cache Hit Rate

**What it measures:** Percentage of requests served from cache

**Good values:**
- > 90%: Excellent
- 80-90%: Good
- 70-80%: Fair
- < 70%: Poor

**How to improve:**
- Increase cache TTL
- Warm cache on startup
- Cache frequently accessed data
- Review cache invalidation strategy
- Increase cache size

## Continuous Performance Testing

### In Development

Run performance tests:
- Before committing major changes
- After adding new features
- When modifying database queries
- After schema changes

### In CI/CD Pipeline

```yaml
# Example GitHub Actions
- name: Run Performance Tests
  run: ./run-performance-tests.sh
  
- name: Check Performance Thresholds
  run: |
    if grep -q "FAILED" performance-results.txt; then
      echo "Performance regression detected"
      exit 1
    fi
```

### In Production

Monitor:
- Query execution times
- Memory usage
- Cache hit rates
- Page load times
- Error rates

Set up alerts for:
- Slow queries (> 1s)
- High memory usage (> 80%)
- Low cache hit rate (< 80%)
- High error rate (> 1%)

## Performance Optimization Workflow

### 1. Identify Issues

Run performance tests:
```bash
./run-performance-tests.sh
```

Review results for:
- Failed tests
- Warnings
- Slow queries
- High memory usage

### 2. Analyze Root Cause

For slow queries:
```sql
EXPLAIN SELECT ...;
```

For memory issues:
```php
echo memory_get_peak_usage(true) / 1024 / 1024 . " MB\n";
```

For page load issues:
- Use browser DevTools
- Check Network tab
- Review Performance tab
- Analyze Lighthouse report

### 3. Implement Fix

Common fixes:
- Add database indexes
- Implement pagination
- Enable caching
- Optimize images
- Minify assets
- Enable compression

### 4. Verify Improvement

Re-run performance tests:
```bash
./run-performance-tests.sh
```

Compare before/after metrics:
- Query execution time
- Memory usage
- Page load time
- Cache hit rate

### 5. Document Changes

Update:
- Performance test results
- Optimization documentation
- Team knowledge base
- Deployment notes

## Troubleshooting

### Tests Timing Out

**Symptoms:** Tests hang or timeout

**Solutions:**
1. Increase PHP `max_execution_time`
2. Check database connection
3. Verify test data exists
4. Review query complexity

### Inconsistent Results

**Symptoms:** Results vary between runs

**Solutions:**
1. Run tests multiple times
2. Check server load
3. Clear caches between runs
4. Use consistent test data
5. Disable background processes

### High Memory Usage

**Symptoms:** Memory limit errors

**Solutions:**
1. Implement pagination
2. Use LIMIT clauses
3. Clear large arrays
4. Check for memory leaks
5. Increase PHP `memory_limit`

### Slow Queries

**Symptoms:** Queries exceed thresholds

**Solutions:**
1. Run EXPLAIN on query
2. Add missing indexes
3. Optimize JOIN conditions
4. Review WHERE clauses
5. Consider query caching

## Best Practices

### Do's ✅

- Run tests regularly
- Track metrics over time
- Set performance budgets
- Monitor production metrics
- Document optimizations
- Share results with team
- Automate testing in CI/CD
- Test with realistic data

### Don'ts ❌

- Don't skip performance tests
- Don't ignore warnings
- Don't optimize prematurely
- Don't test with empty database
- Don't ignore memory usage
- Don't forget to document
- Don't test only on fast hardware
- Don't ignore production metrics

## Performance Checklist

### Database
- [ ] Indexes on all foreign keys
- [ ] Indexes on frequently queried columns
- [ ] Composite indexes for common filters
- [ ] No SELECT * queries
- [ ] LIMIT clauses on all lists
- [ ] Prepared statements everywhere
- [ ] Connection pooling enabled

### Application
- [ ] Pagination on all large lists
- [ ] Caching for frequent queries
- [ ] Lazy loading for images
- [ ] Code splitting by route
- [ ] Efficient memory management
- [ ] Proper error handling
- [ ] Input validation

### Frontend
- [ ] Minified CSS and JavaScript
- [ ] Compressed assets (Gzip/Brotli)
- [ ] Optimized images
- [ ] Cache headers set
- [ ] CDN for static assets
- [ ] Lazy loading images
- [ ] Reduced HTTP requests

### Infrastructure
- [ ] OPcache enabled
- [ ] PHP-FPM configured
- [ ] Database tuned
- [ ] HTTP/2 enabled
- [ ] Keep-alive enabled
- [ ] Proper server resources
- [ ] Monitoring in place

## Additional Resources

- [Performance Optimization Guide](PERFORMANCE_OPTIMIZATION.md)
- [Database Optimization Guide](DATABASE_OPTIMIZATION.md)
- [Performance Test Results](PERFORMANCE_TEST_RESULTS.md)
- [Performance Test README](tests/Performance/README.md)
- [CDN Setup Guide](CDN_SETUP.md)
- [Pagination Usage Guide](PAGINATION_USAGE.md)

## Support

For questions or issues:
1. Review this guide
2. Check test output for errors
3. Consult optimization guides
4. Contact development team

---

**Last Updated:** December 7, 2025  
**Version:** 1.0
