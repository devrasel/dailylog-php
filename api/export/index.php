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
    try {
        // Fetch all user data
        $exportData = [
            'metadata' => [
                'exportDate' => date('Y-m-d H:i:s'),
                'version' => '1.0',
                'userId' => $userId
            ],
            'vehicles' => [],
            'fuelEntries' => [],
            'maintenanceCosts' => [],
            'expenses' => [],
            'settings' => null
        ];

        // Get vehicles
        $stmt = $pdo->prepare("SELECT * FROM Vehicle WHERE userId = ? ORDER BY displayOrder");
        $stmt->execute([$userId]);
        $exportData['vehicles'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Get fuel entries
        $stmt = $pdo->prepare("SELECT * FROM FuelEntry WHERE userId = ? ORDER BY date");
        $stmt->execute([$userId]);
        $exportData['fuelEntries'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Get maintenance costs
        $stmt = $pdo->prepare("SELECT * FROM MaintenanceCost WHERE userId = ? ORDER BY date");
        $stmt->execute([$userId]);
        $exportData['maintenanceCosts'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Get expenses
        $stmt = $pdo->prepare("SELECT * FROM Expense WHERE userId = ? ORDER BY date");
        $stmt->execute([$userId]);
        $exportData['expenses'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Get settings
        $stmt = $pdo->prepare("SELECT * FROM Settings WHERE userId = ?");
        $stmt->execute([$userId]);
        $exportData['settings'] = $stmt->fetch(PDO::FETCH_ASSOC);

        // Get vehicle documents
        $stmt = $pdo->prepare("SELECT vd.* FROM VehicleDocument vd 
                              INNER JOIN Vehicle v ON vd.vehicleId = v.id 
                              WHERE v.userId = ?");
        $stmt->execute([$userId]);
        $exportData['vehicleDocuments'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode($exportData, JSON_PRETTY_PRINT);

    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Export failed: ' . $e->getMessage()]);
    }

} else {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
}
?>
