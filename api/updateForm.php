<?php

session_start();

include '../connection/db.php';

header("Content-Type: application/json");

/* =====================================================
   INPUT
===================================================== */

$uid = $_POST['uid'] ?? ($_SESSION['uid'] ?? null);

$id = $_POST['id'] ?? null;

$action = trim($_POST['action'] ?? '');

$remarks = trim($_POST['remarks'] ?? '');

$correctOM = isset($_POST['correctOM'])
    ? (int)$_POST['correctOM']
    : 0;

$forward_to_id = !empty($_POST['employee'])
    ? (int)$_POST['employee']
    : null;

/* =====================================================
   VALIDATION
===================================================== */

if (!$uid) {

    echo json_encode([
        "success" => false,
        "error" => "User not logged in"
    ]);

    exit;
}

if (!$id) {

    echo json_encode([
        "success" => false,
        "error" => "Invalid Form ID"
    ]);

    exit;
}

if (!$action) {

    echo json_encode([
        "success" => false,
        "error" => "Action is required"
    ]);

    exit;
}

/* =====================================================
   ALLOWED ACTIONS
===================================================== */

$allowedActions = [
    'Forwarded',
    'Rejected'
];

if (!in_array($action, $allowedActions)) {

    echo json_encode([
        "success" => false,
        "error" => "Invalid action"
    ]);

    exit;
}

$conn->beginTransaction();

