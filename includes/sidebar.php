<?php
$activePage = $activePage ?? "";

// Get the current user's role to control menu visibility
$currentRole = $_SESSION["Role"] ?? "User";
$isStaff = in_array($currentRole, ['Admin', 'Police']);
?>

<aside class="sidebar" id="mobile-sidebar">

    <!-- MOBILE CLOSE BUTTON -->
    <button type="button" class="mobile-close-btn" id="mobile-close-btn" aria-label="Close menu">
        ✕
    </button>

    <!-- =====================================================
         VISRS LOGO (clickable — links to Dashboard)
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
            <span class="sidebar-icon">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="7"></rect><rect x="14" y="3" width="7" height="7"></rect><rect x="14" y="14" width="7" height="7"></rect><rect x="3" y="14" width="7" height="7"></rect></svg>
            </span>
            <span class="sidebar-text" data-i18n="dashboard">Dashboard</span>
        </a>

        <!-- VEHICLE SEARCH -->
        <a href="/visrs/search.php" class="<?= $activePage === 'search' ? 'active' : '' ?>" title="Vehicle Search">
            <span class="sidebar-icon">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
            </span>
            <span class="sidebar-text" data-i18n="vehicle_search">Vehicle Search</span>
        </a>

        <!-- VEHICLES -->
        <a href="/visrs/vehicles/" class="<?= $activePage === 'vehicles' ? 'active' : '' ?>" title="Vehicles">
            <span class="sidebar-icon">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 17h2c.6 0 1-.4 1-1v-3c0-.9-.7-1.7-1.5-1.9C18.7 10.6 16 10 16 10s-1.3-1.4-2.2-2.3c-.5-.4-1.1-.7-1.8-.7H5c-.6 0-1.1.4-1.4.9l-1.4 2.9A3.7 3.7 0 0 0 2 12v4c0 .6.4 1 1 1h2"></path><circle cx="7" cy="17" r="2"></circle><path d="M9 17h6"></path><circle cx="17" cy="17" r="2"></circle></svg>
            </span>
            <span class="sidebar-text" data-i18n="vehicles">Vehicles</span>
        </a>

        <!-- OWNERS (Only visible to Admin, Police, Seller) -->
        <?php if ($isStaff): ?>
            <a href="/visrs/owners/" class="<?= $activePage === 'owners' ? 'active' : '' ?>" title="Owners">
                <span class="sidebar-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
                </span>
                <span class="sidebar-text" data-i18n="owners">Owners</span>
            </a>
        <?php endif; ?>

        <!-- OWNERSHIP HISTORY -->
        <a href="/visrs/ownership_history/" class="<?= $activePage === 'ownership_history' ? 'active' : '' ?>" title="Ownership History">
            <span class="sidebar-icon">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line><polyline points="10 9 9 9 8 9"></polyline></svg>
            </span>
            <span class="sidebar-text" data-i18n="ownership_history">Ownership History</span>
        </a>

        <!-- INSURANCE -->
        <a href="/visrs/insurance/" class="<?= $activePage === 'insurance' ? 'active' : '' ?>" title="Insurance">
            <span class="sidebar-icon">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path></svg>
            </span>
            <span class="sidebar-text" data-i18n="insurance">Insurance</span>
        </a>

        <!-- ACCIDENTS -->
        <a href="/visrs/accidents/" class="<?= $activePage === 'accidents' ? 'active' : '' ?>" title="Accidents">
            <span class="sidebar-icon">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path><line x1="12" y1="9" x2="12" y2="13"></line><line x1="12" y1="17" x2="12.01" y2="17"></line></svg>
            </span>
            <span class="sidebar-text" data-i18n="accidents">Accidents</span>
        </a>

        <!-- MARKETPLACE -->
        <a href="/visrs/marketplace/" class="<?= $activePage === 'marketplace' ? 'active' : '' ?>" title="Marketplace">
            <span class="sidebar-icon">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="9" cy="21" r="1"></circle><circle cx="20" cy="21" r="1"></circle><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"></path></svg>
            </span>
            <span class="sidebar-text" data-i18n="marketplace">Marketplace</span>
        </a>

        <!-- ADMINISTRATION (Only visible to Admin) -->
        <?php if ($currentRole === "Admin"): ?>
            <a href="/visrs/users/" class="<?= $activePage === 'users' ? 'active' : '' ?>" title="User Management">
                <span class="sidebar-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>
                </span>
                <span class="sidebar-text" data-i18n="user_management">User Management</span>
            </a>
            <a href="/visrs/audit_logs/" class="<?= $activePage === 'audit_logs' ? 'active' : '' ?>" title="Audit Trail">
                <span class="sidebar-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line><polyline points="10 9 9 9 8 9"></polyline></svg>
                </span>
                <span class="sidebar-text" data-i18n="audit_trail">Audit Trail</span>
            </a>
        <?php endif; ?>

        <!-- MY SETTINGS (available to all logged-in users) -->
        <a href="/visrs/users/settings.php" class="<?= $activePage === 'settings' ? 'active' : '' ?>" title="My Settings">
            <span class="sidebar-icon">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="3"></circle><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"></path></svg>
            </span>
            <span class="sidebar-text" data-i18n="my_settings">My Settings</span>
        </a>

        <!-- LOGOUT -->
        <a href="/visrs/logout.php" title="Logout">
            <span class="sidebar-icon">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path><polyline points="16 17 21 12 16 7"></polyline><line x1="21" y1="12" x2="9" y2="12"></line></svg>
            </span>
            <span class="sidebar-text" data-i18n="logout">Logout</span>
        </a>

    </nav>

</aside>

<!-- MOBILE OVERLAY BACKDROP -->
<div class="mobile-menu-overlay" id="mobile-menu-overlay"></div>