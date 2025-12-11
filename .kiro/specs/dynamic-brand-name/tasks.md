# Implementation Plan

- [x] 1. Create the brand name helper function
  - [x] 1.1 Add `get_brand_name()` function to `includes/template_helpers.php`
    - Implement static caching to avoid multiple database queries
    - Return fallback value "Portal" when setting is empty or missing
    - Handle database errors gracefully with logging
    - _Requirements: 5.1, 5.3, 1.2_
  - [ ]* 1.2 Write property test for setting persistence round-trip
    - **Property 1: Setting persistence round-trip**
    - **Validates: Requirements 1.1**
  - [ ]* 1.3 Write property test for brand name helper returns configured value
    - **Property 2: Brand name helper returns configured value**
    - **Validates: Requirements 5.1, 5.3**
  - [ ]* 1.4 Write property test for fallback behavior
    - **Property 3: Brand name helper returns fallback for empty values**
    - **Validates: Requirements 1.2**

- [x] 2. Update public website templates
  - [x] 2.1 Update `templates/header.php` to use `get_brand_name()`
    - Replace hardcoded brand name in page title
    - Replace hardcoded brand name in logo text
    - Replace hardcoded brand name in meta description
    - Replace hardcoded brand name in aria-label
    - _Requirements: 2.1, 2.2, 2.4_
  - [x] 2.2 Update `templates/footer.php` to use `get_brand_name()`
    - Replace hardcoded brand name in footer logo
    - Replace hardcoded brand name in copyright text
    - _Requirements: 2.3_

- [x] 3. Update admin panel templates
  - [x] 3.1 Update `templates/admin-header.php` to use `get_brand_name()`
    - Replace hardcoded brand name in page title
    - Replace hardcoded brand name in admin logo text
    - _Requirements: 3.1, 3.2_
  - [x] 3.2 Update `templates/admin-footer.php` to use `get_brand_name()`
    - Replace hardcoded brand name in copyright text
    - _Requirements: 3.3_

- [x] 4. Update customer portal templates
  - [x] 4.1 Update `templates/customer-header.php` to use `get_brand_name()`
    - Replace hardcoded brand name in page title
    - Replace hardcoded brand name in portal logo text
    - _Requirements: 4.1, 4.2_
  - [x] 4.2 Update `templates/customer-footer.php` to use `get_brand_name()`
    - Replace hardcoded brand name in copyright text
    - _Requirements: 4.3_

- [x] 5. Update CTA form template
  - [x] 5.1 Update `templates/cta-form.php` to use `get_brand_name()`
    - Replace hardcoded brand name in default subtitle text
    - _Requirements: 6.1_

- [ ] 6. Final Checkpoint - Make sure all tests are passing
  - Ensure all tests pass, ask the user if questions arise.
