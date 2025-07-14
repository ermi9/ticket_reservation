<?php
session_start(); 

require_once __DIR__ . '/../config/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'operator') {
    header("Location: ../public/login.php");
    exit();
}

$operatorUserId = $_SESSION['user_id']; $fullName = $_SESSION['full_name'] ?? 'Operator User';
$pageTitle = "Add New Driver";
$errorMessage = '';
$successMessage = '';

$driverFullName = '';
$driverEmail = '';
$licenseNumber = '';
$phoneNumber = '';


if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $driverFullName = trim($_POST['driverFullName'] ?? '');
    $driverEmail = trim($_POST['driverEmail'] ?? '');
    $driverPassword = $_POST['driverPassword'] ?? '';
    $driverRepeatPassword = $_POST['driverRepeatPassword'] ?? '';
    $licenseNumber = trim($_POST['licenseNumber'] ?? '');
    $phoneNumber = trim($_POST['phoneNumber'] ?? '');

    if (empty($driverFullName) || empty($driverEmail) || empty($driverPassword) || empty($driverRepeatPassword) || empty($licenseNumber) || empty($phoneNumber)) {
        $errorMessage = 'All fields are required.';
    } elseif ($driverPassword !== $driverRepeatPassword) {
        $errorMessage = 'Passwords do not match.';
    } elseif (strlen($driverPassword) < 6) {
        $errorMessage = 'Password must be at least 6 characters long.';
    } elseif (!filter_var($driverEmail, FILTER_VALIDATE_EMAIL)) {
        $errorMessage = 'Invalid email format.';
    } else {
        $conn->begin_transaction();
        try {
            $checkEmailStmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
            $checkEmailStmt->bind_param("s", $driverEmail);
            $checkEmailStmt->execute();
            $checkEmailStmt->store_result();

            if ($checkEmailStmt->num_rows > 0) {
                throw new Exception('This email is already registered. Please use a different email.');
            }
            $checkEmailStmt->close();

            $hashedPassword = password_hash($driverPassword, PASSWORD_DEFAULT);
            $role = 'driver';

            $insertUserStmt = $conn->prepare("INSERT INTO users (full_name, email, password, role) VALUES (?, ?, ?, ?)");
            $insertUserStmt->bind_param("ssss", $driverFullName, $driverEmail, $hashedPassword, $role);
            if (!$insertUserStmt->execute()) {
                throw new Exception('Error adding user: ' . $insertUserStmt->error);
            }
            $newUserId = $insertUserStmt->insert_id; 
            $insertUserStmt->close();

            $insertDriverStmt = $conn->prepare("INSERT INTO drivers (user_id, license_number, phone_number) VALUES (?, ?, ?)");
            $insertDriverStmt->bind_param("iss", $newUserId, $licenseNumber, $phoneNumber);

            if (!$insertDriverStmt->execute()) {
                throw new Exception('Error adding driver details: ' . $insertDriverStmt->error);
            }
            $newDriverTableId = $insertDriverStmt->insert_id;
            $insertDriverStmt->close();


            
            $getOperatorIdStmt = $conn->prepare("SELECT id FROM operator WHERE user_id = ?");
            $getOperatorIdStmt->bind_param("i", $operatorUserId);
            $getOperatorIdStmt->execute();
            $getOperatorIdStmt->bind_result($loggedInOperatorId);
            $getOperatorIdStmt->fetch();
            $getOperatorIdStmt->close();

            if (!$loggedInOperatorId) {
                throw new Exception('Could not find operator ID for current user. Please ensure your operator account is correctly set up in the `operator` table.');
            }

            
            $insertAssignmentStmt = $conn->prepare(
                "INSERT INTO driver_assignments (operator_id, driver_id, route_id, cities_id, assigned_at)
                 VALUES (?, ?, NULL, NULL, NOW())"
            );
            $insertAssignmentStmt->bind_param("ii", $loggedInOperatorId, $newDriverTableId);
            if (!$insertAssignmentStmt->execute()) {
                throw new Exception('Error creating initial driver assignment: ' . $insertAssignmentStmt->error);
            }
            $insertAssignmentStmt->close();

            $conn->commit(); 
            $successMessage = 'Driver added successfully and an initial assignment record created!';
            $driverFullName = $driverEmail = $licenseNumber = $phoneNumber = '';
        } catch (Exception $e) {
            $conn->rollback(); 
            $errorMessage = $e->getMessage();
        }
    }
}

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
    <link rel="stylesheet" href="add_driver.css">
</head>
<body>
    <div class="container">
        <header class="header">
            <h1><?php echo htmlspecialchars($pageTitle); ?></h1>
        </header>

        <main class="main-content">
            <h2>Enter Driver Details</h2>

            <?php if (!empty($successMessage)): ?>
                <p class="message success"><?php echo htmlspecialchars($successMessage); ?></p>
            <?php endif; ?>
            <?php if (!empty($errorMessage)): ?>
                <p class="message error"><?php echo htmlspecialchars($errorMessage); ?></p>
            <?php endif; ?>

            <form action="add_driver.php" method="POST">
                <div class="form-group">
                    <label for="driverFullName" class="form-label">Driver Full Name:</label>
                    <input type="text" id="driverFullName" name="driverFullName" class="form-input" required
                           value="<?php echo htmlspecialchars($driverFullName); ?>">
                </div>
                <div class="form-group">
                    <label for="driverEmail" class="form-label">Driver Email:</label>
                    <input type="email" id="driverEmail" name="driverEmail" class="form-input" required
                           value="<?php echo htmlspecialchars($driverEmail); ?>">
                </div>
                <div class="form-group">
                    <label for="driverPassword" class="form-label">Password:</label>
                    <input type="password" id="driverPassword" name="driverPassword" class="form-input" required>
                </div>
                <div class="form-group">
                    <label for="driverRepeatPassword" class="form-label">Repeat Password:</label>
                    <input type="password" id="driverRepeatPassword" name="driverRepeatPassword" class="form-input" required>
                </div>
                <div class="form-group">
                    <label for="licenseNumber" class="form-label">License Number:</label>
                    <input type="text" id="licenseNumber" name="licenseNumber" class="form-input" required
                           value="<?php echo htmlspecialchars($licenseNumber); ?>">
                </div>
                <div class="form-group">
                    <label for="phoneNumber" class="form-label">Phone Number:</label>
                    <input type="text" id="phoneNumber" name="phoneNumber" class="form-input" required
                           value="<?php echo htmlspecialchars($phoneNumber); ?>">
                </div>
                <div>
                    <button type="submit" class="button">Add Driver</button>
                </div>
            </form>

            <div class="navigation-links">
                <p><a href="operator_index.php" class="secondary">Back to Operator Dashboard</a></p>
                <p><a href="../public/logout.php">Logout</a></p>
            </div>
        </main>

        <footer class="footer">
            <p>&copy; <?php echo date("Y"); ?> Operator System. All rights reserved.</p>
        </footer>
    </div>
</body>
</html>
<?php

?>
