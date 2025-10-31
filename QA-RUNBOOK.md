# QA Runbook: Product Customizer v2.0

This runbook provides comprehensive testing procedures for the Product Customizer system.

## Testing Environment Setup

### Prerequisites
- WordPress with WooCommerce installed
- Aakaari theme active
- At least 1 variable product with color variations
- At least 1 simple product
- Test user accounts: Customer, Shop Manager, Administrator

### Test Data Requirements
- Product with mockups configured for at least 3 color variations
- Product with print area configured (relative coordinates 0-1)
- Test design files: PNG/JPG images (various sizes: small, medium, large)
- Browser DevTools open to monitor console errors

---

## Acceptance Criteria Tests

### AC-1: Design Constraint Enforcement (Client-Side)

**Requirement:** When a user places/resizes/rotates a design on the canvas, the system must prevent it from exceeding print-area boundaries in real-time.

**Test Steps:**

1. **Setup:**
   - [ ] Navigate to a product with customization enabled
   - [ ] Select a color variation
   - [ ] Verify mockup loads
   - [ ] Verify print area overlay is visible (semi-transparent box)

2. **Upload Design:**
   - [ ] Click "Upload Design" button
   - [ ] Select a PNG image (recommended: 500x500px)
   - [ ] Verify design appears centered on canvas
   - [ ] Verify design is within print area boundaries

3. **Test Dragging Constraints:**
   - [ ] Drag design towards top-left corner beyond print area
   - [ ] **Expected:** Design stops at print area boundary
   - [ ] **Actual:** _____
   - [ ] Drag design towards bottom-right corner beyond print area
   - [ ] **Expected:** Design stops at print area boundary
   - [ ] **Actual:** _____
   - [ ] Drag design to each edge (top, right, bottom, left)
   - [ ] **Expected:** Design cannot exceed boundaries on any edge
   - [ ] **Actual:** _____

4. **Test Scaling Constraints:**
   - [ ] Select design on canvas
   - [ ] Grab corner resize handle
   - [ ] Scale up until design would exceed print area
   - [ ] **Expected:** Design stops scaling when boundary is reached
   - [ ] **Actual:** _____
   - [ ] Verify design maintains aspect ratio during scaling
   - [ ] **Expected:** No distortion, proportional scaling
   - [ ] **Actual:** _____

5. **Test Rotation Constraints:**
   - [ ] Select design on canvas
   - [ ] Rotate design using rotation handle
   - [ ] Rotate to 45 degrees
   - [ ] **Expected:** Rotated bounding box stays within print area
   - [ ] **Actual:** _____
   - [ ] Rotate to 90, 180, 270 degrees
   - [ ] **Expected:** Design constrained at all angles
   - [ ] **Actual:** _____

6. **Test Edge Cases:**
   - [ ] Upload large image (larger than print area)
   - [ ] **Expected:** Auto-scaled to fit within print area
   - [ ] **Actual:** _____
   - [ ] Upload small image (much smaller than print area)
   - [ ] **Expected:** Placed centered, can be scaled up to boundary
   - [ ] **Actual:** _____

**Pass Criteria:**
- ✅ Design cannot be dragged outside print area on any edge
- ✅ Design cannot be scaled beyond print area boundaries
- ✅ Rotated designs respect bounding box constraints
- ✅ No JavaScript errors in console
- ✅ Print area overlay remains visible and accurate

**Screenshot Placeholders:**
- `screenshots/ac1-01-print-area-visible.png`
- `screenshots/ac1-02-drag-constraint-top-left.png`
- `screenshots/ac1-03-drag-constraint-bottom-right.png`
- `screenshots/ac1-04-scale-constraint.png`
- `screenshots/ac1-05-rotation-constraint.png`

---

### AC-2: Server-Side Validation Blocking

**Requirement:** When a user submits an invalid design (via tampered client code), the server must reject the add-to-cart request and return an error message.

**Test Steps:**

