/**
 * Optimized Mobile Menu JavaScript
 * Lightweight with smooth performance
 * v1.0.3 - Fixed scroll restoration and touch handling
 */

(function($) {
    'use strict';

    // Cache DOM elements and state globally
    let $menu, $backdrop, $toggle, $close, $body, $html, isOpen = false;
    let scrollPosition = 0;
    let isClosing = false;

    $(document).ready(function() {
        initMobileMenu();
    });

    function initMobileMenu() {
        // Cache all selectors once
        $menu = $('#mobile-menu');
        $backdrop = $('#mobile-menu-backdrop');
        $toggle = $('#mobile-menu-toggle');
        $close = $('#mobile-menu-close, .mobile-menu__close-btn');
        $body = $('body');
        $html = $('html');

        // Early return if elements don't exist
        if (!$menu.length || !$toggle.length) return;

        // Bind events using event delegation for better performance
        $toggle.on('click', openMenu);
        $close.on('click', function(e) {
            e.preventDefault();
            closeMenu();
        });
        $backdrop.on('click', function(e) {
            e.preventDefault();
            closeMenu();
        });

        // Close on navigation click (event delegation) - let links work naturally
        $menu.on('click', '.mobile-menu-nav__item, .mobile-menu-actions__item, .mobile-menu__cta-btn, .mobile-menu-footer__link, .mobile-menu-footer__logout', function() {
            closeMenu();
        });

        // Handle collapsible sections (event delegation)
        $menu.on('click', '.mobile-menu-nav__section-header', function(e) {
            e.preventDefault();
            toggleSection($(this));
        });

        // Prevent body scroll on touch when menu is open
        $backdrop.on('touchmove', function(e) {
            if (isOpen) {
                e.preventDefault();
            }
        });

        // Close on escape key
        $(document).on('keydown', handleEscape);

        // Handle window resize with debounce
        let resizeTimer;
        $(window).on('resize', function() {
            clearTimeout(resizeTimer);
            resizeTimer = setTimeout(function() {
                if ($(window).width() > 768 && isOpen) {
                    closeMenu();
                }
            }, 150);
        });
    }

    function openMenu() {
        if (isOpen) return;
        isOpen = true;

        // Store current scroll position
        scrollPosition = window.pageYOffset || document.documentElement.scrollTop;

        // Add classes
        $menu.addClass('mobile-menu--open');
        $backdrop.addClass('mobile-menu-backdrop--open');
        $body.addClass('mobile-menu-open');
        $toggle.attr('aria-expanded', 'true');

        // Set scroll position as CSS variable to maintain position
        $body.css('top', -scrollPosition + 'px');

        // Focus management for accessibility
        setTimeout(function() {
            $close.first().focus();
        }, 300);
    }

    function closeMenu() {
        if (!isOpen || isClosing) return;
        isOpen = false;
        isClosing = true;

        // Remove classes
        $menu.removeClass('mobile-menu--open');
        $backdrop.removeClass('mobile-menu-backdrop--open');
        $toggle.attr('aria-expanded', 'false');

        // Wait for CSS transition to complete before restoring scroll
        // This prevents the race condition
        setTimeout(function() {
            $body.removeClass('mobile-menu-open');
            $body.css('top', '');

            // Use requestAnimationFrame for smoother scroll restoration
            requestAnimationFrame(function() {
                window.scrollTo(0, scrollPosition);
                isClosing = false;
            });

            // Return focus to toggle for accessibility
            setTimeout(function() {
                if ($toggle.length) {
                    $toggle.focus();
                }
            }, 50);
        }, 300); // Match CSS transition duration (0.3s)
    }

    function toggleSection($header) {
        const $section = $header.closest('.mobile-menu-nav__section');
        const $collapsible = $section.find('.mobile-menu-nav__collapsible');
        const $chevron = $header.find('.mobile-menu-nav__chevron');
        const isExpanded = $collapsible.hasClass('mobile-menu-nav__collapsible--expanded');

        // Close all sections
        $('.mobile-menu-nav__collapsible').removeClass('mobile-menu-nav__collapsible--expanded');
        $('.mobile-menu-nav__chevron').removeClass('mobile-menu-nav__chevron--expanded');

        // Toggle current section
        if (!isExpanded) {
            $collapsible.addClass('mobile-menu-nav__collapsible--expanded');
            $chevron.addClass('mobile-menu-nav__chevron--expanded');
        }
    }

    function handleEscape(e) {
        if (e.key === 'Escape' && isOpen) {
            closeMenu();
        }
    }

})(jQuery);
