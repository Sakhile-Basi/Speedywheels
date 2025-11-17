<?php
require_once '../includes/config.php';
require_once '../includes/db.php';

if (!isset($_SESSION['employee_id'])) {
    header("Location: ../index.php");
    exit();
}

// Validation functions
function validateEmail(string $email): bool {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

function validatePhone(string $phone): bool {
    // Basic phone validation - adjust regex for your region
    return preg_match('/^[\+]?[0-9\s\-\(\)]{10,}$/', $phone) || empty($phone);
}

function isEmailUnique(PDO $pdo, string $email, ?int $excludeId = null): bool {
    $sql = "SELECT COUNT(*) FROM customers WHERE email = ?";
    $params = [$email];
    
    if ($excludeId !== null) {
        $sql .= " AND id != ?";
        $params[] = $excludeId;
    }
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchColumn() == 0;
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['action'])) {
        $action = $_POST['action'];
        
        if ($action == 'add_customer') {
            $name = trim($_POST['name']);
            $email = trim($_POST['email']);
            $phone = trim($_POST['phone']);
            $address = trim($_POST['address']);
            
            // Server-side validation
            $errors = [];
            if (empty($name)) $errors[] = "Name is required.";
            if (empty($email)) $errors[] = "Email is required.";
            if (!validateEmail($email)) $errors[] = "Invalid email format.";
            if (!validatePhone($phone)) $errors[] = "Invalid phone number format.";
            if (!isEmailUnique($pdo, $email)) $errors[] = "Email already exists.";
            
            if (empty($errors)) {
                try {
                    $stmt = $pdo->prepare("INSERT INTO customers (name, email, phone, address) VALUES (?, ?, ?, ?)");
                    $stmt->execute([$name, $email, $phone, $address]);
                    $_SESSION['success'] = "Customer added successfully!";
                } catch (PDOException $e) {
                    $_SESSION['error'] = "Error adding customer: " . $e->getMessage();
                }
            } 
            else {
                $_SESSION['error'] = implode(" ", $errors);
            }
        }
        elseif ($action == 'update_customer') {
            $id = (int)$_POST['id'];
            $name = trim($_POST['name']);
            $email = trim($_POST['email']);
            $phone = trim($_POST['phone']);
            $address = trim($_POST['address']);
            
            // Server-side validation
            $errors = [];
            if (empty($name)) $errors[] = "Name is required.";
            if (empty($email)) $errors[] = "Email is required.";
            if (!validateEmail($email)) $errors[] = "Invalid email format.";
            if (!validatePhone($phone)) $errors[] = "Invalid phone number format.";
            if (!isEmailUnique($pdo, $email, $id)) $errors[] = "Email already exists.";
            
            if (empty($errors)) {
                try {
                    $stmt = $pdo->prepare("UPDATE customers SET name=?, email=?, phone=?, address=? WHERE id=?");
                    $stmt->execute([$name, $email, $phone, $address, $id]);
                    $_SESSION['success'] = "Customer updated successfully!";
                } catch (PDOException $e) {
                    $_SESSION['error'] = "Error updating customer: " . $e->getMessage();
                }
            } 
            else {
                $_SESSION['error'] = implode(" ", $errors);
            }
        }
        elseif ($action == 'delete_customer') {
            $id = (int)$_POST['id'];
            
            try {
                // Check if customer has active rentals
                $stmt = $pdo->prepare("SELECT COUNT(*) FROM rentals WHERE customer_id = ? AND status = 'Ongoing'");
                $stmt->execute([$id]);
                $activeRentals = $stmt->fetchColumn();
                
                if ($activeRentals > 0) {
                    $_SESSION['error'] = "Cannot delete customer with active rentals.";
                } 
                else {
                    $stmt = $pdo->prepare("DELETE FROM customers WHERE id = ?");
                    $stmt->execute([$id]);
                    $_SESSION['success'] = "Customer deleted successfully!";
                }
            } catch (PDOException $e) {
                $_SESSION['error'] = "Error deleting customer: " . $e->getMessage();
            }
            
            header("Location: customers.php");
            exit;
        }
    }
}

