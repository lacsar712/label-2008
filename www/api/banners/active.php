<?php
header('Content-Type: application/json; charset=utf-8');
require_once '../../config.php';
require_once '../../common.php';

$conn = getConnection();

$now = date('Y-m-d H:i:s');

$sql = "SELECT * FROM banners 
        WHERE status = 'enabled' 
        AND (start_time IS NULL OR start_time <= ?)
        AND (end_time IS NULL OR end_time >= ?)
        ORDER BY sort_order ASC, id ASC";

$stmt = $conn->prepare($sql);
$stmt->bind_param("ss", $now, $now);
$stmt->execute();
$result = $stmt->get_result();

$banners = [];
while ($row = $result->fetch_assoc()) {
    $banners[] = $row;
}

$stmt->close();
closeConnection($conn);

json_response(200, '获取成功', $banners);
?>
