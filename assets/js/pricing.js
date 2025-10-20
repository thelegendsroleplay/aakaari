document.addEventListener('DOMContentLoaded', function() {
    // FAQ accordion functionality
    const faqItems = document.querySelectorAll('.pricing-faqs .faq-item h3');
    
    faqItems.forEach(item => {
        item.addEventListener('click', () => {
            const parent = item.parentElement;
            
            // Check if this item is already active
            const isActive = parent.classList.contains('active');
            
            // Close all items
            document.querySelectorAll('.pricing-faqs .faq-item').forEach(faq => {
                faq.classList.remove('active');
            });
            
            // Toggle current item if it wasn't active
            if (!isActive) {
                parent.classList.add('active');
            }
        });
    });
});