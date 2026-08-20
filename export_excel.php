<?php
require_once "auth.php";
require_once "report_helper.php";

date_default_timezone_set('Asia/Manila');
[$rows, $search, $status, $start_date, $end_date] = getReportRows($conn);

$filename = "Property_Inventory_Report_" . date("Y-m-d_His") . ".xls";

header("Content-Type: application/vnd.ms-excel; charset=UTF-8");
header("Content-Disposition: attachment; filename=\"$filename\"");
header("Cache-Control: max-age=0");

echo "\xEF\xBB\xBF";
$totalCost = 0;
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: Arial, sans-serif; font-size: 10pt; }
        table { border-collapse: collapse; width: 100%; }
        td, th { border: 1px solid #000000; padding: 6px; white-space: nowrap; vertical-align: middle; }
        th { background-color: #d9ead3; font-weight: bold; text-align: center; }
        .no-border { border: none !important; background: none !important; }
        .excel-text { mso-number-format: "\@"; }
        .excel-currency { mso-number-format: "Php"\ \#\,\#\#0\.00; }
    </style>
</head>
<body>
    <table>
        <tr><td colspan="12" class="no-border" style="font-size:14pt; font-weight:bold; text-align:center;">PROPERTY MANAGEMENT INFORMATION SYSTEM</td></tr>
        <tr><td colspan="12" class="no-border" style="text-align:center;">Property Inventory Report</td></tr>
        <tr><td colspan="12" class="no-border" style="text-align:center;">Generated: <?= date("F j, Y | h:i A") ?></td></tr>
        <tr style="height:15px;"><td colspan="12" class="no-border"></td></tr>

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
        </tr>

        <?php if ($rows && $rows->num_rows > 0): ?>
            <?php while ($r = $rows->fetch_assoc()): ?>
                <?php $totalCost += (float)($r["cost"] ?? 0); ?>
                <tr>
                    <td><?= htmlspecialchars($r["article"] ?? '') ?></td>
                    <td><?= htmlspecialchars($r["description"] ?? '') ?></td>
                    <td class="excel-text"><?= htmlspecialchars($r["property_no"] ?? '') ?></td>
                    <td class="excel-text"><?= htmlspecialchars($r["new_property_no"] ?? '') ?></td>
                    <td style="text-align:center;"><?= htmlspecialchars($r["unit_of_measure"] ?? 'pc') ?></td>
                    <td style="text-align:right;" class="excel-currency"><?= number_format((float)($r["cost"] ?? 0), 2) ?></td>
                    <td style="text-align:center;"><?= htmlspecialchars($r["qty_property_card"] ?? '1') ?></td>
                    <td style="text-align:center;"><?= htmlspecialchars($r["qty_physical_count"] ?? '1') ?></td>
                    <td><?= htmlspecialchars(strtoupper($r["location"] ?? '')) ?></td>
                    <td style="text-align:center;"><?= htmlspecialchars(strtoupper($r["status"] ?? '')) ?></td>
                    <td style="text-align:center;"><?= htmlspecialchars($r["date_acquired"] ?? '') ?></td>
                    <td><?= htmlspecialchars(strtoupper($r["accountable_person"] ?? '')) ?></td>
                </tr>
            <?php endwhile; ?>
        <?php else: ?>
            <tr><td colspan="12" style="text-align:center;">No property records found.</td></tr>
        <?php endif; ?>

        <tr style="background-color:#f3f3f3; font-weight:bold;">
            <td colspan="5" style="text-align:right;">TOTAL COST:</td>
            <td style="text-align:right;" class="excel-currency"><?= number_format($totalCost, 2) ?></td>
            <td colspan="6"></td>
        </tr>

        <!-- Empty Spacing Before Signatories -->
        <tr style="height:25px;"><td colspan="12" class="no-border"></td></tr>

        <!-- Signatures Header Row -->
        <tr>
            <td colspan="3" class="no-border">Prepared by:</td>
            <td colspan="5" class="no-border"></td>
            <td colspan="4" class="no-border">Approved by:</td>
        </tr>

        <!-- Signature Gap -->
        <tr style="height:35px;"><td colspan="12" class="no-border"></td></tr>

        <!-- Signatory Names -->
        <tr>
            <td colspan="3" class="no-border" style="font-weight:bold;">ALVIN RAY S. BAWAR</td>
            <td colspan="5" class="no-border"></td>
            <td colspan="4" class="no-border" style="font-weight:bold;">JONATHAN R. DIGMA, Ph.D.</td>
        </tr>

        <!-- Signatory Titles -->
        <tr>
            <td colspan="3" class="no-border">Property Custodian</td>
            <td colspan="5" class="no-border"></td>
            <td colspan="4" class="no-border">Campus Administrator</td>
        </tr>
    </table>
</body>
</html>