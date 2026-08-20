<?php
require_once "auth.php";
$search=trim($_GET["search"]??"");
$status=trim($_GET["status"]??"");
$sql="SELECT * FROM properties WHERE 1";
$params=[];$types="";
if($search!==""){ $sql.=" AND (property_no LIKE ? OR description LIKE ? OR accountable_person LIKE ? OR location LIKE ?)"; $s="%$search%"; array_push($params,$s,$s,$s,$s); $types.="ssss"; }
if($status!==""){ $sql.=" AND status=?"; $params[]=$status; $types.="s"; }
$sql.=" ORDER BY id DESC";
$stmt=$conn->prepare($sql);
if($params) $stmt->bind_param($types,...$params);
$stmt->execute();$rows=$stmt->get_result();
$statuses=["Condemned","Unserviceable","Not usable","Repair","Missing","For Verification","Good/Issued","Serviceable"];
?>

<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <title>Properties</title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>
<header class="topbar" style="background-color: #19661b; color: white; position: sticky; top: 0; z-index: 1000; width: 100%;">
    <strong>Property Management Information System</strong>
    <nav>
        <a href="index.php" style="color: white;">Dashboard</a>
        <a href="properties.php" style="color: white;">Properties</a>
        <a href="property_form.php" style="color: white;">Add Property</a>
        <a href="delete_account.php" style="color: #ff0000;">Delete Account</a>
        <a href="logout.php" style="color: white;">Logout</a>
    </nav>
