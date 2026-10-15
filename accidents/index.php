<?php

require_once "../includes/auth.php";
require_once "../includes/database.php";
require_once "../includes/functions.php";

$basePath = "../";
$activePage = "accidents";

$pageTitle = "Accident Records";
$pageSubtitle = "Manage and review vehicle accident records.";


/*
 * Filter state.
 */

$filterFrom         = trim($_GET["from"]         ?? "");
$filterTo           = trim($_GET["to"]           ?? "");
$filterDamage       = trim($_GET["damage"]       ?? "");
$filterVehicle      = trim($_GET["vehicle"]      ?? "");

$validDamageLevels = ["Minor", "Moderate", "Major", "Severe"];

if (!in_array($filterDamage, $validDamageLevels, true)) {
    $filterDamage = "";
}

/* Basic date sanity check */
if ($filterFrom !== "" && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $filterFrom)) {
    $filterFrom = "";
}

if ($filterTo !== "" && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $filterTo)) {
    $filterTo = "";
}


/*
 * Build WHERE clause.
 */

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
    $vehicleLike   = "%" . $filterVehicle . "%";
    $whereParams[] = $vehicleLike;
    $whereParams[] = $vehicleLike;
    $whereParams[] = $vehicleLike;
    $whereParams[] = $vehicleLike;
}

$whereSql = $whereParts
    ? "WHERE " . implode(" AND ", $whereParts)
    : "";


/*
 * Pagination setup.
 */

$perPage = 25;

