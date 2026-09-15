<?php

$basePath = $basePath ?? "";
$activePage = $activePage ?? "";

?>

<aside class="sidebar">

    <div class="sidebar-logo">
        <h2>VISRS</h2>
        <p>Vehicle Information System</p>
    </div>

    <nav class="sidebar-nav">

        <a
            href="<?= $basePath ?>dashboard.php"
            class="<?= $activePage === 'dashboard' ? 'active' : '' ?>"
        >
            <span></span>
            <span>Dashboard</span>
        </a>

        <a
            href="<?= $basePath ?>search.php"
            class="<?= $activePage === 'search' ? 'active' : '' ?>"
        >
            <span></span>
            <span>Vehicle Search</span>
        </a>

        <a
            href="<?= $basePath ?>vehicles/"
            class="<?= $activePage === 'vehicles' ? 'active' : '' ?>"
        >
            <span></span>
            <span>Vehicles</span>
        </a>

        <a
            href="<?= $basePath ?>owners/"
            class="<?= $activePage === 'owners' ? 'active' : '' ?>"
        >
            <span></span>
            <span>Owners</span>
        </a>

        <a
            href="<?= $basePath ?>ownership_history/"
            class="<?= $activePage === 'ownership_history' ? 'active' : '' ?>"
        >
            <span></span>
            <span>Ownership History</span>
        </a>

        <a
            href="<?= $basePath ?>insurance/"
            class="<?= $activePage === 'insurance' ? 'active' : '' ?>"
        >
            <span></span>
            <span>Insurance</span>
        </a>

        <a
            href="<?= $basePath ?>accidents/"
            class="<?= $activePage === 'accidents' ? 'active' : '' ?>"
        >
            <span></span>
            <span>Accidents</span>
        </a>

    <?php if (isset($_SESSION["Role"]) && $_SESSION["Role"] === "Admin"): ?>

        <a
            href="<?= $basePath ?>users/"
            class="<?= $activePage === 'users' ? 'active' : '' ?>"
        >
            <span></span>
            <span>User Management</span>
        </a>

        <a
            href="<?= $basePath ?>audit_logs/"
            class="<?= $activePage === 'audit_logs' ? 'active' : '' ?>"
        >
            <span></span>
            <span>Audit Trail</span>
        </a>

    <?php endif; ?>

        <a
            href="<?= $basePath ?>logout.php"
        >
            <span></span>
            <span>Logout</span>
        </a>

    </nav>

</aside>