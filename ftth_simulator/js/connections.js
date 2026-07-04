/**
 * FTTH Simulator — Connection Manager
 * Handles drawing SVG bezier curves between equipment ports
 * and managing the interactive line drawing state.
 */

class ConnectionManager {
  constructor(canvasManager) {
    this.canvas = canvasManager;
    this.svgLayer = document.getElementById('connections-layer');
    
    // State for interactive drawing
    this.isDrawing = false;
    this.sourceNodeId = null;
    this.drawingLine = null; // SVG path element

    this.initEvents();
  }

  initEvents() {
    const container = document.getElementById('canvas-container');

    // Start connection from output port
    container.addEventListener('mousedown', (e) => {
      const portEl = e.target.closest('.port-output');
      if (portEl) {
        e.stopPropagation(); // Prevent node dragging
        this.startDrawing(portEl.getAttribute('data-node-id'));
      }
    });

    // Draw line following mouse
    container.addEventListener('mousemove', (e) => {
      if (this.isDrawing && this.drawingLine) {
        const containerRect = container.getBoundingClientRect();
        // Convert mouse screen coordinates to logical SVG coordinates inside inner canvas
        const endX = (e.clientX - containerRect.left - this.canvas.panX) / this.canvas.scale;
        const endY = (e.clientY - containerRect.top - this.canvas.panY) / this.canvas.scale;
        this.updateDrawingLine(endX, endY);
      }
    });

    // Finish connection or cancel
    window.addEventListener('mouseup', (e) => {
      if (this.isDrawing) {
        const portEl = e.target.closest('.port-input');
        if (portEl) {
          // Valid drop on input port
          const targetNodeId = portEl.getAttribute('data-node-id');
          this.finishDrawing(targetNodeId);
        } else {
          // Cancel drawing
          this.cancelDrawing();
        }
      }
    });

    // Update lines when a node moves
    document.addEventListener('node:moved', (e) => {
      this.redrawAllConnections();
    });

    // Remove lines when a node is removed
    document.addEventListener('node:removed', (e) => {
      const nodeId = e.detail.id;
      // Find and remove lines associated with this node
      const lines = this.svgLayer.querySelectorAll(`path[data-source="${nodeId}"], path[data-target="${nodeId}"]`);
      lines.forEach(line => line.remove());
      
      // The logical connections are already handled in CanvasManager
      // We just need to cleanup DOM if necessary, or redraw all
      this.redrawAllConnections();
    });

    // Redraw lines when a connection is directly removed
    document.addEventListener('connection:removed', (e) => {
      this.redrawAllConnections();
    });

    // Update lines color/animation when calculation completes
    document.addEventListener('simulation:calculated', (e) => {
      this.updateConnectionStyles(e.detail.results);
    });
  }

  startDrawing(sourceNodeId) {
    this.isDrawing = true;
    this.sourceNodeId = sourceNodeId;

    this.drawingLine = document.createElementNS('http://www.w3.org/2000/svg', 'path');
    this.drawingLine.setAttribute('class', 'drawing-line');
    this.svgLayer.appendChild(this.drawingLine);
  }

  updateDrawingLine(endX, endY) {
    const sourcePos = this._getPortPosition(this.sourceNodeId, 'output');
    if (!sourcePos) return;

    // Draw bezier curve
    const pathData = this._createBezierPath(sourcePos.x, sourcePos.y, endX, endY);
    this.drawingLine.setAttribute('d', pathData);
  }

  finishDrawing(targetNodeId) {
    if (this.sourceNodeId && targetNodeId) {
      const success = this.canvas.addConnection(this.sourceNodeId, targetNodeId);
      if (success) {
        this.redrawAllConnections();
      }
    }
    this.cancelDrawing();
  }

  cancelDrawing() {
    this.isDrawing = false;
    this.sourceNodeId = null;
    if (this.drawingLine) {
      this.drawingLine.remove();
      this.drawingLine = null;
    }
  }

