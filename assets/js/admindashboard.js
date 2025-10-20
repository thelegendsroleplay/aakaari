/**
 * Aakaari Admin Dashboard JavaScript
 */
(function($) {
    'use strict';

    // Initialize when document is ready
    $(document).ready(function() {
        // --- Variables ---
        const applicationModal = $('#applicationModal');
        const applicationDetails = $('#applicationDetails');
        const rejectionReason = $('#rejectionReason');
        const closeModalBtn = $('#closeModalBtn');
        const rejectAppBtn = $('#rejectAppBtn');
        const approveAppBtn = $('#approveAppBtn');
        
        let selectedApplicationId = null;
        
        // --- Event Handlers ---
        
        // Open application modal
        $(document).on('click', '[data-application-id]', function() {
            const applicationId = $(this).data('application-id');
            openApplicationModal(applicationId);
        });
        
        // Close modal
        closeModalBtn.on('click', function() {
            closeModal();
        });
        
        // Close modal on overlay click
        $(document).on('click', '.aakaari-modal-overlay', function() {
            closeModal();
        });
        
        // Escape key closes modal
        $(document).keydown(function(e) {
            if (e.key === 'Escape' && applicationModal.hasClass('active')) {
                closeModal();
            }
        });
        
        // Reject application
        rejectAppBtn.on('click', function() {
            const reason = rejectionReason.val().trim();
            
            if (!reason) {
                showToast('Please provide a rejection reason', 'error');
                return;
            }
            
            rejectApplication(selectedApplicationId, reason);
        });
        
        // Approve application
        approveAppBtn.on('click', function() {
            approveApplication(selectedApplicationId);
        });
        
        // Toggle dropdown menus
        $(document).on('click', '.aakaari-dropdown-trigger', function(e) {
            e.stopPropagation();
            const dropdown = $(this).closest('.aakaari-dropdown');
            
            // Close all other dropdowns
            $('.aakaari-dropdown').not(dropdown).removeClass('active');
            
            // Toggle this dropdown
            dropdown.toggleClass('active');
        });
        
        // Close dropdowns when clicking elsewhere
        $(document).on('click', function() {
            $('.aakaari-dropdown').removeClass('active');
        });
        
        // --- Functions ---
        
        /**
         * Open application review modal
         */
        function openApplicationModal(applicationId) {
            // --- Get data from the clicked row (assuming PHP added data attributes) ---
            const row = $(`button[data-application-id="${applicationId}"]`).closest('tr');
            if (row.length === 0) {
                showToast('Could not find application row.', 'error');
                return;
            }
            
            // Extract all necessary data directly from the table row
            const application = {
                id: applicationId,
                name: row.find('td:eq(0) div:first-child').text().trim() || 'N/A',
                email: row.find('td:eq(1)').text().trim() || 'N/A',
                phone: row.find('td:eq(0) .aakaari-table-subtitle').text().trim() || 'N/A',
                businessName: row.find('td:eq(2) div:first-child').text().trim() || 'N/A',
                businessType: row.find('td:eq(2) .aakaari-table-subtitle').text().trim() || 'N/A',
                city: row.find('td:eq(3)').text().split(',')[0]?.trim() || 'N/A',
                state: row.find('td:eq(3)').text().split(',')[1]?.trim() || 'N/A',
                appliedDate: row.find('td:eq(4)').text().trim() || 'N/A',
                status: row.find('.aakaari-status-badge').text().trim().toLowerCase() || 'pending'
            };

            // Check if we successfully got application data from the row
            if (!application.id) {
                showToast('Application data could not be retrieved.', 'error');
                return;
            }

            selectedApplicationId = applicationId; // Keep track of the ID

            // Populate application details
            const detailsHtml = `
                <div class="application-details-grid">
                    <div class="application-detail-item">
                        <label>Full Name</label>
                        <p>${application.name}</p>
                    </div>
                    <div class="application-detail-item">
                        <label>Email</label>
                        <p>${application.email}</p>
                    </div>
                    <div class="application-detail-item">
                        <label>Phone</label>
                        <p>${application.phone}</p>
                    </div>
                    <div class="application-detail-item">
                        <label>Business Type</label>
                        <p>${application.businessType}</p>
                    </div>
                    <div class="application-detail-item">
                        <label>Business Name</label>
                        <p>${application.businessName}</p>
                    </div>
                    <div class="application-detail-item">
                        <label>Location</label>
                        <p>${application.city}, ${application.state}</p>
                    </div>
                    <div class="application-detail-item">
                        <label>Applied Date</label>
                        <p>${application.appliedDate}</p>
                    </div>
                    <div class="application-detail-item">
                        <label>Status</label>
                        <p><span class="aakaari-status-badge status-${application.status}">${capitalizeFirstLetter(application.status)}</span></p>
                    </div>
                    <div class="application-detail-item" style="grid-column: 1 / -1;">
                        <a href="/wp-admin/post.php?post=${application.id}&action=edit" target="_blank" class="aakaari-button aakaari-button-sm aakaari-button-outline">View Full Application in WP Admin</a>
                    </div>
                </div>
            `;

            applicationDetails.html(detailsHtml);
            rejectionReason.val('');

            // Show modal
            applicationModal.addClass('active');
        }
        
        /**
         * Close modal
         */
        function closeModal() {
            applicationModal.removeClass('active');
            selectedApplicationId = null;
        }
        
        /**
         * Approve application
         */
        function approveApplication(applicationId) {
            if (!applicationId) {
                showToast('Invalid application ID', 'error');
                return;
            }
            
            // Show processing state
            approveAppBtn.prop('disabled', true).html('<span>Processing...</span>');
            
            // Make AJAX request to the server
            $.ajax({
                url: aakaari_admin_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'approve_application',
                    application_id: applicationId,
                    nonce: aakaari_admin_ajax.nonce
                },
                success: function(response) {
                    if (response.success) {
                        showToast('Application approved successfully!', 'success');
                        updateApplicationStatus(applicationId, 'approved');
                    } else {
                        showToast(response.data.message || 'Failed to approve application', 'error');
                    }
                },
                error: function() {
                    showToast('Server error. Please try again.', 'error');
                },
                complete: function() {
                    approveAppBtn.prop('disabled', false).html('<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg> Approve');
                    closeModal();
                }
            });
        }
        
        /**
         * Reject application
         */
        function rejectApplication(applicationId, reason) {
            if (!applicationId) {
                showToast('Invalid application ID', 'error');
                return;
            }
            
            if (!reason) {
                showToast('Please provide a rejection reason', 'error');
                return;
            }
            
            // Show processing state
            rejectAppBtn.prop('disabled', true).html('<span>Processing...</span>');
            
            // Make AJAX request to the server
            $.ajax({
                url: aakaari_admin_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'reject_application',
                    application_id: applicationId,
                    reason: reason,
                    nonce: aakaari_admin_ajax.nonce
                },
                success: function(response) {
                    if (response.success) {
                        showToast('Application rejected', 'success');
                        updateApplicationStatus(applicationId, 'rejected');
                    } else {
                        showToast(response.data.message || 'Failed to reject application', 'error');
                    }
                },
                error: function() {
                    showToast('Server error. Please try again.', 'error');
                },
                complete: function() {
                    rejectAppBtn.prop('disabled', false).html('<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="15" y1="9" x2="9" y2="15"></line><line x1="9" y1="9" x2="15" y2="15"></line></svg> Reject');
                    closeModal();
                }
            });
        }
        
        /**
         * Update application status in UI
         */
        function updateApplicationStatus(applicationId, status) {
            // Update application status badge in tables
            const row = $(`[data-application-id="${applicationId}"]`).closest('tr');
            if (row.length > 0) {
                row.find('.aakaari-status-badge')
                    .removeClass('status-pending status-approved status-rejected')
                    .addClass(`status-${status}`)
                    .text(capitalizeFirstLetter(status));
            }
                
            // You might want to update the counters on the page too
            // For example, if this was the last pending application, update the count
        }
        
        /**
         * Show toast notification
         */
        function showToast(message, type = 'info') {
            // Create toast element if it doesn't exist
            if ($('#aakaari-toast').length === 0) {
                $('body').append(`
                    <div id="aakaari-toast" class="aakaari-toast">
                        <div class="aakaari-toast-message"></div>
                    </div>
                `);
                
                // Add toast styles
                $('<style>')
                    .prop('type', 'text/css')
                    .html(`
                        .aakaari-toast {
                            position: fixed;
                            bottom: 1rem;
                            right: 1rem;
                            background-color: white;
                            border-radius: 0.375rem;
                            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
                            padding: 1rem 1.5rem;
                            min-width: 20rem;
                            transform: translateY(10px);
                            opacity: 0;
                            transition: all 0.2s ease;
                            z-index: 100;
                            border-left: 4px solid #2563eb;
                        }
                        .aakaari-toast.show {
                            transform: translateY(0);
                            opacity: 1;
                        }
                        .aakaari-toast.success {
                            border-left-color: #10b981;
                        }
                        .aakaari-toast.error {
                            border-left-color: #ef4444;
                        }
                        .aakaari-toast.warning {
                            border-left-color: #f59e0b;
                        }
                    `)
                    .appendTo('head');
            }
            
            const toast = $('#aakaari-toast');
            const toastMessage = toast.find('.aakaari-toast-message');
            
            // Set message and type
            toastMessage.text(message);
            toast.removeClass('success error warning').addClass(type);
            
            // Show toast
            toast.addClass('show');
            
            // Hide after delay
            clearTimeout(window.toastTimeout);
            window.toastTimeout = setTimeout(() => {
                toast.removeClass('show');
            }, 3000);
        }
        
        /**
         * Capitalize first letter of a string
         */
        function capitalizeFirstLetter(string) {
            if (!string) return '';
            return string.charAt(0).toUpperCase() + string.slice(1);
        }

        // --- Initialize UI Elements ---
        
        // Add tooltip styles
        $('<style>')
            .prop('type', 'text/css')
            .html(`
                [data-tooltip] {
                    position: relative;
                    cursor: help;
                }
                [data-tooltip]:after {
                    content: attr(data-tooltip);
                    position: absolute;
                    bottom: 100%;
                    left: 50%;
                    transform: translateX(-50%);
                    background-color: #111827;
                    color: white;
                    padding: 0.25rem 0.5rem;
                    border-radius: 0.25rem;
                    font-size: 0.75rem;
                    white-space: nowrap;
                    opacity: 0;
                    pointer-events: none;
                    transition: opacity 0.2s;
                    z-index: 10;
                }
                [data-tooltip]:hover:after {
                    opacity: 1;
                }
            `)
            .appendTo('head');
    });
    
})(jQuery);