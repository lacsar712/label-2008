-- 设置字符集
SET NAMES utf8mb4;
SET CHARACTER SET utf8mb4;

-- 创建数据库
CREATE DATABASE IF NOT EXISTS notice_db DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE notice_db;

-- 创建公告信息表
CREATE TABLE IF NOT EXISTS notices (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL COMMENT '公告标题',
    content TEXT NOT NULL COMMENT '公告内容',
    author VARCHAR(100) NOT NULL COMMENT '发布人',
    publish_date DATETIME DEFAULT CURRENT_TIMESTAMP COMMENT '发布时间',
    update_date DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
    status ENUM('published', 'draft') DEFAULT 'published' COMMENT '状态',
    priority ENUM('high', 'medium', 'low') DEFAULT 'medium' COMMENT '优先级',
    views INT DEFAULT 0 COMMENT '浏览次数',
    INDEX idx_publish_date (publish_date),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 插入示例数据
INSERT INTO notices (title, content, author, priority, status, publish_date) VALUES
('欢迎使用公告信息管理系统', '这是一个功能完善的公告信息管理系统，支持添加、编辑、删除和查询公告信息。', '系统管理员', 'high', 'published', NOW()),
('系统维护通知', '本系统将于本周六进行例行维护，维护时间为凌晨2:00-6:00，期间系统将暂停服务。', '技术部', 'high', 'published', NOW()),
('新功能上线', '我们很高兴地宣布，系统新增了分页显示和高级搜索功能，欢迎体验！', '产品部', 'medium', 'published', DATE_SUB(NOW(), INTERVAL 1 DAY)),
('安全提醒', '请各位用户定期修改密码，确保账户安全。如发现异常情况，请及时联系管理员。', '安全部', 'high', 'published', DATE_SUB(NOW(), INTERVAL 2 DAY)),
('假期安排通知', '根据国家法定节假日安排，本系统将在春节期间正常运行，技术支持团队将保持在线。', '人事部', 'medium', 'published', DATE_SUB(NOW(), INTERVAL 3 DAY)),
('用户调查问卷', '为了更好地改进我们的服务，诚邀您参与用户满意度调查，您的意见对我们非常重要。', '客服部', 'low', 'published', DATE_SUB(NOW(), INTERVAL 5 DAY)),
('培训课程通知', '本月将举办系统使用培训课程，欢迎新用户报名参加，详情请查看培训中心。', '培训部', 'medium', 'published', DATE_SUB(NOW(), INTERVAL 7 DAY)),
('版本更新说明', '系统已更新至v2.0版本，新增了数据导出、批量操作等功能，提升了系统性能。', '技术部', 'medium', 'published', DATE_SUB(NOW(), INTERVAL 10 DAY));
