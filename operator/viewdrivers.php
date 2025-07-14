<?php
session_start();
require_once __DIR__ . '/../config/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'operator') {
    header("Location: ../public/login.php");
    exit();
}

$fullName = $_SESSION['full_name'] ?? 'Operator User';
$pageTitle = "View All Drivers"; 
$drivers = [];
$errorMessage = '';

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
        assigned_city.name AS assigned_city_name,
        da.assigned_at
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
        $errorMessage = 'No drivers found in the system.';
    }
} else {
    $errorMessage = 'Error fetching driver data: ' . $conn->error;
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($pageTitle); ?></title>
    <link rel="stylesheet" href="viewdrivers.css">
</head>
<body>
    <div class="container">
        <header class="header">
            <h1><?php echo htmlspecialchars($pageTitle); ?></h1>
        </header>

        <main class="main-content">
            <h2>Welcome, <?php echo htmlspecialchars($fullName); ?>!</h2>
            <h3>All Driver Details</h3>

            <?php if ($errorMessage): ?>
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
                                <th>Assigned By Operator</th>
                                <th>Assigned Route</th>
                                <th>Assigned City</th>
                                <th>Assigned At</th>
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
                                        <?php echo htmlspecialchars($driver['assigned_at'] ?? 'N/A'); ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>

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
