<?php
require_once "auth.php";
require_once "report_helper.php";
[$rows, $search, $status] = getReportRows($conn);

function pdf_escape($s) {
    $s = iconv("UTF-8", "windows-1252//TRANSLIT", (string)$s);
    return str_replace(["\\", "(", ")", "\r", "\n"], ["\\\\", "\\(", "\\)", "", " "], $s);
}

function add_text(&$c, $x, $y, $text, $size=6, $font="/F1") {
    $c .= "$font $size Tf\n1 0 0 1 $x $y Tm (" . pdf_escape($text) . ") Tj\n";
}

function add_line(&$c, $x1, $y1, $x2, $y2) {
    $c .= "$x1 $y1 m $x2 $y2 l S\n";
}

// Wraps text into an array of lines based on approximate character width
function wrap_text_lines($text, $maxChars) {
    $text = trim((string)$text);
    if ($text === '') return [''];
    $wrapped = wordwrap($text, $maxChars, "\n", true);
    return explode("\n", $wrapped);
}

function render_header(&$c, $pageW, $pageH, $margin, $cols, &$y, $headerY) {
    add_text($c, $margin, $pageH - 30, "PROPERTY MANAGEMENT INFORMATION SYSTEM", 12, "/F2");
    add_text($c, $margin, $pageH - 44, "Property Inventory Report", 9);
    add_text($c, $margin, $pageH - 56, "Generated: " . date("d-M-Y h:i A"), 7);

    $y = $headerY;
    $x = $margin;
    foreach ($cols as $col) {
        // Multi-line header wrapping
        $headerLines = wrap_text_lines($col[0], $col[2]);
        foreach ($headerLines as $hIdx => $hLine) {
            add_text($c, $x + 2, $y - ($hIdx * 7), $hLine, 5.2, "/F2");
        }
        $x += $col[1];
    }
    add_line($c, $margin, $y - 14, $pageW - $margin, $y - 14);
    $y -= 22;
}

$pageW = 841.89; // A4 landscape width
$pageH = 595.28; // A4 landscape height
$margin = 14;

// Column definitions: [Header Title, Column Width (pt), Approx Max Chars Per Line]
$cols = [
    ["ARTICLE / ITEM",            70, 14],
    ["DESCRIPTION",              105, 22],
    ["OLD PROPERTY NO. ASSIGNED", 80, 15],
    ["NEW PROPERTY NO. ASSIGNED", 80, 15],
    ["UNIT OF MEASURE",           45,  8],
    ["COST (PHP)",                55, 11],
    ["QTY (CARD)",                60, 10],
    ["QTY (COUNT)",               60, 10],
    ["LOCATION",                  60, 11],
    ["STATUS",                    48,  9],
    ["DATE ACQUIRED",             55, 10],
    ["ACCOUNTABLE PERSON",        80, 15]
];

$headerY = $pageH - 90;
$pageBottomLimit = 55;

$pagesContent = [];
$currentContent = "BT\n";
$y = 0;

render_header($currentContent, $pageW, $pageH, $margin, $cols, $y, $headerY);

$total = 0;

while ($r = $rows->fetch_assoc()) {
    $colValues = [
        mb_strtoupper($r["article"] ?? ''),
        $r["description"] ?? '',
        $r["property_no"] ?? '',
        $r["new_property_no"] ?? '',
        $r["unit_of_measure"] ?? 'pc',
        "Php " . number_format((float)($r["cost"] ?? 0), 2),
        $r["qty_property_card"] ?? '1',
        $r["qty_physical_count"] ?? '1',
        mb_strtoupper($r["location"] ?? ''),
        mb_strtoupper($r["status"] ?? ''),
        $r["date_acquired"] ?? '',
        mb_strtoupper($r["accountable_person"] ?? '')
    ];

    // Break text into wrapped line arrays per column
    $wrappedCols = [];
    $maxLinesInRow = 1;

    foreach ($cols as $i => $col) {
        $lines = wrap_text_lines($colValues[$i], $col[2]);
        $wrappedCols[$i] = $lines;
        if (count($lines) > $maxLinesInRow) {
            $maxLinesInRow = count($lines);
        }
    }

    // Dynamic row height based on content line count
    $lineHeight = 8;
    $rowH = max(16, ($maxLinesInRow * $lineHeight) + 6);

    // Check if page break is needed
    if (($y - $rowH) < $pageBottomLimit) {
        $currentContent .= "ET";
        $pagesContent[] = $currentContent;
        $currentContent = "BT\n";
        render_header($currentContent, $pageW, $pageH, $margin, $cols, $y, $headerY);
    }

    $topY = $y - 8;
    $x = $margin;

    // Render multi-line cell text
    foreach ($cols as $i => $col) {
        foreach ($wrappedCols[$i] as $lineIdx => $lineText) {
            add_text($currentContent, $x + 2, $topY - ($lineIdx * $lineHeight), $lineText, 6);
        }
        $x += $col[1];
    }

    $y -= $rowH;
    add_line($currentContent, $margin, $y, $pageW - $margin, $y);
    $total += (float)($r["cost"] ?? 0);
}

