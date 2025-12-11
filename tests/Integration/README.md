# Integration Tests

This directory contains integration tests for the SellerPortal System. Integration tests validate end-to-end workflows and external service integrations.

## Test Suites

### 1. User Registration to Purchase Flow Test
**File:** `UserRegistrationToPurchaseFlowTest.php`

Tests the complete user journey from registration through subscription purchase to port allocation.

**Test Cases:**
- Complete user journey (registration → login → purchase → port allocation)
- Purchase flow with no available ports
- Concurrent purchases by multiple users
- Order cancellation before payment

**What it validates:**
- User registration and authentication
- Session management
- Order creation and status updates
- Subscription creation
- Port allocation atomicity
- Data integrity across all related entities

### 2. Payment Gateway Integration Test
**File:** `PaymentGatewayIntegrationTest.php`

Tests integration with Razorpay payment gateway.

**Test Cases:**
- Payment order creation
- Payment signature verification
- Fetching payment details
- Webhook signature verification
- Complete payment flow with mock data
- Payment failure handling
- Refund processing
- Getting Razorpay key ID for frontend
- Order statistics after multiple payments

**Configuration Required:**
These tests require Razorpay credentials to be configured in `config/app.php`:
- `razorpay_key_id`
- `razorpay_key_secret`
- `razorpay_webhook_secret`

**Note:** Tests will be skipped if credentials are not configured or invalid.

### 3. Email Service Integration Test
**File:** `EmailServiceIntegrationTest.php`

Tests email sending functionality with SMTP/SES integration.

**Test Cases:**
- Basic email sending
- Contact form notification emails
- Demo request notification emails
- HTML email content
- Special characters in emails
- Multiple emails in sequence
- Invalid recipient handling
- Complete lead creation and notification flow

**Configuration Required:**
These tests require email credentials to be configured in `.env`:
- `MAIL_HOST`
- `MAIL_USERNAME`
- `MAIL_PASSWORD`
- `MAIL_PORT`
- `MAIL_FROM_ADDRESS`
- `MAIL_FROM_NAME`
- `ADMIN_EMAIL` (for receiving notifications)
- `TEST_EMAIL` (optional, for receiving test emails)

**Note:** Tests will be skipped if email credentials are not configured.

## Running Integration Tests

### Run All Integration Tests
```bash
./vendor/bin/phpunit tests/Integration/ --testdox
```

### Run Specific Test Suite
```bash
# User registration to purchase flow
./vendor/bin/phpunit tests/Integration/UserRegistrationToPurchaseFlowTest.php --testdox

# Payment gateway integration
./vendor/bin/phpunit tests/Integration/PaymentGatewayIntegrationTest.php --testdox

# Email service integration
./vendor/bin/phpunit tests/Integration/EmailServiceIntegrationTest.php --testdox
```

### Run with Verbose Output
```bash
./vendor/bin/phpunit tests/Integration/ --testdox --verbose
```

### Run with Code Coverage
```bash
./vendor/bin/phpunit tests/Integration/ --coverage-html coverage/
```

## Test Data Management

All integration tests follow these principles:

1. **Setup:** Create necessary test data in `setUp()` method
2. **Execution:** Run the test scenario
3. **Teardown:** Clean up all test data in `tearDown()` method

Test data is tracked in arrays (`$testUsers`, `$testOrders`, etc.) and cleaned up in reverse order of dependencies to maintain referential integrity.

## Expected Behavior

### Successful Tests
When all services are properly configured and available:
- User registration flow tests: **4 tests pass**
- Payment gateway tests: **9 tests pass** (if credentials configured)
- Email service tests: **11 tests pass** (if credentials configured)

### Skipped Tests
Tests will be skipped (not failed) when:
- Payment gateway credentials are not configured
- Email service credentials are not configured
- External services are unavailable

This is intentional behavior to allow tests to run in environments without live credentials.

## Test Environment Setup

### For Local Development
1. Copy `.env.example` to `.env`
2. Configure database connection
3. (Optional) Configure Razorpay test mode credentials
4. (Optional) Configure SMTP credentials for email testing

### For CI/CD
- User registration flow tests can run without external service credentials
- Payment and email tests will be skipped in CI unless credentials are provided
- Consider using test mode credentials for payment gateway in CI

## Troubleshooting

### Tests Fail with Database Errors
- Ensure database is running and accessible
- Run migrations: `php bin/migrate.php`
- Seed test data: `php bin/seed.php`

### Payment Tests Always Skip
- Check that Razorpay credentials are configured in `config/app.php`
- Verify credentials are valid (test mode credentials recommended)
- Check network connectivity to Razorpay API

### Email Tests Always Skip
- Check that email credentials are configured in `.env`
- Verify SMTP server is accessible
- Check firewall rules for SMTP port (usually 587 or 465)

### Port Allocation Tests Fail
- Ensure ports exist in the database for the test plan
- Run seeder to create test ports: `php bin/seed.php`
- Check that ports have status 'AVAILABLE'

## Best Practices

1. **Run integration tests before deployment** to ensure all systems work together
2. **Use test mode credentials** for payment gateway in non-production environments
3. **Monitor test execution time** - integration tests are slower than unit tests
4. **Review skipped tests** - ensure they're skipped for the right reasons
5. **Clean up test data** - verify tearDown methods are working correctly

## Contributing

When adding new integration tests:

1. Follow the existing test structure
2. Implement proper setup and teardown
3. Track all created test data for cleanup
4. Handle missing credentials gracefully (skip tests, don't fail)
5. Add documentation to this README
6. Ensure tests are idempotent (can run multiple times)

## Related Documentation

- [Property-Based Tests](../Property/README.md)
- [Unit Tests](../Unit/README.md)
- [Testing Strategy](../../.kiro/specs/karyalay-portal-system/design.md#testing-strategy)
