<?php
require_once '../../common.php';

header('Content-Type: application/json; charset=utf-8');

require_permission('view_analysis:view');

$start_date = isset($_GET['start_date']) ? sanitize($_GET['start_date']) : '';
$end_date = isset($_GET['end_date']) ? sanitize($_GET['end_date']) : '';
$days = isset($_GET['days']) ? intval($_GET['days']) : 30;

$conn = getConnection();

if (!empty($start_date) && !empty($end_date)) {
    $start = DateTime::createFromFormat('Y-m-d', $start_date);
    $end = DateTime::createFromFormat('Y-m-d', $end_date);
    
    if (!$start || !$end) {
        closeConnection($conn);
        json_response(400, '日期格式错误，请使用 YYYY-MM-DD 格式');
    }
    
    $diff = $start->diff($end)->days;
    if ($diff > 365) {
        closeConnection($conn);
        json_response(400, '日期间隔不能超过365天');
    }
    
    $start_date_str = $start->format('Y-m-d');
    $end_date_str = $end->format('Y-m-d');
    
    $sql = "SELECT 
        DATE(created_at) as date,
        COUNT(*) as pv,
        COUNT(DISTINCT visitor_id) as uv
    FROM view_logs 
    WHERE DATE(created_at) BETWEEN ? AND ?
    GROUP BY DATE(created_at)
    ORDER BY date ASC";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $start_date_str, $end_date_str);
} else {
    $days = max(1, min($days, 365));
    
    $sql = "SELECT 
        DATE(created_at) as date,
        COUNT(*) as pv,
        COUNT(DISTINCT visitor_id) as uv
    FROM view_logs 
    WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL ? DAY)
    GROUP BY DATE(created_at)
    ORDER BY date ASC";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $days);
}

$stmt->execute();
$result = $stmt->get_result();

$raw_data = [];
while ($row = $result->fetch_assoc()) {
    $raw_data[$row['date']] = [
        'pv' => intval($row['pv']),
        'uv' => intval($row['uv'])
    ];
}

$stmt->close();
closeConnection($conn);

$trend_data = [];

if (!empty($start_date) && !empty($end_date)) {
    $start = DateTime::createFromFormat('Y-m-d', $start_date);
    $end = DateTime::createFromFormat('Y-m-d', $end_date);
    
    $interval = new DateInterval('P1D');
    $period = new DatePeriod($start, $interval, $end->modify('+1 day'));
    
    foreach ($period as $date) {
        $date_str = $date->format('Y-m-d');
        $trend_data[] = [
            'date' => $date_str,
            'pv' => isset($raw_data[$date_str]) ? $raw_data[$date_str]['pv'] : 0,
            'uv' => isset($raw_data[$date_str]) ? $raw_data[$date_str]['uv'] : 0
        ];
    }
} else {
    for ($i = $days - 1; $i >= 0; $i--) {
        $date = date('Y-m-d', strtotime("-$i days"));
        $trend_data[] = [
            'date' => $date,
            'pv' => isset($raw_data[$date]) ? $raw_data[$date]['pv'] : 0,
            'uv' => isset($raw_data[$date]) ? $raw_data[$date]['uv'] : 0
        ];
    }
}

json_response(200, '获取成功', $trend_data);
?>