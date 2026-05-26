<?php

session_start();

error_reporting(E_ALL);
ini_set('display_errors', 1);

header("Content-Type: application/json; charset=UTF-8");

require_once '../connection/db.php';

try {

    /* =====================================================
       SESSION USER
    ====================================================== */

    $uid = $_SESSION['uid'] ?? null;

    if (!$uid) {
        throw new Exception("User not logged in");
    }

    /* =====================================================
       INPUT
    ====================================================== */

    $formId = isset($_POST['id'])
        ? (int)$_POST['id']
        : 0;

    $remarks = trim($_POST['remarks'] ?? '');

    $reason = trim($_POST['reason'] ?? '');

    if (!$formId) {
        throw new Exception("Invalid form id");
    }

    if ($reason === '') {
        throw new Exception("Reason required");
    }

    // if ($remarks === '') {
    //     throw new Exception("Remarks required");
    // }

    $conn->beginTransaction();

    /* =====================================================
       GET FORM
    ====================================================== */

    $stmtForm = $conn->prepare("
        SELECT
            *
        FROM forms
        WHERE id = :id
        LIMIT 1
    ");

    $stmtForm->execute([
        ':id' => $formId
    ]);

    $form = $stmtForm->fetch(PDO::FETCH_ASSOC);

    if (!$form) {
        throw new Exception("Form not found");
    }

    // /* =====================================================
    //    VALIDATION
    // ====================================================== */

    // if ($form['status'] !== 'Forwarded') {

    //     throw new Exception(
    //         "Only forwarded forms can be pulled back"
    //     );
    // }

    /*
    |---------------------------------------------------------
    | File already opened
    |---------------------------------------------------------
    */

    if ((bool)$form['is_opened'] === true) {

        throw new Exception(
            "Form already opened by forwarded user"
        );
    }

    /*
    |---------------------------------------------------------
    | File already locked
    |---------------------------------------------------------
    */

    if ((bool)$form['is_locked'] === true) {

        throw new Exception(
            "Form already locked"
        );
    }

    /* =====================================================
       GET LAST MOVEMENT
    ====================================================== */

    $stmtMovement = $conn->prepare("
        SELECT
            *
        FROM form_movements
        WHERE form_id = :form_id
        ORDER BY id DESC
        LIMIT 1
    ");

    $stmtMovement->execute([
        ':form_id' => $formId
    ]);

    $lastMovement = $stmtMovement->fetch(PDO::FETCH_ASSOC);

    if (!$lastMovement) {
        throw new Exception("Movement history not found");
    }

    /*
    |---------------------------------------------------------
    | Only sender can pull back
    |---------------------------------------------------------
    */

    if ((int)$lastMovement['from_user_id'] !== (int)$uid) {

        throw new Exception(
            "Only sender can pull back this form"
        );
    }

    /*
    |---------------------------------------------------------
    | Already processed further
    |---------------------------------------------------------
    */

    if (
        (int)$form['current_holder']
        !==
        (int)$lastMovement['to_user_id']
    ) {

        throw new Exception(
            "Form already processed further"
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

    $user = $stmtUser->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        throw new Exception("User not found");
    }

    /* =====================================================
       SAVE OLD VALUES
    ====================================================== */

    $oldStatus = $form['status'];

    $oldHolder = $form['current_holder'];

    $oldLocked = $form['is_locked']
        ? 'true'
        : 'false';

    $oldOpened = $form['is_opened']
        ? 'true'
        : 'false';

    /* =====================================================
       UPDATE FORM
    ====================================================== */

    $stmtUpdate = $conn->prepare("
        UPDATE forms
        SET

            status = 'Pull Back',

            remarks = :remarks,

            current_holder = :current_holder,

            current_role_name = :current_role_name,

            forward_to = NULL,

            last_action = 'Pull Back',

            updated_by = :updated_by,

            updated_at = NOW(),

            is_locked = true,

            locked_by = :locked_by,

            locked_at = NOW(),

            is_opened = true,

            opened_at =  NOW()

        WHERE id = :id
    ");

    $stmtUpdate->execute([

        ':remarks' => $remarks,

        ':current_holder' => $uid,

        ':current_role_name' => $user['designation'],

        ':updated_by' => $uid,

        'locked_by' => $uid,

        ':id' => $formId
    ]);


    /* =====================================================
   INSERT MOVEMENT
===================================================== */

    $stmtInsertMovement = $conn->prepare("
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

    /* =========================================
   USER WHO CURRENTLY HAS FILE
========================================= */

    $currentHolderId =
        (int)$lastMovement['to_user_id'];

    $currentHolderRole =
        $lastMovement['to_role'];

    /* =========================================
   PULL BACK TO ORIGINAL SENDER
========================================= */

    $stmtInsertMovement->execute([

        ':form_id' => $formId,

        /* CURRENT HOLDER */
        ':from_user_id' => $currentHolderId,

        ':from_role' => $currentHolderRole,

        /* ORIGINAL SENDER */
        ':to_user_id' => $uid,

        ':to_role' => $user['designation'],

        ':action' => 'Pull Back',

        ':remarks' => $reason . ' - ' . $remarks
    ]);

    /* =====================================================
       HISTORY PREPARE
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

    $commonHistory = [

        ':form_id' => $formId,

        ':action_by' => $uid,

        ':action_by_role' => $user['designation'],

        ':action_to' => $currentHolderId,

        ':action_to_role' => $currentHolderRole,

        ':remarks' => $reason . ' - ' . $remarks,

        ':ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,

        ':user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null
    ];

    /* =====================================================
       STATUS HISTORY
    ====================================================== */

    $stmtHistory->execute(array_merge($commonHistory, [

        ':action_type' => 'Pull Back',

        ':field_name' => 'status',

        ':old_value' => $oldStatus,

        ':new_value' => 'Pull Back'
    ]));

    /* =====================================================
       COMMIT
    ====================================================== */

    $conn->commit();

    echo json_encode([

        "success" => true,

        "message" => "Form pulled back successfully",

        "data" => [

            "form_id" => $formId,

            "status" => "Pull Back",

            "current_holder" => $uid
        ]
    ]);
} catch (Exception $e) {

    if ($conn->inTransaction()) {
        $conn->rollBack();
    }

    echo json_encode([

        "success" => false,

        "error" => $e->getMessage()
    ]);
}
