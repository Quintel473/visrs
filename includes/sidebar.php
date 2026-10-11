<?php
$activePage = $activePage ?? "";
?>

<aside class="sidebar">

    <!-- =====================================================
         VISRS LOGO (clickable — links to Dashboard)
         Shows full branding when expanded, single letter when collapsed
         ===================================================== -->
    <a href="/visrs/dashboard.php" class="sidebar-logo">

        <h2 class="logo-full">
            VISRS
        </h2>

        <span class="logo-short" aria-hidden="true">
            V
        </span>

        <p data-i18n="vehicle_information_system">
            Vehicle Information System
        </p>

    </a>

    <!-- =====================================================
         COLLAPSE SIDEBAR BUTTON (always visible, inside sidebar)
         ===================================================== -->
    <button
        type="button"
        id="sidebar-toggle"
        class="sidebar-toggle"
        aria-label="Toggle sidebar"
        aria-pressed="false"
        title="Toggle sidebar"
    >
        <span aria-hidden="true">☰</span>
    </button>

    <!-- =====================================================
         NAVIGATION
         ===================================================== -->
    <nav class="sidebar-nav">

        <!-- DASHBOARD -->
        <a href="/visrs/dashboard.php" class="<?= $activePage === 'dashboard' ? 'active' : '' ?>" title="Dashboard">
            <span class="sidebar-icon">📊</span>
            <span class="sidebar-text" data-i18n="dashboard">Dashboard</span>
        </a>

        <!-- VEHICLE SEARCH -->
        <a href="/visrs/search.php" class="<?= $activePage === 'search' ? 'active' : '' ?>" title="Vehicle Search">
            <span class="sidebar-icon">🔍</span>
            <span class="sidebar-text" data-i18n="vehicle_search">Vehicle Search</span>
        </a>

        <!-- VEHICLES -->
        <a href="/visrs/vehicles/" class="<?= $activePage === 'vehicles' ? 'active' : '' ?>" title="Vehicles">
            <span class="sidebar-icon">🚗</span>
            <span class="sidebar-text" data-i18n="vehicles">Vehicles</span>
        </a>

        <!-- OWNERS -->
        <a href="/visrs/owners/" class="<?= $activePage === 'owners' ? 'active' : '' ?>" title="Owners">
            <span class="sidebar-icon">👤</span>
            <span class="sidebar-text" data-i18n="owners">Owners</span>
        </a>

        <!-- OWNERSHIP HISTORY -->
        <a href="/visrs/ownership_history/" class="<?= $activePage === 'ownership_history' ? 'active' : '' ?>" title="Ownership History">
            <span class="sidebar-icon">📋</span>
            <span class="sidebar-text" data-i18n="ownership_history">Ownership History</span>
        </a>

        <!-- INSURANCE -->
        <a href="/visrs/insurance/" class="<?= $activePage === 'insurance' ? 'active' : '' ?>" title="Insurance">
            <span class="sidebar-icon">🛡️</span>
            <span class="sidebar-text" data-i18n="insurance">Insurance</span>
        </a>

        <!-- ACCIDENTS -->
        <a href="/visrs/accidents/" class="<?= $activePage === 'accidents' ? 'active' : '' ?>" title="Accidents">
            <span class="sidebar-icon">⚠️</span>
            <span class="sidebar-text" data-i18n="accidents">Accidents</span>
        </a>

        <!-- MARKETPLACE -->
        <a href="/visrs/marketplace/" class="<?= $activePage === 'marketplace' ? 'active' : '' ?>" title="Marketplace">
            <span class="sidebar-icon">🛒</span>
            <span class="sidebar-text" data-i18n="marketplace">Marketplace</span>
        </a>

        <!-- ADMINISTRATION -->
        <?php if (isset($_SESSION["Role"]) && $_SESSION["Role"] === "Admin"): ?>
            <a href="/visrs/users/" class="<?= $activePage === 'users' ? 'active' : '' ?>" title="User Management">
                <span class="sidebar-icon">👥</span>
                <span class="sidebar-text" data-i18n="user_management">User Management</span>
            </a>
            <a href="/visrs/audit_logs/" class="<?= $activePage === 'audit_logs' ? 'active' : '' ?>" title="Audit Trail">
                <span class="sidebar-icon">📜</span>
                <span class="sidebar-text" data-i18n="audit_trail">Audit Trail</span>
            </a>
        <?php endif; ?>

        <!-- MY SETTINGS (available to all logged-in users) -->
        <a href="/visrs/users/settings.php" class="<?= $activePage === 'settings' ? 'active' : '' ?>" title="My Settings">
            <span class="sidebar-icon">⚙️</span>
            <span class="sidebar-text" data-i18n="my_settings">My Settings</span>
        </a>

        <!-- LOGOUT -->
        <a href="/visrs/logout.php" title="Logout">
            <span class="sidebar-icon">🚪</span>
            <span class="sidebar-text" data-i18n="logout">Logout</span>
        </a>

    </nav>

</aside>