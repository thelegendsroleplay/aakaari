/**
 * Enhanced Home Page - Optimized & Accessible
 * - Lazy loading for performance
 * - Smooth animations
 * - Mobile-optimized interactions
 */

(function() {
  'use strict';

  // Configuration
  const config = {
    quickviewSelector: '.fp-quickview',
    modalClass: 'aakaari-quickview-modal',
    closeButtonClass: 'qv-close',
    ajaxUrl: typeof AakaariHome !== 'undefined' ? AakaariHome.ajax_url : '/wp-admin/admin-ajax.php',
    nonce: typeof AakaariHome !== 'undefined' ? AakaariHome.nonce : '',
    loadingClass: 'is-loading'
  };

  /**
   * Debounce function
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
   * Throttle function
   */
  function throttle(func, limit) {
    let inThrottle;
    return function(...args) {
      if (!inThrottle) {
        func.apply(this, args);
        inThrottle = true;
        setTimeout(() => inThrottle = false, limit);
      }
    };
  }

  /**
   * Create modal element
   */
  function createModal(content) {
    const modal = document.createElement('div');
    modal.className = config.modalClass;
    modal.setAttribute('role', 'dialog');
    modal.setAttribute('aria-modal', 'true');
    modal.setAttribute('aria-labelledby', 'qv-title');
    modal.innerHTML = `
      <div class="qv-overlay"></div>
      <div class="qv-container">
        <button class="${config.closeButtonClass}" aria-label="Close quick view" type="button">
          <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 16 16">
            <path d="M2.146 2.854a.5.5 0 1 1 .708-.708L8 7.293l5.146-5.147a.5.5 0 0 1 .708.708L8.707 8l5.147 5.146a.5.5 0 0 1-.708.708L8 8.707l-5.146 5.147a.5.5 0 0 1-.708-.708L7.293 8 2.146 2.854Z"/>
          </svg>
        </button>
        <div class="qv-content">${content}</div>
      </div>
    `;
    return modal;
  }

  /**
   * Show modal
   */
  function showModal(content) {
    // Remove existing modal
    closeModal();

    // Create and append modal
    const modal = createModal(content);
    document.body.appendChild(modal);
    document.body.style.overflow = 'hidden';

    // Add event listeners
    const closeBtn = modal.querySelector(`.${config.closeButtonClass}`);
    const overlay = modal.querySelector('.qv-overlay');

    closeBtn.addEventListener('click', closeModal);
    overlay.addEventListener('click', closeModal);
    document.addEventListener('keydown', handleEscapeKey);

    // Focus management
    setTimeout(() => closeBtn.focus(), 100);

    // Animate in
    requestAnimationFrame(() => {
      modal.classList.add('is-active');
    });
  }

  /**
   * Close modal
   */
  function closeModal() {
    const modal = document.querySelector(`.${config.modalClass}`);
    if (!modal) return;

    modal.classList.remove('is-active');
    
    setTimeout(() => {
      modal.remove();
      document.body.style.overflow = '';
      document.removeEventListener('keydown', handleEscapeKey);
    }, 300);
  }

  /**
   * Handle escape key
   */
  function handleEscapeKey(e) {
    if (e.key === 'Escape') {
      closeModal();
    }
  }

  /**
   * Handle quick view
   */
  function handleQuickView(e) {
    const button = e.target.closest(config.quickviewSelector);
    if (!button) return;

    e.preventDefault();

    const productId = button.getAttribute('data-product-id');
    if (!productId) {
      console.error('Product ID not found');
      return;
    }

    // Set loading state
    button.classList.add(config.loadingClass);
    button.disabled = true;
    button.setAttribute('aria-busy', 'true');

    const originalText = button.textContent;
    button.textContent = 'Loading...';

    // Fetch product data
    const formData = new FormData();
    formData.append('action', 'aakaari_quickview');
    formData.append('product_id', productId);
    formData.append('nonce', config.nonce);

    fetch(config.ajaxUrl, {
      method: 'POST',
      credentials: 'same-origin',
      body: formData
    })
      .then(response => {
        if (!response.ok) throw new Error('Network error');
        return response.text();
      })
      .then(html => {
        button.classList.remove(config.loadingClass);
        button.disabled = false;
        button.setAttribute('aria-busy', 'false');
        button.textContent = originalText;

        if (!html || html.trim() === '') {
          throw new Error('Empty response');
        }

        showModal(html);
      })
      .catch(error => {
        button.classList.remove(config.loadingClass);
        button.disabled = false;
        button.setAttribute('aria-busy', 'false');
        button.textContent = originalText;

        console.error('Quick view error:', error);

        const errorHTML = `
          <div class="qv-error" role="alert">
            <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" fill="currentColor" viewBox="0 0 16 16">
              <path d="M8 15A7 7 0 1 1 8 1a7 7 0 0 1 0 14zm0 1A8 8 0 1 0 8 0a8 8 0 0 0 0 16z"/>
              <path d="M7.002 11a1 1 0 1 1 2 0 1 1 0 0 1-2 0zM7.1 4.995a.905.905 0 1 1 1.8 0l-.35 3.507a.552.552 0 0 1-1.1 0L7.1 4.995z"/>
            </svg>
            <h3>Unable to Load Product</h3>
            <p>We couldn't load the product preview. Please try again or view the full product page.</p>
            <button class="btn btn-primary" onclick="location.reload()">Refresh Page</button>
          </div>
        `;
        showModal(errorHTML);
      });
  }

  /**
   * Intersection Observer for lazy animations
   */
  function setupIntersectionObserver() {
    if (!('IntersectionObserver' in window)) return;

    const options = {
      threshold: 0.15,
      rootMargin: '0px 0px -50px 0px'
    };

    const observer = new IntersectionObserver((entries) => {
      entries.forEach(entry => {
        if (entry.isIntersecting) {
          entry.target.classList.add('is-visible');
          observer.unobserve(entry.target);
        }
      });
    }, options);

    // Observe elements
    document.querySelectorAll('.fp-card, .how-step').forEach(el => {
      observer.observe(el);
    });
  }

  /**
   * Smooth scroll for anchor links
   */
  function setupSmoothScroll() {
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
      anchor.addEventListener('click', function(e) {
        const href = this.getAttribute('href');
        if (href === '#' || href === '#!') return;

        const target = document.querySelector(href);
        if (!target) return;

        e.preventDefault();

        const headerOffset = 80;
        const elementPosition = target.getBoundingClientRect().top;
        const offsetPosition = elementPosition + window.pageYOffset - headerOffset;

        window.scrollTo({
          top: offsetPosition,
          behavior: 'smooth'
        });

        // Update URL without scrolling
        if (history.pushState) {
          history.pushState(null, null, href);
        }
      });
    });
  }

  /**
   * Add to cart feedback
   */
  function setupAddToCartFeedback() {
    document.addEventListener('click', function(e) {
      const btn = e.target.closest('.fp-order-now');
      if (!btn || btn.classList.contains('is-loading')) return;

      btn.classList.add('is-loading');
      const originalText = btn.textContent;
      btn.textContent = 'Adding...';

      setTimeout(() => {
        btn.classList.remove('is-loading');
        btn.textContent = '✓ Added';
        
        setTimeout(() => {
          btn.textContent = originalText;
        }, 2000);
      }, 800);
    });
  }

  /**
   * Parallax effect for hero section
   */
  function setupParallax() {
    const hero = document.querySelector('.aakaari-hero-section');
    if (!hero) return;

    const handleScroll = throttle(() => {
      const scrolled = window.pageYOffset;
      const heroImage = hero.querySelector('.hero-image');
      
      if (heroImage && scrolled < window.innerHeight) {
        heroImage.style.transform = `translateY(${scrolled * 0.3}px)`;
      }
    }, 16);

    window.addEventListener('scroll', handleScroll, { passive: true });
  }

  /**
   * Stats counter animation
   */
  function animateStats() {
    const stats = document.querySelectorAll('.hero-stats strong');
    if (!stats.length) return;

    const observer = new IntersectionObserver((entries) => {
      entries.forEach(entry => {
        if (entry.isIntersecting) {
          const stat = entry.target;
          const finalValue = stat.textContent;
          
          // Simple counter animation
          stat.style.opacity = '0';
          setTimeout(() => {
            stat.style.transition = 'opacity 0.5s ease';
            stat.style.opacity = '1';
          }, 100);
          
          observer.unobserve(stat);
        }
      });
    }, { threshold: 0.5 });

    stats.forEach(stat => observer.observe(stat));
  }

  /**
   * Respect user preferences
   */
  function respectUserPreferences() {
    // Reduced motion
    const prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    if (prefersReducedMotion) {
      document.documentElement.style.scrollBehavior = 'auto';
      document.body.classList.add('reduce-motion');
    }

    // Dark mode
    const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
    if (prefersDark) {
      document.body.classList.add('dark-mode');
    }
  }

  /**
   * Initialize all features
   */
  function init() {
    // Respect user preferences first
    respectUserPreferences();

    // Event delegation for quick view
    document.addEventListener('click', handleQuickView);

    // Setup features
    setupIntersectionObserver();
    setupSmoothScroll();
    setupAddToCartFeedback();
    animateStats();

    // Parallax only on desktop
    if (window.innerWidth > 991) {
      setupParallax();
    }

    // Cleanup on page unload
    window.addEventListener('beforeunload', closeModal);

    // Handle window resize
    const handleResize = debounce(() => {
      // Recalculate layouts if needed
      closeModal(); // Close modal on resize to prevent layout issues
    }, 250);

    window.addEventListener('resize', handleResize);
  }

  // Initialize when DOM is ready
  if (document.readyState ==='loading') {
document.addEventListener('DOMContentLoaded', init);
} else {
init();
}
})();