<?php

/*
 * VISRS Vehicle Document Upload Handler
 *
 * POST /visrs/vehicles/documents_upload.php
 * Form fields: csrf_token, vehicle_id, doc_type, document (file)
 *
 * Access: Admin, Police, Seller
 */

require_once __DIR__ . "/../includes/auth.php";
require_once __DIR__ . "/../includes/database.php";
require_once __DIR__ . "/../includes/functions.php";

requireRole(["Admin", "Police", "Seller"]);

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: index.php");
    exit;
}

requireValidCSRF();

/* Read submitted values */
$vehicleId = (int) ($_POST["vehicle_id"] ?? 0);
$docType   = trim($_POST["doc_type"] ?? "");

/* Allowed document types */
$allowedDocTypes = [
    "Registration",
    "Insurance",
    "Inspection",
    "Service History",
    "Purchase Invoice",
    "Other"
];

/* Fetch vehicle for redirect + validation */
$stmt = $pdo->prepare("
    SELECT VehicleID, PlateNumber
    FROM vehicles
    WHERE VehicleID = ?
    LIMIT 1
");
$stmt->execute([$vehicleId]);
$vehicle = $stmt->fetch();

if (!$vehicle) {
    $_SESSION["bulk_message"] = [
        "type" => "error",
        "text" => "Vehicle not found."
    ];
    header("Location: /visrs/vehicles/");
    exit;
}

if (!in_array($docType, $allowedDocTypes, true)) {
    $_SESSION["bulk_message"] = [
        "type" => "error",
        "text" => "Invalid document type."
    ];
    header("Location: documents.php?id=" . $vehicleId);
    exit;
}

/* Check file was uploaded */
if (
    !isset($_FILES["document"]) ||
    $_FILES["document"]["error"] === UPLOAD_ERR_NO_FILE
) {
    $_SESSION["bulk_message"] = [
        "type" => "error",
        "text" => "Please select a file to upload."
    ];
    header("Location: documents.php?id=" . $vehicleId);
    exit;
}

$file = $_FILES["document"];

/* Upload errors */
if ($file["error"] !== UPLOAD_ERR_OK) {
    $_SESSION["bulk_message"] = [
        "type" => "error",
        "text" => "The file could not be uploaded. Please try again."
    ];
    header("Location: documents.php?id=" . $vehicleId);
    exit;
}

/* Size limit — 10 MB */
$maxSize = 10 * 1024 * 1024;
if ($file["size"] <= 0 || $file["size"] > $maxSize) {
    $_SESSION["bulk_message"] = [
        "type" => "error",
        "text" => "File must be smaller than 10 MB."
    ];
    header("Location: documents.php?id=" . $vehicleId);
    exit;
}

/* Detect MIME type */
$finfo = new finfo(FILEINFO_MIME_TYPE);
$mimeType = $finfo->file($file["tmp_name"]);

/* Allowed MIME types */
$allowedMimes = [
    "application/pdf"                                                        => "pdf",
    "image/jpeg"                                                             => "jpg",
    "image/png"                                                              => "png",
    "application/msword"                                                     => "doc",
    "application/vnd.openxmlformats-officedocument.wordprocessingml.document" => "docx"
];

if (!isset($allowedMimes[$mimeType])) {
    $_SESSION["bulk_message"] = [
        "type" => "error",
        "text" => "Only PDF, JPG, PNG, DOC, and DOCX files are allowed."
    ];
    header("Location: documents.php?id=" . $vehicleId);
    exit;
}

/* Extra safety for images */
if (
    ($mimeType === "image/jpeg" || $mimeType === "image/png") &&
    @getimagesize($file["tmp_name"]) === false
) {
    $_SESSION["bulk_message"] = [
        "type" => "error",
        "text" => "The uploaded image is not valid."
    ];
    header("Location: documents.php?id=" . $vehicleId);
    exit;
}

/* Per-vehicle document limit */
$countStmt = $pdo->prepare("
    SELECT COUNT(*) FROM vehicle_documents WHERE VehicleID = ?
");
$countStmt->execute([$vehicleId]);
$currentCount = (int) $countStmt->fetchColumn();

if ($currentCount >= 20) {
    $_SESSION["bulk_message"] = [
        "type" => "error",
        "text" => "This vehicle already has 20 documents. Delete one before uploading more."
    ];
    header("Location: documents.php?id=" . $vehicleId);
    exit;
}

/* Prepare upload directory */
$uploadDir = __DIR__ . "/../documents/uploads";

if (!is_dir($uploadDir)) {
    if (!mkdir($uploadDir, 0755, true)) {
        $_SESSION["bulk_message"] = [
            "type" => "error",
            "text" => "Unable to prepare the upload directory."
        ];
        header("Location: documents.php?id=" . $vehicleId);
        exit;
    }
}

/* Build safe filename */
$extension = $allowedMimes[$mimeType];
$randomName = bin2hex(random_bytes(16));
$fileName   = "doc_" . $vehicleId . "_" . $randomName . "." . $extension;
$filePath   = "documents/uploads/" . $fileName;
$destination = $uploadDir . DIRECTORY_SEPARATOR . $fileName;

/* Move file */
if (!move_uploaded_file($file["tmp_name"], $destination)) {
    $_SESSION["bulk_message"] = [
        "type" => "error",
        "text" => "Unable to save the uploaded file."
    ];
    header("Location: documents.php?id=" . $vehicleId);
    exit;
}

/* Insert record */
try {
    $pdo->beginTransaction();

    $insertStmt = $pdo->prepare("
        INSERT INTO vehicle_documents (
            VehicleID,
            UploadedBy,
            DocType,
            OriginalName,
            FileName,
            FilePath,
            FileSize,
            MimeType
        )
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)
    ");

    $insertStmt->execute([
        $vehicleId,
        (int) currentUserId(),
        $docType,
        $file["name"],
        $fileName,
        $filePath,
        (int) $file["size"],
        $mimeType
    ]);

    $documentId = (int) $pdo->lastInsertId();

    /* Audit log */
    logAudit(
        $pdo,
        "Uploaded vehicle document: " . $docType . " for " . $vehicle["PlateNumber"],
        "vehicle_documents",
        $documentId
    );

    $pdo->commit();

    $_SESSION["bulk_message"] = [
        "type" => "success",
        "text" => "Document uploaded successfully."
    ];

} catch (Throwable $e) {

    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    /* Clean up the file if the DB insert failed */
    if (is_file($destination)) {
        @unlink($destination);
    }

    $_SESSION["bulk_message"] = [
        "type" => "error",
        "text" => "Unable to save the document record."
    ];
}

header("Location: documents.php?id=" . $vehicleId);
exit;