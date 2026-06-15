<?php
header('Content-Type: application/json; charset=utf-8');
require_once '../../config.php';
require_once '../../common.php';

require_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(400, '请求方法不允许');
}

if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
    json_response(400, '请上传有效的文件');
}

$file = $_FILES['file'];
$ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

if (!in_array($ext, ['csv', 'xls', 'xlsx'])) {
    json_response(400, '仅支持 CSV 和 Excel 格式文件');
}

if ($file['size'] > 5 * 1024 * 1024) {
    json_response(400, '文件大小不能超过5MB');
}

$required_headers = ['公告标题', '公告内容', '发布人', '分类名称', '优先级', '状态'];
$parsed_rows = [];

if ($ext === 'csv') {
    $handle = fopen($file['tmp_name'], 'r');
    if (!$handle) {
        json_response(500, '无法读取上传文件');
    }

    $bom = fread($handle, 3);
    if ($bom !== "\xEF\xBB\xBF") {
        rewind($handle);
    }

    $header = fgetcsv($handle);
    if (!$header) {
        fclose($handle);
        json_response(400, '文件为空或无法解析表头');
    }

    $header = array_map('trim', $header);
    $missing = array_diff($required_headers, $header);
    if (!empty($missing)) {
        fclose($handle);
        json_response(400, '缺少必要列：' . implode('、', $missing));
    }

    $header_indices = [];
    foreach ($required_headers as $rh) {
        $idx = array_search($rh, $header);
        if ($idx !== false) {
            $header_indices[$rh] = $idx;
        }
    }

    $row_num = 1;
    while (($data = fgetcsv($handle)) !== false) {
        $row_num++;
        $row_data = [];
        foreach ($required_headers as $rh) {
            if (isset($header_indices[$rh]) && isset($data[$header_indices[$rh]])) {
                $row_data[$rh] = trim($data[$header_indices[$rh]]);
            } else {
                $row_data[$rh] = '';
            }
        }
        $parsed_rows[] = $row_data;
    }
    fclose($handle);
} else {
    if (!class_exists('ZipArchive')) {
        json_response(500, '服务器缺少 ZipArchive 扩展，无法解析 Excel 文件，请使用 CSV 格式');
    }

    $zip = new ZipArchive();
    if ($zip->open($file['tmp_name']) !== true) {
        json_response(400, '无法打开 Excel 文件，请检查文件是否损坏');
    }

    $shared_strings = [];
    $ss_entry = $zip->getFromName('xl/sharedStrings.xml');
    if ($ss_entry) {
        $ss_xml = simplexml_load_string($ss_entry);
        if ($ss_xml) {
            foreach ($ss_xml->si as $si) {
                $t = '';
                if (isset($si->t)) {
                    $t = (string) $si->t;
                } elseif (isset($si->r)) {
                    foreach ($si->r as $run) {
                        if (isset($run->t)) {
                            $t .= (string) $run->t;
                        }
                    }
                }
                $shared_strings[] = $t;
            }
        }
    }

    $sheet_entry = $zip->getFromName('xl/worksheets/sheet1.xml');
    $zip->close();

    if (!$sheet_entry) {
        json_response(400, '无法读取 Excel 工作表');
    }

    $sheet_xml = simplexml_load_string($sheet_entry);
    if (!$sheet_xml) {
        json_response(400, '无法解析 Excel 工作表');
    }

    $ns = $sheet_xml->getNamespaces(true);
    $sheet_ns = isset($ns['']) ? $ns[''] : '';

    $all_rows = [];
    foreach ($sheet_xml->sheetData->row as $row) {
        $cells = [];
        foreach ($row->c as $c) {
            $attrs = $c->attributes();
            $ref = (string) $attrs['r'];
            $col = preg_replace('/[0-9]+/', '', $ref);
            $col_idx = columnLetterToIndex($col);
            $type = isset($attrs['t']) ? (string) $attrs['t'] : '';
            $val = (string) $c->v;

            if ($type === 's' && isset($shared_strings[intval($val)])) {
                $val = $shared_strings[intval($val)];
            }
            $cells[$col_idx] = $val;
        }
        ksort($cells);
        $all_rows[] = array_values($cells);
    }

    if (empty($all_rows)) {
        json_response(400, 'Excel 文件为空');
    }

    $header = array_map('trim', $all_rows[0]);
    $missing = array_diff($required_headers, $header);
    if (!empty($missing)) {
        json_response(400, '缺少必要列：' . implode('、', $missing));
    }

    $header_indices = [];
    foreach ($required_headers as $rh) {
        $idx = array_search($rh, $header);
        if ($idx !== false) {
            $header_indices[$rh] = $idx;
        }
    }

    for ($i = 1; $i < count($all_rows); $i++) {
        $data = $all_rows[$i];
        $row_data = [];
        foreach ($required_headers as $rh) {
            if (isset($header_indices[$rh]) && isset($data[$header_indices[$rh]])) {
                $row_data[$rh] = trim($data[$header_indices[$rh]]);
            } else {
                $row_data[$rh] = '';
            }
        }
        $parsed_rows[] = $row_data;
    }
}

if (empty($parsed_rows)) {
    json_response(400, '文件中没有数据行');
}

$conn = getConnection();
$categories_result = $conn->query("SELECT id, name FROM categories WHERE status = 'enabled'");
$category_map = [];
while ($cat = $categories_result->fetch_assoc()) {
    $category_map[$cat['name']] = $cat['id'];
}
closeConnection($conn);

$valid_priorities = ['高', '中', '低'];
$priority_map = ['高' => 'high', '中' => 'medium', '低' => 'low'];
$valid_statuses = ['已发布', '草稿'];
$status_map_val = ['已发布' => 'published', '草稿' => 'draft'];

$preview = [];
foreach ($parsed_rows as $idx => $row) {
    $errors = [];

    if (empty($row['公告标题'])) {
        $errors[] = '公告标题不能为空';
    }
    if (empty($row['公告内容'])) {
        $errors[] = '公告内容不能为空';
    }
    if (empty($row['发布人'])) {
        $errors[] = '发布人不能为空';
    }
    if (!empty($row['分类名称']) && !isset($category_map[$row['分类名称']])) {
        $errors[] = '分类"' . $row['分类名称'] . '"不存在或未启用';
    }
    if (!empty($row['优先级']) && !in_array($row['优先级'], $valid_priorities)) {
        $errors[] = '优先级必须为：高、中、低';
    }
    if (!empty($row['状态']) && !in_array($row['状态'], $valid_statuses)) {
        $errors[] = '状态必须为：已发布、草稿';
    }

    $preview[] = [
        'row_index' => $idx + 1,
        'data' => $row,
        'valid' => empty($errors),
        'errors' => $errors,
    ];
}

$upload_key = bin2hex(random_bytes(16));
$tmp_path = sys_get_temp_dir() . '/notice_import_' . $upload_key . '.json';
file_put_contents($tmp_path, json_encode($parsed_rows, JSON_UNESCAPED_UNICODE));

json_response(200, '文件解析成功', [
    'upload_key' => $upload_key,
    'total_rows' => count($parsed_rows),
    'valid_rows' => count(array_filter($preview, function ($p) { return $p['valid']; })),
    'invalid_rows' => count(array_filter($preview, function ($p) { return !$p['valid']; })),
    'preview' => array_slice($preview, 0, 10),
]);

function columnLetterToIndex($letter)
{
    $index = 0;
    $len = strlen($letter);
    for ($i = 0; $i < $len; $i++) {
        $index = $index * 26 + (ord($letter[$i]) - ord('A') + 1);
    }
    return $index - 1;
}
