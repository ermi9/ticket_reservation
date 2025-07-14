<?php
session_start(); 

require_once __DIR__ . '/../config/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../public/login.php");
    exit();
}

$fullName = $_SESSION['full_name'] ?? 'Admin User';
$pageTitle = "Assign Operator City";
$errorMessage = '';
$successMessage = '';

$operatorUserId = $_GET['user_id'] ?? null; 

$operatorDetails = null;
$cities = []; 


$citiesSql = "SELECT id, name FROM cities ORDER BY name ASC";
$citiesResult = $conn->query($citiesSql);
if ($citiesResult) {
    while ($row = $citiesResult->fetch_assoc()) {
        $cities[] = $row;
    }
} else {
    $errorMessage = 'Error fetching cities: ' . $conn->error;
}

if ($operatorUserId) {
    $operatorSql = "
        SELECT
            u.id AS user_id,
            u.full_name,
            u.email,
            op.city_id AS assigned_city_id
        FROM
            users u
        JOIN
            operator op ON u.id = op.user_id
        WHERE
            u.id = ? AND u.role = 'operator';
    ";
    $stmt = $conn->prepare($operatorSql);
    $stmt->bind_param("i", $operatorUserId);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $operatorDetails = $result->fetch_assoc();
    } else {
        $errorMessage = 'Operator not found or invalid ID.';
        $operatorUserId = null; 
    }
    $stmt->close();
} else {
    $errorMessage = 'No operator selected for assignment.';
}


if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['assign_city'])) {
    $postedOperatorUserId = (int)$_POST['operator_user_id'];
    $newCityId = $_POST['city_id'] ?? null;

    if ($newCityId === '') {
        $newCityId = null;
    } else {
        $newCityId = (int)$newCityId;
    }

    if ($postedOperatorUserId <= 0) {
        $errorMessage = 'Invalid operator ID for assignment.';
    } else {
        $conn->begin_transaction();
        try {
            $updateStmt = $conn->prepare("UPDATE operator SET city_id = ? WHERE user_id = ?");
            if ($newCityId === null) {
                $updateStmt->bind_param("ii", $newCityId, $postedOperatorUserId);
            } else {
                $updateStmt->bind_param("ii", $newCityId, $postedOperatorUserId);
            }

            if (!$updateStmt->execute()) {
                throw new Exception('Error assigning city: ' . $updateStmt->error);
            }

            if ($updateStmt->affected_rows > 0) {
                $successMessage = 'Operator city assigned/updated successfully!';
                $operatorDetails['assigned_city_id'] = $newCityId;
            } else {
                $errorMessage = 'No changes made or operator not found.';
            }
            $updateStmt->close();
            $conn->commit();

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
    <title><?php echo htmlspecialchars($pageTitle); ?></title>
    <link rel="stylesheet" href="assign_operators_cities.css">
</head>
<body>
    <div class="main-content"> <h1>Welcome, <?php echo htmlspecialchars($fullName); ?>!</h1>
        <h2><?php echo htmlspecialchars($pageTitle); ?></h2>
        <h3><?php echo htmlspecialchars($pageTitle); ?></h3>

        <?php if ($successMessage): ?>
            <p class="message success"><?php echo $successMessage; ?></p>
        <?php endif; ?>
        <?php if ($errorMessage): ?>
            <p class="message error"><?php echo $errorMessage; ?></p>
        <?php endif; ?>

        <?php if ($operatorDetails): ?>
            <p><strong>Operator:</strong> <?php echo htmlspecialchars($operatorDetails['full_name']); ?> (<?php echo htmlspecialchars($operatorDetails['email']); ?>)</p>
            <p><strong>Current Assigned City:</strong>
                <?php
                $currentCityName = 'N/A';
                if ($operatorDetails['assigned_city_id'] !== null) {
                    foreach ($cities as $city) {
                        if ($city['id'] == $operatorDetails['assigned_city_id']) {
                            $currentCityName = $city['name'];
                            break;
                        }
                    }
                }
                echo htmlspecialchars($currentCityName);
                ?>
            </p>

            <form action="assign_operator_cities.php?user_id=<?php echo htmlspecialchars($operatorUserId); ?>" method="POST">
                <input type="hidden" name="operator_user_id" value="<?php echo htmlspecialchars($operatorUserId); ?>">
                <div>
                    <label for="city_id">Assign New City:</label><br>
                    <select id="city_id" name="city_id">
                        <option value="">-- Unassign City --</option>
                        <?php foreach ($cities as $city): ?>
                            <option value="<?php echo htmlspecialchars($city['id']); ?>"
                                <?php echo (isset($operatorDetails['assigned_city_id']) && $operatorDetails['assigned_city_id'] == $city['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($city['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <br>
                <div>
                    <button type="submit" name="assign_city">Update City Assignment</button>
                </div>
            </form>

        <?php else: ?>
            <p>Please select an operator from the <a href="manage_operators.php">Manage Operators</a> page.</p>
        <?php endif; ?>

        <div class="navigation-links">
            <p><a href="manage_operators.php">Back to Manage Operators</a></p>
            <p><a href="admin_index.php">Back to Admin Dashboard</a></p>
            <p><a href="../public/logout.php">Logout</a></p>
        </div>
    </div> </body>
</html>
