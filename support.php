<?php

/*
 * VISRS — Support
 *
 * Public support page with contact channels, FAQ,
 * and quick links to common help topics.
 */

if (session_status() === PHP_SESSION_NONE) {

    session_set_cookie_params([
        "httponly" => true,
        "samesite" => "Lax"
    ]);

    session_start();
}


/*
 * Global <head>
 */

$basePath  = "/visrs/";

$pageTitle = "Support";

require_once "includes/head.php";

?>


<body class="legal-body">


<div class="legal-page">


    <?php require_once "includes/legal-nav.php"; ?>


    <main class="legal-content">


        <!-- =============================================
             PAGE HEADER
             ============================================= -->

        <section class="legal-hero">

            <span class="legal-hero-badge">
                Support
            </span>

            <h1 class="legal-hero-title">
                How can we help you?
            </h1>

            <p class="legal-hero-subtitle">
                Find answers to common questions, report an
                issue, or reach the VISRS team directly.
            </p>

        </section>


        <!-- =============================================
             QUICK CONTACT CARDS
             ============================================= -->

        <section class="legal-grid">

            <a
                href="mailto:support@visrs.local"
                class="legal-tile"
            >

                <span class="legal-tile-icon">✉</span>

                <h3 class="legal-tile-title">
                    Email Support
                </h3>

                <p class="legal-tile-text">
                    support@visrs.local<br>
                    Mon&ndash;Fri, 9:00&ndash;17:00
                </p>

            </a>

            <a
                href="tel:+18005550100"
                class="legal-tile"
            >

                <span class="legal-tile-icon">☎</span>

                <h3 class="legal-tile-title">
                    Call Us
                </h3>

                <p class="legal-tile-text">
                    +1 (800) 555-0100<br>
                    Mon&ndash;Fri, 9:00&ndash;17:00
                </p>

            </a>

            <a
                href="#faq"
                class="legal-tile"
            >

                <span class="legal-tile-icon">?</span>

                <h3 class="legal-tile-title">
                    FAQ
                </h3>

                <p class="legal-tile-text">
                    Browse the most common questions about
                    accounts, vehicles, and records.
                </p>

            </a>

        </section>


        <!-- =============================================
             FAQ
             ============================================= -->

        <section
            class="legal-section"
            id="faq"
        >

            <h2 class="legal-section-title">
                Frequently asked questions
            </h2>


            <details class="legal-faq">

                <summary>
                    How do I reset my password?
                </summary>

                <p>
                    On the sign-in page, click
                    <strong>Forgot password?</strong>
                    and follow the instructions emailed to
                    your registered address. If you don't
                    receive an email within 10 minutes,
                    check your spam folder or contact
                    support.
                </p>

            </details>


            <details class="legal-faq">

                <summary>
                    Why is my account locked?
                </summary>

                <p>
                    Accounts are temporarily locked after
                    several consecutive failed sign-in
                    attempts as a security precaution. The
                    lock automatically expires after the
                    cooldown period. If it persists, contact
                    support with your registered email
                    address.
                </p>

            </details>


            <details class="legal-faq">

                <summary>
                    How do I report a stolen vehicle?
                </summary>

                <p>
                    Sign in as a <strong>Police</strong>
                    or <strong>Admin</strong> user, open the
                    relevant vehicle record, and choose
                    <em>Report stolen</em>. Attach any
                    supporting case numbers or documentation
                    in the notes field.
                </p>

            </details>


            <details class="legal-faq">

                <summary>
                    Can I update my profile information?
                </summary>

                <p>
                    Yes. Once signed in, open your profile
                    from the top-right menu. You can change
                    your name, contact details, and profile
                    picture. Your email address can only be
                    updated by an administrator.
                </p>

            </details>


            <details class="legal-faq">

                <summary>
                    I found incorrect vehicle data. What do I do?
                </summary>

                <p>
                    Please email us at
                    <a href="mailto:support@visrs.local">
                        support@visrs.local
                    </a>
                    with the plate number, the record ID,
                    and a description of the issue. We
                    investigate and correct records within
                    2&ndash;3 business days.
                </p>

            </details>


        </section>


        <!-- =============================================
             STILL NEED HELP
             ============================================= -->

        <section class="legal-section legal-callout">

            <h2 class="legal-section-title">
                Still need help?
            </h2>

            <p class="legal-section-text">
                Our team is available during business
                hours. For urgent security matters
                involving stolen vehicles, please contact
                your local authorities first.
            </p>

            <a
                href="mailto:support@visrs.local"
                class="legal-cta"
            >
                Contact support &nbsp;→
            </a>

        </section>


    </main>


    <!-- FOOTER -->

    <footer class="legal-footer">

        <p>
            &copy; <?= date("Y") ?> VISRS &nbsp;·&nbsp;
            <a href="/visrs/support.php">Support</a>
            &nbsp;·&nbsp;
            <a href="/visrs/privacy.php">Privacy</a>
        </p>

    </footer>


</div>


<!-- VISRS GLOBAL JAVASCRIPT -->

<script
    src="/visrs/js/app.js"
    defer
></script>


</body>

</html>