1. **Setup Normal Submission (Control):**
   - [ ] Navigate to customizable product
   - [ ] Upload and position design within print area
   - [ ] Select print type and fabric
   - [ ] Click "Add to Cart"
   - [ ] **Expected:** Success message, item added to cart
   - [ ] **Actual:** _____

2. **Tamper with Design Data (Manual):**
   - [ ] Open browser DevTools → Console tab
   - [ ] Place design within print area normally
   - [ ] Before clicking "Add to Cart", run this code:
   ```javascript
   // Tamper with design bounds to be outside print area
   window.customizer = window.customizer || {};
   window.customizer.overrideBounds = {
       x: -0.2,  // Outside print area (negative)
       y: -0.1,
       width: 1.5,  // Exceeds print area (>1.0)
       height: 1.3
   };
   ```
   - [ ] Click "Add to Cart"
   - [ ] **Expected:** Error message "Your design extends outside the allowed print area"
   - [ ] **Actual:** _____
   - [ ] **Expected:** Item NOT added to cart
   - [ ] **Actual:** _____

3. **Test Direct POST Request (Advanced):**
   - [ ] Use tool like Postman or curl
   - [ ] Send POST to `/wp-admin/admin-ajax.php` with:
   ```
   action: aakaari_add_customized_to_cart
   product_id: [PRODUCT_ID]
   custom_design[applied_transform][x]: -0.5
   custom_design[applied_transform][y]: -0.5
   custom_design[applied_transform][scale]: 2.0
   ```
   - [ ] **Expected:** 400 error response with validation message
   - [ ] **Actual:** _____

4. **Test Missing Required Fields:**
   - [ ] Tamper to remove `print_area_meta` from design data
   - [ ] Submit to cart
   - [ ] **Expected:** Error "Print area metadata is missing"
   - [ ] **Actual:** _____

5. **Test Invalid Attachment IDs:**
   - [ ] Tamper to set `attachment_ids: [99999]` (non-existent)
   - [ ] Submit to cart
   - [ ] **Expected:** Error "Invalid attachment"
   - [ ] **Actual:** _____

6. **Verify Standard WooCommerce Add-to-Cart:**
   - [ ] Test validation hook applies to both AJAX and standard form submission
   - [ ] Disable JavaScript in browser
   - [ ] Try to add customized product (should fail gracefully)
   - [ ] **Expected:** Error message displayed
   - [ ] **Actual:** _____

**Pass Criteria:**
- ✅ Tampered design data is rejected by server
- ✅ Appropriate error messages are displayed to user
- ✅ Invalid items are NOT added to cart
- ✅ Validation applies to both AJAX and standard submissions
- ✅ PHP errors not exposed to frontend (proper error handling)

**Screenshot Placeholders:**
- `screenshots/ac2-01-valid-submission-success.png`
- `screenshots/ac2-02-tampered-data-error.png`
- `screenshots/ac2-03-missing-fields-error.png`
- `screenshots/ac2-04-network-tab-400-error.png`

---

### AC-3: Cart Data Persistence

**Requirement:** Custom design data (including attachment IDs, transform, print/fabric types) must survive the cart → checkout → order flow without corruption.

**Test Steps:**

1. **Add Customized Product to Cart:**
   - [ ] Navigate to customizable product
   - [ ] Upload design image
   - [ ] Select: Print Type = "Direct", Fabric = "Polyester", Color = "White"
   - [ ] Position and scale design
   - [ ] Note transform values (check browser console if needed)
   - [ ] Click "Add to Cart"
   - [ ] **Expected:** Success message
   - [ ] **Actual:** _____

2. **Verify Cart Display:**
   - [ ] Navigate to Cart page
   - [ ] Locate customized item
   - [ ] **Expected:** Custom thumbnail showing design preview
   - [ ] **Actual:** _____
   - [ ] **Expected:** Meta data displayed: Print Type, Fabric, Color
   - [ ] **Actual:** _____
   - [ ] Check page source / inspect element
   - [ ] **Expected:** Attachment IDs stored (not base64)
   - [ ] **Actual:** _____

