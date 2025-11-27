<?php
header("Content-Type: application/json");
require_once '../../config/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$data = json_decode(file_get_contents("php://input"), true);

if (!isset($data['email']) || !isset($data['password'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing email or password']);
    exit;
}

$email = $data['email'];
$password = $data['password'];
$name = $data['name'] ?? null;
$securityQuestion = $data['securityQuestion'] ?? null;
$securityAnswer = $data['securityAnswer'] ?? null;

// Validate security question and answer
if (!$securityQuestion || !$securityAnswer) {
    http_response_code(400);
    echo json_encode(['error' => 'Security question and answer are required']);
    exit;
}

// Check if user exists
$stmt = $pdo->prepare("SELECT id FROM User WHERE email = ?");
$stmt->execute([$email]);
if ($stmt->fetch()) {
    http_response_code(409);
    echo json_encode(['error' => 'User already exists']);
    exit;
}

// Hash password and security answer
$passwordHash = password_hash($password, PASSWORD_BCRYPT);
$answerHash = password_hash($securityAnswer, PASSWORD_BCRYPT);
$id = uniqid('user_', true); // Simple unique ID, or use UUID library if available

try {
    $pdo->beginTransaction();

    // Create User
    $stmt = $pdo->prepare("INSERT INTO User (id, email, name, passwordHash) VALUES (?, ?, ?, ?)");
    $stmt->execute([$id, $email, $name, $passwordHash]);

    // Create Security Question
    $securityId = uniqid('security_', true);
    $stmt = $pdo->prepare("INSERT INTO SecurityQuestion (id, question, answerHash, userId) VALUES (?, ?, ?, ?)");
    $stmt->execute([$securityId, $securityQuestion, $answerHash, $id]);

    // Create Default Settings
    $settingsId = uniqid('settings_', true);
    $stmt = $pdo->prepare("INSERT INTO Settings (id, userId) VALUES (?, ?)");
    $stmt->execute([$settingsId, $id]);

    $pdo->commit();

    echo json_encode(['message' => 'User registered successfully', 'userId' => $id]);
} catch (Exception $e) {
    $pdo->rollBack();
    http_response_code(500);
    echo json_encode(['error' => 'Registration failed: ' . $e->getMessage()]);
}
?>
