<?php
include_once("config.php");

$plant = $_POST['plant'];
$mode  = $_POST['mode'];

$stmt = $conn->prepare("UPDATE line_mode SET mode=?, start_time=NOW() WHERE plant=?");
$stmt->bind_param("ss",$mode,$plant);
$stmt->execute();

echo json_encode([
    "status"=>"success",
    "mode"=>$mode
]);