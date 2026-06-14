<?php
require_once '../../common.php';

header('Content-Type: application/json; charset=utf-8');

require_login();
require_permission('role:assign_permission');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(405, '方法不允许');
}

$data = json_decode(file_get_contents('php://input'), true);

$role_id = intval($data['role_id'] ?? 0);
$permission_ids = $data['permission_ids'] ?? [];

if ($role_id <= 0) {
    json_response(400, '角色ID无效');
}

if (!is_array($permission_ids)) {
    json_response(400, '权限ID格式错误');
}

try {
    $conn = getConnection();
    
    $stmt = $conn->prepare("SELECT id, name FROM roles WHERE id = ?");
    $stmt->bind_param("i", $role_id);
    $stmt->execute();
    $role = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    
    if (!$role) {
        closeConnection($conn);
        json_response(404, '角色不存在');
    }
    
    $conn->begin_transaction();
    
    $stmt = $conn->prepare("DELETE FROM role_permissions WHERE role_id = ?");
    $stmt->bind_param("i", $role_id);
    $stmt->execute();
    $stmt->close();
    
    if (!empty($permission_ids)) {
        $placeholders = implode(',', array_fill(0, count($permission_ids), '?'));
        $types = str_repeat('i', count($permission_ids));
        
        $stmt = $conn->prepare("SELECT id FROM permissions WHERE id IN ($placeholders)");
        $stmt->bind_param($types, ...$permission_ids);
        $stmt->execute();
        $result = $stmt->get_result();
        $valid_ids = [];
        while ($row = $result->fetch_assoc()) {
            $valid_ids[] = $row['id'];
        }
        $stmt->close();
        
        if (!empty($valid_ids)) {
            $insert_values = [];
            $insert_params = [];
            foreach ($valid_ids as $pid) {
                $insert_values[] = "(?, ?)";
                $insert_params[] = $role_id;
                $insert_params[] = $pid;
            }
            
            $insert_sql = "INSERT INTO role_permissions (role_id, permission_id) VALUES " . implode(',', $insert_values);
            $insert_types = str_repeat('ii', count($valid_ids));
            
            $stmt = $conn->prepare($insert_sql);
            $stmt->bind_param($insert_types, ...$insert_params);
            $stmt->execute();
            $stmt->close();
        }
    }
    
    $conn->commit();
    closeConnection($conn);
    
    json_response(200, '权限保存成功');
} catch (Exception $e) {
    if (isset($conn) && $conn->connect_errno === 0) {
        $conn->rollback();
    }
    json_response(500, '服务器错误: ' . $e->getMessage());
}
?>
