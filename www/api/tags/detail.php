<?php
header('Content-Type: application/json; charset=utf-8');
require_once '../../config.php';
require_once '../../common.php';

require_login();
require_permission('tag:view');

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($id <= 0) {
    json_response(400, '参数错误');
}

$conn = getConnection();
$stmt = $conn->prepare("SELECT * FROM tags WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$tag = $result->fetch_assoc();
$stmt->close();
closeConnection($conn);

if (!$tag) {
    json_response(404, '标签不存在');
}

json_response(200, '获取成功', $tag);
?>