// Search functionality with prepared statement
$search = $_GET['search'] ?? '';
if ($search) {
    $stmt = $pdo->prepare("SELECT * FROM customers WHERE name LIKE ? OR email LIKE ? OR phone LIKE ? ORDER BY created_at DESC");
    $stmt->execute(["%$search%", "%$search%", "%$search%"]);
    $customers = $stmt->fetchAll();
} 
else {
    $stmt = $pdo->prepare("SELECT * FROM customers ORDER BY created_at DESC");
    $stmt->execute();
    $customers = $stmt->fetchAll();
}

// Get customers with current rentals - improved query with ordering
$stmt = $pdo->prepare("
    SELECT c.*, r.id as rental_id, car.make, car.model, r.start_date, r.end_date 
    FROM customers c 
    JOIN rentals r ON c.id = r.customer_id 
    JOIN cars car ON r.car_id = car.id 
    WHERE r.status = 'Ongoing'
    ORDER BY r.start_date DESC
");
$stmt->execute();
$rented_customers = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Customer Management - Speedy Wheels</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #2c3e50;
            --secondary: #3498db;
            --success: #2ecc71;
            --light-bg: #f8f9fa;
        }
        .validation-message {
            font-size: 0.875rem;
            margin-top: 0.25rem;
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
                    <a class="nav-link active" href="customers.php">
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

        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?php echo htmlspecialchars($_SESSION['error']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php unset($_SESSION['error']); ?>
        <?php endif; ?>

        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="fw-bold">Customer Management</h2>
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addCustomerModal">
                <i class="fas fa-plus me-2"></i>Add New Customer
            </button>
        </div>

        <!-- Search Bar -->
        <div class="row mb-4">
            <div class="col-md-6">
                <form method="GET">
                    <div class="input-group">
                        <input type="text" class="form-control" name="search" placeholder="Search customers by name, email, or phone..." value="<?php echo htmlspecialchars($search); ?>">
                        <button class="btn btn-outline-primary" type="submit">
                            <i class="fas fa-search"></i>
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Current Rentals Section -->
        <?php if ($rented_customers): ?>
        <div class="card mb-4">
            <div class="card-header bg-warning">
                <h5 class="mb-0"><i class="fas fa-car me-2"></i>Currently Rented Cars</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Customer</th>
                                <th>Car</th>
                                <th>Start Date</th>
                                <th>End Date</th>
                                <th>Contact</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($rented_customers as $customer): ?>
                            <tr>
                                <td>
                                    <strong><?php echo htmlspecialchars($customer['name']); ?></strong><br>
                                    <small class="text-muted"><?php echo htmlspecialchars($customer['email']); ?></small>
                                </td>
                                <td><?php echo htmlspecialchars($customer['make'] . ' ' . $customer['model']); ?></td>
                                <td><?php echo htmlspecialchars($customer['start_date']); ?></td>
                                <td><?php echo htmlspecialchars($customer['end_date']); ?></td>
                                <td><?php echo htmlspecialchars($customer['phone']); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- All Customers Table -->
        <div class="card">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0"><i class="fas fa-users me-2"></i>All Customers</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Phone</th>
                                <th>Address</th>
                                <th>Joined</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($customers)): ?>
                            <tr>
                                <td colspan="7" class="text-center text-muted py-4">
                                    <i class="fas fa-users fa-2x mb-3 d-block"></i>
                                    No customers found
                                </td>
                            </tr>
                            <?php else: ?>
                                <?php foreach($customers as $customer): ?>
                                <tr>
                                    <td><?php echo (int)$customer['id']; ?></td>
                                    <td>
                                        <strong><?php echo htmlspecialchars($customer['name']); ?></strong>
                                    </td>
                                    <td><?php echo htmlspecialchars($customer['email']); ?></td>
                                    <td><?php echo htmlspecialchars($customer['phone']); ?></td>
                                    <td><?php echo htmlspecialchars($customer['address']); ?></td>
                                    <td><?php echo date('M j, Y', strtotime($customer['created_at'])); ?></td>
                                    <td>
                                        <button class="btn btn-sm btn-outline-primary edit-customer" 
                                                data-bs-toggle="modal" 
                                                data-bs-target="#editCustomerModal"
                                                data-id="<?php echo (int)$customer['id']; ?>"
                                                data-name="<?php echo htmlspecialchars($customer['name']); ?>"
                                                data-email="<?php echo htmlspecialchars($customer['email']); ?>"
                                                data-phone="<?php echo htmlspecialchars($customer['phone']); ?>"
                                                data-address="<?php echo htmlspecialchars($customer['address']); ?>">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <form method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this customer?')">
                                            <input type="hidden" name="action" value="delete_customer">
                                            <input type="hidden" name="id" value="<?php echo (int)$customer['id']; ?>">
                                            <button type="submit" class="btn btn-sm btn-outline-danger">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Add Customer Modal -->
    <div class="modal fade" id="addCustomerModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST" id="addCustomerForm">
                    <input type="hidden" name="action" value="add_customer">
                    <div class="modal-header">
                        <h5 class="modal-title">Add New Customer</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Full Name *</label>
                            <input type="text" class="form-control" name="name" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Email *</label>
                            <input type="email" class="form-control" name="email" required id="add_email">
                            <div class="validation-message text-danger" id="add_email_validation"></div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Phone</label>
                            <input type="tel" class="form-control" name="phone" id="add_phone">
                            <div class="validation-message text-danger" id="add_phone_validation"></div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Address</label>
                            <textarea class="form-control" name="address" rows="3"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Add Customer</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Customer Modal -->
    <div class="modal fade" id="editCustomerModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST" id="editCustomerForm">
                    <input type="hidden" name="action" value="update_customer">
                    <input type="hidden" name="id" id="edit_customer_id">
                    <div class="modal-header">
                        <h5 class="modal-title">Edit Customer</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Full Name *</label>
                            <input type="text" class="form-control" name="name" id="edit_customer_name" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Email *</label>
                            <input type="email" class="form-control" name="email" id="edit_customer_email" required>
                            <div class="validation-message text-danger" id="edit_email_validation"></div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Phone</label>
                            <input type="tel" class="form-control" name="phone" id="edit_customer_phone">
                            <div class="validation-message text-danger" id="edit_phone_validation"></div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Address</label>
                            <textarea class="form-control" name="address" id="edit_customer_address" rows="3"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Update Customer</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div class="modal fade" id="deleteCustomerModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST">
                    <input type="hidden" name="action" value="delete_customer">
                    <input type="hidden" name="id" id="delete_customer_id">
                    <div class="modal-header">
                        <h5 class="modal-title">Confirm Delete</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <p>Are you sure you want to delete this customer? This action cannot be undone.</p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-danger">Delete Customer</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Edit customer modal population
        document.querySelectorAll('.edit-customer').forEach(button => {
            button.addEventListener('click', function() {
                document.getElementById('edit_customer_id').value = this.dataset.id;
                document.getElementById('edit_customer_name').value = this.dataset.name;
                document.getElementById('edit_customer_email').value = this.dataset.email;
                document.getElementById('edit_customer_phone').value = this.dataset.phone;
                document.getElementById('edit_customer_address').value = this.dataset.address;
            });
        });

        // Client-side validation
        function validateEmail(email) {
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            return emailRegex.test(email);
        }

        function validatePhone(phone) {
            const phoneRegex = /^[\+]?[0-9\s\-\(\)]{10,}$/;
            return phone === '' || phoneRegex.test(phone);
        }

        // Real-time validation for add form
        document.getElementById('add_email')?.addEventListener('blur', function() {
            const validationEl = document.getElementById('add_email_validation');
            if (!validateEmail(this.value)) {
                validationEl.textContent = 'Please enter a valid email address.';
            } else {
                validationEl.textContent = '';
            }
        });

        document.getElementById('add_phone')?.addEventListener('blur', function() {
            const validationEl = document.getElementById('add_phone_validation');
            if (!validatePhone(this.value)) {
                validationEl.textContent = 'Please enter a valid phone number.';
            } else {
                validationEl.textContent = '';
            }
        });

        // Real-time validation for edit form
        document.getElementById('edit_customer_email')?.addEventListener('blur', function() {
            const validationEl = document.getElementById('edit_email_validation');
            if (!validateEmail(this.value)) {
                validationEl.textContent = 'Please enter a valid email address.';
            } 
            else {
                validationEl.textContent = '';
            }
        });

        document.getElementById('edit_customer_phone')?.addEventListener('blur', function() {
            const validationEl = document.getElementById('edit_phone_validation');
            if (!validatePhone(this.value)) {
                validationEl.textContent = 'Please enter a valid phone number.';
            } 
             {
                validationEl.textContent = '';
            }
        });
    </script>
</body>
</html>