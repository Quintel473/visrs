<?php

require_once "../includes/auth.php";
require_once "../includes/database.php";

$basePath = "../";
$activePage = "ownership_history";

if (!isset($_GET["id"]) || !is_numeric($_GET["id"])) {
    header("Location: index.php");
    exit;
}

$ownershipID = (int) $_GET["id"];

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
";

$stmt = $pdo->prepare($sql);
$stmt->execute([
    "ownershipID" => $ownershipID
]);

$record = $stmt->fetch();

if (!$record) {
    http_response_code(404);
    die("Ownership history record not found.");
}

$isCurrentOwner = empty($record["EndDate"]);

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Ownership Record - VISRS</title>

    <link rel="stylesheet" href="../css/style.css">

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

        @media (max-width: 768px) {

            .detail-grid {
                grid-template-columns: 1fr;
            }

        }

    </style>

</head>

<body>

<div class="layout">

    <?php require_once "../includes/sidebar.php"; ?>

    <main class="main-content">

        <header class="topbar">

            <div>
                <h1>Ownership History Record</h1>
                <p>View detailed ownership information</p>
            </div>

            <div class="user-info">

                <div class="user-avatar">
                    <?= strtoupper(substr($_SESSION["FirstName"], 0, 1)) ?>
                </div>

                <div>
                    <strong>
                        <?= htmlspecialchars($_SESSION["FirstName"] . " " . $_SESSION["LastName"]) ?>
                    </strong>

                </div>

            </div>

        </header>

        <section class="content">

            <div class="page-title">

                <div>

                    <h2>
                        Ownership Record #<?= htmlspecialchars($record["OwnershipID"]) ?>
                    </h2>

                    <p class="page-subtitle">
                        Detailed information about this ownership period
                    </p>

                </div>

            </div>


            <!-- Ownership Status -->

            <div class="card">

                <h3 class="section-title">
                    Ownership Status
                </h3>

                <?php if ($isCurrentOwner): ?>

                    <span class="status-badge status-current">
                        Current Owner
                    </span>

                <?php else: ?>

                    <span class="status-badge status-former">
                        Former Owner
                    </span>

                <?php endif; ?>

            </div>


            <!-- Vehicle Information -->

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
                            <?= htmlspecialchars($record["PlateNumber"]) ?>
                        </span>

                    </div>


                    <div class="detail-item">

                        <span class="detail-label">
                            VIN
                        </span>

                        <span class="detail-value">
                            <?= htmlspecialchars($record["VIN"]) ?>
                        </span>

                    </div>


                    <div class="detail-item">

                        <span class="detail-label">
                            Make
                        </span>

                        <span class="detail-value">
                            <?= htmlspecialchars($record["Make"]) ?>
                        </span>

                    </div>


                    <div class="detail-item">

                        <span class="detail-label">
                            Model
                        </span>

                        <span class="detail-value">
                            <?= htmlspecialchars($record["Model"]) ?>
                        </span>

                    </div>


                    <div class="detail-item">

                        <span class="detail-label">
                            Year
                        </span>

                        <span class="detail-value">
                            <?= htmlspecialchars($record["VehicleYear"]) ?>
                        </span>

                    </div>


                    <div class="detail-item">

                        <span class="detail-label">
                            Color
                        </span>

                        <span class="detail-value">
                            <?= htmlspecialchars($record["Color"] ?: "Not provided") ?>
                        </span>

                    </div>


                    <div class="detail-item">

                        <span class="detail-label">
                            Vehicle Type
                        </span>

                        <span class="detail-value">
                            <?= htmlspecialchars($record["VehicleType"] ?: "Not provided") ?>
                        </span>

                    </div>

                </div>

                <div class="action-row">

                    <a
                        href="<?= $basePath ?>vehicles/view.php?id=<?= $record["VehicleID"] ?>"
                        class="button"
                    >
                        View Vehicle
                    </a>

                </div>

            </div>


            <!-- Owner Information -->

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
                            <?= htmlspecialchars($record["FirstName"] . " " . $record["LastName"]) ?>
                        </span>

                    </div>


                    <div class="detail-item">

                        <span class="detail-label">
                            Email
                        </span>

                        <span class="detail-value">
                            <?= htmlspecialchars($record["Email"] ?: "Not provided") ?>
                        </span>

                    </div>


                    <div class="detail-item">

                        <span class="detail-label">
                            Phone
                        </span>

                        <span class="detail-value">
                            <?= htmlspecialchars($record["Phone"] ?: "Not provided") ?>
                        </span>

                    </div>


                    <div class="detail-item">

                        <span class="detail-label">
                            Address
                        </span>

                        <span class="detail-value">
                            <?= htmlspecialchars($record["Address"] ?: "Not provided") ?>
                        </span>

                    </div>

                </div>

                <div class="action-row">

                    <a
                        href="<?= $basePath ?>owners/view.php?id=<?= $record["OwnerID"] ?>"
                        class="button button-secondary"
                    >
                        View Owner
                    </a>

                </div>

            </div>


            <!-- Ownership Period -->

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
                            <?= htmlspecialchars($record["StartDate"]) ?>
                        </span>

                    </div>


                    <div class="detail-item">

                        <span class="detail-label">
                            End Date
                        </span>

                        <span class="detail-value">

                            <?php if ($record["EndDate"]): ?>

                                <?= htmlspecialchars($record["EndDate"]) ?>

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
                                $record["TransferReason"] ?: "Not provided"
                            ) ?>

                        </span>

                    </div>


                    <div class="detail-item">

                        <span class="detail-label">
                            Record Created
                        </span>

                        <span class="detail-value">
                            <?= htmlspecialchars($record["CreatedAt"]) ?>
                        </span>

                    </div>

                </div>

            </div>


            <!-- Navigation -->

            <div class="action-row">

                <a
                    href="index.php"
                    class="button button-secondary"
                >
                    ← Back to Ownership History
                </a>


                <a
                    href="delete.php?id=<?= $record["OwnershipID"] ?>"
                    class="button"
                    style="background: #991b1b;"
                >
                    Delete Ownership Record
                </a>

                <a
                    href="edit.php?id=<?= $record["OwnershipID"] ?>"
                    class="button"
                >
                    Edit Ownership Record
                </a>

            </div>

        </section>

    </main>

</div>

</body>

</html>