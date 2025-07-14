<?php
// admin/manage_operators.php
session_start(); // Start the session

// Include the database connection file
require_once __DIR__ . '/../config/database.php';

// Check if the user is logged in and has the 'admin' role
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../public/login.php");
    exit();
}

// Set variables for the HTML <title> tag and headings
$fullName = $_SESSION['full_name'] ?? 'Admin User';
$pageTitle = "Manage Operators";
$errorMessage = '';
$successMessage = '';

$operators = []; // Initialize an empty array to store operator data

// --- Handle Operator Deletion (POST Request) ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['delete_operator_id'])) {
    $operatorUserIdToDelete = (int)$_POST['delete_operator_id'];

    // Start a transaction for atomic deletion
    $conn->begin_transaction();
    try {
        // Delete from the 'users' table.
        // Due to ON DELETE CASCADE on operator.user_id, the corresponding
        // entry in the 'operator' table will be automatically deleted.
        $deleteStmt = $conn->prepare("DELETE FROM users WHERE id = ? AND role = 'operator'");
        $deleteStmt->bind_param("i", $operatorUserIdToDelete);

        if (!$deleteStmt->execute()) {
            throw new Exception('Error deleting user: ' . $deleteStmt->error);
        }

        if ($deleteStmt->affected_rows > 0) {
            $successMessage = 'Operator deleted successfully!';
        } else {
            // This might happen if the user_id doesn't exist or isn't an operator
            throw new Exception('Operator not found or could not be deleted.');
        }
        $deleteStmt->close();
        $conn->commit(); // Commit the transaction

    } catch (Exception $e) {
        $conn->rollback(); // Rollback transaction on error
        $errorMessage = $e->getMessage();
    }
}


// --- Fetch All Operators ---
// Join users and operator tables to get full details, and cities for assigned city name
$sql = "
    SELECT
        u.id AS user_id,
        u.full_name,
        u.email,
        op.id AS operator_table_id, -- ID from the operator table itself
        c.name AS assigned_city_name
    FROM
        users u
    JOIN
        operator op ON u.id = op.user_id
    LEFT JOIN
        cities c ON op.city_id = c.id
    WHERE
        u.role = 'operator'
    ORDER BY
        u.full_name ASC;
";

$result = $conn->query($sql);

if ($result) {
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $operators[] = $row;
        }
    } else {
        $errorMessage = 'No operators found.';
    }
} else {
    $errorMessage = 'Error fetching operators: ' . $conn->error;
}

// Ensure connection is closed
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
    <link rel="stylesheet" href="manage_operators.css">
</head>
<body>
    <div class="main-content">
        <h1>Welcome, <?php echo htmlspecialchars($fullName); ?>!</h1>
        <h2><?php echo htmlspecialchars($pageTitle); ?></h2>
        <h3><?php echo htmlspecialchars($pageTitle); ?></h3>

        <?php if ($successMessage): ?>
            <p class="message success"><?php echo $successMessage; ?></p>
        <?php endif; ?>
        <?php if ($errorMessage): ?>
            <p class="message error"><?php echo $errorMessage; ?></p>
        <?php endif; ?>

        <?php if (empty($operators)): ?>
            <p>No operators found in the system.</p>
        <?php else: ?>
            <table>
                <thead>
                    <tr>
                        <th>User ID</th>
                        <th>Full Name</th>
                        <th>Email</th>
                        <th>Assigned City</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($operators as $operator): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($operator['user_id']); ?></td>
                            <td><?php echo htmlspecialchars($operator['full_name']); ?></td>
                            <td><?php echo htmlspecialchars($operator['email']); ?></td>
                            <td><?php echo htmlspecialchars($operator['assigned_city_name'] ?? 'N/A'); ?></td>
                            <td>
                                <a href="assign_operator_cities.php?user_id=<?php echo htmlspecialchars($operator['user_id']); ?>">Edit</a>
                                |
                                <form method="POST" action="manage_operators.php" style="display:inline;">
                                    <input type="hidden" name="delete_operator_id" value="<?php echo htmlspecialchars($operator['user_id']); ?>">
                                    <button type="submit">Delete</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>

        <div class="navigation-links">
            <p><a href="admin_index.php">Back to Admin Dashboard</a></p>
            <p><a href="../public/logout.php">Logout</a></p>
        </div>
    </div>
</body>
</html>
