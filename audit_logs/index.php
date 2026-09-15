<?php

require_once "../includes/auth.php";
require_once "../includes/database.php";

$basePath = "../";
$activePage = "audit_logs";

requireRole(["Admin"]);


/*
 * Pagination settings.
 */

$recordsPerPage = 15;

$currentPage = max(
    1,
    (int) ($_GET["page"] ?? 1)
);

$offset = ($currentPage - 1) * $recordsPerPage;


/*
 * Get total number of audit records.
 */

$countStmt = $pdo->query("
    SELECT COUNT(*)
    FROM audit_logs
");

$totalRecords = (int) $countStmt->fetchColumn();

$totalPages = max(
    1,
    (int) ceil(
        $totalRecords / $recordsPerPage
    )
);


/*
 * Make sure requested page exists.
 */

if ($currentPage > $totalPages) {

    $currentPage = $totalPages;

    $offset = ($currentPage - 1) * $recordsPerPage;

}


/*
 * Load audit records with user information.
 */

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

    ORDER BY a.CreatedAt DESC

    LIMIT ? OFFSET ?
");

$stmt->bindValue(
    1,
    $recordsPerPage,
    PDO::PARAM_INT
);

$stmt->bindValue(
    2,
    $offset,
    PDO::PARAM_INT
);

$stmt->execute();

$logs = $stmt->fetchAll();

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Audit Trail - VISRS</title>

    <link
        rel="stylesheet"
        href="../css/style.css"
    >

    <style>

        .audit-summary {
            margin-bottom: 20px;
            color: #6b7280;
            font-size: 14px;
        }

        .audit-table-wrapper {
            width: 100%;
            overflow-x: auto;
        }

        .audit-table {
            width: 100%;
            border-collapse: collapse;
            min-width: 900px;
        }

        .audit-table th {
            padding: 13px 14px;
            text-align: left;
            background: #f8fafc;
            border-bottom: 1px solid #e5e7eb;
            color: #374151;
            font-size: 13px;
            font-weight: 600;
        }

        .audit-table td {
            padding: 14px;
            border-bottom: 1px solid #e5e7eb;
            color: #4b5563;
            font-size: 13px;
            vertical-align: middle;
        }

        .audit-table tr:hover {
            background: #fafafa;
        }

        .user-name {
            color: #1f2937;
            font-weight: 600;
        }

        .user-email {
            margin-top: 3px;
            color: #6b7280;
            font-size: 12px;
        }

        .role-badge {
            display: inline-block;
            padding: 5px 9px;
            border-radius: 6px;
            background: #eff6ff;
            color: #1d4ed8;
            font-size: 11px;
            font-weight: 600;
        }

        .action-text {
            color: #1f2937;
            font-weight: 500;
        }

        .table-badge {
            display: inline-block;
            padding: 5px 9px;
            border-radius: 6px;
            background: #f3f4f6;
            color: #374151;
            font-size: 11px;
            font-weight: 600;
        }

        .ip-address {
            font-family: monospace;
            color: #6b7280;
            font-size: 12px;
        }

        .empty-state {
            padding: 50px 20px;
            text-align: center;
            color: #6b7280;
        }

        .empty-state h3 {
            margin-bottom: 8px;
            color: #374151;
        }

        .pagination {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 8px;
            margin-top: 25px;
        }

        .pagination a,
        .pagination span {
            min-width: 36px;
            padding: 9px 12px;
            box-sizing: border-box;
            text-align: center;
            border: 1px solid #d1d5db;
            border-radius: 7px;
            color: #374151;
            background: #ffffff;
            text-decoration: none;
            font-size: 13px;
        }

        .pagination a:hover {
            background: #f8fafc;
        }

        .pagination .active {
            background: #2563eb;
            border-color: #2563eb;
            color: #ffffff;
        }

        .pagination .disabled {
            color: #9ca3af;
            background: #f9fafb;
            cursor: not-allowed;
        }

        @media (max-width: 700px) {

            .pagination {
                flex-wrap: wrap;
            }

        }

    </style>

</head>

<body>

<div class="layout">

    <?php require_once "../includes/sidebar.php"; ?>

    <main class="main-content">

        <div class="topbar">

            <div>

                <h1>Audit Trail</h1>

                <p>
                    Monitor changes and activities performed in VISRS.
                </p>

            </div>


            <div class="user-info">

                <div class="user-avatar">

                    <?= strtoupper(
                        substr(
                            $_SESSION["FirstName"] ?? "U",
                            0,
                            1
                        )
                    ) ?>

                </div>


                <div>

                    <strong>

                        <?= htmlspecialchars(
                            $_SESSION["FirstName"] ?? ""
                        ) ?>

                        <?= htmlspecialchars(
                            $_SESSION["LastName"] ?? ""
                        ) ?>

                    </strong>


                </div>

            </div>

        </div>


        <div class="content">

            <div class="card">

                <h2>
                    System Activity
                </h2>


                <p class="audit-summary">

                    <?= number_format($totalRecords) ?>

                    audit record<?= $totalRecords === 1 ? "" : "s" ?>

                    recorded in the system.

                </p>


                <?php if (empty($logs)): ?>

                    <div class="empty-state">

                        <h3>
                            No audit records found
                        </h3>

                        <p>
                            System activity will appear here when
                            records are created, updated, or deleted.
                        </p>

                    </div>

                <?php else: ?>


                    <div class="audit-table-wrapper">

                        <table class="audit-table">

                            <thead>

                                <tr>

                                    <th>
                                        Date & Time
                                    </th>

                                    <th>
                                        User
                                    </th>

                                    <th>
                                        Role
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

                                </tr>

                            </thead>


                            <tbody>

                                <?php foreach ($logs as $log): ?>

                                    <tr>

                                        <td>

                                            <?= htmlspecialchars(
                                                date(
                                                    "M j, Y g:i A",
                                                    strtotime(
                                                        $log["CreatedAt"]
                                                    )
                                                )
                                            ) ?>

                                        </td>


                                        <td>

                                            <?php if (
                                                $log["FirstName"] !== null
                                            ): ?>

                                                <div class="user-name">

                                                    <?= htmlspecialchars(
                                                        $log["FirstName"]
                                                    ) ?>

                                                    <?= htmlspecialchars(
                                                        $log["LastName"]
                                                    ) ?>

                                                </div>

                                                <div class="user-email">

                                                    <?= htmlspecialchars(
                                                        $log["Email"]
                                                    ) ?>

                                                </div>

                                            <?php else: ?>

                                                <span>
                                                    Unknown User
                                                </span>

                                            <?php endif; ?>

                                        </td>


                                        <td>

                                            <?php if (
                                                $log["Role"] !== null
                                            ): ?>

                                                <span class="role-badge">

                                                    <?= htmlspecialchars(
                                                        $log["Role"]
                                                    ) ?>

                                                </span>

                                            <?php else: ?>

                                                <span>
                                                    —
                                                </span>

                                            <?php endif; ?>

                                        </td>


                                        <td>

                                            <span class="action-text">

                                                <?= htmlspecialchars(
                                                    $log["Action"]
                                                ) ?>

                                            </span>

                                        </td>


                                        <td>

                                            <span class="table-badge">

                                                <?= htmlspecialchars(
                                                    $log["TableAffected"]
                                                ) ?>

                                            </span>

                                        </td>


                                        <td>

                                            <?= $log["RecordID"] !== null
                                                ? htmlspecialchars(
                                                    $log["RecordID"]
                                                )
                                                : "—"
                                            ?>

                                        </td>


                                        <td>

                                            <span class="ip-address">

                                                <?= $log["IPAddress"]
                                                    ? htmlspecialchars(
                                                        $log["IPAddress"]
                                                    )
                                                    : "—"
                                                ?>

                                            </span>

                                        </td>

                                    </tr>

                                <?php endforeach; ?>

                            </tbody>

                        </table>

                    </div>


                    <?php if ($totalPages > 1): ?>

                        <div class="pagination">


                            <?php if ($currentPage > 1): ?>

                                <a
                                    href="?page=<?= $currentPage - 1 ?>"
                                >
                                    Previous
                                </a>

                            <?php else: ?>

                                <span class="disabled">
                                    Previous
                                </span>

                            <?php endif; ?>


                            <?php

                            $startPage = max(
                                1,
                                $currentPage - 2
                            );

                            $endPage = min(
                                $totalPages,
                                $currentPage + 2
                            );

                            for (
                                $page = $startPage;
                                $page <= $endPage;
                                $page++
                            ):

                            ?>

                                <?php if (
                                    $page === $currentPage
                                ): ?>

                                    <span class="active">
                                        <?= $page ?>
                                    </span>

                                <?php else: ?>

                                    <a
                                        href="?page=<?= $page ?>"
                                    >
                                        <?= $page ?>
                                    </a>

                                <?php endif; ?>

                            <?php endfor; ?>


                            <?php if (
                                $currentPage < $totalPages
                            ): ?>

                                <a
                                    href="?page=<?= $currentPage + 1 ?>"
                                >
                                    Next
                                </a>

                            <?php else: ?>

                                <span class="disabled">
                                    Next
                                </span>

                            <?php endif; ?>


                        </div>

                    <?php endif; ?>


                <?php endif; ?>


            </div>

        </div>

    </main>

</div>

</body>

</html>