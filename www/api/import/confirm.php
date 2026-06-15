<?php
header('Content-Type: application/json; charset=utf-8');
require_once '../../config.php';
require_once '../../common.php';

require_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(400, '请求方法不允许');
}

$input = json_decode(file_get_contents('php://input'), true);
if (!$input || empty($input['upload_key'])) {
    json_response(400, '缺少上传标识');
}

$upload_key = $input['upload_key'];
$tmp_path = sys_get_temp_dir() . '/notice_import_' . $upload_key . '.json';

if (!file_exists($tmp_path)) {
    json_response(400, '上传数据已过期，请重新上传文件');
}

$rows = json_decode(file_get_contents($tmp_path), true);
if (!is_array($rows) || empty($rows)) {
    unlink($tmp_path);
    json_response(400, '无有效数据可导入');
}

$conn = getConnection();
$categories_result = $conn->query("SELECT id, name FROM categories WHERE status = 'enabled'");
$category_map = [];
while ($cat = $categories_result->fetch_assoc()) {
    $category_map[$cat['name']] = $cat['id'];
}

$priority_map = ['高' => 'high', '中' => 'medium', '低' => 'low'];
$status_map_val = ['已发布' => 'published', '草稿' => 'draft'];

$current_user = get_current_user();
$author_id = $current_user ? intval($current_user['id']) : null;

$results = [];
$success_count = 0;
$fail_count = 0;

$stmt = $conn->prepare("INSERT INTO notices (title, content, author, author_id, category_id, priority, status) VALUES (?, ?, ?, ?, ?, ?, ?)");

foreach ($rows as $idx => $row) {
    $row_index = $idx + 1;
    $errors = [];

    $title = $row['公告标题'] ?? '';
    $content = $row['公告内容'] ?? '';
    $author = $row['发布人'] ?? '';
    $category_name = $row['分类名称'] ?? '';
    $priority_label = $row['优先级'] ?? '中';
    $status_label = $row['状态'] ?? '已发布';

    if (empty($title)) {
        $errors[] = '公告标题不能为空';
    }
    if (empty($content)) {
        $errors[] = '公告内容不能为空';
    }
    if (empty($author)) {
        $errors[] = '发布人不能为空';
    }

    $category_id = null;
    if (!empty($category_name)) {
        if (isset($category_map[$category_name])) {
            $category_id = intval($category_map[$category_name]);
        } else {
            $errors[] = '分类"' . $category_name . '"不存在或未启用';
        }
    }

    $priority = $priority_map[$priority_label] ?? 'medium';
    if (!empty($priority_label) && !isset($priority_map[$priority_label])) {
        $errors[] = '优先级无效';
        $priority = 'medium';
    }

    $status = $status_map_val[$status_label] ?? 'published';
    if (!empty($status_label) && !isset($status_map_val[$status_label])) {
        $errors[] = '状态无效';
        $status = 'published';
    }

    if (!empty($errors)) {
        $results[] = [
            'row_index' => $row_index,
            'title' => $title,
            'success' => false,
            'errors' => $errors,
        ];
        $fail_count++;
        continue;
    }

    $stmt->bind_param("sssisis", $title, $content, $author, $author_id, $category_id, $priority, $status);
    if ($stmt->execute()) {
        $results[] = [
            'row_index' => $row_index,
            'title' => $title,
            'success' => true,
            'errors' => [],
        ];
        $success_count++;
    } else {
        $results[] = [
            'row_index' => $row_index,
            'title' => $title,
            'success' => false,
            'errors' => ['数据库插入失败：' . $conn->error],
        ];
        $fail_count++;
    }
}

$stmt->close();
closeConnection($conn);

unlink($tmp_path);

json_response(200, '导入完成', [
    'total' => count($rows),
    'success_count' => $success_count,
    'fail_count' => $fail_count,
    'results' => $results,
]);
