<?php

/*
 * VISRS Global Header
 *
 * This provides the same topbar on every page.
 *
 * The global JavaScript file is loaded from footer.php.
 * This file provides the VISRS topbar and language selector.
 */

?>

<header class="topbar">

    <div class="topbar-title">
        <?= htmlspecialchars($pageTitle ?? "VISRS") ?>
    </div>


    
    <div class="user-info">

        <!-- Name + avatar link to the logged-in user's profile page -->
        <a
            href="/visrs/users/view.php?id=<?= (int) ($_SESSION["UserID"] ?? 0) ?>"
            class="profile-link"
            title="View profile"
            style="display: flex; flex-direction: row; align-items: center; gap: 10px;"
        >

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

        </a>

    </div>

</header>