3. **Test Cart Persistence Across Sessions:**
   - [ ] Remain logged out (guest user)
   - [ ] Add customized product to cart
   - [ ] Close browser completely
   - [ ] Reopen browser, return to site
   - [ ] Navigate to Cart
   - [ ] **Expected:** Customized item still in cart with design intact
   - [ ] **Actual:** _____

4. **Proceed to Checkout:**
   - [ ] Click "Proceed to Checkout"
   - [ ] Fill in billing details
   - [ ] **Expected:** Order summary shows custom thumbnail and meta
   - [ ] **Actual:** _____
   - [ ] Complete order (test payment method)

5. **Verify Order Confirmation:**
   - [ ] Check "Order Received" page
   - [ ] **Expected:** Custom design preview image displayed
   - [ ] **Actual:** _____
   - [ ] **Expected:** Print Type, Fabric, Color listed
   - [ ] **Actual:** _____

6. **Verify Order in Admin:**
   - [ ] Login as Administrator
   - [ ] Navigate to WooCommerce → Orders
   - [ ] Open the test order
   - [ ] **Expected:** "Customization" column visible with thumbnail
   - [ ] **Actual:** _____
   - [ ] Click order item
   - [ ] **Expected:** Full design data visible in item meta
   - [ ] **Actual:** _____
   - [ ] Check meta: `_custom_design`, `_custom_design_attachments`
   - [ ] **Expected:** All data present and structured correctly
   - [ ] **Actual:** _____

7. **Verify Email Notification:**
   - [ ] Check email inbox for order confirmation
   - [ ] **Expected:** Email contains design preview image
   - [ ] **Actual:** _____
   - [ ] **Expected:** Print Type, Fabric, Color details included
   - [ ] **Actual:** _____

8. **Test Multiple Customized Items:**
   - [ ] Add Product A with Design 1
   - [ ] Add Product A with Design 2 (different design)
   - [ ] **Expected:** Two separate cart line items
   - [ ] **Actual:** _____
   - [ ] **Expected:** Each has unique `unique_key` in cart data
   - [ ] **Actual:** _____
   - [ ] Complete order
   - [ ] **Expected:** Both items preserved with individual designs
   - [ ] **Actual:** _____

**Pass Criteria:**
- ✅ Design data persists from cart through order completion
- ✅ Attachment IDs preserved (files not re-encoded or lost)
- ✅ Transform values maintain accuracy
- ✅ Cart thumbnail displays correctly
- ✅ Order confirmation shows design preview
- ✅ Admin order view shows complete customization data
- ✅ Email includes design preview image
- ✅ Multiple customized items maintain separate identities

**Screenshot Placeholders:**
- `screenshots/ac3-01-cart-custom-thumbnail.png`
- `screenshots/ac3-02-cart-meta-display.png`
- `screenshots/ac3-03-checkout-order-summary.png`
- `screenshots/ac3-04-order-confirmation.png`
- `screenshots/ac3-05-admin-order-customization-column.png`
- `screenshots/ac3-06-admin-order-meta-data.png`
- `screenshots/ac3-07-email-design-preview.png`

---

### AC-4: Variation-Aware Mockup System

**Requirement:** When a user selects a color variation, the mockup image must update instantly to show the correct color, and the print area overlay must remain accurate.

**Test Steps:**

1. **Setup Product with Multiple Variations:**
   - [ ] Ensure test product has variations for colors: White, Black, Red
   - [ ] Admin: Configure mockups for each color variation
   - [ ] Admin: Set print area (use same area for all variations for testing)
   - [ ] Save product

2. **Test Initial Mockup Load:**
   - [ ] Navigate to product page (not logged in)
   - [ ] **Expected:** Default variation mockup loads immediately
   - [ ] **Actual:** _____
   - [ ] **Expected:** Print area overlay visible
   - [ ] **Actual:** _____

