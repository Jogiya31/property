<?php

session_start();

error_reporting(E_ALL);
ini_set('display_errors', 1);

header("Content-Type: application/json; charset=UTF-8");

require_once '../connection/db.php';

try {

    /* =====================================================
       GET INPUT
    ====================================================== */

    $input = json_decode(file_get_contents("php://input"), true);

    $uid = $_SESSION['uid'] ?? ($input['uid'] ?? null);

    $designation = $_SESSION['designation']
        ?? ($input['designation'] ?? null);

    $req_type = strtolower(trim($input['req_type'] ?? ''));

    if (!$uid) {

        throw new Exception("User not logged in");
    }


    /* =====================================================
    CLEAR EXPIRED LOCKS / OPEN STATES
    ===================================================== */

    $stmtClear = $conn->prepare("

        UPDATE forms

        SET

            is_locked = false,

            locked_by = NULL,

            locked_at = NULL,

            is_opened = false,

            opened_at = NULL

        WHERE

            is_opened = true

            AND opened_at IS NOT NULL

            AND opened_at < NOW() - INTERVAL '1 day'

    ");

    $stmtClear->execute();


    /* =====================================================
       BASE QUERY
    ====================================================== */

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
    ";

    $params = [];


    $sql .= "
            WHERE f.uid = :uid
        ";

    $params[':uid'] = $uid;

    /* =====================================================
       ORDER
    ====================================================== */

    $sql .= " ORDER BY f.id DESC";

    /* =====================================================
       EXECUTE
    ====================================================== */

    $stmt = $conn->prepare($sql);

    $stmt->execute($params);

    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    /* =====================================================
       RESPONSE FORMAT
    ====================================================== */

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
                "action" => $row['movement_action'] ?? null,
                "date" => $row['movement_date'] ?? null
            ],

            "created_at" => $row['created_at'],

            "updated_at" => $row['updated_at']
        ];
    }

    /* =====================================================
       FINAL RESPONSE
    ====================================================== */

    echo json_encode([

        "success" => true,

        "req_type" => $req_type,

        "count" => count($data),

        "data" => $data

    ], JSON_UNESCAPED_UNICODE);
} catch (Exception $e) {

    http_response_code(500);

    echo json_encode([

        "success" => false,

        "error" => $e->getMessage()

    ]);
}
