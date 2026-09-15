<?php

require_once __DIR__ . "/../includes/auth.php";
requireRole(["Admin", "Seller"]);

require_once __DIR__ . "/../includes/database.php";
require_once __DIR__ . "/../includes/functions.php";

$pageTitle = "View Inquiry";
$activePage = "marketplace";

/*
|--------------------------------------------------------------------------
| Get Inquiry ID
|--------------------------------------------------------------------------
*/

$interestID = filter_input(
    INPUT_GET,
    "interest_id",
    FILTER_VALIDATE_INT
);

if (!$interestID || $interestID <= 0) {

    http_response_code(400);

    ?>
    <!DOCTYPE html>
    <html lang="en">

    <head>
        <meta charset="UTF-8">

        <meta
            name="viewport"
            content="width=device-width, initial-scale=1.0"
        >

        <title>Invalid Inquiry - VISRS</title>

        <link
            rel="stylesheet"
            href="/visrs/css/style.css"
        >
    </head>

    <body>

    <div class="layout">

        <?php
        require_once __DIR__ . "/../includes/sidebar.php";
        ?>

        <main class="main-content">

            <?php
            require_once __DIR__ . "/../includes/header.php";
            ?>

            <main class="content">

                <div class="card error-card">

                    <h1>Invalid Inquiry</h1>

                    <p>
                        The selected inquiry could not be identified.
                    </p>

                    <a
                        href="/visrs/marketplace/inquiries.php"
                        class="button"
                    >
                        Back to Inquiries
                    </a>

                </div>

            </main>

        </main>

    </div>

    <?php
    require_once __DIR__ . "/../includes/footer.php";
    ?>

    </body>
    </html>

    <?php

    exit;
}


/*
|--------------------------------------------------------------------------
| Inquiry Status Configuration
|--------------------------------------------------------------------------
*/

$allowedInquiryStatuses = [
    "Pending",
    "Contacted",
    "Accepted",
    "Declined",
    "Closed"
];

$statusSuccess = "";
$statusError = "";


/*
|--------------------------------------------------------------------------
| Load Inquiry
|--------------------------------------------------------------------------
|
| Admin:
|   Can view any inquiry.
|
| Seller:
|   Can only view inquiries belonging to their account.
|
*/

