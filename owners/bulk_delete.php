<?php

/*
 * VISRS Bulk Delete — Owners
 *
 * Handles batch deletion of selected owners.
 *
 * Rules:
 *   - Only Admin / Police can bulk delete (matches canManageOwners).
 *   - Each owner is checked for related vehicles before deletion.
 *   - Owners who own one or more vehicles are SKIPPED.
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
        "text" => "No owners were selected."
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
        "text" => "No valid owner IDs were provided."
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
     * For each owner, check for related vehicles first.
     */
    foreach ($ids as $ownerId) {

        /* Fetch name for logging + skip message */
        $stmt = $pdo->prepare("
            SELECT FirstName, LastName
            FROM owners
            WHERE OwnerID = ?
            LIMIT 1
        ");
        $stmt->execute([$ownerId]);
        $owner = $stmt->fetch();

        if (!$owner) {
            /* Already gone — count as skipped silently */
            $skippedCount++;
            continue;
        }

        $ownerName = trim($owner["FirstName"] . " " . $owner["LastName"]);

        /* Check for related vehicles */
        $check = $pdo->prepare("
            SELECT COUNT(*)
            FROM vehicles
            WHERE OwnerID = ?
        ");
        $check->execute([$ownerId]);
        $vehicleCount = (int) $check->fetchColumn();

        if ($vehicleCount > 0) {
            $skippedCount++;
            $skippedItems[] =
                $ownerName
                . " (owns " . $vehicleCount . " vehicle"
                . ($vehicleCount === 1 ? "" : "s")
                . ")";
            continue;
        }

        /* Also check ownership_history in case they have past records */
        $check = $pdo->prepare("
            SELECT COUNT(*)
            FROM ownership_history
            WHERE OwnerID = ?
        ");
        $check->execute([$ownerId]);
        $historyCount = (int) $check->fetchColumn();

        if ($historyCount > 0) {
            $skippedCount++;
            $skippedItems[] =
                $ownerName
                . " (has " . $historyCount . " ownership record"
                . ($historyCount === 1 ? "" : "s")
                . ")";
            continue;
        }

        /* Safe to delete */
        $del = $pdo->prepare("DELETE FROM owners WHERE OwnerID = ?");
        $del->execute([$ownerId]);

        if ($del->rowCount() === 1) {
            $deletedCount++;

            /* Audit each deletion */
            logAudit(
                $pdo,
                "Bulk deleted owner: " . $ownerName,
                "owners",
                $ownerId
            );
        }
    }

    $pdo->commit();

    /* Build flash message */
    $parts = [];

    if ($deletedCount > 0) {
        $parts[] = $deletedCount . " owner(s) deleted.";
    }

    if ($skippedCount > 0) {
        $parts[] = $skippedCount . " skipped (have related records).";
    }

    if (empty($parts)) {
        $parts[] = "No owners were deleted.";
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