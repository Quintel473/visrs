<?php

require_once "../includes/auth.php";
require_once "../includes/database.php";

$basePath = "../";
$activePage = "owners";

$ownerID = $_GET["id"] ?? "";

if (!is_numeric($ownerID)) {
    header("Location: index.php");
    exit;
}


/* Get owner information */

$stmt = $pdo->prepare("
    SELECT
        OwnerID,
        FirstName,
        LastName,
        Address,
        Phone,
        Email,
        CreatedAt
    FROM owners
    WHERE OwnerID = ?
    LIMIT 1
");

$stmt->execute([$ownerID]);

$owner = $stmt->fetch();


/* Check that owner exists */

if (!$owner) {

    http_response_code(404);

    echo "<h1>Owner Not Found</h1>";
    echo "<p>The requested owner could not be found.</p>";
    echo '<p><a href="index.php">Return to Owners</a></p>';

    exit;
}


/* Get vehicles belonging to this owner */

$stmt = $pdo->prepare("
    SELECT
        VehicleID,
        PlateNumber,
        VIN,
        Make,
        Model,
        VehicleYear,
        Color,
        VehicleType,
        Status
    FROM vehicles
    WHERE OwnerID = ?
    ORDER BY VehicleID DESC
");

$stmt->execute([$ownerID]);

$vehicles = $stmt->fetchAll();

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
        VISRS - Owner Details
    </title>

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


        <!-- TOP BAR -->

        <header class="topbar">

            <div class="topbar-title">
                Owner Details
            </div>


            <div class="user-info">

                <span>

                    <?= htmlspecialchars(
                        $_SESSION["FirstName"]
                    ) ?>

                    <?= htmlspecialchars(
                        $_SESSION["LastName"]
                    ) ?>

                </span>


                <div class="user-avatar">

                    <?= strtoupper(
                        substr(
                            $_SESSION["FirstName"],
                            0,
                            1
                        )
                    ) ?>

                </div>

            </div>

        </header>


        <!-- PAGE CONTENT -->

        <main class="content">


            <!-- PAGE TITLE -->

            <div class="page-title">

                <h1>

                    <?= htmlspecialchars(
                        $owner["FirstName"]
                    ) ?>

                    <?= htmlspecialchars(
                        $owner["LastName"]
                    ) ?>

                </h1>

                <p class="page-subtitle">

                    Owner profile and associated vehicle
                    information.

                </p>

            </div>


            <!-- OWNER INFORMATION -->

            <section class="card">

                <h2>
                    Owner Information
                </h2>


                <table
                    style="
                        width: 100%;
                        border-collapse: collapse;
                        margin-top: 15px;
                    "
                >

                    <tr
                        style="
                            border-bottom: 1px solid #e5e7eb;
                        "
                    >

                        <td
                            style="
                                padding: 12px;
                                font-weight: bold;
                                width: 35%;
                            "
                        >
                            Full Name
                        </td>

                        <td style="padding: 12px;">

                            <?= htmlspecialchars(
                                $owner["FirstName"]
                                . " "
                                . $owner["LastName"]
                            ) ?>

                        </td>

                    </tr>


                    <tr
                        style="
                            border-bottom: 1px solid #e5e7eb;
                        "
                    >

                        <td
                            style="
                                padding: 12px;
                                font-weight: bold;
                            "
                        >
                            Address
                        </td>

                        <td style="padding: 12px;">

                            <?= htmlspecialchars(
                                $owner["Address"] ?: "Not provided"
                            ) ?>

                        </td>

                    </tr>


                    <tr
                        style="
                            border-bottom: 1px solid #e5e7eb;
                        "
                    >

                        <td
                            style="
                                padding: 12px;
                                font-weight: bold;
                            "
                        >
                            Phone
                        </td>

                        <td style="padding: 12px;">

                            <?= htmlspecialchars(
                                $owner["Phone"] ?: "Not provided"
                            ) ?>

                        </td>

                    </tr>


                    <tr
                        style="
                            border-bottom: 1px solid #e5e7eb;
                        "
                    >

                        <td
                            style="
                                padding: 12px;
                                font-weight: bold;
                            "
                        >
                            Email
                        </td>

                        <td style="padding: 12px;">

                            <?= htmlspecialchars(
                                $owner["Email"] ?: "Not provided"
                            ) ?>

                        </td>

                    </tr>


                    <tr>

                        <td
                            style="
                                padding: 12px;
                                font-weight: bold;
                            "
                        >
                            Owner ID
                        </td>

                        <td style="padding: 12px;">

                            <?= htmlspecialchars(
                                $owner["OwnerID"]
                            ) ?>

                        </td>

                    </tr>

                </table>

            </section>


            <!-- ASSOCIATED VEHICLES -->

            <section
                class="card"
                style="margin-top: 20px;"
            >

                <h2>
                    Associated Vehicles
                </h2>

                <p
                    style="
                        color: #6b7280;
                        margin-bottom: 20px;
                    "
                >

                    Vehicles currently registered to this owner.

                </p>


                <?php if (count($vehicles) > 0): ?>

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
                                        border-bottom: 2px solid #e5e7eb;
                                        text-align: left;
                                    "
                                >

                                    <th style="padding: 12px;">
                                        Plate Number
                                    </th>

                                    <th style="padding: 12px;">
                                        VIN
                                    </th>

                                    <th style="padding: 12px;">
                                        Vehicle
                                    </th>

                                    <th style="padding: 12px;">
                                        Year
                                    </th>

                                    <th style="padding: 12px;">
                                        Status
                                    </th>

                                    <th style="padding: 12px;">
                                        Action
                                    </th>

                                </tr>

                            </thead>


                            <tbody>

                                <?php foreach ($vehicles as $vehicle): ?>

                                    <tr
                                        style="
                                            border-bottom: 1px solid #e5e7eb;
                                        "
                                    >

                                        <td style="padding: 12px;">

                                            <strong>

                                                <?= htmlspecialchars(
                                                    $vehicle["PlateNumber"]
                                                ) ?>

                                            </strong>

                                        </td>


                                        <td style="padding: 12px;">

                                            <?= htmlspecialchars(
                                                $vehicle["VIN"]
                                            ) ?>

                                        </td>


                                        <td style="padding: 12px;">

                                            <?= htmlspecialchars(
                                                $vehicle["Make"]
                                            ) ?>

                                            <?= htmlspecialchars(
                                                $vehicle["Model"]
                                            ) ?>

                                        </td>


                                        <td style="padding: 12px;">

                                            <?= htmlspecialchars(
                                                $vehicle["VehicleYear"]
                                            ) ?>

                                        </td>


                                        <td style="padding: 12px;">

                                            <?= htmlspecialchars(
                                                $vehicle["Status"]
                                            ) ?>

                                        </td>


                                        <td style="padding: 12px;">

                                            <a
                                                href="../vehicles/view.php?id=<?= $vehicle["VehicleID"] ?>"
                                                class="button button-secondary"
                                                style="padding: 8px 12px;"
                                            >
                                                View
                                            </a>

                                        </td>

                                    </tr>

                                <?php endforeach; ?>

                            </tbody>

                        </table>

                    </div>

                <?php else: ?>

                    <div
                        style="
                            padding: 30px;
                            text-align: center;
                            color: #6b7280;
                        "
                    >

                        <p>
                            This owner currently has no vehicles
                            registered.
                        </p>

                    </div>

                <?php endif; ?>

            </section>


            <!-- ACTION BUTTONS -->

            <div
                style="
                    display: flex;
                    gap: 10px;
                    margin-top: 20px;
                    flex-wrap: wrap;
                "
            >

                <a
                    href="index.php"
                    class="button button-secondary"
                >
                    ← Back to Owners
                </a>


                <a
                    href="edit.php?id=<?= $owner["OwnerID"] ?>"
                    class="button"
                >
                    Edit Owner
                </a>

            </div>


        </main>

    </div>

</div>


<script src="../js/app.js"></script>

</body>

</html>