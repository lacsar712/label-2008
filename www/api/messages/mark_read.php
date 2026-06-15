<?php
header('Content-Type: application/json; charset=utf-8');
require_once '../../config.php';
require_once '../../common.php';

require_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(400, '请求方法错误');
}

$user_id = $_SESSION['user_id'];
$input = json_decode(file_get_contents('php://input'), true);

$ids = [];

if (isset($input['id'])) {
    $ids[] = intval($input['id']);
} elseif (isset($input['ids']) && is_array($input['ids'])) {
    $ids = array_unique(array_map('intval', $input['ids']));
    $ids = array_filter($ids, function($id) {
        return $id > 0;
    });
}

if (empty($ids)) {
    json_response(400, '参数错误');
}

$conn = getConnection();

$placeholders = implode(',', array_fill(0, count($ids), '?'));
$types = str_repeat('i', count($ids));

$check_stmt = $conn->prepare("SELECT id FROM messages WHERE id IN ($placeholders) AND receiver_id = ?");
$check_types = $types . 'i';
$check_params = array_merge($ids, [$user_id]);
$check_stmt->bind_param($check_types, ...$check_params);
$check_stmt->execute();
$check_result = $check_stmt->get_result();
$valid_ids = [];
while ($row = $check_result->fetch_assoc()) {
    $valid_ids[] = $row['id'];
}
$check_stmt->close();

if (empty($valid_ids)) {
    closeConnection($conn);
    json_response(404, '消息不存在');
}

$valid_placeholders = implode(',', array_fill(0, count($valid_ids), '?'));
$valid_types = str_repeat('i', count($valid_ids));

$stmt = $conn->prepare("UPDATE messages SET is_read = 1 WHERE id IN ($valid_placeholders) AND receiver_id = ?");
$update_types = $valid_types . 'i';
$update_params = array_merge($valid_ids, [$user_id]);
$stmt->bind_param($update_types, ...$update_params);

if ($stmt->execute()) {
    $stmt->close();
    closeConnection($conn);
    json_response(200, '标记成功');
} else {
    $error = $conn->error;
    $stmt->close();
    closeConnection($conn);
    json_response(500, '标记失败: ' . $error);
}
?>
