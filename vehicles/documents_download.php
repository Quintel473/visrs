<?php

/*
 * VISRS Vehicle Document Download
 *
 * GET /visrs/vehicles/documents_download.php?id=<DocumentID>
 *
 * Access: any logged-in user
 */

require_once __DIR__ . "/../includes/auth.php";
require_once __DIR__ . "/../includes/database.php";
require_once __DIR__ . "/../includes/functions.php";

$documentId = (int) ($_GET["id"] ?? 0);

if ($documentId <= 0) {
    http_response_code(400);
    exit("Invalid document ID.");
}

/* Load the record */
$stmt = $pdo->prepare("
    SELECT
        DocumentID,
        OriginalName,
        FilePath,
        MimeType,
        FileSize
    FROM vehicle_documents
    WHERE DocumentID = ?
    LIMIT 1
");
$stmt->execute([$documentId]);
$doc = $stmt->fetch();

if (!$doc) {
    http_response_code(404);
    exit("Document not found.");
}

/* Resolve absolute path */
$absolutePath = __DIR__ . "/../" . $doc["FilePath"];

if (!is_file($absolutePath)) {
    http_response_code(404);
    exit("File not found on disk.");
}

/* Stream the file */
$safeName = preg_replace('/[^A-Za-z0-9._\-]/', '_', $doc["OriginalName"]);
if ($safeName === "" || $safeName === null) {
    $safeName = "document";
}

header("Content-Type: " . $doc["MimeType"]);
header("Content-Length: " . (int) $doc["FileSize"]);
header("Content-Disposition: attachment; filename=\"" . $safeName . "\"");
header("X-Content-Type-Options: nosniff");
header("Cache-Control: private, no-store");

readfile($absolutePath);
exit;