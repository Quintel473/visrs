<?php

/*
 * VISRS Bulk Delete — Vehicles
 *
 * Handles batch deletion of selected vehicles.
 *
 * Rules:
 *   - Only Admin / Police / Seller can bulk delete (matches canManageVehicles).
 *   - Each vehicle is checked for related records before deletion.
 *   - Records with related data (insurance, accidents, ownership history)
 *     are SKIPPED, not deleted.
 *   - All deletions and skips are logged in the audit trail.
 */

require_once __DIR__ . "/../includes/auth.php";
require_once __DIR__ . "/../includes/database.php";
require_once __DIR__ . "/../includes/functions.php";

/* Access control */
requireRole(["Admin", "Police", "Seller"]);

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
        "text" => "No vehicles were selected."
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
        "text" => "No valid vehicle IDs were provided."
    ];

    header("Location: index.php");
    exit;
}

/* Prepare in-clause placeholders */
$placeholders = implode(",", array_fill(0, count($ids), "?"));

$deletedCount = 0;
$skippedCount = 0;
$skippedItems = [];


try {

    $pdo->beginTransaction();

    /*
     * For each vehicle, check for related records first.
     */
    foreach ($ids as $vehicleId) {

        /* Fetch plate for logging + skip message */
        $stmt = $pdo->prepare("
            SELECT PlateNumber
            FROM vehicles
            WHERE VehicleID = ?
            LIMIT 1
        ");
        $stmt->execute([$vehicleId]);
        $vehicle = $stmt->fetch();

        if (!$vehicle) {
            /* Already gone — count as skipped but silently */
            $skippedCount++;
            continue;
        }

        $plate = $vehicle["PlateNumber"];

        /* Check related records */
        $related = [];

        $check = $pdo->prepare("SELECT COUNT(*) FROM insurance WHERE VehicleID = ?");
        $check->execute([$vehicleId]);
        if ((int) $check->fetchColumn() > 0) {
            $related[] = "insurance";
        }

        $check = $pdo->prepare("SELECT COUNT(*) FROM accidents WHERE VehicleID = ?");
        $check->execute([$vehicleId]);
        if ((int) $check->fetchColumn() > 0) {
            $related[] = "accidents";
        }

        $check = $pdo->prepare("SELECT COUNT(*) FROM ownership_history WHERE VehicleID = ?");
        $check->execute([$vehicleId]);
        if ((int) $check->fetchColumn() > 0) {
            $related[] = "ownership history";
        }

        if (!empty($related)) {
            $skippedCount++;
            $skippedItems[] = $plate . " (" . implode(", ", $related) . ")";
            continue;
        }

        /* Safe to delete */
        $del = $pdo->prepare("DELETE FROM vehicles WHERE VehicleID = ?");
        $del->execute([$vehicleId]);

        if ($del->rowCount() === 1) {
            $deletedCount++;

            /* Audit each deletion */
            logAudit(
                $pdo,
                "Bulk deleted vehicle: " . $plate,
                "vehicles",
                $vehicleId
            );
        }
    }

    $pdo->commit();

    /* Build flash message */
    $parts = [];

    if ($deletedCount > 0) {
        $parts[] = $deletedCount . " vehicle(s) deleted.";
    }

    if ($skippedCount > 0) {
        $parts[] = $skippedCount . " skipped (have related records).";
    }

    if (empty($parts)) {
        $parts[] = "No vehicles were deleted.";
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