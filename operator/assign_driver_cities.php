<?php
session_start(); 

require_once __DIR__ . '/../config/database.php'; 

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'operator') {
    header("Location: ../public/login.php");
    exit();
}

$operatorUserId = $_SESSION['user_id']; 
$fullName = $_SESSION['full_name'] ?? 'Operator User'; 
$driverUserId = $_GET['user_id'] ?? null; 

$driverDetails = null;
$cities = []; 
$routes = []; 
$errorMessage = '';
$successMessage = '';

$loggedInOperatorId = null;
$getOperatorIdStmt = $conn->prepare("SELECT id FROM operator WHERE user_id = ?");
$getOperatorIdStmt->bind_param("i", $operatorUserId);
$getOperatorIdStmt->execute();
$getOperatorIdStmt->bind_result($loggedInOperatorId);
$getOperatorIdStmt->fetch();
$getOperatorIdStmt->close();

if (!$loggedInOperatorId) {
    $errorMessage = 'Error: Your operator account is not fully set up. Please contact an administrator.';
    $conn->close();
    exit();
}

$citiesSql = "SELECT id, name FROM cities ORDER BY name ASC";
$citiesResult = $conn->query($citiesSql);
if ($citiesResult) {
    while ($row = $citiesResult->fetch_assoc()) {
        $cities[] = $row;
    }
} else {
    $errorMessage = 'Error fetching cities: ' . $conn->error;
}

$routesSql = "
    SELECT
        r.id AS route_id,
        c1.name AS origin_city_name,
        c2.name AS destination_city_name
    FROM
        routes r
    JOIN
        cities c1 ON r.origin_city_id = c1.id  /* Corrected column name from departure_city_id to origin_city_id */
    JOIN
        cities c2 ON r.destination_city_id = c2.id
    ORDER BY
        origin_city_name, destination_city_name ASC;
";
$routesResult = $conn->query($routesSql);
if ($routesResult) {
    while ($row = $routesResult->fetch_assoc()) {
        $routes[] = $row;
    }
} else {
    $errorMessage = 'Error fetching routes: ' . $conn->error;
}


