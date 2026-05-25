<?php

session_start();

include '../connection/db.php';

header("Content-Type: application/json");

$uid = $_SESSION['uid'] ?? null;

$formId = $_POST['form_id'] ?? null;

/* =====================================
   VALIDATION
===================================== */

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

    /* =====================================
       GET FORM
    ===================================== */

    $stmt = $conn->prepare("
        SELECT
            id,
            is_locked,
            locked_by,
            locked_at,
            is_opened,
            opened_at
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
       CHECK LOCK EXPIRY
    ===================================== */

    $lockExpired = false;

    /*
    |--------------------------------------------------------------------------
    | Expire lock after 1 day of inactivity
    |--------------------------------------------------------------------------
    */

    if (

        !empty($form['opened_at'])

        && strtotime($form['opened_at']) < strtotime('-1 day')

    ) {

        $lockExpired = true;
    }

    /* =====================================
       LOCK VALIDATION
    ===================================== */

    /*
    |--------------------------------------------------------------------------
    | Block if:
    | locked by another user
    | AND lock not expired
    |--------------------------------------------------------------------------
    */

    if (

        (bool)$form['is_locked'] === true

        && (int)$form['locked_by'] !== (int)$uid

        && !$lockExpired

    ) {

        throw new Exception(
            "Form already opened by another user"
        );
    }

    /* =====================================
       SAFE ATOMIC LOCK QUERY
    ===================================== */

    /*
    |--------------------------------------------------------------------------
    | IMPORTANT:
    | Atomic update prevents race condition
    |--------------------------------------------------------------------------
    */

    $stmtUpdate = $conn->prepare("
        UPDATE forms
        SET

            is_locked = true,

            locked_by = :uid,

            locked_at = NOW(),

            is_opened = true,

            opened_at = NOW()

        WHERE id = :id

        AND (

            is_locked = false

            OR locked_by = :uid

            OR (

                is_locked = true

                AND opened_at < NOW() - INTERVAL '1 day'
            )
        )
    ");

    $stmtUpdate->execute([
        ':uid' => $uid,
        ':id' => $formId
    ]);

    /* =====================================
       FINAL SAFETY CHECK
    ===================================== */

    /*
    |--------------------------------------------------------------------------
    | rowCount = 0 means:
    | another user locked form
    |--------------------------------------------------------------------------
    */

    if ($stmtUpdate->rowCount() === 0) {

        /*
        |--------------------------------------------------------------------------
        | Get latest lock owner
        |--------------------------------------------------------------------------
        */

        $q = $conn->prepare("
            SELECT
                locked_by
            FROM forms
            WHERE id = :id
            LIMIT 1
        ");

        $q->execute([
            ':id' => $formId
        ]);

        $lockData = $q->fetch(PDO::FETCH_ASSOC);

        throw new Exception(
            "Form already opened by another user"
        );
    }

    /* =====================================
       SUCCESS RESPONSE
    ===================================== */

    echo json_encode([

        "success" => true,

        "message" => "Form locked successfully",

        "data" => [

            "form_id" => $formId,

            "locked_by" => $uid,

            "locked_at" => date('Y-m-d H:i:s')
        ]
    ]);
}

catch (Exception $e) {

    echo json_encode([

        "success" => false,

        "error" => $e->getMessage()
    ]);
}
?>