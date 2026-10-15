<?php

/*
 * VISRS — Upload Accident Photo
 *
 * Handles POST from the accident view page. Validates the
 * file, stores it under uploads/accidents/{accidentID}/,
 * writes a database row, and redirects back.
 */

require_once __DIR__ . "/../includes/auth.php";
require_once __DIR__ . "/../includes/database.php";
require_once __DIR__ . "/../includes/functions.php";

requireRole(["Admin", "Police"]);


/* ---------- Ensure POST ---------- */

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: index.php");
    exit;
}

requireValidCSRF();


/* ---------- Get the accident ID ---------- */

$accidentID = (int) ($_POST["accident_id"] ?? 0);

if ($accidentID <= 0) {
    header("Location: index.php?error=invalid_request");
    exit;
}


/* ---------- Confirm the accident exists ---------- */

$checkStmt = $pdo->prepare("
    SELECT AccidentID
    FROM accidents
    WHERE AccidentID = ?
    LIMIT 1
");

$checkStmt->execute([$accidentID]);

if (!$checkStmt->fetch()) {
    header("Location: index.php?error=not_found");
    exit;
}


/* ---------- Validate the file ---------- */

$error = "";

if (
    !isset($_FILES["photo"]) ||
    $_FILES["photo"]["error"] === UPLOAD_ERR_NO_FILE
) {

    $error = "Please select a photo to upload.";

} else {

    $file = $_FILES["photo"];

    $allowedTypes = [
        "image/jpeg",
        "image/png",
        "image/webp"
    ];

    $maximumFileSize = 10 * 1024 * 1024;  // 10 MB


    if ($file["error"] !== UPLOAD_ERR_OK) {

        $error = "The photo could not be uploaded. Please try again.";

    } elseif ($file["size"] <= 0 || $file["size"] > $maximumFileSize) {

        $error = "The photo must be smaller than 10 MB.";

    } else {

        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mimeType = $finfo->file($file["tmp_name"]);


        if (!in_array($mimeType, $allowedTypes, true)) {

            $error = "Only JPG, PNG, and WebP images are allowed.";

        } elseif (@getimagesize($file["tmp_name"]) === false) {

            $error = "The uploaded file is not a valid image.";
        }
    }
}


/* ---------- If validation failed, redirect back with error ---------- */

if ($error !== "") {

    $_SESSION["photo_error"] = $error;

    header("Location: view.php?id=" . $accidentID);
    exit;
}


/* ---------- Save the file ---------- */

$extension = "jpg";

if ($mimeType === "image/png") {
    $extension = "png";
} elseif ($mimeType === "image/webp") {
    $extension = "webp";
}


$uploadDirectory =
    __DIR__
    . "/../uploads/accidents/"
    . $accidentID;


if (!is_dir($uploadDirectory)) {

    if (!mkdir($uploadDirectory, 0755, true)) {

        $_SESSION["photo_error"] = "Unable to prepare the upload directory.";

        header("Location: view.php?id=" . $accidentID);
        exit;
    }
}


$randomName = bin2hex(random_bytes(16));

$fileName =
    "accident_"
    . $accidentID
    . "_"
    . $randomName
    . "."
    . $extension;

$destination =
    $uploadDirectory
    . DIRECTORY_SEPARATOR
    . $fileName;


if (!move_uploaded_file($file["tmp_name"], $destination)) {

    $_SESSION["photo_error"] = "Unable to save the uploaded photo.";

    header("Location: view.php?id=" . $accidentID);
    exit;
}


/* ---------- Insert the database row ---------- */

try {

    $pdo->beginTransaction();


    $insertStmt = $pdo->prepare("
        INSERT INTO accident_photos
            (AccidentID, UploadedBy, OriginalName,
             StoredName, MimeType, FileSize)
        VALUES
            (?, ?, ?, ?, ?, ?)
    ");

    $insertStmt->execute([
        $accidentID,
        (int) currentUserId(),
        $file["name"],
        $fileName,
        $mimeType,
        (int) $file["size"]
    ]);


    logAudit(
        $pdo,
        "Uploaded accident photo",
        "accident_photos",
        (int) $pdo->lastInsertId()
    );


    $pdo->commit();


    $_SESSION["photo_success"] = "Photo uploaded successfully.";

} catch (Throwable $e) {

    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    /* Clean up the file we just saved */
    if (is_file($destination)) {
        @unlink($destination);
    }

    $_SESSION["photo_error"] = "Unable to save the photo record. Please try again.";
}


header("Location: view.php?id=" . $accidentID);
exit;