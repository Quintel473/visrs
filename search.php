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


/*
 * Global <head>
 */

$pageTitle = "Vehicle Search";

require_once "includes/head.php";

?>

<style>
    /* Search Bar Styling */
    .search-box {
        display: flex;
        gap: 10px;
        align-items: center;
        flex-wrap: wrap;
    }

    .search-box input {
        flex: 1;
        min-width: 250px;
        padding: 12px 16px;
        font-size: 15px;
        border: 1px solid #d1d5db;
        border-radius: 8px;
        background: #ffffff;
        color: #111827;
        outline: none;
        transition: border-color 0.15s ease, box-shadow 0.15s ease;
    }

    .search-box input:focus {
        border-color: #2563eb;
        box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
    }

    .search-box button {
        padding: 12px 24px;
        font-size: 15px;
        font-weight: 600;
        border-radius: 8px;
        white-space: nowrap;
    }

    [data-theme="dark"] .search-box input {
        background: #1e293b;
        border-color: #334155;
        color: #e2e8f0;
    }
</style>

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

<script src="js/app.js"></script>

</body>

</html>