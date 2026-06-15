<?php
header('Content-Type: application/json; charset=utf-8');
require_once '../../config.php';
require_once '../../common.php';

require_login();

$user_id = $_SESSION['user_id'];

$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$per_page = isset($_GET['per_page']) ? intval($_GET['per_page']) : 10;
$type = isset($_GET['type']) ? sanitize($_GET['type']) : '';
$is_read = isset($_GET['is_read']) ? sanitize($_GET['is_read']) : '';

$conn = getConnection();

$where_clauses = ["receiver_id = ?"];
$params = [$user_id];
$types = 'i';

if (!empty($type)) {
    $where_clauses[] = "type = ?";
    $params[] = $type;
    $types .= 's';
}

if ($is_read !== '' && $is_read !== null) {
    $where_clauses[] = "is_read = ?";
    $params[] = intval($is_read);
    $types .= 'i';
}

$where_sql = "WHERE " . implode(" AND ", $where_clauses);

$count_sql = "SELECT COUNT(*) as total FROM messages $where_sql";
$count_stmt = $conn->prepare($count_sql);
$count_stmt->bind_param($types, ...$params);
$count_stmt->execute();
$total_result = $count_stmt->get_result();
$total_records = $total_result->fetch_assoc()['total'];
$count_stmt->close();

$total_pages = ceil($total_records / $per_page);
$offset = ($page - 1) * $per_page;

$sql = "SELECT * FROM messages $where_sql ORDER BY created_at DESC LIMIT ? OFFSET ?";
$params[] = $per_page;
$params[] = $offset;
$types .= 'ii';

$stmt = $conn->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();

$messages = [];
while ($row = $result->fetch_assoc()) {
    $messages[] = $row;
}

$stmt->close();
closeConnection($conn);

json_response(200, '获取成功', [
    'list' => $messages,
    'pagination' => [
        'page' => $page,
        'per_page' => $per_page,
        'total_records' => $total_records,
        'total_pages' => $total_pages
    ]
]);
?>
