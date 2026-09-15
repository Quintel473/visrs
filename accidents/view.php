<?php

require_once "../includes/auth.php";
require_once "../includes/database.php";

$basePath = "../";
$activePage = "accidents";

$pageTitle = "Accident Details";
$pageSubtitle = "View complete information about this accident record.";

$accidentID = (int) ($_GET["id"] ?? 0);

if ($accidentID <= 0) {

    header("Location: index.php");
    exit;
}


/*
 * Load accident record and related vehicle information.
 */

$stmt = $pdo->prepare("
    SELECT
        a.AccidentID,
        a.VehicleID,
        a.AccidentDate,
        a.Location,
        a.Description,
        a.DamageLevel,
        a.ReportNumber,
        a.CreatedAt,

        v.PlateNumber,
        v.VIN,
        v.Make,
        v.Model,
        v.VehicleYear,
        v.Color,
        v.VehicleType,
        v.EngineNumber,
        v.RegistrationDate,
        v.Status AS VehicleStatus

    FROM accidents a

    INNER JOIN vehicles v
        ON a.VehicleID = v.VehicleID

    WHERE a.AccidentID = ?
");

$stmt->execute([
    $accidentID
]);

$accident = $stmt->fetch();


if (!$accident) {

    http_response_code(404);

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
            Accident Not Found - VISRS
        </title>

        <link
            rel="stylesheet"
            href="../css/style.css"
        >

    </head>

    <body>

    <div class="layout">

        <main
            class="main-content"
            style="margin-left:0;width:100%;"
        >

            <div
                class="content"
                style="
                    max-width:700px;
                    margin:80px auto;
                "
            >

                <div class="card">

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
                            Accident Record Not Found
                        </strong>

                    </div>

                    <h2>
                        The requested accident record could not be found.
                    </h2>

                    <p>
                        The record may have been deleted or
                        the requested accident ID is invalid.
                    </p>

                    <a
                        href="index.php"
                        class="button"
                    >
                        Return to Accident Records
                    </a>

                </div>

            </div>

        </main>

    </div>

    </body>
    </html>

    <?php

    exit;
}


/*
 * Format the accident date.
 */

$formattedAccidentDate = date(
    "F j, Y",
    strtotime($accident["AccidentDate"])
);


/*
 * Format the creation date.
 */

$formattedCreatedAt = date(
    "F j, Y g:i A",
    strtotime($accident["CreatedAt"])
);


/*
 * Determine whether the current user
 * can manage accident records.
 */

$canManageAccidents = in_array(
    $_SESSION["Role"] ?? "",
    ["Admin", "Police"],
    true
);


/*
 * Determine damage badge class.
 */

$damageClass = match (
    $accident["DamageLevel"]
) {

    "Minor" =>
        "damage-minor",

    "Moderate" =>
        "damage-moderate",

    "Major" =>
        "damage-major",

    "Severe" =>
        "damage-severe",

    default =>
        ""

};

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
        Accident Details - VISRS
    </title>

    <link
        rel="stylesheet"
        href="../css/style.css"
    >

    <style>

        .detail-section {
            margin-top: 28px;
        }

        .detail-section h3 {
            margin-bottom: 16px;
        }

        .detail-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 16px;
        }

        .detail-box {
            padding: 16px;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            background: #ffffff;
        }

        .detail-label {
            display: block;
            margin-bottom: 6px;
            color: #6b7280;
            font-size: 12px;
            font-weight: 500;
        }

        .detail-value {
            color: #1f2937;
            font-size: 14px;
            font-weight: 600;
        }

        .description-box {
            padding: 18px;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            background: #f8fafc;
            color: #374151;
            line-height: 1.6;
            white-space: pre-wrap;
        }

        .damage-badge {
            display: inline-block;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }

        .damage-minor {
            background: #dcfce7;
            color: #166534;
        }

        .damage-moderate {
            background: #fef3c7;
            color: #92400e;
        }

        .damage-major {
            background: #ffedd5;
            color: #9a3412;
        }

        .damage-severe {
            background: #fee2e2;
            color: #991b1b;
        }

        .vehicle-summary {
            padding: 18px;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            background: #f8fafc;
        }

        .vehicle-summary h3 {
            margin-top: 0;
            margin-bottom: 12px;
        }

        .vehicle-summary p {
            margin: 7px 0;
        }

        .page-actions {
            display: flex;
            gap: 10px;
            margin-top: 30px;
            flex-wrap: wrap;
        }

        .delete-button {
            background: #991b1b;
        }

        .delete-button:hover {
            background: #7f1d1d;
        }

        @media (max-width: 700px) {

            .detail-grid {
                grid-template-columns: 1fr;
            }

            .page-actions {
                flex-direction: column;
            }

            .page-actions .button {
                width: 100%;
                text-align: center;
                box-sizing: border-box;
            }

        }

    </style>

</head>

<body>

