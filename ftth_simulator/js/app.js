/**
 * FTTH Simulator — Main Application Orchestrator
 * Bootstraps all modules and handles global UI state (modals, bottom bar).
 */

class FTTHSimulator {
  constructor() {
    this.initModules();
    this.initUI();
    this.renderPalette();
  }

  initModules() {
    this.canvasManager = new CanvasManager('canvas-container');
    this.calculator = new AttenuationCalculator(this.canvasManager);
    this.dragDropManager = new DragDropManager(this.canvasManager);
    this.connectionManager = new ConnectionManager(this.canvasManager);
    this.tooltipManager = new TooltipManager(this.calculator);

    // Make canvas available globally for tooltip manager
    window.app = this;

    // Listen to recalc events
    document.addEventListener('simulation:needs_recalc', () => {
      this.calculator.recalculate();
    });

    document.addEventListener('simulation:calculated', (e) => {
      this.updateStatusBar(e.detail.results);
      this.updateNodeLEDs(e.detail.results);
    });

    // Listen to events to show properties modal
    document.addEventListener('node:properties', (e) => {
      this.hideInfoModal();
      this.showPropertiesModal(e.detail.node);
    });

    document.addEventListener('node:deselected', () => {
      this.hidePropertiesModal();
      this.hideInfoModal();
    });

    document.addEventListener('node:info', (e) => {
      this.hidePropertiesModal();
      this.showInfoModal(e.detail.node);
    });

    // Autosave triggers
    const triggerSave = () => this.saveState();
    document.addEventListener('node:moved', triggerSave);
    document.addEventListener('node:removed', triggerSave);
    document.addEventListener('simulation:calculated', triggerSave);
    document.addEventListener('canvas:panend', triggerSave);
    document.addEventListener('canvas:zoom', triggerSave);
    
    // Load saved state
    setTimeout(() => this.loadState(), 100);
  }

  initUI() {
    // Mode Selector
    const modeSelect = document.getElementById('network-mode');
    if (modeSelect) {
      modeSelect.addEventListener('change', (e) => {
        const mode = e.target.value; // GPON or EPON
        // Update all OLTs on canvas
        this.canvasManager.getNodes().forEach(node => {
          if (node.type === 'olt') {
            this.canvasManager.updateNodeProperties(node.id, { technology: mode });
          }
        });
      });
    }

    // Modal Close
    document.getElementById('modal-close')?.addEventListener('click', () => {
      this.canvasManager.selectNode(null);
    });

    document.getElementById('info-modal-close')?.addEventListener('click', () => {
      this.hideInfoModal();
    });

    document.getElementById('info-overlay')?.addEventListener('click', (e) => {
      if (e.target === document.getElementById('info-overlay')) {
        this.hideInfoModal();
      }
    });

    // Horizontal scroll mouse wheel translation on status bar
    const statusBarText = document.getElementById('status-bar-text');
    if (statusBarText) {
      statusBarText.addEventListener('wheel', (e) => {
        if (e.deltaY !== 0) {
          e.preventDefault();
          statusBarText.scrollLeft += e.deltaY;
        }
      });
    }

    // Global reset button
    document.getElementById('btn-reset')?.addEventListener('click', async () => {
      const confirmed = await this.showConfirm('Apakah Anda yakin ingin menghapus seluruh workspace?', 'Reset Workspace', 'danger');
      if (confirmed) {
        const nodes = this.canvasManager.getNodes();
        nodes.forEach(n => this.canvasManager.removeNode(n.id));
        localStorage.removeItem('ftth_simulator_state');
        
        // Reset view
        this.canvasManager.scale = 1;
        this.canvasManager.panX = 0;
        this.canvasManager.panY = 0;
        this.canvasManager._updateTransform();
        
        const slider = document.getElementById('zoom-slider');
        const display = document.getElementById('zoom-value');
        if (slider) slider.value = 1;
        if (display) display.textContent = '100%';
      }
    });

    // Export PDF button
    document.getElementById('btn-export')?.addEventListener('click', () => {
      this.exportToPDF();
    });

    // Theme Switcher
    const themeToggle = document.getElementById('btn-theme-toggle');
    if (themeToggle) {
      const savedTheme = localStorage.getItem('ftth_simulator_theme') || 'dark';
      this.setTheme(savedTheme);

      themeToggle.addEventListener('click', () => {
        const currentTheme = document.documentElement.getAttribute('data-theme') || 'dark';
        const newTheme = currentTheme === 'light' ? 'dark' : 'light';
        this.setTheme(newTheme);
      });
    }

    // Guide Modal Events
    document.getElementById('btn-guide')?.addEventListener('click', () => {
      this.showGuideModal();
    });

    document.getElementById('guide-modal-close')?.addEventListener('click', () => {
      this.hideGuideModal();
    });

    document.getElementById('guide-overlay')?.addEventListener('click', (e) => {
      if (e.target === document.getElementById('guide-overlay')) {
        this.hideGuideModal();
      }
    });

    // Guide Modal Tabs Switcher
    const tabBtns = document.querySelectorAll('.guide-tab-btn');
    tabBtns.forEach(btn => {
      btn.addEventListener('click', (e) => {
        const targetId = e.target.getAttribute('data-target');
        
        // Deactivate all tabs
        tabBtns.forEach(b => b.classList.remove('active'));
        document.querySelectorAll('.guide-content-section').forEach(s => s.classList.remove('active'));
        
        // Activate selected tab
        e.target.classList.add('active');
        document.getElementById(targetId)?.classList.add('active');
      });
    });
  }

