<?php
require_once 'config.php';
require_once 'db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['employee_id'])) {
    echo json_encode([]);
    exit;
}

$searchTerm = $_GET['q'] ?? '';

if (strlen($searchTerm) < 2) {
    echo json_encode([]);
    exit;
}

try {
    $stmt = $pdo->prepare("
        SELECT id, name, email, phone 
        FROM customers 
        WHERE name LIKE ? OR email LIKE ? OR phone LIKE ?
        ORDER BY name
        LIMIT 10
    ");
    
    $searchPattern = "%$searchTerm%";
    $stmt->execute([$searchPattern, $searchPattern, $searchPattern]);
    $customers = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode($customers);
    
} catch (Exception $e) {
    echo json_encode([]);
}