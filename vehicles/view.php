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

                            <?= htmlspecialchars(
                                $vehicle["RegistrationDate"]
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