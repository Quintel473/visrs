<?php

require_once __DIR__ . "/../includes/auth.php";
require_once __DIR__ . "/../includes/database.php";
require_once __DIR__ . "/../includes/functions.php";

requireRole([
    "Admin",
    "Seller"
]);

$pageTitle = "Buyer Inquiries";
$activePage = "marketplace";

$currentUserId = (int) currentUserId();
$currentRole = currentUserRole();


/*
|--------------------------------------------------------------------------
| Filters
|--------------------------------------------------------------------------
*/

$statusFilter = trim($_GET["status"] ?? "");

$allowedStatuses = [
    "Pending",
    "Contacted",
    "Accepted",
    "Declined",
    "Closed"
];

if (
    $statusFilter !== ""
    &&
    !in_array($statusFilter, $allowedStatuses, true)
) {
    $statusFilter = "";
}


/*
|--------------------------------------------------------------------------
| Build Inquiry Query
|--------------------------------------------------------------------------
*/

$sql = "
    SELECT
        vi.InterestID,
        vi.ListingID,
        vi.VehicleID,
        vi.BuyerID,
        vi.SellerID,
        vi.Message,
        vi.Status,
        vi.CreatedAt,
        vi.UpdatedAt,

        vl.Price,
        vl.Status AS ListingStatus,

        v.PlateNumber,
        v.VIN,
        v.Make,
        v.Model,
        v.VehicleYear,
        v.Color,
        v.VehicleType,

        buyer.FirstName AS BuyerFirstName,
        buyer.LastName AS BuyerLastName,
        buyer.Email AS BuyerEmail,

        seller.FirstName AS SellerFirstName,
        seller.LastName AS SellerLastName,
        seller.Email AS SellerEmail

    FROM vehicle_interests vi

    INNER JOIN vehicle_listings vl
        ON vi.ListingID = vl.ListingID

    INNER JOIN vehicles v
        ON vi.VehicleID = v.VehicleID

    INNER JOIN users buyer
        ON vi.BuyerID = buyer.UserID

    INNER JOIN users seller
        ON vi.SellerID = seller.UserID
";


$params = [];


/*
|--------------------------------------------------------------------------
| Seller sees only their inquiries
|--------------------------------------------------------------------------
*/

if ($currentRole === "Seller") {

    $sql .= "
        WHERE vi.SellerID = ?
    ";

    $params[] = $currentUserId;

}


/*
|--------------------------------------------------------------------------
| Admin sees all inquiries
|--------------------------------------------------------------------------
*/

if (
    $currentRole === "Admin"
) {

    $sql .= "
        WHERE 1 = 1
    ";

}


/*
|--------------------------------------------------------------------------
| Status Filter
|--------------------------------------------------------------------------
*/

if ($statusFilter !== "") {

    $sql .= "
        AND vi.Status = ?
    ";

    $params[] = $statusFilter;

}


/*
|--------------------------------------------------------------------------
| Sort
|--------------------------------------------------------------------------
*/

$sql .= "
    ORDER BY
        vi.UpdatedAt DESC,
        vi.CreatedAt DESC,
        vi.InterestID DESC
";


$stmt = $pdo->prepare($sql);

$stmt->execute($params);

$inquiries = $stmt->fetchAll();


/*
|--------------------------------------------------------------------------
| Summary Counts
|--------------------------------------------------------------------------
*/

$countSql = "
    SELECT
        COUNT(*) AS TotalCount,

        SUM(
            CASE
                WHEN vi.Status = 'Pending'
                THEN 1
                ELSE 0
            END
        ) AS PendingCount,

        SUM(
            CASE
                WHEN vi.Status = 'Contacted'
                THEN 1
                ELSE 0
            END
        ) AS ContactedCount,

        SUM(
            CASE
                WHEN vi.Status = 'Accepted'
                THEN 1
                ELSE 0
            END
        ) AS AcceptedCount

    FROM vehicle_interests vi
";


$countParams = [];


if ($currentRole === "Seller") {

    $countSql .= "
        WHERE vi.SellerID = ?
    ";

    $countParams[] = $currentUserId;

} else {

    $countSql .= "
        WHERE 1 = 1
    ";

}


