<?php

require_once "../includes/auth.php";
require_once "../includes/database.php";
require_once "../includes/functions.php";

requireRole(['Admin', 'Seller', 'User']);

$pageTitle = "Vehicle Chat";
$activePage = "inquiries";

$currentUserId = currentUserId();
$interestID = isset($_GET['interest_id']) ? (int) $_GET['interest_id'] : 0;

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

if ($interestID <= 0) {
    redirect("/visrs/marketplace/inquiries.php");
}

/*
|--------------------------------------------------------------------------
| Load inquiry
|--------------------------------------------------------------------------
*/

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

        vl.Price,
        vl.Description AS ListingDescription,

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

    WHERE vi.InterestID = ?
    LIMIT 1
");

$stmt->execute([$interestID]);
$inquiry = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$inquiry) {
    redirect("/visrs/marketplace/inquiries.php");
}

/*
|--------------------------------------------------------------------------
| Authorization
|--------------------------------------------------------------------------
*/

$isAdmin = hasRole(['Admin']);
$isBuyer = ((int) $inquiry['BuyerID'] === (int) $currentUserId);
$isSeller = ((int) $inquiry['SellerID'] === (int) $currentUserId);

if (!$isAdmin && !$isBuyer && !$isSeller) {
    http_response_code(403);

    die("
        <!DOCTYPE html>
        <html>
        <head>
            <title>VISRS Access Denied</title>
            <link rel='stylesheet' href='/visrs/css/style.css'>
        </head>
        <body>
            <div style='max-width:600px;margin:100px auto;padding:30px;text-align:center;'>
                <h1>Access Denied</h1>
                <p>You are not authorized to view this conversation.</p>
                <a href='/visrs/marketplace/inquiries.php' class='button'>
                    Back to Inquiries
                </a>
            </div>
        </body>
        </html>
    ");
}

/*
|--------------------------------------------------------------------------
| Determine conversation partner
|--------------------------------------------------------------------------
*/

if ($isSeller) {
    $otherUserId = (int) $inquiry['BuyerID'];
    $otherUserName = trim(
        $inquiry['BuyerFirstName'] . ' ' . $inquiry['BuyerLastName']
    );
    $otherUserRole = "Buyer";
} elseif ($isBuyer) {
    $otherUserId = (int) $inquiry['SellerID'];
    $otherUserName = trim(
        $inquiry['SellerFirstName'] . ' ' . $inquiry['SellerLastName']
    );
    $otherUserRole = "Seller";
} else {
    $otherUserId = (int) $inquiry['SellerID'];
    $otherUserName = trim(
        $inquiry['SellerFirstName'] . ' ' . $inquiry['SellerLastName']
    );
    $otherUserRole = "Seller";
}

/*
|--------------------------------------------------------------------------
| Send message
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    requireValidCSRF();

    $messageText = trim($_POST['message'] ?? '');

    if ($messageText === '') {
        $_SESSION['flash_error'] = "Please enter a message.";
        redirect("/visrs/marketplace/chat.php?interest_id=" . $interestID);
    }

    if (strlen($messageText) > 2000) {
        $_SESSION['flash_error'] = "Message cannot exceed 2000 characters.";
        redirect("/visrs/marketplace/chat.php?interest_id=" . $interestID);
    }

    $stmt = $pdo->prepare("
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

    $stmt->execute([
        (int) $inquiry['InterestID'],
        (int) $inquiry['ListingID'],
        (int) $inquiry['VehicleID'],
        (int) $currentUserId,
        (int) $otherUserId,
        $messageText
    ]);

    $messageId = (int) $pdo->lastInsertId();

    /*
    |--------------------------------------------------------------------------
    | Seller response changes Pending -> Contacted
    |--------------------------------------------------------------------------
    */

    if ($isSeller && $inquiry['Status'] === 'Pending') {

        $statusStmt = $pdo->prepare("
            UPDATE vehicle_interests
            SET Status = 'Contacted'
            WHERE InterestID = ?
        ");

        $statusStmt->execute([$interestID]);
    }

    /*
    |--------------------------------------------------------------------------
    | Audit
    |--------------------------------------------------------------------------
    */

    logAudit(
        $pdo,
        "Sent marketplace chat message",
        "vehicle_messages",
        $messageId
    );

    $_SESSION['flash_success'] = "Message sent successfully.";

    redirect("/visrs/marketplace/chat.php?interest_id=" . $interestID);
}

/*
|--------------------------------------------------------------------------
| Mark messages as read
|--------------------------------------------------------------------------
*/

$readStmt = $pdo->prepare("
    UPDATE vehicle_messages
    SET
        IsRead = 1,
        ReadAt = NOW()
    WHERE InterestID = ?
      AND RecipientID = ?
      AND IsRead = 0
");

$readStmt->execute([
    $interestID,
    $currentUserId
]);

/*
|--------------------------------------------------------------------------
| Load messages
|--------------------------------------------------------------------------
*/

$messageStmt = $pdo->prepare("
    SELECT
        vm.MessageID,
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

    ORDER BY vm.CreatedAt ASC, vm.MessageID ASC
");

$messageStmt->execute([$interestID]);

$messages = $messageStmt->fetchAll(PDO::FETCH_ASSOC);

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title><?= e($pageTitle) ?> - VISRS</title>

    <link
        rel="stylesheet"
        href="/visrs/css/style.css"
    >

    <style>

        /*
        |--------------------------------------------------------------------------
        | Marketplace / Inquiry Style
        |--------------------------------------------------------------------------
        */

        .chat-container {
            max-width: 1200px;
            margin: 0 auto;
        }

        .page-heading {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 20px;
            margin-bottom: 24px;
        }

        .page-heading h1 {
            margin: 0;
            font-size: 25px;
            color: #111827;
        }

        .page-heading p {
            margin: 6px 0 0;
            font-size: 14px;
            color: #64748b;
        }

        .page-heading-actions {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        /*
        |--------------------------------------------------------------------------
        | Inquiry-style cards
        |--------------------------------------------------------------------------
        */

        .inquiry-card {
            background: #ffffff;
            border: 1px solid #e5e7eb;
            border-radius: 10px;
            box-shadow: 0 2px 7px rgba(0,0,0,.04);
            margin-bottom: 20px;
        }

        .inquiry-top {
            padding: 20px;
            border-bottom: 1px solid #e5e7eb;
        }

        .vehicle-name {
            font-size: 18px;
            font-weight: 700;
            color: #111827;
        }

        .vehicle-meta {
            margin-top: 5px;
            color: #64748b;
            font-size: 13px;
        }

        /*
        |--------------------------------------------------------------------------
        | Status badge
        |--------------------------------------------------------------------------
        */

        .status-badge {
            display: inline-flex;
            align-items: center;
            padding: 5px 10px;
            border-radius: 999px;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
        }

        .status-pending {
            background: #fef3c7;
            color: #92400e;
        }

        .status-contacted {
            background: #dbeafe;
            color: #1d4ed8;
        }

        .status-accepted {
            background: #dcfce7;
            color: #166534;
        }

        .status-declined {
            background: #fee2e2;
            color: #991b1b;
        }

        .status-closed {
            background: #e5e7eb;
            color: #374151;
        }

        /*
        |--------------------------------------------------------------------------
        | Inquiry information grid
        |--------------------------------------------------------------------------
        */

        .inquiry-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 18px;
            padding: 20px;
        }

        .inquiry-grid-item label {
            display: block;
            margin-bottom: 5px;
            font-size: 10px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .04em;
            color: #94a3b8;
        }

        .inquiry-grid-item div {
            font-size: 13px;
            color: #334155;
        }

        /*
        |--------------------------------------------------------------------------
        | Original inquiry
        |--------------------------------------------------------------------------
        */

        .buyer-message {
            margin: 0 20px 20px;
            padding: 15px;
            background: #f8fafc;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
        }

        .buyer-message-label {
            font-size: 10px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .04em;
            color: #94a3b8;
            margin-bottom: 7px;
        }

        .buyer-message-text {
            font-size: 13px;
            color: #334155;
            line-height: 1.6;
            white-space: pre-wrap;
        }

        /*
        |--------------------------------------------------------------------------
        | Conversation section
        |--------------------------------------------------------------------------
        */

        .conversation-title {
            padding: 20px;
            border-bottom: 1px solid #e5e7eb;
        }

        .conversation-title h2 {
            margin: 0;
            font-size: 18px;
            color: #111827;
        }

        .conversation-title p {
            margin: 5px 0 0;
            font-size: 13px;
            color: #64748b;
        }

        /*
        |--------------------------------------------------------------------------
        | Messages
        |--------------------------------------------------------------------------
        |
        | IMPORTANT:
        | No chat bubbles.
        | No dark background.
        | No avatar.
        | Messages use the same card language as inquiries.php.
        |
        */

        .messages-list {
            padding: 0 20px;
        }

        .message-row {
            padding: 18px 0;
            border-bottom: 1px solid #e5e7eb;
        }

        .message-row:last-child {
            border-bottom: none;
        }

        .message-row.mine {
            background: #f8fafc;
            margin: 0 -20px;
            padding-left: 20px;
            padding-right: 20px;
        }

        .message-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 15px;
            margin-bottom: 8px;
        }

        .message-sender {
            font-size: 13px;
            font-weight: 700;
            color: #111827;
        }

        .message-role {
            display: inline-block;
            margin-left: 7px;
            font-size: 10px;
            color: #64748b;
            text-transform: uppercase;
            font-weight: 700;
        }

        .message-date {
            font-size: 11px;
            color: #94a3b8;
            white-space: nowrap;
        }

        .message-body {
            font-size: 13px;
            color: #334155;
            line-height: 1.65;
            white-space: pre-wrap;
        }

        .empty-messages {
            padding: 40px 20px;
            text-align: center;
            color: #64748b;
            font-size: 13px;
        }

        /*
        |--------------------------------------------------------------------------
        | Composer
        |--------------------------------------------------------------------------
        */

        .composer-card {
            padding: 20px;
        }

        .composer-label {
            display: block;
            margin-bottom: 8px;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            color: #64748b;
        }

        .composer-textarea {
            width: 100%;
            min-height: 120px;
            padding: 12px;
            border: 1px solid #d1d5db;
            border-radius: 8px;
            resize: vertical;
            font-family: inherit;
            font-size: 13px;
            box-sizing: border-box;
        }

        .composer-textarea:focus {
            outline: none;
            border-color: #111827;
            box-shadow: 0 0 0 2px rgba(17,24,39,.08);
        }

        .composer-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 15px;
            margin-top: 10px;
        }

        .character-count {
            font-size: 11px;
            color: #94a3b8;
        }

        .send-button {
            border: none;
            background: #111827;
            color: #ffffff;
            padding: 10px 18px;
            border-radius: 6px;
            cursor: pointer;
            font-size: 13px;
            font-weight: 600;
        }

        .send-button:hover {
            background: #1f2937;
        }

        /*
        |--------------------------------------------------------------------------
        | Footer actions
        |--------------------------------------------------------------------------
        */

        .inquiry-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 15px;
            padding: 15px 20px;
            border-top: 1px solid #e5e7eb;
        }

        .action-button {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 8px 13px;
            border-radius: 6px;
            border: 1px solid #d1d5db;
            background: #ffffff;
            color: #374151;
            text-decoration: none;
            font-size: 12px;
            font-weight: 600;
        }

        .action-button:hover {
            background: #f9fafb;
        }

        /*
        |--------------------------------------------------------------------------
        | Responsive
        |--------------------------------------------------------------------------
        */

        @media (max-width: 800px) {

            .inquiry-grid {
                grid-template-columns: repeat(2, 1fr);
            }

            .page-heading {
                flex-direction: column;
            }

        }

        @media (max-width: 600px) {

            .inquiry-grid {
                grid-template-columns: 1fr;
            }

            .message-header {
                flex-direction: column;
                gap: 4px;
            }

            .composer-footer {
                flex-direction: column;
                align-items: stretch;
            }

            .send-button {
                width: 100%;
            }

            .inquiry-footer {
                flex-direction: column;
                align-items: stretch;
            }

            .action-button {
                width: 100%;
            }

        }

    </style>

