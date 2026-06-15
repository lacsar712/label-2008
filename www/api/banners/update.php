<?php
header('Content-Type: application/json; charset=utf-8');
require_once '../../config.php';
require_once '../../common.php';

require_login();
require_permission('banner:edit');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(400, '请求方法错误');
}

$input = json_decode(file_get_contents('php://input'), true);

$id = isset($input['id']) ? intval($input['id']) : 0;
$image_url = isset($input['image_url']) ? sanitize($input['image_url']) : '';
$title = isset($input['title']) ? sanitize($input['title']) : '';
$subtitle = isset($input['subtitle']) ? sanitize($input['subtitle']) : '';
$link_url = isset($input['link_url']) ? sanitize($input['link_url']) : '';
$start_time = isset($input['start_time']) ? sanitize($input['start_time']) : null;
$end_time = isset($input['end_time']) ? sanitize($input['end_time']) : null;
$sort_order = isset($input['sort_order']) ? intval($input['sort_order']) : 0;
$status = isset($input['status']) ? sanitize($input['status']) : 'enabled';

if ($id <= 0) {
    json_response(400, '参数错误');
}

if (empty($image_url)) {
    json_response(400, '图片不能为空');
}

$conn = getConnection();

$before_stmt = $conn->prepare("SELECT * FROM banners WHERE id = ?");
$before_stmt->bind_param("i", $id);
$before_stmt->execute();
$before_result = $before_stmt->get_result();
$before_data = $before_result->fetch_assoc();
$before_stmt->close();

if (!$before_data) {
    closeConnection($conn);
    json_response(404, 'Banner不存在');
}

$stmt = $conn->prepare("UPDATE banners SET image_url=?, title=?, subtitle=?, link_url=?, start_time=?, end_time=?, sort_order=?, status=? WHERE id=?");
$stmt->bind_param("ssssssisi", $image_url, $title, $subtitle, $link_url, $start_time, $end_time, $sort_order, $status, $id);

if ($stmt->execute()) {
    $stmt->close();
    $get_stmt = $conn->prepare("SELECT * FROM banners WHERE id = ?");
    $get_stmt->bind_param("i", $id);
    $get_stmt->execute();
    $result = $get_stmt->get_result();
    $banner = $result->fetch_assoc();
    $get_stmt->close();
    closeConnection($conn);
    write_operation_log('update', 'banner', $id, $before_data, $banner);
    json_response(200, '更新成功', $banner);
} else {
    $error = $conn->error;
    $stmt->close();
    closeConnection($conn);
    json_response(500, '更新失败: ' . $error);
}
?>
