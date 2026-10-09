<?php

require_once __DIR__ . "/../includes/auth.php";
require_once __DIR__ . "/../includes/database.php";
require_once __DIR__ . "/../includes/functions.php";

$basePath = "../";
$activePage = "notifications";

$pageTitle = "Notifications";

$userId = (int) currentUserId();

/* Load all notifications for this user */
$stmt = $pdo->prepare("
    SELECT
        NotificationID,
        Type,
        Title,
        Message,
        Link,
        IsRead,
        CreatedAt
    FROM notifications
    WHERE UserID = ?
    ORDER BY CreatedAt DESC
    LIMIT 100
");
$stmt->execute([$userId]);
$notifications = $stmt->fetchAll();

require_once "../includes/head.php";

?>

<style>

    .notif-page-title {
        margin: 0 0 6px 0;
        font-size: 25px;
        font-weight: 700;
        color: #111827;
    }

    .notif-page-subtitle {
        margin: 0 0 20px 0;
        color: #6b7280;
        font-size: 14px;
    }

    .notif-list {
        display: flex;
        flex-direction: column;
        gap: 10px;
    }

    .notif-item {
        display: flex;
        gap: 14px;

        padding: 16px 18px;

        background: #ffffff;
        border: 1px solid #e5e7eb;
        border-left: 4px solid #e5e7eb;
        border-radius: 10px;

        text-decoration: none;
        color: inherit;

        transition: background 0.15s ease, border-color 0.15s ease;
    }

    .notif-item:hover {
        background: #f9fafb;
    }

    .notif-item.unread {
        background: #eff6ff;
        border-left-color: #2563eb;
    }

    .notif-item.unread:hover {
        background: #dbeafe;
    }

    .notif-icon {
        font-size: 22px;
        line-height: 1;
        flex-shrink: 0;
    }

    .notif-content {
        flex: 1;
        min-width: 0;
    }

    .notif-title {
        margin: 0 0 4px 0;
        font-size: 14px;
        font-weight: 700;
        color: #111827;
    }

    .notif-message {
        margin: 0 0 6px 0;
        font-size: 13px;
        color: #4b5563;
        line-height: 1.5;
    }

    .notif-time {
        font-size: 11px;
        color: #9ca3af;
    }

    .notif-empty {
        text-align: center;
        padding: 60px 20px;
        color: #6b7280;
    }

    .notif-empty-icon {
        font-size: 48px;
        margin-bottom: 12px;
    }

    /* Dark mode */
    [data-theme="dark"] .notif-page-title {
        color: #f1f5f9;
    }

    [data-theme="dark"] .notif-page-subtitle {
        color: #94a3b8;
    }

    [data-theme="dark"] .notif-item {
        background: #1e293b;
        border-color: #334155;
        border-left-color: #334155;
        color: #e2e8f0;
    }

    [data-theme="dark"] .notif-item:hover {
        background: #334155;
    }

    [data-theme="dark"] .notif-item.unread {
        background: #1e3a8a;
        border-left-color: #60a5fa;
    }

    [data-theme="dark"] .notif-item.unread:hover {
        background: #1e40af;
    }

    [data-theme="dark"] .notif-title {
        color: #f1f5f9;
    }

    [data-theme="dark"] .notif-message {
        color: #cbd5e1;
    }

    [data-theme="dark"] .notif-time {
        color: #64748b;
    }

</style>

<body>

<div class="layout">

    <?php require_once __DIR__ . "/../includes/sidebar.php"; ?>

    <main class="main-content">

        <?php include __DIR__ . "/../includes/header.php"; ?>

        <div class="content">

            <h1 class="notif-page-title">
                Notifications
            </h1>

            <p class="notif-page-subtitle">
                Your recent activity and alerts.
            </p>


            <?php if (empty($notifications)): ?>

                <div class="notif-empty">

                    <div class="notif-empty-icon">🔔</div>

                    <h3>No notifications yet</h3>

                    <p>You're all caught up.</p>

                </div>

            <?php else: ?>

                <div class="notif-list">

                    <?php foreach ($notifications as $n): ?>

                        <?php
                        $isUnread = ((int) $n["IsRead"] === 0);

                        $icon = "🔔";
                        switch ($n["Type"]) {
                            case "inquiry":    $icon = "📩"; break;
                            case "message":    $icon = "💬"; break;
                            case "status":     $icon = "🔄"; break;
                            case "insurance":  $icon = "🛡️"; break;
                            case "vehicle":    $icon = "🚗"; break;
                        }
                        ?>

                        <a
                            href="<?= $n["Link"]
                                ? htmlspecialchars($n["Link"])
                                : "#"
                            ?>"
                            class="notif-item <?= $isUnread ? "unread" : "" ?>"
                            data-notification-id="<?= (int) $n["NotificationID"] ?>"
                        >

                            <span class="notif-icon">
                                <?= $icon ?>
                            </span>

                            <div class="notif-content">

                                <p class="notif-title">
                                    <?= htmlspecialchars($n["Title"]) ?>
                                </p>

                                <p class="notif-message">
                                    <?= htmlspecialchars($n["Message"]) ?>
                                </p>

                                <span class="notif-time">
                                    <?= htmlspecialchars(
                                        date(
                                            "M j, Y g:i A",
                                            strtotime($n["CreatedAt"])
                                        )
                                    ) ?>
                                </span>

                            </div>

                        </a>

                    <?php endforeach; ?>

                </div>

            <?php endif; ?>

        </div>

    </main>

</div>


<script>

/* When a notification is clicked, mark it as read (fire and forget) */
document.querySelectorAll('.notif-item').forEach(function (item) {

    item.addEventListener('click', function () {

        var id = this.getAttribute('data-notification-id');
        if (!id) return;

        var csrf = '<?= htmlspecialchars(generateCSRFToken()) ?>';

        fetch('/visrs/notifications/mark_read.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'notification_id=' + encodeURIComponent(id)
             + '&csrf_token=' + encodeURIComponent(csrf),
            keepalive: true
        });

    });
});

</script>


<?php require_once __DIR__ . "/../includes/footer.php"; ?>