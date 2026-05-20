<?php

session_start();

error_reporting(E_ALL);
ini_set('display_errors', 1);

header("Content-Type: application/json; charset=UTF-8");

require_once '../connection/db.php';

try {

    /* =====================================================
       SESSION
    ====================================================== */

    $uid = $_SESSION['uid'] ?? null;

    if (!$uid) {
        throw new Exception("User not logged in");
    }

    /* =====================================================
       GET OUTBOX DATA

       IMPORTANT:
       We fetch ONLY latest movement of each form
       sent by logged-in user
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

            f.forward_to,

            f.last_action,

            f.remarks,

            f.created_at,
            f.updated_at,

            f.is_locked,
            f.locked_by,
            f.locked_at,

            f.is_opened,
            f.opened_at,

            owner.username AS form_username,

            currentUser.username AS current_holder_name,

            forwardUser.username AS forward_username,

            lockUser.username AS locked_by_name,

            fm.id AS movement_id,
            fm.action AS movement_action,
            fm.created_at AS movement_date,
            fm.to_user_id,
            fm.from_user_id

        FROM forms f

        INNER JOIN (

            SELECT DISTINCT ON (form_id)

                id,
                form_id,
                from_user_id,
                to_user_id,
                action,
                created_at

            FROM form_movements

            WHERE from_user_id = :uid

            ORDER BY form_id, id DESC

        ) fm
            ON fm.form_id = f.id

        LEFT JOIN users owner
            ON owner.uid = f.uid::INTEGER

        LEFT JOIN users currentUser
            ON currentUser.uid = f.current_holder

        LEFT JOIN users forwardUser
            ON forwardUser.uid = f.forward_to

        LEFT JOIN users lockUser
            ON lockUser.uid = f.locked_by

        ORDER BY f.id DESC
    ";

    $stmt = $conn->prepare($sql);

    $stmt->execute([
        ':uid' => $uid
    ]);

    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $data = [];

    foreach ($rows as $row) {

        /* =====================================================
           CAN PULLBACK LOGIC

           Allow pullback ONLY IF:

           1. Current status = Forwarded
           2. Current holder = forwarded user
           3. File not opened
           4. File not locked
        ====================================================== */

        $canPullBack = false;

        if (
            $row['status'] === 'Forwarded'
            && (int)$row['from_user_id'] === (int)$uid
            && (int)$row['current_holder'] === (int)$row['forward_to']
            && !(bool)$row['is_opened']
            && !(bool)$row['is_locked']
        ) {
            $canPullBack = true;
        }

        /* =====================================================
           LOCK STATUS
        ====================================================== */

        $isLockedByOther = false;

        if (
            (bool)$row['is_locked']
            && (int)$row['locked_by'] !== (int)$uid
        ) {
            $isLockedByOther = true;
        }

        /* =====================================================
           FILE STATUS LABEL
        ====================================================== */

        $statusLabel = $row['status'];

        if ((bool)$row['is_locked']) {

            $statusLabel .= ' (Locked)';
        }

        /* =====================================================
           DATA
        ====================================================== */

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

            "status_label" => $statusLabel,

            "current_phase" => $row['current_phase'],

            "last_action" => $row['last_action'],

            "remarks" => $row['remarks'],

            /* =========================================
               FORM OWNER
            ========================================= */

            "user" => [

                "uid" => $uid,

                "username" => $row['form_username']
            ],

            /* =========================================
               CURRENT HOLDER
            ========================================= */

            "current_holder" => [

                "uid" => $row['current_holder'],

                "username" => $row['current_holder_name'],

                "role" => $row['current_role_name']
            ],

            /* =========================================
               FORWARDED TO
            ========================================= */

            "forward_to" => [

                "uid" => $row['forward_to'],

                "username" => $row['forward_username']
            ],

            /* =========================================
               MOVEMENT
            ========================================= */

            "movement" => [

                "id" => $row['movement_id'],

                "action" => $row['movement_action'],

                "date" => $row['movement_date']
            ],

            /* =========================================
               LOCKING
            ========================================= */

            "is_locked" => (bool)$row['is_locked'],

            "locked_by" => $row['locked_by'],

            "locked_by_name" => $row['locked_by_name'],

            "locked_at" => $row['locked_at'],

            "is_locked_by_other" => $isLockedByOther,

            /* =========================================
               OPEN STATUS
            ========================================= */

            "is_opened" => (bool)$row['is_opened'],

            "opened_at" => $row['opened_at'],

            /* =========================================
               PULLBACK
            ========================================= */

            "can_pullback" => $canPullBack,

            /* =========================================
               DATE
            ========================================= */

            "created_at" => $row['created_at'],

            "updated_at" => $row['updated_at']
        ];
    }

    /* =====================================================
       RESPONSE
    ====================================================== */

    echo json_encode([

        "success" => true,

        "req_type" => "outbox",

        "count" => count($data),

        "data" => $data

    ], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {

    echo json_encode([

        "success" => false,

        "message" => $e->getMessage()

    ], JSON_UNESCAPED_UNICODE);
}