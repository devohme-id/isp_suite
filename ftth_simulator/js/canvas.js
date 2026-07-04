/**
 * FTTH Simulator — Canvas Manager
 * Handles the workspace area, placing nodes, selecting nodes, and panning/zooming.
 */

class CanvasManager {
  constructor(containerId) {
    this.container = document.getElementById(containerId);
    this.nodes = new Map(); // Store all equipment nodes: id -> data
    this.connections = []; // Store all connections: {sourceId, sourcePort, targetId, targetPort}
    
    // Canvas state
    this.scale = 1;
    this.panX = 0;
    this.panY = 0;
    this.isPanning = false;
    this.startX = 0;
    this.startY = 0;
    
    this.selectedNodeId = null;
    this.selectedNodeIds = new Set();
    this.selectedConnection = null; // Store selected connection: {sourceId, targetId}

    this.initEvents();
    this.initZoomSlider();
  }

  initZoomSlider() {
    const slider = document.getElementById('zoom-slider');
    const display = document.getElementById('zoom-value');
    if (slider) {
      slider.addEventListener('input', (e) => {
        this.scale = parseFloat(e.target.value);
        if (display) display.textContent = Math.round(this.scale * 100) + '%';
        this._updateTransform();
      });
      slider.addEventListener('change', () => {
        document.dispatchEvent(new CustomEvent('canvas:zoom'));
      });
    }
  }

