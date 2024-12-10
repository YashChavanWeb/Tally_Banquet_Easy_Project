<?php
define('DB_HOST', 'localhost');      // Database Host
define('DB_PORT', '5432');           // Database Port
define('DB_NAME', 'demo');        // Database Name
define('DB_USER', 'postgres');       // Database Username
define('DB_PASS', 'krisha');         // Database Password

// Define the DSN for PDO connection
$dsn = "pgsql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME;
$username = DB_USER;
$password = DB_PASS;

try {
    // Create a new PDO instance
    $pdo = new PDO($dsn, $username, $password);
    // Set the PDO error mode to exception
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "Database connected successfully!";
} catch (PDOException $e) {
    // Catch and handle any errors
    echo "Connection failed: " . $e->getMessage();
    exit;
}
?>