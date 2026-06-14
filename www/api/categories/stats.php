<?php
header('Content-Type: application/json; charset=utf-8');
require_once '../../config.php';
require_once '../../common.php';

$conn = getConnection();

$sql = "SELECT 
            c.id, 
            c.name, 
            c.emoji, 
            c.color, 
            c.description, 
            c.status,
            c.sort_order,
            COUNT(n.id) as notice_count 
        FROM categories c 
        LEFT JOIN notices n ON c.id = n.category_id 
        WHERE c.status = 'enabled'
        GROUP BY c.id, c.name, c.emoji, c.color, c.description, c.status, c.sort_order 
        ORDER BY c.sort_order ASC, c.id ASC";

$result = $conn->query($sql);

$stats = [];
while ($row = $result->fetch_assoc()) {
    $stats[] = $row;
}

closeConnection($conn);

json_response(200, '获取成功', $stats);
?>