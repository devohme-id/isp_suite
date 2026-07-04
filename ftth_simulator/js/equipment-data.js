/**
 * FTTH Simulator — Equipment Data & Constants
 * Defines all standard attenuation values and equipment specifications
 * based on ITU-T G.984 / IEEE 802.3ah and typical real-world values.
 */

// Global Constants for Attenuation
const STANDARDS = {
  FIBER_LOSS: {
    '1310nm': 0.35, // dB/km (Typical for SM fiber at 1310nm - Upstream GPON)
    '1490nm': 0.25, // dB/km (Typical for SM fiber at 1490nm - Downstream GPON)
    '1550nm': 0.22  // dB/km (Typical for SM fiber at 1550nm - CATV overlay)
  },
  SPLITTER_LOSS: {
    '1:2':  3.5,
    '1:4':  7.0,
    '1:8':  10.5,
    '1:16': 14.0,
    '1:32': 17.5,
    '1:64': 21.0
  },
  CONNECTOR_LOSS: {
    'SC/UPC': 0.5,
    'SC/APC': 0.3,
    'Fusion': 0.1,
    'Mechanical': 0.5
  },
  SENSITIVITY: {
    'GPON': -27, // dBm
    'EPON': -24  // dBm
  }
};

// SVG Icon Data (Inline SVGs for equipment)
const ICONS = {
  olt: `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="4" width="20" height="16" rx="2" ry="2"></rect><path d="M6 8h.01"></path><path d="M10 8h.01"></path><path d="M14 8h.01"></path><path d="M18 8h.01"></path><path d="M6 12h.01"></path><path d="M10 12h.01"></path><path d="M14 12h.01"></path><path d="M18 12h.01"></path><path d="M6 16h.01"></path><path d="M10 16h.01"></path><path d="M14 16h.01"></path><path d="M18 16h.01"></path></svg>`,
  sfp: `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 10v6a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-6"></path><path d="M6 4v6"></path><path d="M10 4v6"></path><path d="M14 4v6"></path><path d="M18 4v6"></path><path d="M4 10h16"></path></svg>`,
  splitter: `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 12h4l4-6h8"></path><path d="M12 12l4 6h4"></path></svg>`,
  cable: `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 9a2 2 0 0 1 2-2h12a2 2 0 0 1 2 2v6a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V9z"></path><path d="M2 12h2"></path><path d="M20 12h2"></path><circle cx="8" cy="12" r="1"></circle><circle cx="16" cy="12" r="1"></circle></svg>`,
  ont: `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 8h14a2 2 0 0 1 2 2v8a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-8a2 2 0 0 1 2-2z"></path><path d="M15 4v4"></path><path d="M9 4v4"></path><path d="M6 12h.01"></path><path d="M6 16h.01"></path></svg>`,
  connector: `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M8 8v8"></path><path d="M16 8v8"></path><path d="M4 12h4"></path><path d="M16 12h4"></path><rect x="8" y="10" width="8" height="4"></rect></svg>`
};

/**
 * Equipment Catalog
 * Contains definitions for all draggable items.
 */