  initEvents() {
    // Zooming via mouse wheel
    this.container.addEventListener('wheel', (e) => {
      e.preventDefault();
      // Calculate a proportional, smooth zoom step based on wheel delta
      let delta = -e.deltaY * 0.001;
      // Clamp the delta to avoid aggressive sudden jumps
      delta = Math.max(-0.15, Math.min(0.15, delta));
      this.zoom(delta, e.clientX, e.clientY);
      document.dispatchEvent(new CustomEvent('canvas:zoom'));
    }, { passive: false });

    // Deselect, Start Panning, or Start Marquee Selection
    this.container.addEventListener('mousedown', (e) => {
      if (e.target === this.container || e.target.id === 'canvas-inner' || e.target.tagName === 'svg' || e.target.classList.contains('connection-path') || e.target.classList.contains('connection-hitbox')) {
        // Only left click
        if (e.button !== 0) return;

        const isConnection = e.target.classList.contains('connection-path') || e.target.classList.contains('connection-hitbox');
        
        if (e.shiftKey) {
          // Start marquee selection (Shift + drag)
          e.preventDefault();
          this.isSelecting = true;
          
          const containerRect = this.container.getBoundingClientRect();
          this.selectStartX = e.clientX - containerRect.left;
          this.selectStartY = e.clientY - containerRect.top;
          
          // Store initial selection state to merge with
          this.preSelectedNodeIds = new Set(this.selectedNodeIds);
          
          // Create marquee DOM element
          this.selectionBoxEl = document.createElement('div');
          this.selectionBoxEl.className = 'selection-marquee';
          this.selectionBoxEl.style.left = this.selectStartX + 'px';
          this.selectionBoxEl.style.top = this.selectStartY + 'px';
          this.selectionBoxEl.style.width = '0px';
          this.selectionBoxEl.style.height = '0px';
          this.container.appendChild(this.selectionBoxEl);
        } else {
          // Deselect everything unless clicking on a connection
          if (!isConnection) {
            this.selectNode(null);
            this.selectConnection(null);
          }
          
          // Start standard panning
          this.isPanning = true;
          this.startX = e.clientX - this.panX;
          this.startY = e.clientY - this.panY;
          this.container.style.cursor = 'grabbing';
        }
      }
    });

    window.addEventListener('mousemove', (e) => {
      if (this.isPanning) {
        this.panX = e.clientX - this.startX;
        this.panY = e.clientY - this.startY;
        this._updateTransform();
      } else if (this.isSelecting && this.selectionBoxEl) {
        const containerRect = this.container.getBoundingClientRect();
        const currentX = e.clientX - containerRect.left;
        const currentY = e.clientY - containerRect.top;
        
        const x = Math.min(this.selectStartX, currentX);
        const y = Math.min(this.selectStartY, currentY);
        const width = Math.abs(this.selectStartX - currentX);
        const height = Math.abs(this.selectStartY - currentY);
        
        this.selectionBoxEl.style.left = x + 'px';
        this.selectionBoxEl.style.top = y + 'px';
        this.selectionBoxEl.style.width = width + 'px';
        this.selectionBoxEl.style.height = height + 'px';
        
        // Find overlapping nodes
        const marqueeRect = this.selectionBoxEl.getBoundingClientRect();
        const nodeEls = this.container.querySelectorAll('.eq-node');
        
        const currentSelected = new Set(this.preSelectedNodeIds);
        nodeEls.forEach(nodeEl => {
          const nodeRect = nodeEl.getBoundingClientRect();
          const overlaps = !(marqueeRect.right < nodeRect.left || 
                             marqueeRect.left > nodeRect.right || 
                             marqueeRect.bottom < nodeRect.top || 
                             marqueeRect.top > nodeRect.bottom);
          const id = nodeEl.id;
          if (overlaps) {
            currentSelected.add(id);
            nodeEl.classList.add('selected');
          } else {
            if (!this.preSelectedNodeIds.has(id)) {
              currentSelected.delete(id);
              nodeEl.classList.remove('selected');
            } else {
              nodeEl.classList.add('selected');
            }
          }
        });
        
        this.selectedNodeIds = currentSelected;
      }
    });

    window.addEventListener('mouseup', () => {
      if (this.isPanning) {
        this.isPanning = false;
        this.container.style.cursor = ''; // Reset cursor
        document.dispatchEvent(new CustomEvent('canvas:panend'));
      } else if (this.isSelecting) {
        this.isSelecting = false;
        if (this.selectionBoxEl) {
          this.selectionBoxEl.remove();
          this.selectionBoxEl = null;
        }
        
        // Update selectedNodeId to be the last selected element to retain compatibility
        const idsArray = Array.from(this.selectedNodeIds);
        if (idsArray.length > 0) {
          this.selectedNodeId = idsArray[idsArray.length - 1];
          const lastNode = this.getNode(this.selectedNodeId);
          if (lastNode) {
            document.dispatchEvent(new CustomEvent('node:selected', { detail: { node: lastNode } }));
          }
        } else {
          this.selectedNodeId = null;
          document.dispatchEvent(new CustomEvent('node:deselected'));
        }
      }
    });
    
    // Keyboard delete
    document.addEventListener('keydown', async (e) => {
      if (e.key === 'Delete' || e.key === 'Backspace') {
        // Prevent deleting if typing in an input
        if (e.target.tagName !== 'INPUT' && e.target.tagName !== 'TEXTAREA') {
          if (this.selectedNodeIds && this.selectedNodeIds.size > 0) {
            const count = this.selectedNodeIds.size;
            let confirmed = true;
            if (count > 1) {
              confirmed = await window.app.showConfirm(`Apakah Anda yakin ingin menghapus ${count} perangkat yang terpilih?`, 'Hapus Beberapa Perangkat', 'danger');
            }
            if (confirmed) {
              const idsToDelete = Array.from(this.selectedNodeIds);
              this.selectNode(null); // Deselect all
              idsToDelete.forEach(id => {
                this.removeNode(id);
              });
            }
          } else if (this.selectedConnection) {
            const confirmed = await window.app.showConfirm('Apakah Anda yakin ingin menghapus koneksi yang terpilih?', 'Hapus Koneksi', 'danger');
            if (confirmed) {
              this.removeConnection(this.selectedConnection.sourceId, this.selectedConnection.targetId);
              this.selectConnection(null);
            }
          }
        }
      }
    });

    // Select connection on click
    this.container.addEventListener('click', (e) => {
      const pathEl = e.target.closest('.connection-path, .connection-hitbox');
      if (pathEl && !e.target.closest('.drawing-line')) {
        const sourceId = pathEl.getAttribute('data-source');
        const targetId = pathEl.getAttribute('data-target');
        this.selectConnection(sourceId, targetId);
      } else if (e.target === this.container || e.target.id === 'canvas-inner' || e.target.tagName === 'svg') {
        this.selectConnection(null);
      }
    });

    // Double-click connection to delete
    this.container.addEventListener('dblclick', async (e) => {
      const pathEl = e.target.closest('.connection-path, .connection-hitbox');
      if (pathEl && !e.target.closest('.drawing-line')) {
        e.stopPropagation();
        const sourceId = pathEl.getAttribute('data-source');
        const targetId = pathEl.getAttribute('data-target');
        
        const sourceNode = this.getNode(sourceId);
        const targetNode = this.getNode(targetId);
        const sourceName = sourceNode ? sourceNode.def.name : 'Perangkat';
        const targetName = targetNode ? targetNode.def.name : 'Perangkat';
        
        const confirmed = await window.app.showConfirm(`Apakah Anda yakin ingin menghapus koneksi antara ${sourceName} dan ${targetName}?`, 'Hapus Koneksi', 'danger');
        if (confirmed) {
          this.removeConnection(sourceId, targetId);
          if (this.selectedConnection && this.selectedConnection.sourceId === sourceId && this.selectedConnection.targetId === targetId) {
            this.selectConnection(null);
          }
        }
      }
    });
  }

