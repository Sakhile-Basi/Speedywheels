<?php
$dbServerName = "localhost";
$dbName = "car_rental";   
$dbUserName = "root";        
$dbPassword = "";            

try {
    $pdo = new PDO("mysql:host=$dbServerName;dbname=$dbName", $dbUserName, $dbPassword);
    
    // Set error mode first
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Enable true prepared statements 
    $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
    
    // Set consistent character set
    $pdo->exec("SET NAMES utf8mb4");
    
} 
catch (PDOException $e) {
    // Log error instead of displaying in production
    error_log("Database connection failed: " . $e->getMessage());
    
    // User message
    die("Database connection error. Please try again later.");
}
  