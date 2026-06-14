<?php
header('Content-Type: application/json; charset=utf-8');
require_once '../../config.php';
require_once '../../common.php';

require_login();
require_permission('category:edit');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(400, '请求方法错误');
}

$input = json_decode(file_get_contents('php://input'), true);

$ids = isset($input['ids']) ? $input['ids'] : [];
$status = isset($input['status']) ? sanitize($input['status']) : '';

if (empty($ids) || !is_array($ids)) {
    json_response(400, '请选择要操作的分类');
}

if (!in_array($status, ['enabled', 'disabled'])) {
    json_response(400, '状态参数错误');
}

$conn = getConnection();

$placeholders = implode(',', array_fill(0, count($ids), '?'));
$types = str_repeat('i', count($ids)) . 's';
$params = array_merge($ids, [$status]);

$stmt = $conn->prepare("UPDATE categories SET status = ? WHERE id IN ($placeholders)");
$stmt->bind_param($types, ...$params);

if ($stmt->execute()) {
    $affected = $stmt->affected_rows;
    $stmt->close();
    closeConnection($conn);
    json_response(200, '操作成功，共更新 ' . $affected . ' 条记录');
} else {
    $error = $conn->error;
    $stmt->close();
    closeConnection($conn);
    json_response(500, '操作失败: ' . $error);
}
?>