</header>
<main class="container">

    <!-- 1. HEADER BLOCK -->
    <div class="page-head">
        <div>
            <h1>List of Properties</h1>
            <p class="muted">Manage property records &bull; <strong id="liveDateTime"><?= date("F j, Y | h:i:s A") ?></strong></p>
        </div>
        <div>
            <a class="btn primary" href="property_form.php">Add Property</a>
        </div>
    </div>

    <!-- 2. SEARCH & FILTER FORM -->
    <form method="GET" action="properties.php" class="filters" style="display: flex; gap: 10px; align-items: center; margin-bottom: 18px;">
        <input type="text" name="search" placeholder="Search properties, description..." value="<?= htmlspecialchars($_GET['search'] ?? '') ?>">
        
        <select name="status">
            <option value="">All statuses</option>
            <?php 
            $filterStatuses = ["Condemned","Unserviceable","Not usable","Repair","Missing","For Verification","Good/Issued","Serviceable"];
            $currentStatus = $_GET['status'] ?? '';
            foreach($filterStatuses as $st): 
            ?>
                <option value="<?= htmlspecialchars($st) ?>" <?= $currentStatus === $st ? 'selected' : '' ?>>
                    <?= htmlspecialchars($st) ?>
                </option>
            <?php endforeach; ?>
        </select>

        <button type="submit" class="btn">Search</button>
        <a href="properties.php" class="btn">Clear</a>
    </form>

    <!-- 3. REPORT BUTTONS -->
    <div class="report-buttons" style="margin-bottom: 15px;">
        <a href="export_excel.php" class="btn report-excel">📊 Generate Excel Report</a>
        <a href="report_pdf.php" class="btn report-pdf" target="_blank" rel="noopener noreferrer">📄 Generate PDF Report</a>
    </div>

    <!-- TABLE STARTS HERE -->
    <div class="table-responsive">
        <style>
        /* Table Layout & Typography */
        .table-nowrap {
            width: 100%;
            border-collapse: collapse;
            font-size: 13px;
        }

        /* Sticky Table Headers (Vertical Scrolling Fix) */
        .table-nowrap th {
            position: sticky;
            top: 0;
            z-index: 10;
            background-color: #f8f9fa;
            font-weight: bold;
            text-align: left;
            white-space: nowrap !important;
            padding: 10px 14px;
            border: 1px solid #dee2e6;
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05);
        }

        /* Multi-line wrapping for data cells */
        .table-nowrap td {
            padding: 10px 14px;
            border: 1px solid #dee2e6;
            vertical-align: top;
            white-space: normal;
        }

        /* Column Specific Sizing */
        .table-nowrap .col-desc {
            min-width: 280px;
            max-width: 350px;
            word-break: break-word;
        }

        /* Action Buttons Styling */
        .btn-action {
            display: inline-block;
            padding: 4px 8px;
            font-size: 12px;
            border-radius: 4px;
            text-decoration: none;
            font-weight: bold;
            margin: 2px;
        }
        .btn-edit {
            background-color: #0d6efd;
            color: #ffffff !important;
        }
        .btn-edit:hover { background-color: #0b5ed7; }
        .btn-delete {
            background-color: #dc3545;
            color: #ffffff !important;
        }
        .btn-delete:hover { background-color: #bb2d3b; }

        /* Outer Container Structure */
        .table-wrapper {
            position: relative;
            width: 100%;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            background: #ffffff;
            overflow: hidden;
        }

        /* Vertical & Horizontal Scroll Container */
        .table-responsive-container {
            overflow-x: auto;
            overflow-y: auto;
            max-height: 500px; /* Adjust height limit for vertical scrollbar */
            width: 100%;
            scrollbar-width: none; /* Hide default top horizontal scrollbar (Firefox) */
            -ms-overflow-style: none; /* Edge/IE */
        }

        /* Hide default top horizontal scrollbar (Webkit / Chrome / Safari / Edge) */
        .table-responsive-container::-webkit-scrollbar:horizontal {
            display: none;
            height: 0;
        }

        /* Pinned Bottom Horizontal Scrollbar */
        .sticky-scroll-wrapper {
            position: sticky;
            bottom: 0;
            overflow-x: auto;
            overflow-y: hidden;
            width: 100%;
            background: #f8f9fa;
            z-index: 20;
            border-top: 1px solid #dee2e6;
            box-shadow: 0 -2px 6px rgba(0, 0, 0, 0.08);
        }

        .sticky-scroll-content {
            height: 1px;
        }
        </style>

        <div class="table-wrapper" id="tableWrapper">
    <!-- Main Scroll Area for Table -->
    <div class="table-responsive-container" id="mainTableScroll">
        <table class="table-nowrap" id="propertiesTable">
            <thead>
                <tr>
                    <th>ARTICLE / ITEM</th>
                    <th>DESCRIPTION</th>
                    <th>OLD PROPERTY NO. ASSIGNED</th>
                    <th>NEW PROPERTY NO. ASSIGNED</th>
                    <th>UNIT OF MEASURE</th>
                    <th>COST (PHP)</th>
                    <th>QTY (PROPERTY CARD)</th>
                    <th>QTY (PHYSICAL COUNT)</th>
                    <th>LOCATION</th>
                    <th>STATUS</th>
                    <th>DATE ACQUIRED</th>
                    <th>ACCOUNTABLE PERSON</th>
                    <th style="text-align: center;">ACTIONS</th>
                </tr>
            </thead>
            <tbody>
                        <?php if (isset($rows) && $rows && $rows->num_rows > 0): ?>
                            <?php while ($r = $rows->fetch_assoc()): ?>
                                <tr>
                                    <td><?= htmlspecialchars($r["article"] ?? '') ?></td>
                                    <td><?= htmlspecialchars($r["description"] ?? '') ?></td>
                                    <td><?= htmlspecialchars($r["property_no"] ?? '') ?></td>
                                    <td><?= htmlspecialchars($r["new_property_no"] ?? '') ?></td>
                                    <td style="text-align: center;"><?= htmlspecialchars($r["unit_of_measure"] ?? 'pc') ?></td>
                                    <td style="text-align: right;"><?= number_format((float)($r["cost"] ?? 0), 2) ?></td>
                                    <td style="text-align: center;"><?= htmlspecialchars($r["qty_property_card"] ?? '1') ?></td>
                                    <td style="text-align: center;"><?= htmlspecialchars($r["qty_physical_count"] ?? '1') ?></td>
                                    <td><?= htmlspecialchars(strtoupper($r["location"] ?? '')) ?></td>
                                    <td style="text-align: center;"><?= htmlspecialchars(strtoupper($r["status"] ?? '')) ?></td>
                                    <td style="text-align: center;"><?= htmlspecialchars($r["date_acquired"] ?? '') ?></td>
                                    <td><?= htmlspecialchars(strtoupper($r["accountable_person"] ?? '')) ?></td>
                                    <td style="text-align: center; white-space: nowrap;">
                                        <a href="property_form.php?id=<?= $r['id'] ?>" class="btn-action btn-edit">Edit</a>
                                        <a href="delete_property.php?id=<?= $r['id'] ?>" class="btn-action btn-delete" onclick="return confirm('Are you sure you want to delete this record?');">Delete</a>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="15" style="text-align: center; padding: 20px;">No property records found.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Single Bottom Horizontal Scrollbar -->
            <div class="sticky-scroll-wrapper" id="stickyScrollWrapper">
                <div class="sticky-scroll-content" id="stickyScrollContent"></div>
            </div>
        </div>
    </div>
</main>

<!-- Scripts -->
<script>
// Live Clock
function updateLiveClock() {
    const now = new Date();
    const dateStr = now.toLocaleDateString('en-US', {
        month: 'long',
        day: 'numeric',
        year: 'numeric'
    });
    const timeStr = now.toLocaleTimeString('en-US', {
        hour: '2-digit',
        minute: '2-digit',
        second: '2-digit',
        hour12: true
    });

    const clockElem = document.getElementById('liveDateTime');
    if (clockElem) {
        clockElem.textContent = `${dateStr} | ${timeStr}`;
    }
}
updateLiveClock();
setInterval(updateLiveClock, 1000);

// Scrollbar Synchronization Script
document.addEventListener("DOMContentLoaded", function () {
    const mainTableScroll = document.getElementById("mainTableScroll");
    const propertiesTable = document.getElementById("propertiesTable");
    const stickyScrollWrapper = document.getElementById("stickyScrollWrapper");
    const stickyScrollContent = document.getElementById("stickyScrollContent");

    if (!mainTableScroll || !propertiesTable || !stickyScrollWrapper || !stickyScrollContent) return;

    function syncScrollWidth() {
        stickyScrollContent.style.width = propertiesTable.offsetWidth + "px";
    }

    let isSyncingMain = false;
    let isSyncingSticky = false;

    mainTableScroll.addEventListener("scroll", function () {
        if (!isSyncingMain) {
            isSyncingSticky = true;
            stickyScrollWrapper.scrollLeft = mainTableScroll.scrollLeft;
        }
        isSyncingMain = false;
    });

    stickyScrollWrapper.addEventListener("scroll", function () {
        if (!isSyncingSticky) {
            isSyncingMain = true;
            mainTableScroll.scrollLeft = stickyScrollWrapper.scrollLeft;
        }
        isSyncingSticky = false;
    });

    syncScrollWidth();
    window.addEventListener("resize", syncScrollWidth);

    if (window.ResizeObserver) {
        new ResizeObserver(syncScrollWidth).observe(propertiesTable);
    }
});
</script>
</body>
</html>