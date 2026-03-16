<?php
// PDO database connection

$host = 'localhost';
$db   = 'appointable_lists';
$user = 'root';
$pass = '';
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (PDOException $e) {
    // Never expose sensitive DB errors to users
    error_log('DB connection failed: ' . $e->getMessage());
    die('Database connection failed. Please try again later.');
}

return $pdo;
