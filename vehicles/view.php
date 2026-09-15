<?php

require_once __DIR__ . "/../includes/auth.php";
require_once __DIR__ . "/../includes/database.php";
require_once __DIR__ . "/../includes/functions.php";

$pageTitle = "Vehicles";
$pageSubtitle = "View complete vehicle information";

$activePage = "vehicles";


/*
|--------------------------------------------------------------------------
| Get Vehicle ID
|--------------------------------------------------------------------------
*/

$vehicleID = isset($_GET["id"])
    ? (int) $_GET["id"]
    : 0;


if ($vehicleID <= 0) {

    header("Location: index.php");
    exit;

}


/*
|--------------------------------------------------------------------------
| Load Vehicle and Owner
|--------------------------------------------------------------------------
*/

$stmt = $pdo->prepare("
    SELECT
        v.VehicleID,
        v.PlateNumber,
        v.VIN,
        v.OwnerID,
        v.Make,
        v.Model,
        v.VehicleYear,
        v.Mileage,
        v.LastServiceDate,
        v.OilChangeIntervalKm,
        v.LastOilChangeDate,
        v.LastOilChangeMileage,
        v.Color,
        v.VehicleType,
        v.EngineNumber,
        v.RegistrationDate,
        v.Status,
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

$stmt->execute([
    $vehicleID
]);

$vehicle = $stmt->fetch();


/*
|--------------------------------------------------------------------------
| Vehicle Not Found
|--------------------------------------------------------------------------
*/

if (!$vehicle) {

    http_response_code(404);

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
            Vehicle Not Found - VISRS
        </title>

        <link
            rel="stylesheet"
            href="/visrs/css/style.css"
        >

    </head>

    <body>

    <div class="layout">

        <main
            class="main-content"
            style="
                margin-left:0;
                width:100%;
            "
        >

            <div
                class="content"
                style="
                    max-width:700px;
                    margin:80px auto;
                "
            >

                <div class="card">

                    <div
                        style="
                            background:#fee2e2;
                            color:#991b1b;
                            padding:14px;
                            border-radius:8px;
                            margin-bottom:20px;
                        "
                    >

                        <strong>
                            Vehicle Not Found
                        </strong>

                    </div>

                    <h2>
                        The requested vehicle could not be found.
                    </h2>

                    <p>
                        The vehicle may have been deleted or
                        the requested vehicle ID is invalid.
                    </p>

                    <a
                        href="index.php"
                        class="button"
                    >
                        Return to Vehicles
                    </a>

                </div>

            </div>

        </main>

    </div>

    </body>

    </html>

    <?php

    exit;

}


/*
|--------------------------------------------------------------------------
| Load Related Record Counts
|--------------------------------------------------------------------------
*/

$ownershipStmt = $pdo->prepare("
    SELECT COUNT(*)
    FROM ownership_history
    WHERE VehicleID = ?
");

$ownershipStmt->execute([
    $vehicleID
]);

$ownershipCount =
    (int) $ownershipStmt->fetchColumn();


$insuranceStmt = $pdo->prepare("
    SELECT COUNT(*)
    FROM insurance
    WHERE VehicleID = ?
");

$insuranceStmt->execute([
    $vehicleID
]);

$insuranceCount =
    (int) $insuranceStmt->fetchColumn();


$accidentStmt = $pdo->prepare("
    SELECT COUNT(*)
    FROM accidents
    WHERE VehicleID = ?
");

$accidentStmt->execute([
    $vehicleID
]);

$accidentCount =
    (int) $accidentStmt->fetchColumn();


/*
|--------------------------------------------------------------------------
| Maintenance Calculations
|--------------------------------------------------------------------------
*/

$currentMileage = $vehicle["Mileage"] !== null
    ? (int) $vehicle["Mileage"]
    : null;

$oilInterval = $vehicle["OilChangeIntervalKm"] !== null
    ? (int) $vehicle["OilChangeIntervalKm"]
    : null;

$lastOilMileage = $vehicle["LastOilChangeMileage"] !== null
    ? (int) $vehicle["LastOilChangeMileage"]
    : null;


/*
|--------------------------------------------------------------------------
| Calculate Mileage Until Next Oil Change
|--------------------------------------------------------------------------
*/

$mileageUntilOilChange = null;

if (
    $currentMileage !== null &&
    $lastOilMileage !== null &&
    $oilInterval !== null &&
    $oilInterval > 0
) {

    $nextOilChangeMileage =
        $lastOilMileage + $oilInterval;

    $mileageUntilOilChange =
        $nextOilChangeMileage - $currentMileage;

}


/*
|--------------------------------------------------------------------------
| Determine Oil Change Status
|--------------------------------------------------------------------------
*/

$oilChangeStatus = "Not enough information";

if ($mileageUntilOilChange !== null) {

    if ($mileageUntilOilChange <= 0) {

        $oilChangeStatus =
            "Oil change due";

    } elseif ($mileageUntilOilChange <= 1000) {

        $oilChangeStatus =
            "Oil change approaching";

    } else {

        $oilChangeStatus =
            "Oil change not yet due";

    }

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
        VISRS -
        <?= htmlspecialchars($vehicle["PlateNumber"]) ?>
    </title>

    <link
        rel="stylesheet"
        href="/visrs/css/style.css"
    >

    <style>

        /*
        |--------------------------------------------------------------------------
        | Maintenance Section
        |--------------------------------------------------------------------------
        */

        .maintenance-section {
            margin-top:20px;
        }

        .maintenance-grid {
            display:grid;
            grid-template-columns:
                repeat(2, minmax(0, 1fr));
            gap:16px;
            margin-top:20px;
        }

        .maintenance-card {
            padding:18px;
            border:1px solid #e5e7eb;
            border-radius:8px;
            background:#ffffff;
        }

        .maintenance-card h3 {
            margin:0 0 8px;
            font-size:13px;
            color:#6b7280;
            font-weight:600;
        }

        .maintenance-card-value {
            font-size:20px;
            font-weight:700;
            color:#111827;
            word-break:break-word;
        }

        .maintenance-card-help {
            margin-top:6px;
            font-size:12px;
            color:#6b7280;
            line-height:1.4;
        }

        .maintenance-status {
            margin-top:20px;
            padding:14px 16px;
            border-radius:8px;
            background:#f9fafb;
            border:1px solid #e5e7eb;
        }

        .maintenance-status-title {
            margin:0 0 5px;
            font-size:13px;
            color:#6b7280;
        }

        .maintenance-status-value {
            font-size:16px;
            font-weight:700;
            color:#111827;
        }

        .maintenance-status-due {
            background:#fef2f2;
            border-color:#fecaca;
        }

        .maintenance-status-due
        .maintenance-status-value {
            color:#991b1b;
        }

        .maintenance-status-warning {
            background:#fffbeb;
            border-color:#fde68a;
        }

        .maintenance-status-warning
        .maintenance-status-value {
            color:#92400e;
        }

        .maintenance-status-ok {
            background:#f0fdf4;
            border-color:#bbf7d0;
        }

        .maintenance-status-ok
        .maintenance-status-value {
            color:#166534;
        }

        .maintenance-info-note {
            margin-top:18px;
            padding:14px 16px;
            border:1px solid #e5e7eb;
            border-radius:8px;
            background:#f9fafb;
            color:#4b5563;
            font-size:12px;
            line-height:1.6;
        }

        .maintenance-info-note strong {
            color:#111827;
        }

        @media (max-width:700px) {

            .maintenance-grid {
                grid-template-columns:1fr;
            }

        }

    </style>

</head>


<body>

<div class="layout">


    <?php

    $activePage = "vehicles";

    include __DIR__ . "/../includes/sidebar.php";

    ?>


    <main class="main-content">


        <?php include __DIR__ . "/../includes/header.php"; ?>


        <main class="content">


            <!-- PAGE HEADER -->

            <h1 class="page-title">

                <?= htmlspecialchars(
                    $vehicle["Make"]
                ) ?>

                <?= htmlspecialchars(
                    $vehicle["Model"]
                ) ?>

            </h1>


            <p class="page-subtitle">
                Complete vehicle registration and ownership information.
            </p>


            <!-- VEHICLE SUMMARY -->

            <section class="card">

                <div
                    style="
                        display:flex;
                        justify-content:space-between;
                        align-items:center;
                        gap:20px;
                        flex-wrap:wrap;
                    "
                >

                    <div>

                        <p
                            style="
                                margin:0 0 6px;
                                color:#6b7280;
                                font-size:14px;
                            "
                        >
                            License Plate
                        </p>

                        <h2 style="margin:0;">

                            <?= htmlspecialchars(
                                $vehicle["PlateNumber"]
                            ) ?>

                        </h2>

                    </div>


                    <div>

                        <p
                            style="
                                margin:0 0 6px;
                                color:#6b7280;
                                font-size:14px;
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

            <section
                class="card"
                style="margin-top:20px;"
            >

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
                                font-size:16px;
                                font-weight:bold;
                                word-break:break-all;
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
                                font-size:20px;
                                font-weight:bold;
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


            <!-- MAINTENANCE & SERVICE -->

            <section
                class="card maintenance-section"
            >

                <h2>
                    Maintenance & Service
                </h2>

                <p
                    style="
                        color:#6b7280;
                        margin:0;
                        font-size:13px;
                    "
                >
                    Current mileage, service history, and oil-change information.
                </p>


                <div class="maintenance-grid">


                    <!-- CURRENT MILEAGE -->

                    <div class="maintenance-card">

                        <h3>
                            Current Mileage
                        </h3>

                        <div class="maintenance-card-value">

                            <?php if ($currentMileage !== null): ?>

                                <?= number_format(
                                    $currentMileage
                                ) ?> km

                            <?php else: ?>

                                Not provided

                            <?php endif; ?>

                        </div>

                        <div class="maintenance-card-help">
                            Current recorded vehicle mileage.
                        </div>

                    </div>


                    <!-- LAST SERVICE -->

                    <div class="maintenance-card">

                        <h3>
                            Last Service
                        </h3>

                        <div class="maintenance-card-value">

                            <?= $vehicle["LastServiceDate"]
                                ? htmlspecialchars(
                                    formatDate(
                                        $vehicle["LastServiceDate"]
                                    )
                                )
                                : "Not provided"
                            ?>

                        </div>

                        <div class="maintenance-card-help">
                            Most recent recorded service date.
                        </div>

                    </div>


                    <!-- OIL CHANGE INTERVAL -->

                    <div class="maintenance-card">

                        <h3>
                            Oil Change Interval
                        </h3>

                        <div class="maintenance-card-value">

                            <?php if ($oilInterval !== null): ?>

                                <?= number_format(
                                    $oilInterval
                                ) ?> km

                            <?php else: ?>

                                Not provided

                            <?php endif; ?>

                        </div>

                        <div class="maintenance-card-help">
                            Recommended distance between oil changes.
                        </div>

                    </div>


                    <!-- LAST OIL CHANGE DATE -->

                    <div class="maintenance-card">

                        <h3>
                            Last Oil Change
                        </h3>

                        <div class="maintenance-card-value">

                            <?= $vehicle["LastOilChangeDate"]
                                ? htmlspecialchars(
                                    formatDate(
                                        $vehicle["LastOilChangeDate"]
                                    )
                                )
                                : "Not provided"
                            ?>

                        </div>

                        <div class="maintenance-card-help">
                            Date of the most recent oil change.
                        </div>

                    </div>


                    <!-- LAST OIL CHANGE MILEAGE -->

                    <div class="maintenance-card">

                        <h3>
                            Mileage at Last Oil Change
                        </h3>

                        <div class="maintenance-card-value">

                            <?php if ($lastOilMileage !== null): ?>

                                <?= number_format(
                                    $lastOilMileage
                                ) ?> km

                            <?php else: ?>

                                Not provided

                            <?php endif; ?>

                        </div>

                        <div class="maintenance-card-help">
                            Mileage recorded when the oil was last changed.
                        </div>

                    </div>


                    <!-- NEXT OIL CHANGE -->

                    <div class="maintenance-card">

                        <h3>
                            Next Oil Change Mileage
                        </h3>

                        <div class="maintenance-card-value">

                            <?php if (
                                $lastOilMileage !== null &&
                                $oilInterval !== null &&
                                $oilInterval > 0
                            ): ?>

                                <?= number_format(
                                    $lastOilMileage +
                                    $oilInterval
                                ) ?> km

                            <?php else: ?>

                                Not available

                            <?php endif; ?>

                        </div>

                        <div class="maintenance-card-help">
                            Estimated mileage when the next oil change is due.
                        </div>

                    </div>


                </div>


                <!-- OIL CHANGE STATUS -->

                <?php

                $oilStatusClass = "";

                if (
                    $mileageUntilOilChange !== null
                ) {

                    if (
                        $mileageUntilOilChange <= 0
                    ) {

                        $oilStatusClass =
                            "maintenance-status-due";

                    } elseif (
                        $mileageUntilOilChange <= 1000
                    ) {

                        $oilStatusClass =
                            "maintenance-status-warning";

                    } else {

                        $oilStatusClass =
                            "maintenance-status-ok";

                    }

                }

                ?>


                <div
                    class="
                        maintenance-status
                        <?= $oilStatusClass ?>
                    "
                >

                    <div class="maintenance-status-title">
                        Oil Change Status
                    </div>

                    <div class="maintenance-status-value">

                        <?= htmlspecialchars(
                            $oilChangeStatus
                        ) ?>

                        <?php if (
                            $mileageUntilOilChange !== null
                        ): ?>

                            <?php if (
                                $mileageUntilOilChange > 0
                            ): ?>

                                — <?= number_format(
                                    $mileageUntilOilChange
                                ) ?> km remaining

                            <?php endif; ?>

                        <?php endif; ?>

                    </div>

                </div>


                <div class="maintenance-info-note">

                    <strong>
                        Maintenance information:
                    </strong>

                    Oil-change status is calculated using the current
                    mileage, the mileage at the last oil change, and the
                    configured oil-change interval.

                </div>

            </section>


            <!-- ADDITIONAL INFORMATION -->

            <section
                class="card"
                style="margin-top:20px;"
            >

                <h2>
                    Additional Vehicle Information
                </h2>


                <table
                    style="
                        width:100%;
                        border-collapse:collapse;
                    "
                >


                    <tr
                        style="
                            border-bottom:1px solid #e5e7eb;
                        "
                    >

                        <td
                            style="
                                padding:12px;
                                font-weight:bold;
                                width:35%;
                            "
                        >
                            Color
                        </td>

                        <td style="padding:12px;">

                            <?= htmlspecialchars(
                                $vehicle["Color"]
                                ?: "Not provided"
                            ) ?>

                        </td>

                    </tr>


                    <tr
                        style="
                            border-bottom:1px solid #e5e7eb;
                        "
                    >

                        <td
                            style="
                                padding:12px;
                                font-weight:bold;
                            "
                        >
                            Vehicle Type
                        </td>

                        <td style="padding:12px;">

                            <?= htmlspecialchars(
                                $vehicle["VehicleType"]
                                ?: "Not provided"
                            ) ?>

                        </td>

                    </tr>


                    <tr
                        style="
                            border-bottom:1px solid #e5e7eb;
                        "
                    >

                        <td
                            style="
                                padding:12px;
                                font-weight:bold;
                            "
                        >
                            Engine Number
                        </td>

                        <td style="padding:12px;">

                            <?= htmlspecialchars(
                                $vehicle["EngineNumber"]
                                ?: "Not provided"
                            ) ?>

                        </td>

                    </tr>


                    <tr
                        style="
                            border-bottom:1px solid #e5e7eb;
                        "
                    >

                        <td
                            style="
                                padding:12px;
                                font-weight:bold;
                            "
                        >
                            Registration Date
                        </td>

                        <td style="padding:12px;">

                            <?= $vehicle["RegistrationDate"]
                                ? htmlspecialchars(
                                    formatDate(
                                        $vehicle["RegistrationDate"]
                                    )
                                )
                                : "Not provided"
                            ?>

                        </td>

                    </tr>


                    <tr>

                        <td
                            style="
                                padding:12px;
                                font-weight:bold;
                            "
                        >
                            Status
                        </td>

                        <td style="padding:12px;">

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
                style="margin-top:20px;"
            >

                <h2>
                    Current Owner
                </h2>


                <table
                    style="
                        width:100%;
                        border-collapse:collapse;
                    "
                >


                    <tr
                        style="
                            border-bottom:1px solid #e5e7eb;
                        "
                    >

                        <td
                            style="
                                padding:12px;
                                font-weight:bold;
                                width:35%;
                            "
                        >
                            Full Name
                        </td>

                        <td style="padding:12px;">

                            <?= htmlspecialchars(
                                $vehicle["OwnerFirstName"]
                                . " "
                                . $vehicle["OwnerLastName"]
                            ) ?>

                        </td>

                    </tr>


                    <tr
                        style="
                            border-bottom:1px solid #e5e7eb;
                        "
                    >

                        <td
                            style="
                                padding:12px;
                                font-weight:bold;
                            "
                        >
                            Address
                        </td>

                        <td style="padding:12px;">

                            <?= htmlspecialchars(
                                $vehicle["OwnerAddress"]
                                ?: "Not provided"
                            ) ?>

                        </td>

                    </tr>


                    <tr
                        style="
                            border-bottom:1px solid #e5e7eb;
                        "
                    >

                        <td
                            style="
                                padding:12px;
                                font-weight:bold;
                            "
                        >
                            Phone
                        </td>

                        <td style="padding:12px;">

                            <?= htmlspecialchars(
                                $vehicle["OwnerPhone"]
                                ?: "Not provided"
                            ) ?>

                        </td>

                    </tr>


                    <tr>

                        <td
                            style="
                                padding:12px;
                                font-weight:bold;
                            "
                        >
                            Email
                        </td>

                        <td style="padding:12px;">

                            <?= htmlspecialchars(
                                $vehicle["OwnerEmail"]
                                ?: "Not provided"
                            ) ?>

                        </td>

                    </tr>


                </table>

            </section>


            <!-- RELATED RECORDS -->

            <section
                class="card"
                style="margin-top:20px;"
            >

                <h2>
                    Related Records
                </h2>


                <p
                    style="
                        color:#6b7280;
                        margin-bottom:20px;
                    "
                >
                    Records associated with this vehicle.
                </p>


                <div
                    style="
                        display:flex;
                        gap:10px;
                        flex-wrap:wrap;
                    "
                >


                    <a
                        href="../ownership_history/?vehicle_id=<?= (int) $vehicle["VehicleID"] ?>"
                        class="button button-secondary"
                    >

                        Ownership History
                        (<?= $ownershipCount ?>)

                    </a>


                    <a
                        href="../insurance/?vehicle_id=<?= (int) $vehicle["VehicleID"] ?>"
                        class="button button-secondary"
                    >

                        Insurance Records
                        (<?= $insuranceCount ?>)

                    </a>


                    <a
                        href="../accidents/?vehicle_id=<?= (int) $vehicle["VehicleID"] ?>"
                        class="button button-secondary"
                    >

                        Accident Records
                        (<?= $accidentCount ?>)

                    </a>


                </div>

            </section>


            <!-- ACTION BUTTONS -->

            <div
                style="
                    display:flex;
                    gap:10px;
                    margin-top:20px;
                    flex-wrap:wrap;
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
                        href="edit.php?id=<?= (int) $vehicle["VehicleID"] ?>"
                        class="button"
                    >
                        Edit Vehicle
                    </a>

                <?php endif; ?>


            </div>


        </main>


    </main>


</div>


<?php include __DIR__ . "/../includes/footer.php"; ?>

</body>

</html>