<?php

$host = getenv('HIMS_DB_HOST') ?: 'localhost';
$dbname = getenv('HIMS_DB_NAME') ?: 'hardware_inventory';
$username = getenv('HIMS_DB_USER') ?: 'root';
$password = getenv('HIMS_DB_PASSWORD') ?: '';

try {
    $pdo = new PDO(
        "mysql:host={$host};dbname={$dbname};charset=utf8mb4",
        $username,
        $password,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        ]
    );
} catch (PDOException $exception) {
    error_log('HIMS database connection failed: ' . $exception->getMessage());
    http_response_code(503);
    exit('The service is temporarily unavailable.');
}
