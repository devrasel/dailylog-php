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
    $stmt = $pdo->prepare("SELECT * FROM Settings WHERE userId = ?");
    $stmt->execute([$userId]);
    $settings = $stmt->fetch();
    
    if (!$settings) {
        // Create default settings if not exists (should have been created on register, but just in case)
        $id = uniqid('settings_', true);
        $stmt = $pdo->prepare("INSERT INTO Settings (id, userId) VALUES (?, ?)");
        $stmt->execute([$id, $userId]);
        
        $stmt = $pdo->prepare("SELECT * FROM Settings WHERE userId = ?");
        $stmt->execute([$userId]);
        $settings = $stmt->fetch();
    }
    
    echo json_encode($settings);

} elseif ($method === 'PUT' || $method === 'POST') {
    $data = json_decode(file_get_contents("php://input"), true);
    
    $fields = [];
    $params = [];
    
    $updatableFields = ['currency', 'dateFormat', 'distanceUnit', 'volumeUnit', 'entriesPerPage', 'timezone'];
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

    $params[] = $userId;
    $sql = "UPDATE Settings SET " . implode(', ', $fields) . " WHERE userId = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    echo json_encode(['message' => 'Settings updated']);

} else {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
}
?>
