<?php
session_start(); 

require_once __DIR__ . '/../config/database.php'; 

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../public/login.php");
    exit();
}

$fullName = $_SESSION['full_name'] ?? 'Admin User';
$pageTitle = "Admin Dashboard";

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
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title><?php echo htmlspecialchars($pageTitle); ?></title>
    <link rel="stylesheet" href="admin_index.css" />
</head>
<body>
    <div class="main-content">
        <h1>Welcome, <?php echo htmlspecialchars($fullName); ?>!</h1>
        <h2><?php echo htmlspecialchars($pageTitle); ?></h2>

        <?php if ($successMessage): ?>
            <p class="message success"><?php echo $successMessage; ?></p>
        <?php endif; ?>

        <?php if ($errorMessage): ?>
            <p class="message error"><?php echo $errorMessage; ?></p>
        <?php endif; ?>

        <p>Please select an administrative task:</p>

        <ul>
            <li><a href="manage_operators.php">Manage Existing Operators</a></li>
            <li><a href="add_operator.php">Add New Operator</a></li>
            <li><a href="assign_operator_cities.php">Assign/Change Operator Cities</a></li>
        </ul>

        <div class="navigation-links">
            <p><a href="../public/logout.php">Logout</a></p>
        </div>
    </div>
</body>
</html>