$countStmt = $pdo->prepare($countSql);

$countStmt->execute($countParams);

$counts = $countStmt->fetch();


$totalCount =
    (int) ($counts["TotalCount"] ?? 0);

$pendingCount =
    (int) ($counts["PendingCount"] ?? 0);

$contactedCount =
    (int) ($counts["ContactedCount"] ?? 0);

$acceptedCount =
    (int) ($counts["AcceptedCount"] ?? 0);


/*
|--------------------------------------------------------------------------
| Flash Messages
|--------------------------------------------------------------------------
*/

$flashSuccess =
    $_SESSION["flash_success"] ?? "";

$flashError =
    $_SESSION["flash_error"] ?? "";

unset(
    $_SESSION["flash_success"],
    $_SESSION["flash_error"]
);

?>

<!DOCTYPE html>

<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>
        <?= e($pageTitle) ?> - VISRS
    </title>

    <link
        rel="stylesheet"
        href="/visrs/css/style.css"
    >

    <style>

        .inquiries-page {

            max-width:1200px;

            margin:0 auto;

        }


        .page-heading {

            margin-bottom:20px;

        }


        .page-heading h1 {

            margin:0 0 6px;

            font-size:25px;

            color:#111827;

        }


        .page-heading p {

            margin:0;

            color:#64748b;

            font-size:14px;

        }


        /*
        |--------------------------------------------------------------------------
        | SUMMARY
        |--------------------------------------------------------------------------
        */

        .summary-grid {

            display:grid;

            grid-template-columns:
                repeat(4, minmax(0, 1fr));

            gap:14px;

            margin-bottom:20px;

        }


        .summary-card {

            padding:18px;

            background:#ffffff;

            border:1px solid #e5e7eb;

            border-radius:10px;

            box-shadow:
                0 2px 7px
                rgba(0,0,0,0.04);

        }


        .summary-label {

            margin-bottom:6px;

            color:#94a3b8;

            font-size:10px;

            font-weight:700;

            text-transform:uppercase;

        }


        .summary-number {

            color:#111827;

            font-size:25px;

            font-weight:750;

        }


        /*
        |--------------------------------------------------------------------------
        | FILTER BAR
        |--------------------------------------------------------------------------
        */

        .filter-bar {

            display:flex;

            align-items:center;

            gap:8px;

            flex-wrap:wrap;

            margin-bottom:20px;

        }


        .filter-button {

            display:inline-block;

            padding:8px 12px;

            border:1px solid #d1d5db;

            border-radius:6px;

            background:#ffffff;

            color:#374151;

            font-size:12px;

            font-weight:600;

            text-decoration:none;

        }


        .filter-button:hover {

            background:#f8fafc;

        }


        .filter-button.active {

            background:#111827;

            border-color:#111827;

            color:#ffffff;

        }


        .clear-filter {

            color:#64748b;

            font-size:12px;

            text-decoration:none;

        }


        .clear-filter:hover {

            color:#111827;

        }


        /*
        |--------------------------------------------------------------------------
        | INQUIRY CARD
        |--------------------------------------------------------------------------
        */

        .inquiry-card {

            margin-bottom:16px;

            padding:20px;

            background:#ffffff;

            border:1px solid #e5e7eb;

            border-radius:10px;

            box-shadow:
                0 2px 7px
                rgba(0,0,0,0.04);

        }


        .inquiry-top {

            display:flex;

            justify-content:space-between;

            align-items:flex-start;

            gap:15px;

            margin-bottom:16px;

        }


        .vehicle-name {

            margin:0 0 5px;

            color:#111827;

            font-size:18px;

            font-weight:750;

        }


        .vehicle-meta {

            color:#64748b;

            font-size:12px;

        }


        /*
        |--------------------------------------------------------------------------
        | STATUS
        |--------------------------------------------------------------------------
        */

        .status-badge {

            display:inline-block;

            padding:5px 9px;

            border-radius:999px;

            font-size:11px;

            font-weight:700;

            white-space:nowrap;

        }


        .status-pending {

            background:#fef3c7;

            color:#92400e;

        }


        .status-contacted {

            background:#dbeafe;

            color:#1e40af;

        }


        .status-accepted {

            background:#dcfce7;

            color:#166534;

        }


        .status-declined {

            background:#fee2e2;

            color:#991b1b;

        }


        .status-closed {

            background:#e5e7eb;

            color:#374151;

        }


        /*
        |--------------------------------------------------------------------------
        | INFORMATION GRID
        |--------------------------------------------------------------------------
        */

        .inquiry-grid {

            display:grid;

            grid-template-columns:
                repeat(4, minmax(0, 1fr));

            gap:14px;

            padding:16px 0;

            border-top:1px solid #f1f5f9;

            border-bottom:1px solid #f1f5f9;

        }


        .inquiry-info-label {

            margin-bottom:4px;

            color:#94a3b8;

            font-size:10px;

            font-weight:700;

            text-transform:uppercase;

        }


        .inquiry-info-value {

            color:#334155;

            font-size:13px;

            font-weight:600;

        }


        /*
        |--------------------------------------------------------------------------
        | BUYER MESSAGE
        |--------------------------------------------------------------------------
        */

        .buyer-message {

            margin-top:16px;

            padding:14px;

            border-radius:7px;

            background:#f8fafc;

            color:#475569;

            font-size:13px;

            line-height:1.6;

        }


        .buyer-message-label {

            margin-bottom:6px;

            color:#94a3b8;

            font-size:10px;

            font-weight:700;

            text-transform:uppercase;

        }


        /*
        |--------------------------------------------------------------------------
        | CARD FOOTER
        |--------------------------------------------------------------------------
        */

        .inquiry-footer {

            display:flex;

            justify-content:space-between;

            align-items:center;

            gap:15px;

            margin-top:16px;

        }


        .submitted-date {

            color:#94a3b8;

            font-size:11px;

        }


        .inquiry-actions {

            display:flex;

            gap:8px;

            flex-wrap:wrap;

        }


        .action-button {

            display:inline-block;

            padding:8px 12px;

            border:1px solid #d1d5db;

            border-radius:6px;

            color:#374151;

            background:#ffffff;

            font-size:12px;

            font-weight:600;

            text-decoration:none;

        }


        .action-button:hover {

            background:#f8fafc;

        }


        .action-button.primary {

            background:#111827;

            border-color:#111827;

            color:#ffffff;

        }


        .action-button.primary:hover {

            background:#1f2937;

        }


        /*
        |--------------------------------------------------------------------------
        | EMPTY STATE
        |--------------------------------------------------------------------------
        */

        .empty-state {

            padding:55px 25px;

            background:#ffffff;

            border:1px solid #e5e7eb;

            border-radius:10px;

            text-align:center;

        }


        .empty-state h2 {

            margin:0 0 8px;

            color:#111827;

            font-size:20px;

        }


        .empty-state p {

            margin:0 auto 18px;

            max-width:520px;

            color:#64748b;

            font-size:13px;

            line-height:1.6;

        }


        /*
        |--------------------------------------------------------------------------
        | RESPONSIVE
        |--------------------------------------------------------------------------
        */

        @media (max-width:850px) {

            .summary-grid {

                grid-template-columns:
                    repeat(2, minmax(0, 1fr));

            }


            .inquiry-grid {

                grid-template-columns:
                    repeat(2, minmax(0, 1fr));

            }

        }


        @media (max-width:600px) {

            .summary-grid {

                grid-template-columns:1fr;

            }


            .inquiry-top {

                flex-direction:column;

            }


            .inquiry-grid {

                grid-template-columns:1fr;

            }


            .inquiry-footer {

                align-items:stretch;

                flex-direction:column;

            }


            .inquiry-actions {

                width:100%;

            }


            .action-button {

                text-align:center;

            }

        }

    </style>

