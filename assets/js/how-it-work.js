document.addEventListener('DOMContentLoaded', function() {
    // FAQ accordion functionality
    const faqItems = document.querySelectorAll('.faq-item h3');
    
    faqItems.forEach(item => {
        item.addEventListener('click', () => {
            const parent = item.parentElement;
            
            // Check if this item is already active
            const isActive = parent.classList.contains('active');
            
            // Close all items
            document.querySelectorAll('.faq-item').forEach(faq => {
                faq.classList.remove('active');
            });
            
            // Toggle current item if it wasn't active
            if (!isActive) {
                parent.classList.add('active');
            }
        });
    });

    // Steps animation - Trigger immediately and on scroll
    const stepItems = document.querySelectorAll('.step-item');
    
    // Check if mobile (simple check)
    const isMobile = window.innerWidth <= 767;
    
    if (isMobile) {
        // On mobile, add animate class immediately without delays to ensure proper order
        stepItems.forEach((item) => {
            item.classList.add('animate');
        });
    } else {
        // On desktop, use staggered animations
        stepItems.forEach((item, index) => {
            // Sort by data-step attribute to ensure correct order
            const stepNumber = parseInt(item.getAttribute('data-step')) || index + 1;
            setTimeout(() => {
                item.classList.add('animate');
            }, (stepNumber - 1) * 150);
        });
        
        // Intersection Observer for scroll animations (if user scrolls back up)
        const observerOptions = {
            threshold: 0.1,
            rootMargin: '0px 0px -50px 0px'
        };

        const stepObserver = new IntersectionObserver((entries) => {
            entries.forEach((entry) => {
                if (entry.isIntersecting && !entry.target.classList.contains('animate')) {
                    entry.target.classList.add('animate');
                }
            });
        }, observerOptions);

        stepItems.forEach((item) => {
            stepObserver.observe(item);
        });
    }
});