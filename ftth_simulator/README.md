# FTTH Attenuation Simulator

An interactive, premium web-based simulator designed to model Fiber-to-the-Home (FTTH) network topologies and estimate optical propagation loss in real-time. Built entirely client-side with native web technologies (**HTML5, CSS3, and Vanilla JavaScript**), it provides telecommunication engineers, students, and network designers with a visual and educational environment to calculate optical link budgets and verify signal margins.

**Live Demo:** [https://devohme-id.github.io/fttx_simulator/](https://devohme-id.github.io/fttx_simulator/)

---

## Key Features

### 1. Interactive Workspace (Pan & Zoom)
* **Smooth Panning:** Click and drag the empty canvas grid space to navigate an infinite canvas.
* **Focal Zooming:** Zoom in and out (from 30% up to 200%) using your mouse scroll wheel or trackpad pinch-to-zoom. Zoom follows your cursor position dynamically (zoom-to-pointer) with proportional, clamped delta scaling for buttery smooth operation.
* **Grid Alignment:** Dot-grid background adapts to Light/Dark themes for comfortable editing.

### 2. Realistic Hardware Casings & Theme Sync
Every equipment card is custom-styled to mimic real-world hardware, adapting visual gradients according to the active theme:
* **OLT (Optical Line Terminal):** Styled as a dark brushed-metal 1U rackmount chassis with mounting ears, screws, and air vents.
* **SFP Module (Transceiver):** Zinc-alloy metallic silver transceiver casing with copper/gold contact fingers and colored latch levers. Designed to remain silver/white metallic across both Light and Dark themes to preserve its signature physical look.
* **ONT (Optical Network Terminal):** Residential desktop router housing with dual rear antennas (that rotate dynamically on hover) and top heat vents.
* **Splitter & ODP:** Transparent fiber cassette trays with internal fiber routing for Splitters, and gray double-lock cabinet boxes with hinges for ODPs.
* **Fiber Spool & Adapters:** Wooden flanges winding yellow fiber cable, and standard SC connectors.
* **Adaptive Sidebar:** Equipment palette cards in the sidebar dynamically match the active theme's background and border variables.

### 3. Active Status Indicators (Live LEDs)
Nodes feature live indicators that react to the simulation's current state:
* **OLT:** 
  * `ACT` (Activity) flashes green when there is an active downstream path connected.
* **ONT:**
  * `PWR` (Power) glows solid green.
  * `PON` (Passive Optical Network) glows green if the received power is good (>= -24 dBm) or yellow under warning/marginal signal conditions.
  * `LOS` (Loss of Signal) flashes red rapidly if the connection is cut or the signal drops below the sensitivity threshold (< -27 dBm).
  * `LAN` flashes green to simulate active local traffic when a path is functional.

### 4. Smart Visual Ports & SC Color-Coding
* **Square SC Adapters:** Connector sockets are designed as square flanges representing actual SC adapter sleeves.
* **Core Glowing:** Port cores emit a bright glowing teal/cyan dot when a cable is connected.
* **Adapter Classification:** Input ports use a blue flange representing **SC/UPC** (Ultra Physical Contact, flat polish), while output ports use a green flange representing **SC/APC** (Angled Physical Contact, 8° polish).

### 5. Port Connection Validation & Capacity Limits
The system enforces realistic connection capacity rules per device type:
* **Splitters & ODP:** Outgoing connection count is capped by the selected splitter ratio (e.g., a 1:2 Splitter allows at most 2 outgoing connections; a 1:8 allows 8).
* **OLT:** Unlimited outgoing connections — freely attach multiple SFP Modules (exception by design).
* **All Other Devices** (SFP, Fiber Cable, Connector, ONT): Limited to a maximum of **1** outgoing connection.
* **Ratio Protection:** Reducing a Splitter/ODP ratio (e.g., 1:8 → 1:2) is blocked if the device already has more active connections than the new limit allows. The dropdown is automatically reverted.
* **Toast Notifications:** Validation errors are displayed via slide-in Glassmorphism toast notifications at the top-right corner.

### 6. Multi-Equipment Selection & Group Dragging
* **Shift+Click Toggle:** Hold Shift and click nodes to add or remove them from the selection set.
* **Shift+Drag Marquee Box:** Hold Shift and drag on empty canvas to draw a semi-transparent dashed selection rectangle. All overlapping nodes are auto-selected.
* **Group Dragging:** Drag any selected node to move the entire selected group together, preserving relative positions and alignment.
* **Group Deletion:** Press Delete/Backspace when multiple nodes are selected. A confirmation dialog indicates the count of devices to be removed.
* **Deselection:** Click on empty canvas space without Shift to clear all selections.

### 7. Floating Selected Actions Overlay
* **Prevent Name Truncation:** Contextual buttons (Info, Settings, Delete) are hidden by default to keep the card headers tidy and prevent equipment name labels from being cut off.
* **Spring Bounce Animation:** Click once on any node to select it, and a larger actions menu (24px buttons with 14px SVGs) slides up from the bottom of the card body with a spring scale-up bounce.

### 8. Attenuation Logic & Real-time Calculations
As you modify properties or connect lines, the simulation calculates path loss recursively from OLT to ONT:
* **Connection Path:** Uses smooth SVG Bezier curves showing signal flow animations. Curves turn Green (Good), Yellow (Warning), or Red (Critical) depending on local power level.
* **Short Connections:** Enhanced Bezier algorithms prevent overshooting or visual looping on closely positioned nodes.
* **Loss Sheet (Rincian Kalkulasi):** Clicking the Info button on any node details the mathematical loss budget (cable attenuation, splitter ratios, connector/splicing insertion loss) step-by-step.
* **Landscape PDF Report:** Generates an A4 Landscape layout report compiling the network diagram (including SVG connection lines), symbol legend, and a structured link budget table ready to print or save.

### 9. Interactive Status Bar with SVG Icons
* **Vector Icons:** All status bar and tooltip icons use crisp inline SVG vectors (no emoji) for consistent, sharp rendering across all platforms and DPI scales.
* **Horizontal Scroll:** Vertical mouse wheel scrolling on the status bar is automatically translated to horizontal scrolling, allowing access to all ONT summaries even when clipped by the zoom slider.
* **Live Summary:** Displays device counts, active links, Tx Power source, and per-ONT link budget results (Rx Power, Total Loss, Margin, Status badge).

### 10. Autosave & Autoload (localStorage)
* **Persistent State:** All simulation state (device positions, connections, configuration parameters, pan coordinates, and zoom level) is automatically saved to browser `localStorage` on every meaningful interaction (move, connect, disconnect, configure, pan, zoom).
* **Seamless Restore:** When the page is reopened, the last topology is fully restored automatically. Use the **Reset** button to clear saved data and start fresh.

### 11. Organized Header Menu
* **Structured Layout:** Header controls are organized with a visual divider separating secondary actions (Panduan, Reset) from the primary action.
* **Primary CTA:** The **Export PDF** button is styled as a teal-colored primary Call-to-Action for quick access to report generation.
* **Theme Toggle:** A circular icon button for instant Light/Dark mode switching.

---

## Standard Loss & Calculation Parameters

The calculator evaluates propagation losses using standard international ITU-T G.984 (GPON), IEEE 802.3ah (EPON), and G.652 Single Mode Fiber values:

| Component | Parameter / Type | Standard Loss (dB) |
| :--- | :--- | :--- |
| **SM Fiber Cable (1490nm)** | Attenuation per km | `0.25 dB / km` |
| **SM Fiber Cable (1310nm)** | Attenuation per km | `0.35 dB / km` |
| **Splicing (Fusion Joint)** | Per point / las fiber | `0.10 dB` |
| **SC/APC Connector** | Angled Polish (Green) | `0.30 dB` |
| **SC/UPC Connector** | Flat Polish (Blue) | `0.50 dB` |
| **Adapter Connector Loss** | Air-gap connection | `0.20 dB` |
| **Passive Splitter 1:2** | Power Split Loss | `3.50 dB` |
| **Passive Splitter 1:4** | Power Split Loss | `7.00 dB` |
| **Passive Splitter 1:8** | Power Split Loss | `10.50 dB` |
| **Passive Splitter 1:16** | Power Split Loss | `14.00 dB` |
| **Passive Splitter 1:32** | Power Split Loss | `17.50 dB` |
| **Passive Splitter 1:64** | Power Split Loss | `21.00 dB` |

### ONT Sensitivity Signal Margins:
* **Good Signal (Hijau):** `Rx Power >= -24.00 dBm` (PON LED solid green, LAN flashing).
* **Warning / Marginal (Kuning):** `Rx Power between -24.01 dBm and -27.00 dBm` (PON LED solid yellow).
* **Critical / No Signal (Merah):** `Rx Power < -27.00 dBm` (LOS LED flashes red rapidly).

---

## Interactive Mouse & Keyboard Shortcuts

| Action | Control / Key | Description |
| :--- | :--- | :--- |
| **Add Equipment** | **Drag & Drop** | Drag a device card from the sidebar list and drop it on the canvas |
| **Move Equipment** | **Left-Click + Drag** | Drag cards around the canvas at any time |
| **Select Node** | **Single Click** | Highlights the node border and slides up the actions overlay panel |
| **Multi-Select (Toggle)** | **Shift + Click** | Hold Shift and click a node to add/remove it from the selection group |
| **Marquee Selection** | **Shift + Drag** (empty canvas) | Hold Shift and drag on empty canvas to draw a selection box; overlapping nodes are auto-selected |
| **Move Group** | **Drag** any selected node | Drag one selected node to move the entire selected group together |
| **Connect Cables** | **Click OUT → Click IN** | Click a green port (*OUT*), then click a yellow port (*IN*) to link them |
| **Configure Properties** | **Double-Click** / Click Gear icon | Opens the configurations properties modal (or select node and click Gear) |
| **Delete Node** | **Delete / Backspace** | Press while a node is selected (or click Delete icon in the floating actions overlay) |
| **Delete Multiple Nodes** | **Delete / Backspace** | Press while multiple nodes are selected; confirmation dialog shows count |
| **Delete Connection** | **Double-Click** on line | Instantly disconnects the cable |
| **Delete Connection (Alt)**| **Click + Delete** | Click the line until it glows blue, then press Delete/Backspace |
| **Canvas Pan** | **Click & Hold empty canvas + Drag** | Move around the workspace canvas |
| **Canvas Zoom** | **Scroll Wheel / Pinch** | Zoom in/out focusing on the cursor coordinates (zoom-to-pointer) |
| **Status Bar Scroll** | **Scroll Wheel** on status bar | Vertical wheel is auto-translated to horizontal scroll |
| **Toggle Theme** | Click **Theme** icon (sun/moon) | Switch between Light and Dark mode instantly |
| **Reset Workspace** | Click **Reset** button | Clears all devices, connections, autosave data, resets pan & zoom |
| **Export PDF** | Click **Export PDF** button | Download A4 Landscape blueprint with SVG connection diagram & budget table |

---

## Project Structure

The project has a modular client-side layout:

```text
├── index.html            # Main markup page, modal layouts, and guide/glossary content
├── css/
│   ├── index.css         # Design system tokens, variables, reset styles
│   ├── layout.css        # Core layout (Header, Sidebar, Workspace, Status Bar, Print styles)
│   ├── sidebar.css       # Sidebar equipment palette styles
│   ├── canvas.css        # Equipment casing designs, LEDs, actions bar, ports, selection marquee
│   ├── tooltip.css       # Floating hover statistics tooltips (SVG icon styles)
│   └── modal.css         # Glassmorphism overlay dialogs (Confirm, Settings, Loss Sheet, Guide)
└── js/
    ├── app.js            # Main orchestrator, modal controls, autosave/autoload, PDF export, toast, theme, status bar
    ├── equipment-data.js # Source-of-truth default parameters and properties form fields
    ├── calculator.js     # Path attenuation propagation engine (recursive tree traversal)
    ├── canvas.js         # Canvas rendering, coordinate transforms, multi-node selection, marquee, port validation
    ├── connections.js    # Bezier curve SVG connection drawing, flow line styling, and animated laser dots
    ├── drag-drop.js      # Pointer API drag-and-drop handlers (palette-to-canvas & group node movement)
    └── tooltip.js        # Attenuation estimation tooltip positioning (SVG vector icons)
```

---

## Running Locally

Since the app has no compilation or build steps, you can run it directly:

1. **Clone the repository:**
   ```bash
   git clone https://github.com/devohme-id/fttx_simulator.git
   cd fttx_simulator
   ```
2. **Launch a local server:**
   You can open `index.html` directly in a browser, or run a quick local server:
   * **Node.js (npx):** `npx serve .`
   * **Python:** `python3 -m http.server 8080`
3. Open `http://localhost:8080` in your web browser.

---

## License

This project is open-source and available under the **MIT License**.
