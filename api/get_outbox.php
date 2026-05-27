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
       OUTBOX QUERY

       RULES:

       1. Employee/Form Owner
          -> can see all own forms

       2. Any workflow user
          -> can see forms forwarded by them

       3. Revert allowed only for latest sender
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

            /* =========================================
            FORM OWNER
            ========================================= */

            owner.uid AS form_owner_uid,
            owner.username AS form_owner_name,

            /* =========================================
            CURRENT HOLDER
            ========================================= */

            currentUser.username AS current_holder_name,

            /* =========================================
            FORWARDED USER
            ========================================= */

            forwardUser.username AS forward_username,

            /* =========================================
            LOCK USER
            ========================================= */

            lockUser.username AS locked_by_name,

            /* =========================================
            USER SENT MOVEMENT
            ========================================= */

            sentMovement.id AS sent_movement_id,
            sentMovement.action AS sent_action,
            sentMovement.created_at AS sent_date,

            sentMovement.from_user_id AS sender_id,
            sentMovement.to_user_id AS receiver_id,

            senderUser.username AS sender_name,
            receiverUser.username AS receiver_name,

            /* =========================================
            LATEST MOVEMENT
            ========================================= */

            latestMovement.id AS latest_movement_id,

            latestMovement.from_user_id AS latest_sender_id,

            latestMovement.to_user_id AS latest_receiver_id,

            latestMovement.action AS latest_action

        FROM forms f

        /* =========================================
        LATEST MOVEMENT OF CURRENT USER
        ========================================= */

        LEFT JOIN LATERAL (

            SELECT sm.*

            FROM form_movements sm

            WHERE
                sm.form_id = f.id
                AND sm.from_user_id = :uid

            ORDER BY sm.id DESC

            LIMIT 1

        ) sentMovement ON true

        /* =========================================
        LATEST MOVEMENT OF FORM
        ========================================= */

        LEFT JOIN LATERAL (

            SELECT lm.*

            FROM form_movements lm

            WHERE lm.form_id = f.id

            ORDER BY lm.id DESC

            LIMIT 1

        ) latestMovement ON true

        /* =========================================
        USERS
        ========================================= */

        LEFT JOIN users owner
            ON owner.uid = f.uid::INTEGER

        LEFT JOIN users currentUser
            ON currentUser.uid = f.current_holder

        LEFT JOIN users forwardUser
            ON forwardUser.uid = f.forward_to

        LEFT JOIN users lockUser
            ON lockUser.uid = f.locked_by

        LEFT JOIN users senderUser
            ON senderUser.uid = sentMovement.from_user_id

        LEFT JOIN users receiverUser
            ON receiverUser.uid = sentMovement.to_user_id

        /* =========================================
        OUTBOX CONDITIONS
        ========================================= */

        WHERE (

            /* =========================================
            FORM OWNER
            ========================================= */

            f.uid::INTEGER = :uid

            OR

            /* =========================================
            USER HAS SENT FORM
            ========================================= */

            EXISTS (

                SELECT 1

                FROM form_movements sm

                WHERE
                    sm.form_id = f.id
                    AND sm.from_user_id = :uid
            )
        )

        /* =========================================
        CURRENT HOLDER SHOULD NOT BE LOGGED-IN USER
        ========================================= */

        AND f.current_holder::INTEGER <> :uid

        /* =========================================
        EXCLUDE DRAFT
        ========================================= */

        AND f.status <> 'Draft'

        /* =========================================
        EXCLUDE PULL BACK RETURNED TO USER
        ========================================= */

        AND NOT (

            f.status = 'Pull Back'

            AND f.current_holder::INTEGER = :uid
        )
        
        /* =========================================
        EXCLUDE Complete
        ========================================= */

        AND NOT (

            f.status = 'Completed'

            AND f.uid::INTEGER = :uid
        )

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
        FORM + USER + LATEST MOVEMENT
        ====================================================== */

        $innerstmt = $conn->prepare("
            SELECT

                f.*,

                owner.username AS owner_username,
                owner.email,
                owner.designation,
                owner.service,
                owner.emp_code,
                owner.payscale,
                owner.address,
                owner.state,

                currentHolder.username AS current_holder_name,

                latestMovement.id AS movement_id,
                latestMovement.action AS movement_action,
                latestMovement.created_at AS movement_created_at,

                latestMovement.from_user_id,
                latestMovement.to_user_id,

                sender.username AS sender_username,
                receiver.username AS receiver_username

            FROM forms f

            LEFT JOIN users owner
                ON owner.uid = f.uid::INTEGER

            LEFT JOIN users currentHolder
                ON currentHolder.uid = f.current_holder

            LEFT JOIN LATERAL (

                SELECT
                    fm.*
                FROM form_movements fm
                WHERE fm.form_id = f.id
                ORDER BY fm.id DESC
                LIMIT 1

            ) latestMovement ON true

            LEFT JOIN users sender
                ON sender.uid = latestMovement.from_user_id

            LEFT JOIN users receiver
                ON receiver.uid = latestMovement.to_user_id

            WHERE f.id = :id

            LIMIT 1
        ");

        $innerstmt->execute([
            ':id' => $row['id'],
        ]);

        $formRow = $innerstmt->fetch(PDO::FETCH_ASSOC);

        if (!$formRow) {

            echo json_encode([
                "success" => false,
                "error" => "Form not found"
            ]);
            exit;
        }

        /* =====================================================
        USER ROLE FLAGS
        ====================================================== */

        $loggedInUserId = $_SESSION["uid"] ?? null;

        $isFormOwner =
            (int)$formRow['uid'] === (int)$loggedInUserId;

        $isCurrentHolder =
            (int)$formRow['current_holder'] === (int)$loggedInUserId;

        $isLatestSender =
            (int)$formRow['from_user_id'] === (int)$loggedInUserId;

        $isLatestReceiver =
            (int)$formRow['to_user_id'] === (int)$loggedInUserId;


        /* =====================================================
        CAN PULLBACK
        ===================================================== */

        $canPullBack = false;

        if (
            $formRow['status'] === 'Forwarded'
            && (int)$formRow['from_user_id'] === (int)$loggedInUserId
            && (int)$formRow['current_holder'] === (int)$formRow['to_user_id']
            && !(bool)$formRow['is_locked']
            && !(bool)$formRow['is_opened']
        ) {

            $canPullBack = true;
        }

        /* =====================================================
        CAN TAKE ACTION
        ===================================================== */

        $canTakeAction = false;

        /* =========================================
        USER CONDITIONS
        ========================================= */

        $isCurrentHolder = (
            (int)$formRow['current_holder'] === (int)$loggedInUserId
        );

        $isLatestSender = (
            (int)$formRow['from_user_id'] === (int)$loggedInUserId
        );

        /* =========================================
            LOCK CONDITIONS
            ========================================= */

        $isLocked = (bool)$formRow['is_locked'];

        $isLockedByLoggedInUser = (
            $isLocked
            && (int)$formRow['locked_by'] === (int)$loggedInUserId
        );

        /* =========================================
            TAKE ACTION RULES

            1. User is current holder OR latest sender
            2. File is not locked
                OR
                File is locked by logged in user
            ========================================= */

        if (
            $isCurrentHolder ||
            $isLatestSender
        ) {

            if (
                !$isLocked
                ||
                $isLockedByLoggedInUser
            ) {

                $canTakeAction = true;
            }
        }

        /* =====================================================
           CAN PULLBACK
        ====================================================== */

        $canPullBack = false;

        if (

            $row['status'] === 'Forwarded'

            && (int)$row['latest_sender_id'] === (int)$uid

            && (int)$row['latest_receiver_id'] === (int)$row['current_holder']

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
           STATUS LABEL
        ====================================================== */

        $statusLabel = $row['status'];

        /* =====================================================
           RESPONSE DATA
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

            "remarks" => $row['remarks'],

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
               FORM OWNER
            ========================================= */

            "form_owner" => [

                "uid" => $row['form_owner_uid'],

                "username" => $row['form_owner_name']
            ],

            /* =========================================
               SENDER
            ========================================= */

            "sender" => [

                "uid" => $row['sender_id'],

                "username" => $row['sender_name']
            ],

            /* =========================================
               RECEIVER
            ========================================= */

            "receiver" => [

                "uid" => $row['receiver_id'],

                "username" => $row['receiver_name']
            ],

            /* =============================================
            LATEST MOVEMENT
            ============================================= */

            "latest_movement" => [

                "id" => $formRow["movement_id"],

                "action" => $formRow["movement_action"],

                "created_at" => $formRow["movement_created_at"],

                "from_user" => [

                    "uid" => $formRow["from_user_id"],

                    "username" => $formRow["sender_username"]
                ],

                "to_user" => [

                    "uid" => $formRow["to_user_id"],

                    "username" => $formRow["receiver_username"]
                ]
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
               MOVEMENT
            ========================================= */

            "movement" => [

                "id" => $row['sent_movement_id'],

                "action" => $row['sent_action'],

                "date" => $row['sent_date']
            ],

            /* =========================================
               LOCK
            ========================================= */

            "lock" => [

                "is_locked" => (bool)$row['is_locked'],

                "locked_by" => $row['locked_by'],

                "locked_by_name" => $row['locked_by_name'],

                "locked_at" => $row['locked_at'],

                "is_locked_by_other" => $isLockedByOther
            ],

            /* =========================================
               OPEN STATE
            ========================================= */

            "open_state" => [

                "is_opened" => (bool)$row['is_opened'],

                "opened_at" => $row['opened_at']
            ],

            /* =========================================
               PERMISSIONS
            ========================================= */

            "permissions" => [

                "is_form_owner" => $isFormOwner,

                "is_current_holder" => $isCurrentHolder,

                "is_latest_sender" => $isLatestSender,

                "is_latest_receiver" => $isLatestReceiver,

                "can_pullback" => $canPullBack,

                "can_take_action" => $canTakeAction
            ],

            /* =========================================
               TIMESTAMPS
            ========================================= */

            "timestamps" => [

                "created_at" => date("d-m-Y h:i A", strtotime($row['created_at'])),

                "updated_at" => date("d-m-Y h:i A", strtotime($row['updated_at']))
            ]
        ];
    }

    /* =====================================================
       RESPONSE
    ====================================================== */

    echo json_encode([

        "success" => true,

        "count" => count($data),

        "data" => $data

    ], JSON_UNESCAPED_UNICODE);
} catch (Exception $e) {

    echo json_encode([

        "success" => false,

        "message" => $e->getMessage()

    ], JSON_UNESCAPED_UNICODE);
}
