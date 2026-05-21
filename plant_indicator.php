<?php
include "config.php";

// Returns count of abnormal machines per plant
$query = "
SELECT plant, COUNT(*) as total
FROM machine
WHERE status = 2
GROUP BY plant
";

$result = $conn->query($query);

$data = [
    1 => 0,
    2 => 0,
    3 => 0
];

while($row = $result->fetch_assoc()){
    $plantVal = $row['plant'];
    if ($plantVal === 'assembly' || $plantVal === '1') {
        $data[1] = (int)$row['total'];
    } elseif ($plantVal === 'test run' || $plantVal === '2') {
        $data[2] = (int)$row['total'];
    } elseif ($plantVal === 'packing' || $plantVal === '3') {
        $data[3] = (int)$row['total'];
    }
}

echo json_encode($data);
