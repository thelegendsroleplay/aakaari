/**
 * Mobile Menu JavaScript
 * Handles opening, closing, and collapsible sections
 */

(function($) {
    'use strict';

    // Initialize when DOM is ready
    $(document).ready(function() {
        initMobileMenu();
    });

    function initMobileMenu() {
        const $mobileMenu = $('#mobile-menu');
        const $backdrop = $('#mobile-menu-backdrop');
        const $toggleBtn = $('#mobile-menu-toggle');
        const $closeBtn = $('#mobile-menu-close');
        const $body = $('body');

        // Open menu
        $toggleBtn.on('click', function() {
            openMenu();
        });

        // Close menu
        $closeBtn.on('click', function() {
            closeMenu();
        });

        // Close on backdrop click
        $backdrop.on('click', function() {
            closeMenu();
        });

        // Close on navigation click
        $('.mobile-menu-nav__item, .mobile-menu-actions__item, .mobile-menu__cta-btn').on('click', function(e) {
            // Don't close if it's a section header
            if (!$(this).hasClass('mobile-menu-nav__section-header')) {
                closeMenu();
            }
        });

        // Handle collapsible sections
        $('.mobile-menu-nav__section-header').on('click', function() {
            toggleSection($(this));
        });

        // Close on escape key
        $(document).on('keydown', function(e) {
            if (e.key === 'Escape' && $mobileMenu.hasClass('mobile-menu--open')) {
                closeMenu();
            }
        });

        function openMenu() {
            $mobileMenu.addClass('mobile-menu--open');
            $backdrop.addClass('mobile-menu-backdrop--open');
            $body.addClass('mobile-menu-open');
            $toggleBtn.attr('aria-expanded', 'true');

            // Focus on close button for accessibility
            setTimeout(function() {
                $closeBtn.focus();
            }, 300);
        }

        function closeMenu() {
            $mobileMenu.removeClass('mobile-menu--open');
            $backdrop.removeClass('mobile-menu-backdrop--open');
            $body.removeClass('mobile-menu-open');
            $toggleBtn.attr('aria-expanded', 'false');

            // Return focus to toggle button
            setTimeout(function() {
                $toggleBtn.focus();
            }, 300);
        }

        function toggleSection($header) {
            const $section = $header.closest('.mobile-menu-nav__section');
            const $collapsible = $section.find('.mobile-menu-nav__collapsible');
            const $chevron = $header.find('.mobile-menu-nav__chevron');
            const sectionName = $header.data('section');

            // Check if already expanded
            const isExpanded = $collapsible.hasClass('mobile-menu-nav__collapsible--expanded');

            // Close all other sections
            $('.mobile-menu-nav__collapsible').removeClass('mobile-menu-nav__collapsible--expanded');
            $('.mobile-menu-nav__chevron').removeClass('mobile-menu-nav__chevron--expanded');

            // Toggle current section
            if (!isExpanded) {
                $collapsible.addClass('mobile-menu-nav__collapsible--expanded');
                $chevron.addClass('mobile-menu-nav__chevron--expanded');
            }
        }

        // Handle window resize
        let resizeTimer;
        $(window).on('resize', function() {
            clearTimeout(resizeTimer);
            resizeTimer = setTimeout(function() {
                // Close menu if window is resized to desktop size
                if ($(window).width() > 768 && $mobileMenu.hasClass('mobile-menu--open')) {
                    closeMenu();
                }
            }, 250);
        });

        // Prevent body scroll when menu is open
        $mobileMenu.on('touchmove', function(e) {
            if ($body.hasClass('mobile-menu-open')) {
                const $target = $(e.target);
                // Allow scroll only within menu content
                if (!$target.closest('.mobile-menu__content').length) {
                    e.preventDefault();
                }
            }
        });
    }

})(jQuery);
