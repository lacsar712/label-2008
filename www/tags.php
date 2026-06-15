<?php header('Content-Type: text/html; charset=UTF-8');
require_once 'common.php';
require_login();
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>标签管理 - 公告信息管理系统</title>
    <link rel="stylesheet" href="style.css">
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+SC:wght@300;400;500;700&display=swap" rel="stylesheet">
</head>
<body>
    <?php include 'header.php'; ?>

    <div class="container">
        <div class="main-content">
            <div class="page-header">
                <h2>标签管理</h2>
                <div class="page-actions">
                    <button class="btn btn-primary" onclick="openTagModal()" data-permission="tag:create">
                        <svg class="btn-icon" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M12 5V19M5 12H19" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                        新增标签
                    </button>
                </div>
            </div>

            <div class="tags-search">
                <div class="search-fields">
                    <div class="search-field">
                        <label>关键词</label>
                        <input type="text" id="tagKeyword" placeholder="搜索标签名称...">
                    </div>
                </div>
                <div class="search-actions">
                    <button class="btn btn-primary" onclick="loadTags()">
                        <svg class="btn-icon" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M21 21L15 15M17 10C17 13.866 13.866 17 10 17C6.13401 17 3 13.866 3 10C3 6.13401 6.13401 3 10 3C13.866 3 17 6.13401 17 10Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                        搜索
                    </button>
                    <button class="btn btn-secondary" onclick="resetTagSearch()">
                        <svg class="btn-icon" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M4 4V9H4.58152M19.9381 11C19.446 7.05369 16.0796 4 12 4C8.64262 4 5.76829 6.06817 4.58152 9M4.58152 9H9M20 20V15H19.4185M19.4185 15C18.2317 17.9318 15.3574 20 12 20C7.92038 20 4.55399 16.9463 4.06189 13M19.4185 15H15" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                        重置
                    </button>
                </div>
            </div>

            <div class="batch-actions" id="batchActions" style="display: none;">
                <span class="selected-count">已选择 <strong id="selectedCount">0</strong> 项</span>
                <button class="btn btn-danger btn-sm" onclick="batchDeleteTags()" data-permission="tag:delete">批量删除</button>
                <button class="btn btn-success btn-sm" onclick="openMergeModal()" data-permission="tag:merge">合并选中</button>
                <button class="btn btn-secondary btn-sm" onclick="clearSelection()">取消选择</button>
            </div>

            <div class="tags-table-container">
                <table class="tags-table">
                    <thead>
                        <tr>
                            <th width="5%"><input type="checkbox" id="selectAll" onchange="toggleSelectAll(this)"></th>
                            <th width="10%">颜色</th>
                            <th width="30%">名称</th>
                            <th width="15%">引用次数</th>
                            <th width="20%">创建时间</th>
                            <th width="20%">操作</th>
                        </tr>
                    </thead>
                    <tbody id="tagTableBody">
                        <tr>
                            <td colspan="6" class="loading">加载中...</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="pagination" id="tagPagination"></div>
        </div>
    </div>

    <div class="modal" id="tagModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 id="tagModalTitle">新增标签</h3>
                <button class="modal-close" onclick="closeTagModal()">&times;</button>
            </div>
            <form id="tagForm" class="modal-body">
                <input type="hidden" id="tagId">
                <div class="form-group">
                    <label for="tagName">标签名称 <span class="required">*</span></label>
                    <input type="text" id="tagName" name="name" required placeholder="请输入标签名称">
                </div>
                <div class="form-group">
                    <label for="tagColor">颜色</label>
                    <input type="color" id="tagColor" name="color" value="#6366f1">
                </div>
                <div class="form-actions">
                    <button type="button" class="btn btn-secondary" onclick="closeTagModal()">取消</button>
                    <button type="submit" class="btn btn-primary">保存</button>
                </div>
            </form>
        </div>
    </div>

    <div class="modal" id="mergeModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>合并标签</h3>
                <button class="modal-close" onclick="closeMergeModal()">&times;</button>
            </div>
            <div class="modal-body">
                <div class="form-group">
                    <label>已选择的标签（将被合并）</label>
                    <div id="mergeSourceTags" class="merge-tags-preview"></div>
                </div>
                <div class="form-group">
                    <label for="mergeTargetId">合并到目标标签 <span class="required">*</span></label>
                    <select id="mergeTargetId" class="form-control">
                        <option value="">请选择目标标签</option>
                    </select>
                    <small class="form-text text-muted">合并后，所有已选标签的引用将转移到目标标签，已选标签将被删除。此操作不可撤销。</small>
                </div>
                <div class="form-actions">
                    <button type="button" class="btn btn-secondary" onclick="closeMergeModal()">取消</button>
                    <button type="button" class="btn btn-danger" onclick="confirmMerge()">确认合并</button>
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
    <script src="tags.js"></script>
</body>
</html>
