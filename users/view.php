<?php

require_once __DIR__ . "/../includes/auth.php";
require_once __DIR__ . "/../includes/database.php";
require_once __DIR__ . "/../includes/functions.php";

$basePath = "../";
$activePage = "users";

$pageTitle = "View User";
$pageSubtitle = "View VISRS user account information";

$currentUserID = (int) ($_SESSION["UserID"] ?? 0);
$isAdmin = (($_SESSION["Role"] ?? "") === "Admin");

$userID = isset($_GET["id"]) ? (int) $_GET["id"] : $currentUserID;

if ($userID <= 0) {
    header("Location: index.php");
    exit;
}

if (!$isAdmin && $userID !== $currentUserID) {
    header("Location: view.php?id=" . $currentUserID);
    exit;
}

$stmt = $pdo->prepare("SELECT UserID, FirstName, LastName, Email, Role, CreatedAt FROM users WHERE UserID = ? LIMIT 1");
$stmt->execute([$userID]);
$user = $stmt->fetch();

if (!$user) {
    header("Location: index.php?error=not_found");
    exit;
}

$isCurrentUser = ((int) $user["UserID"] === $currentUserID);

/* 
|--------------------------------------------------------------------------
| Handle Admin Password Reset 
|--------------------------------------------------------------------------
*/
$passwordMessage = "";
$passwordError = "";

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["action"]) && $_POST["action"] === "admin_change_password" && $isAdmin) {
    requireValidCSRF();
    
    $newPass = $_POST["new_password"] ?? "";
    
    if (strlen($newPass) < 8) {
        $passwordError = "Password must be at least 8 characters.";
    } else {
        try {
            $hashed = password_hash($newPass, PASSWORD_DEFAULT);
            $updateStmt = $pdo->prepare("UPDATE users SET Password = ? WHERE UserID = ?");
            $updateStmt->execute([$hashed, $userID]);
            
            logAudit($pdo, "Admin reset password for User #" . $userID, "users", $userID);
            $passwordMessage = "Password has been successfully changed.";
        } catch (Throwable $e) {
            $passwordError = "Failed to update password.";
        }
    }
}

$roleClass = "user-role user-role-default";
if ($user["Role"] === "Admin") $roleClass = "user-role user-role-admin";
elseif ($user["Role"] === "Police") $roleClass = "user-role user-role-police";
elseif ($user["Role"] === "Seller") $roleClass = "user-role user-role-seller";

$displayRole = ($user["Role"] === "Admin") ? "Administrator" : $user["Role"];

require_once "../includes/head.php";
?>

