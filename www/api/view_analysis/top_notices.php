<?php
require_once '../../common.php';

header('Content-Type: application/json; charset=utf-8');

require_permission('view_analysis:view');

$days = isset($_GET['days']) ? intval($_GET['days']) : 30;
$days = max(1, min($days, 365));
$limit = isset($_GET['limit']) ? intval($_GET['limit']) : 10;
$limit = max(1, min($limit, 100));

$conn = getConnection();

$sql = "SELECT 
    n.id,
    n.title,
    n.author,
    n.publish_date,
    COUNT(vl.id) as view_count
FROM view_logs vl
INNER JOIN notices n ON vl.notice_id = n.id
WHERE vl.created_at >= DATE_SUB(CURDATE(), INTERVAL ? DAY)
GROUP BY vl.notice_id, n.id, n.title, n.author, n.publish_date
ORDER BY view_count DESC
LIMIT ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $days, $limit);
$stmt->execute();
$result = $stmt->get_result();

$notices = [];
while ($row = $result->fetch_assoc()) {
    $notices[] = [
        'id' => intval($row['id']),
        'title' => $row['title'],
        'author' => $row['author'],
        'publish_date' => $row['publish_date'],
        'view_count' => intval($row['view_count'])
    ];
}

$stmt->close();
closeConnection($conn);

json_response(200, '获取成功', $notices);
?>