<?php
header('Content-Type: application/json; charset=utf-8');
require_once '../../config.php';
require_once '../../common.php';

require_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(400, '请求方法错误');
}

$input = json_decode(file_get_contents('php://input'), true);

$id = isset($input['id']) ? intval($input['id']) : 0;
$name = isset($input['name']) ? sanitize($input['name']) : '';
$emoji = isset($input['emoji']) ? sanitize($input['emoji']) : '';
$color = isset($input['color']) ? sanitize($input['color']) : '#6366f1';
$description = isset($input['description']) ? sanitize($input['description']) : '';
$sort_order = isset($input['sort_order']) ? intval($input['sort_order']) : 0;
$status = isset($input['status']) ? sanitize($input['status']) : 'enabled';

if ($id <= 0) {
    json_response(400, '参数错误');
}

if (empty($name)) {
    json_response(400, '分类名称不能为空');
}

$conn = getConnection();

$check_stmt = $conn->prepare("SELECT id FROM categories WHERE id = ?");
$check_stmt->bind_param("i", $id);
$check_stmt->execute();
$check_result = $check_stmt->get_result();
if (!$check_result->fetch_assoc()) {
    $check_stmt->close();
    closeConnection($conn);
    json_response(404, '分类不存在');
}
$check_stmt->close();

$stmt = $conn->prepare("UPDATE categories SET name=?, emoji=?, color=?, description=?, sort_order=?, status=? WHERE id=?");
$stmt->bind_param("ssssisi", $name, $emoji, $color, $description, $sort_order, $status, $id);

if ($stmt->execute()) {
    $stmt->close();
    $get_stmt = $conn->prepare("SELECT * FROM categories WHERE id = ?");
    $get_stmt->bind_param("i", $id);
    $get_stmt->execute();
    $result = $get_stmt->get_result();
    $category = $result->fetch_assoc();
    $get_stmt->close();
    closeConnection($conn);
    json_response(200, '更新成功', $category);
} else {
    $error = $conn->error;
    $stmt->close();
    closeConnection($conn);
    json_response(500, '更新失败: ' . $error);
}
?>