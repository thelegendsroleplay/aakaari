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
        
        // Mock applications data - in a real scenario, this would be populated from PHP
        const applications = [
            {
                id: '1',
                name: 'Rajesh Kumar',
                email: 'rajesh@example.com',
                phone: '+91 9876543210',
                businessName: 'Kumar Enterprises',
                businessType: 'Retail Shop',
                city: 'Mumbai',
                state: 'Maharashtra',
                appliedDate: '2025-10-18',
                status: 'pending'
            },
            {
                id: '2',
                name: 'Priya Sharma',
                email: 'priya@example.com',
                phone: '+91 9876543211',
                businessName: 'Fashion Hub',
                businessType: 'Online Store',
                city: 'Delhi',
                state: 'Delhi',
                appliedDate: '2025-10-17',
                status: 'pending'
            },
            {
                id: '3',
                name: 'Amit Patel',
                email: 'amit@example.com',
                phone: '+91 9876543212',
                businessName: '',
                businessType: 'Individual/Freelancer',
                city: 'Ahmedabad',
                state: 'Gujarat',
                appliedDate: '2025-10-16',
                status: 'pending'
            }
        ];

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
            // Find application data
            const application = applications.find(app => app.id === applicationId);
            
            if (!application) {
                showToast('Application not found', 'error');
                return;
            }
            
            selectedApplicationId = applicationId;
            
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
                        <p>${application.businessName || 'N/A'}</p>
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
            // In a real application, this would make an AJAX call to the server
            
            // Simulate success
            showToast('Application approved successfully!', 'success');
            
            // Update UI
            updateApplicationStatus(applicationId, 'approved');
            
            // Close modal
            closeModal();
        }
        
        /**
         * Reject application
         */
        function rejectApplication(applicationId, reason) {
            // In a real application, this would make an AJAX call to the server
            
            // Simulate success
            showToast('Application rejected', 'success');
            
            // Update UI
            updateApplicationStatus(applicationId, 'rejected');
            
            // Close modal
            closeModal();
        }
        
        /**
         * Update application status in UI
         */
        function updateApplicationStatus(applicationId, status) {
            // Update application status badge in tables
            $(`[data-application-id="${applicationId}"]`).closest('tr')
                .find('.aakaari-status-badge')
                .removeClass('status-pending status-approved status-rejected')
                .addClass(`status-${status}`)
                .text(capitalizeFirstLetter(status));
                
            // In a real application, you might want to refresh data from server or update local data
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