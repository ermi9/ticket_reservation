<?php
session_start(); 

require_once __DIR__ . '/../config/database.php';


if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'driver') {

    header("Location: ../public/login.php");
    exit();
}

$currentDriverUserId = $_SESSION['user_id']; 
$fullName = $_SESSION['full_name'] ?? 'Driver User'; 
$assignments = []; 
$errorMessage = '';


$driverTableId = null;
$getDriverTableIdStmt = $conn->prepare("SELECT id FROM drivers WHERE user_id = ?");
$getDriverTableIdStmt->bind_param("i", $currentDriverUserId);
$getDriverTableIdStmt->execute();
$getDriverTableIdStmt->bind_result($driverTableId);
$getDriverTableIdStmt->fetch();
$getDriverTableIdStmt->close();

if (!$driverTableId) {
    $errorMessage = 'Error: Driver record not found. Please ensure your driver account is correctly set up.';
    $conn->close();
  
}

if (empty($errorMessage)) { 
    $sql = "
        SELECT
            op_u.full_name AS assigned_by_operator_name, -- Operator's full name who made the assignment
            r_origin_city.name AS route_origin_city_name,
            r_dest_city.name AS route_destination_city_name,
            da.assigned_at,
            t.departure_time, -- Associated trip's departure time
            t.available_seats AS trip_available_seats,
            b.plate_number AS bus_plate_number
        FROM
            driver_assignments da
        JOIN
            drivers d ON da.driver_id = d.id
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
            trips t ON r.id = (SELECT rta.route_id FROM route_trip_association rta WHERE rta.trip_id = t.id) -- Link trips via route_trip_association
        LEFT JOIN
            buses b ON t.bus_id = b.id
        WHERE
            d.id = ? -- Filter by the current driver's ID from the 'drivers' table
        ORDER BY
            da.assigned_at DESC;
    ";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $driverTableId);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result) {
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $assignments[] = $row;
            }
        } else {
            $errorMessage = 'No assignments found for you.';
        }
    } else {
        $errorMessage = 'Error fetching your assignments: ' . $conn->error;
    }
}


$conn->close(); 
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Assignments</title>
    <link rel="stylesheet" href="view_my_assignments.css">
</head>
<body>
    <div class="container">
        <header class="header">
            <h1>My Assignments</h1>
        </header>

        <main class="main-content">
            <h2>Welcome, <?php echo htmlspecialchars($fullName); ?>!</h2>
            <h3>My Assigned Trips and Routes</h3>

            <?php if ($errorMessage): ?>
                <p class="message error"><?php echo htmlspecialchars($errorMessage); ?></p>
            <?php endif; ?>

            <?php if (empty($assignments)): ?>
                <p>You currently have no assigned routes or trips.</p>
            <?php else: ?>
                <div class="assignments-list">
                    <?php foreach ($assignments as $assignment): ?>
                        <div class="assignment-details-card">
                            <p><strong>Assigned By:</strong> <span><?php echo htmlspecialchars($assignment['assigned_by_operator_name'] ?? 'N/A'); ?></span></p>
                            <p><strong>Assigned Route:</strong>
                                <span>
                                    <?php
                                        if (!empty($assignment['route_origin_city_name']) && !empty($assignment['route_destination_city_name'])) {
                                            echo htmlspecialchars($assignment['route_origin_city_name'] . ' to ' . $assignment['route_destination_city_name']);
                                        } else {
                                            echo 'N/A';
                                        }
                                    ?>
                                </span>
                            </p>
                            <p><strong>Assigned At:</strong> <span><?php echo htmlspecialchars($assignment['assigned_at']); ?></span></p>
                            <hr>
                            <p><strong>Departure Time:</strong> <span><?php echo htmlspecialchars($assignment['departure_time'] ?? 'N/A'); ?></span></p>
                            <p><strong>Bus Plate:</strong> <span><?php echo htmlspecialchars($assignment['bus_plate_number'] ?? 'N/A'); ?></span></p>
                            <p><strong>Available Seats:</strong> <span><?php echo htmlspecialchars($assignment['trip_available_seats'] ?? 'N/A'); ?></span></p>
                        </div>
                    <?php endforeach; ?>
                </div>
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
