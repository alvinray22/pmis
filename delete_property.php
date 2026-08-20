<?php
require_once "auth.php";
$id=(int)($_GET["id"]??0);
if($id){$stmt=$conn->prepare("DELETE FROM properties WHERE id=?");$stmt->bind_param("i",$id);$stmt->execute();}
header("Location: properties.php"); exit;
?>