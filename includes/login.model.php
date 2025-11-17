<?php
declare(strict_types=1);

// Get employee by email
function get_employee(PDO $pdo, string $email): ?array 
{
    $sql = "SELECT * FROM employees WHERE email = :email";
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(":email", $email);
    $stmt->execute();

    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    return $result ?: null;
}