  setTheme(theme) {
    document.documentElement.setAttribute('data-theme', theme);
    localStorage.setItem('ftth_simulator_theme', theme);
    
    const sunIcon = document.querySelector('.theme-icon-sun');
    const moonIcon = document.querySelector('.theme-icon-moon');
    
    if (theme === 'light') {
      if (sunIcon) sunIcon.style.display = 'none';
      if (moonIcon) moonIcon.style.display = 'block';
    } else {
      if (sunIcon) sunIcon.style.display = 'block';
      if (moonIcon) moonIcon.style.display = 'none';
    }
  }

  renderPalette() {
    const activeGroup = document.getElementById('palette-active');
    const passiveGroup = document.getElementById('palette-passive');
    
    if (!activeGroup || !passiveGroup) return;

    Object.values(EQUIPMENT_CATALOG).forEach(def => {
      const el = document.createElement('div');
      el.className = 'palette-item';
      el.setAttribute('data-type', def.id);
      el.setAttribute('data-category', def.category);
      
      el.innerHTML = `
        <div class="palette-icon">${def.icon}</div>
        <div class="palette-info">
          <div class="palette-name">${def.name}</div>
          <div class="palette-desc">${def.description}</div>
        </div>
      `;

      if (def.category === 'active') {
        activeGroup.appendChild(el);
      } else {
        passiveGroup.appendChild(el);
      }
    });
  }

