<?php
require_once "config.php";
date_default_timezone_set('Asia/Manila'); // Set your local timezone
if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}
?>