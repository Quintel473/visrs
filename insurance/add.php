<?php

require_once __DIR__ . "/../includes/auth.php";
require_once __DIR__ . "/../includes/database.php";

requireRole(["Admin", "Police"]);

$activePage = "insurance";

$pageTitle = "Add Insurance";
$pageSubtitle = "Add an insurance policy for a registered vehicle.";

$error = "";


/*
|--------------------------------------------------------------------------
| Process Form Submission
|--------------------------------------------------------------------------
*/

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    requireValidCSRF();

    $vehicleID = (int) (
        $_POST["VehicleID"] ?? 0
    );

    $providerName = trim(
        $_POST["ProviderName"] ?? ""
    );

    $policyNumber = trim(
        $_POST["PolicyNumber"] ?? ""
    );

    $coverageType = trim(
        $_POST["CoverageType"] ?? ""
    );

    $startDate = trim(
        $_POST["StartDate"] ?? ""
    );

    $expiryDate = trim(
        $_POST["ExpiryDate"] ?? ""
    );

    $status = trim(
        $_POST["Status"] ?? "Active"
    );


    /*
     * Allowed coverage types.
     */

    $allowedCoverageTypes = [
        "",
        "Third Party",
        "Comprehensive",
        "Collision",
        "Liability"
    ];


    /*
     * Allowed insurance statuses.
     */

    $allowedStatuses = [
        "Active",
        "Expired",
        "Cancelled"
    ];


    /*
     * Basic validation.
     */

    if ($vehicleID <= 0) {

        $error =
            "Please select a vehicle.";

    } elseif ($providerName === "") {

        $error =
            "Insurance provider is required.";

    } elseif (strlen($providerName) > 150) {

        $error =
            "Insurance provider cannot exceed 150 characters.";

    } elseif ($policyNumber === "") {

        $error =
            "Policy number is required.";

    } elseif (strlen($policyNumber) > 100) {

        $error =
            "Policy number cannot exceed 100 characters.";

    } elseif (
        !in_array(
            $coverageType,
            $allowedCoverageTypes,
            true
        )
    ) {

        $error =
            "Invalid coverage type.";

    } elseif ($startDate === "") {

        $error =
            "Start date is required.";

    } elseif (
        !DateTime::createFromFormat(
            "Y-m-d",
            $startDate
        ) ||
        DateTime::createFromFormat(
            "Y-m-d",
            $startDate
        )->format("Y-m-d") !== $startDate
    ) {

        $error =
            "Please enter a valid policy start date.";

    } elseif (
        $expiryDate !== "" &&
        (
            !DateTime::createFromFormat(
                "Y-m-d",
                $expiryDate
            ) ||
            DateTime::createFromFormat(
                "Y-m-d",
                $expiryDate
            )->format("Y-m-d") !== $expiryDate
        )
    ) {

        $error =
            "Please enter a valid policy expiry date.";

    } elseif (
        $expiryDate !== "" &&
        $expiryDate < $startDate
    ) {

        $error =
            "Expiry date cannot be earlier than the start date.";

    } elseif (
        !in_array(
            $status,
            $allowedStatuses,
            true
        )
    ) {

        $error =
            "Invalid insurance status.";

    } else {


        /*
         * Verify selected vehicle.
         */

        $vehicleStmt = $pdo->prepare("
            SELECT
                VehicleID,
                PlateNumber,
                VIN,
                Make,
                Model,
                VehicleYear
            FROM vehicles
            WHERE VehicleID = ?
            LIMIT 1
        ");

        $vehicleStmt->execute([
            $vehicleID
        ]);

        $vehicle = $vehicleStmt->fetch();


        if (!$vehicle) {

            $error =
                "The selected vehicle does not exist.";

        } else {


            /*
             * Check for duplicate policy number.
             */

            $duplicateStmt = $pdo->prepare("
                SELECT
                    InsuranceID
                FROM insurance
                WHERE PolicyNumber = ?
                LIMIT 1
            ");

            $duplicateStmt->execute([
                $policyNumber
            ]);

            $duplicate =
                $duplicateStmt->fetch();


            if ($duplicate) {

                $error =
                    "An insurance record with this policy number already exists.";

            } else {

                try {

                    $pdo->beginTransaction();


                    /*
                     * Insert insurance record.
                     */

                    $insertStmt = $pdo->prepare("
                        INSERT INTO insurance
                        (
                            VehicleID,
                            ProviderName,
                            PolicyNumber,
                            CoverageType,
                            StartDate,
                            ExpiryDate,
                            Status
                        )
                        VALUES (?, ?, ?, ?, ?, ?, ?)
                    ");

                    $insertStmt->execute([

                        $vehicleID,

                        $providerName,

                        $policyNumber,

                        $coverageType !== ""
                            ? $coverageType
                            : null,

                        $startDate,

                        $expiryDate !== ""
                            ? $expiryDate
                            : null,

                        $status

                    ]);


                    $insuranceID =
                        (int) $pdo->lastInsertId();


                    /*
                     * Create audit log.
                     */

                    $auditStmt = $pdo->prepare("
                        INSERT INTO audit_logs
                        (
                            UserID,
                            Action,
                            TableAffected,
                            RecordID,
                            IPAddress
                        )
                        VALUES (?, ?, ?, ?, ?)
                    ");

                    $auditStmt->execute([

                        $_SESSION["UserID"],

                        "Added insurance policy " .
                        $policyNumber .
                        " for vehicle " .
                        $vehicle["PlateNumber"],

                        "insurance",

                        $insuranceID,

                        $_SERVER["REMOTE_ADDR"] ?? null

                    ]);


                    $pdo->commit();


                    /*
                     * Open the newly-created record.
                     */

                    header(
                        "Location: view.php?id=" .
                        $insuranceID
                    );

                    exit;


                } catch (Exception $e) {

                    if (
                        $pdo->inTransaction()
                    ) {
                        $pdo->rollBack();
                    }

                    $error =
                        "Unable to save the insurance record.";
                }
            }
        }
    }
}


/*
|--------------------------------------------------------------------------
| Load Vehicles
|--------------------------------------------------------------------------
*/

$vehiclesStmt = $pdo->query("
    SELECT
        VehicleID,
        PlateNumber,
        VIN,
        Make,
        Model,
        VehicleYear
    FROM vehicles
    ORDER BY PlateNumber ASC
");

$vehicles =
    $vehiclesStmt->fetchAll();

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

    <?php require_once __DIR__ . "/../includes/sidebar.php"; ?>


    <main class="main-content">

        <?php require_once __DIR__ . "/../includes/header.php"; ?>


        <div class="content">

            <div class="page-title">

                <div>

                    <h1>
                        Add Insurance
                    </h1>

                    <p class="page-subtitle">
                        Add an insurance policy for a registered vehicle.
                    </p>

                </div>

            </div>


            <div class="card">

                <?php if ($error !== ""): ?>

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
                            Unable to Save Insurance
                        </strong>

                        <div style="margin-top:5px;">

                            <?= htmlspecialchars($error) ?>

                        </div>

                    </div>

                <?php endif; ?>


                <form method="POST">

                    <input
                        type="hidden"
                        name="csrf_token"
                        value="<?= htmlspecialchars(
                            generateCSRFToken()
                        ) ?>"
                    >


                    <!-- VEHICLE -->

                    <div style="margin-bottom:20px;">

                        <label for="VehicleID">

                            <strong>
                                Vehicle
                            </strong>

                        </label>

                        <select
                            name="VehicleID"
                            id="VehicleID"
                            required
                            style="
                                width:100%;
                                min-height:46px;
                                padding:10px;
                                margin-top:8px;
                                appearance:auto;
                            "
                        >

                            <option value="">
                                -- Select Vehicle --
                            </option>


                            <?php foreach (
                                $vehicles
                                as $vehicle
                            ): ?>

                                <option
                                    value="<?= (int) $vehicle["VehicleID"] ?>"
                                    <?= (
                                        (
                                            $_POST["VehicleID"]
                                            ?? ""
                                        ) == $vehicle["VehicleID"]
                                    )
                                        ? "selected"
                                        : ""
                                    ?>
                                >

                                    <?= htmlspecialchars(
                                        $vehicle["PlateNumber"]
                                    ) ?>

                                    -

                                    <?= htmlspecialchars(
                                        $vehicle["Make"]
                                    ) ?>

                                    <?= htmlspecialchars(
                                        $vehicle["Model"]
                                    ) ?>

                                    (<?= htmlspecialchars(
                                        $vehicle["VehicleYear"]
                                    ) ?>)

                                </option>

                            <?php endforeach; ?>

                        </select>

                    </div>


                    <!-- PROVIDER -->

                    <div style="margin-bottom:20px;">

                        <label for="ProviderName">

                            <strong>
                                Insurance Provider
                            </strong>

                        </label>

                        <input
                            type="text"
                            name="ProviderName"
                            id="ProviderName"
                            maxlength="150"
                            required
                            value="<?= htmlspecialchars(
                                $_POST["ProviderName"]
                                ?? ""
                            ) ?>"
                            placeholder="Example: Caribbean Insurance"
                            style="
                                width:100%;
                                min-height:46px;
                                padding:10px;
                                margin-top:8px;
                            "
                        >

                    </div>


                    <!-- POLICY NUMBER -->

                    <div style="margin-bottom:20px;">

                        <label for="PolicyNumber">

                            <strong>
                                Policy Number
                            </strong>

                        </label>

                        <input
                            type="text"
                            name="PolicyNumber"
                            id="PolicyNumber"
                            maxlength="100"
                            required
                            value="<?= htmlspecialchars(
                                $_POST["PolicyNumber"]
                                ?? ""
                            ) ?>"
                            placeholder="Example: POL-2025-001245"
                            style="
                                width:100%;
                                min-height:46px;
                                padding:10px;
                                margin-top:8px;
                            "
                        >

                    </div>


                    <!-- COVERAGE -->

                    <div style="margin-bottom:20px;">

                        <label for="CoverageType">

                            <strong>
                                Coverage Type
                            </strong>

                        </label>

                        <select
                            name="CoverageType"
                            id="CoverageType"
                            style="
                                width:100%;
                                min-height:46px;
                                padding:10px;
                                margin-top:8px;
                                appearance:auto;
                            "
                        >

                            <option value="">
                                -- Select Coverage Type --
                            </option>

                            <option
                                value="Third Party"
                                <?= (
                                    (
                                        $_POST["CoverageType"]
                                        ?? ""
                                    )
                                    === "Third Party"
                                )
                                    ? "selected"
                                    : ""
                                ?>
                            >
                                Third Party
                            </option>

                            <option
                                value="Comprehensive"
                                <?= (
                                    (
                                        $_POST["CoverageType"]
                                        ?? ""
                                    )
                                    === "Comprehensive"
                                )
                                    ? "selected"
                                    : ""
                                ?>
                            >
                                Comprehensive
                            </option>

                            <option
                                value="Collision"
                                <?= (
                                    (
                                        $_POST["CoverageType"]
                                        ?? ""
                                    )
                                    === "Collision"
                                )
                                    ? "selected"
                                    : ""
                                ?>
                            >
                                Collision
                            </option>

                            <option
                                value="Liability"
                                <?= (
                                    (
                                        $_POST["CoverageType"]
                                        ?? ""
                                    )
                                    === "Liability"
                                )
                                    ? "selected"
                                    : ""
                                ?>
                            >
                                Liability
                            </option>

                        </select>

                    </div>


                    <!-- START DATE -->

                    <div style="margin-bottom:20px;">

                        <label for="StartDate">

                            <strong>
                                Policy Start Date
                            </strong>

                        </label>

                        <input
                            type="date"
                            name="StartDate"
                            id="StartDate"
                            required
                            value="<?= htmlspecialchars(
                                $_POST["StartDate"]
                                ?? ""
                            ) ?>"
                            style="
                                width:100%;
                                min-height:46px;
                                padding:10px;
                                margin-top:8px;
                            "
                        >

                    </div>


                    <!-- EXPIRY DATE -->

                    <div style="margin-bottom:20px;">

                        <label for="ExpiryDate">

                            <strong>
                                Policy Expiry Date
                            </strong>

                        </label>

                        <input
                            type="date"
                            name="ExpiryDate"
                            id="ExpiryDate"
                            value="<?= htmlspecialchars(
                                $_POST["ExpiryDate"]
                                ?? ""
                            ) ?>"
                            style="
                                width:100%;
                                min-height:46px;
                                padding:10px;
                                margin-top:8px;
                            "
                        >

                        <small
                            style="
                                display:block;
                                margin-top:6px;
                                color:#6b7280;
                            "
                        >
                            Leave blank if an expiry date is not available.
                        </small>

                    </div>


                    <!-- STATUS -->

                    <div style="margin-bottom:25px;">

                        <label for="Status">

                            <strong>
                                Status
                            </strong>

                        </label>

                        <select
                            name="Status"
                            id="Status"
                            required
                            style="
                                width:100%;
                                min-height:46px;
                                padding:10px;
                                margin-top:8px;
                                appearance:auto;
                            "
                        >

                            <option
                                value="Active"
                                <?= (
                                    (
                                        $_POST["Status"]
                                        ?? "Active"
                                    )
                                    === "Active"
                                )
                                    ? "selected"
                                    : ""
                                ?>
                            >
                                Active
                            </option>

                            <option
                                value="Expired"
                                <?= (
                                    (
                                        $_POST["Status"]
                                        ?? ""
                                    )
                                    === "Expired"
                                )
                                    ? "selected"
                                    : ""
                                ?>
                            >
                                Expired
                            </option>

                            <option
                                value="Cancelled"
                                <?= (
                                    (
                                        $_POST["Status"]
                                        ?? ""
                                    )
                                    === "Cancelled"
                                )
                                    ? "selected"
                                    : ""
                                ?>
                            >
                                Cancelled
                            </option>

                        </select>

                    </div>


                    <!-- BUTTONS -->

                    <div
                        style="
                            display:flex;
                            gap:10px;
                            flex-wrap:wrap;
                        "
                    >

                        <button
                            type="submit"
                            class="button"
                        >
                            Save Insurance
                        </button>

                        <a
                            href="index.php"
                            class="button button-secondary"
                        >
                            Cancel
                        </a>

                    </div>

                </form>

            </div>

        </div>


        <?php require_once __DIR__ . "/../includes/footer.php"; ?>

    </main>

</div>

</body>

</html>