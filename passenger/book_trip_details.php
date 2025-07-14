<?php
session_start();

require_once __DIR__ . '/../config/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'passenger') {
    header("Location: ../public/login.php");
    exit();
}

$currentUserId = $_SESSION['user_id'];
$fullName = $_SESSION['full_name'] ?? 'Passenger';
$pageTitle = "Trip Details and Seat Booking";
$errorMessage = '';
$successMessage = '';
$tripId = $_GET['trip_id'] ?? null;
$tripDetails = null;

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['book_seats'])) {
    $selectedTripId = $_POST['trip_id'] ?? null;
    $numberOfSeatsToBook = (int)($_POST['number_of_seats'] ?? 0);

    if (empty($selectedTripId) || $numberOfSeatsToBook <= 0) {
        $errorMessage = 'Invalid trip or number of seats.';
    } else {
        $conn->begin_transaction();
        try {
            $checkStmt = $conn->prepare("
                SELECT
                    t.available_seats,
                    t.price
                FROM
                    trips t
                WHERE
                    t.id = ? AND t.available_seats >= ?
                FOR UPDATE
            ");
            $checkStmt->bind_param("ii", $selectedTripId, $numberOfSeatsToBook);
            $checkStmt->execute();
            $checkStmt->bind_result($currentAvailableSeats, $tripPrice);
            $checkStmt->fetch();
            $checkStmt->close();

            if ($currentAvailableSeats === null || $currentAvailableSeats < $numberOfSeatsToBook) {
                throw new Exception('Not enough seats available for this trip.');
            }

            $totalPrice = $tripPrice * $numberOfSeatsToBook;
            $bookingStatus = 'confirmed';

            $insertBookingStmt = $conn->prepare("
                INSERT INTO booking (user_id, trip_id, booking_date, number_of_seats, total_price, status)
                VALUES (?, ?, NOW(), ?, ?, ?)
            ");
            $insertBookingStmt->bind_param("iiids", $currentUserId, $selectedTripId, $numberOfSeatsToBook, $totalPrice, $bookingStatus);
            if (!$insertBookingStmt->execute()) {
                throw new Exception('Error booking seats: ' . $insertBookingStmt->error);
            }
            $insertBookingStmt->close();

            $updateTripStmt = $conn->prepare("
                UPDATE trips
                SET available_seats = available_seats - ?
                WHERE id = ?
            ");
            $updateTripStmt->bind_param("ii", $numberOfSeatsToBook, $selectedTripId);
            if (!$updateTripStmt->execute()) {
                throw new Exception('Error updating trip available seats: ' . $updateTripStmt->error);
            }
            $updateTripStmt->close();

            $conn->commit();
            $successMessage = 'Trip booked successfully!';
            header("Location: viewticket.php?booking_success=true");
            exit();

        } catch (Exception $e) {
            $conn->rollback();
            $errorMessage = $e->getMessage();
        }
    }
}

if ($tripId) {
    $tripSql = "
        SELECT
            t.id AS trip_id,
            t.departure_time,
            t.price,
            t.available_seats,
            b.id AS bus_id,
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
        WHERE
            t.id = ?;
    ";
    $stmt = $conn->prepare($tripSql);
    $stmt->bind_param("i", $tripId);
    $stmt->execute();
    $tripResult = $stmt->get_result();

    if ($tripResult->num_rows > 0) {
        $tripDetails = $tripResult->fetch_assoc();
    } else {
        $errorMessage = 'Trip not found.';
    }
    $stmt->close();
} else {
    $errorMessage = 'No trip selected.';
}

if (isset($conn) && $conn->ping()) {
    $conn->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?php echo htmlspecialchars($pageTitle); ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="book_trip_details.css">
</head>
<body>
<div class="main-content">
    <h1><?php echo htmlspecialchars($pageTitle); ?></h1>
    <h3>Welcome, <?php echo htmlspecialchars($fullName); ?>!</h3>

    <?php if ($errorMessage): ?>
        <div class="message error"><?php echo htmlspecialchars($errorMessage); ?></div>
    <?php endif; ?>

    <?php if ($tripDetails): ?>
        <p><strong>Departure:</strong> <?php echo htmlspecialchars($tripDetails['departure_city_name']); ?></p>
        <p><strong>Destination:</strong> <?php echo htmlspecialchars($tripDetails['destination_city_name']); ?></p>
        <p><strong>Departure Time:</strong> <?php echo htmlspecialchars($tripDetails['departure_time']); ?></p>
        <p><strong>Price per Seat:</strong> $<?php echo htmlspecialchars(number_format($tripDetails['price'], 2)); ?></p>
        <p><strong>Available Seats:</strong> <?php echo htmlspecialchars($tripDetails['available_seats']); ?></p>
        <p><strong>Bus Plate:</strong> <?php echo htmlspecialchars($tripDetails['plate_number']); ?></p>
        <p><strong>Bus Capacity:</strong> <?php echo htmlspecialchars($tripDetails['capacity']); ?></p>
        <p><strong>Distance:</strong> <?php echo htmlspecialchars($tripDetails['distance_km']); ?> km</p>

        <?php if ($tripDetails['available_seats'] > 0): ?>
            <h4>How many seats would you like to book?</h4>
            <form action="book_trip_details.php?trip_id=<?php echo htmlspecialchars($tripId); ?>" method="POST">
                <input type="hidden" name="trip_id" value="<?php echo htmlspecialchars($tripId); ?>">
                <label for="number_of_seats">Number of Seats:</label>
                <input type="number" id="number_of_seats" name="number_of_seats"
                       min="1" max="<?php echo htmlspecialchars($tripDetails['available_seats']); ?>"
                       value="1" required>
                <br>
                <button type="submit" name="book_seats">Confirm Booking</button>
            </form>
        <?php else: ?>
            <div class="message error">This trip is fully booked.</div>
        <?php endif; ?>
    <?php else: ?>
        <div class="message error">Unable to load trip details.</div>
    <?php endif; ?>

    <div class="navigation-links">
        <p><a href="booking.php">Back to Available Trips</a></p>
        <p><a href="passenger_index.php">Back to Dashboard</a></p>
        <p><a href="../public/logout.php">Logout</a></p>
    </div>
</div>
</body>
</html>
