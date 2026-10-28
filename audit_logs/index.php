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


<body>


<div class="layout">


    <?php

    include __DIR__ . "/../includes/sidebar.php";

    ?>


    <main class="main-content">


        <?php

        include __DIR__ . "/../includes/header.php";

        ?>


        <div class="content">


            <!-- PAGE INTRODUCTION -->

            <div
                style="
                    margin-bottom:20px;
                "
            >

                <h1
                    class="page-title"
                    style="
                        margin-bottom:6px;
                    "
                >
                    Audit Trail
                </h1>

                <p
                    class="page-subtitle"
                    style="
                        margin:0;
                    "
                >
                    Monitor and review activity performed within the VISRS system.
                </p>

            </div>


            <!-- STATISTICS -->

            <div class="stats-grid">


                <div class="stat-card">

                    <h3>
                        Total Activities
                    </h3>

                    <div class="stat-number">
                        <?= $totalLogs ?>
                    </div>

                    <p>
                        Recorded system activities
                    </p>

                </div>


                <div class="stat-card">

                    <h3>
                        Today's Activities
                    </h3>

                    <div class="stat-number">
                        <?= $todayLogs ?>
                    </div>

                    <p>
                        Activities recorded today
                    </p>

                </div>


                <div class="stat-card">

                    <h3>
                        Active Users
                    </h3>

                    <div class="stat-number">
                        <?= $activeUsers ?>
                    </div>

                    <p>
                        Users with recorded activity
                    </p>

                </div>


            </div>


            <!-- AUDIT LOG CARD -->

            <div
                class="card"
                style="
                    margin-top:20px;
                "
            >


                <!-- CARD HEADER -->

                <div
                    style="
                        display:flex;
                        justify-content:space-between;
                        align-items:center;
                        gap:15px;
                        flex-wrap:wrap;
                        margin-bottom:20px;
                    "
                >

                    <div>

                        <h2
                            style="
                                margin:0 0 5px 0;
                            "
                        >
                            System Activity
                        </h2>

                        <p
                            style="
                                margin:0;
                                color:#6b7280;
                            "
                        >
                            Review actions performed by VISRS users.
                        </p>

                    </div>


                    <?php if ($search !== ""): ?>

                        <a
                            href="/visrs/audit_logs/"
                            class="button button-secondary"
                        >
                            Clear Search
                        </a>

                    <?php endif; ?>


                </div>


                <!-- SEARCH -->

                <form
                    method="GET"
                    action=""
                    style="
                        display:flex;
                        gap:12px;
                        margin-bottom:25px;
                        flex-wrap:wrap;
                    "
                >

                    <input
                        type="text"
                        name="search"
                        value="<?= htmlspecialchars($search) ?>"
                        placeholder="Search activity, user, table or IP address..."
                        style="
                            flex:1;
                            min-width:250px;
                        "
                    >


                    <button
                        type="submit"
                        class="button"
                    >
                        Search
                    </button>

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
                                        <span>IP: <strong><?= htmlspecialchars($log["IPAddress"] ?? "—") ?></strong></span>
                                    </div>

                                </div>
                            </div>

                        <?php endforeach; ?>

                    </div>

                    <?php renderPagination($pagination); ?>


                <?php else: ?>


                    <!-- EMPTY STATE -->

                    <div
                        style="
                            text-align:center;
                            padding:50px 20px;
                            color:#6b7280;
                        "
                    >

                        <div
                            style="
                                font-size:42px;
                                margin-bottom:15px;
                            "
                        >
                            📊
                        </div>

                        <h3
                            style="
                                margin:0 0 8px 0;
                                color:#374151;
                            "
                        >
                            No Audit Records Found
                        </h3>

                        <p
                            style="
                                margin:0;
                            "
                        >
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


<?php

include __DIR__ . "/../includes/footer.php";

?>