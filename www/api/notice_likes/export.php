<?php
require_once '../../config.php';
require_once '../../common.php';

require_permission('notice_like:export');

$notice_id = isset($_GET['notice_id']) ? intval($_GET['notice_id']) : 0;
$client_type = isset($_GET['client_type']) ? sanitize($_GET['client_type']) : '';

if ($notice_id <= 0) {
    header('HTTP/1.0 400 Bad Request');
    echo '公告ID无效';
    exit();
}

$conn = getConnection();

$where_clauses = ["l.notice_id = ?"];
$params = [$notice_id];
$types = 'i';

if (!empty($client_type)) {
    $where_clauses[] = "l.client_type = ?";
    $params[] = $client_type;
    $types .= 's';
}

$where_sql = implode(" AND ", $where_clauses);

$sql = "SELECT l.*, n.title as notice_title 
        FROM notice_likes l 
        LEFT JOIN notices n ON l.notice_id = n.id 
        WHERE $where_sql 
        ORDER BY l.created_at DESC";

$stmt = $conn->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();

$rows = [];
while ($row = $result->fetch_assoc()) {
    $rows[] = $row;
}
$stmt->close();
closeConnection($conn);

$notice_title = !empty($rows) ? $rows[0]['notice_title'] : 'unknown';
$filename = 'notice_likes_' . $notice_id . '_' . date('Ymd_His') . '.csv';

$headers = ['ID', '昵称', '访客ID', 'IP地址', '客户端类型', '点赞时间'];

$client_type_labels = [
    'desktop' => '桌面端',
    'mobile' => '移动端',
    'tablet' => '平板',
    'other' => '其他'
];

$output_rows = [];
foreach ($rows as $row) {
    $output_rows[] = [
        $row['id'],
        $row['nickname'] ?: '匿名',
        $row['visitor_id'],
        $row['ip'],
        isset($client_type_labels[$row['client_type']]) ? $client_type_labels[$row['client_type']] : $row['client_type'],
        $row['created_at']
    ];
}

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Cache-Control: max-age=0');

$output = fopen('php://output', 'w');
fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF));

fputcsv($output, $headers);

foreach ($output_rows as $row) {
    fputcsv($output, $row);
}

fclose($output);
exit();
?>
