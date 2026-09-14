<?php

require_once "../includes/auth.php";
require_once "../includes/database.php";

$basePath = "../";
$activePage = "owners";

$error = "";
$success = "";

/*
|--------------------------------------------------------------------------
| Handle Form Submission
|--------------------------------------------------------------------------
*/

if ($_SERVER["REQUEST_METHOD"] === "POST") {

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

    } elseif ($email !== "" && !filter_var($email, FILTER_VALIDATE_EMAIL)) {

        $error = "Please enter a valid email address.";

    } else {

        /*
        |--------------------------------------------------------------------------
        | Insert Owner
        |--------------------------------------------------------------------------
        */

        try {

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

            $ownerID = $pdo->lastInsertId();


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
                $_SESSION["UserID"],
                "Added owner",
                "owners",
                $ownerID,
                $_SERVER["REMOTE_ADDR"] ?? null
            ]);


            $success = "Owner added successfully.";


            /*
            |--------------------------------------------------------------------------
            | Clear Form
            |--------------------------------------------------------------------------
            */

            $firstName = "";
            $lastName = "";
            $address = "";
            $phone = "";
            $email = "";

        } catch (PDOException $e) {

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

    <title>VISRS - Add Owner</title>

    <link
        rel="stylesheet"
        href="../css/style.css"
    >

</head>

<body>

<div class="layout">

    <!-- SIDEBAR -->

    <aside class="sidebar">

        <?php require_once "../includes/sidebar.php"; ?>

    </aside>


    <!-- MAIN CONTENT -->

    <div class="main-content">

        <!-- TOP BAR -->

        <header class="topbar">

            <div class="topbar-title">
                Add Owner
            </div>

            <div class="user-info">

                <span>
                    <?= htmlspecialchars($_SESSION["FirstName"]) ?>
                    <?= htmlspecialchars($_SESSION["LastName"]) ?>
                </span>

                <div class="user-avatar">

                    <?= strtoupper(
                        substr($_SESSION["FirstName"], 0, 1)
                    ) ?>

                </div>

            </div>

        </header>


        <!-- PAGE CONTENT -->

        <main class="content">

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

                <div style="
                    background: #dcfce7;
                    color: #166534;
                    padding: 12px;
                    border-radius: 6px;
                    margin-bottom: 20px;
                ">

                    <?= htmlspecialchars($success) ?>

                </div>

            <?php endif; ?>


            <!-- FORM -->

            <section class="card">

                <h2>
                    Owner Information
                </h2>

                <form method="POST" action="">


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
                            value="<?= htmlspecialchars($firstName ?? "") ?>"
                            maxlength="100"
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
                            value="<?= htmlspecialchars($lastName ?? "") ?>"
                            maxlength="100"
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
                            value="<?= htmlspecialchars($address ?? "") ?>"
                            maxlength="255"
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
                            value="<?= htmlspecialchars($phone ?? "") ?>"
                            maxlength="30"
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
                            value="<?= htmlspecialchars($email ?? "") ?>"
                            maxlength="150"
                        >

                    </div>


                    <!-- BUTTONS -->

                    <div style="
                        display: flex;
                        gap: 10px;
                        margin-top: 25px;
                    ">

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

        </main>

    </div>

</div>

<script src="../js/app.js"></script>

</body>

</html>