  updateStatusBar(results) {
    const statusEl = document.getElementById('status-bar-text');
    if (!statusEl) return;

    const nodes = this.canvasManager.getNodes();
    const connCount = this.canvasManager.connections.length;

    // -- Empty workspace --
    if (nodes.length === 0) {
      statusEl.innerHTML = '<span class="text-muted">Workspace empty. Drag equipment from the left palette.</span>';
      return;
    }

    // Count equipment by type
    const counts = {};
    nodes.forEach(n => { counts[n.type] = (counts[n.type] || 0) + 1; });

    // Find the source Tx Power (from SFP or OLT)
    let txPower = null;
    let txSource = null;
    const sfpNodes = nodes.filter(n => n.type === 'sfp');
    const oltNodes = nodes.filter(n => n.type === 'olt');

    if (sfpNodes.length > 0) {
      const sfpResult = results.get(sfpNodes[0].id);
      if (sfpResult) {
        txPower = sfpResult.powerOut;
        txSource = 'SFP';
      }
    } else if (oltNodes.length > 0) {
      const oltResult = results.get(oltNodes[0].id);
      if (oltResult) {
        txPower = oltResult.powerOut;
        txSource = 'OLT';
      }
    }

    // Collect ONT endpoint data
    const ontNodes = nodes.filter(n => n.type === 'ont');
    const ontSummaries = [];
    ontNodes.forEach(ont => {
      const res = results.get(ont.id);
      if (res) {
        const sensitivity = parseFloat(ont.properties.sensitivity) || -27;
        const margin = parseFloat((res.powerOut - sensitivity).toFixed(2));
        ontSummaries.push({
          id: ont.id,
          rxPower: res.powerOut,
          totalLoss: res.cumulativeLoss,
          sensitivity: sensitivity,
          margin: margin,
          status: res.status
        });
      }
    });

    // --- Build the HTML segments ---
    const parts = [];

    // Segment 1: Equipment counts
    const eqParts = [];
    if (counts.olt) eqParts.push(`OLT:${counts.olt}`);
    if (counts.sfp) eqParts.push(`SFP:${counts.sfp}`);
    if (counts.splitter) eqParts.push(`Splitter:${counts.splitter}`);
    if (counts.odp) eqParts.push(`ODP:${counts.odp}`);
    if (counts.cable) eqParts.push(`Cable:${counts.cable}`);
    if (counts.connector) eqParts.push(`Conn:${counts.connector}`);
    if (counts.ont) eqParts.push(`ONT:${counts.ont}`);
    
    parts.push(
      `<span class="sb-segment sb-topology">` +
      `<svg class="sb-icon-svg" viewBox="0 0 24 24"><path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"></path><path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"></path></svg> ` +
      `${nodes.length} Devices, ${connCount} Links <span class="sb-dim">(${eqParts.join(' · ')})</span>` +
      `</span>`
    );

    // Segment 2: Tx Power source
    if (txPower !== null) {
      const txSign = txPower >= 0 ? '+' : '';
      parts.push(
        `<span class="sb-segment sb-tx">` +
        `<svg class="sb-icon-svg" viewBox="0 0 24 24"><path d="M2 16.1A5 5 0 0 1 5.9 20M2 12.05A9 9 0 0 1 9.95 20M2 8A14 14 0 0 1 14 20M2 20h.01"></path><circle cx="12" cy="8" r="3"></circle><path d="M12 11v9"></path><path d="M9 20h6"></path></svg> ` +
        `Tx: <span class="sb-val text-accent">${txSign}${txPower.toFixed(1)} dBm</span>` +
        `</span>`
      );
    }

    // Segment 3: ONT results (per-ONT if multiple, single summary if one)
    if (ontSummaries.length === 0 && ontNodes.length === 0) {
      parts.push(
        `<span class="sb-segment sb-hint">` +
        `<svg class="sb-icon-svg" viewBox="0 0 24 24"><path d="M15 14c.2-1 .7-1.7 1.5-2.5 1-.9 1.5-2.2 1.5-3.5A5 5 0 0 0 8 8c0 1 .3 2.2 1.5 3.5.7.7 1.3 1.5 1.5 2.5"></path><line x1="9" y1="18" x2="15" y2="18"></line><line x1="10" y1="22" x2="14" y2="22"></line></svg> ` +
        `<span class="text-muted">Tambahkan ONT untuk evaluasi link budget</span>` +
        `</span>`
      );
    } else if (ontSummaries.length === 0 && ontNodes.length > 0) {
      parts.push(
        `<span class="sb-segment sb-hint">` +
        `<svg class="sb-icon-svg" viewBox="0 0 24 24"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path><line x1="12" y1="9" x2="12" y2="13"></line><line x1="12" y1="17" x2="12.01" y2="17"></line></svg> ` +
        `<span class="text-warning">ONT belum terhubung ke sumber sinyal</span>` +
        `</span>`
      );
    } else {
      ontSummaries.forEach((ont, idx) => {
        const lossSign = ont.totalLoss > 0 ? '-' : '';
        const rxSign = ont.rxPower >= 0 ? '+' : '';
        const marginSign = ont.margin >= 0 ? '+' : '';

        let statusIconSvg, statusClass, statusLabel;
        if (ont.status === 'critical') {
          statusIconSvg = `<svg class="sb-badge-svg" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"></circle><line x1="15" y1="9" x2="9" y2="15"></line><line x1="9" y1="9" x2="15" y2="15"></line></svg>`;
          statusClass = 'text-danger';
          statusLabel = 'FAILED';
        } else if (ont.status === 'warning') {
          statusIconSvg = `<svg class="sb-badge-svg" viewBox="0 0 24 24"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path><line x1="12" y1="9" x2="12" y2="13"></line><line x1="12" y1="17" x2="12.01" y2="17"></line></svg>`;
          statusClass = 'text-warning';
          statusLabel = 'MARGINAL';
        } else {
          statusIconSvg = `<svg class="sb-badge-svg" viewBox="0 0 24 24"><polyline points="20 6 9 17 4 12"></polyline></svg>`;
          statusClass = 'text-success';
          statusLabel = 'OK';
        }

        const ontLabel = ontSummaries.length > 1 ? ` ONT-${idx + 1}:` : '';
        parts.push(
          `<span class="sb-segment sb-budget">` +
          `<svg class="sb-icon-svg" viewBox="0 0 24 24"><line x1="18" y1="20" x2="18" y2="10"></line><line x1="12" y1="20" x2="12" y2="4"></line><line x1="6" y1="20" x2="6" y2="14"></line></svg>${ontLabel} ` +
          `Loss: <span class="sb-val text-warning">${lossSign}${ont.totalLoss.toFixed(2)} dB</span>` +
          ` → Rx: <span class="sb-val ${statusClass}">${rxSign}${ont.rxPower.toFixed(2)} dBm</span>` +
          ` <span class="sb-badge ${statusClass}">${statusIconSvg} ${statusLabel}</span>` +
          ` <span class="sb-dim">(margin: ${marginSign}${ont.margin.toFixed(1)} dB)</span>` +
          `</span>`
        );
      });
    }

    statusEl.innerHTML = parts.join('<span class="sb-divider">│</span>');
  }

