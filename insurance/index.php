<?php

require_once "../includes/auth.php";
require_once "../includes/database.php";

$basePath = "../";
$activePage = "insurance";


/*
 * Get all insurance records.
 *
 * Each insurance record is connected to a vehicle.
 */

$stmt = $pdo->query("
    SELECT
        i.InsuranceID,
        i.VehicleID,
        i.ProviderName,
        i.PolicyNumber,
        i.CoverageType,
        i.StartDate,
        i.ExpiryDate,
        i.Status,

        v.PlateNumber,
        v.VIN,
        v.Make,
        v.Model,
        v.VehicleYear

    FROM insurance i

    INNER JOIN vehicles v
        ON i.VehicleID = v.VehicleID

    ORDER BY i.ExpiryDate ASC, i.InsuranceID DESC
");

$insuranceRecords = $stmt->fetchAll();

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Insurance - VISRS</title>

    <link
        rel="stylesheet"
        href="../css/style.css"
    >

</head>

<body>

<div class="layout">

    <?php require_once "../includes/sidebar.php"; ?>


    <main class="main-content">

        <!-- TOP BAR -->

        <div class="topbar">

            <div>

                <h1>Insurance</h1>

                <p>
                    Manage vehicle insurance records and policies.
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


        <!-- PAGE CONTENT -->

        <div class="content">


            <!-- PAGE HEADER -->

            <div
                style="
                    display:flex;
                    justify-content:space-between;
                    align-items:center;
                    margin-bottom:25px;
                    gap:15px;
                    flex-wrap:wrap;
                "
            >

                <div>

                    <h2 style="margin-bottom:5px;">
                        Insurance Records
                    </h2>

                    <p>
                        View and manage insurance policies associated
                        with registered vehicles.
                    </p>

                </div>


                <a
                    href="add.php"
                    class="button"
                >
                    + Add Insurance
                </a>

            </div>


            <!-- INSURANCE TABLE -->

            <div class="card">

                <?php if (count($insuranceRecords) > 0): ?>

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

                                <tr>

                                    <th
                                        style="
                                            text-align:left;
                                            padding:12px;
                                            border-bottom:1px solid #ddd;
                                        "
                                    >
                                        Vehicle
                                    </th>


                                    <th
                                        style="
                                            text-align:left;
                                            padding:12px;
                                            border-bottom:1px solid #ddd;
                                        "
                                    >
                                        Provider
                                    </th>


                                    <th
                                        style="
                                            text-align:left;
                                            padding:12px;
                                            border-bottom:1px solid #ddd;
                                        "
                                    >
                                        Policy Number
                                    </th>


                                    <th
                                        style="
                                            text-align:left;
                                            padding:12px;
                                            border-bottom:1px solid #ddd;
                                        "
                                    >
                                        Coverage
                                    </th>


                                    <th
                                        style="
                                            text-align:left;
                                            padding:12px;
                                            border-bottom:1px solid #ddd;
                                        "
                                    >
                                        Expiry Date
                                    </th>


                                    <th
                                        style="
                                            text-align:left;
                                            padding:12px;
                                            border-bottom:1px solid #ddd;
                                        "
                                    >
                                        Status
                                    </th>


                                    <th
                                        style="
                                            text-align:left;
                                            padding:12px;
                                            border-bottom:1px solid #ddd;
                                        "
                                    >
                                        Actions
                                    </th>

                                </tr>

                            </thead>


                            <tbody>

                                <?php foreach (
                                    $insuranceRecords
                                    as $insurance
                                ): ?>

                                    <tr>


                                        <!-- VEHICLE -->

                                        <td
                                            style="
                                                padding:12px;
                                                border-bottom:1px solid #eee;
                                            "
                                        >

                                            <strong>

                                                <?= htmlspecialchars(
                                                    $insurance["PlateNumber"]
                                                ) ?>

                                            </strong>

                                            <br>

                                            <small>

                                                <?= htmlspecialchars(
                                                    $insurance["Make"]
                                                ) ?>

                                                <?= htmlspecialchars(
                                                    $insurance["Model"]
                                                ) ?>

                                                (<?= htmlspecialchars(
                                                    $insurance["VehicleYear"]
                                                ) ?>)

                                            </small>

                                        </td>


                                        <!-- PROVIDER -->

                                        <td
                                            style="
                                                padding:12px;
                                                border-bottom:1px solid #eee;
                                            "
                                        >

                                            <?= htmlspecialchars(
                                                $insurance["ProviderName"]
                                            ) ?>

                                        </td>


                                        <!-- POLICY NUMBER -->

                                        <td
                                            style="
                                                padding:12px;
                                                border-bottom:1px solid #eee;
                                            "
                                        >

                                            <?= htmlspecialchars(
                                                $insurance["PolicyNumber"]
                                            ) ?>

                                        </td>


                                        <!-- COVERAGE -->

                                        <td
                                            style="
                                                padding:12px;
                                                border-bottom:1px solid #eee;
                                            "
                                        >

                                            <?= htmlspecialchars(
                                                $insurance["CoverageType"]
                                                    ?? "Not specified"
                                            ) ?>

                                        </td>


                                        <!-- EXPIRY -->

                                        <td
                                            style="
                                                padding:12px;
                                                border-bottom:1px solid #eee;
                                            "
                                        >

                                            <?= $insurance["ExpiryDate"]
                                                ? htmlspecialchars(
                                                    date(
                                                        "M d, Y",
                                                        strtotime(
                                                            $insurance["ExpiryDate"]
                                                        )
                                                    )
                                                )
                                                : "Not specified"
                                            ?>

                                        </td>


                                        <!-- STATUS -->

                                        <td
                                            style="
                                                padding:12px;
                                                border-bottom:1px solid #eee;
                                            "
                                        >

                                            <?php

                                            $status = $insurance["Status"];

                                            $statusBackground = "#e5e7eb";
                                            $statusColor = "#374151";

                                            if ($status === "Active") {

                                                $statusBackground = "#dcfce7";
                                                $statusColor = "#166534";

                                            } elseif (
                                                $status === "Expired"
                                            ) {

                                                $statusBackground = "#fee2e2";
                                                $statusColor = "#991b1b";

                                            } elseif (
                                                $status === "Cancelled"
                                            ) {

                                                $statusBackground = "#fef3c7";
                                                $statusColor = "#92400e";

                                            }

                                            ?>

                                            <span
                                                style="
                                                    display:inline-block;
                                                    padding:6px 10px;
                                                    border-radius:20px;
                                                    background:<?= $statusBackground ?>;
                                                    color:<?= $statusColor ?>;
                                                    font-size:13px;
                                                    font-weight:600;
                                                "
                                            >

                                                <?= htmlspecialchars(
                                                    $status
                                                ) ?>

                                            </span>

                                        </td>


                                        <!-- ACTIONS -->

                                        <td
                                            style="
                                                padding:12px;
                                                border-bottom:1px solid #eee;
                                                white-space:nowrap;
                                            "
                                        >

                                            <a
                                                href="view.php?id=<?= $insurance["InsuranceID"] ?>"
                                                class="button button-secondary"
                                                style="
                                                    padding:8px 12px;
                                                    margin-right:4px;
                                                "
                                            >
                                                View
                                            </a>


                                            <a
                                                href="edit.php?id=<?= $insurance["InsuranceID"] ?>"
                                                class="button"
                                                style="
                                                    padding:8px 12px;
                                                    margin-right:4px;
                                                "
                                            >
                                                Edit
                                            </a>


                                            <a
                                                href="delete.php?id=<?= $insurance["InsuranceID"] ?>"
                                                class="button"
                                                style="
                                                    padding:8px 12px;
                                                    background:#991b1b;
                                                "
                                            >
                                                Delete
                                            </a>

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
                        "
                    >

                        <div
                            style="
                                font-size:48px;
                                margin-bottom:15px;
                            "
                        >
                            🛡️
                        </div>


                        <h2>
                            No Insurance Records
                        </h2>


                        <p
                            style="
                                margin:10px 0 25px;
                            "
                        >
                            No vehicle insurance policies have been
                            added to the system yet.
                        </p>


                        <a
                            href="add.php"
                            class="button"
                        >
                            + Add First Insurance Record
                        </a>

                    </div>

                <?php endif; ?>

            </div>

        </div>

    </main>

</div>

</body>

</html>