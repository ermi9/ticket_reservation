<?php
session_start(); 

require_once __DIR__ . '/../config/database.php'; 

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../public/login.php");
    exit();
}

$fullName = $_SESSION['full_name'] ?? 'Admin User'; 
$cities = []; 
$errorMessage = '';
$successMessage = '';

$citiesSql = "SELECT id, name FROM cities ORDER BY name ASC";
$citiesResult = $conn->query($citiesSql);
if ($citiesResult) {
    while ($row = $citiesResult->fetch_assoc()) {
        $cities[] = $row;
    }
} else {
    $errorMessage = 'Error fetching cities: ' . $conn->error;
}


if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $operatorFullName = trim($_POST['operatorFullName'] ?? '');
    $operatorEmail = trim($_POST['operatorEmail'] ?? '');
    $operatorPassword = $_POST['operatorPassword'] ?? '';
    $operatorRepeatPassword = $_POST['operatorRepeatPassword'] ?? '';
    $assignedCityId = $_POST['assignedCityId'] ?? null; // Can be null if no city is selected

    if ($assignedCityId === '') {
        $assignedCityId = null;
    }

    if (empty($operatorFullName) || empty($operatorEmail) || empty($operatorPassword) || empty($operatorRepeatPassword)) {
        $errorMessage = 'All fields are required.';
    } elseif ($operatorPassword !== $operatorRepeatPassword) {
        $errorMessage = 'Passwords do not match.';
    } elseif (strlen($operatorPassword) < 6) {
        $errorMessage = 'Password must be at least 6 characters long.';
    } elseif (!filter_var($operatorEmail, FILTER_VALIDATE_EMAIL)) {
        $errorMessage = 'Invalid email format.';
    } else {
        $conn->begin_transaction();
        try {
            $checkEmailStmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
            $checkEmailStmt->bind_param("s", $operatorEmail);
            $checkEmailStmt->execute();
            $checkEmailStmt->store_result();

            if ($checkEmailStmt->num_rows > 0) {
                throw new Exception('This email is already registered. Please use a different email.');
            }
            $checkEmailStmt->close();

            $hashedPassword = password_hash($operatorPassword, PASSWORD_DEFAULT);
            $role = 'operator';

            $insertUserStmt = $conn->prepare("INSERT INTO users (full_name, email, password, role) VALUES (?, ?, ?, ?)");
            $insertUserStmt->bind_param("ssss", $operatorFullName, $operatorEmail, $hashedPassword, $role);
            if (!$insertUserStmt->execute()) {
                throw new Exception('Error adding user: ' . $insertUserStmt->error);
            }
            $newUserId = $insertUserStmt->insert_id; // Get the ID of the newly inserted user
            $insertUserStmt->close();

            $insertOperatorStmt = $conn->prepare("INSERT INTO operator (user_id, city_id) VALUES (?, ?)");
            if ($assignedCityId === null) {
                $insertOperatorStmt->bind_param("ii", $newUserId, $assignedCityId); // 'i' for int, 'i' for int (even if null)
            } else {
                $insertOperatorStmt->bind_param("ii", $newUserId, $assignedCityId);
            }

            if (!$insertOperatorStmt->execute()) {
                throw new Exception('Error adding operator details: ' . $insertOperatorStmt->error);
            }
            $insertOperatorStmt->close();

            $conn->commit(); 
            $successMessage = 'Operator added successfully!';
            $operatorFullName = $operatorEmail = ''; 
            $assignedCityId = null; 
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
    <title>Add New Operator</title>
    <link rel="stylesheet" href="add_operator.css"> </head>
<body>
    <div class="main-content"> <h1>Welcome, <?php echo htmlspecialchars($fullName); ?>!</h1>
        <h2>Add New Operator</h2>
        <h3>Add New Operator</h3>

        <?php if ($successMessage): ?>
            <p class="message success"><?php echo $successMessage; ?></p>
        <?php endif; ?>
        <?php if ($errorMessage): ?>
            <p class="message error"><?php echo $errorMessage; ?></p>
        <?php endif; ?>

        <form action="add_operator.php" method="POST">
            <div>
                <label for="operatorFullName">Operator Full Name:</label><br>
                <input type="text" id="operatorFullName" name="operatorFullName" required
                       value="<?php echo htmlspecialchars($operatorFullName ?? ''); ?>">
            </div>
            <br>
            <div>
                <label for="operatorEmail">Operator Email:</label><br>
                <input type="email" id="operatorEmail" name="operatorEmail" required
                       value="<?php echo htmlspecialchars($operatorEmail ?? ''); ?>">
            </div>
            <br>
            <div>
                <label for="operatorPassword">Password:</label><br>
                <input type="password" id="operatorPassword" name="operatorPassword" required>
            </div>
            <br>
            <div>
                <label for="operatorRepeatPassword">Repeat Password:</label><br>
                <input type="password" id="operatorRepeatPassword" name="operatorRepeatPassword" required>
            </div>
            <br>
            <div>
                <label for="assignedCityId">Assign City (Optional):</label><br>
                <select id="assignedCityId" name="assignedCityId">
                    <option value="">-- Select City --</option>
                    <?php foreach ($cities as $city): ?>
                        <option value="<?php echo htmlspecialchars($city['id']); ?>"
                            <?php echo (isset($assignedCityId) && $assignedCityId == $city['id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($city['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <br>
            <div>
                <button type="submit">Add Operator</button>
            </div>
        </form>

        <div class="navigation-links">
            <p><a href="admin_index.php">Back to Admin Dashboard</a></p>
            <p><a href="../public/logout.php">Logout</a></p>
        </div>
    </div> </body>
</html>
