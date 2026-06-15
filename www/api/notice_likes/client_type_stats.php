<?php
require_once '../../common.php';

header('Content-Type: application/json; charset=utf-8');

require_permission('notice_like:view');

$notice_id = isset($_GET['notice_id']) ? intval($_GET['notice_id']) : 0;
$days = isset($_GET['days']) ? intval($_GET['days']) : 0;

if ($notice_id <= 0) {
    json_response(400, '公告ID无效');
}

$conn = getConnection();

$where_clauses = ["notice_id = ?"];
$params = [$notice_id];
$types = 'i';

if ($days > 0) {
    $where_clauses[] = "created_at >= DATE_SUB(CURDATE(), INTERVAL ? DAY)";
    $params[] = $days;
    $types .= 'i';
}

$where_sql = implode(" AND ", $where_clauses);

$sql = "SELECT 
    client_type,
    COUNT(*) as count,
    COUNT(DISTINCT visitor_id) as uv
FROM notice_likes 
WHERE $where_sql
GROUP BY client_type
ORDER BY count DESC";

$stmt = $conn->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();

$distribution = [];
$total = 0;
while ($row = $result->fetch_assoc()) {
    $count = intval($row['count']);
    $total += $count;
    $distribution[] = [
        'client_type' => $row['client_type'] ?: 'unknown',
        'count' => $count,
        'uv' => intval($row['uv'])
    ];
}
$stmt->close();
closeConnection($conn);

foreach ($distribution as &$item) {
    $item['percentage'] = $total > 0 ? round($item['count'] / $total * 100, 2) : 0;
}

$data = [
    'distribution' => $distribution,
    'total' => $total
];

json_response(200, '获取成功', $data);
?>
