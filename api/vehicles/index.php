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
    $stmt = $pdo->prepare("SELECT * FROM Vehicle WHERE userId = ? ORDER BY displayOrder ASC, createdAt DESC");
    $stmt->execute([$userId]);
    $vehicles = $stmt->fetchAll();
    echo json_encode(['vehicles' => $vehicles]);
} elseif ($method === 'POST') {
    $data = json_decode(file_get_contents("php://input"), true);
    
    if (!isset($data['name'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Name is required']);
        exit;
    }

    $id = uniqid('veh_', true);
    $name = $data['name'];
    $make = $data['make'] ?? null;
    $model = $data['model'] ?? null;
    $year = $data['year'] ?? null;
    $licensePlate = $data['licensePlate'] ?? null;
    $chassisNumber = $data['chassisNumber'] ?? null;
    $engineCC = $data['engineCC'] ?? null;
    $color = $data['color'] ?? null;
    $isActive = $data['isActive'] ?? true;
    $displayOrder = $data['displayOrder'] ?? 0;

    $stmt = $pdo->prepare("INSERT INTO Vehicle (id, name, make, model, year, licensePlate, chassisNumber, engineCC, color, isActive, displayOrder, userId) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([$id, $name, $make, $model, $year, $licensePlate, $chassisNumber, $engineCC, $color, $isActive, $displayOrder, $userId]);

    echo json_encode(['message' => 'Vehicle created', 'vehicle' => ['id' => $id, 'name' => $name]]);
} elseif ($method === 'PUT') {
    $data = json_decode(file_get_contents("php://input"), true);
    
    if (!isset($data['id'])) {
        http_response_code(400);
        echo json_encode(['error' => 'ID is required']);
        exit;
    }

    $id = $data['id'];
    // Verify ownership
    $stmt = $pdo->prepare("SELECT id FROM Vehicle WHERE id = ? AND userId = ?");
    $stmt->execute([$id, $userId]);
    if (!$stmt->fetch()) {
        http_response_code(404);
        echo json_encode(['error' => 'Vehicle not found']);
        exit;
    }

    $fields = [];
    $params = [];
    
    $updatableFields = ['name', 'make', 'model', 'year', 'licensePlate', 'chassisNumber', 'engineCC', 'color', 'isActive', 'displayOrder'];
    foreach ($updatableFields as $field) {
        if (isset($data[$field])) {
            $fields[] = "$field = ?";
            $params[] = $data[$field];
        }
    }

    if (empty($fields)) {
        echo json_encode(['message' => 'No changes']);
        exit;
    }

    $params[] = $id;
    $sql = "UPDATE Vehicle SET " . implode(', ', $fields) . " WHERE id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    echo json_encode(['message' => 'Vehicle updated']);
} elseif ($method === 'DELETE') {
    // For DELETE requests, we might need to read body or query param. PHP doesn't parse body for DELETE automatically.
    // Let's assume ID is passed in query string for DELETE or JSON body if we use a helper.
    // Standard REST uses URL path, but here we are using query param or body.
    // Let's check query param first.
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

    $stmt = $pdo->prepare("DELETE FROM Vehicle WHERE id = ? AND userId = ?");
    $stmt->execute([$id, $userId]);

    if ($stmt->rowCount() > 0) {
        echo json_encode(['message' => 'Vehicle deleted']);
    } else {
        http_response_code(404);
        echo json_encode(['error' => 'Vehicle not found']);
    }
} else {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
}
?>
