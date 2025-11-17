<?php
require_once '../includes/config.php';
require_once '../includes/db.php';

if (!isset($_SESSION['employee_id'])) {
    header("Location: ../index.php");
    exit();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'create_rental') {
    $errors = [];
    
    // Server-side validation
    $customer_id = (int)($_POST['customer_id'] ?? 0);
    $car_id = (int)($_POST['car_id'] ?? 0);
    $start_date = $_POST['start_date'] ?? '';
    $end_date = $_POST['end_date'] ?? '';
    $pickup_location = trim($_POST['pickup_location'] ?? '');
    $return_location = trim($_POST['return_location'] ?? '');
    $additional_services = $_POST['additional_services'] ?? [];
    $employee_id = $_SESSION['employee_id'];
    
    // Validate required fields
    if ($customer_id <= 0) $errors[] = "Please select a valid customer.";
    if ($car_id <= 0) $errors[] = "Please select a valid vehicle.";
    if (empty($start_date)) $errors[] = "Pick-up date is required.";
    if (empty($end_date)) $errors[] = "Return date is required.";
    if (empty($pickup_location)) $errors[] = "Pick-up location is required.";
    if (empty($return_location)) $errors[] = "Return location is required.";
    
    // Validate dates
    if ($start_date && $end_date) {
        $start = DateTime::createFromFormat('Y-m-d', $start_date);
        $end = DateTime::createFromFormat('Y-m-d', $end_date);
        $today = new DateTime();
        
        if ($start < $today) $errors[] = "Pick-up date cannot be in the past.";
        if ($end <= $start) $errors[] = "Return date must be after pick-up date.";
    }
    
    if (empty($errors)) {
        try {
            $pdo->beginTransaction();
            
            // Calculate rental days and total cost
            $start = new DateTime($start_date);
            $end = new DateTime($end_date);
            $rental_days = $start->diff($end)->days;
            $rental_days = $rental_days > 0 ? $rental_days : 1; // Minimum 1 day
            
            // Get car daily rate
            $car_stmt = $pdo->prepare("SELECT daily_rate FROM cars WHERE id = ? AND status = 'Available'");
            $car_stmt->execute([$car_id]);
            $car = $car_stmt->fetch();
            
            if (!$car) {
                throw new Exception("Selected car is not available.");
            }
            
            $daily_rate = (float)$car['daily_rate'];
            $total_cost = $daily_rate * $rental_days;
            
            // Calculate additional services cost
            $service_costs = [
                'insurance' => 15,
                'gps' => 10,
                'child-seat' => 8
            ];
            
            $additional_cost = 0;
            foreach ($additional_services as $service) {
                if (isset($service_costs[$service])) {
                    $additional_cost += $service_costs[$service] * $rental_days;
                }
            }
            
            $total_cost += $additional_cost;
            
            // Insert rental record
            $rental_stmt = $pdo->prepare("
                INSERT INTO rentals (customer_id, car_id, employee_id, start_date, end_date, 
                                   pickup_location, return_location, total_cost, status, additional_services)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'Ongoing', ?)
            ");
            
            $services_json = json_encode($additional_services);
            $rental_stmt->execute([
                $customer_id, 
                $car_id, 
                $employee_id, 
                $start_date, 
                $end_date, 
                $pickup_location, 
                $return_location, 
                $total_cost,
                $services_json
            ]);
            
            $rental_id = $pdo->lastInsertId();
            
            // Update car status to Rented
            $update_car_stmt = $pdo->prepare("UPDATE cars SET status = 'Rented' WHERE id = ?");
            $update_car_stmt->execute([$car_id]);
            
            $pdo->commit();
            
            $_SESSION['success'] = "Rental #$rental_id created successfully!";
            header("Location: purchases.php?success=1");
            exit;
            
        } catch (Exception $e) {
            $pdo->rollBack();
            $errors[] = "Error creating rental: " . $e->getMessage();
        }
    }
    
    if (!empty($errors)) {
        $_SESSION['rental_errors'] = $errors;
        $_SESSION['rental_data'] = $_POST;
    }
}

