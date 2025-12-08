# Checkout Page Styling Guide

## Quick Reference for Checkout Page Styles

### Main Sections

#### 1. Hero Section
```css
.checkout-hero
```
- Purple gradient background (#667eea to #764ba2)
- White text with subtle pattern overlay
- Centered title and subtitle

#### 2. Content Section
```css
.checkout-content
```
- Light gray gradient background
- Contains the main checkout grid

#### 3. Checkout Grid
```css
.checkout-grid
```
- Two-column layout (1fr 2fr) on desktop
- Sidebar (order summary) + Main (form)
- Single column on mobile

### Order Summary Components

#### Order Summary Card
```css
.order-summary-card
```
- White background with rounded corners
- Purple border accent
- Box shadow for elevation

#### Plan Badge
```css
.order-plan-badge
```
- Purple gradient background
- Small uppercase text
- Rounded pill shape

#### Features List
```css
.order-features-list
.order-feature-item
```
- Gray background container
- Green checkmark icons
- Compact spacing

#### Pricing Total
```css
.order-pricing-total
```
- Purple gradient background
- Large white text for price
- Prominent display

#### Security Badge
```css
.order-security
```
- Green background (#ecfdf5)
- Lock icon
- Reassuring message

### Form Components

#### Form Sections
```css
.form-section
.form-section-title
.form-section-number
```
- Numbered circular badges (1, 2)
- Clear section separation
- Bottom border dividers

#### Form Grid
```css
.form-grid
```
- Two columns on desktop
- Single column on mobile
- Consistent gap spacing

#### Payment Notice
```css
.payment-notice
```
- Cyan background (#cffafe)
- Info icon
- Important message styling

#### Payment Method Options
```css
.payment-method-option
.payment-method-content
```
- Card-style radio buttons
- Blue border when selected
- Icon + text layout

#### Terms Agreement
```css
.terms-agreement
```
- Gray background
- Checkbox with linked text
- Underlined links

### Trust Badges

```css
.checkout-trust
.trust-badge
```
- Horizontal layout on desktop
- Vertical on mobile
- Green icons with text

### Unavailable State

```css
.checkout-unavailable
.unavailable-icon
```
- Centered card layout
- Large warning icon
- Yellow/amber color scheme
- Clear action buttons

## Color Palette

### Primary Colors
- **Purple Gradient**: `#667eea` to `#764ba2`
- **Primary Blue**: `#2563eb`
- **Success Green**: `#10b981`
- **Info Cyan**: `#06b6d4`
- **Warning Amber**: `#f59e0b`

### Neutral Colors
- **White**: `#ffffff`
- **Gray 50**: `#f9fafb`
- **Gray 100**: `#f3f4f6`
- **Gray 600**: `#4b5563`
- **Gray 900**: `#111827`

## Typography

### Font Sizes
- **Hero Title**: `3rem` (48px)
- **Section Title**: `1.5rem` (24px)
- **Plan Name**: `1.5rem` (24px)
- **Total Price**: `1.875rem` (30px)
- **Body Text**: `1rem` (16px)
- **Small Text**: `0.875rem` (14px)

### Font Weights
- **Bold**: 700 (titles, headings)
- **Semibold**: 600 (labels, emphasis)
- **Medium**: 500 (buttons, links)
- **Normal**: 400 (body text)

## Spacing

### Section Padding
- **Desktop**: `4rem` (64px) vertical
- **Tablet**: `3rem` (48px) vertical
- **Mobile**: `2rem` (32px) vertical

### Card Padding
- **Desktop**: `2rem` (32px)
- **Tablet**: `1.5rem` (24px)
- **Mobile**: `1rem` (16px)

### Gap Spacing
- **Grid Gap**: `2rem` (32px)
- **Form Gap**: `1rem` (16px)
- **Element Gap**: `0.75rem` (12px)

## Border Radius

- **Cards**: `0.75rem` (12px)
- **Buttons**: `0.5rem` (8px)
- **Badges**: `9999px` (pill shape)
- **Inputs**: `0.375rem` (6px)

## Shadows

### Card Shadows
```css
box-shadow: 0 4px 6px rgba(0, 0, 0, 0.07), 0 1px 3px rgba(0, 0, 0, 0.06);
```

### Hover Shadows
```css
box-shadow: 0 8px 16px rgba(0, 0, 0, 0.1);
```

## Transitions

- **Standard**: `200ms ease-in-out`
- **Fast**: `150ms ease-in-out`
- **Slow**: `300ms ease-in-out`

## Responsive Breakpoints

- **Desktop**: `1024px` and above
- **Tablet**: `768px` to `1023px`
- **Mobile**: `767px` and below
- **Small Mobile**: `480px` and below

## Accessibility Features

1. **Focus States**: Blue outline on keyboard focus
2. **ARIA Labels**: Proper labeling for screen readers
3. **Color Contrast**: WCAG AA compliant
4. **Touch Targets**: Minimum 44x44px on mobile
5. **Semantic HTML**: Proper heading hierarchy

## Browser Support

- Chrome 90+
- Firefox 88+
- Safari 14+
- Edge 90+

## Performance Considerations

1. **CSS Variables**: Used for consistent theming
2. **Minimal Animations**: Only essential transitions
3. **Optimized Selectors**: Efficient CSS structure
4. **Mobile-First**: Progressive enhancement approach
