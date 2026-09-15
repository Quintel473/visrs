<?php

require_once "../includes/auth.php";
require_once "../includes/database.php";

$basePath = "../";
$activePage = "vehicles";

/*
|--------------------------------------------------------------------------
| Check Permission
|--------------------------------------------------------------------------
*/

if (!canManageVehicles()) {

    http_response_code(403);

    echo "<h1>Access Denied</h1>";
    echo "<p>You do not have permission to edit vehicle records.</p>";
    echo '<p><a href="index.php">Return to Vehicles</a></p>';

    exit;
}


/*
|--------------------------------------------------------------------------
| Get Vehicle ID
|--------------------------------------------------------------------------
*/

$vehicleID = $_GET["id"] ?? $_POST["vehicle_id"] ?? "";

if (!is_numeric($vehicleID)) {

    header("Location: index.php");
    exit;
}


/*
|--------------------------------------------------------------------------
| Load Vehicle
|--------------------------------------------------------------------------
*/

$stmt = $pdo->prepare("
    SELECT *
    FROM vehicles
    WHERE VehicleID = ?
    LIMIT 1
");

$stmt->execute([$vehicleID]);

$vehicle = $stmt->fetch();

if (!$vehicle) {

    http_response_code(404);

    echo "<h1>Vehicle Not Found</h1>";
    echo "<p>The requested vehicle does not exist.</p>";
    echo '<p><a href="index.php">Return to Vehicles</a></p>';

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


$error = "";
$success = "";


/*
|--------------------------------------------------------------------------
| Handle Update
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
    $status = $_POST["status"] ?? "";


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

    } elseif (
        !is_numeric($vehicleYear) ||
        $vehicleYear < 1900 ||
        $vehicleYear > date("Y")
    ) {

        $error = "Please enter a valid vehicle year.";

    } elseif (
        !in_array(
            $status,
            ["Active", "Inactive", "Stolen", "Sold"]
        )
    ) {

        $error = "Invalid vehicle status.";

    } else {

        /*
        |--------------------------------------------------------------------------
        | Check Duplicate Plate / VIN
        |--------------------------------------------------------------------------
        */

        $duplicateStmt = $pdo->prepare("
            SELECT VehicleID
            FROM vehicles
            WHERE (PlateNumber = ? OR VIN = ?)
            AND VehicleID != ?
            LIMIT 1
        ");

        $duplicateStmt->execute([
            $plateNumber,
            $vin,
            $vehicleID
        ]);

        $duplicate = $duplicateStmt->fetch();


        if ($duplicate) {

            $error = "Another vehicle already uses this plate number or VIN.";

        } else {

            /*
            |--------------------------------------------------------------------------
            | Update Vehicle
            |--------------------------------------------------------------------------
            */

            try {

                $updateStmt = $pdo->prepare("
                    UPDATE vehicles
                    SET
                        PlateNumber = ?,
                        VIN = ?,
                        OwnerID = ?,
                        Make = ?,
                        Model = ?,
                        VehicleYear = ?,
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
                    $ownerID,
                    $make,
                    $model,
                    $vehicleYear,
                    $color !== "" ? $color : null,
                    $vehicleType !== "" ? $vehicleType : null,
                    $engineNumber !== "" ? $engineNumber : null,
                    $registrationDate !== "" ? $registrationDate : null,
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
                    $_SESSION["UserID"],
                    "Updated vehicle",
                    "vehicles",
                    $vehicleID,
                    $_SERVER["REMOTE_ADDR"] ?? null
                ]);


                /*
                |--------------------------------------------------------------------------
                | Reload Updated Vehicle
                |--------------------------------------------------------------------------
                */

                $reloadStmt = $pdo->prepare("
                    SELECT *
                    FROM vehicles
                    WHERE VehicleID = ?
                    LIMIT 1
                ");

                $reloadStmt->execute([$vehicleID]);

                $vehicle = $reloadStmt->fetch();

                $success = "Vehicle updated successfully.";

            } catch (PDOException $e) {

                $error = "Unable to update the vehicle. Please try again.";

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

    <title>VISRS - Edit Vehicle</title>

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

        <?php

            $pageTitle = "Vehicles";

            include __DIR__ . "/../includes/header.php";

        ?>


        <main class="content">

            <h1 class="page-title">
                Edit Vehicle
            </h1>

            <p class="page-subtitle">
                Update the vehicle information below.
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


            <section class="card">

                <h2>
                    Vehicle Information
                </h2>

                <form method="POST" action="">

                    <input
                        type="hidden"
                        name="vehicle_id"
                        value="<?= htmlspecialchars($vehicle["VehicleID"]) ?>"
                    >


                    <!-- PLATE -->

                    <div class="form-group">

                        <label for="plate_number">
                            License Plate Number *
                        </label>

                        <input
                            type="text"
                            id="plate_number"
                            name="plate_number"
                            value="<?= htmlspecialchars($vehicle["PlateNumber"]) ?>"
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
                            value="<?= htmlspecialchars($vehicle["VIN"]) ?>"
                            maxlength="50"
                            required
                        >

                    </div>


                    <!-- OWNER -->

                    <div class="form-group">

                        <label for="owner_id">
                            Vehicle Owner *
                        </label>

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
                                    <?= $vehicle["OwnerID"] == $owner["OwnerID"] ? "selected" : "" ?>
                                >

                                    <?= htmlspecialchars(
                                        $owner["FirstName"]
                                        . " "
                                        . $owner["LastName"]
                                    ) ?>

                                </option>

                            <?php endforeach; ?>

                        </select>

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
                            value="<?= htmlspecialchars($vehicle["Make"]) ?>"
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
                            value="<?= htmlspecialchars($vehicle["Model"]) ?>"
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
                            value="<?= htmlspecialchars($vehicle["VehicleYear"]) ?>"
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
                            value="<?= htmlspecialchars($vehicle["Color"] ?? "") ?>"
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

                            <?php foreach ($vehicleTypes as $type): ?>

                                <option
                                    value="<?= $type ?>"
                                    <?= $vehicle["VehicleType"] === $type ? "selected" : "" ?>
                                >

                                    <?= $type ?>

                                </option>

                            <?php endforeach; ?>

                        </select>

                    </div>


                    <!-- ENGINE -->

                    <div class="form-group">

                        <label for="engine_number">
                            Engine Number
                        </label>

                        <input
                            type="text"
                            id="engine_number"
                            name="engine_number"
                            value="<?= htmlspecialchars($vehicle["EngineNumber"] ?? "") ?>"
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
                            value="<?= htmlspecialchars($vehicle["RegistrationDate"] ?? "") ?>"
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

                            <?php
                            $statuses = [
                                "Active",
                                "Inactive",
                                "Stolen",
                                "Sold"
                            ];
                            ?>

                            <?php foreach ($statuses as $statusOption): ?>

                                <option
                                    value="<?= $statusOption ?>"
                                    <?= $vehicle["Status"] === $statusOption ? "selected" : "" ?>
                                >

                                    <?= $statusOption ?>

                                </option>

                            <?php endforeach; ?>

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
                        >
                            Save Changes
                        </button>

                        <a
                            href="view.php?id=<?= $vehicle["VehicleID"] ?>"
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