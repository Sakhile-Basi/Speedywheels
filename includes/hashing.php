<?php
// require "db.php"; // your PDO connection

// $hashedPwd = password_hash("1234", PASSWORD_DEFAULT);
// echo $hashedPwd;
// $stmt = $pdo->prepare("INSERT INTO employees (name, email, password, position, employeeId, cell, birthDate, street, suburb, city, postalCode) 
//                        VALUES (:name, :email, :password, :position, :employeeId, :cell, )");

// $stmt->execute([
//     ':name'       => 'Sakhile Basi',
//     ':email'      => 'basi@example.com',
//     ':password'   => $hashedPwd,
//     ':position'   => 'Sales',
//     ':employeeId' => 'EMP001',
//     ':cell'       => '0123456789'
// ]);

// echo "User inserted successfully!";


$entered = "1234"; // what you type
$hashFromDb = '$2y$10$tn/lN0iGwHKvyR03miUSJOcJrILMTeWILPb5aNN2mIWa50S.MyIuS';

if (password_verify($entered, $hashFromDb)) {
    echo "Password correct!";
} else {
    echo "Password incorrect!";
}
