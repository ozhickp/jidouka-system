<?php
include 'config.php';

$plant = isset($_GET['plant']) ? $_GET['plant'] : 'assembly';

$data = [];

$query = mysqli_query($conn,"
SELECT machine_name,status
FROM machines
WHERE plant='$plant'
");

while($row = mysqli_fetch_assoc($query)){
    $data[] = [
        "machine_name"=>$row['machine_name'],
        "status"=>$row['status']
    ];
}

header('Content-Type: application/json');
echo json_encode($data);