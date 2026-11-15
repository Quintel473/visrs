<?php

require_once "includes/auth.php";
require_once "includes/database.php";

$basePath = "";
$activePage = "dashboard";

require_once "includes/functions.php";

generateScheduledNotifications($pdo);


/*
|--------------------------------------------------------------------------
| Role-Based Access Control Setup
|--------------------------------------------------------------------------
*/

$currentRole = $_SESSION["Role"] ?? 'User';
$currentUserId = $_SESSION["UserID"] ?? 0;

// Admin and Police see EVERYTHING. Sellers see their inventory. Users see their cars.
$isAdminOrPolice = in_array($currentRole, ['Admin', 'Police']);
$canManageOwners = in_array($currentRole, ['Admin', 'Police']);
$isSeller = ($currentRole === 'Seller');


/* 
 * Build the SQL constraints for Dashboard Stats & Charts.
 */

if ($isAdminOrPolice) {
    $vehicleWhere = "1=1";
    $accidentWhere = "1=1";
    $insuranceWhere = "1=1";
} elseif ($isSeller) {
    $vehicleWhere = "v.SellerID = " . (int)$currentUserId;
    $accidentWhere = "v.SellerID = " . (int)$currentUserId;
    $insuranceWhere = "v.SellerID = " . (int)$currentUserId;
} else {
    $vehicleWhere = "o.UserID = " . (int)$currentUserId;
    $accidentWhere = "o.UserID = " . (int)$currentUserId;
    $insuranceWhere = "o.UserID = " . (int)$currentUserId;
}


/* Dashboard statistics */

