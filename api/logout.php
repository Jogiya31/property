<?php
/**
 * Logout Handler
 * Destroys server-side session and clears client-side storage
 */

session_start();

// Destroy the PHP session
session_unset();
session_destroy();

// Clear session cookies
if (isset($_COOKIE[session_name()])) {
    setcookie(session_name(), '', time() - 3600, '/');
}

// Clear session timestamp cookie if exists
if (isset($_COOKIE['session_timestamp'])) {
    setcookie('session_timestamp', '', time() - 3600, '/');
}

// Return success response for AJAX calls
header('Content-Type: application/json');
echo json_encode([
    'success' => true,
    'message' => 'Logged out successfully'
]);

// Redirect to login page if accessed directly
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    header("Location: ../index.php");
    exit();
}
?>