$countStmt = $pdo->prepare("
    SELECT COUNT(*)
    FROM accidents a
    INNER JOIN vehicles v ON a.VehicleID = v.VehicleID
    {$whereSql}
");

$countStmt->execute($whereParams);
$totalAccidents = (int) $countStmt->fetchColumn();

$pagination = paginationMeta($totalAccidents, $perPage);


/*
 * Load accident records for current page.
 */

$stmt = $pdo->prepare("
    SELECT
        a.AccidentID,
        a.VehicleID,
        a.AccidentDate,
        a.Location,
        a.Description,
        a.DamageLevel,
        a.ReportNumber,

        v.PlateNumber,
        v.VIN,
        v.Make,
        v.Model,
        v.VehicleYear

    FROM accidents a

    INNER JOIN vehicles v
        ON a.VehicleID = v.VehicleID

    {$whereSql}

    ORDER BY
        a.AccidentDate DESC,
        a.AccidentID DESC

    LIMIT ? OFFSET ?
");

$paramIndex = 1;

foreach ($whereParams as $p) {
    $stmt->bindValue($paramIndex++, $p);
}

$stmt->bindValue($paramIndex++, $pagination["perPage"], PDO::PARAM_INT);
$stmt->bindValue($paramIndex++, $pagination["offset"],  PDO::PARAM_INT);
$stmt->execute();

$accidents = $stmt->fetchAll();


$canManage =
    isset($_SESSION["Role"]) &&
    in_array(
        $_SESSION["Role"],
        ["Admin", "Police"],
        true
    );


/* Any filters active? Used to show a "Clear filters" link. */

$hasFilters =
    $filterFrom    !== "" ||
    $filterTo      !== "" ||
    $filterDamage  !== "" ||
    $filterVehicle !== "";


/*
 * Bulk Action Flash Message
 */

$bulkMessage = $_SESSION["bulk_message"] ?? null;
unset($_SESSION["bulk_message"]);


/*
 * Query string to preserve filters across pagination.
 */

$filterQuery =
    ($filterFrom    !== "" ? "&from="    . urlencode($filterFrom)    : "") .
    ($filterTo      !== "" ? "&to="      . urlencode($filterTo)      : "") .
    ($filterDamage  !== "" ? "&damage="  . urlencode($filterDamage)  : "") .
    ($filterVehicle !== "" ? "&vehicle=" . urlencode($filterVehicle) : "");


/*
 * Same string, but starting with "?" for use in href attributes
 * on the export buttons.
 */

$exportQuery = $filterQuery !== ""
    ? "?" . ltrim($filterQuery, "&")
    : "";


/*
 * Global <head>
 */

require_once "../includes/head.php";

?>

<style>

    .filter-bar {
        display: grid;
        grid-template-columns: 1.1fr 1.1fr 1fr 1.4fr auto;
        gap: 12px;
        align-items: end;

        background: #f8fafc;
        border: 1px solid #e5e7eb;
        border-radius: 10px;

        padding: 16px;
        margin-bottom: 20px;
    }

    .filter-field {
        display: flex;
        flex-direction: column;
        min-width: 0;
    }

    .filter-field label {
        margin-bottom: 6px;
        font-size: 12px;
        font-weight: 600;
        color: #374151;
    }

    .filter-field input,
    .filter-field select {
        width: 100%;
        padding: 9px 11px;
        font-size: 13px;
        background: #ffffff;
        color: #111827;
        border: 1px solid #d1d5db;
        border-radius: 7px;
        font-family: inherit;
    }

    .filter-field input:focus,
    .filter-field select:focus {
        outline: none;
        border-color: #2563eb;
        box-shadow: 0 0 0 3px rgba(37,99,235,0.10);
    }

    .filter-actions {
        display: flex;
        gap: 8px;
        align-items: center;
    }

    .filter-actions .button {
        padding: 9px 14px;
        font-size: 13px;
        white-space: nowrap;
    }

    .filter-summary {
        display: flex;
        align-items: center;
        gap: 10px;
        margin-bottom: 16px;
        font-size: 13px;
        color: #64748b;
    }

    .filter-summary a {
        color: #2563eb;
        font-weight: 600;
        text-decoration: none;
    }

    .filter-summary a:hover {
        text-decoration: underline;
    }

    /* Dark mode */

    [data-theme="dark"] .filter-bar {
        background: #0f172a;
        border-color: #334155;
    }

    [data-theme="dark"] .filter-field label {
        color: #cbd5e1;
    }

    [data-theme="dark"] .filter-field input,
    [data-theme="dark"] .filter-field select {
        background: #1e293b;
        color: #e2e8f0;
        border-color: #334155;
    }

    [data-theme="dark"] .filter-summary {
        color: #94a3b8;
    }

    [data-theme="dark"] .filter-summary a {
        color: #60a5fa;
    }

    @media (max-width: 900px) {

        .filter-bar {
            grid-template-columns: 1fr 1fr;
        }

        .filter-actions {
            grid-column: 1 / -1;
        }

    }

    @media (max-width: 600px) {

        .filter-bar {
            grid-template-columns: 1fr;
        }

    }

</style>

<body>

<div class="layout">

    <?php require_once "../includes/sidebar.php"; ?>

    <main class="main-content">

        <?php include __DIR__ . "/../includes/header.php"; ?>

        <div class="content">

            <div class="card">

                <?php if (
                    isset($_GET["deleted"]) &&
                    $_GET["deleted"] === "1"
                ): ?>

                    <div
                        class="auto-dismiss"
                        style="
                            background:#dcfce7;
                            color:#166534;
                            padding:14px;
                            border-radius:8px;
                            margin-bottom:20px;
                        "
                    >
                        Accident record deleted successfully.
                    </div>

                <?php endif; ?>


                <!-- BULK ACTION MESSAGE -->

                <?php if ($bulkMessage): ?>

                    <?php
                    $bulkBg = "#fef3c7";
                    $bulkColor = "#92400e";

                    if ($bulkMessage["type"] === "success") {
                        $bulkBg = "#dcfce7";
                        $bulkColor = "#166534";
                    } elseif ($bulkMessage["type"] === "error") {
                        $bulkBg = "#fee2e2";
                        $bulkColor = "#991b1b";
                    }
                    ?>

                    <div
                        class="auto-dismiss"
                        style="
                            background:<?= $bulkBg ?>;
                            color:<?= $bulkColor ?>;
                            padding:14px;
                            border-radius:8px;
                            margin-bottom:20px;
                        "
                    >

                        <?= htmlspecialchars($bulkMessage["text"]) ?>

                    </div>

                <?php endif; ?>


                <div
                    style="
                        display:flex;
                        justify-content:space-between;
                        align-items:center;
                        margin-bottom:20px;
                        gap:20px;
                        flex-wrap:wrap;
                    "
                >

                    <div>

                        <h2>
                            Accident Records
                        </h2>

                        <p>
                            <?= $pagination["totalRows"] ?>
                            accident record(s) found.
                        </p>

                    </div>


                    <div
                        style="
                            display:flex;
                            gap:10px;
                            flex-wrap:wrap;
                        "
                    >

                        <!-- EXPORT CSV -->

                        <a
                            href="export_csv.php<?= htmlspecialchars($exportQuery) ?>"
                            class="button button-secondary"
                            title="Download the current list as a CSV file"
                        >
                            📄 Export CSV
                        </a>


                        <!-- EXPORT PDF -->

                        <a
                            href="export_pdf.php<?= htmlspecialchars($exportQuery) ?>"
                            target="_blank"
                            class="button button-secondary"
                            title="Open a printable view of the current list"
                        >
                            🖨 Export PDF
                        </a>


                        <?php if ($canManage): ?>

                            <a
                                href="add.php"
                                class="button"
                            >
                                + Add Accident
                            </a>

                        <?php endif; ?>

                    </div>

                </div>


                <!-- FILTER BAR -->

                <form
                    method="GET"
                    action=""
                    class="filter-bar"
                >

                    <div class="filter-field">

                        <label for="from">From Date</label>

                        <input
                            type="date"
                            id="from"
                            name="from"
                            value="<?= htmlspecialchars($filterFrom) ?>"
                        >

                    </div>


                    <div class="filter-field">

                        <label for="to">To Date</label>

                        <input
                            type="date"
                            id="to"
                            name="to"
                            value="<?= htmlspecialchars($filterTo) ?>"
                        >

                    </div>


                    <div class="filter-field">

                        <label for="damage">Damage Level</label>

                        <select id="damage" name="damage">

                            <option value="">All levels</option>

                            <?php foreach ($validDamageLevels as $level): ?>

                                <option
                                    value="<?= htmlspecialchars($level) ?>"
                                    <?= $filterDamage === $level ? "selected" : "" ?>
                                >
                                    <?= htmlspecialchars($level) ?>
                                </option>

                            <?php endforeach; ?>

                        </select>

                    </div>


                    <div class="filter-field">

                        <label for="vehicle">Vehicle (plate, make, or model)</label>

                        <input
                            type="text"
                            id="vehicle"
                            name="vehicle"
                            placeholder="e.g. ABC-123, Toyota, Corolla"
                            value="<?= htmlspecialchars($filterVehicle) ?>"
                            maxlength="100"
                        >

                    </div>


                    <div class="filter-actions">

                        <button
                            type="submit"
                            class="button"
                        >
                            Apply
                        </button>

                        <?php if ($hasFilters): ?>

                            <a
                                href="index.php"
                                class="button button-secondary"
                            >
                                Clear
                            </a>

                        <?php endif; ?>

                    </div>

                </form>


                <?php if ($hasFilters): ?>

                    <div class="filter-summary">

                        <span>

                            <?= $pagination["totalRows"] ?>
                            result(s) match your filters.

                        </span>

                        <a href="index.php">Reset all filters</a>

                    </div>

                <?php endif; ?>


                <?php if (empty($accidents)): ?>

                    <div
                        style="
                            padding:30px;
                            text-align:center;
                            background:#f8fafc;
                            border-radius:8px;
                        "
                    >

                        <h3>
                            <?= $hasFilters ? "No Matching Accidents" : "No Accident Records" ?>
                        </h3>

                        <p>

                            <?php if ($hasFilters): ?>

                                No accidents match the current
                                filter criteria. Try adjusting
                                or clearing the filters.

                            <?php else: ?>

                                There are currently no accident
                                records in the system.

                            <?php endif; ?>

                        </p>


                        <?php if ($hasFilters): ?>

                            <a
                                href="index.php"
                                class="button"
                                style="margin-top:10px;"
                            >
                                Clear Filters
                            </a>

                        <?php elseif ($canManage): ?>

                            <a
                                href="add.php"
                                class="button"
                                style="margin-top:10px;"
                            >
                                Add First Accident Record
                            </a>

                        <?php endif; ?>

                    </div>

                <?php else: ?>


                    <form
                        method="POST"
                        action="bulk_delete.php"
                        id="bulkForm"
                    >

                        <input
                            type="hidden"
                            name="csrf_token"
                            value="<?= htmlspecialchars(generateCSRFToken()) ?>"
                        >


                        <!-- BULK ACTION BAR -->

                        <?php if ($canManage): ?>

                            <div
                                class="bulk-action-bar"
                                id="bulkActionBar"
                            >

                                <span class="bulk-action-count">
                                    <strong id="bulkSelectedCount">0</strong>
                                    selected
                                </span>


                                <div class="bulk-action-buttons">

                                    <button
                                        type="button"
                                        class="button button-secondary"
                                        id="bulkClearSelection"
                                    >
                                        Clear
                                    </button>


                                    <button
                                        type="submit"
                                        class="button"
                                        style="background:#991b1b;"
                                        onclick="return confirmBulkDelete();"
                                    >
                                        Delete Selected
                                    </button>

                                </div>

                            </div>

                        <?php endif; ?>


                        <div
                            style="
                                overflow-x:auto;
                            "
                        >

                            <table
                                style="
                                    width:100%;
                                    border-collapse:collapse;
                                "
                            >

                                <thead>

                                    <tr>

                                        <?php if ($canManage): ?>

                                            <th
                                                style="
                                                    text-align:left;
                                                    padding:12px;
                                                    width:36px;
                                                "
                                            >

                                                <input
                                                    type="checkbox"
                                                    id="selectAll"
                                                    aria-label="Select all accident records"
                                                >

                                            </th>

                                        <?php endif; ?>


                                        <th
                                            style="
                                                text-align:left;
                                                padding:12px;
                                            "
                                        >
                                            Date
                                        </th>

                                        <th
                                            style="
                                                text-align:left;
                                                padding:12px;
                                            "
                                        >
                                            Vehicle
                                        </th>

                                        <th
                                            style="
                                                text-align:left;
                                                padding:12px;
                                            "
                                        >
                                            Location
                                        </th>

                                        <th
                                            style="
                                                text-align:left;
                                                padding:12px;
                                            "
                                        >
                                            Damage Level
                                        </th>

                                        <th
                                            style="
                                                text-align:left;
                                                padding:12px;
                                            "
                                        >
                                            Report Number
                                        </th>

                                        <th
                                            style="
                                                text-align:left;
                                                padding:12px;
                                            "
                                        >
                                            Actions
                                        </th>

                                    </tr>

                                </thead>

                                <tbody>

                                    <?php foreach (
                                        $accidents
                                        as $accident
                                    ): ?>

                                        <tr>


                                            <?php if ($canManage): ?>

                                                <td
                                                    style="
                                                        padding:12px;
                                                    "
                                                >

                                                    <input
                                                        type="checkbox"
                                                        name="selected_ids[]"
                                                        value="<?= (int) $accident["AccidentID"] ?>"
                                                        class="row-checkbox"
                                                        aria-label="Select this accident record"
                                                    >

                                                </td>

                                            <?php endif; ?>


                                            <td
                                                style="
                                                    padding:12px;
                                                "
                                            >

                                                <?= htmlspecialchars(
                                                    date(
                                                        "M j, Y",
                                                        strtotime(
                                                            $accident["AccidentDate"]
                                                        )
                                                    )
                                                ) ?>

                                            </td>


                                            <td
                                                style="
                                                    padding:12px;
                                                "
                                            >

                                                <strong>

                                                    <?= htmlspecialchars(
                                                        $accident["PlateNumber"]
                                                    ) ?>

                                                </strong>

                                                <br>

                                                <small>

                                                    <?= htmlspecialchars(
                                                        $accident["Make"]
                                                    ) ?>

                                                    <?= htmlspecialchars(
                                                        $accident["Model"]
                                                    ) ?>

                                                    <?= htmlspecialchars(
                                                        "(" .
                                                        $accident["VehicleYear"] .
                                                        ")"
                                                    ) ?>

                                                </small>

                                            </td>


                                            <td
                                                style="
                                                    padding:12px;
                                                "
                                            >

                                                <?= $accident["Location"]
                                                    ? htmlspecialchars(
                                                        $accident["Location"]
                                                    )
                                                    : "Not specified"
                                                ?>

                                            </td>


                                            <td
                                                style="
                                                    padding:12px;
                                                "
                                            >

                                                <?= htmlspecialchars(
                                                    $accident["DamageLevel"]
                                                ) ?>

                                            </td>


                                            <td
                                                style="
                                                    padding:12px;
                                                "
                                            >

                                                <?= $accident["ReportNumber"]
                                                    ? htmlspecialchars(
                                                        $accident["ReportNumber"]
                                                    )
                                                    : "Not specified"
                                                ?>

                                            </td>


                                            <td
                                                style="
                                                    padding:12px;
                                                    white-space:nowrap;
                                                "
                                            >

                                                <a
                                                    href="view.php?id=<?= (int) $accident["AccidentID"] ?>"
                                                    class="button button-secondary"
                                                >
                                                    View
                                                </a>


                                                <?php if ($canManage): ?>

                                                    <a
                                                        href="edit.php?id=<?= (int) $accident["AccidentID"] ?>"
                                                        class="button"
                                                    >
                                                        Edit
                                                    </a>

                                                    <a
                                                        href="delete.php?id=<?= (int) $accident["AccidentID"] ?>"
                                                        class="button"
                                                        style="
                                                            background:#991b1b;
                                                            margin-left:5px;
                                                        "
                                                    >
                                                        Delete
                                                    </a>

                                                <?php endif; ?>

                                            </td>

                                        </tr>

                                    <?php endforeach; ?>

                                </tbody>

                            </table>

                        </div>

                    </form>

                    <?php

                    /*
                     * Preserve filters across pagination links.
                     */

                    $pagination["extraQuery"] = $filterQuery;

                    renderPagination($pagination);

                    ?>


                <?php endif; ?>

            </div>

        </div>

    </main>

</div>


<?php require_once "../includes/footer.php"; ?>


<script>

function confirmBulkDelete() {

    var count = document.querySelectorAll(".row-checkbox:checked").length;

    if (count === 0) {
        alert("Please select at least one accident record to delete.");
        return false;
    }

    return confirm(
        "Delete " + count + " selected accident record(s)?\n\n" +
        "This action cannot be undone."
    );
}

</script>