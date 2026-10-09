<?php

/*
 * VISRS Vehicle Document Delete Handler
 *
 * POST /visrs/vehicles/documents_delete.php
 * Form fields: csrf_token, document_id
 *
 * Access: Admin, Police
 */

require_once __DIR__ . "/../includes/auth.php";
require_once __DIR__ . "/../includes/database.php";
require_once __DIR__ . "/../includes/functions.php";

requireRole(["Admin", "Police"]);

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: index.php");
    exit;
}

requireValidCSRF();

$documentId = (int) ($_POST["document_id"] ?? 0);

if ($documentId <= 0) {
    header("Location: /visrs/vehicles/");
    exit;
}

/* Load the record */
$stmt = $pdo->prepare("
    SELECT
        d.DocumentID,
        d.VehicleID,
        d.DocType,
        d.FilePath,
        v.PlateNumber
    FROM vehicle_documents d
    INNER JOIN vehicles v ON d.VehicleID = v.VehicleID
    WHERE d.DocumentID = ?
    LIMIT 1
");
$stmt->execute([$documentId]);
$doc = $stmt->fetch();

if (!$doc) {
    $_SESSION["bulk_message"] = [
        "type" => "error",
        "text" => "Document not found."
    ];
    header("Location: /visrs/vehicles/");
    exit;
}

$vehicleId = (int) $doc["VehicleID"];

try {
    $pdo->beginTransaction();

    /* Delete DB record */
    $del = $pdo->prepare("
        DELETE FROM vehicle_documents WHERE DocumentID = ?
    ");
    $del->execute([$documentId]);

    /* Audit log */
    logAudit(
        $pdo,
        "Deleted vehicle document: " . $doc["DocType"] . " from " . $doc["PlateNumber"],
        "vehicle_documents",
        $documentId
    );

    $pdo->commit();

    /* Best-effort: delete the physical file */
    $absolutePath = __DIR__ . "/../" . $doc["FilePath"];
    if (is_file($absolutePath)) {
        @unlink($absolutePath);
    }

    $_SESSION["bulk_message"] = [
        "type" => "success",
        "text" => "Document deleted successfully."
    ];

} catch (Throwable $e) {

    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    $_SESSION["bulk_message"] = [
        "type" => "error",
        "text" => "Unable to delete the document."
    ];
}

header("Location: documents.php?id=" . $vehicleId);
exit;