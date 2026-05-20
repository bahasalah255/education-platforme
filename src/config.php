<?php
// Database configuration — edit to match your environment
// Disable display of errors in production; keep reporting enabled
ini_set('display_errors', '0');
ini_set('display_startup_errors', '0');
error_reporting(E_ALL);
define('DB_HOST', '127.0.0.1');
define('DB_NAME', 'education');
define('DB_USER', 'root');
define('DB_PASS', 'salah123');

try {
  $pdo = new PDO('mysql:host='.DB_HOST.';dbname='.DB_NAME.';charset=utf8mb4', DB_USER, DB_PASS, [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
  ]);
} catch (Exception $e) {
  http_response_code(500);
  echo 'Database connection failed: ' . htmlspecialchars($e->getMessage());
  exit;
}
