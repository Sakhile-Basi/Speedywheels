<?php
require_once 'config.php';

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    // Normalise and sanitize input
    $email = trim($_POST['email'] ?? '');
    $email = strtolower($email); // Normalise email to lowercase
    $password = trim($_POST['password'] ?? '');

    try {
        require_once 'db.php';
        require_once 'login.model.php';
        require_once 'login.contr.php';
        require_once 'login.view.php';

        $errors = [];
        // --- INPUT VALIDATION BLOCK ---
        if (is_input_empty($email, $password)) {
            $errors["empty_input"] = "Please fill in all fields.";
        }
        // Only proceed with database operations if basic validation passes
        if (empty($errors)) {
            // --- DATABASE OPERATIONS BLOCK ---
            $employee = get_employee($pdo, $email);
            // Check if employee exists
            if (is_employeeEmail_wrong($employee)) {
                $errors["login_incorrect"] = "Invalid email or password.";
            }
            // Check password if employee exists
            else if (is_password_wrong($password, $employee["password"])) {
                $errors["login_incorrect"] = "Invalid email or password.";
            }
        }
        // --- ERROR HANDLING BLOCK ---
        if (!empty($errors)) {
            $_SESSION["error_login"] = $errors;
            header("Location: /WE22/carRental/index.php");
            exit;
        }

        // --- LOGIN SUCCESS BLOCK ---
        // Rotate session to prevent fixation
        session_regenerate_id(true);
        // Store safe session data
        $_SESSION['employee_id']       = $employee['id'];
        $_SESSION['employee_name']     = htmlspecialchars($employee['name']);
        $_SESSION['employee_email']    = htmlspecialchars($employee['email']);
        $_SESSION['employee_position'] = htmlspecialchars($employee['position']);
        $_SESSION['last_regeneration'] = time();

        header("Location: /WE22/carRental/pages/employeeProfile.php?login=success");
        exit;

    } 
    catch (PDOException $e) {
        error_log("Login database error: " . $e->getMessage());
        $_SESSION["error_login"] = ["system_error" => "System error. Please try again."];
        header("Location: /WE22/carRental/index.php");
        exit;
    }
} 
else {
    // Redirect if accessed directly
    header("Location: /WE22/carRental/index.php");
    exit;
}
