<?php

/*
 * VISRS Bulk Delete — Users
 *
 * Handles batch deletion of selected user accounts.
 *
 * Rules:
 *   - Only Admin can bulk delete (matches requireRole(["Admin"])).
 *   - You cannot delete your own account.
 *   - Users with existing audit log entries are SKIPPED — the
 *     audit trail must remain intact.
 *   - All deletions and skips are logged in the audit trail.
 */

require_once __DIR__ . "/../includes/auth.php";
require_once __DIR__ . "/../includes/database.php";
require_once __DIR__ . "/../includes/functions.php";

/* Access control */
requireRole(["Admin"]);

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
        "text" => "No users were selected."
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
        "text" => "No valid user IDs were provided."
    ];

    header("Location: index.php");
    exit;
}


$currentUserID = (int) ($_SESSION["UserID"] ?? 0);

$deletedCount = 0;
$skippedCount = 0;
$skippedItems = [];


try {

    $pdo->beginTransaction();

    /*
     * Process each user.
     */
    foreach ($ids as $userId) {

        /* Fetch user info for logging */
        $stmt = $pdo->prepare("
            SELECT FirstName, LastName, Email
            FROM users
            WHERE UserID = ?
            LIMIT 1
        ");
        $stmt->execute([$userId]);
        $user = $stmt->fetch();

        if (!$user) {
            $skippedCount++;
            continue;
        }

        $userName = trim($user["FirstName"] . " " . $user["LastName"]);
        if ($userName === "") {
            $userName = $user["Email"];
        }

        /*
         * Rule 1: Cannot delete yourself
         */

        if ($userId === $currentUserID) {

            $skippedCount++;
            $skippedItems[] = $userName . " (your own account)";
            continue;
        }

        /*
         * Rule 2: Cannot delete users with audit logs
         */

        $check = $pdo->prepare("
            SELECT COUNT(*)
            FROM audit_logs
            WHERE UserID = ?
        ");
        $check->execute([$userId]);
        $auditCount = (int) $check->fetchColumn();

        if ($auditCount > 0) {

            $skippedCount++;
            $skippedItems[] =
                $userName
                . " (has " . $auditCount . " audit record"
                . ($auditCount === 1 ? "" : "s")
                . ")";
            continue;
        }

        /*
         * Safe to delete.
         */

        $del = $pdo->prepare("DELETE FROM users WHERE UserID = ?");
        $del->execute([$userId]);

        if ($del->rowCount() === 1) {
            $deletedCount++;

            logAudit(
                $pdo,
                "Bulk deleted user account: " . $userName,
                "users",
                $userId
            );
        } else {
            $skippedCount++;
        }
    }

    $pdo->commit();

    /* Build flash message */
    $parts = [];

    if ($deletedCount > 0) {
        $parts[] = $deletedCount . " user(s) deleted.";
    }

    if ($skippedCount > 0) {
        $parts[] = $skippedCount . " skipped (self-delete or audit trail protection).";
    }

    if (empty($parts)) {
        $parts[] = "No users were deleted.";
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