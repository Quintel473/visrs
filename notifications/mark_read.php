<?php

/*
 * VISRS Notification Mark-Read Endpoint
 *
 * POST /visrs/notifications/mark_read.php
 * Body: notification_id=<int>  (optional)
 *
 * If notification_id is omitted, marks ALL as read.
 */

require_once __DIR__ . "/../includes/auth.php";
require_once __DIR__ . "/../includes/database.php";
require_once __DIR__ . "/../includes/functions.php";

header("Content-Type: application/json");

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    http_response_code(405);
    echo json_encode(["ok" => false, "error" => "POST required"]);
    exit;
}

/* CSRF */
requireValidCSRF();

$userId = (int) currentUserId();

if ($userId <= 0) {
    http_response_code(403);
    echo json_encode(["ok" => false, "error" => "Not authenticated"]);
    exit;
}

$notificationId = (int) ($_POST["notification_id"] ?? 0);

if ($notificationId > 0) {
    markNotificationRead($pdo, $notificationId, $userId);
} else {
    markAllNotificationsRead($pdo, $userId);
}

$remaining = unreadNotificationCount($pdo, $userId);

echo json_encode([
    "ok" => true,
    "unread" => $remaining
]);