3. **Test Color Variation Switching:**
   - [ ] Click "White" color swatch
   - [ ] **Expected:** White mockup loads instantly (<500ms)
   - [ ] **Actual:** _____
   - [ ] **Expected:** Print area overlay remains correct
   - [ ] **Actual:** _____
   - [ ] Click "Black" color swatch
   - [ ] **Expected:** Black mockup loads instantly
   - [ ] **Actual:** _____
   - [ ] Click "Red" color swatch
   - [ ] **Expected:** Red mockup loads instantly
   - [ ] **Actual:** _____

4. **Test Design Persistence Across Variation Changes:**
   - [ ] Select "White" variation
   - [ ] Upload design and position it
   - [ ] Note design position
   - [ ] Switch to "Black" variation
   - [ ] **Expected:** Design remains at same position
   - [ ] **Actual:** _____
   - [ ] **Expected:** Design stays within print area
   - [ ] **Actual:** _____
   - [ ] Switch back to "White"
   - [ ] **Expected:** Design still at correct position
   - [ ] **Actual:** _____

5. **Test Missing Mockup Fallback:**
   - [ ] Create variation without mockup configured
   - [ ] Select that variation
   - [ ] **Expected:** Error message or placeholder mockup
   - [ ] **Actual:** _____
   - [ ] **Expected:** User cannot add to cart without mockup
   - [ ] **Actual:** _____

6. **Test Variation-Specific Print Areas (Advanced):**
   - [ ] Configure different print areas for White vs Black variations
   - [ ] Select White variation
   - [ ] Note print area position
   - [ ] Upload design
   - [ ] Switch to Black variation
   - [ ] **Expected:** Print area updates to new position
   - [ ] **Actual:** _____
   - [ ] **Expected:** Design constraint enforcement uses new print area
   - [ ] **Actual:** _____

7. **Test Cart with Multiple Variation Customizations:**
   - [ ] Customize White variation, add to cart
   - [ ] Customize Black variation, add to cart
   - [ ] Navigate to Cart
   - [ ] **Expected:** Two separate line items
   - [ ] **Actual:** _____
   - [ ] **Expected:** Each shows correct color thumbnail
   - [ ] **Actual:** _____

**Pass Criteria:**
- ✅ Mockup switches instantly on variation selection
- ✅ Print area overlay remains accurate after mockup change
- ✅ Design position maintained across variation changes
- ✅ Missing mockups handled gracefully
- ✅ Variation-specific print areas work correctly
- ✅ Multiple variation customizations maintain separate cart items
- ✅ No flickering or visual glitches during mockup swap

**Screenshot Placeholders:**
- `screenshots/ac4-01-white-variation-mockup.png`
- `screenshots/ac4-02-black-variation-mockup.png`
- `screenshots/ac4-03-red-variation-mockup.png`
- `screenshots/ac4-04-design-persists-variation-change.png`
- `screenshots/ac4-05-variation-specific-print-areas.png`

---

### AC-5: Admin Order Management

**Requirement:** Admin must see a thumbnail, download button, and key details (print type, fabric, transform) for every customized order item.

**Test Steps:**

1. **Create Test Order:**
   - [ ] Place order with customized product (as customer)
   - [ ] Complete checkout
   - [ ] Note Order ID

2. **Access Admin Order Edit Page:**
   - [ ] Login as Administrator
   - [ ] Navigate to WooCommerce → Orders
   - [ ] Click on test order
   - [ ] **Expected:** Order edit page loads
   - [ ] **Actual:** _____

3. **Verify Customization Column:**
   - [ ] Scroll to "Order Items" section
   - [ ] **Expected:** "Customization" column header visible
   - [ ] **Actual:** _____
   - [ ] Locate customized product row
   - [ ] **Expected:** Thumbnail image displayed in Customization column
   - [ ] **Actual:** _____
   - [ ] **Expected:** Thumbnail is clickable (opens full preview in new tab)
   - [ ] **Actual:** _____

