<?php

require_once "../includes/auth.php";
require_once "../includes/database.php";

$basePath = "../";
$activePage = "vehicles";

$error = "";
$success = "";

/*
|--------------------------------------------------------------------------
| Load Owners
|--------------------------------------------------------------------------
*/

$ownerStmt = $pdo->query("
    SELECT OwnerID, FirstName, LastName
    FROM owners
    ORDER BY LastName ASC, FirstName ASC
");

$owners = $ownerStmt->fetchAll();


/*
|--------------------------------------------------------------------------
| Handle Form Submission
|--------------------------------------------------------------------------
*/

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $plateNumber = trim($_POST["plate_number"] ?? "");
    $vin = trim($_POST["vin"] ?? "");
    $ownerID = $_POST["owner_id"] ?? "";
    $make = trim($_POST["make"] ?? "");
    $model = trim($_POST["model"] ?? "");
    $vehicleYear = trim($_POST["vehicle_year"] ?? "");
    $color = trim($_POST["color"] ?? "");
    $vehicleType = trim($_POST["vehicle_type"] ?? "");
    $engineNumber = trim($_POST["engine_number"] ?? "");
    $registrationDate = $_POST["registration_date"] ?? "";
    $status = $_POST["status"] ?? "Active";


    /*
    |--------------------------------------------------------------------------
    | Validation
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

        $error = "Please complete all required fields.";

    } elseif (!is_numeric($vehicleYear) || $vehicleYear < 1900 || $vehicleYear > date("Y")) {

        $error = "Please enter a valid vehicle year.";

    } elseif (!in_array($status, ["Active", "Inactive", "Stolen", "Sold"])) {

        $error = "Invalid vehicle status.";

    } else {

        /*
        |--------------------------------------------------------------------------
        | Check for Duplicate Plate or VIN
        |--------------------------------------------------------------------------
        */

        $duplicateStmt = $pdo->prepare("
            SELECT VehicleID
            FROM vehicles
            WHERE PlateNumber = ? OR VIN = ?
            LIMIT 1
        ");

        $duplicateStmt->execute([
            $plateNumber,
            $vin
        ]);

        $duplicate = $duplicateStmt->fetch();


        if ($duplicate) {

            $error = "A vehicle with this plate number or VIN already exists.";

        } else {

            /*
            |--------------------------------------------------------------------------
            | Insert Vehicle
            |--------------------------------------------------------------------------
            */

            try {

                $stmt = $pdo->prepare("
                    INSERT INTO vehicles (
                        PlateNumber,
                        VIN,
                        OwnerID,
                        Make,
                        Model,
                        VehicleYear,
                        Color,
                        VehicleType,
                        EngineNumber,
                        RegistrationDate,
                        Status
                    )
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");

                $stmt->execute([
                    $plateNumber,
                    $vin,
                    $ownerID,
                    $make,
                    $model,
                    $vehicleYear,
                    $color !== "" ? $color : null,
                    $vehicleType !== "" ? $vehicleType : null,
                    $engineNumber !== "" ? $engineNumber : null,
                    $registrationDate !== "" ? $registrationDate : null,
                    $status
                ]);


                /*
                |--------------------------------------------------------------------------
                | Record Audit Log
                |--------------------------------------------------------------------------
                */

                $vehicleID = $pdo->lastInsertId();

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
                    $_SESSION["UserID"],
                    "Added vehicle",
                    "vehicles",
                    $vehicleID,
                    $_SERVER["REMOTE_ADDR"] ?? null
                ]);


                $success = "Vehicle added successfully.";

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
                $color = "";
                $vehicleType = "";
                $engineNumber = "";
                $registrationDate = "";
                $status = "Active";

            } catch (PDOException $e) {

                $error = "Unable to add the vehicle. Please try again.";

            }

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

    <title>VISRS - Add Vehicle</title>

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
                Add Vehicle
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

            <h1 class="page-title">
                Add Vehicle
            </h1>

            <p class="page-subtitle">
                Enter the information below to register a new vehicle.
            </p>


            <?php if ($error !== ""): ?>

                <div class="error-message">

                    <?= htmlspecialchars($error) ?>

                </div>

            <?php endif; ?>


            <?php if ($success !== ""): ?>

                <div style="
                    background: #dcfce7;
                    color: #166534;
                    padding: 12px;
                    border-radius: 6px;
                    margin-bottom: 20px;
                ">

                    <?= htmlspecialchars($success) ?>

                </div>

            <?php endif; ?>


            <!-- FORM -->

            <section class="card">

                <h2>
                    Vehicle Information
                </h2>

                <form method="POST" action="">


                    <!-- PLATE -->

                    <div class="form-group">

                        <label for="plate_number">
                            License Plate Number *
                        </label>

                        <input
                            type="text"
                            id="plate_number"
                            name="plate_number"
                            placeholder="e.g. P1234"
                            value="<?= htmlspecialchars($plateNumber ?? "") ?>"
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
                            placeholder="Enter vehicle identification number"
                            value="<?= htmlspecialchars($vin ?? "") ?>"
                            maxlength="50"
                            required
                        >

                    </div>


                    <!-- OWNER -->

                    <div class="form-group">

                        <label for="owner_id">
                            Vehicle Owner *
                        </label>

                        <?php if (count($owners) > 0): ?>

                            <select
                                id="owner_id"
                                name="owner_id"
                                required
                                style="
                                    width: 100%;
                                    padding: 12px;
                                    border: 1px solid #d1d5db;
                                    border-radius: 6px;
                                    font-size: 15px;
                                    background: white;
                                "
                            >

                                <option value="">
                                    Select an owner
                                </option>

                                <?php foreach ($owners as $owner): ?>

                                    <option
                                        value="<?= $owner["OwnerID"] ?>"
                                        <?= ($ownerID ?? "") == $owner["OwnerID"] ? "selected" : "" ?>
                                    >

                                        <?= htmlspecialchars(
                                            $owner["FirstName"] . " " . $owner["LastName"]
                                        ) ?>

                                    </option>

                                <?php endforeach; ?>

                            </select>

                        <?php else: ?>

                            <p style="
                                color: #991b1b;
                                background: #fee2e2;
                                padding: 12px;
                                border-radius: 6px;
                            ">

                                No owners have been registered yet.

                                Please add an owner before adding a vehicle.

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
                            placeholder="e.g. Toyota"
                            value="<?= htmlspecialchars($make ?? "") ?>"
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
                            placeholder="e.g. Corolla"
                            value="<?= htmlspecialchars($model ?? "") ?>"
                            required
                        >

                    </div>


                    <!-- YEAR -->

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
                            placeholder="<?= date("Y") ?>"
                            value="<?= htmlspecialchars($vehicleYear ?? "") ?>"
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
                            placeholder="e.g. Black"
                            value="<?= htmlspecialchars($color ?? "") ?>"
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
                                width: 100%;
                                padding: 12px;
                                border: 1px solid #d1d5db;
                                border-radius: 6px;
                                font-size: 15px;
                                background: white;
                            "
                        >

                            <option value="">
                                Select vehicle type
                            </option>

                            <option value="Car">
                                Car
                            </option>

                            <option value="SUV">
                                SUV
                            </option>

                            <option value="Truck">
                                Truck
                            </option>

                            <option value="Van">
                                Van
                            </option>

                            <option value="Motorcycle">
                                Motorcycle
                            </option>

                            <option value="Bus">
                                Bus
                            </option>

                            <option value="Other">
                                Other
                            </option>

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
                            placeholder="Enter engine number"
                            value="<?= htmlspecialchars($engineNumber ?? "") ?>"
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
                            value="<?= htmlspecialchars($registrationDate ?? "") ?>"
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
                                width: 100%;
                                padding: 12px;
                                border: 1px solid #d1d5db;
                                border-radius: 6px;
                                font-size: 15px;
                                background: white;
                            "
                        >

                            <option
                                value="Active"
                                <?= ($status ?? "Active") === "Active" ? "selected" : "" ?>
                            >
                                Active
                            </option>

                            <option
                                value="Inactive"
                                <?= ($status ?? "") === "Inactive" ? "selected" : "" ?>
                            >
                                Inactive
                            </option>

                            <option
                                value="Stolen"
                                <?= ($status ?? "") === "Stolen" ? "selected" : "" ?>
                            >
                                Stolen
                            </option>

                            <option
                                value="Sold"
                                <?= ($status ?? "") === "Sold" ? "selected" : "" ?>
                            >
                                Sold
                            </option>

                        </select>

                    </div>


                    <!-- BUTTONS -->

                    <div style="
                        display: flex;
                        gap: 10px;
                        margin-top: 25px;
                    ">

                        <button
                            type="submit"
                            class="button"
                            <?= count($owners) === 0 ? "disabled" : "" ?>
                        >
                            Add Vehicle
                        </button>

                        <a
                            href="index.php"
                            class="button button-secondary"
                        >
                            Cancel
                        </a>

                    </div>

                </form>

            </section>

        </main>

    </div>

</div>

<script src="../js/app.js"></script>

</body>

</html>