<?php

require_once __DIR__ . "/../includes/auth.php";
requireRole(["Admin", "Police"]);

require_once __DIR__ . "/../includes/database.php";
require_once __DIR__ . "/../includes/functions.php";

$pageTitle = "Add Owner";
$pageSubtitle = "Register a new vehicle owner";

$activePage = "owners";

$error = "";
$success = "";

$firstName = "";
$lastName = "";
$address = "";
$phone = "";
$email = "";


/*
|--------------------------------------------------------------------------
| Handle Form Submission
|--------------------------------------------------------------------------
*/

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    requireValidCSRF();

    $firstName = trim($_POST["first_name"] ?? "");
    $lastName = trim($_POST["last_name"] ?? "");
    $address = trim($_POST["address"] ?? "");
    $phone = trim($_POST["phone"] ?? "");
    $email = trim($_POST["email"] ?? "");


    /*
    |--------------------------------------------------------------------------
    | Validation
    |--------------------------------------------------------------------------
    */

    if ($firstName === "" || $lastName === "") {

        $error = "First name and last name are required.";

    } elseif (strlen($firstName) > 100) {

        $error = "First name cannot exceed 100 characters.";

    } elseif (strlen($lastName) > 100) {

        $error = "Last name cannot exceed 100 characters.";

    } elseif (strlen($address) > 255) {

        $error = "Address cannot exceed 255 characters.";

    } elseif (strlen($phone) > 30) {

        $error = "Phone number cannot exceed 30 characters.";

    } elseif (strlen($email) > 150) {

        $error = "Email address cannot exceed 150 characters.";

    } elseif (
        $email !== "" &&
        !filter_var($email, FILTER_VALIDATE_EMAIL)
    ) {

        $error = "Please enter a valid email address.";

    } else {

        /*
        |--------------------------------------------------------------------------
        | Insert Owner + Audit Log
        |--------------------------------------------------------------------------
        */

        try {

            $pdo->beginTransaction();


            $stmt = $pdo->prepare("
                INSERT INTO owners (
                    FirstName,
                    LastName,
                    Address,
                    Phone,
                    Email
                )
                VALUES (?, ?, ?, ?, ?)
            ");

            $stmt->execute([
                $firstName,
                $lastName,
                $address !== "" ? $address : null,
                $phone !== "" ? $phone : null,
                $email !== "" ? $email : null
            ]);


            /*
            |--------------------------------------------------------------------------
            | Get New Owner ID
            |--------------------------------------------------------------------------
            */

            $ownerID = (int) $pdo->lastInsertId();


            /*
            |--------------------------------------------------------------------------
            | Record Audit Log
            |--------------------------------------------------------------------------
            */

            $logStmt = $pdo->prepare("
                INSERT INTO audit_logs (
                    UserID,
                    Action,
                    TableAffected,
                    RecordID,
                    IPAddress
                )
                VALUES (?, ?, ?, ?, ?)
            ");

            $logStmt->execute([
                (int) $_SESSION["UserID"],
                "Added owner",
                "owners",
                $ownerID,
                $_SERVER["REMOTE_ADDR"] ?? null
            ]);


            $pdo->commit();


            /*
            |--------------------------------------------------------------------------
            | Success
            |--------------------------------------------------------------------------
            */

            $success = "Owner added successfully.";

            $firstName = "";
            $lastName = "";
            $address = "";
            $phone = "";
            $email = "";


        } catch (PDOException $e) {

            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            $error = "Unable to add the owner. Please try again.";

        }

    }

}

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

</head>

<body>

<div class="layout">

    <?php include __DIR__ . "/../includes/sidebar.php"; ?>

    <main class="main-content">

        <?php include __DIR__ . "/../includes/header.php"; ?>

        <div class="content">

            <h1 class="page-title">
                Add Owner
            </h1>

            <p class="page-subtitle">
                Enter the information below to register a vehicle owner.
            </p>


            <?php if ($error !== ""): ?>

                <div class="error-message">

                    <?= htmlspecialchars($error) ?>

                </div>

            <?php endif; ?>


            <?php if ($success !== ""): ?>

                <div
                    class="auto-dismiss"
                    style="
                        background:#dcfce7;
                        color:#166534;
                        padding:12px;
                        border-radius:6px;
                        margin-bottom:20px;
                    "
                >

                    <?= htmlspecialchars($success) ?>

                </div>

            <?php endif; ?>


            <!-- OWNER FORM -->

            <section class="card">

                <h2>
                    Owner Information
                </h2>

                <form
                    method="POST"
                    action=""
                >

                    <!-- CSRF PROTECTION -->

                    <input
                        type="hidden"
                        name="csrf_token"
                        value="<?= htmlspecialchars(generateCSRFToken()) ?>"
                    >


                    <!-- FIRST NAME -->

                    <div class="form-group">

                        <label for="first_name">
                            First Name *
                        </label>

                        <input
                            type="text"
                            id="first_name"
                            name="first_name"
                            placeholder="Enter first name"
                            value="<?= htmlspecialchars($firstName) ?>"
                            maxlength="100"
                            autocomplete="given-name"
                            required
                        >

                    </div>


                    <!-- LAST NAME -->

                    <div class="form-group">

                        <label for="last_name">
                            Last Name *
                        </label>

                        <input
                            type="text"
                            id="last_name"
                            name="last_name"
                            placeholder="Enter last name"
                            value="<?= htmlspecialchars($lastName) ?>"
                            maxlength="100"
                            autocomplete="family-name"
                            required
                        >

                    </div>


                    <!-- ADDRESS -->

                    <div class="form-group">

                        <label for="address">
                            Address
                        </label>

                        <input
                            type="text"
                            id="address"
                            name="address"
                            placeholder="Enter residential address"
                            value="<?= htmlspecialchars($address) ?>"
                            maxlength="255"
                            autocomplete="street-address"
                        >

                    </div>


                    <!-- PHONE -->

                    <div class="form-group">

                        <label for="phone">
                            Phone Number
                        </label>

                        <input
                            type="tel"
                            id="phone"
                            name="phone"
                            placeholder="e.g. 473-555-1234"
                            value="<?= htmlspecialchars($phone) ?>"
                            maxlength="30"
                            autocomplete="tel"
                        >

                    </div>


                    <!-- EMAIL -->

                    <div class="form-group">

                        <label for="email">
                            Email Address
                        </label>

                        <input
                            type="email"
                            id="email"
                            name="email"
                            placeholder="e.g. john@example.com"
                            value="<?= htmlspecialchars($email) ?>"
                            maxlength="150"
                            autocomplete="email"
                        >

                    </div>


                    <!-- BUTTONS -->

                    <div
                        style="
                            display:flex;
                            gap:10px;
                            margin-top:25px;
                            flex-wrap:wrap;
                        "
                    >

                        <button
                            type="submit"
                            class="button"
                        >
                            Add Owner
                        </button>

                        <a
                            href="index.php"
                            class="button button-secondary"
                        >
                            Cancel
                        </a>

                    </div>

                </form>

            </section>

        </div>

    </main>

</div>

<?php include __DIR__ . "/../includes/footer.php"; ?>