</head>


<body>


<div class="layout">


    <?php
    require_once __DIR__ .
        "/../includes/sidebar.php";
    ?>


    <main class="main-content">


        <?php
        require_once __DIR__ .
            "/../includes/header.php";
        ?>


        <main class="content">


            <div class="inquiries-page">


                <!-- =================================================
                     PAGE HEADING
                     ================================================= -->

                <div class="page-heading">

                    <h1>
                        Buyer Inquiries
                    </h1>

                    <p>
                        Manage buyer interest and conversations
                        for vehicles listed through the marketplace.
                    </p>

                </div>


                <!-- =================================================
                     FLASH MESSAGES
                     ================================================= -->

                <?php if (
                    $flashSuccess !== ""
                ): ?>

                    <div class="alert alert-success">

                        <?= e($flashSuccess) ?>

                    </div>

                <?php endif; ?>


                <?php if (
                    $flashError !== ""
                ): ?>

                    <div class="alert alert-error">

                        <?= e($flashError) ?>

                    </div>

                <?php endif; ?>


                <!-- =================================================
                     SUMMARY
                     ================================================= -->

                <div class="summary-grid">


                    <div class="summary-card">

                        <div class="summary-label">
                            Total Inquiries
                        </div>

                        <div class="summary-number">
                            <?= $totalCount ?>
                        </div>

                    </div>


                    <div class="summary-card">

                        <div class="summary-label">
                            Pending
                        </div>

                        <div class="summary-number">
                            <?= $pendingCount ?>
                        </div>

                    </div>


                    <div class="summary-card">

                        <div class="summary-label">
                            Contacted
                        </div>

                        <div class="summary-number">
                            <?= $contactedCount ?>
                        </div>

                    </div>


                    <div class="summary-card">

                        <div class="summary-label">
                            Accepted
                        </div>

                        <div class="summary-number">
                            <?= $acceptedCount ?>
                        </div>

                    </div>


                </div>


                <!-- =================================================
                     FILTERS
                     ================================================= -->

                <div class="filter-bar">


                    <a
                        href="/visrs/marketplace/inquiries.php"
                        class="
                            filter-button
                            <?= $statusFilter === ""
                                ? "active"
                                : ""
                            ?>
                        "
                    >
                        All
                    </a>


                    <?php foreach (
                        $allowedStatuses
                        as $status
                    ): ?>


                        <a
                            href="/visrs/marketplace/inquiries.php?status=<?= urlencode($status) ?>"
                            class="
                                filter-button
                                <?= $statusFilter === $status
                                    ? "active"
                                    : ""
                                ?>
                            "
                        >
                            <?= e($status) ?>
                        </a>


                    <?php endforeach; ?>


                    <?php if (
                        $statusFilter !== ""
                    ): ?>

                        <a
                            href="/visrs/marketplace/inquiries.php"
                            class="clear-filter"
                        >
                            Clear Filter
                        </a>

                    <?php endif; ?>


                </div>


                <!-- =================================================
                     INQUIRIES
                     ================================================= -->

                <?php if (
                    count($inquiries) === 0
                ): ?>


                    <div class="empty-state">

                        <h2>
                            No Inquiries Found
                        </h2>

                        <p>

                            <?php if (
                                $currentRole === "Seller"
                                &&
                                $statusFilter === ""
                            ): ?>

                                You currently have no buyer
                                inquiries for your marketplace
                                listings.

                            <?php elseif (
                                $statusFilter !== ""
                            ): ?>

                                There are no inquiries matching
                                the selected status.

                            <?php else: ?>

                                There are currently no buyer
                                inquiries in the marketplace.

                            <?php endif; ?>

                        </p>


                        <a
                            href="/visrs/marketplace/"
                            class="action-button primary"
                        >
                            Back to Marketplace
                        </a>

                    </div>


                <?php else: ?>


                    <?php foreach (
                        $inquiries
                        as $inquiry
                    ): ?>


                        <?php

                        $statusClass =
                            strtolower(
                                $inquiry["Status"]
                            );

                        ?>


                        <div class="inquiry-card">


                            <!-- =====================================
                                 TOP
                                 ===================================== -->

                            <div class="inquiry-top">


                                <div>

                                    <h2 class="vehicle-name">

                                        <?= e(
                                            $inquiry["Make"]
                                            . " "
                                            . $inquiry["Model"]
                                            . " "
                                            . $inquiry["VehicleYear"]
                                        ) ?>

                                    </h2>


                                    <div class="vehicle-meta">

                                        Plate:
                                        <?= e(
                                            $inquiry["PlateNumber"]
                                        ) ?>

                                        &nbsp;•&nbsp;

                                        Type:
                                        <?= displayValue(
                                            $inquiry["VehicleType"]
                                        ) ?>

                                        &nbsp;•&nbsp;

                                        Color:
                                        <?= displayValue(
                                            $inquiry["Color"]
                                        ) ?>

                                    </div>

                                </div>


                                <span
                                    class="
                                        status-badge
                                        status-<?= e(
                                            $statusClass
                                        )
                                        ?>
                                    "
                                >

                                    <?= e(
                                        $inquiry["Status"]
                                    ) ?>

                                </span>


                            </div>


                            <!-- =====================================
                                 INFORMATION
                                 ===================================== -->

                            <div class="inquiry-grid">


                                <div>

                                    <div
                                        class="inquiry-info-label"
                                    >
                                        Buyer
                                    </div>

                                    <div
                                        class="inquiry-info-value"
                                    >

                                        <?= e(
                                            $inquiry[
                                                "BuyerFirstName"
                                            ]
                                            . " "
                                            .
                                            $inquiry[
                                                "BuyerLastName"
                                            ]
                                        ) ?>

                                    </div>

                                </div>


                                <div>

                                    <div
                                        class="inquiry-info-label"
                                    >
                                        Buyer Email
                                    </div>

                                    <div
                                        class="inquiry-info-value"
                                    >

                                        <?= e(
                                            $inquiry[
                                                "BuyerEmail"
                                            ]
                                        ) ?>

                                    </div>

                                </div>


                                <div>

                                    <div
                                        class="inquiry-info-label"
                                    >
                                        Asking Price
                                    </div>

                                    <div
                                        class="inquiry-info-value"
                                    >

                                        $
                                        <?= number_format(
                                            (float)
                                            $inquiry["Price"],
                                            2
                                        ) ?>

                                    </div>

                                </div>


                                <div>

                                    <div
                                        class="inquiry-info-label"
                                    >
                                        Inquiry Date
                                    </div>

                                    <div
                                        class="inquiry-info-value"
                                    >

                                        <?= e(
                                            formatDate(
                                                $inquiry["CreatedAt"]
                                            )
                                        ) ?>

                                    </div>

                                </div>


                            </div>


                            <!-- =====================================
                                 MESSAGE
                                 ===================================== -->

                            <?php if (
                                trim(
                                    $inquiry["Message"] ?? ""
                                ) !== ""
                            ): ?>


                                <div class="buyer-message">

                                    <div
                                        class="buyer-message-label"
                                    >
                                        Buyer Message
                                    </div>

                                    <?= nl2br(
                                        e(
                                            $inquiry["Message"]
                                        )
                                    ) ?>

                                </div>


                            <?php endif; ?>


                            <!-- =====================================
                                 FOOTER
                                 ===================================== -->

                            <div class="inquiry-footer">


                                <div class="submitted-date">

                                    Last updated:
                                    <?= e(
                                        formatDateTime(
                                            $inquiry["UpdatedAt"]
                                        )
                                    ) ?>

                                </div>


                                <div class="inquiry-actions">


                                    <a
                                        href="/visrs/marketplace/inquiry.php?interest_id=<?= (int) $inquiry["InterestID"] ?>"
                                        class="action-button"
                                    >
                                        View Inquiry
                                    </a>


                                    <a
                                        href="/visrs/marketplace/chat.php?interest_id=<?= (int) $inquiry["InterestID"] ?>"
                                        class="
                                            action-button
                                            primary
                                        "
                                    >
                                        Open Chat
                                    </a>


                                </div>


                            </div>


                        </div>


                    <?php endforeach; ?>


                <?php endif; ?>


            </div>


        </main>


    </main>


</div>


<?php

require_once __DIR__ .
    "/../includes/footer.php";

?>