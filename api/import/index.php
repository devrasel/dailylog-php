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
    try {
        $data = json_decode(file_get_contents("php://input"), true);
        
        if (!$data || !isset($data['importData']) || !isset($data['mode'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid request. Missing importData or mode']);
            exit;
        }

        $importData = $data['importData'];
        $mode = $data['mode']; // 'merge' or 'replace'

        // Validate import data structure
        $requiredKeys = ['vehicles', 'fuelEntries', 'maintenanceCosts', 'expenses'];
        foreach ($requiredKeys as $key) {
            if (!isset($importData[$key])) {
                http_response_code(400);
                echo json_encode(['error' => "Invalid data structure. Missing: $key"]);
                exit;
            }
        }

        // Start transaction
        $pdo->beginTransaction();

        $results = [
            'vehicles' => 0,
            'fuelEntries' => 0,
            'maintenanceCosts' => 0,
            'expenses' => 0,
            'settings' => 0
        ];

        // If replace mode, delete existing data
        if ($mode === 'replace') {
            $pdo->prepare("DELETE FROM Expense WHERE userId = ?")->execute([$userId]);
            $pdo->prepare("DELETE FROM MaintenanceCost WHERE userId = ?")->execute([$userId]);
            $pdo->prepare("DELETE FROM FuelEntry WHERE userId = ?")->execute([$userId]);
            $pdo->prepare("DELETE FROM VehicleDocument WHERE vehicleId IN (SELECT id FROM Vehicle WHERE userId = ?)")->execute([$userId]);
            $pdo->prepare("DELETE FROM Vehicle WHERE userId = ?")->execute([$userId]);
        }

        // Create mapping for old IDs to new IDs
        $vehicleIdMap = [];

        // Import vehicles
        foreach ($importData['vehicles'] as $vehicle) {
            $oldId = $vehicle['id'];
            $newId = uniqid('vehicle_', true);
            $vehicleIdMap[$oldId] = $newId;

            $stmt = $pdo->prepare("INSERT INTO Vehicle (id, name, make, model, year, licensePlate, color, isActive, displayOrder, userId, createdAt) 
                                  VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([
                $newId,
                $vehicle['name'] ?? '',
                $vehicle['make'] ?? null,
                $vehicle['model'] ?? null,
                $vehicle['year'] ?? null,
                $vehicle['licensePlate'] ?? null,
                $vehicle['color'] ?? null,
                $vehicle['isActive'] ?? 1,
                $vehicle['displayOrder'] ?? 0,
                $userId,
                $vehicle['createdAt'] ?? date('Y-m-d H:i:s')
            ]);
            $results['vehicles']++;
        }

        // Import fuel entries
        foreach ($importData['fuelEntries'] as $entry) {
            $newId = uniqid('fuel_', true);
            $vehicleId = $vehicleIdMap[$entry['vehicleId']] ?? null;
            
            if (!$vehicleId) continue; // Skip if vehicle doesn't exist

            $stmt = $pdo->prepare("INSERT INTO FuelEntry (id, date, odometer, liters, costPerLiter, totalCost, fuelType, location, notes, vehicleId, userId, createdAt) 
                                  VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([
                $newId,
                $entry['date'],
                $entry['odometer'],
                $entry['liters'],
                $entry['costPerLiter'],
                $entry['totalCost'],
                $entry['fuelType'] ?? 'FULL',
                $entry['location'] ?? null,
                $entry['notes'] ?? null,
                $vehicleId,
                $userId,
                $entry['createdAt'] ?? date('Y-m-d H:i:s')
            ]);
            $results['fuelEntries']++;
        }

        // Import maintenance costs
        foreach ($importData['maintenanceCosts'] as $maintenance) {
            $newId = uniqid('maintenance_', true);
            $vehicleId = $vehicleIdMap[$maintenance['vehicleId']] ?? null;
            
            if (!$vehicleId) continue;

            $stmt = $pdo->prepare("INSERT INTO MaintenanceCost (id, date, description, cost, category, odometer, location, notes, vehicleId, userId, createdAt) 
                                  VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([
                $newId,
                $maintenance['date'],
                $maintenance['description'],
                $maintenance['cost'],
                $maintenance['category'],
                $maintenance['odometer'] ?? null,
                $maintenance['location'] ?? null,
                $maintenance['notes'] ?? null,
                $vehicleId,
                $userId,
                $maintenance['createdAt'] ?? date('Y-m-d H:i:s')
            ]);
            $results['maintenanceCosts']++;
        }

        // Import expenses
        foreach ($importData['expenses'] as $expense) {
            $newId = uniqid('expense_', true);

            $stmt = $pdo->prepare("INSERT INTO Expense (id, date, amount, category, title, description, paymentMethod, userId, createdAt) 
                                  VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([
                $newId,
                $expense['date'],
                $expense['amount'],
                $expense['category'],
                $expense['title'],
                $expense['description'] ?? null,
                $expense['paymentMethod'] ?? 'Cash',
                $userId,
                $expense['createdAt'] ?? date('Y-m-d H:i:s')
            ]);
            $results['expenses']++;
        }

        // Import settings (only if provided and in replace mode)
        if (isset($importData['settings']) && $mode === 'replace' && $importData['settings']) {
            $settings = $importData['settings'];
            $stmt = $pdo->prepare("UPDATE Settings SET currency = ?, dateFormat = ?, distanceUnit = ?, volumeUnit = ?, entriesPerPage = ?, timezone = ? WHERE userId = ?");
            $stmt->execute([
                $settings['currency'] ?? 'BDT',
                $settings['dateFormat'] ?? 'MM/DD/YYYY',
                $settings['distanceUnit'] ?? 'km',
                $settings['volumeUnit'] ?? 'L',
                $settings['entriesPerPage'] ?? 10,
                $settings['timezone'] ?? 'Asia/Dhaka',
                $userId
            ]);
            $results['settings'] = 1;
        }

        // Commit transaction
        $pdo->commit();

        echo json_encode([
            'success' => true,
            'message' => 'Data imported successfully',
            'results' => $results
        ]);

    } catch (Exception $e) {
        // Rollback on error
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        http_response_code(500);
        echo json_encode(['error' => 'Import failed: ' . $e->getMessage()]);
    }

} else {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
}
?>
