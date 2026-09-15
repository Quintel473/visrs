<?php

require_once __DIR__ . "/../includes/auth.php";
requireRole(["Admin", "Police", "Seller"]);

require_once __DIR__ . "/../includes/database.php";
require_once __DIR__ . "/../includes/functions.php";

$pageTitle = "Edit Vehicle";
$pageSubtitle = "Update the vehicle information below";

$activePage = "vehicles";

$error = "";
$success = "";


/*
|--------------------------------------------------------------------------
| Get Vehicle ID
|--------------------------------------------------------------------------
*/

$vehicleID = isset($_GET["id"])
    ? (int) $_GET["id"]
    : (int) ($_POST["vehicle_id"] ?? 0);


if ($vehicleID <= 0) {

    header("Location: index.php");
    exit;

}


/*
|--------------------------------------------------------------------------
| Load Vehicle
|--------------------------------------------------------------------------
*/

$stmt = $pdo->prepare("
    SELECT
        VehicleID,
        PlateNumber,
        VIN,
        OwnerID,
        Make,
        Model,
        VehicleYear,
        Mileage,
        LastServiceDate,
        OilChangeIntervalKm,
        LastOilChangeDate,
        LastOilChangeMileage,
        Color,
        VehicleType,
        EngineNumber,
        RegistrationDate,
        Status
    FROM vehicles
    WHERE VehicleID = ?
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
                        The requested vehicle does not exist.
                    </h2>

                    <p>
                        The vehicle may have been deleted
                        or the requested ID is invalid.
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
| Load Owners
|--------------------------------------------------------------------------
*/

$ownerStmt = $pdo->query("
    SELECT
        OwnerID,
        FirstName,
        LastName
    FROM owners
    ORDER BY LastName ASC, FirstName ASC
");

$owners = $ownerStmt->fetchAll();


/*
|--------------------------------------------------------------------------
| Allowed Values
|--------------------------------------------------------------------------
*/

$allowedStatuses = [
    "Active",
    "Inactive",
    "Stolen",
    "Sold"
];

$allowedVehicleTypes = [
    "",
    "Car",
    "SUV",
    "Truck",
    "Van",
    "Motorcycle",
    "Bus",
    "Other"
];


/*
|--------------------------------------------------------------------------
| Handle Update
|--------------------------------------------------------------------------
*/

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    /*
    |--------------------------------------------------------------------------
    | CSRF Protection
    |--------------------------------------------------------------------------
    */

    requireValidCSRF();


    /*
    |--------------------------------------------------------------------------
    | Verify Submitted Vehicle ID
    |--------------------------------------------------------------------------
    */

    $submittedVehicleID =
        (int) ($_POST["vehicle_id"] ?? 0);


    if (
        $submittedVehicleID !==
        $vehicleID
    ) {

        $error =
            "Invalid vehicle update request.";

    }


    /*
    |--------------------------------------------------------------------------
    | Collect Form Values
    |--------------------------------------------------------------------------
    */

    $plateNumber = trim(
        $_POST["plate_number"] ?? ""
    );

    $vin = trim(
        $_POST["vin"] ?? ""
    );

    $ownerID = trim(
        $_POST["owner_id"] ?? ""
    );

    $make = trim(
        $_POST["make"] ?? ""
    );

    $model = trim(
        $_POST["model"] ?? ""
    );

    $vehicleYear = trim(
        $_POST["vehicle_year"] ?? ""
    );

    $mileage = trim(
        $_POST["mileage"] ?? ""
    );

    $lastServiceDate = trim(
        $_POST["last_service_date"] ?? ""
    );

    $oilChangeIntervalKm = trim(
        $_POST["oil_change_interval_km"] ?? ""
    );

    $lastOilChangeDate = trim(
        $_POST["last_oil_change_date"] ?? ""
    );

    $lastOilChangeMileage = trim(
        $_POST["last_oil_change_mileage"] ?? ""
    );

    $color = trim(
        $_POST["color"] ?? ""
    );

    $vehicleType = trim(
        $_POST["vehicle_type"] ?? ""
    );

    $engineNumber = trim(
        $_POST["engine_number"] ?? ""
    );

    $registrationDate = trim(
        $_POST["registration_date"] ?? ""
    );

    $status = $_POST["status"] ?? "";


    /*
    |--------------------------------------------------------------------------
    | Preserve Submitted Values When Validation Fails
    |--------------------------------------------------------------------------
    */

    if ($error === "") {

        $vehicle["PlateNumber"] =
            $plateNumber;

        $vehicle["VIN"] =
            $vin;

        $vehicle["OwnerID"] =
            $ownerID;

        $vehicle["Make"] =
            $make;

        $vehicle["Model"] =
            $model;

        $vehicle["VehicleYear"] =
            $vehicleYear;

        $vehicle["Mileage"] =
            $mileage;

        $vehicle["LastServiceDate"] =
            $lastServiceDate;

        $vehicle["OilChangeIntervalKm"] =
            $oilChangeIntervalKm;

        $vehicle["LastOilChangeDate"] =
            $lastOilChangeDate;

        $vehicle["LastOilChangeMileage"] =
            $lastOilChangeMileage;

        $vehicle["Color"] =
            $color;

        $vehicle["VehicleType"] =
            $vehicleType;

        $vehicle["EngineNumber"] =
            $engineNumber;

        $vehicle["RegistrationDate"] =
            $registrationDate;

        $vehicle["Status"] =
            $status;

    }


    /*
    |--------------------------------------------------------------------------
    | Required Field Validation
    |--------------------------------------------------------------------------
    */

    if (
        $error === "" &&
        (
            $plateNumber === "" ||
            $vin === "" ||
            $ownerID === "" ||
            $make === "" ||
            $model === "" ||
            $vehicleYear === ""
        )
    ) {

        $error =
            "Please complete all required fields.";

    }


    /*
    |--------------------------------------------------------------------------
    | Owner ID Validation
    |--------------------------------------------------------------------------
    */

    if ($error === "") {

        if (
            !ctype_digit($ownerID) ||
            (int) $ownerID <= 0
        ) {

            $error =
                "Please select a valid vehicle owner.";

        }

    }


    /*
    |--------------------------------------------------------------------------
    | Verify Owner Exists
    |--------------------------------------------------------------------------
    */

    if ($error === "") {

        $ownerCheck = $pdo->prepare("
            SELECT OwnerID
            FROM owners
            WHERE OwnerID = ?
            LIMIT 1
        ");

        $ownerCheck->execute([
            (int) $ownerID
        ]);

        $ownerExists =
            $ownerCheck->fetch();


        if (!$ownerExists) {

            $error =
                "The selected vehicle owner does not exist.";

        }

    }


    /*
    |--------------------------------------------------------------------------
    | Vehicle Year Validation
    |--------------------------------------------------------------------------
    */

    if ($error === "") {

        $currentYear =
            (int) date("Y");


        if (
            !ctype_digit($vehicleYear) ||
            (int) $vehicleYear < 1900 ||
            (int) $vehicleYear > $currentYear
        ) {

            $error =
                "Please enter a valid vehicle year.";

        }

    }


    /*
    |--------------------------------------------------------------------------
    | Mileage Validation
    |--------------------------------------------------------------------------
    */

    if (
        $error === "" &&
        $mileage !== ""
    ) {

        if (
            !ctype_digit($mileage) ||
            (int) $mileage < 0
        ) {

            $error =
                "Please enter a valid vehicle mileage.";

        }

    }


    /*
    |--------------------------------------------------------------------------
    | Oil Change Interval Validation
    |--------------------------------------------------------------------------
    */

    if (
        $error === "" &&
        $oilChangeIntervalKm !== ""
    ) {

        if (
            !ctype_digit($oilChangeIntervalKm) ||
            (int) $oilChangeIntervalKm <= 0
        ) {

            $error =
                "Please enter a valid oil change interval.";

        }

    }


    /*
    |--------------------------------------------------------------------------
    | Last Oil Change Mileage Validation
    |--------------------------------------------------------------------------
    */

    if (
        $error === "" &&
        $lastOilChangeMileage !== ""
    ) {

        if (
            !ctype_digit($lastOilChangeMileage) ||
            (int) $lastOilChangeMileage < 0
        ) {

            $error =
                "Please enter a valid last oil change mileage.";

        }

    }


    /*
    |--------------------------------------------------------------------------
    | Last Oil Change Mileage vs Current Mileage
    |--------------------------------------------------------------------------
    */

    if (
        $error === "" &&
        $mileage !== "" &&
        $lastOilChangeMileage !== ""
    ) {

        if (
            (int) $lastOilChangeMileage >
            (int) $mileage
        ) {

            $error =
                "Last oil change mileage cannot be greater than the current vehicle mileage.";

        }

    }


    /*
    |--------------------------------------------------------------------------
    | Last Service Date Validation
    |--------------------------------------------------------------------------
    */

    if (
        $error === "" &&
        $lastServiceDate !== ""
    ) {

        $dateObject =
            DateTime::createFromFormat(
                "Y-m-d",
                $lastServiceDate
            );

        $dateErrors =
            DateTime::getLastErrors();


        if ($dateErrors === false) {

            $dateErrors = [
                "warning_count" => 0,
                "error_count" => 0
            ];

        }


        if (
            !$dateObject ||
            $dateErrors["warning_count"] > 0 ||
            $dateErrors["error_count"] > 0 ||
            $dateObject->format("Y-m-d")
                !== $lastServiceDate
        ) {

            $error =
                "Please enter a valid last service date.";

        }

    }


    /*
    |--------------------------------------------------------------------------
    | Prevent Future Last Service Date
    |--------------------------------------------------------------------------
    */

    if (
        $error === "" &&
        $lastServiceDate !== ""
    ) {

        if (
            $lastServiceDate >
            date("Y-m-d")
        ) {

            $error =
                "Last service date cannot be in the future.";

        }

    }


    /*
    |--------------------------------------------------------------------------
    | Last Oil Change Date Validation
    |--------------------------------------------------------------------------
    */

    if (
        $error === "" &&
        $lastOilChangeDate !== ""
    ) {

        $dateObject =
            DateTime::createFromFormat(
                "Y-m-d",
                $lastOilChangeDate
            );

        $dateErrors =
            DateTime::getLastErrors();


        if ($dateErrors === false) {

            $dateErrors = [
                "warning_count" => 0,
                "error_count" => 0
            ];

        }


        if (
            !$dateObject ||
            $dateErrors["warning_count"] > 0 ||
            $dateErrors["error_count"] > 0 ||
            $dateObject->format("Y-m-d")
                !== $lastOilChangeDate
        ) {

            $error =
                "Please enter a valid last oil change date.";

        }

    }


    /*
    |--------------------------------------------------------------------------
    | Prevent Future Last Oil Change Date
    |--------------------------------------------------------------------------
    */

    if (
        $error === "" &&
        $lastOilChangeDate !== ""
    ) {

        if (
            $lastOilChangeDate >
            date("Y-m-d")
        ) {

            $error =
                "Last oil change date cannot be in the future.";

        }

    }


    /*
    |--------------------------------------------------------------------------
    | Status Validation
    |--------------------------------------------------------------------------
    */

    if ($error === "") {

        if (
            !in_array(
                $status,
                $allowedStatuses,
                true
            )
        ) {

            $error =
                "Invalid vehicle status.";

        }

    }


    /*
    |--------------------------------------------------------------------------
    | Vehicle Type Validation
    |--------------------------------------------------------------------------
    */

    if ($error === "") {

        if (
            !in_array(
                $vehicleType,
                $allowedVehicleTypes,
                true
            )
        ) {

            $error =
                "Invalid vehicle type.";

        }

    }


    /*
    |--------------------------------------------------------------------------
    | Registration Date Validation
    |--------------------------------------------------------------------------
    */

    if (
        $error === "" &&
        $registrationDate !== ""
    ) {

        $dateObject =
            DateTime::createFromFormat(
                "Y-m-d",
                $registrationDate
            );

        $dateErrors =
            DateTime::getLastErrors();


        if ($dateErrors === false) {

            $dateErrors = [
                "warning_count" => 0,
                "error_count" => 0
            ];

        }


        if (
            !$dateObject ||
            $dateErrors["warning_count"] > 0 ||
            $dateErrors["error_count"] > 0 ||
            $dateObject->format("Y-m-d")
                !== $registrationDate
        ) {

            $error =
                "Please enter a valid registration date.";

        }

    }


    /*
    |--------------------------------------------------------------------------
    | Prevent Future Registration Date
    |--------------------------------------------------------------------------
    */

    if (
        $error === "" &&
        $registrationDate !== ""
    ) {

        if (
            $registrationDate >
            date("Y-m-d")
        ) {

            $error =
                "Registration date cannot be in the future.";

        }

    }


    /*
    |--------------------------------------------------------------------------
    | Registration Date vs Vehicle Year
    |--------------------------------------------------------------------------
    */

    if (
        $error === "" &&
        $registrationDate !== ""
    ) {

        $registrationYear =
            (int) date(
                "Y",
                strtotime($registrationDate)
            );


        if (
            $registrationYear <
            (int) $vehicleYear
        ) {

            $error =
                "Registration date cannot be earlier than the vehicle year.";

        }

    }


    /*
    |--------------------------------------------------------------------------
    | Duplicate Plate / VIN Check
    |--------------------------------------------------------------------------
    */

    if ($error === "") {

        $duplicateStmt = $pdo->prepare("
            SELECT
                VehicleID,
                PlateNumber,
                VIN
            FROM vehicles
            WHERE
                (PlateNumber = ? OR VIN = ?)
                AND VehicleID != ?
            LIMIT 1
        ");

        $duplicateStmt->execute([
            $plateNumber,
            $vin,
            $vehicleID
        ]);

        $duplicate =
            $duplicateStmt->fetch();


        if ($duplicate) {

            if (
                strcasecmp(
                    $duplicate["PlateNumber"],
                    $plateNumber
                ) === 0
            ) {

                $error =
                    "Another vehicle already uses this plate number.";

            } elseif (
                strcasecmp(
                    $duplicate["VIN"],
                    $vin
                ) === 0
            ) {

                $error =
                    "Another vehicle already uses this VIN.";

            } else {

                $error =
                    "Another vehicle already uses this plate number or VIN.";

            }

        }

    }


    /*
    |--------------------------------------------------------------------------
    | Update Vehicle
    |--------------------------------------------------------------------------
    */

    if ($error === "") {

        try {

            $pdo->beginTransaction();


            $updateStmt = $pdo->prepare("
                UPDATE vehicles
                SET
                    PlateNumber = ?,
                    VIN = ?,
                    OwnerID = ?,
                    Make = ?,
                    Model = ?,
                    VehicleYear = ?,
                    Mileage = ?,
                    LastServiceDate = ?,
                    OilChangeIntervalKm = ?,
                    LastOilChangeDate = ?,
                    LastOilChangeMileage = ?,
                    Color = ?,
                    VehicleType = ?,
                    EngineNumber = ?,
                    RegistrationDate = ?,
                    Status = ?
                WHERE VehicleID = ?
            ");


            $updateStmt->execute([

                $plateNumber,

                $vin,

                (int) $ownerID,

                $make,

                $model,

                (int) $vehicleYear,

                $mileage !== ""
                    ? (int) $mileage
                    : null,

                $lastServiceDate !== ""
                    ? $lastServiceDate
                    : null,

                $oilChangeIntervalKm !== ""
                    ? (int) $oilChangeIntervalKm
                    : null,

                $lastOilChangeDate !== ""
                    ? $lastOilChangeDate
                    : null,

                $lastOilChangeMileage !== ""
                    ? (int) $lastOilChangeMileage
                    : null,

                $color !== ""
                    ? $color
                    : null,

                $vehicleType !== ""
                    ? $vehicleType
                    : null,

                $engineNumber !== ""
                    ? $engineNumber
                    : null,

                $registrationDate !== ""
                    ? $registrationDate
                    : null,

                $status,

                $vehicleID

            ]);


            /*
            |--------------------------------------------------------------------------
            | Audit Log
            |--------------------------------------------------------------------------
            */

            $logStmt = $pdo->prepare("
                INSERT INTO audit_logs (
                    UserID,
                    Action,
                    TableAffected,
                    RecordID,
                    IPAddress
                )
                VALUES (?, ?, ?, ?, ?)
            ");


            $logStmt->execute([

                (int) $_SESSION["UserID"],

                "Updated vehicle",

                "vehicles",

                $vehicleID,

                $_SERVER["REMOTE_ADDR"] ?? null

            ]);


            /*
            |--------------------------------------------------------------------------
            | Commit Transaction
            |--------------------------------------------------------------------------
            */

            $pdo->commit();


            /*
            |--------------------------------------------------------------------------
            | Reload Updated Vehicle
            |--------------------------------------------------------------------------
            */

            $reloadStmt = $pdo->prepare("
                SELECT
                    VehicleID,
                    PlateNumber,
                    VIN,
                    OwnerID,
                    Make,
                    Model,
                    VehicleYear,
                    Mileage,
                    LastServiceDate,
                    OilChangeIntervalKm,
                    LastOilChangeDate,
                    LastOilChangeMileage,
                    Color,
                    VehicleType,
                    EngineNumber,
                    RegistrationDate,
                    Status
                FROM vehicles
                WHERE VehicleID = ?
                LIMIT 1
            ");

            $reloadStmt->execute([
                $vehicleID
            ]);

            $vehicle =
                $reloadStmt->fetch();


            $success =
                "Vehicle updated successfully.";


        } catch (Throwable $e) {

            /*
            |--------------------------------------------------------------------------
            | Roll Back Failed Transaction
            |--------------------------------------------------------------------------
            */

            if ($pdo->inTransaction()) {

                $pdo->rollBack();

            }


            $error =
                "Unable to update the vehicle. Please try again.";

        }

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
        <?= htmlspecialchars($pageTitle) ?> - VISRS
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
            margin-top: 30px;
            padding-top: 25px;
            border-top: 1px solid #e5e7eb;
        }

        .maintenance-section-title {
            margin: 0 0 6px;
            font-size: 18px;
            font-weight: 700;
            color: #111827;
        }

        .maintenance-section-description {
            margin: 0 0 20px;
            color: #6b7280;
            font-size: 13px;
            line-height: 1.5;
        }

        .maintenance-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 20px;
        }

        .maintenance-field-help {
            display: block;
            margin-top: 5px;
            color: #6b7280;
            font-size: 12px;
            line-height: 1.4;
        }

        .maintenance-info-note {
            margin-top: 18px;
            padding: 14px 16px;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            background: #f9fafb;
            color: #4b5563;
            font-size: 12px;
            line-height: 1.6;
        }

        .maintenance-info-note strong {
            color: #111827;
        }

        @media (max-width: 700px) {

            .maintenance-grid {
                grid-template-columns: 1fr;
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


            <h1 class="page-title">
                Edit Vehicle
            </h1>


            <p class="page-subtitle">
                Update the vehicle information below.
            </p>


            <?php if ($error !== ""): ?>

                <div
                    class="error-message"
                    style="
                        margin-bottom:20px;
                    "
                >

                    <?= htmlspecialchars($error) ?>

                </div>

            <?php endif; ?>


            <?php if ($success !== ""): ?>

                <div
                    class="auto-dismiss"
                    style="
                        background:#dcfce7;
                        color:#166534;
                        padding:12px;
                        border-radius:6px;
                        margin-bottom:20px;
                    "
                >

                    <?= htmlspecialchars($success) ?>

                </div>

            <?php endif; ?>


            <section class="card">


                <h2>
                    Vehicle Information
                </h2>


                <form
                    method="POST"
                    action=""
                >


                    <!-- CSRF TOKEN -->

                    <input
                        type="hidden"
                        name="csrf_token"
                        value="<?= htmlspecialchars(
                            generateCSRFToken()
                        ) ?>"
                    >


                    <!-- VEHICLE ID -->

                    <input
                        type="hidden"
                        name="vehicle_id"
                        value="<?= (int) $vehicle["VehicleID"] ?>"
                    >


                    <!-- PLATE NUMBER -->

                    <div class="form-group">

                        <label for="plate_number">
                            License Plate Number *
                        </label>

                        <input
                            type="text"
                            id="plate_number"
                            name="plate_number"
                            value="<?= htmlspecialchars(
                                $vehicle["PlateNumber"]
                            ) ?>"
                            maxlength="20"
                            required
                        >

                    </div>


                    <!-- VIN -->

                    <div class="form-group">

                        <label for="vin">
                            VIN *
                        </label>

                        <input
                            type="text"
                            id="vin"
                            name="vin"
                            value="<?= htmlspecialchars(
                                $vehicle["VIN"]
                            ) ?>"
                            maxlength="50"
                            required
                        >

                    </div>


                    <!-- OWNER -->

                    <div class="form-group">

                        <label for="owner_id">
                            Vehicle Owner *
                        </label>

                        <?php if (!empty($owners)): ?>

                            <select
                                id="owner_id"
                                name="owner_id"
                                required
                                style="
                                    width:100%;
                                    padding:12px;
                                    border:1px solid #d1d5db;
                                    border-radius:6px;
                                    font-size:15px;
                                    background:white;
                                "
                            >

                                <option value="">
                                    Select an owner
                                </option>


                                <?php foreach (
                                    $owners as $owner
                                ): ?>

                                    <option
                                        value="<?= (int) $owner["OwnerID"] ?>"
                                        <?= (
                                            (string) $vehicle["OwnerID"]
                                            ===
                                            (string) $owner["OwnerID"]
                                        )
                                            ? "selected"
                                            : ""
                                        ?>
                                    >

                                        <?= htmlspecialchars(
                                            $owner["FirstName"]
                                            . " "
                                            . $owner["LastName"]
                                        ) ?>

                                    </option>

                                <?php endforeach; ?>

                            </select>


                        <?php else: ?>

                            <p
                                style="
                                    color:#991b1b;
                                    background:#fee2e2;
                                    padding:12px;
                                    border-radius:6px;
                                "
                            >

                                No owners have been registered.

                                Please add an owner before
                                editing this vehicle.

                            </p>

                        <?php endif; ?>

                    </div>


                    <!-- MAKE -->

                    <div class="form-group">

                        <label for="make">
                            Make *
                        </label>

                        <input
                            type="text"
                            id="make"
                            name="make"
                            value="<?= htmlspecialchars(
                                $vehicle["Make"]
                            ) ?>"
                            maxlength="100"
                            required
                        >

                    </div>


                    <!-- MODEL -->

                    <div class="form-group">

                        <label for="model">
                            Model *
                        </label>

                        <input
                            type="text"
                            id="model"
                            name="model"
                            value="<?= htmlspecialchars(
                                $vehicle["Model"]
                            ) ?>"
                            maxlength="100"
                            required
                        >

                    </div>


                    <!-- VEHICLE YEAR -->

                    <div class="form-group">

                        <label for="vehicle_year">
                            Vehicle Year *
                        </label>

                        <input
                            type="number"
                            id="vehicle_year"
                            name="vehicle_year"
                            min="1900"
                            max="<?= date("Y") ?>"
                            value="<?= htmlspecialchars(
                                $vehicle["VehicleYear"]
                            ) ?>"
                            required
                        >

                    </div>


                    <!-- COLOR -->

                    <div class="form-group">

                        <label for="color">
                            Color
                        </label>

                        <input
                            type="text"
                            id="color"
                            name="color"
                            value="<?= htmlspecialchars(
                                $vehicle["Color"] ?? ""
                            ) ?>"
                            maxlength="50"
                        >

                    </div>


                    <!-- VEHICLE TYPE -->

                    <div class="form-group">

                        <label for="vehicle_type">
                            Vehicle Type
                        </label>

                        <select
                            id="vehicle_type"
                            name="vehicle_type"
                            style="
                                width:100%;
                                padding:12px;
                                border:1px solid #d1d5db;
                                border-radius:6px;
                                font-size:15px;
                                background:white;
                            "
                        >

                            <option value="">
                                Select vehicle type
                            </option>


                            <?php
                            $vehicleTypes = [
                                "Car",
                                "SUV",
                                "Truck",
                                "Van",
                                "Motorcycle",
                                "Bus",
                                "Other"
                            ];
                            ?>


                            <?php foreach (
                                $vehicleTypes as $type
                            ): ?>

                                <option
                                    value="<?= htmlspecialchars($type) ?>"
                                    <?= (
                                        ($vehicle["VehicleType"] ?? "")
                                        ===
                                        $type
                                    )
                                        ? "selected"
                                        : ""
                                    ?>
                                >

                                    <?= htmlspecialchars($type) ?>

                                </option>

                            <?php endforeach; ?>

                        </select>

                    </div>


                    <!-- ENGINE NUMBER -->

                    <div class="form-group">

                        <label for="engine_number">
                            Engine Number
                        </label>

                        <input
                            type="text"
                            id="engine_number"
                            name="engine_number"
                            value="<?= htmlspecialchars(
                                $vehicle["EngineNumber"] ?? ""
                            ) ?>"
                            maxlength="100"
                        >

                    </div>


                    <!-- REGISTRATION DATE -->

                    <div class="form-group">

                        <label for="registration_date">
                            Registration Date
                        </label>

                        <input
                            type="date"
                            id="registration_date"
                            name="registration_date"
                            value="<?= htmlspecialchars(
                                $vehicle["RegistrationDate"] ?? ""
                            ) ?>"
                            max="<?= date("Y-m-d") ?>"
                        >

                    </div>


                    <!-- STATUS -->

                    <div class="form-group">

                        <label for="status">
                            Status
                        </label>

                        <select
                            id="status"
                            name="status"
                            style="
                                width:100%;
                                padding:12px;
                                border:1px solid #d1d5db;
                                border-radius:6px;
                                font-size:15px;
                                background:white;
                            "
                        >

                            <?php foreach (
                                $allowedStatuses
                                as $statusOption
                            ): ?>

                                <option
                                    value="<?= htmlspecialchars(
                                        $statusOption
                                    ) ?>"
                                    <?= (
                                        ($vehicle["Status"] ?? "")
                                        ===
                                        $statusOption
                                    )
                                        ? "selected"
                                        : ""
                                    ?>
                                >

                                    <?= htmlspecialchars(
                                        $statusOption
                                    ) ?>

                                </option>

                            <?php endforeach; ?>

                        </select>

                    </div>


                    <!-- MAINTENANCE & SERVICE -->

                    <div class="maintenance-section">

                        <h3 class="maintenance-section-title">
                            Maintenance & Service
                        </h3>

                        <p class="maintenance-section-description">
                            Update the vehicle's mileage and maintenance history.
                        </p>


                        <div class="maintenance-grid">


                            <!-- CURRENT MILEAGE -->

                            <div class="form-group">

                                <label for="mileage">
                                    Current Mileage
                                </label>

                                <input
                                    type="number"
                                    id="mileage"
                                    name="mileage"
                                    min="0"
                                    step="1"
                                    value="<?= htmlspecialchars(
                                        $vehicle["Mileage"] ?? ""
                                    ) ?>"
                                >

                                <span class="maintenance-field-help">
                                    Current vehicle mileage in kilometres.
                                </span>

                            </div>


                            <!-- LAST SERVICE DATE -->

                            <div class="form-group">

                                <label for="last_service_date">
                                    Last Service Date
                                </label>

                                <input
                                    type="date"
                                    id="last_service_date"
                                    name="last_service_date"
                                    value="<?= htmlspecialchars(
                                        $vehicle["LastServiceDate"] ?? ""
                                    ) ?>"
                                    max="<?= date("Y-m-d") ?>"
                                >

                                <span class="maintenance-field-help">
                                    Date the vehicle was most recently serviced.
                                </span>

                            </div>


                            <!-- OIL CHANGE INTERVAL -->

                            <div class="form-group">

                                <label for="oil_change_interval_km">
                                    Oil Change Interval
                                </label>

                                <input
                                    type="number"
                                    id="oil_change_interval_km"
                                    name="oil_change_interval_km"
                                    min="1"
                                    step="1"
                                    value="<?= htmlspecialchars(
                                        $vehicle["OilChangeIntervalKm"] ?? ""
                                    ) ?>"
                                >

                                <span class="maintenance-field-help">
                                    Recommended distance between oil changes in kilometres.
                                </span>

                            </div>


                            <!-- LAST OIL CHANGE DATE -->

                            <div class="form-group">

                                <label for="last_oil_change_date">
                                    Last Oil Change Date
                                </label>

                                <input
                                    type="date"
                                    id="last_oil_change_date"
                                    name="last_oil_change_date"
                                    value="<?= htmlspecialchars(
                                        $vehicle["LastOilChangeDate"] ?? ""
                                    ) ?>"
                                    max="<?= date("Y-m-d") ?>"
                                >

                                <span class="maintenance-field-help">
                                    Date the engine oil was last changed.
                                </span>

                            </div>


                            <!-- LAST OIL CHANGE MILEAGE -->

                            <div class="form-group">

                                <label for="last_oil_change_mileage">
                                    Mileage at Last Oil Change
                                </label>

                                <input
                                    type="number"
                                    id="last_oil_change_mileage"
                                    name="last_oil_change_mileage"
                                    min="0"
                                    step="1"
                                    value="<?= htmlspecialchars(
                                        $vehicle["LastOilChangeMileage"] ?? ""
                                    ) ?>"
                                >

                                <span class="maintenance-field-help">
                                    Vehicle mileage when the last oil change was performed.
                                </span>

                            </div>


                        </div>


                        <div class="maintenance-info-note">

                            <strong>
                                Maintenance information:
                            </strong>

                            Keep the mileage and maintenance information
                            up to date so VISRS can accurately track the
                            vehicle's service history and upcoming oil-change
                            requirements.

                        </div>

                    </div>


                    <!-- BUTTONS -->

                    <div
                        style="
                            display:flex;
                            gap:10px;
                            margin-top:25px;
                            flex-wrap:wrap;
                        "
                    >

                        <button
                            type="submit"
                            class="button"
                            <?= empty($owners)
                                ? "disabled"
                                : ""
                            ?>
                        >
                            Save Changes
                        </button>


                        <a
                            href="view.php?id=<?= (int) $vehicle["VehicleID"] ?>"
                            class="button button-secondary"
                        >
                            Cancel
                        </a>

                    </div>


                </form>


            </section>


        </main>


    </main>


</div>


<?php include __DIR__ . "/../includes/footer.php"; ?>

</body>

</html>