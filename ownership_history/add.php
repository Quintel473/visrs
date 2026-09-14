<?php

require_once "../includes/auth.php";
require_once "../includes/database.php";

$basePath = "../";
$activePage = "ownership_history";

$error = "";
$success = "";


/*
|--------------------------------------------------------------------------
| Load Vehicles
|--------------------------------------------------------------------------
*/

$stmt = $pdo->query("
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

$vehicles = $stmt->fetchAll();


/*
|--------------------------------------------------------------------------
| Load Owners
|--------------------------------------------------------------------------
*/

$stmt = $pdo->query("
    SELECT
        OwnerID,
        FirstName,
        LastName,
        Email
    FROM owners
    ORDER BY LastName ASC, FirstName ASC
");

$owners = $stmt->fetchAll();


/*
|--------------------------------------------------------------------------
| Handle Form Submission
|--------------------------------------------------------------------------
*/

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $vehicleID = $_POST["VehicleID"] ?? "";
    $ownerID = $_POST["OwnerID"] ?? "";
    $startDate = $_POST["StartDate"] ?? "";
    $endDate = $_POST["EndDate"] ?? "";
    $transferReason = trim(
        $_POST["TransferReason"] ?? ""
    );


    /*
     * Basic validation
     */

    if (
        !is_numeric($vehicleID) ||
        !is_numeric($ownerID) ||
        $startDate === ""
    ) {

        $error =
            "Vehicle, owner, and ownership start date are required.";

    }


    /*
     * Validate date order
     */

    elseif (
        $endDate !== "" &&
        $endDate < $startDate
    ) {

        $error =
            "The ownership end date cannot be earlier than the start date.";

    }


    else {

        try {


            /*
             * Verify vehicle exists
             */

            $stmt = $pdo->prepare("
                SELECT
                    VehicleID,
                    PlateNumber,
                    Make,
                    Model,
                    VehicleYear
                FROM vehicles
                WHERE VehicleID = ?
                LIMIT 1
            ");

            $stmt->execute([
                $vehicleID
            ]);

            $vehicle = $stmt->fetch();


            if (!$vehicle) {

                $error =
                    "The selected vehicle could not be found.";

            }


            else {


                /*
                 * Verify owner exists
                 */

                $stmt = $pdo->prepare("
                    SELECT
                        OwnerID,
                        FirstName,
                        LastName
                    FROM owners
                    WHERE OwnerID = ?
                    LIMIT 1
                ");

                $stmt->execute([
                    $ownerID
                ]);

                $owner = $stmt->fetch();


                if (!$owner) {

                    $error =
                        "The selected owner could not be found.";

                }


                else {


                    /*
                     * Insert ownership history
                     */

                    $stmt = $pdo->prepare("
                        INSERT INTO ownership_history
                        (
                            VehicleID,
                            OwnerID,
                            StartDate,
                            EndDate,
                            TransferReason
                        )
                        VALUES (?, ?, ?, ?, ?)
                    ");

                    $stmt->execute([
                        $vehicleID,
                        $ownerID,
                        $startDate,
                        $endDate !== ""
                            ? $endDate
                            : null,
                        $transferReason !== ""
                            ? $transferReason
                            : null
                    ]);


                    $ownershipID =
                        $pdo->lastInsertId();


                    /*
                     * Audit log
                     */

                    $audit = $pdo->prepare("
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

                    $audit->execute([
                        $_SESSION["UserID"],
                        "Added ownership history",
                        "ownership_history",
                        $ownershipID,
                        $_SERVER["REMOTE_ADDR"] ?? null
                    ]);


                    $success =
                        "Ownership history record added successfully.";


                    /*
                     * Clear submitted values after successful
                     * insertion.
                     */

                    $_POST = [];

                }

            }

        }

        catch (PDOException $e) {

            $error =
                "Unable to add the ownership history record.";

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
        VISRS - Add Ownership History
    </title>

    <link
        rel="stylesheet"
        href="../css/style.css"
    >


    <style>

        /*
         * Keep sidebar navigation stable.
         */

        .sidebar-nav {
            display: flex;
            flex-direction: column;
            gap: 4px;
        }


        .sidebar-nav a {
            display: flex;
            align-items: center;
            min-height: 44px;
            box-sizing: border-box;
            text-decoration: none;
            white-space: nowrap;
        }


        /*
         * Form controls
         */

        .ownership-form .form-group {
            margin-bottom: 20px;
        }


        .ownership-form label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
        }


        .ownership-form input,
        .ownership-form select {

            width: 100%;

            min-height: 46px;

            padding: 10px 12px;

            box-sizing: border-box;

            border: 1px solid #cbd5e1;

            border-radius: 8px;

            background: #ffffff;

            color: #0f172a;

            font-size: 15px;

            font-family: inherit;

        }


        /*
         * Proper dropdown styling
         */

        .ownership-form select {

            cursor: pointer;

            appearance: auto;

            -webkit-appearance: auto;

            -moz-appearance: auto;

        }


        .ownership-form select:focus,
        .ownership-form input:focus {

            outline: none;

            border-color: #2563eb;

            box-shadow:
                0 0 0 3px
                rgba(37, 99, 235, 0.12);

        }


        .ownership-form small {

            display: block;

            margin-top: 6px;

            color: #64748b;

        }


        .field-help {

            margin-top: 6px;

            font-size: 13px;

            color: #64748b;

        }


        .form-grid {

            display: grid;

            grid-template-columns:
                repeat(
                    2,
                    minmax(0, 1fr)
                );

            gap: 20px;

        }


        .form-full {

            grid-column: 1 / -1;

        }


        .message-success {

            background: #dcfce7;

            color: #166534;

            padding: 15px;

            border-radius: 8px;

            margin-bottom: 20px;

        }


        .message-error {

            background: #fee2e2;

            color: #991b1b;

            padding: 15px;

            border-radius: 8px;

            margin-bottom: 20px;

        }


        @media (max-width: 768px) {

            .form-grid {

                grid-template-columns: 1fr;

            }

            .form-full {

                grid-column: auto;

            }

        }

    </style>

</head>


<body>

<div class="layout">


    <!-- ========================= -->
    <!-- SIDEBAR -->
    <!-- ========================= -->

    <aside class="sidebar">

        <?php require_once "../includes/sidebar.php"; ?>

    </aside>


    <!-- ========================= -->
    <!-- MAIN CONTENT -->
    <!-- ========================= -->

    <div class="main-content">


        <!-- TOPBAR -->

        <header class="topbar">


            <div class="topbar-title">
                Add Ownership History
            </div>


            <div class="user-info">

                <span>

                    <?= htmlspecialchars(
                        $_SESSION["FirstName"]
                    ) ?>

                    <?= htmlspecialchars(
                        $_SESSION["LastName"]
                    ) ?>

                </span>


                <div class="user-avatar">

                    <?= strtoupper(
                        substr(
                            $_SESSION["FirstName"],
                            0,
                            1
                        )
                    ) ?>

                </div>

            </div>


        </header>


        <!-- ========================= -->
        <!-- PAGE CONTENT -->
        <!-- ========================= -->

        <main class="content">


            <div class="page-title">

                <h1>
                    Add Ownership History
                </h1>

                <p class="page-subtitle">
                    Record a vehicle's ownership period
                    and transfer information.
                </p>

            </div>


            <!-- SUCCESS -->

            <?php if ($success !== ""): ?>

                <div class="message-success">

                    <?= htmlspecialchars(
                        $success
                    ) ?>

                </div>

            <?php endif; ?>


            <!-- ERROR -->

            <?php if ($error !== ""): ?>

                <div class="message-error">

                    <?= htmlspecialchars(
                        $error
                    ) ?>

                </div>

            <?php endif; ?>


            <!-- FORM CARD -->

            <section class="card">


                <h2>
                    Ownership Information
                </h2>


                <p
                    style="
                        color: #64748b;
                        margin-top: 8px;
                    "
                >
                    Select the vehicle and owner,
                    then enter the period during which
                    the owner possessed the vehicle.
                </p>


                <form
                    method="POST"
                    action=""
                    class="ownership-form"
                    style="margin-top: 25px;"
                >


                    <div class="form-grid">


                        <!-- ========================= -->
                        <!-- VEHICLE DROPDOWN -->
                        <!-- ========================= -->

                        <div class="form-group">

                            <label for="VehicleID">
                                Vehicle *
                            </label>


                            <select
                                id="VehicleID"
                                name="VehicleID"
                                required
                            >

                                <option value="">
                                    -- Select Vehicle --
                                </option>


                                <?php if (
                                    count($vehicles) === 0
                                ): ?>

                                    <option
                                        value=""
                                        disabled
                                    >
                                        No vehicles available
                                    </option>

                                <?php else: ?>

                                    <?php foreach (
                                        $vehicles
                                        as $vehicle
                                    ): ?>

                                        <option
                                            value="<?= htmlspecialchars(
                                                $vehicle["VehicleID"]
                                            ) ?>"
                                            <?= (
                                                ($_POST["VehicleID"] ?? "")
                                                == $vehicle["VehicleID"]
                                            )
                                                ? "selected"
                                                : "" ?>
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

                                            -

                                            <?= htmlspecialchars(
                                                $vehicle["VehicleYear"]
                                            ) ?>

                                            -
                                            VIN:
                                            <?= htmlspecialchars(
                                                $vehicle["VIN"]
                                            ) ?>

                                        </option>

                                    <?php endforeach; ?>

                                <?php endif; ?>

                            </select>


                            <div class="field-help">
                                Select the vehicle whose ownership
                                history you are recording.
                            </div>

                        </div>


                        <!-- ========================= -->
                        <!-- OWNER DROPDOWN -->
                        <!-- ========================= -->

                        <div class="form-group">

                            <label for="OwnerID">
                                Owner *
                            </label>


                            <select
                                id="OwnerID"
                                name="OwnerID"
                                required
                            >

                                <option value="">
                                    -- Select Owner --
                                </option>


                                <?php if (
                                    count($owners) === 0
                                ): ?>

                                    <option
                                        value=""
                                        disabled
                                    >
                                        No owners available
                                    </option>

                                <?php else: ?>

                                    <?php foreach (
                                        $owners
                                        as $owner
                                    ): ?>

                                        <option
                                            value="<?= htmlspecialchars(
                                                $owner["OwnerID"]
                                            ) ?>"
                                            <?= (
                                                ($_POST["OwnerID"] ?? "")
                                                == $owner["OwnerID"]
                                            )
                                                ? "selected"
                                                : "" ?>
                                        >

                                            <?= htmlspecialchars(
                                                $owner["FirstName"]
                                            ) ?>

                                            <?= htmlspecialchars(
                                                $owner["LastName"]
                                            ) ?>

                                            <?php if (
                                                !empty(
                                                    $owner["Email"]
                                                )
                                            ): ?>

                                                -
                                                <?= htmlspecialchars(
                                                    $owner["Email"]
                                                ) ?>

                                            <?php endif; ?>

                                        </option>

                                    <?php endforeach; ?>

                                <?php endif; ?>

                            </select>


                            <div class="field-help">
                                Select the owner associated with
                                this ownership period.
                            </div>

                        </div>


                        <!-- ========================= -->
                        <!-- START DATE -->
                        <!-- ========================= -->

                        <div class="form-group">

                            <label for="StartDate">
                                Ownership Start Date *
                            </label>


                            <input
                                type="date"
                                id="StartDate"
                                name="StartDate"
                                value="<?= htmlspecialchars(
                                    $_POST["StartDate"] ?? ""
                                ) ?>"
                                required
                            >


                            <div class="field-help">
                                The date the owner acquired
                                the vehicle.
                            </div>

                        </div>


                        <!-- ========================= -->
                        <!-- END DATE -->
                        <!-- ========================= -->

                        <div class="form-group">

                            <label for="EndDate">
                                Ownership End Date
                            </label>


                            <input
                                type="date"
                                id="EndDate"
                                name="EndDate"
                                value="<?= htmlspecialchars(
                                    $_POST["EndDate"] ?? ""
                                ) ?>"
                            >


                            <div class="field-help">
                                Leave blank if this was the
                                current owner's ongoing ownership.
                            </div>

                        </div>


                        <!-- ========================= -->
                        <!-- TRANSFER REASON -->
                        <!-- ========================= -->

                        <div class="form-group form-full">

                            <label for="TransferReason">
                                Transfer Reason
                            </label>


                            <input
                                type="text"
                                id="TransferReason"
                                name="TransferReason"
                                placeholder="Example: Purchased, Sold, Gift, Transfer"
                                value="<?= htmlspecialchars(
                                    $_POST["TransferReason"] ?? ""
                                ) ?>"
                            >


                            <div class="field-help">
                                Explain why ownership changed,
                                if applicable.
                            </div>

                        </div>


                    </div>


                    <!-- ========================= -->
                    <!-- BUTTONS -->
                    <!-- ========================= -->

                    <div
                        style="
                            display: flex;
                            gap: 10px;
                            margin-top: 25px;
                            flex-wrap: wrap;
                        "
                    >


                        <a
                            href="index.php"
                            class="button button-secondary"
                        >
                            Cancel
                        </a>


                        <button
                            type="submit"
                            class="button"
                        >
                            Add Ownership Record
                        </button>


                    </div>


                </form>


            </section>


        </main>

    </div>

</div>


<script src="../js/app.js"></script>

</body>

</html>