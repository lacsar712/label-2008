<?php
header('Content-Type: application/json; charset=utf-8');
require_once '../../config.php';
require_once '../../common.php';

require_login();
require_permission('tag:edit');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(400, '请求方法错误');
}

$input = json_decode(file_get_contents('php://input'), true);

$id = isset($input['id']) ? intval($input['id']) : 0;
$name = isset($input['name']) ? sanitize($input['name']) : '';
$color = isset($input['color']) ? sanitize($input['color']) : '#6366f1';

if ($id <= 0) {
    json_response(400, '参数错误');
}

if (empty($name)) {
    json_response(400, '标签名称不能为空');
}

$conn = getConnection();

$before_stmt = $conn->prepare("SELECT * FROM tags WHERE id = ?");
$before_stmt->bind_param("i", $id);
$before_stmt->execute();
$before_result = $before_stmt->get_result();
$before_data = $before_result->fetch_assoc();
$before_stmt->close();

if (!$before_data) {
    closeConnection($conn);
    json_response(404, '标签不存在');
}

$name_check_stmt = $conn->prepare("SELECT id FROM tags WHERE name = ? AND id != ?");
$name_check_stmt->bind_param("si", $name, $id);
$name_check_stmt->execute();
$name_check_result = $name_check_stmt->get_result();
if ($name_check_result->fetch_assoc()) {
    $name_check_stmt->close();
    closeConnection($conn);
    json_response(400, '标签名称已存在');
}
$name_check_stmt->close();

$stmt = $conn->prepare("UPDATE tags SET name=?, color=? WHERE id=?");
$stmt->bind_param("ssi", $name, $color, $id);

if ($stmt->execute()) {
    $stmt->close();
    $get_stmt = $conn->prepare("SELECT * FROM tags WHERE id = ?");
    $get_stmt->bind_param("i", $id);
    $get_stmt->execute();
    $result = $get_stmt->get_result();
    $tag = $result->fetch_assoc();
    $get_stmt->close();
    closeConnection($conn);
    write_operation_log('update', 'tag', $id, $before_data, $tag);
    json_response(200, '更新成功', $tag);
} else {
    $error = $conn->error;
    $stmt->close();
    closeConnection($conn);
    json_response(500, '更新失败: ' . $error);
}
?>
