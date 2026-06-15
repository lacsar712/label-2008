<?php
header('Content-Type: application/json; charset=utf-8');
require_once '../../config.php';
require_once '../../common.php';

$notice_id = isset($_GET['notice_id']) ? intval($_GET['notice_id']) : 0;

if ($notice_id <= 0) {
    json_response(400, '参数错误');
}

$conn = getConnection();

$notice_stmt = $conn->prepare("SELECT id FROM notices WHERE id = ?");
$notice_stmt->bind_param("i", $notice_id);
$notice_stmt->execute();
$notice_result = $notice_stmt->get_result();
if (!$notice_result->fetch_assoc()) {
    $notice_stmt->close();
    closeConnection($conn);
    json_response(404, '公告不存在');
}
$notice_stmt->close();

$sql = "SELECT t.* FROM tags t
        INNER JOIN notice_tags nt ON t.id = nt.tag_id
        WHERE nt.notice_id = ?
        ORDER BY t.reference_count DESC, t.id ASC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $notice_id);
$stmt->execute();
$result = $stmt->get_result();

$tags = [];
while ($row = $result->fetch_assoc()) {
    $tags[] = $row;
}

$stmt->close();
closeConnection($conn);

json_response(200, '获取成功', $tags);
?>
