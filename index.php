<?php
require_once "auth.php";

function countStatus($conn, $status) {
    $stmt=$conn->prepare("SELECT COUNT(*) c FROM properties WHERE status=?");
    $stmt->bind_param("s",$status); $stmt->execute();
    return (int)$stmt->get_result()->fetch_assoc()["c"];
}

$statuses=["Condemned","Unserviceable","Not usable","Repair","Missing","For Verification","Good/Issued","Serviceable"];
$total=(int)$conn->query("SELECT COUNT(*) c FROM properties")->fetch_assoc()["c"];

// Query property records for the table
$rows = $conn->query("SELECT * FROM properties ORDER BY id DESC");

// Master Color Map (Pastel background + matching border + dark readable text)
function getStatusTheme($status) {
    // Clean string to match regardless of uppercase/lowercase, spaces, or slashes
    $key = strtolower(preg_replace('/[^a-z0-9]/i', '', (string)$status));

    $colors = [
        'serviceable'     => ['bg' => '#d4edda', 'border' => '#c3e6cb'], // Green
        'condemned'       => ['bg' => '#f8d7da', 'border' => '#f5c6cb'], // Red
        'unserviceable'   => ['bg' => '#e2e3e5', 'border' => '#d6d8db'], // Gray
        'repair'          => ['bg' => '#fff3cd', 'border' => '#ffeeba'], // Yellow
        'notusable'       => ['bg' => '#cfe2ff', 'border' => '#b6d4fe'], // Blue
        'missing'         => ['bg' => '#ffe5d0', 'border' => '#ffd8b5'], // Orange
        'forverification' => ['bg' => '#e2d9f3', 'border' => '#d0c2eb'], // Purple
        'goodissued'      => ['bg' => '#eedcff', 'border' => '#e0c2ff'], // Violet
        'total'           => ['bg' => '#d1f2eb', 'border' => '#a3e4d7'], // Blue-Green (Teal)
    ];

    return $colors[$key] ?? ['bg' => '#e2e8f0', 'border' => '#cbd5e1'];
}
?>
<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <title>Dashboard - Property Management</title>
    <link rel="stylesheet" href="assets/style.css">
    <style>
        /* Override default card styling from style.css */
        .cards .card.color-box {
            border-left: 1px solid transparent !important;
            transition: transform 0.2s, box-shadow 0.2s;
        }
        .cards .card.color-box:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.08);
        }
        .cards .card.color-box .label {
            color: #212529 !important;
            font-weight: 600 !important;
        }
        .cards .card.color-box .number {
            color: #111111 !important;
            font-weight: 700 !important;
        }

        /* Status Badge/Pill styling for table */
        .badge-status {
            display: inline-block !important;
            padding: 4px 10px !important;
            border-radius: 6px !important;
            font-size: 11px !important;
            font-weight: 700 !important;
            letter-spacing: 0.3px;
            text-align: center;
            white-space: nowrap;
            color: #212529 !important;
        }
    </style>
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
    <div class="page-head">
    <div>
        <h1>Dashboard</h1>
        <p class="muted" style="margin: 4px 0 0 0;">
    Complete inventory records &bull; <strong id="liveDateTime"><?= date("F j, Y | h:i:s A") ?></strong>
</p>
    </div>
    <a class="btn primary" href="property_form.php">Add Property</a>
</div>

    <!-- Summary Cards -->
    <div class="cards">
        <?php foreach($statuses as $s): 
            $theme = getStatusTheme($s);
        ?>
            <div class="card color-box" style="background-color: <?=$theme['bg']?> !important; border: 1px solid <?=$theme['border']?> !important;">
                <div class="label"><?=htmlspecialchars($s)?></div>
                <div class="number"><?=countStatus($conn,$s)?></div>
            </div>
        <?php endforeach; ?>
        
        <!-- Total Properties Card -->
        <?php $totalTheme = getStatusTheme('total'); ?>
        <div class="card color-box total" style="background-color: <?=$totalTheme['bg']?> !important; border: 1px solid <?=$totalTheme['border']?> !important;">
            <div class="label">Total Properties</div>
            <div class="number"><?=$total?></div>
        </div>
    </div>

    <!-- Recent Properties Table -->
    <div class="panel"><h2>Recent Properties</h2>
<style>
   .table-nowrap {
    width: 100%;
    border-collapse: collapse;
    font-size: 13px;
}

/* Single line header styling */
.table-nowrap th {
    background-color: #f8f9fa;
    font-weight: bold;
    text-align: left;
    white-space: nowrap !important; /* Keeps table headers on a single line */
    padding: 10px 14px;
    border: 1px solid #dee2e6;
}

/* Multi-line wrapping for data cells */
.table-nowrap td {
    padding: 10px 14px;
    border: 1px solid #dee2e6;
    vertical-align: top;
    white-space: normal;
}

/* Prevent squeezing on description column */
.table-nowrap .col-desc {
    min-width: 280px;
    max-width: 350px;
    word-break: break-word;
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
                <?php while ($r = $rows->fetch_assoc()): 
                    $stTheme = getStatusTheme($r["status"] ?? '');
                ?>
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
                        <td style="text-align: center;">
                            <span class="badge-status" style="background-color: <?=$stTheme['bg']?>; border: 1px solid <?=$stTheme['border']?>;">
                                <?= htmlspecialchars(strtoupper($r["status"] ?? '')) ?>
                            </span>
                        </td>
                        <td style="text-align: center;"><?= htmlspecialchars($r["date_acquired"] ?? '') ?></td>
                        <td><?= htmlspecialchars(strtoupper($r["accountable_person"] ?? '')) ?></td>
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
</main>

<!-- Live Clock Script (Updates every 1 second) -->
<script>
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
</script>
</body>
</html>