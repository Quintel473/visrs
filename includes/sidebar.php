<?php
$activePage = $activePage ?? "";
?>

<aside class="sidebar">

    <!-- =====================================================
         VISRS LOGO
         ===================================================== -->
    <div class="sidebar-logo">
        <h2>VISRS</h2>
        <p data-i18n="vehicle_information_system">
            Vehicle Information System
        </p>
    </div>

    <!-- =====================================================
         NAVIGATION
         ===================================================== -->
    <nav class="sidebar-nav">
        <!-- DASHBOARD -->
        <a href="/visrs/dashboard.php" class="<?= $activePage === 'dashboard' ? 'active' : '' ?>">
            <span class="sidebar-icon"></span>
            <span class="sidebar-text" data-i18n="dashboard">Dashboard</span>
        </a>

        <!-- VEHICLE SEARCH -->
        <a href="/visrs/search.php" class="<?= $activePage === 'search' ? 'active' : '' ?>">
            <span class="sidebar-icon"></span>
            <span class="sidebar-text" data-i18n="vehicle_search">Vehicle Search</span>
        </a>

        <!-- VEHICLES -->
        <a href="/visrs/vehicles/" class="<?= $activePage === 'vehicles' ? 'active' : '' ?>">
            <span class="sidebar-icon"></span>
            <span class="sidebar-text" data-i18n="vehicles">Vehicles</span>
        </a>

        <!-- OWNERS -->
        <a href="/visrs/owners/" class="<?= $activePage === 'owners' ? 'active' : '' ?>">
            <span class="sidebar-icon"></span>
            <span class="sidebar-text" data-i18n="owners">Owners</span>
        </a>

        <!-- OWNERSHIP HISTORY -->
        <a href="/visrs/ownership_history/" class="<?= $activePage === 'ownership_history' ? 'active' : '' ?>">
            <span class="sidebar-icon"></span>
            <span class="sidebar-text" data-i18n="ownership_history">Ownership History</span>
        </a>

        <!-- INSURANCE -->
        <a href="/visrs/insurance/" class="<?= $activePage === 'insurance' ? 'active' : '' ?>">
            <span class="sidebar-icon"></span>
            <span class="sidebar-text" data-i18n="insurance">Insurance</span>
        </a>

        <!-- ACCIDENTS -->
        <a href="/visrs/accidents/" class="<?= $activePage === 'accidents' ? 'active' : '' ?>">
            <span class="sidebar-icon"></span>
            <span class="sidebar-text" data-i18n="accidents">Accidents</span>
        </a>

        <!-- MARKETPLACE -->
        <a href="/visrs/marketplace/" class="<?= $activePage === 'marketplace' ? 'active' : '' ?>">
            <span class="sidebar-icon"></span>
            <span class="sidebar-text" data-i18n="marketplace">Marketplace</span>
        </a>

        <!-- ADMINISTRATION -->
        <?php if (isset($_SESSION["Role"]) && $_SESSION["Role"] === "Admin"): ?>
            <a href="/visrs/users/" class="<?= $activePage === 'users' ? 'active' : '' ?>">
                <span class="sidebar-icon"></span>
                <span class="sidebar-text" data-i18n="user_management">User Management</span>
            </a>
            <a href="/visrs/audit_logs/" class="<?= $activePage === 'audit_logs' ? 'active' : '' ?>">
                <span class="sidebar-icon"></span>
                <span class="sidebar-text" data-i18n="audit_trail">Audit Trail</span>
            </a>
        <?php endif; ?>

        <!-- LOGOUT -->
        <a href="/visrs/logout.php">
            <span class="sidebar-icon"></span>
            <span class="sidebar-text" data-i18n="logout">Logout</span>
        </a>
    </nav>

</aside>