  updateNodeLEDs(results) {
    // Traverse all nodes on canvas
    this.canvasManager.getNodes().forEach(node => {
      const el = document.querySelector(`#canvas-inner #${node.id}`);
      if (!el) return;

      if (node.type === 'ont') {
        const ponLed = el.querySelector(`#led-pon-${node.id}`);
        const losLed = el.querySelector(`#led-los-${node.id}`);
        const lanLed = el.querySelector(`#led-lan-${node.id}`);
        
        const res = results.get(node.id);
        
        if (ponLed && losLed && lanLed) {
          // Reset classes
          ponLed.className = 'led';
          losLed.className = 'led';
          lanLed.className = 'led';

          if (res) {
            if (res.status === 'good') {
              ponLed.classList.add('green-on');
              lanLed.classList.add('green-blink');
            } else if (res.status === 'warning') {
              ponLed.classList.add('yellow-on');
              lanLed.classList.add('green-blink');
            } else { // critical
              losLed.classList.add('red-blink');
            }
          } else {
            // Disconnected
            losLed.classList.add('red-blink');
          }
        }
      } else if (node.type === 'olt') {
        const actLed = el.querySelector('.led-panel .led-group:nth-child(2) .led');
        const outgoing = this.canvasManager.getOutgoingConnections(node.id);
        if (actLed) {
          actLed.className = 'led';
          if (outgoing.length > 0) {
            actLed.classList.add('green-blink');
          } else {
            actLed.classList.add('green-on');
          }
        }
      }
    });
  }

