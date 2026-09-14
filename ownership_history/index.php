<?php

require_once "../includes/auth.php";
require_once "../includes/database.php";

$basePath = "../";
$activePage = "ownership_history";

/* Get all ownership history records */
$stmt = $pdo->query("
    SELECT
        oh.OwnershipID,
        oh.VehicleID,
        oh.OwnerID,
        oh.StartDate,
        oh.EndDate,
        oh.TransferReason,

        v.PlateNumber,
        v.VIN,
        v.Make,
        v.Model,
        v.VehicleYear,

        o.FirstName,
        o.LastName

    FROM ownership_history oh

    INNER JOIN vehicles v
        ON oh.VehicleID = v.VehicleID

    INNER JOIN owners o
        ON oh.OwnerID = o.OwnerID

    ORDER BY oh.StartDate DESC
");

$history = $stmt->fetchAll();

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>VISRS - Ownership History</title>

    <link
        rel="stylesheet"
        href="../css/style.css"
    >

</head>

<body>

<div class="layout">

    <!-- SIDEBAR -->

    <aside class="sidebar">

       <?php require_once "../includes/sidebar.php"; ?>

    </aside>


    <!-- MAIN CONTENT -->

    <div class="main-content">

        <!-- TOPBAR -->

        <header class="topbar">

            <div class="topbar-title">
                Ownership History
            </div>

            <div class="user-info">

                <span>

                    <?= htmlspecialchars($_SESSION["FirstName"]) ?>

                    <?= htmlspecialchars($_SESSION["LastName"]) ?>

                </span>

                <div class="user-avatar">

                    <?= strtoupper(
                        substr($_SESSION["FirstName"], 0, 1)
                    ) ?>

                </div>

            </div>

        </header>


        <!-- PAGE CONTENT -->

        <main class="content">

            <div class="page-title">

                <div>

                    <h1>
                        Ownership History
                    </h1>

                    <p class="page-subtitle">
                        View the ownership records associated with vehicles in VISRS.
                    </p>

                </div>

                <div>

                    <a
                        href="add.php"
                        class="button"
                    >
                        Add Ownership Record
                    </a>

                </div>

            </div>


            <!-- OWNERSHIP HISTORY TABLE -->

            <section class="card">

                <?php if (count($history) === 0): ?>

                    <div style="padding: 30px; text-align: center;">

                        <h2>
                            No Ownership History
                        </h2>

                        <p style="margin-top: 10px;">

                            No ownership history records have been
                            added to the system yet.

                        </p>

                        <div style="margin-top: 20px;">

                            <a
                                href="add.php"
                                class="button"
                            >
                                Add First Record
                            </a>

                        </div>

                    </div>

                <?php else: ?>

                    <div style="overflow-x: auto;">

                        <table
                            style="
                                width: 100%;
                                border-collapse: collapse;
                            "
                        >

                            <thead>

                                <tr
                                    style="
                                        border-bottom: 1px solid #e5e7eb;
                                        text-align: left;
                                    "
                                >

                                    <th style="padding: 12px;">
                                        Vehicle
                                    </th>

                                    <th style="padding: 12px;">
                                        VIN
                                    </th>

                                    <th style="padding: 12px;">
                                        Owner
                                    </th>

                                    <th style="padding: 12px;">
                                        Start Date
                                    </th>

                                    <th style="padding: 12px;">
                                        End Date
                                    </th>

                                    <th style="padding: 12px;">
                                        Transfer Reason
                                    </th>

                                    <th style="padding: 12px;">
                                        Actions
                                    </th>

                                </tr>

                            </thead>

                            <tbody>

                                <?php foreach ($history as $record): ?>

                                    <tr
                                        style="
                                            border-bottom: 1px solid #f1f5f9;
                                        "
                                    >

                                        <td style="padding: 12px;">

                                            <strong>

                                                <?= htmlspecialchars(
                                                    $record["PlateNumber"]
                                                ) ?>

                                            </strong>

                                            <br>

                                            <small>

                                                <?= htmlspecialchars(
                                                    $record["Make"]
                                                ) ?>

                                                <?= htmlspecialchars(
                                                    $record["Model"]
                                                ) ?>

                                                (<?= htmlspecialchars(
                                                    $record["VehicleYear"]
                                                ) ?>)

                                            </small>

                                        </td>


                                        <td style="padding: 12px;">

                                            <?= htmlspecialchars(
                                                $record["VIN"]
                                            ) ?>

                                        </td>


                                        <td style="padding: 12px;">

                                            <?= htmlspecialchars(
                                                $record["FirstName"]
                                            ) ?>

                                            <?= htmlspecialchars(
                                                $record["LastName"]
                                            ) ?>

                                        </td>


                                        <td style="padding: 12px;">

                                            <?= htmlspecialchars(
                                                $record["StartDate"]
                                            ) ?>

                                        </td>


                                        <td style="padding: 12px;">

                                            <?php if (
                                                $record["EndDate"] === null ||
                                                $record["EndDate"] === ""
                                            ): ?>

                                                <span
                                                    style="
                                                        color: #166534;
                                                        font-weight: 600;
                                                    "
                                                >
                                                    Current Owner
                                                </span>

                                            <?php else: ?>

                                                <?= htmlspecialchars(
                                                    $record["EndDate"]
                                                ) ?>

                                            <?php endif; ?>

                                        </td>


                                        <td style="padding: 12px;">

                                            <?= htmlspecialchars(
                                                $record["TransferReason"] ?? ""
                                            ) ?>

                                        </td>


                                        <td style="padding: 12px;">

                                            <a
                                                href="view.php?id=<?= $record["OwnershipID"] ?>"
                                                class="button button-secondary"
                                                style="
                                                    padding: 8px 12px;
                                                "
                                            >
                                                View
                                            </a>

                                        </td>

                                    </tr>

                                <?php endforeach; ?>

                            </tbody>

                        </table>

                    </div>

                <?php endif; ?>

            </section>

        </main>

    </div>

</div>


<script src="../js/app.js"></script>

</body>

</html>