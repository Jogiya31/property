<?php

session_start();
include '../connection/db.php';

header("Content-Type: application/json");

/* ================= HELPERS ================= */

function parseNumber($value, $default = 0)
{
    return is_numeric($value) ? $value : $default;
}

function emptyToNull($value)
{
    if ($value === null) {
        return null;
    }

    return trim((string)$value) === '' ? null : $value;
}

function isValidFile($fileKey)
{
    return isset($_FILES[$fileKey]) &&
        $_FILES[$fileKey]['error'] === UPLOAD_ERR_OK &&
        !empty($_FILES[$fileKey]['tmp_name']) &&
        is_uploaded_file($_FILES[$fileKey]['tmp_name']);
}

/* ================= INPUT ================= */

$uid = $_POST['uid'] ?? ($_SESSION['uid'] ?? null);

$editFormId = $_POST['form_id'] ?? null;

$form_type = $_POST['form_type'] ?? null;

$form_status = isset($_POST['form_status'])
    ? (int)$_POST['form_status']
    : 0;

$status = $form_status === 0
    ? 'Draft'
    : 'Pending';

$currentRole = null;
$currentHolder = null;
$currentHolderName = null;
$lastAction = null;
$remarks = $_POST['remarks'] ?? null;

/*
|--------------------------------------------------------------------------
| Example Workflow
|--------------------------------------------------------------------------
| EMP -> SO
| SO -> DH
| DH -> SO
|--------------------------------------------------------------------------
*/

if ($form_status === 1) {

    $currentRole = 'SO';
    $currentHolder = 3;
    $currentHolderName = 'SO';

    $lastAction = 'Forwarded';
}

$propertyDetails = $_POST['propertyDetails'] ?? null;

$purpose = $_POST['purpose'] ?? null;

$acquired_disposed = $_POST['acquired_disposed'] ?? null;

$date_acquisition_disposed =
    emptyToNull($_POST['date_acquisition_disposed'] ?? null);

$mode_acquisition =
    $_POST['mode_acquisition'] ?? null;

$mode_acquisition_other =
    $_POST['mode_acquisition_other'] ?? null;

$mode_disposal =
    $_POST['mode_disposal'] ?? null;

$mode_disposal_other =
    $_POST['mode_disposal_other'] ?? null;

$acquisition_gift =
    $_POST['acquisition_gift'] ?? null;

$other_relevant =
    $_POST['other_relevant'] ?? null;

/* ================= VALIDATION ================= */

if (!$uid) {

    echo json_encode([
        "success" => false,
        "error" => "User not logged in"
    ]);

    exit;
}

if (!$propertyDetails) {

    echo json_encode([
        "success" => false,
        "error" => "No property details found"
    ]);

    exit;
}

$conn->beginTransaction();

