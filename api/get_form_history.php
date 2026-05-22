<?php
session_start();
include '../connection/db.php';
header('Content-Type: application/json');

$input = json_decode(file_get_contents("php://input"), true);
$formId = $input['id'] ?? $_POST['id'] ?? '';

if (!$formId) {
    echo json_encode([
        "success" => false,
        "error" => "Missing form ID"
    ]);
    exit;
}

try {
    $stmt = $conn->prepare("
        SELECT
            h.id,
            h.form_id,
            h.action_type,
            h.action_by,
            h.action_by_role,
            h.action_to,
            h.action_to_role,
            h.field_name,
            h.old_value,
            h.new_value,
            h.remarks,
            h.created_at,
            by_user.username AS action_by_name,
            to_user.username AS action_to_name
        FROM form_history h
        LEFT JOIN users by_user
            ON h.action_by::text = by_user.uid::text
        LEFT JOIN users to_user
            ON h.action_to::text = to_user.uid::text
        WHERE h.form_id = :form_id
        ORDER BY h.created_at ASC, h.id ASC
    ");

    $stmt->execute([':form_id' => $formId]);
    $history = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $formStmt = $conn->prepare("SELECT * FROM forms WHERE id = :form_id");
    $formStmt->execute([':form_id' => $formId]);
    $formData = $formStmt->fetch(PDO::FETCH_ASSOC);

    echo json_encode([
        "success" => true,
        "formData" => $formData,
        "data" => $history
    ]);
} catch (Exception $e) {
    echo json_encode([
        "success" => false,
        "error" => $e->getMessage()
    ]);
}
?>
