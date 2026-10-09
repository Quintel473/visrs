<?php

/*
 * VISRS Bulk Delete — Ownership History
 *
 * Handles batch deletion of selected ownership records.
 *
 * Rules:
 *   - Only Admin / Police can bulk delete (matches $canManage).
 *   - Historical records (with an EndDate) are safe to delete.
 *   - Current-ownership records (no EndDate) are SKIPPED because
 *     deleting them would leave the vehicle without a current owner.
 *   - All deletions and skips are logged in the audit trail.
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
        "text" => "No ownership records were selected."
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
        "text" => "No valid ownership IDs were provided."
    ];

    header("Location: index.php");
    exit;
}


$deletedCount = 0;
$skippedCount = 0;
$skippedItems = [];


try {

    $pdo->beginTransaction();

    /*
     * Process each ownership record.
     */
    foreach ($ids as $ownershipId) {

        /* Fetch record details */
        $stmt = $pdo->prepare("
            SELECT
                oh.OwnerID,
                oh.VehicleID,
                oh.StartDate,
                oh.EndDate,
                v.PlateNumber,
                o.FirstName,
                o.LastName
            FROM ownership_history oh
            INNER JOIN vehicles v
                ON oh.VehicleID = v.VehicleID
            INNER JOIN owners o
                ON oh.OwnerID = o.OwnerID
            WHERE oh.OwnershipID = ?
            LIMIT 1
        ");
        $stmt->execute([$ownershipId]);
        $record = $stmt->fetch();

        if (!$record) {
            $skippedCount++;
            continue;
        }

        $plate = $record["PlateNumber"];
        $ownerName = trim($record["FirstName"] . " " . $record["LastName"]);

        /*
         * Skip CURRENT ownership records (no EndDate).
         * Deleting them would leave the vehicle without a
         * current owner.
         */

        if ($record["EndDate"] === null || $record["EndDate"] === "") {

            $skippedCount++;
            $skippedItems[] =
                $plate . " — " . $ownerName . " (current owner)";
            continue;
        }

        /*
         * Safe to delete — historical record.
         */

        $del = $pdo->prepare("DELETE FROM ownership_history WHERE OwnershipID = ?");
        $del->execute([$ownershipId]);

        if ($del->rowCount() === 1) {
            $deletedCount++;

            logAudit(
                $pdo,
                "Bulk deleted ownership record for " . $plate . " (owner: " . $ownerName . ")",
                "ownership_history",
                $ownershipId
            );
        } else {
            $skippedCount++;
        }
    }

    $pdo->commit();

    /* Build flash message */
    $parts = [];

    if ($deletedCount > 0) {
        $parts[] = $deletedCount . " ownership record(s) deleted.";
    }

    if ($skippedCount > 0) {
        $parts[] = $skippedCount . " skipped (current owner records cannot be bulk-deleted).";
    }

    if (empty($parts)) {
        $parts[] = "No ownership records were deleted.";
    }

    $_SESSION["bulk_message"] = [
        "type" => $deletedCount > 0 ? "success" : "warning",
        "text" => implode(" ", $parts),
        "skipped" => $skippedItems
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