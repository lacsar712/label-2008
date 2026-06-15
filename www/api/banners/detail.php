<?php
header('Content-Type: application/json; charset=utf-8');
require_once '../../config.php';
require_once '../../common.php';

require_login();

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($id <= 0) {
    json_response(400, '参数错误');
}

$conn = getConnection();
$stmt = $conn->prepare("SELECT * FROM banners WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$banner = $result->fetch_assoc();
$stmt->close();
closeConnection($conn);

if (!$banner) {
    json_response(404, 'Banner不存在');
}

json_response(200, '获取成功', $banner);
?>
