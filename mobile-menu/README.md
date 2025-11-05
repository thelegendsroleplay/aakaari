# Mobile Menu System

A comprehensive, feature-rich mobile menu system for the Aakaari B2B dropshipping platform.

## 📁 File Structure

```
/components/mobile-menu/
├── index.tsx                      # Main export file
├── MobileMenu.tsx                 # Main menu component
├── MobileMenuHeader.tsx           # Header with logo and user info
├── MobileMenuNav.tsx              # Navigation items
├── MobileMenuActions.tsx          # Quick action buttons
├── MobileMenuFooter.tsx           # Footer with links and logout
├── mobile-menu.css                # Main menu styles
├── mobile-menu-header.css         # Header styles
├── mobile-menu-nav.css            # Navigation styles
├── mobile-menu-actions.css        # Quick actions styles
├── mobile-menu-footer.css         # Footer styles
└── README.md                      # This file
```

## ✨ Features

### 1. **Smart Header Section**
- Dynamic logo display
- User profile information when logged in
- User avatar with role badge (Admin/Reseller)
- Direct link to dashboard

### 2. **Quick Actions Grid** (for logged-in users)
- Shopping Cart with item count badge
- Orders tracking
- Wallet access
- Earnings overview
- 4-column responsive grid layout

### 3. **Enhanced Navigation**
- Main navigation items with icons
- Collapsible sections for Reseller and Admin tools
- Active page highlighting
- Smooth expand/collapse animations
- Role-based menu items

### 4. **Call-to-Action Section** (for guests)
- Prominent "Become a Reseller" button
- Login button
- Gradient background for visual appeal

### 5. **Footer Section**
- Help & Support link
- Settings (for logged-in users)
- Terms & Privacy
- Logout button (for logged-in users)
- Version information

## 🎨 Design Features

- **Smooth Animations**: Slide-in menu with backdrop fade
- **Touch Optimized**: Proper tap targets and active states
- **Responsive**: Adapts to all screen sizes (320px+)
- **Modern UI**: Clean, professional design
- **Accessibility**: Proper ARIA labels and keyboard support

## 📱 Responsive Breakpoints

- **Default**: 85% width, max 400px
- **375px**: 90% width
- **320px**: 95% width, adjusted padding
- **280px**: 3-column grid for quick actions

## 🎯 Usage

```tsx
import { MobileMenu } from './components/mobile-menu';

<MobileMenu
  isOpen={isOpen}
  onClose={() => setIsOpen(false)}
  currentPage="home"
  onNavigate={(page) => navigate(page)}
  isLoggedIn={true}
  userRole="reseller"
  userName="John Doe"
  cartCount={3}
/>
```

## 🔧 Props

| Prop | Type | Required | Description |
|------|------|----------|-------------|
| `isOpen` | boolean | Yes | Controls menu visibility |
| `onClose` | function | Yes | Callback when menu closes |
| `currentPage` | string | Yes | Current active page |
| `onNavigate` | function | Yes | Navigation handler |
| `isLoggedIn` | boolean | No | User login status |
| `userRole` | 'reseller' \| 'admin' \| null | No | User role |
| `userName` | string | No | Display name |
| `cartCount` | number | No | Shopping cart item count |

## 🎨 Customization

### Colors
All colors use CSS custom properties and can be customized in the individual CSS files.

### Animations
Transition durations and easing functions can be adjusted in the CSS files:
- `mobile-menu.css`: Main menu transitions
- `mobile-menu-nav.css`: Navigation expand/collapse

### Layout
Grid layouts and spacing can be modified in:
- `mobile-menu-actions.css`: Quick actions grid
- `mobile-menu-nav.css`: Navigation item spacing

## 🚀 Performance

- **Optimized Rendering**: Components only re-render when props change
- **CSS Transitions**: Hardware-accelerated animations
- **Lazy Loading**: Content rendered on-demand
- **Touch Performance**: Proper `-webkit-tap-highlight-color` for iOS

## ♿ Accessibility

- Keyboard navigation support
- Proper ARIA labels
- Focus management
- Screen reader friendly
- Touch target sizes meet WCAG guidelines (44x44px minimum)

## 📝 Notes

- Menu uses fixed positioning and high z-index (999)
- Backdrop uses z-index 998
- Automatically prevents body scroll when open
- Smooth close animation when clicking backdrop
- All touch interactions have visual feedback
