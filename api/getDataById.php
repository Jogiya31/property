<?php
session_start();
include '../connection/db.php';
header('Content-Type: application/json');

/* ========= INPUT ========= */
$input = json_decode(file_get_contents("php://input"), true);
$Id = $input['id'] ?? '';

if (!$Id) {
    echo json_encode([
        "success" => false,
        "error" => "Missing form ID"
    ]);
    exit;
}

try {

    /* =====================================================
        FORM + USER DATA (FIXED UID TYPE)
    ===================================================== */
    $stmt = $conn->prepare('
        SELECT 
            f.*,
            u.username,
            u.email,
            u.designation,
            u.service,
            u.emp_code,
            u.payscale,
            u.address,
            u.state
        FROM forms f
        LEFT JOIN users u ON f.uid::integer = u.uid
        WHERE f.id = :Id
    ');

    $stmt->execute([':Id' => $Id]);
    $formRow = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$formRow) {
        echo json_encode([
            "success" => false,
            "error" => "Form not found"
        ]);
        exit;
    }

    /* =====================================================
        PROPERTIES
    ===================================================== */
    $stmtProperties = $conn->prepare("
        SELECT *
        FROM properties
        WHERE form_id = :Id
        ORDER BY id ASC
    ");
    $stmtProperties->execute([':Id' => $Id]);
    $properties = $stmtProperties->fetchAll(PDO::FETCH_ASSOC);

    $formattedProperties = [];

    foreach ($properties as $prop) {

        $propertyId = $prop['id'];

        /* ================= APPLICANTS ================= */
        $stmtApplicants = $conn->prepare("
            SELECT *
            FROM applicants
            WHERE property_id = :property_id
            ORDER BY id ASC
        ");
        $stmtApplicants->execute([':property_id' => $propertyId]);
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

        /* ================= SOURCES ================= */
        $stmtSources = $conn->prepare("
            SELECT s.*, f.file_key, f.file_name, f.file_type
            FROM sources s
            LEFT JOIN files f ON s.file_key = f.file_key
            WHERE s.property_id = :property_id
            ORDER BY s.id ASC
        ");
        $stmtSources->execute([':property_id' => $propertyId]);
        $sources = $stmtSources->fetchAll(PDO::FETCH_ASSOC);

        $formattedSources = [];
        foreach ($sources as $src) {
            $formattedSources[] = [
                "id" => $src["id"],
                "name" => $src["source_name"],
                "amount" => $src["amount"],
                "attachment" => $src["file_name"] ? [
                    "file_key" => $src["file_key"],
                    "file_name" => $src["file_name"],
                    "file_type" => $src["file_type"],
                    "download_url" => "api/view_attachement_file.php?file_key=" . $src["file_key"]
                ] : null
            ];
        }

        /* ================= DISPOSAL FILE ================= */
        $disposalFile = null;

        if (!empty($prop['disposal_file_key'])) {

            $stmtFile = $conn->prepare("
                SELECT file_key, file_name, file_type
                FROM files
                WHERE file_key = :file_key
            ");
            $stmtFile->execute([':file_key' => $prop['disposal_file_key']]);
            $file = $stmtFile->fetch(PDO::FETCH_ASSOC);

            if ($file) {
                $disposalFile = [
                    "file_key" => $file["file_key"],
                    "file_name" => $file["file_name"],
                    "file_type" => $file["file_type"],
                    "download_url" => "api/view_attachement_file.php?file_key=" . $file["file_key"]
                ];
            }
        }

        /* ================= PROPERTY OBJECT ================= */
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
            "party_relationship_description" => $prop["party_relationship_description"],
            "applicant_dealing_parties" => $prop["applicant_dealing_parties"],
            "applicant_dealing_parties_description" => $prop["applicant_dealing_parties_description"],
            "nature_dealing_party" => $prop["nature_dealing_party"],
            "party_transaction_mode" => $prop["party_transaction_mode"],

            "disposal_attachment" => $disposalFile,
            "applicants" => $formattedApplicants,
            "sources" => $formattedSources
        ];
    }

    /* =====================================================
        FINAL RESPONSE
    ===================================================== */
    $response = [
        "id" => $formRow["id"],
        "form_type" => $formRow["form_type"],
        "uid" => $formRow["uid"],
        "purpose" => $formRow["purpose"],
        "forward_to" => $formRow["forward_to"],
        "acquired_disposed" => $formRow["acquired_disposed"],
        "date_acquisition_disposed" => $formRow["date_acquisition_disposed"],
        "mode_acquisition" => $formRow["mode_acquisition"],
        "mode_acquisition_other" => $formRow["mode_acquisition_other"],
        "mode_disposal" => $formRow["mode_disposal"],
        "mode_disposal_other" => $formRow["mode_disposal_other"],
        "acquisition_gift" => $formRow["acquisition_gift"],
        "other_relevant" => $formRow["other_relevant"],
        "status" => $formRow["status"],
        "current_phase" => $formRow["current_phase"],
        "created_at" => $formRow["created_at"],
        "correctom" => $formRow["correctom"],

        "properties" => $formattedProperties,

        "user_details" => [
            "username"    => $formRow["username"],
            "email"       => $formRow["email"],
            "designation" => $formRow["designation"],
            "service"     => $formRow["service"],
            "emp_code"    => $formRow["emp_code"],
            "payscale"    => $formRow["payscale"],
            "address"     => $formRow["address"],
            "state"       => $formRow["state"],
        ]
    ];

    echo json_encode([
        "success" => true,
        "data" => $response
    ]);
} catch (Exception $e) {
    echo json_encode([
        "success" => false,
        "error" => $e->getMessage()
    ]);
}
