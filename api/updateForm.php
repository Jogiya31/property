<?php

session_start();
include '../connection/db.php';

header("Content-Type: application/json");

/* ================= INPUT ================= */

$uid         = $_POST['uid'] ?? ($_SESSION['uid'] ?? null);
$id          = $_POST['id'] ?? null;

$action      = $_POST['action'] ?? null; // Forwarded | reverted

$remarks     = trim($_POST['remarks'] ?? '');

$correctOM   = isset($_POST['correctOM'])
    ? (int)$_POST['correctOM']
    : 0;

$forward_to_id  = !empty($_POST['employee'])
    ? (int)$_POST['employee']
    : null;

/* ================= VALIDATION ================= */

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
            current_role_name
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
       GET CURRENT USER ROLE
    ====================================================== */

    $stmtUser = $conn->prepare("
        SELECT username
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

    /* =====================================================
       DEFAULT VALUES
    ====================================================== */

    $newStatus      = $form['status'];
    $newPhase       = $form['current_phase'];

    $currentHolderId  = $form['current_holder'];
    $currentRoleName    = $form['current_role_name'];

    $lastAction     = null;

    $nextUserName       = null;

    /* =====================================================
       FORWARD USER ROLE
    ====================================================== */

    if ($action === "Forwarded") {

        if (!$forward_to_id) {
            throw new Exception("Please select employee to forward");
        }

        $stmtNext = $conn->prepare("
            SELECT username
            FROM users
            WHERE uid = :id
            LIMIT 1
        ");

        $stmtNext->execute([
            ':id' => $forward_to_id
        ]);

        $nextUser = $stmtNext->fetch(PDO::FETCH_ASSOC);

        if (!$nextUser) {
            throw new Exception("Forward user not found");
        }

        $nextUserName = $nextUser['username'];
    }

    /* =====================================================
       ACTION LOGIC
    ====================================================== */

    if ($action === "Forwarded") {

        $newStatus = "Forwarded";

        $currentHolderId = $forward_to_id;
        $currentRoleName   = $nextUserName;

        $lastAction = "Forwarded";
    }

    elseif ($action === "Rejected") {

        // On rejection, return the form to the original form owner (forms.uid)
        $currentHolderId = $form['uid'];

        $userStmt = $conn->prepare("
            SELECT username
            FROM users
            WHERE uid = :uid
            LIMIT 1
        ");
        $userStmt->execute([
            ':uid' => $currentHolderId
        ]);
        $userData = $userStmt->fetch(PDO::FETCH_ASSOC);

        $currentRoleName = $userData['username'] ?? null;

        $newStatus = "Rejected";

        $lastAction = "Rejected";
    }

    else {

        throw new Exception("Invalid action");
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

            updated_by = :updated_by,

            updated_at = NOW()

        WHERE id = :id
    ");

    $stmtUpdate->bindValue(':status', $newStatus);

    $stmtUpdate->bindValue(':current_phase', $newPhase);

    $stmtUpdate->bindValue(':remarks', $remarks);

    $stmtUpdate->bindValue(':forward_to', $forward_to_id);

    $stmtUpdate->bindValue(':current_holder', $currentHolderId);

    $stmtUpdate->bindValue(':current_role_name', $currentRoleName);

    $stmtUpdate->bindValue(':last_action', $lastAction);

    $stmtUpdate->bindValue(':correctOM', $correctOM, PDO::PARAM_INT);

    $stmtUpdate->bindValue(':updated_by', $uid, PDO::PARAM_INT);

    $stmtUpdate->bindValue(':id', $id, PDO::PARAM_INT);

    $stmtUpdate->execute();

    /* =====================================================
       INSERT MOVEMENT HISTORY
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

        ':from_user_id' => $uid,
        ':from_role' => $currentUserName,

        ':to_user_id' => $currentHolderId,
        ':to_role' => $currentRoleName,

        ':action' => $lastAction,

        ':remarks' => $remarks
    ]);

    /* =====================================================
       COMMIT
    ====================================================== */

    $conn->commit();

    echo json_encode([

        "success" => true,

        "message" => "Form " . strtolower($lastAction) . " successfully",

        "data" => [

            "form_id" => $id,

            "status" => $newStatus,

            "current_holder" => $currentHolderId,

            "current_role_name" => $currentRoleName,

            "forward_to" => $currentHolderId,

            "action" => $lastAction
        ]
    ]);

} catch (Exception $e) {

    $conn->rollBack();

    echo json_encode([

        "success" => false,

        "error" => $e->getMessage()
    ]);
}