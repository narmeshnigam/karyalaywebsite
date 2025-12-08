# Pricing Carousel - Visual States Guide

## State 1: No Plans (Empty State)
```
┌─────────────────────────────────────────┐
│                                         │
│         [Icon]                          │
│   No Pricing Plans Available            │
│   Please check back later...            │
│                                         │
│        [Contact Us Button]              │
│                                         │
└─────────────────────────────────────────┘
```
**Behavior**: Shows empty state message with contact button

---

## State 2: 1-2 Plans (No Carousel)
```
┌──────────────────────────────────────────────────────┐
│                                                      │
│    ┌──────────┐         ┌──────────┐               │
│    │  Plan 1  │         │  Plan 2  │               │
│    │          │         │          │               │
│    │  $99/mo  │         │ $199/mo  │               │
│    │          │         │          │               │
│    │ Features │         │ Features │               │
│    │ [Button] │         │ [Button] │               │
│    └──────────┘         └──────────┘               │
│                                                      │
└──────────────────────────────────────────────────────┘
```
**Behavior**: 
- Cards maintain fixed width (340-380px)
- No navigation buttons or dots
- Cards centered in container

---

## State 3: Exactly 3 Plans (No Carousel)
```
┌────────────────────────────────────────────────────────────────┐
│                                                                │
│  ┌──────────┐    ┌──────────┐    ┌──────────┐               │
│  │  Plan 1  │    │  Plan 2  │    │  Plan 3  │               │
│  │          │    │ Popular! │    │          │               │
│  │  $99/mo  │    │ $199/mo  │    │ $299/mo  │               │
│  │          │    │          │    │          │               │
│  │ Features │    │ Features │    │ Features │               │
│  │ [Button] │    │ [Button] │    │ [Button] │               │
│  └──────────┘    └──────────┘    └──────────┘               │
│                                                                │
└────────────────────────────────────────────────────────────────┘
```
**Behavior**: 
- All 3 cards visible at once
- No navigation buttons or dots
- Perfect fit in container

---

## State 4: 4+ Plans (Carousel Active) - Desktop View

### Slide 1 (First 3 cards)
```
┌────────────────────────────────────────────────────────────────┐
│                                                                │
│ [<] ┌──────────┐    ┌──────────┐    ┌──────────┐        [>] │
│     │  Plan 1  │    │  Plan 2  │    │  Plan 3  │            │
│     │          │    │ Popular! │    │          │            │
│     │  $99/mo  │    │ $199/mo  │    │ $299/mo  │            │
│     │          │    │          │    │          │            │
│     │ Features │    │ Features │    │ Features │            │
│     │ [Button] │    │ [Button] │    │ [Button] │            │
│     └──────────┘    └──────────┘    └──────────┘            │
│                                                                │
│                    ● ○ ○                                      │
└────────────────────────────────────────────────────────────────┘
```

### Slide 2 (Next 3 cards)
```
┌────────────────────────────────────────────────────────────────┐
│                                                                │
│ [<] ┌──────────┐    ┌──────────┐    ┌──────────┐        [>] │
│     │  Plan 4  │    │  Plan 5  │    │  Plan 6  │            │
│     │          │    │          │    │          │            │
│     │ $399/mo  │    │ $499/mo  │    │ $599/mo  │            │
│     │          │    │          │    │          │            │
│     │ Features │    │ Features │    │ Features │            │
│     │ [Button] │    │ [Button] │    │ [Button] │            │
│     └──────────┘    └──────────┘    └──────────┘            │
│                                                                │
│                    ○ ● ○                                      │
└────────────────────────────────────────────────────────────────┘
```

**Behavior**: 
- Navigation arrows appear on left/right
- Dot indicators show current slide
- Click arrows or dots to navigate
- Keyboard arrows work
- Smooth slide animation

---

## State 5: 4+ Plans - Tablet View (2 cards per view)

