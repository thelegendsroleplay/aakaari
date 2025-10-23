/*
 * assets/print-studio/print-studio.js
 * - Added persistent side image upload
 */
(function () {
    'use strict';

    document.addEventListener('DOMContentLoaded', () => {
        const APP_ROOT = document.getElementById('custom-print-studio-app');
        if (!APP_ROOT) return;

        // ... Keep your existing appState, wooCommerceAPI, tempState, canvasState ...
         let appState = {
              activeTab: 'products',
              editingProduct: null,
              products: [],
              fabrics: [ /* defaults */ ],
              printTypes: [ /* defaults */ ],
              categories: [],
              wooCommerceColors: []
         };
    const wooCommerceAPI = {
                  loadInitialData: function () {
                console.log("Attempting to load initial data via aakaari_ps_load_data...");
                return jQuery.ajax({
                    url: AakaariPS.ajax_url,
                    type: 'POST',
                    data: {
                        action: 'aakaari_ps_load_data', // Correct PHP action name
                        nonce: AakaariPS.nonce
                    }
                });
                // No .then here, let initWithWooCommerceData handle it
            },

      // Save product to WooCommerce
      saveProduct: function (product) {
        return jQuery.ajax({
          url: AakaariPS.ajax_url,
          type: 'POST',
          data: {
            action: 'print_studio_save_product',
            nonce: AakaariPS.nonce,
            product_data: JSON.stringify(product)
          }
        }).then(function (response) {
          if (response.success && response.data) {
            // Update product with new WooCommerce ID
            product.woocommerceId = response.data.woocommerce_id;
          }
          return response;
        });
      },

      // Update product status
      updateProductStatus: function (productId, isActive) {
        return jQuery.ajax({
          url: AakaariPS.ajax_url,
          type: 'POST',
          data: {
            action: 'print_studio_update_status',
            nonce: AakaariPS.nonce,
            product_id: productId,
            is_active: isActive ? 1 : 0
          }
        });
      },

      // Save category to WooCommerce
      saveCategory: function (category) {
        return jQuery.ajax({
          url: AakaariPS.ajax_url,
          type: 'POST',
          data: {
            action: 'print_studio_save_category',
            nonce: AakaariPS.nonce,
            category_data: JSON.stringify(category)
          }
        });
      },

      // Delete category from WooCommerce
      deleteCategory: function (categoryId) {
        return jQuery.ajax({
          url: AakaariPS.ajax_url,
          type: 'POST',
          data: {
            action: 'print_studio_delete_category',
            nonce: AakaariPS.nonce,
            category_id: categoryId
          }
        });
      }
    };

    // --- NEW: Initialize with WooCommerce data ---
        function initWithWooCommerceData() {
            APP_ROOT.innerHTML = `<div class="ps-card"><h3>Loading Custom Print Studio...</h3><p class="ps-helper">Loading data from WooCommerce...</p></div>`;

            // Call the single data loading function
            wooCommerceAPI.loadInitialData().then(function (response) {
                 // Check response structure carefully
                 console.log("Initial data response received:", response);

                 if (response.success && response.data) {
                     // Populate appState with data received from aakaari_ps_load_data
                     appState.fetchedProducts = response.data.products || []; // Store fetched products
                     appState.categories = response.data.categories || [];
                     appState.wooCommerceColors = response.data.colors || []; // Uses 'colors' key from PHP response

                      // Now merge fetched products into the main products list if needed, or just use fetchedProducts
                      // For simplicity, let's just replace the default products array
                      appState.products = appState.fetchedProducts;


                     // Check if essential data is present
                     if (!appState.categories.length) console.warn("No categories loaded from WooCommerce.");
                     if (!appState.wooCommerceColors.length) console.warn("No colors loaded from WooCommerce.");
                     if (!appState.products.length) console.warn("No print studio products loaded from WooCommerce.");

                     // Render the full app UI
                     renderApp();
                 } else {
                      // Handle AJAX error reported by server (e.g., nonce failure)
                      throw new Error(response.data?.message || 'Failed to load data (server error)');
                 }

            }).catch(function (jqXHR, textStatus, errorThrown) {
                // Handle actual AJAX/network errors or errors thrown above
                 let errorMsg = 'Unknown error';
                 if (jqXHR.responseJSON && jqXHR.responseJSON.data) {
                     errorMsg = jqXHR.responseJSON.data.message || jqXHR.responseJSON.data; // Use server message if available
                 } else if (typeof errorThrown === 'string' && errorThrown) {
                     errorMsg = errorThrown;
                 } else if (jqXHR.statusText) {
                    errorMsg = `Status ${jqXHR.status}: ${jqXHR.statusText}`;
                 } else if (errorThrown instanceof Error) {
                     errorMsg = errorThrown.message; // Use message from thrown Error
                 }
                 console.error("Error during initial data load:", jqXHR, textStatus, errorThrown);

                APP_ROOT.innerHTML = `
                    <div class="ps-card">
                        <h3 style="color: red;">Error Loading Print Studio</h3>
                        <p class="ps-helper">There was an error loading data from WooCommerce. Please try refreshing the page.</p>
                        <p class="ps-helper">Details: ${escapeHtml(errorMsg)}</p>
                        <p class="ps-helper">Check browser console (F12) and PHP error logs for more details.</p>
                    </div>`;
            });
        }

    // --- Temporary / Form state (from original) ---
    let tempState = {
      productForm: {},
      sideForm: {},
      fabricForm: { name: '', description: '', price: 0 },
      printTypeForm: { name: '', description: '', pricingModel: 'per-inch', price: 0.15 },
      categoryForm: { name: '' },
      editingFabricId: null,
      editingPrintTypeId: null,
      editingCategoryId: null,
      editingSideId: null,
    };

    // --- Canvas-specific state (from original) ---
    let canvasState = {
      ctx: null,
      canvas: null,
      loadedImage: null,
      selectedSideIndex: 0,
      toolMode: 'select', // 'select', 'draw-print', 'draw-restriction'
      interactionMode: 'none', // 'none', 'drawing', 'moving', 'resizing'
      selectedType: null, // 'printArea', 'restrictionArea'
      selectedIndex: null, // index in the array
      dragStart: null, // {x, y}
      tempArea: null, // {x, y, width, height} for 'drawing'
      resizeHandle: null, // e.g., 'se', 'nw', 'n'
      hoveredHandle: null,
      HANDLE_SIZE: 8,
      CANVAS_WIDTH: 500,
      CANVAS_HEIGHT: 500,
    };

        // ... Keep your existing utils (generateId, escapeHtml) ...
    function generateId(prefix = 'id') {
      return `${prefix}-${Date.now()}-${Math.random().toString(36).substring(2, 9)}`;
    }
    function escapeHtml(str) {
      if (!str) return '';
      return String(str).replace(/[&<>"']/g, m => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[m]));
    }

        // --- NEW: Function to upload side image ---
        async function uploadSideImage(file) {
            const formData = new FormData();
            formData.append('action', 'aakaari_ps_upload_side_image'); // Matches PHP action
            formData.append('nonce', AakaariPS.nonce);
            formData.append('side_image_file', file);
            // Optionally add product ID if needed for context in PHP:
            // formData.append('product_id', appState.editingProduct?.woocommerceId || 0);

            // Show some indicator maybe?
            console.log("Uploading side image...");

            try {
                // Use jQuery AJAX as it's already a dependency
                const response = await jQuery.ajax({
                    url: AakaariPS.ajax_url,
                    type: 'POST',
                    data: formData,
                    processData: false, // Important for FormData
                    contentType: false, // Important for FormData
                });

                if (response.success && response.data.url) {
                    console.log("Upload successful:", response.data);
                    return response.data; // Should return { url: '...', attachment_id: '...' }
                } else {
                    throw new Error(response.data?.message || 'Upload failed');
                }
            } catch (error) {
                console.error('Side image upload error:', error);
                 // Use your showToast function if available, otherwise alert
                 alert(`Failed to upload side image: ${error.message || 'Unknown error'}`);
                return null; // Indicate failure
            } finally {
                 console.log("Upload attempt finished.");
                 // Hide indicator
            }
        }

        // ... Keep your existing renderApp, renderDashboardView, renderEditorView, etc. ...
    function renderApp() {
      const viewHtml = appState.editingProduct ? renderEditorView(appState.editingProduct) : renderDashboardView();

      APP_ROOT.innerHTML = `
        <div id="ps-modal-overlay" class="dialog-overlay" style="display:none;"></div>
        
        <div class="ps-grid ${appState.editingProduct ? 'ps-grid--editor' : 'ps-grid--dashboard'}">
          <div class="ps-card">
            <div style="display:flex; justify-content:space-between; align-items:center;">
              <div>
                <h2>Custom Print Studio</h2>
                <p class="ps-helper">Create and manage print areas for products.</p>
              </div>
              <div>
                ${appState.editingProduct ? `
                  <button class="ps-btn ps-btn--primary" data-action="save-product-main">Save Product</button>
                  <button class="ps-btn ps-btn--outline" data-action="cancel-editor">Back to Dashboard</button>
                ` : ''}
              </div>
            </div>

            <div style="margin-top:1rem;">
              ${viewHtml}
            </div>
          </div>

          ${appState.editingProduct ? `
            <div class="ps-sidepanel">
              ${renderCanvasSidebar()}
            </div>
          ` : ''}
        </div>
      `;

      // Render icons
      if (window.lucide && typeof window.lucide.createIcons === 'function') {
        try { window.lucide.createIcons(); } catch (e) { /* ignore */ }
      }

      setupListeners(); // attach delegated listeners
    }
    function renderDashboardView() {
      return `
        ${renderTabs()}
        <div class="ps-tab-content">
          ${appState.activeTab === 'products' ? renderProductsTab() : ''}
          ${appState.activeTab === 'fabrics' ? renderFabricsTab() : ''}
          ${appState.activeTab === 'printTypes' ? renderPrintTypesTab() : ''}
          ${appState.activeTab === 'categories' ? renderCategoriesTab() : ''}
        </div>
      `;
    }
    function renderEditorView(product) {
      const side = product.sides[canvasState.selectedSideIndex] || null;
      return `
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom: 1rem;">
          <div>
            <h3>Edit: ${escapeHtml(product.name)}</h3>
            <p class="ps-helper">${escapeHtml(product.description || 'No description.')}</p>
          </div>
          <button class="ps-btn ps-btn--outline" data-action="show-product-modal" data-product-id="${product.id}">Edit Details</button>
        </div>

        <div style="display:grid; gap:.75rem;">
          <div>
            <label class="ps-helper">Sides</label>
            <div class="ps-tabs" style="margin-top: 0.25rem;">
              ${product.sides.map((s, idx) => `
                <button 
                  class="ps-tab-trigger ${canvasState.selectedSideIndex === idx ? 'active' : ''}" 
                  data-action="select-editor-side" 
                  data-side-index="${idx}">
                  ${escapeHtml(s.name)}
                </button>
              `).join('')}
              <button class="ps-btn ps-btn--outline ps-btn--small" data-action="add-side" title="Add new side">
                <i data-lucide="plus" class="ps-icon"></i>
              </button>
            </div>
          </div>

          <div>
            <label class="ps-helper">Canvas</label>
            <div style="border:1px solid hsl(var(--border)); padding:.5rem; border-radius:.375rem; background: hsl(var(--muted));">
              <canvas id="print-area-canvas" width="${canvasState.CANVAS_WIDTH}" height="${canvasState.CANVAS_HEIGHT}" style="cursor: ${getCanvasCursor()};"></canvas>
            </div>
          </div>

          <div>
            <div style="display:flex; gap:.5rem;">
              <button class="ps-btn ${canvasState.toolMode === 'select' ? 'ps-btn--primary' : 'ps-btn--outline'}" data-action="set-canvas-tool" data-tool="select" title="Select Tool">
                <i data-lucide="mouse-pointer" class="ps-icon"></i>
              </button>
              <button class="ps-btn ${canvasState.toolMode === 'draw-print' ? 'ps-btn--primary' : 'ps-btn--outline'}" data-action="set-canvas-tool" data-tool="draw-print" title="Draw Print Area">
                <i data-lucide="square" class="ps-icon"></i>
              </button>
              <button class="ps-btn ${canvasState.toolMode === 'draw-restriction' ? 'ps-btn--primary' : 'ps-btn--outline'}" data-action="set-canvas-tool" data-tool="draw-restriction" title="Draw Restriction Area">
                <i data-lucide="slash" class="ps-icon"></i>
              </button>
            </div>
          </div>
        </div>
      `;
    }
    function renderCanvasSidebar() {
      const side = getCurrentSide();
      const isAreaSelected = canvasState.selectedType && canvasState.selectedIndex !== null;
      let selectedArea = null;
      if (isAreaSelected && side) {
        const areaArray = canvasState.selectedType === 'printArea' ? side.printAreas : side.restrictionAreas;
        selectedArea = areaArray[canvasState.selectedIndex];
      }

      return `
        <div class="ps-card ps-canvas-sidebar">
          <h4>Canvas Tools</h4>
          <p class="ps-helper">Manage sides and print zones.</p>

          <hr class="ps-hr">
          
          ${side ? `
            <div>
              <strong>Side: ${escapeHtml(side.name)}</strong>
              <div class="ps-row" style="margin-top:0.5rem;">
                <button class="ps-btn ps-btn--outline" data-action="edit-side" data-side-id="${side.id}">Edit Side</button>
                <button class="ps-btn ps-btn--destructive" data-action="delete-side" data-side-id="${side.id}">Delete Side</button>
              </div>
            </div>
          ` : '<p class="ps-helper">No side selected. Add a side to begin.</p>'}
          
          <hr class="ps-hr">

          <div>
            <strong>Selected Area</strong>
            ${selectedArea ? `
              <div class="ps-form-column">
                <div class="ps-form-group">
                  <label class="ps-label" for="area-name">Area Name</label>
                  <input type="text" id="area-name" class="ps-input" 
                         value="${escapeHtml(selectedArea.name)}" 
                         data-action="update-area-prop" data-prop="name">
                </div>
                <div class="ps-grid ps-grid--4">
                  <div class="ps-form-group">
                    <label class="ps-label" for="area-x">X</label>
                    <input type="number" id="area-x" class="ps-input" 
                           value="${selectedArea.x}" 
                           data-action="update-area-prop" data-prop="x">
                  </div>
                  <div class="ps-form-group">
                    <label class="ps-label" for="area-y">Y</label>
                    <input type="number" id="area-y" class="ps-input" 
                           value="${selectedArea.y}" 
                           data-action="update-area-prop" data-prop="y">
                  </div>
                  <div class="ps-form-group">
                    <label class="ps-label" for="area-w">W</label>
                    <input type="number" id="area-w" class="ps-input" 
                           value="${selectedArea.width}" 
                           data-action="update-area-prop" data-prop="width">
                  </div>
                  <div class="ps-form-group">
                    <label class="ps-label" for="area-h">H</label>
                    <input type="number" id="area-h" class="ps-input" 
                           value="${selectedArea.height}" 
                           data-action="update-area-prop" data-prop="height">
                  </div>
                </div>
                <div class="ps-row">
                  <button class="ps-btn ps-btn--outline" data-action="duplicate-area">
                    <i data-lucide="copy" class="ps-icon"></i> Duplicate
                  </button>
                  <button class="ps-btn ps-btn--destructive" data-action="delete-area">
                    <i data-lucide="trash-2" class="ps-icon"></i> Delete
                  </button>
                </div>
              </div>
            ` : '<p class="ps-helper">Click an area on the canvas to select it.</p>'}
          </div>
          
          <hr class="ps-hr">
          
          <div>
            <strong>All Areas on This Side</strong>
            <div class="ps-area-list">
              ${(side && (side.printAreas || []).length > 0) ?
        side.printAreas.map((a, idx) => renderAreaListItem(a, idx, 'printArea')).join('') :
        '<p class="ps-helper">No print areas.</p>'}
            </div>
            <div class="ps-area-list">
              ${(side && (side.restrictionAreas || []).length > 0) ?
        side.restrictionAreas.map((a, idx) => renderAreaListItem(a, idx, 'restrictionArea')).join('') :
        '<p class="ps-helper">No restriction areas.</p>'}
            </div>
          </div>
        </div>
      `;
    }
    function renderAreaListItem(area, index, type) {
      const isSelected = canvasState.selectedType === type && canvasState.selectedIndex === index;
      const icon = type === 'printArea' ? 'square' : 'slash';
      return `
        <button 
          class="ps-area-item ${isSelected ? 'active' : ''} ${type === 'restrictionArea' ? 'is-restriction' : ''}" 
          data-action="select-area" 
          data-type="${type}" 
          data-index="${index}">
          <i data-lucide="${icon}" class="ps-icon"></i>
          <span class="ps-area-item__name">${escapeHtml(area.name)}</span>
          <span class="ps-area-item__dims">${area.width}x${area.height}</span>
        </button>
      `;
    }

    // ---------- Dashboard Tab Renderers (from original) ----------
    function renderTabs() {
      const tabs = [
        { id: 'products', label: 'Products', icon: 'package' },
        { id: 'fabrics', label: 'Fabrics', icon: 'layers' },
        { id: 'printTypes', label: 'Print Types', icon: 'printer' },
        { id: 'categories', label: 'Categories', icon: 'tag' }
      ];
      return `
        <div class="ps-tabs">
          ${tabs.map(tab => `
            <button 
              class="ps-tab-trigger ${appState.activeTab === tab.id ? 'active' : ''}" 
              data-action="set-dashboard-tab" 
              data-tab-id="${tab.id}">
              <i data-lucide="${tab.icon}" class="ps-icon"></i>
              <span>${tab.label}</span>
            </button>
          `).join('')}
        </div>
      `;
    }

    function renderProductsTab() {
      return `
        <div class="ps-tab-content-header">
          <h3>Manage Products</h3>
          <button class="ps-btn ps-btn--primary" data-action="add-product">
            <i data-lucide="plus" class="ps-icon"></i> Add Product
          </button>
        </div>
        <table class="ps-table">
          <thead>
            <tr><th>Product</th><th>Category</th><th>Price</th><th>Sides</th><th>Status</th><th>Actions</th></tr>
          </thead>
          <tbody>
            ${appState.products.length === 0 ? `
              <tr><td colspan="6" class="ps-table-empty">No products found.</td></tr>
            ` : appState.products.map(p => `
              <tr>
                <td>${escapeHtml(p.name)}</td>
                <td><span class="ps-badge">${escapeHtml(p.category)}</span></td>
                <td>$${(p.basePrice || 0).toFixed(2)}</td>
                <td>${(p.sides || []).length}</td>
                <td>
                  <label class="ps-switch">
                    <input type="checkbox" ${p.isActive ? 'checked' : ''} 
                           data-action="toggle-product-active" data-product-id="${p.id}">
                    <span class="ps-slider"></span>
                  </label>
                </td>
                <td>
                  <button class="ps-btn ps-btn--outline ps-btn--small" data-action="edit-product" data-product-id="${p.id}">
                    <i data-lucide="edit-3" class="ps-icon"></i> Edit
                  </button>
                </td>
              </tr>
            `).join('')}
          </tbody>
        </table>
      `;
    }

    function renderFabricsTab() {
      return `
        <div class="ps-tab-content-header">
          <h3>Manage Fabrics</h3>
          <button class="ps-btn ps-btn--primary" data-action="show-fabric-modal">
            <i data-lucide="plus" class="ps-icon"></i> Add Fabric
          </button>
        </div>
        <table class="ps-table">
          <thead>
            <tr><th>Name</th><th>Description</th><th>Price Adj.</th><th>Actions</th></tr>
          </thead>
          <tbody>
            ${appState.fabrics.length === 0 ? `
              <tr><td colspan="4" class="ps-table-empty">No fabrics found.</td></tr>
            ` : appState.fabrics.map(f => `
              <tr>
                <td>${escapeHtml(f.name)}</td>
                <td>${escapeHtml(f.description)}</td>
                <td>${f.price > 0 ? `+$${f.price.toFixed(2)}` : '—'}</td>
                <td>
                  <button class="ps-btn ps-btn--outline ps-btn--small" data-action="show-fabric-modal" data-fabric-id="${f.id}">
                    <i data-lucide="edit-3" class="ps-icon"></i> Edit
                  </button>
                  <button class="ps-btn ps-btn--destructive ps-btn--small" data-action="delete-fabric" data-fabric-id="${f.id}">
                    <i data-lucide="trash-2" class="ps-icon"></i>
                  </button>
                </td>
              </tr>
            `).join('')}
          </tbody>
        </table>
      `;
    }

    function renderPrintTypesTab() {
      return `
        <div class="ps-tab-content-header">
          <h3>Manage Print Types</h3>
          <button class="ps-btn ps-btn--primary" data-action="show-print-type-modal">
            <i data-lucide="plus" class="ps-icon"></i> Add Print Type
          </button>
        </div>
        <table class="ps-table">
          <thead>
            <tr><th>Name</th><th>Description</th><th>Pricing</th><th>Actions</th></tr>
          </thead>
          <tbody>
            ${appState.printTypes.length === 0 ? `
              <tr><td colspan="4" class="ps-table-empty">No print types found.</td></tr>
            ` : appState.printTypes.map(pt => `
              <tr>
                <td>${escapeHtml(pt.name)}</td>
                <td>${escapeHtml(pt.description)}</td>
                <td>
                  $${pt.price.toFixed(2)} 
                  <span class="ps-badge">${escapeHtml(pt.pricingModel)}</span>
                </td>
                <td>
                  <button class="ps-btn ps-btn--outline ps-btn--small" data-action="show-print-type-modal" data-print-type-id="${pt.id}">
                    <i data-lucide="edit-3" class="ps-icon"></i> Edit
                  </button>
                  <button class="ps-btn ps-btn--destructive ps-btn--small" data-action="delete-print-type" data-print-type-id="${pt.id}">
                    <i data-lucide="trash-2" class="ps-icon"></i>
                  </button>
                </td>
              </tr>
            `).join('')}
          </tbody>
        </table>
      `;
    }

    function renderCategoriesTab() {
      return `
        <div class="ps-tab-content-header">
          <h3>Manage Categories</h3>
          <button class="ps-btn ps-btn--primary" data-action="show-category-modal">
            <i data-lucide="plus" class="ps-icon"></i> Add Category
          </button>
        </div>
        <table class="ps-table">
          <thead>
            <tr><th>Name</th><th>Actions</th></tr>
          </thead>
          <tbody>
            ${appState.categories.length === 0 ? `
              <tr><td colspan="2" class="ps-table-empty">No categories found.</td></tr>
            ` : appState.categories.map(c => `
              <tr>
                <td>${escapeHtml(c.name)}</td>
                <td>
                  <button class="ps-btn ps-btn--outline ps-btn--small" data-action="show-category-modal" data-category-id="${c.id}">
                    <i data-lucide="edit-3" class="ps-icon"></i> Edit
                  </button>
                  <button class="ps-btn ps-btn--destructive ps-btn--small" data-action="delete-category" data-category-id="${c.id}">
                    <i data-lucide="trash-2" class="ps-icon"></i>
                  </button>
                </td>
              </tr>
            `).join('')}
          </tbody>
        </table>
      `;
    }

        // ... (all other rendering functions) ...
    function renderModal(title, content, footer) {
      return `
        <div class="dialog-content" role="dialog" aria-modal="true" aria-labelledby="dialog-title">
          <div class="ps-modal-header">
            <h3 id="dialog-title">${title}</h3>
            <button class="ps-btn ps-btn--icon" data-action="close-modal">
              <i data-lucide="x" class="ps-icon"></i>
            </button>
          </div>
          <div class="ps-modal-body">
            ${content}
          </div>
          <div class="ps-modal-footer">
            ${footer}
          </div>
        </div>
      `;
    }
             function renderSideModal() {
      const isEditing = !!tempState.editingSideId;
      const form = tempState.sideForm;
      const title = isEditing ? 'Edit Side' : 'Add New Side';
      const content = `
        <div class="ps-form-column">
          <div class="ps-form-group">
            <label class="ps-label" for="side-name">Side Name</label>
            <input type="text" id="side-name" class="ps-input" 
                   value="${escapeHtml(form.name)}" data-form="sideForm" data-prop="name">
          </div>
          <div class="ps-form-group">
            <label class="ps-label" for="side-imageUpload">Upload Template Image</label>
            <input type="file" id="side-imageUpload" class="ps-input ps-input--file" 
                   accept="image/png, image/jpeg, image/jpg">
            <p class="ps-helper">
              Upload a PNG or JPG. Recommended 500x500.<br>
              <span id="current-image-name" style="font-weight: 500;">
                ${form.imageUrl ? 'Current image is set.' : 'No image set.'}
              </span>
            </p>
          </div>
        </div>
      `;
      const footer = `
        <button class="ps-btn ps-btn--outline" data-action="close-modal">Cancel</button>
        <button class="ps-btn ps-btn--primary" data-action="save-side">
          ${isEditing ? 'Save Changes' : 'Create Side'}
        </button>
      `;
      return renderModal(title, content, footer);
    }

        // ... Keep your existing canvas logic (initCanvasEditor, drawCanvas, handlers) ...
function initCanvasEditor() {
      const canvas = document.getElementById('print-area-canvas');
      if (!canvas) return;
      
      // Clear old listeners if any
      if (canvasState.canvas) {
          canvasState.canvas.removeEventListener('mousedown', handleCanvasMouseDown);
          canvasState.canvas.removeEventListener('mousemove', handleCanvasMouseMove);
          canvasState.canvas.removeEventListener('mouseup', handleCanvasMouseUp);
          canvasState.canvas.removeEventListener('mouseleave', handleCanvasMouseLeave);
      }

      canvasState.canvas = canvas;
      canvasState.ctx = canvas.getContext('2d');
      canvasState.CANVAS_WIDTH = canvas.width;
      canvasState.CANVAS_HEIGHT = canvas.height;
      canvasState.loadedImage = null; // Force reload
      
      canvas.addEventListener('mousedown', handleCanvasMouseDown);
      canvas.addEventListener('mousemove', handleCanvasMouseMove);
      canvas.addEventListener('mouseup', handleCanvasMouseUp);
      canvas.addEventListener('mouseleave', handleCanvasMouseLeave);
      
      drawCanvas();
    }
    function drawCanvas() {
      const { canvas, ctx, CANVAS_WIDTH, CANVAS_HEIGHT } = canvasState;
      if (!canvas || !ctx) return;

      // clear
      ctx.clearRect(0, 0, CANVAS_WIDTH, CANVAS_HEIGHT);
      ctx.fillStyle = '#f8fafc'; // muted background
      ctx.fillRect(0, 0, CANVAS_WIDTH, CANVAS_HEIGHT);

      const side = getCurrentSide();

      // Draw background image if present
      const drawAllAreas = () => {
        if (!side) return;

        // Draw restriction areas first (bottom layer)
        (side.restrictionAreas || []).forEach((a, idx) => {
          const isSelected = canvasState.selectedType === 'restrictionArea' && canvasState.selectedIndex === idx;
          drawArea(ctx, a, 'restriction', isSelected);
        });

        // Draw print areas (top layer)
        (side.printAreas || []).forEach((a, idx) => {
          const isSelected = canvasState.selectedType === 'printArea' && canvasState.selectedIndex === idx;
          drawArea(ctx, a, 'print', isSelected);
        });

        // Draw selection handles on top
        if (canvasState.selectedType && canvasState.selectedIndex !== null) {
          const area = canvasState.selectedType === 'printArea' ? 
                       side.printAreas[canvasState.selectedIndex] : 
                       side.restrictionAreas[canvasState.selectedIndex];
          if (area) drawResizeHandles(ctx, area);
        }

        // Draw temp drawing area
        if (canvasState.tempArea) {
          const isRestriction = canvasState.toolMode === 'draw-restriction';
          ctx.save();
          ctx.strokeStyle = isRestriction ? 'rgba(239,68,68,0.9)' : 'rgba(34,197,94,0.9)';
          ctx.fillStyle = isRestriction ? 'rgba(239,68,68,0.1)' : 'rgba(34,197,94,0.1)';
          ctx.lineWidth = 2;
          ctx.setLineDash([6, 4]);
          ctx.strokeRect(canvasState.tempArea.x, canvasState.tempArea.y, canvasState.tempArea.width, canvasState.tempArea.height);
          ctx.fillRect(canvasState.tempArea.x, canvasState.tempArea.y, canvasState.tempArea.width, canvasState.tempArea.height);
          ctx.restore();
        }
      };

      if (side && side.imageUrl) {
        if (!canvasState.loadedImage || canvasState.loadedImage.src !== side.imageUrl) {
          const img = new Image();
          img.crossOrigin = 'anonymous';
          img.onload = () => {
            canvasState.loadedImage = img;
            ctx.drawImage(img, 0, 0, CANVAS_WIDTH, CANVAS_HEIGHT);
            drawAllAreas();
          };
          img.onerror = () => {
            console.error("Failed to load image:", side.imageUrl);
            // If it's a blob URL that's now invalid, it might fail.
            // We'll just draw the areas without it.
            canvasState.loadedImage = null; // clear bad image
            drawAllAreas(); // draw areas on blank bg
          };
          img.src = side.imageUrl;
        } else {
          ctx.drawImage(canvasState.loadedImage, 0, 0, CANVAS_WIDTH, CANVAS_HEIGHT);
          drawAllAreas();
        }
      } else {
        drawAllAreas(); // No image, just draw areas
      }
    }

// Add this function inside the DOMContentLoaded handler in print-studio.js
    function getCanvasCursor() {
        if (canvasState.interactionMode === 'drawing') return 'crosshair';
        if (canvasState.interactionMode === 'moving') return 'move';
        if (canvasState.interactionMode === 'resizing') {
            const cursors = {
                'tl': 'nwse-resize', 'tm': 'ns-resize', 'tr': 'nesw-resize',
                'ml': 'ew-resize', 'mr': 'ew-resize',
                'bl': 'nesw-resize', 'bm': 'ns-resize', 'br': 'nwse-resize'
            };
            return cursors[canvasState.resizeHandle] || 'default';
        }
        if (canvasState.toolMode !== 'select') return 'crosshair';
        if (canvasState.hoveredHandle) {
            const cursors = {
                'tl': 'nwse-resize', 'tm': 'ns-resize', 'tr': 'nesw-resize',
                'ml': 'ew-resize', 'mr': 'ew-resize',
                'bl': 'nesw-resize', 'bm': 'ns-resize', 'br': 'nwse-resize'
            };
            return cursors[canvasState.hoveredHandle] || 'default';
        }
        return 'default';
    }
    function handleCanvasMouseDown(e) {
        const pos = getCanvasCoordinates(e);
        const side = getCurrentSide();
        if (!side) return;

        if (canvasState.toolMode === 'draw-print' || canvasState.toolMode === 'draw-restriction') {
            canvasState.interactionMode = 'drawing';
            canvasState.dragStart = pos;
            canvasState.tempArea = { id: 'tmp-' + Date.now(), x: pos.x, y: pos.y, width: 1, height: 1 };
        } else if (canvasState.toolMode === 'select') {
            // Check resize handles first
            if (canvasState.selectedType && canvasState.selectedIndex !== null) {
                const areaArray = canvasState.selectedType === 'printArea' ? side.printAreas : side.restrictionAreas;
                const area = areaArray[canvasState.selectedIndex];
                const handle = getHandleAtPosition(pos, area);
                if (handle) {
                    canvasState.interactionMode = 'resizing';
                    canvasState.resizeHandle = handle;
                    canvasState.dragStart = pos;
                    // Store original area for resizing logic
                    canvasState.tempArea = { ...area }; 
                    return;
                }
            }
            
            // Check for move (check print areas first, LIFO)
            let newSelection = null;
            for (let i = (side.printAreas || []).length - 1; i >= 0; i--) {
                if (isInsideArea(pos, side.printAreas[i])) {
                    newSelection = { type: 'printArea', index: i };
                    break;
                }
            }
            // Check restriction areas if no print area found
            if (!newSelection) {
                 for (let i = (side.restrictionAreas || []).length - 1; i >= 0; i--) {
                    if (isInsideArea(pos, side.restrictionAreas[i])) {
                        newSelection = { type: 'restrictionArea', index: i };
                        break;
                    }
                }
            }
            
            if (newSelection) {
                canvasState.interactionMode = 'moving';
                canvasState.selectedType = newSelection.type;
                canvasState.selectedIndex = newSelection.index;
                const area = newSelection.type === 'printArea' ? side.printAreas[newSelection.index] : side.restrictionAreas[newSelection.index];
                canvasState.dragStart = { x: pos.x - area.x, y: pos.y - area.y };
            } else {
                // Clicked on empty space
                canvasState.selectedType = null;
                canvasState.selectedIndex = null;
            }
            
            renderCanvasSidebarAndIcons();
            drawCanvas();
        }
    }
    
    function handleCanvasMouseMove(e) {
        const pos = getCanvasCoordinates(e);
        const side = getCurrentSide();
        if (!side) return;

        if (canvasState.interactionMode === 'drawing' && canvasState.tempArea) {
            canvasState.tempArea.width = Math.abs(pos.x - canvasState.dragStart.x);
            canvasState.tempArea.height = Math.abs(pos.y - canvasState.dragStart.y);
            canvasState.tempArea.x = Math.min(pos.x, canvasState.dragStart.x);
            canvasState.tempArea.y = Math.min(pos.y, canvasState.dragStart.y);
            drawCanvas();
        } else if (canvasState.interactionMode === 'moving') {
            const areaArray = canvasState.selectedType === 'printArea' ? side.printAreas : side.restrictionAreas;
            const area = areaArray[canvasState.selectedIndex];
            area.x = pos.x - canvasState.dragStart.x;
            area.y = pos.y - canvasState.dragStart.y;
            // Add bounds check
            area.x = Math.max(0, Math.min(area.x, canvasState.CANVAS_WIDTH - area.width));
            area.y = Math.max(0, Math.min(area.y, canvasState.CANVAS_HEIGHT - area.height));
            
            drawCanvas();
            updateSidebarInputs(area);
        } else if (canvasState.interactionMode === 'resizing') {
            const areaArray = canvasState.selectedType === 'printArea' ? side.printAreas : side.restrictionAreas;
            const area = areaArray[canvasState.selectedIndex];
            const origArea = canvasState.tempArea;
            
            let { x, y, width, height } = origArea;
            
            // Calculate new dimensions based on mouse position relative to original drag start
            const dx = pos.x - canvasState.dragStart.x;
            const dy = pos.y - canvasState.dragStart.y;
            
            switch (canvasState.resizeHandle) {
                case 'tl':
                    width = origArea.width - dx;
                    height = origArea.height - dy;
                    x = origArea.x + dx;
                    y = origArea.y + dy;
                    break;
                case 'tm':
                    height = origArea.height - dy;
                    y = origArea.y + dy;
                    break;
                case 'tr':
                    width = origArea.width + dx;
                    height = origArea.height - dy;
                    y = origArea.y + dy;
                    break;
                case 'ml':
                    width = origArea.width - dx;
                    x = origArea.x + dx;
                    break;
                case 'mr':
                    width = origArea.width + dx;
                    break;
                case 'bl':
                    width = origArea.width - dx;
                    height = origArea.height + dy;
                    x = origArea.x + dx;
                    break;
                case 'bm':
                    height = origArea.height + dy;
                    break;
                case 'br':
                    width = origArea.width + dx;
                    height = origArea.height + dy;
                    break;
            }

            // Enforce minimum size and update
            if (width < 20) {
                if (canvasState.resizeHandle.includes('l')) {
                    x = origArea.x + origArea.width - 20;
                }
                width = 20;
            }
            if (height < 20) {
                if (canvasState.resizeHandle.includes('t')) {
                    y = origArea.y + origArea.height - 20;
                }
                height = 20;
            }
            
            // Apply bounds check
            if (x < 0) { width += x; x = 0; }
            if (y < 0) { height += y; y = 0; }
            if (x + width > canvasState.CANVAS_WIDTH) { width = canvasState.CANVAS_WIDTH - x; }
            if (y + height > canvasState.CANVAS_HEIGHT) { height = canvasState.CANVAS_HEIGHT - y; }

            area.x = Math.round(x);
            area.y = Math.round(y);
            area.width = Math.round(width);
            area.height = Math.round(height);
            
            drawCanvas();
            updateSidebarInputs(area);
        } else if (canvasState.toolMode === 'select') {
            // Hover logic
            let handleFound = null;
            if (canvasState.selectedType && canvasState.selectedIndex !== null) {
                 const areaArray = canvasState.selectedType === 'printArea' ? side.printAreas : side.restrictionAreas;
                 const area = areaArray[canvasState.selectedIndex];
                 handleFound = getHandleAtPosition(pos, area);
            }
            if (handleFound !== canvasState.hoveredHandle) {
                canvasState.hoveredHandle = handleFound;
                canvasState.canvas.style.cursor = getCanvasCursor();
            }
        }
    }
    
    function handleCanvasMouseUp(e) {
        if (canvasState.interactionMode === 'drawing' && canvasState.tempArea) {
            if (canvasState.tempArea.width > 10 && canvasState.tempArea.height > 10) {
                const side = getCurrentSide();
                if (side) {
                    const area = { ...canvasState.tempArea, id: generateId('area') };
                    if (canvasState.toolMode === 'draw-print') {
                        area.name = 'Print Area ' + ((side.printAreas || []).length + 1);
                        side.printAreas = side.printAreas || [];
                        side.printAreas.push(area);
                        canvasState.selectedType = 'printArea';
                        canvasState.selectedIndex = side.printAreas.length - 1;
                    } else if (canvasState.toolMode === 'draw-restriction') {
                        area.name = 'Zone ' + ((side.restrictionAreas || []).length + 1);
                        side.restrictionAreas = side.restrictionAreas || [];
                        side.restrictionAreas.push(area);
                        canvasState.selectedType = 'restrictionArea';
                        canvasState.selectedIndex = side.restrictionAreas.length - 1;
                    }
                }
                canvasState.toolMode = 'select'; // Switch back to select
                renderApp(); // Full re-render to update sidebar and tool buttons
            }
        } else if (canvasState.interactionMode === 'resizing') {
            // On mouse up from resize, reset tempArea
            canvasState.tempArea = null;
        }

        canvasState.interactionMode = 'none';
        canvasState.dragStart = null;
        canvasState.tempArea = null;
        canvasState.resizeHandle = null;
        canvasState.canvas.style.cursor = getCanvasCursor();
        drawCanvas();
    }
    
    function handleCanvasMouseLeave(e) {
        if (canvasState.interactionMode === 'drawing' || 
            canvasState.interactionMode === 'moving' || 
            canvasState.interactionMode === 'resizing') {
            handleCanvasMouseUp(e); // Commit changes if mouse leaves while interacting
        }
        if (canvasState.hoveredHandle) {
            canvasState.hoveredHandle = null;
            canvasState.canvas.style.cursor = getCanvasCursor();
        }
    }
    function updateSidebarInputs(area) {
        const nameInput = document.getElementById('area-name');
        const xInput = document.getElementById('area-x');
        const yInput = document.getElementById('area-y');
        const wInput = document.getElementById('area-w');
        const hInput = document.getElementById('area-h');
        
        if (nameInput && document.activeElement !== nameInput) nameInput.value = area.name;
        if (xInput && document.activeElement !== xInput) xInput.value = area.x;
        if (yInput && document.activeElement !== yInput) yInput.value = area.y;
        if (wInput && document.activeElement !== wInput) wInput.value = area.width;
        if (hInput && document.activeElement !== hInput) hInput.value = area.height;
    }
    
    function renderCanvasSidebarAndIcons() {
        const panel = APP_ROOT.querySelector('.ps-sidepanel');
        if (panel) {
            panel.innerHTML = renderCanvasSidebar();
            if (window.lucide && typeof window.lucide.createIcons === 'function') {
                try { window.lucide.createIcons(); } catch (e) { /* ignore */ }
            }
        }
    }

        // ... Keep existing setupListeners, delegatedInput, delegatedClick (mostly) ...
    function setupListeners() {
      // Use event delegation on APP_ROOT
      APP_ROOT.removeEventListener('click', delegatedClick);
      APP_ROOT.addEventListener('click', delegatedClick);
      
      APP_ROOT.removeEventListener('input', delegatedInput);
      APP_ROOT.addEventListener('input', delegatedInput);
      
      APP_ROOT.removeEventListener('change', delegatedChange);
      APP_ROOT.addEventListener('change', delegatedChange);

      // init canvas (if any)
      if (appState.editingProduct) {
        initCanvasEditor();
      }
    }
         function delegatedClick(e) {
             const target = e.target.closest('[data-action]');
             if (!target) return;
             const action = target.getAttribute('data-action');
             const data = target.dataset;

              // --- Trigger handleSaveSide ---
             if (action === 'save-side') handleSaveSide(); // Make sure this is called

             // ... (handle all other actions) ...
              if (action === 'set-dashboard-tab') { /* ... */ }
              if (action === 'add-product') handleAddNewProduct();
                    if (action === 'edit-product') handleEditProduct(data.productId);
      
      // Modal Triggers
      if (action === 'show-fabric-modal') handleShowFabricModal(data.fabricId);
      if (action === 'show-print-type-modal') handleShowPrintTypeModal(data.printTypeId);
      if (action === 'show-category-modal') handleShowCategoryModal(data.categoryId);
      if (action === 'show-product-modal') handleShowProductModal();
      
      // Modal Actions
      if (action === 'close-modal') closeModal();
      if (action === 'save-fabric') handleSaveFabric();
      if (action === 'save-print-type') handleSavePrintType();
      if (action === 'save-category') handleSaveCategory();
      if (action === 'save-product-details') handleSaveProductDetails();
      if (action === 'save-side') handleSaveSide();

      // Delete Actions
      if (action === 'delete-fabric') handleDelete('fabric', data.fabricId);
      if (action === 'delete-print-type') handleDelete('printType', data.printTypeId);
      if (action === 'delete-category') handleDelete('category', data.categoryId);
      if (action === 'delete-side') handleDeleteSide(data.sideId);
      
      // Editor Actions
      if (action === 'cancel-editor') {
        appState.editingProduct = null;
        resetCanvasState();
        renderApp();
      }
      if (action === 'save-product-main') handleSaveProductMain();
      if (action === 'select-editor-side') {
        const idx = parseInt(data.sideIndex || '0', 10);
        canvasState.selectedSideIndex = idx;
        resetCanvasState(false); // keep tool mode
        renderApp(); // Re-renders editor and sidebar
      }
      if (action === 'add-side') handleAddSide();
      if (action === 'edit-side') handleEditSide(data.sideId);
      
      // Canvas Sidebar Actions
      if (action === 'set-canvas-tool') {
        canvasState.toolMode = data.tool;
        // only re-render editor buttons, not whole app
        const editorView = APP_ROOT.querySelector('.ps-card > div[style="margin-top:1rem;"]');
        if (editorView) {
            editorView.innerHTML = renderEditorView(appState.editingProduct);
             if (window.lucide && typeof window.lucide.createIcons === 'function') {
                try { window.lucide.createIcons(); } catch (e) { /* ignore */ }
            }
            initCanvasEditor(); // re-init canvas listeners
        }
      }
      if (action === 'select-area') {
        canvasState.selectedType = data.type;
        canvasState.selectedIndex = parseInt(data.index, 10);
        renderCanvasSidebarAndIcons();
        drawCanvas();
      }
      if (action === 'duplicate-area') handleDuplicateArea();
      if (action === 'delete-area') handleDeleteArea();
    
         }
    function delegatedInput(e) {
      const target = e.target.closest('[data-form], [data-action]');
      if (!target) return;

      const data = target.dataset;
      const action = target.getAttribute('data-action');

      // Modal Form Inputs
      if (data.form && data.prop) {
        const formName = data.form; // 'fabricForm', 'productForm', etc.
        const propName = data.prop;
        
        if (!tempState[formName]) return;
        
        let value = target.value;
        if (target.type === 'number') value = parseFloat(value) || 0;
        if (target.type === 'checkbox') {
          // Handle checkbox group (like colors, printTypes)
          const propArray = tempState[formName][propName] || [];
          if (target.checked) {
            if (!propArray.includes(target.value)) propArray.push(target.value);
          } else {
            const index = propArray.indexOf(target.value);
            if (index > -1) propArray.splice(index, 1);
          }
          tempState[formName][propName] = propArray;
        } else if (target.type === 'radio') {
            if (target.checked) tempState[formName][propName] = value;
        } else {
          // Handle text, textarea, select-one
          if (propName === 'salePrice' && value === '') value = null;
          tempState[formName][propName] = value;
        }
      }
      
      // Canvas Sidebar Inputs
      if (action === 'update-area-prop') {
        const side = getCurrentSide();
        if (!side || canvasState.selectedType === null || canvasState.selectedIndex === null) return;
        
        const areaArray = canvasState.selectedType === 'printArea' ? side.printAreas : side.restrictionAreas;
        const area = areaArray[canvasState.selectedIndex];
        if (!area) return;
        
        const prop = data.prop;
        let value = target.value;
        
        if (prop === 'name') {
          area.name = value;
        } else {
          area[prop] = parseInt(value, 10) || 0;
        }
        
        drawCanvas();
      }
    }

        // --- UPDATE delegatedChange ---
        function delegatedChange(e) {
            const target = e.target;
            const data = target.dataset;
            const action = target.getAttribute('data-action');

            // --- Product active toggle switch ---
            if (action === 'toggle-product-active') {
                // ... (keep existing logic) ...
                 const prod = appState.products.find(p => p.id === data.productId);
                if (prod && prod.woocommerceId) {
                  prod.isActive = target.checked;
                  wooCommerceAPI.updateProductStatus(prod.woocommerceId, prod.isActive).catch(/* ... error handling ... */);
                }
            }

            // --- Modal Form Inputs (for selects) ---
            if (data.form && data.prop && target.tagName === 'SELECT') {
                 tempState[data.form][data.prop] = target.value;
            }

            // --- Handle side image file selection (Preview Only) ---
            if (target.id === 'side-imageUpload' && target.files && target.files[0]) {
                const file = target.files[0];
                 const allowedTypes = ['image/png', 'image/jpeg', 'image/gif', 'image/webp'];
                 if (!allowedTypes.includes(file.type)) {
                     alert('Invalid file type. Please select a PNG, JPG, GIF, or WEBP file.');
                     target.value = ''; // Clear selection
                     return;
                 }

                // Revoke old blob URL if it exists
                if (tempState.sideForm.imageUrl && tempState.sideForm.imageUrl.startsWith('blob:')) {
                    URL.revokeObjectURL(tempState.sideForm.imageUrl);
                }

                // Create a local blob URL for preview
                const localUrl = URL.createObjectURL(file);
                tempState.sideForm.imageUrl = localUrl; // Store blob URL for preview

                // Update the helper text
                const helperText = document.getElementById('current-image-name');
                if (helperText) {
                    helperText.textContent = `Preview: ${file.name}`;
                }
                 // Store the actual File object separately for upload later
                 tempState.sideForm._newFile = file; // Use a temporary property
            }
        }


        // ... Keep existing resetCanvasState ...
 function resetCanvasState(fullReset = true) {
        if(fullReset) canvasState.toolMode = 'select';
        canvasState.interactionMode = 'none';
        canvasState.selectedType = null;
        canvasState.selectedIndex = null;
        canvasState.dragStart = null;
        canvasState.tempArea = null;
        canvasState.resizeHandle = null;
        canvasState.hoveredHandle = null;
        canvasState.loadedImage = null;
    }


        // ... Keep existing Dashboard Handlers (handleAddNewProduct, handleEditProduct, etc.) ...
    function handleAddNewProduct() {
      const newProd = { 
        id: generateId('prod'), 
        name: 'New Product', 
        description: '', 
        basePrice: 0, 
        salePrice: null, 
        category: appState.categories[0]?.name || '', 
        woocommerceId: '', 
        isActive: false, 
        colors: [], 
        availablePrintTypes: [], 
        sides: [] 
      };
      // Add to main state *first*
      appState.products.push(newProd);
      // Now edit a copy
      appState.editingProduct = JSON.parse(JSON.stringify(newProd));
      canvasState.selectedSideIndex = 0;
      resetCanvasState();
      renderApp();
    }
    function handleEditProduct(productId) {
      const p = appState.products.find(x => x.id === productId);
      if (p) {
        // deep copy for editing
        appState.editingProduct = JSON.parse(JSON.stringify(p));
        canvasState.selectedSideIndex = 0;
        resetCanvasState();
        renderApp();
      }
    }
    function handleShowFabricModal(fabricId = null) {
      if (fabricId) {
        const fabric = appState.fabrics.find(f => f.id === fabricId);
        tempState.fabricForm = JSON.parse(JSON.stringify(fabric));
        tempState.editingFabricId = fabricId;
      } else {
        tempState.fabricForm = { name: '', description: '', price: 0 };
        tempState.editingFabricId = null;
      }
      showModal(renderFabricModal());
    }
    
    function handleSaveFabric() {
      const form = tempState.fabricForm;
      if (!form.name) return alert('Fabric name is required.');
      
      if (tempState.editingFabricId) {
        // Update
        const index = appState.fabrics.findIndex(f => f.id === tempState.editingFabricId);
        if (index > -1) appState.fabrics[index] = { ...form };
      } else {
        // Create
        appState.fabrics.push({ ...form, id: generateId('fab') });
      }
      closeModal();
      renderApp();
    }
    
    function handleShowPrintTypeModal(printTypeId = null) {
      if (printTypeId) {
        const pt = appState.printTypes.find(p => p.id === printTypeId);
        tempState.printTypeForm = JSON.parse(JSON.stringify(pt));
        tempState.editingPrintTypeId = printTypeId;
      } else {
        tempState.printTypeForm = { name: '', description: '', pricingModel: 'per-inch', price: 0.15 };
        tempState.editingPrintTypeId = null;
      }
      showModal(renderPrintTypeModal());
    }
    
    function handleSavePrintType() {
      const form = tempState.printTypeForm;
      if (!form.name) return alert('Print type name is required.');
      
      if (tempState.editingPrintTypeId) {
        const index = appState.printTypes.findIndex(p => p.id === tempState.editingPrintTypeId);
        if (index > -1) appState.printTypes[index] = { ...form };
      } else {
        appState.printTypes.push({ ...form, id: generateId('print') });
      }
      closeModal();
      renderApp();
    }
    
    function handleShowCategoryModal(categoryId = null) {
      if (categoryId) {
        const cat = appState.categories.find(c => c.id === categoryId);
        tempState.categoryForm = JSON.parse(JSON.stringify(cat));
        tempState.editingCategoryId = categoryId;
      } else {
        tempState.categoryForm = { name: '' };
        tempState.editingCategoryId = null;
      }
      showModal(renderCategoryModal());
    }
    
    // --- REPLACED (with WooCommerce update) ---
    function handleSaveCategory() {
      const form = tempState.categoryForm;
      if (!form.name) return alert('Category name is required.');
      
      // Save to WooCommerce
      wooCommerceAPI.saveCategory(form).then(function(response) {
        if (response.success && response.data) {
          if (tempState.editingCategoryId) {
            const index = appState.categories.findIndex(c => c.id === tempState.editingCategoryId);
            if (index > -1) {
              appState.categories[index] = response.data;
            }
          } else {
            appState.categories.push(response.data);
          }
          closeModal();
          renderApp();
        } else {
          alert('Error saving category: ' + (response.data || 'Unknown error'));
        }
      }).catch(function(error) {
        alert('Error saving category: ' + error.message);
      });
    }
    
    // --- REPLACED (with WooCommerce update) ---
    function handleDelete(type, id) {
      const typeMap = {
        'fabric': { stateKey: 'fabrics', name: 'Fabric' },
        'printType': { stateKey: 'printTypes', name: 'Print Type' },
        'category': { stateKey: 'categories', name: 'Category' },
      };
      const config = typeMap[type];
      if (!config) return;

      if (confirm(`Are you sure you want to delete this ${config.name}?`)) {
        if (type === 'category') {
          // Delete from WooCommerce
          wooCommerceAPI.deleteCategory(id).then(function(response) {
            if (response.success) {
              appState.categories = appState.categories.filter(item => item.id !== id);
              renderApp();
            } else {
              alert('Error deleting category: ' + (response.data || 'Unknown error'));
            }
          }).catch(function(error) {
            alert('Error deleting category: ' + error.message);
          });
        } else {
          // Local deletion for non-WooCommerce items
          appState[config.stateKey] = appState[config.stateKey].filter(item => item.id !== id);
          renderApp();
        }
      }
    }

    // ---------- Handlers (Editor) (from original) ----------
    function handleShowProductModal() {
      // Load editing product data into the form
      tempState.productForm = JSON.parse(JSON.stringify(appState.editingProduct));
      showModal(renderProductModal());
      // Re-run lucide for modal icons
      if (window.lucide && typeof window.lucide.createIcons === 'function') {
        try { window.lucide.createIcons(); } catch (e) { /* ignore */ }
      }
    }
    
    function handleSaveProductDetails() {
      // Save from modal (tempState.productForm) to the main editor state (appState.editingProduct)
      const form = tempState.productForm;
      if (!form.name) return alert('Product name is required.');
      
      // Update all properties *except* sides
      const { sides, ...details } = form;
      appState.editingProduct = { ...appState.editingProduct, ...details };
      
      closeModal();
      renderApp(); // Re-render editor to show new name, etc.
    }

        // --- UPDATE handleSaveProductMain ---
        function handleSaveProductMain() {
            const product = appState.editingProduct;
             if (!product) return; // Should not happen if button is visible
            if (!product.name) return alert('Product name is required.');
            if (!product.sides || product.sides.length === 0) return alert('Product must have at least one side.');

            // **Crucial**: Ensure side imageURLs are persistent before saving product
            // This is handled within handleSaveSide now which should be called *before* this.
            // Let's re-verify:
             let hasBlobUrl = false;
             product.sides.forEach(side => {
                 if (side.imageUrl && side.imageUrl.startsWith('blob:')) {
                     hasBlobUrl = true;
                 }
             });
             if (hasBlobUrl) {
                 alert("Error: One or more side images haven't been uploaded yet. Please re-edit the side and save it to upload the image.");
                 return; // Prevent saving with temporary blob URLs
             }


            // Show saving indicator
            const saveBtn = document.querySelector('[data-action="save-product-main"]');
            const originalText = saveBtn ? saveBtn.innerHTML : 'Save Product';
             if (saveBtn) {
                 saveBtn.innerHTML = '<i data-lucide="loader-2" class="ps-icon animate-spin"></i> Saving...'; // Use animate-spin if you have CSS for it
                 saveBtn.disabled = true;
                 // Re-render icon if needed
                 if (window.lucide) window.lucide.createIcons();
             }


            // Save to WooCommerce using API
            wooCommerceAPI.saveProduct(product).then(function(response) {
                if (response.success && response.data) {
                    // Update the product in the main state with the final data from server
                    const savedProductData = response.data;
                    const index = appState.products.findIndex(p => p.id === savedProductData.id || p.woocommerceId === savedProductData.woocommerceId);

                    if (index > -1) {
                         // Update existing product in the list
                         // Merge carefully, maybe just replace entirely if structure is guaranteed
                         appState.products[index] = savedProductData;
                    } else {
                        // It was a new product, add it to the list
                        appState.products.push(savedProductData);
                    }

                    appState.editingProduct = null; // Exit editor mode
                    resetCanvasState();
                    renderApp(); // Re-render dashboard list
                     // Use your showToast function
                     showToast('Product saved successfully!', 'success');

                } else {
                     // Use your showToast function
                     showToast('Error saving product: ' + (response.data?.message || 'Unknown error'), 'error');
                     if (saveBtn) {
                        saveBtn.innerHTML = originalText;
                        saveBtn.disabled = false;
                     }
                }
            }).catch(function(error) {
                 // Use your showToast function
                 showToast('Server error saving product: ' + (error.message || 'Check console'), 'error');
                 if (saveBtn) {
                    saveBtn.innerHTML = originalText;
                    saveBtn.disabled = false;
                 }
            });
        }

    function handleAddSide() {
        tempState.sideForm = { name: 'New Side', imageUrl: '' };
        tempState.editingSideId = null;
        showModal(renderSideModal());
    }
    
    function handleEditSide(sideId) {
        const side = appState.editingProduct.sides.find(s => s.id === sideId);
        if (side) {
            tempState.sideForm = JSON.parse(JSON.stringify(side));
            tempState.editingSideId = sideId;
            showModal(renderSideModal());
        }
    }
    
    function handleDeleteSide(sideId) {
        const sideName = appState.editingProduct.sides.find(s => s.id === sideId)?.name || 'this side';
        if (confirm(`Are you sure you want to delete "${escapeHtml(sideName)}"?`)) {
            
             // Revoke blob URL if it exists, to prevent memory leaks
            const side = appState.editingProduct.sides.find(s => s.id === sideId);
            if (side && side.imageUrl && side.imageUrl.startsWith('blob:')) {
                URL.revokeObjectURL(side.imageUrl);
            }

            appState.editingProduct.sides = appState.editingProduct.sides.filter(s => s.id !== sideId);
            if (canvasState.selectedSideIndex >= appState.editingProduct.sides.length) {
                canvasState.selectedSideIndex = Math.max(0, appState.editingProduct.sides.length - 1);
            }
            resetCanvasState(false);
            renderApp();
        }
    }
        // --- !! UPDATE handleSaveSide !! ---
        async function handleSaveSide() { // Make it async
            const form = tempState.sideForm;
            const isEditing = !!tempState.editingSideId;
            if (!form.name) return alert('Side name is required.');

            // Get the button and show loading state
            const saveSideBtn = document.querySelector('#ps-modal-overlay .ps-btn[data-action="save-side"]');
            const originalSideBtnText = saveSideBtn ? saveSideBtn.innerHTML : (isEditing ? 'Save Changes' : 'Create Side');
             if(saveSideBtn) {
                saveSideBtn.disabled = true;
                saveSideBtn.innerHTML = '<i data-lucide="loader-2" class="ps-icon animate-spin"></i> Saving...';
                if(window.lucide) window.lucide.createIcons();
             }


            let finalImageUrl = form.imageUrl; // Keep existing URL by default
            let uploadedAttachmentId = form.attachmentId || null; // Keep existing ID

            // Check if a *new* file was selected (using the temporary property)
            if (form._newFile) {
                 // Upload the new file
                 const uploadResult = await uploadSideImage(form._newFile);
                 if (uploadResult) {
                     finalImageUrl = uploadResult.url; // Use the persistent URL from WordPress
                     uploadedAttachmentId = uploadResult.attachment_id; // Store attachment ID
                     // If there was an old blob URL for preview, revoke it
                     if (form.imageUrl && form.imageUrl.startsWith('blob:')) {
                         URL.revokeObjectURL(form.imageUrl);
                     }
                 } else {
                     // Upload failed, stop the save process
                     if(saveSideBtn) { // Reset button
                         saveSideBtn.disabled = false;
                         saveSideBtn.innerHTML = originalSideBtnText;
                     }
                     alert('Side image upload failed. Changes not saved.');
                     return;
                 }
            }

            // Clean up temporary file property
            delete form._newFile;


            if (isEditing) {
                // Update existing side in appState.editingProduct
                const index = appState.editingProduct.sides.findIndex(s => s.id === tempState.editingSideId);
                if (index > -1) {
                    const oldSide = appState.editingProduct.sides[index];
                    // Revoke OLD blob URL if it existed and is different now
                    if (oldSide.imageUrl && oldSide.imageUrl.startsWith('blob:') && oldSide.imageUrl !== finalImageUrl) {
                         URL.revokeObjectURL(oldSide.imageUrl);
                    }
                    // Update the side object in the main product state
                    appState.editingProduct.sides[index] = {
                        ...oldSide, // Keep printAreas, restrictionAreas
                        name: form.name,
                        imageUrl: finalImageUrl, // Use the final URL
                        attachmentId: uploadedAttachmentId // Store the ID
                    };
                }
            } else {
                // Create new side in appState.editingProduct
                appState.editingProduct.sides.push({
                    id: generateId('side'),
                    name: form.name,
                    imageUrl: finalImageUrl,
                    attachmentId: uploadedAttachmentId,
                    printAreas: [],
                    restrictionAreas: []
                });
                canvasState.selectedSideIndex = appState.editingProduct.sides.length - 1; // Select the new side
            }

            closeModal(); // Closes modal, clears tempState
            resetCanvasState(false); // Reset canvas view but keep tool mode
            renderApp(); // Re-render the editor view to show updated side list/canvas
             // Use your showToast
             showToast(isEditing ? 'Side updated!' : 'Side added!', 'success');
        }


    function handleDeleteArea() {
      const side = getCurrentSide();
      if (!side || canvasState.selectedType === null || canvasState.selectedIndex === null) return;
      
      const areaArray = canvasState.selectedType === 'printArea' ? side.printAreas : side.restrictionAreas;
      const area = areaArray[canvasState.selectedIndex];
      
      if (confirm(`Are you sure you want to delete "${escapeHtml(area.name)}"?`)) {
        areaArray.splice(canvasState.selectedIndex, 1);
        canvasState.selectedType = null;
        canvasState.selectedIndex = null;
        renderCanvasSidebarAndIcons();
        drawCanvas();
      }
    }
    
    function handleDuplicateArea() {
      const side = getCurrentSide();
      if (!side || canvasState.selectedType === null || canvasState.selectedIndex === null) return;
      
      const areaArray = canvasState.selectedType === 'printArea' ? side.printAreas : side.restrictionAreas;
      const area = areaArray[canvasState.selectedIndex];
      
      const newArea = {
          ...area,
          id: generateId('area'),
          name: `${area.name} (Copy)`,
          x: area.x + 20,
          y: area.y + 20,
      };
      
      areaArray.push(newArea);
      canvasState.selectedIndex = areaArray.length - 1; // select new
      
      renderCanvasSidebarAndIcons();
      drawCanvas();
    }

    // ---------- Modal Helpers (from original) ----------
    function showModal(modalHtml) {
      const overlay = document.getElementById('ps-modal-overlay');
      overlay.innerHTML = modalHtml;
      overlay.style.display = 'grid';
    }

    function closeModal() {
      const overlay = document.getElementById('ps-modal-overlay');
      overlay.innerHTML = '';
      overlay.style.display = 'none';
      
      // Revoke blob URL if one was created in temp state but not saved
      if (tempState.sideForm.imageUrl && tempState.sideForm.imageUrl.startsWith('blob:')) {
           // Check if it was saved
           const savedSide = appState.editingProduct?.sides.find(s => s.id === tempState.editingSideId);
           if (!savedSide || savedSide.imageUrl !== tempState.sideForm.imageUrl) {
                URL.revokeObjectURL(tempState.sideForm.imageUrl);
           }
      }

      // Clear all temp forms
      tempState.fabricForm = {};
      tempState.printTypeForm = {};
      tempState.categoryForm = {};
      tempState.productForm = {};
      tempState.sideForm = {};
      tempState.editingFabricId = null;
      tempState.editingPrintTypeId = null;
      tempState.editingCategoryId = null;
      tempState.editingSideId = null;
    }

         // --- NEW: Add showToast function (copy from admindashboard.js or adapt) ---
          function showToast(message, type = 'info') {
             // Basic alert fallback if showToast isn't fully implemented here
             alert(`${type.toUpperCase()}: ${message}`);
             // If you copy the showToast from admindashboard.js, ensure the #aakaari-toast element exists or is created.
         }

        // --- UPDATE Init ---
        function initAdminPrintStudio() {
             if (typeof AakaariPS === 'undefined' || !AakaariPS.ajax_url) {
                 APP_ROOT.innerHTML = '<div class="ps-card"><h3 style="color:red;">Error: Print Studio Core Data Missing</h3><p>Could not load AJAX configuration (AakaariPS object). Please ensure the PHP localization in <code>inc/print-studio-init.php</code> is correct and the script is enqueued properly.</p></div>';
                 return;
             }
             // Load initial data from WooCommerce
             // (Assuming initWithWooCommerceData calls renderApp on success)
              initWithWooCommerceData();
         }

        // --- Start the app ---
        initAdminPrintStudio(); // Call the specific init function

        // Expose API for debugging
        window.AakaariPrintStudio = { appState, tempState, canvasState, api: wooCommerceAPI };

    }); // End DOMContentLoaded
})();