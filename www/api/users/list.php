<?php
require_once '../../common.php';

header('Content-Type: application/json; charset=utf-8');

require_login();
require_permission('user:view');

try {
    $conn = getConnection();
    
    $page = isset($_GET['page']) ? intval($_GET['page']) : 1;
    $per_page = isset($_GET['per_page']) ? intval($_GET['per_page']) : 20;
    $offset = ($page - 1) * $per_page;
    $search = isset($_GET['search']) ? trim($_GET['search']) : '';
    
    $where_sql = '';
    $params = [];
    $types = '';
    
    if (!empty($search)) {
        $where_sql = "WHERE u.username LIKE ? OR u.nickname LIKE ? OR u.email LIKE ?";
        $search_term = "%$search%";
        $params = [$search_term, $search_term, $search_term];
        $types = 'sss';
    }
    
    $count_sql = "SELECT COUNT(*) as total FROM users u $where_sql";
    $stmt = $conn->prepare($count_sql);
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $total = $stmt->get_result()->fetch_assoc()['total'];
    $stmt->close();
    
    $sql = "SELECT u.id, u.username, u.nickname, u.email, u.avatar_url, u.status, u.register_time, u.last_login_time
            FROM users u
            $where_sql
            ORDER BY u.id DESC
            LIMIT ? OFFSET ?";
    
    $stmt = $conn->prepare($sql);
    $params[] = $per_page;
    $params[] = $offset;
    $types .= 'ii';
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    
    $users = [];
    while ($row = $result->fetch_assoc()) {
        $user_id = $row['id'];
        
        $role_stmt = $conn->prepare("SELECT r.* FROM roles r 
                                     INNER JOIN user_roles ur ON r.id = ur.role_id 
                                     WHERE ur.user_id = ?
                                     ORDER BY r.id ASC");
        $role_stmt->bind_param("i", $user_id);
        $role_stmt->execute();
        $roles = [];
        $role_result = $role_stmt->get_result();
        while ($role_row = $role_result->fetch_assoc()) {
            $roles[] = $role_row;
        }
        $role_stmt->close();
        
        $row['roles'] = $roles;
        $row['role_ids'] = array_column($roles, 'id');
        $row['role_names'] = array_column($roles, 'display_name');
        
        $users[] = $row;
    }
    $stmt->close();
    
    closeConnection($conn);
    
    json_response(200, '获取成功', [
        'list' => $users,
        'pagination' => [
            'page' => $page,
            'per_page' => $per_page,
            'total' => $total,
            'total_pages' => ceil($total / $per_page)
        ]
    ]);
} catch (Exception $e) {
    json_response(500, '服务器错误: ' . $e->getMessage());
}
?>
