<?php

require_once "../includes/auth.php";
require_once "../includes/database.php";

$basePath = "../";
$activePage = "ownership_history";

$ownershipID = (int) ($_GET["id"] ?? $_POST["OwnershipID"] ?? 0);

if ($ownershipID <= 0) {
    header("Location: index.php");
    exit;
}


/*
 * Load ownership record.
 */

$stmt = $pdo->prepare("
    SELECT
        oh.*,
        v.PlateNumber,
        v.VIN,
        v.Make,
        v.Model,
        v.VehicleYear,
        o.FirstName,
        o.LastName
    FROM ownership_history oh

    INNER JOIN vehicles v
        ON oh.VehicleID = v.VehicleID

    INNER JOIN owners o
        ON oh.OwnerID = o.OwnerID

    WHERE oh.OwnershipID = ?
");

$stmt->execute([$ownershipID]);

$record = $stmt->fetch();

if (!$record) {
    header("Location: index.php");
    exit;
}


$error = "";


/*
 * Delete ownership record.
 */

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    try {

        $pdo->beginTransaction();


        $vehicleID = (int) $record["VehicleID"];

        $wasCurrent = empty($record["EndDate"]);


        /*
         * If deleting a CURRENT ownership record,
         * we need to find the previous owner.
         */

        $previousOwner = null;

        if ($wasCurrent) {

            $previousStmt = $pdo->prepare("
                SELECT
                    OwnershipID,
                    OwnerID,
                    StartDate,
                    EndDate
                FROM ownership_history

                WHERE VehicleID = ?
                AND OwnershipID <> ?

                AND StartDate <= ?

                ORDER BY StartDate DESC, OwnershipID DESC

                LIMIT 1
            ");

            $previousStmt->execute([
                $vehicleID,
                $ownershipID,
                $record["StartDate"]
            ]);

            $previousOwner = $previousStmt->fetch();

        }


        /*
         * Delete the ownership record.
         */

        $deleteStmt = $pdo->prepare("
            DELETE FROM ownership_history
            WHERE OwnershipID = ?
        ");

        $deleteStmt->execute([$ownershipID]);


        /*
         * If the deleted record was current and
         * a previous owner exists:
         *
         * 1. Make previous record current.
         * 2. Update vehicles.OwnerID.
         */

        if ($wasCurrent && $previousOwner) {

            $restoreHistoryStmt = $pdo->prepare("
                UPDATE ownership_history

                SET EndDate = NULL

                WHERE OwnershipID = ?
            ");

            $restoreHistoryStmt->execute([
                $previousOwner["OwnershipID"]
            ]);


            $restoreVehicleStmt = $pdo->prepare("
                UPDATE vehicles

                SET OwnerID = ?

                WHERE VehicleID = ?
            ");

            $restoreVehicleStmt->execute([
                $previousOwner["OwnerID"],
                $vehicleID
            ]);

        }


        /*
         * If there is no previous owner,
         * check whether another current record exists.
         */

        if ($wasCurrent && !$previousOwner) {

            $remainingCurrentStmt = $pdo->prepare("
                SELECT OwnerID

                FROM ownership_history

                WHERE VehicleID = ?
                AND EndDate IS NULL

                ORDER BY StartDate DESC, OwnershipID DESC

                LIMIT 1
            ");

            $remainingCurrentStmt->execute([
                $vehicleID
            ]);

            $remainingCurrent = $remainingCurrentStmt->fetch();


            if ($remainingCurrent) {

                $restoreVehicleStmt = $pdo->prepare("
                    UPDATE vehicles

                    SET OwnerID = ?

                    WHERE VehicleID = ?
                ");

                $restoreVehicleStmt->execute([
                    $remainingCurrent["OwnerID"],
                    $vehicleID
                ]);

            } else {

                /*
                 * There is no remaining ownership history.
                 *
                 * We deliberately do not change vehicles.OwnerID.
                 *
                 * This avoids creating an invalid owner relationship
                 * because vehicles.OwnerID is NOT NULL.
                 */

            }

        }


        /*
         * Audit deletion.
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
            "Deleted ownership record for vehicle " . $record["PlateNumber"],
            "ownership_history",
            $ownershipID,
            $_SERVER["REMOTE_ADDR"] ?? null
        ]);


        $pdo->commit();


        header("Location: index.php");
        exit;


    } catch (Exception $e) {

        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }

        $error = "Unable to delete the ownership record.";
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

    <title>Delete Ownership Record - VISRS</title>

    <link
        rel="stylesheet"
        href="../css/style.css"
    >

</head>

<body>

<div class="layout">

    <?php require_once "../includes/sidebar.php"; ?>

    <main class="main-content">

        <div class="topbar">

            <div>

                <h1>Delete Ownership Record</h1>

                <p>
                    Confirm removal of this ownership record.
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


                <h2>
                    Are you sure you want to delete this record?
                </h2>


                <div style="margin-top:20px;">

                    <p>
                        <strong>Vehicle:</strong>

                        <?= htmlspecialchars(
                            $record["PlateNumber"]
                        ) ?>

                        -

                        <?= htmlspecialchars(
                            $record["Make"]
                        ) ?>

                        <?= htmlspecialchars(
                            $record["Model"]
                        ) ?>

                    </p>


                    <p>
                        <strong>Owner:</strong>

                        <?= htmlspecialchars(
                            $record["FirstName"]
                        ) ?>

                        <?= htmlspecialchars(
                            $record["LastName"]
                        ) ?>

                    </p>


                    <p>
                        <strong>Start Date:</strong>

                        <?= htmlspecialchars(
                            $record["StartDate"]
                        ) ?>

                    </p>


                    <p>
                        <strong>End Date:</strong>

                        <?= $record["EndDate"]
                            ? htmlspecialchars($record["EndDate"])
                            : "Current Owner"
                        ?>

                    </p>


                    <p>
                        <strong>Transfer Reason:</strong>

                        <?= $record["TransferReason"]
                            ? htmlspecialchars(
                                $record["TransferReason"]
                            )
                            : "Not specified"
                        ?>

                    </p>

                </div>


                <?php if (empty($record["EndDate"])): ?>

                    <div
                        style="
                            background:#fff7ed;
                            color:#9a3412;
                            padding:14px;
                            border-radius:8px;
                            margin-top:20px;
                        "
                    >

                        <strong>Important:</strong>

                        This is the vehicle's current ownership record.

                        If a previous ownership record exists,
                        the previous owner will be restored as the
                        current owner.

                    </div>

                <?php endif; ?>


                <form
                    method="POST"
                    style="
                        display:flex;
                        gap:10px;
                        margin-top:25px;
                    "
                >

                    <input
                        type="hidden"
                        name="OwnershipID"
                        value="<?= $ownershipID ?>"
                    >


                    <button
                        type="submit"
                        class="button"
                        style="background:#991b1b;"
                    >
                        Yes, Delete Record
                    </button>


                    <a
                        href="view.php?id=<?= $ownershipID ?>"
                        class="button button-secondary"
                    >
                        Cancel
                    </a>

                </form>

            </div>

        </div>

    </main>

</div>

</body>

</html>