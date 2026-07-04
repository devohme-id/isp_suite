/**
 * FTTH Simulator — Tooltip Manager
 * Handles showing hovering tooltips over equipment nodes and connection lines
 * displaying estimated attenuation and power levels.
 */

class TooltipManager {
  constructor(calculator) {
    this.calculator = calculator;
    this.tooltipEl = null;
    this.activeTarget = null;
    this.showTimeout = null;
    this.hideTimeout = null;

    this._createDOM();
    this.initEvents();
  }

  _createDOM() {
    this.tooltipEl = document.createElement('div');
    this.tooltipEl.className = 'sim-tooltip';
    document.body.appendChild(this.tooltipEl);
  }

  initEvents() {
    // Use event delegation on document to catch all hoverables
    document.addEventListener('mouseover', (e) => {
      const nodeEl = e.target.closest('.eq-node');
      const pathEl = e.target.closest('.connection-path, .connection-hitbox');

      if (nodeEl) {
        this._scheduleShow(e, nodeEl, 'node');
      } else if (pathEl && !e.target.closest('.drawing-line')) {
        this._scheduleShow(e, pathEl, 'line');
      }
    });

    document.addEventListener('mouseout', (e) => {
      const nodeEl = e.target.closest('.eq-node');
      const pathEl = e.target.closest('.connection-path, .connection-hitbox');

      if (nodeEl) {
        // Only hide if we are leaving the node boundary completely
        const relatedNode = e.relatedTarget ? e.relatedTarget.closest('.eq-node') : null;
        if (relatedNode !== nodeEl) {
          this._scheduleHide();
        }
      } else if (pathEl) {
        // Only hide if we are leaving the connection line boundary completely
        const relatedPath = e.relatedTarget ? e.relatedTarget.closest('.connection-path, .connection-hitbox') : null;
        if (relatedPath !== pathEl) {
          this._scheduleHide();
        }
      }
    });

    document.addEventListener('mousemove', (e) => {
      if (this.tooltipEl.classList.contains('visible') && this.activeTarget) {
        // Only update position if hovering over a line, nodes have fixed tooltips above them
        if (this.activeTarget.type === 'line') {
          this._updatePosition(e.clientX, e.clientY);
        }
      }
    });
    
    // Hide tooltip when dragging starts
    document.addEventListener('pointerdown', () => {
      this._hide();
    });
  }

  _scheduleShow(e, targetEl, type) {
    clearTimeout(this.hideTimeout);
    
    // Don't reshow if already showing same target
    if (this.activeTarget && this.activeTarget.el === targetEl) return;
    
    this.activeTarget = { el: targetEl, type: type };
    
    this.showTimeout = setTimeout(() => {
      this._show(e.clientX, e.clientY);
    }, 300); // 300ms delay
  }

  _scheduleHide() {
    clearTimeout(this.showTimeout);
    this.hideTimeout = setTimeout(() => {
      this._hide();
    }, 100);
  }

  _show(mouseX, mouseY) {
    if (!this.activeTarget) return;

    let html = '';
    
    if (this.activeTarget.type === 'node') {
      html = this._buildNodeTooltip(this.activeTarget.el.id);
    } else if (this.activeTarget.type === 'line') {
      html = this._buildLineTooltip(this.activeTarget.el);
    }

    if (!html) return;

    this.tooltipEl.innerHTML = html;
    this.tooltipEl.classList.add('visible');

    if (this.activeTarget.type === 'node') {
      // Position above the node
      const rect = this.activeTarget.el.getBoundingClientRect();
      const tooltipRect = this.tooltipEl.getBoundingClientRect();
      
      const top = rect.top - tooltipRect.height - 15;
      const left = rect.left + (rect.width / 2) - (tooltipRect.width / 2);
      
      this.tooltipEl.style.top = `${top}px`;
      this.tooltipEl.style.left = `${left}px`;
    } else {
      // Position at mouse cursor for lines
      this._updatePosition(mouseX, mouseY);
    }
  }

  _hide() {
    this.tooltipEl.classList.remove('visible');
    this.activeTarget = null;
  }