  zoom(delta, mouseX, mouseY) {
    const oldScale = this.scale;
    const newScale = Math.max(0.3, Math.min(2, oldScale + delta));
    if (newScale === oldScale) return;
    
    this.scale = newScale;
    
    const slider = document.getElementById('zoom-slider');
    const display = document.getElementById('zoom-value');
    if (slider) slider.value = this.scale;
    if (display) display.textContent = Math.round(this.scale * 100) + '%';
    
    // Zoom towards cursor location
    if (mouseX !== undefined && mouseY !== undefined) {
      const rect = this.container.getBoundingClientRect();
      const mX = mouseX - rect.left;
      const mY = mouseY - rect.top;
      
      const canvasX = (mX - this.panX) / oldScale;
      const canvasY = (mY - this.panY) / oldScale;
      
      this.panX = mX - canvasX * newScale;
      this.panY = mY - canvasY * newScale;
    }
    
    this._updateTransform();
  }

  _updateTransform() {
    this.container.style.backgroundPosition = `${this.panX}px ${this.panY}px`;
    // Scale the background grid as well
    this.container.style.backgroundSize = `${20 * this.scale}px ${20 * this.scale}px`;
    
    const inner = document.getElementById('canvas-inner');
    if (inner) {
      inner.style.transform = `translate(${this.panX}px, ${this.panY}px) scale(${this.scale})`;
    }
  }

  /**
   * Add a new equipment node to the canvas
   */
  addNode(type, x, y, providedId = null) {
    const equipmentDef = EQUIPMENT_CATALOG[type];
    if (!equipmentDef) return null;

    const id = providedId || 'node_' + Math.random().toString(36).substr(2, 9);
    
    // Initialize default properties
    const properties = {};
    if (equipmentDef.properties) {
      Object.keys(equipmentDef.properties).forEach(key => {
        properties[key] = equipmentDef.properties[key].default;
      });
    }

    const nodeData = {
      id,
      type,
      x,
      y,
      properties,
      def: equipmentDef
    };

    this.nodes.set(id, nodeData);
    this._renderNode(nodeData);
    
    // Trigger calculation
    document.dispatchEvent(new CustomEvent('simulation:needs_recalc'));
    
    return nodeData;
  }

