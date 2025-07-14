<?php
 session_start();  

 require_once __DIR__ . '/../config/database.php';

 if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'operator') {
    header("Location: ../public/login.php");
    exit();
}

$fullName = $_SESSION['full_name'] ?? 'Operator User';
$pageTitle = "Operator Dashboard";
$successMessage = ''; 
$errorMessage = ''; 

if (isset($conn) && $conn->ping()) {
    $conn->close();
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($pageTitle); ?></title>
    <link rel="stylesheet" href="operator_index.css">
</head>
<body>
    <div class="container">
        <header class="header">
            <h1>Operator Dashboard</h1>
        </header>

        <main class="main-content">
            <h2 class="section-title">Welcome, <?php echo htmlspecialchars($fullName); ?>!</h2>
            <p>Please select an operational task:</p>

            <div class="navigation-links">
                <ul>
                    <li><a href="manage_drivers.php" class="button secondary">Manage Existing Drivers</a></li>
                    <li><a href="add_driver.php" class="button secondary">Add New Driver</a></li>
                    <li><a href="assign_driver_cities.php" class="button secondary">Assign/Change Driver Cities</a></li>
                    <li><a href="viewdrivers.php" class="button secondary">View All Drivers</a></li>
                </ul>
            </div>

            <br>
            <p class="text-center"><a href="../public/logout.php" class="button">Logout</a></p>
        </main>

        <footer class="footer">
            <p>&copy; <?php echo date("Y"); ?> Operator System. All rights reserved.</p>
        </footer>
    </div>
</body>
</html>
<?php
require_once __DIR__ . '/../public/includes/footer.php';
?>