  _updatePosition(x, y) {
    const tooltipRect = this.tooltipEl.getBoundingClientRect();
    this.tooltipEl.style.top = `${y - tooltipRect.height - 15}px`;
    this.tooltipEl.style.left = `${x - (tooltipRect.width / 2)}px`;
  }

  _buildNodeTooltip(nodeId) {
    // Need access to CanvasManager to get node properties
    // In a real module system, this would be injected. We'll rely on global/window for this specific simulator
    const canvasManager = window.app && window.app.canvasManager;
    if (!canvasManager) return '';

    const node = canvasManager.getNode(nodeId);
    if (!node) return '';

    const result = this.calculator.getResult(nodeId);
    
    let statsHtml = '';
    if (result) {
      let statusIconSvg = `<svg class="tooltip-status-svg" viewBox="0 0 24 24"><polyline points="20 6 9 17 4 12"></polyline></svg>`;
      let statusText = 'Normal';
      let statusClass = 'ok';
      
      if (result.status === 'warning') {
        statusIconSvg = `<svg class="tooltip-status-svg" viewBox="0 0 24 24"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path><line x1="12" y1="9" x2="12" y2="13"></line><line x1="12" y1="17" x2="12.01" y2="17"></line></svg>`;
        statusText = 'Warning';
        statusClass = 'warning';
      }
      if (result.status === 'critical') {
        statusIconSvg = `<svg class="tooltip-status-svg" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"></circle><line x1="15" y1="9" x2="9" y2="15"></line><line x1="9" y1="9" x2="15" y2="15"></line></svg>`;
        statusText = 'Critical';
        statusClass = 'critical';
      }

      statsHtml = `
        <div class="tooltip-grid">
          <div class="tooltip-label">Loss Perangkat:</div>
          <div class="tooltip-value val-loss">-${result.equipmentLoss} dB</div>
          
          <div class="tooltip-label">Kumulatif Loss:</div>
          <div class="tooltip-value val-loss">-${result.cumulativeLoss} dB</div>
          
          <div class="tooltip-label">Rx Power In:</div>
          <div class="tooltip-value val-power">${result.powerIn !== null ? result.powerIn + ' dBm' : 'N/A'}</div>
          
          <div class="tooltip-label">Tx Power Out:</div>
          <div class="tooltip-value val-power">${result.powerOut} dBm</div>
        </div>
        <div class="tooltip-status ${statusClass}">
          ${statusIconSvg} Status: ${statusText}
        </div>
      `;
    } else {
      statsHtml = `<div class="tooltip-label">Belum terkoneksi ke sumber sinyal (OLT)</div>`;
    }

    return `
      <div class="tooltip-header">
        <span class="tooltip-badge ${node.def.category}">${node.def.category}</span>
        <span class="tooltip-title">${node.def.name}</span>
      </div>
      <div style="font-size: 11px; color: var(--text-muted); margin-bottom: 8px;">
        ${node.def.description || ''}
      </div>
      ${statsHtml}
    `;
  }

  _buildLineTooltip(pathEl) {
    const sourceId = pathEl.getAttribute('data-source');
    const result = this.calculator.getResult(sourceId);
    
    if (!result) return '';

    // Power in the fiber is the output of the source minus link/splice assumed loss
    const power = (result.powerOut - 0.1).toFixed(2);

    return `
      <div class="tooltip-header">
        <span class="tooltip-badge passive">LINK</span>
        <span class="tooltip-title">Signal Flow</span>
      </div>
      <div class="tooltip-grid">
        <div class="tooltip-label">Power Level:</div>
        <div class="tooltip-value val-power">${power} dBm</div>
      </div>
      <div style="font-size: 10px; color: var(--text-muted); margin-top: var(--sp-2); text-align: center; border-top: 1px solid var(--glass-border); padding-top: var(--sp-1);">
        Double-click untuk memutuskan koneksi
      </div>
    `;
  }
}

// Export for module usage
if (typeof module !== 'undefined' && module.exports) {
  module.exports = { TooltipManager };
}
