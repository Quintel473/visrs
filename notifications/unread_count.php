<?php

/*
 * VISRS Notification Unread Count
 *
 * GET /visrs/notifications/unread_count.php
 * Returns: { "unread": N }
 */

require_once __DIR__ . "/../includes/auth.php";
require_once __DIR__ . "/../includes/database.php";
require_once __DIR__ . "/../includes/functions.php";

header("Content-Type: application/json");

$userId = (int) currentUserId();

if ($userId <= 0) {
    echo json_encode(["unread" => 0]);
    exit;
}

$unread = unreadNotificationCount($pdo, $userId);

echo json_encode(["unread" => $unread]);