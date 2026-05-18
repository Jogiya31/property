<?php
session_start();

// Define timeout in seconds (e.g., 30 minutes)
$timeout = 1800;

// Check if session exists
if (!isset($_SESSION['uid'])) {
    header("Location: ../index.php");
    exit();
}

// Check session timeout (auto-logout)
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > $timeout)) {
    // Session has expired
    session_destroy();
    header("Location: ../index.php?timeout=1");
    exit();
}

// Update last activity timestamp
$_SESSION['last_activity'] = time();
?>