4. **Verify Download Button:**
   - [ ] Locate "Download Files" button in Customization column
   - [ ] **Expected:** Button visible for customized item
   - [ ] **Actual:** _____
   - [ ] Click "Download Files" button
   - [ ] **Expected:** Download modal/link appears
   - [ ] **Actual:** _____
   - [ ] **Expected:** List of design files (PNGs) with download links
   - [ ] **Actual:** _____
   - [ ] Click download link
   - [ ] **Expected:** File downloads successfully
   - [ ] **Actual:** _____
   - [ ] Verify downloaded file is correct design image
   - [ ] **Expected:** File matches original upload
   - [ ] **Actual:** _____

5. **Verify Design Details Display:**
   - [ ] Expand order item meta (if collapsed)
   - [ ] **Expected:** "Print Type" meta displayed
   - [ ] **Actual:** _____
   - [ ] **Expected:** "Fabric Type" meta displayed
   - [ ] **Actual:** _____
   - [ ] **Expected:** "Color" meta displayed
   - [ ] **Actual:** _____
   - [ ] Check if transform data is visible (may be hidden meta)
   - [ ] **Expected:** Transform accessible via meta fields
   - [ ] **Actual:** _____

6. **Test Non-Customized Item (Control):**
   - [ ] Add non-customized product to same order
   - [ ] Refresh order page
   - [ ] **Expected:** Customization column shows "—" for non-customized item
   - [ ] **Actual:** _____
   - [ ] **Expected:** No download button for non-customized item
   - [ ] **Actual:** _____

7. **Test Bulk Order View:**
   - [ ] Navigate to WooCommerce → Orders (list view)
   - [ ] **Expected:** Can identify customized orders (via icon or indicator)
   - [ ] **Actual:** _____
   - [ ] Click "Edit" on customized order
   - [ ] **Expected:** Customization data loads quickly (<2s)
   - [ ] **Actual:** _____

8. **Test Shop Manager Role:**
   - [ ] Login as Shop Manager
   - [ ] Access same test order
   - [ ] **Expected:** Can view thumbnails and design details
   - [ ] **Actual:** _____
   - [ ] **Expected:** Can download design files
   - [ ] **Actual:** _____

**Pass Criteria:**
- ✅ Thumbnail visible in admin order view
- ✅ Thumbnail clickable to view full preview
- ✅ Download button functional
- ✅ All design files downloadable
- ✅ Print Type, Fabric, Color displayed correctly
- ✅ Transform data accessible (even if in hidden meta)
- ✅ Non-customized items show placeholder
- ✅ Shop Manager role has appropriate access

**Screenshot Placeholders:**
- `screenshots/ac5-01-admin-order-customization-column.png`
- `screenshots/ac5-02-download-files-button.png`
- `screenshots/ac5-03-download-modal.png`
- `screenshots/ac5-04-design-meta-details.png`
- `screenshots/ac5-05-non-customized-item-placeholder.png`

---

### AC-6: Security and File Handling

**Requirement:** File uploads must use `wp_handle_upload()`, all AJAX endpoints must validate nonces, and capabilities must be checked. No base64 images should bloat the database.

**Test Steps:**

1. **Verify Secure Upload Handling:**
   - [ ] Open browser DevTools → Network tab
   - [ ] Upload design file on product page
   - [ ] Find AJAX request to `admin-ajax.php?action=aakaari_upload_design`
   - [ ] Check request payload
   - [ ] **Expected:** Multipart form data (not base64 JSON)
   - [ ] **Actual:** _____
   - [ ] Check response
   - [ ] **Expected:** Returns `attachment_id` (integer)
   - [ ] **Actual:** _____
   - [ ] Navigate to WP Admin → Media Library
   - [ ] **Expected:** Uploaded file visible in library
   - [ ] **Actual:** _____

