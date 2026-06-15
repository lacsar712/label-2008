<?php
require_once 'common.php';
require_permission('notice_like:view');
header('Content-Type: text/html; charset=UTF-8');
$current_user = get_current_user();
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>点赞洞察 - 公告信息管理系统</title>
    <link rel="stylesheet" href="style.css">
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+SC:wght@300;400;500;700&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
</head>
<body>
    <?php include 'header.php'; ?>

    <div class="container">
        <div class="main-content">
            <div class="analysis-header">
                <div class="analysis-title">
                    <h2>点赞洞察</h2>
                    <p>公告点赞用户数据分析</p>
                </div>
                <div class="header-actions">
                    <button class="btn btn-primary" id="exportBtn" disabled onclick="exportLikes()">
                        <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" style="width: 16px; height: 16px; margin-right: 6px;">
                            <path d="M21 15V19C21 19.5304 20.7893 20.0391 20.4142 20.4142C20.0391 20.7893 19.5304 21 19 21H5C4.46957 21 3.96086 20.7893 3.58579 20.4142C3.21071 20.0391 3 19.5304 3 19V15" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            <path d="M7 10L12 15L17 10" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            <path d="M12 15V3" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                        导出点赞名单
                    </button>
                </div>
            </div>

            <div class="like-insight-layout">
                <div class="like-sidebar">
                    <div class="sidebar-card">
                        <h3>选择公告</h3>
                        <div class="search-select-wrapper">
                            <input type="text" id="noticeSearchInput" placeholder="搜索公告标题..." autocomplete="off">
                            <div class="search-dropdown" id="noticeDropdown" style="display: none;"></div>
                        </div>
                        <div class="selected-notice" id="selectedNotice" style="display: none;">
                            <div class="selected-notice-title" id="selectedNoticeTitle"></div>
                            <div class="selected-notice-meta" id="selectedNoticeMeta"></div>
                        </div>
                    </div>
                </div>

                <div class="like-main-content">
                    <div class="stats-overview" id="statsOverview" style="display: none;">
                        <div class="stat-card">
                            <div class="stat-card-header">
                                <span class="stat-card-title">总点赞数</span>
                                <div class="stat-card-icon stat-icon-pv">
                                    <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                        <path d="M14 9V5A3 3 0 0 0 8 7v4H5a2 2 0 0 0-2 2v8a2 2 0 0 0 2 2h11a2 2 0 0 0 2-2v-6a2 2 0 0 0-2-2h-3z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                    </svg>
                                </div>
                            </div>
                            <div class="stat-card-value" id="totalCount">0</div>
                            <div class="stat-card-subtitle">累计点赞</div>
                        </div>

                        <div class="stat-card">
                            <div class="stat-card-header">
                                <span class="stat-card-title">今日新增</span>
                                <div class="stat-card-icon stat-icon-uv">
                                    <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                        <path d="M12 20V10" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                        <path d="M18 20V4" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                        <path d="M6 20v-6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                    </svg>
                                </div>
                            </div>
                            <div class="stat-card-value" id="todayCount">0</div>
                            <div class="stat-card-change" id="todayChange">
                                <span class="change-icon">—</span>
                                <span class="change-value">0%</span>
                                <span class="change-label">较昨日</span>
                            </div>
                        </div>

                        <div class="stat-card">
                            <div class="stat-card-header">
                                <span class="stat-card-title">点赞用户 UV</span>
                                <div class="stat-card-icon stat-icon-users">
                                    <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                        <path d="M17 21V19C17 17.9391 16.5786 16.9217 15.8284 16.1716C15.0783 15.4214 14.0609 15 13 15H5C3.93913 15 2.92172 15.4214 2.17157 16.1716C1.42143 16.9217 1 17.9391 1 19V21" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                        <path d="M9 11C11.2091 11 13 9.20914 13 7C13 4.79086 11.2091 3 9 3C6.79086 3 5 4.79086 5 7C5 9.20914 6.79086 11 9 11Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                        <path d="M23 21V19C22.9993 18.1137 22.7044 17.2528 22.1614 16.5523C21.6184 15.8519 20.8581 15.3516 20 15.13" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                        <path d="M16 3.13C16.8604 3.35031 17.623 3.85071 18.1676 4.55232C18.7122 5.25392 19.0078 6.11683 19.0078 7.005C19.0078 7.89317 18.7122 8.75608 18.1676 9.45768C17.623 10.1593 16.8604 10.6597 16 10.88" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                    </svg>
                                </div>
                            </div>
                            <div class="stat-card-value" id="totalUv">0</div>
                            <div class="stat-card-subtitle">独立访客数</div>
                        </div>
                    </div>

                    <div class="chart-section" id="trendSection" style="display: none;">
                        <div class="chart-card">
                            <div class="chart-card-header">
                                <h3>近 30 天点赞趋势</h3>
                            </div>
                            <div class="chart-container" style="height: 280px;">
                                <canvas id="trendChart"></canvas>
                            </div>
                        </div>
                    </div>

                    <div class="chart-card" id="likesTableSection" style="display: none;">
                        <div class="chart-card-header">
                            <h3>点赞用户列表</h3>
                            <div class="table-filter">
                                <select id="clientTypeFilter" onchange="loadLikesList(1)">
                                    <option value="">全部客户端</option>
                                    <option value="desktop">桌面端</option>
                                    <option value="mobile">移动端</option>
                                    <option value="tablet">平板</option>
                                    <option value="other">其他</option>
                                </select>
                            </div>
                        </div>
                        <div class="table-container">
                            <table class="data-table" id="likesTable">
                                <thead>
                                    <tr>
                                        <th width="10%">ID</th>
                                        <th width="25%">昵称</th>
                                        <th width="20%">访客ID</th>
                                        <th width="15%">IP</th>
                                        <th width="15%" class="sortable-header" data-sort="client_type" onclick="toggleSort('client_type')">
                                            客户端类型
                                            <span class="sort-icon">↕</span>
                                        </th>
                                        <th width="15%" class="sortable-header active-sort" data-sort="created_at" onclick="toggleSort('created_at')">
                                            点赞时间
                                            <span class="sort-icon">↓</span>
                                        </th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td colspan="6" class="loading-cell">请选择公告查看数据</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <div class="pagination" id="likesPagination" style="display: none;">
                            <span class="pagination-info" id="paginationInfo">共 0 条</span>
                            <div class="pagination-buttons">
                                <button class="page-btn" id="prevPageBtn" onclick="prevPage()">上一页</button>
                                <span class="page-numbers" id="pageNumbers"></span>
                                <button class="page-btn" id="nextPageBtn" onclick="nextPage()">下一页</button>
                            </div>
                        </div>
                    </div>

                    <div class="empty-state" id="emptyState">
                        <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" class="empty-icon">
                            <path d="M14 9V5A3 3 0 0 0 8 7v4H5a2 2 0 0 0-2 2v8a2 2 0 0 0 2 2h11a2 2 0 0 0 2-2v-6a2 2 0 0 0-2-2h-3z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                        <p>请从左侧选择一个公告查看点赞数据</p>
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
    <script src="notice_likes.js"></script>
</body>
</html>
