<?php
require_once '../../common.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(405, '请求方法不允许');
}

$input = json_decode(file_get_contents('php://input'), true);
$notice_id = isset($input['notice_id']) ? intval($input['notice_id']) : 0;

if ($notice_id <= 0) {
    json_response(400, '公告ID无效');
}

$conn = getConnection();

$stmt = $conn->prepare("SELECT id FROM notices WHERE id = ?");
$stmt->bind_param("i", $notice_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    $stmt->close();
    closeConnection($conn);
    json_response(404, '公告不存在');
}
$stmt->close();

$visitor_id = get_visitor_id();
$ip = get_client_ip();
$client_type = get_client_type();
$user_agent = isset($_SERVER['HTTP_USER_AGENT']) ? substr($_SERVER['HTTP_USER_AGENT'], 0, 500) : null;

$user = get_logged_in_user();
$nickname = $user ? ($user['nickname'] ?: $user['username']) : '匿名用户';

$stmt = $conn->prepare("SELECT id FROM notice_likes WHERE notice_id = ? AND visitor_id = ?");
$stmt->bind_param("is", $notice_id, $visitor_id);
$stmt->execute();
$result = $stmt->get_result();
$is_liked = $result->num_rows > 0;
$stmt->close();

if ($is_liked) {
    $stmt = $conn->prepare("DELETE FROM notice_likes WHERE notice_id = ? AND visitor_id = ?");
    $stmt->bind_param("is", $notice_id, $visitor_id);
    $stmt->execute();
    $stmt->close();
    
    closeConnection($conn);
    json_response(200, '取消点赞成功', ['liked' => false]);
} else {
    $stmt = $conn->prepare("INSERT INTO notice_likes (notice_id, visitor_id, nickname, ip, client_type, user_agent) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("isssss", $notice_id, $visitor_id, $nickname, $ip, $client_type, $user_agent);
    $result = $stmt->execute();
    $stmt->close();
    
    closeConnection($conn);
    
    if ($result) {
        json_response(200, '点赞成功', ['liked' => true]);
    } else {
        json_response(500, '点赞失败');
    }
}
?>