2. **Test File Type Validation:**
   - [ ] Attempt to upload `.exe` file
   - [ ] **Expected:** Error "Invalid file type"
   - [ ] **Actual:** _____
   - [ ] Attempt to upload `.php` file
   - [ ] **Expected:** Error "Invalid file type"
   - [ ] **Actual:** _____
   - [ ] Attempt to upload `.svg` file (may be blocked by default WP settings)
   - [ ] **Expected:** Appropriate handling based on site settings
   - [ ] **Actual:** _____
   - [ ] Upload valid PNG file
   - [ ] **Expected:** Success
   - [ ] **Actual:** _____

3. **Test File Size Limits:**
   - [ ] Upload very large image (>10MB)
   - [ ] **Expected:** Error "File too large" or WP upload limit message
   - [ ] **Actual:** _____
   - [ ] Check PHP error log for file upload errors
   - [ ] **Expected:** No exposed errors to frontend
   - [ ] **Actual:** _____

4. **Verify Nonce Validation (Frontend AJAX):**
   - [ ] Open DevTools → Console
   - [ ] Try to trigger upload without nonce:
   ```javascript
   jQuery.post(ajaxurl, {
       action: 'aakaari_upload_design',
       // nonce intentionally omitted
   }, function(response) {
       console.log(response);
   });
   ```
   - [ ] **Expected:** 403 error or "Invalid nonce" message
   - [ ] **Actual:** _____

5. **Verify Nonce Validation (Admin AJAX):**
   - [ ] Login as Administrator
   - [ ] Navigate to product edit page
   - [ ] Open DevTools → Console
   - [ ] Try to save print area without nonce:
   ```javascript
   jQuery.post(ajaxurl, {
       action: 'aakaari_save_print_area',
       product_id: 123,
       print_area: '{"x":0.1,"y":0.1,"w":0.8,"h":0.8}'
       // nonce intentionally omitted
   }, function(response) {
       console.log(response);
   });
   ```
   - [ ] **Expected:** 403 error or "Invalid nonce" message
   - [ ] **Actual:** _____

6. **Test Capability Checks:**
   - [ ] Logout and test as unauthenticated user
   - [ ] Try to access admin AJAX endpoints directly (e.g., save print area)
   - [ ] **Expected:** 403 error "Unauthorized"
   - [ ] **Actual:** _____
   - [ ] Login as Subscriber role
   - [ ] Try to access admin endpoints
   - [ ] **Expected:** 403 error "Unauthorized"
   - [ ] **Actual:** _____
   - [ ] Login as Shop Manager
   - [ ] Try to save print area
   - [ ] **Expected:** Success (Shop Manager has `edit_products`)
   - [ ] **Actual:** _____

7. **Verify No Base64 in Database:**
   - [ ] Complete order with customized product
   - [ ] Access database (phpMyAdmin or WP-CLI)
   - [ ] Query order meta:
   ```sql
   SELECT meta_value FROM wp_postmeta
   WHERE post_id = [ORDER_ID]
   AND meta_key = '_custom_design';
   ```
   - [ ] Inspect `meta_value`
   - [ ] **Expected:** Contains `attachment_ids` array with integers
   - [ ] **Actual:** _____
   - [ ] **Expected:** Contains `preview_url` (URL string, not base64)
   - [ ] **Actual:** _____
   - [ ] **Expected:** No long base64 strings (no `data:image/png;base64,iVBOR...`)
   - [ ] **Actual:** _____

8. **Test Input Sanitization:**
   - [ ] Attempt SQL injection in product_id parameter:
   ```javascript
   jQuery.post(ajaxurl, {
       action: 'aakaari_upload_design',
       product_id: "123'; DROP TABLE wp_posts; --",
       nonce: '[VALID_NONCE]'
   });
   ```
   - [ ] **Expected:** Sanitized via `absint()`, no SQL executed
   - [ ] **Actual:** _____
   - [ ] Attempt XSS in design data:
   ```javascript
   custom_design.color = '<script>alert("XSS")</script>';
   ```
   - [ ] Add to cart
   - [ ] View cart page source
   - [ ] **Expected:** Script tags escaped/sanitized
   - [ ] **Actual:** _____

