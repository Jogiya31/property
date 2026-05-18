<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

header("Content-Type: application/json; charset=UTF-8");

include '../connection/db.php';

try {

    // ================= BASE QUERY =================
    $sql = "SELECT uid, username, email, designation, service, emp_code, address, state FROM users";


    // ================= ORDER =================
    $sql .= " ORDER BY username asc";

    $stmt = $conn->prepare($sql);
    $stmt->execute();

    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        "success" => true,
        "count"   => count($data),
        "data"    => $data
    ], JSON_UNESCAPED_UNICODE);
} catch (PDOException $e) {

    http_response_code(500);

    echo json_encode([
        "success" => false,
        "error"   => $e->getMessage()
    ]);
}