if (isAdmin()) {

    $stmt = $pdo->prepare("
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

            b.FirstName AS BuyerFirstName,
            b.LastName AS BuyerLastName,
            b.Email AS BuyerEmail,

            s.FirstName AS SellerFirstName,
            s.LastName AS SellerLastName,
            s.Email AS SellerEmail,

            vl.Price,
            vl.Status AS ListingStatus,

            v.PlateNumber,
            v.VIN,
            v.Make,
            v.Model,
            v.VehicleYear,
            v.Color,
            v.VehicleType,
            v.Status AS VehicleStatus

        FROM vehicle_interests vi

        INNER JOIN users b
            ON vi.BuyerID = b.UserID

        INNER JOIN users s
            ON vi.SellerID = s.UserID

        INNER JOIN vehicle_listings vl
            ON vi.ListingID = vl.ListingID

        INNER JOIN vehicles v
            ON vi.VehicleID = v.VehicleID

        WHERE vi.InterestID = ?

        LIMIT 1
    ");

    $stmt->execute([
        (int) $interestID
    ]);

} else {

    $stmt = $pdo->prepare("
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

            b.FirstName AS BuyerFirstName,
            b.LastName AS BuyerLastName,
            b.Email AS BuyerEmail,

            s.FirstName AS SellerFirstName,
            s.LastName AS SellerLastName,
            s.Email AS SellerEmail,

            vl.Price,
            vl.Status AS ListingStatus,

            v.PlateNumber,
            v.VIN,
            v.Make,
            v.Model,
            v.VehicleYear,
            v.Color,
            v.VehicleType,
            v.Status AS VehicleStatus

        FROM vehicle_interests vi

        INNER JOIN users b
            ON vi.BuyerID = b.UserID

        INNER JOIN users s
            ON vi.SellerID = s.UserID

        INNER JOIN vehicle_listings vl
            ON vi.ListingID = vl.ListingID

        INNER JOIN vehicles v
            ON vi.VehicleID = v.VehicleID

        WHERE vi.InterestID = ?

          AND vi.SellerID = ?

        LIMIT 1
    ");

    $stmt->execute([
        (int) $interestID,
        (int) currentUserId()
    ]);
}

$inquiry = $stmt->fetch();


/*
|--------------------------------------------------------------------------
| Inquiry Not Found
|--------------------------------------------------------------------------
*/

if (!$inquiry) {

    http_response_code(404);

    ?>

    <!DOCTYPE html>
    <html lang="en">

    <head>

        <meta charset="UTF-8">

        <meta
            name="viewport"
            content="width=device-width, initial-scale=1.0"
        >

        <title>Inquiry Not Found - VISRS</title>

        <link
            rel="stylesheet"
            href="/visrs/css/style.css"
        >

    </head>

    <body>

    <div class="layout">

        <?php
        require_once __DIR__ . "/../includes/sidebar.php";
        ?>

        <main class="main-content">

            <?php
            require_once __DIR__ . "/../includes/header.php";
            ?>

            <main class="content">

                <div class="card error-card">

                    <h1>Inquiry Not Found</h1>

                    <p>
                        The inquiry does not exist, or you do not have
                        permission to view it.
                    </p>

                    <a
                        href="/visrs/marketplace/inquiries.php"
                        class="button"
                    >
                        Back to Inquiries
                    </a>

                </div>

            </main>

        </main>

    </div>

    <?php
    require_once __DIR__ . "/../includes/footer.php";
    ?>

    </body>
    </html>

    <?php

    exit;
}


/*
|--------------------------------------------------------------------------
| Update Inquiry Status
|--------------------------------------------------------------------------
|
| Admin:
|   Can update any inquiry.
|
| Seller:
|   Can only update their own inquiries.
|
*/

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    requireValidCSRF();

    $newStatus = trim(
        $_POST["status"] ?? ""
    );


    /*
    |--------------------------------------------------------------------------
    | Validate Submitted Status
    |--------------------------------------------------------------------------
    */

    if (!in_array(
        $newStatus,
        $allowedInquiryStatuses,
        true
    )) {

        $statusError =
            "Please select a valid inquiry status.";

    } else {

        /*
        |--------------------------------------------------------------------------
        | Verify Permission
        |--------------------------------------------------------------------------
        */

        $hasPermission = false;

        if (isAdmin()) {

            $hasPermission = true;

        } elseif (
            (int) $inquiry["SellerID"] ===
            (int) currentUserId()
        ) {

            $hasPermission = true;
        }


        /*
        |--------------------------------------------------------------------------
        | Unauthorized Status Change
        |--------------------------------------------------------------------------
        */

        if (!$hasPermission) {

            http_response_code(403);

            $statusError =
                "You do not have permission to update this inquiry.";

        } else {

            $oldStatus =
                $inquiry["Status"];


            /*
            |--------------------------------------------------------------------------
            | No Change
            |--------------------------------------------------------------------------
            */

            if ($oldStatus === $newStatus) {

                $statusSuccess =
                    "The inquiry is already marked as "
                    . $newStatus
                    . ".";

            } else {

                /*
                |--------------------------------------------------------------------------
                | Update Database
                |--------------------------------------------------------------------------
                */

                try {

                    $pdo->beginTransaction();


                    $updateStmt = $pdo->prepare("
                        UPDATE vehicle_interests

                        SET
                            Status = ?

                        WHERE InterestID = ?
                    ");

                    $updateStmt->execute([
                        $newStatus,
                        (int) $interestID
                    ]);


                    /*
                    |--------------------------------------------------------------------------
                    | Audit Log
                    |--------------------------------------------------------------------------
                    |
                    | IMPORTANT:
                    | logAudit() requires the PDO connection as its
                    | first argument.
                    |
                    */

                    logAudit(
                        $pdo,
                        "Changed inquiry status from "
                        . $oldStatus
                        . " to "
                        . $newStatus,
                        "vehicle_interests",
                        (int) $interestID
                    );


                    /*
                    |--------------------------------------------------------------------------
                    | Commit Transaction
                    |--------------------------------------------------------------------------
                    */

                    $pdo->commit();


                    /*
                    |--------------------------------------------------------------------------
                    | Update Current Page Data
                    |--------------------------------------------------------------------------
                    */

                    $inquiry["Status"] =
                        $newStatus;

                    $inquiry["UpdatedAt"] =
                        date("Y-m-d H:i:s");


                    $statusSuccess =
                        "Inquiry status updated to "
                        . $newStatus
                        . ".";

                } catch (Throwable $e) {

                    if ($pdo->inTransaction()) {
                        $pdo->rollBack();
                    }

                    $statusError =
                        "Unable to update the inquiry status. Please try again.";
                }
            }
        }
    }
}


/*
|--------------------------------------------------------------------------
| Page Information
|--------------------------------------------------------------------------
*/

$pageTitle =
    "Inquiry #"
    . (int) $inquiry["InterestID"];


/*
|--------------------------------------------------------------------------
| Status Badge Class
|--------------------------------------------------------------------------
*/

$statusClass = "status-pending";

switch ($inquiry["Status"]) {

    case "Contacted":

        $statusClass = "status-contacted";

        break;

    case "Accepted":

        $statusClass = "status-accepted";

        break;

    case "Declined":

        $statusClass = "status-declined";

        break;

    case "Closed":

        $statusClass = "status-closed";

        break;

    case "Pending":

    default:

        $statusClass = "status-pending";

        break;
}


/*
|--------------------------------------------------------------------------
| Message
|--------------------------------------------------------------------------
*/

$message =
    trim(
        $inquiry["Message"] ?? ""
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
        <?= htmlspecialchars($pageTitle) ?> - VISRS
    </title>

    <link
        rel="stylesheet"
        href="/visrs/css/style.css"
    >

    <style>

        /*
        |--------------------------------------------------------------------------
        | Page Header
        |--------------------------------------------------------------------------
        */

        .inquiry-page-header {

            display:flex;

            justify-content:space-between;

            align-items:flex-start;

            gap:20px;

            margin-bottom:25px;

            flex-wrap:wrap;
        }


        .inquiry-page-header h1 {

            margin:0 0 6px;
        }


        .inquiry-page-header p {

            margin:0;

            color:#64748b;
        }


        .inquiry-actions {

            display:flex;

            gap:10px;

            flex-wrap:wrap;
        }


        /*
        |--------------------------------------------------------------------------
        | Main Grid
        |--------------------------------------------------------------------------
        */

        .inquiry-grid {

            display:grid;

            grid-template-columns:
                minmax(0, 1.15fr)
                minmax(300px, 0.85fr);

            gap:20px;
        }


        .inquiry-section {

            margin-bottom:20px;
        }


        .inquiry-section:last-child {

            margin-bottom:0;
        }


        .inquiry-section h2 {

            margin:0 0 16px;

            font-size:18px;
        }


        /*
        |--------------------------------------------------------------------------
        | Detail Grid
        |--------------------------------------------------------------------------
        */

        .detail-grid {

            display:grid;

            grid-template-columns:
                repeat(2, minmax(0, 1fr));

            gap:14px;
        }


        .detail-item {

            padding:13px 14px;

            border:1px solid #e5e7eb;

            border-radius:8px;

            background:#f8fafc;
        }


        .detail-label {

            display:block;

            margin-bottom:5px;

            color:#64748b;

            font-size:12px;

            font-weight:600;
        }


        .detail-value {

            color:#111827;

            font-size:14px;

            font-weight:600;

            word-break:break-word;
        }


        /*
        |--------------------------------------------------------------------------
        | Message
        |--------------------------------------------------------------------------
        */

        .inquiry-message {

            padding:18px;

            border:1px solid #e5e7eb;

            border-radius:10px;

            background:#f8fafc;

            color:#374151;

            line-height:1.7;

            white-space:pre-wrap;

            word-break:break-word;

            min-height:60px;
        }


        /*
        |--------------------------------------------------------------------------
        | Status Badges
        |--------------------------------------------------------------------------
        */

        .status-badge {

            display:inline-block;

            padding:6px 11px;

            border-radius:999px;

            font-size:12px;

            font-weight:700;

            line-height:1;
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
        | Status Management
        |--------------------------------------------------------------------------
        */

        .status-management {

            padding:18px;

            border:1px solid #e5e7eb;

            border-radius:10px;

            background:#f8fafc;
        }


        .current-status-row {

            display:flex;

            align-items:center;

            justify-content:space-between;

            gap:15px;

            flex-wrap:wrap;

            margin-bottom:18px;
        }


        .current-status-label {

            display:block;

            margin-bottom:7px;

            color:#64748b;

            font-size:13px;

            font-weight:600;
        }


        .status-help-text {

            margin:0 0 18px;

            color:#64748b;

            font-size:13px;

            line-height:1.6;
        }


        .status-form {

            display:flex;

            align-items:flex-end;

            gap:12px;

            flex-wrap:wrap;
        }


        .status-form-group {

            flex:1;

            min-width:220px;
        }


        .status-form-group label {

            display:block;

            margin-bottom:7px;

            color:#374151;

            font-size:13px;

            font-weight:600;
        }


        .status-form-group select {

            width:100%;

            box-sizing:border-box;

            padding:11px 12px;

            border:1px solid #d1d5db;

            border-radius:7px;

            background:#ffffff;

            color:#111827;

            font-size:14px;

            cursor:pointer;
        }


        .status-form-group select:focus {

            outline:none;

            border-color:#2563eb;

            box-shadow:
                0 0 0 3px
                rgba(37,99,235,0.10);
        }


        /*
        |--------------------------------------------------------------------------
        | Alerts
        |--------------------------------------------------------------------------
        */

        .inquiry-alert {

            padding:13px 15px;

            border-radius:8px;

            margin-bottom:20px;

            font-size:14px;

            font-weight:600;
        }


        .inquiry-alert-success {

            background:#dcfce7;

            border:1px solid #bbf7d0;

            color:#166534;
        }


        .inquiry-alert-error {

            background:#fee2e2;

            border:1px solid #fecaca;

            color:#991b1b;
        }


        /*
        |--------------------------------------------------------------------------
        | Vehicle Summary
        |--------------------------------------------------------------------------
        */

        .vehicle-summary {

            padding:18px;

            border:1px solid #e5e7eb;

            border-radius:10px;

            background:#ffffff;
        }


        .vehicle-summary-title {

            margin:0 0 5px;

            font-size:20px;

            font-weight:800;

            color:#111827;
        }


        .vehicle-summary-subtitle {

            margin:0 0 16px;

            color:#64748b;

            font-size:13px;
        }


        .price-display {

            margin-bottom:18px;

            font-size:25px;

            font-weight:800;

            color:#111827;
        }


        /*
        |--------------------------------------------------------------------------
        | Timeline
        |--------------------------------------------------------------------------
        */

        .timeline-item {

            display:flex;

            justify-content:space-between;

            gap:20px;

            padding:11px 0;

            border-bottom:1px solid #f1f5f9;
        }


        .timeline-item:last-child {

            border-bottom:0;
        }


        .timeline-label {

            color:#64748b;

            font-size:13px;
        }


        .timeline-value {

            color:#374151;

            font-size:13px;

            font-weight:600;

            text-align:right;
        }


        /*
        |--------------------------------------------------------------------------
        | Responsive
        |--------------------------------------------------------------------------
        */

        @media (max-width:900px) {

            .inquiry-grid {

                grid-template-columns:1fr;
            }

        }


        @media (max-width:600px) {

            .detail-grid {

                grid-template-columns:1fr;
            }


            .inquiry-actions {

                width:100%;
            }


            .inquiry-actions .button {

                flex:1;

                text-align:center;
            }


            .status-form {

                display:block;
            }


            .status-form-group {

                width:100%;

                margin-bottom:12px;
            }


            .status-form .button {

                width:100%;
            }


            .timeline-item {

                display:block;
            }


            .timeline-value {

                margin-top:4px;

                text-align:left;
            }

        }

    </style>

</head>


<body>

<div class="layout">

    <?php
    require_once __DIR__ . "/../includes/sidebar.php";
    ?>


    <main class="main-content">

        <?php
        require_once __DIR__ . "/../includes/header.php";
        ?>


        <main class="content">


            <!-- =========================================================
                 PAGE HEADER
                 ========================================================= -->

            <div class="inquiry-page-header">

                <div>

                    <h1>
                        Inquiry #<?= (int) $inquiry["InterestID"] ?>
                    </h1>

                    <p>
                        Review and manage the buyer's inquiry for this
                        marketplace listing.
                    </p>

                </div>


                <div class="inquiry-actions">

                    <a
                        href="/visrs/marketplace/inquiries.php"
                        class="button button-secondary"
                    >
                        Back to Inquiries
                    </a>


                    <a
                        href="/visrs/marketplace/view.php?vehicle_id=<?= (int) $inquiry["VehicleID"] ?>"
                        class="button"
                    >
                        View Vehicle
                    </a>

                </div>

            </div>


            <!-- =========================================================
                 STATUS UPDATE FEEDBACK
                 ========================================================= -->

            <?php if ($statusSuccess !== ""): ?>

                <div
                    class="inquiry-alert inquiry-alert-success"
                >

                    <?= htmlspecialchars(
                        $statusSuccess
                    ) ?>

                </div>

            <?php endif; ?>


            <?php if ($statusError !== ""): ?>

                <div
                    class="inquiry-alert inquiry-alert-error"
                >

                    <?= htmlspecialchars(
                        $statusError
                    ) ?>

                </div>

            <?php endif; ?>


            <div class="inquiry-grid">


                <!-- =====================================================
                     LEFT COLUMN
                     ===================================================== -->

                <div>


                    <!-- =================================================
                         BUYER INFORMATION
                         ================================================= -->

                    <section class="card inquiry-section">

                        <h2>
                            Buyer Information
                        </h2>


                        <div class="detail-grid">


                            <div class="detail-item">

                                <span class="detail-label">
                                    Buyer Name
                                </span>

                                <span class="detail-value">

                                    <?= htmlspecialchars(
                                        $inquiry["BuyerFirstName"]
                                        . " "
                                        . $inquiry["BuyerLastName"]
                                    ) ?>

                                </span>

                            </div>


                            <div class="detail-item">

                                <span class="detail-label">
                                    Email
                                </span>

                                <span class="detail-value">

                                    <?= htmlspecialchars(
                                        $inquiry["BuyerEmail"]
                                    ) ?>

                                </span>

                            </div>


                            <div class="detail-item">

                                <span class="detail-label">
                                    Inquiry Status
                                </span>

                                <span class="detail-value">

                                    <span
                                        class="status-badge <?= htmlspecialchars($statusClass) ?>"
                                    >
                                        <?= htmlspecialchars(
                                            $inquiry["Status"]
                                        ) ?>
                                    </span>

                                </span>

                            </div>


                            <div class="detail-item">

                                <span class="detail-label">
                                    Submitted
                                </span>

                                <span class="detail-value">

                                    <?= htmlspecialchars(
                                        formatDateTime(
                                            $inquiry["CreatedAt"]
                                        )
                                    ) ?>

                                </span>

                            </div>


                        </div>

                    </section>


                    <!-- =================================================
                         STATUS MANAGEMENT
                         ================================================= -->

                    <section class="card inquiry-section">

                        <h2>
                            Manage Inquiry Status
                        </h2>


                        <div class="status-management">


                            <div class="current-status-row">

                                <div>

                                    <span class="current-status-label">
                                        Current Status
                                    </span>

                                    <span
                                        class="status-badge <?= htmlspecialchars($statusClass) ?>"
                                    >

                                        <?= htmlspecialchars(
                                            $inquiry["Status"]
                                        ) ?>

                                    </span>

                                </div>

                            </div>


                            <p class="status-help-text">

                                Update the inquiry as communication with
                                the buyer progresses. Every status change
                                is saved to the database and recorded in
                                the VISRS audit log.

                            </p>


                            <form
                                method="POST"
                                class="status-form"
                            >

                                <input
                                    type="hidden"
                                    name="csrf_token"
                                    value="<?= htmlspecialchars(
                                        generateCSRFToken()
                                    ) ?>"
                                >


                                <div class="status-form-group">

                                    <label
                                        for="inquiry-status"
                                    >
                                        Change Status
                                    </label>


                                    <select
                                        id="inquiry-status"
                                        name="status"
                                        required
                                    >

                                        <?php foreach (
                                            $allowedInquiryStatuses
                                            as $statusOption
                                        ): ?>

                                            <option
                                                value="<?= htmlspecialchars(
                                                    $statusOption
                                                ) ?>"
                                                <?= $inquiry["Status"] === $statusOption
                                                    ? "selected"
                                                    : "" ?>
                                            >
                                                <?= htmlspecialchars(
                                                    $statusOption
                                                ) ?>
                                            </option>

                                        <?php endforeach; ?>

                                    </select>

                                </div>


                                <button
                                    type="submit"
                                    class="button"
                                >
                                    Update Status
                                </button>

                            </form>

                        </div>

                    </section>


                    <!-- =================================================
                         BUYER MESSAGE
                         ================================================= -->

                    <section class="card inquiry-section">

                        <h2>
                            Buyer Message
                        </h2>


                        <div class="inquiry-message">

                            <?php if ($message !== ""): ?>

                                <?= htmlspecialchars(
                                    $message
                                ) ?>

                            <?php else: ?>

                                No message was provided with this inquiry.

                            <?php endif; ?>

                        </div>

                    </section>


                    <!-- =================================================
                         SELLER INFORMATION
                         ================================================= -->

                    <section class="card inquiry-section">

                        <h2>
                            Seller Information
                        </h2>


                        <div class="detail-grid">


                            <div class="detail-item">

                                <span class="detail-label">
                                    Seller Name
                                </span>

                                <span class="detail-value">

                                    <?= htmlspecialchars(
                                        $inquiry["SellerFirstName"]
                                        . " "
                                        . $inquiry["SellerLastName"]
                                    ) ?>

                                </span>

                            </div>


                            <div class="detail-item">

                                <span class="detail-label">
                                    Seller Email
                                </span>

                                <span class="detail-value">

                                    <?= htmlspecialchars(
                                        $inquiry["SellerEmail"]
                                    ) ?>

                                </span>

                            </div>


                        </div>

                    </section>


                </div>


                <!-- =====================================================
                     RIGHT COLUMN
                     ===================================================== -->

                <div>


                    <!-- =================================================
                         VEHICLE LISTING
                         ================================================= -->

                    <section class="card inquiry-section">

                        <h2>
                            Vehicle Listing
                        </h2>


                        <div class="vehicle-summary">


                            <h3 class="vehicle-summary-title">

                                <?= htmlspecialchars(
                                    $inquiry["VehicleYear"]
                                    . " "
                                    . $inquiry["Make"]
                                    . " "
                                    . $inquiry["Model"]
                                ) ?>

                            </h3>


                            <p class="vehicle-summary-subtitle">

                                <?= htmlspecialchars(
                                    $inquiry["Color"]
                                    ?: "Color not specified"
                                ) ?>


                                <?php if (
                                    !empty(
                                        $inquiry["VehicleType"]
                                    )
                                ): ?>

                                    ·

                                    <?= htmlspecialchars(
                                        $inquiry["VehicleType"]
                                    ) ?>

                                <?php endif; ?>

                            </p>


                            <div class="price-display">

                                $

                                <?= number_format(
                                    (float) $inquiry["Price"],
                                    2
                                ) ?>

                            </div>


                            <div class="detail-grid">


                                <div class="detail-item">

                                    <span class="detail-label">
                                        Plate Number
                                    </span>

                                    <span class="detail-value">

                                        <?= htmlspecialchars(
                                            $inquiry["PlateNumber"]
                                        ) ?>

                                    </span>

                                </div>


                                <div class="detail-item">

                                    <span class="detail-label">
                                        VIN
                                    </span>

                                    <span class="detail-value">

                                        <?= htmlspecialchars(
                                            $inquiry["VIN"]
                                        ) ?>

                                    </span>

                                </div>


                                <div class="detail-item">

                                    <span class="detail-label">
                                        Vehicle Status
                                    </span>

                                    <span class="detail-value">

                                        <?= htmlspecialchars(
                                            $inquiry["VehicleStatus"]
                                        ) ?>

                                    </span>

                                </div>


                                <div class="detail-item">

                                    <span class="detail-label">
                                        Listing Status
                                    </span>

                                    <span class="detail-value">

                                        <?= htmlspecialchars(
                                            $inquiry["ListingStatus"]
                                        ) ?>

                                    </span>

                                </div>


                            </div>

                        </div>

                    </section>


                    <!-- =================================================
                         INQUIRY TIMELINE
                         ================================================= -->

                    <section class="card inquiry-section">

                        <h2>
                            Inquiry Timeline
                        </h2>


                        <div>


                            <div class="timeline-item">

                                <span class="timeline-label">
                                    Inquiry Submitted
                                </span>

                                <span class="timeline-value">

                                    <?= htmlspecialchars(
                                        formatDateTime(
                                            $inquiry["CreatedAt"]
                                        )
                                    ) ?>

                                </span>

                            </div>


                            <div class="timeline-item">

                                <span class="timeline-label">
                                    Last Updated
                                </span>

                                <span class="timeline-value">

                                    <?= htmlspecialchars(
                                        formatDateTime(
                                            $inquiry["UpdatedAt"]
                                        )
                                    ) ?>

                                </span>

                            </div>


                            <div class="timeline-item">

                                <span class="timeline-label">
                                    Current Status
                                </span>

                                <span class="timeline-value">

                                    <span
                                        class="status-badge <?= htmlspecialchars($statusClass) ?>"
                                    >

                                        <?= htmlspecialchars(
                                            $inquiry["Status"]
                                        ) ?>

                                    </span>

                                </span>

                            </div>


                            <div class="timeline-item">

                                <span class="timeline-label">
                                    Inquiry ID
                                </span>

                                <span class="timeline-value">

                                    #<?= (int) $inquiry["InterestID"] ?>

                                </span>

                            </div>


                        </div>

                    </section>


                </div>


            </div>


        </main>


    </main>

</div>


<?php
require_once __DIR__ . "/../includes/footer.php";
?>

</body>

</html>