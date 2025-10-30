# Print Area Restriction - Proper Implementation Plan

## Problem Analysis

### Issue 1: Designs Can Be Placed Outside Print Area
**Current Problem:**
- Despite constraint logic, customers can still upload and position designs beyond print area
- Dragging might be constrained, but initial placement is wrong
- Upload/drop operations not properly constrained

**Root Causes to Investigate:**
1. Coordinate system mismatch between Print Studio and Product Customizer
2. Canvas scaling not accounted for
3. Constraint function not called in all placement scenarios
4. Print area coordinates might be relative to different origin

### Issue 2: Print Area Overlay Shows Wrong Position
**Current Problem:**
- Visual overlay doesn't match actual print area boundaries
- Misleading for customers
- Suggests coordinate system transformation issue

**Root Causes to Investigate:**
1. Print Studio canvas size vs Product Customizer canvas size
2. Different coordinate origins
3. Scaling factors not applied
4. Image background affecting coordinates

## Deep Investigation Needed

### 1. Understanding Coordinate Systems

**Print Studio Canvas:**
```javascript
canvasState.CANVAS_WIDTH: 500
canvasState.CANVAS_HEIGHT: 500
```
- Admin draws print areas on 500x500 canvas
- Coordinates saved: e.g., {x: 60, y: 60, width: 380, height: 420}
- These are absolute pixel coordinates

**Product Customizer Canvas:**
```javascript
state.canvas.width: ? (need to check)
state.canvas.height: ? (need to check)
```
- Customer sees product on canvas
- Canvas might be different size
- Might have background image that scales
- Need to investigate actual dimensions

**Questions to Answer:**
1. What size is the product customizer canvas?
2. Is the product image scaled to fit canvas?
3. Are print area coordinates relative to image or canvas?
4. Do we need to transform coordinates?

### 2. Design Placement Points

**All scenarios where designs are positioned:**

| Scenario | Current Function | Constraint Applied? | Issue |
|----------|-----------------|---------------------|-------|
| Upload image | `handleImageUpload()` | ❓ | Needs verification |
| Add text | `addTextDesign()` | ❓ | Needs verification |
| Drag design | `handleCanvasMouseMove()` | ❓ | Needs verification |
| Drop design | `handleCanvasMouseUp()` | ❌ | Not implemented |
| Resize design | ? | ❌ | Not implemented |
| Initial placement | Various | ❓ | Needs verification |

### 3. Print Area Data Flow

**Need to trace:**
```
Print Studio (Admin)
  ↓ saves to
Product Meta (_aakaari_print_studio_data)
  ↓ loaded by
PHP (cp-functions.php)
  ↓ localized to
JavaScript (AAKAARI_PRODUCTS)
  ↓ used by
Product Customizer (product-customizer.js)
```

**Verification needed:**
- Are coordinates transformed anywhere in this flow?
- Do we lose precision?
- Is scaling applied?

## Proper Implementation Plan

### Phase 1: Investigation & Debugging (15-20 min)

#### Step 1.1: Check Canvas Dimensions
```javascript
// In product-customizer.js
console.log('Canvas dimensions:', state.canvas.width, state.canvas.height);
console.log('Canvas display size:', canvas.getBoundingClientRect());
```

#### Step 1.2: Check Print Area Data
```javascript
// Log what we receive
console.log('Product data:', AAKAARI_PRODUCTS[0]);
console.log('Print areas:', AAKAARI_PRODUCTS[0].sides[0].printAreas);
```

#### Step 1.3: Check Coordinate Calculation
```javascript
// When mouse moves, log positions
console.log('Mouse canvas coords:', x, y);
console.log('Design position:', design.x, design.y);
console.log('Print area bounds:', printArea);
```

#### Step 1.4: Visual Debug Overlay
```javascript
// Draw actual print area with different color
// Draw mouse position
// Draw design bounding box
// Show all coordinates on canvas
```

### Phase 2: Fix Coordinate System (20-30 min)

#### Step 2.1: Determine Scaling Factor
```javascript
// Calculate if print areas need scaling
const scaleFactor = {
  x: state.canvas.width / PRINT_STUDIO_CANVAS_WIDTH,
  y: state.canvas.height / PRINT_STUDIO_CANVAS_HEIGHT
};
```

#### Step 2.2: Transform Print Areas
```javascript
// Apply scaling when loading print areas
function transformPrintArea(area) {
  return {
    x: area.x * scaleFactor.x,
    y: area.y * scaleFactor.y,
    width: area.width * scaleFactor.x,
    height: area.height * scaleFactor.y,
    name: area.name
  };
}
```

