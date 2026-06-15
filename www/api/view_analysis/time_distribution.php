<?php
require_once '../../common.php';

header('Content-Type: application/json; charset=utf-8');

require_permission('view_analysis:view');

$days = isset($_GET['days']) ? intval($_GET['days']) : 30;
$days = max(1, min($days, 365));

$conn = getConnection();

$sql = "SELECT 
    CASE 
        WHEN HOUR(created_at) BETWEEN 0 AND 5 THEN '凌晨(0-5点)'
        WHEN HOUR(created_at) BETWEEN 6 AND 8 THEN '早晨(6-8点)'
        WHEN HOUR(created_at) BETWEEN 9 AND 11 THEN '上午(9-11点)'
        WHEN HOUR(created_at) BETWEEN 12 AND 13 THEN '中午(12-13点)'
        WHEN HOUR(created_at) BETWEEN 14 AND 17 THEN '下午(14-17点)'
        WHEN HOUR(created_at) BETWEEN 18 AND 20 THEN '傍晚(18-20点)'
        WHEN HOUR(created_at) BETWEEN 21 AND 23 THEN '夜间(21-23点)'
    END as time_slot,
    COUNT(*) as count
FROM view_logs 
WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL ? DAY)
GROUP BY time_slot
ORDER BY FIELD(time_slot, '凌晨(0-5点)', '早晨(6-8点)', '上午(9-11点)', '中午(12-13点)', '下午(14-17点)', '傍晚(18-20点)', '夜间(21-23点)')";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $days);
$stmt->execute();
$result = $stmt->get_result();

$distribution = [];
$total = 0;
while ($row = $result->fetch_assoc()) {
    $total += intval($row['count']);
    $distribution[] = [
        'time_slot' => $row['time_slot'],
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