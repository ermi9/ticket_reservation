<?php
session_start(); 

require_once __DIR__ . '/../config/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'passenger') {
    header("Location: ../public/login.php");
    exit();
}

$fullName = $_SESSION['full_name'] ?? 'Passenger';

$pageTitle = "Passenger Dashboard";
$successMessage = ''; 
$errorMessage = ''; 

if (isset($conn) && $conn->ping()) {
    $conn->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Passenger Dashboard</title>
    <link rel="stylesheet" href="passenger_index.css" />
</head>
<body>
    <div class="main-content">
        <h1>Welcome, <?php echo htmlspecialchars($fullName); ?>!</h1>

        <p>Please select an option:</p>

        <ul>
            <li><a href="booking.php">Book a Trip</a></li>
            <li><a href="viewticket.php">Booking Details</a></li>
        </ul>

        <a href="../public/logout.php" class="logout-link">Logout</a>
    </div>
</body>
</html>
