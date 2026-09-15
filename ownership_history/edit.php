<?php

require_once __DIR__ . "/../includes/auth.php";
requireRole(["Admin", "Police"]);

require_once __DIR__ . "/../includes/database.php";
require_once __DIR__ . "/../includes/functions.php";

$activePage = "ownership_history";
$pageTitle = "Edit Ownership Record";
$pageSubtitle = "Update ownership information.";

$ownershipID = (int) (
    $_GET["id"] ??
    $_POST["OwnershipID"] ??
    0
);

if ($ownershipID <= 0) {
    header("Location: index.php");
    exit;
}

$error = "";


/*
|--------------------------------------------------------------------------
| Load Existing Ownership Record
|--------------------------------------------------------------------------
*/

$stmt = $pdo->prepare("
    SELECT
        oh.*,
        v.PlateNumber,
        v.VIN,
        v.Make,
        v.Model,
        v.VehicleYear
    FROM ownership_history oh
    INNER JOIN vehicles v
        ON oh.VehicleID = v.VehicleID
    WHERE oh.OwnershipID = ?
    LIMIT 1
");

$stmt->execute([
    $ownershipID
]);

$record = $stmt->fetch();

if (!$record) {
    header("Location: index.php");
    exit;
}


/*
|--------------------------------------------------------------------------
| Process Update
|--------------------------------------------------------------------------
*/

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    requireValidCSRF();

    $vehicleID = (int) (
        $_POST["VehicleID"] ?? 0
    );

    $ownerID = (int) (
        $_POST["OwnerID"] ?? 0
    );

    $startDate = trim(
        $_POST["StartDate"] ?? ""
    );

    $endDate = trim(
        $_POST["EndDate"] ?? ""
    );

    $transferReason = trim(
        $_POST["TransferReason"] ?? ""
    );


    /*
     * Basic validation.
     */

    if (
        $vehicleID <= 0 ||
        $ownerID <= 0 ||
        $startDate === ""
    ) {

        $error =
            "Vehicle, owner, and start date are required.";

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
            "Please enter a valid start date.";

    } elseif (
        $endDate !== "" &&
        (
            !DateTime::createFromFormat(
                "Y-m-d",
                $endDate
            ) ||
            DateTime::createFromFormat(
                "Y-m-d",
                $endDate
            )->format("Y-m-d") !== $endDate
        )
    ) {

        $error =
            "Please enter a valid end date.";

    } elseif (
        $endDate !== "" &&
        $endDate < $startDate
    ) {

        $error =
            "End date cannot be earlier than the start date.";

    } elseif (
        strlen($transferReason) > 255
    ) {

        $error =
            "Transfer reason cannot exceed 255 characters.";

    } else {


        /*
         * Verify vehicle.
         */

        $vehicleStmt = $pdo->prepare("
            SELECT
                VehicleID,
                PlateNumber,
                VIN,
                OwnerID,
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


        /*
         * Verify owner.
         */

        $ownerStmt = $pdo->prepare("
            SELECT
                OwnerID,
                FirstName,
                LastName
            FROM owners
            WHERE OwnerID = ?
            LIMIT 1
        ");

        $ownerStmt->execute([
            $ownerID
        ]);

        $owner = $ownerStmt->fetch();


        if (!$vehicle) {

            $error =
                "The selected vehicle does not exist.";

        } elseif (!$owner) {

            $error =
                "The selected owner does not exist.";

        } else {


            /*
             * Check ownership-period overlap.
             *
             * Exclude the record currently being edited.
             */

            $overlapStmt = $pdo->prepare("
                SELECT
                    OwnershipID
                FROM ownership_history
                WHERE VehicleID = ?
                AND OwnershipID <> ?
                AND StartDate <= ?
                AND (
                    EndDate IS NULL
                    OR EndDate >= ?
                )
                LIMIT 1
            ");

            /*
             * For a current record, use a far-future
             * comparison date.
             */

            $comparisonEndDate =
                $endDate !== ""
                    ? $endDate
                    : "9999-12-31";

            $overlapStmt->execute([
                $vehicleID,
                $ownershipID,
                $comparisonEndDate,
                $startDate
            ]);

            $overlap =
                $overlapStmt->fetch();


            if ($overlap) {

                $error =
                    "The selected ownership dates overlap another ownership record for this vehicle.";

            } else {

                $isCurrent =
                    ($endDate === "");


                /*
                 * If this record is current,
                 * make sure another current record
                 * does not exist for the vehicle.
                 */

                if ($isCurrent) {

                    $currentStmt = $pdo->prepare("
                        SELECT
                            OwnershipID
                        FROM ownership_history
                        WHERE VehicleID = ?
                        AND EndDate IS NULL
                        AND OwnershipID <> ?
                        LIMIT 1
                    ");

                    $currentStmt->execute([
                        $vehicleID,
                        $ownershipID
                    ]);

                    $otherCurrent =
                        $currentStmt->fetch();


                    if ($otherCurrent) {

                        $error =
                            "This vehicle already has another current ownership record.";
                    }
                }


                if ($error === "") {

                    try {

                        $pdo->beginTransaction();


                        /*
                         * Remember the original vehicle
                         * and whether the original record
                         * was current.
                         */

                        $originalVehicleID =
                            (int) $record["VehicleID"];

                        $originalWasCurrent =
                            empty($record["EndDate"]);


                        /*
                         * Update ownership record.
                         */

                        $updateStmt = $pdo->prepare("
                            UPDATE ownership_history
                            SET
                                VehicleID = ?,
                                OwnerID = ?,
                                StartDate = ?,
                                EndDate = ?,
                                TransferReason = ?
                            WHERE OwnershipID = ?
                        ");

                        $updateStmt->execute([
                            $vehicleID,
                            $ownerID,
                            $startDate,
                            $endDate !== ""
                                ? $endDate
                                : null,
                            $transferReason !== ""
                                ? $transferReason
                                : null,
                            $ownershipID
                        ]);


                        /*
                         * If the edited record is CURRENT,
                         * update the selected vehicle's
                         * current OwnerID.
                         */

                        if ($isCurrent) {

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
                             * If the record was moved from
                             * another vehicle, restore the
                             * previous current owner of the
                             * original vehicle.
                             */

                            if (
                                $originalVehicleID !==
                                $vehicleID
                            ) {

                                $previousCurrentStmt = $pdo->prepare("
                                    SELECT
                                        OwnerID
                                    FROM ownership_history
                                    WHERE VehicleID = ?
                                    AND EndDate IS NULL
                                    AND OwnershipID <> ?
                                    ORDER BY
                                        StartDate DESC,
                                        OwnershipID DESC
                                    LIMIT 1
                                ");

                                $previousCurrentStmt->execute([
                                    $originalVehicleID,
                                    $ownershipID
                                ]);

                                $previousCurrent =
                                    $previousCurrentStmt->fetch();


                                if ($previousCurrent) {

                                    $restoreVehicleStmt = $pdo->prepare("
                                        UPDATE vehicles
                                        SET OwnerID = ?
                                        WHERE VehicleID = ?
                                    ");

                                    $restoreVehicleStmt->execute([
                                        $previousCurrent["OwnerID"],
                                        $originalVehicleID
                                    ]);

                                }

                            }

                        } elseif ($originalWasCurrent) {

                            /*
                             * The record was changed from
                             * CURRENT to HISTORICAL.
                             *
                             * Find another current record
                             * for the vehicle.
                             */

                            $replacementStmt = $pdo->prepare("
                                SELECT
                                    OwnerID
                                FROM ownership_history
                                WHERE VehicleID = ?
                                AND EndDate IS NULL
                                AND OwnershipID <> ?
                                ORDER BY
                                    StartDate DESC,
                                    OwnershipID DESC
                                LIMIT 1
                            ");

                            $replacementStmt->execute([
                                $vehicleID,
                                $ownershipID
                            ]);

                            $replacement =
                                $replacementStmt->fetch();


                            if ($replacement) {

                                $updateVehicleStmt = $pdo->prepare("
                                    UPDATE vehicles
                                    SET OwnerID = ?
                                    WHERE VehicleID = ?
                                ");

                                $updateVehicleStmt->execute([
                                    $replacement["OwnerID"],
                                    $vehicleID
                                ]);

                            } else {

                                /*
                                 * No current ownership record
                                 * remains for the vehicle.
                                 *
                                 * Keep the vehicle OwnerID
                                 * synchronized with the
                                 * selected historical owner
                                 * rather than leaving it pointing
                                 * to an invalid current record.
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
                            }
                        }


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
                            "Updated ownership record for vehicle " .
                            $vehicle["PlateNumber"],
                            "ownership_history",
                            $ownershipID,
                            $_SERVER["REMOTE_ADDR"] ?? null
                        ]);


                        $pdo->commit();


                        header(
                            "Location: view.php?id=" .
                            $ownershipID
                        );

                        exit;


                    } catch (Exception $e) {

                        if (
                            $pdo->inTransaction()
                        ) {
                            $pdo->rollBack();
                        }

                        $error =
                            "Unable to update the ownership record.";
                    }
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

$vehicles = $vehiclesStmt->fetchAll();


/*
|--------------------------------------------------------------------------
| Load Owners
|--------------------------------------------------------------------------
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

    <title>
        Edit Ownership Record - VISRS
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

        <?php
        include __DIR__ . "/../includes/header.php";
        ?>


        <div class="content">

            <div class="page-title">

                <h1>
                    Edit Ownership Record
                </h1>

                <p class="page-subtitle">
                    Update ownership information.
                </p>

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
                            Unable to Save Changes
                        </strong>

                        <div style="margin-top:5px;">

                            <?= htmlspecialchars($error) ?>

                        </div>

                    </div>

                <?php endif; ?>


                <form method="POST">

                    <input
                        type="hidden"
                        name="OwnershipID"
                        value="<?= $ownershipID ?>"
                    >

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

                            <?php foreach (
                                $vehicles
                                as $vehicle
                            ): ?>

                                <option
                                    value="<?= (int) $vehicle["VehicleID"] ?>"
                                    <?= (
                                        (
                                            $_POST["VehicleID"]
                                            ?? $record["VehicleID"]
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


                    <!-- OWNER -->

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

                            <?php foreach (
                                $owners
                                as $owner
                            ): ?>

                                <option
                                    value="<?= (int) $owner["OwnerID"] ?>"
                                    <?= (
                                        (
                                            $_POST["OwnerID"]
                                            ?? $record["OwnerID"]
                                        ) == $owner["OwnerID"]
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


                    <!-- START DATE -->

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
                                $_POST["StartDate"]
                                ?? $record["StartDate"]
                            ) ?>"
                            style="
                                width:100%;
                                min-height:46px;
                                padding:10px;
                                margin-top:8px;
                            "
                        >

                    </div>


                    <!-- END DATE -->

                    <div style="margin-bottom:20px;">

                        <label for="EndDate">
                            <strong>End Date</strong>
                        </label>

                        <input
                            type="date"
                            name="EndDate"
                            id="EndDate"
                            value="<?= htmlspecialchars(
                                $_POST["EndDate"]
                                ?? (
                                    $record["EndDate"]
                                    ?? ""
                                )
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
                            Leave blank if this is the current owner.
                        </small>

                    </div>


                    <!-- TRANSFER REASON -->

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
                                $_POST["TransferReason"]
                                ?? (
                                    $record["TransferReason"]
                                    ?? ""
                                )
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


                    <!-- ACTIONS -->

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
                            href="view.php?id=<?= $ownershipID ?>"
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