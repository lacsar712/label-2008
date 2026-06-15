<?php
header('Content-Type: application/json; charset=utf-8');
require_once '../../config.php';
require_once '../../common.php';

require_login();
require_permission('message:send');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(400, '请求方法错误');
}

$input = json_decode(file_get_contents('php://input'), true);

$receiver_id = isset($input['receiver_id']) ? intval($input['receiver_id']) : 0;
$type = isset($input['type']) ? sanitize($input['type']) : '';
$title = isset($input['title']) ? sanitize($input['title']) : '';
$body = isset($input['body']) ? sanitize($input['body']) : '';
$entity_type = isset($input['entity_type']) ? sanitize($input['entity_type']) : null;
$entity_id = isset($input['entity_id']) ? intval($input['entity_id']) : null;

if ($receiver_id <= 0) {
    json_response(400, '接收人不能为空');
}

if (empty($title)) {
    json_response(400, '消息标题不能为空');
}

if (empty($type)) {
    json_response(400, '消息类型不能为空');
}

$conn = getConnection();

$check_stmt = $conn->prepare("SELECT id FROM users WHERE id = ?");
$check_stmt->bind_param("i", $receiver_id);
$check_stmt->execute();
$check_result = $check_stmt->get_result();
if (!$check_result->fetch_assoc()) {
    $check_stmt->close();
    closeConnection($conn);
    json_response(404, '接收人不存在');
}
$check_stmt->close();

$stmt = $conn->prepare("INSERT INTO messages (receiver_id, type, title, body, entity_type, entity_id) VALUES (?, ?, ?, ?, ?, ?)");
$stmt->bind_param("issssi", $receiver_id, $type, $title, $body, $entity_type, $entity_id);

if ($stmt->execute()) {
    $id = $conn->insert_id;
    $stmt->close();
    closeConnection($conn);
    json_response(200, '发送成功', ['id' => $id]);
} else {
    $error = $conn->error;
    $stmt->close();
    closeConnection($conn);
    json_response(500, '发送失败: ' . $error);
}
?>
