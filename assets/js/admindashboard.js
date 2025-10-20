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
        
        // Toggle dropdown menus with improved handling
        $(document).on('click', '.aakaari-dropdown-trigger', function(e) {
            e.stopPropagation();
            const dropdown = $(this).closest('.aakaari-dropdown');
            
            // Close all other dropdowns
            $('.aakaari-dropdown').not(dropdown).removeClass('active');
            
            // Toggle this dropdown
            dropdown.toggleClass('active');
        });
        
        // Special handling for notification dropdown contents
        $(document).on('click', '.aakaari-notification-dropdown .aakaari-notification-item', function(e) {
            e.stopPropagation(); // Prevent closing dropdown when clicking on notification
        });
        
        // Close dropdowns when clicking elsewhere
        $(document).on('click', function() {
            $('.aakaari-dropdown').removeClass('active');
        });
        
        // Handle view details action
        $(document).on('click', '[data-action="view-reseller"]', function(e) {
            e.preventDefault();
            const resellerId = $(this).data('id');
            
            // Show loading toast
            showToast('Loading reseller details...', 'info');
            
            // For demonstration, simply navigate to a URL or load a modal
            // In a real implementation, you would load a modal with AJAX data
            window.location.href = $(this).attr('href') || `?tab=resellers&view=${resellerId}`;
        });
        
        // Handle suspend/activate account
        $(document).on('click', '[data-action="suspend-reseller"], [data-action="activate-reseller"]', function(e) {
            e.preventDefault();
            const resellerId = $(this).data('id');
            const action = $(this).data('action');
            const status = action === 'suspend-reseller' ? 'suspended' : 'active';
            const confirmMessage = `Are you sure you want to ${action === 'suspend-reseller' ? 'suspend' : 'activate'} this reseller account?`;
            
            if (confirm(confirmMessage)) {
                // Show loading toast
                showToast('Processing...', 'info');
                
                // Send AJAX request to update status
                $.ajax({
                    url: aakaari_admin_ajax.ajax_url,
                    type: 'POST',
                    data: {
                        action: 'update_reseller_status',
                        reseller_id: resellerId,
                        status: status,
                        nonce: aakaari_admin_ajax.nonce
                    },
                    success: function(response) {
                        if (response.success) {
                            showToast('Reseller status updated successfully', 'success');
                            // Reload page to reflect changes
                            window.location.reload();
                        } else {
                            showToast(response.data.message || 'Failed to update status', 'error');
                        }
                    },
                    error: function() {
                        showToast('Server error. Please try again.', 'error');
                    }
                });
            }
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
                    // Reload page after short delay to reflect changes
                    setTimeout(() => window.location.reload(), 1000);
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
                    // Reload page after short delay to reflect changes
                    setTimeout(() => window.location.reload(), 1000);
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
    });
    // Handle notification bell clicks
$(document).on('click', '#notification-bell-button', function(e) {
    e.stopPropagation();
    const $dropdown = $(this).closest('.aakaari-dropdown');
    
    // If we're opening the dropdown, mark notifications as seen
    if (!$dropdown.hasClass('active')) {
        markNotificationsAsSeen();
    }
    
    // Toggle dropdown
    $dropdown.toggleClass('active');
    
    // Close other dropdowns
    $('.aakaari-dropdown').not($dropdown).removeClass('active');
});

/**
 * Mark notifications as seen via AJAX
 */
function markNotificationsAsSeen() {
    // Current timestamp
    const timestamp = Math.floor(Date.now() / 1000);
    
    // Hide notification badge immediately for better UX
    $('#notification-badge').fadeOut(200);
    
    // Send AJAX request to mark notifications as seen
    $.ajax({
        url: aakaari_admin_ajax.ajax_url,
        type: 'POST',
        data: {
            action: 'mark_notifications_seen',
            timestamp: timestamp,
            nonce: aakaari_admin_ajax.nonce
        },
        success: function(response) {
            if (response.success) {
                // Badge already hidden above
            }
        },
        error: function() {
            // If there's an error, show the badge again
            $('#notification-badge').fadeIn(200);
        }
    });
}

// Handle individual payout button
$(document).on('click', '[data-action="process-payout"]', function(e) {
    e.preventDefault();
    const resellerId = $(this).data('id');
    const amount = parseFloat($(this).data('amount'));
    
    if (isNaN(amount) || amount <= 0) {
        showToast('Invalid payout amount', 'error');
        return;
    }
    
    if (confirm(`Are you sure you want to process a payout of ₹${amount.toLocaleString()} for this reseller?`)) {
        processSinglePayout(resellerId, amount, $(this));
    }
});

// Handle bulk payout button
$('#processPayoutsBtn').on('click', function() {
    if (confirm('Are you sure you want to process payouts for all eligible resellers? This will clear their wallet balances.')) {
        processBulkPayouts($(this));
    }
});

