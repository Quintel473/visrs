<?php

require_once "../includes/auth.php";
require_once "../includes/database.php";

$basePath = "../";
$activePage = "accidents";

$pageTitle = "Accident Records";
$pageSubtitle = "Manage and review vehicle accident records.";


/*
 * Load accident records.
 */

$stmt = $pdo->query("
    SELECT
        a.AccidentID,
        a.VehicleID,
        a.AccidentDate,
        a.Location,
        a.Description,
        a.DamageLevel,
        a.ReportNumber,

        v.PlateNumber,
        v.VIN,
        v.Make,
        v.Model,
        v.VehicleYear

    FROM accidents a

    INNER JOIN vehicles v
        ON a.VehicleID = v.VehicleID

    ORDER BY
        a.AccidentDate DESC,
        a.AccidentID DESC
");

$accidents = $stmt->fetchAll();


$canManage =
    isset($_SESSION["Role"]) &&
    in_array(
        $_SESSION["Role"],
        ["Admin", "Police"],
        true
    );

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
        Accidents - VISRS
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

        <?php include __DIR__ . "/../includes/header.php"; ?>

        <div class="content">

            <div class="card">

                <?php if (
                    isset($_GET["deleted"]) &&
                    $_GET["deleted"] === "1"
                ): ?>

                    <div
                        class="auto-dismiss"
                        style="
                            background:#dcfce7;
                            color:#166534;
                            padding:14px;
                            border-radius:8px;
                            margin-bottom:20px;
                        "
                    >
                        Accident record deleted successfully.
                    </div>

                <?php endif; ?>


                <div
                    style="
                        display:flex;
                        justify-content:space-between;
                        align-items:center;
                        margin-bottom:20px;
                        gap:20px;
                    "
                >

                    <div>

                        <h2>
                            Accident Records
                        </h2>

                        <p>
                            <?= count($accidents) ?>
                            accident record(s) found.
                        </p>

                    </div>


                    <?php if ($canManage): ?>

                        <a
                            href="add.php"
                            class="button"
                        >
                            + Add Accident
                        </a>

                    <?php endif; ?>

                </div>


                <?php if (empty($accidents)): ?>

                    <div
                        style="
                            padding:30px;
                            text-align:center;
                            background:#f8fafc;
                            border-radius:8px;
                        "
                    >

                        <h3>
                            No Accident Records
                        </h3>

                        <p>
                            There are currently no accident
                            records in the system.
                        </p>

                        <?php if ($canManage): ?>

                            <a
                                href="add.php"
                                class="button"
                                style="margin-top:10px;"
                            >
                                Add First Accident Record
                            </a>

                        <?php endif; ?>

                    </div>

                <?php else: ?>

                    <div
                        style="
                            overflow-x:auto;
                        "
                    >

                        <table
                            style="
                                width:100%;
                                border-collapse:collapse;
                            "
                        >

                            <thead>

                                <tr>

                                    <th
                                        style="
                                            text-align:left;
                                            padding:12px;
                                        "
                                    >
                                        Date
                                    </th>

                                    <th
                                        style="
                                            text-align:left;
                                            padding:12px;
                                        "
                                    >
                                        Vehicle
                                    </th>

                                    <th
                                        style="
                                            text-align:left;
                                            padding:12px;
                                        "
                                    >
                                        Location
                                    </th>

                                    <th
                                        style="
                                            text-align:left;
                                            padding:12px;
                                        "
                                    >
                                        Damage Level
                                    </th>

                                    <th
                                        style="
                                            text-align:left;
                                            padding:12px;
                                        "
                                    >
                                        Report Number
                                    </th>

                                    <th
                                        style="
                                            text-align:left;
                                            padding:12px;
                                        "
                                    >
                                        Actions
                                    </th>

                                </tr>

                            </thead>

                            <tbody>

                                <?php foreach (
                                    $accidents
                                    as $accident
                                ): ?>

                                    <tr>

                                        <td
                                            style="
                                                padding:12px;
                                            "
                                        >

                                            <?= htmlspecialchars(
                                                date(
                                                    "M j, Y",
                                                    strtotime(
                                                        $accident["AccidentDate"]
                                                    )
                                                )
                                            ) ?>

                                        </td>


                                        <td
                                            style="
                                                padding:12px;
                                            "
                                        >

                                            <strong>

                                                <?= htmlspecialchars(
                                                    $accident["PlateNumber"]
                                                ) ?>

                                            </strong>

                                            <br>

                                            <small>

                                                <?= htmlspecialchars(
                                                    $accident["Make"]
                                                ) ?>

                                                <?= htmlspecialchars(
                                                    $accident["Model"]
                                                ) ?>

                                                <?= htmlspecialchars(
                                                    "(" .
                                                    $accident["VehicleYear"] .
                                                    ")"
                                                ) ?>

                                            </small>

                                        </td>


                                        <td
                                            style="
                                                padding:12px;
                                            "
                                        >

                                            <?= $accident["Location"]
                                                ? htmlspecialchars(
                                                    $accident["Location"]
                                                )
                                                : "Not specified"
                                            ?>

                                        </td>


                                        <td
                                            style="
                                                padding:12px;
                                            "
                                        >

                                            <?= htmlspecialchars(
                                                $accident["DamageLevel"]
                                            ) ?>

                                        </td>


                                        <td
                                            style="
                                                padding:12px;
                                            "
                                        >

                                            <?= $accident["ReportNumber"]
                                                ? htmlspecialchars(
                                                    $accident["ReportNumber"]
                                                )
                                                : "Not specified"
                                            ?>

                                        </td>


                                        <td
                                            style="
                                                padding:12px;
                                                white-space:nowrap;
                                            "
                                        >

                                            <a
                                                href="view.php?id=<?= (int) $accident["AccidentID"] ?>"
                                                class="button button-secondary"
                                            >
                                                View
                                            </a>


                                            <?php if ($canManage): ?>

                                                <a
                                                    href="edit.php?id=<?= (int) $accident["AccidentID"] ?>"
                                                    class="button button-secondary"
                                                >
                                                    Edit
                                                </a>

                                                <a
                                                    href="delete.php?id=<?= (int) $accident["AccidentID"] ?>"
                                                    class="button"
                                                    style="
                                                        background:#991b1b;
                                                        margin-left:5px;
                                                    "
                                                >
                                                    Delete
                                                </a>

                                            <?php endif; ?>

                                        </td>

                                    </tr>

                                <?php endforeach; ?>

                            </tbody>

                        </table>

                    </div>

                <?php endif; ?>

            </div>

        </div>

    </main>

</div>

<?php require_once "../includes/footer.php"; ?>