**Pass Criteria:**
- ✅ Files uploaded via `wp_handle_upload()` (visible in Media Library)
- ✅ Invalid file types rejected
- ✅ File size limits enforced
- ✅ All AJAX endpoints validate nonces
- ✅ Capability checks prevent unauthorized access
- ✅ No base64 images in database
- ✅ All inputs sanitized (no SQL injection or XSS)
- ✅ Error messages don't expose system information

**Screenshot Placeholders:**
- `screenshots/ac6-01-network-multipart-upload.png`
- `screenshots/ac6-02-media-library-uploaded-file.png`
- `screenshots/ac6-03-invalid-file-type-error.png`
- `screenshots/ac6-04-nonce-validation-403.png`
- `screenshots/ac6-05-database-attachment-ids.png`

---

## Manual Test Scenarios

### Scenario 1: End-to-End Customer Journey

**Objective:** Verify complete customization workflow from product page to order completion.

**Steps:**

1. Navigate to customizable product as guest user
2. Select "White" color variation
3. Upload custom PNG design (logo or text image)
4. Position and scale design within print area
5. Select Print Type: "Direct"
6. Select Fabric: "Polyester"
7. Click "Add to Cart"
8. Verify success message
9. Navigate to Cart
10. Verify custom thumbnail and meta display
11. Click "Proceed to Checkout"
12. Fill in billing/shipping details
13. Complete order (test payment method)
14. Verify order confirmation page shows design preview
15. Login as Administrator
16. Navigate to order in admin
17. Download design file
18. Verify downloaded file matches original upload

**Expected Results:**
- ✅ All steps complete without errors
- ✅ Design data persists through entire flow
- ✅ Admin can download original design file
- ✅ Order email includes design preview

**Actual Results:** _____

---

### Scenario 2: Multi-Variation Customization

**Objective:** Test adding multiple variations of the same product with different customizations.

**Steps:**

1. Navigate to variable product
2. Customize "White" variation with Design A
3. Add to cart
4. Return to product page
5. Customize "Black" variation with Design B
6. Add to cart
7. Navigate to Cart
8. Verify two separate line items with different thumbnails
9. Update quantity of White variation to 2
10. Verify each maintains separate customization
11. Complete checkout
12. Verify order confirmation shows both customizations

**Expected Results:**
- ✅ Two separate cart line items (not aggregated)
- ✅ Each maintains unique design
- ✅ Quantity updates don't corrupt customization data
- ✅ Order includes both designs

**Actual Results:** _____

---

### Scenario 3: Edge Case - Very Large Design

**Objective:** Test system behavior with oversized design file.

**Steps:**

1. Prepare large PNG file (e.g., 4000x4000px, 5MB)
2. Navigate to customizable product
3. Attempt to upload large file
4. If upload succeeds, verify:
   - Design auto-scales to fit print area
   - No canvas performance issues (lag, freezing)
   - File stored efficiently in media library
5. Complete order
6. Admin: Download file
7. Verify file integrity

**Expected Results:**
- ✅ Large file handled gracefully (either uploaded or appropriate error)
- ✅ Canvas remains responsive
- ✅ File download works in admin

**Actual Results:** _____

---

### Scenario 4: Constraint Bypass Attempt

**Objective:** Attempt to bypass client-side constraints using browser DevTools.

**Steps:**

1. Navigate to customizable product
2. Upload design
3. Open DevTools → Console
4. Manipulate canvas object to force design outside print area:
```javascript
const canvas = window.customizerCanvas;
const design = canvas.getActiveObject();
design.set({left: -100, top: -100});
canvas.renderAll();
```
5. Click "Add to Cart"
6. Verify server-side validation blocks submission
7. Verify error message displayed

**Expected Results:**
- ✅ Add to cart blocked by server validation
- ✅ Appropriate error message shown
- ✅ Item not added to cart

**Actual Results:** _____

---

### Scenario 5: Admin Workflow - Configure New Product

**Objective:** Test admin experience configuring customization for a new product.

