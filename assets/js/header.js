/**
 * Mobile Menu Navigation
 * Simple and effective mobile menu controller
 */

(function() {
  'use strict';

  // Configuration
  const config = {
    menuToggleSelector: '#mobile-menu-toggle',
    navigationSelector: '#site-navigation',
    closeButtonSelector: '.mobile-menu-close',
    activeClass: 'active',
    isActiveClass: 'is-active',
    breakpoint: 991
  };

  // State
  let state = {
    menuOpen: false,
    isMobile: window.innerWidth <= config.breakpoint
  };

  // DOM Elements
  const menuToggle = document.querySelector(config.menuToggleSelector);
  const mainNav = document.querySelector(config.navigationSelector);
  const body = document.body;

  if (!menuToggle || !mainNav) {
    console.warn('Mobile menu elements not found');
    return;
  }

  const closeButton = mainNav.querySelector(config.closeButtonSelector);
  const menuItems = mainNav.querySelectorAll('.menu-item');

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

    // Check if click is on backdrop
    const isBackdropClick = !mainNav.querySelector('.sidebar-container').contains(event.target) &&
                           !menuToggle.contains(event.target);

    if (isBackdropClick) {
      closeMenu();
    }
  }

  /**
   * Close menu when clicking navigation links
   */
  function handleMenuItemClick() {
    if (state.isMobile && state.menuOpen) {
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

  /**
   * Initialize event listeners
   */
  function init() {
    // Set initial ARIA state
    menuToggle.setAttribute('aria-expanded', 'false');
    menuToggle.setAttribute('aria-label', 'Toggle navigation menu');

    // Menu toggle click
    menuToggle.addEventListener('click', toggleMenu);

    // Close button click
    if (closeButton) {
      closeButton.addEventListener('click', toggleMenu);
    }

    // Outside click detection
    document.addEventListener('click', handleOutsideClick);

    // Keyboard support
    document.addEventListener('keydown', handleEscapeKey);

    // Window resize
    window.addEventListener('resize', debounce(handleResize, 150));

    // Browser navigation (back/forward)
    window.addEventListener('popstate', closeMenu);

    // Close menu when clicking menu items
    menuItems.forEach(item => {
      item.addEventListener('click', handleMenuItemClick);
    });

    // Close menu when clicking bottom action buttons
    const bottomActionButtons = mainNav.querySelectorAll('.shopping-cart-btn, .logout-btn');
    bottomActionButtons.forEach(button => {
      button.addEventListener('click', handleMenuItemClick);
    });
  }

  // Initialize when DOM is ready
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
