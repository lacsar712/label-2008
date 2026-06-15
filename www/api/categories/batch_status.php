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

$placeholders_select = implode(',', array_fill(0, count($ids), '?'));
$types_select = str_repeat('i', count($ids));
$before_stmt = $conn->prepare("SELECT * FROM categories WHERE id IN ($placeholders_select)");
$before_stmt->bind_param($types_select, ...$ids);
$before_stmt->execute();
$before_result = $before_stmt->get_result();
$before_data = [];
while ($row = $before_result->fetch_assoc()) {
    $before_data[] = $row;
}
$before_stmt->close();

$stmt = $conn->prepare("UPDATE categories SET status = ? WHERE id IN ($placeholders)");
$stmt->bind_param($types, ...$params);

if ($stmt->execute()) {
    $affected = $stmt->affected_rows;
    $stmt->close();
    
    $after_stmt = $conn->prepare("SELECT * FROM categories WHERE id IN ($placeholders_select)");
    $after_stmt->bind_param($types_select, ...$ids);
    $after_stmt->execute();
    $after_result = $after_stmt->get_result();
    $after_data = [];
    while ($row = $after_result->fetch_assoc()) {
        $after_data[] = $row;
    }
    $after_stmt->close();
    
    closeConnection($conn);
    
    write_operation_log('batch_update', 'category', $ids, [
        'categories' => $before_data
    ], [
        'categories' => $after_data,
        'new_status' => $status
    ]);
    
    json_response(200, '操作成功，共更新 ' . $affected . ' 条记录');
} else {
    $error = $conn->error;
    $stmt->close();
    closeConnection($conn);
    json_response(500, '操作失败: ' . $error);
}
?>