  showPropertiesModal(node) {
    const overlay = document.getElementById('properties-overlay');
    const title = document.getElementById('modal-title');
    const form = document.getElementById('properties-form');
    
    if (!overlay || !title || !form) return;

    title.textContent = `${node.def.name} Properties`;
    form.innerHTML = ''; // clear

    if (Object.keys(node.properties).length === 0) {
      form.innerHTML = '<div class="text-muted text-sm">No configurable properties for this device.</div>';
    } else {
      Object.keys(node.properties).forEach(key => {
        const propDef = node.def.properties[key];
        const currentValue = node.properties[key];
        
        const group = document.createElement('div');
        group.className = 'form-group';
        
        const label = document.createElement('label');
        label.className = 'form-label';
        label.textContent = propDef.label || key;
        if (propDef.unit) label.textContent += ` (${propDef.unit})`;
        
        let inputHtml = '';
        
        if (propDef.type === 'select') {
          const options = propDef.options.map(opt => 
            `<option value="${opt}" ${opt === currentValue ? 'selected' : ''}>${opt}</option>`
          ).join('');
          inputHtml = `<select class="form-control" data-key="${key}">${options}</select>`;
          
          group.appendChild(label);
          group.insertAdjacentHTML('beforeend', inputHtml);
          
          // Event listener
          const select = group.querySelector('select');
          select.addEventListener('change', (e) => {
            this.canvasManager.updateNodeProperties(node.id, { [key]: e.target.value });
          });
          
        } else if (propDef.type === 'range') {
          inputHtml = `
            <div class="range-with-value">
              <input type="range" class="form-control" data-key="${key}" 
                     min="${propDef.min}" max="${propDef.max}" step="${propDef.step || 1}" 
                     value="${currentValue}">
              <div class="range-value-display">${currentValue}</div>
            </div>
          `;
          
          group.appendChild(label);
          group.insertAdjacentHTML('beforeend', inputHtml);
          
          // Event listener
          const range = group.querySelector('input');
          const display = group.querySelector('.range-value-display');
          range.addEventListener('input', (e) => {
            const val = parseFloat(e.target.value);
            display.textContent = val;
            this.canvasManager.updateNodeProperties(node.id, { [key]: val });
          });
          
        } else if (propDef.type === 'number') {
          inputHtml = `<input type="number" class="form-control" data-key="${key}" 
                              min="${propDef.min||''}" max="${propDef.max||''}" step="${propDef.step||1}"
                              value="${currentValue}">`;
          
          group.appendChild(label);
          group.insertAdjacentHTML('beforeend', inputHtml);
          
          const numInput = group.querySelector('input');
          numInput.addEventListener('change', (e) => {
            this.canvasManager.updateNodeProperties(node.id, { [key]: parseFloat(e.target.value) });
          });
        }

        form.appendChild(group);
      });

      // Add a Delete button at the bottom of the modal
      const deleteBtnContainer = document.createElement('div');
      deleteBtnContainer.style.marginTop = 'var(--sp-4)';
      deleteBtnContainer.style.borderTop = '1px solid var(--glass-border)';
      deleteBtnContainer.style.paddingTop = 'var(--sp-4)';
      deleteBtnContainer.style.display = 'flex';
      deleteBtnContainer.style.justifyContent = 'flex-end';

      const deleteBtn = document.createElement('button');
      deleteBtn.className = 'btn btn-danger';
      deleteBtn.style.width = '100%';
      deleteBtn.innerHTML = `
        <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-right: 6px; vertical-align: middle;"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg>
        Hapus Perangkat
      `;
      deleteBtn.addEventListener('click', async () => {
        const confirmed = await this.showConfirm(`Apakah Anda yakin ingin menghapus perangkat ${node.def.name} ini?`, 'Hapus Perangkat', 'danger');
        if (confirmed) {
          this.canvasManager.removeNode(node.id);
          this.hidePropertiesModal();
        }
      });
      deleteBtnContainer.appendChild(deleteBtn);
      form.appendChild(deleteBtnContainer);
    }

    overlay.classList.add('active');
  }

  showConfirm(message, title = 'Konfirmasi', type = 'danger') {
    return new Promise((resolve) => {
      const overlay = document.getElementById('confirm-overlay');
      const titleEl = document.getElementById('confirm-title');
      const messageEl = document.getElementById('confirm-message');
      const btnCancel = document.getElementById('confirm-btn-cancel');
      const btnYes = document.getElementById('confirm-btn-yes');
      const btnClose = document.getElementById('confirm-close');

      if (!overlay || !titleEl || !messageEl || !btnCancel || !btnYes || !btnClose) {
        resolve(window.confirm(message));
        return;
      }

      titleEl.textContent = title;
      messageEl.textContent = message;

      // Configure button styling based on type
      if (type === 'danger') {
        btnYes.className = 'btn btn-danger';
        btnYes.textContent = 'Hapus';
      } else {
        btnYes.className = 'btn btn-primary';
        btnYes.textContent = 'Ya';
      }

      const cleanup = (result) => {
        overlay.classList.remove('active');
        btnYes.replaceWith(btnYes.cloneNode(true));
        btnCancel.replaceWith(btnCancel.cloneNode(true));
        btnClose.replaceWith(btnClose.cloneNode(true));
        resolve(result);
      };

      const newBtnYes = document.getElementById('confirm-btn-yes');
      const newBtnCancel = document.getElementById('confirm-btn-cancel');
      const newBtnClose = document.getElementById('confirm-close');

      newBtnYes.addEventListener('click', () => cleanup(true));
      newBtnCancel.addEventListener('click', () => cleanup(false));
      newBtnClose.addEventListener('click', () => cleanup(false));

      overlay.classList.add('active');
    });
  }

  hidePropertiesModal() {
    const overlay = document.getElementById('properties-overlay');
    if (overlay) overlay.classList.remove('active');
  }

  showGuideModal() {
    const overlay = document.getElementById('guide-overlay');
    if (overlay) overlay.classList.add('active');
  }

  hideGuideModal() {
    const overlay = document.getElementById('guide-overlay');
    if (overlay) overlay.classList.remove('active');
  }

