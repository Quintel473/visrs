<?php

/*
 * VISRS Bulk Delete — Insurance
 *
 * Handles batch deletion of selected insurance records.
 *
 * Rules:
 *   - Only Admin / Police can bulk delete (matches canManageInsurance).
 *   - Insurance records are safe to bulk-delete — no dependencies.
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
        "text" => "No insurance records were selected."
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
        "text" => "No valid insurance IDs were provided."
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
     * Delete each insurance record.
     */
    foreach ($ids as $insuranceId) {

        /* Fetch policy number for logging */
        $stmt = $pdo->prepare("
            SELECT PolicyNumber
            FROM insurance
            WHERE InsuranceID = ?
            LIMIT 1
        ");
        $stmt->execute([$insuranceId]);
        $record = $stmt->fetch();

        if (!$record) {
            $skippedCount++;
            continue;
        }

        $policy = $record["PolicyNumber"];

        /* Delete */
        $del = $pdo->prepare("DELETE FROM insurance WHERE InsuranceID = ?");
        $del->execute([$insuranceId]);

        if ($del->rowCount() === 1) {
            $deletedCount++;

            logAudit(
                $pdo,
                "Bulk deleted insurance policy: " . $policy,
                "insurance",
                $insuranceId
            );
        } else {
            $skippedCount++;
            $skippedItems[] = $policy;
        }
    }

    $pdo->commit();

    /* Build flash message */
    $parts = [];

    if ($deletedCount > 0) {
        $parts[] = $deletedCount . " insurance record(s) deleted.";
    }

    if ($skippedCount > 0) {
        $parts[] = $skippedCount . " skipped.";
    }

    if (empty($parts)) {
        $parts[] = "No insurance records were deleted.";
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