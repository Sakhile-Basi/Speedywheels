<?php
require_once 'config.php';
require_once 'db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['employee_id'])) {
    echo json_encode(['success' => false, 'error' => 'Not authenticated']);
    exit;
}

$id = $_SESSION['employee_id'];

// Reuse the same functions from employeeProfile.php
function getRentalPerformance(PDO $pdo, int $id): array {
    $stmt = $pdo->prepare("
        SELECT 
            COUNT(*) as total_rentals,
            SUM(CASE WHEN status = 'Returned' THEN 1 ELSE 0 END) as completed_rentals
        FROM rentals 
        WHERE employee_id = ?
    ");
    $stmt->execute([$id]);
    return $stmt->fetch(PDO::FETCH_ASSOC) ?: ['total_rentals' => 0, 'completed_rentals' => 0];
}

function getTodaysRentalsCount(PDO $pdo, int $id): int {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM rentals WHERE start_date = CURDATE() AND employee_id = ?");
    $stmt->execute([$id]);
    return (int)$stmt->fetchColumn();
}

function getAvailableCarsCount(PDO $pdo): int {
    $stmt = $pdo->query("SELECT COUNT(*) FROM cars WHERE status = 'Available'");
    return (int)$stmt->fetchColumn();
}

try {
    $rentals_data = getRentalPerformance($pdo, $id);
    $total_rentals = $rentals_data['total_rentals'] ?? 0;
    $completed_rentals = $rentals_data['completed_rentals'] ?? 0;
    $completion_percentage = $total_rentals > 0 ? round(($completed_rentals / $total_rentals) * 100) : 0;
    $ongoing_rentals = $total_rentals - $completed_rentals;
    
    $today_rentals_count = getTodaysRentalsCount($pdo, $id);
    $available_cars_count = getAvailableCarsCount($pdo);
    
    echo json_encode([
        'success' => true,
        'total_rentals' => $total_rentals,
        'completed_rentals' => $completed_rentals,
        'ongoing_rentals' => $ongoing_rentals,
        'completion_percentage' => $completion_percentage,
        'today_rentals' => $today_rentals_count,
        'available_cars' => $available_cars_count
    ]);
    
} 
catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}