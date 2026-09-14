<?php

require_once "../includes/auth.php";
require_once "../includes/database.php";

$basePath = "../";
$activePage = "insurance";

$error = "";


/*
 * Process the form when submitted.
 */

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $vehicleID = (int) ($_POST["VehicleID"] ?? 0);

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
     * Basic validation.
     */

    if ($vehicleID <= 0) {

        $error = "Please select a vehicle.";

    } elseif ($providerName === "") {

        $error = "Insurance provider is required.";

    } elseif ($policyNumber === "") {

        $error = "Policy number is required.";

    } elseif ($startDate === "") {

        $error = "Start date is required.";

    } elseif ($expiryDate !== "" && $expiryDate < $startDate) {

        $error = "Expiry date cannot be earlier than the start date.";

    } elseif (
        !in_array(
            $status,
            ["Active", "Expired", "Cancelled"]
        )
    ) {

        $error = "Invalid insurance status.";

    } else {

        /*
         * Verify that the selected vehicle exists.
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
        ");

        $vehicleStmt->execute([
            $vehicleID
        ]);

        $vehicle = $vehicleStmt->fetch();


        if (!$vehicle) {

            $error = "The selected vehicle does not exist.";

        } else {

            /*
             * Check for duplicate policy numbers.
             */

            $duplicateStmt = $pdo->prepare("
                SELECT InsuranceID
                FROM insurance
                WHERE PolicyNumber = ?
                LIMIT 1
            ");

            $duplicateStmt->execute([
                $policyNumber
            ]);

            $duplicate = $duplicateStmt->fetch();


            if ($duplicate) {

                $error =
                    "An insurance record with this policy number already exists.";

            } else {

                /*
                 * Insert the insurance record.
                 */

                try {

                    $pdo->beginTransaction();


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
                        $pdo->lastInsertId();


                    /*
                     * Create an audit log.
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
                     * Send the user to the new
                     * insurance record.
                     */

                    header(
                        "Location: view.php?id=" .
                        $insuranceID
                    );

                    exit;


                } catch (Exception $e) {

                    if ($pdo->inTransaction()) {

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
 * Load vehicles for the dropdown.
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

$vehicles = $vehiclesStmt->fetchAll();

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Add Insurance - VISRS</title>

    <link
        rel="stylesheet"
        href="../css/style.css"
    >

</head>

<body>

<div class="layout">

    <?php require_once "../includes/sidebar.php"; ?>


    <main class="main-content">

        <!-- TOP BAR -->

        <div class="topbar">

            <div>

                <h1>Add Insurance</h1>

                <p>
                    Add an insurance policy for a registered vehicle.
                </p>

            </div>


            <div class="user-info">

                <div class="user-avatar">

                    <?= strtoupper(
                        substr(
                            $_SESSION["FirstName"] ?? "U",
                            0,
                            1
                        )
                    ) ?>

                </div>


                <div>

                    <strong>

                        <?= htmlspecialchars(
                            $_SESSION["FirstName"] ?? ""
                        ) ?>

                        <?= htmlspecialchars(
                            $_SESSION["LastName"] ?? ""
                        ) ?>

                    </strong>

                    <small>

                        <?= htmlspecialchars(
                            $_SESSION["Role"] ?? ""
                        ) ?>

                    </small>

                </div>

            </div>

        </div>


        <!-- CONTENT -->

        <div class="content">

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

                        <?= htmlspecialchars($error) ?>

                    </div>

                <?php endif; ?>


                <form method="POST">


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
                                    value="<?= $vehicle["VehicleID"] ?>"
                                    <?= (
                                        isset(
                                            $_POST["VehicleID"]
                                        )
                                        &&
                                        $_POST["VehicleID"]
                                        ==
                                        $vehicle["VehicleID"]
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
                                $_POST["ProviderName"] ?? ""
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
                                $_POST["PolicyNumber"] ?? ""
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
                                $_POST["StartDate"] ?? ""
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
                                $_POST["ExpiryDate"] ?? ""
                            ) ?>"
                            style="
                                width:100%;
                                min-height:46px;
                                padding:10px;
                                margin-top:8px;
                            "
                        >

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

    </main>

</div>

</body>

</html>