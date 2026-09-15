<?php

/*
 * VISRS Global Header
 *
 * This provides the same topbar on every page.
 *
 * The global JavaScript file is loaded from footer.php.
 * This file is responsible only for the VISRS topbar.
 */

?>

<header class="topbar">

    <div class="topbar-title">
        <?= htmlspecialchars($pageTitle ?? "VISRS") ?>
    </div>


    <div class="user-info">

        <span>
            <?php

            if (
                isset($_SESSION["FirstName"]) &&
                isset($_SESSION["LastName"])
            ) {

                echo htmlspecialchars(
                    $_SESSION["FirstName"] . " " . $_SESSION["LastName"]
                );

            } else {

                echo "User";

            }

            ?>
        </span>


        <div class="user-avatar">

            <?php

            if (!empty($_SESSION["FirstName"])) {

                echo htmlspecialchars(
                    strtoupper(
                        substr(
                            $_SESSION["FirstName"],
                            0,
                            1
                        )
                    )
                );

            } else {

                echo "U";

            }

            ?>

        </div>

    </div>

</header>