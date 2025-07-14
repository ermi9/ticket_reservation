<?php
session_start(); 

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'driver') {
    header("Location: ../public/login.php");
    exit();
}

$fullName = $_SESSION['full_name'] ?? 'Driver User';  
$pageTitle = "Driver Dashboard";  
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($pageTitle); ?></title>
    <link rel="stylesheet" href="driver_index.css">
</head>
<body>
    <div class="container">
        <header class="header">
            <h1><?php echo htmlspecialchars($pageTitle); ?></h1>
        </header>

        <main class="main-content">
            <h2>Welcome, <?php echo htmlspecialchars($fullName); ?>!</h2>
            <h3>Driver Dashboard</h3>

            <p>Please select an action:</p>

            <div class="navigation-links">
                <ul>
                    <li><a href="view_my_assignments.php">View My Assigned Trips/Routes</a></li>
                    <li><a href="my_profile.php">View My Profile</a></li>
                </ul>
            </div>

            <div class="logout-link">
                <p><a href="../public/logout.php">Logout</a></p>
            </div>
        </main>

        <footer class="footer">
            <p>&copy; <?php echo date("Y"); ?> Driver Panel. All rights reserved.</p>
        </footer>
    </div>
</body>
</html>
