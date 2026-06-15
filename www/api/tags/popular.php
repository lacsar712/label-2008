<?php
header('Content-Type: application/json; charset=utf-8');
require_once '../../config.php';
require_once '../../common.php';

$limit = isset($_GET['limit']) ? intval($_GET['limit']) : 20;

if ($limit <= 0) {
    $limit = 20;
}

$conn = getConnection();

$sql = "SELECT * FROM tags ORDER BY reference_count DESC, id ASC LIMIT ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $limit);
$stmt->execute();
$result = $stmt->get_result();

$tags = [];
while ($row = $result->fetch_assoc()) {
    $tags[] = $row;
}

$stmt->close();
closeConnection($conn);

json_response(200, '获取成功', $tags);
?>