  redrawAllConnections() {
    // Clear existing lines and hitboxes
    const existing = this.svgLayer.querySelectorAll('.connection-path, .connection-hitbox');
    existing.forEach(el => el.remove());

    // Clear connected class from all ports first
    document.querySelectorAll('.port').forEach(p => p.classList.remove('connected'));

    const connections = this.canvas.connections;
    
    connections.forEach(conn => {
      const startPos = this._getPortPosition(conn.sourceId, 'output');
      const endPos = this._getPortPosition(conn.targetId, 'input');

      if (startPos && endPos) {
        const pathData = this._createBezierPath(startPos.x, startPos.y, endPos.x, endPos.y);

        // Visible connection path
        const path = document.createElementNS('http://www.w3.org/2000/svg', 'path');
        path.setAttribute('class', 'connection-path animated');
        path.setAttribute('data-source', conn.sourceId);
        path.setAttribute('data-target', conn.targetId);
        path.setAttribute('d', pathData);
        this.svgLayer.appendChild(path);

        // Invisible thick hitbox path for easy selection/hovering
        const hitbox = document.createElementNS('http://www.w3.org/2000/svg', 'path');
        hitbox.setAttribute('class', 'connection-hitbox');
        hitbox.setAttribute('data-source', conn.sourceId);
        hitbox.setAttribute('data-target', conn.targetId);
        hitbox.setAttribute('d', pathData);

        // Link hover state to visible path
        hitbox.addEventListener('mouseenter', () => {
          path.classList.add('hovered');
        });
        hitbox.addEventListener('mouseleave', () => {
          path.classList.remove('hovered');
        });

        this.svgLayer.appendChild(hitbox);
        
        // Add visual indicator class to ports in active canvas only
        document.querySelector(`#canvas-inner .port-output[data-node-id="${conn.sourceId}"]`)?.classList.add('connected');
        document.querySelector(`#canvas-inner .port-input[data-node-id="${conn.targetId}"]`)?.classList.add('connected');
      }
    });
    
    // Force CSS update
    document.dispatchEvent(new CustomEvent('simulation:needs_recalc'));
  }

  updateConnectionStyles(calcResults) {
    const lines = this.svgLayer.querySelectorAll('.connection-path');
    lines.forEach(line => {
      const sourceId = line.getAttribute('data-source');
      const targetId = line.getAttribute('data-target');
      
      const targetResult = calcResults.get(targetId);
      
      if (targetResult) {
        // Color line based on signal at target input
        // Using the same status logic
        line.setAttribute('data-signal', targetResult.status);
      } else {
        line.removeAttribute('data-signal');
      }
    });
  }

  _getPortPosition(nodeId, type) {
    const node = this.canvas.getNode(nodeId);
    if (!node) return null;

    const nodeEl = document.querySelector(`#canvas-inner #${nodeId}`);
    if (!nodeEl) return null;

    // Default sizes for node categories to fallback on if not yet rendered/measured
    const defaultSizes = {
      olt: { w: 180, h: 72 },
      sfp: { w: 130, h: 72 },
      ont: { w: 150, h: 72 },
      splitter: { w: 140, h: 72 },
      odp: { w: 150, h: 72 },
      fiber_cable: { w: 140, h: 72 },
      drop_cable: { w: 140, h: 72 },
      connector: { w: 125, h: 72 }
    };
    
    const w = nodeEl.offsetWidth || defaultSizes[node.type]?.w || 140;
    const h = nodeEl.offsetHeight || defaultSizes[node.type]?.h || 72;

    if (type === 'input') {
      return {
        x: node.x,
        y: node.y + h / 2
      };
    } else {
      return {
        x: node.x + w,
        y: node.y + h / 2
      };
    }
  }

  _createBezierPath(x1, y1, x2, y2) {
    const dx = x2 - x1;
    let curve;
    if (dx >= 0) {
      // Normal flow: target is to the right of source.
      // Cap curve to half of the distance, up to a maximum of 100px.
      // This prevents any horizontal overshoot/loops on short connections.
      curve = Math.min(dx * 0.5, 100);
    } else {
      // Reverse flow: target is to the left of source.
      // Use a minimum curve of 50px to keep the loopback smooth and clear.
      curve = Math.max(50, Math.abs(dx) * 0.5);
    }
    
    return `M ${x1} ${y1} C ${x1 + curve} ${y1}, ${x2 - curve} ${y2}, ${x2} ${y2}`;
  }
}

// Export for module usage
if (typeof module !== 'undefined' && module.exports) {
  module.exports = { ConnectionManager };
}
