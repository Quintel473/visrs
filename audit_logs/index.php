<?php

require_once __DIR__ . "/../includes/auth.php";
requireRole(["Admin"]);

require_once __DIR__ . "/../includes/database.php";

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

if ($search !== "") {

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
    ");

    $searchValue = "%" . $search . "%";

    $stmt->execute([
        $searchValue,
        $searchValue,
        $searchValue,
        $searchValue,
        $searchValue,
        $searchValue
    ]);

} else {

    $stmt = $pdo->query("
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
    ");

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

?>

<!DOCTYPE html>

<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>
        Audit Trail - VISRS
    </title>

    <link
        rel="stylesheet"
        href="/visrs/css/style.css"
    >

</head>


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


                <!-- TABLE -->

                <?php if (count($logs) > 0): ?>

                    <div
                        style="
                            overflow-x:auto;
                        "
                    >

                        <table
                            class="data-table"
                            style="
                                width:100%;
                            "
                        >

                            <thead>

                                <tr>

                                    <th>
                                        ID
                                    </th>

                                    <th>
                                        User
                                    </th>

                                    <th>
                                        Action
                                    </th>

                                    <th>
                                        Table
                                    </th>

                                    <th>
                                        Record ID
                                    </th>

                                    <th>
                                        IP Address
                                    </th>

                                    <th>
                                        Date & Time
                                    </th>

                                </tr>

                            </thead>


                            <tbody>

                                <?php foreach ($logs as $log): ?>

                                    <tr>


                                        <!-- LOG ID -->

                                        <td>

                                            <strong>
                                                #<?= (int) $log["LogID"] ?>
                                            </strong>

                                        </td>


                                        <!-- USER -->

                                        <td>

                                            <?php if ($log["FirstName"] !== null): ?>

                                                <strong>
                                                    <?= htmlspecialchars(
                                                        $log["FirstName"] .
                                                        " " .
                                                        $log["LastName"]
                                                    ) ?>
                                                </strong>

                                                <br>

                                                <small
                                                    style="
                                                        color:#6b7280;
                                                    "
                                                >
                                                    <?= htmlspecialchars(
                                                        $log["Role"] ?? ""
                                                    ) ?>
                                                </small>

                                            <?php else: ?>

                                                <span
                                                    style="
                                                        color:#6b7280;
                                                    "
                                                >
                                                    Unknown User
                                                </span>

                                            <?php endif; ?>

                                        </td>


                                        <!-- ACTION -->

                                        <td>

                                            <?= htmlspecialchars(
                                                $log["Action"]
                                            ) ?>

                                        </td>


                                        <!-- TABLE -->

                                        <td>

                                            <?php if (!empty($log["TableAffected"])): ?>

                                                <span
                                                    style="
                                                        display:inline-block;
                                                        padding:5px 9px;
                                                        border-radius:6px;
                                                        background:#f3f4f6;
                                                        color:#374151;
                                                        font-size:13px;
                                                        font-weight:600;
                                                    "
                                                >
                                                    <?= htmlspecialchars(
                                                        $log["TableAffected"]
                                                    ) ?>
                                                </span>

                                            <?php else: ?>

                                                <span
                                                    style="
                                                        color:#9ca3af;
                                                    "
                                                >
                                                    —
                                                </span>

                                            <?php endif; ?>

                                        </td>


                                        <!-- RECORD ID -->

                                        <td>

                                            <?php if ($log["RecordID"] !== null): ?>

                                                <?= (int) $log["RecordID"] ?>

                                            <?php else: ?>

                                                <span
                                                    style="
                                                        color:#9ca3af;
                                                    "
                                                >
                                                    —

                                                </span>

                                            <?php endif; ?>

                                        </td>


                                        <!-- IP ADDRESS -->

                                        <td>

                                            <?= htmlspecialchars(
                                                $log["IPAddress"] ?? "—"
                                            ) ?>

                                        </td>


                                        <!-- DATE -->

                                        <td>

                                            <strong>
                                                <?= htmlspecialchars(
                                                    date(
                                                        "M d, Y",
                                                        strtotime(
                                                            $log["CreatedAt"]
                                                        )
                                                    )
                                                ) ?>
                                            </strong>

                                            <br>

                                            <small
                                                style="
                                                    color:#6b7280;
                                                "
                                            >
                                                <?= htmlspecialchars(
                                                    date(
                                                        "h:i A",
                                                        strtotime(
                                                            $log["CreatedAt"]
                                                        )
                                                    )
                                                ) ?>
                                            </small>

                                        </td>


                                    </tr>

                                <?php endforeach; ?>

                            </tbody>

                        </table>

                    </div>


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


</body>

</html>