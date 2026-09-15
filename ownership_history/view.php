<?php

require_once __DIR__ . "/../includes/auth.php";
require_once __DIR__ . "/../includes/database.php";

$activePage = "ownership_history";

$canManage =
    isset($_SESSION["Role"]) &&
    in_array(
        $_SESSION["Role"],
        ["Admin", "Police"],
        true
    );


/*
|--------------------------------------------------------------------------
| Validate Ownership Record ID
|--------------------------------------------------------------------------
*/

if (
    !isset($_GET["id"]) ||
    !is_numeric($_GET["id"]) ||
    (int) $_GET["id"] <= 0
) {

    header("Location: index.php");
    exit;
}

$ownershipID = (int) $_GET["id"];


/*
|--------------------------------------------------------------------------
| Load Ownership Record
|--------------------------------------------------------------------------
*/

$sql = "
    SELECT
        oh.OwnershipID,
        oh.StartDate,
        oh.EndDate,
        oh.TransferReason,
        oh.CreatedAt,

        v.VehicleID,
        v.PlateNumber,
        v.VIN,
        v.Make,
        v.Model,
        v.VehicleYear,
        v.Color,
        v.VehicleType,

        o.OwnerID,
        o.FirstName,
        o.LastName,
        o.Address,
        o.Phone,
        o.Email

    FROM ownership_history oh

    INNER JOIN vehicles v
        ON oh.VehicleID = v.VehicleID

    INNER JOIN owners o
        ON oh.OwnerID = o.OwnerID

    WHERE oh.OwnershipID = :ownershipID

    LIMIT 1
";

$stmt = $pdo->prepare($sql);

$stmt->execute([
    "ownershipID" => $ownershipID
]);

$record = $stmt->fetch();


/*
|--------------------------------------------------------------------------
| Record Not Found
|--------------------------------------------------------------------------
*/

if (!$record) {

    http_response_code(404);

    $pageTitle = "Ownership Record Not Found";
    $pageSubtitle = "The requested ownership record could not be found.";

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
            Ownership Record Not Found - VISRS
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

                <div
                    class="card"
                    style="
                        max-width:700px;
                        margin:60px auto;
                    "
                >

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
                            Ownership Record Not Found
                        </strong>
                    </div>

                    <h2>
                        The requested ownership record could not be found.
                    </h2>

                    <p
                        style="
                            margin-top:10px;
                            color:#6b7280;
                        "
                    >
                        The record may have been deleted or the
                        requested ID may be invalid.
                    </p>

                    <div style="margin-top:20px;">

                        <a
                            href="index.php"
                            class="button"
                        >
                            Back to Ownership History
                        </a>

                    </div>

                </div>

            </div>

            <?php require_once __DIR__ . "/../includes/footer.php"; ?>

        </main>

    </div>

    </body>

    </html>

    <?php

    exit;
}


$isCurrentOwner =
    empty($record["EndDate"]);

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
        Ownership Record - VISRS
    </title>

    <link
        rel="stylesheet"
        href="/visrs/css/style.css"
    >

    <style>

        .detail-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 20px;
        }

        .detail-item {
            padding: 15px;
            background: #f8fafc;
            border-radius: 8px;
            border: 1px solid #e2e8f0;
        }

        .detail-label {
            display: block;
            font-size: 13px;
            font-weight: 600;
            color: #64748b;
            margin-bottom: 6px;
        }

        .detail-value {
            font-size: 16px;
            font-weight: 500;
            color: #1e293b;
            word-break: break-word;
        }

        .status-badge {
            display: inline-block;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 13px;
            font-weight: 600;
        }

        .status-current {
            background: #dcfce7;
            color: #166534;
        }

        .status-former {
            background: #e2e8f0;
            color: #475569;
        }

        .section-title {
            margin-bottom: 18px;
            font-size: 20px;
            color: #1e293b;
        }

        .action-row {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            margin-top: 20px;
        }

        .danger-button {
            background: #991b1b !important;
            color: #ffffff !important;
        }

        .danger-button:hover {
            background: #7f1d1d !important;
        }

        @media (max-width: 768px) {

            .detail-grid {
                grid-template-columns: 1fr;
            }

        }

    </style>

</head>

<body>

