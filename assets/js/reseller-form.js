/**
 * Enhanced Reseller Form Validation
 */
jQuery(document).ready(function($) {
    const resellerForm = $("#resellerForm");
    
    if (resellerForm.length) {
        // Real-time validation for phone number (numbers, spaces, +, -)
        $("#phone").on("input", function() {
            this.value = this.value.replace(/[^0-9\s\+\-]/g, "");
        });
        
        // Real-time validation for account number (numbers only)
        $("#accountNumber").on("input", function() {
            this.value = this.value.replace(/[^0-9]/g, "");
        });
        
        // Real-time validation for pincode (numbers only)
        $("#pincode").on("input", function() {
            this.value = this.value.replace(/[^0-9]/g, "");
        });
        
        // Real-time validation for IFSC (uppercase, letters, numbers)
        $("#ifsc").on("input", function() {
            this.value = this.value.replace(/[^A-Z0-9]/g, "").toUpperCase();
        });
        
        // Form submission validation
        resellerForm.on("submit", function(e) {
            let isValid = true;
            
            // Validate required fields
            $("[required]").each(function() {
                if (!$(this).val().trim()) {
                    alert($(this).attr("name") + " is required");
                    $(this).focus();
                    isValid = false;
                    return false; // Break the loop
                }
            });
            
            if (!isValid) {
                e.preventDefault();
                return false;
            }
            
            // Validate phone (should be at least 10 digits)
            const phoneField = $("#phone");
            if (phoneField.length && phoneField.val().trim()) {
                const phoneDigits = phoneField.val().replace(/[^0-9]/g, "");
                if (phoneDigits.length < 10) {
                    alert("Please enter a valid phone number with at least 10 digits");
                    phoneField.focus();
                    e.preventDefault();
                    return false;
                }
            }
            
            // Validate account number (should be 9-18 digits)
            const accountField = $("#accountNumber");
            if (accountField.length && accountField.val().trim()) {
                if (!/^\d{9,18}$/.test(accountField.val())) {
                    alert("Please enter a valid account number (9-18 digits)");
                    accountField.focus();
                    e.preventDefault();
                    return false;
                }
            }
            
            // Validate pincode (should be 6 digits for India)
            const pincodeField = $("#pincode");
            if (pincodeField.length && pincodeField.val().trim()) {
                if (!/^\d{6}$/.test(pincodeField.val())) {
                    alert("Please enter a valid 6-digit pincode");
                    pincodeField.focus();
                    e.preventDefault();
                    return false;
                }
            }
            
            // Validate IFSC (should be 11 characters)
            const ifscField = $("#ifsc");
            if (ifscField.length && ifscField.val().trim()) {
                if (!/^[A-Z]{4}0[A-Z0-9]{6}$/.test(ifscField.val())) {
                    alert("Please enter a valid IFSC code (e.g., SBIN0001234)");
                    ifscField.focus();
                    e.preventDefault();
                    return false;
                }
            }
            
            return isValid;
        });
    }
});