#### Step 2.3: Account for Image Positioning
```javascript
// If product image is centered/offset on canvas
const imageOffset = {
  x: (canvas.width - imageWidth) / 2,
  y: (canvas.height - imageHeight) / 2
};
```

### Phase 3: Implement Proper Constraints (30-40 min)

#### Step 3.1: Unified Constraint Function
```javascript
/**
 * Master constraint function that handles all scenarios
 * - Accounts for coordinate system transformation
 * - Handles canvas scaling
 * - Validates against print areas
 * - Prevents restriction area overlap
 */
function constrainDesignPosition(design, newX, newY) {
  // Get transformed print area
  const printArea = getTransformedPrintArea();

  // Account for design center vs top-left positioning
  const designLeft = newX - design.width / 2;
  const designTop = newY - design.height / 2;
  const designRight = designLeft + design.width;
  const designBottom = designTop + design.height;

  // Constrain to print area
  let constrainedX = newX;
  let constrainedY = newY;

  // Left boundary
  if (designLeft < printArea.x) {
    constrainedX = printArea.x + design.width / 2;
  }

  // Right boundary
  if (designRight > printArea.x + printArea.width) {
    constrainedX = printArea.x + printArea.width - design.width / 2;
  }

  // Top boundary
  if (designTop < printArea.y) {
    constrainedY = printArea.y + design.height / 2;
  }

  // Bottom boundary
  if (designBottom > printArea.y + printArea.height) {
    constrainedY = printArea.y + printArea.height - design.height / 2;
  }

  return { x: constrainedX, y: constrainedY };
}
```

#### Step 3.2: Apply to ALL Operations

**Image Upload:**
```javascript
function handleImageUpload(file) {
  // ... load image ...

  // Initial position (center of print area, not canvas)
  const printArea = getTransformedPrintArea();
  const initialX = printArea.x + printArea.width / 2;
  const initialY = printArea.y + printArea.height / 2;

  design.x = initialX;
  design.y = initialY;

  // Apply constraint
  const constrained = constrainDesignPosition(design, initialX, initialY);
  design.x = constrained.x;
  design.y = constrained.y;
}
```

**Text Creation:**
```javascript
function addTextDesign(text) {
  // ... create design ...

  // Position in center of print area
  const printArea = getTransformedPrintArea();
  const initialX = printArea.x + printArea.width / 2;
  const initialY = printArea.y + printArea.height / 2;

  const constrained = constrainDesignPosition(design, initialX, initialY);
  design.x = constrained.x;
  design.y = constrained.y;
}
```

**Drag Operation:**
```javascript
function handleCanvasMouseMove(event) {
  if (!state.draggingDesign) return;

  // Get mouse position
  const rect = canvas.getBoundingClientRect();
  const mouseX = event.clientX - rect.left;
  const mouseY = event.clientY - rect.top;

  // Desired position
  const desiredX = mouseX;
  const desiredY = mouseY;

  // Apply constraint
  const constrained = constrainDesignPosition(
    state.draggingDesign,
    desiredX,
    desiredY
  );

  state.draggingDesign.x = constrained.x;
  state.draggingDesign.y = constrained.y;

  renderCanvas();
}
```

**Drop Operation:**
```javascript
function handleCanvasMouseUp(event) {
  if (state.draggingDesign) {
    // Final constraint check
    const constrained = constrainDesignPosition(
      state.draggingDesign,
      state.draggingDesign.x,
      state.draggingDesign.y
    );

    state.draggingDesign.x = constrained.x;
    state.draggingDesign.y = constrained.y;

    state.draggingDesign = null;
    renderCanvas();
  }
}
```

### Phase 4: Fix Visual Overlay (15-20 min)

#### Step 4.1: Accurate Print Area Drawing
```javascript
function drawPrintArea(ctx, area) {
  // Transform coordinates to match canvas
  const transformed = transformPrintArea(area);

  ctx.save();

  // Draw EXACTLY where print area is
  ctx.fillStyle = 'rgba(59, 130, 246, 0.1)';
  ctx.fillRect(
    transformed.x,
    transformed.y,
    transformed.width,
    transformed.height
  );

  // Dashed border
  ctx.strokeStyle = '#3B82F6';
  ctx.lineWidth = 2;
  ctx.setLineDash([8, 4]);
  ctx.strokeRect(
    transformed.x,
    transformed.y,
    transformed.width,
    transformed.height
  );

  // Labels
  ctx.font = 'bold 14px Arial';
  ctx.fillStyle = '#3B82F6';
  ctx.fillText('✓ DESIGN AREA', transformed.x + 10, transformed.y + 25);

  ctx.restore();
}
```

