<?php

require_once "../includes/auth.php";
require_once "../includes/database.php";

$basePath = "../";
$activePage = "vehicles";

$vehicleID = $_GET["id"] ?? "";

if (!is_numeric($vehicleID)) {
    header("Location: index.php");
    exit;
}

$stmt = $pdo->prepare("
    SELECT
        v.*,
        o.FirstName AS OwnerFirstName,
        o.LastName AS OwnerLastName,
        o.Address AS OwnerAddress,
        o.Phone AS OwnerPhone,
        o.Email AS OwnerEmail
    FROM vehicles v
    INNER JOIN owners o
        ON v.OwnerID = o.OwnerID
    WHERE v.VehicleID = ?
    LIMIT 1
");

$stmt->execute([$vehicleID]);

$vehicle = $stmt->fetch();

if (!$vehicle) {

    http_response_code(404);

    echo "<h1>Vehicle Not Found</h1>";
    echo "<p>The requested vehicle could not be found.</p>";
    echo '<p><a href="index.php">Return to Vehicles</a></p>';

    exit;
}

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
        VISRS - <?= htmlspecialchars($vehicle["PlateNumber"]) ?>
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

        <!-- TOPBAR -->

        <?php

            $pageTitle = "Vehicles";

            include __DIR__ . "/../includes/header.php";

        ?>


        <!-- PAGE CONTENT -->

        <main class="content">

            <!-- PAGE HEADER -->

            <div class="page-title">

                <h1>

                    <?= htmlspecialchars($vehicle["Make"]) ?>

                    <?= htmlspecialchars($vehicle["Model"]) ?>

                </h1>

                <p class="page-subtitle">

                    Complete vehicle registration and ownership
                    information.

                </p>

            </div>


            <!-- VEHICLE SUMMARY -->

            <section class="card">

                <div
                    style="
                        display: flex;
                        justify-content: space-between;
                        align-items: center;
                        gap: 20px;
                        flex-wrap: wrap;
                    "
                >

                    <div>

                        <p
                            style="
                                margin: 0 0 6px;
                                color: #6b7280;
                                font-size: 14px;
                            "
                        >
                            License Plate
                        </p>

                        <h2 style="margin: 0;">

                            <?= htmlspecialchars(
                                $vehicle["PlateNumber"]
                            ) ?>

                        </h2>

                    </div>


                    <div>

                        <p
                            style="
                                margin: 0 0 6px;
                                color: #6b7280;
                                font-size: 14px;
                            "
                        >
                            Vehicle Status
                        </p>

                        <strong>

                            <?= htmlspecialchars(
                                $vehicle["Status"]
                            ) ?>

                        </strong>

                    </div>

                </div>

            </section>


            <!-- BASIC VEHICLE INFORMATION -->

            <section class="card" style="margin-top: 20px;">

                <h2>
                    Vehicle Information
                </h2>

                <div class="stats-grid">

                    <div class="stat-card">

                        <h3>
                            License Plate
                        </h3>

                        <div class="stat-number">

                            <?= htmlspecialchars(
                                $vehicle["PlateNumber"]
                            ) ?>

                        </div>

                    </div>


                    <div class="stat-card">

                        <h3>
                            VIN
                        </h3>

                        <div
                            style="
                                font-size: 16px;
                                font-weight: bold;
                                word-break: break-all;
                            "
                        >

                            <?= htmlspecialchars(
                                $vehicle["VIN"]
                            ) ?>

                        </div>

                    </div>


                    <div class="stat-card">

                        <h3>
                            Make & Model
                        </h3>

                        <div
                            style="
                                font-size: 20px;
                                font-weight: bold;
                            "
                        >

                            <?= htmlspecialchars(
                                $vehicle["Make"]
                            ) ?>

                            <?= htmlspecialchars(
                                $vehicle["Model"]
                            ) ?>

                        </div>

                    </div>


                    <div class="stat-card">

                        <h3>
                            Year
                        </h3>

                        <div class="stat-number">

                            <?= htmlspecialchars(
                                $vehicle["VehicleYear"]
                            ) ?>

                        </div>

                    </div>

                </div>

            </section>


            <!-- ADDITIONAL INFORMATION -->

            <section
                class="card"
                style="margin-top: 20px;"
            >

                <h2>
                    Additional Vehicle Information
                </h2>

                <table
                    style="
                        width: 100%;
                        border-collapse: collapse;
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
                            Color
                        </td>

                        <td style="padding: 12px;">

                            <?= htmlspecialchars(
                                $vehicle["Color"] ?: "Not provided"
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
                            Vehicle Type
                        </td>

                        <td style="padding: 12px;">

                            <?= htmlspecialchars(
                                $vehicle["VehicleType"] ?: "Not provided"
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
                            Engine Number
                        </td>

                        <td style="padding: 12px;">

                            <?= htmlspecialchars(
                                $vehicle["EngineNumber"] ?: "Not provided"
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
                            Registration Date
                        </td>

                        <td style="padding: 12px;">

                            <?= htmlspecialchars(
                                $vehicle["RegistrationDate"] ?: "Not provided"
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
                            Status
                        </td>

                        <td style="padding: 12px;">

                            <strong>

                                <?= htmlspecialchars(
                                    $vehicle["Status"]
                                ) ?>

                            </strong>

                        </td>

                    </tr>

                </table>

            </section>


            <!-- CURRENT OWNER -->

            <section
                class="card"
                style="margin-top: 20px;"
            >

                <h2>
                    Current Owner
                </h2>

                <table
                    style="
                        width: 100%;
                        border-collapse: collapse;
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
                                $vehicle["OwnerFirstName"]
                                . " "
                                . $vehicle["OwnerLastName"]
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
                                $vehicle["OwnerAddress"] ?: "Not provided"
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
                                $vehicle["OwnerPhone"] ?: "Not provided"
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
                            Email
                        </td>

                        <td style="padding: 12px;">

                            <?= htmlspecialchars(
                                $vehicle["OwnerEmail"] ?: "Not provided"
                            ) ?>

                        </td>

                    </tr>

                </table>

            </section>


            <!-- FUTURE RECORDS -->

            <section
                class="card"
                style="margin-top: 20px;"
            >

                <h2>
                    Related Records
                </h2>

                <p
                    style="
                        color: #6b7280;
                        margin-bottom: 20px;
                    "
                >
                    Additional records associated with this vehicle
                    will appear here.
                </p>

                <div class="quick-actions">

                    <a
                        href="../insurance/"
                        class="button button-secondary"
                    >
                        Insurance Records
                    </a>

                    <a
                        href="../accidents/"
                        class="button button-secondary"
                    >
                        Accident Records
                    </a>

                </div>

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
                    ← Back to Vehicles
                </a>


                <?php if (canManageVehicles()): ?>

                    <a
                        href="edit.php?id=<?= $vehicle["VehicleID"] ?>"
                        class="button"
                    >
                        Edit Vehicle
                    </a>

                <?php endif; ?>

            </div>

        </main>

    </div>

</div>


<script src="../js/app.js"></script>

</body>

</html>