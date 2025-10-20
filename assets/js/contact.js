document.addEventListener('DOMContentLoaded', function() {
    const contactForm = document.getElementById('contactForm');
    
    if (contactForm) {
        contactForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            // Validate form
            if (validateForm()) {
                // Simulate form submission - replace with actual AJAX submission
                submitForm();
            }
        });
    }
    
    function validateForm() {
        let isValid = true;
        const requiredFields = contactForm.querySelectorAll('[required]');
        
        requiredFields.forEach(field => {
            if (!field.value.trim()) {
                markInvalid(field, 'This field is required');
                isValid = false;
            } else {
                markValid(field);
                
                // Additional validation for email
                if (field.type === 'email') {
                    const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                    if (!emailPattern.test(field.value)) {
                        markInvalid(field, 'Please enter a valid email address');
                        isValid = false;
                    }
                }
                
                // Additional validation for phone
                if (field.name === 'phone') {
                    const phonePattern = /^[\d\s\+\-\(\)]{7,20}$/;
                    if (!phonePattern.test(field.value)) {
                        markInvalid(field, 'Please enter a valid phone number');
                        isValid = false;
                    }
                }
            }
        });
        
        return isValid;
    }
    
    function markInvalid(field, message) {
        field.classList.add('invalid');
        
        // Create or update error message
        let errorElement = field.parentElement.querySelector('.error-message');
        
        if (!errorElement) {
            errorElement = document.createElement('p');
            errorElement.className = 'error-message';
            field.parentElement.appendChild(errorElement);
        }
        
        errorElement.textContent = message;
    }
    
    function markValid(field) {
        field.classList.remove('invalid');
        
        // Remove error message if exists
        const errorElement = field.parentElement.querySelector('.error-message');
        if (errorElement) {
            errorElement.remove();
        }
    }
    
    function submitForm() {
        // Get the submit button and show loading state
        const submitBtn = contactForm.querySelector('button[type="submit"]');
        const originalText = submitBtn.innerHTML;
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg> Sending...';
        
        // In a real implementation, you would use AJAX to submit the form
        // For example with the Fetch API:
        /*
        const formData = new FormData(contactForm);
        
        fetch('/your-form-handler-endpoint', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            // Handle successful submission
            showSuccessMessage();
        })
        .catch(error => {
            // Handle error
            showErrorMessage();
        })
        .finally(() => {
            // Reset button
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalText;
        });
        */
        
        // For demonstration, we'll simulate a successful submission after a delay
        setTimeout(() => {
            showSuccessMessage();
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalText;
        }, 1500);
    }
    
    function showSuccessMessage() {
        // Hide the form
        contactForm.style.display = 'none';
        
        // Create and show success message
        const successMessage = document.createElement('div');
        successMessage.className = 'success-message';
        successMessage.innerHTML = `
            <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" fill="#10B981" viewBox="0 0 16 16">
                <path d="M16 8A8 8 0 1 1 0 8a8 8 0 0 1 16 0zm-3.97-3.03a.75.75 0 0 0-1.08.022L7.477 9.417 5.384 7.323a.75.75 0 0 0-1.06 1.06L6.97 11.03a.75.75 0 0 0 1.079-.02l3.992-4.99a.75.75 0 0 0-.01-1.05z"/>
            </svg>
            <h3>Message Sent Successfully!</h3>
            <p>Thank you for contacting us. We will get back to you as soon as possible.</p>
            <button class="btn btn-primary send-new">Send Another Message</button>
        `;
        
        contactForm.parentElement.appendChild(successMessage);
        
        // Add event listener to "Send Another Message" button
        const sendNewBtn = document.querySelector('.send-new');
        if (sendNewBtn) {
            sendNewBtn.addEventListener('click', function() {
                // Remove success message
                successMessage.remove();
                
                // Reset and show the form
                contactForm.reset();
                contactForm.style.display = 'block';
            });
        }
    }
});