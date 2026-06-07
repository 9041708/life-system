-- ============================================
-- 今天干嘛 - 数据库更新脚本（旧用户执行）
-- 新用户无需执行，系统自动创建
-- ============================================

CREATE TABLE IF NOT EXISTS today_food (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    category VARCHAR(50) NOT NULL DEFAULT '家常菜',
    difficulty TINYINT DEFAULT 1 COMMENT '1简单 2中等 3困难',
    time_min INT DEFAULT 30 COMMENT '耗时分钟',
    recipe_url VARCHAR(500) DEFAULT '',
    ingredients VARCHAR(500) DEFAULT '' COMMENT '逗号分隔',
    is_takeout TINYINT DEFAULT 1 COMMENT '1可外卖 0只能做',
    tags VARCHAR(200) DEFAULT '',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS today_places (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(200) NOT NULL,
    city VARCHAR(100) NOT NULL,
    province VARCHAR(50) DEFAULT '',
    is_free TINYINT DEFAULT 1 COMMENT '1免费 0收费',
    ticket_price DECIMAL(10,2) DEFAULT 0,
    description TEXT,
    tips VARCHAR(500) DEFAULT '',
    category VARCHAR(50) DEFAULT '景点' COMMENT '景点/公园/博物馆/商场/美食街',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS today_shows (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(200) NOT NULL,
    type VARCHAR(20) NOT NULL DEFAULT 'tv' COMMENT 'tv/movie/variety/anime',
    platform VARCHAR(100) NOT NULL DEFAULT '',
    status VARCHAR(50) DEFAULT '',
    cast TEXT,
    description TEXT,
    rating DECIMAL(2,1) DEFAULT 0,
    year INT DEFAULT 0,
    tags VARCHAR(200) DEFAULT '',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 如果已有数据则跳过种子数据
SELECT @cnt := COUNT(*) FROM today_food;
