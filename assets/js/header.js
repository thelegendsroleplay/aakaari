/**
 * Modern Mobile Menu Navigation
 * Enhanced with swipe gestures, smooth animations, and accessibility
 */

(function() {
  'use strict';

  // ===============================================
  // Configuration
  // ===============================================
  const config = {
    menuToggleSelector: '#mobile-menu-toggle',
    navigationSelector: '#site-navigation',
    activeClass: 'active',
    isActiveClass: 'is-active',
    breakpoint: 991,
    swipeThreshold: 50,
    swipeVelocityThreshold: 0.3
  };

  // ===============================================
  // State Management
  // ===============================================
  let state = {
    menuOpen: false,
    isMobile: window.innerWidth <= config.breakpoint,
    touchStartX: 0,
    touchStartY: 0,
    touchCurrentX: 0,
    touchCurrentY: 0,
    isSwiping: false
  };

  // ===============================================
  // DOM Elements
  // ===============================================
  const menuToggle = document.querySelector(config.menuToggleSelector);
  const mainNav = document.querySelector(config.navigationSelector);
  const body = document.body;

  if (!menuToggle || !mainNav) {
    console.warn('Mobile menu elements not found');
    return;
  }

  const navLinks = mainNav.querySelectorAll('a');

  // ===============================================
  // Core Menu Functions
  // ===============================================

  /**
   * Close mobile menu
   */
  function closeMenu() {
    if (!state.menuOpen) return;

    mainNav.classList.remove(config.activeClass);
    menuToggle.classList.remove(config.isActiveClass);
    menuToggle.setAttribute('aria-expanded', 'false');
    body.style.overflow = '';
    state.menuOpen = false;

    // Announce to screen readers
    announceToScreenReader('Menu closed');
  }

  /**
   * Open mobile menu
   */
  function openMenu() {
    if (state.menuOpen) return;

    mainNav.classList.add(config.activeClass);
    menuToggle.classList.add(config.isActiveClass);
    menuToggle.setAttribute('aria-expanded', 'true');
    body.style.overflow = 'hidden';
    state.menuOpen = true;

    // Announce to screen readers
    announceToScreenReader('Menu opened');
  }

  /**
   * Toggle mobile menu
   */
  function toggleMenu(event) {
    if (event) {
      event.preventDefault();
      event.stopPropagation();
    }

    if (state.menuOpen) {
      closeMenu();
    } else {
      openMenu();
    }
  }

  // ===============================================
  // Swipe Gesture Support
  // ===============================================

  /**
   * Handle touch start
   */
  function handleTouchStart(event) {
    if (!state.menuOpen || !state.isMobile) return;

    state.touchStartX = event.touches[0].clientX;
    state.touchStartY = event.touches[0].clientY;
    state.touchCurrentX = state.touchStartX;
    state.touchCurrentY = state.touchStartY;
    state.isSwiping = true;
  }

  /**
   * Handle touch move
   */
  function handleTouchMove(event) {
    if (!state.isSwiping || !state.menuOpen) return;

    state.touchCurrentX = event.touches[0].clientX;
    state.touchCurrentY = event.touches[0].clientY;

    const deltaX = state.touchCurrentX - state.touchStartX;
    const deltaY = Math.abs(state.touchCurrentY - state.touchStartY);

    // Only track horizontal swipes
    if (deltaY > 50) {
      state.isSwiping = false;
      return;
    }

    // Prevent scrolling while swiping
    if (Math.abs(deltaX) > 10) {
      event.preventDefault();
    }

    // Apply visual feedback for swipe-to-close (swipe left)
    if (deltaX < 0) {
      const translateValue = Math.max(deltaX, -380);
      mainNav.style.transform = `translateX(${translateValue}px)`;
      mainNav.style.transition = 'none';
    }
  }

  /**
   * Handle touch end
   */
  function handleTouchEnd(event) {
    if (!state.isSwiping) return;

    state.isSwiping = false;

    const deltaX = state.touchCurrentX - state.touchStartX;
    const deltaY = Math.abs(state.touchCurrentY - state.touchStartY);

    // Reset transform with transition
    mainNav.style.transition = '';
    mainNav.style.transform = '';

    // Close menu if swipe left exceeds threshold
    if (deltaX < -config.swipeThreshold && deltaY < 50) {
      closeMenu();
    }
  }

  // ===============================================
  // Event Handlers
  // ===============================================

  /**
   * Handle window resize
   */
  function handleResize() {
    const wasMobile = state.isMobile;
    state.isMobile = window.innerWidth <= config.breakpoint;

    // Close menu when switching from mobile to desktop
    if (wasMobile && !state.isMobile && state.menuOpen) {
      closeMenu();
    }
  }

  /**
   * Handle clicking outside menu
   */
  function handleOutsideClick(event) {
    if (!state.isMobile || !state.menuOpen) return;

    // Check if click is on backdrop (::before pseudo-element area)
    const isBackdropClick = !mainNav.contains(event.target) &&
                           !menuToggle.contains(event.target);

    if (isBackdropClick) {
      closeMenu();
    }
  }

  /**
   * Close menu when clicking navigation links
   */
  function handleNavLinkClick(event) {
    if (state.isMobile && state.menuOpen) {
      // Small delay to allow navigation to start
      setTimeout(closeMenu, 100);
    }
  }

  /**
   * Handle Escape key
   */
  function handleEscapeKey(event) {
    if (event.key === 'Escape' && state.menuOpen) {
      closeMenu();
      menuToggle.focus();
    }
  }

  /**
   * Handle keyboard navigation for accessibility
   */
  function handleKeyboardNav(event) {
    if (!state.menuOpen) return;

    const focusableElements = mainNav.querySelectorAll(
      'a, button, input, [tabindex]:not([tabindex="-1"])'
    );
    const firstElement = focusableElements[0];
    const lastElement = focusableElements[focusableElements.length - 1];

    // Trap focus within menu
    if (event.key === 'Tab') {
      if (event.shiftKey && document.activeElement === firstElement) {
        event.preventDefault();
        lastElement.focus();
      } else if (!event.shiftKey && document.activeElement === lastElement) {
        event.preventDefault();
        firstElement.focus();
      }
    }
  }

  // ===============================================
  // Utility Functions
  // ===============================================

  /**
   * Announce to screen readers
   */
  function announceToScreenReader(message) {
    const announcement = document.createElement('div');
    announcement.setAttribute('role', 'status');
    announcement.setAttribute('aria-live', 'polite');
    announcement.className = 'sr-only';
    announcement.textContent = message;
    document.body.appendChild(announcement);

    setTimeout(() => {
      document.body.removeChild(announcement);
    }, 1000);
  }

  /**
   * Debounce function for resize events
   */
  function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
      const later = () => {
        clearTimeout(timeout);
        func(...args);
      };
      clearTimeout(timeout);
      timeout = setTimeout(later, wait);
    };
  }

  // ===============================================
  // Initialization
  // ===============================================

  function init() {
    // Set initial ARIA state
    menuToggle.setAttribute('aria-expanded', 'false');
    menuToggle.setAttribute('aria-label', 'Toggle navigation menu');

    // Menu toggle click
    menuToggle.addEventListener('click', toggleMenu);

    // Outside click detection
    document.addEventListener('click', handleOutsideClick);

    // Keyboard support
    document.addEventListener('keydown', handleEscapeKey);
    document.addEventListener('keydown', handleKeyboardNav);

    // Window resize
    window.addEventListener('resize', debounce(handleResize, 150));

    // Browser navigation (back/forward)
    window.addEventListener('popstate', closeMenu);

    // Touch gestures for swipe-to-close
    mainNav.addEventListener('touchstart', handleTouchStart, { passive: true });
    mainNav.addEventListener('touchmove', handleTouchMove, { passive: false });
    mainNav.addEventListener('touchend', handleTouchEnd, { passive: true });

    // Close menu when clicking navigation links
    navLinks.forEach(link => {
      link.addEventListener('click', handleNavLinkClick);
    });

    // Close menu when clicking close button in mobile menu
    const closeButton = mainNav.querySelector('.mobile-menu-close');
    if (closeButton) {
      closeButton.addEventListener('click', toggleMenu);
    }

    // Handle search form submission
    const searchForm = mainNav.querySelector('.search-form');
    if (searchForm) {
      searchForm.addEventListener('submit', () => {
        // Close menu after a short delay to allow form submission
        setTimeout(closeMenu, 100);
      });
    }

    // Prevent body scroll when menu is open
    mainNav.addEventListener('touchmove', (e) => {
      if (state.menuOpen) {
        const isScrollable = e.target.closest('.mobile-menu-content');
        if (!isScrollable) {
          e.preventDefault();
        }
      }
    }, { passive: false });
  }

  // ===============================================
  // Start Application
  // ===============================================

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }

  // Expose API for external use if needed
  window.AakaariMobileMenu = {
    open: openMenu,
    close: closeMenu,
    toggle: toggleMenu,
    isOpen: () => state.menuOpen
  };

})();
