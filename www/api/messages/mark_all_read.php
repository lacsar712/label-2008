<?php
header('Content-Type: application/json; charset=utf-8');
require_once '../../config.php';
require_once '../../common.php';

require_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(400, '请求方法错误');
}

$user_id = $_SESSION['user_id'];

$conn = getConnection();

$stmt = $conn->prepare("UPDATE messages SET is_read = 1 WHERE receiver_id = ? AND is_read = 0");
$stmt->bind_param("i", $user_id);

if ($stmt->execute()) {
    $affected = $conn->affected_rows;
    $stmt->close();
    closeConnection($conn);
    json_response(200, '全部标记已读成功', ['affected' => $affected]);
} else {
    $error = $conn->error;
    $stmt->close();
    closeConnection($conn);
    json_response(500, '标记失败: ' . $error);
}
?>
