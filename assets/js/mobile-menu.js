/**
 * Optimized Mobile Menu JavaScript
 * Lightweight with smooth performance
 */

(function($) {
    'use strict';

    // Cache DOM elements globally
    let $menu, $backdrop, $toggle, $close, $body, isOpen = false;

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

        // Early return if elements don't exist
        if (!$menu.length || !$toggle.length) return;

        // Bind events using event delegation for better performance
        $toggle.on('click', openMenu);
        $close.on('click', closeMenu);
        $backdrop.on('click', closeMenu);

        // Close on navigation click (event delegation)
        $menu.on('click', '.mobile-menu-nav__item, .mobile-menu-actions__item, .mobile-menu__cta-btn, .mobile-menu-footer__link, .mobile-menu-footer__logout',  closeMenu);

        // Handle collapsible sections (event delegation)
        $menu.on('click', '.mobile-menu-nav__section-header', function() {
            toggleSection($(this));
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

        // Prevent body scroll on touch devices
        $menu.on('touchmove', function(e) {
            if (isOpen && !$(e.target).closest('.mobile-menu__content').length) {
                e.preventDefault();
            }
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
        setTimeout(function() { $close.first().focus(); }, 300);
    }

    function closeMenu(e) {
        if (!isOpen) return;

        // Prevent default and stop propagation
        if (e && e.preventDefault) {
            e.preventDefault();
            e.stopPropagation();
        }

        isOpen = false;

        $menu.removeClass('mobile-menu--open');
        $backdrop.removeClass('mobile-menu-backdrop--open');
        $body.removeClass('mobile-menu-open');
        $toggle.attr('aria-expanded', 'false');

        // Force a reflow to ensure transition completes
        void $menu[0].offsetHeight;

        // Return focus to toggle
        setTimeout(function() {
            $toggle.focus();
        }, 300);
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
