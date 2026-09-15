<?php

require_once "includes/auth.php";
require_once "includes/database.php";

$basePath = "";
$activePage = "search";

$search = trim($_GET["search"] ?? "");

$vehicles = [];

if ($search !== "") {

    $stmt = $pdo->prepare("
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
            o.FirstName AS OwnerFirstName,
            o.LastName AS OwnerLastName
        FROM vehicles v
        INNER JOIN owners o
            ON v.OwnerID = o.OwnerID
        WHERE
            v.PlateNumber LIKE ?
            OR v.VIN LIKE ?
            OR o.FirstName LIKE ?
            OR o.LastName LIKE ?
            OR CONCAT(o.FirstName, ' ', o.LastName) LIKE ?
            OR v.Make LIKE ?
            OR v.Model LIKE ?
        ORDER BY v.VehicleID DESC
    ");

    $searchTerm = "%" . $search . "%";

    $stmt->execute([
        $searchTerm,
        $searchTerm,
        $searchTerm,
        $searchTerm,
        $searchTerm,
        $searchTerm,
        $searchTerm
    ]);

    $vehicles = $stmt->fetchAll();
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

    <title>VISRS - Vehicle Search</title>

    <link
        rel="stylesheet"
        href="css/style.css"
    >

</head>

<body>

<div class="layout">

    <!-- Sidebar -->

    <aside class="sidebar">

        <?php require_once "includes/sidebar.php"; ?>

    </aside>


    <!-- Main Content -->

    <main class="main-content">

        <!-- Topbar -->

    <?php

        $pageTitle = "Vehicle Search";

        include __DIR__ . "/includes/header.php";

    ?>

        <!-- Page Content -->

        <section class="content">

            <div class="page-title">

                <h1>Vehicle Search</h1>

                <p class="page-subtitle">
                    Search for vehicles using a plate number, VIN,
                    owner name, make, or model.
                </p>

            </div>


            <!-- Search Box -->

            <div class="card">

                <form method="GET" action="search.php">

                    <div class="search-box">

                        <input
                            type="text"
                            name="search"
                            value="<?= htmlspecialchars($search) ?>"
                            placeholder="Enter plate number, VIN, owner name, make, or model..."
                            autocomplete="off"
                        >

                        <button
                            type="submit"
                            class="button"
                        >
                            Search
                        </button>

                    </div>

                </form>

            </div>


            <?php if ($search !== ""): ?>

                <div style="margin-top: 25px;">

                    <h2>
                        Search Results
                    </h2>

                    <p>
                        Showing results for:
                        <strong>
                            <?= htmlspecialchars($search) ?>
                        </strong>
                    </p>

                </div>


                <?php if (count($vehicles) > 0): ?>

                    <div class="card" style="margin-top: 20px; overflow-x: auto;">

                        <table style="width: 100%; border-collapse: collapse;">

                            <thead>

                                <tr>

                                    <th style="padding: 12px; text-align: left;">
                                        Plate Number
                                    </th>

                                    <th style="padding: 12px; text-align: left;">
                                        VIN
                                    </th>

                                    <th style="padding: 12px; text-align: left;">
                                        Vehicle
                                    </th>

                                    <th style="padding: 12px; text-align: left;">
                                        Year
                                    </th>

                                    <th style="padding: 12px; text-align: left;">
                                        Owner
                                    </th>

                                    <th style="padding: 12px; text-align: left;">
                                        Status
                                    </th>

                                    <th style="padding: 12px; text-align: left;">
                                        Action
                                    </th>

                                </tr>

                            </thead>

                            <tbody>

                                <?php foreach ($vehicles as $vehicle): ?>

                                    <tr>

                                        <td style="padding: 12px;">
                                            <?= htmlspecialchars($vehicle["PlateNumber"]) ?>
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

                                            <?= htmlspecialchars($vehicle["OwnerFirstName"]) ?>

                                            <?= htmlspecialchars($vehicle["OwnerLastName"]) ?>

                                        </td>

                                        <td style="padding: 12px;">
                                            <?= htmlspecialchars($vehicle["Status"]) ?>
                                        </td>

                                        <td style="padding: 12px;">

                                            <a
                                                href="vehicles/view.php?id=<?= $vehicle["VehicleID"] ?>"
                                                class="button button-secondary"
                                                style="padding: 8px 12px;"
                                            >
                                                View
                                            </a>

                                        </td>

                                    </tr>

                                <?php endforeach; ?>

                            </tbody>

                        </table>

                    </div>

                <?php else: ?>

                    <div
                        class="card"
                        style="margin-top: 20px; text-align: center; padding: 40px;"
                    >

                        <h2>No vehicles found</h2>

                        <p>
                            No vehicle records matched your search.
                        </p>

                    </div>

                <?php endif; ?>

            <?php else: ?>

                <div
                    class="card"
                    style="margin-top: 25px; text-align: center; padding: 40px;"
                >

                    <h2>Search the Vehicle Database</h2>

                    <p>
                        Enter a license plate, VIN, owner name,
                        make, or model above to begin searching.
                    </p>

                </div>

            <?php endif; ?>

        </section>

    </main>

</div>

</body>

</html>