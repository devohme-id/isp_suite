/**
 * FTTH Simulator — Drag & Drop Manager
 * Handles dragging equipment from the sidebar to the canvas,
 * and dragging existing nodes around the canvas.
 */

class DragDropManager {
  constructor(canvasManager) {
    this.canvas = canvasManager;
    this.container = document.getElementById('canvas-container');
    
    // State for creating new nodes
    this.draggedType = null;
    this.ghostEl = null;

    // State for moving existing nodes
    this.movingNodeId = null;
    this.dragStartX = 0;
    this.dragStartY = 0;
    this.initialNodePositions = null;

    this.initEvents();
  }

  initEvents() {
    // 1. Sidebar to Canvas Dragging (Event Delegation for dynamic elements)
    const sidebar = document.querySelector('.app-sidebar');
    if (sidebar) {
      sidebar.addEventListener('pointerdown', (e) => {
        const item = e.target.closest('.palette-item');
        if (item) {
          this._onPalettePointerDown(e, item);
        }
      });
    }

    // 2. Canvas node dragging
    // Handled via event delegation on the container
    this.container.addEventListener('pointerdown', (e) => {
      const nodeEl = e.target.closest('.eq-node');
      // Ignore if clicking on a port (that's for connections)
      if (nodeEl && !e.target.classList.contains('port')) {
        this._onNodePointerDown(e, nodeEl);
      }
    });

    // Global pointer move and up
    window.addEventListener('pointermove', (e) => this._onPointerMove(e));
    window.addEventListener('pointerup', (e) => this._onPointerUp(e));
  }

  _onPalettePointerDown(e, item) {
    e.preventDefault();
    this.draggedType = item.getAttribute('data-type');
    
    // Create ghost element following the pointer
    this.ghostEl = item.cloneNode(true);
    this.ghostEl.classList.add('drag-ghost');
    document.body.appendChild(this.ghostEl);
    
    // Center ghost on cursor
    this._updateGhostPosition(e.clientX, e.clientY);
  }

  _onNodePointerDown(e, nodeEl) {
    if (e.button !== 0) return;
    
    const nodeId = nodeEl.id;

    // Toggle or select if click happens
    if (e.shiftKey) {
      this.canvas.selectNode(nodeId, false, true); // toggle selection
    } else {
      if (!this.canvas.selectedNodeIds.has(nodeId)) {
        this.canvas.selectNode(nodeId, false, false); // exclusive select
      }
    }

    if (this.canvas.selectedNodeIds.has(nodeId)) {
      this.movingNodeId = nodeId;
      this.dragStartX = e.clientX;
      this.dragStartY = e.clientY;
      
      this.initialNodePositions = new Map();
      this.canvas.selectedNodeIds.forEach(id => {
        const node = this.canvas.getNode(id);
        if (node) {
          this.initialNodePositions.set(id, { x: node.x, y: node.y });
          const el = document.querySelector(`#canvas-inner #${id}`);
          if (el) el.classList.add('dragging');
        }
      });
    }
  }

  _onPointerMove(e) {
    // Handling new node drop ghost
    if (this.ghostEl) {
      this._updateGhostPosition(e.clientX, e.clientY);
      
      // Check if over canvas
      const canvasRect = this.container.getBoundingClientRect();
      if (e.clientX >= canvasRect.left && e.clientX <= canvasRect.right &&
          e.clientY >= canvasRect.top && e.clientY <= canvasRect.bottom) {
        this.container.classList.add('drag-over');
      } else {
        this.container.classList.remove('drag-over');
      }
    }

    // Handling existing nodes move
    if (this.movingNodeId && this.initialNodePositions) {
      const screenDx = e.clientX - this.dragStartX;
      const screenDy = e.clientY - this.dragStartY;
      
      const logicalDx = screenDx / this.canvas.scale;
      const logicalDy = screenDy / this.canvas.scale;
      
      const snap = 20;
      
      this.initialNodePositions.forEach((startPos, id) => {
        let newX = startPos.x + logicalDx;
        let newY = startPos.y + logicalDy;
        
        newX = Math.round(newX / snap) * snap;
        newY = Math.round(newY / snap) * snap;
        
        this.canvas.updateNodePosition(id, newX, newY);
      });
    }
  }

  _onPointerUp(e) {
    // Finalize new node drop
    if (this.ghostEl && this.draggedType) {
      const canvasRect = this.container.getBoundingClientRect();
      
      // Check if dropped inside canvas
      if (e.clientX >= canvasRect.left && e.clientX <= canvasRect.right &&
          e.clientY >= canvasRect.top && e.clientY <= canvasRect.bottom) {
        
        // Calculate dynamic dimensions for cursor centering offset
        let width = 140; // Default
        if (this.draggedType === 'olt') width = 180;
        else if (this.draggedType === 'ont') width = 150;
        else if (this.draggedType === 'sfp') width = 130;
        else if (this.draggedType === 'connector') width = 125;
        
        let x = (e.clientX - canvasRect.left - this.canvas.panX) / this.canvas.scale - (width / 2);
        let y = (e.clientY - canvasRect.top - this.canvas.panY) / this.canvas.scale - 25;
        
        // Snap to grid
        x = Math.round(x / 20) * 20;
        y = Math.round(y / 20) * 20;

        const newNode = this.canvas.addNode(this.draggedType, x, y);
        if (newNode) {
          this.canvas.selectNode(newNode.id);
        }
      }

      // Cleanup ghost
      this.ghostEl.remove();
      this.ghostEl = null;
      this.draggedType = null;
      this.container.classList.remove('drag-over');
    }

    // Finalize existing node move
    if (this.movingNodeId) {
      const screenDx = e.clientX - this.dragStartX;
      const screenDy = e.clientY - this.dragStartY;
      const dist = Math.sqrt(screenDx * screenDx + screenDy * screenDy);
      
      // If it was a simple click without drag, select exclusively
      if (dist < 3 && !e.shiftKey) {
        this.canvas.selectNode(this.movingNodeId, false, false);
      }

      if (this.initialNodePositions) {
        this.initialNodePositions.forEach((_, id) => {
          const el = document.querySelector(`#canvas-inner #${id}`);
          if (el) el.classList.remove('dragging');
        });
        this.initialNodePositions = null;
      }
      this.movingNodeId = null;
    }
  }

  _updateGhostPosition(x, y) {
    if (!this.ghostEl) return;
    // Offset slightly so cursor doesn't completely hide it
    this.ghostEl.style.left = `${x - 20}px`;
    this.ghostEl.style.top = `${y - 20}px`;
  }
}

// Export for module usage
if (typeof module !== 'undefined' && module.exports) {
  module.exports = { DragDropManager };
}
