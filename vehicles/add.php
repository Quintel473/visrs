<?php

require_once __DIR__ . "/../includes/auth.php";
requireRole(["Admin", "Police", "Seller"]);

require_once __DIR__ . "/../includes/database.php";
require_once __DIR__ . "/../includes/functions.php";

$pageTitle = "Add Vehicle";
$pageSubtitle = "Register a new vehicle in the VISRS system";

$activePage = "vehicles";

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

$owners = $ownerStmt->fetchAll();


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
    | Basic Required Field Validation
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
    | Status Validation
    |--------------------------------------------------------------------------
    */

    if ($error === "") {

        if (!in_array(
            $status,
            $allowedStatuses,
            true
        )) {

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

        if (!in_array(
            $vehicleType,
            $allowedVehicleTypes,
            true
        )) {

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
    | Validate Registration Date Against Vehicle Year
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

                (int) $ownerID,

                $make,

                $model,

                (int) $vehicleYear,

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
            | Create Audit Log
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

                "Added vehicle",

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
            $color = "";
            $vehicleType = "";
            $engineNumber = "";
            $registrationDate = "";
            $status = "Active";


        } catch (PDOException $e) {

            /*
            |--------------------------------------------------------------------------
            | Roll Back Failed Transaction
            |--------------------------------------------------------------------------
            */

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
                Add Vehicle
            </h1>


            <p class="page-subtitle">
                Enter the information below to register a new vehicle.
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


            <!--
            |--------------------------------------------------------------------------
            | VEHICLE FORM
            |--------------------------------------------------------------------------
            -->

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


                    <!-- PLATE NUMBER -->

                    <div class="form-group">

                        <label for="plate_number">
                            License Plate Number *
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
                            value="<?= htmlspecialchars(
                                $vin
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


                        <?php if (count($owners) > 0): ?>

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


                        <?php else: ?>

                            <p
                                style="
                                    color:#991b1b;
                                    background:#fee2e2;
                                    padding:12px;
                                    border-radius:6px;
                                "
                            >

                                No owners have been registered yet.

                                Please add an owner before adding
                                a vehicle.

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
                            value="<?= htmlspecialchars(
                                $make
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
                            placeholder="e.g. Corolla"
                            value="<?= htmlspecialchars(
                                $model
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
                            placeholder="<?= date("Y") ?>"
                            value="<?= htmlspecialchars(
                                $vehicleYear
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
                            placeholder="e.g. Black"
                            value="<?= htmlspecialchars(
                                $color
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

                            <option
                                value="Car"
                                <?= $vehicleType === "Car"
                                    ? "selected"
                                    : ""
                                ?>
                            >
                                Car
                            </option>

                            <option
                                value="SUV"
                                <?= $vehicleType === "SUV"
                                    ? "selected"
                                    : ""
                                ?>
                            >
                                SUV
                            </option>

                            <option
                                value="Truck"
                                <?= $vehicleType === "Truck"
                                    ? "selected"
                                    : ""
                                ?>
                            >
                                Truck
                            </option>

                            <option
                                value="Van"
                                <?= $vehicleType === "Van"
                                    ? "selected"
                                    : ""
                                ?>
                            >
                                Van
                            </option>

                            <option
                                value="Motorcycle"
                                <?= $vehicleType === "Motorcycle"
                                    ? "selected"
                                    : ""
                                ?>
                            >
                                Motorcycle
                            </option>

                            <option
                                value="Bus"
                                <?= $vehicleType === "Bus"
                                    ? "selected"
                                    : ""
                                ?>
                            >
                                Bus
                            </option>

                            <option
                                value="Other"
                                <?= $vehicleType === "Other"
                                    ? "selected"
                                    : ""
                                ?>
                            >
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
                            value="<?= htmlspecialchars(
                                $engineNumber
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
                                $registrationDate
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

                            <option
                                value="Active"
                                <?= $status === "Active"
                                    ? "selected"
                                    : ""
                                ?>
                            >
                                Active
                            </option>

                            <option
                                value="Inactive"
                                <?= $status === "Inactive"
                                    ? "selected"
                                    : ""
                                ?>
                            >
                                Inactive
                            </option>

                            <option
                                value="Stolen"
                                <?= $status === "Stolen"
                                    ? "selected"
                                    : ""
                                ?>
                            >
                                Stolen
                            </option>

                            <option
                                value="Sold"
                                <?= $status === "Sold"
                                    ? "selected"
                                    : ""
                                ?>
                            >
                                Sold
                            </option>

                        </select>

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
                            <?= count($owners) === 0
                                ? "disabled"
                                : ""
                            ?>
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


    </main>


</div>


<?php include __DIR__ . "/../includes/footer.php"; ?>