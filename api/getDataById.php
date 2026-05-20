<?php

session_start();
include '../connection/db.php';

header('Content-Type: application/json');

error_reporting(E_ALL);
ini_set('display_errors', 1);

/* =====================================================
   INPUT
===================================================== */

$input = json_decode(file_get_contents("php://input"), true);

$formId = $input['id'] ?? '';

$loggedInUserId = $_SESSION['uid'] ?? null;

if (!$loggedInUserId) {

    echo json_encode([
        "success" => false,
        "error" => "User not logged in"
    ]);
    exit;
}

if (!$formId) {

    echo json_encode([
        "success" => false,
        "error" => "Missing form ID"
    ]);
    exit;
}

try {

    /* =====================================================
       FORM + USER + LATEST MOVEMENT
    ====================================================== */

    $stmt = $conn->prepare("
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

    $stmt->execute([
        ':id' => $formId
    ]);

    $formRow = $stmt->fetch(PDO::FETCH_ASSOC);

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

    if (
        (
            (int)$formRow['current_holder'] === (int)$loggedInUserId
            ||
            (int)$formRow['from_user_id'] === (int)$loggedInUserId
        )
        && !(bool)$formRow['is_locked']
    ) {

        $canTakeAction = true;
    }

    /* =====================================================
       PROPERTIES
    ====================================================== */

    $stmtProperties = $conn->prepare("
        SELECT *
        FROM properties
        WHERE form_id = :form_id
        ORDER BY id ASC
    ");

    $stmtProperties->execute([
        ':form_id' => $formId
    ]);

    $properties = $stmtProperties->fetchAll(PDO::FETCH_ASSOC);

    $formattedProperties = [];

    foreach ($properties as $prop) {

        $propertyId = $prop['id'];

        /* =============================================
           APPLICANTS
        ============================================= */

        $stmtApplicants = $conn->prepare("
            SELECT *
            FROM applicants
            WHERE property_id = :property_id
            ORDER BY id ASC
        ");

        $stmtApplicants->execute([
            ':property_id' => $propertyId
        ]);

        $applicants = $stmtApplicants->fetchAll(PDO::FETCH_ASSOC);

        $formattedApplicants = [];

        foreach ($applicants as $app) {

            $formattedApplicants[] = [

                "id" => $app["id"],

                "name" => $app["name"],

                "interest" => $app["interest"],

                "relationship" => $app["relationship"]
            ];
        }

        /* =============================================
           SOURCES
        ============================================= */

        $stmtSources = $conn->prepare("
            SELECT

                s.*,

                f.file_key,
                f.file_name,
                f.file_type

            FROM sources s

            LEFT JOIN files f
                ON s.file_key = f.file_key

            WHERE s.property_id = :property_id

            ORDER BY s.id ASC
        ");

        $stmtSources->execute([
            ':property_id' => $propertyId
        ]);

        $sources = $stmtSources->fetchAll(PDO::FETCH_ASSOC);

        $formattedSources = [];

        foreach ($sources as $src) {

            $formattedSources[] = [

                "id" => $src["id"],

                "name" => $src["source_name"],

                "amount" => $src["amount"],

                "attachment" => $src["file_name"]
                    ? [
                        "file_key" => $src["file_key"],
                        "file_name" => $src["file_name"],
                        "file_type" => $src["file_type"],
                        "download_url" =>
                        "api/view_attachement_file.php?file_key=" .
                            $src["file_key"]
                    ]
                    : null
            ];
        }

        /* =============================================
           DISPOSAL ATTACHMENT
        ============================================= */

        $disposalAttachment = null;

        if (!empty($prop['disposal_file_key'])) {

            $stmtFile = $conn->prepare("
                SELECT
                    file_key,
                    file_name,
                    file_type
                FROM files
                WHERE file_key = :file_key
                LIMIT 1
            ");

            $stmtFile->execute([
                ':file_key' => $prop['disposal_file_key']
            ]);

            $file = $stmtFile->fetch(PDO::FETCH_ASSOC);

            if ($file) {

                $disposalAttachment = [

                    "file_key" => $file["file_key"],

                    "file_name" => $file["file_name"],

                    "file_type" => $file["file_type"],

                    "download_url" =>
                    "api/view_attachement_file.php?file_key=" .
                        $file["file_key"]
                ];
            }
        }

        /* =============================================
           PROPERTY OBJECT
        ============================================= */

        $formattedProperties[] = [

            "id" => $prop["id"],

            "property_location" => $prop["property_location"],

            "property_description" => $prop["property_description"],

            "property_hold" => $prop["property_hold"],

            "property_price" => $prop["property_price"],

            "disposal_property" => $prop["disposal_property"],

            "disposal_property_reason" => $prop["disposal_property_reason"],

            "party_name" => $prop["party_name"],

            "party_address" => $prop["party_address"],

            "party_relationship" => $prop["party_relationship"],

            "party_relationship_description" =>
            $prop["party_relationship_description"],

            "applicant_dealing_parties" =>
            $prop["applicant_dealing_parties"],

            "applicant_dealing_parties_description" =>
            $prop["applicant_dealing_parties_description"],

            "nature_dealing_party" =>
            $prop["nature_dealing_party"],

            "party_transaction_mode" =>
            $prop["party_transaction_mode"],

            "disposal_attachment" => $disposalAttachment,

            "applicants" => $formattedApplicants,

            "sources" => $formattedSources
        ];
    }

    /* =====================================================
       FINAL RESPONSE
    ====================================================== */

    $response = [

        "id" => (int)$formRow["id"],

        "reference_no" => $formRow["reference_no"],

        "form_type" => $formRow["form_type"],

        "uid" => $formRow["uid"],

        "purpose" => $formRow["purpose"],

        "forward_to" => $formRow["forward_to"],

        "acquired_disposed" => $formRow["acquired_disposed"],

        "date_acquisition_disposed" =>
        $formRow["date_acquisition_disposed"],

        "mode_acquisition" => $formRow["mode_acquisition"],

        "mode_acquisition_other" =>
        $formRow["mode_acquisition_other"],

        "mode_disposal" => $formRow["mode_disposal"],

        "mode_disposal_other" =>
        $formRow["mode_disposal_other"],

        "acquisition_gift" => $formRow["acquisition_gift"],

        "other_relevant" => $formRow["other_relevant"],

        "status" => $formRow["status"],

        "current_phase" => $formRow["current_phase"],

        "remarks" => $formRow["remarks"],

        "created_at" => $formRow["created_at"],

        "updated_at" => $formRow["updated_at"],

        "correctom" => $formRow["correctom"],

        /* =============================================
           OWNER
        ============================================= */

        "owner_user" => [

            "uid" => $formRow["uid"],

            "username" => $formRow["owner_username"],

            "email" => $formRow["email"],

            "designation" => $formRow["designation"],

            "service" => $formRow["service"],

            "emp_code" => $formRow["emp_code"],

            "payscale" => $formRow["payscale"],

            "address" => $formRow["address"],

            "state" => $formRow["state"]
        ],

        /* =============================================
           CURRENT HOLDER
        ============================================= */

        "current_holder_user" => [

            "uid" => $formRow["current_holder"],

            "username" => $formRow["current_holder_name"],

            "role" => $formRow["current_role_name"]
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

        /* =============================================
           ACCESS FLAGS
        ============================================= */

        "permissions" => [

            "is_form_owner" => $isFormOwner,

            "is_current_holder" => $isCurrentHolder,

            "is_latest_sender" => $isLatestSender,

            "is_latest_receiver" => $isLatestReceiver,

            "can_pullback" => $canPullBack,

            "can_take_action" => $canTakeAction
        ],

        /* =============================================
           LOCK INFO
        ============================================= */

        "lock_info" => [

            "is_locked" => (bool)$formRow["is_locked"],

            "locked_by" => $formRow["locked_by"],

            "locked_at" => $formRow["locked_at"],

            "is_opened" => (bool)$formRow["is_opened"],

            "opened_at" => $formRow["opened_at"]
        ],

        "properties" => $formattedProperties
    ];

    echo json_encode([

        "success" => true,

        "data" => $response

    ], JSON_UNESCAPED_UNICODE);
} catch (Exception $e) {

    echo json_encode([

        "success" => false,

        "error" => $e->getMessage()

    ], JSON_UNESCAPED_UNICODE);
}