### Slide 1
```
┌──────────────────────────────────────────┐
│                                          │
│ [<]  ┌──────────┐    ┌──────────┐  [>] │
│      │  Plan 1  │    │  Plan 2  │      │
│      │          │    │ Popular! │      │
│      │  $99/mo  │    │ $199/mo  │      │
│      │          │    │          │      │
│      │ Features │    │ Features │      │
│      │ [Button] │    │ [Button] │      │
│      └──────────┘    └──────────┘      │
│                                          │
│              ● ○ ○                      │
└──────────────────────────────────────────┘
```

**Behavior**: 
- Shows 2 cards at a time
- More slides to navigate through
- Same navigation controls

---

## State 6: 4+ Plans - Mobile View (1 card per view)

### Slide 1
```
┌────────────────────────┐
│                        │
│ [<]  ┌──────────┐ [>] │
│      │  Plan 1  │     │
│      │          │     │
│      │  $99/mo  │     │
│      │          │     │
│      │ Features │     │
│      │          │     │
│      │ [Button] │     │
│      └──────────┘     │
│                        │
│      ● ○ ○ ○ ○ ○      │
└────────────────────────┘
```

**Behavior**: 
- Shows 1 card at a time
- Swipe gestures enabled
- Smaller navigation buttons
- More dots for more slides

---

## Navigation States

### Disabled Previous Button (First Slide)
```
[<] - Grayed out, not clickable
```

### Disabled Next Button (Last Slide)
```
[>] - Grayed out, not clickable
```

### Active Navigation
```
[<] - Blue on hover, clickable
[>] - Blue on hover, clickable
```

### Dot Indicators
```
● ○ ○ - First slide active (filled dot is wider)
○ ● ○ - Second slide active
○ ○ ● - Third slide active
```

---

## Interaction Methods

### 1. Click Navigation Buttons
- Click left arrow: Go to previous slide
- Click right arrow: Go to next slide
- Buttons disable at boundaries

### 2. Click Dot Indicators
- Click any dot: Jump directly to that slide
- Active dot expands to show current position

### 3. Keyboard Navigation
- Press ← (Left Arrow): Previous slide
- Press → (Right Arrow): Next slide
- Works when page has focus

### 4. Touch/Swipe (Mobile/Tablet)
- Swipe left: Next slide
- Swipe right: Previous slide
- Minimum swipe distance: 50px

---

## Animation Details

### Slide Transition
- **Duration**: 0.5 seconds
- **Easing**: ease-in-out
- **Method**: CSS transform translateX
- **Smooth**: Hardware accelerated

### Button Hover
- **Duration**: 0.3 seconds
- **Effect**: Background color change to primary
- **Icon**: Changes to white

### Dot Transition
- **Duration**: 0.3 seconds
- **Effect**: Width expansion for active dot
- **Hover**: Scale up slightly

---

## Responsive Breakpoints Summary

| Screen Size | Cards Visible | Button Size | Padding |
|-------------|---------------|-------------|---------|
| Desktop (1024px+) | 3 | 48px | 60px |
| Tablet (768-1023px) | 2 | 48px | 50px |
| Mobile (< 768px) | 1 | 40px | 40px |

---

## Card Width Behavior

### Desktop
- **Min Width**: 340px
- **Max Width**: 380px
- **Flex**: `0 0 calc(33.333% - gap)`

### Tablet
- **Min Width**: 300px
- **Flex**: `0 0 calc(50% - gap)`

### Mobile
- **Min Width**: 280px
- **Max Width**: 100%
- **Flex**: `0 0 100%`

---

## Special Features

### Featured Plan Badge
```
┌──────────────┐
│ Most Popular │ ← Badge appears above card
├──────────────┤
│   Plan 2     │
│              │
│  $199/mo     │
└──────────────┘
```
- Appears on second plan (index 1)
- Positioned absolutely above card
- Purple gradient background
- Always visible regardless of slide

### Card Hover Effect
- Lifts up 8px
- Shadow increases
- Border changes to primary color
- Smooth 0.3s transition
