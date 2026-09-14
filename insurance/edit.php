<?php

require_once "../includes/auth.php";
require_once "../includes/database.php";

$basePath = "../";
$activePage = "insurance";

$error = "";

$insuranceID = (int) ($_GET["id"] ?? $_POST["InsuranceID"] ?? 0);

if ($insuranceID <= 0) {
    header("Location: index.php");
    exit;
}


/*
 * Load the existing insurance record.
 */

$insuranceStmt = $pdo->prepare("
    SELECT
        InsuranceID,
        VehicleID,
        ProviderName,
        PolicyNumber,
        CoverageType,
        StartDate,
        ExpiryDate,
        Status
    FROM insurance
    WHERE InsuranceID = ?
");

$insuranceStmt->execute([
    $insuranceID
]);

$insurance = $insuranceStmt->fetch();


if (!$insurance) {
    header("Location: index.php");
    exit;
}


/*
 * Process the form.
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
        $_POST["Status"] ?? ""
    );


    /*
     * Preserve submitted values if validation fails.
     */

    $insurance["VehicleID"] = $vehicleID;
    $insurance["ProviderName"] = $providerName;
    $insurance["PolicyNumber"] = $policyNumber;
    $insurance["CoverageType"] = $coverageType;
    $insurance["StartDate"] = $startDate;
    $insurance["ExpiryDate"] = $expiryDate;
    $insurance["Status"] = $status;


    /*
     * Validate required fields.
     */

    if ($vehicleID <= 0) {

        $error = "Please select a vehicle.";

    } elseif ($providerName === "") {

        $error = "Insurance provider is required.";

    } elseif ($policyNumber === "") {

        $error = "Policy number is required.";

    } elseif ($startDate === "") {

        $error = "Policy start date is required.";

    } elseif ($status === "") {

        $error = "Insurance status is required.";

    }


    /*
     * Validate start date.
     */

    $startDateObject = null;

    if ($error === "") {

        $startDateObject = DateTime::createFromFormat(
            "Y-m-d",
            $startDate
        );

        $startDateErrors = DateTime::getLastErrors();

        if (
            !$startDateObject ||
            (
                $startDateErrors !== false &&
                (
                    $startDateErrors["warning_count"] > 0 ||
                    $startDateErrors["error_count"] > 0
                )
            ) ||
            $startDateObject->format("Y-m-d") !== $startDate
        ) {

            $error = "Please enter a valid policy start date.";

        }
    }


    /*
     * Validate expiry date.
     *
     * Expiry date is optional.
     * If supplied, it must be a valid date
     * and cannot be before the start date.
     */

    if ($error === "" && $expiryDate !== "") {

        $expiryDateObject = DateTime::createFromFormat(
            "Y-m-d",
            $expiryDate
        );

        $expiryDateErrors = DateTime::getLastErrors();

        if (
            !$expiryDateObject ||
            (
                $expiryDateErrors !== false &&
                (
                    $expiryDateErrors["warning_count"] > 0 ||
                    $expiryDateErrors["error_count"] > 0
                )
            ) ||
            $expiryDateObject->format("Y-m-d") !== $expiryDate
        ) {

            $error = "Please enter a valid policy expiry date.";

        } elseif ($expiryDateObject < $startDateObject) {

            $error =
                "Expiry date cannot be earlier than the start date.";

        }
    }


    /*
     * Validate status.
     */

    if ($error === "") {

        $allowedStatuses = [
            "Active",
            "Expired",
            "Cancelled"
        ];

        if (!in_array($status, $allowedStatuses, true)) {

            $error = "Invalid insurance status.";

        }
    }


    /*
     * Verify the vehicle exists.
     */

    if ($error === "") {

        $vehicleStmt = $pdo->prepare("
            SELECT
                VehicleID,
                PlateNumber,
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

            $error =
                "The selected vehicle does not exist.";

        }
    }


    /*
     * Check for duplicate policy number.
     */

    if ($error === "") {

        $duplicateStmt = $pdo->prepare("
            SELECT InsuranceID
            FROM insurance
            WHERE PolicyNumber = ?
            AND InsuranceID != ?
            LIMIT 1
        ");

        $duplicateStmt->execute([
            $policyNumber,
            $insuranceID
        ]);

        $duplicate = $duplicateStmt->fetch();


        if ($duplicate) {

            $error =
                "Another insurance record already uses this policy number.";

        }
    }


    /*
     * Update the insurance record.
     */

    if ($error === "") {

        try {

            $pdo->beginTransaction();


            $updateStmt = $pdo->prepare("
                UPDATE insurance
                SET
                    VehicleID = ?,
                    ProviderName = ?,
                    PolicyNumber = ?,
                    CoverageType = ?,
                    StartDate = ?,
                    ExpiryDate = ?,
                    Status = ?
                WHERE InsuranceID = ?
            ");


            $updateStmt->execute([

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

                $status,

                $insuranceID

            ]);


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

                "Updated insurance policy " .
                $policyNumber .
                " for vehicle " .
                $vehicle["PlateNumber"],

                "insurance",

                $insuranceID,

                $_SERVER["REMOTE_ADDR"] ?? null

            ]);


            $pdo->commit();


            /*
             * Return to the updated record.
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
                "Unable to update the insurance record.";

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

    <title>
        Edit Insurance - VISRS
    </title>

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

                <h1>
                    Edit Insurance
                </h1>

                <p>
                    Update the insurance policy information.
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

                        <?= htmlspecialchars(
                            $error
                        ) ?>

                    </div>

                <?php endif; ?>


                <form method="POST">

                    <input
                        type="hidden"
                        name="InsuranceID"
                        value="<?= $insurance["InsuranceID"] ?>"
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
                                as $vehicleOption
                            ): ?>

                                <option
                                    value="<?= $vehicleOption["VehicleID"] ?>"
                                    <?= (
                                        (int) $insurance["VehicleID"]
                                        ===
                                        (int) $vehicleOption["VehicleID"]
                                    )
                                        ? "selected"
                                        : ""
                                    ?>
                                >

                                    <?= htmlspecialchars(
                                        $vehicleOption["PlateNumber"]
                                    ) ?>

                                    -

                                    <?= htmlspecialchars(
                                        $vehicleOption["Make"]
                                    ) ?>

                                    <?= htmlspecialchars(
                                        $vehicleOption["Model"]
                                    ) ?>

                                    (<?= htmlspecialchars(
                                        $vehicleOption["VehicleYear"]
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
                                $insurance["ProviderName"]
                            ) ?>"
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
                                $insurance["PolicyNumber"]
                            ) ?>"
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
                                    $insurance["CoverageType"]
                                    ===
                                    "Third Party"
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
                                    $insurance["CoverageType"]
                                    ===
                                    "Comprehensive"
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
                                    $insurance["CoverageType"]
                                    ===
                                    "Collision"
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
                                    $insurance["CoverageType"]
                                    ===
                                    "Liability"
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
                                $insurance["StartDate"] ?? ""
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
                                $insurance["ExpiryDate"] ?? ""
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
                                    $insurance["Status"]
                                    ===
                                    "Active"
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
                                    $insurance["Status"]
                                    ===
                                    "Expired"
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
                                    $insurance["Status"]
                                    ===
                                    "Cancelled"
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
                            Save Changes
                        </button>


                        <a
                            href="view.php?id=<?= $insurance["InsuranceID"] ?>"
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