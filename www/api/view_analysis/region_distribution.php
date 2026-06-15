<?php
require_once '../../common.php';

header('Content-Type: application/json; charset=utf-8');

require_permission('view_analysis:view');

$days = isset($_GET['days']) ? intval($_GET['days']) : 30;
$days = max(1, min($days, 365));
$limit = isset($_GET['limit']) ? intval($_GET['limit']) : 10;
$limit = max(1, min($limit, 50));

$conn = getConnection();

$sql = "SELECT 
    COALESCE(NULLIF(region, ''), '未知') as region,
    COUNT(*) as count
FROM view_logs 
WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL ? DAY)
GROUP BY region
ORDER BY count DESC
LIMIT ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $days, $limit);
$stmt->execute();
$result = $stmt->get_result();

$distribution = [];
$total = 0;
while ($row = $result->fetch_assoc()) {
    $total += intval($row['count']);
    $distribution[] = [
        'region' => $row['region'],
        'count' => intval($row['count'])
    ];
}

$stmt->close();
closeConnection($conn);

foreach ($distribution as &$item) {
    $item['percentage'] = $total > 0 ? round($item['count'] / $total * 100, 2) : 0;
}

json_response(200, '获取成功', [
    'total' => $total,
    'distribution' => $distribution
]);
?>