/* assets/print-studio/print-studio.js
   - Updated renderSideModal() to use <input type="file">
   - Updated delegatedChange() to handle file uploads
*/
(function () {
  'use strict';

  // Wait for DOM and ensure container exists
  document.addEventListener('DOMContentLoaded', () => {
    const APP_ROOT = document.getElementById('custom-print-studio-app');
    if (!APP_ROOT) return; // only run where the container exists

    // --- App state (from original) ---
    let appState = {
      activeTab: 'products',
      editingProduct: null, // This will be a DEEP COPY of the product being edited
      products: [
        {
          id: 'prod-1',
          name: 'Classic Unisex T-Shirt',
          description: 'A comfortable and stylish t-shirt for everyday wear.',
          basePrice: 15.00,
          salePrice: 12.50,
          category: 'T-Shirts',
          woocommerceId: 'wc-101',
          isActive: true,
          colors: ['#000000', '#FFFFFF', '#FF0000'],
          availablePrintTypes: ['print-1'],
          sides: [
            {
              id: 'side-1',
              name: 'Front',
              imageUrl: 'https://i.imgur.com/2s4P2c5.png',
              printAreas: [
                { id: 'area-1', name: 'Center Chest', x: 150, y: 100, width: 200, height: 300 }
              ],
              restrictionAreas: [
                { id: 'rest-1', name: 'Collar', x: 180, y: 20, width: 140, height: 50 }
              ],
            },
            {
              id: 'side-2',
              name: 'Back',
              imageUrl: 'https://i.imgur.com/Ph6p2tq.png',
              printAreas: [
                { id: 'area-2', name: 'Full Back', x: 100, y: 100, width: 300, height: 400 }
              ],
              restrictionAreas: [],
            }
          ]
        },
        {
          id: 'prod-2',
          name: 'Premium Hoodie',
          description: 'Heavyweight hoodie for maximum comfort.',
          basePrice: 35.00,
          salePrice: null,
          category: 'Hoodies',
          woocommerceId: 'wc-102',
          isActive: false,
          colors: ['#000000', '#3B82F6'],
          availablePrintTypes: ['print-1', 'print-2'],
          sides: [
            {
              id: 'side-3',
              name: 'Front',
              imageUrl: '',
              printAreas: [
                { id: 'area-3', name: 'Left Chest', x: 80, y: 120, width: 100, height: 100 }
              ],
              restrictionAreas: [],
            }
          ]
        }
      ],
      fabrics: [
        { id: 'fab-1', name: '100% Cotton', description: 'Standard soft cotton, 180 GSM.', price: 0 },
        { id: 'fab-2', name: 'Premium Tri-Blend', description: 'A soft, durable blend of three fabrics.', price: 2.50 }
      ],
      printTypes: [
        { id: 'print-1', name: 'DTF (Direct to Film)', description: 'Vibrant colors, durable finish.', pricingModel: 'per-inch', price: 0.15 },
        { id: 'print-2', name: 'Embroidery', description: 'Stitched design for a premium look.', pricingModel: 'fixed', price: 8.00 }
      ],
      categories: [{ id: 'cat-1', name: 'T-Shirts' }, { id: 'cat-2', name: 'Hoodies' }, { id: 'cat-3', name: 'Mugs' }],
      wooCommerceColors: [
        { name: 'Black', hex: '#000000' }, { name: 'White', hex: '#FFFFFF' }, { name: 'Red', hex: '#FF0000' },
        { name: 'Blue', hex: '#3B82F6' }, { name: 'Green', hex: '#22C55E' }, { name: 'Heather Grey', hex: '#E5E7EB' }
      ]
    };

    // --- Temporary / Form state ---
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

    // ---------- Utils ----------
    function generateId(prefix = 'id') {
      return `${prefix}-${Date.now()}-${Math.random().toString(36).substring(2, 9)}`;
    }

    function escapeHtml(str) {
      if (!str) return '';
      return String(str).replace(/[&<>"']/g, m => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[m]));
    }

    // ---------- Main Rendering ----------
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
            <div class="ps-color-picker">
              ${appState.wooCommerceColors.map(c => `
                <label class="ps-color-chip" style="--chip-color: ${c.hex};" title="${escapeHtml(c.name)}">
                  <input type="checkbox" value="${c.hex}" data-form="productForm" data-prop="colors"
                         ${form.colors.includes(c.hex) ? 'checked' : ''}>
                  <span class="ps-color-chip-check"><i data-lucide="check" class="ps-icon-small"></i></span>
                </label>
              `).join('')}
            </div>
          </div>
          <div class="ps-form-group">
            <label class="ps-label">Available Print Types</label>
            <div class="ps-form-column">
              ${appState.printTypes.map(pt => `
                <label class="ps-checkbox-label">
                  <input type="checkbox" value="${pt.id}" data-form="productForm" data-prop="availablePrintTypes"
                         ${form.availablePrintTypes.includes(pt.id) ? 'checked' : ''}>
                  <span>${escapeHtml(pt.name)}</span>
                </label>
              `).join('')}
            </div>
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
    
    function handleSaveCategory() {
      const form = tempState.categoryForm;
      if (!form.name) return alert('Category name is required.');
      
      if (tempState.editingCategoryId) {
        const index = appState.categories.findIndex(c => c.id === tempState.editingCategoryId);
        if (index > -1) appState.categories[index] = { ...form };
      } else {
        appState.categories.push({ ...form, id: generateId('cat') });
      }
      closeModal();
      renderApp();
    }
    
    function handleDelete(type, id) {
        const typeMap = {
            'fabric': { stateKey: 'fabrics', name: 'Fabric' },
            'printType': { stateKey: 'printTypes', name: 'Print Type' },
            'category': { stateKey: 'categories', name: 'Category' },
        };
        const config = typeMap[type];
        if (!config) return;

        if (confirm(`Are you sure you want to delete this ${config.name}?`)) {
            appState[config.stateKey] = appState[config.stateKey].filter(item => item.id !== id);
            renderApp();
        }
    }

    // ---------- Handlers (Editor) ----------
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
    
    function handleSaveProductMain() {
        // This is the "main" save button, not the modal
        const product = appState.editingProduct;
        if (!product.name) return alert('Product name is required.');
        if (!product.sides || product.sides.length === 0) return alert('Product must have at least one side.');
        
        const index = appState.products.findIndex(p => p.id === product.id);
        if (index > -1) {
            // Save the deep copy back to the main state
            appState.products[index] = JSON.parse(JSON.stringify(product));
        }
        
        appState.editingProduct = null;
        resetCanvasState();
        renderApp();
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
    
    function handleSaveSide() {
        const form = tempState.sideForm;
        if (!form.name) return alert('Side name is required.');
        
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

    // ---------- kickoff ----------
    renderApp();
    
    // Expose some small API for debugging if needed:
    window.AakaariPrintStudio = { appState, tempState, canvasState };
  });
})();