<?php
require_once '../../config.php';
require_once '../../common.php';

require_login();

$format = isset($_GET['format']) ? sanitize($_GET['format']) : 'csv';

$headers = ['公告标题', '公告内容', '发布人', '分类名称', '优先级', '状态'];
$example_row = ['示例公告标题', '示例公告内容', '张三', '系统公告', '中', '已发布'];

if ($format === 'excel') {
    header('Content-Type: application/vnd.ms-excel; charset=utf-8');
    header('Content-Disposition: attachment; filename="notice_import_template.xls"');
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

    echo '<tr>';
    foreach ($example_row as $val) {
        echo '<td style="padding:6px;">' . htmlspecialchars($val) . '</td>';
    }
    echo '</tr>';

    echo '</table></body></html>';
} else {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="notice_import_template.csv"');
    header('Cache-Control: max-age=0');

    $output = fopen('php://output', 'w');
    fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF));

    fputcsv($output, $headers);
    fputcsv($output, $example_row);

    fclose($output);
}
exit();
