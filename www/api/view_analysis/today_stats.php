<?php
require_once '../../common.php';

header('Content-Type: application/json; charset=utf-8');

require_permission('view_analysis:view');

$conn = getConnection();

$sql = "SELECT 
    COUNT(*) as pv,
    COUNT(DISTINCT visitor_id) as uv
FROM view_logs 
WHERE DATE(created_at) = CURDATE()";

$result = $conn->query($sql);
$today = $result->fetch_assoc();

$sql_yesterday = "SELECT 
    COUNT(*) as pv,
    COUNT(DISTINCT visitor_id) as uv
FROM view_logs 
WHERE DATE(created_at) = DATE_SUB(CURDATE(), INTERVAL 1 DAY)";

$result_yesterday = $conn->query($sql_yesterday);
$yesterday = $result_yesterday->fetch_assoc();

closeConnection($conn);

$data = [
    'today_pv' => intval($today['pv']),
    'today_uv' => intval($today['uv']),
    'yesterday_pv' => intval($yesterday['pv']),
    'yesterday_uv' => intval($yesterday['uv']),
    'pv_change' => $yesterday['pv'] > 0 ? round(($today['pv'] - $yesterday['pv']) / $yesterday['pv'] * 100, 2) : ($today['pv'] > 0 ? 100 : 0),
    'uv_change' => $yesterday['uv'] > 0 ? round(($today['uv'] - $yesterday['uv']) / $yesterday['uv'] * 100, 2) : ($today['uv'] > 0 ? 100 : 0)
];

json_response(200, '获取成功', $data);
?>