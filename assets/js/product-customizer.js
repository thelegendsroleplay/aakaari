/* product-customizer.js
   PNG-only image upload + preview + canvas + add-to-cart AJAX
   DOM-safe: queries DOM only after DOMContentLoaded
*/

(function () {
  'use strict';

  // Localized variables from PHP (via wp_localize_script)
  const PRODUCTS = (window.AAKAARI_PRODUCTS && Array.isArray(window.AAKAARI_PRODUCTS) ? window.AAKAARI_PRODUCTS : []);
  const SETTINGS = window.AAKAARI_SETTINGS || {
    ajax_url: '/wp-admin/admin-ajax.php',
    nonce: ''
  };

  // Debug of localized objects (kept)
  console.group('Aakaari (JS) Localized Data');
  console.log('AAKAARI_PRODUCTS ->', window.AAKAARI_PRODUCTS);
  console.log('AAKAARI_PRINT_TYPES ->', window.AAKAARI_PRINT_TYPES);
  console.log('AAKAARI_SETTINGS ->', window.AAKAARI_SETTINGS);
  console.groupEnd();

  // ---- Delay DOM queries until DOM is ready ----
  document.addEventListener('DOMContentLoaded', function () {

    // Basic DOM refs (must match markup in single-product.php)
    const fileInput = document.getElementById('file-upload-input');
    const addImageBtn = document.getElementById('add-image-btn');
    const designListContainer = document.getElementById('design-list-container');
    const designListPlaceholder = document.getElementById('design-list-placeholder');
    const interactiveCanvas = document.getElementById('interactive-canvas');
    const canvasContainer = document.getElementById('canvas-container');
    const addToCartBtn = document.getElementById('add-to-cart-btn');
    const productBasePriceEl = document.getElementById('product-base-price');
    const productBasePriceStrike = document.getElementById('product-base-price-strikethrough');
    const totalPriceEl = document.getElementById('total-price');
    const printCostValue = document.getElementById('print-cost-value');
    const printCostSummary = document.getElementById('print-cost-summary');
    const productNameEl = document.getElementById('customizer-product-name');
    const productDescEl = document.getElementById('customizer-product-desc');

    // Safeguard: interactive canvas must exist — if not, log helpful error and continue safely.
    if (!interactiveCanvas) {
      console.error('Aakaari: interactive-canvas not found. Make sure your template contains <canvas id="interactive-canvas"> (or update the ID in JS).');
      // do not abort the whole script; return to prevent exceptions that depend on canvas.
      return;
    }

    // Canvas setup
    const ctx = interactiveCanvas.getContext('2d');
    const canvasW = interactiveCanvas.width;
    const canvasH = interactiveCanvas.height;

    // In-memory state
    const state = {
      product: (PRODUCTS.length ? PRODUCTS[0] : null),
      designs: [], // each: { id, type:'image', dataURL, x,y,scale,rotation, fileIndex }
      files: [],   // actual File objects to send to server; index corresponds to fileIndex in designs
      activeDesignId: null,
      printCost: 0,
    };

    /* -------------- All helper functions and full logic here --------------
       (You can paste the rest of your existing code here exactly as before:
        - uid(), renderDesignList(), renderCanvas(), recalcTotal(), isValidPng(),
        - handleFileInputChange(), bindEvents(), buildDesignsPayload(), handleAddToCart(), init()
       )
       The important change is that all DOM lookups and initial calls (init()) must be inside this DOMContentLoaded handler.
    */

    // -- helpers and implementation (copy your previously working logic below) --

    function uid(prefix = 'd') {
      return prefix + '_' + Math.random().toString(36).substr(2, 9);
    }

    function initProductInfo() {
      if (state.product) {
        productNameEl && (productNameEl.textContent = state.product.name || '');
        productDescEl && (productDescEl.textContent = state.product.description || '');
        const basePrice = (state.product.basePrice != null ? state.product.basePrice : 0.00);
        const salePrice = state.product.salePrice;
        if (salePrice) {
          productBasePriceStrike && (productBasePriceStrike.textContent = '$' + Number(basePrice).toFixed(2));
          productBasePriceStrike && productBasePriceStrike.classList.remove('hidden');
          productBasePriceEl && (productBasePriceEl.textContent = '$' + Number(salePrice).toFixed(2));
        } else {
          productBasePriceStrike && productBasePriceStrike.classList.add('hidden');
          productBasePriceEl && (productBasePriceEl.textContent = '$' + Number(basePrice).toFixed(2));
        }
        recalcTotal();
      } else {
        console.warn('Aakaari: no product data localized. Make sure AAKAARI_PRODUCTS is present.');
      }
    }

    // ... paste the rest of the functions from previous full script here ...
    // for brevity, re-including the crucial functions is expected. Example continuation:

    function renderDesignList() {
      designListContainer.innerHTML = '';
      if (!state.designs.length) {
        designListPlaceholder && designListPlaceholder.classList.remove('hidden');
        addToCartBtn.disabled = true;
        return;
      }
      designListPlaceholder && designListPlaceholder.classList.add('hidden');
      addToCartBtn.disabled = false;

      state.designs.forEach(d => {
        const item = document.createElement('div');
        item.className = 'design-item';
        if (state.activeDesignId === d.id) item.classList.add('selected');

        const left = document.createElement('div');
        left.style.display = 'flex';
        left.style.alignItems = 'center';
        left.style.gap = '8px';

        const thumb = document.createElement('img');
        thumb.src = d.dataURL;
        thumb.style.width = '48px';
        thumb.style.height = '48px';
        thumb.style.objectFit = 'cover';
        thumb.style.borderRadius = '6px';
        left.appendChild(thumb);

        const meta = document.createElement('div');
        meta.style.fontSize = '13px';
        meta.innerHTML = `<div style="font-weight:600">${d.type === 'image' ? 'Image' : 'Text'}</div>
                          <div style="color:#6B7280;font-size:12px">ID: ${d.id}</div>`;

        left.appendChild(meta);

        const actions = document.createElement('div');
        actions.style.display = 'flex';
        actions.style.gap = '8px';

        const selectBtn = document.createElement('button');
        selectBtn.textContent = 'Select';
        selectBtn.className = 'side-btn';
        selectBtn.style.padding = '6px 8px';
        selectBtn.addEventListener('click', () => {
          state.activeDesignId = d.id;
          renderDesignList();
        });

        const removeBtn = document.createElement('button');
        removeBtn.textContent = 'Remove';
        removeBtn.className = 'side-btn';
        removeBtn.style.padding = '6px 8px';
        removeBtn.addEventListener('click', () => {
          const idx = state.designs.findIndex(x => x.id === d.id);
          if (idx >= 0) {
            const fileIndex = state.designs[idx].fileIndex;
            state.designs.splice(idx, 1);
            if (typeof fileIndex === 'number' && state.files[fileIndex]) {
              state.files[fileIndex] = null;
            }
            if (state.activeDesignId === d.id) state.activeDesignId = null;
            renderDesignList();
            renderCanvas();
            recalcTotal();
          }
        });

        actions.appendChild(selectBtn);
        actions.appendChild(removeBtn);

        item.appendChild(left);
        item.appendChild(actions);
        item.style.display = 'flex';
        item.style.justifyContent = 'space-between';
        item.style.alignItems = 'center';
        item.style.padding = '8px';
        item.style.border = '1px solid #F1F5F9';
        item.style.borderRadius = '6px';
        item.style.marginBottom = '8px';

        designListContainer.appendChild(item);
      });
    }

    function renderCanvas() {
      ctx.clearRect(0, 0, canvasW, canvasH);
      ctx.fillStyle = '#fff';
      ctx.fillRect(0, 0, canvasW, canvasH);

      state.designs.forEach(d => {
        if (d.type === 'image') {
          if (!d._imgObj) {
            const img = new Image();
            img.src = d.dataURL;
            img.onload = function () {
              d._imgObj = img;
              requestAnimationFrame(renderCanvas);
            };
            img.onerror = function () {
              console.error('Aakaari: failed to load uploaded image for design', d.id);
            };
          } else {
            const img = d._imgObj;
            const w = img.width * (d.scale || 1);
            const h = img.height * (d.scale || 1);
            const x = (d.x != null ? d.x : (canvasW - w) / 2);
            const y = (d.y != null ? d.y : (canvasH - h) / 2);
            ctx.save();
            ctx.translate(x + w / 2, y + h / 2);
            ctx.rotate((d.rotation || 0) * Math.PI / 180);
            ctx.drawImage(img, -w / 2, -h / 2, w, h);
            ctx.restore();
          }
        }
      });
    }

    function recalcTotal() {
      const base = (state.product && state.product.basePrice != null) ? Number(state.product.basePrice) : 0;
      const perImage = 5.00;
      const imageCount = state.designs.filter(d => d.type === 'image').length;
      const printCost = perImage * imageCount;
      state.printCost = printCost;
      printCostValue && (printCostValue.textContent = '+$' + printCost.toFixed(2));
      if (imageCount > 0) {
        printCostSummary && (printCostSummary.style.display = 'flex');
      } else {
        printCostSummary && (printCostSummary.style.display = 'none');
      }
      totalPriceEl && (totalPriceEl.textContent = '$' + (base + printCost).toFixed(2));
    }

    function isValidPng(file) {
      if (!file) return false;
      const mimeOk = file.type === 'image/png';
      const extOk = /\.png$/i.test(file.name);
      return mimeOk && extOk;
    }

    function handleFileInputChange(e) {
      const files = e.target.files;
      if (!files || !files.length) return;
      const file = files[0];

      if (!isValidPng(file)) {
        alert('Only PNG files are allowed. Please upload a .png file.');
        fileInput.value = '';
        return;
      }

      if (SETTINGS.max_upload_size && file.size > SETTINGS.max_upload_size) {
        alert('File is too large. Max upload size: ' + Math.round(SETTINGS.max_upload_size / 1024 / 1024) + ' MB');
        fileInput.value = '';
        return;
      }

      const reader = new FileReader();
      reader.onload = function (ev) {
        const dataURL = ev.target.result;
        const designId = uid('img');
        const fileIndex = state.files.length;
        state.files.push(file);
        const d = {
          id: designId,
          type: 'image',
          dataURL: dataURL,
          x: null,
          y: null,
          scale: 0.5,
          rotation: 0,
          fileIndex: fileIndex
        };
        state.designs.push(d);
        state.activeDesignId = designId;
        renderDesignList();
        renderCanvas();
        recalcTotal();
        fileInput.value = '';
      };
      reader.onerror = function () {
        alert('Failed to read file.');
        fileInput.value = '';
      };
      reader.readAsDataURL(file);
    }

    function bindEvents() {
      if (addImageBtn && fileInput) {
        addImageBtn.addEventListener('click', (ev) => {
          ev.preventDefault();
          fileInput.click();
        });
        fileInput.addEventListener('change', handleFileInputChange);
      }

      let dragging = false;
      let dragOffset = { x: 0, y: 0 };
      let draggedDesign = null;

      function findDesignAtPoint(px, py) {
        for (let i = state.designs.length - 1; i >= 0; i--) {
          const d = state.designs[i];
          if (d._imgObj) {
            const img = d._imgObj;
            const w = img.width * (d.scale || 1);
            const h = img.height * (d.scale || 1);
            const x = (d.x != null ? d.x : (canvasW - w) / 2);
            const y = (d.y != null ? d.y : (canvasH - h) / 2);
            if (px >= x && px <= x + w && py >= y && py <= y + h) {
              return d;
            }
          }
        }
        return null;
      }

      canvasContainer.addEventListener('pointerdown', function (ev) {
        const rect = interactiveCanvas.getBoundingClientRect();
        const px = ev.clientX - rect.left;
        const py = ev.clientY - rect.top;
        const d = findDesignAtPoint(px, py);
        if (d) {
          dragging = true;
          draggedDesign = d;
          state.activeDesignId = d.id;
          const img = d._imgObj;
          const w = img.width * (d.scale || 1);
          const h = img.height * (d.scale || 1);
          const x = (d.x != null ? d.x : (canvasW - w) / 2);
          const y = (d.y != null ? d.y : (canvasH - h) / 2);
          dragOffset.x = px - x;
          dragOffset.y = py - y;
          renderDesignList();
        }
      });

      window.addEventListener('pointermove', function (ev) {
        if (!dragging || !draggedDesign) return;
        const rect = interactiveCanvas.getBoundingClientRect();
        const px = ev.clientX - rect.left;
        const py = ev.clientY - rect.top;
        draggedDesign.x = px - dragOffset.x;
        draggedDesign.y = py - dragOffset.y;
        requestAnimationFrame(renderCanvas);
      });

      window.addEventListener('pointerup', function () {
        if (dragging) {
          dragging = false;
          draggedDesign = null;
        }
      });

      addToCartBtn && addToCartBtn.addEventListener('click', function (ev) {
        ev.preventDefault();
        handleAddToCart();
      });
    }

    function buildDesignsPayload() {
      return state.designs.map(d => {
        return {
          id: d.id,
          type: d.type,
          dataURL: d.dataURL,
          x: d.x,
          y: d.y,
          scale: d.scale,
          rotation: d.rotation,
          fileIndex: d.fileIndex
        };
      });
    }

    async function handleAddToCart() {
      if (!state.product || !state.product.id) {
        alert('Product not found.');
        return;
      }
      if (!state.designs.length) {
        alert('Please add at least one design.');
        return;
      }
      if (!SETTINGS.ajax_url) {
        alert('AJAX endpoint not configured.');
        return;
      }
      const fd = new FormData();
      fd.append('action', 'aakaari_add_to_cart');
      fd.append('security', SETTINGS.nonce || '');
      fd.append('product_id', state.product.id);
      const designsPayload = buildDesignsPayload();
      fd.append('designs', JSON.stringify(designsPayload));
      state.files.forEach((fileObj, idx) => {
        if (fileObj) {
          fd.append('files[]', fileObj, fileObj.name);
        }
      });

      addToCartBtn.disabled = true;
      addToCartBtn.textContent = 'Adding...';

      try {
        const resp = await fetch(SETTINGS.ajax_url, {
          method: 'POST',
          body: fd,
          credentials: 'same-origin'
        });
        const json = await resp.json();
        if (!json) throw new Error('No JSON response');

        if (json.success) {
          const data = json.data || {};
          console.log('Aakaari add-to-cart success', data);
          // redirect to cart
          window.location.href = (typeof wc_cart_url !== 'undefined') ? wc_cart_url : '/cart/';
        } else {
          console.error('Aakaari add-to-cart failed', json);
          alert((json.data && json.data.message) || 'Add to cart failed. Check console for details.');
        }
      } catch (err) {
        console.error('Aakaari add-to-cart exception', err);
        alert('Network error. See console for details.');
      } finally {
        addToCartBtn.disabled = false;
        addToCartBtn.textContent = 'Add to Cart';
      }
    }

    // Initialize
    function init() {
      initProductInfo();
      bindEvents();
      renderDesignList();
      renderCanvas();
      recalcTotal();
      console.log('Aakaari product customizer initialized', state);
    }

    // Start the app
    init();

  }); // end DOMContentLoaded

})();