  showInfoModal(node) {
    const overlay = document.getElementById('info-overlay');
    const title = document.getElementById('info-modal-title');
    const content = document.getElementById('info-modal-content');
    
    if (!overlay || !title || !content) return;

    title.textContent = `Detail Estimasi: ${node.def.name}`;
    
    const res = this.calculator.getResult(node.id);
    let detailsHtml = '';

    if (res) {
      const inPower = res.powerIn !== null ? `${res.powerIn.toFixed(2)} dBm` : 'N/A';
      const outPower = res.powerOut !== null ? `${res.powerOut.toFixed(2)} dBm` : 'N/A';
      const componentLoss = res.equipmentLoss !== null ? `${res.equipmentLoss.toFixed(2)} dB` : 'N/A';
      const totalLoss = res.cumulativeLoss !== null ? `${res.cumulativeLoss.toFixed(2)} dB` : 'N/A';
      
      let statusClass = 'badge-success';
      let statusText = 'Sinyal Baik';
      
      if (res.status === 'warning') {
        statusClass = 'badge-warning';
        statusText = 'Sinyal Marginal';
      } else if (res.status === 'critical') {
        statusClass = 'badge-danger';
        statusText = 'Sinyal Mati/Lemah';
      }

      detailsHtml = `
        <div style="display: flex; flex-direction: column; gap: var(--sp-4);">
          <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid var(--border-default); padding-bottom: var(--sp-2);">
            <span class="text-secondary font-mono">Status Koneksi</span>
            <span class="badge ${statusClass}">${statusText}</span>
          </div>
          <div style="display: grid; grid-template-columns: 1fr auto; gap: var(--sp-2); font-size: var(--text-base);">
            <span class="text-secondary">Input Power</span>
            <strong class="font-mono" style="color: var(--text-primary);">${inPower}</strong>
            
            <span class="text-secondary">Redaman Komponen</span>
            <strong class="font-mono" style="color: var(--color-warning);">${componentLoss}</strong>
            
            <span class="text-secondary">Total Redaman Jalur</span>
            <strong class="font-mono" style="color: var(--color-danger);">${totalLoss}</strong>
            
            <span class="text-secondary">Output Power</span>
            <strong class="font-mono" style="color: var(--accent-teal);">${outPower}</strong>
          </div>
        </div>
      `;
    } else {
      detailsHtml = `
        <div class="text-muted text-sm" style="text-align: center; padding: var(--sp-4);">
          Belum ada sinyal terhubung ke perangkat ini.<br>
          Hubungkan kabel dari OLT ke input perangkat ini untuk melakukan kalkulasi redaman.
        </div>
      `;
    }

    content.innerHTML = detailsHtml;
    overlay.classList.add('active');
  }

  hideInfoModal() {
    const overlay = document.getElementById('info-overlay');
    if (overlay) {
      overlay.classList.remove('active');
    }
  }

  saveState() {
    const nodes = Array.from(this.canvasManager.nodes.values()).map(n => ({
      id: n.id,
      type: n.type,
      x: n.x,
      y: n.y,
      properties: n.properties
    }));
    
    const state = {
      nodes,
      connections: this.canvasManager.connections,
      panX: this.canvasManager.panX,
      panY: this.canvasManager.panY,
      scale: this.canvasManager.scale
    };
    
    localStorage.setItem('ftth_simulator_state', JSON.stringify(state));
  }

  loadState() {
    const saved = localStorage.getItem('ftth_simulator_state');
    if (!saved) return;
    
    try {
      const state = JSON.parse(saved);
      
      if (state.scale) {
        this.canvasManager.scale = state.scale;
        this.canvasManager.panX = state.panX || 0;
        this.canvasManager.panY = state.panY || 0;
        this.canvasManager._updateTransform();
        
        const slider = document.getElementById('zoom-slider');
        const display = document.getElementById('zoom-value');
        if (slider) slider.value = this.canvasManager.scale;
        if (display) display.textContent = Math.round(this.canvasManager.scale * 100) + '%';
      }

      state.nodes.forEach(n => {
        const node = this.canvasManager.addNode(n.type, n.x, n.y, n.id);
        if (node) {
          node.properties = n.properties;
          // Refresh label immediately
          this.canvasManager.updateNodeProperties(node.id, n.properties);
        }
      });

      state.connections.forEach(c => {
        this.canvasManager.addConnection(c.sourceId, c.targetId);
      });
      
      this.connectionManager.redrawAllConnections();
      this.calculator.recalculate();
      
    } catch (e) {
      console.error('Failed to load state', e);
    }
  }

