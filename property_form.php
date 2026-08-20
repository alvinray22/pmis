<?php
require_once "auth.php";

$id = (int)($_GET["id"] ?? 0);
$editing = $id > 0;
$error = "";

$data = [
    "article"            => "",
    "description"        => "",
    "property_no"        => "",
    "new_property_no"    => "",
    "unit_of_measure"    => "pc",
    "cost"               => "0.00",
    "qty_property_card"  => "1",
    "qty_physical_count" => "1",
    "location"           => "",
    "status"             => "Serviceable",
    "date_acquired"      => "",
    "accountable_person" => ""
];

if ($editing) {
    $stmt = $conn->prepare("SELECT * FROM properties WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $found = $stmt->get_result()->fetch_assoc();
    if (!$found) {
        header("Location: properties.php");
        exit;
    }
    $data = array_merge($data, $found);
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    foreach ($data as $k => $v) {
        if (isset($_POST[$k])) {
            $data[$k] = trim($_POST[$k]);
        }
    }

    $data["cost"]               = (float)($_POST["cost"] ?? 0);
    $data["qty_property_card"]  = (int)($_POST["qty_property_card"] ?? 1);
    $data["qty_physical_count"] = (int)($_POST["qty_physical_count"] ?? 1);

    if ($data["article"] === "" || $data["description"] === "" || $data["status"] === "" || $data["property_no"] === "") {
        $error = "Please complete the required fields (Article/Item, Description, Status, and Old Property No.).";
    } else if ($editing) {
        // UPDATE record (bind_param has 13 types: sssssdiissssi)
        $stmt = $conn->prepare("UPDATE properties SET article=?, description=?, property_no=?, new_property_no=?, unit_of_measure=?, cost=?, qty_property_card=?, qty_physical_count=?, location=?, status=?, date_acquired=?, accountable_person=? WHERE id=?");
        $stmt->bind_param(
            "sssssdiissssi",
            $data["article"],
            $data["description"],
            $data["property_no"],
            $data["new_property_no"],
            $data["unit_of_measure"],
            $data["cost"],
            $data["qty_property_card"],
            $data["qty_physical_count"],
            $data["location"],            // Bound as string 's'
            $data["status"],
            $data["date_acquired"],
            $data["accountable_person"],
            $id
        );
        $stmt->execute();
        header("Location: properties.php");
        exit;
    } else {
        // INSERT record (bind_param has 12 types: sssssdiissss)
        $stmt = $conn->prepare("INSERT INTO properties (article, description, property_no, new_property_no, unit_of_measure, cost, qty_property_card, qty_physical_count, location, status, date_acquired, accountable_person) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param(
            "sssssdiissss",
            $data["article"],
            $data["description"],
            $data["property_no"],
            $data["new_property_no"],
            $data["unit_of_measure"],
            $data["cost"],
            $data["qty_property_card"],
            $data["qty_physical_count"],
            $data["location"],            // Bound as string 's'
            $data["status"],
            $data["date_acquired"],
            $data["accountable_person"]
        );
        if (!$stmt->execute()) {
            $error = "Unable to save property record. Please check input data.";
        } else {
            header("Location: properties.php");
            exit;
        }
    }
}

$statuses = ["Condemned", "Unserviceable", "Not usable", "Repair", "Missing", "For Verification", "Good/Issued", "Serviceable"];
?>
<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <title><?= $editing ? "Edit" : "Add" ?> Property</title>
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

<main class="container narrow">
    <div class="page-head">
        <div>
            <h1><?= $editing ? 'Edit Property' : 'Add Property' ?></h1>
            <p class="muted" style="margin: 4px 0 0 0;">
                Complete inventory records &bull; <strong id="liveDateTime"><?= date("F j, Y | h:i:s A") ?></strong>
            </p>
        </div>
    </div>

    <?php if ($error): ?>
        <div class="alert danger"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <div class="panel">
        <form method="post" class="grid-form">
            <label>Article / Item *
                <input name="article" required value="<?= htmlspecialchars($data["article"]) ?>">
            </label>

            <label>Description *
                <input name="description" required value="<?= htmlspecialchars($data["description"]) ?>">
            </label>

            <label>Old Property No. Assigned *
                <input name="property_no" required value="<?= htmlspecialchars($data["property_no"]) ?>">
            </label>

            <label>New Property No. Assigned (For Validation)
                <input name="new_property_no" value="<?= htmlspecialchars($data["new_property_no"]) ?>">
            </label>

            <label>Unit of Measure
                <input name="unit_of_measure" value="<?= htmlspecialchars($data["unit_of_measure"]) ?>">
            </label>

            <label>Cost (PHP)
                <input type="number" step="0.01" min="0" name="cost" value="<?= htmlspecialchars($data["cost"]) ?>">
            </label>

            <label>Qty per Property Card
                <input type="number" min="0" name="qty_property_card" value="<?= htmlspecialchars($data["qty_property_card"]) ?>">
            </label>

            <label>Qty per Physical Count
                <input type="number" min="0" name="qty_physical_count" value="<?= htmlspecialchars($data["qty_physical_count"]) ?>">
            </label>

            <label>Location
                <input name="location" value="<?= htmlspecialchars($data["location"]) ?>">
            </label>

            <label>Status *
                <select name="status" required>
                    <?php foreach ($statuses as $s): ?>
                        <option <?= $data["status"] === $s ? "selected" : "" ?>><?= htmlspecialchars($s) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>

            <label>Date Acquired
                <input type="date" name="date_acquired" value="<?= htmlspecialchars($data["date_acquired"]) ?>">
            </label>

            <label>Accountable Person
                <input name="accountable_person" value="<?= htmlspecialchars($data["accountable_person"]) ?>">
            </label>

            <div class="form-actions" style="grid-column: 1 / -1;">
                <button class="btn primary">Save Property</button>
                <a class="btn" href="properties.php">Cancel</a>
            </div>
        </form>
    </div>
</main>

<script>
function updateLiveClock() {
    const now = new Date();
    const dateStr = now.toLocaleDateString('en-US', { month: 'long', day: 'numeric', year: 'numeric' });
    const timeStr = now.toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit', second: '2-digit', hour12: true });
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