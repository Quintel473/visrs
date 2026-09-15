<?php

require_once __DIR__ . "/../includes/auth.php";
requireRole(["Admin", "Seller", "User"]);

require_once __DIR__ . "/../includes/database.php";
require_once __DIR__ . "/../includes/functions.php";

$pageTitle = "Messages";
$activePage = "marketplace";

$currentUserID = (int) currentUserId();

$error = "";
$success = "";

$interestID = filter_input(
    INPUT_GET,
    "interest_id",
    FILTER_VALIDATE_INT
);

$listingID = filter_input(
    INPUT_GET,
    "listing_id",
    FILTER_VALIDATE_INT
);


/*
|--------------------------------------------------------------------------
| Determine Inquiry
|--------------------------------------------------------------------------
|
| The chat must always belong to an existing vehicle inquiry.
|
| If interest_id is supplied, use it directly.
|
| If listing_id is supplied, find the current user's inquiry
| for that listing.
|
*/

if (!$interestID && $listingID) {

    /*
    |--------------------------------------------------------------------------
    | Buyer opening chat from vehicle listing
    |--------------------------------------------------------------------------
    */

    $interestStmt = $pdo->prepare("
        SELECT
            InterestID
        FROM vehicle_interests
        WHERE ListingID = ?
          AND BuyerID = ?
        LIMIT 1
    ");

    $interestStmt->execute([
        (int) $listingID,
        $currentUserID
    ]);

    $existingInterest =
        $interestStmt->fetch();

    if ($existingInterest) {

        $interestID =
            (int) $existingInterest["InterestID"];

    }

}


/*
|--------------------------------------------------------------------------
| Require Inquiry
|--------------------------------------------------------------------------
*/

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

        <title>
            Message Seller - VISRS
        </title>

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

                <div class="card">

                    <h1>
                        Inquiry Required
                    </h1>

                    <p>
                        You must submit an inquiry for this vehicle before
                        starting a conversation with the seller.
                    </p>

                    <?php if ($listingID): ?>

                        <a
                            href="/visrs/marketplace/view.php?vehicle_id=<?= (int) $listingID ?>"
                            class="button"
                        >
                            Back to Vehicle
                        </a>

                    <?php else: ?>

                        <a
                            href="/visrs/marketplace/"
                            class="button"
                        >
                            Back to Marketplace
                        </a>

                    <?php endif; ?>

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
| Load Inquiry
|--------------------------------------------------------------------------
|
| Admin can view any inquiry.
|
| Seller can only access inquiries belonging to them.
|
| Buyer can only access their own inquiry.
|
*/

if (isAdmin()) {

    $inquiryStmt = $pdo->prepare("
        SELECT

            vi.InterestID,
            vi.ListingID,
            vi.VehicleID,
            vi.BuyerID,
            vi.SellerID,
            vi.Message AS InquiryMessage,
            vi.Status AS InquiryStatus,
            vi.CreatedAt AS InquiryCreatedAt,
            vi.UpdatedAt AS InquiryUpdatedAt,

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

    $inquiryStmt->execute([
        (int) $interestID
    ]);

} elseif (hasRole(["Seller"])) {

    $inquiryStmt = $pdo->prepare("
        SELECT

            vi.InterestID,
            vi.ListingID,
            vi.VehicleID,
            vi.BuyerID,
            vi.SellerID,
            vi.Message AS InquiryMessage,
            vi.Status AS InquiryStatus,
            vi.CreatedAt AS InquiryCreatedAt,
            vi.UpdatedAt AS InquiryUpdatedAt,

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

    $inquiryStmt->execute([
        (int) $interestID,
        $currentUserID
    ]);

} else {

    $inquiryStmt = $pdo->prepare("
        SELECT

            vi.InterestID,
            vi.ListingID,
            vi.VehicleID,
            vi.BuyerID,
            vi.SellerID,
            vi.Message AS InquiryMessage,
            vi.Status AS InquiryStatus,
            vi.CreatedAt AS InquiryCreatedAt,
            vi.UpdatedAt AS InquiryUpdatedAt,

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
          AND vi.BuyerID = ?

        LIMIT 1
    ");

    $inquiryStmt->execute([
        (int) $interestID,
        $currentUserID
    ]);
}


$inquiry =
    $inquiryStmt->fetch();


/*
|--------------------------------------------------------------------------
| Inquiry Not Found / Unauthorized
|--------------------------------------------------------------------------
*/

if (!$inquiry) {

    http_response_code(403);

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
            Access Denied - VISRS
        </title>

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

                <div class="card">

                    <h1>
                        Conversation Unavailable
                    </h1>

                    <p>
                        This conversation does not exist, or you do not
                        have permission to access it.
                    </p>

                    <a
                        href="/visrs/marketplace/"
                        class="button"
                    >
                        Back to Marketplace
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
| Handle New Message
|--------------------------------------------------------------------------
*/

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    requireValidCSRF();

    $messageText =
        trim(
            $_POST["message"] ?? ""
        );


    /*
    |--------------------------------------------------------------------------
    | Validate Message
    |--------------------------------------------------------------------------
    */

    if ($messageText === "") {

        $error =
            "Please enter a message.";

    } elseif (strlen($messageText) > 2000) {

        $error =
            "Your message cannot exceed 2,000 characters.";

    } elseif (
        $inquiry["InquiryStatus"] === "Closed"
        ||
        $inquiry["InquiryStatus"] === "Declined"
    ) {

        $error =
            "This inquiry is closed and can no longer receive messages.";

    } else {

        /*
        |--------------------------------------------------------------------------
        | Determine Recipient
        |--------------------------------------------------------------------------
        */

        if (
            $currentUserID ===
            (int) $inquiry["BuyerID"]
        ) {

            $recipientID =
                (int) $inquiry["SellerID"];

        } elseif (
            $currentUserID ===
            (int) $inquiry["SellerID"]
        ) {

            $recipientID =
                (int) $inquiry["BuyerID"];

        } elseif (isAdmin()) {

            /*
            | Admin can inspect conversations but should not impersonate
            | either participant.
            */

            $error =
                "Administrators can view conversations but cannot send messages.";

            $recipientID = 0;

        } else {

            $error =
                "You are not a participant in this conversation.";

            $recipientID = 0;
        }


        /*
        |--------------------------------------------------------------------------
        | Insert Message
        |--------------------------------------------------------------------------
        */

        if ($error === "") {

            try {

                $pdo->beginTransaction();


                $messageStmt = $pdo->prepare("
                    INSERT INTO vehicle_messages (
                        InterestID,
                        ListingID,
                        VehicleID,
                        SenderID,
                        RecipientID,
                        MessageText
                    )
                    VALUES (?, ?, ?, ?, ?, ?)
                ");


                $messageStmt->execute([

                    (int) $inquiry["InterestID"],

                    (int) $inquiry["ListingID"],

                    (int) $inquiry["VehicleID"],

                    $currentUserID,

                    $recipientID,

                    $messageText

                ]);


                $messageID =
                    (int) $pdo->lastInsertId();


                /*
                |--------------------------------------------------------------------------
                | Audit Log
                |--------------------------------------------------------------------------
                */

                logAudit(
                    "Sent marketplace message",
                    "vehicle_messages",
                    $messageID
                );


                $pdo->commit();


                /*
                |--------------------------------------------------------------------------
                | Redirect
                |--------------------------------------------------------------------------
                |
                | Prevents duplicate messages if the user refreshes.
                |
                */

                redirect(
                    "message.php?interest_id="
                    . (int) $interestID
                    . "&sent=1"
                );

            } catch (Throwable $e) {

                if (
                    $pdo->inTransaction()
                ) {

                    $pdo->rollBack();
                }


                $error =
                    "Unable to send your message. Please try again.";
            }
        }
    }
}


/*
|--------------------------------------------------------------------------
| Success Message
|--------------------------------------------------------------------------
*/

if (
    isset($_GET["sent"])
    &&
    $_GET["sent"] === "1"
) {

    $success =
        "Message sent successfully.";
}


/*
|--------------------------------------------------------------------------
| Mark Messages as Read
|--------------------------------------------------------------------------
|
| Only mark messages sent TO the current user as read.
|
*/

if (!isAdmin()) {

    $readStmt = $pdo->prepare("
        UPDATE vehicle_messages
        SET
            IsRead = 1,
            ReadAt = CURRENT_TIMESTAMP
        WHERE InterestID = ?
          AND RecipientID = ?
          AND IsRead = 0
    ");

    $readStmt->execute([
        (int) $inquiry["InterestID"],
        $currentUserID
    ]);
}


/*
|--------------------------------------------------------------------------
| Load Messages
|--------------------------------------------------------------------------
*/

$messageStmt = $pdo->prepare("
    SELECT

        vm.MessageID,
        vm.InterestID,
        vm.SenderID,
        vm.RecipientID,
        vm.MessageText,
        vm.IsRead,
        vm.CreatedAt,
        vm.ReadAt,

        u.FirstName,
        u.LastName,
        u.Role

    FROM vehicle_messages vm

    INNER JOIN users u
        ON vm.SenderID = u.UserID

    WHERE vm.InterestID = ?

    ORDER BY
        vm.CreatedAt ASC,
        vm.MessageID ASC
");


$messageStmt->execute([
    (int) $inquiry["InterestID"]
]);


$messages =
    $messageStmt->fetchAll();


/*
|--------------------------------------------------------------------------
| Status Badge
|--------------------------------------------------------------------------
*/

$statusClass = "status-pending";

switch (
    $inquiry["InquiryStatus"]
) {

    case "Contacted":

        $statusClass =
            "status-contacted";

        break;

    case "Accepted":

        $statusClass =
            "status-accepted";

        break;

    case "Declined":

        $statusClass =
            "status-declined";

        break;

    case "Closed":

        $statusClass =
            "status-closed";

        break;

    case "Pending":

    default:

        $statusClass =
            "status-pending";

        break;
}


$pageTitle =
    "Conversation #"
    . (int) $inquiry["InterestID"];

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
        | Chat Page
        |--------------------------------------------------------------------------
        */

        .chat-page-header {

            display:flex;

            justify-content:space-between;

            align-items:flex-start;

            gap:20px;

            margin-bottom:22px;

            flex-wrap:wrap;
        }


        .chat-page-header h1 {

            margin:0 0 6px;
        }


        .chat-page-header p {

            margin:0;

            color:#64748b;
        }


        .chat-actions {

            display:flex;

            gap:10px;

            flex-wrap:wrap;
        }


        /*
        |--------------------------------------------------------------------------
        | Conversation Summary
        |--------------------------------------------------------------------------
        */

        .conversation-summary {

            display:grid;

            grid-template-columns:
                minmax(0, 1fr)
                auto
                auto;

            gap:20px;

            align-items:center;

            padding:18px;

            margin-bottom:20px;

            border:1px solid #e5e7eb;

            border-radius:12px;

            background:#ffffff;
        }


        .conversation-vehicle {

            min-width:0;
        }


        .conversation-vehicle h2 {

            margin:0 0 5px;

            font-size:18px;
        }


        .conversation-vehicle p {

            margin:0;

            color:#64748b;

            font-size:13px;
        }


        .conversation-person {

            padding-left:20px;

            border-left:1px solid #e5e7eb;
        }


        .conversation-person-label {

            display:block;

            margin-bottom:4px;

            color:#64748b;

            font-size:11px;

            font-weight:700;

            text-transform:uppercase;

            letter-spacing:0.4px;
        }


        .conversation-person-name {

            color:#111827;

            font-size:14px;

            font-weight:700;
        }


        /*
        |--------------------------------------------------------------------------
        | Status
        |--------------------------------------------------------------------------
        */

        .status-badge {

            display:inline-block;

            padding:6px 11px;

            border-radius:999px;

            font-size:12px;

            font-weight:700;
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
        | Alerts
        |--------------------------------------------------------------------------
        */

        .chat-alert {

            padding:13px 15px;

            margin-bottom:18px;

            border-radius:8px;

            font-size:14px;

            font-weight:600;
        }


        .chat-alert-success {

            background:#dcfce7;

            border:1px solid #bbf7d0;

            color:#166534;
        }


        .chat-alert-error {

            background:#fee2e2;

            border:1px solid #fecaca;

            color:#991b1b;
        }


        /*
        |--------------------------------------------------------------------------
        | Chat Container
        |--------------------------------------------------------------------------
        */

        .chat-card {

            padding:0;

            overflow:hidden;

            border:1px solid #e5e7eb;

            border-radius:12px;

            background:#ffffff;
        }


        .chat-header {

            padding:18px 20px;

            border-bottom:1px solid #e5e7eb;

            background:#f8fafc;
        }


        .chat-header-title {

            margin:0;

            color:#111827;

            font-size:16px;

            font-weight:800;
        }


        .chat-header-subtitle {

            margin:5px 0 0;

            color:#64748b;

            font-size:12px;
        }


        /*
        |--------------------------------------------------------------------------
        | Messages
        |--------------------------------------------------------------------------
        */

        .chat-messages {

            min-height:420px;

            max-height:620px;

            overflow-y:auto;

            padding:25px;

            background:#f8fafc;
        }


        .chat-empty {

            display:flex;

            align-items:center;

            justify-content:center;

            min-height:360px;

            text-align:center;

            color:#64748b;

            font-size:14px;
        }


        .message-row {

            display:flex;

            margin-bottom:17px;
        }


        .message-row:last-child {

            margin-bottom:0;
        }


        .message-row-own {

            justify-content:flex-end;
        }


        .message-row-other {

            justify-content:flex-start;
        }


        .message-bubble-wrapper {

            max-width:72%;
        }


        .message-sender {

            margin-bottom:5px;

            color:#64748b;

            font-size:11px;

            font-weight:700;
        }


        .message-row-own .message-sender {

            text-align:right;
        }


        .message-bubble {

            padding:12px 15px;

            border-radius:12px;

            background:#ffffff;

            border:1px solid #e5e7eb;

            color:#374151;

            font-size:14px;

            line-height:1.6;

            white-space:pre-wrap;

            word-break:break-word;

            box-shadow:
                0 1px 2px rgba(0,0,0,0.03);
        }


        .message-row-own .message-bubble {

            background:#111827;

            border-color:#111827;

            color:#ffffff;

            border-bottom-right-radius:4px;
        }


        .message-row-other .message-bubble {

            border-bottom-left-radius:4px;
        }


        .message-time {

            margin-top:5px;

            color:#94a3b8;

            font-size:10px;
        }


        .message-row-own .message-time {

            text-align:right;
        }


        /*
        |--------------------------------------------------------------------------
        | Message Form
        |--------------------------------------------------------------------------
        */

        .chat-compose {

            padding:18px 20px;

            border-top:1px solid #e5e7eb;

            background:#ffffff;
        }


        .chat-compose textarea {

            display:block;

            width:100%;

            min-height:90px;

            box-sizing:border-box;

            resize:vertical;

            padding:12px 13px;

            border:1px solid #d1d5db;

            border-radius:8px;

            font-family:inherit;

            font-size:14px;

            line-height:1.5;
        }


        .chat-compose textarea:focus {

            outline:none;

            border-color:#2563eb;

            box-shadow:
                0 0 0 3px rgba(37,99,235,0.10);
        }


        .chat-compose-footer {

            display:flex;

            justify-content:space-between;

            align-items:center;

            gap:15px;

            margin-top:10px;

            flex-wrap:wrap;
        }


        .chat-character-count {

            color:#64748b;

            font-size:12px;
        }


        .chat-closed-notice {

            padding:14px 16px;

            border-radius:8px;

            background:#f1f5f9;

            color:#475569;

            font-size:13px;

            line-height:1.5;
        }


        /*
        |--------------------------------------------------------------------------
        | Responsive
        |--------------------------------------------------------------------------
        */

        @media (max-width:800px) {

            .conversation-summary {

                grid-template-columns:1fr;

                gap:14px;
            }


            .conversation-person {

                padding-left:0;

                padding-top:14px;

                border-left:0;

                border-top:1px solid #e5e7eb;
            }

        }


        @media (max-width:600px) {

            .chat-actions {

                width:100%;
            }


            .chat-actions .button {

                flex:1;

                text-align:center;
            }


            .chat-messages {

                padding:16px;

                min-height:350px;
            }


            .message-bubble-wrapper {

                max-width:88%;
            }


            .chat-compose {

                padding:15px;
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

            <div class="chat-page-header">

                <div>

                    <h1>
                        Conversation
                    </h1>

                    <p>
                        Communicate with the buyer or seller about this
                        marketplace vehicle.
                    </p>

                </div>


                <div class="chat-actions">

                    <?php if (isAdmin()): ?>

                        <a
                            href="/visrs/marketplace/inquiry.php?interest_id=<?= (int) $inquiry["InterestID"] ?>"
                            class="button button-secondary"
                        >
                            View Inquiry
                        </a>

                    <?php else: ?>

                        <a
                            href="/visrs/marketplace/inquiry.php?interest_id=<?= (int) $inquiry["InterestID"] ?>"
                            class="button button-secondary"
                        >
                            View Inquiry
                        </a>

                    <?php endif; ?>


                    <a
                        href="/visrs/marketplace/view.php?vehicle_id=<?= (int) $inquiry["VehicleID"] ?>"
                        class="button"
                    >
                        View Vehicle
                    </a>

                </div>

            </div>


            <!-- =========================================================
                 ALERTS
                 ========================================================= -->

            <?php if ($success !== ""): ?>

                <div
                    class="chat-alert chat-alert-success"
                >

                    <?= htmlspecialchars(
                        $success
                    ) ?>

                </div>

            <?php endif; ?>


            <?php if ($error !== ""): ?>

                <div
                    class="chat-alert chat-alert-error"
                >

                    <?= htmlspecialchars(
                        $error
                    ) ?>

                </div>

            <?php endif; ?>


            <!-- =========================================================
                 CONVERSATION SUMMARY
                 ========================================================= -->

            <div class="conversation-summary">


                <div class="conversation-vehicle">

                    <h2>

                        <?= htmlspecialchars(
                            $inquiry["VehicleYear"]
                            . " "
                            . $inquiry["Make"]
                            . " "
                            . $inquiry["Model"]
                        ) ?>

                    </h2>

                    <p>

                        Plate:
                        <?= htmlspecialchars(
                            $inquiry["PlateNumber"]
                        ) ?>

                        &nbsp;·&nbsp;

                        $

                        <?= number_format(
                            (float) $inquiry["Price"],
                            2
                        ) ?>

                    </p>

                </div>


                <div class="conversation-person">

                    <span class="conversation-person-label">
                        Buyer
                    </span>

                    <span class="conversation-person-name">

                        <?= htmlspecialchars(
                            $inquiry["BuyerFirstName"]
                            . " "
                            . $inquiry["BuyerLastName"]
                        ) ?>

                    </span>

                </div>


                <div>

                    <span
                        class="status-badge <?= htmlspecialchars($statusClass) ?>"
                    >

                        <?= htmlspecialchars(
                            $inquiry["InquiryStatus"]
                        ) ?>

                    </span>

                </div>


            </div>


            <!-- =========================================================
                 CHAT
                 ========================================================= -->

            <section class="card chat-card">


                <div class="chat-header">

                    <h2 class="chat-header-title">
                        Messages
                    </h2>

                    <p class="chat-header-subtitle">

                        Conversation for Inquiry
                        #<?= (int) $inquiry["InterestID"] ?>

                    </p>

                </div>


                <div
                    class="chat-messages"
                    id="chat-messages"
                >


                    <?php if (count($messages) === 0): ?>

                        <div class="chat-empty">

                            <div>

                                <strong>
                                    No messages yet.
                                </strong>

                                <p>
                                    Start the conversation by sending
                                    a message below.
                                </p>

                            </div>

                        </div>

                    <?php else: ?>


                        <?php foreach ($messages as $chatMessage): ?>

                            <?php

                            $isOwnMessage =
                                (int) $chatMessage["SenderID"]
                                ===
                                $currentUserID;

                            ?>

                            <div
                                class="message-row <?= $isOwnMessage
                                    ? "message-row-own"
                                    : "message-row-other"
                                ?>"
                            >

                                <div class="message-bubble-wrapper">


                                    <div class="message-sender">

                                        <?php if ($isOwnMessage): ?>

                                            You

                                        <?php else: ?>

                                            <?= htmlspecialchars(
                                                $chatMessage["FirstName"]
                                                . " "
                                                . $chatMessage["LastName"]
                                            ) ?>

                                        <?php endif; ?>

                                    </div>


                                    <div class="message-bubble">

                                        <?= htmlspecialchars(
                                            $chatMessage["MessageText"]
                                        ) ?>

                                    </div>


                                    <div class="message-time">

                                        <?= htmlspecialchars(
                                            formatDateTime(
                                                $chatMessage["CreatedAt"]
                                            )
                                        ) ?>

                                    </div>


                                </div>

                            </div>

                        <?php endforeach; ?>


                    <?php endif; ?>


                </div>


                <!-- =====================================================
                     MESSAGE COMPOSER
                     ===================================================== -->

                <?php if (
                    !isAdmin()
                    &&
                    $inquiry["InquiryStatus"] !== "Closed"
                    &&
                    $inquiry["InquiryStatus"] !== "Declined"
                ): ?>


                    <div class="chat-compose">

                        <form
                            method="POST"
                            action=""
                        >


                            <input
                                type="hidden"
                                name="csrf_token"
                                value="<?= htmlspecialchars(
                                    generateCSRFToken()
                                ) ?>"
                            >


                            <textarea
                                name="message"
                                id="message"
                                maxlength="2000"
                                required
                                placeholder="Type your message here..."
                                data-character-count="message-count"
                            ></textarea>


                            <div class="chat-compose-footer">


                                <span
                                    class="chat-character-count"
                                    id="message-count"
                                >
                                    0 / 2000
                                </span>


                                <button
                                    type="submit"
                                    class="button"
                                >
                                    Send Message
                                </button>


                            </div>


                        </form>

                    </div>


                <?php else: ?>


                    <div class="chat-compose">

                        <div class="chat-closed-notice">

                            <?php if (isAdmin()): ?>

                                Administrators can view this conversation,
                                but cannot send messages as either participant.

                            <?php elseif (
                                $inquiry["InquiryStatus"] === "Declined"
                            ): ?>

                                This inquiry has been declined. Messaging
                                is no longer available.

                            <?php elseif (
                                $inquiry["InquiryStatus"] === "Closed"
                            ): ?>

                                This inquiry has been closed. Messaging
                                is no longer available.

                            <?php endif; ?>

                        </div>

                    </div>


                <?php endif; ?>


            </section>


        </main>


    </main>

</div>


<?php
require_once __DIR__ . "/../includes/footer.php";
?>


<script>

document.addEventListener("DOMContentLoaded", function () {

    const chatMessages =
        document.getElementById("chat-messages");

    if (chatMessages) {

        chatMessages.scrollTop =
            chatMessages.scrollHeight;

    }


    const messageInput =
        document.getElementById("message");

    const messageCount =
        document.getElementById("message-count");


    if (
        messageInput
        &&
        messageCount
    ) {

        function updateMessageCount() {

            messageCount.textContent =
                messageInput.value.length
                + " / 2000";

        }


        messageInput.addEventListener(
            "input",
            updateMessageCount
        );


        updateMessageCount();

    }

});

</script>


</body>

</html>