<?php

require_once __DIR__ . "/../includes/auth.php";
requireRole(["Admin", "Police", "Seller"]);

require_once __DIR__ . "/../includes/database.php";
require_once __DIR__ . "/../includes/functions.php";

$pageTitle = "Add Vehicle";
$activePage = "vehicles";

/*
|--------------------------------------------------------------------------
| Seller Association
|--------------------------------------------------------------------------
|
| Sellers are automatically associated with vehicles they create.
| Admin and Police-created vehicles remain unassigned.
|
*/

$currentSellerId = hasRole(["Seller"])
    ? (int) currentUserId()
    : null;

$error = "";
$success = "";

/*
|--------------------------------------------------------------------------
| Form Values
|--------------------------------------------------------------------------
*/

$plateNumber = "";
$vin = "";
$ownerID = "";
$make = "";
$model = "";
$vehicleYear = "";
$mileage = "";
$lastServiceDate = "";
$oilChangeIntervalKm = "";
$lastOilChangeDate = "";
$lastOilChangeMileage = "";
$color = "";
$vehicleType = "";
$engineNumber = "";
$registrationDate = "";
$status = "Active";

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

$owners = $ownerStmt->fetchAll(PDO::FETCH_ASSOC);

/*
|--------------------------------------------------------------------------
| Handle Form Submission
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

    $status = $_POST["status"] ?? "Active";

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
    | Required Field Validation
    |--------------------------------------------------------------------------
    */

    if (
        $plateNumber === "" ||
        $vin === "" ||
        $ownerID === "" ||
        $make === "" ||
        $model === "" ||
        $vehicleYear === ""
    ) {
        $error =
            "Please complete all required fields.";
    }

    /*
    |--------------------------------------------------------------------------
    | Owner ID Validation
    |--------------------------------------------------------------------------
    */

    elseif (
        !ctype_digit($ownerID) ||
        (int) $ownerID <= 0
    ) {
        $error =
            "Please select a valid vehicle owner.";
    }

    /*
    |--------------------------------------------------------------------------
    | Verify Owner Exists
    |--------------------------------------------------------------------------
    */

    else {

        $ownerCheck = $pdo->prepare("
            SELECT OwnerID
            FROM owners
            WHERE OwnerID = ?
            LIMIT 1
        ");

        $ownerCheck->execute([
            (int) $ownerID
        ]);

        $ownerExists = $ownerCheck->fetch();

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

        $currentYear = (int) date("Y");

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

        $dateObject = DateTime::createFromFormat(
            "Y-m-d",
            $lastServiceDate
        );

        $dateErrors = DateTime::getLastErrors();

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
            $dateObject->format("Y-m-d") !== $lastServiceDate
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

        if ($lastServiceDate > date("Y-m-d")) {
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

        $dateObject = DateTime::createFromFormat(
            "Y-m-d",
            $lastOilChangeDate
        );

        $dateErrors = DateTime::getLastErrors();

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
            $dateObject->format("Y-m-d") !== $lastOilChangeDate
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

        if ($lastOilChangeDate > date("Y-m-d")) {
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

        $dateObject = DateTime::createFromFormat(
            "Y-m-d",
            $registrationDate
        );

        $dateErrors = DateTime::getLastErrors();

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
            $dateObject->format("Y-m-d") !== $registrationDate
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

        if ($registrationDate > date("Y-m-d")) {
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
    | Duplicate Plate or VIN Check
    |--------------------------------------------------------------------------
    */

    if ($error === "") {

        $duplicateStmt = $pdo->prepare("
            SELECT
                VehicleID,
                PlateNumber,
                VIN
            FROM vehicles
            WHERE PlateNumber = ?
               OR VIN = ?
            LIMIT 1
        ");

        $duplicateStmt->execute([
            $plateNumber,
            $vin
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
                    "A vehicle with this plate number already exists.";

            } elseif (
                strcasecmp(
                    $duplicate["VIN"],
                    $vin
                ) === 0
            ) {
                $error =
                    "A vehicle with this VIN already exists.";

            } else {
                $error =
                    "A vehicle with this plate number or VIN already exists.";
            }
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Insert Vehicle
    |--------------------------------------------------------------------------
    */

    if ($error === "") {

        try {

            $pdo->beginTransaction();

            $stmt = $pdo->prepare("
                INSERT INTO vehicles (
                    PlateNumber,
                    VIN,
                    OwnerID,
                    SellerID,
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
                )
                VALUES (
                    ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?
                )
            ");

            $stmt->execute([

                $plateNumber,

                $vin,

                (int) $ownerID,

                $currentSellerId,

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

                $status
            ]);

            /*
            |--------------------------------------------------------------------------
            | Get New Vehicle ID
            |--------------------------------------------------------------------------
            */

            $vehicleID =
                (int) $pdo->lastInsertId();

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
                (int) currentUserId(),
                "Added vehicle",
                "vehicles",
                $vehicleID,
                $_SERVER["REMOTE_ADDR"] ?? null
            ]);

            /*
            |--------------------------------------------------------------------------
            | Commit
            |--------------------------------------------------------------------------
            */

            $pdo->commit();

            /*
            |--------------------------------------------------------------------------
            | Success
            |--------------------------------------------------------------------------
            */

            $success =
                "Vehicle added successfully.";

            /*
            |--------------------------------------------------------------------------
            | Clear Form
            |--------------------------------------------------------------------------
            */

            $plateNumber = "";
            $vin = "";
            $ownerID = "";
            $make = "";
            $model = "";
            $vehicleYear = "";
            $mileage = "";
            $lastServiceDate = "";
            $oilChangeIntervalKm = "";
            $lastOilChangeDate = "";
            $lastOilChangeMileage = "";
            $color = "";
            $vehicleType = "";
            $engineNumber = "";
            $registrationDate = "";
            $status = "Active";

        } catch (PDOException $e) {

            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            $error =
                "Unable to add the vehicle. Please try again.";
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
        | Add Vehicle Page
        |--------------------------------------------------------------------------
        */

        .vehicle-form-page {
            max-width: 1100px;
            margin: 0 auto;
        }

        /*
        |--------------------------------------------------------------------------
        | Heading
        |--------------------------------------------------------------------------
        */

        .vehicle-page-heading {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 20px;
            margin-bottom: 25px;
        }

        .vehicle-page-heading h1 {
            margin: 0 0 6px;
            font-size: 25px;
            font-weight: 700;
            color: #111827;
        }

        .vehicle-page-heading p {
            margin: 0;
            font-size: 14px;
            color: #6b7280;
        }

        /*
        |--------------------------------------------------------------------------
        | Alerts
        |--------------------------------------------------------------------------
        */

        .vehicle-alert {
            margin-bottom: 20px;
            padding: 14px 16px;
            border-radius: 8px;
            font-size: 13px;
            line-height: 1.5;
        }

        .vehicle-alert-error {
            background: #fef2f2;
            border: 1px solid #fecaca;
            color: #991b1b;
        }

        .vehicle-alert-success {
            background: #f0fdf4;
            border: 1px solid #bbf7d0;
            color: #166534;
        }

        /*
        |--------------------------------------------------------------------------
        | Form Card
        |--------------------------------------------------------------------------
        */

        .vehicle-form-card {
            background: #ffffff;
            border: 1px solid #e5e7eb;
            border-radius: 10px;
            box-shadow: 0 2px 7px rgba(0, 0, 0, 0.04);
            overflow: hidden;
        }

        .vehicle-form-card-header {
            padding: 20px 24px;
            border-bottom: 1px solid #e5e7eb;
        }

        .vehicle-form-card-header h2 {
            margin: 0 0 5px;
            font-size: 18px;
            font-weight: 700;
            color: #111827;
        }

        .vehicle-form-card-header p {
            margin: 0;
            font-size: 13px;
            color: #6b7280;
        }

        .vehicle-form-card-body {
            padding: 25px;
        }

        /*
        |--------------------------------------------------------------------------
        | Form Sections
        |--------------------------------------------------------------------------
        */

        .vehicle-form-section {
            margin-bottom: 28px;
        }

        .vehicle-form-section:last-child {
            margin-bottom: 0;
        }

        .vehicle-form-section-title {
            margin: 0 0 16px;
            padding-bottom: 10px;
            border-bottom: 1px solid #f0f0f0;
            font-size: 14px;
            font-weight: 700;
            color: #111827;
        }

        /*
        |--------------------------------------------------------------------------
        | Form Grid
        |--------------------------------------------------------------------------
        */

        .vehicle-form-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 20px;
        }

        .vehicle-form-group {
            display: flex;
            flex-direction: column;
        }

        .vehicle-form-group label {
            margin-bottom: 7px;
            font-size: 13px;
            font-weight: 600;
            color: #374151;
        }

        .vehicle-required {
            color: #dc2626;
        }

        /*
        |--------------------------------------------------------------------------
        | Inputs
        |--------------------------------------------------------------------------
        */

        .vehicle-form-group input,
        .vehicle-form-group select {
            width: 100%;
            box-sizing: border-box;
            min-height: 42px;
            padding: 10px 12px;
            border: 1px solid #d1d5db;
            border-radius: 7px;
            background: #ffffff;
            color: #111827;
            font-size: 14px;
            outline: none;
            transition:
                border-color 0.15s ease,
                box-shadow 0.15s ease;
        }

        .vehicle-form-group input::placeholder {
            color: #9ca3af;
        }

        .vehicle-form-group input:focus,
        .vehicle-form-group select:focus {
            border-color: #111827;
            box-shadow:
                0 0 0 3px rgba(17, 24, 39, 0.08);
        }

        .vehicle-form-help {
            margin-top: 5px;
            font-size: 12px;
            color: #6b7280;
            line-height: 1.45;
        }

        /*
        |--------------------------------------------------------------------------
        | Maintenance Information
        |--------------------------------------------------------------------------
        */

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

        /*
        |--------------------------------------------------------------------------
        | Seller Notice
        |--------------------------------------------------------------------------
        */

        .seller-assignment-notice {
            display: flex;
            align-items: flex-start;
            gap: 12px;
            margin-top: 5px;
            padding: 14px 16px;
            border: 1px solid #dbeafe;
            border-radius: 8px;
            background: #eff6ff;
            color: #1e40af;
            font-size: 13px;
            line-height: 1.5;
        }

        .seller-assignment-icon {
            font-size: 17px;
            line-height: 1;
            margin-top: 1px;
        }

        /*
        |--------------------------------------------------------------------------
        | Owner Empty State
        |--------------------------------------------------------------------------
        */

        .no-owner-message {
            padding: 14px 16px;
            border: 1px solid #fecaca;
            border-radius: 8px;
            background: #fef2f2;
            color: #991b1b;
            font-size: 13px;
            line-height: 1.5;
        }

        /*
        |--------------------------------------------------------------------------
        | Buttons
        |--------------------------------------------------------------------------
        */

        .vehicle-form-actions {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
            padding-top: 22px;
            margin-top: 25px;
            border-top: 1px solid #e5e7eb;
        }

        .vehicle-button {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-height: 40px;
            padding: 9px 16px;
            border-radius: 7px;
            text-decoration: none;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            box-sizing: border-box;
            transition:
                background 0.15s ease,
                border-color 0.15s ease;
        }

        .vehicle-button-primary {
            border: 1px solid #111827;
            background: #111827;
            color: #ffffff;
        }

        .vehicle-button-primary:hover {
            background: #1f2937;
            border-color: #1f2937;
        }

        .vehicle-button-secondary {
            border: 1px solid #d1d5db;
            background: #ffffff;
            color: #374151;
        }

        .vehicle-button-secondary:hover {
            background: #f9fafb;
            border-color: #9ca3af;
        }

        .vehicle-button:disabled {
            opacity: 0.55;
            cursor: not-allowed;
        }

        /*
        |--------------------------------------------------------------------------
        | Responsive
        |--------------------------------------------------------------------------
        */

        @media (max-width: 800px) {

            .vehicle-page-heading {
                flex-direction: column;
            }

            .vehicle-form-grid {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 600px) {

            .vehicle-form-card-body {
                padding: 18px;
            }

            .vehicle-form-card-header {
                padding: 18px;
            }

            .vehicle-form-actions {
                flex-direction: column-reverse;
            }

            .vehicle-button {
                width: 100%;
            }
        }

    </style>

</head>

<body>

<div class="layout">

    <?php include __DIR__ . "/../includes/sidebar.php"; ?>

    <main class="main-content">

        <?php include __DIR__ . "/../includes/header.php"; ?>

        <div class="content">

            <div class="vehicle-form-page">

                <!-- PAGE HEADING -->

                <div class="vehicle-page-heading">

                    <div>

                        <h1>
                            Add Vehicle
                        </h1>

                        <p>
                            Register a new vehicle in the VISRS system.
                        </p>

                    </div>

                    <a
                        href="/visrs/vehicles/index.php"
                        class="vehicle-button vehicle-button-secondary"
                    >
                        ← Back to Vehicles
                    </a>

                </div>


                <!-- ERROR -->

                <?php if ($error !== ""): ?>

                    <div class="vehicle-alert vehicle-alert-error">

                        <?= htmlspecialchars($error) ?>

                    </div>

                <?php endif; ?>


                <!-- SUCCESS -->

                <?php if ($success !== ""): ?>

                    <div class="vehicle-alert vehicle-alert-success auto-dismiss">

                        <?= htmlspecialchars($success) ?>

                    </div>

                <?php endif; ?>


                <!-- FORM CARD -->

                <section class="vehicle-form-card">

                    <div class="vehicle-form-card-header">

                        <h2>
                            Vehicle Information
                        </h2>

                        <p>
                            Enter the information below to register a new vehicle.
                        </p>

                    </div>


                    <div class="vehicle-form-card-body">

                        <form
                            method="POST"
                            action=""
                        >

                            <!-- CSRF -->

                            <input
                                type="hidden"
                                name="csrf_token"
                                value="<?= htmlspecialchars(
                                    generateCSRFToken()
                                ) ?>"
                            >


                            <!-- VEHICLE IDENTIFICATION -->

                            <div class="vehicle-form-section">

                                <h3 class="vehicle-form-section-title">
                                    Vehicle Identification
                                </h3>

                                <div class="vehicle-form-grid">

                                    <div class="vehicle-form-group">

                                        <label for="plate_number">

                                            License Plate Number

                                            <span class="vehicle-required">
                                                *
                                            </span>

                                        </label>

                                        <input
                                            type="text"
                                            id="plate_number"
                                            name="plate_number"
                                            placeholder="e.g. P1234"
                                            value="<?= htmlspecialchars(
                                                $plateNumber
                                            ) ?>"
                                            maxlength="20"
                                            required
                                        >

                                    </div>


                                    <div class="vehicle-form-group">

                                        <label for="vin">

                                            VIN

                                            <span class="vehicle-required">
                                                *
                                            </span>

                                        </label>

                                        <input
                                            type="text"
                                            id="vin"
                                            name="vin"
                                            placeholder="Enter vehicle identification number"
                                            value="<?= htmlspecialchars(
                                                $vin
                                            ) ?>"
                                            maxlength="50"
                                            required
                                        >

                                    </div>

                                </div>

                            </div>


                            <!-- VEHICLE DETAILS -->

                            <div class="vehicle-form-section">

                                <h3 class="vehicle-form-section-title">
                                    Vehicle Details
                                </h3>

                                <div class="vehicle-form-grid">

                                    <div class="vehicle-form-group">

                                        <label for="make">

                                            Make

                                            <span class="vehicle-required">
                                                *
                                            </span>

                                        </label>

                                        <input
                                            type="text"
                                            id="make"
                                            name="make"
                                            placeholder="e.g. Toyota"
                                            value="<?= htmlspecialchars(
                                                $make
                                            ) ?>"
                                            maxlength="100"
                                            required
                                        >

                                    </div>


                                    <div class="vehicle-form-group">

                                        <label for="model">

                                            Model

                                            <span class="vehicle-required">
                                                *
                                            </span>

                                        </label>

                                        <input
                                            type="text"
                                            id="model"
                                            name="model"
                                            placeholder="e.g. Corolla"
                                            value="<?= htmlspecialchars(
                                                $model
                                            ) ?>"
                                            maxlength="100"
                                            required
                                        >

                                    </div>


                                    <div class="vehicle-form-group">

                                        <label for="vehicle_year">

                                            Vehicle Year

                                            <span class="vehicle-required">
                                                *
                                            </span>

                                        </label>

                                        <input
                                            type="number"
                                            id="vehicle_year"
                                            name="vehicle_year"
                                            min="1900"
                                            max="<?= date("Y") ?>"
                                            placeholder="<?= date("Y") ?>"
                                            value="<?= htmlspecialchars(
                                                $vehicleYear
                                            ) ?>"
                                            required
                                        >

                                    </div>


                                    <div class="vehicle-form-group">

                                        <label for="color">
                                            Color
                                        </label>

                                        <input
                                            type="text"
                                            id="color"
                                            name="color"
                                            placeholder="e.g. Black"
                                            value="<?= htmlspecialchars(
                                                $color
                                            ) ?>"
                                            maxlength="50"
                                        >

                                    </div>


                                    <div class="vehicle-form-group">

                                        <label for="vehicle_type">
                                            Vehicle Type
                                        </label>

                                        <select
                                            id="vehicle_type"
                                            name="vehicle_type"
                                        >

                                            <option value="">
                                                Select vehicle type
                                            </option>

                                            <?php
                                            $vehicleTypeOptions = [
                                                "Car",
                                                "SUV",
                                                "Truck",
                                                "Van",
                                                "Motorcycle",
                                                "Bus",
                                                "Other"
                                            ];

                                            foreach (
                                                $vehicleTypeOptions
                                                as $type
                                            ):
                                            ?>

                                                <option
                                                    value="<?= htmlspecialchars($type) ?>"
                                                    <?= $vehicleType === $type
                                                        ? "selected"
                                                        : ""
                                                    ?>
                                                >
                                                    <?= htmlspecialchars($type) ?>
                                                </option>

                                            <?php endforeach; ?>

                                        </select>

                                    </div>


                                    <div class="vehicle-form-group">

                                        <label for="engine_number">
                                            Engine Number
                                        </label>

                                        <input
                                            type="text"
                                            id="engine_number"
                                            name="engine_number"
                                            placeholder="Enter engine number"
                                            value="<?= htmlspecialchars(
                                                $engineNumber
                                            ) ?>"
                                            maxlength="100"
                                        >

                                    </div>

                                </div>

                            </div>


                            <!-- MAINTENANCE & SERVICE -->

                            <div class="vehicle-form-section">

                                <h3 class="vehicle-form-section-title">
                                    Maintenance & Service
                                </h3>

                                <div class="vehicle-form-grid">

                                    <!-- CURRENT MILEAGE -->

                                    <div class="vehicle-form-group">

                                        <label for="mileage">
                                            Current Mileage
                                        </label>

                                        <input
                                            type="number"
                                            id="mileage"
                                            name="mileage"
                                            min="0"
                                            step="1"
                                            placeholder="e.g. 85420"
                                            value="<?= htmlspecialchars(
                                                $mileage
                                            ) ?>"
                                        >

                                        <span class="vehicle-form-help">
                                            Current vehicle mileage in kilometres.
                                        </span>

                                    </div>


                                    <!-- LAST SERVICE -->

                                    <div class="vehicle-form-group">

                                        <label for="last_service_date">
                                            Last Service Date
                                        </label>

                                        <input
                                            type="date"
                                            id="last_service_date"
                                            name="last_service_date"
                                            value="<?= htmlspecialchars(
                                                $lastServiceDate
                                            ) ?>"
                                            max="<?= date("Y-m-d") ?>"
                                        >

                                        <span class="vehicle-form-help">
                                            Date the vehicle was most recently serviced.
                                        </span>

                                    </div>


                                    <!-- OIL CHANGE INTERVAL -->

                                    <div class="vehicle-form-group">

                                        <label for="oil_change_interval_km">
                                            Oil Change Interval
                                        </label>

                                        <input
                                            type="number"
                                            id="oil_change_interval_km"
                                            name="oil_change_interval_km"
                                            min="1"
                                            step="1"
                                            placeholder="e.g. 10000"
                                            value="<?= htmlspecialchars(
                                                $oilChangeIntervalKm
                                            ) ?>"
                                        >

                                        <span class="vehicle-form-help">
                                            Recommended distance between oil changes in kilometres.
                                        </span>

                                    </div>


                                    <!-- LAST OIL CHANGE -->

                                    <div class="vehicle-form-group">

                                        <label for="last_oil_change_date">
                                            Last Oil Change Date
                                        </label>

                                        <input
                                            type="date"
                                            id="last_oil_change_date"
                                            name="last_oil_change_date"
                                            value="<?= htmlspecialchars(
                                                $lastOilChangeDate
                                            ) ?>"
                                            max="<?= date("Y-m-d") ?>"
                                        >

                                        <span class="vehicle-form-help">
                                            Date the engine oil was last changed.
                                        </span>

                                    </div>


                                    <!-- LAST OIL CHANGE MILEAGE -->

                                    <div class="vehicle-form-group">

                                        <label for="last_oil_change_mileage">
                                            Mileage at Last Oil Change
                                        </label>

                                        <input
                                            type="number"
                                            id="last_oil_change_mileage"
                                            name="last_oil_change_mileage"
                                            min="0"
                                            step="1"
                                            placeholder="e.g. 75420"
                                            value="<?= htmlspecialchars(
                                                $lastOilChangeMileage
                                            ) ?>"
                                        >

                                        <span class="vehicle-form-help">
                                            Vehicle mileage when the last oil change was performed.
                                        </span>

                                    </div>

                                </div>


                                <div class="maintenance-info-note">

                                    <strong>
                                        Maintenance information:
                                    </strong>

                                    Providing the current mileage and oil-change
                                    history allows VISRS to calculate upcoming
                                    maintenance requirements for the vehicle.

                                </div>

                            </div>


                            <!-- REGISTRATION -->

                            <div class="vehicle-form-section">

                                <h3 class="vehicle-form-section-title">
                                    Registration & Ownership
                                </h3>

                                <div class="vehicle-form-grid">

                                    <!-- OWNER -->

                                    <div class="vehicle-form-group">

                                        <label for="owner_id">

                                            Vehicle Owner

                                            <span class="vehicle-required">
                                                *
                                            </span>

                                        </label>

                                        <?php if (count($owners) > 0): ?>

                                            <select
                                                id="owner_id"
                                                name="owner_id"
                                                required
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
                                                            (string) $ownerID ===
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

                                            <span class="vehicle-form-help">
                                                Select the current registered owner.
                                            </span>

                                        <?php else: ?>

                                            <div class="no-owner-message">

                                                No owners have been registered yet.

                                                Please add an owner before adding
                                                a vehicle.

                                            </div>

                                        <?php endif; ?>

                                    </div>


                                    <!-- REGISTRATION DATE -->

                                    <div class="vehicle-form-group">

                                        <label for="registration_date">
                                            Registration Date
                                        </label>

                                        <input
                                            type="date"
                                            id="registration_date"
                                            name="registration_date"
                                            value="<?= htmlspecialchars(
                                                $registrationDate
                                            ) ?>"
                                            max="<?= date("Y-m-d") ?>"
                                        >

                                    </div>


                                    <!-- STATUS -->

                                    <div class="vehicle-form-group">

                                        <label for="status">
                                            Status
                                        </label>

                                        <select
                                            id="status"
                                            name="status"
                                        >

                                            <?php
                                            $statusOptions = [
                                                "Active",
                                                "Inactive",
                                                "Stolen",
                                                "Sold"
                                            ];

                                            foreach (
                                                $statusOptions
                                                as $statusOption
                                            ):
                                            ?>

                                                <option
                                                    value="<?= htmlspecialchars($statusOption) ?>"
                                                    <?= $status === $statusOption
                                                        ? "selected"
                                                        : ""
                                                    ?>
                                                >
                                                    <?= htmlspecialchars($statusOption) ?>
                                                </option>

                                            <?php endforeach; ?>

                                        </select>

                                    </div>

                                </div>

                            </div>


                            <!-- SELLER NOTICE -->

                            <?php if (hasRole(["Seller"])): ?>

                                <div class="seller-assignment-notice">

                                    <div class="seller-assignment-icon">
                                        ℹ
                                    </div>

                                    <div>

                                        <strong>
                                            Seller Vehicle Assignment
                                        </strong>

                                        <br>

                                        Because you are logged in as a Seller,
                                        this vehicle will automatically be
                                        associated with your seller account.
                                        You will then be able to list this
                                        vehicle for sale in the marketplace.

                                    </div>

                                </div>

                            <?php endif; ?>


                            <!-- ACTIONS -->

                            <div class="vehicle-form-actions">

                                <a
                                    href="/visrs/vehicles/index.php"
                                    class="vehicle-button vehicle-button-secondary"
                                >
                                    Cancel
                                </a>

                                <button
                                    type="submit"
                                    class="vehicle-button vehicle-button-primary"
                                    <?= count($owners) === 0
                                        ? "disabled"
                                        : ""
                                    ?>
                                >
                                    Add Vehicle
                                </button>

                            </div>

                        </form>

                    </div>

                </section>

            </div>

        </div>

    </main>

</div>

<?php include __DIR__ . "/../includes/footer.php"; ?>

</body>

</html>