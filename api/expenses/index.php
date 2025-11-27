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
    $month = $_GET['month'] ?? date('Y-m'); // Format: YYYY-MM
    $category = $_GET['category'] ?? null;
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
    $offset = ($page - 1) * $limit;
    
    // Parse month
    $startDate = $month . '-01 00:00:00';
    $endDate = date('Y-m-t 23:59:59', strtotime($startDate));
    
    $whereClause = "WHERE userId = ? AND date >= ? AND date <= ?";
    $params = [$userId, $startDate, $endDate];
    
    if ($category) {
        $whereClause .= " AND category = ?";
        $params[] = $category;
    }
    
    // 1. Get Stats (Aggregates for the month)
    // We need to fetch all expenses for the month to calculate category totals and total amount
    // Pagination should only affect the list returned, not the monthly summary
    
    $statsQuery = "SELECT * FROM Expense $whereClause ORDER BY date DESC";
    $stmt = $pdo->prepare($statsQuery);
    $stmt->execute($params);
    $allExpenses = $stmt->fetchAll();
    
    $totalAmount = 0;
    $categoryTotals = [];
    
    foreach ($allExpenses as $expense) {
        $totalAmount += $expense['amount'];
        $cat = $expense['category'];
        if (!isset($categoryTotals[$cat])) {
            $categoryTotals[$cat] = 0;
        }
        $categoryTotals[$cat] += $expense['amount'];
    }
    
    // Sort categories by amount DESC
    arsort($categoryTotals);
    $topCategories = array_slice($categoryTotals, 0, 5, true);
    
    // Get top expenses (global top 5, not paginated)
    $topExpenses = array_slice($allExpenses, 0, 5);

    // 2. Get Paginated Expenses
    $query = "SELECT id, date, amount, category, title, description, paymentMethod, 'expense' as source 
              FROM Expense $whereClause ORDER BY date DESC";
    
    if ($limit > 0) {
        $query .= " LIMIT $limit OFFSET $offset";
    }
    
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $expenses = $stmt->fetchAll();
    
    echo json_encode([
        'expenses' => $expenses,
        'stats' => [
            'totalAmount' => $totalAmount,
            'totalEntries' => count($allExpenses),
            'topCategories' => $topCategories,
            'topExpenses' => $topExpenses
        ],
        'pagination' => [
            'page' => $page,
            'limit' => $limit,
            'total' => count($allExpenses),
            'totalPages' => $limit > 0 ? ceil(count($allExpenses) / $limit) : 1
        ]
    ]);


} elseif ($method === 'POST') {
    $data = json_decode(file_get_contents("php://input"), true);
    
    // Validation
    $required = ['date', 'amount', 'category', 'title'];
    foreach ($required as $field) {
        if (!isset($data[$field])) {
            http_response_code(400);
            echo json_encode(['error' => "Missing field: $field"]);
            exit;
        }
    }

    $id = uniqid('exp_', true);
    $date = date('Y-m-d H:i:s', strtotime($data['date']));
    $amount = $data['amount'];
    $category = $data['category'];
    $title = $data['title'];
    $description = $data['description'] ?? null;
    $paymentMethod = $data['paymentMethod'] ?? 'Cash';

    $stmt = $pdo->prepare("INSERT INTO Expense (id, date, amount, category, title, description, paymentMethod, userId) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([$id, $date, $amount, $category, $title, $description, $paymentMethod, $userId]);

    echo json_encode(['message' => 'Expense created', 'expense' => ['id' => $id]]);

} elseif ($method === 'PUT') {
    $data = json_decode(file_get_contents("php://input"), true);
    
    if (!isset($data['id'])) {
        http_response_code(400);
        echo json_encode(['error' => 'ID is required']);
        exit;
    }
    
    $id = $data['id'];
    
    // Verify ownership
    $stmt = $pdo->prepare("SELECT id FROM Expense WHERE id = ? AND userId = ?");
    $stmt->execute([$id, $userId]);
    if (!$stmt->fetch()) {
        http_response_code(404);
        echo json_encode(['error' => 'Expense not found']);
        exit;
    }

    $fields = [];
    $params = [];
    
    $updatableFields = ['date', 'amount', 'category', 'title', 'description', 'paymentMethod'];
    foreach ($updatableFields as $field) {
        if (isset($data[$field])) {
            $fields[] = "$field = ?";
            if ($field === 'date') {
                $params[] = date('Y-m-d H:i:s', strtotime($data[$field]));
            } else {
                $params[] = $data[$field];
            }
        }
    }

    if (empty($fields)) {
        echo json_encode(['message' => 'No changes']);
        exit;
    }

    $params[] = $id;
    $sql = "UPDATE Expense SET " . implode(', ', $fields) . " WHERE id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    echo json_encode(['message' => 'Expense updated']);

} elseif ($method === 'DELETE') {
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

    $stmt = $pdo->prepare("DELETE FROM Expense WHERE id = ? AND userId = ?");
    $stmt->execute([$id, $userId]);

    if ($stmt->rowCount() > 0) {
        echo json_encode(['message' => 'Expense deleted']);
    } else {
        http_response_code(404);
        echo json_encode(['error' => 'Expense not found']);
    }
} else {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
}
?>