#### Step 4.2: Debug Overlay (Temporary)
```javascript
function drawDebugInfo(ctx) {
  const printArea = getTransformedPrintArea();

  // Show print area coordinates
  ctx.font = '12px monospace';
  ctx.fillStyle = '#000';
  ctx.fillText(`Print Area: x=${printArea.x}, y=${printArea.y}`, 10, 20);
  ctx.fillText(`Size: ${printArea.width}x${printArea.height}`, 10, 35);

  // Show canvas size
  ctx.fillText(`Canvas: ${canvas.width}x${canvas.height}`, 10, 50);

  // Draw crosshair at print area origin
  ctx.strokeStyle = '#FF0000';
  ctx.lineWidth = 1;
  ctx.beginPath();
  ctx.moveTo(printArea.x - 10, printArea.y);
  ctx.lineTo(printArea.x + 10, printArea.y);
  ctx.moveTo(printArea.x, printArea.y - 10);
  ctx.lineTo(printArea.x, printArea.y + 10);
  ctx.stroke();
}
```

### Phase 5: Add Reset Button (10 min)

#### Step 5.1: Add Button to UI
```javascript
// In setupEventListeners() or similar
$('#reset-designs-btn').on('click', function() {
  if (confirm('Clear all designs? This cannot be undone.')) {
    resetDesigns();
  }
});
```

#### Step 5.2: Reset Function
```javascript
function resetDesigns() {
  // Clear all designs for current side
  state.designs = state.designs.filter(
    d => d.sideIndex !== state.selectedSide
  );

  // Update UI
  updateDesignList();
  updateProductState();
  renderCanvas();

  // Show feedback
  console.log('All designs cleared for current side');
}
```

### Phase 6: Comprehensive Testing (20-30 min)

#### Test Cases:

1. **Upload Image Test**
   - Upload small image → Should appear in print area center
   - Upload large image → Should be constrained within print area
   - Try to drag outside → Should stop at boundary

2. **Add Text Test**
   - Add short text → Should appear in print area center
   - Add long text → Should be constrained within print area
   - Try to drag outside → Should stop at boundary

3. **Visual Alignment Test**
   - Print area overlay should perfectly match actual boundaries
   - Designs should not go outside visible print area
   - Restriction areas should be accurate

4. **Edge Cases Test**
   - Canvas resize
   - Multiple print areas
   - No print areas defined
   - Very small print area
   - Design larger than print area

5. **Reset Button Test**
   - Clear designs on current side only
   - Other sides unaffected
   - Confirmation dialog works
   - UI updates correctly

## Success Criteria

✅ **Criterion 1:** Customer CANNOT place any design outside print area
✅ **Criterion 2:** Visual overlay EXACTLY matches actual print area boundaries
✅ **Criterion 3:** Dragging is smooth but constrained
✅ **Criterion 4:** Initial placement always within print area
✅ **Criterion 5:** Reset button clears all designs
✅ **Criterion 6:** Works consistently across all operations

## Expected Issues & Solutions

### Issue: Canvas scaling
**Solution:** Calculate and apply scale factor to all print area coordinates

### Issue: Image offset
**Solution:** Account for product image position when calculating constraints

### Issue: Design positioning (center vs corner)
**Solution:** Clarify if design.x/y is center or top-left, adjust constraints accordingly

### Issue: Performance during drag
**Solution:** Optimize constraint calculation, consider throttling if needed

## Files to Modify

1. **assets/js/product-customizer.js** - Main implementation
2. **inc/cp-functions.php** - If coordinate transformation needed server-side

## Timeline Estimate

- Investigation: 20 minutes
- Coordinate fix: 30 minutes
- Constraint implementation: 40 minutes
- Visual overlay fix: 20 minutes
- Reset button: 10 minutes
- Testing: 30 minutes
- **Total: ~2.5 hours**

## Next Steps

1. ✅ Create this plan (DONE)
2. ⏳ Get your approval on approach
3. ⏳ Start investigation phase
4. ⏳ Implement fixes systematically
5. ⏳ Test thoroughly
6. ⏳ Document and commit

---

**Ready to proceed?** Please review this plan and let me know if this approach makes sense. Once approved, I'll start with the investigation phase to understand the exact coordinate system issues.
