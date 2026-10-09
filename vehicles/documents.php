<?php

require_once __DIR__ . "/../includes/auth.php";
require_once __DIR__ . "/../includes/database.php";
require_once __DIR__ . "/../includes/functions.php";

requireRole(["Admin", "Police", "Seller"]);

$basePath = "../";
$activePage = "vehicles";

$pageTitle = "Vehicle Documents";

$vehicleId = (int) ($_GET["id"] ?? 0);

if ($vehicleId <= 0) {
    header("Location: index.php");
    exit;
}

/* Load vehicle */
$stmt = $pdo->prepare("
    SELECT VehicleID, PlateNumber, Make, Model, VehicleYear
    FROM vehicles
    WHERE VehicleID = ?
    LIMIT 1
");
$stmt->execute([$vehicleId]);
$vehicle = $stmt->fetch();

if (!$vehicle) {
    header("Location: index.php");
    exit;
}

/* Load documents */
$docStmt = $pdo->prepare("
    SELECT
        d.DocumentID,
        d.DocType,
        d.OriginalName,
        d.FileSize,
        d.MimeType,
        d.CreatedAt,
        u.FirstName,
        u.LastName
    FROM vehicle_documents d
    LEFT JOIN users u ON d.UploadedBy = u.UserID
    WHERE d.VehicleID = ?
    ORDER BY d.CreatedAt DESC
");
$docStmt->execute([$vehicleId]);
$documents = $docStmt->fetchAll();

/* Flash */
$flash = $_SESSION["bulk_message"] ?? null;
unset($_SESSION["bulk_message"]);

$canDelete = hasRole(["Admin", "Police"]);
$canUpload = hasRole(["Admin", "Police", "Seller"]);

/* File size helper */
function formatBytes($bytes) {
    $bytes = (int) $bytes;
    if ($bytes < 1024) return $bytes . " B";
    if ($bytes < 1048576) return round($bytes / 1024, 1) . " KB";
    return round($bytes / 1048576, 1) . " MB";
}

/* Doc type icon */
function docIcon($type) {
    switch ($type) {
        case "Registration":    return "📋";
        case "Insurance":       return "🛡️";
        case "Inspection":      return "🔍";
        case "Service History": return "🔧";
        case "Purchase Invoice":return "🧾";
        default:                return "📄";
    }
}

require_once "../includes/head.php";

?>

<style>

    .doc-page-header {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        gap: 20px;
        flex-wrap: wrap;
        margin-bottom: 22px;
    }

    .doc-page-header h1 {
        margin: 0 0 6px;
        font-size: 24px;
        color: #111827;
    }

    .doc-page-header p {
        margin: 0;
        color: #64748b;
        font-size: 14px;
    }

    .doc-list {
        display: flex;
        flex-direction: column;
        gap: 10px;
        margin-top: 18px;
    }

    .doc-item {
        display: flex;
        align-items: center;
        gap: 16px;
        padding: 14px 16px;
        background: #ffffff;
        border: 1px solid #e5e7eb;
        border-radius: 10px;
    }

    .doc-icon {
        width: 42px;
        height: 42px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 22px;
        background: #eff6ff;
        border-radius: 8px;
        flex-shrink: 0;
    }

    .doc-info {
        flex: 1;
        min-width: 0;
    }

    .doc-name {
        margin: 0 0 3px;
        font-size: 14px;
        font-weight: 700;
        color: #111827;
        word-break: break-word;
    }

    .doc-meta {
        margin: 0;
        color: #6b7280;
        font-size: 12px;
        line-height: 1.5;
    }

    .doc-type-badge {
        display: inline-block;
        padding: 3px 8px;
        border-radius: 5px;
        background: #dbeafe;
        color: #1e40af;
        font-size: 11px;
        font-weight: 700;
        margin-right: 6px;
    }

    .doc-actions {
        display: flex;
        gap: 6px;
        flex-wrap: wrap;
        flex-shrink: 0;
    }

    .doc-actions .button,
    .doc-actions button {
        padding: 7px 11px;
        font-size: 12px;
    }

    .doc-empty {
        padding: 40px 20px;
        text-align: center;
        color: #64748b;
        background: #f8fafc;
        border-radius: 10px;
        margin-top: 18px;
    }

    .doc-empty-icon {
        font-size: 42px;
        margin-bottom: 10px;
    }

    .upload-form {
        margin-top: 24px;
    }

    .upload-form .form-row {
        display: flex;
        gap: 12px;
        flex-wrap: wrap;
        align-items: flex-end;
    }

    .upload-form .form-group {
        flex: 1;
        min-width: 220px;
        margin: 0;
    }

    /* Dark mode */
    [data-theme="dark"] .doc-page-header h1 { color: #f1f5f9; }
    [data-theme="dark"] .doc-page-header p  { color: #94a3b8; }

    [data-theme="dark"] .doc-item {
        background: #1e293b;
        border-color: #334155;
    }

    [data-theme="dark"] .doc-icon {
        background: #1e3a8a;
    }

    [data-theme="dark"] .doc-name { color: #f1f5f9; }
    [data-theme="dark"] .doc-meta { color: #94a3b8; }

    [data-theme="dark"] .doc-type-badge {
        background: #1e3a8a;
        color: #bfdbfe;
    }

    [data-theme="dark"] .doc-empty {
        background: #1e293b;
        color: #94a3b8;
    }

</style>

<body>

<div class="layout">

    <?php require_once __DIR__ . "/../includes/sidebar.php"; ?>

    <main class="main-content">

        <?php include __DIR__ . "/../includes/header.php"; ?>

        <div class="content">

            <a
                href="view.php?id=<?= (int) $vehicle["VehicleID"] ?>"
                style="
                    display:inline-block;
                    margin-bottom:18px;
                    color:#475569;
                    text-decoration:none;
                    font-size:13px;
                    font-weight:600;
                "
            >
                ← Back to Vehicle
            </a>


            <!-- PAGE HEADER -->

            <div class="doc-page-header">

                <div>

                    <h1>
                        Vehicle Documents
                    </h1>

                    <p>

                        <?= e($vehicle["VehicleYear"] . " " . $vehicle["Make"] . " " . $vehicle["Model"]) ?>

                        &nbsp;·&nbsp;

                        Plate: <?= e($vehicle["PlateNumber"]) ?>

                    </p>

                </div>

            </div>


            <!-- FLASH MESSAGE -->

            <?php if ($flash): ?>

                <?php
                $flashBg = "#eff6ff";
                $flashColor = "#1e40af";

                if ($flash["type"] === "success") {
                    $flashBg = "#dcfce7";
                    $flashColor = "#166534";
                } elseif ($flash["type"] === "error") {
                    $flashBg = "#fee2e2";
                    $flashColor = "#991b1b";
                }
                ?>

                <div
                    class="auto-dismiss"
                    style="
                        background:<?= $flashBg ?>;
                        color:<?= $flashColor ?>;
                        padding:14px;
                        border-radius:8px;
                        margin-bottom:20px;
                        font-weight:600;
                    "
                >
                    <?= e($flash["text"]) ?>
                </div>

            <?php endif; ?>


            <!-- UPLOAD FORM -->

            <?php if ($canUpload): ?>

                <section class="card upload-form">

                    <h2>
                        Upload a Document
                    </h2>

                    <p style="color:#6b7280; margin:0 0 18px;">
                        Allowed types: PDF, JPG, PNG, DOC, DOCX — max 10 MB per file.
                    </p>

                    <form
                        method="POST"
                        action="documents_upload.php"
                        enctype="multipart/form-data"
                    >

                        <input
                            type="hidden"
                            name="csrf_token"
                            value="<?= e(generateCSRFToken()) ?>"
                        >

                        <input
                            type="hidden"
                            name="vehicle_id"
                            value="<?= (int) $vehicle["VehicleID"] ?>"
                        >

                        <div class="form-row">

                            <div class="form-group">

                                <label for="doc_type">
                                    Document Type
                                </label>

                                <select
                                    id="doc_type"
                                    name="doc_type"
                                    required
                                >

                                    <option value="">Select type…</option>
                                    <option value="Registration">Registration Certificate</option>
                                    <option value="Insurance">Insurance Policy</option>
                                    <option value="Inspection">Inspection Report</option>
                                    <option value="Service History">Service History</option>
                                    <option value="Purchase Invoice">Purchase Invoice</option>
                                    <option value="Other">Other</option>

                                </select>

                            </div>


                            <div class="form-group">

                                <label for="document">
                                    File
                                </label>

                                <input
                                    type="file"
                                    id="document"
                                    name="document"
                                    accept=".pdf,.jpg,.jpeg,.png,.doc,.docx"
                                    required
                                >

                            </div>


                            <div>

                                <button
                                    type="submit"
                                    class="button"
                                >
                                    Upload
                                </button>

                            </div>

                        </div>

                    </form>

                </section>

            <?php endif; ?>


            <!-- DOCUMENT LIST -->

            <section class="card">

                <h2>
                    Documents (<?= count($documents) ?>)
                </h2>


                <?php if (empty($documents)): ?>

                    <div class="doc-empty">

                        <div class="doc-empty-icon">📁</div>

                        <h3 style="margin:0 0 6px; color:inherit;">No documents yet</h3>

                        <p style="margin:0;">
                            Upload the first document for this vehicle.
                        </p>

                    </div>

                <?php else: ?>

                    <div class="doc-list">

                        <?php foreach ($documents as $doc): ?>

                            <div class="doc-item">

                                <div class="doc-icon">
                                    <?= docIcon($doc["DocType"]) ?>
                                </div>

                                <div class="doc-info">

                                    <p class="doc-name">
                                        <?= e($doc["OriginalName"]) ?>
                                    </p>

                                    <p class="doc-meta">

                                        <span class="doc-type-badge">
                                            <?= e($doc["DocType"]) ?>
                                        </span>

                                        <?= formatBytes($doc["FileSize"]) ?>

                                        &nbsp;·&nbsp;

                                        <?= e(date("M j, Y g:i A", strtotime($doc["CreatedAt"]))) ?>

                                        <?php if (!empty($doc["FirstName"])): ?>

                                            &nbsp;·&nbsp;

                                            Uploaded by

                                            <?= e(trim($doc["FirstName"] . " " . $doc["LastName"])) ?>

                                        <?php endif; ?>

                                    </p>

                                </div>

                                <div class="doc-actions">

                                    <a
                                        href="documents_download.php?id=<?= (int) $doc["DocumentID"] ?>"
                                        class="button button-secondary"
                                    >
                                        Download
                                    </a>


                                    <?php if ($canDelete): ?>

                                        <form
                                            method="POST"
                                            action="documents_delete.php"
                                            style="display:inline;"
                                            onsubmit="return confirm('Delete this document? This cannot be undone.');"
                                        >

                                            <input
                                                type="hidden"
                                                name="csrf_token"
                                                value="<?= e(generateCSRFToken()) ?>"
                                            >

                                            <input
                                                type="hidden"
                                                name="document_id"
                                                value="<?= (int) $doc["DocumentID"] ?>"
                                            >

                                            <button
                                                type="submit"
                                                class="button"
                                                style="background:#991b1b;"
                                            >
                                                Delete
                                            </button>

                                        </form>

                                    <?php endif; ?>

                                </div>

                            </div>

                        <?php endforeach; ?>

                    </div>

                <?php endif; ?>

            </section>

        </div>

    </main>

</div>

<?php require_once __DIR__ . "/../includes/footer.php"; ?>