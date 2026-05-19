<?php

session_start();

error_reporting(E_ALL);
ini_set('display_errors', 1);

header("Content-Type: application/json; charset=UTF-8");

require_once '../connection/db.php';

try {
    $uid = $_SESSION['uid'] ?? null;

    if (!$uid) {
        throw new Exception("User not logged in");
    }

    $sql = "
        SELECT
            f.id,
            f.reference_no,
            f.form_type,
            f.purpose,
            f.acquired_disposed,
            f.date_acquisition_disposed,
            f.mode_acquisition,
            f.mode_disposal,
            f.status,
            f.current_phase,
            f.current_holder,
            f.current_role_name,
            f.forward_to,
            f.last_action,
            f.remarks,
            f.created_at,
            f.updated_at,
            owner.username AS form_username,
            currentUser.username AS current_holder_name,
            forwardUser.username AS forward_username
        FROM forms f
        LEFT JOIN users owner
            ON owner.uid = f.uid::INTEGER
        LEFT JOIN users currentUser
            ON currentUser.uid = f.current_holder
        LEFT JOIN users forwardUser
            ON forwardUser.uid = f.forward_to
        WHERE f.current_holder = :uid
            AND f.status != 'Draft'
        ORDER BY f.id DESC
    ";

    $stmt = $conn->prepare($sql);
    $stmt->execute([':uid' => $uid]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $data = [];

    foreach ($rows as $row) {
        $data[] = [
            "id" => (int)$row['id'],
            "reference_no" => $row['reference_no'],
            "form_type" => $row['form_type'],
            "purpose" => $row['purpose'],
            "acquired_disposed" => $row['acquired_disposed'],
            "date_acquisition_disposed" => $row['date_acquisition_disposed'],
            "mode_acquisition" => $row['mode_acquisition'],
            "mode_disposal" => $row['mode_disposal'],
            "status" => $row['status'],
            "current_phase" => $row['current_phase'],
            "current_holder" => [
                "uid" => $row['current_holder'],
                "username" => $row['current_holder_name'],
                "role" => $row['current_role_name']
            ],
            "last_action" => $row['last_action'],
            "remarks" => $row['remarks'],
            "user" => [
                "uid" => $uid,
                "username" => $row['form_username']
            ],
            "forward_to" => [
                "uid" => $row['forward_to'] ?? null,
                "username" => $row['forward_username'] ?? null
            ],
            "movement" => [
                "action" => null,
                "date" => null
            ],
            "created_at" => $row['created_at'],
            "updated_at" => $row['updated_at']
        ];
    }

    echo json_encode([
        "success" => true,
        "req_type" => "inbox",
        "count" => count($data),
        "data" => $data
    ], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    echo json_encode([
        "success" => false,
        "message" => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
