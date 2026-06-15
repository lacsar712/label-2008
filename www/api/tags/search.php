<?php
header('Content-Type: application/json; charset=utf-8');
require_once '../../config.php';
require_once '../../common.php';

$q = isset($_GET['q']) ? sanitize($_GET['q']) : '';
$limit = isset($_GET['limit']) ? intval($_GET['limit']) : 20;

if (empty($q)) {
    json_response(400, '搜索关键词不能为空');
}

$conn = getConnection();

$sql = "SELECT * FROM tags WHERE name LIKE ? ORDER BY reference_count DESC, id ASC LIMIT ?";
$stmt = $conn->prepare($sql);
$search_param = "%$q%";
$stmt->bind_param("si", $search_param, $limit);
$stmt->execute();
$result = $stmt->get_result();

$tags = [];
while ($row = $result->fetch_assoc()) {
    $tags[] = $row;
}

$stmt->close();
closeConnection($conn);

json_response(200, '搜索成功', $tags);
?>