/**
 * Process a single reseller payout
 */
function processSinglePayout(resellerId, amount, button) {
    // Show processing state
    const originalText = button.html();
    button.prop('disabled', true).html('Processing...');
    
    $.ajax({
        url: aakaari_admin_ajax.ajax_url,
        type: 'POST',
        data: {
            action: 'process_single_payout',
            reseller_id: resellerId,
            amount: amount,
            nonce: aakaari_admin_ajax.nonce
        },
        success: function(response) {
            if (response.success) {
                showToast('Payout processed successfully!', 'success');
                
                // Update UI - remove the row or update the balance
                const row = button.closest('tr');
                row.fadeOut(400, function() {
                    row.remove();
                    
                    // Check if table is now empty
                    const tbody = $('.aakaari-table tbody');
                    if (tbody.children('tr').length === 0) {
                        tbody.append(`
                            <tr>
                                <td colspan="6" class="aakaari-text-center aakaari-py-8">
                                    <p class="aakaari-text-muted">No pending payouts found.</p>
                                </td>
                            </tr>
                        `);
                    }
                });
                
                // Update stats
                updatePayoutStats(-amount);
            } else {
                showToast(response.data.message || 'Failed to process payout', 'error');
                button.prop('disabled', false).html(originalText);
            }
        },
        error: function() {
            showToast('Server error. Please try again.', 'error');
            button.prop('disabled', false).html(originalText);
        }
    });
}

/**
 * Process bulk payouts
 */
function processBulkPayouts(button) {
    // Show processing state
    const originalText = button.html();
    button.prop('disabled', true).html('Processing...');
    
    $.ajax({
        url: aakaari_admin_ajax.ajax_url,
        type: 'POST',
        data: {
            action: 'process_bulk_payouts',
            nonce: aakaari_admin_ajax.nonce
        },
        success: function(response) {
            if (response.success) {
                showToast(response.data.message, 'success');
                
                // Reload the page to reflect changes
                setTimeout(function() {
                    window.location.reload();
                }, 1500);
            } else {
                showToast(response.data.message || 'No eligible payouts found', 'warning');
                button.prop('disabled', false).html(originalText);
            }
        },
        error: function() {
            showToast('Server error. Please try again.', 'error');
            button.prop('disabled', false).html(originalText);
        }
    });
}

/**
 * Update payout statistics after processing payouts
 */
function updatePayoutStats(amountChange) {
    // Update pending amount display
    const pendingAmountElement = $('.aakaari-stats-grid').first().find('.aakaari-stat-value').first();
    const currentAmount = parseFloat(pendingAmountElement.text().replace('₹', '').replace(/,/g, ''));
    
    if (!isNaN(currentAmount)) {
        const newAmount = currentAmount + amountChange;
        pendingAmountElement.text('₹' + newAmount.toLocaleString());
    }
    
    // Update pending resellers count
    const pendingResellersElement = $('.aakaari-stats-grid').first().find('.aakaari-stat-trend').first();
    const currentCount = parseInt(pendingResellersElement.text().split(' ')[0]);
    
    if (!isNaN(currentCount) && currentCount > 0) {
        const newCount = currentCount - 1;
        pendingResellersElement.text(newCount + ' resellers pending');
    }
}

// In your processBulkPayouts function's success callback:
if (response.success) {
    // Populate the modal content
    let resultContent = `
        <div class="payout-result-summary">
            <h4>Payout Summary</h4>
            <p>Successfully processed ${response.data.successful_payouts.length} payouts</p>
            <p class="total-amount">Total: ₹${response.data.total_amount.toLocaleString()}</p>
        </div>
        
        <div class="payout-result-list">
            <h4>Processed Payouts</h4>
    `;
    
    if (response.data.successful_payouts.length > 0) {
        response.data.successful_payouts.forEach(payout => {
            resultContent += `
                <div class="payout-result-item">
                    <div>${payout.name}</div>
                    <div class="amount">₹${payout.amount.toLocaleString()}</div>
                </div>
            `;
        });
    } else {
        resultContent += `<p>No payouts were processed</p>`;
    }
    
    resultContent += `</div>`;
    
    if (response.data.failed_payouts.length > 0) {
        resultContent += `
            <div class="payout-errors">
                <h4>Failed Payouts (${response.data.failed_payouts.length})</h4>
                <ul>
        `;
        
        response.data.failed_payouts.forEach(failed => {
            resultContent += `<li>${failed.name}: ${failed.error}</li>`;
        });
        
        resultContent += `</ul></div>`;
    }
    
    // Show the modal with results
    $('#payoutResultContent').html(resultContent);
    $('#payoutResultModal').addClass('active');
    
    // Add a close handler for the modal
    $('#closePayoutResultBtn').on('click', function() {
        $('#payoutResultModal').removeClass('active');
        // Reload the page after closing the modal
        window.location.reload();
    });
}
})(jQuery);