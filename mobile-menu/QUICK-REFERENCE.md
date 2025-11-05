# Mobile Menu - Quick Reference Card

## 🚀 One-Minute Setup

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

## 📁 File Tree (16 files)

```
mobile-menu/
├── Components (5)
│   ├── MobileMenu.tsx
│   ├── MobileMenuHeader.tsx
│   ├── MobileMenuNav.tsx
│   ├── MobileMenuActions.tsx
│   └── MobileMenuFooter.tsx
├── Styles (6)
│   ├── mobile-menu.css
│   ├── mobile-menu-header.css
│   ├── mobile-menu-nav.css
│   ├── mobile-menu-actions.css
│   ├── mobile-menu-footer.css
│   └── mobile-utils.css
├── Docs (5)
│   ├── README.md
│   ├── FEATURES.md
│   ├── INTEGRATION.md
│   ├── STRUCTURE.md
│   └── QUICK-REFERENCE.md
└── Index
    └── index.tsx
```

## ⚡ Props at a Glance

| Prop | Type | Default | Description |
|------|------|---------|-------------|
| `isOpen`* | boolean | - | Menu visibility |
| `onClose`* | function | - | Close handler |
| `currentPage`* | string | - | Active page |
| `onNavigate`* | function | - | Nav handler |
| `isLoggedIn` | boolean | false | Auth status |
| `userRole` | string | null | 'reseller'\|'admin' |
| `userName` | string | - | Display name |
| `cartCount` | number | 0 | Cart items |

*Required

## 🎨 CSS Classes

### Main
- `.mobile-menu` - Menu panel
- `.mobile-menu--open` - Open state
- `.mobile-menu-backdrop` - Overlay
- `.mobile-menu-backdrop--open` - Active overlay

### Header
- `.mobile-menu-header` - Header container
- `.mobile-menu-header__logo` - Logo button
- `.mobile-menu-header__user` - User card
- `.mobile-menu-header__avatar` - User icon

### Navigation
- `.mobile-menu-nav__item` - Nav button
- `.mobile-menu-nav__item--active` - Active nav
- `.mobile-menu-nav__section-header` - Collapsible header
- `.mobile-menu-nav__collapsible` - Expandable section

### Actions
- `.mobile-menu-actions__grid` - 4-col grid
- `.mobile-menu-actions__item` - Action button
- `.mobile-menu-actions__badge` - Count badge

### Footer
- `.mobile-menu-footer__link` - Footer link
- `.mobile-menu-footer__logout` - Logout button

## 🎯 Common Tasks

### Add Navigation Item
```tsx
// In MobileMenuNav.tsx
const mainNavItems = [
  { label: 'New Page', value: 'new-page', icon: NewIcon },
];
```

### Change Colors
```css
/* In mobile-menu.css or component CSS */
.mobile-menu-nav__item--active {
  background-color: #your-color;
  color: #your-text-color;
}
```

### Adjust Width
```css
/* In mobile-menu.css */
.mobile-menu {
  width: 90%; /* was 85% */
  max-width: 450px; /* was 400px */
}
```

### Add Quick Action
```tsx
// In MobileMenuActions.tsx
const actions = [
  { label: 'New', value: 'new', icon: NewIcon, show: true },
];
```

## 📱 Breakpoints

| Screen | Width | Menu Width | Grid |
|--------|-------|------------|------|
| Default | All | 85% (400px max) | 4 col |
| Small | 375px | 90% | 4 col |
| XSmall | 320px | 95% | 4 col |
| Tiny | 280px | 95% | 3 col |

## 🎨 Key Dimensions

| Element | Size |
|---------|------|
| Logo Icon | 28px |
| User Avatar | 44px |
| Nav Icon | 20px |
| Sub Icon | 18px |
| Action Icon | 20px |
| Action Badge | 20px min |
| Tap Target | 44px min |

## ⚙️ Feature Flags

| Feature | Condition | Component |
|---------|-----------|-----------|
| User Profile | `isLoggedIn` | Header |
| Quick Actions | `isLoggedIn` | Actions |
| Reseller Tools | `userRole === 'reseller'` | Nav |
| Admin Tools | `userRole === 'admin'` | Nav |
| CTA Section | `!isLoggedIn` | Menu |
| Settings Link | `isLoggedIn` | Footer |
| Logout Button | `isLoggedIn` | Footer |

## 🐛 Debug Checklist

- [ ] Menu state toggles
- [ ] onClose is called
- [ ] onNavigate receives page
- [ ] Cart count updates
- [ ] User info displays
- [ ] Role-based items show
- [ ] CSS files loaded
- [ ] No console errors
- [ ] Body scroll locks
- [ ] Animations smooth

## 📊 Performance Metrics

- Animation: 300ms
- Z-index: 999 (menu), 998 (backdrop)
- Max Width: 400px
- Min Support: 280px
- Files: 16 total
- Components: 5 React
- CSS: ~1500 lines
- Icons: Lucide React

## 🔗 Quick Links

- Main docs: `README.md`
- Features: `FEATURES.md`
- Setup: `INTEGRATION.md`
- Structure: `STRUCTURE.md`

## 💡 Pro Tips

1. **Test on real devices** - Not just Chrome DevTools
2. **Mind safe areas** - iPhone notch support included
3. **Check dark mode** - Styles ready in mobile-utils.css
4. **Optimize badges** - Update only when cart changes
5. **Profile sections** - Add console.logs to debug state
6. **Animation jank** - Use Chrome Performance tab
7. **Touch targets** - Keep minimum 44×44px
8. **Reduce motion** - Already supported

## 🎓 Learn More

| File | Purpose |
|------|---------|
| README.md | Overview & usage |
| FEATURES.md | Complete feature list |
| INTEGRATION.md | How to integrate |
| STRUCTURE.md | Visual diagrams |
| QUICK-REFERENCE.md | This file |

## 🚨 Gotchas

❌ **Don't** modify protected files outside mobile-menu/
❌ **Don't** change z-index without checking conflicts
❌ **Don't** add too many nav items (max 10)
❌ **Don't** forget to test landscape mode
❌ **Don't** remove accessibility features

✅ **Do** test on multiple devices
✅ **Do** use provided CSS classes
✅ **Do** follow naming conventions
✅ **Do** check all user states
✅ **Do** maintain consistent spacing

## 📞 Need Help?

1. Check README.md for detailed docs
2. Review FEATURES.md for capabilities
3. See INTEGRATION.md for setup help
4. Look at STRUCTURE.md for architecture
5. Console.log the props being passed

---

**Last Updated:** November 5, 2025
**Version:** 1.0.0
**Status:** ✅ Production Ready