  exportToPDF() {
    const printArea = document.getElementById('print-area');
    const tableContainer = document.getElementById('print-table-container');
    const diagramContainer = document.getElementById('print-diagram-container');
    
    const modeSelect = document.getElementById('mode-select');
    const mode = modeSelect ? modeSelect.value : 'GPON';
    document.getElementById('print-date').textContent = `Generated on: ${new Date().toLocaleString()} | Mode: ${mode}`;
    
    // 1. Generate Table
    let tableHtml = `
      <table class="print-table">
        <thead>
          <tr>
            <th>ID</th>
            <th>Type</th>
            <th>Properties</th>
            <th>Input Power</th>
            <th>Attenuation</th>
            <th>Output Power</th>
          </tr>
        </thead>
        <tbody>
    `;
    
    const nodes = this.canvasManager.getNodes();
    const results = this.calculator.results;
    
    // Sort nodes generally by X position for better logical flow
    const sortedNodes = [...nodes].sort((a, b) => a.x - b.x);
    
    sortedNodes.forEach(node => {
      const res = results.get(node.id);
      
      let propsStr = Object.entries(node.properties).map(([k,v]) => {
         const label = node.def.properties[k]?.label || k;
         const unit = node.def.properties[k]?.unit || '';
         return `${label}: ${v}${unit}`;
      }).join(', ');
      if (!propsStr) propsStr = '-';
      
      let inPow = '-';
      let outPow = '-';
      let loss = '-';
      
      if (res) {
        if (res.powerIn !== null && res.powerIn !== undefined) {
          inPow = res.powerIn.toFixed(2) + ' dBm';
        } else {
          inPow = 'N/A';
        }
        if (res.powerOut !== null && res.powerOut !== undefined) {
          outPow = res.powerOut.toFixed(2) + ' dBm';
        }
        if (res.equipmentLoss !== null && res.equipmentLoss !== undefined) {
          loss = res.equipmentLoss.toFixed(2) + ' dB';
        }
      }
      
      if (node.def.category === 'olt') {
         inPow = 'N/A';
         loss = 'N/A';
      }
      
      tableHtml += `
        <tr>
          <td>${node.id.split('_')[1] || node.id}</td>
          <td>${node.def.name}</td>
          <td>${propsStr}</td>
          <td>${inPow}</td>
          <td>${loss}</td>
          <td>${outPow}</td>
        </tr>
      `;
    });
    tableHtml += `</tbody></table>`;
    tableContainer.innerHTML = tableHtml;
    
    // 2. Clone Diagram
    diagramContainer.innerHTML = '';
    const innerClone = document.getElementById('canvas-inner').cloneNode(true);
    
    // Clean up interactive elements
    innerClone.querySelectorAll('.eq-node-settings, .eq-node-info').forEach(el => el.remove());
    innerClone.querySelectorAll('.port').forEach(el => {
      // Make ports visibly static without hover
      el.style.opacity = '1';
      el.style.transform = 'none';
      el.style.border = '2px solid #555';
    });
    
    // Calculate bounding box
    let minX = Infinity, minY = Infinity, maxX = -Infinity, maxY = -Infinity;
    nodes.forEach(n => {
       if (n.x < minX) minX = n.x;
       if (n.y < minY) minY = n.y;
       if (n.x > maxX) maxX = n.x;
       if (n.y > maxY) maxY = n.y;
    });
    
    if (nodes.length === 0) {
      minX = 0; minY = 0; maxX = 500; maxY = 500;
    }
    
    const nodeWidth = 140; // rough width
    const nodeHeight = 80;
    const diagramWidth = (maxX - minX) + nodeWidth + 100; // padding
    const diagramHeight = (maxY - minY) + nodeHeight + 100;
    
    // Normalize positions
    innerClone.style.transform = 'none';
    innerClone.style.position = 'relative';
    innerClone.style.width = diagramWidth + 'px';
    innerClone.style.height = diagramHeight + 'px';
    
    const xOffset = 50 - minX;
    const yOffset = 50 - minY;
    
    // Shift nodes
    innerClone.querySelectorAll('.eq-node').forEach(nodeEl => {
       const curX = parseFloat(nodeEl.style.left);
       const curY = parseFloat(nodeEl.style.top);
       nodeEl.style.left = (curX + xOffset) + 'px';
       nodeEl.style.top = (curY + yOffset) + 'px';
    });
    
    // Shift SVG connections (must run BEFORE stripping element IDs)
    const svgLayer = innerClone.querySelector('#connections-layer');
    if (svgLayer) {
       // Explicitly set dimensions in pixels so percentages do not collapse during print
       svgLayer.style.width = diagramWidth + 'px';
       svgLayer.style.height = diagramHeight + 'px';
       svgLayer.setAttribute('width', diagramWidth);
       svgLayer.setAttribute('height', diagramHeight);
       
       // Wrap all paths inside a <g> group element to translate them cleanly
       const gEl = document.createElementNS('http://www.w3.org/2000/svg', 'g');
       gEl.setAttribute('transform', `translate(${xOffset}, ${yOffset})`);
       
       // Move all children of the SVG into the group
       while (svgLayer.firstChild) {
         gEl.appendChild(svgLayer.firstChild);
       }
       svgLayer.appendChild(gEl);
    }

    // Strip ID attributes to prevent duplicate IDs in the document
    innerClone.removeAttribute('id');
    innerClone.querySelectorAll('[id]').forEach(el => el.removeAttribute('id'));
    
    // Scale container if it's too wide or too tall for a single A4 Landscape page
    // Usable page height is limited to keep the table on the same page.
    const maxA4Width = 1000;
    const maxA4Height = 230; // Keep diagram compact vertically
    
    const widthScale = diagramWidth > maxA4Width ? maxA4Width / diagramWidth : 1;
    const heightScale = diagramHeight > maxA4Height ? maxA4Height / diagramHeight : 1;
    const scale = Math.min(widthScale, heightScale);
    
    innerClone.style.transformOrigin = 'top left';
    if (scale !== 1) {
      innerClone.style.transform = `scale(${scale})`;
    }
    diagramContainer.style.height = (diagramHeight * scale) + 'px';
    diagramContainer.style.width = (diagramWidth * scale) + 'px';
    
    diagramContainer.appendChild(innerClone);
    
    // Clean up print container after printing completes
    const cleanup = () => {
      diagramContainer.innerHTML = '';
      tableContainer.innerHTML = '';
    };

    window.addEventListener('afterprint', cleanup, { once: true });
    
    // Trigger print
    setTimeout(() => {
      window.print();
      // Call cleanup directly in case afterprint doesn't trigger in some browsers
      cleanup();
    }, 500); // 500ms to ensure DOM updates and images/styles are flushed
  }

