<?php
require_once '../includes/config.php';
require_once '../includes/db.php';

if (!isset($_SESSION['employee_id'])) {
    header("Location: ../index.php");
    exit();
}

// Fetch cars from database with prepared statement
$stmt = $pdo->prepare("SELECT * FROM cars ORDER BY make, model");
$stmt->execute();
$cars = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Our Cars - Speedy Wheels</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #2c3e50;
            --secondary: #3498db;
            --success: #2ecc71;
            --warning: #f39c12;
            --light-bg: #f8f9fa;
        }
        
        body {
            background-color: #f5f7fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .car-card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
            overflow: hidden;
            margin-bottom: 25px;
            border: none;
        }
        
        .car-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.15);
        }
        
        .car-image {
            height: 200px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 3rem;
        }
        
        .car-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .status-available { border-left: 4px solid var(--success); }
        .status-rented { border-left: 4px solid var(--warning); }
        .status-maintenance { border-left: 4px solid #dc3545; }
        
        .car-specs {
            font-size: 0.85rem;
            color: #6c757d;
        }
        
        .price-tag {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--primary);
        }
        
        .filter-btn.active {
            background-color: var(--primary);
            color: white;
        }
        
        .search-box {
            max-width: 400px;
        }
        
        .action-buttons {
            opacity: 0;
            transition: opacity 0.3s ease;
        }
        
        .car-card:hover .action-buttons {
            opacity: 1;
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
                        <a class="nav-link" href="employeeProfile.php">
                            <i class="fas fa-user me-1"></i>My Profile
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="customers.php">
                            <i class="fas fa-users me-1"></i>Customer Info
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="cars.php">
                            <i class="fas fa-car-side me-1"></i>Our Cars
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="purchases.php">
                             <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor" class="me-1" style="vertical-align: -0.125em;">
                            <path d="M20 9V5l-8-4-8 4v4h16zm0 2H4v8h2v-4a2 2 0 0 1 2-2h8a2 2 0 0 1 2 2v4h2V11zm-6 6h-4v4h4v-4z"/>
                        </svg>New Rental
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
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2 class="fw-bold text-primary">Our Car Fleet</h2>
                <p class="text-muted">Manage and view all available vehicles</p>
            </div>
            <div class="d-flex gap-2">
                <div class="search-box">
                    <div class="input-group">
                        <input type="text" class="form-control" placeholder="Search cars..." id="searchInput">
                        <button class="btn btn-outline-primary" type="button">
                            <i class="fas fa-search"></i>
                        </button>
                    </div>
                </div>
                <!-- Add Car Button for Future CRUD -->
                <button class="btn btn-success" onclick="addNewCar()">
                    <i class="fas fa-plus me-1"></i>Add Car
                </button>
            </div>
        </div>

        <!-- Filter Buttons -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="btn-group" role="group">
                    <button type="button" class="btn btn-outline-primary filter-btn active" data-filter="all">All Cars</button>
                    <button type="button" class="btn btn-outline-success filter-btn" data-filter="Available">Available</button>
                    <button type="button" class="btn btn-outline-warning filter-btn" data-filter="Rented">Rented</button>
                    <button type="button" class="btn btn-outline-danger filter-btn" data-filter="Maintenance">Maintenance</button>
                </div>
            </div>
        </div>

        <!-- Cars Grid -->
        <div class="row" id="carsGrid">
            <?php if (empty($cars)): ?>
            <div class="col-12 text-center py-5">
                <i class="fas fa-car fa-3x text-muted mb-3"></i>
                <h4 class="text-muted">No cars available</h4>
                <p class="text-muted">Add cars to your fleet to get started.</p>
            </div>
            <?php else: ?>
                <?php foreach($cars as $car): ?>
                <div class="col-lg-4 col-md-6 mb-4" 
                     data-status="<?php echo htmlspecialchars($car['status']); ?>"
                     data-make="<?php echo htmlspecialchars(strtolower($car['make'])); ?>"
                     data-model="<?php echo htmlspecialchars(strtolower($car['model'])); ?>">
                    <div class="car-card status-<?php echo strtolower($car['status']); ?>">
                        <!-- Action Buttons (hover reveal) -->
                        <div class="action-buttons position-absolute end-0 top-0 p-2">
                            <div class="btn-group btn-group-sm">
                                <button class="btn btn-outline-secondary" 
                                        onclick="editCar(<?php echo (int)$car['id']; ?>)"
                                        title="Edit Car">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button class="btn btn-outline-danger" 
                                        onclick="deleteCar(<?php echo (int)$car['id']; ?>)"
                                        title="Delete Car">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </div>
                        
                        <div class="car-image">
                            <?php 
                            $imagePath = !empty($car['image_path']) ? "../" . $car['image_path'] : '';
                            if (!empty($imagePath) && file_exists($imagePath)): 
                            ?>
                                <img src="<?php echo htmlspecialchars($imagePath); ?>" 
                                     alt="<?php echo htmlspecialchars($car['make'] . ' ' . $car['model']); ?>" 
                                     onerror="handleImageError(this)">
                            <?php else: ?>
                                <i class="fas fa-car"></i>
                            <?php endif; ?>
                        </div>
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <h5 class="card-title fw-bold">
                                    <?php echo htmlspecialchars($car['make'] . ' ' . $car['model']); ?>
                                </h5>
                                <span class="badge bg-<?php 
                                    echo match($car['status']) {
                                        'Available' => 'success',
                                        'Rented' => 'warning',
                                        'Maintenance' => 'danger',
                                        default => 'secondary'
                                    }; 
                                ?>">
                                    <?php echo htmlspecialchars($car['status']); ?>
                                </span>
                            </div>
                            <p class="car-specs mb-3">
                                <i class="fas fa-calendar me-1"></i><?php echo (int)$car['year']; ?>
                                <i class="fas fa-palette ms-3 me-1"></i><?php echo htmlspecialchars($car['color']); ?>
                                <i class="fas fa-tag ms-3 me-1"></i><?php echo htmlspecialchars($car['license_plate']); ?>
                            </p>
                            <div class="d-flex justify-content-between align-items-center">
                                <div class="price-tag">R<?php echo number_format((float)$car['daily_rate'], 2); ?>/day</div>
                                <button class="btn btn-primary btn-sm" 
                                        onclick="viewCarDetails(<?php echo (int)$car['id']; ?>)">
                                    <i class="fas fa-eye me-1"></i>View Details
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Filter functionality
        document.querySelectorAll('.filter-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                document.querySelectorAll('.filter-btn').forEach(b => b.classList.remove('active'));
                this.classList.add('active');
                
                const filter = this.getAttribute('data-filter');
                filterCars(filter);
            });
        });

        // Enhanced search functionality
        document.getElementById('searchInput').addEventListener('input', function(e) {
            const searchTerm = e.target.value.toLowerCase().trim();
            filterCarsBySearch(searchTerm);
        });

        function filterCars(filter) {
            const cars = document.querySelectorAll('#carsGrid .col-lg-4');
            
            cars.forEach(car => {
                const status = car.getAttribute('data-status');
                if (filter === 'all' || status === filter) {
                    car.style.display = 'block';
                } else {
                    car.style.display = 'none';
                }
            });
        }

        function filterCarsBySearch(searchTerm) {
            const cars = document.querySelectorAll('#carsGrid .col-lg-4');
            const activeFilter = document.querySelector('.filter-btn.active').getAttribute('data-filter');
            
            cars.forEach(car => {
                const title = car.querySelector('.card-title').textContent.toLowerCase();
                const make = car.getAttribute('data-make');
                const model = car.getAttribute('data-model');
                const status = car.getAttribute('data-status');
                
                const matchesSearch = title.includes(searchTerm) || 
                                   make.includes(searchTerm) || 
                                   model.includes(searchTerm);
                const matchesFilter = activeFilter === 'all' || status === activeFilter;
                
                car.style.display = matchesSearch && matchesFilter ? 'block' : 'none';
            });
        }

        function handleImageError(img) {
            img.style.display = 'none';
            img.parentNode.innerHTML = '<i class="fas fa-car"></i>';
        }

        // CRUD Functions (to be implemented)
        function viewCarDetails(carId) {
            // Redirect to car details page or show modal
            window.location.href = `car-details.php?id=${carId}`;
        }

        function editCar(carId) {
            // Future implementation for edit functionality
            console.log('Edit car:', carId);
            // window.location.href = `edit-car.php?id=${carId}`;
        }

        function deleteCar(carId) {
            // Future implementation for delete functionality
            if (confirm('Are you sure you want to delete this car?')) {
                console.log('Delete car:', carId);
                // Implement AJAX delete or form submission
            }
        }

        function addNewCar() {
            // Future implementation for add functionality
            console.log('Add new car');
            // window.location.href = 'add-car.php';
        }

        // Initialize page
        document.addEventListener('DOMContentLoaded', function() {
            // Any initialization code can go here
        });
    </script>
</body>
</html>