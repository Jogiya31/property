<?php
/**
 * Session Verification Endpoint
 * Check if user's session is still active on the server
 */

session_start();

header('Content-Type: application/json');

// Check if session is valid
if (isset($_SESSION['uid'])) {
    // Update last activity
    $_SESSION['last_activity'] = time();
    
    echo json_encode([
        'success' => true,
        'message' => 'Session is valid',
        'uid' => $_SESSION['uid'],
        'username' => $_SESSION['username']
    ]);
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Session is invalid or expired'
    ]);
}
?>
