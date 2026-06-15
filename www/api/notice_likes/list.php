<?php
require_once '../../common.php';

header('Content-Type: application/json; charset=utf-8');

require_permission('notice_like:view');

$notice_id = isset($_GET['notice_id']) ? intval($_GET['notice_id']) : 0;
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$page_size = isset($_GET['page_size']) ? intval($_GET['page_size']) : 20;
$client_type = isset($_GET['client_type']) ? sanitize($_GET['client_type']) : '';
$sort_by = isset($_GET['sort_by']) ? sanitize($_GET['sort_by']) : 'created_at';
$sort_order = isset($_GET['sort_order']) ? sanitize($_GET['sort_order']) : 'desc';

if ($notice_id <= 0) {
    json_response(400, '公告ID无效');
}

$page = max(1, $page);
$page_size = max(1, min($page_size, 100));

$allowed_sort_columns = ['created_at', 'nickname', 'client_type'];
if (!in_array($sort_by, $allowed_sort_columns)) {
    $sort_by = 'created_at';
}
$sort_order = strtolower($sort_order) === 'asc' ? 'ASC' : 'DESC';

$conn = getConnection();

$where_clauses = ["notice_id = ?"];
$params = [$notice_id];
$types = 'i';

if (!empty($client_type)) {
    $where_clauses[] = "client_type = ?";
    $params[] = $client_type;
    $types .= 's';
}

$where_sql = implode(" AND ", $where_clauses);

$count_sql = "SELECT COUNT(*) as total FROM notice_likes WHERE $where_sql";
$stmt = $conn->prepare($count_sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();
$total = intval($result->fetch_assoc()['total']);
$stmt->close();

$offset = ($page - 1) * $page_size;

$sql = "SELECT id, notice_id, visitor_id, nickname, ip, client_type, created_at 
        FROM notice_likes 
        WHERE $where_sql 
        ORDER BY $sort_by $sort_order 
        LIMIT ? OFFSET ?";

$stmt = $conn->prepare($sql);
$params[] = $page_size;
$params[] = $offset;
$types .= 'ii';
$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();

$list = [];
while ($row = $result->fetch_assoc()) {
    $list[] = [
        'id' => intval($row['id']),
        'notice_id' => intval($row['notice_id']),
        'visitor_id' => $row['visitor_id'],
        'nickname' => $row['nickname'],
        'ip' => $row['ip'],
        'client_type' => $row['client_type'],
        'created_at' => $row['created_at']
    ];
}
$stmt->close();
closeConnection($conn);

$data = [
    'list' => $list,
    'total' => $total,
    'page' => $page,
    'page_size' => $page_size,
    'total_pages' => ceil($total / $page_size)
];

json_response(200, '获取成功', $data);
?>
