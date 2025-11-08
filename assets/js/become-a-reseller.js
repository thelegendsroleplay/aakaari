/**
 * Reseller Form JavaScript
 * Handles file upload, validation, and form interactions
 */
document.addEventListener('DOMContentLoaded', function() {
    console.log('[File Upload Debug] Script loaded and DOM ready');

    // Get reference to form elements
    const form = document.getElementById('resellerForm');

    // Handle all file upload inputs
    const fileInputs = ['aadhaarFront', 'aadhaarBack', 'panCard', 'bankProof', 'businessProof'];

    fileInputs.forEach(inputId => {
        const fileInput = document.getElementById(inputId);
        const fileUploadArea = document.querySelector(`.file-upload-area[data-input-id="${inputId}"]`);
        const selectedFileDiv = document.querySelector(`.selected-file[data-for="${inputId}"]`);

        if (!fileInput || !fileUploadArea || !selectedFileDiv) {
            console.log(`[File Upload Debug] Missing element for ${inputId}`);
            return;
        }

        console.log(`[File Upload Debug] Setting up event listeners for ${inputId}`);

        // Global flag to prevent multiple simultaneous triggers
        let isProcessing = false;
        let processingTimeout = null;

        // Handle file selection
        fileInput.addEventListener('change', function(e) {
            console.log(`[File Upload Debug] ${inputId} - Change event fired`, {
                filesSelected: this.files.length
            });
            updateSelectedFileInfo(this.files, selectedFileDiv, fileInput);
            // Reset processing flag when file is selected or cancelled
            isProcessing = false;
            if (processingTimeout) {
                clearTimeout(processingTimeout);
                processingTimeout = null;
            }
        });

        // Handle drag and drop for file upload
        fileUploadArea.addEventListener('dragover', function(e) {
            e.preventDefault();
            e.stopPropagation();
            this.classList.add('dragover');
        });

        fileUploadArea.addEventListener('dragleave', function(e) {
            e.preventDefault();
            e.stopPropagation();
            this.classList.remove('dragover');
        });

        fileUploadArea.addEventListener('drop', function(e) {
            e.preventDefault();
            e.stopPropagation();
            this.classList.remove('dragover');
            console.log(`[File Upload Debug] ${inputId} - Drop event`);

            if (e.dataTransfer.files.length) {
                fileInput.files = e.dataTransfer.files;
                updateSelectedFileInfo(e.dataTransfer.files, selectedFileDiv, fileInput);
            }
        });

        // Simple click handler - just open the file dialog
        fileUploadArea.addEventListener('click', function(e) {
            console.log(`[File Upload Debug] ${inputId} - Click event`, {
                isProcessing: isProcessing
            });

            // Prevent default to stop any additional events from firing
            e.preventDefault();
            e.stopPropagation();

            // Guard: if already processing, completely ignore
            if (isProcessing) {
                console.log(`[File Upload Debug] ${inputId} - BLOCKED: Already processing`);
                return false;
            }

            // Set processing flag immediately
            isProcessing = true;
            console.log(`[File Upload Debug] ${inputId} - Opening file dialog`);

            // Trigger file input
            fileInput.click();

            // Safety timeout: reset if dialog is cancelled quickly
            processingTimeout = setTimeout(() => {
                console.log(`[File Upload Debug] ${inputId} - Timeout reset`);
                isProcessing = false;
            }, 1000);
        });
    });

    // Display selected file information
    function updateSelectedFileInfo(files, container, inputElement) {
        if (files && files.length > 0) {
            const file = files[0];
            const fileSize = formatFileSize(file.size);

            container.innerHTML = `
                <div class="file-info">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                        <polyline points="14 2 14 8 20 8"></polyline>
                    </svg>
                    <span class="file-name">${file.name}</span>
                    <span class="file-size">${fileSize}</span>
                    <button type="button" class="remove-file" aria-label="Remove file">&times;</button>
                </div>
            `;

            container.classList.add('active');

            // Add handler for remove button
            const removeButton = container.querySelector('.remove-file');
            if (removeButton) {
                removeButton.addEventListener('click', function(e) {
                    e.stopPropagation();
                    inputElement.value = '';
                    container.classList.remove('active');
                    container.innerHTML = '';
                });
            }
        }
    }

    // Format file size
    function formatFileSize(bytes) {
        if (bytes === 0) return '0 Bytes';

        const k = 1024;
        const sizes = ['Bytes', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));

        return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
    }

    // Form validation
    if (form) {
        form.addEventListener('submit', function(e) {
            let isValid = true;

            // Clear all previous error messages
            const errorMessages = form.querySelectorAll('.form-error');
            errorMessages.forEach(error => error.remove());

            // Check required text fields
            const requiredFields = form.querySelectorAll('input[required]:not([type="file"]):not([type="checkbox"]), textarea[required]');
            requiredFields.forEach(field => {
                if (!field.value.trim()) {
                    isValid = false;
                    displayError(field, 'This field is required');
                }
            });

            // Check required checkboxes
            const requiredCheckboxes = form.querySelectorAll('input[type="checkbox"][required]');
            requiredCheckboxes.forEach(field => {
                if (!field.checked) {
                    isValid = false;
                    displayError(field, 'You must agree to the terms and conditions');
                }
            });

            // Validate email
            const emailField = document.getElementById('email');
            if (emailField && emailField.value.trim() && !isValidEmail(emailField.value)) {
                isValid = false;
                displayError(emailField, 'Please enter a valid email address');
            }

            // Validate phone (basic validation - can be enhanced for specific formats)
            const phoneField = document.getElementById('phone');
            if (phoneField && phoneField.value.trim() && !isValidPhone(phoneField.value)) {
                isValid = false;
                displayError(phoneField, 'Please enter a valid phone number');
            }

            // Validate file uploads
            const requiredDocuments = ['aadhaarFront', 'aadhaarBack', 'panCard', 'bankProof'];
            requiredDocuments.forEach(docId => {
                const fileInput = document.getElementById(docId);
                if (fileInput && fileInput.files.length === 0) {
                    isValid = false;
                    displayError(fileInput, 'This document is required');
                } else if (fileInput && fileInput.files.length > 0) {
                    const file = fileInput.files[0];
                    const maxSize = 5 * 1024 * 1024; // 5MB
                    const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'application/pdf'];

                    // Check file type
                    const fileType = file.type;
                    if (!allowedTypes.includes(fileType)) {
                        isValid = false;
                        displayError(fileInput, 'Please upload a PDF, JPG, or PNG file');
                    } else if (file.size > maxSize) {
                        isValid = false;
                        displayError(fileInput, 'File size should not exceed 5MB');
                    }
                }
            });

            // Validate optional business proof if uploaded
            const businessProofInput = document.getElementById('businessProof');
            if (businessProofInput && businessProofInput.files.length > 0) {
                const file = businessProofInput.files[0];
                const maxSize = 5 * 1024 * 1024; // 5MB
                const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'application/pdf'];

                if (!allowedTypes.includes(file.type)) {
                    isValid = false;
                    displayError(businessProofInput, 'Please upload a PDF, JPG, or PNG file');
                } else if (file.size > maxSize) {
                    isValid = false;
                    displayError(businessProofInput, 'File size should not exceed 5MB');
                }
            }

            // Validate IFSC code (basic format validation)
            const ifscField = document.getElementById('ifsc');
            if (ifscField && ifscField.value.trim() && !isValidIFSC(ifscField.value)) {
                isValid = false;
                displayError(ifscField, 'Please enter a valid IFSC code (e.g., SBIN0001234)');
            }

            // Validate account number (basic check - should be numeric and reasonable length)
            const accountField = document.getElementById('accountNumber');
            if (accountField && accountField.value.trim() && !isValidAccountNumber(accountField.value)) {
                isValid = false;
                displayError(accountField, 'Please enter a valid account number');
            }

            // Validate pincode (basic check for Indian pincodes)
            const pincodeField = document.getElementById('pincode');
            if (pincodeField && pincodeField.value.trim() && !isValidPincode(pincodeField.value)) {
                isValid = false;
                displayError(pincodeField, 'Please enter a valid 6-digit pincode');
            }

            if (!isValid) {
                e.preventDefault();

                // Scroll to first error
                const firstError = form.querySelector('.form-error');
                if (firstError) {
                    firstError.scrollIntoView({ behavior: 'smooth', block: 'center' });
                }
            }
        });
    }

    // Display error message below field
    function displayError(field, message) {
        const errorElement = document.createElement('span');
        errorElement.className = 'form-error';
        errorElement.textContent = message;

        // Insert after field or its parent (for checkboxes within labels)
        const parent = field.closest('.form-group') || field.closest('.checkbox-group') || field.parentElement;
        parent.appendChild(errorElement);
    }

    // Email validation
    function isValidEmail(email) {
        const pattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return pattern.test(email);
    }

    // Phone validation (basic - allowing various formats)
    function isValidPhone(phone) {
        // Allow digits, spaces, plus, hyphen, parentheses
        // At least 6 digits, no more than 15 digits total
        const pattern = /^[\d\s\+\-\(\)]{6,20}$/;
        const digitCount = phone.replace(/\D/g, '').length;
        return pattern.test(phone) && digitCount >= 6 && digitCount <= 15;
    }

    // IFSC validation (basic format)
    function isValidIFSC(ifsc) {
        // 4 characters followed by 0 and 6 alphanumeric characters
        const pattern = /^[A-Z]{4}0[A-Z0-9]{6}$/;
        return pattern.test(ifsc);
    }

    // Account number validation (basic)
    function isValidAccountNumber(accountNumber) {
        // Only digits, length between 9 and 18
        const pattern = /^\d{9,18}$/;
        return pattern.test(accountNumber);
    }

    // Pincode validation (Indian format)
    function isValidPincode(pincode) {
        // 6 digits for Indian pincodes
        const pattern = /^\d{6}$/;
        return pattern.test(pincode);
    }

    // Enhance input fields with character count/limits
    const textInputs = document.querySelectorAll('input[type="text"], input[type="tel"], input[type="email"], textarea');
    textInputs.forEach(input => {
        // Add maxlength if not present
        if (!input.hasAttribute('maxlength')) {
            if (input.id === 'accountNumber') {
                input.setAttribute('maxlength', '18');
            } else if (input.id === 'ifsc') {
                input.setAttribute('maxlength', '11');
            } else if (input.id === 'pincode') {
                input.setAttribute('maxlength', '6');
            } else if (input.id === 'phone') {
                input.setAttribute('maxlength', '20');
            }
        }
    });

    // Auto-format IFSC code to uppercase
    const ifscInput = document.getElementById('ifsc');
    if (ifscInput) {
        ifscInput.addEventListener('input', function() {
            this.value = this.value.toUpperCase();
        });
    }
});
