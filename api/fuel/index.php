<?php
session_start();
header("Content-Type: application/json");
require_once '../../config/db.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Not authenticated']);
    exit;
}

$userId = $_SESSION['user_id'];
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $vehicleId = $_GET['vehicleId'] ?? null;
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
    $offset = ($page - 1) * $limit;
    
    // Base conditions
    $whereClause = "WHERE userId = ?";
    $params = [$userId];

    if ($vehicleId) {
        $whereClause .= " AND vehicleId = ?";
        $params[] = $vehicleId;
    }

    // 1. Get Global Stats (Aggregates)
    $statsQuery = "SELECT 
        COUNT(*) as totalEntries,
        SUM(totalCost) as totalCost,
        SUM(liters) as totalLiters,
        MAX(odometer) - MIN(odometer) as totalDistance
        FROM FuelEntry $whereClause";
        
    $stmt = $pdo->prepare($statsQuery);
    $stmt->execute($params);
    $statsResult = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $stats = [
        'totalEntries' => (int)$statsResult['totalEntries'],
        'totalCost' => (float)$statsResult['totalCost'],
        'totalLiters' => (float)$statsResult['totalLiters'],
        'totalDistance' => (float)$statsResult['totalDistance'],
        'averageCostPerLiter' => 0,
        'averageConsumption' => 0, // Not easily calc via SQL without more logic
        'bestConsumption' => 0,
        'worstConsumption' => 0,
        'mileagePerLiter' => 0,
        'totalMileage' => 0
    ];

    if ($stats['totalLiters'] > 0) {
        $stats['averageCostPerLiter'] = $stats['totalCost'] / $stats['totalLiters'];
    }

    // 2. Get Entries (Paginated or All)
    $query = "SELECT * FROM FuelEntry $whereClause ORDER BY date DESC";
    
    if ($limit > 0) {
        $query .= " LIMIT $limit OFFSET $offset";
    }

    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $entries = $stmt->fetchAll();

    echo json_encode([
        'entries' => $entries, 
        'stats' => $stats,
        'pagination' => [
            'page' => $page,
            'limit' => $limit,
            'total' => $stats['totalEntries'],
            'totalPages' => $limit > 0 ? ceil($stats['totalEntries'] / $limit) : 1
        ]
    ]);

} elseif ($method === 'POST') {
    $data = json_decode(file_get_contents("php://input"), true);
    
    // Validation
    $required = ['date', 'odometer', 'liters', 'costPerLiter', 'totalCost', 'vehicleId'];
    foreach ($required as $field) {
        if (!isset($data[$field])) {
            http_response_code(400);
            echo json_encode(['error' => "Missing field: $field"]);
            exit;
        }
    }

    $id = uniqid('fuel_', true);
    $date = date('Y-m-d H:i:s', strtotime($data['date'])); // Ensure MySQL format
    $odometer = $data['odometer'];
    $liters = $data['liters'];
    $costPerLiter = $data['costPerLiter'];
    $totalCost = $data['totalCost'];
    $fuelType = $data['fuelType'] ?? 'FULL';
    $location = $data['location'] ?? null;
    $notes = $data['notes'] ?? null;
    $vehicleId = $data['vehicleId'];
    
    // Verify vehicle ownership
    $stmt = $pdo->prepare("SELECT id FROM Vehicle WHERE id = ? AND userId = ?");
    $stmt->execute([$vehicleId, $userId]);
    if (!$stmt->fetch()) {
        http_response_code(403);
        echo json_encode(['error' => 'Invalid vehicle']);
        exit;
    }

    $stmt = $pdo->prepare("INSERT INTO FuelEntry (id, date, odometer, liters, costPerLiter, totalCost, fuelType, location, notes, vehicleId, userId) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([$id, $date, $odometer, $liters, $costPerLiter, $totalCost, $fuelType, $location, $notes, $vehicleId, $userId]);

    echo json_encode(['message' => 'Fuel entry created', 'entry' => ['id' => $id]]);

} elseif ($method === 'PUT') {
    $data = json_decode(file_get_contents("php://input"), true);
    
    if (!isset($data['id'])) {
        http_response_code(400);
        echo json_encode(['error' => 'ID is required']);
        exit;
    }
    
    $id = $data['id'];
    
    // Verify ownership
    $stmt = $pdo->prepare("SELECT id FROM FuelEntry WHERE id = ? AND userId = ?");
    $stmt->execute([$id, $userId]);
    if (!$stmt->fetch()) {
        http_response_code(404);
        echo json_encode(['error' => 'Entry not found']);
        exit;
    }

    $fields = [];
    $params = [];
    
    $updatableFields = ['date', 'odometer', 'liters', 'costPerLiter', 'totalCost', 'fuelType', 'location', 'notes', 'vehicleId'];
    foreach ($updatableFields as $field) {
        if (isset($data[$field])) {
            $fields[] = "$field = ?";
            if ($field === 'date') {
                $params[] = date('Y-m-d H:i:s', strtotime($data[$field]));
            } else {
                $params[] = $data[$field];
            }
        }
    }

    if (empty($fields)) {
        echo json_encode(['message' => 'No changes']);
        exit;
    }

    $params[] = $id;
    $sql = "UPDATE FuelEntry SET " . implode(', ', $fields) . " WHERE id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    echo json_encode(['message' => 'Fuel entry updated']);

} elseif ($method === 'DELETE') {
    $id = $_GET['id'] ?? null;
    if (!$id) {
        $data = json_decode(file_get_contents("php://input"), true);
        $id = $data['id'] ?? null;
    }

    if (!$id) {
        http_response_code(400);
        echo json_encode(['error' => 'ID is required']);
        exit;
    }

    $stmt = $pdo->prepare("DELETE FROM FuelEntry WHERE id = ? AND userId = ?");
    $stmt->execute([$id, $userId]);

    if ($stmt->rowCount() > 0) {
        echo json_encode(['message' => 'Fuel entry deleted']);
    } else {
        http_response_code(404);
        echo json_encode(['error' => 'Entry not found']);
    }
} else {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
}
?>
