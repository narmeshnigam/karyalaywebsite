# Accessibility Fixes Required

## Priority: Medium
**Impact:** WCAG 2.1 Level AA Compliance

---

## Summary

The accessibility audit identified minor color contrast issues that need to be addressed to achieve full WCAG 2.1 Level AA compliance. All structural accessibility features (ARIA attributes, keyboard navigation, semantic HTML) are properly implemented.

---

## Required Color Contrast Fixes

### 1. Success Color (Green)
**Current:** `#28a745`  
**Contrast Ratio:** 3.13:1 (white text)  
**Required:** 4.5:1 minimum  
**Recommended Fix:** Change to `#218838` or `#1e7e34`

**Files to Update:**
- `assets/css/variables.css` - Update `--color-success` variable

```css
/* Before */
--color-success: #28a745;

/* After */
--color-success: #218838;  /* or #1e7e34 for even better contrast */
```

---

### 2. Info Color (Cyan)
**Current:** `#17a2b8`  
**Contrast Ratio:** 3.04:1 (white text)  
**Required:** 4.5:1 minimum  
**Recommended Fix:** Change to `#117a8b`

**Files to Update:**
- `assets/css/variables.css` - Update `--color-info` variable

```css
/* Before */
--color-info: #17a2b8;

/* After */
--color-info: #117a8b;
```

---

### 3. Muted Text Color
**Current:** `#868e96`  
**Contrast Ratio:** 3.32:1 (on white background)  
**Required:** 4.5:1 minimum for normal text  
**Recommended Fix:** Use `#6c757d` instead, or only use current color for large text (18pt+ or 14pt+ bold)

**Files to Update:**
- `assets/css/variables.css` - Update `--color-text-muted` variable or add usage guidelines

```css
/* Option 1: Change the color */
--color-text-muted: #6c757d;  /* Darker, meets 4.5:1 */

/* Option 2: Keep color but document it's for large text only */
--color-text-muted: #868e96;  /* Use only for large text (18pt+ or 14pt+ bold) */
```

---

### 4. Input Border Color
**Current:** `#ced4da`  
**Contrast Ratio:** 1.49:1 (on white background)  
**Required:** 3:1 minimum for UI components  
**Recommended Fix:** Change to `#adb5bd` or `#6c757d`

**Files to Update:**
- `assets/css/components.css` - Update form control border colors

```css
/* Before */
.form-control {
    border: 1px solid #ced4da;
}

/* After */
.form-control {
    border: 1px solid #adb5bd;  /* Meets 3:1 ratio */
}
```

---

## HTML Structure Fix

### Add lang Attribute to HTML Element

**Issue:** Some pages don't have the `lang` attribute on the `<html>` element  
**Required:** `<html lang="en">` for English content

**Files to Update:**
- `templates/header.php` - Ensure HTML tag includes lang attribute

```php
<!DOCTYPE html>
<html lang="en">
<head>
    <!-- ... -->
</head>
```

---

## Implementation Steps

1. **Update CSS Variables** (5 minutes)
   - Open `assets/css/variables.css`
   - Update the four color variables listed above
   - Save and test visual appearance

2. **Update Form Styles** (2 minutes)
   - Open `assets/css/components.css`
   - Update `.form-control` border color
   - Save and test form appearance

3. **Update Header Template** (1 minute)
   - Open `templates/header.php`
   - Ensure `<html>` tag has `lang="en"` attribute
   - Save

4. **Run Tests** (2 minutes)
   ```bash
   ./vendor/bin/phpunit tests/Accessibility/ColorContrastTest.php
   ```

5. **Visual QA** (10 minutes)
   - Review all pages to ensure colors still look good
   - Check buttons, forms, and text readability
   - Verify no visual regressions

**Total Time Estimate:** ~20 minutes

---

## Testing After Fixes

Run the accessibility test suite to verify all issues are resolved:

```bash
# Run all accessibility tests
./vendor/bin/phpunit tests/Accessibility/ --testdox

# Run only color contrast tests
./vendor/bin/phpunit tests/Accessibility/ColorContrastTest.php --testdox

# Run only screen reader tests
./vendor/bin/phpunit tests/Accessibility/ScreenReaderTest.php --testdox

# Run only keyboard navigation tests
./vendor/bin/phpunit tests/Accessibility/KeyboardNavigationTest.php --testdox
```

Expected result: All tests should pass ✅

---

## Verification Checklist

After implementing fixes, verify:

- [ ] Success buttons have sufficient contrast (4.5:1 minimum)
- [ ] Info buttons have sufficient contrast (4.5:1 minimum)
- [ ] Muted text has sufficient contrast (4.5:1 minimum)
- [ ] Input borders have sufficient contrast (3:1 minimum)
- [ ] HTML lang attribute is present on all pages
- [ ] All accessibility tests pass
- [ ] Visual appearance is acceptable
- [ ] No regressions in existing functionality

---

## Additional Notes

### Why These Colors?

The recommended colors maintain the same hue and visual identity while meeting WCAG AA standards:

- **Success Green:** `#218838` is Bootstrap's success-dark color, widely tested and accepted
- **Info Cyan:** `#117a8b` maintains the cyan identity while being darker
- **Muted Text:** `#6c757d` is already used for secondary text and meets standards
- **Input Borders:** `#adb5bd` is a standard gray that meets UI component requirements

### Browser Compatibility

All recommended colors are standard hex values with full browser support. No compatibility issues expected.

### Design Impact

The color changes are subtle and maintain the overall design aesthetic. The darker colors actually improve readability for all users, not just those with visual impairments.

---

## Resources

- [WCAG 2.1 Contrast Requirements](https://www.w3.org/WAI/WCAG21/Understanding/contrast-minimum.html)
- [WebAIM Contrast Checker](https://webaim.org/resources/contrastchecker/)
- [Color Contrast Analyzer](https://www.tpgi.com/color-contrast-checker/)

---

**Document Created:** December 7, 2025  
**Priority:** Medium  
**Estimated Effort:** 20 minutes  
**Impact:** WCAG 2.1 Level AA Compliance
