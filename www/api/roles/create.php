<?php
require_once '../../common.php';

header('Content-Type: application/json; charset=utf-8');

require_login();
require_permission('role:create');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(405, '方法不允许');
}

$data = json_decode(file_get_contents('php://input'), true);

$name = trim($data['name'] ?? '');
$display_name = trim($data['display_name'] ?? '');
$description = trim($data['description'] ?? '');

if (empty($name)) {
    json_response(400, '角色标识不能为空');
}

if (empty($display_name)) {
    json_response(400, '角色名称不能为空');
}

if (!preg_match('/^[a-z_]+$/', $name)) {
    json_response(400, '角色标识只能包含小写字母和下划线');
}

try {
    $conn = getConnection();
    
    $stmt = $conn->prepare("SELECT id FROM roles WHERE name = ?");
    $stmt->bind_param("s", $name);
    $stmt->execute();
    if ($stmt->get_result()->fetch_assoc()) {
        $stmt->close();
        closeConnection($conn);
        json_response(400, '角色标识已存在');
    }
    $stmt->close();
    
    $stmt = $conn->prepare("INSERT INTO roles (name, display_name, description) VALUES (?, ?, ?)");
    $stmt->bind_param("sss", $name, $display_name, $description);
    
    if ($stmt->execute()) {
        $role_id = $conn->insert_id;
        $stmt->close();
        closeConnection($conn);
        json_response(200, '创建成功', ['id' => $role_id]);
    } else {
        $stmt->close();
        closeConnection($conn);
        json_response(500, '创建失败');
    }
} catch (Exception $e) {
    json_response(500, '服务器错误: ' . $e->getMessage());
}
?>
