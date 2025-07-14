<?php
session_start();

require_once __DIR__ . '/../config/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'passenger') {
    header("Location: ../public/login.php");
    exit();
}

$fullName = $_SESSION['full_name'] ?? 'Passenger';
$pageTitle = "Available Trips";
$successMessage = '';
$errorMessage = '';

$trips = [];

$sql = "
    SELECT
        t.id AS trip_id,
        t.departure_time,
        t.price,
        t.available_seats,
        b.plate_number,
        b.capacity,
        r.distance_km,
        dc.name AS departure_city_name,
        destc.name AS destination_city_name
    FROM
        trips t
    JOIN
        buses b ON t.bus_id = b.id
    JOIN
        route_trip_association rta ON t.id = rta.trip_id
    JOIN
        routes r ON rta.route_id = r.id
    JOIN
        cities dc ON r.origin_city_id = dc.id
    JOIN
        cities destc ON r.destination_city_id = destc.id
    ORDER BY
        t.departure_time ASC;
";

$result = $conn->query($sql);

if ($result) {
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $trips[] = $row;
        }
    } else {
        $errorMessage = 'No upcoming trips available at the moment.';
    }
} else {
    $errorMessage = 'Error fetching trips: ' . $conn->error;
}

if (isset($conn) && $conn->ping()) {
    $conn->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title><?php echo htmlspecialchars($pageTitle); ?></title>
    <link rel="stylesheet" href="booking.css" />
</head>
<body>
    <div class="main-content">
        <h1>Welcome, <?php echo htmlspecialchars($fullName); ?>!</h1>
        <h2>Available Trips</h2>

        <?php if ($errorMessage): ?>
            <p class="error"><?php echo htmlspecialchars($errorMessage); ?></p>
        <?php endif; ?>

        <?php if (empty($trips)): ?>
            <p>No upcoming trips found.</p>
        <?php else: ?>
            <table>
                <thead>
                    <tr>
                        <th>Trip ID</th>
                        <th>Departure Time</th>
                        <th>Departure City</th>
                        <th>Destination City</th>
                        <th>Price</th>
                        <th>Available Seats</th>
                        <th>Bus Plate</th>
                        <th>Bus Capacity</th>
                        <th>Distance (km)</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($trips as $trip): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($trip['trip_id']); ?></td>
                            <td><?php echo htmlspecialchars($trip['departure_time']); ?></td>
                            <td><?php echo htmlspecialchars($trip['departure_city_name']); ?></td>
                            <td><?php echo htmlspecialchars($trip['destination_city_name']); ?></td>
                            <td>$<?php echo htmlspecialchars(number_format($trip['price'], 2)); ?></td>
                            <td><?php echo htmlspecialchars($trip['available_seats']); ?></td>
                            <td><?php echo htmlspecialchars($trip['plate_number']); ?></td>
                            <td><?php echo htmlspecialchars($trip['capacity']); ?></td>
                            <td><?php echo htmlspecialchars($trip['distance_km']); ?></td>
                            <td><a href="book_trip_details.php?trip_id=<?php echo urlencode($trip['trip_id']); ?>">Choose Trip</a></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>

        <div class="navigation-links">
            <p><a href="passenger_index.php">Back to Dashboard</a></p>
            <p><a href="../public/logout.php">Logout</a></p>
        </div>
    </div>
</body>
</html>
