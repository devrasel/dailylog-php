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

if ($method === 'POST') {
    // Handle file upload
    if (!isset($_FILES['file']) || !isset($_POST['vehicleId'])) {
        http_response_code(400);
        echo json_encode(['error' => 'File and vehicleId are required']);
        exit;
    }

    $vehicleId = $_POST['vehicleId'];
    
    // Verify vehicle ownership
    $stmt = $pdo->prepare("SELECT id FROM Vehicle WHERE id = ? AND userId = ?");
    $stmt->execute([$vehicleId, $userId]);
    if (!$stmt->fetch()) {
        http_response_code(403);
        echo json_encode(['error' => 'Invalid vehicle']);
        exit;
    }

    $file = $_FILES['file'];
    $filename = $file['name'];
    $filetype = $file['type'];
    $filesize = $file['size'];
    $tmpName = $file['tmp_name'];

    // Validate file type
    $allowedTypes = ['application/pdf', 'image/jpeg', 'image/jpg', 'image/png'];
    if (!in_array($filetype, $allowedTypes)) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid file type. Only PDF, JPG, PNG allowed']);
        exit;
    }

    // Validate file size (max 10MB)
    if ($filesize > 10 * 1024 * 1024) {
        http_response_code(400);
        echo json_encode(['error' => 'File too large. Max 10MB']);
        exit;
    }

    // Generate unique filename
    $ext = pathinfo($filename, PATHINFO_EXTENSION);
    $newFilename = time() . '-' . uniqid() . '.' . $ext;
    $uploadPath = '../../static/uploads/' . $newFilename;

    // Move uploaded file
    if (!move_uploaded_file($tmpName, $uploadPath)) {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to upload file']);
        exit;
    }

    // Save to database
    $id = uniqid('doc_', true);
    $url = 'static/uploads/' . $newFilename;
    
    $stmt = $pdo->prepare("INSERT INTO VehicleDocument (id, filename, filetype, size, url, vehicleId) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->execute([$id, $filename, $filetype, $filesize, $url, $vehicleId]);

    echo json_encode([
        'message' => 'Document uploaded successfully',
        'document' => [
            'id' => $id,
            'filename' => $filename,
            'filetype' => $filetype,
            'size' => $filesize,
            'url' => $url
        ]
    ]);

} elseif ($method === 'GET') {
    // Get documents for a vehicle
    $vehicleId = $_GET['vehicleId'] ?? null;
    if (!$vehicleId) {
        http_response_code(400);
        echo json_encode(['error' => 'vehicleId is required']);
        exit;
    }

    // Verify vehicle ownership
    $stmt = $pdo->prepare("SELECT id FROM Vehicle WHERE id = ? AND userId = ?");
    $stmt->execute([$vehicleId, $userId]);
    if (!$stmt->fetch()) {
        http_response_code(403);
        echo json_encode(['error' => 'Invalid vehicle']);
        exit;
    }

    $stmt = $pdo->prepare("SELECT * FROM VehicleDocument WHERE vehicleId = ? ORDER BY createdAt DESC");
    $stmt->execute([$vehicleId]);
    $documents = $stmt->fetchAll();

    echo json_encode(['documents' => $documents]);

} elseif ($method === 'DELETE') {
    // Delete document
    $data = json_decode(file_get_contents("php://input"), true);
    $id = $data['id'] ?? null;

    if (!$id) {
        http_response_code(400);
        echo json_encode(['error' => 'ID is required']);
        exit;
    }

    // Get document and verify ownership
    $stmt = $pdo->prepare("SELECT d.*, v.userId FROM VehicleDocument d JOIN Vehicle v ON d.vehicleId = v.id WHERE d.id = ?");
    $stmt->execute([$id]);
    $doc = $stmt->fetch();

    if (!$doc || $doc['userId'] !== $userId) {
        http_response_code(404);
        echo json_encode(['error' => 'Document not found']);
        exit;
    }

    // Delete file from filesystem
    $filepath = '../../' . $doc['url'];
    if (file_exists($filepath)) {
        unlink($filepath);
    }

    // Delete from database
    $stmt = $pdo->prepare("DELETE FROM VehicleDocument WHERE id = ?");
    $stmt->execute([$id]);

    echo json_encode(['message' => 'Document deleted']);

} else {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
}
?>
