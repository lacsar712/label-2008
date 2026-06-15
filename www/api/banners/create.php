<?php
header('Content-Type: application/json; charset=utf-8');
require_once '../../config.php';
require_once '../../common.php';

require_login();
require_permission('banner:create');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(400, '请求方法错误');
}

$input = json_decode(file_get_contents('php://input'), true);

$image_url = isset($input['image_url']) ? sanitize($input['image_url']) : '';
$title = isset($input['title']) ? sanitize($input['title']) : '';
$subtitle = isset($input['subtitle']) ? sanitize($input['subtitle']) : '';
$link_url = isset($input['link_url']) ? sanitize($input['link_url']) : '';
$start_time = isset($input['start_time']) ? sanitize($input['start_time']) : null;
$end_time = isset($input['end_time']) ? sanitize($input['end_time']) : null;
$sort_order = isset($input['sort_order']) ? intval($input['sort_order']) : 0;
$status = isset($input['status']) ? sanitize($input['status']) : 'enabled';

if (empty($image_url)) {
    json_response(400, '图片不能为空');
}

$conn = getConnection();

$max_sort_stmt = $conn->query("SELECT MAX(sort_order) as max_sort FROM banners");
$max_sort_row = $max_sort_stmt->fetch_assoc();
$sort_order = $sort_order > 0 ? $sort_order : ($max_sort_row['max_sort'] + 1);

$stmt = $conn->prepare("INSERT INTO banners (image_url, title, subtitle, link_url, start_time, end_time, sort_order, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
$stmt->bind_param("ssssssis", $image_url, $title, $subtitle, $link_url, $start_time, $end_time, $sort_order, $status);

if ($stmt->execute()) {
    $id = $conn->insert_id;
    $stmt->close();
    $get_stmt = $conn->prepare("SELECT * FROM banners WHERE id = ?");
    $get_stmt->bind_param("i", $id);
    $get_stmt->execute();
    $result = $get_stmt->get_result();
    $banner = $result->fetch_assoc();
    $get_stmt->close();
    closeConnection($conn);
    write_operation_log('create', 'banner', $id, null, $banner);
    json_response(200, '创建成功', $banner);
} else {
    $error = $conn->error;
    $stmt->close();
    closeConnection($conn);
    json_response(500, '创建失败: ' . $error);
}
?>