try {

    /* =====================================================
       GET USER DETAILS
    ===================================================== */

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

    $loggedInUser = $stmtUser->fetch(PDO::FETCH_ASSOC);

    if (!$loggedInUser) {
        throw new Exception("User not found");
    }

    $username = $loggedInUser['username'];
    $userRole = $loggedInUser['designation'];

    $formId = null;

    /* =====================================================
       UPDATE DRAFT
    ===================================================== */

    if ($editFormId) {

        $stmtCheck = $conn->prepare("
            SELECT id
            FROM forms
            WHERE id = :id
                AND uid = :uid
                AND status = 'Draft'
            LIMIT 1
        ");

        $stmtCheck->execute([
            ":id" => $editFormId,
            ":uid" => $uid
        ]);

        $existingId = $stmtCheck->fetchColumn();

        if (!$existingId) {
            throw new Exception("Draft not found or not editable");
        }

        $stmtUpdate = $conn->prepare("
            UPDATE forms SET

                form_type = :form_type,
                purpose = :purpose,
                acquired_disposed = :acquired_disposed,
                date_acquisition_disposed = :date_acquisition_disposed,

                mode_acquisition = :mode_acquisition,
                mode_acquisition_other = :mode_acquisition_other,

                mode_disposal = :mode_disposal,
                mode_disposal_other = :mode_disposal_other,

                acquisition_gift = :acquisition_gift,
                other_relevant = :other_relevant,

                status = :status,

                forward_to = :forward_to,

                updated_by = :uid,
                updated_at = NOW(),

                current_holder = :current_holder,
                current_role_name = :current_role_name,

                last_action = :last_action

            WHERE id = :id
        ");

        $stmtUpdate->execute([

            ":form_type" => $form_type,
            ":purpose" => $purpose,
            ":acquired_disposed" => $acquired_disposed,
            ":date_acquisition_disposed" => $date_acquisition_disposed,

            ":mode_acquisition" => $mode_acquisition,
            ":mode_acquisition_other" => $mode_acquisition_other,

            ":mode_disposal" => $mode_disposal,
            ":mode_disposal_other" => $mode_disposal_other,

            ":acquisition_gift" => $acquisition_gift,
            ":other_relevant" => $other_relevant,

            ":status" => $status,

            ":forward_to" => $currentHolder,

            ":uid" => $uid,

            ":current_holder" => $currentHolder,
            ":current_role_name" => $currentRole,

            ":last_action" => $lastAction,

            ":id" => $editFormId
        ]);

        /* DELETE OLD DATA */

        $stmtDeleteApplicants = $conn->prepare("
            DELETE FROM applicants
            WHERE property_id IN (
                SELECT id
                FROM properties
                WHERE form_id = :form_id
            )
        ");

        $stmtDeleteApplicants->execute([
            ":form_id" => $editFormId
        ]);

        $stmtDeleteSources = $conn->prepare("
            DELETE FROM sources
            WHERE property_id IN (
                SELECT id
                FROM properties
                WHERE form_id = :form_id
            )
        ");

        $stmtDeleteSources->execute([
            ":form_id" => $editFormId
        ]);

        $stmtDeleteProperties = $conn->prepare("
            DELETE FROM properties
            WHERE form_id = :form_id
        ");

        $stmtDeleteProperties->execute([
            ":form_id" => $editFormId
        ]);

        $formId = (int)$editFormId;
    } else {

        /* =====================================================
           INSERT FORM
        ===================================================== */

        $stmt = $conn->prepare("
            INSERT INTO forms (

                uid,
                created_by,

                form_type,
                purpose,

                acquired_disposed,
                date_acquisition_disposed,

                mode_acquisition,
                mode_acquisition_other,

                mode_disposal,
                mode_disposal_other,

                acquisition_gift,
                other_relevant,

                status,

                forward_to,

                current_holder,
                current_role_name,

                last_action

            )
            VALUES (

                :uid,
                :created_by,

                :form_type,
                :purpose,

                :acquired_disposed,
                :date_acquisition_disposed,

                :mode_acquisition,
                :mode_acquisition_other,

                :mode_disposal,
                :mode_disposal_other,

                :acquisition_gift,
                :other_relevant,

                :status,

                :forward_to,

                :current_holder,
                :current_role_name,

                :last_action
            )
            RETURNING id
        ");

        $stmt->execute([

            ":uid" => $uid,
            ":created_by" => $uid,

            ":form_type" => $form_type,
            ":purpose" => $purpose,

            ":acquired_disposed" => $acquired_disposed,
            ":date_acquisition_disposed" => $date_acquisition_disposed,

            ":mode_acquisition" => $mode_acquisition,
            ":mode_acquisition_other" => $mode_acquisition_other,

            ":mode_disposal" => $mode_disposal,
            ":mode_disposal_other" => $mode_disposal_other,

            ":acquisition_gift" => $acquisition_gift,
            ":other_relevant" => $other_relevant,

            ":status" => $status,

            ":forward_to" => $currentHolder,

            ":current_holder" => $currentHolder,
            ":current_role_name" => $currentRole,

            ":last_action" => $lastAction
        ]);

        $formId = $stmt->fetchColumn();

        /* =====================================================
           INSERT MOVEMENT
        ===================================================== */

        if ($form_status === 1) {

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

                ":form_id" => $formId,

                ":from_user_id" => $uid,
                ":from_role" => $userRole,

                ":to_user_id" => $currentHolder,
                ":to_role" => $currentRole,

                ":action" => 'Created',

                ":remarks" => 'Form submitted'
            ]);
        }
    }

    /* =====================================================
       PROPERTY LOOP
    ===================================================== */

    $properties = json_decode($propertyDetails, true);

    if (!is_array($properties)) {
        throw new Exception("Invalid propertyDetails JSON");
    }

    foreach ($properties as $property) {

        $stmt = $conn->prepare("
            INSERT INTO properties (

                form_id,

                property_location,
                property_description,
                property_hold,
                property_price,

                disposal_property,
                disposal_property_reason,
                disposal_file_key,

                party_name,
                party_address,

                party_relationship,
                party_relationship_description,

                applicant_dealing_parties,
                applicant_dealing_parties_description,

                nature_dealing_party,
                party_transaction_mode

            )
            VALUES (

                :form_id,

                :property_location,
                :property_description,
                :property_hold,
                :property_price,

                :disposal_property,
                :disposal_property_reason,
                :disposal_property_attachment,

                :party_name,
                :party_address,

                :party_relationship,
                :party_relationship_description,

                :applicant_dealing_parties,
                :applicant_dealing_parties_description,

                :nature_dealing_party,
                :party_transaction_mode
            )
            RETURNING id
        ");

        $stmt->execute([

            ":form_id" => $formId,

            ":property_location" =>
            $property['property_location'] ?? null,

            ":property_description" =>
            $property['property_description'] ?? null,

            ":property_hold" =>
            $property['property_hold'] ?? null,

            ":property_price" =>
            parseNumber($property['property_price'] ?? 0),

            ":disposal_property" =>
            $property['disposal_property'] ?? null,

            ":disposal_property_reason" =>
            $property['disposal_property_reason'] ?? null,

            ":disposal_property_attachment" =>
            $property['disposal_property_attachment'] ?? null,

            ":party_name" =>
            $property['party_name'] ?? null,

            ":party_address" =>
            $property['party_address'] ?? null,

            ":party_relationship" =>
            $property['party_relationship'] ?? null,

            ":party_relationship_description" =>
            $property['party_relationship_description'] ?? null,

            ":applicant_dealing_parties" =>
            $property['applicant_dealing_parties'] ?? null,

            ":applicant_dealing_parties_description" =>
            $property['applicant_dealing_parties_description'] ?? null,

            ":nature_dealing_party" =>
            $property['nature_dealing_party'] ?? null,

            ":party_transaction_mode" =>
            $property['party_transaction_mode'] ?? null
        ]);

        $propertyId = $stmt->fetchColumn();

        /* =====================================================
           APPLICANTS
        ===================================================== */

        if (!empty($property['applicants'])) {

            foreach ($property['applicants'] as $applicant) {

                $stmt = $conn->prepare("
                    INSERT INTO applicants (

                        property_id,
                        name,
                        interest,
                        relationship

                    )
                    VALUES (

                        :property_id,
                        :name,
                        :interest,
                        :relationship
                    )
                ");

                $stmt->execute([

                    ":property_id" => $propertyId,

                    ":name" =>
                    $applicant['name'] ?? null,

                    ":interest" =>
                    parseNumber($applicant['interest'] ?? 0),

                    ":relationship" =>
                    $applicant['relationship'] ?? null
                ]);
            }
        }
    }

    $conn->commit();

    /* =====================================================
       FETCH FORM
    ===================================================== */

    $stmtForm = $conn->prepare("
        SELECT *
        FROM forms
        WHERE id = :form_id
    ");

    $stmtForm->execute([
        ':form_id' => $formId
    ]);

    $formData = $stmtForm->fetch(PDO::FETCH_ASSOC);

    /* =====================================================
       HISTORY
    ===================================================== */

    $ipAddress =
        $_SERVER['REMOTE_ADDR'] ?? null;

    $userAgent =
        $_SERVER['HTTP_USER_AGENT'] ?? null;

    $actionType = $editFormId
        ? ($form_status == 0
            ? 'Draft Updated'
            : 'Form Updated')
        : ($form_status == 0
            ? 'Draft Created'
            : 'Form Submitted');

    // create history only if form is submitted (not for drafts) or if a draft is being updated 
    if ($form_status != 0) {

        /* =====================================================
        READABLE HISTORY TEXT
        ===================================================== */
        $createdAt = !empty($formData['created_at'])
            ? date('d-m-Y h:i A', strtotime($formData['created_at']))
            : date('d-m-Y h:i A');

        $historyText = "

            {$form_type} property form submitted by {$username} ({$userRole})
            with the purpose of '{$purpose}'
            and forwarded to {$currentHolderName} ({$currentRole})
            on {$createdAt}.

        ";

        /* =====================================================
        INSERT HISTORY
        ===================================================== */

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

            ':form_id' => $formId,

            ':action_type' => $actionType,

            ':action_by' => $uid,
            ':action_by_role' => $userRole,

            ':action_to' => $currentHolder,
            ':action_to_role' => $currentRole,

            ':field_name' => 'New Form Submission',

            ':old_value' => null,

            ':new_value' => $historyText,

            ':remarks' => $remarks ?? null,

            ':ip_address' => $ipAddress,

            ':user_agent' => $userAgent
        ]);
    }

    echo json_encode([

        "success" => true,

        "message" => $editFormId
            ? (
                $form_status === 0
                ? "Draft updated successfully"
                : "Form updated and submitted successfully"
            )
            : (
                $form_status === 0
                ? "Draft saved successfully"
                : "Form submitted successfully"
            ),

        "Data" => $formData,

        "status" => $status
    ]);
} catch (Exception $e) {

    $conn->rollBack();

    echo json_encode([

        "success" => false,

        "error" => $e->getMessage()
    ]);
}
