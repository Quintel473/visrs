<?php

require_once "../includes/auth.php";
require_once "../includes/database.php";

$basePath = "../";
$activePage = "accidents";

requireRole(["Admin", "Police"]);

$pageTitle = "Add Accident Record";
$pageSubtitle = "Record a new vehicle accident in the system.";

$error = "";

$vehicleID = "";
$accidentDate = "";
$location = "";
$description = "";
$damageLevel = "Minor";
$reportNumber = "";


if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $vehicleID = (int) ($_POST["VehicleID"] ?? 0);

    $accidentDate = trim(
        $_POST["AccidentDate"] ?? ""
    );

    $location = trim(
        $_POST["Location"] ?? ""
    );

    $description = trim(
        $_POST["Description"] ?? ""
    );

    $damageLevel = trim(
        $_POST["DamageLevel"] ?? "Minor"
    );

    $reportNumber = trim(
        $_POST["ReportNumber"] ?? ""
    );


    /*
     * Validate vehicle.
     */

    if ($vehicleID <= 0) {

        $error = "Please select a vehicle.";

    } elseif ($accidentDate === "") {

        $error = "Accident date is required.";

    } else {

        /*
         * Validate accident date.
         */

        $dateObject = DateTime::createFromFormat(
            "Y-m-d",
            $accidentDate
        );

        $dateErrors = DateTime::getLastErrors();

        if (
            !$dateObject ||
            (
                $dateErrors !== false &&
                (
                    $dateErrors["warning_count"] > 0 ||
                    $dateErrors["error_count"] > 0
                )
            ) ||
            $dateObject->format("Y-m-d") !== $accidentDate
        ) {

            $error =
                "Please enter a valid accident date.";
        }
    }


    /*
     * Validate damage level.
     */

    if ($error === "") {

        $allowedDamageLevels = [
            "Minor",
            "Moderate",
            "Major",
            "Severe"
        ];

        if (
            !in_array(
                $damageLevel,
                $allowedDamageLevels,
                true
            )
        ) {

            $error =
                "Invalid damage level.";
        }
    }


    /*
     * Confirm selected vehicle exists.
     */

    if ($error === "") {

        $vehicleStmt = $pdo->prepare("
            SELECT
                VehicleID,
                PlateNumber,
                VIN,
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
     * Insert accident record.
     */

    if ($error === "") {

        try {

            $pdo->beginTransaction();


            $insertStmt = $pdo->prepare("
                INSERT INTO accidents
                (
                    VehicleID,
                    AccidentDate,
                    Location,
                    Description,
                    DamageLevel,
                    ReportNumber
                )
                VALUES (?, ?, ?, ?, ?, ?)
            ");


            $insertStmt->execute([
                $vehicleID,
                $accidentDate,
                $location !== ""
                    ? $location
                    : null,
                $description !== ""
                    ? $description
                    : null,
                $damageLevel,
                $reportNumber !== ""
                    ? $reportNumber
                    : null
            ]);


            $newAccidentID =
                $pdo->lastInsertId();


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
                "Added accident record for vehicle " .
                $vehicle["PlateNumber"],
                "accidents",
                $newAccidentID,
                $_SERVER["REMOTE_ADDR"] ?? null
            ]);


            $pdo->commit();


            header(
                "Location: view.php?id=" .
                $newAccidentID
            );

            exit;

        } catch (Exception $e) {

            if ($pdo->inTransaction()) {

                $pdo->rollBack();
            }

            $error =
                "Unable to add the accident record.";
        }
    }
}


