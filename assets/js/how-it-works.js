/**
 * How It Works Page - Interactive Animations & Functionality
 * Handles scroll-triggered animations, FAQ accordion, and smooth interactions
 */

(function() {
  'use strict';

  // ===============================================
  // Configuration
  // ===============================================
  const config = {
    animationDelay: 150,
    scrollThreshold: 0.15,
    reducedMotion: window.matchMedia('(prefers-reduced-motion: reduce)').matches
  };

  // ===============================================
  // FAQ Accordion Functionality
  // ===============================================
  function initFAQAccordion() {
    const faqItems = document.querySelectorAll('.faq-item');

    if (!faqItems || faqItems.length === 0) {
      return;
    }

    faqItems.forEach((item, index) => {
      const question = item.querySelector('h3');
      const answer = item.querySelector('.faq-answer');

      if (!question || !answer) {
        return;
      }

      // Make question visually clickable
      question.style.cursor = 'pointer';
      question.setAttribute('role', 'button');
      question.setAttribute('aria-expanded', 'false');
      question.setAttribute('tabindex', '0');

      // Toggle function
      const toggleFAQ = function() {
        const isActive = item.classList.contains('active');

        // Close all other FAQ items
        faqItems.forEach(otherItem => {
          if (otherItem !== item && otherItem.classList.contains('active')) {
            otherItem.classList.remove('active');
            const otherQuestion = otherItem.querySelector('h3');
            if (otherQuestion) {
              otherQuestion.setAttribute('aria-expanded', 'false');
            }
          }
        });

        // Toggle current item
        if (isActive) {
          item.classList.remove('active');
          question.setAttribute('aria-expanded', 'false');
        } else {
          item.classList.add('active');
          question.setAttribute('aria-expanded', 'true');

          // Smooth scroll to question if needed
          setTimeout(() => {
            const rect = item.getBoundingClientRect();
            const isVisible = rect.top >= 0 && rect.bottom <= window.innerHeight;

            if (!isVisible) {
              item.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
            }
          }, 300);
        }
      };

      // Click handler on entire item
      item.addEventListener('click', function(e) {
        e.preventDefault();
        toggleFAQ();
      });

      // Keyboard support
      question.addEventListener('keydown', function(e) {
        if (e.key === 'Enter' || e.key === ' ') {
          e.preventDefault();
          toggleFAQ();
        }
      });
    });
  }

  // ===============================================
  // Scroll-Triggered Animations
  // ===============================================
  function initScrollAnimations() {
    // Skip complex animations if user prefers reduced motion
    if (config.reducedMotion) {
      document.querySelectorAll('.step-item').forEach(item => {
        item.classList.add('animate');
      });
      return;
    }

    // Create Intersection Observer for step items
    const stepObserver = new IntersectionObserver(
      (entries) => {
        entries.forEach(entry => {
          if (entry.isIntersecting) {
            entry.target.classList.add('animate');
            // Optionally unobserve after animation
            // stepObserver.unobserve(entry.target);
          }
        });
      },
      {
        threshold: config.scrollThreshold,
        rootMargin: '0px 0px -50px 0px'
      }
    );

    // Observe all step items
    document.querySelectorAll('.step-item').forEach(item => {
      stepObserver.observe(item);
    });

    // Create Intersection Observer for feature cards
    const featureObserver = new IntersectionObserver(
      (entries) => {
        entries.forEach((entry, index) => {
          if (entry.isIntersecting) {
            setTimeout(() => {
              entry.target.style.opacity = '1';
              entry.target.style.transform = 'translateY(0)';
            }, index * 100);
            featureObserver.unobserve(entry.target);
          }
        });
      },
      {
        threshold: 0.1,
        rootMargin: '0px 0px -80px 0px'
      }
    );

    // Observe all feature cards
    document.querySelectorAll('.feature-card').forEach(card => {
      card.style.opacity = '0';
      card.style.transform = 'translateY(30px)';
      card.style.transition = 'opacity 0.6s ease, transform 0.6s ease';
      featureObserver.observe(card);
    });
  }

  // ===============================================
  // Smooth Scroll for Anchor Links
  // ===============================================
  function initSmoothScroll() {
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
      anchor.addEventListener('click', function(e) {
        const href = this.getAttribute('href');

        if (href === '#') return;

        e.preventDefault();

        const target = document.querySelector(href);

        if (target) {
          const headerOffset = 100;
          const elementPosition = target.getBoundingClientRect().top;
          const offsetPosition = elementPosition + window.pageYOffset - headerOffset;

          window.scrollTo({
            top: offsetPosition,
            behavior: 'smooth'
          });
        }
      });
    });
  }

  // ===============================================
  // Add Hover Effect Enhancements (Desktop Only)
  // ===============================================
  function initHoverEnhancements() {
    if (window.matchMedia('(hover: hover) and (pointer: fine)').matches) {
      // Add parallax effect to step cards on desktop
      const stepCards = document.querySelectorAll('.step-card');

      stepCards.forEach(card => {
        card.addEventListener('mousemove', function(e) {
          const rect = card.getBoundingClientRect();
          const x = e.clientX - rect.left;
          const y = e.clientY - rect.top;

          const centerX = rect.width / 2;
          const centerY = rect.height / 2;

          const rotateX = (y - centerY) / 20;
          const rotateY = (centerX - x) / 20;

          card.style.transform = `perspective(1000px) rotateX(${rotateX}deg) rotateY(${rotateY}deg) translateY(-8px) scale(1.02)`;
        });

        card.addEventListener('mouseleave', function() {
          card.style.transform = '';
        });
      });
    }
  }

  // ===============================================
  // Progressive Enhancement - Add Loading States
  // ===============================================
  function addProgressiveEnhancements() {
    // Add loaded class after images load
    const images = document.querySelectorAll('img');
    let loadedCount = 0;

    function checkAllLoaded() {
      loadedCount++;
      if (loadedCount === images.length) {
        document.body.classList.add('images-loaded');
      }
    }

    if (images.length === 0) {
      document.body.classList.add('images-loaded');
    } else {
      images.forEach(img => {
        if (img.complete) {
          checkAllLoaded();
        } else {
          img.addEventListener('load', checkAllLoaded);
          img.addEventListener('error', checkAllLoaded);
        }
      });
    }
  }

  // ===============================================
  // Mobile Touch Enhancements
  // ===============================================
  function initMobileTouchEnhancements() {
    if ('ontouchstart' in window) {
      // Add touch-friendly feedback
      const interactiveElements = document.querySelectorAll('.step-card, .feature-card, .faq-item');

      interactiveElements.forEach(element => {
        element.addEventListener('touchstart', function() {
          this.style.transition = 'transform 0.1s ease';
        }, { passive: true });

        element.addEventListener('touchend', function() {
          this.style.transition = '';
        }, { passive: true });
      });
    }
  }

  // ===============================================
  // Performance Optimization
  // ===============================================
  function optimizePerformance() {
    // Lazy load images if IntersectionObserver is supported
    if ('IntersectionObserver' in window) {
      const images = document.querySelectorAll('img[data-src]');

      const imageObserver = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
          if (entry.isIntersecting) {
            const img = entry.target;
            img.src = img.dataset.src;
            img.removeAttribute('data-src');
            imageObserver.unobserve(img);
          }
        });
      });

      images.forEach(img => imageObserver.observe(img));
    }
  }

  // ===============================================
  // Initialization
  // ===============================================
  function init() {
    // Core functionality
    initFAQAccordion();
    initScrollAnimations();
    initSmoothScroll();

    // Progressive enhancements
    addProgressiveEnhancements();
    initMobileTouchEnhancements();
    optimizePerformance();

    // Desktop-only enhancements
    if (window.innerWidth > 768) {
      initHoverEnhancements();
    }

    // Add loaded class to body
    document.body.classList.add('how-it-works-loaded');
  }

  // ===============================================
  // Execute on DOM Ready
  // ===============================================
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }

  // ===============================================
  // Handle Window Resize
  // ===============================================
  let resizeTimer;
  window.addEventListener('resize', function() {
    clearTimeout(resizeTimer);
    resizeTimer = setTimeout(function() {
      // Reinit hover enhancements if screen size changes
      if (window.innerWidth > 768) {
        initHoverEnhancements();
      }
    }, 250);
  });

})();
