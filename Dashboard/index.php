<?php

session_start();

if (!isset($_SESSION["user_id"])) {
    header("Location: ../login.php");
    exit;
}

$userName = $_SESSION["user_name"];
$userEmail = $_SESSION["user_email"];

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Digital — Completion Report Builder</title>

    <link rel="stylesheet" href="styles.css?v=<?php echo filemtime(__DIR__ . '/styles.css'); ?>">

</head>

<body>

<script>
    window.JT_USER_ID = <?php echo json_encode((int) $_SESSION["user_id"]); ?>;
</script>

<!-- ============ TOP BAR ============ -->

<header class="topbar">

    <div class="topbar-brand">

        <span
            class="jt-mark"
            aria-hidden="true"
        >
            GoIn
        </span>

        <div class="topbar-titles">

            <strong>
                Completion Report Builder
            </strong>

            <span id="reportLabel">
                New report
            </span>

        </div>

    </div>


    <div class="topbar-actions">

        <button
            class="btn btn-ghost"
            id="btnNewReport"
            title="Start a new blank report"
        >
            New
        </button>


        <button
            class="btn btn-ghost"
            id="btnDuplicate"
            title="Duplicate the most recent saved report"
        >
            Duplicate last
        </button>


        <button
            class="btn btn-ghost"
            id="btnLoad"
            title="Open a saved report"
        >
            Open…
        </button>


        <button
            class="btn btn-secondary"
            id="btnSaveDraft"
            title="Save current progress"
        >
            Save draft
        </button>


        <div class="dropdown">

            <button
                class="btn btn-primary"
                id="btnGenerate"
                title="Export the finished report"
            >
                Generate ▾
            </button>


            <div
                class="dropdown-menu"
                id="generateMenu"
            >

                <button data-action="pdf">
                    Download as PDF
                </button>

                <button data-action="doc">
                    Download as Word (.doc)
                </button>

            </div>

        </div>


        <!-- LOGGED-IN USER -->

        <div class="user-info">

            <span class="user-name">
                <?php echo htmlspecialchars($userName); ?>
            </span>

            <span class="user-email">
                <?php echo htmlspecialchars($userEmail); ?>
            </span>

        </div>

    </div>

</header>


<div class="app-shell">


    <!-- ============ LEFT: SECTION NAV / CHECKLIST ============ -->

    <nav
        class="sidenav"
        id="sidenav"
        aria-label="Report sections"
    >

        <div class="sidenav-scroll">

            <p class="sidenav-hint">
                Sections
            </p>


            <ol
                class="section-list"
                id="sectionList"
            ></ol>


            <div class="sidenav-divider"></div>


            <p class="sidenav-hint">
                Saved reports
            </p>


            <ul
                class="report-list"
                id="reportList"
            >
                <li class="muted">
                    No saved reports yet
                </li>
            </ul>


            <div class="sidenav-divider"></div>


            <button
                class="btn btn-ghost btn-block"
                id="btnDefaults"
            >
                Agency defaults…
            </button>


            <button
                class="btn btn-ghost btn-sm btn-block"
                onclick="openTemplateModal()"
                style="margin-top: 8px;"
            >
                Upload Template...
            </button>

            <button
                class="btn btn-ghost btn-sm btn-block"
                id="btnDefaultTemplate"
                style="margin-top: 8px;"
                hidden
            >
                Back to default template
            </button>

        </div>

    </nav>



    <!-- ============ CENTER: EDITOR ============ -->

    <main
        class="editor"
        id="editor"
        aria-label="Section editor"
    >

        <!-- injected per-section forms -->

    </main>



    <!-- ============ RIGHT: LIVE PREVIEW ============ -->

    <aside
        class="previewpane"
        aria-label="Live preview"
    >

        <div class="previewpane-head">

            <span>
                Live preview
            </span>


            <div class="preview-zoom">

                <button
                    id="zoomOut"
                    class="btn btn-ghost btn-xs"
                    title="Zoom out"
                >
                    –
                </button>


                <span id="zoomLevel">
                    70%
                </span>


                <button
                    id="zoomIn"
                    class="btn btn-ghost btn-xs"
                    title="Zoom in"
                >
                    +
                </button>

            </div>

        </div>


        <div
            class="previewpane-scroll"
            id="previewScroll"
        >

            <div
                class="preview-pages"
                id="previewPages"
            ></div>

        </div>

    </aside>

</div>



<!-- ============ MODAL: AGENCY DEFAULTS ============ -->

<div
    class="modal-backdrop"
    id="defaultsModal"
