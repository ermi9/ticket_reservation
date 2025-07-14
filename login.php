<?php
session_start(); 
require_once __DIR__ . '/../config/database.php';


$successMessage = '';
$errorMessage = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    
    if (empty($email) || empty($password)) {
        $errorMessage = 'Both email and password are required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errorMessage = 'Invalid email format.';
    } else {
        $conn = new mysqli($dbHost, $dbUser, $dbPass, $dbName);

        if ($conn->connect_error) {
            die("Connection failed: " . $conn->connect_error);
        }

        $stmt = $conn->prepare("SELECT id, full_name, password, role FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows == 1) {
            $stmt->bind_result($userId, $fullName, $hashedPassword, $role);
            $stmt->fetch();

            if (password_verify($password, $hashedPassword)) {
               
                $_SESSION['user_id'] = $userId;
                $_SESSION['full_name'] = $fullName;
                $_SESSION['email'] = $email;
                $_SESSION['role'] = $role;

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
        $conn->close(); 
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
</head>
<body>

    <h2>Login</h2>

    <?php if ($successMessage): ?>
        <p style="color: green;"><?php echo $successMessage; ?></p>
    <?php endif; ?>
    <?php if ($errorMessage): ?>
        <p style="color: red;"><?php echo $errorMessage; ?></p>
    <?php endif; ?>

    <form action="login.php" method="POST">
        <div>
            <label for="email">Email address:</label><br>
            <input type="email" id="email" name="email" required
                   value="<?php echo htmlspecialchars($email ?? ''); ?>">
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

    <p>Don't have an account? <a href="signup.php">Sign up here</a></p>

</body>
</html>
