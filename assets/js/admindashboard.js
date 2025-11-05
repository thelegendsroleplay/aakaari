/**
 * Aakaari Admin Dashboard JavaScript
 * - Includes both dashboard core functionality and custom products admin
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
        
        // Reject application - show reason section
        rejectAppBtn.on('click', function() {
            // Show rejection section
            $('#actionFormsContainer').show();
            $('#rejectionReasonSection').slideDown();
            $('#documentRequestSection').hide();
            $('#cooldownSection').hide();
        });
        
        // Submit rejection
        $(document).on('click', '#submitRejectionBtn', function() {
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
        
        // Quick action card clicks
        $(document).on('click', '#requestDocCard', function() {
            $('#actionFormsContainer').show();
            $('#documentRequestSection').slideDown();
            $('#rejectionReasonSection').hide();
            $('#cooldownSection').hide();
        });
        
        $(document).on('click', '#allowResubmitCard', function() {
            if (confirm('Allow this reseller to resubmit their verification documents?')) {
                allowDocumentResubmission(selectedApplicationId);
            }
        });
        
        $(document).on('click', '#setCooldownCard', function() {
            $('#actionFormsContainer').show();
            $('#cooldownSection').slideDown();
            $('#rejectionReasonSection').hide();
            $('#documentRequestSection').hide();
        });
        
        $(document).on('click', '#resetCooldownCard', function() {
            if (confirm('Are you sure you want to reset the cooldown for this application?')) {
                resetCooldown(selectedApplicationId);
            }
        });

        $(document).on('click', '#allowResubmissionCard', function() {
            if (confirm('Allow this user to resubmit their application? This will clear any cooldown and enable resubmission.')) {
                allowResubmission(selectedApplicationId);
            }
        });

        $(document).on('click', '#requestDocumentsCard', function() {
            $('#actionFormsContainer').show();
            $('#documentRequestSection').slideDown();
            $('#rejectionReasonSection').hide();
            $('#cooldownSection').hide();
        });

        $(document).on('click', '#deleteApplicationCard', function() {
            if (confirm('WARNING: This will permanently delete the application and all associated data. This action cannot be undone. Are you sure?')) {
                deleteApplication(selectedApplicationId);
            }
        });
        
        // Submit document request
        $(document).on('click', '#submitDocRequestBtn', function() {
            const selectedDocs = [];
            $('input[name="requested_documents[]"]:checked').each(function() {
                selectedDocs.push($(this).val());
            });

            const message = $('#documentRequestMessage').val().trim();

            if (selectedDocs.length === 0) {
                showToast('Please select at least one document to request', 'error');
                return;
            }

            requestDocuments(selectedApplicationId, selectedDocs, message);
        });
        
        // Submit cooldown
        $(document).on('click', '#submitCooldownBtn', function() {
            const duration = parseInt($('#cooldownDuration').val());
            if (!duration || duration < 1) {
                showToast('Please enter a valid cooldown duration', 'error');
                return;
            }
            setCooldown(selectedApplicationId, duration);
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

                <!-- Documents Section -->
                <div class="documents-section" style="margin-top: 2rem;">
                    <h4 class="section-header">Reseller Documents</h4>
                    <div id="applicationDocuments" class="documents-grid">
                        <div class="loading-documents">
                            <span>Loading documents...</span>
                        </div>
                    </div>
                </div>
            `;

            applicationDetails.html(detailsHtml);

            // Fetch and display documents
            loadApplicationDocuments(applicationId);
            rejectionReason.val('');
            
            // Reset all action form sections
            $('#actionFormsContainer').hide();
            $('#rejectionReasonSection').hide();
            $('#documentRequestSection').hide();
            $('#cooldownSection').hide();
            $('#documentRequest').val('');
            $('#rejectionReason').val('');
            $('#cooldownDuration').val('24');
            
            // Close any open dropdowns
            closeDropdowns();

            // Show modal
            applicationModal.addClass('active');
        }

        /**
         * Load and display application documents
         */
        function loadApplicationDocuments(applicationId) {
            const documentsContainer = $('#applicationDocuments');

            $.ajax({
                url: aakaari_admin_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'get_application_documents',
                    application_id: applicationId,
                    nonce: aakaari_admin_ajax.nonce
                },
                success: function(response) {
                    if (response.success && response.data.documents) {
                        const documents = response.data.documents;
                        const documentLabels = {
                            'aadhaar_front': 'Aadhaar Card (Front)',
                            'aadhaar_back': 'Aadhaar Card (Back)',
                            'pan_card': 'PAN Card',
                            'bank_proof': 'Bank Proof',
                            'business_proof': 'Business Registration / GST',
                            'id_proof': 'ID Proof (Legacy)'
                        };

                        let documentsHtml = '';

                        // Check which required documents are missing
                        const requiredDocs = ['aadhaar_front', 'aadhaar_back', 'pan_card', 'bank_proof'];
                        const missingDocs = requiredDocs.filter(doc => !documents[doc]);

                        // Display each document
                        Object.keys(documentLabels).forEach(key => {
                            if (documents[key]) {
                                const url = documents[key];
                                const isImage = /\.(jpg|jpeg|png|gif)$/i.test(url);
                                const isPdf = /\.pdf$/i.test(url);

                                documentsHtml += `
                                    <div class="document-item">
                                        <div class="document-header">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                                                <polyline points="14 2 14 8 20 8"></polyline>
                                            </svg>
                                            <strong>${documentLabels[key]}</strong>
                                        </div>
                                        <div class="document-actions">
                                            ${isImage ? `<button class="doc-btn doc-view" data-url="${url}" data-type="image" title="View Document">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                    <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                                    <circle cx="12" cy="12" r="3"></circle>
                                                </svg>
                                                View
                                            </button>` : ''}
                                            ${isPdf ? `<button class="doc-btn doc-view" data-url="${url}" data-type="pdf" title="View PDF">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                    <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                                    <circle cx="12" cy="12" r="3"></circle>
                                                </svg>
                                                View PDF
                                            </button>` : ''}
                                            <a href="${url}" class="doc-btn doc-download" download title="Download Document">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                    <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                                                    <polyline points="7 10 12 15 17 10"></polyline>
                                                    <line x1="12" y1="15" x2="12" y2="3"></line>
                                                </svg>
                                                Download
                                            </a>
                                        </div>
                                    </div>
                                `;
                            }
                        });

                        // Show missing required documents
                        if (missingDocs.length > 0) {
                            missingDocs.forEach(doc => {
                                documentsHtml += `
                                    <div class="document-item missing">
                                        <div class="document-header">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#dc2626" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                <circle cx="12" cy="12" r="10"></circle>
                                                <line x1="15" y1="9" x2="9" y2="15"></line>
                                                <line x1="9" y1="9" x2="15" y2="15"></line>
                                            </svg>
                                            <strong>${documentLabels[doc]}</strong>
                                        </div>
                                        <span class="missing-label">Not uploaded</span>
                                    </div>
                                `;
                            });
                        }

                        documentsContainer.html(documentsHtml);

                        // Add click handlers for view buttons
                        $('.doc-view').on('click', function() {
                            const url = $(this).data('url');
                            const type = $(this).data('type');
                            viewDocument(url, type);
                        });
                    } else {
                        documentsContainer.html('<p class="no-documents">No documents found for this application.</p>');
                    }
                },
                error: function() {
                    documentsContainer.html('<p class="error-documents">Failed to load documents. Please try again.</p>');
                }
            });
        }

        /**
         * View document in modal or new tab
         */
        function viewDocument(url, type) {
            if (type === 'image') {
                // Show image in a modal
                const modalHtml = `
                    <div class="aakaari-doc-preview-modal" id="docPreviewModal">
                        <div class="doc-preview-overlay"></div>
                        <div class="doc-preview-container">
                            <button class="doc-preview-close">&times;</button>
                            <img src="${url}" alt="Document Preview" style="max-width: 100%; max-height: 90vh;">
                        </div>
                    </div>
                `;
                $('body').append(modalHtml);

                // Close preview modal
                $(document).on('click', '#docPreviewModal .doc-preview-close, #docPreviewModal .doc-preview-overlay', function() {
                    $('#docPreviewModal').remove();
                });
            } else if (type === 'pdf') {
                // Open PDF in new tab
                window.open(url, '_blank');
            } else {
                // Fallback: download
                window.location.href = url;
            }
        }

        /**
         * Close modal
         */
        function closeModal() {
            applicationModal.removeClass('active');
            selectedApplicationId = null;
            
            // Reset all action form sections
            $('#actionFormsContainer').hide();
            $('#rejectionReasonSection').hide();
            $('#documentRequestSection').hide();
            $('#cooldownSection').hide();
            $('#documentRequest').val('');
            $('#rejectionReason').val('');
            $('#cooldownDuration').val('24');
            
            // Close any open dropdowns
            closeDropdowns();
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
         * Update application status in UI (both table and modal)
         */
        function updateApplicationStatus(applicationId, status, statusLabel) {
            // If statusLabel is not provided, use status as label
            if (!statusLabel) {
                statusLabel = status;
            }

            // Update application status badge in tables
            const row = $(`[data-application-id="${applicationId}"]`).closest('tr');
            if (row.length > 0) {
                row.find('.aakaari-status-badge')
                    .removeClass('status-pending status-approved status-rejected status-docs-requested status-on-cooldown status-resubmission-allowed')
                    .addClass(`status-${status}`)
                    .text(capitalizeFirstLetter(statusLabel));
            }

            // Update status badge in modal if it's open
            if (applicationModal.hasClass('active') && selectedApplicationId === applicationId) {
                $('#applicationDetails .aakaari-status-badge')
                    .removeClass('status-pending status-approved status-rejected status-docs-requested status-on-cooldown status-resubmission-allowed')
                    .addClass(`status-${status}`)
                    .text(capitalizeFirstLetter(statusLabel));
            }
        }
        
        /**
         * Close all dropdowns
         */
        function closeDropdowns() {
            $('.aakaari-dropdown').removeClass('active');
        }
        
        /**
         * Reset cooldown for an application
         */
        function resetCooldown(applicationId) {
            if (!applicationId) {
                showToast('Invalid application ID', 'error');
                return;
            }

            $.ajax({
                url: aakaari_admin_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'reset_application_cooldown',
                    application_id: applicationId,
                    nonce: aakaari_admin_ajax.nonce
                },
                success: function(response) {
                    if (response.success) {
                        showToast(response.data.message || 'Cooldown reset successfully! User can now reapply.', 'success');
                        // Close modal and reload to reflect changes
                        setTimeout(() => {
                            closeModal();
                            location.reload();
                        }, 1500);
                    } else {
                        showToast(response.data.message || 'Failed to reset cooldown', 'error');
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Reset cooldown error:', error);
                    showToast('Server error. Please try again.', 'error');
                }
            });
        }

        /**
         * Allow resubmission
         */
        function allowResubmission(applicationId) {
            if (!applicationId) {
                showToast('Invalid application ID', 'error');
                return;
            }

            $.ajax({
                url: aakaari_admin_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'allow_resubmission',
                    application_id: applicationId,
                    nonce: aakaari_admin_ajax.nonce
                },
                success: function(response) {
                    if (response.success) {
                        showToast(response.data.message || 'Resubmission enabled successfully', 'success');
                        // Update status badge dynamically
                        updateApplicationStatus(applicationId, 'resubmission_allowed', 'Resubmission Allowed');
                        // Close modal after 1.5 seconds
                        setTimeout(() => closeModal(), 1500);
                    } else {
                        showToast(response.data.message || 'Failed to enable resubmission', 'error');
                    }
                },
                error: function() {
                    showToast('Server error. Please try again.', 'error');
                }
            });
        }

        /**
         * Delete application
         */
        function deleteApplication(applicationId) {
            if (!applicationId) {
                showToast('Invalid application ID', 'error');
                return;
            }

            $.ajax({
                url: aakaari_admin_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'delete_application',
                    application_id: applicationId,
                    nonce: aakaari_admin_ajax.nonce
                },
                success: function(response) {
                    if (response.success) {
                        showToast(response.data.message || 'Application deleted successfully', 'success');
                        // Close modal and refresh the page to remove the deleted application
                        setTimeout(() => {
                            closeModal();
                            location.reload();
                        }, 1500);
                    } else {
                        showToast(response.data.message || 'Failed to delete application', 'error');
                    }
                },
                error: function() {
                    showToast('Server error. Please try again.', 'error');
                }
            });
        }

        /**
         * Request additional documentation
         */
        function requestDocumentation(applicationId, request) {
            if (!applicationId || !request) {
                showToast('Invalid parameters', 'error');
                return;
            }

            const btn = $('#submitDocRequestBtn');
            btn.prop('disabled', true).html('<span>Processing...</span>');

            $.ajax({
                url: aakaari_admin_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'request_application_documentation',
                    application_id: applicationId,
                    documentation_request: request,
                    nonce: aakaari_admin_ajax.nonce
                },
                success: function(response) {
                    if (response.success) {
                        showToast('Documentation request sent successfully', 'success');
                        // Update status badge dynamically
                        updateApplicationStatus(applicationId, 'docs-requested', 'Docs Requested');
                        // Close modal and hide action forms
                        $('#actionFormsContainer').hide();
                        $('#documentRequestSection').hide();
                        $('#documentRequest').val('');
                        // Optional: close modal after 1.5 seconds or keep it open
                        setTimeout(() => closeModal(), 1500);
                    } else {
                        showToast(response.data.message || 'Failed to send request', 'error');
                    }
                },
                error: function() {
                    showToast('Server error. Please try again.', 'error');
                },
                complete: function() {
                    btn.prop('disabled', false).html('<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="22" y1="2" x2="11" y2="13"></line><polygon points="22 2 15 22 11 13 2 9 22 2"></polygon></svg> Send Request');
                }
            });
        }
        
        /**
         * Allow document resubmission
         */
        function allowDocumentResubmission(applicationId) {
            if (!applicationId) {
                showToast('Invalid application ID', 'error');
                return;
            }

            $.ajax({
                url: aakaari_admin_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'allow_document_resubmission',
                    application_id: applicationId,
                    nonce: aakaari_admin_ajax.nonce
                },
                success: function(response) {
                    if (response.success) {
                        showToast('Document resubmission enabled for reseller', 'success');
                        // Update status badge dynamically
                        updateApplicationStatus(applicationId, 'resubmission-allowed', 'Resubmission Allowed');
                        // Close modal after 1.5 seconds
                        setTimeout(() => closeModal(), 1500);
                    } else {
                        showToast(response.data.message || 'Failed to enable resubmission', 'error');
                    }
                },
                error: function() {
                    showToast('Server error. Please try again.', 'error');
                }
            });
        }
        
        /**
         * Set cooldown/timer for review
         */
        function setCooldown(applicationId, duration) {
            if (!applicationId || !duration) {
                showToast('Invalid parameters', 'error');
                return;
            }

            const btn = $('#submitCooldownBtn');
            btn.prop('disabled', true).html('<span>Processing...</span>');

            $.ajax({
                url: aakaari_admin_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'set_application_cooldown',
                    application_id: applicationId,
                    duration: duration,
                    nonce: aakaari_admin_ajax.nonce
                },
                success: function(response) {
                    if (response.success) {
                        showToast(`Cooldown set successfully for ${duration} hours`, 'success');
                        // Update status badge dynamically
                        updateApplicationStatus(applicationId, 'on-cooldown', 'On Cooldown');
                        // Close modal and hide action forms
                        $('#actionFormsContainer').hide();
                        $('#cooldownSection').hide();
                        $('#cooldownDuration').val('24');
                        // Close modal after 1.5 seconds
                        setTimeout(() => closeModal(), 1500);
                    } else {
                        showToast(response.data.message || 'Failed to set cooldown', 'error');
                    }
                },
                error: function() {
                    showToast('Server error. Please try again.', 'error');
                },
                complete: function() {
                    btn.prop('disabled', false).html('<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg> Set Cooldown');
                }
            });
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

        // ============================================
        // ORDER MANAGEMENT HANDLERS
        // ============================================
        console.log('Aakaari Admin Dashboard: Initializing order handlers...');

        /**
         * Show toast notification
         */
        function showOrderToast(message, type = 'info') {
            // Create toast element if it doesn't exist
            if ($('#aakaari-order-toast').length === 0) {
                $('body').append(`
                    <div id="aakaari-order-toast" class="aakaari-toast">
                        <div class="aakaari-toast-message"></div>
                    </div>
                `);

                // Add toast styles
                if ($('#aakaari-toast-styles').length === 0) {
                    $('<style id="aakaari-toast-styles">')
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
                                z-index: 10000;
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
                            .aakaari-modal {
                                display: none;
                                position: fixed;
                                top: 0;
                                left: 0;
                                right: 0;
                                bottom: 0;
                                width: 100vw;
                                height: 100vh;
                                z-index: 999999;
                                align-items: center;
                                justify-content: center;
                                padding: 20px;
                            }
                            .aakaari-modal.active {
                                display: flex !important;
                            }
                            .aakaari-modal-overlay {
                                position: fixed;
                                top: 0;
                                left: 0;
                                right: 0;
                                bottom: 0;
                                width: 100vw;
                                height: 100vh;
                                background-color: rgba(0, 0, 0, 0.75);
                                z-index: 999998;
                            }
                            .aakaari-modal-content {
                                position: relative;
                                background-color: white;
                                border-radius: 12px;
                                box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
                                max-width: 1000px;
                                width: 100%;
                                max-height: 90vh;
                                overflow: hidden;
                                z-index: 999999;
                                display: flex;
                                flex-direction: column;
                            }
                            .aakaari-modal-header {
                                padding: 24px 32px;
                                border-bottom: 2px solid #e5e7eb;
                                display: flex;
                                justify-content: space-between;
                                align-items: center;
                                background-color: #f9fafb;
                                flex-shrink: 0;
                            }
                            .aakaari-modal-header h3 {
                                margin: 0;
                                font-size: 24px;
                                font-weight: 700;
                                color: #111827;
                            }
                            .aakaari-modal-close {
                                background: #ef4444;
                                border: none;
                                font-size: 24px;
                                line-height: 1;
                                cursor: pointer;
                                color: white;
                                padding: 0;
                                width: 40px;
                                height: 40px;
                                display: flex;
                                align-items: center;
                                justify-content: center;
                                border-radius: 6px;
                                transition: background-color 0.2s;
                                flex-shrink: 0;
                            }
                            .aakaari-modal-close:hover {
                                background-color: #dc2626;
                            }
                            .aakaari-modal-body {
                                padding: 32px;
                                overflow-y: auto;
                                flex-grow: 1;
                                background-color: white;
                            }
                            .aakaari-modal-footer {
                                padding: 20px 32px;
                                border-top: 2px solid #e5e7eb;
                                display: flex;
                                justify-content: flex-end;
                                gap: 12px;
                                background-color: #f9fafb;
                                flex-shrink: 0;
                            }
                            .aakaari-button {
                                padding: 12px 24px;
                                border-radius: 6px;
                                font-weight: 600;
                                font-size: 14px;
                                cursor: pointer;
                                transition: all 0.2s;
                                border: none;
                                display: inline-flex;
                                align-items: center;
                                justify-content: center;
                            }
                            .aakaari-button-outline {
                                background-color: white;
                                color: #374151;
                                border: 2px solid #d1d5db;
                            }
                            .aakaari-button-outline:hover {
                                background-color: #f9fafb;
                                border-color: #9ca3af;
                            }
                            .aakaari-button-primary {
                                background-color: #2271b1;
                                color: white;
                                border: 2px solid #2271b1;
                            }
                            .aakaari-button-primary:hover {
                                background-color: #135e96;
                                border-color: #135e96;
                            }
                            .status-badge {
                                display: inline-block;
                                padding: 6px 12px;
                                border-radius: 9999px;
                                font-size: 12px;
                                font-weight: 700;
                                text-transform: uppercase;
                                letter-spacing: 0.5px;
                            }
                            .status-pending {
                                background-color: #fef3c7;
                                color: #92400e;
                            }
                            .status-processing {
                                background-color: #dbeafe;
                                color: #1e40af;
                            }
                            .status-completed {
                                background-color: #d1fae5;
                                color: #065f46;
                            }
                            .status-on-hold {
                                background-color: #fed7aa;
                                color: #9a3412;
                            }
                            .status-cancelled {
                                background-color: #fee2e2;
                                color: #991b1b;
                            }
                            .status-refunded {
                                background-color: #e0e7ff;
                                color: #3730a3;
                            }
                            .status-failed {
                                background-color: #fecaca;
                                color: #7f1d1d;
                            }
                            .status-paid {
                                background-color: #d1fae5;
                                color: #065f46;
                            }
                            .order-details-grid {
                                display: grid;
                                gap: 24px;
                            }
                            .order-details-grid h4 {
                                margin: 0 0 16px 0;
                                font-size: 18px;
                                font-weight: 700;
                                color: #111827;
                                border-bottom: 2px solid #e5e7eb;
                                padding-bottom: 8px;
                            }
                            .aakaari-info-table {
                                width: 100%;
                                border-collapse: collapse;
                            }
                            .aakaari-info-table tr {
                                border-bottom: 1px solid #f3f4f6;
                            }
                            .aakaari-info-table td {
                                padding: 12px 8px;
                                vertical-align: top;
                            }
                            .aakaari-info-table td:first-child {
                                font-weight: 600;
                                color: #6b7280;
                                width: 40%;
                            }
                            .aakaari-info-table td:last-child {
                                color: #111827;
                            }
                        `)
                        .appendTo('head');
                }
            }

            const toast = $('#aakaari-order-toast');
            const toastMessage = toast.find('.aakaari-toast-message');

            // Set message and type
            toastMessage.text(message);
            toast.removeClass('success error warning').addClass(type);

            // Show toast
            toast.addClass('show');

            // Hide after delay
            clearTimeout(window.orderToastTimeout);
            window.orderToastTimeout = setTimeout(() => {
                toast.removeClass('show');
            }, 3000);
        }

        // Handle view order details
        $(document).on('click', '[data-action="view-order"]', function(e) {
            e.preventDefault();
            console.log('View order details clicked');

            const orderId = $(this).data('id');
            console.log('Order ID:', orderId);

            if (!orderId) {
                showOrderToast('Invalid order ID', 'error');
                return;
            }

            // Show loading
            showOrderToast('Loading order details...', 'info');

            // Get order details via AJAX
            $.ajax({
                url: typeof aakaari_admin_ajax !== 'undefined' ? aakaari_admin_ajax.ajax_url : '/wp-admin/admin-ajax.php',
                type: 'POST',
                data: {
                    action: 'aakaari_get_order_details',
                    order_id: orderId,
                    nonce: typeof aakaari_admin_ajax !== 'undefined' ? aakaari_admin_ajax.nonce : ''
                },
                success: function(response) {
                    console.log('AJAX Response:', response);
                    if (response.success && response.data) {
                        showOrderDetailsModal(response.data);
                    } else {
                        showOrderToast(response.data?.message || 'Failed to load order details', 'error');
                    }
                },
                error: function(xhr, status, error) {
                    console.error('AJAX Error:', status, error);
                    showOrderToast('Error loading order details', 'error');
                }
            });
        });

        // Handle update order status
        $(document).on('click', '[data-action="update-status"]', function(e) {
            e.preventDefault();
            const orderId = $(this).data('id');

            if (!orderId) {
                showOrderToast('Invalid order ID', 'error');
                return;
            }

            // Show status selection modal
            showStatusUpdateModal(orderId);
        });

        // Handle download invoice
        $(document).on('click', '[data-action="download-invoice"]', function(e) {
            e.preventDefault();
            const orderId = $(this).data('id');

            if (!orderId) {
                showOrderToast('Invalid order ID', 'error');
                return;
            }

            // Open invoice in new window
            const invoiceUrl = `${window.location.origin}/wp-admin/post.php?post=${orderId}&action=edit`;
            window.open(invoiceUrl, '_blank');

            showOrderToast('Opening invoice...', 'info');
        });

        /**
         * Show order details modal
         */
        function showOrderDetailsModal(orderData) {
            console.log('Showing modal with data:', orderData);

            // Create modal HTML if it doesn't exist
            let modal = $('#orderDetailsModal');
            if (modal.length === 0) {
                $('body').append(`
                    <div id="orderDetailsModal" class="aakaari-modal">
                        <div class="aakaari-modal-overlay"></div>
                        <div class="aakaari-modal-content">
                            <div class="aakaari-modal-header">
                                <h3>Order Details</h3>
                                <button class="aakaari-modal-close" id="closeOrderModalBtn">&times;</button>
                            </div>
                            <div class="aakaari-modal-body" id="orderDetailsContent"></div>
                            <div class="aakaari-modal-footer">
                                <button class="aakaari-button aakaari-button-outline" id="closeOrderModalBtn2">Close</button>
                            </div>
                        </div>
                    </div>
                `);
                modal = $('#orderDetailsModal');

                // Close button handlers
                $(document).on('click', '#closeOrderModalBtn, #closeOrderModalBtn2', function() {
                    modal.removeClass('active');
                });

                modal.find('.aakaari-modal-overlay').on('click', function() {
                    modal.removeClass('active');
                });
            }

            // Populate modal content
            const content = $('#orderDetailsContent');
            let html = `
                <div class="order-details-grid" style="display: grid; gap: 1.5rem;">
                    <div class="order-info-section">
                        <h4 style="margin-bottom: 1rem; font-size: 1.1rem; font-weight: 600;">Order Information</h4>
                        <table class="aakaari-info-table" style="width: 100%; border-collapse: collapse;">
                            <tr style="border-bottom: 1px solid #e5e7eb;"><td style="padding: 0.75rem 0;"><strong>Order ID:</strong></td><td style="padding: 0.75rem 0;">#${orderData.id}</td></tr>
                            <tr style="border-bottom: 1px solid #e5e7eb;"><td style="padding: 0.75rem 0;"><strong>Order Number:</strong></td><td style="padding: 0.75rem 0;">${orderData.order_number}</td></tr>
                            <tr style="border-bottom: 1px solid #e5e7eb;"><td style="padding: 0.75rem 0;"><strong>Date:</strong></td><td style="padding: 0.75rem 0;">${orderData.date}</td></tr>
                            <tr style="border-bottom: 1px solid #e5e7eb;"><td style="padding: 0.75rem 0;"><strong>Status:</strong></td><td style="padding: 0.75rem 0;"><span class="status-badge status-${orderData.status}">${orderData.status}</span></td></tr>
                            <tr style="border-bottom: 1px solid #e5e7eb;"><td style="padding: 0.75rem 0;"><strong>Total:</strong></td><td style="padding: 0.75rem 0;">${orderData.total}</td></tr>
                            <tr><td style="padding: 0.75rem 0;"><strong>Payment Status:</strong></td><td style="padding: 0.75rem 0;">${orderData.payment_status}</td></tr>
                        </table>
                    </div>

                    <div class="customer-info-section">
                        <h4 style="margin-bottom: 1rem; font-size: 1.1rem; font-weight: 600;">Customer Information</h4>
                        <table class="aakaari-info-table" style="width: 100%; border-collapse: collapse;">
                            <tr style="border-bottom: 1px solid #e5e7eb;"><td style="padding: 0.75rem 0;"><strong>Name:</strong></td><td style="padding: 0.75rem 0;">${orderData.customer_name}</td></tr>
                            <tr style="border-bottom: 1px solid #e5e7eb;"><td style="padding: 0.75rem 0;"><strong>Email:</strong></td><td style="padding: 0.75rem 0;">${orderData.customer_email}</td></tr>
                            ${orderData.customer_phone ? `<tr><td style="padding: 0.75rem 0;"><strong>Phone:</strong></td><td style="padding: 0.75rem 0;">${orderData.customer_phone}</td></tr>` : ''}
                        </table>
                    </div>

                    ${orderData.billing_address ? `
                    <div class="address-section">
                        <h4 style="margin-bottom: 0.75rem; font-size: 1.1rem; font-weight: 600;">Billing Address</h4>
                        <p style="margin: 0; line-height: 1.6;">${orderData.billing_address}</p>
                    </div>
                    ` : ''}

                    ${orderData.shipping_address ? `
                    <div class="address-section">
                        <h4 style="margin-bottom: 0.75rem; font-size: 1.1rem; font-weight: 600;">Shipping Address</h4>
                        <p style="margin: 0; line-height: 1.6;">${orderData.shipping_address}</p>
                    </div>
                    ` : ''}

                    ${orderData.items && orderData.items.length > 0 ? `
                    <div class="order-items-section">
                        <h4 style="margin-bottom: 1rem; font-size: 1.1rem; font-weight: 600;">Order Items</h4>
                        <table class="aakaari-table" style="width: 100%; border-collapse: collapse; border: 1px solid #e5e7eb;">
                            <thead>
                                <tr style="background-color: #f9fafb;">
                                    <th style="padding: 0.75rem; text-align: left; border-bottom: 1px solid #e5e7eb;">Product</th>
                                    <th style="padding: 0.75rem; text-align: left; border-bottom: 1px solid #e5e7eb;">Quantity</th>
                                    <th style="padding: 0.75rem; text-align: left; border-bottom: 1px solid #e5e7eb;">Price</th>
                                    <th style="padding: 0.75rem; text-align: left; border-bottom: 1px solid #e5e7eb;">Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                ${orderData.items.map(item => `
                                    <tr ${item.is_customized ? 'style="background-color: #f0f7ff;"' : ''}>
                                        <td style="padding: 0.75rem; border-bottom: 1px solid #e5e7eb;">
                                            ${item.is_customized ? '<span style="display: inline-block; background: #2271b1; color: white; font-size: 10px; padding: 2px 6px; border-radius: 3px; margin-right: 6px; font-weight: bold;">CUSTOM</span>' : ''}
                                            <strong>${item.name}</strong>
                                            ${item.meta_display ? `<div style="margin-top: 10px;">${item.meta_display}</div>` : ''}
                                        </td>
                                        <td style="padding: 0.75rem; border-bottom: 1px solid #e5e7eb;">${item.quantity}</td>
                                        <td style="padding: 0.75rem; border-bottom: 1px solid #e5e7eb;">${item.price}</td>
                                        <td style="padding: 0.75rem; border-bottom: 1px solid #e5e7eb;">${item.total}</td>
                                    </tr>
                                `).join('')}
                            </tbody>
                        </table>
                    </div>
                    ` : ''}

                    ${orderData.notes ? `
                    <div class="order-notes-section">
                        <h4 style="margin-bottom: 0.75rem; font-size: 1.1rem; font-weight: 600;">Order Notes</h4>
                        <p style="margin: 0; line-height: 1.6;">${orderData.notes}</p>
                    </div>
                    ` : ''}
                </div>
            `;

            content.html(html);
            modal.addClass('active');
            console.log('Modal should now be visible');
        }

        /**
         * Show status update modal
         */
        function showStatusUpdateModal(orderId) {
            // Create status update modal if it doesn't exist
            let modal = $('#statusUpdateModal');
            if (modal.length === 0) {
                $('body').append(`
                    <div id="statusUpdateModal" class="aakaari-modal">
                        <div class="aakaari-modal-overlay"></div>
                        <div class="aakaari-modal-content" style="max-width: 500px;">
                            <div class="aakaari-modal-header">
                                <h3>Update Order Status</h3>
                                <button class="aakaari-modal-close" id="closeStatusModalBtn">&times;</button>
                            </div>
                            <div class="aakaari-modal-body">
                                <div class="form-group" style="margin-bottom: 1rem;">
                                    <label for="orderStatusSelect" style="display: block; margin-bottom: 0.5rem; font-weight: 500;">Select New Status:</label>
                                    <select id="orderStatusSelect" class="aakaari-select" style="width: 100%; padding: 0.5rem; border: 1px solid #d1d5db; border-radius: 0.375rem;">
                                        <option value="pending">Pending Payment</option>
                                        <option value="processing">Processing</option>
                                        <option value="on-hold">On Hold</option>
                                        <option value="completed">Completed</option>
                                        <option value="cancelled">Cancelled</option>
                                        <option value="refunded">Refunded</option>
                                        <option value="failed">Failed</option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label for="statusUpdateNote" style="display: block; margin-bottom: 0.5rem; font-weight: 500;">Note (optional):</label>
                                    <textarea id="statusUpdateNote" class="aakaari-textarea" rows="3" placeholder="Add a note about this status change..." style="width: 100%; padding: 0.5rem; border: 1px solid #d1d5db; border-radius: 0.375rem; resize: vertical;"></textarea>
                                </div>
                            </div>
                            <div class="aakaari-modal-footer">
                                <button class="aakaari-button aakaari-button-outline" id="cancelStatusUpdateBtn" style="padding: 0.5rem 1rem; border: 1px solid #d1d5db; border-radius: 0.375rem; background: white; cursor: pointer;">Cancel</button>
                                <button class="aakaari-button" id="confirmStatusUpdateBtn" style="padding: 0.5rem 1rem; border: none; border-radius: 0.375rem; background: #2271b1; color: white; cursor: pointer;">Update Status</button>
                            </div>
                        </div>
                    </div>
                `);
                modal = $('#statusUpdateModal');

                // Close button handlers
                $(document).on('click', '#closeStatusModalBtn, #cancelStatusUpdateBtn', function() {
                    modal.removeClass('active');
                });

                modal.find('.aakaari-modal-overlay').on('click', function() {
                    modal.removeClass('active');
                });
            }

            // Store order ID for later use
            modal.data('orderId', orderId);

            // Show modal
            modal.addClass('active');

            // Confirm button handler
            $('#confirmStatusUpdateBtn').off('click').on('click', function() {
                const newStatus = $('#orderStatusSelect').val();
                const note = $('#statusUpdateNote').val();

                updateOrderStatus(orderId, newStatus, note);
            });
        }

        /**
         * Update order status
         */
        function updateOrderStatus(orderId, newStatus, note) {
            // Show loading
            showOrderToast('Updating order status...', 'info');

            $.ajax({
                url: typeof aakaari_admin_ajax !== 'undefined' ? aakaari_admin_ajax.ajax_url : '/wp-admin/admin-ajax.php',
                type: 'POST',
                data: {
                    action: 'aakaari_update_order_status',
                    order_id: orderId,
                    status: newStatus,
                    note: note,
                    nonce: typeof aakaari_admin_ajax !== 'undefined' ? aakaari_admin_ajax.nonce : ''
                },
                success: function(response) {
                    if (response.success) {
                        showOrderToast('Order status updated successfully', 'success');
                        $('#statusUpdateModal').removeClass('active');

                        // Reload the page to show updated status
                        setTimeout(() => {
                            location.reload();
                        }, 1500);
                    } else {
                        showOrderToast(response.data?.message || 'Failed to update order status', 'error');
                    }
                },
                error: function() {
                    showOrderToast('Error updating order status', 'error');
                }
            });
        }

        console.log('Order handlers initialized successfully!');

        // ============================================
        // END ORDER MANAGEMENT HANDLERS
        // ============================================

        // Initialize Custom Products Admin if we're on the products tab
        if ($('#aakaari-cp-app').length > 0) {
            initializeCustomProductsAdmin();
        }
    });

    /**
     * Custom Products Admin Module
     * Converted from the original vanilla JS to jQuery-based implementation
     */
function initializeCustomProductsAdmin() {
    // Check if we have the required Ajax configuration
    const ajax = window.aakaari_cp_ajax || {};
    const $root = $('#aakaari-cp-app');
    
    if (!$root.length) return;

    // Add this check for plugin activation state
    const pluginIsActive = typeof ajax.ajax_url !== 'undefined' && 
                           typeof ajax.nonce !== 'undefined' &&
                           !$('#aakaari-cp-app .aakaari-plugin-not-active').length;
    
    // If plugin isn't active, show the mock interface instead
    if (!pluginIsActive) {
        console.log('Custom Products plugin not active or not properly initialized, showing mock interface');
        mockCustomProductsInterface();
        return; // Exit early - don't try to use the real plugin code
    }
        // State
        let state = {
            products: [],
            fabrics: [],
            print_types: [],
            woo_colors: [],
            categories: []
        };

        // Fetch initial data
        function fetchData() {
            return $.ajax({
                url: ajax.ajax_url,
                method: 'POST',
                data: {
                    action: 'aakaari_cp_get_products',
                    _ajax_nonce: ajax.nonce
                },
                success: function(response) {
                    if (response.success) {
                        state = $.extend(state, response.data);
                        render();
                    } else {
                        console.error('Fetch error', response);
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Ajax error:', status, error);
                }
            });
        }

        // Save data
        function saveData() {
            return $.ajax({
                url: ajax.ajax_url,
                method: 'POST',
                data: {
                    action: 'aakaari_cp_save_products',
                    _ajax_nonce: ajax.nonce,
                    data: JSON.stringify(state)
                },
                success: function(response) {
                    if (response.success) {
                        showToast('Custom products saved successfully!', 'success');
                    } else {
                        console.error('Save error', response);
                        showToast('Error saving products', 'error');
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Ajax error:', status, error);
                    showToast('Server error. Please try again.', 'error');
                }
            });
        }

        // Reset data
        function resetData() {
            if (!confirm('Reset stored Aakaari configuration?')) return;
            
            return $.ajax({
                url: ajax.ajax_url,
                method: 'POST',
                data: {
                    action: 'aakaari_cp_reset',
                    _ajax_nonce: ajax.nonce
                },
                success: function() {
                    showToast('Configuration reset successfully', 'success');
                    fetchData();
                },
                error: function(xhr, status, error) {
                    console.error('Ajax error:', status, error);
                    showToast('Server error. Please try again.', 'error');
                }
            });
        }

        // UI rendering
        function render() {
            $root.empty();
            
            // Stats cards
            const totalProducts = state.products.length;
            const activeProducts = state.products.filter(p => p.isActive).length;
            const totalSides = state.products.reduce((s, p) => s + (p.sides ? p.sides.length : 0), 0);

            const $grid = $('<div>').addClass('aakaari-grid');
            $grid.append(makeCard('Total Products', String(totalProducts), `${activeProducts} active`));
            $grid.append(makeCard('Total Sides', String(totalSides), 'Across all products'));
            $grid.append(makeCard('Fabrics', String(state.fabrics.length), 'Available materials'));
            $grid.append(makeCard('Print Types', String(state.print_types.length), 'Printing methods'));
            $root.append($grid);

            const $controls = $('<div>').addClass('aakaari-controls');
            $controls.append(button('Add Product', () => openProductModal(createEmptyProduct())));
            $controls.append(button('Save All', saveData));
            $controls.append(button('Reset', resetData));
            $root.append($controls);

            // Products table
            const $list = $('<div>').addClass('aakaari-list');
            const $table = $('<table>').addClass('aakaari-table');
            const $thead = $('<thead>');
            const $tr = $('<tr>');
            
            $tr.append($('<th>').text('Product'));
            $tr.append($('<th>').text('Category'));
            $tr.append($('<th>').text('Price'));
            $tr.append($('<th>').text('Sides'));
            $tr.append($('<th>').text('Status'));
            $tr.append($('<th>').text('Action'));
            
            $thead.append($tr);
            $table.append($thead);

            const $tbody = $('<tbody>');
            (state.products || []).forEach(p => {
                const $tr = $('<tr>');
                
                // Product name and description
                const $td1 = $('<td>');
                const $div1 = $('<div>');
                $div1.append($('<div>').text(p.name || '(unnamed)'));
                $div1.append($('<div>').addClass('aakaari-muted').text((p.description || '').substring(0, 60) + '...'));
                $td1.append($div1);
                
                // Category
                const $td2 = $('<td>').text(p.category || '');
                
                // Price
                const $td3 = $('<td>').text(p.basePrice ? '₹' + Number(p.basePrice).toFixed(2) : '-');
                
                // Sides count
                const $td4 = $('<td>').text(String((p.sides || []).length));
                
                // Status toggle
                const $td5 = $('<td>');
                const $statusDiv = $('<div>');
                const $checkbox = $('<input>').attr({type: 'checkbox'});
                $checkbox.prop('checked', !!p.isActive);
                $checkbox.on('change', () => {
                    p.isActive = $checkbox.prop('checked');
                    render(); // Re-render to reflect the change immediately
                });
                $statusDiv.append($checkbox);
                $statusDiv.append($('<span>').css('margin-left', '6px').text($checkbox.prop('checked') ? 'Active' : 'Inactive'));
                $td5.append($statusDiv);
                
                // Action button
                const $td6 = $('<td>');
                $td6.append(button('Edit', () => openProductModal(cloneDeep(p))));
                
                $tr.append($td1, $td2, $td3, $td4, $td5, $td6);
                $tbody.append($tr);
            });
            
            $table.append($tbody);
            $list.append($table);
            $root.append($list);
        }

        // Helper UI builders
        function makeCard(title, big, small) {
            const $card = $('<div>').addClass('aakaari-card');
            $card.append($('<div>').css({fontWeight: '600', marginBottom: '6px'}).text(title));
            $card.append($('<div>').css({fontSize: '22px', marginBottom: '4px'}).text(big));
            $card.append($('<div>').css({fontSize: '12px', color: '#666'}).text(small));
            return $card;
        }

        function button(label, cb) {
            const $button = $('<button>').addClass('aakaari-btn').text(label);
            $button.on('click', cb);
            return $button;
        }

        function cloneDeep(v) {
            return JSON.parse(JSON.stringify(v));
        }

        function createEmptyProduct() {
            return {
                id: 'p-' + Date.now(),
                name: '',
                description: '',
                basePrice: 0,
                salePrice: null,
                category: '',
                woocommerceId: '',
                isActive: false,
                colors: [],
                availablePrintTypes: [],
                availableFabrics: [],
                sides: []
            };
        }

        // Modal product editor
        let currentModal = null;
        
        function openProductModal(product) {
            closeModal();

            const $backdrop = $('<div>').addClass('aakaari-modal-backdrop');
            const $modal = $('<div>').addClass('aakaari-modal');

            // Left column - product details
            const $left = $('<div>').css({display: 'grid', gap: '8px', marginBottom: '12px'});
            $left.append(labeled('Name', inputText(product, 'name')));
            $left.append(labeled('Description', inputText(product, 'description')));
            $left.append(labeled('Base Price', inputNumber(product, 'basePrice')));
            $left.append(labeled('Sale Price', inputNumber(product, 'salePrice')));
            $left.append(labeled('Category', selectCategory(product)));
            $left.append(labeled('WooCommerce ID', inputText(product, 'woocommerceId')));
            $left.append(labeled('Active', checkboxInput(product, 'isActive')));

            // Sides area
            const $sidesList = $('<div>').attr('id', 'aakaari-sides-list');
            renderSides(product).forEach(node => $sidesList.append(node));
            
            const $addSideBtn = button('Add Side', () => {
                const s = {
                    id: 'side-' + Date.now(),
                    name: 'New Side',
                    printAreas: [],
                    restrictionAreas: []
                };
                product.sides = product.sides || [];
                product.sides.push(s);
                $sidesList.empty();
                renderSides(product).forEach(node => $sidesList.append(node));
            });

            // Save / cancel
            const $actions = $('<div>').css({
                display: 'flex',
                gap: '8px',
                justifyContent: 'flex-end',
                marginTop: '12px'
            });
            
            $actions.append(button('Cancel', closeModal));
            $actions.append(button('Save', () => {
                const idx = state.products.findIndex(p => p.id === product.id);
                if (idx === -1) {
                    state.products.push(product);
                } else {
                    state.products[idx] = product;
                }
                closeModal();
                render();
            }));

            $modal.append($left);
            $modal.append($('<div>').append(
                $('<h3>').text('Sides'),
                $sidesList,
                $addSideBtn
            ));
            $modal.append($actions);

            $('body').append($backdrop);
            $('body').append($modal);
            currentModal = {
                backdrop: $backdrop,
                modal: $modal,
                product: product
            };

            $backdrop.on('click', closeModal);
        }

        function closeModal() {
            if (!currentModal) return;
            currentModal.modal.remove();
            currentModal.backdrop.remove();
            currentModal = null;
        }

        function labeled(label, inputEl) {
            const $container = $('<div>');
            $container.append($('<div>').css({fontWeight: '600', marginBottom: '4px'}).text(label));
            $container.append(inputEl);
            return $container;
        }

        function inputText(obj, prop) {
            const $input = $('<input>').addClass('aakaari-input').val(obj[prop] || '');
            $input.on('input', (e) => obj[prop] = $(e.target).val());
            return $input;
        }

        function inputNumber(obj, prop) {
            const $input = $('<input>').addClass('aakaari-input').attr({
                type: 'number',
                step: '0.01'
            }).val((obj[prop] !== null && obj[prop] !== undefined) ? obj[prop] : '');
            
            $input.on('input', (e) => {
                obj[prop] = $(e.target).val() === '' ? null : parseFloat($(e.target).val());
            });
            
            return $input;
        }

        function checkboxInput(obj, prop) {
            const $wrap = $('<label>');
            const $checkbox = $('<input>').attr({type: 'checkbox'});
            $checkbox.prop('checked', !!obj[prop]);
            $checkbox.on('change', () => obj[prop] = $checkbox.prop('checked'));
            $wrap.append($checkbox);
            return $wrap;
        }

        function selectCategory(product) {
            const $select = $('<select>').addClass('aakaari-input');
            $select.append($('<option>').attr('value', '').text('-- select --'));
            (state.categories || []).forEach(c => {
                $select.append($('<option>').attr('value', c.name).text(c.name));
            });
            $select.val(product.category || '');
            $select.on('change', (e) => product.category = $(e.target).val());
            return $select;
        }

        function renderSides(product) {
            const nodes = [];
            (product.sides || []).forEach((s, idx) => {
                const $wrap = $('<div>').css({
                    border: '1px solid #eee',
                    padding: '8px',
                    marginBottom: '6px',
                    borderRadius: '4px'
                });
                
                const $nameInput = $('<input>').addClass('aakaari-input').val(s.name);
                $nameInput.on('input', (e) => s.name = $(e.target).val());
                
                const $areasSummary = $('<div>');
                $areasSummary.append($('<div>').text('Print Areas: ' + ((s.printAreas || []).length)));
                $areasSummary.append($('<div>').text('Restriction Areas: ' + ((s.restrictionAreas || []).length)));
                
                // Button to open full side editor (includes canvas)
                const $openEditor = button('Open Side Editor', () => openSideEditor(product, idx));
                
                const $removeBtn = button('Remove Side', () => {
                    product.sides.splice(idx, 1);
                    const $list = $('#aakaari-sides-list');
                    $list.empty();
                    renderSides(product).forEach(node => $list.append(node));
                });
                
                $wrap.append($nameInput);
                $wrap.append($areasSummary);
                $wrap.append($openEditor);
                $wrap.append($removeBtn);
                nodes.push($wrap);
            });
            return nodes;
        }

        /**
         * Side Editor with Canvas
         * Ported from React/TSX to jQuery
         */
        function openSideEditor(product, sideIndex) {
            const side = product.sides[sideIndex];
            if (!side) return;

            // modal setup
            const $backdrop = $('<div>').addClass('aakaari-modal-backdrop');
            const $modal = $('<div>').addClass('aakaari-modal');

            // toolbar
            const $toolbar = $('<div>').addClass('aakaari-canvas-toolbar');
            const $selectBtn = button('Select', () => setTool('select'));
            const $drawPrintBtn = button('Draw Print', () => setTool('draw-print'));
            const $drawRestrBtn = button('Draw Restriction', () => setTool('draw-restriction'));
            $toolbar.append($selectBtn, $drawPrintBtn, $drawRestrBtn);

            // canvas wrapper
            const $canvasWrap = $('<div>').addClass('aakaari-canvas-wrap');
            const CANVAS_WIDTH = 700;
            const CANVAS_HEIGHT = 700;
            const $canvas = $('<canvas>').attr({
                width: CANVAS_WIDTH,
                height: CANVAS_HEIGHT
            }).css({
                cursor: 'default',
                display: 'block'
            });
            $canvasWrap.append($canvas);

            // right panel for area management
            const $right = $('<div>').css({
                marginLeft: '12px',
                minWidth: '240px',
                maxWidth: '320px'
            });

            // Add area buttons
            const $addPrintBtn = button('Add Print Area', () => {
                side.printAreas = side.printAreas || [];
                side.printAreas.push({
                    id: 'area-' + Date.now(),
                    name: 'Print Area',
                    x: 50,
                    y: 50,
                    width: 120,
                    height: 120
                });
                redraw();
                refreshSideList();
            });
            
            const $addRestrBtn = button('Add Restriction Area', () => {
                side.restrictionAreas = side.restrictionAreas || [];
                side.restrictionAreas.push({
                    id: 'res-' + Date.now(),
                    name: 'Restriction',
                    x: 200,
                    y: 200,
                    width: 80,
                    height: 80
                });
                redraw();
                refreshSideList();
            });

            $right.append($addPrintBtn, $addRestrBtn, $('<hr>'));
            const $sideList = $('<div>');
            $right.append($sideList);

            const $footer = $('<div>').css({
                display: 'flex',
                gap: '8px',
                justifyContent: 'flex-end',
                marginTop: '12px'
            });
            
            $footer.append(button('Close', () => {
                $backdrop.remove();
                $modal.remove();
                render();
            }));
            
            $footer.append(button('Save Side', () => {
                $backdrop.remove();
                $modal.remove();
                render();
            }));

            // layout
            const $container = $('<div>').css({
                display: 'flex',
                gap: '12px',
                alignItems: 'flex-start'
            });
            $container.append($('<div>').append($toolbar, $canvasWrap));
            $container.append($right);
            $modal.append($container);
            $modal.append($footer);

            $('body').append($backdrop, $modal);

            // Canvas logic state
            const canvas = $canvas[0];
            const ctx = canvas.getContext('2d');
            let toolMode = 'select'; // 'select' | 'draw-print' | 'draw-restriction'
            let interactionMode = 'none'; // 'none' | 'drawing' | 'moving' | 'resizing'
            let selectedType = null; // 'print' | 'restriction' | null
            let selectedIndex = null;
            let dragStart = null;
            let tempArea = null;
            const HANDLE_SIZE = 8;
            let resizeHandle = null;
            let hoveredHandle = null;
            let cursor = 'default';

            function setTool(mode) {
                toolMode = mode;
                selectedType = null;
                selectedIndex = null;
                interactionMode = 'none';
                redraw();
            }

            function redraw() {
                // clear + bg
                ctx.fillStyle = '#F8FAFC';
                ctx.fillRect(0, 0, CANVAS_WIDTH, CANVAS_HEIGHT);

                // grid
                ctx.strokeStyle = '#E2E8F0';
                ctx.lineWidth = 1;
                for (let i = 0; i <= CANVAS_WIDTH; i += 50) {
                    ctx.beginPath();
                    ctx.moveTo(i, 0);
                    ctx.lineTo(i, CANVAS_HEIGHT);
                    ctx.stroke();
                }
                for (let i = 0; i <= CANVAS_HEIGHT; i += 50) {
                    ctx.beginPath();
                    ctx.moveTo(0, i);
                    ctx.lineTo(CANVAS_WIDTH, i);
                    ctx.stroke();
                }

                // draw restriction areas first
                (side.restrictionAreas || []).forEach((area, idx) => {
                    ctx.fillStyle = (selectedType === 'restriction' && selectedIndex === idx) ? 'rgba(239,68,68,0.2)' : 'rgba(239,68,68,0.1)';
                    ctx.fillRect(area.x, area.y, area.width, area.height);
                    ctx.strokeStyle = '#EF4444';
                    ctx.lineWidth = (selectedType === 'restriction' && selectedIndex === idx) ? 2 : 1;
                    ctx.setLineDash([4, 4]);
                    ctx.strokeRect(area.x, area.y, area.width, area.height);
                    ctx.setLineDash([]);
                    ctx.fillStyle = '#EF4444';
                    ctx.font = '12px Arial';
                    ctx.fillText(area.name || 'Restriction', area.x + 6, area.y + 15);
                    if (selectedType === 'restriction' && selectedIndex === idx) drawHandles(area);
                });

                // draw print areas
                (side.printAreas || []).forEach((area, idx) => {
                    ctx.strokeStyle = (selectedType === 'print' && selectedIndex === idx) ? '#F97316' : '#2563EB';
                    ctx.lineWidth = (selectedType === 'print' && selectedIndex === idx) ? 3 : 2;
                    ctx.setLineDash([8, 4]);
                    ctx.strokeRect(area.x, area.y, area.width, area.height);
                    ctx.setLineDash([]);
                    ctx.fillStyle = (selectedType === 'print' && selectedIndex === idx) ? '#F97316' : '#2563EB';
                    ctx.font = '12px Arial';
                    ctx.fillText(area.name || 'Print', area.x + 6, area.y - 6);
                    if (selectedType === 'print' && selectedIndex === idx) drawHandles(area);
                });

                canvas.style.cursor = cursor;
            }

            function drawHandles(area) {
                // corners: tl, tr, bl, br; sides: top,right,bottom,left
                const handles = getHandles(area);
                ctx.fillStyle = '#fff';
                ctx.strokeStyle = '#333';
                handles.forEach(h => {
                    ctx.fillRect(h.x - HANDLE_SIZE / 2, h.y - HANDLE_SIZE / 2, HANDLE_SIZE, HANDLE_SIZE);
                    ctx.strokeRect(h.x - HANDLE_SIZE / 2, h.y - HANDLE_SIZE / 2, HANDLE_SIZE, HANDLE_SIZE);
                });
            }

            function getHandles(area) {
                const x = area.x,
                      y = area.y,
                      w = area.width,
                      h = area.height;
                return [{
                        name: 'tl',
                        x: x,
                        y: y
                    },
                    {
                        name: 'tr',
                        x: x + w,
                        y: y
                    },
                    {
                        name: 'bl',
                        x: x,
                        y: y + h
                    },
                    {
                        name: 'br',
                        x: x + w,
                        y: y + h
                    },
                    {
                        name: 'top',
                        x: x + w / 2,
                        y: y
                    },
                    {
                        name: 'right',
                        x: x + w,
                        y: y + h / 2
                    },
                    {
                        name: 'bottom',
                        x: x + w / 2,
                        y: y + h
                    },
                    {
                        name: 'left',
                        x: x,
                        y: y + h / 2
                    }
                ];
            }

            function getAreaAtPoint(px, py) {
                // find topmost (print areas drawn last so select print area first)
                const printAreas = side.printAreas || [];
                for (let i = printAreas.length - 1; i >= 0; i--) {
                    const a = printAreas[i];
                    if (px >= a.x && px <= a.x + a.width && py >= a.y && py <= a.y + a.height) return {
                        type: 'print',
                        index: i,
                        area: a
                    };
                }
                const resAreas = side.restrictionAreas || [];
                for (let i = resAreas.length - 1; i >= 0; i--) {
                    const a = resAreas[i];
                    if (px >= a.x && px <= a.x + a.width && py >= a.y && py <= a.y + a.height) return {
                        type: 'restriction',
                        index: i,
                        area: a
                    };
                }
                return null;
            }

            function getHandleUnderPoint(area, px, py) {
                const handles = getHandles(area);
                for (const h of handles) {
                    if (px >= h.x - HANDLE_SIZE / 2 && px <= h.x + HANDLE_SIZE / 2 && py >= h.y - HANDLE_SIZE / 2 && py <= h.y + HANDLE_SIZE / 2) return h.name;
                }
                return null;
            }

            // mouse events
            $canvas.on('mousedown', (e) => {
                const rect = canvas.getBoundingClientRect();
                const x = Math.round(e.clientX - rect.left);
                const y = Math.round(e.clientY - rect.top);

                if (toolMode === 'draw-print' || toolMode === 'draw-restriction') {
                    interactionMode = 'drawing';
                    dragStart = {
                        x,
                        y
                    };
                    tempArea = {
                        id: 'tmp',
                        name: (toolMode === 'draw-print') ? 'New Print' : 'New Restriction',
                        x,
                        y,
                        width: 0,
                        height: 0
                    };
                    redraw();
                    return;
                }

                // select mode
                const hit = getAreaAtPoint(x, y);
                if (hit) {
                    selectedType = hit.type;
                    selectedIndex = hit.index;
                    const area = hit.area;
                    const handle = getHandleUnderPoint(area, x, y);
                    if (handle) {
                        interactionMode = 'resizing';
                        resizeHandle = handle;
                    } else {
                        interactionMode = 'moving';
                    }
                    dragStart = {
                        x,
                        y,
                        origArea: Object.assign({}, area)
                    };
                } else {
                    selectedType = null;
                    selectedIndex = null;
                    interactionMode = 'none';
                }
                redraw();
                refreshSideList();
            });

            $canvas.on('mousemove', (e) => {
                const rect = canvas.getBoundingClientRect();
                const x = Math.round(e.clientX - rect.left);
                const y = Math.round(e.clientY - rect.top);

                // drawing
                if (interactionMode === 'drawing' && dragStart && tempArea) {
                    const nx = Math.min(dragStart.x, x);
                    const ny = Math.min(dragStart.y, y);
                    const nw = Math.abs(x - dragStart.x);
                    const nh = Math.abs(y - dragStart.y);
                    tempArea.x = nx;
                    tempArea.y = ny;
                    tempArea.width = nw;
                    tempArea.height = nh;
                    // preview: draw existing then temp
                    redraw();
                    // draw temp rectangle overlay
                    ctx.setLineDash([6, 4]);
                    ctx.strokeStyle = (toolMode === 'draw-print') ? '#2563EB' : '#EF4444';
                    ctx.strokeRect(tempArea.x, tempArea.y, tempArea.width, tempArea.height);
                    ctx.setLineDash([]);
                    return;
                }

                // moving
                if (interactionMode === 'moving' && dragStart && selectedType !== null && selectedIndex !== null) {
                    const dx = x - dragStart.x;
                    const dy = y - dragStart.y;
                    const areaList = selectedType === 'print' ? side.printAreas : side.restrictionAreas;
                    const area = areaList[selectedIndex];
                    area.x = Math.max(0, dragStart.origArea.x + dx);
                    area.y = Math.max(0, dragStart.origArea.y + dy);
                    redraw();
                    refreshSideList();
                    return;
                }

                // resizing
                if (interactionMode === 'resizing' && dragStart && resizeHandle && selectedType !== null && selectedIndex !== null) {
                    const areaList = selectedType === 'print' ? side.printAreas : side.restrictionAreas;
                    const area = areaList[selectedIndex];
                    const ox = dragStart.origArea.x,
                          oy = dragStart.origArea.y,
                          ow = dragStart.origArea.width,
                          oh = dragStart.origArea.height;
                    let nx = ox,
                        ny = oy,
                        nw = ow,
                        nh = oh;
                    if (resizeHandle.includes('l')) {
                        nx = Math.min(ox + ow - 10, x);
                        nw = Math.max(10, ox + ow - nx);
                    }
                    if (resizeHandle.includes('r')) {
                        nw = Math.max(10, x - ox);
                    }
                    if (resizeHandle.includes('t')) {
                        ny = Math.min(oy + oh - 10, y);
                        nh = Math.max(10, oy + oh - ny);
                    }
                    if (resizeHandle.includes('b')) {
                        nh = Math.max(10, y - oy);
                    }
                    area.x = Math.max(0, nx);
                    area.y = Math.max(0, ny);
                    area.width = Math.max(10, nw);
                    area.height = Math.max(10, nh);
                    redraw();
                    refreshSideList();
                    return;
                }

                // hover handle detection (for cursor)
                const hit = getAreaAtPoint(x, y);
                if (hit) {
                    const area = hit.area;
                    const handle = getHandleUnderPoint(area, x, y);
                    if (handle) {
                        cursor = 'nwse-resize';
                    } else cursor = 'move';
                } else {
                    cursor = 'default';
                }
                canvas.style.cursor = cursor;
            });

            $canvas.on('mouseup', (e) => {
                const rect = canvas.getBoundingClientRect();
                const x = Math.round(e.clientX - rect.left);
                const y = Math.round(e.clientY - rect.top);

                if (interactionMode === 'drawing' && tempArea) {
                    if (tempArea.width >= 8 && tempArea.height >= 8) {
                        if (toolMode === 'draw-print') {
                            side.printAreas = side.printAreas || [];
                            side.printAreas.push(Object.assign({}, tempArea, {
                                id: 'area-' + Date.now()
                            }));
                        } else {
                            side.restrictionAreas = side.restrictionAreas || [];
                            side.restrictionAreas.push(Object.assign({}, tempArea, {
                                id: 'res-' + Date.now()
                            }));
                        }
                    }
                    tempArea = null;
                    interactionMode = 'none';
                    dragStart = null;
                    redraw();
                    refreshSideList();
                    return;
                }

                if (interactionMode === 'moving' || interactionMode === 'resizing') {
                    interactionMode = 'none';
                    resizeHandle = null;
                    dragStart = null;
                }
            });

            // side list + edit controls
            function refreshSideList() {
                $sideList.empty();

                // Print Areas
                const $printHeader = $('<div>').append($('<strong>').text('Print Areas'));
                $sideList.append($printHeader);
                
                (side.printAreas || []).forEach((area, i) => {
                    const $row = $('<div>').css({
                        border: '1px solid #eee',
                        padding: '6px',
                        margin: '6px 0',
                        borderRadius: '4px',
                        display: 'flex',
                        justifyContent: 'space-between',
                        alignItems: 'center'
                    });
                    
                    const $info = $('<div>').append(
                        $('<div>').append($('<strong>').text(area.name || ('Print ' + (i + 1)))),
                        $('<div>').css('font-size', '12px').css('color', '#666').text(`${area.width}×${area.height} @ (${area.x},${area.y})`)
                    );
                    
                    const $actions = $('<div>');
                    $actions.append(button('Select', () => {
                        selectedType = 'print';
                        selectedIndex = i;
                        redraw();
                    }));
                    $actions.append(button('Delete', () => {
                        side.printAreas.splice(i, 1);
                        if (selectedType === 'print' && selectedIndex === i) {
                            selectedType = null;
                            selectedIndex = null;
                        }
                        redraw();
                        refreshSideList();
                    }));
                    
                    $row.append($info, $actions);
                    $sideList.append($row);
                });

                // Restriction Areas
                const $resHeader = $('<div>').append($('<strong>').text('Restriction Areas'));
                $sideList.append($resHeader);
                
                (side.restrictionAreas || []).forEach((area, i) => {
                    const $row = $('<div>').css({
                        border: '1px solid #fee',
                        padding: '6px',
                        margin: '6px 0',
                        borderRadius: '4px',
                        display: 'flex',
                        justifyContent: 'space-between',
                        alignItems: 'center'
                    });
                    
                    const $info = $('<div>').append(
                        $('<div>').append($('<strong>').text(area.name || ('Restr ' + (i + 1)))),
                        $('<div>').css('font-size', '12px').css('color', '#666').text(`${area.width}×${area.height} @ (${area.x},${area.y})`)
                    );
                    
                    const $actions = $('<div>');
                    $actions.append(button('Select', () => {
                        selectedType = 'restriction';
                        selectedIndex = i;
                        redraw();
                    }));
                    $actions.append(button('Delete', () => {
                        side.restrictionAreas.splice(i, 1);
                        if (selectedType === 'restriction' && selectedIndex === i) {
                            selectedType = null;
                            selectedIndex = null;
                        }
                        redraw();
                        refreshSideList();
                    }));
                    
                    $row.append($info, $actions);
                    $sideList.append($row);
                });
            }

            refreshSideList();
            redraw();
        }

        /**
         * Show toast notification for Custom Products
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

        // --- Order Action Handlers ---
        console.log('Aakaari Admin Dashboard: Order handlers initialized');

        // Handle view order details
        $(document).on('click', '[data-action="view-order"]', function(e) {
            e.preventDefault();
            console.log('View order details clicked');

            const orderId = $(this).data('id');
            console.log('Order ID:', orderId);

            if (!orderId) {
                alert('Invalid order ID');
                return;
            }

            // Show loading indicator
            alert('Loading order details for Order #' + orderId + '...');

            // Get order details via AJAX
            $.ajax({
                url: typeof aakaari_admin_ajax !== 'undefined' ? aakaari_admin_ajax.ajax_url : '/wp-admin/admin-ajax.php',
                type: 'POST',
                data: {
                    action: 'aakaari_get_order_details',
                    order_id: orderId,
                    nonce: typeof aakaari_admin_ajax !== 'undefined' ? aakaari_admin_ajax.nonce : ''
                },
                success: function(response) {
                    if (response.success && response.data) {
                        showOrderDetailsModal(response.data);
                    } else {
                        showToast(response.data?.message || 'Failed to load order details', 'error');
                    }
                },
                error: function() {
                    showToast('Error loading order details', 'error');
                }
            });
        });

        // Handle update order status
        $(document).on('click', '[data-action="update-status"]', function(e) {
            e.preventDefault();
            const orderId = $(this).data('id');

            if (!orderId) {
                showToast('Invalid order ID', 'error');
                return;
            }

            // Show status selection modal
            showStatusUpdateModal(orderId);
        });

        // Handle download invoice
        $(document).on('click', '[data-action="download-invoice"]', function(e) {
            e.preventDefault();
            const orderId = $(this).data('id');

            if (!orderId) {
                showToast('Invalid order ID', 'error');
                return;
            }

            // Open invoice in new window (assuming WooCommerce invoice URL)
            const invoiceUrl = `${window.location.origin}/wp-admin/post.php?post=${orderId}&action=edit`;
            window.open(invoiceUrl, '_blank');

            showToast('Opening invoice...', 'info');
        });

        // --- Order Modal Functions ---

        function showOrderDetailsModal(orderData) {
            // Create modal HTML if it doesn't exist
            let modal = $('#orderDetailsModal');
            if (modal.length === 0) {
                $('body').append(`
                    <div id="orderDetailsModal" class="aakaari-modal">
                        <div class="aakaari-modal-overlay"></div>
                        <div class="aakaari-modal-content" style="max-width: 800px; max-height: 90vh; overflow-y: auto;">
                            <div class="aakaari-modal-header">
                                <h3>Order Details</h3>
                                <button class="aakaari-modal-close" id="closeOrderModalBtn">&times;</button>
                            </div>
                            <div class="aakaari-modal-body" id="orderDetailsContent"></div>
                            <div class="aakaari-modal-footer">
                                <button class="aakaari-button aakaari-button-outline" id="closeOrderModalBtn2">Close</button>
                            </div>
                        </div>
                    </div>
                `);
                modal = $('#orderDetailsModal');

                // Close button handlers
                $('#closeOrderModalBtn, #closeOrderModalBtn2').on('click', function() {
                    modal.removeClass('active');
                });

                modal.find('.aakaari-modal-overlay').on('click', function() {
                    modal.removeClass('active');
                });
            }

            // Populate modal content
            const content = $('#orderDetailsContent');
            let html = `
                <div class="order-details-grid">
                    <div class="order-info-section">
                        <h4>Order Information</h4>
                        <table class="aakaari-info-table">
                            <tr><td><strong>Order ID:</strong></td><td>#${orderData.id}</td></tr>
                            <tr><td><strong>Order Number:</strong></td><td>${orderData.order_number}</td></tr>
                            <tr><td><strong>Date:</strong></td><td>${orderData.date}</td></tr>
                            <tr><td><strong>Status:</strong></td><td><span class="status-badge status-${orderData.status}">${orderData.status}</span></td></tr>
                            <tr><td><strong>Total:</strong></td><td>${orderData.total}</td></tr>
                            <tr><td><strong>Payment Status:</strong></td><td>${orderData.payment_status}</td></tr>
                        </table>
                    </div>

                    <div class="customer-info-section">
                        <h4>Customer Information</h4>
                        <table class="aakaari-info-table">
                            <tr><td><strong>Name:</strong></td><td>${orderData.customer_name}</td></tr>
                            <tr><td><strong>Email:</strong></td><td>${orderData.customer_email}</td></tr>
                            ${orderData.customer_phone ? `<tr><td><strong>Phone:</strong></td><td>${orderData.customer_phone}</td></tr>` : ''}
                        </table>
                    </div>

                    ${orderData.billing_address ? `
                    <div class="address-section">
                        <h4>Billing Address</h4>
                        <p>${orderData.billing_address}</p>
                    </div>
                    ` : ''}

                    ${orderData.shipping_address ? `
                    <div class="address-section">
                        <h4>Shipping Address</h4>
                        <p>${orderData.shipping_address}</p>
                    </div>
                    ` : ''}

                    ${orderData.items && orderData.items.length > 0 ? `
                    <div class="order-items-section">
                        <h4>Order Items</h4>
                        <table class="aakaari-table">
                            <thead>
                                <tr>
                                    <th>Product</th>
                                    <th>Quantity</th>
                                    <th>Price</th>
                                    <th>Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                ${orderData.items.map(item => `
                                    <tr ${item.is_customized ? 'style="background-color: #f0f7ff;"' : ''}>
                                        <td>
                                            ${item.is_customized ? '<span style="display: inline-block; background: #2271b1; color: white; font-size: 10px; padding: 2px 6px; border-radius: 3px; margin-right: 6px;">CUSTOM</span>' : ''}
                                            <strong>${item.name}</strong>
                                            ${item.meta_display ? `<div style="margin-top: 10px;">${item.meta_display}</div>` : ''}
                                        </td>
                                        <td>${item.quantity}</td>
                                        <td>${item.price}</td>
                                        <td>${item.total}</td>
                                    </tr>
                                `).join('')}
                            </tbody>
                        </table>
                    </div>
                    ` : ''}

                    ${orderData.notes ? `
                    <div class="order-notes-section">
                        <h4>Order Notes</h4>
                        <p>${orderData.notes}</p>
                    </div>
                    ` : ''}
                </div>
            `;

            content.html(html);
            modal.addClass('active');
        }

        function showStatusUpdateModal(orderId) {
            // Create status update modal if it doesn't exist
            let modal = $('#statusUpdateModal');
            if (modal.length === 0) {
                $('body').append(`
                    <div id="statusUpdateModal" class="aakaari-modal">
                        <div class="aakaari-modal-overlay"></div>
                        <div class="aakaari-modal-content" style="max-width: 500px;">
                            <div class="aakaari-modal-header">
                                <h3>Update Order Status</h3>
                                <button class="aakaari-modal-close" id="closeStatusModalBtn">&times;</button>
                            </div>
                            <div class="aakaari-modal-body">
                                <div class="form-group">
                                    <label for="orderStatusSelect">Select New Status:</label>
                                    <select id="orderStatusSelect" class="aakaari-select" style="width: 100%;">
                                        <option value="pending">Pending Payment</option>
                                        <option value="processing">Processing</option>
                                        <option value="on-hold">On Hold</option>
                                        <option value="completed">Completed</option>
                                        <option value="cancelled">Cancelled</option>
                                        <option value="refunded">Refunded</option>
                                        <option value="failed">Failed</option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label for="statusUpdateNote">Note (optional):</label>
                                    <textarea id="statusUpdateNote" class="aakaari-textarea" rows="3" placeholder="Add a note about this status change..."></textarea>
                                </div>
                            </div>
                            <div class="aakaari-modal-footer">
                                <button class="aakaari-button aakaari-button-outline" id="cancelStatusUpdateBtn">Cancel</button>
                                <button class="aakaari-button" id="confirmStatusUpdateBtn">Update Status</button>
                            </div>
                        </div>
                    </div>
                `);
                modal = $('#statusUpdateModal');

                // Close button handlers
                $('#closeStatusModalBtn, #cancelStatusUpdateBtn').on('click', function() {
                    modal.removeClass('active');
                });

                modal.find('.aakaari-modal-overlay').on('click', function() {
                    modal.removeClass('active');
                });
            }

            // Store order ID for later use
            modal.data('orderId', orderId);

            // Show modal
            modal.addClass('active');

            // Confirm button handler
            $('#confirmStatusUpdateBtn').off('click').on('click', function() {
                const newStatus = $('#orderStatusSelect').val();
                const note = $('#statusUpdateNote').val();

                updateOrderStatus(orderId, newStatus, note);
            });
        }

        function updateOrderStatus(orderId, newStatus, note) {
            // Show loading
            showToast('Updating order status...', 'info');

            $.ajax({
                url: typeof aakaari_admin_ajax !== 'undefined' ? aakaari_admin_ajax.ajax_url : '/wp-admin/admin-ajax.php',
                type: 'POST',
                data: {
                    action: 'aakaari_update_order_status',
                    order_id: orderId,
                    status: newStatus,
                    note: note,
                    nonce: typeof aakaari_admin_ajax !== 'undefined' ? aakaari_admin_ajax.nonce : ''
                },
                success: function(response) {
                    if (response.success) {
                        showToast('Order status updated successfully', 'success');
                        $('#statusUpdateModal').removeClass('active');

                        // Reload the page to show updated status
                        setTimeout(() => {
                            location.reload();
                        }, 1500);
                    } else {
                        showToast(response.data?.message || 'Failed to update order status', 'error');
                    }
                },
                error: function() {
                    showToast('Error updating order status', 'error');
                }
            });
        }

        // Start the fetch data process
        fetchData();
    }
})(jQuery);