// Check space for Total & Signatures
if (($y - 90) < $pageBottomLimit) {
    $currentContent .= "ET";
    $pagesContent[] = $currentContent;
    $currentContent = "BT\n";
    render_header($currentContent, $pageW, $pageH, $margin, $cols, $y, $headerY);
}

// Total Cost Section
$y -= 15;
add_text($currentContent, $margin, $y, "TOTAL COST", 8, "/F2");
add_text($currentContent, $margin + 340, $y, "Php " . number_format($total, 2), 8, "/F2");

// Signatures Section
$sigY = $y - 35;
if ($sigY < 60) $sigY = 60;

$leftX = $margin + 20;
add_text($currentContent, $leftX, $sigY, "Prepared by:", 8, "/F1");
add_text($currentContent, $leftX, $sigY - 25, "ALVIN RAY S. BAWAR", 9, "/F2");
add_text($currentContent, $leftX, $sigY - 36, "Property Custodian", 8, "/F1");

$rightX = $pageW - $margin - 220;
add_text($currentContent, $rightX, $sigY, "Approved by:", 8, "/F1");
add_text($currentContent, $rightX, $sigY - 25, "JONATHAN R. DIGMA, Ph.D.", 9, "/F2");
add_text($currentContent, $rightX, $sigY - 36, "Campus Administrator", 8, "/F1");

$currentContent .= "ET";
$pagesContent[] = $currentContent;

// PDF Structure Construction
$numPages = count($pagesContent);
$kids = [];
for ($k = 1; $k <= $numPages; $k++) {
    $pageObjId = 3 + (2 * $k);
    $kids[] = "$pageObjId 0 R";
}

$objects = [];
$objects[1] = "<< /Type /Catalog /Pages 2 0 R >>";
$objects[2] = "<< /Type /Pages /Kids [" . implode(" ", $kids) . "] /Count $numPages >>";
$objects[3] = "<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>";
$objects[4] = "<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica-Bold >>";

for ($k = 1; $k <= $numPages; $k++) {
    $pageObjId = 3 + (2 * $k);
    $contentObjId = 4 + (2 * $k);
    
    $objects[$pageObjId] = "<< /Type /Page /Parent 2 0 R /MediaBox [0 0 $pageW $pageH] /Resources << /Font << /F1 3 0 R /F2 4 0 R >> >> /Contents $contentObjId 0 R >>";
    $objects[$contentObjId] = "<< /Length " . strlen($pagesContent[$k-1]) . " >>\nstream\n" . $pagesContent[$k-1] . "\nendstream";
}

ksort($objects);

$pdf = "%PDF-1.4\n";
$offsets = [0];
$totalObjects = count($objects);

foreach ($objects as $id => $obj) {
    $offsets[$id] = strlen($pdf);
    $pdf .= $id . " 0 obj\n" . $obj . "\nendobj\n";
}

$xref = strlen($pdf);
$pdf .= "xref\n0 " . ($totalObjects + 1) . "\n0000000000 65535 f \n";
for ($i = 1; $i <= $totalObjects; $i++) {
    $pdf .= sprintf("%010d 00000 n \n", $offsets[$i]);
}
$pdf .= "trailer\n<< /Size " . ($totalObjects + 1) . " /Root 1 0 R >>\nstartxref\n" . $xref . "\n%%EOF";

if (ob_get_length()) ob_end_clean();

header("Content-Type: application/pdf");
header('Content-Disposition: inline; filename="property_report_' . date("Ymd_His") . '.pdf"');
header("Content-Length: " . strlen($pdf));
echo $pdf;
?>