  _renderNode(nodeData) {
    const el = document.createElement('div');
    el.className = 'eq-node animate-bounce-in';
    el.id = nodeData.id;
    el.setAttribute('data-category', nodeData.def.category);
    el.setAttribute('data-type', nodeData.type);
    
    // Apply position
    el.style.left = `${nodeData.x}px`;
    el.style.top = `${nodeData.y}px`;

    // Construct DOM
    let portsHtml = '';
    if (nodeData.def.ports.input > 0) {
      portsHtml += `<div class="port port-input" data-port-type="input" data-node-id="${nodeData.id}"></div>
                    <div class="port-label">IN</div>`;
    }
    if (nodeData.def.ports.output > 0) {
      portsHtml += `<div class="port port-output" data-port-type="output" data-node-id="${nodeData.id}"></div>
                    <div class="port-label">OUT</div>`;
    }

    // Dynamic casing details matching physical devices
    let extraHtml = '';
    if (nodeData.type === 'olt') {
      extraHtml = `
        <div class="led-panel">
          <div class="led-group"><span>PWR</span><div class="led green-on"></div></div>
          <div class="led-group"><span>ACT</span><div class="led green-blink"></div></div>
          <div class="led-group"><span>SYS</span><div class="led green-on"></div></div>
        </div>
      `;
    } else if (nodeData.type === 'sfp') {
      const latchColor = (nodeData.properties.sfpClass === 'Class C+' || nodeData.properties.sfpClass === 'EPON PX20+') ? '#16a34a' : '#2563eb';
      extraHtml = `
        <div class="sfp-contacts">
          <div class="sfp-contact-bar"></div>
          <div class="sfp-contact-bar"></div>
          <div class="sfp-contact-bar"></div>
        </div>
        <div class="sfp-latch" style="border-color: ${latchColor}"></div>
      `;
    } else if (nodeData.type === 'ont') {
      extraHtml = `
        <div class="ont-antenna-left"></div>
        <div class="ont-antenna-right"></div>
        <div class="ont-vents">
          <div class="ont-vent-slit"></div>
          <div class="ont-vent-slit"></div>
          <div class="ont-vent-slit"></div>
        </div>
        <div class="led-panel">
          <div class="led-group"><span>PWR</span><div class="led green-on"></div></div>
          <div class="led-group"><span>PON</span><div class="led" id="led-pon-${nodeData.id}"></div></div>
          <div class="led-group"><span>LOS</span><div class="led red-blink" id="led-los-${nodeData.id}"></div></div>
          <div class="led-group"><span>LAN</span><div class="led" id="led-lan-${nodeData.id}"></div></div>
        </div>
      `;
    } else if (nodeData.type === 'odp') {
      extraHtml = `<div class="odp-lock"></div>`;
    } else if (nodeData.type === 'fiber_cable' || nodeData.type === 'drop_cable') {
      extraHtml = `
        <div class="spool-flange-l"></div>
        <div class="spool-flange-r"></div>
      `;
    } else if (nodeData.type === 'connector') {
      extraHtml = `
        <div class="connector-body-visual" data-conn="${nodeData.properties.type || 'SC/APC'}"></div>
      `;
    }

    el.innerHTML = `
      ${portsHtml}
      ${extraHtml}
      <div class="eq-node-actions">
        <div class="eq-node-info" title="Detail Redaman">
          <svg viewBox="0 0 24 24" width="12" height="12" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="16" x2="12" y2="12"></line><line x1="12" y1="8" x2="12.01" y2="8"></line></svg>
        </div>
        <div class="eq-node-settings" title="Pengaturan">
          <svg viewBox="0 0 24 24" width="12" height="12" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="3"></circle><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"></path></svg>
        </div>
        <div class="eq-node-delete" title="Hapus Perangkat">
          <svg viewBox="0 0 24 24" width="12" height="12" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path><line x1="10" y1="11" x2="10" y2="17"></line><line x1="14" y1="11" x2="14" y2="17"></line></svg>
        </div>
      </div>
      <div class="eq-node-header">
        <div class="eq-node-icon">${nodeData.def.icon}</div>
        <div class="eq-node-name">${nodeData.def.name}</div>
      </div>
      <div class="eq-node-body">
        <div class="eq-node-value js-node-label">${nodeData.def.getLabel(nodeData.properties)}</div>
      </div>
    `;

    // Apply color adapters for connector ports initially
    if (nodeData.type === 'connector') {
      const type = nodeData.properties.type || 'SC/APC';
      const color = type === 'SC/APC' ? '#16a34a' : '#2563eb';
      const inputPort = el.querySelector('.port-input');
      const outputPort = el.querySelector('.port-output');
      if (inputPort) inputPort.style.borderLeftColor = color;
      if (outputPort) outputPort.style.borderRightColor = color;
    } else if (nodeData.type === 'sfp') {
      const inputPort = el.querySelector('.port-input');
      if (inputPort) inputPort.style.borderLeftColor = '#2563eb';
    }

    // Node selection event
    el.addEventListener('mousedown', (e) => {
      // Don't select if clicking on a port, settings, info, or delete button
      if (!e.target.classList.contains('port') && !e.target.closest('.eq-node-settings') && !e.target.closest('.eq-node-info') && !e.target.closest('.eq-node-delete')) {
        if (e.shiftKey) {
          this.selectNode(nodeData.id, false, true); // toggle selection
        } else {
          if (!this.selectedNodeIds.has(nodeData.id)) {
            this.selectNode(nodeData.id, false, false); // exclusive select
          }
        }
      }
    });

    // Settings button click
    const settingsBtn = el.querySelector('.eq-node-settings');
    settingsBtn.addEventListener('mousedown', (e) => {
      e.stopPropagation(); // prevent dragging
      document.dispatchEvent(new CustomEvent('node:properties', { detail: { node: nodeData } }));
    });

    // Info button click
    const infoBtn = el.querySelector('.eq-node-info');
    infoBtn.addEventListener('mousedown', (e) => {
      e.stopPropagation(); // prevent dragging
      document.dispatchEvent(new CustomEvent('node:info', { detail: { node: nodeData } }));
    });

    // Delete button click
    const deleteBtn = el.querySelector('.eq-node-delete');
    deleteBtn.addEventListener('mousedown', async (e) => {
      e.stopPropagation(); // prevent dragging
      const confirmed = await window.app.showConfirm(`Apakah Anda yakin ingin menghapus perangkat ${nodeData.def.name} ini?`, 'Hapus Perangkat', 'danger');
      if (confirmed) {
        this.removeNode(nodeData.id);
      }
    });

    // Double click to open properties
    el.addEventListener('dblclick', (e) => {
      if (!e.target.classList.contains('port')) {
        document.dispatchEvent(new CustomEvent('node:properties', { detail: { node: nodeData } }));
      }
    });
    
    // Tooltip events handled globally or via event delegation

    const inner = document.getElementById('canvas-inner') || this.container;
    inner.appendChild(el);
  }

