<?php
require_once '../../config.php';
require_once '../../common.php';

require_login();
require_permission('log:export');

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

$sql = "SELECT l.*, u.username FROM operation_logs l LEFT JOIN users u ON l.user_id = u.id $where_sql ORDER BY l.created_at DESC";

if (!empty($params)) {
    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
} else {
    $result = $conn->query($sql);
}

$rows = [];
while ($row = $result->fetch_assoc()) {
    $rows[] = $row;
}

if (isset($stmt)) {
    $stmt->close();
}
closeConnection($conn);

$headers = ['ID', '操作人', '操作类型', '目标类型', '目标ID', 'IP地址', '操作时间'];

$output_rows = [];
foreach ($rows as $row) {
    $output_rows[] = [
        $row['id'],
        $row['user_nickname'] ?: ($row['username'] ?: '未知'),
        get_operation_type_label($row['operation_type']),
        get_target_type_label($row['target_type']),
        $row['target_id'],
        $row['ip'],
        $row['created_at']
    ];
}

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="operation_logs_' . date('Ymd_His') . '.csv"');
header('Cache-Control: max-age=0');

$output = fopen('php://output', 'w');
fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF));

fputcsv($output, $headers);

foreach ($output_rows as $row) {
    fputcsv($output, $row);
}

fclose($output);
exit();
?>