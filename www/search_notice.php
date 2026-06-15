<?php
header('Content-Type: text/html; charset=UTF-8');
require_once 'common.php';
?>
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
    require_once 'common.php';
    
    // 处理删除操作
    if (isset($_GET['delete'])) {
        require_permission('notice:delete');
        $id = intval($_GET['delete']);
        $conn = getConnection();
        
        // 获取完整的删除前数据用于日志
        $notice_stmt = $conn->prepare("SELECT * FROM notices WHERE id = ?");
        $notice_stmt->bind_param("i", $id);
        $notice_stmt->execute();
        $notice_result = $notice_stmt->get_result();
        $before_data = $notice_result->fetch_assoc();
        $notice_stmt->close();
        
        // 获取标签信息
        if ($before_data) {
            $tags_stmt = $conn->prepare("SELECT t.id, t.name FROM tags t INNER JOIN notice_tags nt ON t.id = nt.tag_id WHERE nt.notice_id = ?");
            $tags_stmt->bind_param("i", $id);
            $tags_stmt->execute();
            $tags_result = $tags_stmt->get_result();
            $notice_tags = [];
            while ($tag = $tags_result->fetch_assoc()) {
                $notice_tags[] = $tag;
            }
            $tags_stmt->close();
            $before_data['tags'] = $notice_tags;
        }

        $stmt = $conn->prepare("DELETE FROM notices WHERE id = ?");
        $stmt->bind_param("i", $id);
        
        if ($stmt->execute()) {
            $success_message = "公告删除成功！";
            if ($before_data) {
                send_message_to_all('notice', '公告已删除', '公告「' . $before_data['title'] . '」已被删除', 'notice', $id);
                write_operation_log('delete', 'notice', $id, $before_data, null);
            }
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
    $search_category = isset($_GET['search_category']) ? sanitize($_GET['search_category']) : '';
    $search_tags_param = isset($_GET['search_tags']) ? sanitize($_GET['search_tags']) : '';
    $search_tags = [];
    if (!empty($search_tags_param)) {
        $search_tags = array_map('intval', explode(',', $search_tags_param));
        $search_tags = array_filter($search_tags, function($id) {
            return $id > 0;
        });
    }
    
    // 获取分类列表
    $conn = getConnection();
    $category_result = $conn->query("SELECT * FROM categories WHERE status = 'enabled' ORDER BY sort_order ASC, id ASC");
    $categories = [];
    while ($cat = $category_result->fetch_assoc()) {
        $categories[] = $cat;
    }
    
    // 获取所有标签列表
    $tags_result = $conn->query("SELECT * FROM tags ORDER BY reference_count DESC, id ASC");
    $all_tags = [];
    while ($tag = $tags_result->fetch_assoc()) {
        $all_tags[] = $tag;
    }
    
    closeConnection($conn);
    
    // 构建查询
    $conn = getConnection();
    $where_clauses = [];
    $params = [];
    $types = '';
    
    if (!empty($search_title)) {
        $where_clauses[] = "n.title LIKE ?";
        $params[] = "%$search_title%";
        $types .= 's';
    }
    
    if (!empty($search_author)) {
        $where_clauses[] = "n.author LIKE ?";
        $params[] = "%$search_author%";
        $types .= 's';
    }
    
    if (!empty($search_priority)) {
        $where_clauses[] = "n.priority = ?";
        $params[] = $search_priority;
        $types .= 's';
    }
    
    if (!empty($search_category)) {
        $where_clauses[] = "n.category_id = ?";
        $params[] = $search_category;
        $types .= 'i';
    }
    
    if (!empty($search_tags)) {
        $placeholders = implode(',', array_fill(0, count($search_tags), '?'));
        $where_clauses[] = "n.id IN (SELECT notice_id FROM notice_tags WHERE tag_id IN ($placeholders))";
        foreach ($search_tags as $tag_id) {
            $params[] = $tag_id;
            $types .= 'i';
        }
    }
    
    $where_sql = !empty($where_clauses) ? "WHERE " . implode(" AND ", $where_clauses) : "";
    
    // 获取总记录数
    $count_sql = "SELECT COUNT(*) as total FROM notices n $where_sql";
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
    $sql = "SELECT n.*, c.name as category_name, c.emoji as category_emoji, c.color as category_color FROM notices n LEFT JOIN categories c ON n.category_id = c.id $where_sql ORDER BY n.publish_date DESC LIMIT ? OFFSET ?";
    $params[] = $per_page;
    $params[] = $offset;
    $types .= 'ii';
    
    $stmt = $conn->prepare($sql);
    if (!empty($types)) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    
    // 获取所有公告的标签
    $notice_ids = [];
    $notices_data = [];
    while ($row = $result->fetch_assoc()) {
        $notice_ids[] = $row['id'];
        $notices_data[] = $row;
    }
    $result->free();
    
    $notice_tags = [];
    if (!empty($notice_ids)) {
        $placeholders = implode(',', array_fill(0, count($notice_ids), '?'));
        $tag_types = str_repeat('i', count($notice_ids));
        $tag_sql = "SELECT nt.notice_id, t.* FROM notice_tags nt INNER JOIN tags t ON nt.tag_id = t.id WHERE nt.notice_id IN ($placeholders) ORDER BY t.reference_count DESC, t.id ASC";
        $tag_stmt = $conn->prepare($tag_sql);
        $tag_stmt->bind_param($tag_types, ...$notice_ids);
        $tag_stmt->execute();
        $tag_result = $tag_stmt->get_result();
        while ($tag_row = $tag_result->fetch_assoc()) {
            if (!isset($notice_tags[$tag_row['notice_id']])) {
                $notice_tags[$tag_row['notice_id']] = [];
            }
            $notice_tags[$tag_row['notice_id']][] = $tag_row;
        }
        $tag_stmt->close();
    }
    
    // 重新构造结果
    $result = new ArrayObject($notices_data);
    $result = $result->getIterator();
    ?>
    
    <?php include 'header.php'; ?>

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
                <div class="search-header">
                    <h2>查询公告</h2>
                    <a href="recycle_bin.php" class="btn btn-secondary recycle-btn" data-permission="notice:recycle">
                        <svg class="btn-icon" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M3 6H5H21M8 6V4C8 3.44772 8.44772 3 9 3H15C15.5523 3 16 3.44772 16 4V6M19 6L18.1327 19.1425C18.0579 20.1891 17.187 21 16.1378 21H7.86224C6.81296 21 5.94208 20.1891 5.86732 19.1425L5 6M10 11V17M14 11V17" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                        回收站
                    </a>
                </div>
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
                            <label for="search_category">分类</label>
                            <select id="search_category" name="search_category">
                                <option value="">全部</option>
                                <?php foreach ($categories as $cat): ?>
                                    <option value="<?php echo $cat['id']; ?>" 
                                        <?php echo $search_category == $cat['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars(($cat['emoji'] ? $cat['emoji'] . ' ' : '') . $cat['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
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
                        <div class="search-field">
                            <label>标签</label>
                            <div class="tag-multi-select">
                                <div class="tag-selector" id="tagSelector">
                                    <span class="tag-selector-placeholder">点击选择标签...</span>
                                </div>
                                <div class="tag-dropdown" id="tagDropdown">
                                    <?php foreach ($all_tags as $tag): ?>
                                        <label class="tag-dropdown-item">
                                            <input type="checkbox" name="search_tag_checkbox" value="<?php echo $tag['id']; ?>" 
                                                <?php echo in_array($tag['id'], $search_tags) ? 'checked' : ''; ?>
                                                onchange="updateSelectedTags()">
                                            <span class="tag-badge" style="background-color: <?php echo $tag['color']; ?>20; color: <?php echo $tag['color']; ?>;">
                                                <?php echo htmlspecialchars($tag['name']); ?>
                                            </span>
                                            <span class="tag-count">(<?php echo $tag['reference_count']; ?>)</span>
                                        </label>
                                    <?php endforeach; ?>
                                    <?php if (empty($all_tags)): ?>
                                        <div class="no-tags">暂无标签</div>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <input type="hidden" id="search_tags" name="search_tags" value="<?php echo htmlspecialchars(implode(',', $search_tags)); ?>">
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
                            <th width="8%">分类</th>
                            <th width="18%">标题</th>
                            <th width="20%">内容摘要</th>
                            <th width="12%">标签</th>
                            <th width="6%">发布人</th>
                            <th width="6%">优先级</th>
                            <th width="11%">发布时间</th>
                            <th width="14%">操作</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($row->valid()): $notice = $row->current(); $row->next(); ?>
                        <tr>
                            <td><?php echo $notice['id']; ?></td>
                            <td>
                                <?php if ($notice['category_name']): ?>
                                    <span class="category-badge" style="background-color: <?php echo htmlspecialchars($notice['category_color']); ?>20; color: <?php echo htmlspecialchars($notice['category_color']); ?>;">
                                        <?php echo htmlspecialchars(($notice['category_emoji'] ? $notice['category_emoji'] . ' ' : '') . $notice['category_name']); ?>
                                    </span>
                                <?php else: ?>
                                    <span class="text-muted">-</span>
                                <?php endif; ?>
                            </td>
                            <td class="notice-title-cell">
                                <?php echo htmlspecialchars($notice['title']); ?>
                            </td>
                            <td class="notice-content-cell">
                                <?php 
                                $content = htmlspecialchars($notice['content']);
                                echo mb_substr($content, 0, 40, 'UTF-8') . (mb_strlen($content, 'UTF-8') > 40 ? '...' : ''); 
                                ?>
                            </td>
                            <td>
                                <?php if (isset($notice_tags[$notice['id']]) && !empty($notice_tags[$notice['id']])): ?>
                                    <div class="notice-tags">
                                        <?php foreach (array_slice($notice_tags[$notice['id']], 0, 3) as $tag): ?>
                                            <span class="tag-badge" style="background-color: <?php echo htmlspecialchars($tag['color']); ?>20; color: <?php echo htmlspecialchars($tag['color']); ?>;" title="<?php echo htmlspecialchars($tag['name']); ?>">
                                                <?php echo htmlspecialchars($tag['name']); ?>
                                            </span>
                                        <?php endforeach; ?>
                                        <?php if (count($notice_tags[$notice['id']]) > 3): ?>
                                            <span class="tag-more">+<?php echo count($notice_tags[$notice['id']]) - 3; ?></span>
                                        <?php endif; ?>
                                    </div>
                                <?php else: ?>
                                    <span class="text-muted">-</span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo htmlspecialchars($notice['author']); ?></td>
                            <td>
                                <span class="priority-badge priority-<?php echo $notice['priority']; ?>">
                                    <?php 
                                    echo ['high' => '高', 'medium' => '中', 'low' => '低'][$notice['priority']]; 
                                    ?>
                                </span>
                            </td>
                            <td><?php echo date('Y-m-d H:i', strtotime($notice['publish_date'])); ?></td>
                            <td class="action-buttons">
                                <a href="add_notice.php?id=<?php echo $notice['id']; ?>" class="btn-icon-action edit" title="编辑" data-permission="notice:edit">
                                    <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                        <path d="M11 4H4C3.46957 4 2.96086 4.21071 2.58579 4.58579C2.21071 4.96086 2 5.46957 2 6V20C2 20.5304 2.21071 21.0391 2.58579 21.4142C2.96086 21.7893 3.46957 22 4 22H18C18.5304 22 19.0391 21.7893 19.4142 21.4142C19.7893 21.0391 20 20.5304 20 20V13M18.5 2.5C18.8978 2.1022 19.4374 1.87868 20 1.87868C20.5626 1.87868 21.1022 2.1022 21.5 2.5C21.8978 2.8978 22.1213 3.43739 22.1213 4C22.1213 4.56261 21.8978 5.1022 21.5 5.5L12 15L8 16L9 12L18.5 2.5Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                    </svg>
                                </a>
                                <a href="?delete=<?php echo $notice['id']; ?>&page=<?php echo $page; ?><?php echo !empty($search_title) ? '&search_title=' . urlencode($search_title) : ''; ?><?php echo !empty($search_author) ? '&search_author=' . urlencode($search_author) : ''; ?><?php echo !empty($search_priority) ? '&search_priority=' . urlencode($search_priority) : ''; ?><?php echo !empty($search_category) ? '&search_category=' . urlencode($search_category) : ''; ?><?php echo !empty($search_tags) ? '&search_tags=' . urlencode(implode(',', $search_tags)) : ''; ?>" 
                                   class="btn-icon-action delete" 
                                   title="删除"
                                   data-permission="notice:delete"
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
                if (!empty($search_category)) $query_params[] = 'search_category=' . urlencode($search_category);
                if (!empty($search_tags)) $query_params[] = 'search_tags=' . urlencode(implode(',', $search_tags));
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
    closeConnection($conn);
    ?>
    <script src="app.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const tagSelector = document.getElementById('tagSelector');
        const tagDropdown = document.getElementById('tagDropdown');
        
        if (tagSelector && tagDropdown) {
            tagSelector.addEventListener('click', function(e) {
                e.stopPropagation();
                tagDropdown.classList.toggle('show');
            });

            document.addEventListener('click', function(e) {
                if (!e.target.closest('.tag-multi-select')) {
                    tagDropdown.classList.remove('show');
                }
            });
        }

        updateSelectedTagsDisplay();
    });

    function updateSelectedTags() {
        const checkboxes = document.querySelectorAll('input[name="search_tag_checkbox"]:checked');
        const selectedIds = Array.from(checkboxes).map(cb => parseInt(cb.value));
        document.getElementById('search_tags').value = selectedIds.join(',');
        updateSelectedTagsDisplay();
    }

    function updateSelectedTagsDisplay() {
        const selector = document.getElementById('tagSelector');
        const hiddenInput = document.getElementById('search_tags');
        if (!selector || !hiddenInput) return;

        const selectedIds = hiddenInput.value ? hiddenInput.value.split(',').map(Number) : [];
        
        if (selectedIds.length === 0) {
            selector.innerHTML = '<span class="tag-selector-placeholder">点击选择标签...</span>';
            return;
        }

        const allTags = <?php echo json_encode($all_tags); ?>;
        const selectedTags = allTags.filter(t => selectedIds.includes(t.id));

        selector.innerHTML = selectedTags.map(tag => `
            <span class="tag-item" style="background-color: ${tag.color}20; color: ${tag.color};">
                ${escapeHtml(tag.name)}
                <button type="button" class="tag-remove" onclick="removeSearchTag(${tag.id}, event)">×</button>
            </span>
        `).join('');
    }

    function removeSearchTag(tagId, event) {
        event.stopPropagation();
        const checkbox = document.querySelector(`input[name="search_tag_checkbox"][value="${tagId}"]`);
        if (checkbox) {
            checkbox.checked = false;
            updateSelectedTags();
        }
    }

    function escapeHtml(text) {
        if (!text) return '';
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
    </script>
</body>
</html>
