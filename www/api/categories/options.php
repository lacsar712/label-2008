<?php
header('Content-Type: application/json; charset=utf-8');
require_once '../../config.php';
require_once '../../common.php';

$conn = getConnection();

$sql = "SELECT * FROM categories WHERE status = 'enabled' ORDER BY sort_order ASC, id ASC";
$result = $conn->query($sql);

$categories = [];
while ($row = $result->fetch_assoc()) {
    $categories[] = $row;
}

closeConnection($conn);

json_response(200, '获取成功', $categories);
?>