<?php
require_once '../../common.php';

header('Content-Type: application/json; charset=utf-8');

require_permission('view_analysis:view');

$days = isset($_GET['days']) ? intval($_GET['days']) : 7;
$days = max(1, min($days, 365));

$conn = getConnection();

$sql = "SELECT 
    COUNT(*) as pv,
    COUNT(DISTINCT visitor_id) as uv
FROM view_logs 
WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL ? DAY)";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $days);
$stmt->execute();
$result = $stmt->get_result();
$current = $result->fetch_assoc();
$stmt->close();

$prev_days = $days;
$sql_prev = "SELECT 
    COUNT(*) as pv,
    COUNT(DISTINCT visitor_id) as uv
FROM view_logs 
WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL ? DAY)
AND created_at < DATE_SUB(CURDATE(), INTERVAL ? DAY)";

$stmt_prev = $conn->prepare($sql_prev);
$stmt_prev->bind_param("ii", $prev_days, $days);
$stmt_prev->execute();
$result_prev = $stmt_prev->get_result();
$prev = $result_prev->fetch_assoc();
$stmt_prev->close();

closeConnection($conn);

$data = [
    'days' => $days,
    'pv' => intval($current['pv']),
    'uv' => intval($current['uv']),
    'prev_pv' => intval($prev['pv']),
    'prev_uv' => intval($prev['uv']),
    'pv_change' => $prev['pv'] > 0 ? round(($current['pv'] - $prev['pv']) / $prev['pv'] * 100, 2) : ($current['pv'] > 0 ? 100 : 0),
    'uv_change' => $prev['uv'] > 0 ? round(($current['uv'] - $prev['uv']) / $prev['uv'] * 100, 2) : ($current['uv'] > 0 ? 100 : 0)
];

json_response(200, '获取成功', $data);
?>