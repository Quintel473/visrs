<?php

require_once __DIR__ . "/../includes/auth.php";
requireRole(["Admin"]);

require_once __DIR__ . "/../includes/database.php";
require_once __DIR__ . "/../includes/functions.php";

$basePath = "../";
$activePage = "audit_logs";
$pageTitle = "Audit Trail";


/*
 * Search
 */

$search = trim($_GET["search"] ?? "");


/*
 * Build audit log query.
 *
 * We join the users table so the administrator
 * can see which user performed each action.
 */

/* Pagination setup */
$perPage = 25;

if ($search !== "") {

    $searchValue = "%" . $search . "%";

    /* Count matching rows */
    $countStmt = $pdo->prepare("
        SELECT COUNT(*)
        FROM audit_logs a
        LEFT JOIN users u
            ON a.UserID = u.UserID
        WHERE
            a.Action LIKE ?
            OR a.TableAffected LIKE ?
            OR a.IPAddress LIKE ?
            OR u.FirstName LIKE ?
            OR u.LastName LIKE ?
            OR u.Email LIKE ?
    ");

    $countStmt->execute([
        $searchValue,
        $searchValue,
        $searchValue,
        $searchValue,
        $searchValue,
        $searchValue
    ]);

    $totalMatchingLogs = (int) $countStmt->fetchColumn();

    /* Pagination metadata */
    $pagination = paginationMeta($totalMatchingLogs, $perPage);

    /* Load current page */
    $stmt = $pdo->prepare("
        SELECT
            a.LogID,
            a.UserID,
            a.Action,
            a.TableAffected,
            a.RecordID,
            a.IPAddress,
            a.CreatedAt,
            u.FirstName,
            u.LastName,
            u.Email,
            u.Role
        FROM audit_logs a
        LEFT JOIN users u
            ON a.UserID = u.UserID
        WHERE
            a.Action LIKE ?
            OR a.TableAffected LIKE ?
            OR a.IPAddress LIKE ?
            OR u.FirstName LIKE ?
            OR u.LastName LIKE ?
            OR u.Email LIKE ?
        ORDER BY a.CreatedAt DESC, a.LogID DESC
        LIMIT ? OFFSET ?
    ");

    $stmt->bindValue(1, $searchValue, PDO::PARAM_STR);
    $stmt->bindValue(2, $searchValue, PDO::PARAM_STR);
    $stmt->bindValue(3, $searchValue, PDO::PARAM_STR);
    $stmt->bindValue(4, $searchValue, PDO::PARAM_STR);
    $stmt->bindValue(5, $searchValue, PDO::PARAM_STR);
    $stmt->bindValue(6, $searchValue, PDO::PARAM_STR);
    $stmt->bindValue(7, $pagination["perPage"], PDO::PARAM_INT);
    $stmt->bindValue(8, $pagination["offset"],  PDO::PARAM_INT);

    $stmt->execute();

} else {

    /* Count all rows */
    $countStmt = $pdo->query("SELECT COUNT(*) FROM audit_logs");
    $totalAllLogs = (int) $countStmt->fetchColumn();

    /* Pagination metadata */
    $pagination = paginationMeta($totalAllLogs, $perPage);

    /* Load current page */
    $stmt = $pdo->prepare("
        SELECT
            a.LogID,
            a.UserID,
            a.Action,
            a.TableAffected,
            a.RecordID,
            a.IPAddress,
            a.CreatedAt,
            u.FirstName,
            u.LastName,
            u.Email,
            u.Role
        FROM audit_logs a
        LEFT JOIN users u
            ON a.UserID = u.UserID
        ORDER BY a.CreatedAt DESC, a.LogID DESC
        LIMIT ? OFFSET ?
    ");

    $stmt->bindValue(1, $pagination["perPage"], PDO::PARAM_INT);
    $stmt->bindValue(2, $pagination["offset"],  PDO::PARAM_INT);

    $stmt->execute();

}


$logs = $stmt->fetchAll();


/*
 * Count total logs.
 */

$countStmt = $pdo->query("
    SELECT COUNT(*)
    FROM audit_logs
");

$totalLogs = (int) $countStmt->fetchColumn();


/*
 * Count today's logs.
 */

$todayStmt = $pdo->query("
    SELECT COUNT(*)
    FROM audit_logs
    WHERE DATE(CreatedAt) = CURDATE()
");

$todayLogs = (int) $todayStmt->fetchColumn();


/*
 * Count distinct users who have performed
 * logged actions.
 */

$usersStmt = $pdo->query("
    SELECT COUNT(DISTINCT UserID)
    FROM audit_logs
    WHERE UserID IS NOT NULL
");

$activeUsers = (int) $usersStmt->fetchColumn();


/*
 * FIX: Global <head> (was inline <!DOCTYPE> + <head> block,
 * with hardcoded /visrs/css/style.css path).
 */

require_once "../includes/head.php";

?>

<style>
    /* =========================================================
       AUDIT TIMELINE STYLES
       ========================================================= */
    
    .audit-timeline {
        position: relative;
        margin-top: 20px;
        padding-left: 30px;
    }

    /* The vertical line */
    .audit-timeline::before {
        content: '';
        position: absolute;
        top: 0;
        bottom: 0;
        left: 12px;
        width: 2px;
        background: #e5e7eb;
    }

    .audit-item {
        position: relative;
        margin-bottom: 25px;
    }

    .audit-item:last-child {
        margin-bottom: 0;
    }

    /* Restored to your original exact styling */
    .audit-dot {
        position: absolute;
        left: -31px;
        top: 4px;
        width: 14px;
        height: 14px;
        border-radius: 50%;
        background: #2563eb; /* Default blue */
        border: 3px solid #ffffff;
        box-shadow: 0 0 0 3px #e5e7eb;
        z-index: 2;
    }

    /* Dot colors based on action */
    .action-create { background: #10b981 !important; } /* Green */
    .action-delete { background: #ef4444 !important; } /* Red */
    .action-login  { background: #3b82f6 !important; } /* Blue */
    .action-update { background: #f59e0b !important; } /* Amber */

    /* The content card */
    .audit-content {
        background: #ffffff;
        border: 1px solid #e5e7eb;
        border-radius: 10px;
        padding: 16px 20px;
        box-shadow: 0 1px 2px rgba(0,0,0,0.03);
    }

    .audit-header {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        gap: 15px;
        margin-bottom: 8px;
    }

    .audit-title {
        margin: 0;
        font-size: 15px;
        font-weight: 700;
        color: #111827;
    }

    .audit-time {
        font-size: 12px;
        color: #6b7280;
        white-space: nowrap;
    }

    .audit-details {
        margin: 0 0 12px 0;
        font-size: 13px;
        color: #374151;
        line-height: 1.6;
    }

    .audit-meta {
        display: flex;
        gap: 15px;
        padding-top: 12px;
        border-top: 1px solid #f3f4f6;
        font-size: 12px;
        color: #6b7280;
        /* Ensure IP address doesn't break layout */
        word-break: break-all; 
    }

    .audit-meta strong {
        color: #374151;
    }

    /* =========================================================
       DARK MODE OVERRIDES
       ========================================================= */

    [data-theme="dark"] .audit-timeline::before {
        background: #334155;
    }

    [data-theme="dark"] .audit-dot {
        border-color: #1e293b;
        box-shadow: 0 0 0 3px #334155;
    }

    [data-theme="dark"] .audit-content {
        background: #1e293b;
        border-color: #334155;
    }

    [data-theme="dark"] .audit-title {
        color: #f8fafc;
    }

    [data-theme="dark"] .audit-time {
        color: #94a3b8;
    }

    [data-theme="dark"] .audit-details {
        color: #cbd5e1;
    }

    [data-theme="dark"] .audit-meta {
        border-top-color: #334155;
        color: #94a3b8;
    }

    [data-theme="dark"] .audit-meta strong {
        color: #e2e8f0;
    }

    [data-theme="dark"] .stat-card {
        background: #1e293b;
        border-color: #334155;
    }

    [data-theme="dark"] .stat-card h3 {
        color: #94a3b8;
    }

    [data-theme="dark"] .stat-number {
        color: #f8fafc;
    }

    [data-theme="dark"] .stat-card p {
        color: #cbd5e1;
    }

    /* Fix the empty state text color */
    [data-theme="dark"] h3[style*="color:#374151"] {
        color: #f8fafc !important;
    }
</style>

<body>

<div class="layout">

    <?php include __DIR__ . "/../includes/sidebar.php"; ?>

    <main class="main-content">

        <?php include __DIR__ . "/../includes/header.php"; ?>

        <div class="content">

            <!-- PAGE INTRODUCTION -->

            <div style="margin-bottom:20px;">

                <h1 class="page-title" style="margin-bottom:6px;">
                    Audit Trail
                </h1>

                <p class="page-subtitle" style="margin:0;">
                    Monitor and review activity performed within the VISRS system.
                </p>

            </div>


            <!-- STATISTICS -->

            <div class="stats-grid">

                <div class="stat-card">
                    <h3>Total Activities</h3>
                    <div class="stat-number"><?= $totalLogs ?></div>
                    <p>Recorded system activities</p>
                </div>

                <div class="stat-card">
                    <h3>Today's Activities</h3>
                    <div class="stat-number"><?= $todayLogs ?></div>
                    <p>Activities recorded today</p>
                </div>

                <div class="stat-card">
                    <h3>Active Users</h3>
                    <div class="stat-number"><?= $activeUsers ?></div>
                    <p>Users with recorded activity</p>
                </div>

            </div>


            <!-- AUDIT LOG CARD -->

            <div class="card" style="margin-top:20px;">

                <!-- CARD HEADER -->

                <div style="display:flex; justify-content:space-between; align-items:center; gap:15px; flex-wrap:wrap; margin-bottom:20px;">

                    <div>
                        <h2 style="margin:0 0 5px 0;">System Activity</h2>
                        <p style="margin:0; color:#6b7280;">Review actions performed by VISRS users.</p>
                    </div>

                    <?php if ($search !== ""): ?>
                        <a href="/visrs/audit_logs/" class="button button-secondary">Clear Search</a>
                    <?php endif; ?>

                </div>


                <!-- SEARCH -->

                <form method="GET" action="" style="display:flex; gap:12px; margin-bottom:25px; flex-wrap:wrap;">

                    <input
                        type="text"
                        name="search"
                        value="<?= htmlspecialchars($search) ?>"
                        placeholder="Search activity, user, table or IP address..."
                        style="flex:1; min-width:250px;"
                    >

                    <button type="submit" class="button">Search</button>

                </form>


                <!-- TIMELINE -->

                <?php if (count($logs) > 0): ?>

                    <div class="audit-timeline">

                        <?php foreach ($logs as $log): 
                            
                            /* Determine color based on action */
                            $actionText = strtolower($log["Action"]);
                            $dotClass = "action-update"; // Default (Amber)
                            
                            if (strpos($actionText, 'create') !== false || strpos($actionText, 'add') !== false || strpos($actionText, 'insert') !== false) {
                                $dotClass = "action-create"; // Green
                            } elseif (strpos($actionText, 'delete') !== false || strpos($actionText, 'remove') !== false) {
                                $dotClass = "action-delete"; // Red
                            } elseif (strpos($actionText, 'login') !== false || strpos($actionText, 'auth') !== false) {
                                $dotClass = "action-login"; // Blue
                            }

                            /* Format user name */
                            $userName = $log["FirstName"] 
                                ? htmlspecialchars($log["FirstName"] . " " . $log["LastName"]) 
                                : "Unknown User";
                                
                            $userRole = $log["Role"] ? htmlspecialchars($log["Role"]) : "System";
                            
                            /* Format Time */
                            $logTime = date("M d, Y • h:i A", strtotime($log["CreatedAt"]));

                            /* Format IP (Localhost check) */
                            $ipDisplay = htmlspecialchars($log["IPAddress"] ?? "—");
                            if ($ipDisplay === "::1") {
                                $ipDisplay = "::1 (Localhost)";
                            }
                        ?>

                            <div class="audit-item">
                                
                                <!-- Colored dot -->
                                <div class="audit-dot <?= $dotClass ?>"></div>
                                
                                <!-- Content card -->
                                <div class="audit-content">
                                    
                                    <div class="audit-header">
                                        <h3 class="audit-title">
                                            <?= htmlspecialchars($log["Action"]) ?>
                                        </h3>
                                        <span class="audit-time">
                                            <?= $logTime ?>
                                        </span>
                                    </div>

                                    <p class="audit-details">
                                        <strong><?= $userName ?></strong> 
                                        (<?= $userRole ?>) performed this action.
                                        <?php if (!empty($log["TableAffected"])): ?>
                                            <br>
                                            Affected Table: <strong><?= htmlspecialchars($log["TableAffected"]) ?></strong>
                                            <?php if ($log["RecordID"] !== null): ?>
                                                (Record #<?= (int) $log["RecordID"] ?>)
                                            <?php endif; ?>
                                        <?php endif; ?>
                                    </p>

                                    <div class="audit-meta">
                                        <span>ID: <strong>#<?= (int) $log["LogID"] ?></strong></span>
                                        <span>IP: <strong><?= $ipDisplay ?></strong></span>
                                    </div>

                                </div>
                            </div>

                        <?php endforeach; ?>

                    </div>

                    <?php renderPagination($pagination); ?>


                <?php else: ?>


                    <!-- EMPTY STATE -->

                    <div style="text-align:center; padding:50px 20px; color:#6b7280;">

                        <div style="font-size:42px; margin-bottom:15px;">📊</div>

                        <h3 style="margin:0 0 8px 0; color:#374151;">No Audit Records Found</h3>

                        <p style="margin:0;">
                            <?php if ($search !== ""): ?>
                                No activity records matched your search.
                            <?php else: ?>
                                No system activity has been recorded yet.
                            <?php endif; ?>
                        </p>

                    </div>


                <?php endif; ?>

            </div>

        </div>

    </main>

</div>


<?php include __DIR__ . "/../includes/footer.php"; ?>