$stmt = $pdo->query("
    SELECT COUNT(*) AS TotalVehicles
    FROM vehicles v
    INNER JOIN owners o ON v.OwnerID = o.OwnerID
    WHERE $vehicleWhere
");
$totalVehicles = $stmt->fetch()["TotalVehicles"];


$stmt = $pdo->query("
    SELECT COUNT(*) AS TotalOwners
    FROM owners
");
$totalOwners = $stmt->fetch()["TotalOwners"];


$stmt = $pdo->query("
    SELECT COUNT(*) AS TotalAccidents
    FROM accidents a
    INNER JOIN vehicles v ON a.VehicleID = v.VehicleID
    INNER JOIN owners o ON v.OwnerID = o.OwnerID
    WHERE $accidentWhere
");
$totalAccidents = $stmt->fetch()["TotalAccidents"];


$stmt = $pdo->query("
    SELECT COUNT(*) AS TotalInsurance
    FROM insurance i
    INNER JOIN vehicles v ON i.VehicleID = v.VehicleID
    INNER JOIN owners o ON v.OwnerID = o.OwnerID
    WHERE $insuranceWhere
");
$totalInsurance = $stmt->fetch()["TotalInsurance"];


/*
|--------------------------------------------------------------------------
| Chart Data — Vehicles by Status
|--------------------------------------------------------------------------
*/

$statusStmt = $pdo->query("
    SELECT
        v.Status,
        COUNT(*) AS Count
    FROM vehicles v
    INNER JOIN owners o ON v.OwnerID = o.OwnerID
    WHERE $vehicleWhere
    GROUP BY v.Status
");

$vehicleStatusData = [
    "Active"   => 0,
    "Inactive" => 0,
    "Stolen"   => 0,
    "Sold"     => 0,
];

while ($row = $statusStmt->fetch()) {
    $vehicleStatusData[$row["Status"]] = (int) $row["Count"];
}


/*
|--------------------------------------------------------------------------
| Chart Data — Vehicles by Make
|--------------------------------------------------------------------------
*/

$makeStmt = $pdo->query("
    SELECT
        v.Make,
        COUNT(*) AS Count
    FROM vehicles v
    INNER JOIN owners o ON v.OwnerID = o.OwnerID
    WHERE $vehicleWhere
      AND v.Make IS NOT NULL
      AND v.Make <> ''
    GROUP BY v.Make
    ORDER BY Count DESC, v.Make ASC
");

$makeRows = $makeStmt->fetchAll();

$vehicleMakeData = [];

$topN = 6;
$otherCount = 0;

foreach ($makeRows as $i => $row) {

    if ($i < $topN) {
        $vehicleMakeData[$row["Make"]] = (int) $row["Count"];
    } else {
        $otherCount += (int) $row["Count"];
    }
}

if ($otherCount > 0) {
    $vehicleMakeData["Other"] = $otherCount;
}


/*
|--------------------------------------------------------------------------
| Chart Data — Accidents Over Time (last 6 months)
|--------------------------------------------------------------------------
*/

$accidentStmt = $pdo->query("
    SELECT
        DATE_FORMAT(a.AccidentDate, '%Y-%m') AS Month,
        COUNT(*) AS Count
    FROM accidents a
    INNER JOIN vehicles v ON a.VehicleID = v.VehicleID
    INNER JOIN owners o ON v.OwnerID = o.OwnerID
    WHERE $accidentWhere
      AND a.AccidentDate >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
    GROUP BY DATE_FORMAT(a.AccidentDate, '%Y-%m')
    ORDER BY Month ASC
");

$accidentMonthsRaw = [];
while ($row = $accidentStmt->fetch()) {
    $accidentMonthsRaw[$row["Month"]] = (int) $row["Count"];
}

$accidentMonthLabels = [];
$accidentMonthCounts = [];

for ($i = 5; $i >= 0; $i--) {
    $monthKey = date("Y-m", strtotime("-$i months"));
    $accidentMonthLabels[] = date("M Y", strtotime($monthKey . "-01"));
    $accidentMonthCounts[] = $accidentMonthsRaw[$monthKey] ?? 0;
}


/*
|--------------------------------------------------------------------------
| Chart Data — Accidents by Damage Level
|--------------------------------------------------------------------------
*/

$damageStmt = $pdo->query("
    SELECT
        a.DamageLevel,
        COUNT(*) AS Count
    FROM accidents a
    INNER JOIN vehicles v ON a.VehicleID = v.VehicleID
    INNER JOIN owners o ON v.OwnerID = o.OwnerID
    WHERE $accidentWhere
    GROUP BY a.DamageLevel
");

$damageData = [
    "Minor"    => 0,
    "Moderate" => 0,
    "Major"    => 0,
    "Severe"   => 0,
];

while ($row = $damageStmt->fetch()) {
    if (isset($damageData[$row["DamageLevel"]])) {
        $damageData[$row["DamageLevel"]] = (int) $row["Count"];
    }
}


/*
|--------------------------------------------------------------------------
| Enable Chart.js in head.php
|--------------------------------------------------------------------------
*/

$loadCharts = true;

$pageTitle = "Dashboard";

require_once "includes/head.php";

?>

<style>
    .sidebar-nav { display: flex; flex-direction: column; gap: 4px; }
    .sidebar-nav a { display: flex; align-items: center; min-height: 44px; box-sizing: border-box; text-decoration: none; white-space: nowrap; }
    
    /* Dashboard Stats */
    .stat-number { font-size: 30px; font-weight: 700; margin-top: 8px; }
    .stat-label { color: #64748b; font-size: 14px; }

    /* Quick Actions Grid */
    .dashboard-actions { 
        display: grid; 
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); 
        gap: 15px; 
        margin-top: 20px; 
    }
    
    .dashboard-action-button { 
        display: flex; 
        align-items: center; 
        justify-content: center; 
        gap: 8px; /* Added gap for the SVG icon */
        padding: 14px 20px;
        min-height: 52px; 
        box-sizing: border-box; 
        text-decoration: none; 
        border-radius: 8px; 
        font-weight: 600; 
        font-size: 14px;
        border: 1px solid #e5e7eb;
        background: #ffffff;
        color: #374151;
        transition: all 0.2s ease;
        box-shadow: 0 1px 2px rgba(0,0,0,0.05);
    }

    /* Ensure the SVG icons scale nicely inside the buttons */
    .dashboard-action-button svg {
        width: 18px;
        height: 18px;
        stroke: currentColor;
        flex-shrink: 0;
    }

    .dashboard-action-button:hover {
        background: #f8fafc;
        border-color: #cbd5e1;
        color: #111827;
        transform: translateY(-1px);
        box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05);
    }

    /* Primary Actions (e.g. Add Vehicle) */
    .dashboard-action-button.primary-action {
        background: #2563eb;
        border-color: #2563eb;
        color: #ffffff;
    }
    .dashboard-action-button.primary-action:hover {
        background: #1d4ed8;
        border-color: #1d4ed8;
        color: #ffffff;
    }

    /* Dark Mode Support */
    [data-theme="dark"] .dashboard-action-button {
        background: #1e293b;
        border-color: #334155;
        color: #e2e8f0;
    }
    [data-theme="dark"] .dashboard-action-button:hover {
        background: #334155;
        border-color: #475569;
        color: #ffffff;
    }
    [data-theme="dark"] .dashboard-action-button.primary-action {
        background: #2563eb;
        border-color: #2563eb;
        color: #ffffff;
    }
    [data-theme="dark"] .dashboard-action-button.primary-action:hover {
        background: #1d4ed8;
    }

    @media (max-width: 768px) { .dashboard-actions { grid-template-columns: 1fr; } }
</style>

<body>

<div class="layout">

    <aside class="sidebar">
        <?php require_once "includes/sidebar.php"; ?>
    </aside>

    <div class="main-content">

        <?php
        $pageTitle = "Dashboard";
        include __DIR__ . "/includes/header.php";
        ?>

        <main class="content">

            <!-- PAGE TITLE -->

            <div class="page-title">

                <h1>

                    <span data-i18n="welcome_back">
                        Welcome back,
                    </span>

                    <?= htmlspecialchars(
                        $_SESSION["FirstName"]
                    ) ?>!

                </h1>

                <p
                    class="page-subtitle"
                    data-i18n="vehicle_information_search_system"
                >
                    Vehicle Information Search & Retrieval System
                </p>

            </div>


            <!-- ========================= -->
            <!-- STATISTICS -->
            <!-- ========================= -->

            <div class="stats-grid">

                <!-- VEHICLES -->

                <div class="stat-card">

                    <div
                        class="stat-label"
                        data-i18n="total_vehicles"
                    >
                        <?php 
                        if ($isAdminOrPolice) echo "Total Vehicles";
                        elseif ($isSeller) echo "My Listings";
                        else echo "My Vehicles";
                        ?>
                    </div>

                    <div class="stat-number" data-target="<?= (int) $totalVehicles ?>">

                        <?= htmlspecialchars(
                            $totalVehicles
                        ) ?>

                    </div>

                </div>


                <!-- OWNERS (Only show if Admin/Police) -->

                <?php if ($canManageOwners): ?>

                    <div class="stat-card">

                        <div
                            class="stat-label"
                            data-i18n="total_owners"
                        >
                            Total Owners
                        </div>

                        <div class="stat-number" data-target="<?= (int) $totalOwners ?>">

                            <?= htmlspecialchars(
                                $totalOwners
                            ) ?>

                        </div>

                    </div>
                    
                <?php endif; ?>


                <!-- ACCIDENTS -->

                <div class="stat-card">

                    <div
                        class="stat-label"
                        data-i18n="accident_records"
                    >
                        <?php 
                        if ($isAdminOrPolice) echo "Accident Records";
                        elseif ($isSeller) echo "Listing Accidents";
                        else echo "My Accidents";
                        ?>
                    </div>

                    <div class="stat-number" data-target="<?= (int) $totalAccidents ?>">

                        <?= htmlspecialchars(
                            $totalAccidents
                        ) ?>

                    </div>

                </div>


                <!-- INSURANCE -->

                <div class="stat-card">

                    <div
                        class="stat-label"
                        data-i18n="insurance_records"
                    >
                        <?php 
                        if ($isAdminOrPolice) echo "Insurance Records";
                        elseif ($isSeller) echo "Listing Insurance";
                        else echo "My Insurance";
                        ?>
                    </div>

                    <div class="stat-number" data-target="<?= (int) $totalInsurance ?>">

                        <?= htmlspecialchars(
                            $totalInsurance
                        ) ?>

                    </div>

                </div>

            </div>


            <!-- ========================= -->
            <!-- SEARCH -->
            <!-- ========================= -->

            <section
                class="card"
                style="margin-top: 25px;"
            >

                <h2 data-i18n="search_vehicle_records">
                    Search Vehicle Records
                </h2>

                <p
                    class="text-muted"
                    style="margin-top: 8px;"
                    data-i18n="search_vehicle_description"
                >
                    Search by license plate, VIN, owner,
                    make, or model.
                </p>


                <form
                    action="search.php"
                    method="GET"
                    style="margin-top: 20px;"
                >

                    <div
                        style="
                            display: flex;
                            gap: 10px;
                            flex-wrap: wrap;
                        "
                    >

                        <input
                            type="text"
                            name="search"
                            data-i18n-placeholder="search_vehicle_placeholder"
                            placeholder="Enter plate, VIN, owner, make or model..."
                            style="
                                flex: 1;
                                min-width: 250px;
                            "
                        >


                        <button
                            type="submit"
                            class="button"
                            data-i18n="search_vehicles"
                        >
                            Search Vehicles
                        </button>

                    </div>

                </form>

            </section>


            <!-- ========================= -->
            <!-- QUICK ACTIONS -->
            <!-- ========================= -->

            <section
                class="card"
                style="margin-top: 25px;"
            >

                <h2 data-i18n="quick_actions">
                    Quick Actions
                </h2>


                <div class="dashboard-actions">

                    <a href="search.php" class="dashboard-action-button">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
                        Search Vehicles
                    </a>


                    <!-- Add Vehicle (Staff only) -->
                    <?php if ($isAdminOrPolice || $isSeller): ?>
                        <a href="vehicles/add.php" class="dashboard-action-button primary-action">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
                            Add Vehicle
                        </a>
                    <?php endif; ?>


                    <!-- Add Owner (Admin/Police only) -->
                    <?php if ($canManageOwners): ?>
                        <a href="owners/add.php" class="dashboard-action-button">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
                            Add Owner
                        </a>
                    <?php endif; ?>


                    <!-- Add Ownership Record (Admin/Police only) -->
                    <?php if ($canManageOwners): ?>
                        <a href="ownership_history/add.php" class="dashboard-action-button">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line><polyline points="10 9 9 9 8 9"></polyline></svg>
                            Add Ownership
                        </a>
                    <?php endif; ?>


                    <!-- View Vehicles (Everyone) -->
                    <a href="vehicles/" class="dashboard-action-button">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 17h2c.6 0 1-.4 1-1v-3c0-.9-.7-1.7-1.5-1.9C18.7 10.6 16 10 16 10s-1.3-1.4-2.2-2.3c-.5-.4-1.1-.7-1.8-.7H5c-.6 0-1.1.4-1.4.9l-1.4 2.9A3.7 3.7 0 0 0 2 12v4c0 .6.4 1 1 1h2"></path><circle cx="7" cy="17" r="2"></circle><path d="M9 17h6"></path><circle cx="17" cy="17" r="2"></circle></svg>
                        View Vehicles
                    </a>


                    <!-- View Owners (Admin/Police only) -->
                    <?php if ($canManageOwners): ?>
                        <a href="owners/" class="dashboard-action-button">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>
                            View Owners
                        </a>
                    <?php endif; ?>


                    <!-- View Ownership History (Everyone) -->
                    <a href="ownership_history/" class="dashboard-action-button">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line><polyline points="10 9 9 9 8 9"></polyline></svg>
                        View History
                    </a>


                    <!-- Manage Users (Admin only) -->
                    <?php if ($currentRole === "Admin"): ?>
                        <a href="users/" class="dashboard-action-button">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="3"></circle><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"></path></svg>
                            Manage Users
                        </a>
                    <?php endif; ?>

                </div>

            </section>


            <!-- ========================= -->
            <!-- SYSTEM INFORMATION (Admin Only) -->
            <!-- ========================= -->

            <?php if ($currentRole === "Admin"): ?>

                <section
                    class="card"
                    style="margin-top: 25px;"
                >

                    <h2 data-i18n="system_information">
                        System Information
                    </h2>

                    <div
                        style="
                            display: grid;
                            grid-template-columns:
                                repeat(
                                    auto-fit,
                                    minmax(200px, 1fr)
                                );
                            gap: 20px;
                            margin-top: 20px;
                        "
                    >

                        <div>
                            <strong data-i18n="current_user">Current User</strong>
                            <p class="text-muted" style="margin-top: 5px;">
                                <?= htmlspecialchars($_SESSION["FirstName"]) ?>
                                <?= htmlspecialchars($_SESSION["LastName"]) ?>
                            </p>
                        </div>

                        <div>
                            <strong data-i18n="role">Role</strong>
                            <p class="text-muted" style="margin-top: 5px;">
                                <?= htmlspecialchars($_SESSION["Role"]) ?>
                            </p>
                        </div>

                        <div>
                            <strong data-i18n="system">System</strong>
                            <p class="text-muted" style="margin-top: 5px;">
                                VISRS
                            </p>
                        </div>

                        <div>
                            <strong data-i18n="status">Status</strong>
                            <p class="text-success" style="margin-top: 5px;" data-i18n="system_operational">
                                System Operational
                            </p>
                        </div>

                    </div>

                </section>

            <?php endif; ?>


            <!-- ========================= -->
            <!-- CHARTS -->
            <!-- ========================= -->

            <div class="charts-grid">

                <div class="chart-card">
                    <h2><?= $isSeller ? "My Listings by Status" : "Vehicles by Status" ?></h2>
                    <p>Distribution of registered vehicles by their current status.</p>
                    <div class="chart-canvas-wrapper is-loading">
                        <canvas id="vehicleStatusChart"></canvas>
                    </div>
                </div>

                <div class="chart-card">
                    <h2><?= $isSeller ? "My Listings by Make" : "Vehicles by Make" ?></h2>
                    <p>Distribution of registered vehicles by manufacturer.</p>
                    <div class="chart-canvas-wrapper is-loading">
                        <canvas id="vehicleMakeChart"></canvas>
                    </div>
                </div>

                <div class="chart-card">
                    <h2><?= $isSeller ? "My Listings: Accidents Over Time" : "Accidents Over Time" ?></h2>
                    <p>Number of recorded accidents per month over the last 6 months.</p>
                    <div class="chart-canvas-wrapper is-loading">
                        <canvas id="accidentsChart"></canvas>
                    </div>
                </div>

                <div class="chart-card">
                    <h2><?= $isSeller ? "My Listings: Accidents by Damage" : "Accidents by Damage Level" ?></h2>
                    <p>Severity distribution across all recorded accidents.</p>
                    <div class="chart-canvas-wrapper is-loading">
                        <canvas id="accidentDamageChart"></canvas>
                    </div>
                </div>

            </div>

        </main>

    </div>

</div>


<script src="js/app.js"></script>

<script>

(function () {

    'use strict';

    var vehicleStatusData = <?= json_encode($vehicleStatusData) ?>;
    var vehicleMakeData   = <?= json_encode($vehicleMakeData) ?>;
    var accidentLabels    = <?= json_encode($accidentMonthLabels) ?>;
    var accidentCounts    = <?= json_encode($accidentMonthCounts) ?>;
    var accidentDamageData = <?= json_encode($damageData) ?>;


    function getThemeColors() {
        var isDark = document.documentElement.getAttribute('data-theme') === 'dark';
        return {
            text:      isDark ? '#e2e8f0' : '#374151',
            textMuted: isDark ? '#94a3b8' : '#6b7280',
            grid:      isDark ? '#334155' : '#e5e7eb',
            border:    isDark ? '#1e293b' : '#ffffff'
        };
    }


    var vehicleStatusChart = null;
    var vehicleMakeChart = null;
    var accidentsChart = null;
    var accidentDamageChart = null;


    function buildVehicleStatusChart() {
        var ctx = document.getElementById('vehicleStatusChart');
        if (!ctx) return;
        var wrapper = ctx.parentNode;
        wrapper.classList.add('is-loading');
        var colors = getThemeColors();
        if (vehicleStatusChart) { vehicleStatusChart.destroy(); }

        vehicleStatusChart = new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: ['Active', 'Inactive', 'Stolen', 'Sold'],
                datasets: [{
                    data: [vehicleStatusData.Active, vehicleStatusData.Inactive, vehicleStatusData.Stolen, vehicleStatusData.Sold],
                    backgroundColor: ['#16a34a', '#94a3b8', '#dc2626', '#f59e0b'],
                    borderColor: colors.border,
                    borderWidth: 2
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                animation: { duration: 600, onComplete: function () { wrapper.classList.remove('is-loading'); } },
                plugins: {
                    legend: { position: 'bottom', labels: { color: colors.text, padding: 14, font: { family: 'Arial, Helvetica, sans-serif', size: 13 } } },
                    tooltip: { callbacks: { label: function (context) { return context.label + ': ' + context.parsed; } } }
                },
                cutout: '60%'
            }
        });
    }


    function buildVehicleMakeChart() {
        var ctx = document.getElementById('vehicleMakeChart');
        if (!ctx) return;
        var wrapper = ctx.parentNode;
        wrapper.classList.add('is-loading');
        var colors = getThemeColors();
        var labels = Object.keys(vehicleMakeData);
        var values = Object.values(vehicleMakeData);

        if (labels.length === 0) {
            wrapper.innerHTML = '<div style="display:flex;align-items:center;justify-content:center;height:100%;color:' + colors.textMuted + ';font-size:13px;text-align:center;padding:20px;">No vehicle data to display.</div>';
            wrapper.classList.remove('is-loading');
            return;
        }

        if (vehicleMakeChart) { vehicleMakeChart.destroy(); }
        var palette = ['#2563eb', '#16a34a', '#f59e0b', '#dc2626', '#8b5cf6', '#0891b2', '#94a3b8'];

        vehicleMakeChart = new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: labels,
                datasets: [{ data: values, backgroundColor: palette.slice(0, labels.length), borderColor: colors.border, borderWidth: 2 }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                animation: { duration: 600, onComplete: function () { wrapper.classList.remove('is-loading'); } },
                plugins: {
                    legend: { position: 'bottom', labels: { color: colors.text, padding: 12, boxWidth: 12, font: { family: 'Arial, Helvetica, sans-serif', size: 12 } } },
                    tooltip: { callbacks: { label: function (context) { return context.label + ': ' + context.parsed; } } }
                },
                cutout: '60%'
            }
        });
    }


    function buildAccidentsChart() {
        var ctx = document.getElementById('accidentsChart');
        if (!ctx) return;
        var wrapper = ctx.parentNode;
        wrapper.classList.add('is-loading');
        var colors = getThemeColors();
        if (accidentsChart) { accidentsChart.destroy(); }

        accidentsChart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: accidentLabels,
                datasets: [{ label: 'Accidents', data: accidentCounts, backgroundColor: '#2563eb', borderRadius: 6, maxBarThickness: 46 }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                animation: { duration: 600, onComplete: function () { wrapper.classList.remove('is-loading'); } },
                plugins: {
                    legend: { display: false },
                    tooltip: { callbacks: { label: function (context) { return context.parsed.y + ' accident(s)'; } } }
                },
                scales: {
                    x: { ticks: { color: colors.textMuted, font: { family: 'Arial, Helvetica, sans-serif' } }, grid: { display: false } },
                    y: { beginAtZero: true, ticks: { color: colors.textMuted, precision: 0, font: { family: 'Arial, Helvetica, sans-serif' } }, grid: { color: colors.grid } }
                }
            }
        });
    }


    function buildAccidentDamageChart() {
        var ctx = document.getElementById('accidentDamageChart');
        if (!ctx) return;
        var wrapper = ctx.parentNode;
        wrapper.classList.add('is-loading');
        var colors = getThemeColors();
        if (accidentDamageChart) { accidentDamageChart.destroy(); }

        accidentDamageChart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: ['Minor', 'Moderate', 'Major', 'Severe'],
                datasets: [{ label: 'Accidents', data: [accidentDamageData.Minor, accidentDamageData.Moderate, accidentDamageData.Major, accidentDamageData.Severe], backgroundColor: ['#16a34a', '#f59e0b', '#ea580c', '#dc2626'], borderRadius: 6, maxBarThickness: 56 }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                animation: { duration: 600, onComplete: function () { wrapper.classList.remove('is-loading'); } },
                plugins: {
                    legend: { display: false },
                    tooltip: { callbacks: { label: function (context) { return context.parsed.y + ' accident(s)'; } } }
                },
                scales: {
                    x: { ticks: { color: colors.textMuted, font: { family: 'Arial, Helvetica, sans-serif' } }, grid: { display: false } },
                    y: { beginAtZero: true, ticks: { color: colors.textMuted, precision: 0, font: { family: 'Arial, Helvetica, sans-serif' } }, grid: { color: colors.grid } }
                }
            }
        });
    }


    function buildCharts() {
        buildVehicleStatusChart();
        buildVehicleMakeChart();
        buildAccidentsChart();
        buildAccidentDamageChart();
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', buildCharts);
    } else {
        buildCharts();
    }

    document.addEventListener('click', function (e) {
        var toggle = e.target.closest('#theme-toggle');
        if (!toggle) return;
        setTimeout(buildCharts, 50);
    });

})();

</script>

</body>

</html>