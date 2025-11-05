/**
 * Optimized Mobile Menu JavaScript
 * Lightweight and smooth performance
 */

(function($) {
    'use strict';

    // Cache DOM elements
    let $menu, $backdrop, $toggle, $close, $body;
    let isOpen = false;

    // Initialize when DOM is ready
    $(document).ready(function() {
        initMobileMenu();
    });

    function initMobileMenu() {
        // Cache elements
        $menu = $('#mobile-menu');
        $backdrop = $('#mobile-menu-backdrop');
        $toggle = $('#mobile-menu-toggle');
        $close = $('#mobile-menu-close');
        $body = $('body');

        // Return if elements don't exist
        if (!$menu.length || !$toggle.length) return;

        // Bind events
        $toggle.on('click', openMenu);
        $close.on('click', closeMenu);
        $backdrop.on('click', closeMenu);

        // Close on navigation click
        $('.mobile-menu__link, .mobile-menu__btn').on('click', closeMenu);

        // Close on escape key
        $(document).on('keydown', handleEscape);

        // Close on window resize to desktop
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
        $menu.addClass('mobile-menu--open');
        $backdrop.addClass('mobile-menu-backdrop--open');
        $body.addClass('mobile-menu-open');
        $toggle.attr('aria-expanded', 'true');

        // Focus management for accessibility
        setTimeout(function() {
            $close.focus();
        }, 300);
    }

    function closeMenu() {
        if (!isOpen) return;

        isOpen = false;
        $menu.removeClass('mobile-menu--open');
        $backdrop.removeClass('mobile-menu-backdrop--open');
        $body.removeClass('mobile-menu-open');
        $toggle.attr('aria-expanded', 'false');

        // Return focus to toggle button
        setTimeout(function() {
            $toggle.focus();
        }, 300);
    }

    function handleEscape(e) {
        if (e.key === 'Escape' && isOpen) {
            closeMenu();
        }
    }

})(jQuery);
