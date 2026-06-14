<?php header('Content-Type: text/html; charset=UTF-8');
require_once 'common.php';
require_login();
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>分类管理 - 公告信息管理系统</title>
    <link rel="stylesheet" href="style.css">
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+SC:wght@300;400;500;700&display=swap" rel="stylesheet">
</head>
<body>
    <?php include 'header.php'; ?>

    <div class="container">
        <div class="main-content">
            <div class="page-header">
                <h2>分类管理</h2>
                <div class="page-actions">
                    <button class="btn btn-primary" onclick="openCategoryModal()">
                        <svg class="btn-icon" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M12 5V19M5 12H19" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                        新增分类
                    </button>
                </div>
            </div>

            <div class="categories-search">
                <div class="search-fields">
                    <div class="search-field">
                        <label>关键词</label>
                        <input type="text" id="categoryKeyword" placeholder="搜索分类名称或描述...">
                    </div>
                    <div class="search-field">
                        <label>状态</label>
                        <select id="categoryStatus">
                            <option value="">全部</option>
                            <option value="enabled">启用</option>
                            <option value="disabled">禁用</option>
                        </select>
                    </div>
                </div>
                <div class="search-actions">
                    <button class="btn btn-primary" onclick="loadCategories()">
                        <svg class="btn-icon" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M21 21L15 15M17 10C17 13.866 13.866 17 10 17C6.13401 17 3 13.866 3 10C3 6.13401 6.13401 3 10 3C13.866 3 17 6.13401 17 10Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                        搜索
                    </button>
                    <button class="btn btn-secondary" onclick="resetCategorySearch()">
                        <svg class="btn-icon" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M4 4V9H4.58152M19.9381 11C19.446 7.05369 16.0796 4 12 4C8.64262 4 5.76829 6.06817 4.58152 9M4.58152 9H9M20 20V15H19.4185M19.4185 15C18.2317 17.9318 15.3574 20 12 20C7.92038 20 4.55399 16.9463 4.06189 13M19.4185 15H15" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                        重置
                    </button>
                </div>
            </div>

            <div class="batch-actions" id="batchActions" style="display: none;">
                <span class="selected-count">已选择 <strong id="selectedCount">0</strong> 项</span>
                <button class="btn btn-success btn-sm" onclick="batchUpdateStatus('enabled')">批量启用</button>
                <button class="btn btn-warning btn-sm" onclick="batchUpdateStatus('disabled')">批量禁用</button>
                <button class="btn btn-secondary btn-sm" onclick="clearSelection()">取消选择</button>
            </div>

            <div class="categories-table-container">
                <table class="categories-table">
                    <thead>
                        <tr>
                            <th width="5%"><input type="checkbox" id="selectAll" onchange="toggleSelectAll(this)"></th>
                            <th width="8%">排序</th>
                            <th width="10%">图标</th>
                            <th width="15%">名称</th>
                            <th width="25%">描述</th>
                            <th width="10%">颜色</th>
                            <th width="10%">状态</th>
                            <th width="12%">创建时间</th>
                            <th width="10%">操作</th>
                        </tr>
                    </thead>
                    <tbody id="categoryTableBody">
                        <tr>
                            <td colspan="9" class="loading">加载中...</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="pagination" id="categoryPagination"></div>
        </div>
    </div>

    <div class="modal" id="categoryModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 id="modalTitle">新增分类</h3>
                <button class="modal-close" onclick="closeCategoryModal()">&times;</button>
            </div>
            <form id="categoryForm" class="modal-body">
                <input type="hidden" id="categoryId">
                <div class="form-group">
                    <label for="categoryName">分类名称 <span class="required">*</span></label>
                    <input type="text" id="categoryName" name="name" required placeholder="请输入分类名称">
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label for="categoryEmoji">Emoji 图标</label>
                        <input type="text" id="categoryEmoji" name="emoji" placeholder="例如：📢">
                    </div>
                    <div class="form-group">
                        <label for="categoryColor">颜色</label>
                        <input type="color" id="categoryColor" name="color" value="#6366f1">
                    </div>
                </div>
                <div class="form-group">
                    <label for="categoryDescription">分类描述</label>
                    <textarea id="categoryDescription" name="description" rows="3" placeholder="请输入分类描述"></textarea>
                </div>
                <div class="form-group">
                    <label for="categoryFormStatus">状态</label>
                    <select id="categoryFormStatus" name="status">
                        <option value="enabled">启用</option>
                        <option value="disabled">禁用</option>
                    </select>
                </div>
                <div class="form-actions">
                    <button type="button" class="btn btn-secondary" onclick="closeCategoryModal()">取消</button>
                    <button type="submit" class="btn btn-primary">保存</button>
                </div>
            </form>
        </div>
    </div>

    <footer class="footer">
        <div class="container">
            <p>&copy; 2024 公告信息管理系统. All rights reserved.</p>
        </div>
    </footer>
    <script src="app.js"></script>
    <script src="categories.js"></script>
</body>
</html>