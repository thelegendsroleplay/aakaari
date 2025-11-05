# Mobile Menu Integration Guide

## 🚀 Quick Start

The mobile menu is already integrated into the Header component and ready to use!

## 📝 How It Works

### 1. Header Component Integration

The `Header.tsx` component already includes the mobile menu:

```tsx
import { MobileMenu } from './mobile-menu';

export function Header({ currentPage, onNavigate, isLoggedIn, userRole }) {
  const [mobileMenuOpen, setMobileMenuOpen] = useState(false);
  const { getCartCount } = useCart();

  return (
    <header>
      {/* Mobile Menu Trigger */}
      <Button onClick={() => setMobileMenuOpen(true)}>
        <Menu />
      </Button>
      
      {/* Mobile Menu */}
      <MobileMenu
        isOpen={mobileMenuOpen}
        onClose={() => setMobileMenuOpen(false)}
        currentPage={currentPage}
        onNavigate={handleNavClick}
        isLoggedIn={isLoggedIn}
        userRole={userRole}
        userName={userName}
        cartCount={getCartCount()}
      />
    </header>
  );
}
```

### 2. Body Scroll Prevention

The menu automatically prevents body scrolling when open:

```tsx
useEffect(() => {
  if (isOpen) {
    document.body.classList.add('mobile-menu-open');
  } else {
    document.body.classList.remove('mobile-menu-open');
  }
}, [isOpen]);
```

The CSS handles the rest:
```css
body.mobile-menu-open {
  overflow: hidden;
  position: fixed;
  width: 100%;
}
```

### 3. Navigation Flow

```
User taps hamburger menu
  ↓
Menu slides in from right
  ↓
Backdrop appears behind menu
  ↓
User navigates or taps backdrop
  ↓
Menu closes, page navigates
```

## 🎨 Customization

### Changing Colors

Edit the CSS files in `/components/mobile-menu/`:

```css
/* Primary color */
.mobile-menu-header__logo-icon {
  color: #2563eb; /* Change this */
}

/* Active state */
.mobile-menu-nav__item--active {
  background-color: #eff6ff; /* Change this */
  color: #2563eb; /* Change this */
}
```

### Adjusting Animation Speed

In `mobile-menu.css`:

```css
.mobile-menu {
  transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1);
  /* Change 0.3s to your preferred duration */
}
```

### Adding New Menu Items

In `MobileMenuNav.tsx`:

```tsx
const mainNavItems = [
  { label: 'Home', value: 'home', icon: Home },
  { label: 'Your New Page', value: 'new-page', icon: YourIcon },
  // Add more items here
];
```

### Customizing Quick Actions

In `MobileMenuActions.tsx`:

```tsx
const actions = [
  {
    label: 'Cart',
    value: 'cart',
    icon: ShoppingCart,
    badge: cartCount > 0 ? cartCount : undefined,
    show: true,
  },
  // Add more actions here
];
```

## 🔧 Advanced Configuration

### Changing Menu Width

In `mobile-menu.css`:

```css
.mobile-menu {
  width: 85%; /* Default */
  max-width: 400px; /* Maximum width */
}

/* For wider menu */
.mobile-menu {
  width: 90%;
  max-width: 450px;
}
```

### Adjusting Z-Index

```css
.mobile-menu-backdrop {
  z-index: 998; /* Under menu */
}

.mobile-menu {
  z-index: 999; /* Above backdrop */
}
```

### Adding New Sections

1. Create new component in `/components/mobile-menu/`
2. Import in `MobileMenu.tsx`
3. Add to render tree:

```tsx
<div className="mobile-menu__content">
  <MobileMenuActions {...props} />
  <MobileMenuNav {...props} />
  <YourNewSection {...props} /> {/* Add here */}
</div>
```

## 📱 Testing Checklist

### Functionality
- [ ] Menu opens on hamburger click
- [ ] Menu closes on backdrop click
- [ ] Menu closes on X button click
- [ ] Menu closes after navigation
- [ ] Active page is highlighted
- [ ] Cart count displays correctly
- [ ] User info shows when logged in
- [ ] Role-based sections appear correctly

### Responsive Design
- [ ] Works on iPhone SE (320px)
- [ ] Works on iPhone 12 (390px)
- [ ] Works on Samsung Galaxy (360px)
- [ ] Works on iPad (768px - should hide)
- [ ] Landscape mode works
- [ ] Notched devices (safe areas)

### Animations
- [ ] Smooth slide-in
- [ ] Smooth slide-out
- [ ] Backdrop fade
- [ ] Chevron rotation
- [ ] Section collapse/expand
- [ ] No lag or jank

### Accessibility
- [ ] Keyboard navigation
- [ ] Screen reader announces menu
- [ ] Focus management
- [ ] Color contrast (WCAG AA)
- [ ] Touch targets ≥ 44px
- [ ] Reduced motion support

### Performance
- [ ] No layout shift
- [ ] Smooth 60fps animations
- [ ] No scroll lag
- [ ] Fast open/close
- [ ] No memory leaks

## 🐛 Troubleshooting

### Menu doesn't open
- Check if `isOpen` state is being set
- Verify z-index isn't being overridden
- Check for JavaScript errors in console

### Menu is cut off
- Check viewport meta tag
- Verify no `overflow: hidden` on parent
- Check for transform conflicts

### Animations are janky
- Reduce animation complexity
- Use `will-change` sparingly
- Check for heavy re-renders
- Profile with Chrome DevTools

### Scroll doesn't work
- Verify `overflow-y: auto` on content
- Check height constraints
- Test `-webkit-overflow-scrolling`

### Body still scrolls
- Check `mobile-menu-open` class
- Verify CSS is loaded
- Test `position: fixed` fallback

## 🎯 Best Practices

### Do's ✅
- Keep menu items under 10
- Use clear, concise labels
- Provide visual feedback
- Test on real devices
- Follow platform conventions
- Maintain consistent spacing

### Don'ts ❌
- Don't add too many items
- Don't use tiny tap targets
- Don't ignore safe areas
- Don't disable animations without reason
- Don't forget accessibility
- Don't nest menus too deep

## 📚 Related Files

- `/components/Header.tsx` - Main header integration
- `/components/CartContext.tsx` - Cart count provider
- `/App.tsx` - Navigation handler
- `/styles/globals.css` - Global styles

## 🔗 Resources

- [Mobile Menu Best Practices](https://www.nngroup.com/articles/hamburger-menus/)
- [Touch Target Sizes](https://web.dev/accessible-tap-targets/)
- [iOS Safe Areas](https://webkit.org/blog/7929/designing-websites-for-iphone-x/)
- [Motion Accessibility](https://web.dev/prefers-reduced-motion/)

## 💡 Tips

1. **Test on real devices** - Emulators can't replicate touch feel
2. **Mind the thumb zone** - Place important items in easy reach
3. **Keep it simple** - Don't overload with options
4. **Use icons** - Visual cues improve recognition
5. **Show state** - Active, visited, disabled states
6. **Consider gestures** - Swipe to close is intuitive
7. **Optimize assets** - Use SVG for icons
8. **Monitor performance** - Profile animations regularly

## 🎓 Learning Resources

### Understanding the Code
- `MobileMenu.tsx` - Main orchestrator
- `MobileMenuNav.tsx` - Navigation logic
- `mobile-menu.css` - Core animations
- `mobile-utils.css` - Advanced features

### Key Concepts
- React state management
- CSS transitions & transforms
- Touch event handling
- Accessibility patterns
- Responsive design principles

---

**Need help?** Check the README.md and FEATURES.md files for more details!