  updateNodePosition(id, x, y) {
    const node = this.nodes.get(id);
    if (node) {
      node.x = x;
      node.y = y;
      const el = document.querySelector(`#canvas-inner #${id}`);
      if (el) {
        el.style.left = `${x}px`;
        el.style.top = `${y}px`;
      }
      // Fire event to update connections
      document.dispatchEvent(new CustomEvent('node:moved', { detail: { id } }));
    }
  }

  updateNodeProperties(id, newProps) {
    const node = this.nodes.get(id);
    if (node) {
      // Revert safety gate: check if reducing splitter/ODP capacity conflicts with active connection count
      if (newProps.ratio && (node.type === 'splitter' || node.type === 'odp')) {
        const newRatioLimit = parseInt(newProps.ratio.split(':')[1]) || 8;
        const currentConnections = this.connections.filter(c => c.sourceId === id).length;
        if (currentConnections > newRatioLimit) {
          if (window.app && typeof window.app.showToast === 'function') {
            window.app.showToast(`Gagal merubah rasio: perangkat memiliki ${currentConnections} koneksi aktif, kapasitas baru hanya ${newRatioLimit}. Hapus beberapa koneksi terlebih dahulu.`, 'error');
          } else {
            alert(`Gagal merubah rasio: perangkat memiliki ${currentConnections} koneksi aktif, kapasitas baru hanya ${newRatioLimit}. Hapus beberapa koneksi terlebih dahulu.`);
          }
          // Revert the selector option in the open properties modal
          const select = document.querySelector(`#properties-form select[data-key="ratio"]`);
          if (select) {
            select.value = node.properties.ratio;
          }
          return;
        }
      }

      node.properties = { ...node.properties, ...newProps };
      
      // Update label
      const el = document.querySelector(`#canvas-inner #${id}`);
      if (el) {
        const labelEl = el.querySelector('.js-node-label');
        if (labelEl) {
          labelEl.textContent = node.def.getLabel(node.properties);
        }

        // Dynamic visual updates for specific casing properties
        if (node.type === 'connector') {
          const connVisual = el.querySelector('.connector-body-visual');
          if (connVisual) {
            connVisual.setAttribute('data-conn', node.properties.type);
          }
          // Update connector port border colors dynamically
          const inputPort = el.querySelector('.port-input');
          const outputPort = el.querySelector('.port-output');
          if (node.properties.type === 'SC/APC') {
            if (inputPort) inputPort.style.borderLeftColor = '#16a34a';
            if (outputPort) outputPort.style.borderRightColor = '#16a34a';
          } else {
            if (inputPort) inputPort.style.borderLeftColor = '#2563eb';
            if (outputPort) outputPort.style.borderRightColor = '#2563eb';
          }
        } else if (node.type === 'sfp') {
          const latch = el.querySelector('.sfp-latch');
          if (latch) {
            const isGreen = node.properties.sfpClass === 'Class C+' || node.properties.sfpClass === 'EPON PX20+';
            latch.style.borderColor = isGreen ? '#16a34a' : '#2563eb';
          }
        }
      }
      
      document.dispatchEvent(new CustomEvent('simulation:needs_recalc'));
    }
  }