// Fetch available cars
$available_cars = $pdo->query("
    SELECT * FROM cars 
    WHERE status = 'Available' 
    ORDER BY make, model
")->fetchAll();

// Fetch locations
$locations = ['Downtown Branch', 'Airport Branch', 'Suburb Branch'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>New Rental - Speedy Wheels</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
         :root {
            --primary: #2c3e50;
            --secondary: #3498db;
            --success: #2ecc71;
            --light-bg: #f8f9fa;
        }
        
        body {
            background-color: #f5f7fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .rental-card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            border: none;
            margin-bottom: 20px;
        }
        
        .step-indicator {
            display: flex;
            justify-content: space-between;
            margin-bottom: 30px;
            position: relative;
        }
        
        .step-indicator::before {
            content: '';
            position: absolute;
            top: 20px;
            left: 0;
            right: 0;
            height: 2px;
            background: #e9ecef;
            z-index: 1;
        }
        
        .step {
            text-align: center;
            position: relative;
            z-index: 2;
            flex: 1;
        }
        
        .step-number {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: #e9ecef;
            color: #6c757d;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 10px;
            font-weight: 600;
            border: 3px solid white;
        }
        
        .step.active .step-number {
            background: var(--primary);
            color: white;
        }
        
        .step.completed .step-number {
            background: var(--success);
            color: white;
        }
        
        .car-option {
            border: 2px solid #e9ecef;
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 15px;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .car-option:hover {
            border-color: var(--secondary);
        }
        
        .car-option.selected {
            border-color: var(--primary);
            background-color: rgba(52, 152, 219, 0.05);
        }
        
        .form-section {
            display: none;
        }
        
        .form-section.active {
            display: block;
        }
        
        .car-image-small {
            width: 80px;
            height: 60px;
            object-fit: cover;
            border-radius: 8px;
        }
        
        .summary-card {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 20px;
            position: sticky;
            top: 20px;
        }
        .customer-search-results {
            position: absolute;
            top: 100%;
            left: 0;
            right: 0;
            background: white;
            border: 1px solid #ddd;
            border-radius: 4px;
            max-height: 200px;
            overflow-y: auto;
            z-index: 1000;
            display: none;
        }
        
        .customer-result {
            padding: 8px 12px;
            cursor: pointer;
            border-bottom: 1px solid #eee;
        }
        
        .customer-result:hover {
            background-color: #f8f9fa;
        }
        
        .error-message {
            color: #dc3545;
            font-size: 0.875rem;
            margin-top: 0.25rem;
        }
        
        .loading-spinner {
            display: none;
            text-align: center;
            padding: 10px;
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
                    <a class="nav-link" href="cars.php">
                        <i class="fas fa-car-side me-1"></i>Our Cars
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link active" href="purchases.php">
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
        <!-- Success/Error Messages -->
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?php echo htmlspecialchars($_SESSION['success']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php unset($_SESSION['success']); ?>
        <?php endif; ?>

        <?php if (isset($_SESSION['rental_errors'])): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <h5>Please fix the following errors:</h5>
                <ul class="mb-0">
                    <?php foreach ($_SESSION['rental_errors'] as $error): ?>
                        <li><?php echo htmlspecialchars($error); ?></li>
                    <?php endforeach; ?>
                </ul>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php unset($_SESSION['rental_errors']); ?>
        <?php endif; ?>

        <!-- Page Header -->
        <div class="row mb-4">
            <div class="col-12">
                <h2 class="fw-bold text-primary">Create New Rental</h2>
                <p class="text-muted">Process a new car rental for a customer</p>
            </div>
        </div>

        <!-- Progress Steps -->
        <div class="rental-card p-4">
            <div class="step-indicator">
                <div class="step active" data-step="1">
                    <div class="step-number">1</div>
                    <div class="step-label">Customer</div>
                </div>
                <div class="step" data-step="2">
                    <div class="step-number">2</div>
                    <div class="step-label">Vehicle</div>
                </div>
                <div class="step" data-step="3">
                    <div class="step-number">3</div>
                    <div class="step-label">Details</div>
                </div>
                <div class="step" data-step="4">
                    <div class="step-number">4</div>
                    <div class="step-label">Confirmation</div>
                </div>
            </div>

            <form id="rentalForm" method="POST" action="">
                <input type="hidden" name="action" value="create_rental">
                
                <!-- Step 1: Customer Information -->
                <div class="form-section active" id="step1">
                    <h4 class="mb-4">Customer Information</h4>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Customer Search</label>
                            <div class="position-relative">
                                <input type="text" class="form-control" id="customerSearch" 
                                       placeholder="Search by name, email, or phone..." 
                                       autocomplete="off">
                                <div class="customer-search-results" id="customerResults">
                                    <div class="loading-spinner" id="searchLoading">
                                        <i class="fas fa-spinner fa-spin"></i> Searching...
                                    </div>
                                </div>
                            </div>
                            <small class="text-muted">Or <a href="customers.php" target="_blank">add new customer</a></small>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Customer ID</label>
                            <input type="text" class="form-control" id="customerId" name="customer_id" 
                                   placeholder="Select customer first" readonly>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-12">
                            <div class="card bg-light" id="selectedCustomerCard" style="display: none;">
                                <div class="card-body">
                                    <h6>Selected Customer</h6>
                                    <p class="mb-1"><strong>Name:</strong> <span id="customerName">-</span></p>
                                    <p class="mb-1"><strong>Email:</strong> <span id="customerEmail">-</span></p>
                                    <p class="mb-0"><strong>Phone:</strong> <span id="customerPhone">-</span></p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="mt-4">
                        <button type="button" class="btn btn-primary next-step" data-next="2" id="step1Next" disabled>
                            Next: Select Vehicle
                        </button>
                    </div>
                </div>

                <!-- Step 2: Vehicle Selection -->
                <div class="form-section" id="step2">
                    <h4 class="mb-4">Select Vehicle</h4>
                    <div class="row">
                        <div class="col-md-8">
                            <!-- Available Cars -->
                            <?php if (empty($available_cars)): ?>
                                <div class="alert alert-warning">
                                    <i class="fas fa-exclamation-triangle me-2"></i>
                                    No available cars at the moment.
                                </div>
                            <?php else: ?>
                                <?php foreach($available_cars as $index => $car): ?>
                                <div class="car-option <?php echo $index === 0 ? 'selected' : ''; ?>" 
                                     data-car-id="<?php echo $car['id']; ?>"
                                     data-daily-rate="<?php echo $car['daily_rate']; ?>">
                                    <div class="row align-items-center">
                                        <div class="col-auto">
                                            <?php if (!empty($car['image_path']) && file_exists("../" . $car['image_path'])): ?>
                                                <img src="../<?php echo htmlspecialchars($car['image_path']); ?>" 
                                                     alt="<?php echo htmlspecialchars($car['make'] . ' ' . $car['model']); ?>" 
                                                     class="car-image-small">
                                            <?php else: ?>
                                                <div class="car-image-small bg-secondary d-flex align-items-center justify-content-center">
                                                    <i class="fas fa-car text-white"></i>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                        <div class="col">
                                            <h6 class="mb-1"><?php echo htmlspecialchars($car['make'] . ' ' . $car['model'] . ' ' . $car['year']); ?></h6>
                                            <p class="text-muted mb-1">
                                                <?php echo htmlspecialchars($car['transmission'] ?? 'Automatic'); ?> • 
                                                <?php echo htmlspecialchars($car['fuel_type'] ?? 'Petrol'); ?> • 
                                                <?php echo htmlspecialchars($car['seats'] ?? '5'); ?> Seats
                                            </p>
                                            <div class="d-flex align-items-center">
                                                <span class="badge bg-success me-2">Available</span>
                                                <strong class="text-primary">R<?php echo number_format($car['daily_rate'], 2); ?>/day</strong>
                                            </div>
                                        </div>
                                        <div class="col-auto">
                                            <input type="radio" name="car_id" value="<?php echo $car['id']; ?>" 
                                                   <?php echo $index === 0 ? 'checked' : ''; ?>>
                                        </div>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                        <div class="col-md-4">
                            <div class="summary-card">
                                <h6>Rental Summary</h6>
                                <div class="mb-3">
                                    <small class="text-muted">Selected Vehicle</small>
                                    <p class="mb-1 fw-bold" id="summaryCar">-</p>
                                </div>
                                <div class="mb-3">
                                    <small class="text-muted">Daily Rate</small>
                                    <p class="mb-1 fw-bold text-primary" id="summaryRate">-</p>
                                </div>
                                <div class="mb-3">
                                    <small class="text-muted">Estimated Total</small>
                                    <p class="mb-1 fw-bold" id="summaryTotal">-</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="mt-4">
                        <button type="button" class="btn btn-outline-secondary prev-step" data-prev="1">Back</button>
                        <button type="button" class="btn btn-primary next-step" data-next="3" 
                                <?php echo empty($available_cars) ? 'disabled' : ''; ?>>Next: Rental Details
                        </button>
                    </div>
                </div>

                <!-- Step 3: Rental Details -->
                <div class="form-section" id="step3">
                    <h4 class="mb-4">Rental Details</h4>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Pick-up Date *</label>
                            <input type="date" class="form-control" name="start_date" id="startDate" required
                                   min="<?php echo date('Y-m-d'); ?>">
                            <div class="error-message" id="startDateError"></div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Return Date *</label>
                            <input type="date" class="form-control" name="end_date" id="endDate" required
                                   min="<?php echo date('Y-m-d'); ?>">
                            <div class="error-message" id="endDateError"></div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Pick-up Location *</label>
                            <select class="form-select" name="pickup_location" required>
                                <option value="">Select location</option>
                                <?php foreach($locations as $location): ?>
                                    <option value="<?php echo htmlspecialchars($location); ?>">
                                        <?php echo htmlspecialchars($location); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Return Location *</label>
                            <select class="form-select" name="return_location" required>
                                <option value="">Select location</option>
                                <?php foreach($locations as $location): ?>
                                    <option value="<?php echo htmlspecialchars($location); ?>">
                                        <?php echo htmlspecialchars($location); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Additional Services</label>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="additional_services[]" value="insurance" id="insurance">
                            <label class="form-check-label" for="insurance">Additional Insurance (R15/day)</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="additional_services[]" value="gps" id="gps">
                            <label class="form-check-label" for="gps">GPS Navigation (R10/day)</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="additional_services[]" value="child-seat" id="childSeat">
                            <label class="form-check-label" for="childSeat">Child Safety Seat (R8/day)</label>
                        </div>
                    </div>
                    <div class="mt-4">
                        <button type="button" class="btn btn-outline-secondary prev-step" data-prev="2">Back</button>
                        <button type="button" class="btn btn-primary next-step" data-next="4" id="step3Next">
                            Next: Confirmation
                        </button>
                    </div>
                </div>

                <!-- Step 4: Confirmation -->
                <div class="form-section" id="step4">
                    <h4 class="mb-4">Confirmation</h4>
                    <div class="row">
                        <div class="col-md-8">
                            <div class="card bg-light">
                                <div class="card-body">
                                    <h5 class="card-title">Rental Summary</h5>
                                    <div class="row">
                                        <div class="col-6">
                                            <p><strong>Customer:</strong> <span id="confirmCustomer">-</span></p>
                                            <p><strong>Vehicle:</strong> <span id="confirmVehicle">-</span></p>
                                            <p><strong>Rental Period:</strong> <span id="confirmPeriod">-</span> days</p>
                                            <p><strong>Pick-up:</strong> <span id="confirmPickup">-</span></p>
                                            <p><strong>Return:</strong> <span id="confirmReturn">-</span></p>
                                        </div>
                                        <div class="col-6">
                                            <p><strong>Daily Rate:</strong> R<span id="confirmDailyRate">-</span></p>
                                            <p><strong>Additional Services:</strong> <span id="confirmServices">None</span></p>
                                            <p><strong>Total Cost:</strong> R<span id="confirmTotal">-</span></p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="mt-4">
                        <button type="button" class="btn btn-outline-secondary prev-step" data-prev="3">Back</button>
                        <button type="submit" class="btn btn-success">
                            <i class="fas fa-check me-2"></i>Confirm Rental
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Customer search functionality
        let searchTimeout;
        document.getElementById('customerSearch').addEventListener('input', function(e) {
            clearTimeout(searchTimeout);
            const query = e.target.value.trim();
            
            if (query.length < 2) {
                document.getElementById('customerResults').style.display = 'none';
                return;
            }
            
            searchTimeout = setTimeout(() => {
                searchCustomers(query);
            }, 300);
        });

        async function searchCustomers(query) {
            const resultsDiv = document.getElementById('customerResults');
            const loadingDiv = document.getElementById('searchLoading');
            
            resultsDiv.style.display = 'block';
            loadingDiv.style.display = 'block';
            resultsDiv.innerHTML = '';
            resultsDiv.appendChild(loadingDiv);
            
            try {
                const response = await fetch(`../includes/search_customers.php?q=${encodeURIComponent(query)}`);
                const customers = await response.json();
                
                resultsDiv.innerHTML = '';
                
                if (customers.length === 0) {
                    resultsDiv.innerHTML = '<div class="customer-result text-muted">No customers found</div>';
                } else {
                    customers.forEach(customer => {
                        const div = document.createElement('div');
                        div.className = 'customer-result';
                        div.innerHTML = `
                            <strong>${escapeHtml(customer.name)}</strong><br>
                            <small>${escapeHtml(customer.email)} | ${escapeHtml(customer.phone)}</small>
                        `;
                        div.addEventListener('click', () => selectCustomer(customer));
                        resultsDiv.appendChild(div);
                    });
                }
            } catch (error) {
                resultsDiv.innerHTML = '<div class="customer-result text-danger">Error searching customers</div>';
            }
        }

        function selectCustomer(customer) {
            document.getElementById('customerId').value = customer.id;
            document.getElementById('customerName').textContent = customer.name;
            document.getElementById('customerEmail').textContent = customer.email;
            document.getElementById('customerPhone').textContent = customer.phone;
            document.getElementById('selectedCustomerCard').style.display = 'block';
            document.getElementById('customerResults').style.display = 'none';
            document.getElementById('customerSearch').value = '';
            document.getElementById('step1Next').disabled = false;
            
            // Update confirmation
            document.getElementById('confirmCustomer').textContent = customer.name;
        }

        function escapeHtml(unsafe) {
            return unsafe
                .replace(/&/g, "&amp;")
                .replace(/</g, "&lt;")
                .replace(/>/g, "&gt;")
                .replace(/"/g, "&quot;")
                .replace(/'/g, "&#039;");
        }

        // Car selection and pricing
        let selectedCar = null;
        document.querySelectorAll('.car-option').forEach(option => {
            option.addEventListener('click', function() {
                document.querySelectorAll('.car-option').forEach(opt => {
                    opt.classList.remove('selected');
                });
                this.classList.add('selected');
                this.querySelector('input[type="radio"]').checked = true;
                
                selectedCar = {
                    id: this.dataset.carId,
                    name: this.querySelector('h6').textContent,
                    dailyRate: parseFloat(this.dataset.dailyRate)
                };
                
                updateCarSummary();
            });
        });

        // Date validation
        document.getElementById('startDate').addEventListener('change', validateDates);
        document.getElementById('endDate').addEventListener('change', validateDates);

        function validateDates() {
            const startDate = document.getElementById('startDate');
            const endDate = document.getElementById('endDate');
            const startError = document.getElementById('startDateError');
            const endError = document.getElementById('endDateError');
            
            startError.textContent = '';
            endError.textContent = '';
            
            if (startDate.value && endDate.value) {
                const start = new Date(startDate.value);
                const end = new Date(endDate.value);
                const today = new Date();
                today.setHours(0, 0, 0, 0);
                
                if (start < today) {
                    startError.textContent = 'Pick-up date cannot be in the past.';
                    document.getElementById('step3Next').disabled = true;
                    return;
                }
                
                if (end <= start) {
                    endError.textContent = 'Return date must be after pick-up date.';
                    document.getElementById('step3Next').disabled = true;
                    return;
                }
                
                document.getElementById('step3Next').disabled = false;
                updateCarSummary();
            }
        }

        function updateCarSummary() {
            if (!selectedCar) return;
            
            const startDate = document.getElementById('startDate').value;
            const endDate = document.getElementById('endDate').value;
            
            document.getElementById('summaryCar').textContent = selectedCar.name;
            document.getElementById('summaryRate').textContent = `R${selectedCar.dailyRate.toFixed(2)}/day`;
            
            if (startDate && endDate) {
                const start = new Date(startDate);
                const end = new Date(endDate);
                const days = Math.ceil((end - start) / (1000 * 60 * 60 * 24));
                const total = selectedCar.dailyRate * days;
                
                document.getElementById('summaryTotal').textContent = `R${total.toFixed(2)} (${days} days)`;
                
                // Update confirmation
                document.getElementById('confirmVehicle').textContent = selectedCar.name;
                document.getElementById('confirmDailyRate').textContent = selectedCar.dailyRate.toFixed(2);
                document.getElementById('confirmPeriod').textContent = days;
                document.getElementById('confirmPickup').textContent = formatDate(startDate);
                document.getElementById('confirmReturn').textContent = formatDate(endDate);
                
                // Calculate total with services
                let finalTotal = total;
                const services = [];
                const serviceCosts = { insurance: 15, gps: 10, 'child-seat': 8 };
                
                document.querySelectorAll('input[name="additional_services[]"]:checked').forEach(checkbox => {
                    services.push(checkbox.nextElementSibling.textContent.split(' (')[0]);
                    finalTotal += serviceCosts[checkbox.value] * days;
                });
                
                document.getElementById('confirmServices').textContent = services.length > 0 ? services.join(', ') : 'None';
                document.getElementById('confirmTotal').textContent = finalTotal.toFixed(2);
            }
        }

        function formatDate(dateString) {
            const date = new Date(dateString);
            return date.toLocaleDateString('en-US', { 
                year: 'numeric', 
                month: 'short', 
                day: 'numeric' 
            });
        }

        // Multi-step form functionality (keep existing)
        document.querySelectorAll('.next-step').forEach(btn => {
            btn.addEventListener('click', function() {
                const nextStep = this.getAttribute('data-next');
                showStep(nextStep);
            });
        });

        document.querySelectorAll('.prev-step').forEach(btn => {
            btn.addEventListener('click', function() {
                const prevStep = this.getAttribute('data-prev');
                showStep(prevStep);
            });
        });

        function showStep(step) {
            document.querySelectorAll('.form-section').forEach(section => {
                section.classList.remove('active');
            });
            document.getElementById(`step${step}`).classList.add('active');
            
            document.querySelectorAll('.step').forEach(stepEl => {
                const stepNum = stepEl.getAttribute('data-step');
                stepEl.classList.remove('active', 'completed');
                
                if (stepNum === step) {
                    stepEl.classList.add('active');
                } else if (stepNum < step) {
                    stepEl.classList.add('completed');
                }
            });
        }

        // Initialize first car selection
        if (document.querySelector('.car-option.selected')) {
            const selectedOption = document.querySelector('.car-option.selected');
            selectedCar = {
                id: selectedOption.dataset.carId,
                name: selectedOption.querySelector('h6').textContent,
                dailyRate: parseFloat(selectedOption.dataset.dailyRate)
            };
            updateCarSummary();
        }
    </script>
</body>
</html>