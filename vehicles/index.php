<?php

require_once "../includes/auth.php";
require_once "../includes/database.php";

$basePath = "../";
$activePage = "vehicles";

$stmt = $pdo->query("
    SELECT 
        v.VehicleID,
        v.PlateNumber,
        v.VIN,
        v.Make,
        v.Model,
        v.VehicleYear,
        v.Color,
        v.VehicleType,
        v.Status,
        o.FirstName,
        o.LastName
    FROM vehicles v
    INNER JOIN owners o ON v.OwnerID = o.OwnerID
    ORDER BY v.VehicleID DESC
");

$vehicles = $stmt->fetchAll();

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>VISRS - Vehicles</title>

    <link rel="stylesheet" href="../css/style.css">

</head>

<body>

<div class="layout">

    <!-- SIDEBAR -->

    <aside class="sidebar">

        <?php require_once "../includes/sidebar.php"; ?>

    </aside>


    <!-- MAIN CONTENT -->

    <div class="main-content">

        <!-- TOP BAR -->

        <header class="topbar">

            <div class="topbar-title">
                Vehicles
            </div>

            <div class="user-info">

                <span>
                    <?= htmlspecialchars($_SESSION["FirstName"]) ?>
                    <?= htmlspecialchars($_SESSION["LastName"]) ?>
                </span>

                <div class="user-avatar">

                    <?= strtoupper(
                        substr($_SESSION["FirstName"], 0, 1)
                    ) ?>

                </div>

            </div>

        </header>


        <!-- PAGE CONTENT -->

        <main class="content">

            <h1 class="page-title">
                Vehicle Management
            </h1>

            <p class="page-subtitle">
                View and manage vehicle records stored in the VISRS database.
            </p>


            <!-- ACTION BAR -->

            <section class="card">

                <div style="
                    display: flex;
                    justify-content: space-between;
                    align-items: center;
                    gap: 15px;
                    flex-wrap: wrap;
                ">

                    <div>

                        <h2 style="margin-bottom: 5px;">
                            Registered Vehicles
                        </h2>

                        <p style="color: #6b7280;">
                            <?= count($vehicles) ?> vehicle(s) currently registered.
                        </p>

                    </div>

                    <a
                        href="add.php"
                        class="button"
                    >
                        + Add Vehicle
                    </a>

                </div>

            </section>


            <!-- VEHICLE TABLE -->

            <section
                class="card"
                style="margin-top: 20px;"
            >

                <h2>Vehicle Records</h2>

                <?php if (count($vehicles) > 0): ?>

                    <div style="overflow-x: auto;">

                        <table style="
                            width: 100%;
                            border-collapse: collapse;
                        ">

                            <thead>

                                <tr style="
                                    border-bottom: 2px solid #e5e7eb;
                                    text-align: left;
                                ">

                                    <th style="padding: 12px;">
                                        Plate Number
                                    </th>

                                    <th style="padding: 12px;">
                                        VIN
                                    </th>

                                    <th style="padding: 12px;">
                                        Vehicle
                                    </th>

                                    <th style="padding: 12px;">
                                        Year
                                    </th>

                                    <th style="padding: 12px;">
                                        Owner
                                    </th>

                                    <th style="padding: 12px;">
                                        Status
                                    </th>

                                    <th style="padding: 12px;">
                                        Action
                                    </th>

                                </tr>

                            </thead>

                            <tbody>

                                <?php foreach ($vehicles as $vehicle): ?>

                                    <tr style="
                                        border-bottom: 1px solid #e5e7eb;
                                    ">

                                        <td style="padding: 12px;">
                                            <strong>
                                                <?= htmlspecialchars($vehicle["PlateNumber"]) ?>
                                            </strong>
                                        </td>

                                        <td style="padding: 12px;">
                                            <?= htmlspecialchars($vehicle["VIN"]) ?>
                                        </td>

                                        <td style="padding: 12px;">
                                            <?= htmlspecialchars($vehicle["Make"]) ?>
                                            <?= htmlspecialchars($vehicle["Model"]) ?>
                                        </td>

                                        <td style="padding: 12px;">
                                            <?= htmlspecialchars($vehicle["VehicleYear"]) ?>
                                        </td>

                                        <td style="padding: 12px;">
                                            <?= htmlspecialchars($vehicle["FirstName"]) ?>
                                            <?= htmlspecialchars($vehicle["LastName"]) ?>
                                        </td>

                                        <td style="padding: 12px;">

                                            <?= htmlspecialchars($vehicle["Status"]) ?>

                                        </td>

                                        <td style="padding: 12px;">

                                            <a
                                                href="view.php?id=<?= $vehicle["VehicleID"] ?>"
                                                class="button"
                                                style="padding: 8px 12px;"
                                            >
                                                View
                                            </a>

                                            <?php if (canManageVehicles()): ?>

                                            <a
                                                href="edit.php?id=<?= $vehicle["VehicleID"] ?>"
                                                class="button button-secondary"
                                                style="padding: 8px 12px;"
                                            >
                                                Edit
                                            </a>

                                                <a
                                                    href="delete.php?id=<?= $vehicle["VehicleID"] ?>"
                                                    class="button"
                                                    style="padding: 8px 12px; background: #991b1b;"
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

                <?php else: ?>

                    <div style="
                        padding: 30px;
                        text-align: center;
                        color: #6b7280;
                    ">

                        <p>
                            No vehicle records have been added yet.
                        </p>

                        <p style="margin-top: 10px;">

                            <a href="add.php" class="button">
                                Add Your First Vehicle
                            </a>

                        </p>

                    </div>

                <?php endif; ?>

            </section>

        </main>

    </div>

</div>

<script src="../js/app.js"></script>

</body>

</html>