<?php

require_once __DIR__ . "/../includes/auth.php";
require_once __DIR__ . "/../includes/database.php";

$activePage = "ownership_history";

$canManage =
    isset($_SESSION["Role"]) &&
    in_array(
        $_SESSION["Role"],
        ["Admin", "Police"],
        true
    );


/*
|--------------------------------------------------------------------------
| Load Ownership History
|--------------------------------------------------------------------------
*/

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

    ORDER BY
        oh.StartDate DESC,
        oh.OwnershipID DESC
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

    <title>
        VISRS - Ownership History
    </title>

    <link
        rel="stylesheet"
        href="/visrs/css/style.css"
    >

</head>

<body>

<div class="layout">

    <?php require_once __DIR__ . "/../includes/sidebar.php"; ?>


    <main class="main-content">

        <?php

        $pageTitle = "Ownership History";
        $pageSubtitle =
            "View the ownership records associated with vehicles in VISRS.";

        include __DIR__ . "/../includes/header.php";

        ?>


        <div class="content">

            <div class="page-title">

                <div>

                    <h1>
                        Ownership History
                    </h1>

                    <p class="page-subtitle">
                        View the ownership records associated with vehicles in VISRS.
                    </p>

                </div>


                <?php if ($canManage): ?>

                    <div>

                        <a
                            href="add.php"
                            class="button"
                        >
                            Add Ownership Record
                        </a>

                    </div>

                <?php endif; ?>

            </div>


            <!-- SUCCESS MESSAGE -->

            <?php if (
                isset($_GET["deleted"]) &&
                $_GET["deleted"] === "1"
            ): ?>

                <div
                    class="auto-dismiss"
                    style="
                        background:#dcfce7;
                        color:#166534;
                        padding:14px;
                        border-radius:8px;
                        margin-bottom:20px;
                    "
                >

                    Ownership record deleted successfully.

                </div>

            <?php endif; ?>


            <!-- OWNERSHIP HISTORY TABLE -->

            <section class="card">

                <?php if (count($history) === 0): ?>

                    <div
                        style="
                            padding:30px;
                            text-align:center;
                        "
                    >

                        <h2>
                            No Ownership History
                        </h2>

                        <p
                            style="
                                margin-top:10px;
                                color:#6b7280;
                            "
                        >
                            No ownership history records have been
                            added to the system yet.
                        </p>


                        <?php if ($canManage): ?>

                            <div style="margin-top:20px;">

                                <a
                                    href="add.php"
                                    class="button"
                                >
                                    Add First Record
                                </a>

                            </div>

                        <?php endif; ?>

                    </div>

                <?php else: ?>

                    <div
                        style="
                            overflow-x:auto;
                        "
                    >

                        <table
                            style="
                                width:100%;
                                border-collapse:collapse;
                            "
                        >

                            <thead>

                                <tr
                                    style="
                                        border-bottom:1px solid #e5e7eb;
                                        text-align:left;
                                    "
                                >

                                    <th style="padding:12px;">
                                        Vehicle
                                    </th>

                                    <th style="padding:12px;">
                                        VIN
                                    </th>

                                    <th style="padding:12px;">
                                        Owner
                                    </th>

                                    <th style="padding:12px;">
                                        Start Date
                                    </th>

                                    <th style="padding:12px;">
                                        End Date
                                    </th>

                                    <th style="padding:12px;">
                                        Transfer Reason
                                    </th>

                                    <th style="padding:12px;">
                                        Actions
                                    </th>

                                </tr>

                            </thead>


                            <tbody>

                                <?php foreach (
                                    $history
                                    as $record
                                ): ?>

                                    <tr
                                        style="
                                            border-bottom:1px solid #f1f5f9;
                                        "
                                    >

                                        <!-- VEHICLE -->

                                        <td style="padding:12px;">

                                            <strong>

                                                <?= htmlspecialchars(
                                                    $record["PlateNumber"]
                                                ) ?>

                                            </strong>

                                            <br>

                                            <small
                                                style="
                                                    color:#6b7280;
                                                "
                                            >

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


                                        <!-- VIN -->

                                        <td style="padding:12px;">

                                            <span
                                                style="
                                                    font-family:monospace;
                                                "
                                            >
                                                <?= htmlspecialchars(
                                                    $record["VIN"]
                                                ) ?>
                                            </span>

                                        </td>


                                        <!-- OWNER -->

                                        <td style="padding:12px;">

                                            <?= htmlspecialchars(
                                                $record["FirstName"]
                                            ) ?>

                                            <?= htmlspecialchars(
                                                $record["LastName"]
                                            ) ?>

                                        </td>


                                        <!-- START DATE -->

                                        <td style="padding:12px;">

                                            <?= htmlspecialchars(
                                                $record["StartDate"]
                                            ) ?>

                                        </td>


                                        <!-- END DATE -->

                                        <td style="padding:12px;">

                                            <?php if (
                                                $record["EndDate"] === null ||
                                                $record["EndDate"] === ""
                                            ): ?>

                                                <span
                                                    style="
                                                        color:#166534;
                                                        font-weight:600;
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


                                        <!-- TRANSFER REASON -->

                                        <td style="padding:12px;">

                                            <?php if (
                                                !empty(
                                                    $record["TransferReason"]
                                                )
                                            ): ?>

                                                <?= htmlspecialchars(
                                                    $record["TransferReason"]
                                                ) ?>

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


                                        <!-- ACTIONS -->

                                        <td
                                            style="
                                                padding:12px;
                                                white-space:nowrap;
                                            "
                                        >

                                            <a
                                                href="view.php?id=<?= (int) $record["OwnershipID"] ?>"
                                                class="button button-secondary"
                                                style="
                                                    padding:8px 12px;
                                                "
                                            >
                                                View
                                            </a>


                                            <?php if ($canManage): ?>

                                                <a
                                                    href="edit.php?id=<?= (int) $record["OwnershipID"] ?>"
                                                    class="button"
                                                    style="
                                                        padding:8px 12px;
                                                        margin-left:5px;
                                                    "
                                                >
                                                    Edit
                                                </a>


                                                <a
                                                    href="delete.php?id=<?= (int) $record["OwnershipID"] ?>"
                                                    class="button button-danger"
                                                    style="
                                                        padding:8px 12px;
                                                        margin-left:5px;
                                                    "
                                                >
                                                    Delete
                                                </a>

                                            <?php endif; ?>

                                        </td>

                                    </tr>

                                <?php endforeach; ?>

                            </tbody>

                        </table>

                    </div>

                <?php endif; ?>

            </section>

        </div>


        <?php require_once __DIR__ . "/../includes/footer.php"; ?>

    </main>

</div>

</body>

</html>