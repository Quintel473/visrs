<?php

require_once "../includes/auth.php";
require_once "../includes/database.php";

requireRole(["Admin", "Police"]);

$basePath = "../";
$activePage = "owners";

$ownerID = $_GET["id"] ?? $_POST["OwnerID"] ?? "";

if (!is_numeric($ownerID)) {
    header("Location: index.php");
    exit;
}


/* Get existing owner */

$stmt = $pdo->prepare("
    SELECT *
    FROM owners
    WHERE OwnerID = ?
    LIMIT 1
");

$stmt->execute([$ownerID]);

$owner = $stmt->fetch();

if (!$owner) {

    http_response_code(404);

    echo "<h1>Owner Not Found</h1>";
    echo "<p>The requested owner could not be found.</p>";
    echo '<p><a href="index.php">Return to Owners</a></p>';

    exit;
}


$error = "";
$success = "";


/* Process update */

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $firstName = trim($_POST["FirstName"] ?? "");
    $lastName = trim($_POST["LastName"] ?? "");
    $address = trim($_POST["Address"] ?? "");
    $phone = trim($_POST["Phone"] ?? "");
    $email = trim($_POST["Email"] ?? "");


    /* Validation */

    if ($firstName === "" || $lastName === "") {

        $error = "First name and last name are required.";

    } elseif ($email !== "" && !filter_var($email, FILTER_VALIDATE_EMAIL)) {

        $error = "Please enter a valid email address.";

    } else {

        try {

            $stmt = $pdo->prepare("
                UPDATE owners
                SET
                    FirstName = ?,
                    LastName = ?,
                    Address = ?,
                    Phone = ?,
                    Email = ?
                WHERE OwnerID = ?
            ");

            $stmt->execute([
                $firstName,
                $lastName,
                $address !== "" ? $address : null,
                $phone !== "" ? $phone : null,
                $email !== "" ? $email : null,
                $ownerID
            ]);


            /* Create audit log */

            $audit = $pdo->prepare("
                INSERT INTO audit_logs
                (
                    UserID,
                    Action,
                    TableAffected,
                    RecordID,
                    IPAddress
                )
                VALUES (?, ?, ?, ?, ?)
            ");

            $audit->execute([
                $_SESSION["UserID"],
                "Updated owner",
                "owners",
                $ownerID,
                $_SERVER["REMOTE_ADDR"] ?? null
            ]);


            $success = "Owner information updated successfully.";


            /* Reload updated owner */

            $stmt = $pdo->prepare("
                SELECT *
                FROM owners
                WHERE OwnerID = ?
                LIMIT 1
            ");

            $stmt->execute([$ownerID]);

            $owner = $stmt->fetch();

        } catch (PDOException $e) {

            $error = "Unable to update the owner record.";

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
        VISRS - Edit Owner
    </title>

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

        <?php

            $pageTitle = "Owners";

            include __DIR__ . "/../includes/header.php";

        ?>


        <!-- PAGE CONTENT -->

        <main class="content">


            <div class="page-title">

                <h1>
                    Edit Owner
                </h1>

                <p class="page-subtitle">

                    Update the owner's contact and
                    registration information.

                </p>

            </div>


            <!-- SUCCESS MESSAGE -->

            <?php if ($success !== ""): ?>

                <div
                    style="
                        background: #dcfce7;
                        color: #166534;
                        padding: 15px;
                        border-radius: 8px;
                        margin-bottom: 20px;
                    "
                >

                    <?= htmlspecialchars($success) ?>

                </div>

            <?php endif; ?>


            <!-- ERROR MESSAGE -->

            <?php if ($error !== ""): ?>

                <div
                    style="
                        background: #fee2e2;
                        color: #991b1b;
                        padding: 15px;
                        border-radius: 8px;
                        margin-bottom: 20px;
                    "
                >

                    <?= htmlspecialchars($error) ?>

                </div>

            <?php endif; ?>


            <!-- EDIT FORM -->

            <section class="card">

                <h2>
                    Owner Information
                </h2>


                <form
                    method="POST"
                    action=""
                    style="margin-top: 20px;"
                >

                    <input
                        type="hidden"
                        name="OwnerID"
                        value="<?= htmlspecialchars($owner["OwnerID"]) ?>"
                    >


                    <!-- FIRST NAME -->

                    <div class="form-group">

                        <label for="FirstName">
                            First Name *
                        </label>

                        <input
                            type="text"
                            id="FirstName"
                            name="FirstName"
                            value="<?= htmlspecialchars($owner["FirstName"]) ?>"
                            required
                        >

                    </div>


                    <!-- LAST NAME -->

                    <div class="form-group">

                        <label for="LastName">
                            Last Name *
                        </label>

                        <input
                            type="text"
                            id="LastName"
                            name="LastName"
                            value="<?= htmlspecialchars($owner["LastName"]) ?>"
                            required
                        >

                    </div>


                    <!-- ADDRESS -->

                    <div class="form-group">

                        <label for="Address">
                            Address
                        </label>

                        <input
                            type="text"
                            id="Address"
                            name="Address"
                            value="<?= htmlspecialchars($owner["Address"] ?? "") ?>"
                        >

                    </div>


                    <!-- PHONE -->

                    <div class="form-group">

                        <label for="Phone">
                            Phone Number
                        </label>

                        <input
                            type="text"
                            id="Phone"
                            name="Phone"
                            value="<?= htmlspecialchars($owner["Phone"] ?? "") ?>"
                        >

                    </div>


                    <!-- EMAIL -->

                    <div class="form-group">

                        <label for="Email">
                            Email Address
                        </label>

                        <input
                            type="email"
                            id="Email"
                            name="Email"
                            value="<?= htmlspecialchars($owner["Email"] ?? "") ?>"
                        >

                    </div>


                    <!-- BUTTONS -->

                    <div
                        style="
                            display: flex;
                            gap: 10px;
                            margin-top: 20px;
                            flex-wrap: wrap;
                        "
                    >

                        <a
                            href="index.php"
                            class="button button-secondary"
                        >
                            Cancel
                        </a>


                        <button
                            type="submit"
                            class="button"
                        >
                            Save Changes
                        </button>

                    </div>

                </form>

            </section>

        </main>

    </div>

</div>


<script src="../js/app.js"></script>

</body>

</html>