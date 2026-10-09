<?php

require_once "../includes/auth.php";
require_once "../includes/database.php";
require_once "../includes/functions.php";

$basePath = "../";
$activePage = "accidents";

$pageTitle = "Accident Records";
$pageSubtitle = "Manage and review vehicle accident records.";


/*
 * Pagination setup.
 */

$perPage = 25;

$countStmt = $pdo->query("SELECT COUNT(*) FROM accidents");
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

    ORDER BY
        a.AccidentDate DESC,
        a.AccidentID DESC

    LIMIT ? OFFSET ?
");

$stmt->bindValue(1, $pagination["perPage"], PDO::PARAM_INT);
$stmt->bindValue(2, $pagination["offset"],  PDO::PARAM_INT);
$stmt->execute();

$accidents = $stmt->fetchAll();


$canManage =
    isset($_SESSION["Role"]) &&
    in_array(
        $_SESSION["Role"],
        ["Admin", "Police"],
        true
    );


/*
 * Bulk Action Flash Message
 */

$bulkMessage = $_SESSION["bulk_message"] ?? null;
unset($_SESSION["bulk_message"]);


/*
 * Global <head>
 */

require_once "../includes/head.php";

?>

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


                    <?php if ($canManage): ?>

                        <a
                            href="add.php"
                            class="button"
                        >
                            + Add Accident
                        </a>

                    <?php endif; ?>

                </div>


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
                            No Accident Records
                        </h3>

                        <p>
                            There are currently no accident
                            records in the system.
                        </p>

                        <?php if ($canManage): ?>

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
                                                        class="button button-secondary"
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

                    <?php renderPagination($pagination); ?>


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