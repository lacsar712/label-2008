<?php
header('Content-Type: application/json; charset=utf-8');
require_once '../../config.php';
require_once '../../common.php';

require_login();
require_permission('tag:view');

$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$per_page = isset($_GET['per_page']) ? intval($_GET['per_page']) : 10;
$keyword = isset($_GET['keyword']) ? sanitize($_GET['keyword']) : '';

$conn = getConnection();

$where_clauses = [];
$params = [];
$types = '';

if (!empty($keyword)) {
    $where_clauses[] = "name LIKE ?";
    $params[] = "%$keyword%";
    $types .= 's';
}

$where_sql = !empty($where_clauses) ? "WHERE " . implode(" AND ", $where_clauses) : "";

$count_sql = "SELECT COUNT(*) as total FROM tags $where_sql";
if (!empty($params)) {
    $count_stmt = $conn->prepare($count_sql);
    $count_stmt->bind_param($types, ...$params);
    $count_stmt->execute();
    $total_result = $count_stmt->get_result();
    $total_records = $total_result->fetch_assoc()['total'];
    $count_stmt->close();
} else {
    $total_result = $conn->query($count_sql);
    $total_records = $total_result->fetch_assoc()['total'];
}

$total_pages = ceil($total_records / $per_page);
$offset = ($page - 1) * $per_page;

$sql = "SELECT * FROM tags $where_sql ORDER BY reference_count DESC, id ASC LIMIT ? OFFSET ?";
$params[] = $per_page;
$params[] = $offset;
$types .= 'ii';

$stmt = $conn->prepare($sql);
if (!empty($types)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();

$tags = [];
while ($row = $result->fetch_assoc()) {
    $tags[] = $row;
}

$stmt->close();
closeConnection($conn);

json_response(200, '获取成功', [
    'list' => $tags,
    'pagination' => [
        'page' => $page,
        'per_page' => $per_page,
        'total_records' => $total_records,
        'total_pages' => $total_pages
    ]
]);
?>