  showToast(message, type = 'error') {
    let toastContainer = document.getElementById('toast-container');
    if (!toastContainer) {
      toastContainer = document.createElement('div');
      toastContainer.id = 'toast-container';
      toastContainer.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        z-index: 9999;
        display: flex;
        flex-direction: column;
        gap: 10px;
        pointer-events: none;
      `;
      document.body.appendChild(toastContainer);
    }

    const toast = document.createElement('div');
    toast.style.cssText = `
      background: var(--glass-bg-strong);
      border: 1px solid ${type === 'error' ? 'var(--color-danger)' : 'var(--glass-border)'};
      border-radius: var(--radius-md);
      padding: var(--sp-3) var(--sp-4);
      color: var(--text-primary);
      font-size: var(--text-sm);
      font-family: sans-serif;
      box-shadow: var(--shadow-lg);
      backdrop-filter: blur(var(--glass-blur));
      -webkit-backdrop-filter: blur(var(--glass-blur));
      display: flex;
      align-items: center;
      gap: 10px;
      pointer-events: auto;
      transform: translateX(120%);
      transition: transform 300ms cubic-bezier(0.175, 0.885, 0.32, 1.275);
    `;

    const icon = type === 'error' ? '❌' : 'ℹ️';
    toast.innerHTML = `
      <span>${icon}</span>
      <span style="flex: 1; font-weight: 500; text-align: left;">${message}</span>
    `;

    toastContainer.appendChild(toast);

    // Trigger slide-in
    setTimeout(() => {
      toast.style.transform = 'translateX(0)';
    }, 10);

    // Slide-out and remove
    setTimeout(() => {
      toast.style.transform = 'translateX(120%)';
      setTimeout(() => {
        toast.remove();
      }, 300);
    }, 4000);
  }
}

// Bootstrap
document.addEventListener('DOMContentLoaded', () => {
  new FTTHSimulator();
});
