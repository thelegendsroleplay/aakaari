/*
 * assets/print-studio/print-studio.js
 * - Fixed AJAX action mismatch for initial data load.
 * - Added persistent side image upload.
 */
(function () {
    'use strict';

    document.addEventListener('DOMContentLoaded', () => {
        const APP_ROOT = document.getElementById('custom-print-studio-app');
        if (!APP_ROOT) return;

        let appState = {
             activeTab: 'products',
             editingProduct: null,
             products: [],
             // Keep default fabrics/printTypes for fallback or initial structure
             fabrics: [
                { id: 'fab-1', name: '100% Cotton', description: 'Standard soft cotton, 180 GSM.', price: 0 },
                { id: 'fab-2', name: 'Premium Tri-Blend', description: 'A soft, durable blend.', price: 2.50 }
             ],
             printTypes: [
                 { id: 'pt_dtf', name: 'DTF (Direct to Film)', description: 'Vibrant colors.', pricingModel: 'per-inch', price: 0.15 },
                 { id: 'pt_embroidery', name: 'Embroidery', description: 'Stitched design.', pricingModel: 'fixed', price: 8.00 }
             ],
             // These will be filled by AJAX:
             categories: [],
             wooCommerceColors: [], // Renamed for clarity, filled from 'colors' in AJAX response
             fetchedProducts: [] // Store products fetched from AJAX separately initially
        };
    // --- Temporary / Form state ---


// --- WooCommerce API methods (CORRECTED ACTIONS) ---
const wooCommerceAPI = {

    loadInitialData: function () {
        console.log("Attempting to load initial data via aakaari_ps_load_data...");
        return jQuery.ajax({
            url: AakaariPS.ajax_url,
            type: 'POST',
            data: {
                action: 'aakaari_ps_load_data',
                nonce: AakaariPS.nonce
            }
        });
    },

    saveProduct: function (product) {
        return jQuery.ajax({
            url: AakaariPS.ajax_url,
            type: 'POST',
            data: {
                action: 'aakaari_ps_save_product',
                nonce: AakaariPS.nonce,
                product_data: JSON.stringify(product)
            }
        });
    },

    updateProductStatus: function (productId, isActive) {
        return jQuery.ajax({
            url: AakaariPS.ajax_url,
            type: 'POST',
            data: {
                action: 'aakaari_ps_update_status',
                nonce: AakaariPS.nonce,
                product_id: productId,
                is_active: isActive ? 1 : 0
            }
        });
    },

    saveCategory: function (category) {
        return jQuery.ajax({
            url: AakaariPS.ajax_url,
            type: 'POST',
            data: {
                action: 'aakaari_ps_save_category',
                nonce: AakaariPS.nonce,
                category_data: JSON.stringify(category)
            }
        });
    },

    deleteCategory: function (categoryId) {
        return jQuery.ajax({
            url: AakaariPS.ajax_url,
            type: 'POST',
            data: {
                action: 'aakaari_ps_delete_category',
                nonce: AakaariPS.nonce,
                category_id: categoryId
            }
        });
    },
    
    saveFabric: function (fabric) {
        return jQuery.ajax({
            url: AakaariPS.ajax_url,
            type: 'POST',
            data: {
                action: 'aakaari_ps_save_fabric',
                nonce: AakaariPS.nonce,
                fabric_data: JSON.stringify(fabric)
            }
        });
    },
    
    deleteFabric: function (fabricId) {
        return jQuery.ajax({
            url: AakaariPS.ajax_url,
            type: 'POST',
            data: {
                action: 'aakaari_ps_delete_fabric',
                nonce: AakaariPS.nonce,
                fabric_id: fabricId
            }
        });
    },
    
    savePrintType: function (printType) {
        return jQuery.ajax({
            url: AakaariPS.ajax_url,
            type: 'POST',
            data: {
                action: 'aakaari_ps_save_print_type',
                nonce: AakaariPS.nonce,
                print_type_data: JSON.stringify(printType)
            }
        });
    },
    
    deletePrintType: function (printTypeId) {
        return jQuery.ajax({
            url: AakaariPS.ajax_url,
            type: 'POST',
            data: {
                action: 'aakaari_ps_delete_print_type',
                nonce: AakaariPS.nonce,
                print_type_id: printTypeId
            }
        });
    }
};

        // --- Updated: Initialize using the single AJAX call ---
        function initWithWooCommerceData() {
            APP_ROOT.innerHTML = `<div class="ps-card"><h3>Loading Custom Print Studio...</h3><p class="ps-helper">Loading data from WooCommerce...</p></div>`;

            // Call the single data loading function
            wooCommerceAPI.loadInitialData().then(function (response) {
                 // Check response structure carefully
                 console.log("Initial data response received:", response);

                 if (response.success && response.data) {
                     // Populate appState with data received from aakaari_ps_load_data
                     appState.fetchedProducts = response.data.products || [];
                     appState.categories = response.data.categories || [];
                     appState.wooCommerceColors = response.data.colors || [];
                     appState.fabrics = response.data.fabrics || [];
                     appState.printTypes = response.data.printTypes || [];

                      // Now merge fetched products into the main products list if needed, or just use fetchedProducts
                      // For simplicity, let's just replace the default products array
                      appState.products = appState.fetchedProducts;

                     // Enhanced logging for debugging
                     console.log("✓ Loaded " + appState.products.length + " print studio products");
                     console.log("✓ Loaded " + appState.categories.length + " categories");
                     console.log("✓ Loaded " + appState.wooCommerceColors.length + " colors from WooCommerce");
                     console.log("✓ Loaded " + appState.fabrics.length + " fabrics from WooCommerce");
                     console.log("✓ Loaded " + appState.printTypes.length + " print types from WooCommerce");
                     
                     if (appState.wooCommerceColors.length > 0) {
                         console.log("Colors available:", appState.wooCommerceColors.map(c => c.name).join(', '));
                     } else {
                         console.warn("⚠ No colors loaded! Please add colors in WooCommerce > Products > Attributes > Color");
                     }

                     // Check if essential data is present
                     if (!appState.categories.length) console.warn("⚠ No categories loaded from WooCommerce.");
                     if (!appState.products.length) console.info("ℹ No print studio products found yet (this is normal for new installations).");

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

    // --- Canvas-specific state ---
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

        // ... (rest of your print-studio.js code: utils, render functions, event handlers, canvas logic, etc.) ...
        // Ensure that renderProductModal uses appState.wooCommerceColors and appState.printTypes (the default/fallback ones initially)
        // Ensure render functions for tabs use the correct appState properties.

         // --- Utils ---
    function generateId(prefix = 'id') {
      return `${prefix}-${Date.now()}-${Math.random().toString(36).substring(2, 9)}`;
    }
    function escapeHtml(str) {
      if (!str) return '';
      return String(str).replace(/[&<>"']/g, m => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[m]));
    }

         // --- Image Upload ---
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

    // ---------- Dashboard Tab Renderers ----------
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

    // ---------- Modal Renderers ----------
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

    function renderFabricModal() {
      const isEditing = !!tempState.editingFabricId;
      const form = tempState.fabricForm;
      const title = isEditing ? 'Edit Fabric' : 'Add New Fabric';
      const content = `
        <div class="ps-form-column">
          <div class="ps-form-group">
            <label class="ps-label" for="fabric-name">Fabric Name</label>
            <input type="text" id="fabric-name" class="ps-input" 
                   value="${escapeHtml(form.name)}" data-form="fabricForm" data-prop="name">
          </div>
          <div class="ps-form-group">
            <label class="ps-label" for="fabric-desc">Description</label>
            <textarea id="fabric-desc" class="ps-textarea" 
                      data-form="fabricForm" data-prop="description">${escapeHtml(form.description)}</textarea>
          </div>
          <div class="ps-form-group">
            <label class="ps-label" for="fabric-price">Price Adjustment ($)</label>
            <input type="number" id="fabric-price" class="ps-input" 
                   value="${form.price}" step="0.01" data-form="fabricForm" data-prop="price">
            <p class="ps-helper">Extra cost added to the base product price.</p>
          </div>
        </div>
      `;
      const footer = `
        <button class="ps-btn ps-btn--outline" data-action="close-modal">Cancel</button>
        <button class="ps-btn ps-btn--primary" data-action="save-fabric">
          ${isEditing ? 'Save Changes' : 'Create Fabric'}
        </button>
      `;
      return renderModal(title, content, footer);
    }

    function renderPrintTypeModal() {
      const isEditing = !!tempState.editingPrintTypeId;
      const form = tempState.printTypeForm;
      const title = isEditing ? 'Edit Print Type' : 'Add New Print Type';
      const content = `
        <div class="ps-form-column">
          <div class="ps-form-group">
            <label class="ps-label" for="pt-name">Print Type Name</label>
            <input type="text" id="pt-name" class="ps-input" 
                   value="${escapeHtml(form.name)}" data-form="printTypeForm" data-prop="name">
          </div>
          <div class="ps-form-group">
            <label class="ps-label" for="pt-desc">Description</label>
            <textarea id="pt-desc" class="ps-textarea" 
                      data-form="printTypeForm" data-prop="description">${escapeHtml(form.description)}</textarea>
          </div>
          <div class="ps-grid ps-grid--2">
            <div class="ps-form-group">
              <label class="ps-label" for="pt-model">Pricing Model</label>
              <select id="pt-model" class="ps-select" data-form="printTypeForm" data-prop="pricingModel">
                <option value="per-inch" ${form.pricingModel === 'per-inch' ? 'selected' : ''}>Per Square Inch</option>
                <option value="fixed" ${form.pricingModel === 'fixed' ? 'selected' : ''}>Fixed Price</option>
              </select>
            </div>
            <div class="ps-form-group">
              <label class="ps-label" for="pt-price">Price ($)</label>
              <input type="number" id="pt-price" class="ps-input" 
                     value="${form.price}" step="0.01" data-form="printTypeForm" data-prop="price">
            </div>
          </div>
        </div>
      `;
      const footer = `
        <button class="ps-btn ps-btn--outline" data-action="close-modal">Cancel</button>
        <button class="ps-btn ps-btn--primary" data-action="save-print-type">
          ${isEditing ? 'Save Changes' : 'Create Print Type'}
        </button>
      `;
      return renderModal(title, content, footer);
    }

    function renderCategoryModal() {
      const isEditing = !!tempState.editingCategoryId;
      const form = tempState.categoryForm;
      const title = isEditing ? 'Edit Category' : 'Add New Category';
      const content = `
        <div class="ps-form-column">
          <div class="ps-form-group">
            <label class="ps-label" for="cat-name">Category Name</label>
            <input type="text" id="cat-name" class="ps-input" 
                   value="${escapeHtml(form.name)}" data-form="categoryForm" data-prop="name">
            <p class="ps-helper">This should match a WooCommerce product category.</p>
          </div>
        </div>
      `;
      const footer = `
        <button class="ps-btn ps-btn--outline" data-action="close-modal">Cancel</button>
        <button class="ps-btn ps-btn--primary" data-action="save-category">
          ${isEditing ? 'Save Changes' : 'Create Category'}
        </button>
      `;
      return renderModal(title, content, footer);
    }

    function renderProductModal() {
      const form = tempState.productForm;
      const title = 'Edit Product Details';
      const content = `
        <div class="ps-form-column">
          <div class="ps-form-group">
            <label class="ps-label" for="prod-name">Product Name</label>
            <input type="text" id="prod-name" class="ps-input" 
                   value="${escapeHtml(form.name)}" data-form="productForm" data-prop="name">
          </div>
          <div class="ps-form-group">
            <label class="ps-label" for="prod-desc">Description</label>
            <textarea id="prod-desc" class="ps-textarea" 
                      data-form="productForm" data-prop="description">${escapeHtml(form.description)}</textarea>
          </div>
          <div class="ps-grid ps-grid--2">
            <div class="ps-form-group">
              <label class="ps-label" for="prod-basePrice">Base Price ($)</label>
              <input type="number" id="prod-basePrice" class="ps-input" 
                     value="${form.basePrice}" step="0.01" data-form="productForm" data-prop="basePrice">
            </div>
            <div class="ps-form-group">
              <label class="ps-label" for="prod-salePrice">Sale Price ($)</label>
              <input type="number" id="prod-salePrice" class="ps-input" 
                     value="${form.salePrice || ''}" step="0.01" placeholder="Optional" data-form="productForm" data-prop="salePrice">
            </div>
          </div>
          <div class="ps-grid ps-grid--2">
            <div class="ps-form-group">
              <label class="ps-label" for="prod-category">Category</label>
              <select id="prod-category" class="ps-select" data-form="productForm" data-prop="category">
                ${appState.categories.map(c => `
                  <option value="${escapeHtml(c.name)}" ${form.category === c.name ? 'selected' : ''}>
                    ${escapeHtml(c.name)}
                  </option>
                `).join('')}
              </select>
            </div>
            <div class="ps-form-group">
              <label class="ps-label" for="prod-wooId">WooCommerce ID</label>
              <input type="text" id="prod-wooId" class="ps-input" 
                     value="${escapeHtml(form.woocommerceId)}" data-form="productForm" data-prop="woocommerceId">
            </div>
          </div>
          <div class="ps-form-group">
            <label class="ps-label">Available Colors</label>
            ${appState.wooCommerceColors.length > 0 ? `
            <div class="ps-color-picker">
              ${appState.wooCommerceColors.map(c => `
                <label class="ps-color-chip" style="--chip-color: ${c.hex};" title="${escapeHtml(c.name)}">
                  <input type="checkbox" value="${c.hex}" data-form="productForm" data-prop="colors"
                         ${form.colors.includes(c.hex) ? 'checked' : ''}>
                  <span class="ps-color-chip-check"><i data-lucide="check" class="ps-icon-small"></i></span>
                </label>
              `).join('')}
            </div>
            ` : `
            <p class="ps-helper" style="color: #f59e0b;">
              <i data-lucide="alert-circle" class="ps-icon"></i>
              No colors found. Please add colors in <strong>WooCommerce > Products > Attributes > Color</strong>.
            </p>
            `}
          </div>
          <div class="ps-form-group">
            <label class="ps-label">Available Fabrics</label>
            ${appState.fabrics.length > 0 ? `
            <div class="ps-form-column">
              ${appState.fabrics.map(f => `
                <label class="ps-checkbox-label">
                  <input type="checkbox" value="${f.id}" data-form="productForm" data-prop="fabrics"
                         ${form.fabrics && form.fabrics.includes(f.id) ? 'checked' : ''}>
                  <span>${escapeHtml(f.name)}</span>
                </label>
              `).join('')}
            </div>
            ` : `
            <p class="ps-helper" style="color: #f59e0b;">
              <i data-lucide="alert-circle" class="ps-icon"></i>
              No fabrics found. Please add fabrics in the <strong>Fabrics</strong> tab.
            </p>
            `}
          </div>
          <div class="ps-form-group">
            <label class="ps-label">Available Print Types</label>
            ${appState.printTypes.length > 0 ? `
            <div class="ps-form-column">
              ${appState.printTypes.map(pt => `
                <label class="ps-checkbox-label">
                  <input type="checkbox" value="${pt.id}" data-form="productForm" data-prop="availablePrintTypes"
                         ${form.availablePrintTypes.includes(pt.id) ? 'checked' : ''}>
                  <span>${escapeHtml(pt.name)}</span>
                </label>
              `).join('')}
            </div>
            ` : `
            <p class="ps-helper" style="color: #f59e0b;">
              <i data-lucide="alert-circle" class="ps-icon"></i>
              No print types found. Please add print types in the <strong>Print Types</strong> tab.
            </p>
            `}
          </div>
        </div>
      `;
      const footer = `
        <button class="ps-btn ps-btn--outline" data-action="close-modal">Cancel</button>
        <button class="ps-btn ps-btn--primary" data-action="save-product-details">Save Details</button>
      `;
      return renderModal(title, content, footer);
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

    // ---------- Canvas init & draw ----------
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

    function drawArea(ctx, a, type, isSelected) {
      const isRestriction = type === 'restriction';
      ctx.save();
      ctx.strokeStyle = isRestriction ? 
        (isSelected ? '#d00000' : 'rgba(239,68,68,0.9)') : // red
        (isSelected ? '#2563EB' : 'rgba(59,130,246,0.9)'); // blue
      ctx.fillStyle = isRestriction ? 
        (isSelected ? 'rgba(239,68,68,0.15)' : 'rgba(239,68,68,0.06)') :
        (isSelected ? 'rgba(59,130,246,0.15)' : 'rgba(59,130,246,0.06)');
      
      ctx.lineWidth = isSelected ? 3 : 2;
      ctx.setLineDash(isRestriction ? [4, 4] : []);
      
      ctx.fillRect(a.x, a.y, a.width, a.height);
      ctx.strokeRect(a.x, a.y, a.width, a.height);

      // Draw name
      ctx.fillStyle = isRestriction ? '#d00000' : '#2563EB';
      ctx.font = '600 12px -apple-system, sans-serif';
      ctx.fillText(a.name, a.x + 5, a.y + 16);
      
      ctx.restore();
    }
    
    function drawResizeHandles(ctx, area) {
        const { HANDLE_SIZE, hoveredHandle } = canvasState;
        const handles = getHandleCoordinates(area);
        
        ctx.save();
        Object.keys(handles).forEach(key => {
            const handle = handles[key];
            const isHovered = hoveredHandle === key;
            ctx.fillStyle = isHovered ? 'hsl(var(--primary))' : '#FFFFFF';
            ctx.strokeStyle = 'hsl(var(--primary))';
            ctx.lineWidth = 1.5;
            ctx.fillRect(handle.x - HANDLE_SIZE / 2, handle.y - HANDLE_SIZE / 2, HANDLE_SIZE, HANDLE_SIZE);
            ctx.strokeRect(handle.x - HANDLE_SIZE / 2, handle.y - HANDLE_SIZE / 2, HANDLE_SIZE, HANDLE_SIZE);
        });
        ctx.restore();
    }
    
    function getHandleCoordinates(area) {
        return {
            'tl': { x: area.x, y: area.y },
            'tm': { x: area.x + area.width / 2, y: area.y },
            'tr': { x: area.x + area.width, y: area.y },
            'ml': { x: area.x, y: area.y + area.height / 2 },
            'mr': { x: area.x + area.width, y: area.y + area.height / 2 },
            'bl': { x: area.x, y: area.y + area.height },
            'bm': { x: area.x + area.width / 2, y: area.y + area.height },
            'br': { x: area.x + area.width, y: area.y + area.height },
        };
    }
    
    function getHandleAtPosition(pos, area) {
        const { HANDLE_SIZE } = canvasState;
        const handles = getHandleCoordinates(area);
        
        for (const key in handles) {
            const handle = handles[key];
            if (pos.x >= handle.x - HANDLE_SIZE && pos.x <= handle.x + HANDLE_SIZE &&
                pos.y >= handle.y - HANDLE_SIZE && pos.y <= handle.y + HANDLE_SIZE) {
                return key;
            }
        }
        return null;
    }
    
    function isInsideArea(pos, area) {
        return pos.x >= area.x && pos.x <= area.x + area.width &&
               pos.y >= area.y && pos.y <= area.y + area.height;
    }
    
    function getCanvasCoordinates(e) {
        const rect = canvasState.canvas.getBoundingClientRect();
        const x = Math.round((e.clientX - rect.left) * (canvasState.CANVAS_WIDTH / rect.width));
        const y = Math.round((e.clientY - rect.top) * (canvasState.CANVAS_HEIGHT / rect.height));
        return { x, y };
    }
    
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

    function getCurrentSide() {
      if (!appState.editingProduct || !appState.editingProduct.sides) return null;
      return appState.editingProduct.sides[canvasState.selectedSideIndex] || null;
    }
    // ---------- START: ADDING MISSING FUNCTIONS ----------

    function renderCategoryModal() {
      const isEditing = !!tempState.editingCategoryId;
      const form = tempState.categoryForm;
      const title = isEditing ? 'Edit Category' : 'Add New Category';
      const content = `
        <div class="ps-form-column">
          <div class="ps-form-group">
            <label class="ps-label" for="cat-name">Category Name</label>
            <input type="text" id="cat-name" class="ps-input" 
                   value="${escapeHtml(form.name)}" data-form="categoryForm" data-prop="name">
            <p class="ps-helper">This should match a WooCommerce product category.</p>
          </div>
        </div>
      `;
      const footer = `
        <button class="ps-btn ps-btn--outline" data-action="close-modal">Cancel</button>
        <button class="ps-btn ps-btn--primary" data-action="save-category">
          ${isEditing ? 'Save Changes' : 'Create Category'}
        </button>
      `;
      return renderModal(title, content, footer);
    }

    function getCanvasCoordinates(e) {
        if (!canvasState.canvas) return { x: 0, y: 0 }; // Safety check
        const rect = canvasState.canvas.getBoundingClientRect();
        // Adjust for potential CSS scaling
        const scaleX = canvasState.CANVAS_WIDTH / rect.width;
        const scaleY = canvasState.CANVAS_HEIGHT / rect.height;
        const x = Math.round((e.clientX - rect.left) * scaleX);
        const y = Math.round((e.clientY - rect.top) * scaleY);
        return { x, y };
    }

    function drawArea(ctx, a, type, isSelected) {
      // Ensure area has valid dimensions
       if (!a || typeof a.x !== 'number' || typeof a.y !== 'number' || typeof a.width !== 'number' || typeof a.height !== 'number') {
           console.warn("Attempted to draw invalid area:", a);
           return;
       }

      const isRestriction = type === 'restriction';
      ctx.save();
      ctx.strokeStyle = isRestriction ? 
        (isSelected ? '#d00000' : 'rgba(239,68,68,0.9)') : // red
        (isSelected ? '#2563EB' : 'rgba(59,130,246,0.9)'); // blue
      ctx.fillStyle = isRestriction ? 
        (isSelected ? 'rgba(239,68,68,0.15)' : 'rgba(239,68,68,0.06)') :
        (isSelected ? 'rgba(59,130,246,0.15)' : 'rgba(59,130,246,0.06)');
      
      ctx.lineWidth = isSelected ? 3 : 2;
      ctx.setLineDash(isRestriction ? [4, 4] : []);
      
      ctx.fillRect(a.x, a.y, a.width, a.height);
      ctx.strokeRect(a.x, a.y, a.width, a.height);
      ctx.setLineDash([]); // Reset line dash

      // Draw name
      ctx.fillStyle = isRestriction ? '#b91c1c' : '#1d4ed8'; // Darker text fill
      ctx.font = '600 12px -apple-system, sans-serif';
      ctx.textAlign = 'left';
      ctx.textBaseline = 'top';
      
      const text = a.name || (isRestriction ? 'Restriction' : 'Print Area');
      const textMetrics = ctx.measureText(text);
      const textBgPadding = 2;
      ctx.fillStyle = 'rgba(255, 255, 255, 0.7)'; // Semi-transparent white background
      ctx.fillRect(a.x + 4 - textBgPadding, a.y + 4 - textBgPadding, textMetrics.width + textBgPadding*2, 12 + textBgPadding*2);

      ctx.fillStyle = isRestriction ? '#b91c1c' : '#1d4ed8'; // Restore text color
      ctx.fillText(text, a.x + 4, a.y + 4); // Position text inside the area
      
      ctx.restore();
    }
    
    function drawResizeHandles(ctx, area) {
        const { HANDLE_SIZE, hoveredHandle } = canvasState;
        const handles = getHandleCoordinates(area);
        
        ctx.save();
        Object.keys(handles).forEach(key => {
            const handle = handles[key];
            const isHovered = hoveredHandle === key;
            ctx.fillStyle = isHovered ? 'hsl(var(--primary))' : '#FFFFFF';
            ctx.strokeStyle = 'hsl(var(--primary))';
            ctx.lineWidth = 1.5;
            ctx.fillRect(handle.x - HANDLE_SIZE / 2, handle.y - HANDLE_SIZE / 2, HANDLE_SIZE, HANDLE_SIZE);
            ctx.strokeRect(handle.x - HANDLE_SIZE / 2, handle.y - HANDLE_SIZE / 2, HANDLE_SIZE, HANDLE_SIZE);
        });
        ctx.restore();
    }
    
    function getHandleCoordinates(area) {
        return {
            'tl': { x: area.x, y: area.y },
            'tm': { x: area.x + area.width / 2, y: area.y },
            'tr': { x: area.x + area.width, y: area.y },
            'ml': { x: area.x, y: area.y + area.height / 2 },
            'mr': { x: area.x + area.width, y: area.y + area.height / 2 },
            'bl': { x: area.x, y: area.y + area.height },
            'bm': { x: area.x + area.width / 2, y: area.y + area.height },
            'br': { x: area.x + area.width, y: area.y + area.height },
        };
    }
    
    function getHandleAtPosition(pos, area) {
        const { HANDLE_SIZE } = canvasState;
        const handles = getHandleCoordinates(area);
        
        for (const key in handles) {
            const handle = handles[key];
            if (pos.x >= handle.x - HANDLE_SIZE && pos.x <= handle.x + HANDLE_SIZE &&
                pos.y >= handle.y - HANDLE_SIZE && pos.y <= handle.y + HANDLE_SIZE) {
                return key;
            }
        }
        return null;
    }
    
    function isInsideArea(pos, area) {
        if (!area) return false; // Safety check
        return pos.x >= area.x && pos.x <= area.x + area.width &&
               pos.y >= area.y && pos.y <= area.y + area.height;
    }

// ---------- END: Missing Canvas Helper Functions ----------
// ---------- START: Missing Canvas Helper Functions ----------

    function drawArea(ctx, a, type, isSelected) {
      // Ensure area has valid dimensions
       if (!a || typeof a.x !== 'number' || typeof a.y !== 'number' || typeof a.width !== 'number' || typeof a.height !== 'number') {
           console.warn("Attempted to draw invalid area:", a);
           return;
       }

      const isRestriction = type === 'restriction';
      ctx.save();
      ctx.strokeStyle = isRestriction ? 
        (isSelected ? '#d00000' : 'rgba(239,68,68,0.9)') : // red
        (isSelected ? '#2563EB' : 'rgba(59,130,246,0.9)'); // blue
      ctx.fillStyle = isRestriction ? 
        (isSelected ? 'rgba(239,68,68,0.15)' : 'rgba(239,68,68,0.06)') :
        (isSelected ? 'rgba(59,130,246,0.15)' : 'rgba(59,130,246,0.06)');
      
      ctx.lineWidth = isSelected ? 3 : 2;
      ctx.setLineDash(isRestriction ? [4, 4] : []);
      
      ctx.fillRect(a.x, a.y, a.width, a.height);
      ctx.strokeRect(a.x, a.y, a.width, a.height);
      ctx.setLineDash([]); // Reset line dash

      // Draw name
      ctx.fillStyle = isRestriction ? '#b91c1c' : '#1d4ed8'; // Darker text fill
      ctx.font = '600 12px -apple-system, sans-serif';
      ctx.textAlign = 'left';
      ctx.textBaseline = 'top';
      
      const text = a.name || (isRestriction ? 'Restriction' : 'Print Area');
      const textMetrics = ctx.measureText(text);
      const textBgPadding = 2;
      ctx.fillStyle = 'rgba(255, 255, 255, 0.7)'; // Semi-transparent white background
      ctx.fillRect(a.x + 4 - textBgPadding, a.y + 4 - textBgPadding, textMetrics.width + textBgPadding*2, 12 + textBgPadding*2);

      ctx.fillStyle = isRestriction ? '#b91c1c' : '#1d4ed8'; // Restore text color
      ctx.fillText(text, a.x + 4, a.y + 4); // Position text inside the area
      
      ctx.restore();
    }
    
    function drawResizeHandles(ctx, area) {
        const { HANDLE_SIZE, hoveredHandle } = canvasState;
        const handles = getHandleCoordinates(area);
        
        ctx.save();
        Object.keys(handles).forEach(key => {
            const handle = handles[key];
            const isHovered = hoveredHandle === key;
            ctx.fillStyle = isHovered ? 'hsl(var(--primary))' : '#FFFFFF';
            ctx.strokeStyle = 'hsl(var(--primary))';
            ctx.lineWidth = 1.5;
            ctx.fillRect(handle.x - HANDLE_SIZE / 2, handle.y - HANDLE_SIZE / 2, HANDLE_SIZE, HANDLE_SIZE);
            ctx.strokeRect(handle.x - HANDLE_SIZE / 2, handle.y - HANDLE_SIZE / 2, HANDLE_SIZE, HANDLE_SIZE);
        });
        ctx.restore();
    }
    
    function getHandleCoordinates(area) {
        return {
            'tl': { x: area.x, y: area.y },
            'tm': { x: area.x + area.width / 2, y: area.y },
            'tr': { x: area.x + area.width, y: area.y },
            'ml': { x: area.x, y: area.y + area.height / 2 },
            'mr': { x: area.x + area.width, y: area.y + area.height / 2 },
            'bl': { x: area.x, y: area.y + area.height },
            'bm': { x: area.x + area.width / 2, y: area.y + area.height },
            'br': { x: area.x + area.width, y: area.y + area.height },
        };
    }
    
    function getHandleAtPosition(pos, area) {
        const { HANDLE_SIZE } = canvasState;
        const handles = getHandleCoordinates(area);
        
        for (const key in handles) {
            const handle = handles[key];
            if (pos.x >= handle.x - HANDLE_SIZE && pos.x <= handle.x + HANDLE_SIZE &&
                pos.y >= handle.y - HANDLE_SIZE && pos.y <= handle.y + HANDLE_SIZE) {
                return key;
            }
        }
        return null;
    }
    
    function isInsideArea(pos, area) {
        if (!area) return false; // Safety check
        return pos.x >= area.x && pos.x <= area.x + area.width &&
               pos.y >= area.y && pos.y <= area.y + area.height;
    }
    
    function getCanvasCoordinates(e) {
        if (!canvasState.canvas) return { x: 0, y: 0 }; // Safety check
        const rect = canvasState.canvas.getBoundingClientRect();
        // Adjust for potential CSS scaling
        const scaleX = canvasState.CANVAS_WIDTH / rect.width;
        const scaleY = canvasState.CANVAS_HEIGHT / rect.height;
        const x = Math.round((e.clientX - rect.left) * scaleX);
        const y = Math.round((e.clientY - rect.top) * scaleY);
        return { x, y };
    }

    // ---------- END: Missing Canvas Helper Functions ----------
    // ---------- Canvas Event Handlers ----------
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

    // ---------- Event handling (delegation) ----------
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

      // Dashboard actions
      if (action === 'set-dashboard-tab') {
        appState.activeTab = data.tabId;
        renderApp();
      }
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
          if (!Array.isArray(tempState[formName][propName])) {
            tempState[formName][propName] = [];
          }
          const propArray = tempState[formName][propName];
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
    
    function delegatedChange(e) {
        const target = e.target;
        const data = target.dataset;
        const action = target.getAttribute('data-action');
        
        // Product active toggle switch
        if (action === 'toggle-product-active') {
            const prod = appState.products.find(p => p.id === data.productId);
            if (prod) prod.isActive = target.checked;
        }
        
        // Modal Form Inputs (for selects)
        if (data.form && data.prop && target.tagName === 'SELECT') {
             tempState[data.form][data.prop] = target.value;
        }
        
        // *** NEW: Handle side image file upload in the modal ***
        if (target.id === 'side-imageUpload' && target.files && target.files[0]) {
            const file = target.files[0];
            
            // If there's an old blob URL, revoke it to prevent memory leaks
            if (tempState.sideForm.imageUrl && tempState.sideForm.imageUrl.startsWith('blob:')) {
                URL.revokeObjectURL(tempState.sideForm.imageUrl);
            }
            
            // Create a local URL for the selected file
            const localUrl = URL.createObjectURL(file);
            
            // Update the temp form state
            tempState.sideForm.imageUrl = localUrl;
            
            // Update the helper text to show the new file name
            const helperText = document.getElementById('current-image-name');
            if (helperText) {
                helperText.textContent = `New file: ${file.name}`;
            }
        }
    }
    
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

    // ---------- Handlers (Dashboard) ----------
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
      console.log('handleSaveFabric called with form:', form);
      
      if (!form.name || form.name.trim() === '') {
          alert('Fabric name is required.');
          return;
      }
      
      const saveBtn = document.querySelector('#ps-modal-overlay [data-action="save-fabric"]');
      if(saveBtn) saveBtn.disabled = true;

      console.log('Sending fabric data to API:', form);
      
      // Save to WooCommerce via API
      wooCommerceAPI.saveFabric(form).then(function(response) {
        console.log('Fabric save response:', response);
        if (response.success && response.data) {
          // response.data contains the updated/new fabric object { id, name, description, price }
          if (tempState.editingFabricId) {
            // Find and update in state
            const index = appState.fabrics.findIndex(f => f.id === tempState.editingFabricId);
            if (index > -1) {
              appState.fabrics[index] = response.data;
            }
          } else {
            // Add new to state
            appState.fabrics.push(response.data);
          }
          closeModal();
          renderApp(); // Redraw the dashboard
          showToast(tempState.editingFabricId ? 'Fabric updated!' : 'Fabric added!', 'success');
        } else {
          console.error('Fabric save failed:', response);
          alert('Error saving fabric: ' + (response.data?.message || response.data || 'Unknown error'));
          if(saveBtn) saveBtn.disabled = false;
        }
      }).catch(function(error) {
        console.error('Failed to save fabric - full error:', error);
        console.error('Error response:', error.responseJSON);
        console.error('Error status:', error.status);
        console.error('Error text:', error.statusText);
        
        let errorMsg = 'Network error saving fabric.';
        if (error.responseJSON && error.responseJSON.data) {
          errorMsg = error.responseJSON.data;
          
          // Show helpful message for missing attribute
          if (errorMsg.includes('does not exist')) {
            errorMsg += '\n\nPlease go to: WooCommerce > Products > Attributes\nAnd create an attribute with:\n- Name: Fabric\n- Slug: fabric';
          }
        }
        
        alert(errorMsg);
        if(saveBtn) saveBtn.disabled = false;
      });
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
      if (!form.name || form.name.trim() === '') {
          alert('Print type name is required.');
          return;
      }
      
      const saveBtn = document.querySelector('#ps-modal-overlay [data-action="save-print-type"]');
      if(saveBtn) saveBtn.disabled = true;

      // Save to WooCommerce via API
      wooCommerceAPI.savePrintType(form).then(function(response) {
        if (response.success && response.data) {
          // response.data contains the updated/new print type object { id, name, description, pricingModel, price }
          if (tempState.editingPrintTypeId) {
            // Find and update in state
            const index = appState.printTypes.findIndex(p => p.id === tempState.editingPrintTypeId);
            if (index > -1) {
              appState.printTypes[index] = response.data;
            }
          } else {
            // Add new to state
            appState.printTypes.push(response.data);
          }
          closeModal();
          renderApp(); // Redraw the dashboard
          showToast(tempState.editingPrintTypeId ? 'Print type updated!' : 'Print type added!', 'success');
        } else {
          alert('Error saving print type: ' + (response.data?.message || 'Unknown error'));
          if(saveBtn) saveBtn.disabled = false;
        }
      }).catch(function(error) {
        console.error('Failed to save print type:', error);
        console.error('Error response:', error.responseJSON);
        
        let errorMsg = 'Network error saving print type.';
        if (error.responseJSON && error.responseJSON.data) {
          errorMsg = error.responseJSON.data;
          
          // Show helpful message for missing attribute
          if (errorMsg.includes('does not exist')) {
            errorMsg += '\n\nPlease go to: WooCommerce > Products > Attributes\nAnd create an attribute with:\n- Name: Print Type\n- Slug: print_type';
          }
        }
        
        alert(errorMsg);
        if(saveBtn) saveBtn.disabled = false;
      });
    }
    
// --- CORRECTED Category Modal Handler ---
    function handleShowCategoryModal(categoryId = null) {
      if (categoryId) {
        // --- This is EDIT mode ---
        const cat = appState.categories.find(c => c.id == categoryId); // Use == for loose comparison (e.g., '12' vs 12)
        if (!cat) {
            console.error(`Category with ID ${categoryId} not found in appState.`);
            showToast(`Error: Category not found.`, 'error');
            return; // Stop
        }
        tempState.categoryForm = JSON.parse(JSON.stringify(cat)); // Safe to parse
        tempState.editingCategoryId = categoryId;
      } else {
        // --- This is ADD NEW mode ---
        tempState.categoryForm = { name: '' }; // Start with an empty form
        tempState.editingCategoryId = null;
      }
      showModal(renderCategoryModal()); // Now show the modal
    }
    

// --- CORRECTED Category Save Handler (Uses API) ---
    function handleSaveCategory() {
      const form = tempState.categoryForm;
      if (!form || !form.name || form.name.trim() === '') {
          alert('Category name is required.');
          return;
      }
      
      const saveBtn = document.querySelector('#ps-modal-overlay [data-action="save-category"]');
      if(saveBtn) saveBtn.disabled = true;

      // Save to WooCommerce via API
      wooCommerceAPI.saveCategory(form).then(function(response) {
        if (response.success && response.data) {
          // response.data contains the updated/new category object { id, name }
          if (tempState.editingCategoryId) {
            // Find and update in state
            const index = appState.categories.findIndex(c => c.id == tempState.editingCategoryId);
            if (index > -1) {
              appState.categories[index] = response.data;
            }
          } else {
            // Add new to state
            appState.categories.push(response.data);
          }
          closeModal();
          renderApp(); // Redraw the dashboard
          showToast(tempState.editingCategoryId ? 'Category updated!' : 'Category added!', 'success');
        } else {
          alert('Error saving category: ' (response.data?.message || 'Unknown error'));
          if(saveBtn) saveBtn.disabled = false;
        }
      }).catch(function(error) {
        alert('AJAX Error saving category: ' + (error.responseJSON?.data?.message || error.statusText || 'Unknown error'));
        if(saveBtn) saveBtn.disabled = false;
      });
    }
    
    function handleDelete(type, id) {
        const typeMap = {
            'fabric': { stateKey: 'fabrics', name: 'Fabric', apiMethod: 'deleteFabric' },
            'printType': { stateKey: 'printTypes', name: 'Print Type', apiMethod: 'deletePrintType' },
            'category': { stateKey: 'categories', name: 'Category', apiMethod: 'deleteCategory' },
        };
        const config = typeMap[type];
        if (!config) return;

        if (confirm(`Are you sure you want to delete this ${config.name}?`)) {
            // Call the API to delete from WooCommerce
            if (config.apiMethod && wooCommerceAPI[config.apiMethod]) {
                wooCommerceAPI[config.apiMethod](id).then(function(response) {
                    if (response.success) {
                        // Remove from state
                        appState[config.stateKey] = appState[config.stateKey].filter(item => item.id !== id);
                        renderApp();
                        showToast(`${config.name} deleted!`, 'success');
                    } else {
                        alert(`Error deleting ${config.name}: ` + (response.data?.message || 'Unknown error'));
                    }
                }).catch(function(error) {
                    console.error(`Failed to delete ${config.name}:`, error);
                    alert('Network error. Check console.');
                });
            } else {
                // Fallback for items without API (shouldn't happen)
                appState[config.stateKey] = appState[config.stateKey].filter(item => item.id !== id);
                renderApp();
            }
        }
    }

    // ---------- Handlers (Editor) ----------
    function handleShowProductModal() {
      // Load editing product data into the form
      tempState.productForm = JSON.parse(JSON.stringify(appState.editingProduct));
      
      // Ensure colors array exists
      if (!Array.isArray(tempState.productForm.colors)) {
        tempState.productForm.colors = [];
      }
      
      // Ensure fabrics array exists
      if (!Array.isArray(tempState.productForm.fabrics)) {
        tempState.productForm.fabrics = [];
      }
      
      // Ensure availablePrintTypes array exists
      if (!Array.isArray(tempState.productForm.availablePrintTypes)) {
        tempState.productForm.availablePrintTypes = [];
      }
      
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
    
    function handleSaveProductMain() {
        // This is the "main" save button, not the modal
        const product = appState.editingProduct;
        if (!product.name) return alert('Product name is required.');
        if (!product.sides || product.sides.length === 0) return alert('Product must have at least one side.');
        
        // Save to WooCommerce
        wooCommerceAPI.saveProduct(product).then(response => {
            if (response.success) {
                const savedProduct = response.data;
                
                // Update the product in appState with the saved data
                const index = appState.products.findIndex(p => p.id === product.id);
                if (index > -1) {
                    appState.products[index] = savedProduct;
                } else {
                    appState.products.push(savedProduct);
                }
                
                appState.editingProduct = null;
                resetCanvasState();
                renderApp();
                alert('Product saved successfully!');
            } else {
                alert('Failed to save product: ' + (response.data?.message || 'Unknown error'));
            }
        }).catch(error => {
            console.error('Save error:', error);
            alert('Failed to save product. Check console for details.');
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
    
    async function handleSaveSide() {
        const form = tempState.sideForm;
        if (!form.name) return alert('Side name is required.');
        
        // Check if we need to upload a new image (blob URL means local file)
        if (form.imageUrl && form.imageUrl.startsWith('blob:')) {
            const fileInput = document.getElementById('side-imageUpload');
            if (fileInput && fileInput.files && fileInput.files[0]) {
                const uploadResult = await uploadSideImage(fileInput.files[0]);
                if (uploadResult && uploadResult.url) {
                    // Replace blob URL with actual uploaded URL
                    form.imageUrl = uploadResult.url;
                    // Revoke blob URL
                    URL.revokeObjectURL(tempState.sideForm.imageUrl);
                } else {
                    alert('Failed to upload image. Side will be saved without image.');
                    form.imageUrl = '';
                }
            }
        }
        
        if (tempState.editingSideId) {
            // Update
            const index = appState.editingProduct.sides.findIndex(s => s.id === tempState.editingSideId);
            if (index > -1) {
                // Important: Preserve printAreas and restrictionAreas from the *original* side
                const oldSide = appState.editingProduct.sides[index];
                appState.editingProduct.sides[index] = { 
                    ...oldSide, // This keeps printAreas, restrictionAreas
                    ...form      // This updates name, imageUrl
                };
            }
        } else {
            // Create
            appState.editingProduct.sides.push({ ...form, id: generateId('side'), printAreas: [], restrictionAreas: [] });
            canvasState.selectedSideIndex = appState.editingProduct.sides.length - 1; // Select new side
        }
        
        closeModal();
        resetCanvasState(false);
        renderApp();
    }
    
    // ---------- Handlers (Canvas Sidebar) ----------
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

    // ---------- Modal Helpers ----------
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

         // --- Toast ---
         function showToast(message, type = 'info') {
             // Basic alert fallback
             alert(`${type.toUpperCase()}: ${message}`);
         }


         // --- Start the app ---
         function initAdminPrintStudio() {
             if (typeof AakaariPS === 'undefined' || !AakaariPS.ajax_url || !AakaariPS.nonce) { // Check nonce too
                 APP_ROOT.innerHTML = '<div class="ps-card"><h3 style="color:red;">Error: Print Studio Core Data Missing</h3><p>Could not load AJAX configuration (AakaariPS object). Please ensure PHP localization is correct.</p></div>';
                 console.error("AakaariPS object is missing or incomplete:", window.AakaariPS);
                 return;
             }
             // Load initial data from WooCommerce
             initWithWooCommerceData();
         }

         initAdminPrintStudio();

         window.AakaariPrintStudio = { appState, tempState, canvasState, api: wooCommerceAPI };

    }); // End DOMContentLoaded
})();