/*
 * Get vehicles for selection.
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
        Add Accident Record - VISRS
    </title>

    <link
        rel="stylesheet"
        href="../css/style.css"
    >

    <style>

        /* =========================================
           ACCIDENT FORM CONTROLS
           ========================================= */

        .select-wrapper {

            position: relative;
            width: 100%;
        }


        .visrs-select {

            width: 100%;
            appearance: none;
            -webkit-appearance: none;

            padding: 13px 45px 13px 15px;

            border: 1px solid #d1d5db;
            border-radius: 8px;

            background: #ffffff;
            color: #1f2937;

            font-size: 14px;
            font-family: inherit;

            cursor: pointer;
            outline: none;

            transition:
                border-color 0.2s ease,
                box-shadow 0.2s ease;
        }


        .visrs-select:hover {

            border-color: #9ca3af;
        }


        .visrs-select:focus {

            border-color: #2563eb;

            box-shadow:
                0 0 0 3px
                rgba(37, 99, 235, 0.10);
        }


        .select-arrow {

            position: absolute;

            right: 15px;
            top: 50%;

            transform: translateY(-50%);

            color: #6b7280;
            font-size: 18px;

            pointer-events: none;
        }


        .form-help {

            display: block;

            margin-top: 7px;

            color: #6b7280;
            font-size: 12px;
        }


        /* Description */

        .textarea-wrapper {

            position: relative;
        }


        .visrs-textarea {

            display: block;

            width: 100%;
            min-height: 140px;

            padding: 14px 15px;

            border: 1px solid #d1d5db;
            border-radius: 8px;

            background: #ffffff;
            color: #1f2937;

            font-family: inherit;
            font-size: 14px;
            line-height: 1.6;

            resize: vertical;
            outline: none;

            box-sizing: border-box;

            transition:
                border-color 0.2s ease,
                box-shadow 0.2s ease;
        }


        .visrs-textarea::placeholder {

            color: #9ca3af;
        }


        .visrs-textarea:hover {

            border-color: #9ca3af;
        }


        .visrs-textarea:focus {

            border-color: #2563eb;

            box-shadow:
                0 0 0 3px
                rgba(37, 99, 235, 0.10);
        }


        .textarea-footer {

            display: flex;

            justify-content: space-between;
            align-items: center;

            margin-top: 7px;

            color: #6b7280;
            font-size: 12px;
        }


        #descriptionCount {

            font-weight: 500;
        }


        /* Damage Level */

        .damage-options {

            display: grid;

            grid-template-columns:
                repeat(2, 1fr);

            gap: 12px;

            margin-top: 8px;
        }


        .damage-option {

            position: relative;

            display: block;

            cursor: pointer;
        }


        .damage-option input {

            position: absolute;

            opacity: 0;
            pointer-events: none;
        }


        .damage-option-content {

            display: flex;

            flex-direction: column;

            gap: 5px;

            min-height: 72px;

            padding: 14px 16px;

            border: 1px solid #d1d5db;
            border-radius: 8px;

            background: #ffffff;

            transition:
                border-color 0.2s ease,
                background 0.2s ease,
                box-shadow 0.2s ease,
                transform 0.2s ease;
        }


        .damage-option-content strong {

            color: #1f2937;
            font-size: 14px;
        }


        .damage-option-content small {

            color: #6b7280;

            font-size: 12px;
            line-height: 1.4;
        }


        .damage-option:hover
        .damage-option-content {

            border-color: #9ca3af;

            transform:
                translateY(-1px);
        }


        .damage-option input:checked
        + .damage-option-content {

            border-color: #2563eb;

            background: #f8fbff;

            box-shadow:
                0 0 0 3px
                rgba(37, 99, 235, 0.08);
        }


        .damage-option input:checked
        + .damage-option-content strong {

            color: #2563eb;
        }


        /* Responsive */

        @media (max-width: 700px) {

            .damage-options {

                grid-template-columns: 1fr;
            }


            .textarea-footer {

                gap: 10px;

                flex-direction: column;

                align-items: flex-start;
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
                    Accident Information
                </h2>


                <form
                    method="POST"
                    style="margin-top:20px;"
                >


                    <!-- VEHICLE -->

                    <div class="form-group">

                        <label for="VehicleID">

                            Vehicle *

                        </label>


                        <div class="select-wrapper">

                            <select
                                name="VehicleID"
                                id="VehicleID"
                                class="visrs-select"
                                required
                            >

                                <option value="">

                                    Select a vehicle

                                </option>


                                <?php foreach (
                                    $vehicles
                                    as $vehicle
                                ): ?>

                                    <option
                                        value="<?= $vehicle["VehicleID"] ?>"
                                        <?= (string) $vehicleID ===
                                            (string) $vehicle["VehicleID"]
                                            ? "selected"
                                            : ""
                                        ?>
                                    >

                                        <?= htmlspecialchars(
                                            $vehicle["PlateNumber"]
                                        ) ?>

                                        —

                                        <?= htmlspecialchars(
                                            $vehicle["Make"]
                                        ) ?>

                                        <?= htmlspecialchars(
                                            $vehicle["Model"]
                                        ) ?>

                                        ·

                                        <?= htmlspecialchars(
                                            $vehicle["VehicleYear"]
                                        ) ?>

                                    </option>

                                <?php endforeach; ?>

                            </select>


                            <span class="select-arrow">

                                ⌄

                            </span>

                        </div>


                        <small class="form-help">

                            Select the vehicle involved
                            in the accident.

                        </small>

                    </div>


                    <!-- ACCIDENT DATE -->

                    <div class="form-group">

                        <label for="AccidentDate">

                            Accident Date *

                        </label>


                        <input
                            type="date"
                            name="AccidentDate"
                            id="AccidentDate"
                            value="<?= htmlspecialchars(
                                $accidentDate
                            ) ?>"
                            required
                        >

                    </div>


                    <!-- LOCATION -->

                    <div class="form-group">

                        <label for="Location">

                            Location

                        </label>


                        <input
                            type="text"
                            name="Location"
                            id="Location"
                            value="<?= htmlspecialchars(
                                $location
                            ) ?>"
                            placeholder="Enter accident location"
                        >

                    </div>


                    <!-- DESCRIPTION -->

                    <div class="form-group">

                        <label for="Description">

                            Accident Description

                        </label>


                        <div class="textarea-wrapper">

                            <textarea
                                name="Description"
                                id="Description"
                                class="visrs-textarea"
                                rows="5"
                                maxlength="2000"
                                placeholder="Describe what happened, including the circumstances of the accident, visible damage, and any other relevant details..."
                            ><?= htmlspecialchars(
                                $description
                            ) ?></textarea>


                            <div class="textarea-footer">

                                <span>

                                    Provide relevant details
                                    about the accident.

                                </span>


                                <span id="descriptionCount">

                                    <?= strlen($description) ?>
                                    / 2000

                                </span>

                            </div>

                        </div>

                    </div>


                    <!-- DAMAGE LEVEL -->

                    <div class="form-group">

                        <label>

                            Damage Level *

                        </label>


                        <div class="damage-options">


                            <label class="damage-option">

                                <input
                                    type="radio"
                                    name="DamageLevel"
                                    value="Minor"
                                    <?= $damageLevel === "Minor"
                                        ? "checked"
                                        : ""
                                    ?>
                                >


                                <span
                                    class="damage-option-content"
                                >

                                    <strong>
                                        Minor
                                    </strong>

                                    <small>
                                        Limited damage with
                                        minor repairs required.
                                    </small>

                                </span>

                            </label>


                            <label class="damage-option">

                                <input
                                    type="radio"
                                    name="DamageLevel"
                                    value="Moderate"
                                    <?= $damageLevel === "Moderate"
                                        ? "checked"
                                        : ""
                                    ?>
                                >


                                <span
                                    class="damage-option-content"
                                >

                                    <strong>
                                        Moderate
                                    </strong>

                                    <small>
                                        Noticeable damage
                                        requiring significant
                                        repairs.
                                    </small>

                                </span>

                            </label>


                            <label class="damage-option">

                                <input
                                    type="radio"
                                    name="DamageLevel"
                                    value="Major"
                                    <?= $damageLevel === "Major"
                                        ? "checked"
                                        : ""
                                    ?>
                                >


                                <span
                                    class="damage-option-content"
                                >

                                    <strong>
                                        Major
                                    </strong>

                                    <small>
                                        Extensive damage
                                        affecting major
                                        vehicle components.
                                    </small>

                                </span>

                            </label>


                            <label class="damage-option">

                                <input
                                    type="radio"
                                    name="DamageLevel"
                                    value="Severe"
                                    <?= $damageLevel === "Severe"
                                        ? "checked"
                                        : ""
                                    ?>
                                >


                                <span
                                    class="damage-option-content"
                                >

                                    <strong>
                                        Severe
                                    </strong>

                                    <small>
                                        Extremely serious
                                        damage to the vehicle.
                                    </small>

                                </span>

                            </label>


                        </div>

                    </div>


                    <!-- REPORT NUMBER -->

                    <div class="form-group">

                        <label for="ReportNumber">

                            Report Number

                        </label>


                        <input
                            type="text"
                            name="ReportNumber"
                            id="ReportNumber"
                            value="<?= htmlspecialchars(
                                $reportNumber
                            ) ?>"
                            placeholder="Enter police report number"
                        >

                    </div>


                    <!-- BUTTONS -->

                    <div
                        style="
                            display:flex;
                            gap:10px;
                            margin-top:25px;
                        "
                    >

                        <button
                            type="submit"
                            class="button"
                        >

                            Add Accident

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


<script>

const description =
    document.getElementById("Description");

const descriptionCount =
    document.getElementById("descriptionCount");


if (
    description &&
    descriptionCount
) {

    description.addEventListener(
        "input",
        function () {

            descriptionCount.textContent =
                this.value.length +
                " / 2000";

        }
    );

}

</script>


</body>

</html>