const EQUIPMENT_CATALOG = {
  // --- ACTIVE EQUIPMENT ---
  olt: {
    id: 'olt',
    name: 'OLT',
    fullName: 'Optical Line Terminal',
    category: 'active',
    icon: ICONS.olt,
    description: 'Sumber sinyal pusat',
    ports: { input: 0, output: 1 },
    properties: {
      technology: { label: 'Teknologi', type: 'select', options: ['GPON', 'EPON'], default: 'GPON' }
    },
    getLabel: (props) => `OLT (${props.technology})`,
    // OLT generates signal, it doesn't attenuate
    getAttenuation: () => 0
  },
  
  sfp: {
    id: 'sfp',
    name: 'SFP Module',
    fullName: 'Small Form-factor Pluggable',
    category: 'active',
    icon: ICONS.sfp,
    description: 'Modul Tx/Rx (Transceiver)',
    ports: { input: 1, output: 1 },
    properties: {
      sfpClass: { label: 'Class', type: 'select', options: ['Class B+', 'Class C+', 'EPON PX20+'], default: 'Class B+' },
      txPower: { label: 'Tx Power', type: 'range', min: -3, max: 15, step: 0.1, default: 3.0, unit: 'dBm' }
    },
    getLabel: (props) => `${props.sfpClass}`,
    // SFP sets the initial power level (or acts as an amplifier if placed inline, though typically it's at the OLT)
    // For this simulation, we'll treat it as a component that injects power.
    // If it's connected to an OLT, the SFP determines the starting Tx.
    getAttenuation: () => 0 
  },

  ont: {
    id: 'ont',
    name: 'ONT / ONU',
    fullName: 'Optical Network Terminal',
    category: 'active',
    icon: ICONS.ont,
    description: 'Perangkat di sisi pelanggan',
    ports: { input: 1, output: 0 },
    properties: {
      sensitivity: { label: 'Rx Sensitivity', type: 'number', default: -27, unit: 'dBm' }
    },
    getLabel: (props) => `ONT`,
    getAttenuation: () => 0 // End point, no outgoing attenuation
  },

  // --- PASSIVE EQUIPMENT ---
  splitter: {
    id: 'splitter',
    name: 'Splitter',
    fullName: 'Optical Splitter',
    category: 'passive',
    icon: ICONS.splitter,
    description: 'Pembagi sinyal optik',
    ports: { input: 1, output: 1 }, // Visually 1 output port that can take multiple connections
    properties: {
      ratio: { label: 'Rasio Splitter', type: 'select', options: ['1:2', '1:4', '1:8', '1:16', '1:32', '1:64'], default: '1:8' }
    },
    getLabel: (props) => `Splitter ${props.ratio}`,
    getAttenuation: (props) => STANDARDS.SPLITTER_LOSS[props.ratio]
  },

  odp: {
    id: 'odp',
    name: 'ODP',
    fullName: 'Optical Distribution Point',
    category: 'passive',
    icon: ICONS.splitter,
    description: 'Titik distribusi dengan splitter',
    ports: { input: 1, output: 1 },
    properties: {
      ratio: { label: 'Internal Splitter', type: 'select', options: ['1:2', '1:4', '1:8', '1:16'], default: '1:8' },
      connLoss: { label: 'Adapter Loss', type: 'number', default: 0.3, unit: 'dB', step: 0.1 }
    },
    getLabel: (props) => `ODP (${props.ratio})`,
    // ODP loss is splitter loss + connector loss
    getAttenuation: (props) => STANDARDS.SPLITTER_LOSS[props.ratio] + (props.connLoss || 0)
  },

  fiber_cable: {
    id: 'fiber_cable',
    name: 'Kabel Fiber',
    fullName: 'Kabel Distribusi/Feeder',
    category: 'passive',
    icon: ICONS.cable,
    description: 'Kabel optik single mode',
    ports: { input: 1, output: 1 },
    properties: {
      length: { label: 'Panjang Kabel', type: 'range', min: 0.1, max: 50, step: 0.1, default: 5.0, unit: 'km' },
      wavelength: { label: 'Wavelength', type: 'select', options: ['1310nm', '1490nm', '1550nm'], default: '1490nm' },
      splices: { label: 'Jumlah Sambungan (Splice)', type: 'number', default: 0, min: 0, max: 20 }
    },
    getLabel: (props) => `${props.length} km`,
    getAttenuation: (props) => {
      const fiberLoss = props.length * STANDARDS.FIBER_LOSS[props.wavelength];
      const spliceLoss = (props.splices || 0) * STANDARDS.CONNECTOR_LOSS['Fusion'];
      return fiberLoss + spliceLoss;
    }
  },

  drop_cable: {
    id: 'drop_cable',
    name: 'Kabel Drop',
    fullName: 'Drop Core Cable',
    category: 'passive',
    icon: ICONS.cable,
    description: 'Kabel instalasi pelanggan',
    ports: { input: 1, output: 1 },
    properties: {
      length: { label: 'Panjang', type: 'range', min: 10, max: 500, step: 10, default: 100, unit: 'm' }
    },
    getLabel: (props) => `${props.length} m`,
    getAttenuation: (props) => {
      // Convert meters to km
      const lengthKm = props.length / 1000;
      return lengthKm * STANDARDS.FIBER_LOSS['1490nm']; // Usually downstream
    }
  },

  connector: {
    id: 'connector',
    name: 'Konektor/Adapter',
    fullName: 'Optical Connector/Adapter',
    category: 'passive',
    icon: ICONS.connector,
    description: 'Titik sambung patchcord',
    ports: { input: 1, output: 1 },
    properties: {
      type: { label: 'Tipe', type: 'select', options: ['SC/UPC', 'SC/APC'], default: 'SC/APC' }
    },
    getLabel: (props) => props.type,
    getAttenuation: (props) => STANDARDS.CONNECTOR_LOSS[props.type]
  }
};

// Export for module usage
if (typeof module !== 'undefined' && module.exports) {
  module.exports = { STANDARDS, EQUIPMENT_CATALOG, ICONS };
}
