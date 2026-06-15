<?php
require_once 'common.php';
require_permission('view_analysis:view');
header('Content-Type: text/html; charset=UTF-8');
$current_user = get_current_user();
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>浏览分析 - 公告信息管理系统</title>
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
                    <h2>浏览分析</h2>
                    <p>公告浏览行为数据分析</p>
                </div>
                
                <div class="time-range-selector">
                    <div class="time-range-buttons">
                        <button class="time-range-btn active" data-days="7">7天</button>
                        <button class="time-range-btn" data-days="30">30天</button>
                        <button class="time-range-btn" data-days="90">90天</button>
                        <button class="time-range-btn" id="customRangeBtn">自定义</button>
                    </div>
                    <div class="custom-date-range" id="customDateRange" style="display: none;">
                        <input type="date" id="startDate">
                        <span>至</span>
                        <input type="date" id="endDate">
                        <button class="btn btn-primary btn-sm" id="applyCustomRange">应用</button>
                        <button class="btn btn-secondary btn-sm" id="cancelCustomRange">取消</button>
                    </div>
                </div>
            </div>

            <div class="stats-overview">
                <div class="stat-card">
                    <div class="stat-card-header">
                        <span class="stat-card-title">今日 PV</span>
                        <div class="stat-card-icon stat-icon-pv">
                            <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M15 12C15 13.6569 13.6569 15 12 15C10.3431 15 9 13.6569 9 12C9 10.3431 10.3431 9 12 9C13.6569 9 15 10.3431 15 12Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                <path d="M2.45801 12C3.73201 7.943 7.52301 5 12.001 5C16.478 5 20.269 7.943 21.543 12C20.269 16.057 16.478 19 12.001 19C7.52301 19 3.73201 16.057 2.45801 12Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                        </div>
                    </div>
                    <div class="stat-card-value" id="todayPv">0</div>
                    <div class="stat-card-change" id="todayPvChange">
                        <span class="change-icon">↑</span>
                        <span class="change-value">0%</span>
                        <span class="change-label">较昨日</span>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-card-header">
                        <span class="stat-card-title">今日 UV</span>
                        <div class="stat-card-icon stat-icon-uv">
                            <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M16 7C16 9.20914 14.2091 11 12 11C9.79086 11 8 9.20914 8 7C8 4.79086 9.79086 3 12 3C14.2091 3 16 4.79086 16 7Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                <path d="M12 14C8.13401 14 5 17.134 5 21H19C19 17.134 15.866 14 12 14Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                        </div>
                    </div>
                    <div class="stat-card-value" id="todayUv">0</div>
                    <div class="stat-card-change" id="todayUvChange">
                        <span class="change-icon">↑</span>
                        <span class="change-value">0%</span>
                        <span class="change-label">较昨日</span>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-card-header">
                        <span class="stat-card-title">周期总 PV</span>
                        <div class="stat-card-icon stat-icon-period">
                            <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M9 19V6L12 4L15 6V19" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                <path d="M5 21H19" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                <path d="M12 12H8" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                <path d="M12 15H8" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                <path d="M12 9H16" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                <path d="M12 6H16" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                        </div>
                    </div>
                    <div class="stat-card-value" id="periodPv">0</div>
                    <div class="stat-card-change" id="periodPvChange">
                        <span class="change-icon">↑</span>
                        <span class="change-value">0%</span>
                        <span class="change-label">较上周期</span>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-card-header">
                        <span class="stat-card-title">周期总 UV</span>
                        <div class="stat-card-icon stat-icon-users">
                            <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M17 21V19C17 17.9391 16.5786 16.9217 15.8284 16.1716C15.0783 15.4214 14.0609 15 13 15H5C3.93913 15 2.92172 15.4214 2.17157 16.1716C1.42143 16.9217 1 17.9391 1 19V21" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                <path d="M9 11C11.2091 11 13 9.20914 13 7C13 4.79086 11.2091 3 9 3C6.79086 3 5 4.79086 5 7C5 9.20914 6.79086 11 9 11Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                <path d="M23 21V19C22.9993 18.1137 22.7044 17.2528 22.1614 16.5523C21.6184 15.8519 20.8581 15.3516 20 15.13" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                <path d="M16 3.13C16.8604 3.35031 17.623 3.85071 18.1676 4.55232C18.7122 5.25392 19.0078 6.11683 19.0078 7.005C19.0078 7.89317 18.7122 8.75608 18.1676 9.45768C17.623 10.1593 16.8604 10.6597 16 10.88" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                        </div>
                    </div>
                    <div class="stat-card-value" id="periodUv">0</div>
                    <div class="stat-card-change" id="periodUvChange">
                        <span class="change-icon">↑</span>
                        <span class="change-value">0%</span>
                        <span class="change-label">较上周期</span>
                    </div>
                </div>
            </div>

            <div class="chart-section">
                <div class="chart-card">
                    <div class="chart-card-header">
                        <h3>PV/UV 趋势</h3>
                    </div>
                    <div class="chart-container">
                        <canvas id="trendChart"></canvas>
                    </div>
                </div>
            </div>

            <div class="analysis-grid">
                <div class="chart-card">
                    <div class="chart-card-header">
                        <h3>浏览量 Top10 公告</h3>
                    </div>
                    <div class="table-container">
                        <table class="top-notices-table" id="topNoticesTable">
                            <thead>
                                <tr>
                                    <th width="10%">排名</th>
                                    <th width="50%">公告标题</th>
                                    <th width="15%">作者</th>
                                    <th width="25%">浏览量</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td colspan="4" class="loading-cell">加载中...</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="chart-card">
                    <div class="chart-card-header">
                        <h3>时段访问占比</h3>
                    </div>
                    <div class="chart-container pie-chart-container">
                        <canvas id="timeDistributionChart"></canvas>
                    </div>
                </div>

                <div class="chart-card">
                    <div class="chart-card-header">
                        <h3>地区访问分布</h3>
                    </div>
                    <div class="chart-container bar-chart-container">
                        <canvas id="regionDistributionChart"></canvas>
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
    <script src="view_analysis.js"></script>
</body>
</html>
