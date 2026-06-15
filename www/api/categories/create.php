<?php
header('Content-Type: application/json; charset=utf-8');
require_once '../../config.php';
require_once '../../common.php';

require_login();
require_permission('category:create');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(400, '请求方法错误');
}

$input = json_decode(file_get_contents('php://input'), true);

$name = isset($input['name']) ? sanitize($input['name']) : '';
$emoji = isset($input['emoji']) ? sanitize($input['emoji']) : '';
$color = isset($input['color']) ? sanitize($input['color']) : '#6366f1';
$description = isset($input['description']) ? sanitize($input['description']) : '';
$sort_order = isset($input['sort_order']) ? intval($input['sort_order']) : 0;
$status = isset($input['status']) ? sanitize($input['status']) : 'enabled';

if (empty($name)) {
    json_response(400, '分类名称不能为空');
}

$conn = getConnection();

$max_sort_stmt = $conn->query("SELECT MAX(sort_order) as max_sort FROM categories");
$max_sort_row = $max_sort_stmt->fetch_assoc();
$sort_order = $sort_order > 0 ? $sort_order : ($max_sort_row['max_sort'] + 1);

$stmt = $conn->prepare("INSERT INTO categories (name, emoji, color, description, sort_order, status) VALUES (?, ?, ?, ?, ?, ?)");
$stmt->bind_param("ssssis", $name, $emoji, $color, $description, $sort_order, $status);

if ($stmt->execute()) {
    $id = $conn->insert_id;
    $stmt->close();
    $get_stmt = $conn->prepare("SELECT * FROM categories WHERE id = ?");
    $get_stmt->bind_param("i", $id);
    $get_stmt->execute();
    $result = $get_stmt->get_result();
    $category = $result->fetch_assoc();
    $get_stmt->close();
    closeConnection($conn);
    write_operation_log('create', 'category', $id, null, $category);
    json_response(200, '创建成功', $category);
} else {
    $error = $conn->error;
    $stmt->close();
    closeConnection($conn);
    json_response(500, '创建失败: ' . $error);
}
?>