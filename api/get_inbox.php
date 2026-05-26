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

    $loggedInUserId = $_SESSION['uid'] ?? null;

    if (!$loggedInUserId) {

        throw new Exception("User not logged in");
    }

    /* =====================================================
       INBOX
       ONLY CURRENT HOLDER CAN SEE
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
            f.last_action,
            f.remarks,

            f.created_at,
            f.updated_at,

            f.current_holder,
            f.current_role_name,

            f.forward_to,

            f.is_locked,
            f.locked_by,
            f.locked_at,

            f.is_opened,
            f.opened_at,

            /* =========================================
               OWNER
            ========================================= */

            owner.uid AS owner_uid,
            owner.username AS owner_username,
            owner.designation AS owner_designation,

            /* =========================================
               CURRENT HOLDER
            ========================================= */

            currentUser.uid AS current_holder_uid,
            currentUser.username AS current_holder_username,

            /* =========================================
               FORWARD USER
            ========================================= */

            forwardUser.uid AS forward_uid,
            forwardUser.username AS forward_username,

            /* =========================================
               LATEST MOVEMENT
            ========================================= */

            latestMovement.id AS movement_id,
            latestMovement.action AS movement_action,
            latestMovement.created_at AS movement_date,

            latestMovement.from_user_id,
            latestMovement.to_user_id,

            /* =========================================
               FROM USER
            ========================================= */

            fromUser.username AS from_username,
            fromUser.designation AS from_designation,

            /* =========================================
               TO USER
            ========================================= */

            toUser.username AS to_username,
            toUser.designation AS to_designation,

            /* =========================================
               LOCK USER
            ========================================= */

            lockUser.username AS locked_by_name

        FROM forms f

        /* =========================================
           OWNER
        ========================================= */

        LEFT JOIN users owner
            ON owner.uid = f.uid::INTEGER

        /* =========================================
           CURRENT HOLDER
        ========================================= */

        LEFT JOIN users currentUser
            ON currentUser.uid = f.current_holder

        /* =========================================
           FORWARD USER
        ========================================= */

        LEFT JOIN users forwardUser
            ON forwardUser.uid = f.forward_to

        /* =========================================
           LOCK USER
        ========================================= */

        LEFT JOIN users lockUser
            ON lockUser.uid = f.locked_by

        /* =========================================
           LATEST MOVEMENT
        ========================================= */

        LEFT JOIN LATERAL (

            SELECT
                fm.*

            FROM form_movements fm

            WHERE fm.form_id = f.id

            ORDER BY fm.id DESC

            LIMIT 1

        ) latestMovement ON true

        /* =========================================
           FROM USER
        ========================================= */

        LEFT JOIN users fromUser
            ON fromUser.uid = latestMovement.from_user_id

        /* =========================================
           TO USER
        ========================================= */

        LEFT JOIN users toUser
            ON toUser.uid = latestMovement.to_user_id

        /* =========================================
           INBOX FILTER
        ========================================= */

        WHERE
            f.current_holder::INTEGER = :uid
            AND (
                f.status = 'Pending'
                OR f.status = 'Pull Back'
            )

        ORDER BY f.id DESC
    ";

    $stmt = $conn->prepare($sql);

    $stmt->execute([
        ':uid' => $loggedInUserId
    ]);

    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $data = [];

    foreach ($rows as $row) {

        /* =====================================================
           PERMISSIONS
        ====================================================== */

        $isCurrentHolder = false;

        if (
            (int)$row['current_holder'] === (int)$loggedInUserId
        ) {

            $isCurrentHolder = true;
        }

        $canTakeAction = false;

        if (
            $isCurrentHolder &&
            !(bool)$row['is_locked']
        ) {

            $canTakeAction = true;
        }

        $isLockedByOther = false;

        if (
            (bool)$row['is_locked']
            && (int)$row['locked_by'] !== (int)$loggedInUserId
        ) {

            $isLockedByOther = true;
        }

        /* =====================================================
           STATUS LABEL
        ====================================================== */

        $statusLabel = $row['status'];

        /* =====================================================
           RESPONSE
        ====================================================== */

        $data[] = [

            "id" => (int)$row['id'],

            "reference_no" => $row['reference_no'],

            "form_type" => $row['form_type'],

            "purpose" => $row['purpose'],

            "acquired_disposed" => $row['acquired_disposed'],

            "date_acquisition_disposed" =>
            $row['date_acquisition_disposed'],

            "mode_acquisition" =>
            $row['mode_acquisition'],

            "mode_disposal" =>
            $row['mode_disposal'],

            /* =========================================
               WORKFLOW
            ========================================= */

            "workflow" => [

                "status" => $row['status'],

                "status_label" => $statusLabel,

                "current_phase" => $row['current_phase'],

                "last_action" => $row['last_action']
            ],

            /* =========================================
               OWNER
            ========================================= */

            "form_owner" => [

                "uid" => $row['owner_uid'],

                "username" => $row['owner_username'],

                "designation" => $row['owner_designation']
            ],

            /* =========================================
               CURRENT HOLDER
            ========================================= */

            "current_holder" => [

                "uid" => $row['current_holder_uid'],

                "username" =>
                $row['current_holder_username'],

                "role" =>
                $row['current_role_name']
            ],

            /* =========================================
               FORWARD TO
            ========================================= */

            "forward_to" => [

                "uid" => $row['forward_uid'],

                "username" => $row['forward_username']
            ],

            /* =========================================
               LATEST MOVEMENT
            ========================================= */

            "latest_movement" => [

                "id" => $row['movement_id'],

                "action" => $row['movement_action'],

                "date" => $row['movement_date'],

                "from_user" => [

                    "uid" => $row['from_user_id'],

                    "username" => $row['from_username'],

                    "designation" =>
                    $row['from_designation']
                ],

                "to_user" => [

                    "uid" => $row['to_user_id'],

                    "username" => $row['to_username'],

                    "designation" =>
                    $row['to_designation']
                ]
            ],

            /* =========================================
               LOCK
            ========================================= */

            "lock" => [

                "is_locked" =>
                (bool)$row['is_locked'],

                "locked_by" =>
                $row['locked_by'],

                "locked_by_name" =>
                $row['locked_by_name'],

                "locked_at" =>
                $row['locked_at'],

                "is_locked_by_other" =>
                $isLockedByOther
            ],

            /* =========================================
               OPEN STATE
            ========================================= */

            "open_state" => [

                "is_opened" =>
                (bool)$row['is_opened'],

                "opened_at" =>
                $row['opened_at']
            ],

            /* =========================================
               PERMISSIONS
            ========================================= */

            "permissions" => [

                "is_current_holder" =>
                $isCurrentHolder,

                "can_take_action" =>
                $canTakeAction
            ],

            /* =========================================
               REMARKS
            ========================================= */

            "remarks" => $row['remarks'],

            /* =========================================
               TIMESTAMPS
            ========================================= */

            "timestamps" => [

                "created_at" =>
                $row['created_at'],

                "updated_at" =>
                $row['updated_at']
            ]
        ];
    }

    /* =====================================================
       RESPONSE
    ====================================================== */

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
