<?php
// public/signup.php
session_start();

// Include the database connection file
require_once __DIR__ . '/../config/database.php'; // Corrected path to database.php

// --- Initialize Variables for Feedback Messages ---
$successMessage = '';
$errorMessage = '';

// Initialize form fields for sticky form
$fullName = '';
$email = '';

// --- Check if the form has been submitted ---
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // 1. Retrieve form data
    $fullName = trim($_POST['fullName'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $repeatPassword = $_POST['repeatPassword'] ?? '';

    // 2. Server-side Validation
    if (empty($fullName) || empty($email) || empty($password) || empty($repeatPassword)) {
        $errorMessage = 'All fields are required.';
    } elseif ($password !== $repeatPassword) {
        $errorMessage = 'Passwords do not match.';
    } elseif (strlen($password) < 6) {
        $errorMessage = 'Password must be at least 6 characters long.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errorMessage = 'Invalid email format.';
    } else {
        // 4. Check for unique email
        // Using prepared statements to prevent SQL injection
        $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            $errorMessage = 'This email is already registered. Please use a different email or log in.';
        } else {
            // 5. Hash the password
            // PASSWORD_DEFAULT uses the strongest algorithm available (currently bcrypt)
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

            // 6. Insert user into the 'users' table with role 'passenger'
            $role = 'passenger';
            $insertStmt = $conn->prepare("INSERT INTO users (full_name, email, password, role) VALUES (?, ?, ?, ?)");
            $insertStmt->bind_param("ssss", $fullName, $email, $hashedPassword, $role);

            if ($insertStmt->execute()) {
                $successMessage = 'Sign up successful! You can now log in.';
                // Optionally, clear the form fields after successful registration
                $fullName = $email = ''; // Passwords are not retained for security
            } else {
                $errorMessage = 'Error: ' . $insertStmt->error;
            }
            $insertStmt->close();
        }
        $stmt->close();
    }
}
// Ensure connection is closed if not already closed by exit()
if (isset($conn) && $conn->ping()) {
    $conn->close();
}

// Set page title for the HTML <title> tag and <h3>
$pageTitle = "Sign Up";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($pageTitle); ?></title>
    <link rel="stylesheet" href="signup.css">
</head>
<body>
    <div class="main-content">
        <h1>Welcome to Bus Booking!</h1>
        <h2>Register New Account</h2>
        <h3><?php echo htmlspecialchars($pageTitle); ?></h3>

        <?php if ($successMessage): ?>
            <p class="message success"><?php echo $successMessage; ?></p>
        <?php endif; ?>
        <?php if ($errorMessage): ?>
            <p class="message error"><?php echo $errorMessage; ?></p>
        <?php endif; ?>

        <form action="signup.php" method="POST">
            <div>
                <label for="fullName">Full Name:</label><br>
                <input type="text" id="fullName" name="fullName" required
                       value="<?php echo htmlspecialchars($fullName); ?>">
            </div>
            <br>
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
                <label for="repeatPassword">Repeat Password:</label><br>
                <input type="password" id="repeatPassword" name="repeatPassword" required>
            </div>
            <br>
            <div>
                <button type="submit">Sign Up</button>
            </div>
        </form>

        <div class="navigation-links">
            <p>Already have an account? <a href="login.php">Log in here</a></p>
        </div>
    </div>
</body>
</html>
