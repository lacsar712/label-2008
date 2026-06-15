<?php
require_once '../../common.php';

header('Content-Type: application/json; charset=utf-8');

require_login();

$q = isset($_GET['q']) ? sanitize($_GET['q']) : '';
$limit = isset($_GET['limit']) ? intval($_GET['limit']) : 20;
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;

if (empty($q)) {
    json_response(400, '搜索关键词不能为空');
}

$limit = max(1, min($limit, 100));
$page = max(1, $page);
$offset = ($page - 1) * $limit;

$conn = getConnection();

$sql = "SELECT id, title, author, publish_date, status FROM notices WHERE title LIKE ? ORDER BY publish_date DESC LIMIT ? OFFSET ?";
$stmt = $conn->prepare($sql);
$search_param = "%$q%";
$stmt->bind_param("sii", $search_param, $limit, $offset);
$stmt->execute();
$result = $stmt->get_result();

$notices = [];
while ($row = $result->fetch_assoc()) {
    $notices[] = [
        'id' => intval($row['id']),
        'title' => $row['title'],
        'author' => $row['author'],
        'publish_date' => $row['publish_date'],
        'status' => $row['status']
    ];
}
$stmt->close();

$count_sql = "SELECT COUNT(*) as total FROM notices WHERE title LIKE ?";
$stmt = $conn->prepare($count_sql);
$stmt->bind_param("s", $search_param);
$stmt->execute();
$count_result = $stmt->get_result();
$total = intval($count_result->fetch_assoc()['total']);
$stmt->close();

closeConnection($conn);

$data = [
    'list' => $notices,
    'total' => $total,
    'page' => $page,
    'limit' => $limit
];

json_response(200, '搜索成功', $data);
?>
