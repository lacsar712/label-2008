<?php
header('Content-Type: application/json; charset=utf-8');
require_once '../../config.php';
require_once '../../common.php';

require_login();

$user_id = $_SESSION['user_id'];

$conn = getConnection();

$stmt = $conn->prepare("SELECT type, COUNT(*) as count, SUM(CASE WHEN is_read = 0 THEN 1 ELSE 0 END) as unread_count FROM messages WHERE receiver_id = ? GROUP BY type");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

$types = [];
$total_count = 0;
$total_unread = 0;
while ($row = $result->fetch_assoc()) {
    $row['count'] = intval($row['count']);
    $row['unread_count'] = intval($row['unread_count']);
    $total_count += $row['count'];
    $total_unread += $row['unread_count'];
    $types[] = $row;
}

$stmt->close();
closeConnection($conn);

json_response(200, '获取成功', [
    'types' => $types,
    'total' => [
        'count' => $total_count,
        'unread_count' => $total_unread
    ]
]);
?>
