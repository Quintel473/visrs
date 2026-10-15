<?php

require_once "includes/auth.php";
require_once "includes/database.php";

$basePath = "";
$activePage = "dashboard";

/*
 * Lazy-cron: generate scheduled notifications (insurance expiry,
 * etc.) the first time a user loads the dashboard each day.
 */
require_once "includes/functions.php";

generateScheduledNotifications($pdo);


/* Dashboard statistics */

$stmt = $pdo->query("
    SELECT COUNT(*) AS TotalVehicles
    FROM vehicles
");

$totalVehicles = $stmt->fetch()["TotalVehicles"];


$stmt = $pdo->query("
    SELECT COUNT(*) AS TotalOwners
    FROM owners
");

$totalOwners = $stmt->fetch()["TotalOwners"];


$stmt = $pdo->query("
    SELECT COUNT(*) AS TotalAccidents
    FROM accidents
");

$totalAccidents = $stmt->fetch()["TotalAccidents"];


$stmt = $pdo->query("
    SELECT COUNT(*) AS TotalInsurance
    FROM insurance
");

$totalInsurance = $stmt->fetch()["TotalInsurance"];


/*
|--------------------------------------------------------------------------
| Chart Data — Vehicles by Status
|--------------------------------------------------------------------------
*/

$statusStmt = $pdo->query("
    SELECT
        Status,
        COUNT(*) AS Count
    FROM vehicles
    GROUP BY Status
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
|
| Top 6 makes by count, with everything else grouped as "Other".
*/

$makeStmt = $pdo->query("
    SELECT
        Make,
        COUNT(*) AS Count
    FROM vehicles
    WHERE Make IS NOT NULL
      AND Make <> ''
    GROUP BY Make
    ORDER BY Count DESC, Make ASC
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
        DATE_FORMAT(AccidentDate, '%Y-%m') AS Month,
        COUNT(*) AS Count
    FROM accidents
    WHERE AccidentDate >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
    GROUP BY DATE_FORMAT(AccidentDate, '%Y-%m')
    ORDER BY Month ASC
");

$accidentMonthsRaw = [];
while ($row = $accidentStmt->fetch()) {
    $accidentMonthsRaw[$row["Month"]] = (int) $row["Count"];
}

/* Build a full 6-month series even for empty months */
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
        DamageLevel,
        COUNT(*) AS Count
    FROM accidents
    GROUP BY DamageLevel
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

    /*
     * Dashboard-specific navigation styling.
     * Keeping the sidebar links at a fixed height prevents
     * the navigation from moving around when additional
     * menu items are added.
     */

    .sidebar-nav {
        display: flex;
        flex-direction: column;
        gap: 4px;
    }


    .sidebar-nav a {
        display: flex;
        align-items: center;
        min-height: 44px;
        box-sizing: border-box;
        text-decoration: none;
        white-space: nowrap;
    }


    .dashboard-actions {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
        gap: 15px;
        margin-top: 20px;
    }


    .dashboard-action-button {
        display: flex;
        align-items: center;
        justify-content: center;
        min-height: 48px;
        box-sizing: border-box;
        text-decoration: none;
        border-radius: 8px;
        font-weight: 600;
    }


    .stat-number {
        font-size: 30px;
        font-weight: 700;
        margin-top: 8px;
    }


    .stat-label {
        color: #64748b;
        font-size: 14px;
    }


    @media (max-width: 768px) {

        .dashboard-actions {
            grid-template-columns: 1fr;
        }

    }

</style>


<body>

<div class="layout">


    <!-- ========================= -->
    <!-- SIDEBAR -->
    <!-- ========================= -->

    <aside class="sidebar">

        <?php require_once "includes/sidebar.php"; ?>

    </aside>


    <!-- ========================= -->
    <!-- MAIN CONTENT -->
    <!-- ========================= -->

    <div class="main-content">


        <!-- TOPBAR -->

        <?php

        $pageTitle = "Dashboard";

        include __DIR__ . "/includes/header.php";

        ?>


        <!-- PAGE CONTENT -->

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
                        Total Vehicles
                    </div>

                    <div class="stat-number">

                        <?= htmlspecialchars(
                            $totalVehicles
                        ) ?>

                    </div>

                </div>


                <!-- OWNERS -->

                <div class="stat-card">

                    <div
                        class="stat-label"
                        data-i18n="total_owners"
                    >
                        Total Owners
                    </div>

                    <div class="stat-number">

                        <?= htmlspecialchars(
                            $totalOwners
                        ) ?>

                    </div>

                </div>


                <!-- ACCIDENTS -->

                <div class="stat-card">

                    <div
                        class="stat-label"
                        data-i18n="accident_records"
                    >
                        Accident Records
                    </div>

                    <div class="stat-number">

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
                        Insurance Records
                    </div>

                    <div class="stat-number">

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
                            name="q"
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


                    <a
                        href="search.php"
                        class="button dashboard-action-button"
                        data-i18n="search_vehicles"
                    >
                        Search Vehicles
                    </a>


                    <a
                        href="vehicles/add.php"
                        class="button dashboard-action-button"
                        data-i18n="add_vehicle"
                    >
                        Add Vehicle
                    </a>


                    <a
                        href="owners/add.php"
                        class="button dashboard-action-button"
                        data-i18n="add_owner"
                    >
                        Add Owner
                    </a>


                    <a
                        href="ownership_history/add.php"
                        class="button dashboard-action-button"
                        data-i18n="add_ownership_record"
                    >
                        Add Ownership Record
                    </a>


                    <a
                        href="vehicles/"
                        class="button button-secondary dashboard-action-button"
                        data-i18n="view_vehicles"
                    >
                        View Vehicles
                    </a>


                    <a
                        href="owners/"
                        class="button button-secondary dashboard-action-button"
                        data-i18n="view_owners"
                    >
                        View Owners
                    </a>


                    <a
                        href="ownership_history/"
                        class="button button-secondary dashboard-action-button"
                        data-i18n="view_ownership_history"
                    >
                        View Ownership History
                    </a>


                    <?php if ($_SESSION["Role"] === "Admin"): ?>

                        <a
                            href="users/"
                            class="button button-secondary dashboard-action-button"
                            data-i18n="manage_users"
                        >
                            Manage Users
                        </a>

                    <?php endif; ?>


                </div>

            </section>


            <!-- ========================= -->
            <!-- SYSTEM INFORMATION -->
            <!-- ========================= -->

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

                        <strong data-i18n="current_user">
                            Current User
                        </strong>

                        <p
                            class="text-muted"
                            style="margin-top: 5px;"
                        >

                            <?= htmlspecialchars(
                                $_SESSION["FirstName"]
                            ) ?>

                            <?= htmlspecialchars(
                                $_SESSION["LastName"]
                            ) ?>

                        </p>

                    </div>


                    <div>

                        <strong data-i18n="role">
                            Role
                        </strong>

                        <p
                            class="text-muted"
                            style="margin-top: 5px;"
                        >

                            <?= htmlspecialchars(
                                $_SESSION["Role"]
                            ) ?>

                        </p>

                    </div>


                    <div>

                        <strong data-i18n="system">
                            System
                        </strong>

                        <p
                            class="text-muted"
                            style="margin-top: 5px;"
                        >
                            VISRS
                        </p>

                    </div>


                    <div>

                        <strong data-i18n="status">
                            Status
                        </strong>

                        <p
                            class="text-success"
                            style="margin-top: 5px;"
                            data-i18n="system_operational"
                        >
                            System Operational
                        </p>

                    </div>

                </div>

            </section>


            <!-- ========================= -->
            <!-- CHARTS -->
            <!-- ========================= -->

            <div class="charts-grid">


                <!-- VEHICLES BY STATUS -->

                <div class="chart-card">

                    <h2>
                        Vehicles by Status
                    </h2>

                    <p>
                        Distribution of all registered vehicles by their current status.
                    </p>

                    <div class="chart-canvas-wrapper is-loading">

                        <canvas id="vehicleStatusChart"></canvas>

                    </div>

                </div>


                <!-- VEHICLES BY MAKE -->

                <div class="chart-card">

                    <h2>
                        Vehicles by Make
                    </h2>

                    <p>
                        Distribution of registered vehicles by manufacturer.
                    </p>

                    <div class="chart-canvas-wrapper is-loading">

                        <canvas id="vehicleMakeChart"></canvas>

                    </div>

                </div>


                <!-- ACCIDENTS OVER TIME -->

                <div class="chart-card">

                    <h2>
                        Accidents Over Time
                    </h2>

                    <p>
                        Number of recorded accidents per month over the last 6 months.
                    </p>

                    <div class="chart-canvas-wrapper is-loading">

                        <canvas id="accidentsChart"></canvas>

                    </div>

                </div>


                <!-- ACCIDENTS BY DAMAGE LEVEL -->

                <div class="chart-card">

                    <h2>
                        Accidents by Damage Level
                    </h2>

                    <p>
                        Severity distribution across all recorded accidents.
                    </p>

                    <div class="chart-canvas-wrapper is-loading">

                        <canvas id="accidentDamageChart"></canvas>

                    </div>

                </div>


            </div>


        </main>

    </div>

</div>


<script src="js/app.js"></script>


<!-- =========================================================
     Dashboard Charts (Chart.js)
     ========================================================= -->
<script>

(function () {

    'use strict';

    /* Data from PHP */
    var vehicleStatusData = <?= json_encode($vehicleStatusData) ?>;
    var vehicleMakeData   = <?= json_encode($vehicleMakeData) ?>;
    var accidentLabels    = <?= json_encode($accidentMonthLabels) ?>;
    var accidentCounts    = <?= json_encode($accidentMonthCounts) ?>;
    var accidentDamageData = <?= json_encode($damageData) ?>;


    /* Color palette that adapts to the current theme */
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

        if (vehicleStatusChart) {
            vehicleStatusChart.destroy();
        }

        vehicleStatusChart = new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: [
                    'Active',
                    'Inactive',
                    'Stolen',
                    'Sold'
                ],
                datasets: [{
                    data: [
                        vehicleStatusData.Active,
                        vehicleStatusData.Inactive,
                        vehicleStatusData.Stolen,
                        vehicleStatusData.Sold
                    ],
                    backgroundColor: [
                        '#16a34a',
                        '#94a3b8',
                        '#dc2626',
                        '#f59e0b'
                    ],
                    borderColor: colors.border,
                    borderWidth: 2
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                animation: {
                    duration: 600,
                    onComplete: function () {
                        wrapper.classList.remove('is-loading');
                    }
                },
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            color: colors.text,
                            padding: 14,
                            font: {
                                family: 'Arial, Helvetica, sans-serif',
                                size: 13
                            }
                        }
                    },
                    tooltip: {
                        callbacks: {
                            label: function (context) {
                                return context.label + ': ' + context.parsed;
                            }
                        }
                    }
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

        /* No data — show a friendly placeholder */
        if (labels.length === 0) {

            wrapper.innerHTML =
                '<div style="' +
                'display:flex;align-items:center;justify-content:center;' +
                'height:100%;color:' + colors.textMuted + ';' +
                'font-size:13px;text-align:center;padding:20px;' +
                '">No vehicle data to display.</div>';

            wrapper.classList.remove('is-loading');
            return;
        }

        if (vehicleMakeChart) {
            vehicleMakeChart.destroy();
        }

        /* Palette tuned for up to 7 slices (6 makes + Other) */
        var palette = [
            '#2563eb',
            '#16a34a',
            '#f59e0b',
            '#dc2626',
            '#8b5cf6',
            '#0891b2',
            '#94a3b8'
        ];

        vehicleMakeChart = new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: labels,
                datasets: [{
                    data: values,
                    backgroundColor: palette.slice(0, labels.length),
                    borderColor: colors.border,
                    borderWidth: 2
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                animation: {
                    duration: 600,
                    onComplete: function () {
                        wrapper.classList.remove('is-loading');
                    }
                },
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            color: colors.text,
                            padding: 12,
                            boxWidth: 12,
                            font: {
                                family: 'Arial, Helvetica, sans-serif',
                                size: 12
                            }
                        }
                    },
                    tooltip: {
                        callbacks: {
                            label: function (context) {
                                return context.label + ': ' + context.parsed;
                            }
                        }
                    }
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

        if (accidentsChart) {
            accidentsChart.destroy();
        }

        accidentsChart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: accidentLabels,
                datasets: [{
                    label: 'Accidents',
                    data: accidentCounts,
                    backgroundColor: '#2563eb',
                    borderRadius: 6,
                    maxBarThickness: 46
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                animation: {
                    duration: 600,
                    onComplete: function () {
                        wrapper.classList.remove('is-loading');
                    }
                },
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        callbacks: {
                            label: function (context) {
                                return context.parsed.y + ' accident(s)';
                            }
                        }
                    }
                },
                scales: {
                    x: {
                        ticks: {
                            color: colors.textMuted,
                            font: {
                                family: 'Arial, Helvetica, sans-serif'
                            }
                        },
                        grid: {
                            display: false
                        }
                    },
                    y: {
                        beginAtZero: true,
                        ticks: {
                            color: colors.textMuted,
                            precision: 0,
                            font: {
                                family: 'Arial, Helvetica, sans-serif'
                            }
                        },
                        grid: {
                            color: colors.grid
                        }
                    }
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

        if (accidentDamageChart) {
            accidentDamageChart.destroy();
        }

        accidentDamageChart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: ['Minor', 'Moderate', 'Major', 'Severe'],
                datasets: [{
                    label: 'Accidents',
                    data: [
                        accidentDamageData.Minor,
                        accidentDamageData.Moderate,
                        accidentDamageData.Major,
                        accidentDamageData.Severe
                    ],
                    backgroundColor: [
                        '#16a34a',   /* Minor    - green  */
                        '#f59e0b',   /* Moderate - amber  */
                        '#ea580c',   /* Major    - orange */
                        '#dc2626'    /* Severe   - red    */
                    ],
                    borderRadius: 6,
                    maxBarThickness: 56
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                animation: {
                    duration: 600,
                    onComplete: function () {
                        wrapper.classList.remove('is-loading');
                    }
                },
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        callbacks: {
                            label: function (context) {
                                return context.parsed.y + ' accident(s)';
                            }
                        }
                    }
                },
                scales: {
                    x: {
                        ticks: {
                            color: colors.textMuted,
                            font: {
                                family: 'Arial, Helvetica, sans-serif'
                            }
                        },
                        grid: {
                            display: false
                        }
                    },
                    y: {
                        beginAtZero: true,
                        ticks: {
                            color: colors.textMuted,
                            precision: 0,
                            font: {
                                family: 'Arial, Helvetica, sans-serif'
                            }
                        },
                        grid: {
                            color: colors.grid
                        }
                    }
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


    /* Initial build */
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', buildCharts);
    } else {
        buildCharts();
    }


    /* Rebuild when theme toggles */
    document.addEventListener('click', function (e) {

        var toggle = e.target.closest('#theme-toggle');
        if (!toggle) return;

        /* Small delay so the theme attribute is set first */
        setTimeout(buildCharts, 50);

    });

})();

</script>


</body>

</html>