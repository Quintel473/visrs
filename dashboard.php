<?php

require_once "includes/auth.php";
require_once "includes/database.php";

$basePath = "";
$activePage = "dashboard";


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

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>VISRS - Dashboard</title>

    <link
        rel="stylesheet"
        href="css/style.css"
    >

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

</head>


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


        </main>

    </div>

</div>


<script src="js/app.js"></script>

</body>

</html>