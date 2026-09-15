<?php

require_once __DIR__ . "/../includes/auth.php";
require_once __DIR__ . "/../includes/database.php";

requireRole(["Admin", "Police"]);

$activePage = "insurance";

$pageTitle = "Delete Insurance Record";
$pageSubtitle = "Confirm removal of this insurance record.";

$error = "";

$insuranceID = (int) (
    $_GET["id"] ??
    $_POST["InsuranceID"] ??
    0
);


if ($insuranceID <= 0) {

    header("Location: index.php");
    exit;
}


/*
|--------------------------------------------------------------------------
| Load Insurance Record
|--------------------------------------------------------------------------
*/

$stmt = $pdo->prepare("
    SELECT
        i.InsuranceID,
        i.VehicleID,
        i.PolicyNumber,
        i.ProviderName,
        i.CoverageType,
        i.StartDate,
        i.ExpiryDate,
        i.Status,

        v.PlateNumber,
        v.Make,
        v.Model,
        v.VehicleYear

    FROM insurance i

    INNER JOIN vehicles v
        ON i.VehicleID = v.VehicleID

    WHERE i.InsuranceID = ?

    LIMIT 1
");

$stmt->execute([
    $insuranceID
]);

$insurance = $stmt->fetch();


if (!$insurance) {

    header("Location: index.php");
    exit;
}


/*
|--------------------------------------------------------------------------
| Process Deletion
|--------------------------------------------------------------------------
*/

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    requireValidCSRF();

    try {

        $pdo->beginTransaction();


        /*
         * Re-check that the record still exists.
         *
         * This prevents attempting to delete a record
         * that may have already been removed.
         */

        $verifyStmt = $pdo->prepare("
            SELECT
                InsuranceID,
                PolicyNumber,
                ProviderName,
                PlateNumber
            FROM insurance i

            INNER JOIN vehicles v
                ON i.VehicleID = v.VehicleID

            WHERE i.InsuranceID = ?

            LIMIT 1
        ");

        $verifyStmt->execute([
            $insuranceID
        ]);

        $currentInsurance =
            $verifyStmt->fetch();


        if (!$currentInsurance) {

            throw new Exception(
                "Insurance record no longer exists."
            );
        }


        /*
         * Delete insurance record.
         */

        $deleteStmt = $pdo->prepare("
            DELETE FROM insurance
            WHERE InsuranceID = ?
        ");

        $deleteStmt->execute([
            $insuranceID
        ]);


        /*
         * Confirm that the deletion actually occurred.
         */

        if ($deleteStmt->rowCount() !== 1) {

            throw new Exception(
                "The insurance record could not be deleted."
            );
        }


        /*
         * Create audit log.
         *
         * The audit record is created inside the
         * same transaction so the deletion and its
         * audit trail remain consistent.
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

            "Deleted insurance policy " .
            $currentInsurance["PolicyNumber"] .
            " for vehicle " .
            $currentInsurance["PlateNumber"],

            "insurance",

            $insuranceID,

            $_SERVER["REMOTE_ADDR"] ?? null

        ]);


        $pdo->commit();


        /*
         * Return to insurance list.
         */

        header(
            "Location: index.php?deleted=1"
        );

        exit;


    } catch (Exception $e) {

        if ($pdo->inTransaction()) {

            $pdo->rollBack();
        }

        $error =
            "Unable to delete the insurance record.";
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

    <style>

        .delete-warning {
            background: #fff7ed;
            color: #9a3412;
            padding: 14px;
            border-radius: 8px;
            margin-top: 20px;
            border: 1px solid #fed7aa;
        }

        .delete-danger-button {
            background: #991b1b !important;
            color: #ffffff !important;
        }

        .delete-danger-button:hover {
            background: #7f1d1d !important;
        }

        .record-summary {
            margin-top: 20px;
        }

        .record-summary p {
            margin-bottom: 10px;
        }

        .delete-actions {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            margin-top: 25px;
        }

    </style>

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
                        Delete Insurance Record
                    </h1>

                    <p class="page-subtitle">
                        Confirm removal of this insurance record.
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
                            Unable to Delete Insurance
                        </strong>

                        <div style="margin-top:5px;">

                            <?= htmlspecialchars($error) ?>

                        </div>

                    </div>

                <?php endif; ?>


                <h2>
                    Are you sure you want to delete this record?
                </h2>


                <div class="record-summary">

                    <p>

                        <strong>
                            Policy Number:
                        </strong>

                        <?= htmlspecialchars(
                            $insurance["PolicyNumber"]
                        ) ?>

                    </p>


                    <p>

                        <strong>
                            Provider:
                        </strong>

                        <?= htmlspecialchars(
                            $insurance["ProviderName"]
                        ) ?>

                    </p>


                    <p>

                        <strong>
                            Vehicle:
                        </strong>

                        <?= htmlspecialchars(
                            $insurance["PlateNumber"]
                        ) ?>

                        -

                        <?= htmlspecialchars(
                            $insurance["Make"]
                        ) ?>

                        <?= htmlspecialchars(
                            $insurance["Model"]
                        ) ?>

                        (<?= htmlspecialchars(
                            $insurance["VehicleYear"]
                        ) ?>)

                    </p>


                    <p>

                        <strong>
                            Coverage Type:
                        </strong>

                        <?= $insurance["CoverageType"]
                            ? htmlspecialchars(
                                $insurance["CoverageType"]
                            )
                            : "Not specified"
                        ?>

                    </p>


                    <p>

                        <strong>
                            Start Date:
                        </strong>

                        <?= $insurance["StartDate"]
                            ? htmlspecialchars(
                                $insurance["StartDate"]
                            )
                            : "Not specified"
                        ?>

                    </p>


                    <p>

                        <strong>
                            Expiry Date:
                        </strong>

                        <?= $insurance["ExpiryDate"]
                            ? htmlspecialchars(
                                $insurance["ExpiryDate"]
                            )
                            : "Not specified"
                        ?>

                    </p>


                    <p>

                        <strong>
                            Status:
                        </strong>

                        <?= htmlspecialchars(
                            $insurance["Status"]
                        ) ?>

                    </p>

                </div>


                <div class="delete-warning">

                    <strong>
                        Important:
                    </strong>

                    This action cannot be undone.
                    Deleting this insurance record will permanently
                    remove it from the system.

                </div>


                <form
                    method="POST"
                    class="delete-actions"
                >

                    <input
                        type="hidden"
                        name="InsuranceID"
                        value="<?= $insuranceID ?>"
                    >


                    <input
                        type="hidden"
                        name="csrf_token"
                        value="<?= htmlspecialchars(
                            generateCSRFToken()
                        ) ?>"
                    >


                    <button
                        type="submit"
                        class="button delete-danger-button"
                    >
                        Yes, Delete Record
                    </button>


                    <a
                        href="view.php?id=<?= $insuranceID ?>"
                        class="button button-secondary"
                    >
                        Cancel
                    </a>

                </form>

            </div>

        </div>


        <?php require_once __DIR__ . "/../includes/footer.php"; ?>

    </main>

</div>

</body>

</html>