<?php

/*
 * VISRS Bulk Delete — Accidents
 *
 * Handles batch deletion of selected accident records.
 *
 * Rules:
 *   - Only Admin / Police can bulk delete (matches $canManage).
 *   - Accident records are safe to bulk-delete — no dependencies.
 *   - All deletions are logged in the audit trail.
 */

require_once __DIR__ . "/../includes/auth.php";
require_once __DIR__ . "/../includes/database.php";
require_once __DIR__ . "/../includes/functions.php";

/* Access control */
requireRole(["Admin", "Police"]);

/* Must be a POST request */
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: index.php");
    exit;
}

/* CSRF check */
requireValidCSRF();

/* Read selected IDs */
$selectedIds = $_POST["selected_ids"] ?? [];

if (!is_array($selectedIds) || count($selectedIds) === 0) {

    $_SESSION["bulk_message"] = [
        "type" => "warning",
        "text" => "No accident records were selected."
    ];

    header("Location: index.php");
    exit;
}

/* Sanitize IDs */
$ids = [];
foreach ($selectedIds as $id) {
    $id = (int) $id;
    if ($id > 0) {
        $ids[] = $id;
    }
}

if (count($ids) === 0) {

    $_SESSION["bulk_message"] = [
        "type" => "warning",
        "text" => "No valid accident IDs were provided."
    ];

    header("Location: index.php");
    exit;
}


$deletedCount = 0;
$skippedCount = 0;


try {

    $pdo->beginTransaction();

    /*
     * Delete each accident record.
     */
    foreach ($ids as $accidentId) {

        /* Fetch record info for logging */
        $stmt = $pdo->prepare("
            SELECT
                a.AccidentDate,
                v.PlateNumber
            FROM accidents a
            INNER JOIN vehicles v
                ON a.VehicleID = v.VehicleID
            WHERE a.AccidentID = ?
            LIMIT 1
        ");
        $stmt->execute([$accidentId]);
        $record = $stmt->fetch();

        if (!$record) {
            $skippedCount++;
            continue;
        }

        $plate = $record["PlateNumber"];

        /* Delete */
        $del = $pdo->prepare("DELETE FROM accidents WHERE AccidentID = ?");
        $del->execute([$accidentId]);

        if ($del->rowCount() === 1) {
            $deletedCount++;

            logAudit(
                $pdo,
                "Bulk deleted accident record for vehicle " . $plate,
                "accidents",
                $accidentId
            );
        } else {
            $skippedCount++;
        }
    }

    $pdo->commit();

    /* Build flash message */
    $parts = [];

    if ($deletedCount > 0) {
        $parts[] = $deletedCount . " accident record(s) deleted.";
    }

    if ($skippedCount > 0) {
        $parts[] = $skippedCount . " skipped.";
    }

    if (empty($parts)) {
        $parts[] = "No accident records were deleted.";
    }

    $_SESSION["bulk_message"] = [
        "type" => $deletedCount > 0 ? "success" : "warning",
        "text" => implode(" ", $parts)
    ];

} catch (Throwable $e) {

    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    $_SESSION["bulk_message"] = [
        "type" => "error",
        "text" => "Bulk delete failed. Please try again."
    ];
}

header("Location: index.php");
exit;