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
    
    $whereClause = "WHERE userId = ?";
    $params = [$userId];

    if ($vehicleId) {
        $whereClause .= " AND vehicleId = ?";
        $params[] = $vehicleId;
    }

    // 1. Get Global Stats (Aggregates)
    $statsQuery = "SELECT * FROM MaintenanceCost $whereClause ORDER BY date DESC";
    $stmt = $pdo->prepare($statsQuery);
    $stmt->execute($params);
    $allEntries = $stmt->fetchAll();

    // Calculate stats
    $stats = [
        'totalEntries' => count($allEntries),
        'totalCost' => 0,
        'averageCost' => 0,
        'categories' => [],
        'monthlyCosts' => []
    ];

    foreach ($allEntries as $entry) {
        $stats['totalCost'] += $entry['cost'];
        
        // Category stats
        if (!isset($stats['categories'][$entry['category']])) {
            $stats['categories'][$entry['category']] = 0;
        }
        $stats['categories'][$entry['category']] += $entry['cost'];

        // Monthly stats
        $month = date('Y-m', strtotime($entry['date']));
        if (!isset($stats['monthlyCosts'][$month])) {
            $stats['monthlyCosts'][$month] = 0;
        }
        $stats['monthlyCosts'][$month] += $entry['cost'];
    }
    
    if ($stats['totalEntries'] > 0) {
        $stats['averageCost'] = $stats['totalCost'] / $stats['totalEntries'];
    }

    // 2. Get Paginated Entries
    $query = "SELECT * FROM MaintenanceCost $whereClause ORDER BY date DESC";
    
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
    
    $required = ['date', 'description', 'cost', 'category', 'vehicleId'];
    foreach ($required as $field) {
        if (!isset($data[$field])) {
            http_response_code(400);
            echo json_encode(['error' => "Missing field: $field"]);
            exit;
        }
    }

    $id = uniqid('maint_', true);
    $date = date('Y-m-d H:i:s', strtotime($data['date']));
    $description = $data['description'];
    $cost = $data['cost'];
    $category = $data['category'];
    $odometer = $data['odometer'] ?? null;
    $location = $data['location'] ?? null;
    $notes = $data['notes'] ?? null;
    $vehicleId = $data['vehicleId'];
    
    // Verify vehicle
    $stmt = $pdo->prepare("SELECT id FROM Vehicle WHERE id = ? AND userId = ?");
    $stmt->execute([$vehicleId, $userId]);
    if (!$stmt->fetch()) {
        http_response_code(403);
        echo json_encode(['error' => 'Invalid vehicle']);
        exit;
    }

    $stmt = $pdo->prepare("INSERT INTO MaintenanceCost (id, date, description, cost, category, odometer, location, notes, vehicleId, userId) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([$id, $date, $description, $cost, $category, $odometer, $location, $notes, $vehicleId, $userId]);

    echo json_encode(['message' => 'Maintenance cost created', 'entry' => ['id' => $id]]);

} elseif ($method === 'PUT') {
    $data = json_decode(file_get_contents("php://input"), true);
    
    if (!isset($data['id'])) {
        http_response_code(400);
        echo json_encode(['error' => 'ID is required']);
        exit;
    }
    
    $id = $data['id'];
    
    // Verify ownership
    $stmt = $pdo->prepare("SELECT id FROM MaintenanceCost WHERE id = ? AND userId = ?");
    $stmt->execute([$id, $userId]);
    if (!$stmt->fetch()) {
        http_response_code(404);
        echo json_encode(['error' => 'Entry not found']);
        exit;
    }

    $fields = [];
    $params = [];
    
    $updatableFields = ['date', 'description', 'cost', 'category', 'odometer', 'location', 'notes', 'vehicleId'];
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
    $sql = "UPDATE MaintenanceCost SET " . implode(', ', $fields) . " WHERE id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    echo json_encode(['message' => 'Maintenance cost updated']);

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

    $stmt = $pdo->prepare("DELETE FROM MaintenanceCost WHERE id = ? AND userId = ?");
    $stmt->execute([$id, $userId]);

    if ($stmt->rowCount() > 0) {
        echo json_encode(['message' => 'Maintenance cost deleted']);
    } else {
        http_response_code(404);
        echo json_encode(['error' => 'Entry not found']);
    }
} else {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
}
?>
