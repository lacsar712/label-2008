<?php
require_once '../../common.php';

header('Content-Type: application/json; charset=utf-8');

require_permission('notice_like:view');

$notice_id = isset($_GET['notice_id']) ? intval($_GET['notice_id']) : 0;

if ($notice_id <= 0) {
    json_response(400, '公告ID无效');
}

$conn = getConnection();

$stmt = $conn->prepare("SELECT COUNT(*) as total_count, COUNT(DISTINCT visitor_id) as uv FROM notice_likes WHERE notice_id = ?");
$stmt->bind_param("i", $notice_id);
$stmt->execute();
$result = $stmt->get_result();
$total = $result->fetch_assoc();
$stmt->close();

$stmt = $conn->prepare("SELECT COUNT(*) as today_count, COUNT(DISTINCT visitor_id) as today_uv FROM notice_likes WHERE notice_id = ? AND DATE(created_at) = CURDATE()");
$stmt->bind_param("i", $notice_id);
$stmt->execute();
$result = $stmt->get_result();
$today = $result->fetch_assoc();
$stmt->close();

$stmt = $conn->prepare("SELECT COUNT(*) as yesterday_count FROM notice_likes WHERE notice_id = ? AND DATE(created_at) = DATE_SUB(CURDATE(), INTERVAL 1 DAY)");
$stmt->bind_param("i", $notice_id);
$stmt->execute();
$result = $stmt->get_result();
$yesterday = $result->fetch_assoc();
$stmt->close();

closeConnection($conn);

$yesterday_count = intval($yesterday['yesterday_count']);
$today_count = intval($today['today_count']);
$growth_rate = $yesterday_count > 0 
    ? round(($today_count - $yesterday_count) / $yesterday_count * 100, 2) 
    : ($today_count > 0 ? 100 : 0);

$data = [
    'total_count' => intval($total['total_count']),
    'total_uv' => intval($total['uv']),
    'today_count' => $today_count,
    'today_uv' => intval($today['today_uv']),
    'yesterday_count' => $yesterday_count,
    'growth_rate' => $growth_rate
];

json_response(200, '获取成功', $data);
?>
