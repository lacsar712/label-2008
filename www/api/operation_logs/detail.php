<?php
header('Content-Type: application/json; charset=utf-8');
require_once '../../config.php';
require_once '../../common.php';

require_login();
require_permission('log:view');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    json_response(400, '请求方法错误');
}

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($id <= 0) {
    json_response(400, '参数错误');
}

$conn = getConnection();

$stmt = $conn->prepare("SELECT l.*, u.username, u.avatar_url FROM operation_logs l LEFT JOIN users u ON l.user_id = u.id WHERE l.id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$log = $result->fetch_assoc();
$stmt->close();
closeConnection($conn);

if (!$log) {
    json_response(404, '日志不存在');
}

$before_data = $log['before_data'] ? json_decode($log['before_data'], true) : null;
$after_data = $log['after_data'] ? json_decode($log['after_data'], true) : null;

$diff = compute_data_diff($before_data, $after_data);

$diff_with_labels = [];
foreach ($diff as $item) {
    $diff_with_labels[] = [
        'field' => $item['field'],
        'field_label' => get_field_label($item['field']),
        'old_value' => $item['old_value'],
        'new_value' => $item['new_value']
    ];
}

$log['operation_type_label'] = get_operation_type_label($log['operation_type']);
$log['target_type_label'] = get_target_type_label($log['target_type']);
$log['before_data'] = $before_data;
$log['after_data'] = $after_data;
$log['diff'] = $diff_with_labels;

json_response(200, '查询成功', $log);
?>