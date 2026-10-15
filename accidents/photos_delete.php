<?php

/*
 * VISRS — Delete Accident Photo
 *
 * Removes a photo record and its file from disk. Requires
 * Admin or Police role and a valid CSRF token.
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

$photoID = (int) ($_POST["photo_id"] ?? 0);

if ($photoID <= 0) {
    header("Location: index.php?error=invalid_request");
    exit;
}


/* ---------- Load the photo ---------- */

$stmt = $pdo->prepare("
    SELECT PhotoID, AccidentID, StoredName
    FROM accident_photos
    WHERE PhotoID = ?
    LIMIT 1
");

$stmt->execute([$photoID]);
$photo = $stmt->fetch();

if (!$photo) {
    header("Location: index.php?error=not_found");
    exit;
}

$accidentID = (int) $photo["AccidentID"];


/* ---------- Delete the row, then the file ---------- */

try {

    $pdo->beginTransaction();


    $delStmt = $pdo->prepare("
        DELETE FROM accident_photos WHERE PhotoID = ?
    ");

    $delStmt->execute([$photoID]);


    logAudit(
        $pdo,
        "Deleted accident photo",
        "accident_photos",
        $photoID
    );


    $pdo->commit();


    /* Best-effort file cleanup */
    $filePath =
        __DIR__
        . "/../uploads/accidents/"
        . $accidentID
        . "/"
        . $photo["StoredName"];

    if (is_file($filePath)) {
        @unlink($filePath);
    }

    $_SESSION["photo_success"] = "Photo deleted.";

} catch (Throwable $e) {

    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    $_SESSION["photo_error"] = "Unable to delete the photo. Please try again.";
}


header("Location: view.php?id=" . $accidentID);
exit;