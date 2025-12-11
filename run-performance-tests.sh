#!/bin/bash

# Performance Test Runner Script
# Runs all performance tests and generates a summary report

echo "═══════════════════════════════════════════════════════════════"
echo "         SellerPortal SYSTEM - PERFORMANCE TESTS            "
echo "═══════════════════════════════════════════════════════════════"
echo ""
echo "Starting performance test suite..."
echo ""

# Colors for output
GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Test results
TOTAL_TESTS=0
PASSED_TESTS=0
FAILED_TESTS=0

# Function to run a test suite
run_test_suite() {
    local test_file=$1
    local test_name=$2
    
    echo "───────────────────────────────────────────────────────────────"
    echo "Running: $test_name"
    echo "───────────────────────────────────────────────────────────────"
    
    # Run the test and capture output
    output=$(./vendor/bin/phpunit "$test_file" --testdox 2>&1)
    exit_code=$?
    
    # Check if tests passed (exit code 0) or had only risky tests (which is OK)
    if [ $exit_code -eq 0 ] || echo "$output" | grep -q "OK, but incomplete, skipped, or risky tests"; then
        # Check for actual failures
        if echo "$output" | grep -q "FAILURES!\|ERRORS!"; then
            echo -e "${RED}✗ $test_name: FAILED${NC}"
            ((FAILED_TESTS++))
        else
            echo -e "${GREEN}✓ $test_name: PASSED${NC}"
            ((PASSED_TESTS++))
        fi
    else
        echo -e "${RED}✗ $test_name: FAILED${NC}"
        ((FAILED_TESTS++))
    fi
    
    ((TOTAL_TESTS++))
    echo ""
}

# Run test suites
echo "1. Database Query Performance Tests"
run_test_suite "tests/Performance/DatabaseQueryPerformanceTest.php" "Database Query Performance"

echo "2. Large Dataset Handling Tests"
run_test_suite "tests/Performance/LargeDatasetTest.php" "Large Dataset Handling"

echo "3. Performance Report Generation"
run_test_suite "tests/Performance/PerformanceReportTest.php" "Performance Report"

# Optional: Page load time tests (requires web server)
if [ "$1" == "--with-page-load" ]; then
    echo "4. Page Load Time Tests"
    run_test_suite "tests/Performance/PageLoadTimeTest.php" "Page Load Time"
fi

# Summary
echo "═══════════════════════════════════════════════════════════════"
echo "                      TEST SUMMARY                             "
echo "═══════════════════════════════════════════════════════════════"
echo ""
echo "Total Test Suites: $TOTAL_TESTS"
echo -e "${GREEN}Passed: $PASSED_TESTS${NC}"
if [ $FAILED_TESTS -gt 0 ]; then
    echo -e "${RED}Failed: $FAILED_TESTS${NC}"
else
    echo "Failed: 0"
fi
echo ""

# Overall result
if [ $FAILED_TESTS -eq 0 ]; then
    echo -e "${GREEN}═══════════════════════════════════════════════════════════════${NC}"
    echo -e "${GREEN}              ALL PERFORMANCE TESTS PASSED! ✓                  ${NC}"
    echo -e "${GREEN}═══════════════════════════════════════════════════════════════${NC}"
    exit 0
else
    echo -e "${RED}═══════════════════════════════════════════════════════════════${NC}"
    echo -e "${RED}           SOME PERFORMANCE TESTS FAILED! ✗                    ${NC}"
    echo -e "${RED}═══════════════════════════════════════════════════════════════${NC}"
    exit 1
fi