  removeNode(id) {
    if (this.nodes.has(id)) {
      this.nodes.delete(id);
      
      // Clean up logical connections associated with this node
      this.connections = this.connections.filter(
        c => c.sourceId !== id && c.targetId !== id
      );

      const el = document.querySelector(`#canvas-inner #${id}`);
      if (el) {
        // Fade out animation before removing
        el.style.transition = 'opacity 200ms, transform 200ms';
        el.style.opacity = '0';
        el.style.transform = 'scale(0.8)';
        setTimeout(() => el.remove(), 200);
      }
      
      this.selectedNodeIds.delete(id);
      
      if (this.selectedNodeId === id) {
        this.selectedNodeId = null;
        document.dispatchEvent(new CustomEvent('node:deselected'));
      }
      
      // Fire event to remove associated connections
      document.dispatchEvent(new CustomEvent('node:removed', { detail: { id } }));
      document.dispatchEvent(new CustomEvent('simulation:needs_recalc'));
    }
  }

  selectNode(id, append = false, toggle = false) {
    if (!id) {
      // Deselect all
      this.selectedNodeIds.forEach(selectedId => {
        const nodeEl = document.querySelector(`#canvas-inner #${selectedId}`);
        if (nodeEl) nodeEl.classList.remove('selected');
      });
      this.selectedNodeIds.clear();
      this.selectedNodeId = null;
      document.dispatchEvent(new CustomEvent('node:deselected'));
      return;
    }

    if (toggle) {
      if (this.selectedNodeIds.has(id)) {
        this.selectedNodeIds.delete(id);
        const el = document.querySelector(`#canvas-inner #${id}`);
        if (el) el.classList.remove('selected');
      } else {
        this.selectedNodeIds.add(id);
        const el = document.querySelector(`#canvas-inner #${id}`);
        if (el) el.classList.add('selected');
      }
    } else if (append) {
      this.selectedNodeIds.add(id);
      const el = document.querySelector(`#canvas-inner #${id}`);
      if (el) el.classList.add('selected');
    } else {
      // Clear previous selection
      this.selectedNodeIds.forEach(selectedId => {
        if (selectedId !== id) {
          const nodeEl = document.querySelector(`#canvas-inner #${selectedId}`);
          if (nodeEl) nodeEl.classList.remove('selected');
        }
      });
      this.selectedNodeIds.clear();
      this.selectedNodeIds.add(id);
      const el = document.querySelector(`#canvas-inner #${id}`);
      if (el) el.classList.add('selected');
    }

    // Update primary selectedNodeId
    const idsArray = Array.from(this.selectedNodeIds);
    if (idsArray.length > 0) {
      this.selectedNodeId = idsArray[idsArray.length - 1];
      const node = this.nodes.get(this.selectedNodeId);
      
      if (this.selectedConnection) {
        this.selectConnection(null);
      }
      
      document.dispatchEvent(new CustomEvent('node:selected', { detail: { node } }));
    } else {
      this.selectedNodeId = null;
      document.dispatchEvent(new CustomEvent('node:deselected'));
    }
  }