try {

    /* =====================================================
       GET CURRENT FORM
    ====================================================== */

    $stmtForm = $conn->prepare("
        SELECT
            id,
            uid,
            status,
            current_phase,
            current_holder,
            current_role_name,
            is_locked,
            locked_by,
            locked_at,
            is_opened,
            opened_at
        FROM forms
        WHERE id = :id
        LIMIT 1
    ");

    $stmtForm->execute([
        ':id' => $id
    ]);

    $form = $stmtForm->fetch(PDO::FETCH_ASSOC);

    if (!$form) {
        throw new Exception("Form not found");
    }

    /* =====================================================
       PREVIOUS WORKFLOW STATE
    ====================================================== */

    $previousHolderId = $form['current_holder'];

    $previousRoleName = $form['current_role_name'];

    $previousStatus = $form['status'];

    /* =====================================================
       AUTHORIZATION
    ====================================================== */

    if ((int)$previousHolderId !== (int)$uid) {

        throw new Exception(
            "You are not authorized to process this form"
        );
    }

    /* =====================================================
       GET CURRENT USER
    ====================================================== */

    $stmtUser = $conn->prepare("
        SELECT
            uid,
            username,
            designation
        FROM users
        WHERE uid = :uid
        LIMIT 1
    ");

    $stmtUser->execute([
        ':uid' => $uid
    ]);

    $userData = $stmtUser->fetch(PDO::FETCH_ASSOC);

    if (!$userData) {
        throw new Exception("User not found");
    }

    $currentUserName = $userData['username'];

    $currentUserRole = $userData['designation'];

    /* =====================================================
       DEFAULT VALUES
    ====================================================== */

    $newStatus = $form['status'];

    $newPhase = $form['current_phase'];

    $newHolderId = $form['current_holder'];

    $newHolderRole = $form['current_role_name'];

    $lastAction = null;

    $nextUserName = null;

    $nextUserRole = null;

    $isLocked = false;

    $lockedBy = null;

    $lockedAt = null;

    $isOpened = false;

    $openedAt = null;

    /* =====================================================
       FORWARD VALIDATION
    ====================================================== */

    if ($action === "Forwarded") {

        if (!$forward_to_id) {

            throw new Exception(
                "Please select employee to forward"
            );
        }

        if ($forward_to_id == $uid) {

            throw new Exception(
                "You cannot forward form to yourself"
            );
        }

        $stmtNext = $conn->prepare("
            SELECT
                uid,
                username,
                designation
            FROM users
            WHERE uid = :id
            LIMIT 1
        ");

        $stmtNext->execute([
            ':id' => $forward_to_id
        ]);

        $nextUser = $stmtNext->fetch(PDO::FETCH_ASSOC);

        if (!$nextUser) {

            throw new Exception(
                "Forward user not found"
            );
        }

        $nextUserName = $nextUser['username'];

        $nextUserRole = $nextUser['designation'];
    }

    /* =====================================================
       ACTION : FORWARDED
    ====================================================== */

    if ($action === "Forwarded") {

        $newStatus = "Forwarded";

        $newHolderId = $forward_to_id;

        $newHolderRole = $nextUserRole;

        $lastAction = "Forwarded";

        $isLocked = false;

        $lockedBy = null;

        $lockedAt = null;

        $isOpened = false;

        $openedAt = null;
    }

    /* =====================================================
       ACTION : REJECTED
    ====================================================== */

    elseif ($action === "Rejected") {

        $newHolderId = $form['uid'];

        $forward_to_id = $form['uid'];

        $stmtOwner = $conn->prepare("
            SELECT
                uid,
                username,
                designation
            FROM users
            WHERE uid = :uid
            LIMIT 1
        ");

        $stmtOwner->execute([
            ':uid' => $newHolderId
        ]);

        $ownerData = $stmtOwner->fetch(PDO::FETCH_ASSOC);

        if (!$ownerData) {

            throw new Exception(
                "Original owner not found"
            );
        }

        $newHolderRole = $ownerData['designation'];

        $newStatus = "Rejected";

        $lastAction = "Rejected";

        $isLocked = false;

        $lockedBy = null;

        $lockedAt = null;

        $isOpened = false;

        $openedAt = null;
    }

    /* =====================================================
       UPDATE FORM
    ====================================================== */

    $stmtUpdate = $conn->prepare("
        UPDATE forms SET

            status = :status,

            current_phase = :current_phase,

            remarks = :remarks,

            forward_to = :forward_to,

            current_holder = :current_holder,

            current_role_name = :current_role_name,

            last_action = :last_action,

            correctom = :correctOM,

            is_locked = :is_locked,

            locked_by = :locked_by,

            locked_at = :locked_at,

            is_opened = :is_opened,

            opened_at = :opened_at,

            updated_by = :updated_by,

            updated_at = NOW()

        WHERE id = :id
    ");

    $stmtUpdate->bindValue(':status', $newStatus);

    $stmtUpdate->bindValue(':current_phase', $newPhase);

    $stmtUpdate->bindValue(':remarks', $remarks);

    $stmtUpdate->bindValue(':forward_to', $forward_to_id);

    $stmtUpdate->bindValue(':current_holder', $newHolderId);

    $stmtUpdate->bindValue(':current_role_name', $newHolderRole);

    $stmtUpdate->bindValue(':last_action', $lastAction);

    $stmtUpdate->bindValue(
        ':correctOM',
        $correctOM,
        PDO::PARAM_INT
    );

    $stmtUpdate->bindValue(
        ':is_locked',
        $isLocked,
        PDO::PARAM_BOOL
    );

    $stmtUpdate->bindValue(':locked_by', $lockedBy);

    $stmtUpdate->bindValue(':locked_at', $lockedAt);

    $stmtUpdate->bindValue(
        ':is_opened',
        $isOpened,
        PDO::PARAM_BOOL
    );

    $stmtUpdate->bindValue(':opened_at', $openedAt);

    $stmtUpdate->bindValue(
        ':updated_by',
        $uid,
        PDO::PARAM_INT
    );

    $stmtUpdate->bindValue(
        ':id',
        $id,
        PDO::PARAM_INT
    );

    $stmtUpdate->execute();

    /* =====================================================
       INSERT MOVEMENT
    ====================================================== */

    $stmtMovement = $conn->prepare("
        INSERT INTO form_movements (

            form_id,

            from_user_id,
            from_role,

            to_user_id,
            to_role,

            action,

            remarks

        )
        VALUES (

            :form_id,

            :from_user_id,
            :from_role,

            :to_user_id,
            :to_role,

            :action,

            :remarks
        )
    ");

    $stmtMovement->execute([

        ':form_id' => $id,

        ':from_user_id' => $previousHolderId,

        ':from_role' => $previousRoleName,

        ':to_user_id' => $newHolderId,

        ':to_role' => $newHolderRole,

        ':action' => $lastAction,

        ':remarks' => $remarks
    ]);

    /* =====================================================
       INSERT HISTORY
    ====================================================== */

    $stmtHistory = $conn->prepare("
        INSERT INTO form_history (

            form_id,

            action_type,

            action_by,
            action_by_role,

            action_to,
            action_to_role,

            field_name,

            old_value,
            new_value,

            remarks,

            ip_address,
            user_agent

        )
        VALUES (

            :form_id,

            :action_type,

            :action_by,
            :action_by_role,

            :action_to,
            :action_to_role,

            :field_name,

            :old_value,
            :new_value,

            :remarks,

            :ip_address,
            :user_agent
        )
    ");

    $stmtHistory->execute([

        ':form_id' => $id,

        ':action_type' => $lastAction,

        ':action_by' => $uid,

        ':action_by_role' => $currentUserRole,

        ':action_to' => $newHolderId,

        ':action_to_role' => $newHolderRole,

        ':field_name' => 'Status',

        ':old_value' => $previousStatus,

        ':new_value' => $newStatus,

        ':remarks' => $remarks,

        ':ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,

        ':user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null
    ]);

    /* =====================================================
       COMMIT
    ====================================================== */

    $conn->commit();

    echo json_encode([

        "success" => true,

        "message" => "Form " .
            strtolower($lastAction) .
            " successfully",

        "data" => [

            "form_id" => $id,

            "workflow" => [

                "status" => $newStatus,

                "action" => $lastAction,

                "phase" => $newPhase
            ],

            "sender" => [

                "uid" => $previousHolderId,

                "role" => $previousRoleName
            ],

            "receiver" => [

                "uid" => $newHolderId,

                "role" => $newHolderRole
            ]
        ]
    ]);
}

catch (Exception $e) {

    if ($conn->inTransaction()) {

        $conn->rollBack();
    }

    echo json_encode([

        "success" => false,

        "error" => $e->getMessage()
    ]);
}
?>