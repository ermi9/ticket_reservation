<?php
// config/database.php

// --- Database Connection Configuration ---
// IMPORTANT: Adjust these credentials for your setup.
$dbHost = 'localhost';
$dbUser = 'root'; // Your MySQL username
$dbPass = '';     // Your MySQL password
$dbName = 'bus_db'; // Your database name

// Create a database connection
$conn = new mysqli($dbHost, $dbUser, $dbPass, $dbName);

// Check connection
if ($conn->connect_error) {
    // For development, die() with error is okay.
    // For production, you might log the error and display a generic message.
    die("Connection failed: " . $conn->connect_error);
}

// The $conn variable is now available to any file that includes this one.
?>