<div class="layout">

    <?php require_once __DIR__ . "/../includes/sidebar.php"; ?>


    <main class="main-content">

        <?php

        $pageTitle = "Ownership History";
        $pageSubtitle =
            "Detailed information about this ownership period.";

        include __DIR__ . "/../includes/header.php";

        ?>


        <div class="content">

            <div class="page-title">

                <div>

                    <h1>
                        Ownership Record #<?= (int) $record["OwnershipID"] ?>
                    </h1>

                    <p class="page-subtitle">
                        Detailed information about this ownership period.
                    </p>

                </div>

            </div>


            <!-- OWNERSHIP STATUS -->

            <div class="card">

                <h3 class="section-title">
                    Ownership Status
                </h3>

                <?php if ($isCurrentOwner): ?>

                    <span
                        class="status-badge status-current"
                    >
                        Current Owner
                    </span>

                <?php else: ?>

                    <span
                        class="status-badge status-former"
                    >
                        Former Owner
                    </span>

                <?php endif; ?>

            </div>


            <!-- VEHICLE INFORMATION -->

            <div class="card">

                <h3 class="section-title">
                    Vehicle Information
                </h3>

                <div class="detail-grid">

                    <div class="detail-item">

                        <span class="detail-label">
                            License Plate
                        </span>

                        <span class="detail-value">
                            <?= htmlspecialchars(
                                $record["PlateNumber"]
                            ) ?>
                        </span>

                    </div>


                    <div class="detail-item">

                        <span class="detail-label">
                            VIN
                        </span>

                        <span class="detail-value">
                            <?= htmlspecialchars(
                                $record["VIN"]
                            ) ?>
                        </span>

                    </div>


                    <div class="detail-item">

                        <span class="detail-label">
                            Make
                        </span>

                        <span class="detail-value">
                            <?= htmlspecialchars(
                                $record["Make"]
                            ) ?>
                        </span>

                    </div>


                    <div class="detail-item">

                        <span class="detail-label">
                            Model
                        </span>

                        <span class="detail-value">
                            <?= htmlspecialchars(
                                $record["Model"]
                            ) ?>
                        </span>

                    </div>


                    <div class="detail-item">

                        <span class="detail-label">
                            Year
                        </span>

                        <span class="detail-value">
                            <?= htmlspecialchars(
                                $record["VehicleYear"]
                            ) ?>
                        </span>

                    </div>


                    <div class="detail-item">

                        <span class="detail-label">
                            Color
                        </span>

                        <span class="detail-value">

                            <?= htmlspecialchars(
                                $record["Color"] ?: "Not provided"
                            ) ?>

                        </span>

                    </div>


                    <div class="detail-item">

                        <span class="detail-label">
                            Vehicle Type
                        </span>

                        <span class="detail-value">

                            <?= htmlspecialchars(
                                $record["VehicleType"]
                                ?: "Not provided"
                            ) ?>

                        </span>

                    </div>

                </div>


                <div class="action-row">

                    <a
                        href="/visrs/vehicles/view.php?id=<?= (int) $record["VehicleID"] ?>"
                        class="button"
                    >
                        View Vehicle
                    </a>

                </div>

            </div>


            <!-- OWNER INFORMATION -->

            <div class="card">

                <h3 class="section-title">
                    Owner Information
                </h3>

                <div class="detail-grid">

                    <div class="detail-item">

                        <span class="detail-label">
                            Full Name
                        </span>

                        <span class="detail-value">

                            <?= htmlspecialchars(
                                $record["FirstName"] .
                                " " .
                                $record["LastName"]
                            ) ?>

                        </span>

                    </div>


                    <div class="detail-item">

                        <span class="detail-label">
                            Email
                        </span>

                        <span class="detail-value">

                            <?= htmlspecialchars(
                                $record["Email"]
                                ?: "Not provided"
                            ) ?>

                        </span>

                    </div>


                    <div class="detail-item">

                        <span class="detail-label">
                            Phone
                        </span>

                        <span class="detail-value">

                            <?= htmlspecialchars(
                                $record["Phone"]
                                ?: "Not provided"
                            ) ?>

                        </span>

                    </div>


                    <div class="detail-item">

                        <span class="detail-label">
                            Address
                        </span>

                        <span class="detail-value">

                            <?= htmlspecialchars(
                                $record["Address"]
                                ?: "Not provided"
                            ) ?>

                        </span>

                    </div>

                </div>


                <div class="action-row">

                    <a
                        href="/visrs/owners/view.php?id=<?= (int) $record["OwnerID"] ?>"
                        class="button button-secondary"
                    >
                        View Owner
                    </a>

                </div>

            </div>


            <!-- OWNERSHIP PERIOD -->

            <div class="card">

                <h3 class="section-title">
                    Ownership Period
                </h3>

                <div class="detail-grid">

                    <div class="detail-item">

                        <span class="detail-label">
                            Start Date
                        </span>

                        <span class="detail-value">
                            <?= htmlspecialchars(
                                $record["StartDate"]
                            ) ?>
                        </span>

                    </div>


                    <div class="detail-item">

                        <span class="detail-label">
                            End Date
                        </span>

                        <span class="detail-value">

                            <?php if (
                                !empty($record["EndDate"])
                            ): ?>

                                <?= htmlspecialchars(
                                    $record["EndDate"]
                                ) ?>

                            <?php else: ?>

                                Current Ownership

                            <?php endif; ?>

                        </span>

                    </div>


                    <div class="detail-item">

                        <span class="detail-label">
                            Transfer Reason
                        </span>

                        <span class="detail-value">

                            <?= htmlspecialchars(
                                $record["TransferReason"]
                                ?: "Not provided"
                            ) ?>

                        </span>

                    </div>


                    <div class="detail-item">

                        <span class="detail-label">
                            Record Created
                        </span>

                        <span class="detail-value">

                            <?= htmlspecialchars(
                                $record["CreatedAt"]
                            ) ?>

                        </span>

                    </div>

                </div>

            </div>


            <!-- NAVIGATION / ACTIONS -->

            <div class="action-row">

                <a
                    href="index.php"
                    class="button button-secondary"
                >
                    ← Back to Ownership History
                </a>


                <?php if ($canManage): ?>

                    <a
                        href="edit.php?id=<?= (int) $record["OwnershipID"] ?>"
                        class="button"
                    >
                        Edit Ownership Record
                    </a>


                    <a
                        href="delete.php?id=<?= (int) $record["OwnershipID"] ?>"
                        class="button danger-button"
                    >
                        Delete Ownership Record
                    </a>

                <?php endif; ?>

            </div>

        </div>


        <?php require_once __DIR__ . "/../includes/footer.php"; ?>

    </main>

</div>

</body>

</html>