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
    
    // 获取变更前的权限列表（必须在删除前获取）
    $before_perms_stmt = $conn->prepare("SELECT permission_id FROM role_permissions WHERE role_id = ?");
    $before_perms_stmt->bind_param("i", $role_id);
    $before_perms_stmt->execute();
    $before_perms_result = $before_perms_stmt->get_result();
    $before_permission_ids = [];
    while ($row = $before_perms_result->fetch_assoc()) {
        $before_permission_ids[] = $row['permission_id'];
    }
    $before_perms_stmt->close();
    
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
    
    // 获取变更后的权限列表
    $after_perms_stmt = $conn->prepare("SELECT permission_id FROM role_permissions WHERE role_id = ?");
    $after_perms_stmt->bind_param("i", $role_id);
    $after_perms_stmt->execute();
    $after_perms_result = $after_perms_stmt->get_result();
    $after_permission_ids = [];
    while ($row = $after_perms_result->fetch_assoc()) {
        $after_permission_ids[] = $row['permission_id'];
    }
    $after_perms_stmt->close();
    
    closeConnection($conn);
    
    write_operation_log('assign_permission', 'role_permission', $role_id, [
        'role_id' => $role_id,
        'permission_ids' => $before_permission_ids
    ], [
        'role_id' => $role_id,
        'permission_ids' => $after_permission_ids
    ]);
    
    json_response(200, '权限保存成功');
} catch (Exception $e) {
    if (isset($conn) && $conn->connect_errno === 0) {
        $conn->rollback();
    }
    json_response(500, '服务器错误: ' . $e->getMessage());
}
?>