<style>
    .user-role { display: inline-block; padding: 6px 10px; border-radius: 999px; font-size: 12px; font-weight: 600; }
    .user-role-default { background: #f3f4f6; color: #374151; }
    .user-role-admin { background: #dbeafe; color: #1d4ed8; }
    .user-role-police { background: #dcfce7; color: #166534; }
    .user-role-seller { background: #fef3c7; color: #92400e; }

    .user-detail-row { display: flex; justify-content: space-between; align-items: center; gap: 20px; padding: 16px 0; border-bottom: 1px solid #f1f5f9; }
    .user-detail-row:last-child { border-bottom: none; }
    .user-detail-label { color: #6b7280; font-size: 14px; }
    .user-detail-value { color: #111827; font-size: 15px; font-weight: 500; text-align: right; }

    @media (max-width: 600px) {
        .user-detail-row { display: block; }
        .user-detail-value { text-align: left; margin-top: 5px; }
    }

    /* DARK MODE */
    [data-theme="dark"] .user-role-default { background: #334155; color: #cbd5e1; }
    [data-theme="dark"] .user-role-admin { background: #1e3a8a; color: #bfdbfe; }
    [data-theme="dark"] .user-role-police { background: #064e3b; color: #6ee7b7; }
    [data-theme="dark"] .user-role-seller { background: #78350f; color: #fcd34d; }
    [data-theme="dark"] .user-detail-row { border-bottom-color: #334155; }
    [data-theme="dark"] .user-detail-label { color: #94a3b8; }
    [data-theme="dark"] .user-detail-value { color: #f8fafc; }
    [data-theme="dark"] div[style*="background:#f8fafc"] { background: #1e293b !important; }
</style>

<body>
<div class="layout">
    <?php include __DIR__ . "/../includes/sidebar.php"; ?>
    <main class="main-content">
        <?php include __DIR__ . "/../includes/header.php"; ?>
        <div class="content">

            <div class="card">
                <!-- HEADER -->
                <div style="display:flex; justify-content:space-between; align-items:center; gap:20px; margin-bottom:25px; flex-wrap:wrap;">
                    <div>
                        <h1 class="page-title"><?= $isAdmin ? "User Details" : "My Profile" ?></h1>
                        <p style="color:#6b7280; margin:0;">View information about this VISRS account.</p>
                    </div>
                    <?php if ($isAdmin): ?>
                        <a href="index.php" class="button button-secondary">← Back to Users</a>
                    <?php endif; ?>
                </div>

                <!-- USER INFORMATION -->
                <div style="background:#f8fafc; padding:20px; border-radius:10px;">
                    <div class="user-detail-row">
                        <div class="user-detail-label">User ID</div>
                        <div class="user-detail-value">#<?= (int) $user["UserID"] ?></div>
                    </div>
                    <div class="user-detail-row">
                        <div class="user-detail-label">Full Name</div>
                        <div class="user-detail-value"><?= htmlspecialchars($user["FirstName"] . " " . $user["LastName"]) ?></div>
                    </div>
                    <div class="user-detail-row">
                        <div class="user-detail-label">Email Address</div>
                        <div class="user-detail-value"><?= htmlspecialchars($user["Email"]) ?></div>
                    </div>
                    <div class="user-detail-row">
                        <div class="user-detail-label">Account Role</div>
                        <div class="user-detail-value">
                            <span class="<?= htmlspecialchars($roleClass) ?>"><?= htmlspecialchars($displayRole) ?></span>
                        </div>
                    </div>
                    <div class="user-detail-row">
                        <div class="user-detail-label">Account Created</div>
                        <div class="user-detail-value"><?= htmlspecialchars(date("F d, Y", strtotime($user["CreatedAt"]))) ?></div>
                    </div>
                </div>

                <!-- ADMIN ACTIONS -->
                <?php if ($isAdmin): ?>
                    
                    <!-- PASSWORD CHANGE CARD -->
                    <div style="margin-top:30px; border-top:1px solid #e5e7eb; padding-top:25px;">
                        <h3 style="margin:0 0 15px 0; font-size:16px;">Admin Actions: Reset Password</h3>
                        
                        <?php if ($passwordMessage): ?>
                            <div style="background:#dcfce7; color:#166534; padding:10px 14px; border-radius:6px; margin-bottom:15px; font-size:14px;"><?= htmlspecialchars($passwordMessage) ?></div>
                        <?php endif; ?>
                        <?php if ($passwordError): ?>
                            <div style="background:#fee2e2; color:#991b1b; padding:10px 14px; border-radius:6px; margin-bottom:15px; font-size:14px;"><?= htmlspecialchars($passwordError) ?></div>
                        <?php endif; ?>

                        <form method="POST" style="display:flex; gap:10px; align-items:flex-end; flex-wrap:wrap;">
                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(generateCSRFToken()) ?>">
                            <input type="hidden" name="action" value="admin_change_password">
                            
                            <div style="flex:1; min-width:250px;">
                                <label style="display:block; margin-bottom:6px; font-size:13px; font-weight:600; color:#374151;">New Password</label>
                                <input type="text" name="new_password" placeholder="Enter new password..." required style="width:100%; padding:10px; border:1px solid #d1d5db; border-radius:6px;">
                            </div>
                            
                            <button type="submit" class="button" style="background:#f59e0b;">Change Password</button>
                        </form>
                    </div>

                    <!-- DELETE & OTHER ACTIONS -->
                    <div style="display:flex; gap:12px; margin-top:25px; flex-wrap:wrap; border-top:1px solid #e5e7eb; padding-top:25px;">
                        <a href="edit.php?id=<?= (int) $user["UserID"] ?>" class="button">Edit User Info</a>

                        <?php if (!$isCurrentUser): ?>
                            <a href="delete.php?id=<?= (int) $user["UserID"] ?>" class="button" style="background:#991b1b;">
                                Force Delete User
                            </a>
                        <?php endif; ?>

                        <button type="button" onclick="window.print()" class="button" style="background:#15803d;">
                            🖨 Print / Save as PDF
                        </button>
                        
                        <a href="index.php" class="button button-secondary">Back to List</a>
                    </div>

                <?php endif; ?>
            </div>
        </div>
    </main>
</div>
<?php include __DIR__ . "/../includes/footer.php"; ?>