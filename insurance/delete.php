<?php

require_once "../includes/auth.php";
require_once "../includes/database.php";

requireRole(["Admin", "Police"]);

$basePath = "../";
$activePage = "insurance";

$error = "";

$insuranceID = (int) ($_GET["id"] ?? 0);

if ($insuranceID <= 0) {

    header("Location: index.php");
    exit;

}


/*
 * Load the insurance record.
 */

$stmt = $pdo->prepare("
    SELECT
        i.InsuranceID,
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
 * Process deletion.
 */

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    try {

        $pdo->beginTransaction();


        /*
         * Delete the insurance record.
         */

        $deleteStmt = $pdo->prepare("
            DELETE FROM insurance
            WHERE InsuranceID = ?
        ");

        $deleteStmt->execute([
            $insuranceID
        ]);


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

            "Deleted insurance policy " .
            $insurance["PolicyNumber"] .
            " for vehicle " .
            $insurance["PlateNumber"],

            "insurance",

            $insuranceID,

            $_SERVER["REMOTE_ADDR"] ?? null

        ]);


        $pdo->commit();


        header("Location: index.php");
        exit;


    } catch (Exception $e) {

        if ($pdo->inTransaction()) {

            $pdo->rollBack();

        }

        $error =
            "Unable to delete the insurance record.";
    }
}


/*
 * Page header information.
 */

$pageTitle = "Delete Insurance Record";
$pageSubtitle = "Confirm removal of this insurance record.";

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
        href="../css/style.css"
    >

</head>

<body>

<div class="layout">

    <?php require_once "../includes/sidebar.php"; ?>


    <main class="main-content">

        <?php require_once "../includes/header.php"; ?>


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

                        <strong>Policy Number:</strong>

                        <?= htmlspecialchars(
                            $insurance["PolicyNumber"]
                        ) ?>

                    </p>


                    <p>

                        <strong>Provider:</strong>

                        <?= htmlspecialchars(
                            $insurance["ProviderName"]
                        ) ?>

                    </p>


                    <p>

                        <strong>Vehicle:</strong>

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

                    </p>


                    <p>

                        <strong>Coverage Type:</strong>

                        <?= $insurance["CoverageType"]
                            ? htmlspecialchars(
                                $insurance["CoverageType"]
                            )
                            : "Not specified"
                        ?>

                    </p>


                    <p>

                        <strong>Start Date:</strong>

                        <?= $insurance["StartDate"]
                            ? htmlspecialchars(
                                $insurance["StartDate"]
                            )
                            : "Not specified"
                        ?>

                    </p>


                    <p>

                        <strong>Expiry Date:</strong>

                        <?= $insurance["ExpiryDate"]
                            ? htmlspecialchars(
                                $insurance["ExpiryDate"]
                            )
                            : "Not specified"
                        ?>

                    </p>


                    <p>

                        <strong>Status:</strong>

                        <?= htmlspecialchars(
                            $insurance["Status"]
                        ) ?>

                    </p>

                </div>


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

                    This action cannot be undone.
                    Deleting this insurance record will permanently
                    remove it from the system.

                </div>


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
                        name="InsuranceID"
                        value="<?= htmlspecialchars($insuranceID) ?>"
                    >


                    <button
                        type="submit"
                        class="button"
                        style="background:#991b1b;"
                    >
                        Yes, Delete Record
                    </button>


                    <a
                        href="view.php?id=<?= htmlspecialchars($insuranceID) ?>"
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