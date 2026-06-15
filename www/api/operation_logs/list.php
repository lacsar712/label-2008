<?php
header('Content-Type: application/json; charset=utf-8');
require_once '../../config.php';
require_once '../../common.php';

require_login();
require_permission('log:view');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    json_response(400, '请求方法错误');
}

$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$per_page = isset($_GET['per_page']) ? intval($_GET['per_page']) : 20;
$per_page = min(max($per_page, 1), 100);
$offset = ($page - 1) * $per_page;

$user_id = isset($_GET['user_id']) ? intval($_GET['user_id']) : 0;
$operator = isset($_GET['operator']) ? sanitize($_GET['operator']) : '';
$operation_type = isset($_GET['operation_type']) ? sanitize($_GET['operation_type']) : '';
$target_type = isset($_GET['target_type']) ? sanitize($_GET['target_type']) : '';
$target_id = isset($_GET['target_id']) ? sanitize($_GET['target_id']) : '';
$keyword = isset($_GET['keyword']) ? sanitize($_GET['keyword']) : '';
$date_from = isset($_GET['date_from']) ? sanitize($_GET['date_from']) : '';
$date_to = isset($_GET['date_to']) ? sanitize($_GET['date_to']) : '';

$conn = getConnection();

$where_clauses = [];
$params = [];
$types = '';

if ($user_id > 0) {
    $where_clauses[] = "l.user_id = ?";
    $params[] = $user_id;
    $types .= 'i';
}

if (!empty($operator)) {
    $where_clauses[] = "(l.user_nickname LIKE ? OR u.username LIKE ?)";
    $operator_param = "%$operator%";
    $params[] = $operator_param;
    $params[] = $operator_param;
    $types .= 'ss';
}

if (!empty($operation_type)) {
    $where_clauses[] = "l.operation_type = ?";
    $params[] = $operation_type;
    $types .= 's';
}

if (!empty($target_type)) {
    $where_clauses[] = "l.target_type = ?";
    $params[] = $target_type;
    $types .= 's';
}

if (!empty($target_id)) {
    $where_clauses[] = "l.target_id = ?";
    $params[] = $target_id;
    $types .= 's';
}

if (!empty($keyword)) {
    $where_clauses[] = "(l.user_nickname LIKE ? OR l.operation_type LIKE ? OR l.target_type LIKE ? OR l.target_id LIKE ?)";
    $keyword_param = "%$keyword%";
    $params[] = $keyword_param;
    $params[] = $keyword_param;
    $params[] = $keyword_param;
    $params[] = $keyword_param;
    $types .= 'ssss';
}

if (!empty($date_from)) {
    $where_clauses[] = "l.created_at >= ?";
    $params[] = $date_from . ' 00:00:00';
    $types .= 's';
}

if (!empty($date_to)) {
    $where_clauses[] = "l.created_at <= ?";
    $params[] = $date_to . ' 23:59:59';
    $types .= 's';
}

$where_sql = !empty($where_clauses) ? "WHERE " . implode(" AND ", $where_clauses) : "";

$count_sql = "SELECT COUNT(*) as total FROM operation_logs l $where_sql";
$count_stmt = $conn->prepare($count_sql);
if (!empty($params)) {
    $count_stmt->bind_param($types, ...$params);
}
$count_stmt->execute();
$count_result = $count_stmt->get_result();
$total = $count_result->fetch_assoc()['total'];
$count_stmt->close();

$sql = "SELECT l.*, u.username, u.avatar_url 
        FROM operation_logs l 
        LEFT JOIN users u ON l.user_id = u.id 
        $where_sql 
        ORDER BY l.created_at DESC 
        LIMIT ? OFFSET ?";

$list_params = $params;
$list_types = $types . 'ii';
$list_params[] = $per_page;
$list_params[] = $offset;

$stmt = $conn->prepare($sql);
$stmt->bind_param($list_types, ...$list_params);
$stmt->execute();
$result = $stmt->get_result();

$logs = [];
while ($row = $result->fetch_assoc()) {
    $row['operation_type_label'] = get_operation_type_label($row['operation_type']);
    $row['target_type_label'] = get_target_type_label($row['target_type']);
    unset($row['before_data']);
    unset($row['after_data']);
    unset($row['user_agent']);
    $logs[] = $row;
}

$stmt->close();
closeConnection($conn);

$operation_types = [
    ['value' => 'create', 'label' => '创建'],
    ['value' => 'update', 'label' => '更新'],
    ['value' => 'delete', 'label' => '删除'],
    ['value' => 'batch_update', 'label' => '批量更新'],
    ['value' => 'batch_delete', 'label' => '批量删除'],
    ['value' => 'assign_permission', 'label' => '分配权限'],
    ['value' => 'assign_role', 'label' => '分配角色'],
    ['value' => 'set_tags', 'label' => '设置标签'],
    ['value' => 'merge_tags', 'label' => '合并标签'],
    ['value' => 'import', 'label' => '导入']
];

$target_types = [
    ['value' => 'notice', 'label' => '公告'],
    ['value' => 'category', 'label' => '分类'],
    ['value' => 'tag', 'label' => '标签'],
    ['value' => 'role', 'label' => '角色'],
    ['value' => 'user', 'label' => '用户'],
    ['value' => 'role_permission', 'label' => '角色权限'],
    ['value' => 'user_role', 'label' => '用户角色']
];

json_response(200, '查询成功', [
    'list' => $logs,
    'pagination' => [
        'current_page' => $page,
        'per_page' => $per_page,
        'total' => $total,
        'total_pages' => ceil($total / $per_page)
    ],
    'filters' => [
        'operation_types' => $operation_types,
        'target_types' => $target_types
    ]
]);
?>