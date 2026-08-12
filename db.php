<?php
// ============================================
// Database Connection & Session Initialization
// Uses PDO for secure database operations
// ============================================

// Set correct timezone for India (IST)
date_default_timezone_set('Asia/Kolkata');

$host = 'localhost';
$dbname = 'medishare_db';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
    
    // Set MySQL session timezone to IST
    $pdo->exec("SET time_zone = '+05:30'");
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}
