<?php

$activePage = $activePage ?? "";

?>

<aside class="sidebar">

    <!-- =====================================================
         VISRS LOGO
         ===================================================== -->

    <div class="sidebar-logo">

        <h2>VISRS</h2>

        <p>
            Vehicle Information System
        </p>

    </div>


    <!-- =====================================================
         NAVIGATION
         ===================================================== -->

    <nav class="sidebar-nav">


        <!-- DASHBOARD -->

        <a
            href="/visrs/dashboard.php"
            class="<?= $activePage === 'dashboard' ? 'active' : '' ?>"
        >

            <span class="sidebar-icon">
            </span>

            <span class="sidebar-text">
                Dashboard
            </span>

        </a>


        <!-- VEHICLE SEARCH -->

        <a
            href="/visrs/search.php"
            class="<?= $activePage === 'search' ? 'active' : '' ?>"
        >

            <span class="sidebar-icon">
            </span>

            <span class="sidebar-text">
                Vehicle Search
            </span>

        </a>


        <!-- VEHICLES -->

        <a
            href="/visrs/vehicles/"
            class="<?= $activePage === 'vehicles' ? 'active' : '' ?>"
        >

            <span class="sidebar-icon">
            </span>

            <span class="sidebar-text">
                Vehicles
            </span>

        </a>


        <!-- OWNERS -->

        <a
            href="/visrs/owners/"
            class="<?= $activePage === 'owners' ? 'active' : '' ?>"
        >

            <span class="sidebar-icon">
            </span>

            <span class="sidebar-text">
                Owners
            </span>

        </a>


        <!-- OWNERSHIP HISTORY -->

        <a
            href="/visrs/ownership_history/"
            class="<?= $activePage === 'ownership_history' ? 'active' : '' ?>"
        >

            <span class="sidebar-icon">
            </span>

            <span class="sidebar-text">
                Ownership History
            </span>

        </a>


        <!-- INSURANCE -->

        <a
            href="/visrs/insurance/"
            class="<?= $activePage === 'insurance' ? 'active' : '' ?>"
        >

            <span class="sidebar-icon">
            </span>

            <span class="sidebar-text">
                Insurance
            </span>

        </a>


        <!-- ACCIDENTS -->

        <a
            href="/visrs/accidents/"
            class="<?= $activePage === 'accidents' ? 'active' : '' ?>"
        >

            <span class="sidebar-icon">
            </span>

            <span class="sidebar-text">
                Accidents
            </span>

        </a>


        <!-- =================================================
             VEHICLE MARKETPLACE
             ================================================= -->

        <a
            href="/visrs/marketplace/"
            class="<?= $activePage === 'marketplace' ? 'active' : '' ?>"
        >

            <span class="sidebar-icon">
            </span>

            <span class="sidebar-text">
                Marketplace
            </span>

        </a>


        <!-- =================================================
             ADMINISTRATION
             ================================================= -->

        <?php if (
            isset($_SESSION["Role"]) &&
            $_SESSION["Role"] === "Admin"
        ): ?>


            <!-- USER MANAGEMENT -->

            <a
                href="/visrs/users/"
                class="<?= $activePage === 'users' ? 'active' : '' ?>"
            >

                <span class="sidebar-icon">
                </span>

                <span class="sidebar-text">
                    User Management
                </span>

            </a>


            <!-- AUDIT TRAIL -->

            <a
                href="/visrs/audit_logs/"
                class="<?= $activePage === 'audit_logs' ? 'active' : '' ?>"
            >

                <span class="sidebar-icon">
                </span>

                <span class="sidebar-text">
                    Audit Trail
                </span>

            </a>


        <?php endif; ?>


        <!-- =================================================
             LOGOUT
             ================================================= -->

        <a
            href="/visrs/logout.php"
        >

            <span class="sidebar-icon">
            </span>

            <span class="sidebar-text">
                Logout
            </span>

        </a>


    </nav>

</aside>