<?php
// public/login.php
session_start(); // Start the session at the very beginning

// Include the database connection file
require_once __DIR__ . '/../config/database.php'; // Corrected path to database.php

// If a user is already logged in, redirect them to their respective dashboard
if (isset($_SESSION['user_id']) && isset($_SESSION['role'])) {
    if ($_SESSION['role'] === 'admin') {
        header("Location: ../admin/admin_index.php"); // Corrected path
        exit();
    } elseif ($_SESSION['role'] === 'operator') {
        header("Location: ../operator/operator_index.php"); // Corrected path
        exit();
    } elseif ($_SESSION['role'] === 'driver') {
        header("Location: ../driver/driver_index.php"); // Corrected path
        exit();
    } elseif ($_SESSION['role'] === 'passenger') {
        header("Location: ../passenger/passenger_index.php"); // Corrected path
        exit();
    }
}

// --- Initialize Variables for Feedback Messages ---
$successMessage = '';
$errorMessage = '';

// Initialize email for sticky form
$email = '';

// --- Check if the form has been submitted ---
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // 1. Retrieve form data
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    // 2. Server-side Validation
    if (empty($email) || empty($password)) {
        $errorMessage = 'Both email and password are required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errorMessage = 'Invalid email format.';
    } else {
        // 4. Retrieve user from the 'users' table by email
        // Using prepared statements to prevent SQL injection
        // Fetch full_name along with id, password, and role
        $stmt = $conn->prepare("SELECT id, full_name, password, role FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows == 1) {
            $stmt->bind_result($userId, $fullName, $hashedPassword, $role);
            $stmt->fetch();

            // 5. Verify the password
            if (password_verify($password, $hashedPassword)) {
                // Login successful!
                // Set session variables
                $_SESSION['user_id'] = $userId;
                $_SESSION['full_name'] = $fullName;
                $_SESSION['email'] = $email;
                $_SESSION['role'] = $role;

                // Redirect based on role to their respective subfolders
                switch ($role) {
                    case 'admin':
                        header("Location: ../admin/admin_index.php");
                        exit();
                    case 'operator':
                        header("Location: ../operator/operator_index.php");
                        exit();
                    case 'driver':
                        header("Location: ../driver/driver_index.php");
                        exit();
                    case 'passenger':
                        header("Location: ../passenger/passenger_index.php");
                        exit();
                    default:
                        // Fallback for unknown roles or if role is not set
                        $errorMessage = 'Login successful, but role not recognized. Please contact support.';
                        break;
                }
            } else {
                $errorMessage = 'Invalid email or password.';
            }
        } else {
            $errorMessage = 'Invalid email or password.';
        }

        $stmt->close();
    }
}
// Ensure connection is closed if not already closed by exit()
if (isset($conn) && $conn->ping()) {
    $conn->close();
}

// Set page title for header.php (still used for consistency with header.php's title tag)
$pageTitle = "Login";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($pageTitle); ?></title>
    <link rel="stylesheet" href="login.css">
</head>
<body>
    <div class="main-content"> <h2>Welcome!</h2>
        <h3><?php echo htmlspecialchars($pageTitle); ?></h3>

        <?php if ($successMessage): ?>
            <p class="message success"><?php echo $successMessage; ?></p>
        <?php endif; ?>
        <?php if ($errorMessage): ?>
            <p class="message error"><?php echo $errorMessage; ?></p>
        <?php endif; ?>

        <form action="login.php" method="POST">
            <div>
                <label for="email">Email address:</label><br>
                <input type="email" id="email" name="email" required
                       value="<?php echo htmlspecialchars($email); ?>">
            </div>
            <br>
            <div>
                <label for="password">Password:</label><br>
                <input type="password" id="password" name="password" required>
            </div>
            <br>
            <div>
                <button type="submit">Login</button>
            </div>
        </form>

        <div class="navigation-links">
            <p>Don't have an account? <a href="signup.php">Sign up here</a></p>
        </div>
    </div> </body>
</html>
