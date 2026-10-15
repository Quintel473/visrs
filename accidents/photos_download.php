<?php

/*
 * VISRS — Download Accident Photo
 *
 * Streams a photo back to the browser with a
 * Content-Disposition header. Verifies the requesting
 * user is authenticated.
 */

require_once __DIR__ . "/../includes/auth.php";
require_once __DIR__ . "/../includes/database.php";
require_once __DIR__ . "/../includes/functions.php";

$photoID = (int) ($_GET["id"] ?? 0);

if ($photoID <= 0) {
    http_response_code(400);
    exit;
}


/* ---------- Load the photo record ---------- */

$stmt = $pdo->prepare("
    SELECT
        PhotoID,
        AccidentID,
        OriginalName,
        StoredName,
        MimeType,
        FileSize
    FROM accident_photos
    WHERE PhotoID = ?
    LIMIT 1
");

$stmt->execute([$photoID]);
$photo = $stmt->fetch();

if (!$photo) {
    http_response_code(404);
    exit("Photo not found.");
}


/* ---------- Build the path ---------- */

$filePath =
    __DIR__
    . "/../uploads/accidents/"
    . (int) $photo["AccidentID"]
    . "/"
    . $photo["StoredName"];


if (!is_file($filePath)) {
    http_response_code(404);
    exit("File not found on disk.");
}


/* ---------- Serve it ---------- */

$inline = isset($_GET["inline"]) && $_GET["inline"] === "1";

header("Content-Type: " . $photo["MimeType"]);
header("Content-Length: " . filesize($filePath));
header("Cache-Control: private, max-age=3600");

if ($inline) {

    header(
        "Content-Disposition: inline; filename=\""
        . rawurlencode($photo["OriginalName"])
        . "\""
    );

} else {

    header(
        "Content-Disposition: attachment; filename=\""
        . rawurlencode($photo["OriginalName"])
        . "\""
    );
}

readfile($filePath);
exit;