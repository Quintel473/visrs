<?php

/*
 * VISRS — Accident Records (Printable)
 *
 * Renders a clean, printable page of accident records.
 * Honours the same filters as the list page. Uses the
 * browser's print dialog to save as PDF.
 */

require_once __DIR__ . "/../includes/auth.php";
require_once __DIR__ . "/../includes/database.php";
require_once __DIR__ . "/../includes/functions.php";


/* ---------- Read filters ---------- */

$filterFrom    = trim($_GET["from"]    ?? "");
$filterTo      = trim($_GET["to"]      ?? "");
$filterDamage  = trim($_GET["damage"]  ?? "");
$filterVehicle = trim($_GET["vehicle"] ?? "");

$validDamageLevels = ["Minor", "Moderate", "Major", "Severe"];

if (!in_array($filterDamage, $validDamageLevels, true)) {
    $filterDamage = "";
}

if ($filterFrom !== "" && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $filterFrom)) {
    $filterFrom = "";
}

if ($filterTo !== "" && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $filterTo)) {
    $filterTo = "";
}


/* ---------- Build WHERE clause ---------- */

$whereParts  = [];
$whereParams = [];

if ($filterFrom !== "") {
    $whereParts[]  = "a.AccidentDate >= ?";
    $whereParams[] = $filterFrom;
}

if ($filterTo !== "") {
    $whereParts[]  = "a.AccidentDate <= ?";
    $whereParams[] = $filterTo;
}

if ($filterDamage !== "") {
    $whereParts[]  = "a.DamageLevel = ?";
    $whereParams[] = $filterDamage;
}

if ($filterVehicle !== "") {
    $whereParts[]  = "(
        v.PlateNumber LIKE ?
        OR v.Make LIKE ?
        OR v.Model LIKE ?
        OR CONCAT(v.Make, ' ', v.Model) LIKE ?
    )";
    $like = "%" . $filterVehicle . "%";
    $whereParams[] = $like;
    $whereParams[] = $like;
    $whereParams[] = $like;
    $whereParams[] = $like;
}

$whereSql = $whereParts
    ? "WHERE " . implode(" AND ", $whereParts)
    : "";


/* ---------- Fetch all matching records ---------- */

