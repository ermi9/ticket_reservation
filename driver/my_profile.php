<?php
 session_start();

 require_once __DIR__ . '/../config/database.php';


 if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'driver') {
    header("Location: ../public/login.php");
    exit();
}

$currentDriverUserId = $_SESSION['user_id']; 
$fullName = $_SESSION['full_name'] ?? 'Driver User'; 
$driverProfile = null; 
$errorMessage = '';


$sql = "
    SELECT
        u.full_name,
        u.email,
        d.license_number,
        d.phone_number
    FROM
        users u
    JOIN
        drivers d ON u.id = d.user_id
    WHERE
        u.id = ? AND u.role = 'driver';
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $currentDriverUserId);
$stmt->execute();
$result = $stmt->get_result();

if ($result) {
    if ($result->num_rows > 0) {
        $driverProfile = $result->fetch_assoc();
    } else {
        $errorMessage = 'Your driver profile could not be found.';
    }
} else {
    $errorMessage = 'Error fetching your profile data: ' . $conn->error;
}

$conn->close(); 
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile</title>
    <link rel="stylesheet" href="my_profile.css">
</head>
<body>
    <div class="container">
        <header class="header">
            <h1>My Profile</h1>
        </header>

        <main class="main-content">
            <h2>Welcome, <?php echo htmlspecialchars($fullName); ?>!</h2>
            <h3>My Profile Details</h3>

            <?php if ($errorMessage): ?>
                <p class="message error"><?php echo htmlspecialchars($errorMessage); ?></p>
            <?php endif; ?>

            <?php if ($driverProfile): ?>
                <div class="profile-details">
                    <p><strong>Full Name:</strong> <span><?php echo htmlspecialchars($driverProfile['full_name']); ?></span></p>
                    <p><strong>Email:</strong> <span><?php echo htmlspecialchars($driverProfile['email']); ?></span></p>
                    <p><strong>License Number:</strong> <span><?php echo htmlspecialchars($driverProfile['license_number']); ?></span></p>
                    <p><strong>Phone Number:</strong> <span><?php echo htmlspecialchars($driverProfile['phone_number']); ?></span></p>
                </div>
            <?php else: ?>
                <p>Unable to load your profile details.</p>
            <?php endif; ?>

            <div class="navigation-links">
                <p><a href="driver_index.php" class="secondary">Back to Driver Dashboard</a></p>
                <p><a href="../public/logout.php">Logout</a></p>
            </div>
        </main>

        <footer class="footer">
            <p>&copy; <?php echo date("Y"); ?> Driver Panel. All rights reserved.</p>
        </footer>
    </div>
</body>
</html>
