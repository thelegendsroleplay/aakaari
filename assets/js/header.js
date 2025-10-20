/**
 * Enhanced Header Mobile Navigation
 * Fully responsive with smooth animations and accessibility support
 */

(function() {
  'use strict';

  // Configuration
  const config = {
    menuToggleSelector: '#mobile-menu-toggle',
    navigationSelector: '#site-navigation',
    activeClass: 'active',
    isActiveClass: 'is-active',
    breakpoint: 991
  };

  // State
  let menuOpen = false;
  let isMobile = window.innerWidth <= config.breakpoint;

  // Get DOM elements
  const menuToggle = document.querySelector(config.menuToggleSelector);
  const mainNav = document.querySelector(config.navigationSelector);
  const body = document.body;

  if (!menuToggle || !mainNav) return;

  const navLinks = mainNav.querySelectorAll('a');

  /**
   * Close mobile menu
   */
  function closeMenu() {
    if (!menuOpen) return;

    mainNav.classList.remove(config.activeClass);
    menuToggle.classList.remove(config.isActiveClass);
    menuToggle.setAttribute('aria-expanded', 'false');
    body.style.overflow = '';
    menuOpen = false;
  }

  /**
   * Open mobile menu
   */
  function openMenu() {
    if (menuOpen) return;

    mainNav.classList.add(config.activeClass);
    menuToggle.classList.add(config.isActiveClass);
    menuToggle.setAttribute('aria-expanded', 'true');
    body.style.overflow = 'hidden';
    menuOpen = true;
  }

  /**
   * Toggle mobile menu
   */
  function toggleMenu(event) {
    event.preventDefault();
    event.stopPropagation();
    
    if (menuOpen) {
      closeMenu();
    } else {
      openMenu();
    }
  }

  /**
   * Handle window resize
   */
  function handleResize() {
    const wasMobile = isMobile;
    isMobile = window.innerWidth <= config.breakpoint;

    // Close menu when switching from mobile to desktop
    if (wasMobile && !isMobile && menuOpen) {
      closeMenu();
    }
  }

  /**
   * Handle clicking outside menu
   */
  function handleOutsideClick(event) {
    if (!isMobile || !menuOpen) return;

    const isMenuClick = mainNav.contains(event.target);
    const isToggleClick = menuToggle.contains(event.target);

    if (!isMenuClick && !isToggleClick) {
      closeMenu();
    }
  }

  /**
   * Close menu when clicking navigation links
   */
  function handleNavLinkClick() {
    if (isMobile && menuOpen) {
      closeMenu();
    }
  }

  /**
   * Handle Escape key
   */
  function handleEscapeKey(event) {
    if (event.key === 'Escape' && menuOpen) {
      closeMenu();
      menuToggle.focus();
    }
  }

  /**
   * Initialize event listeners
   */
  function init() {
    // Set initial state
    menuToggle.setAttribute('aria-expanded', 'false');

    // Event listeners
    menuToggle.addEventListener('click', toggleMenu);
    document.addEventListener('click', handleOutsideClick);
    document.addEventListener('keydown', handleEscapeKey);
    window.addEventListener('resize', handleResize);
    window.addEventListener('popstate', closeMenu);

    // Close menu when clicking nav links
    navLinks.forEach(link => {
      link.addEventListener('click', handleNavLinkClick);
    });
  }

  // Initialize when DOM is ready
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
})();