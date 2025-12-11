# Accessibility Quick Reference Guide
## For Developers Working on SellerPortal System

---

## ✅ What's Already Working Well

### Forms
- All fields have `<label>` elements with `for` attributes
- Required fields have `aria-required="true"`
- Error messages have `role="alert"` and `aria-live="polite"`
- Fields with errors have `aria-invalid="true"` and `aria-describedby`

### Keyboard Navigation
- All interactive elements are keyboard accessible
- Focus indicators are visible
- Tab order is logical
- No keyboard traps

### HTML Structure
- Semantic HTML elements used throughout
- Proper heading hierarchy
- Navigation uses `<nav>` elements

---

## ⚠️ Quick Fixes Needed

### 1. Color Updates (5 minutes)
**File:** `assets/css/variables.css`

```css
/* Change these 4 values: */
--color-success: #218838;  /* was #28a745 */
--color-info: #117a8b;     /* was #17a2b8 */
--color-text-muted: #6c757d;  /* was #868e96 */
```

### 2. Form Border Update (2 minutes)
**File:** `assets/css/components.css`

```css
.form-control {
    border: 1px solid #adb5bd;  /* was #ced4da */
}
```

### 3. HTML Lang Attribute (1 minute)
**File:** `templates/header.php`

```html
<html lang="en">  <!-- Add lang attribute -->
```

---

## 🧪 Running Accessibility Tests

```bash
# Run all accessibility tests
./vendor/bin/phpunit tests/Accessibility/ --testdox

# Run specific test suites
./vendor/bin/phpunit tests/Accessibility/ColorContrastTest.php
./vendor/bin/phpunit tests/Accessibility/ScreenReaderTest.php
./vendor/bin/phpunit tests/Accessibility/KeyboardNavigationTest.php

# Run property-based accessibility tests
./vendor/bin/phpunit tests/Property/FormAccessibilityPropertyTest.php
./vendor/bin/phpunit tests/Property/ValidationErrorAccessibilityPropertyTest.php
```

---

## 📋 Accessibility Checklist for New Features

When adding new features, ensure:

### Forms
- [ ] Every input has a `<label>` with matching `for` attribute
- [ ] Required fields have `aria-required="true"`
- [ ] Error messages have `role="alert"` and `aria-live="polite"`
- [ ] Fields with errors have `aria-invalid="true"`
- [ ] Error messages linked via `aria-describedby`

### Buttons & Links
- [ ] Buttons have descriptive text (not just "Click here")
- [ ] Links have descriptive text (not just "Read more")
- [ ] Icon-only buttons have `aria-label`

### Colors
- [ ] Text has 4.5:1 contrast ratio (or 3:1 for large text)
- [ ] UI components have 3:1 contrast ratio
- [ ] Don't rely on color alone to convey information

### Keyboard
- [ ] All interactive elements are keyboard accessible
- [ ] Focus indicators are visible
- [ ] No keyboard traps
- [ ] Logical tab order

### HTML
- [ ] Use semantic HTML elements
- [ ] Maintain heading hierarchy (h1 → h2 → h3)
- [ ] Images have alt text
- [ ] Tables have proper structure (thead, th, tbody)

---

## 🔧 Helper Functions Available

### Template Helpers (`includes/template_helpers.php`)

```php
// Render accessible form field
render_accessible_field([
    'id' => 'email',
    'name' => 'email',
    'label' => 'Email Address',
    'type' => 'email',
    'required' => true,
    'error' => 'Please enter a valid email',
    'help' => 'We will never share your email'
]);

// Render form errors summary
render_form_errors($errors);

// Validate form accessibility
$missingLabels = validate_form_accessibility($html);

// Check if field has associated label
$hasLabel = has_associated_label($html, $fieldId);
```

---

## 📚 Resources

### Testing Tools
- [WAVE Browser Extension](https://wave.webaim.org/extension/)
- [axe DevTools](https://www.deque.com/axe/devtools/)
- [Lighthouse (Chrome DevTools)](https://developers.google.com/web/tools/lighthouse)

### Guidelines
- [WCAG 2.1 Quick Reference](https://www.w3.org/WAI/WCAG21/quickref/)
- [WebAIM Contrast Checker](https://webaim.org/resources/contrastchecker/)
- [MDN Accessibility Guide](https://developer.mozilla.org/en-US/docs/Web/Accessibility)

### Examples
- See `public/accessible-form-example.php` for complete implementation
- Review `tests/Property/FormAccessibilityPropertyTest.php` for test patterns

---

## 🎯 WCAG 2.1 Level AA Requirements

### Text Contrast
- Normal text (< 18pt): **4.5:1** minimum
- Large text (≥ 18pt or 14pt bold): **3:1** minimum

### UI Components
- Borders, icons, focus indicators: **3:1** minimum

### Keyboard
- All functionality available via keyboard
- Focus indicators visible
- No keyboard traps

### Forms
- Labels for all inputs
- Error identification
- Error suggestions
- Help text available

---

## 💡 Common Mistakes to Avoid

❌ **Don't:**
- Use `placeholder` as a label replacement
- Remove focus outlines with `outline: none` without replacement
- Use color alone to indicate errors
- Create keyboard traps
- Skip heading levels (h1 → h3)
- Use generic link text ("click here", "read more")

✅ **Do:**
- Use `<label>` elements for all form fields
- Provide visible focus indicators
- Use multiple indicators for errors (color + icon + text)
- Test with keyboard only
- Maintain heading hierarchy
- Use descriptive link text

---

## 🚀 Quick Win Tips

1. **Use the helper functions** - They handle ARIA attributes automatically
2. **Run tests early** - Catch issues before they become problems
3. **Test with keyboard** - Tab through your feature before submitting
4. **Check contrast** - Use browser DevTools color picker
5. **Review the example** - `accessible-form-example.php` shows best practices

---

**Last Updated:** December 7, 2025  
**For Questions:** See `ACCESSIBILITY_AUDIT.md` for detailed information
