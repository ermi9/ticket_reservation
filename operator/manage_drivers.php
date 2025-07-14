<?php
session_start();  

 require_once __DIR__ . '/../config/database.php';

 if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'operator') {
    header("Location: ../public/login.php");
    exit();
}

 $fullName = $_SESSION['full_name'] ?? 'Operator User';
$pageTitle = "Manage Drivers";
$errorMessage = '';
$successMessage = '';

$drivers = [];

 if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['delete_driver_user_id'])) {
    $driverUserIdToDelete = (int)$_POST['delete_driver_user_id'];  

     $conn->begin_transaction();
    try {
         $getDriverIdStmt = $conn->prepare("SELECT id FROM drivers WHERE user_id = ?");
        $getDriverIdStmt->bind_param("i", $driverUserIdToDelete);
        $getDriverIdStmt->execute();
        $getDriverIdStmt->bind_result($driverTableId);
        $getDriverIdStmt->fetch();
        $getDriverIdStmt->close();

        if (!$driverTableId) {
            throw new Exception('Driver record not found for this user ID.');
        }

         $deleteAssignmentsStmt = $conn->prepare("DELETE FROM driver_assignments WHERE driver_id = ?");
        $deleteAssignmentsStmt->bind_param("i", $driverTableId);
        if (!$deleteAssignmentsStmt->execute()) {
            throw new Exception('Error deleting driver assignments: ' . $deleteAssignmentsStmt->error);
        }
        $deleteAssignmentsStmt->close();

         $deleteDriverStmt = $conn->prepare("DELETE FROM drivers WHERE id = ?");
        $deleteDriverStmt->bind_param("i", $driverTableId);
        if (!$deleteDriverStmt->execute()) {
            throw new Exception('Error deleting driver details: ' . $deleteDriverStmt->error);
        }
        $deleteDriverStmt->close();

         $deleteUserStmt = $conn->prepare("DELETE FROM users WHERE id = ? AND role = 'driver'");
        $deleteUserStmt->bind_param("i", $driverUserIdToDelete);
        if (!$deleteUserStmt->execute()) {
            throw new Exception('Error deleting user account: ' . $deleteUserStmt->error);
        }

        if ($deleteUserStmt->affected_rows > 0) {
            $successMessage = 'Driver and all associated data deleted successfully!';
        } else {
            throw new Exception('User not found or could not be deleted (might not be a driver role).');
        }
        $deleteUserStmt->close();
        $conn->commit();

    } catch (Exception $e) {
        $conn->rollback();
        $errorMessage = $e->getMessage();
    }
}


 $sql = "
    SELECT
        u.id AS user_id,
        u.full_name,
        u.email,
        d.license_number,
        d.phone_number,
        da.id AS assignment_id,
        op_u.full_name AS assigned_by_operator_name, -- Operator's full name who made the assignment
        r_origin_city.name AS route_origin_city_name,
        r_dest_city.name AS route_destination_city_name,
        assigned_city.name AS assigned_city_name
    FROM
        users u
    JOIN
        drivers d ON u.id = d.user_id
    LEFT JOIN
        driver_assignments da ON d.id = da.driver_id -- LEFT JOIN because a driver might not have an assignment yet
    LEFT JOIN
        operator op ON da.operator_id = op.id -- Join to get operator's operator_id
    LEFT JOIN
        users op_u ON op.user_id = op_u.id -- Join to get operator's user details (name)
    LEFT JOIN
        routes r ON da.route_id = r.id
    LEFT JOIN
        cities r_origin_city ON r.origin_city_id = r_origin_city.id
    LEFT JOIN
        cities r_dest_city ON r.destination_city_id = r_dest_city.id
    LEFT JOIN
        cities assigned_city ON da.cities_id = assigned_city.id
    WHERE
        u.role = 'driver'
    ORDER BY
        u.full_name ASC;
";

$result = $conn->query($sql);

if ($result) {
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $drivers[] = $row;
        }
    } else {
        $errorMessage = 'No drivers found.';
    }
} else {
    $errorMessage = 'Error fetching drivers: ' . $conn->error;
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
    <link rel="stylesheet" href="manage_drivers.css">
</head>
<body>
    <div class="container">
        <header class="header">
            <h1><?php echo htmlspecialchars($pageTitle); ?></h1>
        </header>

        <main class="main-content">
            <?php if (!empty($successMessage)): ?>
                <p class="message success"><?php echo htmlspecialchars($successMessage); ?></p>
            <?php endif; ?>
            <?php if (!empty($errorMessage)): ?>
                <p class="message error"><?php echo htmlspecialchars($errorMessage); ?></p>
            <?php endif; ?>

            <?php if (empty($drivers)): ?>
                <p>No drivers found in the system.</p>
            <?php else: ?>
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>User ID</th>
                                <th>Full Name</th>
                                <th>Email</th>
                                <th>License No.</th>
                                <th>Phone No.</th>
                                <th>Assigned By</th>
                                <th>Assigned Route</th>
                                <th>Assigned City</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($drivers as $driver): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($driver['user_id']); ?></td>
                                    <td><?php echo htmlspecialchars($driver['full_name']); ?></td>
                                    <td><?php echo htmlspecialchars($driver['email']); ?></td>
                                    <td><?php echo htmlspecialchars($driver['license_number']); ?></td>
                                    <td><?php echo htmlspecialchars($driver['phone_number']); ?></td>
                                    <td>
                                        <?php echo htmlspecialchars($driver['assigned_by_operator_name'] ?? 'N/A'); ?>
                                    </td>
                                    <td>
                                        <?php
                                            if (!empty($driver['route_origin_city_name']) && !empty($driver['route_destination_city_name'])) {
                                                echo htmlspecialchars($driver['route_origin_city_name'] . ' to ' . $driver['route_destination_city_name']);
                                            } else {
                                                echo 'N/A';
                                            }
                                        ?>
                                    </td>
                                    <td>
                                        <?php echo htmlspecialchars($driver['assigned_city_name'] ?? 'N/A'); ?>
                                    </td>
                                    <td>
                                        <a href="assign_driver_cities.php?user_id=<?php echo htmlspecialchars($driver['user_id']); ?>">Edit/Assign City</a>
                                        <form method="POST" action="manage_drivers.php" style="display:inline;">
                                            <input type="hidden" name="delete_driver_user_id" value="<?php echo htmlspecialchars($driver['user_id']); ?>">
                                            <button type="submit" class="button delete-button">Delete</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>

            <div class="navigation-links">
                <p><a href="operator_index.php" class="button secondary">Back to Operator Dashboard</a></p>
                <p><a href="../public/logout.php" class="button">Logout</a></p>
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