</head>

<body>

<div class="layout">

    <?php require_once "../includes/sidebar.php"; ?>

    <main class="main-content">

        <?php require_once "../includes/header.php"; ?>

        <main class="content">

            <div class="chat-container">

                <!-- PAGE HEADING -->

                <div class="page-heading">

                    <div>

                        <h1>Buyer Inquiry</h1>

                        <p>
                            Conversation regarding
                            <?= e($inquiry['Make']) ?>
                            <?= e($inquiry['Model']) ?>
                            <?= e($inquiry['VehicleYear']) ?>
                        </p>

                    </div>

                    <div class="page-heading-actions">

                        <a
                            href="/visrs/marketplace/inquiries.php"
                            class="action-button"
                        >
                            ← Back to Inquiries
                        </a>

                        <a
                            href="/visrs/marketplace/view.php?vehicle_id=<?= (int) $inquiry['VehicleID'] ?>"
                            class="action-button"
                        >
                            View Vehicle
                        </a>

                    </div>

                </div>

                <!-- INQUIRY SUMMARY -->

                <div class="inquiry-card">

                    <div class="inquiry-top">

                        <div style="
                            display:flex;
                            justify-content:space-between;
                            align-items:flex-start;
                            gap:15px;
                            flex-wrap:wrap;
                        ">

                            <div>

                                <div class="vehicle-name">

                                    <?= e($inquiry['Make']) ?>
                                    <?= e($inquiry['Model']) ?>
                                    <?= e($inquiry['VehicleYear']) ?>

                                </div>

                                <div class="vehicle-meta">

                                    Plate:
                                    <?= e($inquiry['PlateNumber']) ?>

                                    &nbsp; • &nbsp;

                                    VIN:
                                    <?= e($inquiry['VIN']) ?>

                                </div>

                            </div>

                            <div>

                                <?php
                                $statusClass = 'status-' . strtolower($inquiry['Status']);
                                ?>

                                <span class="status-badge <?= e($statusClass) ?>">
                                    <?= e($inquiry['Status']) ?>
                                </span>

                            </div>

                        </div>

                    </div>

                    <div class="inquiry-grid">

                        <div class="inquiry-grid-item">

                            <label>Buyer</label>

                            <div>
                                <?= e(
                                    $inquiry['BuyerFirstName'] . ' ' .
                                    $inquiry['BuyerLastName']
                                ) ?>
                            </div>

                        </div>

                        <div class="inquiry-grid-item">

                            <label>Buyer Email</label>

                            <div>
                                <?= e($inquiry['BuyerEmail']) ?>
                            </div>

                        </div>

                        <div class="inquiry-grid-item">

                            <label>Seller</label>

                            <div>
                                <?= e(
                                    $inquiry['SellerFirstName'] . ' ' .
                                    $inquiry['SellerLastName']
                                ) ?>
                            </div>

                        </div>

                        <div class="inquiry-grid-item">

                            <label>Vehicle Price</label>

                            <div>
                                $<?= number_format(
                                    (float) $inquiry['Price'],
                                    2
                                ) ?>
                            </div>

                        </div>

                        <div class="inquiry-grid-item">

                            <label>Vehicle Type</label>

                            <div>
                                <?= e($inquiry['VehicleType'] ?: 'Not specified') ?>
                            </div>

                        </div>

                        <div class="inquiry-grid-item">

                            <label>Inquiry Date</label>

                            <div>
                                <?= formatDateTime($inquiry['CreatedAt']) ?>
                            </div>

                        </div>

                    </div>

                    <?php if (!empty($inquiry['Message'])): ?>

                        <div class="buyer-message">

                            <div class="buyer-message-label">
                                Original Inquiry
                            </div>

                            <div class="buyer-message-text">
                                <?= e($inquiry['Message']) ?>
                            </div>

                        </div>

                    <?php endif; ?>

                </div>


                <!-- CONVERSATION -->

                <div class="inquiry-card">

                    <div class="conversation-title">

                        <h2>Conversation</h2>

                        <p>
                            Messages between you and
                            <?= e($otherUserName) ?>
                            (<?= e($otherUserRole) ?>)
                        </p>

                    </div>

                    <div class="messages-list">

                        <?php if (empty($messages)): ?>

                            <div class="empty-messages">

                                No messages have been sent yet.

                                Start the conversation below.

                            </div>

                        <?php else: ?>

                            <?php foreach ($messages as $message): ?>

                                <?php
                                $isMine =
                                    (int) $message['SenderID']
                                    === (int) $currentUserId;
                                ?>

                                <div class="message-row <?= $isMine ? 'mine' : '' ?>">

                                    <div class="message-header">

                                        <div>

                                            <span class="message-sender">

                                                <?= e(
                                                    $message['FirstName'] . ' ' .
                                                    $message['LastName']
                                                ) ?>

                                            </span>

                                            <span class="message-role">
                                                <?= e($message['Role']) ?>
                                            </span>

                                        </div>

                                        <div class="message-date">

                                            <?= formatDateTime(
                                                $message['CreatedAt']
                                            ) ?>

                                        </div>

                                    </div>

                                    <div class="message-body">

                                        <?= e($message['MessageText']) ?>

                                    </div>

                                </div>

                            <?php endforeach; ?>

                        <?php endif; ?>

                    </div>

                </div>


                <!-- SEND MESSAGE -->

                <div class="inquiry-card">

                    <div class="composer-card">

                        <form
                            method="POST"
                            action=""
                        >

                            <input
                                type="hidden"
                                name="csrf_token"
                                value="<?= e($_SESSION['csrf_token']) ?>"
                            >

                            <label
                                for="message"
                                class="composer-label"
                            >
                                Message
                            </label>

                            <textarea
                                id="message"
                                name="message"
                                class="composer-textarea"
                                maxlength="2000"
                                placeholder="Type your message..."
                                required
                            ></textarea>

                            <div class="composer-footer">

                                <span
                                    class="character-count"
                                    id="characterCount"
                                >
                                    0 / 2000
                                </span>

                                <button
                                    type="submit"
                                    class="send-button"
                                >
                                    Send Message
                                </button>

                            </div>

                        </form>

                    </div>

                </div>

            </div>

        </main>

    </main>

</div>

<?php require_once "../includes/footer.php"; ?>

<script>

document.addEventListener("DOMContentLoaded", function () {

    const textarea = document.getElementById("message");
    const counter = document.getElementById("characterCount");

    if (textarea && counter) {

        function updateCounter() {

            counter.textContent =
                textarea.value.length + " / 2000";

        }

        textarea.addEventListener(
            "input",
            updateCounter
        );

        updateCounter();

    }

    /*
    |--------------------------------------------------------------------------
    | Enter sends message
    |--------------------------------------------------------------------------
    */

    if (textarea) {

        textarea.addEventListener("keydown", function (event) {

            if (
                event.key === "Enter" &&
                !event.shiftKey
            ) {

                event.preventDefault();

                if (textarea.value.trim() !== "") {

                    textarea.form.submit();

                }

            }

        });

    }

});

</script>

</body>
</html>