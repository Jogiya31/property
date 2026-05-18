<?php
session_start();
include '../connection/db.php';

$data = json_decode(file_get_contents("php://input"), true);

$email = strtolower($data['email'] ?? '');
$password = $data['password'];

$stmt = $conn->prepare("SELECT u.*  FROM users u  WHERE u.email = :u");
$stmt->execute([':u' => $email]);
$user = $stmt->fetch();

if ($user && password_verify($password, $user['password_hash'])) {
    $_SESSION['uid'] = $user['uid'];
    $_SESSION['username'] = $user['username'];
    $_SESSION['email'] = $user['email'];
    $_SESSION['designation'] = $user['designation'];
    $_SESSION['state'] = $user['state'];
    $_SESSION['empcode'] = $user['emp_code'];
    $_SESSION['payscale'] = $user['payscale'];
    $_SESSION['address'] = $user['address'];
    $_SESSION['service'] = $user['service'];
    $_SESSION['last_activity'] = time(); // Track session activity for auto-logout

    echo json_encode([
        "success" => true,
        "uid" => $user['uid'],
        "username" => $user['username'],
        "email" => $user['email'],
        "designation" => $user['designation'],
        "state" => $user['state'],
        "empcode" => $user['emp_code'],
        "payscale" => $user['payscale'],
        "address" => $user['address'],
        "service" => $user['service']
    ]);
} else {
    echo json_encode([
        "success" => false,
        "message" => "Invalid login"
    ]);
}
