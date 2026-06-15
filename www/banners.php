<?php header('Content-Type: text/html; charset=UTF-8');
require_once 'common.php';
require_login();
require_permission('banner:view');
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Banner管理 - 公告信息管理系统</title>
    <link rel="stylesheet" href="style.css">
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+SC:wght@300;400;500;700&display=swap" rel="stylesheet">
</head>
<body>
    <?php include 'header.php'; ?>

    <div class="container">
        <div class="main-content">
            <div class="page-header">
                <h2>Banner管理</h2>
                <div class="page-actions">
                    <button class="btn btn-primary" onclick="openPreviewModal()">
                        <svg class="btn-icon" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            <path d="M12 15a3 3 0 1 0 0-6 3 3 0 0 0 0 6Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                        预览
                    </button>
                    <button class="btn btn-primary" onclick="openBannerModal()">
                        <svg class="btn-icon" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M12 5V19M5 12H19" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                        新增Banner
                    </button>
                </div>
                <div class="page-actions">
                </div>
            </div>

            <div class="banners-search">
                <div class="search-fields">
                    <div class="search-field">
                        <label>关键词</label>
                        <input type="text" id="bannerKeyword" placeholder="搜索标题或副标题...">
                    </div>
                    <div class="search-field">
                        <label>状态</label>
                        <select id="bannerStatus">
                            <option value="">全部</option>
                            <option value="enabled">启用</option>
                            <option value="disabled">禁用</option>
                        </select>
                    </div>
                </div>
                <div class="search-actions">
                    <button class="btn btn-primary" onclick="loadBanners()">
                        <svg class="btn-icon" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M21 21L15 15M17 10C17 13.866 13.866 17 10 17C6.13401 17 3 13.866 3 10C3 6.13401 6.13401 3 10 3C13.866 3 17 6.13401 17 10Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                        搜索
                    </button>
                    <button class="btn btn-secondary" onclick="resetBannerSearch()">
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

            <div class="banners-table-container">
                <table class="banners-table">
                    <thead>
                        <tr>
                            <th width="5%"><input type="checkbox" id="selectAll" onchange="toggleSelectAll(this)"></th>
                            <th width="6%">排序</th>
                            <th width="15%">缩略图</th>
                            <th width="15%">标题</th>
                            <th width="15%">副标题</th>
                            <th width="10%">生效时间</th>
                            <th width="8%">状态</th>
                            <th width="12%">创建时间</th>
                            <th width="14%">操作</th>
                        </tr>
                    </thead>
                    <tbody id="bannerTableBody">
                        <tr>
                            <td colspan="9" class="loading">加载中...</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="pagination" id="bannerPagination"></div>
        </div>
    </div>

    <div class="modal" id="bannerModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 id="modalTitle">新增Banner</h3>
                <button class="modal-close" onclick="closeBannerModal()">&times;</button>
            </div>
            <form id="bannerForm" class="modal-body">
                <input type="hidden" id="bannerId">
                <div class="form-group">
                    <label for="bannerImage">Banner图片 <span class="required">*</span></label>
                    <div class="banner-upload-area" id="bannerUploadArea" onclick="document.getElementById('bannerImageInput').click()">
                        <div class="banner-upload-placeholder" id="bannerUploadPlaceholder">
                            <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" width="48" height="48">
                                <path d="M21 15V19C21 20.1046 20.1046 21 19 21H5C3.89543 21 3 20.1046 3 19V15" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                <path d="M17 8L12 3L7 8" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                <path d="M12 3V15" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                            <p>点击上传图片</p>
                            <span>支持 JPG、PNG、GIF、WebP 格式，最大 5MB</span>
                        </div>
                        <img id="bannerImagePreview" src="" alt="Banner预览" style="display: none;">
                    </div>
                    <input type="file" id="bannerImageInput" accept="image/jpeg,image/jpg,image/png,image/gif,image/webp" style="display: none;">
                    <input type="hidden" id="bannerImageUrl" name="image_url">
                </div>
                <div class="form-group">
                    <label for="bannerTitle">标题</label>
                    <input type="text" id="bannerTitle" name="title" placeholder="请输入标题">
                </div>
                <div class="form-group">
                    <label for="bannerSubtitle">副标题</label>
                    <input type="text" id="bannerSubtitle" name="subtitle" placeholder="请输入副标题">
                </div>
                <div class="form-group">
                    <label for="bannerLinkUrl">跳转链接</label>
                    <input type="text" id="bannerLinkUrl" name="link_url" placeholder="请输入跳转链接（选填）">
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label for="bannerStartTime">生效开始时间</label>
                        <input type="datetime-local" id="bannerStartTime" name="start_time">
                        <small class="form-hint">留空表示立即生效</small>
                    </div>
                    <div class="form-group">
                        <label for="bannerEndTime">生效结束时间</label>
                        <input type="datetime-local" id="bannerEndTime" name="end_time">
                        <small class="form-hint">留空表示永不过期</small>
                    </div>
                </div>
                <div class="form-group">
                    <label for="bannerFormStatus">状态</label>
                    <select id="bannerFormStatus" name="status">
                        <option value="enabled">启用</option>
                        <option value="disabled">禁用</option>
                    </select>
                </div>
                <div class="form-actions">
                    <button type="button" class="btn btn-secondary" onclick="closeBannerModal()">取消</button>
                    <button type="submit" class="btn btn-primary">保存</button>
                </div>
            </form>
        </div>
    </div>

    <div class="modal" id="previewModal">
        <div class="modal-content preview-modal-content">
            <div class="modal-header">
                <h3>Banner预览</h3>
                <button class="modal-close" onclick="closePreviewModal()">&times;</button>
            </div>
            <div class="preview-modal-body">
                <div class="banner-preview-container" id="bannerPreviewContainer">
                    <div class="banner-preview-slides" id="bannerPreviewSlides"></div>
                    <button class="banner-preview-prev" id="bannerPreviewPrev" onclick="prevPreviewSlide()">
                        <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M15 18L9 12L15 6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </button>
                    <button class="banner-preview-next" id="bannerPreviewNext" onclick="nextPreviewSlide()">
                        <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M9 18L15 12L9 6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </button>
                    <div class="banner-preview-dots" id="bannerPreviewDots"></div>
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
    <script src="banners.js"></script>
</body>
</html>