$stmt = $pdo->prepare("
    SELECT
        a.AccidentID,
        a.AccidentDate,
        a.Location,
        a.DamageLevel,
        a.ReportNumber,
        a.Description,

        v.PlateNumber,
        v.VIN,
        v.Make,
        v.Model,
        v.VehicleYear

    FROM accidents a
    INNER JOIN vehicles v ON a.VehicleID = v.VehicleID

    {$whereSql}

    ORDER BY a.AccidentDate DESC, a.AccidentID DESC
");

$stmt->execute($whereParams);
$rows = $stmt->fetchAll();


/* ---------- Log the export ---------- */

logAudit(
    $pdo,
    "Exported accident records to PDF (" . count($rows) . " rows)",
    "accidents",
    0
);


/* ---------- Build a description of the filters for the header ---------- */

$filterLabels = [];

if ($filterFrom !== "")    $filterLabels[] = "From: " . $filterFrom;
if ($filterTo !== "")      $filterLabels[] = "To: " . $filterTo;
if ($filterDamage !== "")  $filterLabels[] = "Damage: " . $filterDamage;
if ($filterVehicle !== "") $filterLabels[] = "Vehicle: " . $filterVehicle;

$filterSummary = $filterLabels
    ? implode(" · ", $filterLabels)
    : "No filters applied";

$basePath  = "../";
$pageTitle = "Accident Records — Print View";

require_once "../includes/head.php";

?>

<style>

    body {
        background: #ffffff;
    }

    .print-page {
        max-width: 1100px;
        margin: 0 auto;
        padding: 30px 40px 60px 40px;
    }

    .print-header {
        border-bottom: 3px solid #111827;
        padding-bottom: 16px;
        margin-bottom: 24px;
    }

    .print-header h1 {
        margin: 0 0 6px 0;
        font-size: 26px;
        color: #111827;
    }

    .print-header .print-meta {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 20px;
        flex-wrap: wrap;
        font-size: 13px;
        color: #6b7280;
    }

    .print-filters {
        background: #f8fafc;
        border: 1px solid #e5e7eb;
        border-radius: 8px;
        padding: 12px 16px;
        margin-bottom: 24px;
        font-size: 13px;
        color: #374151;
    }

    .print-filters strong {
        color: #111827;
    }

    .print-actions {
        display: flex;
        gap: 10px;
        margin-bottom: 24px;
        flex-wrap: wrap;
    }

    .print-table {
        width: 100%;
        border-collapse: collapse;
        font-size: 12px;
    }

    .print-table th {
        background: #f3f4f6;
        color: #111827;
        padding: 10px 12px;
        border: 1px solid #d1d5db;
        text-align: left;
        font-weight: 700;
        font-size: 11px;
        text-transform: uppercase;
        letter-spacing: 0.3px;
        white-space: nowrap;
    }

    .print-table td {
        padding: 10px 12px;
        border: 1px solid #e5e7eb;
        vertical-align: top;
        color: #374151;
    }

    .print-table tr:nth-child(even) td {
        background: #f9fafb;
    }

    .print-table .damage-cell {
        white-space: nowrap;
        font-weight: 600;
    }

    .damage-minor    { color: #166534; }
    .damage-moderate { color: #92400e; }
    .damage-major    { color: #9a3412; }
    .damage-severe   { color: #991b1b; }

    .print-footer {
        margin-top: 30px;
        padding-top: 14px;
        border-top: 1px solid #e5e7eb;
        font-size: 11px;
        color: #9ca3af;
        text-align: center;
    }

    @media print {

        .print-actions,
        .sidebar,
        .topbar,
        footer {
            display: none !important;
        }

        .print-page {
            padding: 0;
            max-width: 100%;
        }

        body {
            font-size: 11px;
        }

        .print-table th,
        .print-table td {
            padding: 6px 8px;
        }

        .print-table {
            font-size: 10px;
        }

        a {
            color: #111827;
            text-decoration: none;
        }
    }

    @page {
        margin: 15mm;
    }

</style>

<body>

<div class="print-page">

    <!-- Header -->

    <div class="print-header">

        <h1>
            VISRS — Accident Records Report
        </h1>

        <div class="print-meta">

            <span>
                Generated on
                <strong><?= date("F j, Y \a\\t g:i A") ?></strong>
            </span>

            <span>
                <strong><?= count($rows) ?></strong>
                record(s) included
            </span>

        </div>

    </div>


    <!-- Filters summary -->

    <div class="print-filters">

        <strong>Filters applied:</strong>

        <?= htmlspecialchars($filterSummary) ?>

    </div>


    <!-- Action buttons (hidden when printing) -->

    <div class="print-actions">

        <button
            type="button"
            class="button"
            onclick="window.print()"
        >
            🖨 Print / Save as PDF
        </button>

        <a
            href="index.php"
            class="button button-secondary"
        >
            Back to Accident List
        </a>

    </div>


    <!-- Records table -->

    <?php if (empty($rows)): ?>

        <div
            style="
                padding:40px;
                text-align:center;
                background:#f8fafc;
                border:1px dashed #d1d5db;
                border-radius:10px;
                color:#6b7280;
            "
        >
            No accident records match the current filters.
        </div>

    <?php else: ?>

        <table class="print-table">

            <thead>

                <tr>

                    <th>ID</th>
                    <th>Date</th>
                    <th>Plate</th>
                    <th>Vehicle</th>
                    <th>Location</th>
                    <th>Damage</th>
                    <th>Report #</th>
                    <th>Description</th>

                </tr>

            </thead>

            <tbody>

                <?php foreach ($rows as $r): ?>

                    <?php
                    $damageKey = strtolower($r["DamageLevel"]);
                    ?>

                    <tr>

                        <td>
                            #<?= (int) $r["AccidentID"] ?>
                        </td>

                        <td>
                            <?= htmlspecialchars(
                                date("M j, Y", strtotime($r["AccidentDate"]))
                            ) ?>
                        </td>

                        <td>
                            <strong>
                                <?= htmlspecialchars($r["PlateNumber"]) ?>
                            </strong>
                        </td>

                        <td>
                            <?= htmlspecialchars(
                                $r["Make"] . " " . $r["Model"]
                                . " (" . $r["VehicleYear"] . ")"
                            ) ?>
                        </td>

                        <td>
                            <?= $r["Location"]
                                ? htmlspecialchars($r["Location"])
                                : "—"
                            ?>
                        </td>

                        <td class="damage-cell damage-<?= htmlspecialchars($damageKey) ?>">
                            <?= htmlspecialchars($r["DamageLevel"]) ?>
                        </td>

                        <td>
                            <?= $r["ReportNumber"]
                                ? htmlspecialchars($r["ReportNumber"])
                                : "—"
                            ?>
                        </td>

                        <td>
                            <?= $r["Description"]
                                ? htmlspecialchars($r["Description"])
                                : "—"
                            ?>
                        </td>

                    </tr>

                <?php endforeach; ?>

            </tbody>

        </table>

    <?php endif; ?>


    <!-- Footer -->

    <div class="print-footer">

        VISRS — Vehicle Information Search &amp; Retrieval System
        &nbsp;·&nbsp;
        Printed <?= date("Y-m-d H:i") ?>

    </div>

</div>


<script>

/* Auto-open the print dialog when the page finishes loading. */
window.addEventListener("load", function () {
    setTimeout(function () { window.print(); }, 400);
});

</script>

</body>

</html>