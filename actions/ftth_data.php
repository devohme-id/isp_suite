<?php
require_once '../config.php';
require_login();

header('Content-Type: application/json');

$action = $_GET['action'] ?? '';

try {
    switch ($action) {
        case 'get_olt_ports':
            $olt_id = (int)($_GET['olt_id'] ?? 0);
            $filter = $_GET['filter'] ?? 'all'; // 'all', 'idle', 'active'
            
            $sql = "SELECT id, port_number, port_label, port_type, status FROM olt_ports WHERE olt_id = ?";
            if ($filter === 'idle') {
                // Idle ports: either status is inactive OR not used in backbones
                $sql .= " AND id NOT IN (SELECT olt_port_id FROM backbones WHERE olt_port_id IS NOT NULL)";
            }
            $sql .= " ORDER BY port_number ASC";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$olt_id]);
            echo json_encode(['success' => true, 'ports' => $stmt->fetchAll()]);
            break;

        case 'get_backbone_cores':
            $backbone_id = (int)($_GET['backbone_id'] ?? 0);
            $filter = $_GET['filter'] ?? 'all'; // 'all', 'idle'

            $sql = "SELECT bc.id, bc.tube_number, bc.core_number, bc.status, bc.notes, bc.olt_port_id,
                           op.port_number, op.port_label, op.port_type, o.id as olt_id, o.olt_name
                    FROM backbone_cores bc
                    LEFT JOIN backbones b ON bc.backbone_id = b.id
                    LEFT JOIN olt_ports op ON COALESCE(bc.olt_port_id, b.olt_port_id) = op.id
                    LEFT JOIN olts o ON op.olt_id = o.id
                    WHERE bc.backbone_id = ?";
            if ($filter === 'idle') {
                $sql .= " AND bc.status = 'idle'";
            }
            $sql .= " ORDER BY bc.tube_number ASC, bc.core_number ASC";

            $stmt = $pdo->prepare($sql);
            $stmt->execute([$backbone_id]);
            echo json_encode(['success' => true, 'cores' => $stmt->fetchAll()]);
            break;

        case 'get_rk_connections':
            $rk_id = (int)($_GET['rk_id'] ?? 0);
            $stmt = $pdo->prepare("
                SELECT rc.id, rc.notes, bc.tube_number, bc.core_number, b.backbone_code
                FROM rk_connections rc
                JOIN backbone_cores bc ON rc.backbone_core_id = bc.id
                JOIN backbones b ON bc.backbone_id = b.id
                WHERE rc.rk_id = ?
                ORDER BY b.backbone_code ASC, bc.tube_number ASC, bc.core_number ASC
            ");
            $stmt->execute([$rk_id]);
            echo json_encode(['success' => true, 'connections' => $stmt->fetchAll()]);
            break;

        case 'get_rk_list':
            $stmt = $pdo->query("SELECT id, rk_name, location_description FROM rk_points ORDER BY rk_name ASC");
            echo json_encode(['success' => true, 'rk_list' => $stmt->fetchAll()]);
            break;

        case 'get_distributions':
            $rk_id = (int)($_GET['rk_id'] ?? 0);
            $stmt = $pdo->prepare("SELECT id, dist_code, cable_type, coverage_area FROM distributions WHERE rk_id = ? ORDER BY dist_code ASC");
            $stmt->execute([$rk_id]);
            echo json_encode(['success' => true, 'distributions' => $stmt->fetchAll()]);
            break;

        case 'get_dist_cores':
            $dist_id = (int)($_GET['distribution_id'] ?? 0);
            $filter = $_GET['filter'] ?? 'all'; // 'all', 'idle'

            $sql = "SELECT id, tube_number, core_number, status, notes FROM distribution_cores WHERE distribution_id = ?";
            if ($filter === 'idle') {
                $sql .= " AND status = 'idle'";
            }
            $sql .= " ORDER BY tube_number ASC, core_number ASC";

            $stmt = $pdo->prepare($sql);
            $stmt->execute([$dist_id]);
            echo json_encode(['success' => true, 'cores' => $stmt->fetchAll()]);
            break;

        case 'get_dp_ports':
            $dp_id = (int)($_GET['dp_id'] ?? 0);
            if (!$dp_id) {
                echo json_encode(['success' => false, 'message' => 'Invalid DP ID']);
                exit;
            }

            $stmt = $pdo->prepare("SELECT total_ports FROM drop_points WHERE id = ?");
            $stmt->execute([$dp_id]);
            $dp = $stmt->fetch();

            if (!$dp) {
                echo json_encode(['success' => false, 'message' => 'Drop Point not found']);
                exit;
            }

            $stmt_occupied = $pdo->prepare("SELECT dp_port, id as customer_id, name FROM customers WHERE dp_id = ? AND dp_port IS NOT NULL");
            $stmt_occupied->execute([$dp_id]);
            $occupied_data = $stmt_occupied->fetchAll();

            $occupied_ports = [];
            foreach ($occupied_data as $row) {
                $occupied_ports[$row['dp_port']] = [
                    'customer_id' => $row['customer_id'],
                    'name' => $row['name']
                ];
            }

            echo json_encode([
                'success' => true,
                'total_ports' => (int)$dp['total_ports'],
                'occupied_ports' => $occupied_ports
            ]);
            break;

        case 'get_network_path':
            $dp_id = (int)($_GET['dp_id'] ?? 0);
            if (!$dp_id) {
                echo json_encode(['success' => false, 'message' => 'Invalid DP ID']);
                exit;
            }

            // Tracing OLT -> Backbone -> RK -> Distribution -> DP
            $stmt = $pdo->prepare("
                SELECT 
                    dp.id AS dp_id, dp.dp_code, dp.dp_name, dp.zone_area AS dp_zone,
                    dc.tube_number AS dist_tube, dc.core_number AS dist_core,
                    d.dist_code, d.coverage_area,
                    rk.id AS rk_id, rk.rk_name, rk.location_description AS rk_location,
                    bc.tube_number AS bb_tube, bc.core_number AS bb_core,
                    b.backbone_code,
                    op.port_number AS olt_port, op.port_label AS olt_port_label, op.port_type AS olt_port_type,
                    olt.id AS olt_id, olt.olt_name, olt.olt_model
                FROM drop_points dp
                LEFT JOIN distribution_cores dc ON dp.dist_core_id = dc.id
                LEFT JOIN distributions d ON dc.distribution_id = d.id
                LEFT JOIN rk_points rk ON d.rk_id = rk.id
                LEFT JOIN rk_connections rc ON rk.id = rc.rk_id AND rc.backbone_core_id IN (
                    SELECT id FROM backbone_cores WHERE backbone_id = (
                        SELECT id FROM backbones WHERE olt_port_id IS NOT NULL LIMIT 1
                    )
                ) -- simplified heuristic matching, let's do a precise connection match:
                -- Wait! An RK has connections to specific backbone cores. Let's trace it properly:
                -- To get the backbone core feeding this RK connection:
                -- We link rk_connections to backbone_cores. But which backbone core is connected to the RK connection?
                -- rk_connections stores: rk_id, backbone_core_id.
                -- Let's trace back from RK connections. Note that rk_connections connects a backbone core to the RK.
                -- Let's query:
                WHERE dp.id = ?
            ");
            
            // Let's rewrite the query to trace from DP upwards:
            // DP (dist_core_id) -> distribution_cores (distribution_id) -> distributions (rk_id) -> rk_points
            // RK (rk_points.id) -> rk_connections (backbone_core_id) -> backbone_cores (backbone_id) -> backbones (olt_port_id) -> olt_ports -> olts
            $stmt = $pdo->prepare("
                SELECT 
                    dp.id AS dp_id, dp.dp_code, dp.dp_name, dp.zone_area AS dp_zone,
                    dc.tube_number AS dist_tube, dc.core_number AS dist_core,
                    d.dist_code, d.coverage_area AS dist_coverage,
                    rk.id AS rk_id, rk.rk_name, rk.location_description AS rk_location,
                    bc.id AS bb_core_id, bc.tube_number AS bb_tube, bc.core_number AS bb_core,
                    b.id AS backbone_id, b.backbone_code,
                    op.id AS olt_port_id, op.port_number AS olt_port, op.port_label AS olt_port_label, op.port_type AS olt_port_type,
                    olt.id AS olt_id, olt.olt_name, olt.olt_model
                FROM drop_points dp
                LEFT JOIN distribution_cores dc ON dp.dist_core_id = dc.id
                LEFT JOIN distributions d ON dc.distribution_id = d.id
                LEFT JOIN rk_points rk ON d.rk_id = rk.id
                LEFT JOIN rk_connections rc ON rk.id = rc.rk_id
                LEFT JOIN backbone_cores bc ON rc.backbone_core_id = bc.id
                LEFT JOIN backbones b ON bc.backbone_id = b.id
                LEFT JOIN olt_ports op ON COALESCE(bc.olt_port_id, b.olt_port_id) = op.id
                LEFT JOIN olts olt ON op.olt_id = olt.id
                WHERE dp.id = ?
            ");
            $stmt->execute([$dp_id]);
            $path = $stmt->fetch();

            if (!$path) {
                echo json_encode(['success' => false, 'message' => 'Path not found']);
                exit;
            }

            echo json_encode(['success' => true, 'path' => $path]);
            break;

        default:
            echo json_encode(['success' => false, 'message' => 'Unknown action']);
            break;
    }
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>
