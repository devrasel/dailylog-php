<?php
session_start();
header("Content-Type: application/json");

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Not authenticated']);
    exit;
}

$categories = [
    ['id' => 'Food', 'name' => 'Food', 'icon' => 'fa-utensils', 'color' => '#10b981'],
    ['id' => 'Transport', 'name' => 'Transport', 'icon' => 'fa-bus', 'color' => '#3b82f6'],
    ['id' => 'Fuel', 'name' => 'Fuel', 'icon' => 'fa-gas-pump', 'color' => '#ef4444'],
    ['id' => 'Maintenance', 'name' => 'Maintenance', 'icon' => 'fa-wrench', 'color' => '#f59e0b'],
    ['id' => 'Shopping', 'name' => 'Shopping', 'icon' => 'fa-shopping-bag', 'color' => '#8b5cf6'],
    ['id' => 'Entertainment', 'name' => 'Entertainment', 'icon' => 'fa-film', 'color' => '#ec4899'],
    ['id' => 'Health', 'name' => 'Health', 'icon' => 'fa-heartbeat', 'color' => '#14b8a6'],
    ['id' => 'Bills', 'name' => 'Bills', 'icon' => 'fa-file-invoice-dollar', 'color' => '#6366f1'],
    ['id' => 'Other', 'name' => 'Other', 'icon' => 'fa-ellipsis-h', 'color' => '#6b7280']
];

echo json_encode(['categories' => $categories]);
?>
