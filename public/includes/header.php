<?php

// Ensure session is started if not already (though main files should start it)
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($pageTitle ?? 'Bus Booking System'); ?></title>
    </head>
<body>
    <h2>Welcome, <?php echo htmlspecialchars($fullName ?? 'User'); ?>!</h2>
    <h3><?php echo htmlspecialchars($pageTitle ?? 'Dashboard'); ?></h3>

    <?php if (isset($successMessage) && $successMessage): ?>
        <p class="message success"><?php echo $successMessage; ?></p>
    <?php endif; ?>
    <?php if (isset($errorMessage) && $errorMessage): ?>
        <p class="message error"><?php echo $errorMessage; ?></p>
    <?php endif; ?>
