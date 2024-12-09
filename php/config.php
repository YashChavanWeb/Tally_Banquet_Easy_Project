<?php
define('DB_HOST', 'localhost');      // Database Host
define('DB_PORT', '5432');           // Database Port
define('DB_NAME', 'Tallydb');        // Database Name
define('DB_USER', 'postgres');      // Database Username
define('DB_PASS', '12345678');      // Database Password

// Define the DSN for PDO connection
$dsn = "pgsql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME;
$username = DB_USER;
$password = DB_PASS;
?>
