# Pricing Page Carousel Implementation

## Overview
Implemented a horizontal scrollable carousel for pricing cards that displays 3 cards at a time and allows sliding when there are more than 3 plans.

## Features Implemented

### 1. Fixed-Width Cards
- Cards maintain consistent width (340px - 380px)
- Width doesn't flex when fewer than 3 items
- Cards are always the same size regardless of content

### 2. Horizontal Layout
- Always displays 3 cards in a row on desktop
- 2 cards on tablet (768px - 1023px)
- 1 card on mobile (< 768px)

### 3. Carousel Navigation
- **Previous/Next Buttons**: Arrow buttons on left and right
- **Dot Indicators**: Shows current slide position
- **Keyboard Support**: Arrow keys for navigation
- **Touch/Swipe Support**: Swipe gestures on mobile devices
- **Auto-disable**: Navigation buttons disable at start/end

### 4. Responsive Behavior
- **Desktop (1024px+)**: 3 cards per view
- **Tablet (768px - 1023px)**: 2 cards per view
- **Mobile (< 768px)**: 1 card per view
- Automatically adjusts on window resize

## Technical Implementation

### HTML Structure
```
pricing-carousel-wrapper
├── pricing-carousel-nav (prev button) - only if > 3 plans
├── pricing-carousel
│   └── pricing-carousel-track
│       └── pricing-card (multiple)
├── pricing-carousel-nav (next button) - only if > 3 plans
└── pricing-carousel-dots - only if > 3 plans
```

### CSS Features
- **Flexbox Layout**: Cards in horizontal row with gap
- **Smooth Transitions**: 0.5s ease-in-out for sliding
- **Fixed Card Width**: `flex: 0 0 calc(33.333% - gap)`
- **Hover Effects**: Navigation buttons change color on hover
- **Active Dot**: Expands to show current position

### JavaScript Functionality
- **Dynamic Calculation**: Adjusts cards per view based on screen size
- **Smooth Sliding**: CSS transform for smooth animation
- **Touch Events**: Swipe detection with threshold
- **Keyboard Events**: Arrow key navigation
- **Resize Handling**: Debounced resize listener
- **Accessibility**: ARIA labels and keyboard support

## User Interactions

### Navigation Methods
1. **Click Navigation Buttons**: Left/right arrows
2. **Click Dot Indicators**: Jump to specific slide
3. **Keyboard Navigation**: Left/right arrow keys
4. **Touch Swipe**: Swipe left/right on touch devices

### Visual Feedback
- Navigation buttons highlight on hover
- Disabled state for buttons at boundaries
- Active dot indicator expands
- Smooth slide transitions

## Conditional Display

### Navigation Controls
- **Show when**: More than 3 plans in database
- **Hide when**: 3 or fewer plans
- This keeps the UI clean when carousel isn't needed

### Card Behavior
- **1-3 Plans**: Cards display in fixed-width row, no scrolling
- **4+ Plans**: Carousel activates with navigation controls

## Performance Optimizations

1. **Debounced Resize**: Prevents excessive recalculations
2. **Passive Touch Events**: Improves scroll performance
3. **CSS Transitions**: Hardware-accelerated transforms
4. **Conditional Rendering**: Navigation only when needed

## Accessibility Features

1. **ARIA Labels**: Descriptive labels for navigation buttons
2. **Keyboard Navigation**: Full keyboard support
3. **Focus Management**: Proper focus states
4. **Semantic HTML**: Article tags for cards
5. **Screen Reader Support**: Proper button labels

## Browser Compatibility

✅ Chrome/Edge (latest)
✅ Firefox (latest)
✅ Safari (latest)
✅ Mobile browsers (iOS Safari, Chrome Mobile)
✅ Touch devices (tablets, phones)

## Responsive Breakpoints

### Desktop (1024px+)
- 3 cards visible
- 60px padding for navigation buttons
- Full-size navigation buttons (48px)

### Tablet (768px - 1023px)
- 2 cards visible
- 50px padding for navigation buttons
- Full-size navigation buttons (48px)

### Mobile (< 768px)
- 1 card visible
- 40px padding for navigation buttons
- Smaller navigation buttons (40px)
- Optimized for touch interaction

## Edge Cases Handled

1. **Exactly 3 Plans**: No navigation shown, cards display in row
2. **1-2 Plans**: Cards maintain fixed width, centered display
3. **Many Plans**: Smooth pagination through all plans
4. **Window Resize**: Recalculates and adjusts layout
5. **Touch Devices**: Swipe gestures work smoothly
6. **Keyboard Users**: Full navigation support

## CSS Variables Used

- `--spacing-*`: Consistent spacing throughout
- `--color-*`: Design system colors
- `--radius-*`: Border radius values
- `--font-*`: Typography settings

## Future Enhancements (Optional)

1. Auto-play carousel with pause on hover
2. Infinite loop scrolling
3. Lazy loading for card images
4. Animation effects on card entrance
5. Compare plans feature
6. Filter/sort functionality
