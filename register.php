<?php

require_once __DIR__ . "/includes/database.php";

$error = "";
$success = "";

$firstName = "";
$lastName = "";
$email = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $firstName = trim($_POST["FirstName"] ?? "");
    $lastName = trim($_POST["LastName"] ?? "");
    $email = trim($_POST["Email"] ?? "");
    $password = $_POST["Password"] ?? "";
    $confirmPassword = $_POST["ConfirmPassword"] ?? "";


    /*
     * Validate required fields
     */

    if (
        $firstName === "" ||
        $lastName === "" ||
        $email === "" ||
        $password === "" ||
        $confirmPassword === ""
    ) {

        $error = "Please complete all required fields.";

    }


    /*
     * Validate email
     */

    elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {

        $error = "Please enter a valid email address.";

    }


    /*
     * Check password length
     */

    elseif (strlen($password) < 8) {

        $error = "Password must be at least 8 characters long.";

    }


    /*
     * Check password confirmation
     */

    elseif ($password !== $confirmPassword) {

        $error = "Passwords do not match.";

    }


    /*
     * Check whether email already exists
     */

    else {

        $stmt = $pdo->prepare("
            SELECT UserID
            FROM users
            WHERE Email = ?
            LIMIT 1
        ");

        $stmt->execute([$email]);

        $existingUser = $stmt->fetch();


        if ($existingUser) {

            $error = "An account with this email address already exists.";

        }

    }


    /*
     * Create account
     */

    if ($error === "") {

        /*
         * Never allow public registration to choose
         * an administrative or privileged role.
         *
         * Every public registration receives the
         * standard User role.
         */

        $role = "User";


        /*
         * Securely hash the password.
         */

        $hashedPassword = password_hash(
            $password,
            PASSWORD_DEFAULT
        );


        $stmt = $pdo->prepare("
            INSERT INTO users
            (
                FirstName,
                LastName,
                Email,
                Password,
                Role
            )
            VALUES
            (
                ?,
                ?,
                ?,
                ?,
                ?
            )
        ");


        try {

            $stmt->execute([
                $firstName,
                $lastName,
                $email,
                $hashedPassword,
                $role
            ]);


            /*
             * Registration successful.
             */

            $success = "Your account has been created successfully. You can now log in.";

            /*
             * Clear the form fields.
             */

            $firstName = "";
            $lastName = "";
            $email = "";

        } catch (PDOException $e) {

            /*
             * Handle duplicate email or other database errors
             * without exposing database information to the user.
             */

            $error = "Unable to create your account. Please try again.";

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

    <title>Create Account - VISRS</title>

    <link
        rel="stylesheet"
        href="/visrs/css/style.css"
    >

</head>


<body>


<div
    style="
        min-height:100vh;
        display:flex;
        align-items:center;
        justify-content:center;
        padding:40px 20px;
        background:#f3f4f6;
    "
>


    <div
        class="card"
        style="
            width:100%;
            max-width:520px;
        "
    >


        <!-- VISRS Branding -->

        <div
            style="
                text-align:center;
                margin-bottom:30px;
            "
        >

            <h1
                style="
                    margin:0;
                    font-size:32px;
                    font-weight:700;
                "
            >
                VISRS
            </h1>

            <p
                style="
                    margin:8px 0 0 0;
                    color:#6b7280;
                    font-size:14px;
                "
            >
                Vehicle Information Search & Retrieval System
            </p>

        </div>


        <!-- Page Heading -->

        <div
            style="
                margin-bottom:25px;
            "
        >

            <h2
                style="
                    margin:0 0 8px 0;
                "
            >
                Create Account
            </h2>

            <p
                style="
                    margin:0;
                    color:#6b7280;
                "
            >
                Create a VISRS account to access the system.
            </p>

        </div>


        <!-- Error Message -->

        <?php if ($error !== ""): ?>

            <div
                style="
                    background:#fee2e2;
                    color:#991b1b;
                    padding:14px;
                    border-radius:8px;
                    margin-bottom:20px;
                "
            >

                <strong>
                    Registration Error
                </strong>

                <p
                    style="
                        margin:6px 0 0 0;
                    "
                >
                    <?= htmlspecialchars($error) ?>
                </p>

            </div>

        <?php endif; ?>


        <!-- Success Message -->

        <?php if ($success !== ""): ?>

            <div
                style="
                    background:#dcfce7;
                    color:#166534;
                    padding:14px;
                    border-radius:8px;
                    margin-bottom:20px;
                "
            >

                <strong>
                    Account Created
                </strong>

                <p
                    style="
                        margin:6px 0 0 0;
                    "
                >
                    <?= htmlspecialchars($success) ?>
                </p>

            </div>

        <?php endif; ?>


        <!-- Registration Form -->

        <form
            method="POST"
            action=""
        >


            <!-- First Name -->

            <div class="form-group">

                <label for="FirstName">
                    First Name
                </label>

                <input
                    type="text"
                    id="FirstName"
                    name="FirstName"
                    value="<?= htmlspecialchars($firstName) ?>"
                    required
                    maxlength="100"
                    autocomplete="given-name"
                    class="auto-focus"
                >

            </div>


            <!-- Last Name -->

            <div class="form-group">

                <label for="LastName">
                    Last Name
                </label>

                <input
                    type="text"
                    id="LastName"
                    name="LastName"
                    value="<?= htmlspecialchars($lastName) ?>"
                    required
                    maxlength="100"
                    autocomplete="family-name"
                >

            </div>


            <!-- Email -->

            <div class="form-group">

                <label for="Email">
                    Email Address
                </label>

                <input
                    type="email"
                    id="Email"
                    name="Email"
                    value="<?= htmlspecialchars($email) ?>"
                    required
                    maxlength="150"
                    autocomplete="email"
                >

            </div>


            <!-- Password -->

            <div class="form-group">

                <label for="Password">
                    Password
                </label>

                <div
                    style="
                        position:relative;
                    "
                >

                    <input
                        type="password"
                        id="Password"
                        name="Password"
                        required
                        minlength="8"
                        autocomplete="new-password"
                        style="
                            padding-right:80px;
                        "
                    >

                    <button
                        type="button"
                        data-toggle-password="Password"
                        style="
                            position:absolute;
                            right:10px;
                            top:50%;
                            transform:translateY(-50%);
                            border:none;
                            background:transparent;
                            cursor:pointer;
                            color:#374151;
                            font-weight:600;
                        "
                    >
                        Show
                    </button>

                </div>

                <small
                    style="
                        display:block;
                        margin-top:6px;
                        color:#6b7280;
                    "
                >
                    Password must contain at least 8 characters.
                </small>

            </div>


            <!-- Confirm Password -->

            <div class="form-group">

                <label for="ConfirmPassword">
                    Confirm Password
                </label>

                <div
                    style="
                        position:relative;
                    "
                >

                    <input
                        type="password"
                        id="ConfirmPassword"
                        name="ConfirmPassword"
                        required
                        minlength="8"
                        autocomplete="new-password"
                        style="
                            padding-right:80px;
                        "
                    >

                    <button
                        type="button"
                        data-toggle-password="ConfirmPassword"
                        style="
                            position:absolute;
                            right:10px;
                            top:50%;
                            transform:translateY(-50%);
                            border:none;
                            background:transparent;
                            cursor:pointer;
                            color:#374151;
                            font-weight:600;
                        "
                    >
                        Show
                    </button>

                </div>

            </div>


            <!-- Account Role Information -->

            <div
                style="
                    background:#f3f4f6;
                    padding:14px;
                    border-radius:8px;
                    margin-top:20px;
                    margin-bottom:25px;
                "
            >

                <strong>
                    Account Type
                </strong>

                <p
                    style="
                        margin:6px 0 0 0;
                        color:#6b7280;
                        font-size:14px;
                    "
                >
                    New accounts are registered as standard User accounts.
                    Administrative and staff roles can only be assigned by
                    an authorized administrator.
                </p>

            </div>


            <!-- Buttons -->

            <div
                style="
                    display:flex;
                    gap:12px;
                    flex-wrap:wrap;
                "
            >

                <button
                    type="submit"
                    class="button"
                >
                    Create Account
                </button>


                <a
                    href="/visrs/login.php"
                    class="button button-secondary"
                >
                    Back to Login
                </a>

            </div>


        </form>


    </div>


</div>


<!-- Global VISRS JavaScript -->

<script
    src="/visrs/js/app.js"
    defer
></script>


</body>

</html>