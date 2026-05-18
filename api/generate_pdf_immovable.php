<?php
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__.'/error.log');

require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/../connection/db.php';

use Mpdf\Mpdf;

/* ================= INPUT ================= */
$rawInput = file_get_contents("php://input");
$inputs   = json_decode($rawInput, true);

$id = $inputs['id'] ?? '';

if (empty($id) || !is_numeric($id)) {
    http_response_code(400);
    exit('Invalid ID');
}

/* ================= FETCH FORM ================= */
$stmt = $conn->prepare("
    SELECT 
        f.*,
        u.username,
        u.designation,
        u.emp_code
    FROM forms f
    LEFT JOIN users u ON f.uid::integer = u.uid
    WHERE f.id = :id
");
$stmt->execute([':id' => $id]);
$form = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$form) {
    exit('No data found');
}

/* ================= FETCH PROPERTIES ================= */
$stmtProp = $conn->prepare("
    SELECT *
    FROM properties
    WHERE form_id = :id
");
$stmtProp->execute([':id' => $id]);
$propertiesRaw = $stmtProp->fetchAll(PDO::FETCH_ASSOC);

/* ================= FETCH APPLICANTS & SOURCES ================= */
$properties = [];

foreach ($propertiesRaw as $prop) {

    $propertyId = $prop['id'];

    /* ===== APPLICANTS ===== */
    $stmtApplicants = $conn->prepare("
        SELECT name, interest, relationship
        FROM applicants
        WHERE property_id = :property_id
    ");
    $stmtApplicants->execute([':property_id' => $propertyId]);
    $applicants = $stmtApplicants->fetchAll(PDO::FETCH_ASSOC);

    /* ===== SOURCES ===== */
    $stmtSources = $conn->prepare("
        SELECT source_name AS name, amount
        FROM sources
        WHERE property_id = :property_id
    ");
    $stmtSources->execute([':property_id' => $propertyId]);
    $sources = $stmtSources->fetchAll(PDO::FETCH_ASSOC);

    $prop['applicants'] = $applicants ?: [];
    $prop['sources']    = $sources ?: [];

    $properties[] = $prop;
}

/* ================= SAFE FUNCTION ================= */
function safe($val)
{
    return htmlspecialchars($val ?? '', ENT_QUOTES, 'UTF-8');
}

/* ================= BUILD PROPERTY TEXT ================= */
$propertyText = '';

if (count($properties) === 1) {

    $p = $properties[0];

    // Applicants
    $applicantsText = '';
    if (!empty($p['applicants'])) {
        $arr = [];
        foreach ($p['applicants'] as $a) {
            $arr[] = ($a['interest'] ?? '') . "% " . ($a['relationship'] ?? '') . " (" . ($a['name'] ?? '') . ")";
        }
        $applicantsText = implode(" and ", $arr);
    }

    // Sources
    $sourceText = '';
    if (!empty($p['sources'])) {
        $arr = [];
        foreach ($p['sources'] as $s) {
            $arr[] = "₹" . number_format($s['amount'], 0) . "/-";
        }
        $sourceText = "The funds for the said purchase were arranged through " . implode(" and ", $arr) . ".";
    }

    $propertyText = "
        <p style='text-align:justify;'>
            The immovable property, i.e., " . safe($p['property_description']) . ",
            located at " . safe($p['property_location']) . ",
            for a total consideration amount of ₹" . number_format($p['property_price'], 0) . "/-,
            is owned by $applicantsText.
        </p>

        <p style='text-align:justify;'>
            The said property is " . (strpos($applicantsText, '100') !== false ? "100% owned by self" : "jointly owned") . "
            and was acquired on " . safe($form['date_acquisition_disposed']) . ".
            $sourceText
        </p>
    ";

} else {

    $ordinals = ["first", "second", "third", "fourth"];

    foreach ($properties as $index => $p) {

        $num = $index + 1;

        $prefix = isset($ordinals[$index]) ?
            "The {$ordinals[$index]} property pertains to" :
            "The property no. $num pertains to";

        // Applicants
        $applicantsText = '';
        if (!empty($p['applicants'])) {
            $arr = [];
            foreach ($p['applicants'] as $a) {
                $arr[] = ($a['interest'] ?? '') . "% in the name of " . ($a['relationship'] ?? '') . " (" . ($a['name'] ?? '') . ")";
            }
            $applicantsText = implode(" and ", $arr);
        }

        // Sources
        $sourceText = '';
        if (!empty($p['sources'])) {
            $arr = [];
            foreach ($p['sources'] as $s) {
                $arr[] = "₹" . number_format($s['amount'], 0) . "/- paid by " . ($s['name'] ?? '');
            }
            $sourceText = "The funds of this property comprised of " . implode(", ", $arr) . ".";
        }

        $propertyText .= "
            <p style='text-align:justify;'>
                <strong>$num.</strong>
                $prefix " . safe($p['property_description']) . ",
                located at " . safe($p['property_location']) . ",
                for a total consideration amount of ₹" . number_format($p['property_price'], 0) . "/-,
                is owned by $applicantsText.
                $sourceText
            </p>
        ";
    }
}

/* ================= BUILD FINAL HTML ================= */
$html = '
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<style>
body { font-family: "Times New Roman", serif; font-size: 14px; line-height:1.6; }
.header { text-align:center; font-weight:bold; }
.right { text-align:right; margin-top:10px; }
.title { text-align:center; font-weight:bold; margin:20px 0; font-size:18px; }
.subject { font-weight:bold; margin-bottom:15px; }
.signature { text-align:right; margin-top:40px; }
.copy { margin-top:40px; }
</style>
</head>
<body>

<div class="header">
    <div><strong>F. No. PF-7625/NIC/2026-Adm.II</strong></div>
    <div><strong>Government of India</strong></div>
    <div><strong>Ministry of Electronics and Information Technology</strong></div>
    <div><strong>National Informatics Centre</strong></div>
    <div>(Administration Section-II)</div>
</div>

<div class="right">
    A – Block, CGO Complex,<br>
    Lodhi Road, New Delhi – 110003<br>
    Dated: ' . date("d-m-Y") . '
</div>

<div class="title">MEMORANDUM</div>

<div class="subject">
    Subject: Intimation regarding ' . safe($form['acquired_disposed']) . ' of Immovable Property – reg.
</div>

<p style="text-align:justify;">
    The undersigned is directed to refer to the application in Form-I dated 
    ' . safe($form['date_acquisition_disposed']) . '
    submitted by ' . safe($form['username']) . ', ' . safe($form['designation']) . '
    (Employee Code: ' . safe($form['emp_code']) . '),
    regarding the ' . safe($form['acquired_disposed']) . ' of immovable property.
</p>

' . $propertyText . '

<p style="text-align:justify;">
    The intimation given in Form-I under Rule 18(2) of the Central Civil
    Services (Conduct) Rules, 1964, is hereby acknowledged for the
    aforementioned ' . safe($form['acquired_disposed']) . ' of immovable property.
    The officer is advised to strictly adhere to the time frame and procedure prescribed under the Rules.
</p>

<div class="signature">
    <strong>(Balraj Singh)</strong><br>
    HoD & Joint Director<br>
    Tel:- 24305006<br>
    Email:- s.balraj@nic.in
</div>

<div class="copy">
    <p><strong>To:</strong> ' . safe($form['username']) . ', ' . safe($form['designation']) . ', Employee Code: ' . safe($form['emp_code']) . '</p>
    <p><strong>Copy to:</strong></p>
    <ol>
        <li>Vol-II/' . safe($form['emp_code']) . '</li>
        <li>Office Copy</li>
    </ol>
</div>

</body>
</html>
';

/* ================= GENERATE PDF ================= */
try {

    $mpdf = new Mpdf([
        'mode' => 'utf-8',
        'margin_top' => 20,
        'margin_bottom' => 20,
        'margin_left' => 15,
        'margin_right' => 15
    ]);

    $mpdf->WriteHTML($html);

    $mpdf->Output('Memo.pdf', 'D');

} catch (\Mpdf\MpdfException $e) {
    error_log($e->getMessage());
    http_response_code(500);
    exit('PDF generation failed');
}

exit;