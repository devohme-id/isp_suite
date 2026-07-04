/**
 * FTTH Simulator — Calculator Engine
 * Traverses the connection graph to calculate optical power levels at every node.
 */

class AttenuationCalculator {
  constructor(canvasManager) {
    this.canvas = canvasManager; // Reference to the canvas to access nodes and connections
    this.results = new Map(); // Maps nodeId -> calculation result object
  }

  /**
   * Recalculate all signal levels across the entire graph.
   * Typically called whenever a property changes, or a node/connection is added/removed.
   */
  recalculate() {
    this.results.clear();
    
    // Find all start nodes (OLT or SFP)
    const nodes = this.canvas.getNodes();
    const startNodes = nodes.filter(n => n.type === 'olt' || n.type === 'sfp');
    
    if (startNodes.length > 0) {
      // Process each independent tree starting from sources
      startNodes.forEach(sourceNode => {
        // Determine initial Tx Power
        let txPower = 0;
        
        if (sourceNode.type === 'sfp') {
          txPower = parseFloat(sourceNode.properties.txPower) || 3.0;
        } else if (sourceNode.type === 'olt') {
          // If OLT has an SFP connected directly, we use SFP's power.
          // Otherwise, assume a default Tx power based on technology.
          const connectedSfp = this._findDirectlyConnected(sourceNode, 'sfp');
          if (connectedSfp) {
            // Will be handled when iterating over SFP
            return;
          } else {
            // Default assumed if no SFP (simulate integrated module)
            txPower = sourceNode.properties.technology === 'EPON' ? 2.0 : 3.0;
          }
        }

        // Initialize calculation for source node
        this.results.set(sourceNode.id, {
          powerIn: null, // Source has no input power
          powerOut: txPower,
          cumulativeLoss: 0,
          equipmentLoss: 0,
          status: 'good'
        });

        // Traverse the graph from this source
        this._traverse(sourceNode, txPower, 0);
      });
    }

    // Fire event to notify UI to update (tooltips, line colors, etc.)
    document.dispatchEvent(new CustomEvent('simulation:calculated', { 
      detail: { results: this.results } 
    }));
  }

  /**
   * Recursive graph traversal
   * @param {Object} node Current node
   * @param {Number} powerIn Signal power entering this node
   * @param {Number} cumulativeLoss Total loss up to this point
   */
  _traverse(node, powerIn, cumulativeLoss) {
    // Calculate this node's intrinsic loss
    const equipmentDef = EQUIPMENT_CATALOG[node.type];
    let eqLoss = 0;
    
    if (equipmentDef && typeof equipmentDef.getAttenuation === 'function') {
      eqLoss = equipmentDef.getAttenuation(node.properties);
    }
    
    // Some equipment like ONT have sensitivity thresholds
    let status = 'good';
    let powerOut = powerIn - eqLoss;
    let newCumulativeLoss = cumulativeLoss + eqLoss;

    if (node.type === 'ont') {
      const sensitivity = parseFloat(node.properties.sensitivity) || -27;
      if (powerOut < sensitivity) {
        status = 'critical'; // Signal too weak
      } else if (powerOut < sensitivity + 3) {
        status = 'warning'; // Approaching limit
      } else if (powerOut > -8) {
        status = 'warning'; // Signal too strong (overload)
      }
    } else {
      // Generic status based on power level
      if (powerOut < -28) status = 'critical';
      else if (powerOut < -25) status = 'warning';
    }

    // Save result for this node
    // Only update if we haven't visited or if this path is worse (simulating worst-case)
    const existing = this.results.get(node.id);
    if (!existing || powerOut < existing.powerOut) {
      this.results.set(node.id, {
        powerIn: parseFloat(powerIn.toFixed(2)),
        powerOut: parseFloat(powerOut.toFixed(2)),
        cumulativeLoss: parseFloat(newCumulativeLoss.toFixed(2)),
        equipmentLoss: parseFloat(eqLoss.toFixed(2)),
        status: status
      });
    }

    // Find outgoing connections from this node
    const connections = this.canvas.getOutgoingConnections(node.id);
    
    connections.forEach(conn => {
      const targetNode = this.canvas.getNode(conn.targetId);
      if (targetNode) {
        // Connector loss (assuming 0.1dB for standard splice/connector per connection line)
        // In a real precise model, we might want to let users place connectors explicitly,
        // but adding a small base loss for every link is realistic.
        const linkLoss = 0.1; 
        
        this._traverse(
          targetNode, 
          powerOut - linkLoss, 
          newCumulativeLoss + linkLoss
        );
      }
    });
  }

  /**
   * Helper to find a specific type of equipment directly connected to an output port
   */
  _findDirectlyConnected(sourceNode, targetType) {
    const connections = this.canvas.getOutgoingConnections(sourceNode.id);
    for (let conn of connections) {
      const target = this.canvas.getNode(conn.targetId);
      if (target && target.type === targetType) {
        return target;
      }
    }
    return null;
  }

  /**
   * Get calculation result for a specific node
   */
  getResult(nodeId) {
    return this.results.get(nodeId) || null;
  }

  /**
   * Get power level at a specific connection line (mid-point)
   */
  getConnectionPower(connection) {
    const sourceResult = this.results.get(connection.sourceId);
    if (sourceResult) {
      return sourceResult.powerOut - 0.1; // Outgoing power minus link loss
    }
    return null;
  }
}

// Export for module usage
if (typeof module !== 'undefined' && module.exports) {
  module.exports = { AttenuationCalculator };
}
