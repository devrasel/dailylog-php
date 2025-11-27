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
    // Fetch user's current security question
    $stmt = $pdo->prepare("SELECT question FROM SecurityQuestion WHERE userId = ? LIMIT 1");
    $stmt->execute([$userId]);
    $securityQuestion = $stmt->fetch();
    
    if ($securityQuestion) {
        echo json_encode(['question' => $securityQuestion['question']]);
    } else {
        http_response_code(404);
        echo json_encode(['error' => 'No security question found']);
    }
} elseif ($method === 'PUT') {
    $data = json_decode(file_get_contents("php://input"), true);
    
    // Validate required fields
    if (!isset($data['newQuestion']) || !isset($data['newAnswer']) || !isset($data['currentPassword'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Missing required fields']);
        exit;
    }
    
    $newQuestion = $data['newQuestion'];
    $newAnswer = $data['newAnswer'];
    $currentPassword = $data['currentPassword'];
    
    // Verify current password
    $stmt = $pdo->prepare("SELECT passwordHash FROM User WHERE id = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch();
    
    if (!password_verify($currentPassword, $user['passwordHash'])) {
        http_response_code(401);
        echo json_encode(['error' => 'Incorrect password']);
        exit;
    }
    
    // Hash new answer
    $newAnswerHash = password_hash($newAnswer, PASSWORD_BCRYPT);
    
    // Check if security question exists
    $stmt = $pdo->prepare("SELECT id FROM SecurityQuestion WHERE userId = ?");
    $stmt->execute([$userId]);
    $existing = $stmt->fetch();
    
    if ($existing) {
        // Update existing
        $stmt = $pdo->prepare("UPDATE SecurityQuestion SET question = ?, answerHash = ? WHERE userId = ?");
        $stmt->execute([$newQuestion, $newAnswerHash, $userId]);
    } else {
        // Create new
        $securityId = uniqid('security_', true);
        $stmt = $pdo->prepare("INSERT INTO SecurityQuestion (id, question, answerHash, userId) VALUES (?, ?, ?, ?)");
        $stmt->execute([$securityId, $newQuestion, $newAnswerHash, $userId]);
    }
    
    echo json_encode(['message' => 'Security question updated successfully']);
} else {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
}
?>
