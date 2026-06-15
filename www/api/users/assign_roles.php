<?php
require_once '../../common.php';

header('Content-Type: application/json; charset=utf-8');

require_login();
require_permission('user:assign_role');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(405, '方法不允许');
}

$data = json_decode(file_get_contents('php://input'), true);

$user_id = intval($data['user_id'] ?? 0);
$role_ids = $data['role_ids'] ?? [];

if ($user_id <= 0) {
    json_response(400, '用户ID无效');
}

if (!is_array($role_ids)) {
    json_response(400, '角色ID格式错误');
}

try {
    $conn = getConnection();
    
    $stmt = $conn->prepare("SELECT id FROM users WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    
    if (!$user) {
        closeConnection($conn);
        json_response(404, '用户不存在');
    }
    
    $conn->begin_transaction();
    
    // 获取变更前的角色列表（必须在删除前获取）
    $before_roles_stmt = $conn->prepare("SELECT role_id FROM user_roles WHERE user_id = ?");
    $before_roles_stmt->bind_param("i", $user_id);
    $before_roles_stmt->execute();
    $before_roles_result = $before_roles_stmt->get_result();
    $before_role_ids = [];
    while ($row = $before_roles_result->fetch_assoc()) {
        $before_role_ids[] = $row['role_id'];
    }
    $before_roles_stmt->close();
    
    $stmt = $conn->prepare("DELETE FROM user_roles WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $stmt->close();
    
    if (!empty($role_ids)) {
        $placeholders = implode(',', array_fill(0, count($role_ids), '?'));
        $types = str_repeat('i', count($role_ids));
        
        $stmt = $conn->prepare("SELECT id FROM roles WHERE id IN ($placeholders)");
        $stmt->bind_param($types, ...$role_ids);
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
            foreach ($valid_ids as $rid) {
                $insert_values[] = "(?, ?)";
                $insert_params[] = $user_id;
                $insert_params[] = $rid;
            }
            
            $insert_sql = "INSERT INTO user_roles (user_id, role_id) VALUES " . implode(',', $insert_values);
            $insert_types = str_repeat('ii', count($valid_ids));
            
            $stmt = $conn->prepare($insert_sql);
            $stmt->bind_param($insert_types, ...$insert_params);
            $stmt->execute();
            $stmt->close();
        }
    }
    
    $conn->commit();
    
    // 获取变更后的角色列表
    $after_roles_stmt = $conn->prepare("SELECT role_id FROM user_roles WHERE user_id = ?");
    $after_roles_stmt->bind_param("i", $user_id);
    $after_roles_stmt->execute();
    $after_roles_result = $after_roles_stmt->get_result();
    $after_role_ids = [];
    while ($row = $after_roles_result->fetch_assoc()) {
        $after_role_ids[] = $row['role_id'];
    }
    $after_roles_stmt->close();
    
    closeConnection($conn);
    
    write_operation_log('assign_role', 'user_role', $user_id, [
        'user_id' => $user_id,
        'role_ids' => $before_role_ids
    ], [
        'user_id' => $user_id,
        'role_ids' => $after_role_ids
    ]);
    
    json_response(200, '角色分配成功');
} catch (Exception $e) {
    if (isset($conn) && $conn->connect_errno === 0) {
        $conn->rollback();
    }
    json_response(500, '服务器错误: ' . $e->getMessage());
}
?>
