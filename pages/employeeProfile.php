<?php
require_once '../includes/config.php';
require_once '../includes/db.php';

if (!isset($_SESSION['employee_id'])) {
    header("Location: ../index.php");
    exit();
}

$id = $_SESSION['employee_id'];

// Database functions for cleaner code
function getEmployeeInfo(PDO $pdo, int $id): ?array {
    $stmt = $pdo->prepare("SELECT * FROM employees WHERE id = ?");
    $stmt->execute([$id]);
    return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
}

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

function getRecentRentals(PDO $pdo, int $id, int $limit = 5): array {
    $stmt = $pdo->prepare("
        SELECT r.*, c.name as customer_name, car.make, car.model 
        FROM rentals r 
        JOIN customers c ON r.customer_id = c.id 
        JOIN cars car ON r.car_id = car.id 
        WHERE r.employee_id = ? 
        ORDER BY r.start_date DESC 
        LIMIT ?
    ");
    $stmt->execute([$id, $limit]);
    return $stmt->fetchAll();
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

// Fetch data using functions
$employee = getEmployeeInfo($pdo, $id);
if (!$employee) {
    die("No employee found with ID $id");
}

$rentals_data = getRentalPerformance($pdo, $id);
$total_rentals = $rentals_data['total_rentals'] ?? 0;
$completed_rentals = $rentals_data['completed_rentals'] ?? 0;
$completion_percentage = $total_rentals > 0 ? round(($completed_rentals / $total_rentals) * 100) : 0;
$ongoing_rentals = $total_rentals - $completed_rentals;

$recent_rentals_data = getRecentRentals($pdo, $id);
$today_rentals_count = getTodaysRentalsCount($pdo, $id);
$available_cars_count = getAvailableCarsCount($pdo);

// Calculate member since date
$member_since = isset($employee['created_at']) ? date('F j, Y', strtotime($employee['created_at'])) : date('F j, Y');
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Employee Dashboard - Speedy Wheels</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #2c3e50;
            --secondary: #3498db;
            --success: #2ecc71;
            --light-bg: #f8f9fa;
            --card-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        
        body {
            background-color: #f5f7fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .navbar-brand {
            font-weight: 700;
        }
        
        .welcome-card {
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            border: none;
            border-radius: 10px;
            box-shadow: var(--card-shadow);
        }
        
        .info-card {
            background: white;
            border-radius: 10px;
            box-shadow: var(--card-shadow);
            margin-bottom: 20px;
        }
        
        .info-card .card-header {
            background-color: var(--primary);
            color: white;
            font-weight: 600;
            border-radius: 10px 10px 0 0 !important;
        }
        
        .progress-ring {
            width: 120px;
            height: 120px;
        }
        
        .recent-activity {
            max-height: 300px;
            overflow-y: auto;
        }
        
        .quick-stat {
            text-align: center;
            padding: 15px;
            transition: transform 0.2s ease;
        }
        
        .quick-stat:hover {
            transform: translateY(-2px);
        }
        
        .quick-stat .number {
            font-size: 2.5rem;
            font-weight: bold;
            margin-bottom: 5px;
        }
        
        .quick-stat .label {
            font-size: 0.9rem;
            color: #1a1d1fff;
        }
        
       
        .progress-ring-container {
            position: relative;
            display: inline-block;
        }
        
        .progress-ring-bg {
            fill: none;
            stroke: #e0e0e0;
            stroke-width: 10;
        }
        
        .progress-ring-fill {
            fill: none;
            stroke: #2ecc71;
            stroke-width: 10;
            stroke-linecap: round;
            transform: rotate(-90deg);
            transform-origin: 50% 50%;
            transition: stroke-dashoffset 0.3s ease;
        }
        
        .stat-card {
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .stat-card:hover {
            box-shadow: 0 6px 12px rgba(0,0,0,0.15);
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark" style="background-color: var(--primary);">
    <div class="container">
        <a class="navbar-brand" href="#">
            <i class="fas fa-car me-2"></i>Speedy Wheels
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav me-auto">
                <li class="nav-item">
                    <a class="nav-link" href="../includes/logout.php">
                        <i class="fas fa-sign-out-alt me-1"></i>Logout
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link active" href="employeeProfile.php">
                        <i class="fas fa-user me-1"></i>My Profile
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="customers.php">
                        <i class="fas fa-users me-1"></i>Customer Info
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="cars.php">
                        <i class="fas fa-car-side me-1"></i>Our Cars
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="purchases.php">
                         <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor" class="me-1" style="vertical-align: -0.125em;">
                            <path d="M20 9V5l-8-4-8 4v4h16zm0 2H4v8h2v-4a2 2 0 0 1 2-2h8a2 2 0 0 1 2 2v4h2V11zm-6 6h-4v4h4v-4z"/>
                        </svg>
                        New Rental
                    </a>
                </li>
            </ul>
            <div class="d-flex align-items-center text-light">
                <i class="fas fa-user-circle me-2"></i>
                <span><?php echo htmlspecialchars($_SESSION['employee_name'] ?? 'Employee'); ?></span>
            </div>
        </div>
    </div>
</nav>

    <div class="container mt-4">
        <!-- Welcome Card -->
        <div class="card welcome-card text-white mb-4">
            <div class="card-body p-4">
                <div class="row align-items-center">
                    <div class="col-md-8">
                        <h2 class="card-title mb-2">Welcome back, <?php echo htmlspecialchars($employee['name']); ?>!</h2>
                        <p class="card-text mb-0">Manage rentals, assist customers, and keep our fleet moving efficiently.</p>
                    </div>
                    <div class="col-md-4 text-end">
                        <i class="fas fa-car fa-4x opacity-75"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Quick Stats Row -->
        <div class="row mb-4" id="statsContainer">
            <div class="col-md-3 mb-3">
                <div class="card text-white bg-primary stat-card" onclick="refreshStats()">
                    <div class="card-body text-center quick-stat">
                        <div class="number" id="totalRentals"><?php echo $total_rentals; ?></div>
                        <div class="label">Total Rentals</div>
                        <small><i class="fas fa-sync-alt me-1"></i>Click to refresh</small>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="card text-white bg-success stat-card">
                    <div class="card-body text-center quick-stat">
                        <div class="number" id="completedRentals"><?php echo $completed_rentals; ?></div>
                        <div class="label">Completed</div>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="card text-white bg-warning stat-card">
                    <div class="card-body text-center quick-stat">
                        <div class="number" id="ongoingRentals"><?php echo $ongoing_rentals; ?></div>
                        <div class="label">Ongoing</div>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="card text-white bg-info stat-card">
                    <div class="card-body text-center quick-stat">
                        <div class="number" id="availableCars"><?php echo $available_cars_count; ?></div>
                        <div class="label">Available Cars</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Main Content Area -->
            <div class="col-lg-8">
                <!-- Rental Performance Card -->
                <div class="info-card">
                    <div class="card-header py-3">
                        <h5 class="mb-0">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor" class="me-2" style="vertical-align: -0.2em;">
                <path d="M3 13h8V3H3v10zm0 8h8v-6H3v6zm10 0h8V11h-8v10zm0-18v6h8V3h-8z"/>
            </svg>
            Rental Performance</h5>
                    </div>
                    <div class="card-body p-4">
                        <div class="row align-items-center">
                            <div class="col-md-5 text-center">
                                <div class="progress-ring-container">
                                    <svg class="progress-ring" viewBox="0 0 120 120">
                                        <!-- Background circle -->
                                        <circle class="progress-ring-bg" r="52" cx="60" cy="60" />
                                        <!-- Progress circle - no overlap with proper layering -->
                                        <circle class="progress-ring-fill" 
                                                r="52" 
                                                cx="60" 
                                                cy="60"
                                                stroke-dasharray="326.56"
                                                stroke-dashoffset="<?php echo 326.56 - (326.56 * $completion_percentage / 100); ?>" />
                                    </svg>
                                    <div class="position-absolute top-50 start-50 translate-middle">
                                        <h3 class="mb-0 fw-bold" style="color: #2ecc71;" id="completionPercentage"><?php echo $completion_percentage; ?>%</h3>
                                        <small class="text-muted">Completed</small>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-7">
                                <div class="row text-center">
                                    <div class="col-6 mb-3">
                                        <div class="p-3 rounded" style="background-color: rgba(46, 204, 113, 0.1);">
                                            <h4 class="fw-bold mb-1" style="color: #2ecc71;" id="completedCount"><?php echo $completed_rentals; ?></h4>
                                            <small class="text-muted">Completed Rentals</small>
                                        </div>
                                    </div>
                                    <div class="col-6 mb-3">
                                        <div class="p-3 rounded" style="background-color: rgba(243, 156, 18, 0.1);">
                                            <h4 class="fw-bold mb-1" style="color: #f39c12;" id="ongoingCount"><?php echo $ongoing_rentals; ?></h4>
                                            <small class="text-muted">Ongoing Rentals</small>
                                        </div>
                                    </div>
                                    <div class="col-12">
                                        <div class="p-3 rounded" style="background-color: rgba(52, 152, 219, 0.1);">
                                            <h4 class="fw-bold mb-1" style="color: #3498db;" id="totalCount"><?php echo $total_rentals; ?></h4>
                                            <small class="text-muted">Total Rentals Processed</small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Recent Activity -->
                <div class="info-card mt-4">
                    <div class="card-header py-3">
                        <h5 class="mb-0">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor" class="me-2" style="vertical-align: -0.2em;">
                <path d="M11.99 2C6.47 2 2 6.48 2 12s4.47 10 9.99 10C17.52 22 22 17.52 22 12S17.52 2 11.99 2zM12 20c-4.42 0-8-3.58-8-8s3.58-8 8-8 8 3.58 8 8-3.58 8-8 8zm.5-13H11v6l5.25 3.15.75-1.23-4.5-2.67z"/>
            </svg>
            Recent Rental Activity</h5>
                    </div>
                    <div class="card-body p-4">
                        <div class="recent-activity" id="recentActivity">
                            <?php if (empty($recent_rentals_data)): ?>
                                <div class="text-center text-muted py-4">
                                    <i class="fas fa-clipboard-list fa-3x mb-3"></i>
                                    <p>No recent rental activity.</p>
                                    <a href="purchases.php" class="btn btn-primary">Create First Rental</a>
                                </div>
                            <?php else: ?>
                                <div class="table-responsive">
                                    <table class="table table-sm table-hover">
                                        <thead>
                                            <tr>
                                                <th>Rental ID</th>
                                                <th>Customer</th>
                                                <th>Car</th>
                                                <th>Start Date</th>
                                                <th>Status</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach($recent_rentals_data as $rental): ?>
                                            <tr>
                                                <td><strong>#<?php echo $rental['id']; ?></strong></td>
                                                <td><?php echo htmlspecialchars($rental['customer_name']); ?></td>
                                                <td><?php echo htmlspecialchars($rental['make'] . ' ' . $rental['model']); ?></td>
                                                <td><?php echo date('M j, Y', strtotime($rental['start_date'])); ?></td>
                                                <td>
                                                    <span class="badge bg-<?php echo $rental['status'] == 'Ongoing' ? 'warning' : 'success'; ?>">
                                                        <?php echo $rental['status']; ?>
                                                    </span>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Sidebar -->
            <div class="col-lg-4">
                <!-- Employee Info Card -->
                <div class="info-card">
                    <div class="card-header py-3">
                        <h5 class="mb-0">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor" class="me-2" style="vertical-align: -0.2em;">
                <path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/>
            </svg>
            Employee Information</h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <small class="text-muted">Full Name</small>
                            <p class="mb-2 fw-bold"><?php echo htmlspecialchars($employee['name']); ?></p>
                        </div>
                        <div class="mb-3">
                            <small class="text-muted">Email Address</small>
                            <p class="mb-2 fw-bold"><?php echo htmlspecialchars($employee['email']); ?></p>
                        </div>
                        <div class="mb-3">
                            <small class="text-muted">Employee ID</small>
                            <p class="mb-2 fw-bold">#<?php echo htmlspecialchars($employee['id']); ?></p>
                        </div>
                        <div class="mb-3">
                            <small class="text-muted">Position</small>
                            <p class="mb-2 fw-bold"><?php echo htmlspecialchars($employee['position'] ?? 'Rental Agent'); ?></p>
                        </div>
                        <div class="mb-3">
                            <small class="text-muted">Member Since</small>
                            <p class="mb-2 fw-bold"><?php echo $member_since; ?></p>
                        </div>
                        <div class="mt-4 pt-3 border-top">
                            <small class="text-muted"><i class="fas fa-info-circle me-1"></i>Contact HR for information updates</small>
                        </div>
                    </div>
                </div>

                <!-- Quick Actions Card -->
                <div class="info-card mt-4">
                    <div class="card-header py-3">
                        <h5 class="mb-0 text-center">Quick Actions</h5>
                    </div>
                    <div class="card-body p-3">
                        <div class="d-grid gap-2">
                            <a href="purchases.php" class="btn btn-outline-primary btn-sm">
                                 <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor" class="me-1" style="vertical-align: -0.125em;">
                            <path d="M20 9V5l-8-4-8 4v4h16zm0 2H4v8h2v-4a2 2 0 0 1 2-2h8a2 2 0 0 1 2 2v4h2V11zm-6 6h-4v4h4v-4z"/>
                        </svg>New Rental
                            </a>
                            <a href="customers.php" class="btn btn-outline-success btn-sm">
                                <i class="fas fa-users me-2"></i>Manage Customers
                            </a>
                            <a href="cars.php" class="btn btn-outline-info btn-sm">
                                <i class="fas fa-car-side me-2"></i>View Cars
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Today's Summary -->
                <div class="info-card mt-4">
                    <div class="card-header py-3">
                        <h5 class="mb-0 text-center">Today's Summary</h5>
                    </div>
                    <div class="card-body p-3">
                        <div class="row text-center">
                            <div class="col-6 mb-3">
                                <div class="p-2 rounded" style="background-color: rgba(52, 152, 219, 0.1);">
                                    <h6 class="fw-bold mb-1" style="color: #3498db;" id="todayRentals"><?php echo $today_rentals_count; ?></h6>
                                    <small class="text-muted">Today's Rentals</small>
                                </div>
                            </div>
                            <div class="col-6 mb-3">
                                <div class="p-2 rounded" style="background-color: rgba(46, 204, 113, 0.1);">
                                    <h6 class="fw-bold mb-1" style="color: #2ecc71;" id="availableCount"><?php echo $available_cars_count; ?></h6>
                                    <small class="text-muted">Available Cars</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // AJAX function to refresh stats
        async function refreshStats() {
            const statsContainer = document.getElementById('statsContainer');
            statsContainer.style.opacity = '0.7';
            
            try {
                const response = await fetch('../includes/get_employee_stats.php');
                const data = await response.json();
                
                if (data.success) {
                    // Update all stat elements
                    document.getElementById('totalRentals').textContent = data.total_rentals;
                    document.getElementById('completedRentals').textContent = data.completed_rentals;
                    document.getElementById('ongoingRentals').textContent = data.ongoing_rentals;
                    document.getElementById('availableCars').textContent = data.available_cars;
                    document.getElementById('completionPercentage').textContent = data.completion_percentage + '%';
                    document.getElementById('completedCount').textContent = data.completed_rentals;
                    document.getElementById('ongoingCount').textContent = data.ongoing_rentals;
                    document.getElementById('totalCount').textContent = data.total_rentals;
                    document.getElementById('todayRentals').textContent = data.today_rentals;
                    document.getElementById('availableCount').textContent = data.available_cars;
                    
                    // Update progress ring
                    const progressRing = document.querySelector('.progress-ring-fill');
                    const circumference = 326.56;
                    const offset = circumference - (circumference * data.completion_percentage / 100);
                    progressRing.style.strokeDashoffset = offset;
                    
                    // Show success feedback
                    showNotification('Stats updated successfully!', 'success');
                }
            } catch (error) {
                console.error('Error refreshing stats:', error);
                showNotification('Error updating stats', 'error');
            } finally {
                statsContainer.style.opacity = '1';
            }
        }
        
        function showNotification(message, type) {
            // Simple notification 
            const alert = document.createElement('div');
            alert.className = `alert alert-${type === 'success' ? 'success' : 'danger'} alert-dismissible fade show position-fixed`;
            alert.style.top = '20px';
            alert.style.right = '20px';
            alert.style.zIndex = '9999';
            alert.innerHTML = `
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            `;
            document.body.appendChild(alert);
            
            setTimeout(() => {
                alert.remove();
            }, 3000);
        }
        
        // Auto-refresh stats every 30 seconds
        setInterval(refreshStats, 30000);
        
        // Add click handlers to all stat cards
        document.querySelectorAll('.stat-card').forEach(card => {
            card.addEventListener('click', refreshStats);
        });
    </script>
</body>
</html>