  selectConnection(sourceId, targetId) {
    const svgLayer = document.getElementById('connections-layer');
    if (svgLayer) {
      const selectedPaths = svgLayer.querySelectorAll('.connection-path.selected');
      selectedPaths.forEach(p => p.classList.remove('selected'));
    }

    if (sourceId && targetId) {
      this.selectedConnection = { sourceId, targetId };
      const pathEl = svgLayer?.querySelector(`.connection-path[data-source="${sourceId}"][data-target="${targetId}"]`);
      if (pathEl) {
        pathEl.classList.add('selected');
      }
      // Deselect node when selecting connection
      if (this.selectedNodeId) {
        this.selectNode(null);
      }
    } else {
      this.selectedConnection = null;
    }
  }

  getNode(id) {
    return this.nodes.get(id);
  }

  getNodes() {
    return Array.from(this.nodes.values());
  }

  // --- Connection Management Hooks ---
  // The actual SVG line drawing is handled by ConnectionManager, 
  // but CanvasManager stores the logical relationships.

  addConnection(sourceId, targetId) {
    // Prevent self connection
    if (sourceId === targetId) return false;
    
    // Prevent duplicate connection
    const exists = this.connections.find(c => c.sourceId === sourceId && c.targetId === targetId);
    if (exists) return false;

    // Validate outgoing connection capacity limit
    const sourceNode = this.getNode(sourceId);
    if (!sourceNode) return false;

    let maxConnections = 1; // Default for active lines, connectors, cables, SFP
    if (sourceNode.type === 'splitter' || sourceNode.type === 'odp') {
      const ratio = sourceNode.properties.ratio || '1:8';
      maxConnections = parseInt(ratio.split(':')[1]) || 8;
    } else if (sourceNode.type === 'olt') {
      maxConnections = Infinity; // OLT can have multiple SFPs connected
    }

    const currentOutgoing = this.connections.filter(c => c.sourceId === sourceId).length;
    if (currentOutgoing >= maxConnections) {
      const sourceName = sourceNode.def.name;
      const detailMsg = (sourceNode.type === 'splitter' || sourceNode.type === 'odp')
        ? `kapasitas Splitter ${sourceNode.properties.ratio} (maks. ${maxConnections} koneksi)`
        : `perangkat ${sourceName} hanya mendukung 1 koneksi keluar`;
      
      if (window.app && typeof window.app.showToast === 'function') {
        window.app.showToast(`Koneksi ditolak: ${detailMsg}`, 'error');
      } else {
        alert(`Koneksi ditolak: ${detailMsg}`);
      }
      return false;
    }

    // Check if target input is already connected (only 1 input allowed usually)
    const targetHasInput = this.connections.find(c => c.targetId === targetId);
    if (targetHasInput) {
      // Remove old connection
      this.removeConnection(targetHasInput.sourceId, targetId);
    }

    this.connections.push({ sourceId, targetId });
    document.dispatchEvent(new CustomEvent('simulation:needs_recalc'));
    return true;
  }

  removeConnection(sourceId, targetId) {
    this.connections = this.connections.filter(
      c => !(c.sourceId === sourceId && c.targetId === targetId)
    );
    document.dispatchEvent(new CustomEvent('connection:removed', { detail: { sourceId, targetId } }));
    document.dispatchEvent(new CustomEvent('simulation:needs_recalc'));
  }

  getOutgoingConnections(nodeId) {
    return this.connections.filter(c => c.sourceId === nodeId);
  }

  getIncomingConnection(nodeId) {
    return this.connections.find(c => c.targetId === nodeId);
  }
}

// Export for module usage
if (typeof module !== 'undefined' && module.exports) {
  module.exports = { CanvasManager };
}
