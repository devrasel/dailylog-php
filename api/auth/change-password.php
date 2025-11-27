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
    // Fetch user's security question
    $stmt = $pdo->prepare("SELECT question FROM SecurityQuestion WHERE userId = ? LIMIT 1");
    $stmt->execute([$userId]);
    $securityQuestion = $stmt->fetch();
    
    if ($securityQuestion) {
        echo json_encode(['question' => $securityQuestion['question']]);
    } else {
        http_response_code(404);
        echo json_encode(['error' => 'No security question found']);
    }
} elseif ($method === 'POST') {
    $data = json_decode(file_get_contents("php://input"), true);
    
    // Validate required fields
    if (!isset($data['securityAnswer']) || !isset($data['currentPassword']) || !isset($data['newPassword'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Missing required fields']);
        exit;
    }
    
    $securityAnswer = $data['securityAnswer'];
    $currentPassword = $data['currentPassword'];
    $newPassword = $data['newPassword'];
    
    // 1. Verify security question answer
    $stmt = $pdo->prepare("SELECT answerHash FROM SecurityQuestion WHERE userId = ? LIMIT 1");
    $stmt->execute([$userId]);
    $securityQuestion = $stmt->fetch();
    
    if (!$securityQuestion) {
        http_response_code(404);
        echo json_encode(['error' => 'No security question found']);
        exit;
    }
    
    if (!password_verify($securityAnswer, $securityQuestion['answerHash'])) {
        http_response_code(401);
        echo json_encode(['error' => 'Incorrect security answer']);
        exit;
    }
    
    // 2. Verify current password
    $stmt = $pdo->prepare("SELECT passwordHash FROM User WHERE id = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch();
    
    if (!password_verify($currentPassword, $user['passwordHash'])) {
        http_response_code(401);
        echo json_encode(['error' => 'Incorrect current password']);
        exit;
    }
    
    // 3. Update password
    $newPasswordHash = password_hash($newPassword, PASSWORD_BCRYPT);
    $stmt = $pdo->prepare("UPDATE User SET passwordHash = ? WHERE id = ?");
    $stmt->execute([$newPasswordHash, $userId]);
    
    echo json_encode(['message' => 'Password changed successfully']);
} else {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
}
?>
