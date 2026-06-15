<?php
require_once 'common.php';
require_login();
header('Content-Type: text/html; charset=UTF-8');

$conn = getConnection();
$category_result = $conn->query("SELECT id, name FROM categories WHERE status = 'enabled' ORDER BY sort_order ASC, id ASC");
$categories = [];
while ($cat = $category_result->fetch_assoc()) {
    $categories[] = $cat;
}
closeConnection($conn);
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>数据导入导出 - 公告信息管理系统</title>
    <link rel="stylesheet" href="style.css">
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+SC:wght@300;400;500;700&display=swap" rel="stylesheet">
</head>
<body>
    <?php include 'header.php'; ?>

    <div class="container">
        <div class="main-content">
            <div class="page-header-bar">
                <h1 class="page-title">数据导入导出</h1>
                <a href="search_notice.php" class="btn btn-secondary" style="display: inline-flex; align-items: center; gap: 6px;">
                    <svg class="btn-icon" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" style="width: 18px; height: 18px;">
                        <path d="M21 21L16.65 16.65M11 19C6.58172 19 3 15.4183 3 11C3 6.58172 6.58172 3 11 3C15.4183 3 19 6.58172 19 11C19 15.4183 15.4183 19 11 19Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                    前往公告列表
                </a>
            </div>
            <div class="import-export-layout">
                <div class="import-export-panel export-panel">
                    <div class="panel-header">
                        <svg class="panel-icon" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M21 15V19C21 19.5304 20.7893 20.0391 20.4142 20.4142C20.0391 20.7893 19.5304 21 19 21H5C4.46957 21 3.96086 20.7893 3.58579 20.4142C3.21071 20.0391 3 19.5304 3 19V15" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            <path d="M7 10L12 15L17 10" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            <path d="M12 15V3" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                        <h2>导出公告数据</h2>
                        <p>选择字段和筛选条件后导出为 CSV 或 Excel 格式</p>
                    </div>

                    <div class="panel-body">
                        <div class="form-group">
                            <label>导出字段</label>
                            <div class="field-checkbox-group" id="exportFields">
                                <label class="checkbox-item">
                                    <input type="checkbox" name="export_field" value="id" checked> ID
                                </label>
                                <label class="checkbox-item">
                                    <input type="checkbox" name="export_field" value="title" checked> 公告标题
                                </label>
                                <label class="checkbox-item">
                                    <input type="checkbox" name="export_field" value="content" checked> 公告内容
                                </label>
                                <label class="checkbox-item">
                                    <input type="checkbox" name="export_field" value="author" checked> 发布人
                                </label>
                                <label class="checkbox-item">
                                    <input type="checkbox" name="export_field" value="category_name" checked> 分类
                                </label>
                                <label class="checkbox-item">
                                    <input type="checkbox" name="export_field" value="priority" checked> 优先级
                                </label>
                                <label class="checkbox-item">
                                    <input type="checkbox" name="export_field" value="status" checked> 状态
                                </label>
                                <label class="checkbox-item">
                                    <input type="checkbox" name="export_field" value="publish_date" checked> 发布时间
                                </label>
                                <label class="checkbox-item">
                                    <input type="checkbox" name="export_field" value="update_date"> 更新时间
                                </label>
                                <label class="checkbox-item">
                                    <input type="checkbox" name="export_field" value="views"> 浏览次数
                                </label>
                            </div>
                            <div class="checkbox-actions">
                                <button type="button" class="btn-link" onclick="toggleAllFields(true)">全选</button>
                                <button type="button" class="btn-link" onclick="toggleAllFields(false)">全不选</button>
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="exportPriority">优先级</label>
                                <select id="exportPriority">
                                    <option value="">全部</option>
                                    <option value="high">高</option>
                                    <option value="medium">中</option>
                                    <option value="low">低</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="exportStatus">状态</label>
                                <select id="exportStatus">
                                    <option value="">全部</option>
                                    <option value="published">已发布</option>
                                    <option value="draft">草稿</option>
                                </select>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="exportCategory">分类</label>
                            <select id="exportCategory">
                                <option value="">全部分类</option>
                                <?php foreach ($categories as $cat): ?>
                                    <option value="<?php echo $cat['id']; ?>"><?php echo htmlspecialchars($cat['name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="exportDateFrom">开始日期</label>
                                <input type="date" id="exportDateFrom">
                            </div>
                            <div class="form-group">
                                <label for="exportDateTo">结束日期</label>
                                <input type="date" id="exportDateTo">
                            </div>
                        </div>

                        <div class="export-actions">
                            <button type="button" class="btn btn-primary" onclick="doExport('csv')" data-permission="notice:export">
                                <svg class="btn-icon" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M21 15V19C21 19.5304 20.7893 20.0391 20.4142 20.4142C20.0391 20.7893 19.5304 21 19 21H5C4.46957 21 3.96086 20.7893 3.58579 20.4142C3.21071 20.0391 3 19.5304 3 19V15" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                    <path d="M7 10L12 15L17 10" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                    <path d="M12 15V3" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                </svg>
                                导出 CSV
                            </button>
                            <button type="button" class="btn btn-secondary" onclick="doExport('excel')" data-permission="notice:export">
                                <svg class="btn-icon" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M21 15V19C21 19.5304 20.7893 20.0391 20.4142 20.4142C20.0391 20.7893 19.5304 21 19 21H5C4.46957 21 3.96086 20.7893 3.58579 20.4142C3.21071 20.0391 3 19.5304 3 19V15" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                    <path d="M7 10L12 15L17 10" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                    <path d="M12 15V3" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                </svg>
                                导出 Excel
                            </button>
                        </div>
                    </div>
                </div>

                <div class="import-export-panel import-panel">
                    <div class="panel-header">
                        <svg class="panel-icon" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M21 15V19C21 19.5304 20.7893 20.0391 20.4142 20.4142C20.0391 20.7893 19.5304 21 19 21H5C4.46957 21 3.96086 20.7893 3.58579 20.4142C3.21071 20.0391 3 19.5304 3 19V15" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            <path d="M17 8L12 3L7 8" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            <path d="M12 3V15" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                        <h2>导入公告数据</h2>
                        <p>上传 CSV/Excel 文件，预览确认后批量导入</p>
                    </div>

                    <div class="panel-body">
                        <div class="template-download">
                            <svg class="template-icon" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M19 21H5C4.46957 21 3.96086 20.7893 3.58579 20.4142C3.21071 20.0391 3 19.5304 3 19V5C3 4.46957 3.21071 3.96086 3.58579 3.58579C3.96086 3.21071 4.46957 3 5 3H16L21 8V19C21 19.5304 20.7893 20.0391 20.4142 20.4142C20.0391 20.7893 19.5304 21 19 21Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                <path d="M17 3V8H22" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                            <span>下载导入模板：</span>
                            <a href="api/export/template?format=csv" class="btn-link">CSV 模板</a>
                            <span class="divider">|</span>
                            <a href="api/export/template?format=excel" class="btn-link">Excel 模板</a>
                        </div>

                        <div class="upload-area" id="uploadArea" data-permission="notice:import">
                            <input type="file" id="importFile" accept=".csv,.xls,.xlsx" style="display:none;">
                            <div class="upload-placeholder" id="uploadPlaceholder">
                                <svg class="upload-icon" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M21 15V19C21 19.5304 20.7893 20.0391 20.4142 20.4142C20.0391 20.7893 19.5304 21 19 21H5C4.46957 21 3.96086 20.7893 3.58579 20.4142C3.21071 20.0391 3 19.5304 3 19V15" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                    <path d="M17 8L12 3L7 8" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                    <path d="M12 3V15" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                </svg>
                                <p>将文件拖拽到此处，或 <span class="upload-browse">点击选择文件</span></p>
                                <small>支持 CSV、XLS、XLSX 格式，最大 5MB</small>
                            </div>
                            <div class="upload-progress" id="uploadProgress" style="display:none;">
                                <span class="spinner"></span>
                                <span>正在解析文件...</span>
                            </div>
                            <div class="upload-file-info" id="uploadFileInfo" style="display:none;">
                                <svg class="file-icon" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M19 21H5C4.46957 21 3.96086 20.7893 3.58579 20.4142C3.21071 20.0391 3 19.5304 3 19V5C3 4.46957 3.21071 3.96086 3.58579 3.58579C3.96086 3.21071 4.46957 3 5 3H16L21 8V19C21 19.5304 20.7893 20.0391 20.4142 20.4142C20.0391 20.7893 19.5304 21 19 21Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                </svg>
                                <span id="uploadFileName"></span>
                                <button type="button" class="btn-link btn-remove-file" onclick="clearUpload()">移除</button>
                            </div>
                        </div>

                        <div class="preview-section" id="previewSection" style="display:none;">
                            <div class="preview-header">
                                <h4>数据预览 <small id="previewCount"></small></h4>
                            </div>
                            <div class="preview-table-container">
                                <table class="preview-table" id="previewTable">
                                    <thead>
                                        <tr>
                                            <th>行号</th>
                                            <th>公告标题</th>
                                            <th>发布人</th>
                                            <th>分类</th>
                                            <th>优先级</th>
                                            <th>状态</th>
                                            <th>校验</th>
                                        </tr>
                                    </thead>
                                    <tbody id="previewTableBody"></tbody>
                                </table>
                            </div>
                        </div>

                        <div class="import-actions" id="importActions" style="display:none;">
                            <button type="button" class="btn btn-primary" id="confirmImportBtn" onclick="confirmImport()">
                                <svg class="btn-icon" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M9 12L11 14L15 10M21 12C21 16.9706 16.9706 21 12 21C7.02944 21 3 16.9706 3 12C3 7.02944 7.02944 3 12 3C16.9706 3 21 7.02944 21 12Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                </svg>
                                确认导入
                            </button>
                        </div>

                        <div class="import-progress-section" id="importProgressSection" style="display:none;">
                            <div class="progress-bar-container">
                                <div class="progress-bar" id="importProgressBar" style="width: 0%"></div>
                            </div>
                            <p class="progress-text" id="importProgressText">正在导入...</p>
                        </div>

                        <div class="import-result-section" id="importResultSection" style="display:none;">
                            <div class="result-summary">
                                <div class="result-stat result-success">
                                    <span class="result-number" id="resultSuccessCount">0</span>
                                    <span class="result-label">成功</span>
                                </div>
                                <div class="result-stat result-fail">
                                    <span class="result-number" id="resultFailCount">0</span>
                                    <span class="result-label">失败</span>
                                </div>
                            </div>
                            <div class="result-detail" id="resultDetail" style="display:none;">
                                <h4>错误明细</h4>
                                <div class="result-table-container">
                                    <table class="result-table">
                                        <thead>
                                            <tr>
                                                <th>行号</th>
                                                <th>标题</th>
                                                <th>错误信息</th>
                                            </tr>
                                        </thead>
                                        <tbody id="resultTableBody"></tbody>
                                    </table>
                                </div>
                            </div>
                            <button type="button" class="btn btn-secondary" onclick="resetImport()" style="margin-top: var(--spacing-lg);">
                                重新导入
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <footer class="footer">
        <div class="container">
            <p>&copy; 2024 公告信息管理系统. All rights reserved.</p>
        </div>
    </footer>

    <script src="app.js"></script>
    <script src="data_import_export.js"></script>
</body>
</html>
