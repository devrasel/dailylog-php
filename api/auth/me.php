<?php
session_start();
header("Content-Type: application/json");
require_once '../../config/db.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Not authenticated']);
    exit;
}

$stmt = $pdo->prepare("SELECT id, email, name, createdAt FROM User WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

if ($user) {
    echo json_encode(['user' => $user]);
} else {
    session_destroy();
    http_response_code(401);
    echo json_encode(['error' => 'User not found']);
}
?>
