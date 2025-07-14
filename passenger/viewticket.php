<?php
session_start(); 

require_once __DIR__ . '/../config/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'passenger') {
    header("Location: ../public/login.php");
    exit();
}

$currentUserId = $_SESSION['user_id'];
$fullName = $_SESSION['full_name'] ?? 'Passenger';
$pageTitle = "Your Booking Details";
$errorMessage = '';
$successMessage = '';
$bookings = [];

if (isset($_GET['booking_success']) && $_GET['booking_success'] === 'true') {
    $successMessage = 'Your trip has been successfully booked!';
}

$sql = "
    SELECT
        b.id AS booking_id,
        b.booking_date,
        b.number_of_seats,
        b.total_price,
        b.status,
        t.departure_time,
        t.price AS trip_price,
        bus.plate_number,
        bus.capacity,
        dc.name AS departure_city_name,
        destc.name AS destination_city_name
    FROM
        booking b
    JOIN
        trips t ON b.trip_id = t.id
    JOIN
        buses bus ON t.bus_id = bus.id
    JOIN
        route_trip_association rta ON t.id = rta.trip_id
    JOIN
        routes r ON rta.route_id = r.id
    JOIN
        cities dc ON r.origin_city_id = dc.id
    JOIN
        cities destc ON r.destination_city_id = destc.id
    WHERE
        b.user_id = ?
    ORDER BY
        b.booking_date DESC;
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $currentUserId);
$stmt->execute();
$result = $stmt->get_result();

if ($result) {
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $bookings[] = $row;
        }
    } else {
        $errorMessage = 'You have no active bookings.';
    }
} else {
    $errorMessage = 'Error fetching your bookings: ' . $conn->error;
}

$stmt->close();
if (isset($conn) && $conn->ping()) {
    $conn->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <title>Your Booking Details</title>
    <link rel="stylesheet" href="viewticket.css" />
</head>
<body>
    <div class="main-content">
        <h1>Welcome, <?php echo htmlspecialchars($fullName); ?>!</h1>
        <h2>Your Booking Details</h2>

        <?php if (!empty($successMessage)): ?>
            <div class="message success"><?php echo htmlspecialchars($successMessage); ?></div>
        <?php endif; ?>

        <?php if (!empty($errorMessage)): ?>
            <p><?php echo htmlspecialchars($errorMessage); ?></p>
        <?php elseif (!empty($bookings)): ?>
            <?php foreach ($bookings as $booking): ?>
                <div class="booking-card">
                    <p><strong>Booking ID:</strong> <?php echo htmlspecialchars($booking['booking_id']); ?></p>
                    <p><strong>Booking Date:</strong> <?php echo htmlspecialchars($booking['booking_date']); ?></p>
                    <p><strong>Status:</strong> <?php echo htmlspecialchars(ucfirst($booking['status'])); ?></p>
                    <p><strong>Departure Time:</strong> <?php echo htmlspecialchars($booking['departure_time']); ?></p>
                    <p><strong>Departure City:</strong> <?php echo htmlspecialchars($booking['departure_city_name']); ?></p>
                    <p><strong>Destination City:</strong> <?php echo htmlspecialchars($booking['destination_city_name']); ?></p>
                    <p><strong>Bus Plate:</strong> <?php echo htmlspecialchars($booking['plate_number']); ?></p>
                    <p><strong>Seats Booked:</strong> <?php echo htmlspecialchars($booking['number_of_seats']); ?></p>
                    <p><strong>Total Price:</strong> $<?php echo htmlspecialchars(number_format($booking['total_price'], 2)); ?></p>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>

        <div class="navigation-links">
            <p><a href="passenger_index.php">Back to Dashboard</a></p>
            <p><a href="../public/logout.php">Logout</a></p>
        </div>
    </div>
</body>
</html>