>

    <div class="modal">


        <div class="modal-head">

            <h2>
                Agency defaults
            </h2>


            <button
                class="btn btn-ghost btn-xs"
                id="closeDefaults"
            >
                ✕
            </button>

        </div>


        <p class="muted small">
            Saved once, auto-filled into every new report.
            Only month-specific content needs re-entry.
        </p>


        <div class="form-grid">


            <label>
                Contractor / company name

                <input
                    type="text"
                    id="defContractorName"
                    placeholder="JT Digital Marketing Services"
                >

            </label>


            <label>
                Contractor address

                <input
                    type="text"
                    id="defContractorAddress"
                    placeholder="Joel Casipong Apt 3, Brgy. Looc, Lapu-Lapu City, Cebu, Philippines 6015"
                >

            </label>


            <label>
                Prepared by — company

                <input
                    type="text"
                    id="defPreparedByCompany"
                    placeholder="JT Digital Marketing Services"
                >

            </label>


            <label>
                Viber name

                <input
                    type="text"
                    id="defViberName"
                    placeholder="Jeah Tradio"
                >

            </label>


            <label>
                Viber number

                <input
                    type="text"
                    id="defViberNumber"
                    placeholder="+63 945 561 6280"
                >

            </label>


            <label>
                Email

                <input
                    type="email"
                    id="defEmail"
                    placeholder="hello@jtdigitalmarketingservices.com"
                >

            </label>


            <label>
                Website

                <input
                    type="text"
                    id="defWebsite"
                    placeholder="jtdigitalmarketingservices.com"
                >

            </label>


            <label>
                Location

                <input
                    type="text"
                    id="defLocation"
                    placeholder="Lapu-Lapu City, Cebu, Philippines 6015"
                >

            </label>

        </div>


        <div class="modal-actions">

            <button
                class="btn btn-ghost"
                id="cancelDefaults"
            >
                Cancel
            </button>


            <button
                class="btn btn-primary"
                id="saveDefaults"
            >
                Save defaults
            </button>

        </div>

    </div>

</div>



<!-- ============ MODAL: OPEN REPORT ============ -->

<div
    class="modal-backdrop"
    id="loadModal"
>

    <div class="modal">


        <div class="modal-head">

            <h2>
                Open a saved report
            </h2>


            <button
                class="btn btn-ghost btn-xs"
                id="closeLoad"
            >
                ✕
            </button>

        </div>


        <ul
            class="report-list-modal"
            id="reportListModal"
        ></ul>


        <div class="modal-actions">

            <button
                class="btn btn-ghost"
                id="cancelLoad"
            >
                Close
            </button>

        </div>

    </div>

</div>



<!-- ============ MODAL: CUSTOM TEMPLATE UPLOAD ============ -->

<div
    id="templateModal"
    class="modal-backdrop"
>

    <div class="modal">


        <div class="modal-head">

            <h2>Custom templates</h2>


            <button
                class="btn btn-ghost btn-xs"
                onclick="closeTemplateModal()"
            >
                ✕
            </button>

        </div>


        <div class="template-tabs" role="tablist" aria-label="Custom template actions">
            <button type="button" class="btn btn-ghost active" id="showUploadedTemplates" role="tab" aria-selected="true">Uploaded templates</button>
            <button type="button" class="btn btn-ghost" id="showTemplateUpload" role="tab" aria-selected="false">Upload template</button>
        </div>

        <section id="uploadedTemplatesPanel" role="tabpanel" aria-labelledby="showUploadedTemplates">
            <p class="muted small template-panel-description">Choose a saved template for the live preview, or delete one you no longer need.</p>
            <ul id="templateListUl" class="uploaded-template-list"></ul>
        </section>

        <section id="templateUploadPanel" role="tabpanel" aria-labelledby="showTemplateUpload" hidden>
            <p class="muted small template-panel-description">
                Upload a PDF to analyze and clean it with TemplateCopier before using it in the live preview. The PDF is sent to Gemini for layout analysis; don't upload confidential files unless approved.
            </p>
            <form
                id="templateUploadForm"
                onsubmit="handleTemplateUpload(event)"
                action="../TemplateCopier/analyze.php"
                method="POST"
                enctype="multipart/form-data"
            >
                <input type="hidden" name="dashboard_upload" value="1">
                <div class="form-grid single" style="gap: 12px;">
                    <label>Template Name
                        <input type="text" name="template_name" id="templateNameInput" placeholder="e.g., Q3 Special Format" required>
                    </label>
                    <label>Select PDF Template
                        <input type="file" name="template" id="templateFileInput" accept=".pdf" required style="padding: 6px;border: 1px solid var(--line);border-radius: var(--radius-sm);background: #fff;">
                    </label>
                </div>
                <div class="modal-actions">
                    <button type="button" class="btn btn-ghost" onclick="closeTemplateModal()">Cancel</button>
                    <button type="submit" class="btn btn-primary">Upload Template</button>
                </div>
            </form>
        </section>

    </div>

</div>

<!-- ============ TOAST ============ -->
<div
    class="toast"
    id="toast"
></div>



<!-- ============ JAVASCRIPT LIBRARIES ============ -->

<script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>

<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>

<script type="module">
    import * as pdfjsLib from 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/4.10.38/pdf.min.mjs';
    pdfjsLib.GlobalWorkerOptions.workerSrc = 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/4.10.38/pdf.worker.min.mjs';
    window.pdfjsLib = pdfjsLib;
</script>

<script src="app.js?v=<?php echo filemtime(__DIR__ . '/app.js'); ?>"></script>


</body>

</html>