<div class="layout">

    <?php require_once "../includes/sidebar.php"; ?>

    <main class="main-content">

        <?php include __DIR__ . "/../includes/header.php"; ?>

        <div class="content">

            <div class="card">

                <!-- ACCIDENT SUMMARY -->

                <h2>
                    Accident Record #<?= (int) $accident["AccidentID"] ?>
                </h2>


                <div class="detail-grid">

                    <div class="detail-box">

                        <span class="detail-label">
                            Accident Date
                        </span>

                        <span class="detail-value">

                            <?= htmlspecialchars(
                                $formattedAccidentDate
                            ) ?>

                        </span>

                    </div>


                    <div class="detail-box">

                        <span class="detail-label">
                            Damage Level
                        </span>

                        <span
                            class="damage-badge <?= htmlspecialchars(
                                $damageClass
                            ) ?>"
                        >

                            <?= htmlspecialchars(
                                $accident["DamageLevel"]
                            ) ?>

                        </span>

                    </div>


                    <div class="detail-box">

                        <span class="detail-label">
                            Location
                        </span>

                        <span class="detail-value">

                            <?= $accident["Location"]
                                ? htmlspecialchars(
                                    $accident["Location"]
                                )
                                : "Not specified"
                            ?>

                        </span>

                    </div>


                    <div class="detail-box">

                        <span class="detail-label">
                            Report Number
                        </span>

                        <span class="detail-value">

                            <?= $accident["ReportNumber"]
                                ? htmlspecialchars(
                                    $accident["ReportNumber"]
                                )
                                : "Not specified"
                            ?>

                        </span>

                    </div>

                </div>


                <!-- DESCRIPTION -->

                <div class="detail-section">

                    <h3>
                        Accident Description
                    </h3>

                    <div class="description-box">

                        <?= $accident["Description"]
                            ? htmlspecialchars(
                                $accident["Description"]
                            )
                            : "No description was provided for this accident."
                        ?>

                    </div>

                </div>


                <!-- VEHICLE INFORMATION -->

                <div class="detail-section">

                    <h3>
                        Vehicle Information
                    </h3>

                    <div class="vehicle-summary">

                        <h3>

                            <?= htmlspecialchars(
                                $accident["Make"]
                            ) ?>

                            <?= htmlspecialchars(
                                $accident["Model"]
                            ) ?>

                            (<?= htmlspecialchars(
                                $accident["VehicleYear"]
                            ) ?>)

                        </h3>


                        <p>

                            <strong>
                                Plate Number:
                            </strong>

                            <?= htmlspecialchars(
                                $accident["PlateNumber"]
                            ) ?>

                        </p>


                        <p>

                            <strong>
                                VIN:
                            </strong>

                            <?= htmlspecialchars(
                                $accident["VIN"]
                            ) ?>

                        </p>


                        <p>

                            <strong>
                                Color:
                            </strong>

                            <?= $accident["Color"]
                                ? htmlspecialchars(
                                    $accident["Color"]
                                )
                                : "Not specified"
                            ?>

                        </p>


                        <p>

                            <strong>
                                Vehicle Type:
                            </strong>

                            <?= $accident["VehicleType"]
                                ? htmlspecialchars(
                                    $accident["VehicleType"]
                                )
                                : "Not specified"
                            ?>

                        </p>


                        <p>

                            <strong>
                                Vehicle Status:
                            </strong>

                            <?= htmlspecialchars(
                                $accident["VehicleStatus"]
                            ) ?>

                        </p>

                    </div>

                </div>


                <!-- RECORD INFORMATION -->

                <div class="detail-section">

                    <h3>
                        Record Information
                    </h3>


                    <div class="detail-grid">

                        <div class="detail-box">

                            <span class="detail-label">
                                Accident ID
                            </span>

                            <span class="detail-value">

                                <?= (int) $accident["AccidentID"] ?>

                            </span>

                        </div>


                        <div class="detail-box">

                            <span class="detail-label">
                                Vehicle ID
                            </span>

                            <span class="detail-value">

                                <?= (int) $accident["VehicleID"] ?>

                            </span>

                        </div>


                        <div class="detail-box">

                            <span class="detail-label">
                                Record Created
                            </span>

                            <span class="detail-value">

                                <?= htmlspecialchars(
                                    $formattedCreatedAt
                                ) ?>

                            </span>

                        </div>

                    </div>

                </div>


                <!-- ACTIONS -->

                <div class="page-actions">

                    <?php if ($canManageAccidents): ?>

                        <a
                            href="edit.php?id=<?= (int) $accident["AccidentID"] ?>"
                            class="button"
                        >
                            Edit Accident
                        </a>


                        <a
                            href="delete.php?id=<?= (int) $accident["AccidentID"] ?>"
                            class="button delete-button"
                        >
                            Delete Accident
                        </a>

                    <?php endif; ?>


                    <a
                        href="index.php"
                        class="button button-secondary"
                    >
                        Back to Accident Records
                    </a>

                </div>

            </div>

        </div>

    </main>

</div>

<?php require_once "../includes/footer.php"; ?>