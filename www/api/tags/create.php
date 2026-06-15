<?php
header('Content-Type: application/json; charset=utf-8');
require_once '../../config.php';
require_once '../../common.php';

require_login();
require_permission('tag:create');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(400, '请求方法错误');
}

$input = json_decode(file_get_contents('php://input'), true);

$name = isset($input['name']) ? sanitize($input['name']) : '';
$color = isset($input['color']) ? sanitize($input['color']) : '#6366f1';

if (empty($name)) {
    json_response(400, '标签名称不能为空');
}

$conn = getConnection();

$check_stmt = $conn->prepare("SELECT id FROM tags WHERE name = ?");
$check_stmt->bind_param("s", $name);
$check_stmt->execute();
$check_result = $check_stmt->get_result();
if ($check_result->fetch_assoc()) {
    $check_stmt->close();
    closeConnection($conn);
    json_response(400, '标签已存在');
}
$check_stmt->close();

$stmt = $conn->prepare("INSERT INTO tags (name, color) VALUES (?, ?)");
$stmt->bind_param("ss", $name, $color);

if ($stmt->execute()) {
    $id = $conn->insert_id;
    $stmt->close();
    $get_stmt = $conn->prepare("SELECT * FROM tags WHERE id = ?");
    $get_stmt->bind_param("i", $id);
    $get_stmt->execute();
    $result = $get_stmt->get_result();
    $tag = $result->fetch_assoc();
    $get_stmt->close();
    closeConnection($conn);
    json_response(200, '创建成功', $tag);
} else {
    $error = $conn->error;
    $stmt->close();
    closeConnection($conn);
    json_response(500, '创建失败: ' . $error);
}
?>
