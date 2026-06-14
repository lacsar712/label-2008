<?php header('Content-Type: text/html; charset=UTF-8'); ?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>查询公告 - 公告信息管理系统</title>
    <link rel="stylesheet" href="style.css">
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+SC:wght@300;400;500;700&display=swap" rel="stylesheet">
</head>
<body>
    <?php
    require_once 'config.php';
    
    // 处理删除操作
    if (isset($_GET['delete'])) {
        $id = intval($_GET['delete']);
        $conn = getConnection();
        $stmt = $conn->prepare("DELETE FROM notices WHERE id = ?");
        $stmt->bind_param("i", $id);
        
        if ($stmt->execute()) {
            $success_message = "公告删除成功！";
        } else {
            $error_message = "删除失败: " . $conn->error;
        }
        
        $stmt->close();
        closeConnection($conn);
    }
    
    // 分页设置
    $page = isset($_GET['page']) ? intval($_GET['page']) : 1;
    $per_page = 8;
    $offset = ($page - 1) * $per_page;
    
    // 搜索条件
    $search_title = isset($_GET['search_title']) ? sanitize($_GET['search_title']) : '';
    $search_author = isset($_GET['search_author']) ? sanitize($_GET['search_author']) : '';
    $search_priority = isset($_GET['search_priority']) ? sanitize($_GET['search_priority']) : '';
    
    // 构建查询
    $conn = getConnection();
    $where_clauses = [];
    $params = [];
    $types = '';
    
    if (!empty($search_title)) {
        $where_clauses[] = "title LIKE ?";
        $params[] = "%$search_title%";
        $types .= 's';
    }
    
    if (!empty($search_author)) {
        $where_clauses[] = "author LIKE ?";
        $params[] = "%$search_author%";
        $types .= 's';
    }
    
    if (!empty($search_priority)) {
        $where_clauses[] = "priority = ?";
        $params[] = $search_priority;
        $types .= 's';
    }
    
    $where_sql = !empty($where_clauses) ? "WHERE " . implode(" AND ", $where_clauses) : "";
    
    // 获取总记录数
    $count_sql = "SELECT COUNT(*) as total FROM notices $where_sql";
    if (!empty($params)) {
        $count_stmt = $conn->prepare($count_sql);
        $count_stmt->bind_param($types, ...$params);
        $count_stmt->execute();
        $total_result = $count_stmt->get_result();
        $total_records = $total_result->fetch_assoc()['total'];
        $count_stmt->close();
    } else {
        $total_result = $conn->query($count_sql);
        $total_records = $total_result->fetch_assoc()['total'];
    }
    
    $total_pages = ceil($total_records / $per_page);
    
    // 获取当前页数据
    $sql = "SELECT * FROM notices $where_sql ORDER BY publish_date DESC LIMIT ? OFFSET ?";
    $params[] = $per_page;
    $params[] = $offset;
    $types .= 'ii';
    
    $stmt = $conn->prepare($sql);
    if (!empty($types)) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    ?>
    
    <!-- 导航栏 -->
    <nav class="navbar">
        <div class="container">
            <div class="nav-brand">
                <svg class="logo-icon" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M19 3H5C3.89543 3 3 3.89543 3 5V19C3 20.1046 3.89543 21 5 21H19C20.1046 21 21 20.1046 21 19V5C21 3.89543 20.1046 3 19 3Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    <path d="M7 7H17M7 12H17M7 17H13" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
                <h1>公告信息管理系统</h1>
            </div>
            <ul class="nav-menu">
                <li><a href="index.php">首页</a></li>
                <li><a href="add_notice.php">添加公告</a></li>
                <li><a href="search_notice.php" class="active">查询公告</a></li>
            </ul>
        </div>
    </nav>

    <div class="container">
        <!-- 主要内容 -->
        <div class="main-content">
            <?php if (isset($success_message)): ?>
            <div class="alert alert-success">
                <svg class="alert-icon" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M9 12L11 14L15 10M21 12C21 16.9706 16.9706 21 12 21C7.02944 21 3 16.9706 3 12C3 7.02944 7.02944 3 12 3C16.9706 3 21 7.02944 21 12Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
                <?php echo $success_message; ?>
            </div>
            <?php endif; ?>

            <?php if (isset($error_message)): ?>
            <div class="alert alert-error">
                <svg class="alert-icon" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M12 8V12M12 16H12.01M21 12C21 16.9706 16.9706 21 12 21C7.02944 21 3 16.9706 3 12C3 7.02944 7.02944 3 12 3C16.9706 3 21 7.02944 21 12Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
                <?php echo $error_message; ?>
            </div>
            <?php endif; ?>

            <!-- 搜索表单 -->
            <div class="search-container">
                <h2>查询公告</h2>
                <form method="GET" action="" class="search-form">
                    <div class="search-fields">
                        <div class="search-field">
                            <label for="search_title">标题</label>
                            <input type="text" id="search_title" name="search_title" 
                                   value="<?php echo htmlspecialchars($search_title); ?>"
                                   placeholder="搜索标题...">
                        </div>
                        <div class="search-field">
                            <label for="search_author">发布人</label>
                            <input type="text" id="search_author" name="search_author" 
                                   value="<?php echo htmlspecialchars($search_author); ?>"
                                   placeholder="搜索发布人...">
                        </div>
                        <div class="search-field">
                            <label for="search_priority">优先级</label>
                            <select id="search_priority" name="search_priority">
                                <option value="">全部</option>
                                <option value="high" <?php echo $search_priority == 'high' ? 'selected' : ''; ?>>高</option>
                                <option value="medium" <?php echo $search_priority == 'medium' ? 'selected' : ''; ?>>中</option>
                                <option value="low" <?php echo $search_priority == 'low' ? 'selected' : ''; ?>>低</option>
                            </select>
                        </div>
                    </div>
                    <div class="search-actions">
                        <button type="submit" class="btn btn-primary">
                            <svg class="btn-icon" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M21 21L15 15M17 10C17 13.866 13.866 17 10 17C6.13401 17 3 13.866 3 10C3 6.13401 6.13401 3 10 3C13.866 3 17 6.13401 17 10Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                            搜索
                        </button>
                        <a href="search_notice.php" class="btn btn-secondary">
                            <svg class="btn-icon" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M4 4V9H4.58152M19.9381 11C19.446 7.05369 16.0796 4 12 4C8.64262 4 5.76829 6.06817 4.58152 9M4.58152 9H9M20 20V15H19.4185M19.4185 15C18.2317 17.9318 15.3574 20 12 20C7.92038 20 4.55399 16.9463 4.06189 13M19.4185 15H15" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                            重置
                        </a>
                    </div>
                </form>
            </div>

            <!-- 结果统计 -->
            <div class="results-info">
                <p>共找到 <strong><?php echo $total_records; ?></strong> 条公告，当前第 <strong><?php echo $page; ?></strong> / <strong><?php echo max(1, $total_pages); ?></strong> 页</p>
            </div>

            <!-- 公告列表 -->
            <div class="notices-table-container">
                <?php if ($result->num_rows > 0): ?>
                <table class="notices-table">
                    <thead>
                        <tr>
                            <th width="5%">ID</th>
                            <th width="25%">标题</th>
                            <th width="30%">内容摘要</th>
                            <th width="10%">发布人</th>
                            <th width="8%">优先级</th>
                            <th width="12%">发布时间</th>
                            <th width="10%">操作</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($row = $result->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo $row['id']; ?></td>
                            <td class="notice-title-cell">
                                <?php echo htmlspecialchars($row['title']); ?>
                            </td>
                            <td class="notice-content-cell">
                                <?php 
                                $content = htmlspecialchars($row['content']);
                                echo mb_substr($content, 0, 60, 'UTF-8') . (mb_strlen($content, 'UTF-8') > 60 ? '...' : ''); 
                                ?>
                            </td>
                            <td><?php echo htmlspecialchars($row['author']); ?></td>
                            <td>
                                <span class="priority-badge priority-<?php echo $row['priority']; ?>">
                                    <?php 
                                    echo ['high' => '高', 'medium' => '中', 'low' => '低'][$row['priority']]; 
                                    ?>
                                </span>
                            </td>
                            <td><?php echo date('Y-m-d H:i', strtotime($row['publish_date'])); ?></td>
                            <td class="action-buttons">
                                <a href="add_notice.php?id=<?php echo $row['id']; ?>" class="btn-icon-action edit" title="编辑">
                                    <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                        <path d="M11 4H4C3.46957 4 2.96086 4.21071 2.58579 4.58579C2.21071 4.96086 2 5.46957 2 6V20C2 20.5304 2.21071 21.0391 2.58579 21.4142C2.96086 21.7893 3.46957 22 4 22H18C18.5304 22 19.0391 21.7893 19.4142 21.4142C19.7893 21.0391 20 20.5304 20 20V13M18.5 2.5C18.8978 2.1022 19.4374 1.87868 20 1.87868C20.5626 1.87868 21.1022 2.1022 21.5 2.5C21.8978 2.8978 22.1213 3.43739 22.1213 4C22.1213 4.56261 21.8978 5.1022 21.5 5.5L12 15L8 16L9 12L18.5 2.5Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                    </svg>
                                </a>
                                <a href="?delete=<?php echo $row['id']; ?>&page=<?php echo $page; ?><?php echo !empty($search_title) ? '&search_title=' . urlencode($search_title) : ''; ?><?php echo !empty($search_author) ? '&search_author=' . urlencode($search_author) : ''; ?><?php echo !empty($search_priority) ? '&search_priority=' . urlencode($search_priority) : ''; ?>" 
                                   class="btn-icon-action delete" 
                                   title="删除"
                                   onclick="return confirm('确定要删除这条公告吗？');">
                                    <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                        <path d="M19 7L18.1327 19.1425C18.0579 20.1891 17.187 21 16.1378 21H7.86224C6.81296 21 5.94208 20.1891 5.86732 19.1425L5 7M10 11V17M14 11V17M15 7V4C15 3.44772 14.5523 3 14 3H10C9.44772 3 9 3.44772 9 4V7M4 7H20" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                    </svg>
                                </a>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
                <?php else: ?>
                <div class="no-results">
                    <svg class="no-results-icon" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M21 21L15 15M17 10C17 13.866 13.866 17 10 17C6.13401 17 3 13.866 3 10C3 6.13401 6.13401 3 10 3C13.866 3 17 6.13401 17 10Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                    <p>没有找到符合条件的公告</p>
                </div>
                <?php endif; ?>
            </div>

            <!-- 分页 -->
            <?php if ($total_pages > 1): ?>
            <div class="pagination">
                <?php
                $query_params = [];
                if (!empty($search_title)) $query_params[] = 'search_title=' . urlencode($search_title);
                if (!empty($search_author)) $query_params[] = 'search_author=' . urlencode($search_author);
                if (!empty($search_priority)) $query_params[] = 'search_priority=' . urlencode($search_priority);
                $query_string = !empty($query_params) ? '&' . implode('&', $query_params) : '';
                
                // 上一页
                if ($page > 1): ?>
                    <a href="?page=<?php echo $page - 1; ?><?php echo $query_string; ?>" class="page-link">
                        <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M15 18L9 12L15 6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                        上一页
                    </a>
                <?php endif; ?>
                
                <?php
                // 页码显示逻辑
                $start_page = max(1, $page - 2);
                $end_page = min($total_pages, $page + 2);
                
                if ($start_page > 1): ?>
                    <a href="?page=1<?php echo $query_string; ?>" class="page-number">1</a>
                    <?php if ($start_page > 2): ?>
                        <span class="page-ellipsis">...</span>
                    <?php endif; ?>
                <?php endif; ?>
                
                <?php for ($i = $start_page; $i <= $end_page; $i++): ?>
                    <a href="?page=<?php echo $i; ?><?php echo $query_string; ?>" 
                       class="page-number <?php echo $i == $page ? 'active' : ''; ?>">
                        <?php echo $i; ?>
                    </a>
                <?php endfor; ?>
                
                <?php if ($end_page < $total_pages): ?>
                    <?php if ($end_page < $total_pages - 1): ?>
                        <span class="page-ellipsis">...</span>
                    <?php endif; ?>
                    <a href="?page=<?php echo $total_pages; ?><?php echo $query_string; ?>" class="page-number">
                        <?php echo $total_pages; ?>
                    </a>
                <?php endif; ?>
                
                <!-- 下一页 -->
                <?php if ($page < $total_pages): ?>
                    <a href="?page=<?php echo $page + 1; ?><?php echo $query_string; ?>" class="page-link">
                        下一页
                        <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M9 18L15 12L9 6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </a>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- 页脚 -->
    <footer class="footer">
        <div class="container">
            <p>&copy; 2024 公告信息管理系统. All rights reserved.</p>
        </div>
    </footer>

    <?php
    $stmt->close();
    closeConnection($conn);
    ?>
</body>
</html>
