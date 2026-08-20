<?php
function getReportRows($conn) {
    $search     = trim($_GET['search'] ?? '');
    $status     = trim($_GET['status'] ?? '');
    $start_date = trim($_GET['start_date'] ?? '');
    $end_date   = trim($_GET['end_date'] ?? '');

    $sql = "SELECT * FROM properties WHERE 1=1";
    $params = [];
    $types  = "";

    if ($search !== '') {
        $sql .= " AND (property_no LIKE ? OR new_property_no LIKE ? OR article LIKE ? OR description LIKE ? OR accountable_person LIKE ? OR location LIKE ?)";
        $searchTerm = "%{$search}%";
        for ($i = 0; $i < 6; $i++) {
            $params[] = $searchTerm;
            $types   .= "s";
        }
    }

    if ($status !== '') {
        $sql .= " AND status = ?";
        $params[] = $status;
        $types   .= "s";
    }

    if ($start_date !== '') {
        $sql .= " AND date_acquired >= ?";
        $params[] = $start_date;
        $types   .= "s";
    }

    if ($end_date !== '') {
        $sql .= " AND date_acquired <= ?";
        $params[] = $end_date;
        $types   .= "s";
    }

    $sql .= " ORDER BY id DESC";

    if (!empty($params)) {
        $stmt = $conn->prepare($sql);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $rows = $stmt->get_result();
    } else {
        $rows = $conn->query($sql);
    }

    return [$rows, $search, $status, $start_date, $end_date];
    if (!function_exists('getStatusTheme')) {
    function getStatusTheme($status) {
        $key = strtolower(preg_replace('/[^a-z0-9]/i', '', (string)$status));

        $colors = [
            'serviceable'     => ['bg' => '#d4edda', 'border' => '#c3e6cb'],
            'condemned'       => ['bg' => '#f8d7da', 'border' => '#f5c6cb'],
            'unserviceable'   => ['bg' => '#e2e3e5', 'border' => '#d6d8db'],
            'repair'          => ['bg' => '#fff3cd', 'border' => '#ffeeba'],
            'notusable'       => ['bg' => '#cfe2ff', 'border' => '#b6d4fe'],
            'missing'         => ['bg' => '#ffe5d0', 'border' => '#ffd8b5'],
            'forverification' => ['bg' => '#e2d9f3', 'border' => '#d0c2eb'],
            'goodissued'      => ['bg' => '#eedcff', 'border' => '#e0c2ff'],
            'total'           => ['bg' => '#d1f2eb', 'border' => '#a3e4d7'],
        ];

        return $colors[$key] ?? ['bg' => '#e2e8f0', 'border' => '#cbd5e1'];
    }
}
}