**Steps:**

1. Login as Administrator
2. Navigate to Products → Add New
3. Create new variable product with color variations
4. Enable "Product Customization" checkbox
5. Upload mockup images for each variation:
   - White: white-tshirt.png
   - Black: black-tshirt.png
6. Configure print area using visual editor:
   - Set x: 0.25, y: 0.30, w: 0.50, h: 0.40
7. Save product
8. View product on frontend
9. Verify mockups load correctly
10. Verify print area overlay is accurate
11. Test customization and add to cart

**Expected Results:**
- ✅ Admin UI is intuitive and clear
- ✅ Mockup uploads succeed
- ✅ Print area configuration saves correctly
- ✅ Frontend displays match admin configuration
- ✅ Customization works as expected

**Actual Results:** _____

---

### Scenario 6: Cross-Browser Compatibility

**Objective:** Verify customization system works across major browsers.

**Browsers to Test:**
- [ ] Chrome (latest)
- [ ] Firefox (latest)
- [ ] Safari (latest)
- [ ] Edge (latest)
- [ ] Mobile Safari (iOS)
- [ ] Mobile Chrome (Android)

**Steps (Repeat for Each Browser):**

1. Navigate to customizable product
2. Upload design
3. Drag, scale, rotate design
4. Verify constraints work
5. Switch color variations
6. Add to cart
7. Complete checkout
8. Check for:
   - Layout issues
   - Canvas rendering problems
   - JavaScript errors in console
   - Touch gesture support (mobile)

**Expected Results:**
- ✅ Consistent behavior across all browsers
- ✅ No visual glitches or layout breaks
- ✅ Canvas interactions smooth (no lag)
- ✅ Mobile touch gestures functional

**Actual Results:**

| Browser | Status | Notes |
|---------|--------|-------|
| Chrome  | ⬜ Pass / ⬜ Fail | |
| Firefox | ⬜ Pass / ⬜ Fail | |
| Safari  | ⬜ Pass / ⬜ Fail | |
| Edge    | ⬜ Pass / ⬜ Fail | |
| Mobile Safari | ⬜ Pass / ⬜ Fail | |
| Mobile Chrome | ⬜ Pass / ⬜ Fail | |

---

## Regression Testing Checklist

After any code changes, verify:

- [ ] Existing non-customized products still work
- [ ] Standard WooCommerce cart/checkout unaffected
- [ ] Other theme features (reseller dashboard, etc.) functional
- [ ] Performance: Page load times acceptable (<3s)
- [ ] No PHP errors/warnings in debug log
- [ ] No JavaScript console errors
- [ ] Responsive design intact (mobile/tablet)

---

## Performance Benchmarks

| Metric | Target | Actual |
|--------|--------|--------|
| Product page load time | <3s | _____ |
| Mockup swap time | <500ms | _____ |
| Canvas initialization | <1s | _____ |
| Add to cart response time | <2s | _____ |
| Design upload time (1MB file) | <3s | _____ |
| Admin order page load | <2s | _____ |

---

## Bug Report Template

When logging bugs found during QA:

**Bug ID:** _____
**Severity:** ⬜ Critical ⬜ High ⬜ Medium ⬜ Low
**Component:** ⬜ Frontend Canvas ⬜ Backend Validation ⬜ Cart ⬜ Admin ⬜ Other

**Description:**
_____

**Steps to Reproduce:**
1. _____
2. _____
3. _____

**Expected Behavior:**
_____

**Actual Behavior:**
_____

**Screenshots:**
_____

**Environment:**
- Browser: _____
- WordPress Version: _____
- WooCommerce Version: _____
- Theme Version: _____

**Console Errors (if any):**
```
_____
```

---

## Sign-Off

**QA Tester:** ___________________
**Date:** ___________________
**Overall Status:** ⬜ Pass ⬜ Fail ⬜ Pass with Minor Issues

**Summary:**
_____

**Blockers:**
_____

**Recommendations:**
_____
