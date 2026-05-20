<?php

session_start();

include '../connection/db.php';

header("Content-Type: application/json");

$uid = $_SESSION['uid'] ?? null;

$formId = $_POST['form_id'] ?? null;

if (!$uid) {

    echo json_encode([
        "success" => false,
        "error" => "User not logged in"
    ]);

    exit;
}

if (!$formId) {

    echo json_encode([
        "success" => false,
        "error" => "Invalid Form ID"
    ]);

    exit;
}

try {

    $stmt = $conn->prepare("
        SELECT
            id,
            is_locked,
            locked_by,
            current_holder
        FROM forms
        WHERE id = :id
        LIMIT 1
    ");

    $stmt->execute([
        ':id' => $formId
    ]);

    $form = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$form) {

        throw new Exception("Form not found");
    }

    /* =====================================
       ONLY CURRENT HOLDER CAN OPEN
    ===================================== */

    if ((int)$form['current_holder'] !== (int)$uid) {

        throw new Exception("You are not authorized to open this form");
    }

    /* =====================================
       IF LOCKED BY OTHER USER
    ===================================== */

    if (

        $form['is_locked'] == true &&

        (int)$form['locked_by'] !== (int)$uid

    ) {

        throw new Exception("Form already opened by another user");
    }

    /* =====================================
       LOCK FORM
    ===================================== */

    $stmtUpdate = $conn->prepare("
        UPDATE forms SET

            is_locked = true,

            locked_by = :uid,

            locked_at = NOW(),

            is_opened = true,

            opened_at = NOW()

        WHERE id = :id
    ");

    $stmtUpdate->execute([
        ':uid' => $uid,
        ':id' => $formId
    ]);

    echo json_encode([
        "success" => true,
        "message" => "Form locked successfully"
    ]);

} catch (Exception $e) {

    echo json_encode([
        "success" => false,
        "error" => $e->getMessage()
    ]);
}