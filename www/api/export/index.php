<?php
require_once '../../config.php';
require_once '../../common.php';

require_login();

$format = isset($_GET['format']) ? sanitize($_GET['format']) : 'csv';
$fields_param = isset($_GET['fields']) ? $_GET['fields'] : '';
$priority = isset($_GET['priority']) ? sanitize($_GET['priority']) : '';
$status = isset($_GET['status']) ? sanitize($_GET['status']) : '';
$category_id = isset($_GET['category_id']) ? intval($_GET['category_id']) : 0;
$date_from = isset($_GET['date_from']) ? sanitize($_GET['date_from']) : '';
$date_to = isset($_GET['date_to']) ? sanitize($_GET['date_to']) : '';

$all_fields = [
    'id' => 'ID',
    'title' => '公告标题',
    'content' => '公告内容',
    'author' => '发布人',
    'category_name' => '分类',
    'priority' => '优先级',
    'status' => '状态',
    'publish_date' => '发布时间',
    'update_date' => '更新时间',
    'views' => '浏览次数',
];

if (!empty($fields_param)) {
    $selected_fields = array_filter(explode(',', $fields_param), function ($f) {
        return array_key_exists($f, $GLOBALS['all_fields']);
    });
    if (empty($selected_fields)) {
        $selected_fields = array_keys($all_fields);
    }
} else {
    $selected_fields = array_keys($all_fields);
}

$conn = getConnection();

$where_clauses = [];
$params = [];
$types = '';

if (!empty($priority) && in_array($priority, ['high', 'medium', 'low'])) {
    $where_clauses[] = "n.priority = ?";
    $params[] = $priority;
    $types .= 's';
}

if (!empty($status) && in_array($status, ['published', 'draft'])) {
    $where_clauses[] = "n.status = ?";
    $params[] = $status;
    $types .= 's';
}

if ($category_id > 0) {
    $where_clauses[] = "n.category_id = ?";
    $params[] = $category_id;
    $types .= 'i';
}

if (!empty($date_from)) {
    $where_clauses[] = "n.publish_date >= ?";
    $params[] = $date_from . ' 00:00:00';
    $types .= 's';
}

if (!empty($date_to)) {
    $where_clauses[] = "n.publish_date <= ?";
    $params[] = $date_to . ' 23:59:59';
    $types .= 's';
}

$where_sql = !empty($where_clauses) ? "WHERE " . implode(" AND ", $where_clauses) : "";

$sql = "SELECT n.*, c.name as category_name FROM notices n LEFT JOIN categories c ON n.category_id = c.id $where_sql ORDER BY n.publish_date DESC";

if (!empty($params)) {
    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
} else {
    $result = $conn->query($sql);
}

$rows = [];
while ($row = $result->fetch_assoc()) {
    $rows[] = $row;
}

if (isset($stmt)) {
    $stmt->close();
}
closeConnection($conn);

$priority_map = ['high' => '高', 'medium' => '中', 'low' => '低'];
$status_map = ['published' => '已发布', 'draft' => '草稿'];

$output_rows = [];
foreach ($rows as $row) {
    $out = [];
    foreach ($selected_fields as $field) {
        $val = $row[$field] ?? '';
        if ($field === 'priority') {
            $val = $priority_map[$val] ?? $val;
        } elseif ($field === 'status') {
            $val = $status_map[$val] ?? $val;
        }
        $out[$field] = $val;
    }
    $output_rows[] = $out;
}

$headers = [];
foreach ($selected_fields as $field) {
    $headers[] = $all_fields[$field];
}

if ($format === 'excel') {
    header('Content-Type: application/vnd.ms-excel; charset=utf-8');
    header('Content-Disposition: attachment; filename="notices_export_' . date('Ymd_His') . '.xls"');
    header('Cache-Control: max-age=0');

    echo "\xEF\xBB\xBF";

    echo '<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns="http://www.w3.org/TR/REC-html40">';
    echo '<head><meta http-equiv="Content-Type" content="text/html; charset=utf-8"></head>';
    echo '<body><table border="1">';

    echo '<tr>';
    foreach ($headers as $h) {
        echo '<th style="background-color:#6366f1;color:#ffffff;font-weight:bold;padding:8px;">' . htmlspecialchars($h) . '</th>';
    }
    echo '</tr>';

    foreach ($output_rows as $row) {
        echo '<tr>';
        foreach ($selected_fields as $field) {
            echo '<td style="padding:6px;">' . htmlspecialchars($row[$field]) . '</td>';
        }
        echo '</tr>';
    }

    echo '</table></body></html>';
} else {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="notices_export_' . date('Ymd_His') . '.csv"');
    header('Cache-Control: max-age=0');

    $output = fopen('php://output', 'w');
    fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF));

    fputcsv($output, $headers);

    foreach ($output_rows as $row) {
        $line = [];
        foreach ($selected_fields as $field) {
            $line[] = $row[$field];
        }
        fputcsv($output, $line);
    }

    fclose($output);
}
exit();
