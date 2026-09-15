<?php

require_once "../includes/auth.php";
require_once "../includes/database.php";

requireRole(["Admin", "Police"]);

$basePath = "../";
$activePage = "ownership_history";

$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $vehicleID = (int) ($_POST["VehicleID"] ?? 0);
    $ownerID = (int) ($_POST["OwnerID"] ?? 0);
    $startDate = trim($_POST["StartDate"] ?? "");
    $endDate = trim($_POST["EndDate"] ?? "");
    $transferReason = trim($_POST["TransferReason"] ?? "");

    /*
     * Basic validation.
     */

    if ($vehicleID <= 0 || $ownerID <= 0 || $startDate === "") {

        $error = "Vehicle, owner, and start date are required.";

    } elseif ($endDate !== "" && $endDate < $startDate) {

        $error = "End date cannot be earlier than the start date.";

    } else {

        /*
         * Get selected vehicle.
         */

        $vehicleStmt = $pdo->prepare("
            SELECT *
            FROM vehicles
            WHERE VehicleID = ?
        ");

        $vehicleStmt->execute([$vehicleID]);

        $vehicle = $vehicleStmt->fetch();


        /*
         * Get selected owner.
         */

        $ownerStmt = $pdo->prepare("
            SELECT *
            FROM owners
            WHERE OwnerID = ?
        ");

        $ownerStmt->execute([$ownerID]);

        $owner = $ownerStmt->fetch();


        if (!$vehicle) {

            $error = "The selected vehicle does not exist.";

        } elseif (!$owner) {

            $error = "The selected owner does not exist.";

        } else {

            /*
             * Blank EndDate means this is the current owner.
             */

            $isCurrent = ($endDate === "");


            /*
             * Find the vehicle's current ownership record.
             */

            $currentStmt = $pdo->prepare("
                SELECT
                    oh.OwnershipID,
                    oh.OwnerID,
                    oh.StartDate,
                    oh.EndDate
                FROM ownership_history oh
                WHERE oh.VehicleID = ?
                AND oh.EndDate IS NULL
                ORDER BY oh.StartDate DESC, oh.OwnershipID DESC
                LIMIT 1
            ");

            $currentStmt->execute([$vehicleID]);

            $currentRecord = $currentStmt->fetch();


            /*
             * CURRENT OWNERSHIP / TRANSFER
             */

            if ($isCurrent) {

                /*
                 * If there is already a current owner,
                 * treat this as an ownership transfer.
                 */

                if ($currentRecord) {

                    if ((int) $currentRecord["OwnerID"] === $ownerID) {

                        $error =
                            "This owner is already the current owner of this vehicle.";

                    } elseif ($startDate <= $currentRecord["StartDate"]) {

                        $error =
                            "The new ownership start date must be after the previous owner's start date. " .
                            "The previous owner became the owner on " .
                            date(
                                "m/d/Y",
                                strtotime($currentRecord["StartDate"])
                            ) .
                            ".";

                    } else {

                        try {

                            $pdo->beginTransaction();


                            /*
                             * Close the previous owner's record.
                             */

                            $closePreviousStmt = $pdo->prepare("
                                UPDATE ownership_history
                                SET EndDate = ?
                                WHERE OwnershipID = ?
                            ");

                            $closePreviousStmt->execute([
                                $startDate,
                                $currentRecord["OwnershipID"]
                            ]);


                            /*
                             * Create the new current ownership record.
                             */

                            $insertStmt = $pdo->prepare("
                                INSERT INTO ownership_history
                                (
                                    VehicleID,
                                    OwnerID,
                                    StartDate,
                                    EndDate,
                                    TransferReason
                                )
                                VALUES (?, ?, ?, NULL, ?)
                            ");

                            $insertStmt->execute([
                                $vehicleID,
                                $ownerID,
                                $startDate,
                                $transferReason !== ""
                                    ? $transferReason
                                    : "Ownership transfer"
                            ]);

                            $ownershipID = $pdo->lastInsertId();


                            /*
                             * Update the vehicle's current owner.
                             */

                            $updateVehicleStmt = $pdo->prepare("
                                UPDATE vehicles
                                SET OwnerID = ?
                                WHERE VehicleID = ?
                            ");

                            $updateVehicleStmt->execute([
                                $ownerID,
                                $vehicleID
                            ]);


                            /*
                             * Audit log.
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
                                "Transferred vehicle " .
                                $vehicle["PlateNumber"] .
                                " ownership from OwnerID " .
                                $currentRecord["OwnerID"] .
                                " to OwnerID " .
                                $ownerID,
                                "ownership_history",
                                $ownershipID,
                                $_SERVER["REMOTE_ADDR"] ?? null
                            ]);


                            $pdo->commit();


                            header(
                                "Location: view.php?id=" . $ownershipID
                            );

                            exit;


                        } catch (Exception $e) {

                            if ($pdo->inTransaction()) {
                                $pdo->rollBack();
                            }

                            $error =
                                "Unable to complete the ownership transfer.";
                        }
                    }

                } else {

                    /*
                     * No current ownership record exists.
                     *
                     * Create a new current ownership record.
                     */

                    if ((int) $vehicle["OwnerID"] === $ownerID) {

                        $error =
                            "This owner is already listed as the vehicle's current owner.";

                    } else {

                        try {

                            $pdo->beginTransaction();


                            /*
                             * Insert current ownership.
                             */

                            $insertStmt = $pdo->prepare("
                                INSERT INTO ownership_history
                                (
                                    VehicleID,
                                    OwnerID,
                                    StartDate,
                                    EndDate,
                                    TransferReason
                                )
                                VALUES (?, ?, ?, NULL, ?)
                            ");

                            $insertStmt->execute([
                                $vehicleID,
                                $ownerID,
                                $startDate,
                                $transferReason !== ""
                                    ? $transferReason
                                    : "Initial ownership"
                            ]);

                            $ownershipID = $pdo->lastInsertId();


                            /*
                             * Update vehicle owner.
                             */

                            $updateVehicleStmt = $pdo->prepare("
                                UPDATE vehicles
                                SET OwnerID = ?
                                WHERE VehicleID = ?
                            ");

                            $updateVehicleStmt->execute([
                                $ownerID,
                                $vehicleID
                            ]);


                            /*
                             * Audit log.
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
                                "Added current ownership record for vehicle " .
                                $vehicle["PlateNumber"],
                                "ownership_history",
                                $ownershipID,
                                $_SERVER["REMOTE_ADDR"] ?? null
                            ]);


                            $pdo->commit();


                            header(
                                "Location: view.php?id=" . $ownershipID
                            );

                            exit;


                        } catch (Exception $e) {

                            if ($pdo->inTransaction()) {
                                $pdo->rollBack();
                            }

                            $error =
                                "Unable to save the ownership record.";
                        }
                    }
                }


            /*
             * HISTORICAL OWNERSHIP RECORD
             */

            } else {

                /*
                 * Check for overlap with existing ownership records.
                 */

                $overlapStmt = $pdo->prepare("
                    SELECT
                        OwnershipID,
                        StartDate,
                        EndDate
                    FROM ownership_history
                    WHERE VehicleID = ?
                    AND StartDate < ?
                    AND (
                        EndDate IS NULL
                        OR EndDate > ?
                    )
                    LIMIT 1
                ");

                $overlapStmt->execute([
                    $vehicleID,
                    $endDate,
                    $startDate
                ]);

                $overlap = $overlapStmt->fetch();


                if ($overlap) {

                    $error =
                        "The selected ownership dates overlap an existing ownership record for this vehicle.";

                } else {

                    try {

                        $pdo->beginTransaction();


                        /*
                         * Insert historical record.
                         */

                        $insertStmt = $pdo->prepare("
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

                        $insertStmt->execute([
                            $vehicleID,
                            $ownerID,
                            $startDate,
                            $endDate,
                            $transferReason !== ""
                                ? $transferReason
                                : "Historical ownership"
                        ]);

                        $ownershipID = $pdo->lastInsertId();


                        /*
                         * Audit log.
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
                            "Added historical ownership record for vehicle " .
                            $vehicle["PlateNumber"],
                            "ownership_history",
                            $ownershipID,
                            $_SERVER["REMOTE_ADDR"] ?? null
                        ]);


                        $pdo->commit();


                        header(
                            "Location: view.php?id=" . $ownershipID
                        );

                        exit;


                    } catch (Exception $e) {

                        if ($pdo->inTransaction()) {
                            $pdo->rollBack();
                        }

                        $error =
                            "Unable to save the ownership record.";
                    }
                }
            }
        }
    }
}


/*
 * Load vehicles.
 */

$vehiclesStmt = $pdo->query("
    SELECT
        v.VehicleID,
        v.PlateNumber,
        v.VIN,
        v.Make,
        v.Model,
        v.VehicleYear,
        o.FirstName,
        o.LastName
    FROM vehicles v
    LEFT JOIN owners o
        ON v.OwnerID = o.OwnerID
    ORDER BY v.PlateNumber ASC
");

$vehicles = $vehiclesStmt->fetchAll();


/*
 * Load owners.
 */

$ownersStmt = $pdo->query("
    SELECT
        OwnerID,
        FirstName,
        LastName
    FROM owners
    ORDER BY LastName ASC, FirstName ASC
");

$owners = $ownersStmt->fetchAll();

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Add Ownership Record - VISRS</title>

    <link
        rel="stylesheet"
        href="../css/style.css"
    >

</head>

<body>

<div class="layout">

    <?php require_once "../includes/sidebar.php"; ?>

    <main class="main-content">

        <?php

        $pageTitle = "Add Ownership Record";
        $pageSubtitle = "Record ownership information for a vehicle.";

        include __DIR__ . "/../includes/header.php";

        ?>


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

                    <div style="margin-bottom:20px;">

                        <label for="VehicleID">
                            <strong>Vehicle</strong>
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

                            <?php foreach ($vehicles as $vehicle): ?>

                                <option
                                    value="<?= $vehicle["VehicleID"] ?>"
                                    <?= (
                                        isset($_POST["VehicleID"]) &&
                                        $_POST["VehicleID"] ==
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


                    <div style="margin-bottom:20px;">

                        <label for="OwnerID">
                            <strong>Owner</strong>
                        </label>

                        <select
                            name="OwnerID"
                            id="OwnerID"
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
                                -- Select Owner --
                            </option>

                            <?php foreach ($owners as $owner): ?>

                                <option
                                    value="<?= $owner["OwnerID"] ?>"
                                    <?= (
                                        isset($_POST["OwnerID"]) &&
                                        $_POST["OwnerID"] ==
                                        $owner["OwnerID"]
                                    )
                                        ? "selected"
                                        : ""
                                    ?>
                                >

                                    <?= htmlspecialchars(
                                        $owner["FirstName"]
                                    ) ?>

                                    <?= htmlspecialchars(
                                        $owner["LastName"]
                                    ) ?>

                                </option>

                            <?php endforeach; ?>

                        </select>

                    </div>


                    <div style="margin-bottom:20px;">

                        <label for="StartDate">
                            <strong>Start Date</strong>
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


                    <div style="margin-bottom:20px;">

                        <label for="EndDate">
                            <strong>End Date</strong>
                        </label>

                        <input
                            type="date"
                            name="EndDate"
                            id="EndDate"
                            value="<?= htmlspecialchars(
                                $_POST["EndDate"] ?? ""
                            ) ?>"
                            style="
                                width:100%;
                                min-height:46px;
                                padding:10px;
                                margin-top:8px;
                            "
                        >

                        <small>
                            Leave blank if this is the current owner.
                        </small>

                    </div>


                    <div style="margin-bottom:20px;">

                        <label for="TransferReason">
                            <strong>Transfer Reason</strong>
                        </label>

                        <input
                            type="text"
                            name="TransferReason"
                            id="TransferReason"
                            maxlength="255"
                            value="<?= htmlspecialchars(
                                $_POST["TransferReason"] ?? ""
                            ) ?>"
                            placeholder="Example: Sale of vehicle"
                            style="
                                width:100%;
                                min-height:46px;
                                padding:10px;
                                margin-top:8px;
                            "
                        >

                    </div>


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
                            Save Ownership Record
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