if ($driverUserId) {
    $driverSql = "
        SELECT
            u.id AS user_id,
            u.full_name,
            u.email,
            d.id AS driver_table_id,
            da.id AS assignment_id,
            da.route_id AS assigned_route_id,
            da.cities_id AS assigned_city_id
        FROM
            users u
        JOIN
            drivers d ON u.id = d.user_id
        LEFT JOIN
            driver_assignments da ON d.id = da.driver_id
        WHERE
            u.id = ? AND u.role = 'driver';
    ";
    $stmt = $conn->prepare($driverSql);
    $stmt->bind_param("i", $driverUserId);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $driverDetails = $result->fetch_assoc();
    } else {
        $errorMessage = 'Driver not found or invalid ID.';
        $driverUserId = null; 
    }
    $stmt->close();
} else {
    $errorMessage = 'No driver selected for assignment.';
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_assignment'])) {
    $postedDriverUserId = (int)$_POST['driver_user_id'];
    $postedDriverTableId = (int)$_POST['driver_table_id']; // The ID from the 'drivers' table
    $newRouteId = $_POST['route_id'] ?? null;
    $newCityId = $_POST['city_id'] ?? null;

    $newRouteId = ($newRouteId === '') ? null : (int)$newRouteId;
    $newCityId = ($newCityId === '') ? null : (int)$newCityId;

    if ($postedDriverUserId <= 0 || $postedDriverTableId <= 0) {
        $errorMessage = 'Invalid driver ID for assignment update.';
    } else {
        $conn->begin_transaction();
        try {
            $checkAssignmentStmt = $conn->prepare("SELECT id FROM driver_assignments WHERE driver_id = ?");
            $checkAssignmentStmt->bind_param("i", $postedDriverTableId);
            $checkAssignmentStmt->execute();
            $checkAssignmentStmt->store_result();
            $assignmentExists = $checkAssignmentStmt->num_rows > 0;
            $checkAssignmentStmt->close();

            if ($assignmentExists) {
                $updateStmt = $conn->prepare("
                    UPDATE driver_assignments
                    SET route_id = ?, cities_id = ?, assigned_at = NOW()
                    WHERE driver_id = ?
                ");
                $updateStmt->bind_param("iii", $newRouteId, $newCityId, $postedDriverTableId);
            } else {
                $updateStmt = $conn->prepare("
                    INSERT INTO driver_assignments (operator_id, driver_id, route_id, cities_id, assigned_at)
                    VALUES (?, ?, ?, ?, NOW())
                ");
                $updateStmt->bind_param("iiii", $loggedInOperatorId, $postedDriverTableId, $newRouteId, $newCityId);
            }

            if (!$updateStmt->execute()) {
                throw new Exception('Error updating driver assignment: ' . $updateStmt->error);
            }

            if ($updateStmt->affected_rows > 0 || ($assignmentExists && $updateStmt->affected_rows === 0)) {
                $successMessage = 'Driver assignment updated successfully!';
                $driverDetails['assigned_route_id'] = $newRouteId;
                $driverDetails['assigned_city_id'] = $newCityId;
            } else {
                $errorMessage = 'No changes made or assignment could not be updated.';
            }
            $updateStmt->close();
            $conn->commit();

        } catch (Exception $e) {
            $conn->rollback();
            $errorMessage = $e->getMessage();
        }
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Assign Driver City and Route</title>
    <link rel="stylesheet" href="assign_driver_cities.css"> </head>
<body>
    <div class="main-content">
        <h1>Welcome, <?php echo htmlspecialchars($fullName); ?>!</h1>
        <h2>Assign City and Route to Driver</h2>
        <h3>Assign City and Route to Driver</h3>

        <?php if ($successMessage): ?>
            <p class="message success"><?php echo $successMessage; ?></p>
        <?php endif; ?>
        <?php if ($errorMessage): ?>
            <p class="message error"><?php echo $errorMessage; ?></p>
        <?php endif; ?>

        <?php if ($driverDetails): ?>
            <p><strong>Driver:</strong> <?php echo htmlspecialchars($driverDetails['full_name']); ?> (<?php echo htmlspecialchars($driverDetails['email']); ?>)</p>
            <p><strong>Current Assigned City:</strong>
                <?php
                $currentCityName = 'N/A';
                if ($driverDetails['assigned_city_id'] !== null) {
                    foreach ($cities as $city) {
                        if ($city['id'] == $driverDetails['assigned_city_id']) {
                            $currentCityName = $city['name'];
                            break;
                        }
                    }
                }
                echo htmlspecialchars($currentCityName);
                ?>
            </p>
            <p><strong>Current Assigned Route:</strong>
                <?php
                $currentRouteName = 'N/A';
                if ($driverDetails['assigned_route_id'] !== null) {
                    foreach ($routes as $route) {
                        if ($route['route_id'] == $driverDetails['assigned_route_id']) {
                            $currentRouteName = htmlspecialchars($route['origin_city_name'] . ' to ' . $route['destination_city_name']);
                            break;
                        }
                    }
                }
                echo $currentRouteName;
                ?>
            </p>

            <form action="assign_driver_cities.php?user_id=<?php echo htmlspecialchars($driverUserId); ?>" method="POST">
                <input type="hidden" name="driver_user_id" value="<?php echo htmlspecialchars($driverUserId); ?>">
                <input type="hidden" name="driver_table_id" value="<?php echo htmlspecialchars($driverDetails['driver_table_id']); ?>">

                <div>
                    <label for="city_id">Assign City:</label><br>
                    <select id="city_id" name="city_id">
                        <option value="">-- Unassign City --</option>
                        <?php foreach ($cities as $city): ?>
                            <option value="<?php echo htmlspecialchars($city['id']); ?>"
                                <?php echo (isset($driverDetails['assigned_city_id']) && $driverDetails['assigned_city_id'] == $city['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($city['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <br>
                <div>
                    <label for="route_id">Assign Route:</label><br>
                    <select id="route_id" name="route_id">
                        <option value="">-- Unassign Route --</option>
                        <?php foreach ($routes as $route): ?>
                            <option value="<?php echo htmlspecialchars($route['route_id']); ?>"
                                <?php echo (isset($driverDetails['assigned_route_id']) && $driverDetails['assigned_route_id'] == $route['route_id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($route['origin_city_name'] . ' to ' . $route['destination_city_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <br>
                <div>
                    <button type="submit" name="update_assignment">Update Assignment</button>
                </div>
            </form>

        <?php else: ?>
            <p>Please select a driver from the <a href="manage_drivers.php">Manage Drivers</a> page.</p>
        <?php endif; ?>

        <div class="navigation-links">
            <p><a href="manage_drivers.php">Back to Manage Drivers</a></p>
            <p><a href="operator_index.php">Back to Operator Dashboard</a></p>
            <p><a href="../public/logout.php">Logout</a></p>
        </div>
    </div>
</body>
</html>
