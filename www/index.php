<?php header('Content-Type: text/html; charset=UTF-8'); ?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>公告信息管理系统</title>
    <link rel="stylesheet" href="style.css">
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+SC:wght@300;400;500;700&display=swap" rel="stylesheet">
</head>
<body>
    <?php include 'header.php'; ?>

    <div class="container">

        <!-- 主要内容 -->
        <div class="main-content">
            <!-- 轮播Banner -->
            <div class="banner-carousel" id="bannerCarousel" style="display: none;">
                <div class="banner-carousel-slides" id="bannerCarouselSlides"></div>
                <button class="banner-carousel-prev" id="bannerCarouselPrev" onclick="prevBannerSlide()">
                    <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M15 18L9 12L15 6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </button>
                <button class="banner-carousel-next" id="bannerCarouselNext" onclick="nextBannerSlide()">
                    <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M9 18L15 12L9 6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </button>
                <div class="banner-carousel-dots" id="bannerCarouselDots"></div>
            </div>

            <!-- 欢迎横幅 -->
            <div class="welcome-banner">
                <div class="banner-content">
                    <h2>欢迎使用公告信息管理系统</h2>
                    <p>高效管理您的公告信息，让信息传递更加便捷</p>
                </div>
                <div class="banner-stats">
                    <?php
                    require_once 'config.php';
                    $conn = getConnection();
                    
                    // 获取统计数据
                    $total_result = $conn->query("SELECT COUNT(*) as total FROM notices");
                    $total = $total_result->fetch_assoc()['total'];
                    
                    $today_result = $conn->query("SELECT COUNT(*) as today FROM notices WHERE DATE(publish_date) = CURDATE()");
                    $today = $today_result->fetch_assoc()['today'];
                    
                    $views_result = $conn->query("SELECT SUM(views) as total_views FROM notices");
                    $total_views = $views_result->fetch_assoc()['total_views'] ?? 0;
                    
                    closeConnection($conn);
                    ?>
                    <div class="stat-item">
                        <div class="stat-number"><?php echo $total; ?></div>
                        <div class="stat-label">总公告数</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-number"><?php echo $today; ?></div>
                        <div class="stat-label">今日发布</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-number"><?php echo $total_views; ?></div>
                        <div class="stat-label">总浏览量</div>
                    </div>
                </div>
            </div>

            <!-- 快捷操作 -->
            <div class="quick-actions">
                <h3>快捷操作</h3>
                <div class="action-cards">
                    <a href="add_notice.php" class="action-card" data-permission="notice:create">
                        <svg class="action-icon" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M12 5V19M5 12H19" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                        <h4>添加公告</h4>
                        <p>发布新的公告信息</p>
                    </a>
                    <a href="search_notice.php" class="action-card">
                        <svg class="action-icon" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M21 21L15 15M17 10C17 13.866 13.866 17 10 17C6.13401 17 3 13.866 3 10C3 6.13401 6.13401 3 10 3C13.866 3 17 6.13401 17 10Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                        <h4>查询公告</h4>
                        <p>搜索和浏览公告</p>
                    </a>
                    <a href="categories.php" class="action-card" data-permission="category:view">
                        <svg class="action-icon" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M7 7H20M7 12H20M7 17H20M3 7H3.01M3 12H3.01M3 17H3.01" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                        <h4>分类管理</h4>
                        <p>管理公告分类</p>
                    </a>
                    <a href="roles.php" class="action-card" data-permission="role:view">
                        <svg class="action-icon" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M12 15L9 12L10.5 10.5L12 12L16.5 7.5L18 9L12 15Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            <path d="M9 14L4.5 18.5L3 21L5.5 19.5L10 15" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            <path d="M14 4L18.5 8.5L16 11L11.5 6.5L14 4Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            <path d="M13 5L11 7L7 11L5 13" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                        <h4>角色管理</h4>
                        <p>管理系统角色和权限</p>
                    </a>
                    <a href="user_roles.php" class="action-card" data-permission="user:view">
                        <svg class="action-icon" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M17 21V19C17 17.9391 16.5786 16.9217 15.8284 16.1716C15.0783 15.4214 14.0609 15 13 15H5C3.93913 15 2.92172 15.4214 2.17157 16.1716C1.42143 16.9217 1 17.9391 1 19V21" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            <path d="M9 11C11.2091 11 13 9.20914 13 7C13 4.79086 11.2091 3 9 3C6.79086 3 5 4.79086 5 7C5 9.20914 6.79086 11 9 11Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            <path d="M23 21V19C22.9993 18.1137 22.7044 17.2528 22.1614 16.5523C21.6184 15.8519 20.8581 15.3516 20 15.13" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            <path d="M16 3.13C16.8604 3.35031 17.623 3.85071 18.1676 4.55232C18.7122 5.25392 19.0078 6.11683 19.0078 7.005C19.0078 7.89317 18.7122 8.75608 18.1676 9.45768C17.623 10.1593 16.8604 10.6597 16 10.88" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                        <h4>用户角色</h4>
                        <p>为用户分配角色</p>
                    </a>
                </div>
            </div>

            <!-- 按分类浏览 -->
            <div class="category-browse">
                <div class="section-header">
                    <h3>按分类浏览</h3>
                    <a href="search_notice.php" class="view-all">查看全部 →</a>
                </div>
                <div class="category-cards" id="categoryCards">
                    <div class="loading">加载中...</div>
                </div>
            </div>

            <!-- 最新公告列表 -->
            <div class="recent-notices">
                <div class="section-header">
                    <h3>最新公告</h3>
                    <a href="search_notice.php" class="view-all">查看全部 →</a>
                </div>
                <div class="notices-grid">
                    <?php
                    $conn = getConnection();
                    $sql = "SELECT * FROM notices WHERE status = 'published' ORDER BY publish_date DESC LIMIT 6";
                    $result = $conn->query($sql);
                    
                    if ($result->num_rows > 0) {
                        while($row = $result->fetch_assoc()) {
                            $priority_class = 'priority-' . $row['priority'];
                            $priority_text = [
                                'high' => '高',
                                'medium' => '中',
                                'low' => '低'
                            ][$row['priority']];
                            ?>
                            <div class="notice-card">
                                <div class="notice-header">
                                    <span class="priority-badge <?php echo $priority_class; ?>">
                                        <?php echo $priority_text; ?>
                                    </span>
                                    <span class="notice-date">
                                        <?php echo date('Y-m-d', strtotime($row['publish_date'])); ?>
                                    </span>
                                </div>
                                <h4 class="notice-title"><?php echo htmlspecialchars($row['title']); ?></h4>
                                <p class="notice-excerpt">
                                    <?php 
                                    $content = htmlspecialchars($row['content']);
                                    echo mb_substr($content, 0, 80, 'UTF-8') . (mb_strlen($content, 'UTF-8') > 80 ? '...' : ''); 
                                    ?>
                                </p>
                                <div class="notice-footer">
                                    <span class="notice-author">
                                        <svg class="icon" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                            <path d="M16 7C16 9.20914 14.2091 11 12 11C9.79086 11 8 9.20914 8 7C8 4.79086 9.79086 3 12 3C14.2091 3 16 4.79086 16 7Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                            <path d="M12 14C8.13401 14 5 17.134 5 21H19C19 17.134 15.866 14 12 14Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                        </svg>
                                        <?php echo htmlspecialchars($row['author']); ?>
                                    </span>
                                    <span class="notice-views">
                                        <svg class="icon" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                            <path d="M15 12C15 13.6569 13.6569 15 12 15C10.3431 15 9 13.6569 9 12C9 10.3431 10.3431 9 12 9C13.6569 9 15 10.3431 15 12Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                            <path d="M2.45801 12C3.73201 7.943 7.52301 5 12.001 5C16.478 5 20.269 7.943 21.543 12C20.269 16.057 16.478 19 12.001 19C7.52301 19 3.73201 16.057 2.45801 12Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                        </svg>
                                        <?php echo $row['views']; ?>
                                    </span>
                                </div>
                            </div>
                            <?php
                        }
                    } else {
                        echo '<p class="no-data">暂无公告信息</p>';
                    }
                    closeConnection($conn);
                    ?>
                </div>
            </div>

            <!-- 标签云 -->
            <div class="tag-cloud-section">
                <div class="section-header">
                    <h3>标签云</h3>
                    <a href="tags.php" class="view-all" data-permission="tag:view">管理标签 →</a>
                </div>
                <div class="tag-cloud" id="tagCloud">
                    <div class="loading">加载中...</div>
                </div>
            </div>
        </div>
    </div>

    <!-- 页脚 -->
    <footer class="footer">
        <div class="container">
            <p>&copy; 2024 公告信息管理系统. All rights reserved.</p>
        </div>
    </footer>
    <script src="app.js"></script>
</body>
</html>
