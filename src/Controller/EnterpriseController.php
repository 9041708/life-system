<?php
namespace App\Controller;

use App\Service\Config;
use App\Service\Database;

class EnterpriseController
{
    private function requireLogin(): int {
        $uid = (int)($_SESSION['user_id'] ?? 0);
        if ($uid <= 0) {
            if ($this->isApiRequest()) { $this->json(['ok' => false, 'error' => '请先登录']); }
            header('Location: /public/index.php?route=login');
            exit;
        }
        return $uid;
    }

    private function isApiRequest(): bool {
        return ($_POST['action'] ?? '') !== '';
    }

    private function render(string $view, array $p = []): void {
        extract($p);
        $appName = Config::get('app.name');
        $_SESSION['current_page_title'] = $p['pageTitle'] ?? '我的企业';
        include __DIR__ . '/../../templates/layout_main.php';
    }

    private function json(array $d): void {
        while (ob_get_level()) ob_end_clean();
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($d, JSON_UNESCAPED_UNICODE);
        exit;
    }

    public function __construct() {
        $this->initTables();
    }

    // 系统预设研发项目池
    private function getRdPool(): array {
        return [
            1 => ['name' => '智能客服系统', 'cost' => 80000, 'min_quality' => 40, 'max_quality' => 75, 'days' => 20, 'desc' => 'AI驱动的客户服务解决方案'],
            2 => ['name' => '移动支付SDK', 'cost' => 120000, 'min_quality' => 50, 'max_quality' => 85, 'days' => 25, 'desc' => '跨平台移动支付集成开发包'],
            3 => ['name' => '企业ERP系统', 'cost' => 200000, 'min_quality' => 45, 'max_quality' => 80, 'days' => 35, 'desc' => '企业资源规划管理平台'],
            4 => ['name' => '云存储平台', 'cost' => 150000, 'min_quality' => 55, 'max_quality' => 90, 'days' => 28, 'desc' => '分布式云存储解决方案'],
            5 => ['name' => '物联网传感器', 'cost' => 180000, 'min_quality' => 50, 'max_quality' => 85, 'days' => 30, 'desc' => '智能物联网传感设备'],
            6 => ['name' => '数据分析引擎', 'cost' => 250000, 'min_quality' => 60, 'max_quality' => 95, 'days' => 40, 'desc' => '大数据实时分析处理引擎'],
            7 => ['name' => '安全加密芯片', 'cost' => 300000, 'min_quality' => 55, 'max_quality' => 90, 'days' => 45, 'desc' => '硬件级安全加密方案'],
            8 => ['name' => '智能推荐算法', 'cost' => 100000, 'min_quality' => 45, 'max_quality' => 80, 'days' => 22, 'desc' => '基于机器学习的推荐系统'],
            9 => ['name' => '低代码开发平台', 'cost' => 160000, 'min_quality' => 50, 'max_quality' => 85, 'days' => 32, 'desc' => '可视化低代码应用构建工具'],
            10 => ['name' => '自动驾驶模块', 'cost' => 500000, 'min_quality' => 65, 'max_quality' => 98, 'days' => 60, 'desc' => 'L3级自动驾驶核心模块'],
            11 => ['name' => '量子加密通信', 'cost' => 800000, 'min_quality' => 70, 'max_quality' => 100, 'days' => 75, 'desc' => '量子密钥分发通信系统'],
            12 => ['name' => '元宇宙引擎', 'cost' => 600000, 'min_quality' => 60, 'max_quality' => 95, 'days' => 50, 'desc' => '虚拟世界构建与渲染引擎'],
            13 => ['name' => '生物芯片', 'cost' => 400000, 'min_quality' => 55, 'max_quality' => 90, 'days' => 42, 'desc' => '生物信息检测芯片'],
            14 => ['name' => '智能仓储系统', 'cost' => 130000, 'min_quality' => 40, 'max_quality' => 75, 'days' => 24, 'desc' => '自动化仓储管理方案'],
            15 => ['name' => '绿色能源电池', 'cost' => 220000, 'min_quality' => 50, 'max_quality' => 88, 'days' => 33, 'desc' => '高效环保储能电池技术'],
        ];
    }

    private function initTables(): void {
        $pdo = Database::getConnection();

        // 确保股票账户表存在（企业注册需要从股票账户扣款）
        $pdo->exec("CREATE TABLE IF NOT EXISTS ent_accounts (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL UNIQUE,
            balance DECIMAL(18,2) DEFAULT 1000000,
            loan_amount DECIMAL(18,2) DEFAULT 0,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $pdo->exec("CREATE TABLE IF NOT EXISTS ent_companies (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL UNIQUE,
            name VARCHAR(50) NOT NULL,
            capital DECIMAL(18,2) DEFAULT 0 COMMENT '注册资本',
            balance DECIMAL(18,2) DEFAULT 0 COMMENT '企业可用资金',
            level INT DEFAULT 1 COMMENT '1-7级',
            total_revenue DECIMAL(18,2) DEFAULT 0,
            total_profit DECIMAL(18,2) DEFAULT 0,
            total_orders_completed INT DEFAULT 0,
            total_rd_count INT DEFAULT 0,
            happiness DECIMAL(5,2) DEFAULT 50 COMMENT '员工满意度',
            labor_risk INT DEFAULT 0 COMMENT '劳动风险值0-100',
            social_insurance TINYINT DEFAULT 0 COMMENT '社保',
            housing_fund TINYINT DEFAULT 0 COMMENT '公积金',
            canteen TINYINT DEFAULT 0 COMMENT '食堂',
            dormitory TINYINT DEFAULT 0 COMMENT '宿舍',
            transport TINYINT DEFAULT 0 COMMENT '交通补贴',
            holiday_bonus TINYINT DEFAULT 0 COMMENT '节日福利',
            speed INT DEFAULT 1 COMMENT '倍速1/2/5',
            sim_time INT DEFAULT 0 COMMENT '已模拟天数',
            last_settle_at DATETIME NULL COMMENT '上次结算时间',
            last_payday INT DEFAULT 0 COMMENT '上次发薪日(模拟天)',
            is_bankrupt TINYINT DEFAULT 0,
            is_listed TINYINT DEFAULT 0 COMMENT '是否上市',
            listing_deposit DECIMAL(18,2) DEFAULT 0 COMMENT '上市保证金',
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $pdo->exec("CREATE TABLE IF NOT EXISTS ent_employees (
            id INT AUTO_INCREMENT PRIMARY KEY,
            company_id INT NOT NULL,
            name VARCHAR(20) NOT NULL,
            grade ENUM('C','B','A','S') DEFAULT 'C',
            department VARCHAR(30) DEFAULT '订单部' COMMENT '所属部门',
            salary DECIMAL(10,2) DEFAULT 5000,
            output_mult DECIMAL(4,2) DEFAULT 0.8 COMMENT '效率倍率',
            happiness INT DEFAULT 50,
            age INT DEFAULT 22,
            retire_age INT DEFAULT 60,
            hire_day INT DEFAULT 0 COMMENT '入职模拟天',
            is_active TINYINT DEFAULT 1,
            INDEX idx_company (company_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $pdo->exec("CREATE TABLE IF NOT EXISTS ent_assets (
            id INT AUTO_INCREMENT PRIMARY KEY,
            company_id INT NOT NULL,
            category VARCHAR(20) NOT NULL COMMENT '装修/网络/桌椅/设备',
            level INT DEFAULT 1,
            effect_bonus DECIMAL(10,2) DEFAULT 0,
            purchased_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_company (company_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $pdo->exec("CREATE TABLE IF NOT EXISTS ent_finance (
            id INT AUTO_INCREMENT PRIMARY KEY,
            company_id INT NOT NULL,
            type VARCHAR(20) NOT NULL COMMENT 'income/expense',
            category VARCHAR(30) NOT NULL,
            amount DECIMAL(14,2) NOT NULL,
            balance_after DECIMAL(18,2) DEFAULT 0,
            sim_day INT DEFAULT 0,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_company_day (company_id, sim_day)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $pdo->exec("CREATE TABLE IF NOT EXISTS ent_products (
            id INT AUTO_INCREMENT PRIMARY KEY,
            company_id INT NOT NULL,
            name VARCHAR(50) NOT NULL,
            quality INT DEFAULT 50 COMMENT '品质1-100',
            base_price DECIMAL(12,2) DEFAULT 0,
            INDEX idx_company (company_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $pdo->exec("CREATE TABLE IF NOT EXISTS ent_company_orders (
            id INT AUTO_INCREMENT PRIMARY KEY,
            company_id INT NOT NULL,
            client_name VARCHAR(30) NOT NULL,
            product_id INT DEFAULT NULL,
            product_name VARCHAR(50) NOT NULL,
            quantity INT DEFAULT 1,
            unit_price DECIMAL(12,2) DEFAULT 0,
            total_amount DECIMAL(14,2) DEFAULT 0,
            type ENUM('normal','urgent','vip') DEFAULT 'normal',
            status ENUM('pending','in_progress','completed','cancelled') DEFAULT 'pending',
            deadline INT DEFAULT 3 COMMENT '时限天数',
            progress INT DEFAULT 0 COMMENT '生产进度%',
            sim_day_created INT DEFAULT 0,
            INDEX idx_company (company_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $pdo->exec("CREATE TABLE IF NOT EXISTS ent_production_lines (
            id INT AUTO_INCREMENT PRIMARY KEY,
            company_id INT NOT NULL,
            name VARCHAR(30) DEFAULT '产线',
            status ENUM('idle','busy','broken') DEFAULT 'idle',
            current_order_id INT DEFAULT NULL,
            progress INT DEFAULT 0,
            efficiency DECIMAL(5,2) DEFAULT 1.0,
            INDEX idx_company (company_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $pdo->exec("CREATE TABLE IF NOT EXISTS ent_company_loans (
            id INT AUTO_INCREMENT PRIMARY KEY,
            company_id INT NOT NULL,
            plan_type VARCHAR(20) NOT NULL COMMENT '低息长期/高息短期/抵押贷款',
            amount DECIMAL(14,2) NOT NULL,
            rate DECIMAL(5,4) NOT NULL COMMENT '月利率',
            months INT NOT NULL,
            remaining INT NOT NULL,
            monthly_payment DECIMAL(14,2) DEFAULT 0,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_company (company_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $pdo->exec("CREATE TABLE IF NOT EXISTS ent_rd_projects (
            id INT AUTO_INCREMENT PRIMARY KEY,
            company_id INT NOT NULL,
            name VARCHAR(50) NOT NULL,
            progress INT DEFAULT 0 COMMENT '研发进度0-100',
            quality INT DEFAULT 50,
            cost DECIMAL(12,2) DEFAULT 0,
            research_days INT DEFAULT 30 COMMENT '预计研发天数',
            status ENUM('researching','completed') DEFAULT 'researching',
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_company (company_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        try { $pdo->exec("ALTER TABLE ent_rd_projects ADD COLUMN research_days INT DEFAULT 30 AFTER cost"); } catch (\Throwable $e) {}

        $pdo->exec("CREATE TABLE IF NOT EXISTS ent_stores (
            id INT AUTO_INCREMENT PRIMARY KEY,
            company_id INT NOT NULL,
            type ENUM('online','offline') DEFAULT 'online',
            name VARCHAR(50) NOT NULL,
            region VARCHAR(30) DEFAULT '本地',
            cost DECIMAL(12,2) DEFAULT 0,
            daily_revenue DECIMAL(12,2) DEFAULT 0,
            is_active TINYINT DEFAULT 1,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_company (company_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $pdo->exec("CREATE TABLE IF NOT EXISTS ent_crisis (
            id INT AUTO_INCREMENT PRIMARY KEY,
            company_id INT NOT NULL,
            event_name VARCHAR(50) NOT NULL,
            effect_desc VARCHAR(200) DEFAULT '',
            start_day INT DEFAULT 0,
            end_day INT DEFAULT 0,
            is_active TINYINT DEFAULT 1,
            INDEX idx_company (company_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $pdo->exec("CREATE TABLE IF NOT EXISTS ent_enterprise_items (
            id INT AUTO_INCREMENT PRIMARY KEY,
            category VARCHAR(20) NOT NULL,
            name VARCHAR(50) NOT NULL,
            description VARCHAR(200) DEFAULT '',
            base_price DECIMAL(12,2) DEFAULT 0,
            unlock_level INT DEFAULT 1,
            upgrade_rate DECIMAL(5,2) DEFAULT 0.3
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        // 商城商品初始数据
        $cnt = $pdo->query("SELECT COUNT(*) FROM ent_enterprise_items")->fetchColumn();
        if ($cnt == 0) {
            $items = [
                ['装修', '办公装修', '提升场地面积，每级+50㎡，提升客户好感', 500000, 1, 0.3],
                ['网络', '网络设施', '提升订单处理速度，每级-5%时间', 100000, 1, 0.2],
                ['桌椅', '办公桌椅', '提升员工舒适度，每级+5%满意度', 200000, 1, 0.25],
                ['设备', '生产设备', '提升产能，每级+20%产出', 800000, 1, 0.4],
            ];
            $si = $pdo->prepare('INSERT INTO ent_enterprise_items (category, name, description, base_price, unlock_level, upgrade_rate) VALUES (?,?,?,?,?,?)');
            foreach ($items as $it) $si->execute($it);
        }
    }

    // ==================== 企业等级信息 ====================
    private function getLevelInfo(int $level): array {
        $levels = [
            1 => ['name' => '创业公司', 'emp_limit' => 10, 'revenue_req' => 0, 'asset_req' => 0, 'order_req' => 0, 'rd_req' => 0, 'area_req' => 0],
            2 => ['name' => '微小企业', 'emp_limit' => 25, 'revenue_req' => 0, 'asset_req' => 2000000, 'order_req' => 10, 'rd_req' => 0, 'area_req' => 0],
            3 => ['name' => '小型企业', 'emp_limit' => 50, 'revenue_req' => 0, 'asset_req' => 5000000, 'order_req' => 50, 'rd_req' => 1, 'area_req' => 0],
            4 => ['name' => '中型企业', 'emp_limit' => 100, 'revenue_req' => 0, 'asset_req' => 20000000, 'order_req' => 200, 'rd_req' => 5, 'area_req' => 100],
            5 => ['name' => '大型企业', 'emp_limit' => 200, 'revenue_req' => 0, 'asset_req' => 100000000, 'order_req' => 1000, 'rd_req' => 20, 'area_req' => 500],
            6 => ['name' => '集团企业', 'emp_limit' => 500, 'revenue_req' => 0, 'asset_req' => 1000000000, 'order_req' => 5000, 'rd_req' => 50, 'area_req' => 2000],
            7 => ['name' => '全球企业', 'emp_limit' => 1000, 'revenue_req' => 0, 'asset_req' => 10000000000, 'order_req' => 20000, 'rd_req' => 100, 'area_req' => 10000],
        ];
        return $levels[$level] ?? $levels[1];
    }

    // ==================== 获取企业数据 ====================
    private function getCompany(int $userId): ?array {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT * FROM ent_companies WHERE user_id = ? AND is_bankrupt = 0 LIMIT 1');
        $stmt->execute([$userId]);
        return $stmt->fetch(\PDO::FETCH_ASSOC) ?: null;
    }

    // ==================== 主页面 ====================
    public function index(): void {
        $uid = $this->requireLogin();
        $company = $this->getCompany($uid);
        if (!$company) {
            // 检查是否有破产公司
            $pdo = Database::getConnection();
            $stmt = $pdo->prepare('SELECT * FROM ent_companies WHERE user_id = ? AND is_bankrupt = 1 LIMIT 1');
            $stmt->execute([$uid]);
            $bankrupt = $stmt->fetch(\PDO::FETCH_ASSOC);
            if ($bankrupt) {
                $this->render('enterprise/register', [
                    'pageTitle' => '企业破产',
                    'bankrupt' => $bankrupt,
                ]);
                return;
            }
            $this->render('enterprise/register', ['pageTitle' => '注册企业', 'bankrupt' => null]);
            return;
        }
        $pdo = Database::getConnection();
        $this->autoSettle($company);

        // 刷新企业数据
        $company = $this->getCompany($uid);
        $levelInfo = $this->getLevelInfo((int)$company['level']);
        $company['level_name'] = $levelInfo['name'];
        $company['emp_limit'] = $levelInfo['emp_limit'];

        // 模拟时间转换
        $years = floor($company['sim_time'] / 365);
        $months = floor(($company['sim_time'] % 365) / 30);
        $days = $company['sim_time'] % 30;

        $employees = $pdo->prepare('SELECT * FROM ent_employees WHERE company_id = ? AND is_active = 1 ORDER BY FIELD(grade,"S","A","B","C"), id');
        $employees->execute([$company['id']]);
        $emps = $employees->fetchAll(\PDO::FETCH_ASSOC);

        $assets = $pdo->prepare('SELECT * FROM ent_assets WHERE company_id = ? ORDER BY category');
        $assets->execute([$company['id']]);
        $assetList = $assets->fetchAll(\PDO::FETCH_ASSOC);

        $finLog = $pdo->prepare('SELECT * FROM ent_finance WHERE company_id = ? ORDER BY id DESC LIMIT 30');
        $finLog->execute([$company['id']]);
        $finList = $finLog->fetchAll(\PDO::FETCH_ASSOC);

        $orders = $pdo->prepare('SELECT * FROM ent_company_orders WHERE company_id = ? AND status IN ("pending","in_progress") ORDER BY FIELD(type,"vip","urgent","normal"), id');
        $orders->execute([$company['id']]);
        $orderList = $orders->fetchAll(\PDO::FETCH_ASSOC);

        $prodLines = $pdo->prepare('SELECT * FROM ent_production_lines WHERE company_id = ? ORDER BY id');
        $prodLines->execute([$company['id']]);
        $lines = $prodLines->fetchAll(\PDO::FETCH_ASSOC);

        $products = $pdo->prepare('SELECT * FROM ent_products WHERE company_id = ? ORDER BY id');
        $products->execute([$company['id']]);
        $prodList = $products->fetchAll(\PDO::FETCH_ASSOC);

        $loans = $pdo->prepare('SELECT * FROM ent_company_loans WHERE company_id = ? AND remaining > 0 ORDER BY id');
        $loans->execute([$company['id']]);
        $loanList = $loans->fetchAll(\PDO::FETCH_ASSOC);

        $rd = $pdo->prepare('SELECT * FROM ent_rd_projects WHERE company_id = ? ORDER BY id DESC');
        $rd->execute([$company['id']]);
        $rdList = $rd->fetchAll(\PDO::FETCH_ASSOC);

        $stores = $pdo->prepare('SELECT * FROM ent_stores WHERE company_id = ? AND is_active = 1 ORDER BY id');
        $stores->execute([$company['id']]);
        $storeList = $stores->fetchAll(\PDO::FETCH_ASSOC);

        $crisis = $pdo->prepare('SELECT * FROM ent_crisis WHERE company_id = ? AND is_active = 1 ORDER BY id');
        $crisis->execute([$company['id']]);
        $crisisList = $crisis->fetchAll(\PDO::FETCH_ASSOC);

        $rdEmpStmt = $pdo->prepare('SELECT COUNT(*) FROM ent_employees WHERE company_id = ? AND department = "研发部" AND is_active = 1');
        $rdEmpStmt->execute([$company['id']]);
        $rdEmpCount = (int)$rdEmpStmt->fetchColumn();

        // 个人股票账户余额
        $accStmt = $pdo->prepare('SELECT balance FROM ent_accounts WHERE user_id = ?');
        $accStmt->execute([$uid]);
        $personalBalance = $accStmt->fetchColumn() ?: 0;

        // 升级条件状态
        $upgradeStatus = $this->getUpgradeStatus($company);

        // 部门列表
        $departments = ['订单部', '生产部', '采购部', '研发部', '市场部', '财务部', '人事部', '技术部', '客服部', '法务部', '战略投资部'];
        $deptUnlock = [
            '订单部' => 1, '生产部' => 1, '采购部' => 1,
            '研发部' => 2, '市场部' => 2,
            '财务部' => 3, '人事部' => 3,
            '技术部' => 4, '客服部' => 4,
            '法务部' => 5, '战略投资部' => 6,
        ];

        $this->render('enterprise/index', [
            'pageTitle' => '我的企业',
            'company' => $company,
            'years' => $years, 'months' => $months, 'days' => $days,
            'employees' => $emps, 'assets' => $assetList,
            'finList' => $finList, 'orderList' => $orderList,
            'prodLines' => $lines, 'prodList' => $prodList,
            'loanList' => $loanList, 'rdList' => $rdList,
            'storeList' => $storeList, 'crisisList' => $crisisList,
            'departments' => $departments, 'deptUnlock' => $deptUnlock,
            'rdPool' => $this->getRdPool(), 'rdEmpCount' => $rdEmpCount,
            'personalBalance' => $personalBalance, 'upgradeStatus' => $upgradeStatus,
        ]);
    }

    // ==================== 注册 ====================
    public function register(): void {
        $uid = $this->requireLogin();
        $pdo = Database::getConnection();

        // 检查是否有活跃公司
        $exist = $pdo->prepare('SELECT * FROM ent_companies WHERE user_id = ? LIMIT 1');
        $exist->execute([$uid]);
        $existing = $exist->fetch(\PDO::FETCH_ASSOC);

        if ($existing && !$existing['is_bankrupt']) {
            $this->json(['ok' => false, 'error' => '你已注册企业，请刷新页面']);
        }

        // 如果是破产公司，重新激活
        if ($existing && $existing['is_bankrupt']) {
            $pdo->prepare('UPDATE ent_companies SET is_bankrupt = 0 WHERE user_id = ?')->execute([$uid]);
            $this->json(['ok' => true, 'message' => '企业已恢复运营！（原公司：' . htmlspecialchars($existing['name']) . '）']);
            return;
        }

        $name = trim($_POST['name'] ?? '');
        if (mb_strlen($name) < 2 || mb_strlen($name) > 20) {
            $this->json(['ok' => false, 'error' => '公司名称需2-20个字符']);
        }
        if (preg_match('/[<>\/\\\\]/u', $name)) {
            $this->json(['ok' => false, 'error' => '公司名称包含非法字符']);
        }

        $stmt = $pdo->prepare('SELECT id FROM ent_companies WHERE name = ? AND is_bankrupt = 0');
        $stmt->execute([$name]);
        if ($stmt->fetch()) {
            $this->json(['ok' => false, 'error' => '该公司名称已被使用']);
        }

        // 检查用户资金
        $accStmt = $pdo->prepare('SELECT * FROM ent_accounts WHERE user_id = ?');
        $accStmt->execute([$uid]);
        $account = $accStmt->fetch(\PDO::FETCH_ASSOC);
        $balance = $account ? (float)$account['balance'] : 0;

        if ($balance < 1000000) {
            $this->json(['ok' => false, 'error' => '股票账户可用资金不足100万，无法注册']);
        }

        $pdo->beginTransaction();
        try {
            // 扣除资金
            if ($account) {
                $upd = $pdo->prepare('UPDATE ent_accounts SET balance = balance - 1000000 WHERE user_id = ?');
                $upd->execute([$uid]);
            }
            // 创建企业
            $ins = $pdo->prepare('INSERT INTO ent_companies (user_id, name, capital, balance, level, sim_time, last_settle_at, last_payday) VALUES (?, ?, 1000000, 1000000, 1, 0, NOW(), 0)');
            $ins->execute([$uid, $name]);
            $companyId = $pdo->lastInsertId();

            // 赠送初始资产：装修1级 + 设备1级
            $pdo->prepare('INSERT INTO ent_assets (company_id, category, level, effect_bonus) VALUES (?, "装修", 1, 50)')->execute([$companyId]);
            $pdo->prepare('INSERT INTO ent_assets (company_id, category, level, effect_bonus) VALUES (?, "设备", 1, 1.0)')->execute([$companyId]);

            // 初始产线
            $pdo->prepare('INSERT INTO ent_production_lines (company_id, name, status) VALUES (?, "1号产线", "idle")')->execute([$companyId]);

            // 赠送8个基础产品（可用于接单生产）
            $baseProducts = [
                ['办公文具套装', 40, 5000],
                ['定制笔记本', 35, 8000],
                ['企业名片印刷', 30, 3000],
                ['节日礼盒', 45, 15000],
                ['电子配件包', 50, 12000],
                ['日用清洁套装', 35, 6000],
                ['宣传画册', 40, 4000],
                ['会员卡定制', 30, 2000],
            ];
            $prodStmt = $pdo->prepare('INSERT INTO ent_products (company_id, name, quality, base_price) VALUES (?, ?, ?, ?)');
            foreach ($baseProducts as $bp) {
                $prodStmt->execute([$companyId, $bp[0], $bp[1], $bp[2]]);
            }

            $pdo->prepare('INSERT INTO ent_finance (company_id, type, category, amount, balance_after, sim_day) VALUES (?, "income", "注册资本", 1000000, 1000000, 0)')->execute([$companyId]);

            $pdo->commit();
            $this->json(['ok' => true, 'message' => '企业注册成功！']);
        } catch (\Throwable $e) {
            $pdo->rollBack();
            $this->json(['ok' => false, 'error' => '注册失败：' . $e->getMessage()]);
        }
    }

    // ==================== API 统一入口 ====================
    public function api(): void {
        $uid = $this->requireLogin();
        $action = $_POST['action'] ?? '';
        // register 是独立公开方法，不走 apiXxx 模式
        if ($action === 'register') {
            $this->register();
            return;
        }
        $method = 'api' . ucfirst($action);
        if (method_exists($this, $method)) {
            $this->$method($uid);
        } else {
            $this->json(['ok' => false, 'error' => '未知操作']);
        }
    }

    // ==================== 自动结算 ====================
    private function autoSettle(array &$company): void {
        $pdo = Database::getConnection();
        $speed = (int)$company['speed'];
        $lastSettle = $company['last_settle_at'] ? strtotime($company['last_settle_at']) : strtotime($company['created_at']);
        $now = time();
        $realSeconds = max(0, $now - $lastSettle);

        if ($realSeconds < 60) return; // 至少1分钟才结算

        // 模拟度过天数 = 实际秒数/60 × 倍速
        // 1倍速: 1分钟=1天 → 每60秒1天
        // 2倍速: 30秒=1天 → 每30秒1天
        // 5倍速: 12秒=1天 → 每12秒1天
        $daysPerRealSecond = $speed / 60.0;
        $passedDays = (int)($realSeconds * $daysPerRealSecond);

        if ($passedDays <= 0) return;

        $newSimTime = $company['sim_time'] + $passedDays;
        $oldSimTime = $company['sim_time'];

        // 逐日结算
        for ($d = 1; $d <= $passedDays; $d++) {
            $currentDay = $oldSimTime + $d;
            $this->dailySettle($company, $currentDay);
        }

        // 更新企业时间
        $upd = $pdo->prepare('UPDATE ent_companies SET sim_time = ?, last_settle_at = NOW() WHERE id = ?');
        $upd->execute([$newSimTime, $company['id']]);
        $company['sim_time'] = $newSimTime;
        $company['last_settle_at'] = date('Y-m-d H:i:s');
    }

    private function dailySettle(array &$company, int $simDay): void {
        $pdo = Database::getConnection();
        $cid = $company['id'];
        $companyData = $pdo->query("SELECT * FROM ent_companies WHERE id = $cid")->fetch(\PDO::FETCH_ASSOC);
        if (!$companyData) return;
        $company = $companyData;

        // 1. 订单进度推进
        $orders = $pdo->prepare('SELECT * FROM ent_company_orders WHERE company_id = ? AND status = "in_progress"');
        $orders->execute([$cid]);
        foreach ($orders->fetchAll(\PDO::FETCH_ASSOC) as $order) {
            $line = $pdo->prepare('SELECT * FROM ent_production_lines WHERE company_id = ? AND current_order_id = ?');
            $line->execute([$cid, $order['id']]);
            $lineData = $line->fetch(\PDO::FETCH_ASSOC);
            if ($lineData && $lineData['status'] === 'busy') {
                $dailyProgress = (int)(20 * $lineData['efficiency']); // 每天推进20%基础
                $newProgress = min(100, $order['progress'] + $dailyProgress);
                $pdo->prepare('UPDATE ent_company_orders SET progress = ? WHERE id = ?')->execute([$newProgress, $order['id']]);

                if ($newProgress >= 100) {
                    // 订单完成
                    $pdo->prepare('UPDATE ent_company_orders SET status = "completed", progress = 100 WHERE id = ?')->execute([$order['id']]);
                    $pdo->prepare('UPDATE ent_production_lines SET status = "idle", current_order_id = NULL, progress = 0 WHERE id = ?')->execute([$lineData['id']]);
                    // 收入
                    $revenue = (float)$order['total_amount'];
                    $pdo->prepare('UPDATE ent_companies SET balance = balance + ?, total_revenue = total_revenue + ?, total_orders_completed = total_orders_completed + 1 WHERE id = ?')->execute([$revenue, $revenue, $cid]);
                    $pdo->prepare('INSERT INTO ent_finance (company_id, type, category, amount, balance_after, sim_day) VALUES (?, "income", "订单完成", ?, (SELECT balance FROM ent_companies WHERE id = ?), ?)')->execute([$cid, $revenue, $cid, $simDay]);
                }
            }
        }

        // 2. 检查超时订单
        $expired = $pdo->prepare('UPDATE ent_company_orders SET status = "cancelled" WHERE company_id = ? AND status IN ("pending","in_progress") AND (? - sim_day_created) > deadline');
        $expired->execute([$cid, $simDay]);

        // 3. 店铺营收
        $stores = $pdo->prepare('SELECT * FROM ent_stores WHERE company_id = ? AND is_active = 1');
        $stores->execute([$cid]);
        foreach ($stores->fetchAll(\PDO::FETCH_ASSOC) as $store) {
            $dailyRev = (float)$store['daily_revenue'];
            if ($dailyRev > 0) {
                $pdo->prepare('UPDATE ent_companies SET balance = balance + ?, total_revenue = total_revenue + ? WHERE id = ?')->execute([$dailyRev, $dailyRev, $cid]);
                $pdo->prepare('INSERT INTO ent_finance (company_id, type, category, amount, balance_after, sim_day) VALUES (?, "income", "店铺营收", ?, (SELECT balance FROM ent_companies WHERE id = ?), ?)')->execute([$cid, $dailyRev, $cid, $simDay]);
            }
        }

        // 4. 研发进度推进
        $rdProjects = $pdo->prepare('SELECT * FROM ent_rd_projects WHERE company_id = ? AND status = "researching"');
        $rdProjects->execute([$cid]);
        foreach ($rdProjects->fetchAll(\PDO::FETCH_ASSOC) as $rd) {
            $rdDeptEmps = $pdo->prepare('SELECT COUNT(*) FROM ent_employees WHERE company_id = ? AND department = "研发部" AND is_active = 1');
            $rdDeptEmps->execute([$cid]);
            $rdEmpCount = (int)$rdDeptEmps->fetchColumn();
            $basePerDay = max(1, round(100 / max(1, (int)$rd['research_days']))); // 基础每天进度
            $rdProgressPerDay = max(1, $basePerDay + ($rdEmpCount * 3)); // 每名研发员工+3%
            $newRd = min(100, $rd['progress'] + $rdProgressPerDay);
            $pdo->prepare('UPDATE ent_rd_projects SET progress = ? WHERE id = ?')->execute([$newRd, $rd['id']]);
            if ($newRd >= 100) {
                $pdo->prepare('UPDATE ent_rd_projects SET status = "completed", progress = 100 WHERE id = ?')->execute([$rd['id']]);
                $pdo->prepare('UPDATE ent_companies SET total_rd_count = total_rd_count + 1 WHERE id = ?')->execute([$cid]);
                // 自动创建产品
                $prodName = $rd['name'] . '（研发品）';
                $quality = (int)$rd['quality'];
                $basePrice = 10000 + ($quality * 200);
                $pdo->prepare('INSERT INTO ent_products (company_id, name, quality, base_price) VALUES (?, ?, ?, ?)')->execute([$cid, $prodName, $quality, $basePrice]);
            }
        }

        // 5. 自动刷新订单（每天概率生成）
        if (mt_rand(1, 100) <= 40) { // 40%概率每日有新订单
            $products = $pdo->prepare('SELECT * FROM ent_products WHERE company_id = ? ORDER BY RAND() LIMIT 1');
            $products->execute([$cid]);
            $prod = $products->fetch(\PDO::FETCH_ASSOC);
            if ($prod) {
                $types = ['normal', 'normal', 'normal', 'urgent', 'vip'];
                $type = $types[array_rand($types)];
                $qty = $type === 'vip' ? mt_rand(5, 20) : ($type === 'urgent' ? mt_rand(2, 5) : mt_rand(1, 3));
                $deadline = $type === 'urgent' ? 1 : ($type === 'vip' ? 5 : 3);
                $unitPrice = (float)$prod['base_price'] * ($type === 'urgent' ? 1.3 : ($type === 'vip' ? 1.5 : 1.0));
                $total = $unitPrice * $qty;
                $clients = ['张三', '李四', '王五', '赵六', '钱七', '孙八', '周九', '吴十', '郑十一', '陈总'];
                $client = $clients[array_rand($clients)];
                $pdo->prepare('INSERT INTO ent_company_orders (company_id, client_name, product_id, product_name, quantity, unit_price, total_amount, type, deadline, sim_day_created) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)')->execute([$cid, $client, $prod['id'], $prod['name'], $qty, round($unitPrice, 2), round($total, 2), $type, $deadline, $simDay]);
            }
        }

        // 6. 检查发薪日（每30天）
        if ($simDay - $company['last_payday'] >= 30) {
            $this->paySalaries($cid, $simDay);
            $pdo->prepare('UPDATE ent_companies SET last_payday = ? WHERE id = ?')->execute([$simDay, $cid]);

            // 月度劳动风险检查
            $this->checkLaborRisk($cid, $simDay);

            // 月度贷款利息
            $this->processLoanInterest($cid, $simDay);

            // 月度随机危机事件
            $this->randomCrisis($cid, $simDay);
        }

        // 7. 检查升级条件
        $this->checkLevelUp($cid);

        // 8. 检查破产
        $this->checkBankruptcy($cid);
    }

    private function paySalaries(int $cid, int $simDay): void {
        $pdo = Database::getConnection();
        $emps = $pdo->prepare('SELECT * FROM ent_employees WHERE company_id = ? AND is_active = 1');
        $emps->execute([$cid]);
        $allEmps = $emps->fetchAll(\PDO::FETCH_ASSOC);
        $empCount = count($allEmps);
        $totalSalary = 0;
        foreach ($allEmps as $emp) {
            $totalSalary += (float)$emp['salary'];
        }

        $comp = $pdo->prepare('SELECT * FROM ent_companies WHERE id = ?');
        $comp->execute([$cid]);
        $companyData = $comp->fetch(\PDO::FETCH_ASSOC);

        // 获取资产等级
        $assets = $pdo->prepare('SELECT * FROM ent_assets WHERE company_id = ?');
        $assets->execute([$cid]);
        $assetMap = [];
        foreach ($assets->fetchAll(\PDO::FETCH_ASSOC) as $a) {
            $assetMap[$a['category']] = (int)$a['level'];
        }
        $decoLv = $assetMap['装修'] ?? 0;
        $netLv = $assetMap['网络'] ?? 0;
        $deskLv = $assetMap['桌椅'] ?? 0;
        $equipLv = $assetMap['设备'] ?? 0;

        // 运营成本（月）
        $rentCost = $decoLv * 25000;               // 场地租金: 装修每级50㎡ × 500/㎡
        $utilityCost = max(5000, $empCount * 300);   // 水电费: 每员工300/月，最低5000
        $netCost = $netLv * 10000;                   // 网费: 网络每级统一
        $maintainCost = $equipLv * 50000;            // 设备维护: 设备每级5万
        $depreCost = $deskLv * 10000;                // 桌椅折旧: 每级1万

        // 福利成本
        $welfareCost = 0;
        if ($companyData['social_insurance']) $welfareCost += $empCount * 2000;
        if ($companyData['housing_fund']) $welfareCost += $empCount * 300;
        if ($companyData['canteen']) $welfareCost += $empCount * 500;
        if ($companyData['dormitory']) $welfareCost += $empCount * 800;
        if ($companyData['transport']) $welfareCost += $empCount * 200;

        // 节日福利（每季度 = 每90天）
        $holidayCost = 0;
        if ($simDay > 0 && $simDay % 90 < 30 && $companyData['holiday_bonus']) {
            $holidayCost = $empCount * 300;
        }

        $totalCost = $totalSalary + $welfareCost + $holidayCost + $rentCost + $utilityCost + $netCost + $maintainCost + $depreCost;
        if ($totalCost <= 0) return;

        $pdo->prepare("UPDATE ent_companies SET balance = balance - ? WHERE id = ?")->execute([$totalCost, $cid]);
        $bal = $pdo->prepare("SELECT balance FROM ent_companies WHERE id = ?");
        $bal->execute([$cid]);
        $balance = $bal->fetchColumn();

        // 逐项记录财务
        $finEntries = [
            ['薪资支出', $totalSalary],
            ['福利支出', $welfareCost + $holidayCost],
            ['场地租金', $rentCost],
            ['水电杂费', $utilityCost],
            ['网络费用', $netCost],
            ['设备维护', $maintainCost],
            ['桌椅折旧', $depreCost],
        ];
        foreach ($finEntries as $entry) {
            if ($entry[1] > 0) {
                $pdo->prepare('INSERT INTO ent_finance (company_id, type, category, amount, balance_after, sim_day) VALUES (?, "expense", ?, ?, ?, ?)')->execute([$cid, $entry[0], $entry[1], $balance, $simDay]);
            }
        }
    }

    private function checkLaborRisk(int $cid, int $simDay): void {
        $pdo = Database::getConnection();
        $comp = $pdo->query("SELECT * FROM ent_companies WHERE id = $cid")->fetch(\PDO::FETCH_ASSOC);
        $risk = (int)$comp['labor_risk'];
        if (!$comp['social_insurance'] || !$comp['housing_fund']) {
            $risk = min(100, $risk + 20);
        } else {
            $risk = max(0, $risk - 5);
        }
        $pdo->prepare('UPDATE ent_companies SET labor_risk = ? WHERE id = ?')->execute([$risk, $cid]);

        if ($risk >= 40 && $risk < 70) {
            $pdo->prepare("UPDATE ent_companies SET balance = balance - 50000 WHERE id = ?")->execute([$cid]);
            $bal = $pdo->query("SELECT balance FROM ent_companies WHERE id = $cid")->fetchColumn();
            $pdo->prepare('INSERT INTO ent_finance (company_id, type, category, amount, balance_after, sim_day) VALUES (?, "expense", "劳动风险罚款", 50000, ?, ?)')->execute([$cid, $bal, $simDay]);
        } elseif ($risk >= 70 && $risk < 100) {
            $pdo->prepare("UPDATE ent_companies SET balance = balance - 150000 WHERE id = ?")->execute([$cid]);
            $bal = $pdo->query("SELECT balance FROM ent_companies WHERE id = $cid")->fetchColumn();
            $pdo->prepare('INSERT INTO ent_finance (company_id, type, category, amount, balance_after, sim_day) VALUES (?, "expense", "劳动风险罚款(翻倍)", 150000, ?, ?)')->execute([$cid, $bal, $simDay]);
        } elseif ($risk >= 100) {
            // 停业整顿 - 后续可通过恢复按钮解除
            $pdo->prepare('INSERT INTO ent_crisis (company_id, event_name, effect_desc, start_day, end_day) VALUES (?, "停业整顿", "所有生产/销售暂停，请开启社保和公积金后点击恢复运营", ?, ?)')->execute([$cid, $simDay, $simDay + 9999]);
        }
    }

    private function processLoanInterest(int $cid, int $simDay): void {
        $pdo = Database::getConnection();
        $loans = $pdo->prepare('SELECT * FROM ent_company_loans WHERE company_id = ? AND remaining > 0');
        $loans->execute([$cid]);
        foreach ($loans->fetchAll(\PDO::FETCH_ASSOC) as $loan) {
            $interest = round((float)$loan['amount'] * (float)$loan['rate'], 2);
            $principal = (float)$loan['monthly_payment'];
            $totalDue = $interest + $principal;
            $pdo->prepare("UPDATE ent_companies SET balance = balance - ? WHERE id = ?")->execute([$totalDue, $cid]);
            $pdo->prepare("UPDATE ent_company_loans SET remaining = remaining - 1 WHERE id = ?")->execute([$loan['id']]);
            $bal = $pdo->query("SELECT balance FROM ent_companies WHERE id = $cid")->fetchColumn();
            $pdo->prepare('INSERT INTO ent_finance (company_id, type, category, amount, balance_after, sim_day) VALUES (?, "expense", "贷款月供", ?, ?, ?)')->execute([$cid, $totalDue, $bal, $simDay]);
        }
    }

    private function randomCrisis(int $cid, int $simDay): void {
        $pdo = Database::getConnection();
        $roll = mt_rand(1, 100);
        $events = [
            ['prob' => 15, 'name' => '原材料涨价', 'desc' => '采购成本+30%（持续15天）'],
            ['prob' => 8, 'name' => '核心员工离职', 'desc' => '随机一名A/S级员工流失'],
            ['prob' => 10, 'name' => '税务稽查', 'desc' => '罚款50~200万'],
            ['prob' => 12, 'name' => '市场低迷', 'desc' => '订单量减少30%（持续30天）'],
            ['prob' => 10, 'name' => '设备故障', 'desc' => '随机产线停产5天'],
        ];

        $cumulative = 0;
        foreach ($events as $ev) {
            $cumulative += $ev['prob'];
            if ($roll <= $cumulative) {
                $duration = 15; // 默认
                if ($ev['name'] === '核心员工离职') {
                    // 随机移除一名A/S级员工
                    $emp = $pdo->prepare("SELECT id FROM ent_employees WHERE company_id = ? AND grade IN ('A','S') AND is_active = 1 ORDER BY RAND() LIMIT 1");
                    $emp->execute([$cid]);
                    $target = $emp->fetch(\PDO::FETCH_ASSOC);
                    if ($target) {
                        $pdo->prepare('UPDATE ent_employees SET is_active = 0 WHERE id = ?')->execute([$target['id']]);
                    }
                } elseif ($ev['name'] === '税务稽查') {
                    $fine = mt_rand(500000, 2000000);
                    $pdo->prepare("UPDATE ent_companies SET balance = balance - ? WHERE id = ?")->execute([$fine, $cid]);
                    $bal = $pdo->query("SELECT balance FROM ent_companies WHERE id = $cid")->fetchColumn();
                    $pdo->prepare('INSERT INTO ent_finance (company_id, type, category, amount, balance_after, sim_day) VALUES (?, "expense", "税务罚款", ?, ?, ?)')->execute([$cid, $fine, $bal, $simDay]);
                } elseif ($ev['name'] === '设备故障') {
                    $duration = 5;
                    $line = $pdo->prepare("SELECT id FROM ent_production_lines WHERE company_id = ? AND status = 'busy' ORDER BY RAND() LIMIT 1");
                    $line->execute([$cid]);
                    $target = $line->fetch(\PDO::FETCH_ASSOC);
                    if ($target) {
                        $pdo->prepare('UPDATE ent_production_lines SET status = "broken" WHERE id = ?')->execute([$target['id']]);
                    }
                }
                // 市场低迷设为30天
                if ($ev['name'] === '市场低迷') $duration = 30;

                $pdo->prepare('INSERT INTO ent_crisis (company_id, event_name, effect_desc, start_day, end_day) VALUES (?, ?, ?, ?, ?)')->execute([$cid, $ev['name'], $ev['desc'], $simDay, $simDay + $duration]);
                break;
            }
        }

        // 清理过期危机
        $pdo->prepare('UPDATE ent_crisis SET is_active = 0 WHERE company_id = ? AND end_day <= ? AND is_active = 1')->execute([$cid, $simDay]);
    }

    private function checkLevelUp(int $cid): void {
        $pdo = Database::getConnection();
        $comp = $pdo->query("SELECT * FROM ent_companies WHERE id = $cid")->fetch(\PDO::FETCH_ASSOC);
        $level = (int)$comp['level'];
        if ($level >= 7) return;

        $nextLevel = $this->getLevelInfo($level + 1);

        // 计算总资产
        $assets = $pdo->query("SELECT SUM(effect_bonus) FROM ent_assets WHERE company_id = $cid")->fetchColumn();
        $totalAsset = (float)$comp['balance'] + (float)$comp['capital'] + (float)$assets;

        // 场地面积
        $deco = $pdo->query("SELECT SUM(effect_bonus) FROM ent_assets WHERE company_id = $cid AND category = '装修'")->fetchColumn();
        $area = (float)$deco;

        // 检查条件
        $canUpgrade = true;
        $reasons = [];
        if ($totalAsset < $nextLevel['asset_req']) { $canUpgrade = false; $reasons[] = '资产不足'; }
        if ($nextLevel['order_req'] > 0 && (int)$comp['total_orders_completed'] < $nextLevel['order_req']) { $canUpgrade = false; $reasons[] = '完成订单数不足'; }
        if ($nextLevel['rd_req'] > 0 && (int)$comp['total_rd_count'] < $nextLevel['rd_req']) { $canUpgrade = false; $reasons[] = '研发数不足'; }
        if ($nextLevel['area_req'] > 0 && $area < $nextLevel['area_req']) { $canUpgrade = false; $reasons[] = '场地面积不足'; }
        // 员工人数检查
        $empCount = (int)$pdo->query("SELECT COUNT(*) FROM ent_employees WHERE company_id = $cid AND is_active = 1")->fetchColumn();
        if ($level == 1 && $empCount < 5) { $canUpgrade = false; $reasons[] = '员工不足5人'; }
        if ($level == 2 && $empCount < 20) { $canUpgrade = false; $reasons[] = '员工不足20人'; }
        if ($level == 3 && $empCount < 50) { $canUpgrade = false; $reasons[] = '员工不足50人'; }
        if ($level == 4 && $empCount < 200) { $canUpgrade = false; $reasons[] = '员工不足200人'; }
        if ($level == 5 && $empCount < 1000) { $canUpgrade = false; $reasons[] = '员工不足1000人'; }
        if ($level == 6 && $empCount < 5000) { $canUpgrade = false; $reasons[] = '员工不足5000人'; }

        if ($canUpgrade) {
            $pdo->prepare('UPDATE ent_companies SET level = level + 1 WHERE id = ?')->execute([$cid]);
        }
    }

    // 获取升级条件状态（供前端展示）
    private function getUpgradeStatus(array $company): array {
        $pdo = Database::getConnection();
        $cid = (int)$company['id'];
        $level = (int)$company['level'];
        if ($level >= 7) return ['can_upgrade' => false, 'next_level' => null, 'conditions' => [], 'reached' => true];

        $nextInfo = $this->getLevelInfo($level + 1);
        $nextName = $nextInfo['name'];

        $stmt = $pdo->prepare('SELECT COALESCE(SUM(effect_bonus),0) FROM ent_assets WHERE company_id = ?');
        $stmt->execute([$cid]);
        $assetsVal = (float)$stmt->fetchColumn();

        $totalAsset = (float)$company['balance'] + (float)$company['capital'] + $assetsVal;

        $stmt2 = $pdo->prepare("SELECT COALESCE(SUM(effect_bonus),0) FROM ent_assets WHERE company_id = ? AND category = '装修'");
        $stmt2->execute([$cid]);
        $area = (float)$stmt2->fetchColumn();

        $orderCount = (int)$company['total_orders_completed'];
        $rdCount = (int)$company['total_rd_count'];

        $stmt3 = $pdo->prepare('SELECT COUNT(*) FROM ent_employees WHERE company_id = ? AND is_active = 1');
        $stmt3->execute([$cid]);
        $empCount = (int)$stmt3->fetchColumn();

        $empReqs = [1 => 5, 2 => 20, 3 => 50, 4 => 200, 5 => 1000, 6 => 5000];

        $conditions = [];
        if ($nextInfo['asset_req'] > 0) $conditions[] = ['label' => '总资产 ≥ ¥' . number_format($nextInfo['asset_req']), 'met' => $totalAsset >= $nextInfo['asset_req'], 'current' => '¥' . number_format($totalAsset)];
        if ($nextInfo['order_req'] > 0) $conditions[] = ['label' => '完成订单 ≥ ' . $nextInfo['order_req'], 'met' => $orderCount >= $nextInfo['order_req'], 'current' => $orderCount . '单'];
        if ($nextInfo['rd_req'] > 0) $conditions[] = ['label' => '研发成果 ≥ ' . $nextInfo['rd_req'], 'met' => $rdCount >= $nextInfo['rd_req'], 'current' => $rdCount . '项'];
        if ($nextInfo['area_req'] > 0) $conditions[] = ['label' => '场地面积 ≥ ' . $nextInfo['area_req'] . '㎡', 'met' => $area >= $nextInfo['area_req'], 'current' => $area . '㎡'];
        if (isset($empReqs[$level])) $conditions[] = ['label' => '员工 ≥ ' . $empReqs[$level] . '人', 'met' => $empCount >= $empReqs[$level], 'current' => $empCount . '人'];

        $allMet = true;
        foreach ($conditions as $c) { if (!$c['met']) { $allMet = false; break; } }

        return [
            'can_upgrade' => $allMet,
            'next_level' => $nextName,
            'next_lv' => $level + 1,
            'conditions' => $conditions,
            'reached' => false,
        ];
    }

    private function checkBankruptcy(int $cid): void {
        $pdo = Database::getConnection();
        $comp = $pdo->query("SELECT * FROM ent_companies WHERE id = $cid")->fetch(\PDO::FETCH_ASSOC);
        if ((float)$comp['balance'] < 0) {
            // 尝试变卖资产
            $assets = $pdo->query("SELECT SUM(level * 50000) as total FROM ent_assets WHERE company_id = $cid")->fetchColumn();
            $sellValue = round((float)$assets * 0.3, 2); // 折价30%
            if ($sellValue > 0) {
                $pdo->prepare("UPDATE ent_companies SET balance = balance + ? WHERE id = ?")->execute([$sellValue, $cid]);
                $pdo->exec("DELETE FROM ent_assets WHERE company_id = $cid");
            }
            $comp2 = $pdo->query("SELECT * FROM ent_companies WHERE id = $cid")->fetch(\PDO::FETCH_ASSOC);
            if ((float)$comp2['balance'] < -1000000) {
                // 破产
                $pdo->prepare('UPDATE ent_companies SET is_bankrupt = 1 WHERE id = ?')->execute([$cid]);
                $pdo->prepare("UPDATE ent_employees SET is_active = 0 WHERE company_id = ?")->execute([$cid]);
                $pdo->prepare("UPDATE ent_company_orders SET status = 'cancelled' WHERE company_id = ? AND status IN ('pending','in_progress')")->execute([$cid]);
            }
        }
    }

    // ==================== API: 员工相关 ====================
    private function apiRecruit(int $uid): void {
        $company = $this->requireCompany($uid);
        $paid = ($_POST['paid'] ?? '0') === '1';
        $pdo = Database::getConnection();

        // 检查员工上限
        $levelInfo = $this->getLevelInfo((int)$company['level']);
        $empCount = (int)$pdo->query("SELECT COUNT(*) FROM ent_employees WHERE company_id = {$company['id']} AND is_active = 1")->fetchColumn();
        if ($empCount >= $levelInfo['emp_limit']) {
            $this->json(['ok' => false, 'error' => '员工已达上限(' . $levelInfo['emp_limit'] . '人)，请先升级企业']);
        }

        if ($paid) {
            $cost = 10000;
            if ((float)$company['balance'] < $cost) {
                $this->json(['ok' => false, 'error' => '企业资金不足，付费招聘需¥10,000']);
            }
            $pdo->prepare('UPDATE ent_companies SET balance = balance - ? WHERE id = ?')->execute([$cost, $company['id']]);
            $pdo->prepare('INSERT INTO ent_finance (company_id, type, category, amount, balance_after, sim_day) VALUES (?, "expense", "付费招聘", ?, (SELECT balance FROM ent_companies WHERE id = ?), ?)')->execute([$company['id'], $cost, $company['id'], $company['sim_time']]);
            $count = 10;
        } else {
            $count = 5;
        }

        // 生成候选人
        $grades = ['C', 'C', 'C', 'C', 'B', 'B', 'A', 'S']; // 权重
        $names = ['张伟', '李娜', '王强', '刘洋', '陈静', '杨帆', '赵敏', '钱进', '孙悦', '周明', '吴昊', '郑丽', '陈鑫', '黄磊', '林峰'];
        $candidates = [];
        for ($i = 0; $i < $count; $i++) {
            $grade = $grades[array_rand($grades)];
            $salaryMap = ['C' => 5000, 'B' => 8000, 'A' => 12000, 'S' => 20000];
            $outputMap = ['C' => 0.8, 'B' => 1.2, 'A' => 1.8, 'S' => 3.0];
            $candidates[] = [
                'name' => $names[array_rand($names)] . ($i + 1),
                'grade' => $grade,
                'salary' => $salaryMap[$grade],
                'output_mult' => $outputMap[$grade],
                'age' => mt_rand(22, 35),
                'retire_age' => 60,
            ];
        }
        $this->json(['ok' => true, 'candidates' => $candidates]);
    }

    private function apiHire(int $uid): void {
        $company = $this->requireCompany($uid);
        $pdo = Database::getConnection();

        $levelInfo = $this->getLevelInfo((int)$company['level']);
        $empCount = (int)$pdo->query("SELECT COUNT(*) FROM ent_employees WHERE company_id = {$company['id']} AND is_active = 1")->fetchColumn();
        if ($empCount >= $levelInfo['emp_limit']) {
            $this->json(['ok' => false, 'error' => '员工已达上限']);
        }

        $name = $_POST['name'] ?? '';
        $grade = $_POST['grade'] ?? 'C';
        $salary = (float)($_POST['salary'] ?? 5000);
        $outputMult = (float)($_POST['output_mult'] ?? 0.8);
        $dept = $_POST['department'] ?? '订单部';

        if (!in_array($grade, ['C', 'B', 'A', 'S'])) {
            $this->json(['ok' => false, 'error' => '无效的员工等级']);
        }

        $pdo->prepare('INSERT INTO ent_employees (company_id, name, grade, department, salary, output_mult, age, hire_day) VALUES (?, ?, ?, ?, ?, ?, 22, (SELECT sim_time FROM ent_companies WHERE id = ?))')->execute([$company['id'], $name, $grade, $dept, $salary, $outputMult, $company['id']]);

        $this->json(['ok' => true, 'message' => "成功入职：$name"]);
    }

    private function apiFire(int $uid): void {
        $company = $this->requireCompany($uid);
        $empId = (int)($_POST['emp_id'] ?? 0);
        $pdo = Database::getConnection();

        $emp = $pdo->prepare('SELECT * FROM ent_employees WHERE id = ? AND company_id = ? AND is_active = 1');
        $emp->execute([$empId, $company['id']]);
        $employee = $emp->fetch(\PDO::FETCH_ASSOC);
        if (!$employee) {
            $this->json(['ok' => false, 'error' => '员工不存在']);
        }

        // 遣散费
        $yearsServed = max(1, (int)(($company['sim_time'] - $employee['hire_day']) / 365));
        $severance = min($yearsServed, 3) * (float)$employee['salary'];

        $pdo->prepare('UPDATE ent_employees SET is_active = 0 WHERE id = ?')->execute([$empId]);
        $pdo->prepare('UPDATE ent_companies SET balance = balance - ? WHERE id = ?')->execute([$severance, $company['id']]);

        $bal = $pdo->query("SELECT balance FROM ent_companies WHERE id = {$company['id']}")->fetchColumn();
        $pdo->prepare('INSERT INTO ent_finance (company_id, type, category, amount, balance_after, sim_day) VALUES (?, "expense", "遣散费", ?, ?, ?)')->execute([$company['id'], $severance, $bal, $company['sim_time']]);

        $this->json(['ok' => true, 'message' => "已解雇 {$employee['name']}，遣散费 ¥" . number_format($severance)]);
    }

    // ==================== API: 设置部门 ====================
    private function apiSetDept(int $uid): void {
        $company = $this->requireCompany($uid);
        $empId = (int)($_POST['emp_id'] ?? 0);
        $dept = $_POST['department'] ?? '订单部';
        $pdo = Database::getConnection();

        $allowed = ['订单部', '生产部', '采购部', '研发部', '市场部', '财务部', '人事部', '技术部', '客服部', '法务部', '战略投资部'];
        if (!in_array($dept, $allowed)) {
            $this->json(['ok' => false, 'error' => '无效部门']);
        }

        // 检查部门解锁
        $deptUnlock = [
            '订单部' => 1, '生产部' => 1, '采购部' => 1,
            '研发部' => 2, '市场部' => 2,
            '财务部' => 3, '人事部' => 3,
            '技术部' => 4, '客服部' => 4,
            '法务部' => 5, '战略投资部' => 6,
        ];
        if ($deptUnlock[$dept] > (int)$company['level']) {
            $this->json(['ok' => false, 'error' => '该部门尚未解锁']);
        }

        $pdo->prepare('UPDATE ent_employees SET department = ? WHERE id = ? AND company_id = ?')->execute([$dept, $empId, $company['id']]);
        $this->json(['ok' => true, 'message' => '部门设置成功']);
    }

    // ==================== API: 商城购买 ====================
    private function apiBuyAsset(int $uid): void {
        $company = $this->requireCompany($uid);
        $category = $_POST['category'] ?? '';
        $pdo = Database::getConnection();

        $allowed = ['装修', '网络', '桌椅', '设备'];
        if (!in_array($category, $allowed)) {
            $this->json(['ok' => false, 'error' => '无效资产类别']);
        }

        // 查找当前等级
        $asset = $pdo->prepare('SELECT * FROM ent_assets WHERE company_id = ? AND category = ?');
        $asset->execute([$company['id'], $category]);
        $existing = $asset->fetch(\PDO::FETCH_ASSOC);

        $basePrices = ['装修' => 500000, '网络' => 100000, '桌椅' => 200000, '设备' => 800000];
        $upgradeRates = ['装修' => 0.3, '网络' => 0.2, '桌椅' => 0.25, '设备' => 0.4];

        $basePrice = $basePrices[$category];
        $upgradeRate = $upgradeRates[$category];

        if ($existing) {
            $newLevel = $existing['level'] + 1;
            if ($newLevel > 10) {
                $this->json(['ok' => false, 'error' => '已达最高等级(10级)']);
            }
            $cost = round($basePrice * pow(1 + $upgradeRate, $newLevel - 2));
            $effectBonus = match($category) {
                '装修' => $newLevel * 50,
                '网络' => $newLevel * 5,
                '桌椅' => $newLevel * 5,
                '设备' => $newLevel * 0.2,
                default => 0,
            };
            $pdo->prepare('UPDATE ent_assets SET level = ?, effect_bonus = ? WHERE id = ?')->execute([$newLevel, $effectBonus, $existing['id']]);
        } else {
            $newLevel = 1;
            $cost = $basePrice;
            $effectBonus = match($category) {
                '装修' => 50, '网络' => 5, '桌椅' => 5, '设备' => 1.0,
                default => 0,
            };
            $pdo->prepare('INSERT INTO ent_assets (company_id, category, level, effect_bonus) VALUES (?, ?, ?, ?)')->execute([$company['id'], $category, $newLevel, $effectBonus]);
        }

        if ((float)$company['balance'] < $cost) {
            $this->json(['ok' => false, 'error' => '企业资金不足']);
        }

        $pdo->prepare('UPDATE ent_companies SET balance = balance - ? WHERE id = ?')->execute([$cost, $company['id']]);
        $bal = $pdo->query("SELECT balance FROM ent_companies WHERE id = {$company['id']}")->fetchColumn();
        $pdo->prepare('INSERT INTO ent_finance (company_id, type, category, amount, balance_after, sim_day) VALUES (?, "expense", "购买资产", ?, ?, ?)')->execute([$company['id'], $cost, $bal, $company['sim_time']]);

        $this->json(['ok' => true, 'message' => "{$category}升级至{$newLevel}级，花费¥" . number_format($cost)]);
    }

    // ==================== API: 产线管理 ====================
    private function apiBuyProdLine(int $uid): void {
        $company = $this->requireCompany($uid);
        $pdo = Database::getConnection();
        $count = (int)$pdo->query("SELECT COUNT(*) FROM ent_production_lines WHERE company_id = {$company['id']}")->fetchColumn();
        $cost = 500000 + ($count * 200000); // 逐条递增

        if ((float)$company['balance'] < $cost) {
            $this->json(['ok' => false, 'error' => '资金不足，需要 ¥' . number_format($cost)]);
        }

        $pdo->prepare('UPDATE ent_companies SET balance = balance - ? WHERE id = ?')->execute([$cost, $company['id']]);
        $pdo->prepare('INSERT INTO ent_production_lines (company_id, name, status) VALUES (?, ?, "idle")')->execute([$company['id'], ($count + 1) . '号产线']);
        $bal = $pdo->query("SELECT balance FROM ent_companies WHERE id = {$company['id']}")->fetchColumn();
        $pdo->prepare('INSERT INTO ent_finance (company_id, type, category, amount, balance_after, sim_day) VALUES (?, "expense", "购买产线", ?, ?, ?)')->execute([$company['id'], $cost, $bal, $company['sim_time']]);

        $this->json(['ok' => true, 'message' => '新产线已购买']);
    }

    // ==================== API: 订单接单 ====================
    private function apiTakeOrder(int $uid): void {
        $company = $this->requireCompany($uid);
        $orderId = (int)($_POST['order_id'] ?? 0);
        $pdo = Database::getConnection();

        // 检查停业
        $crisis = $pdo->prepare("SELECT COUNT(*) FROM ent_crisis WHERE company_id = ? AND event_name = '停业整顿' AND is_active = 1");
        $crisis->execute([$company['id']]);
        if ($crisis->fetchColumn() > 0) {
            $this->json(['ok' => false, 'error' => '企业正在停业整顿，请先恢复运营']);
        }

        // 找空闲产线
        $line = $pdo->prepare("SELECT * FROM ent_production_lines WHERE company_id = ? AND status = 'idle' ORDER BY id LIMIT 1");
        $line->execute([$company['id']]);
        $freeLine = $line->fetch(\PDO::FETCH_ASSOC);
        if (!$freeLine) {
            $this->json(['ok' => false, 'error' => '没有空闲产线，请等待或购买新产线']);
        }

        $order = $pdo->prepare('SELECT * FROM ent_company_orders WHERE id = ? AND company_id = ? AND status = "pending"');
        $order->execute([$orderId, $company['id']]);
        $orderData = $order->fetch(\PDO::FETCH_ASSOC);
        if (!$orderData) {
            $this->json(['ok' => false, 'error' => '订单不存在或已处理']);
        }

        $pdo->beginTransaction();
        try {
            $pdo->prepare('UPDATE ent_company_orders SET status = "in_progress" WHERE id = ?')->execute([$orderId]);
            $pdo->prepare('UPDATE ent_production_lines SET status = "busy", current_order_id = ? WHERE id = ?')->execute([$orderId, $freeLine['id']]);
            $pdo->commit();
            $this->json(['ok' => true, 'message' => '已接单，' . $freeLine['name'] . '开始生产']);
        } catch (\Throwable $e) {
            $pdo->rollBack();
            $this->json(['ok' => false, 'error' => $e->getMessage()]);
        }
    }

    // ==================== API: 添加产品 ====================
    private function apiAddProduct(int $uid): void {
        $company = $this->requireCompany($uid);
        $name = trim($_POST['name'] ?? '');
        $price = (float)($_POST['price'] ?? 0);
        if (empty($name) || $price <= 0) {
            $this->json(['ok' => false, 'error' => '请填写完整信息']);
        }
        if ((float)$company['balance'] < 50000) {
            $this->json(['ok' => false, 'error' => '企业资金不足，研发至少需要¥50,000']);
        }
        $pdo = Database::getConnection();
        // 自定义产品进入研发流程，品质和研发周期根据投入预算决定
        $researchDays = $price <= 50000 ? mt_rand(15, 25) : ($price <= 100000 ? mt_rand(25, 40) : mt_rand(35, 55));
        $quality = $price <= 50000 ? mt_rand(30, 55) : ($price <= 100000 ? mt_rand(40, 70) : mt_rand(50, 85));
        $cost = max(50000, $price * 0.3); // 研发消耗 = 定价的30%
        if ($cost > (float)$company['balance']) {
            $this->json(['ok' => false, 'error' => '资金不足，研发需¥' . number_format($cost)]);
        }
        $pdo->prepare('UPDATE ent_companies SET balance = balance - ? WHERE id = ?')->execute([$cost, $company['id']]);
        $pdo->prepare('INSERT INTO ent_rd_projects (company_id, name, quality, cost, research_days) VALUES (?, ?, ?, ?, ?)')->execute([$company['id'], $name, $quality, $cost, $researchDays]);
        $bal = $pdo->query("SELECT balance FROM ent_companies WHERE id = {$company['id']}")->fetchColumn();
        $pdo->prepare('INSERT INTO ent_finance (company_id, type, category, amount, balance_after, sim_day) VALUES (?, "expense", "研发投入", ?, ?, ?)')->execute([$company['id'], $cost, $bal, $company['sim_time']]);

        $rdEmpStmt = $pdo->prepare('SELECT COUNT(*) FROM ent_employees WHERE company_id = ? AND department = "研发部" AND is_active = 1');
        $rdEmpStmt->execute([$company['id']]);
        $rdEmpCount = (int)$rdEmpStmt->fetchColumn();
        $basePerDay = max(1, round(100 / max(1, $researchDays)));
        $withEmp = max(1, $basePerDay + $rdEmpCount * 3);
        $estDays = ceil(100 / $withEmp);
        $this->json(['ok' => true, 'message' => "产品「{$name}」已提交研发！预计{$researchDays}天完成（研发部加速约{$estDays}天），研发完成后自动上架销售"]);
    }

    // ==================== API: 开启研发 ====================
    private function apiStartRd(int $uid): void {
        $company = $this->requireCompany($uid);
        if ((int)$company['level'] < 2) {
            $this->json(['ok' => false, 'error' => '企业需达到2级(微小企业)才能开启研发']);
        }
        $name = trim($_POST['name'] ?? '');
        $poolId = (int)($_POST['pool_id'] ?? 0);
        $cost = (float)($_POST['cost'] ?? 100000);
        $pdo = Database::getConnection();

        if ($poolId > 0) {
            $pool = $this->getRdPool();
            if (!isset($pool[$poolId])) {
                $this->json(['ok' => false, 'error' => '无效的研发项目']);
            }
            $proj = $pool[$poolId];
            $name = $proj['name'];
            $cost = $proj['cost'];
            $quality = mt_rand($proj['min_quality'], $proj['max_quality']);
            $researchDays = $proj['days'];
        } else {
            if (empty($name)) $this->json(['ok' => false, 'error' => '请选择研发项目']);
            $quality = mt_rand(30, 100);
            $researchDays = 30;
        }
        if ($cost > (float)$company['balance']) $this->json(['ok' => false, 'error' => '资金不足']);

        $pdo->prepare('UPDATE ent_companies SET balance = balance - ? WHERE id = ?')->execute([$cost, $company['id']]);
        $pdo->prepare('INSERT INTO ent_rd_projects (company_id, name, quality, cost, research_days) VALUES (?, ?, ?, ?, ?)')->execute([$company['id'], $name, $quality, $cost, $researchDays]);
        $bal = $pdo->query("SELECT balance FROM ent_companies WHERE id = {$company['id']}")->fetchColumn();
        $pdo->prepare('INSERT INTO ent_finance (company_id, type, category, amount, balance_after, sim_day) VALUES (?, "expense", "研发投入", ?, ?, ?)')->execute([$company['id'], $cost, $bal, $company['sim_time']]);
        $this->json(['ok' => true, 'message' => "研发项目「{$name}」已启动，预计{$researchDays}天完成"]);
    }

    // ==================== API: 开店 ====================
    private function apiOpenStore(int $uid): void {
        $company = $this->requireCompany($uid);
        if ((int)$company['level'] < 2) {
            $this->json(['ok' => false, 'error' => '企业需达到2级才能开店']);
        }
        $type = $_POST['type'] ?? 'online';
        $name = trim($_POST['name'] ?? '');
        $cost = $type === 'online' ? 100000 : 300000;

        if (empty($name)) $this->json(['ok' => false, 'error' => '请填写店铺名称']);
        if ($cost > (float)$company['balance']) $this->json(['ok' => false, 'error' => '资金不足']);

        $pdo = Database::getConnection();
        $dailyRev = $type === 'online' ? 5000 : 15000;
        $pdo->prepare('UPDATE ent_companies SET balance = balance - ? WHERE id = ?')->execute([$cost, $company['id']]);
        $pdo->prepare('INSERT INTO ent_stores (company_id, type, name, cost, daily_revenue) VALUES (?, ?, ?, ?, ?)')->execute([$company['id'], $type, $name, $cost, $dailyRev]);
        $bal = $pdo->query("SELECT balance FROM ent_companies WHERE id = {$company['id']}")->fetchColumn();
        $pdo->prepare('INSERT INTO ent_finance (company_id, type, category, amount, balance_after, sim_day) VALUES (?, "expense", "开设店铺", ?, ?, ?)')->execute([$company['id'], $cost, $bal, $company['sim_time']]);
        $this->json(['ok' => true, 'message' => '店铺开设成功']);
    }

    // ==================== API: 贷款 ====================
    private function apiLoan(int $uid): void {
        $company = $this->requireCompany($uid);
        $planType = $_POST['plan_type'] ?? '低息长期';
        $pdo = Database::getConnection();

        $plans = [
            '低息长期' => ['amount' => 2000000, 'rate' => 0.01, 'months' => 12],
            '高息短期' => ['amount' => 5000000, 'rate' => 0.03, 'months' => 3],
            '抵押贷款' => ['amount' => 10000000, 'rate' => 0.015, 'months' => 6],
        ];

        if (!isset($plans[$planType])) {
            $this->json(['ok' => false, 'error' => '无效贷款方案']);
        }

        $plan = $plans[$planType];
        $monthlyPayment = round($plan['amount'] / $plan['months'], 2);
        $pdo->prepare('UPDATE ent_companies SET balance = balance + ? WHERE id = ?')->execute([$plan['amount'], $company['id']]);
        $pdo->prepare('INSERT INTO ent_company_loans (company_id, plan_type, amount, rate, months, remaining, monthly_payment) VALUES (?, ?, ?, ?, ?, ?, ?)')->execute([$company['id'], $planType, $plan['amount'], $plan['rate'], $plan['months'], $plan['months'], $monthlyPayment]);

        $bal = $pdo->query("SELECT balance FROM ent_companies WHERE id = {$company['id']}")->fetchColumn();
        $pdo->prepare('INSERT INTO ent_finance (company_id, type, category, amount, balance_after, sim_day) VALUES (?, "income", "贷款入账", ?, ?, ?)')->execute([$company['id'], $plan['amount'], $bal, $company['sim_time']]);

        $this->json(['ok' => true, 'message' => "贷款{$planType} ¥" . number_format($plan['amount']) . " 已到账"]);
    }

    // ==================== API: 福利设置 ====================
    private function apiWelfare(int $uid): void {
        $company = $this->requireCompany($uid);
        $pdo = Database::getConnection();
        $fields = ['social_insurance', 'housing_fund', 'canteen', 'dormitory', 'transport', 'holiday_bonus'];
        foreach ($fields as $f) {
            $val = isset($_POST[$f]) ? 1 : 0;
            $pdo->prepare("UPDATE ent_companies SET $f = ? WHERE id = ?")->execute([$val, $company['id']]);
        }

        // 如果开启了社保和公积金，降低风险
        if (($_POST['social_insurance'] ?? 0) && ($_POST['housing_fund'] ?? 0)) {
            $pdo->prepare('UPDATE ent_companies SET labor_risk = 0 WHERE id = ?')->execute([$company['id']]);
        }

        $this->json(['ok' => true, 'message' => '福利配置已更新']);
    }

    // ==================== API: 分红 ====================
    private function apiDividend(int $uid): void {
        $company = $this->requireCompany($uid);
        $amount = (float)($_POST['amount'] ?? 0);
        if ($amount <= 0 || $amount > (float)$company['balance']) {
            $this->json(['ok' => false, 'error' => '分红金额无效']);
        }
        $pdo = Database::getConnection();
        $taxAmount = round($amount * 0.2, 2);
        $netAmount = round($amount - $taxAmount, 2);

        $pdo->prepare('UPDATE ent_companies SET balance = balance - ? WHERE id = ?')->execute([$amount, $company['id']]);
        $pdo->prepare('UPDATE ent_accounts SET balance = balance + ? WHERE user_id = ?')->execute([$netAmount, $uid]);

        $bal = $pdo->query("SELECT balance FROM ent_companies WHERE id = {$company['id']}")->fetchColumn();
        $pdo->prepare('INSERT INTO ent_finance (company_id, type, category, amount, balance_after, sim_day) VALUES (?, "expense", "分红(含20%税)", ?, ?, ?)')->execute([$company['id'], $amount, $bal, $company['sim_time']]);

        $this->json(['ok' => true, 'message' => "分红 ¥" . number_format($netAmount) . " 已转入股票账户（扣税20%）"]);
    }

    // ==================== API: 速度调整 ====================
    private function apiSetSpeed(int $uid): void {
        $company = $this->requireCompany($uid);
        $speed = (int)($_POST['speed'] ?? 1);
        if (!in_array($speed, [1, 2, 5])) $speed = 1;
        $pdo = Database::getConnection();
        $pdo->prepare('UPDATE ent_companies SET speed = ? WHERE id = ?')->execute([$speed, $company['id']]);
        $this->json(['ok' => true, 'speed' => $speed]);
    }

    // ==================== API: 恢复运营 ====================
    private function apiRecover(int $uid): void {
        $company = $this->requireCompany($uid);
        $pdo = Database::getConnection();
        // 必须开启了社保公积金
        if (!$company['social_insurance'] || !$company['housing_fund']) {
            $this->json(['ok' => false, 'error' => '请先在福利配置中开启社保和公积金']);
        }
        $pdo->prepare("UPDATE ent_crisis SET is_active = 0 WHERE company_id = ? AND event_name = '停业整顿' AND is_active = 1")->execute([$company['id']]);
        $pdo->prepare('UPDATE ent_companies SET labor_risk = 0 WHERE id = ?')->execute([$company['id']]);
        $this->json(['ok' => true, 'message' => '已恢复运营']);
    }

    // ==================== API: 维修产线 ====================
    private function apiRepairLine(int $uid): void {
        $company = $this->requireCompany($uid);
        $lineId = (int)($_POST['line_id'] ?? 0);
        $pdo = Database::getConnection();
        $cost = 100000;

        if ((float)$company['balance'] < $cost) {
            $this->json(['ok' => false, 'error' => '资金不足，维修需 ¥' . number_format($cost)]);
        }

        $pdo->prepare('UPDATE ent_companies SET balance = balance - ? WHERE id = ?')->execute([$cost, $company['id']]);
        $pdo->prepare("UPDATE ent_production_lines SET status = 'idle' WHERE id = ? AND company_id = ?")->execute([$lineId, $company['id']]);
        $bal = $pdo->query("SELECT balance FROM ent_companies WHERE id = {$company['id']}")->fetchColumn();
        $pdo->prepare('INSERT INTO ent_finance (company_id, type, category, amount, balance_after, sim_day) VALUES (?, "expense", "维修产线", ?, ?, ?)')->execute([$company['id'], $cost, $bal, $company['sim_time']]);

        $this->json(['ok' => true, 'message' => '产线已修复']);
    }

    // ==================== API: 上市 ====================
    private function apiIpo(int $uid): void {
        $company = $this->requireCompany($uid);
        if ((int)$company['level'] < 5) {
            $this->json(['ok' => false, 'error' => '企业需达到5级(大型企业)才能申请上市']);
        }
        if ($company['is_listed']) {
            $this->json(['ok' => false, 'error' => '企业已上市']);
        }
        $deposit = 50000000;
        if ((float)$company['balance'] < $deposit) {
            $this->json(['ok' => false, 'error' => '资金不足，需缴纳5000万保证金']);
        }
        $pdo = Database::getConnection();
        $pdo->prepare('UPDATE ent_companies SET balance = balance - ?, is_listed = 1, listing_deposit = ? WHERE id = ?')->execute([$deposit, $deposit, $company['id']]);
        $bal = $pdo->query("SELECT balance FROM ent_companies WHERE id = {$company['id']}")->fetchColumn();
        $pdo->prepare('INSERT INTO ent_finance (company_id, type, category, amount, balance_after, sim_day) VALUES (?, "expense", "上市保证金", ?, ?, ?)')->execute([$company['id'], $deposit, $bal, $company['sim_time']]);
        $this->json(['ok' => true, 'message' => '企业已成功上市！']);
    }

    // ==================== API: 注资 ====================
    private function apiInvest(int $uid): void {
        $company = $this->requireCompany($uid);
        $amount = (float)($_POST['amount'] ?? 0);
        if ($amount <= 0) $this->json(['ok' => false, 'error' => '请输入有效金额']);
        $pdo = Database::getConnection();
        $acc = $pdo->prepare('SELECT balance FROM ent_accounts WHERE user_id = ?');
        $acc->execute([$uid]);
        $personalBalance = (float)($acc->fetchColumn() ?: 0);
        if ($personalBalance < $amount) {
            $this->json(['ok' => false, 'error' => '个人资产不足，当前可用：¥' . number_format($personalBalance)]);
        }
        $pdo->prepare('UPDATE ent_accounts SET balance = balance - ? WHERE user_id = ?')->execute([$amount, $uid]);
        $pdo->prepare('UPDATE ent_companies SET balance = balance + ? WHERE id = ?')->execute([$amount, $company['id']]);
        $bal = $pdo->query("SELECT balance FROM ent_companies WHERE id = {$company['id']}")->fetchColumn();
        $pdo->prepare('INSERT INTO ent_finance (company_id, type, category, amount, balance_after, sim_day) VALUES (?, "income", "股东注资", ?, ?, ?)')->execute([$company['id'], $amount, $bal, $company['sim_time']]);
        $this->json(['ok' => true, 'message' => '成功注资 ¥' . number_format($amount)]);
    }

    // ==================== API: 手动升级 ====================
    private function apiDoUpgrade(int $uid): void {
        $company = $this->requireCompany($uid);
        $pdo = Database::getConnection();
        $company = $pdo->query("SELECT * FROM ent_companies WHERE id = {$company['id']}")->fetch(\PDO::FETCH_ASSOC);
        $status = $this->getUpgradeStatus($company);
        if (!$status['can_upgrade']) {
            $this->json(['ok' => false, 'error' => '升级条件未满足，请查看条件列表']);
        }
        $pdo->prepare('UPDATE ent_companies SET level = level + 1 WHERE id = ?')->execute([$company['id']]);
        $this->json(['ok' => true, 'message' => '企业已升级为：' . $status['next_level']]);
    }

    // ==================== API: 获取升级状态 ====================
    private function apiUpgradeStatus(int $uid): void {
        $company = $this->requireCompany($uid);
        $pdo = Database::getConnection();
        $company = $pdo->query("SELECT * FROM ent_companies WHERE id = {$company['id']}")->fetch(\PDO::FETCH_ASSOC);
        $status = $this->getUpgradeStatus($company);
        $this->json(['ok' => true, 'status' => $status]);
    }

    // ==================== 商城页面 ====================
    public function mall(): void {
        $uid = $this->requireLogin();
        $company = $this->getCompany($uid);
        if (!$company) {
            header('Location: /public/index.php?route=enterprise');
            exit;
        }
        $pdo = Database::getConnection();
        $this->autoSettle($company);
        $company = $this->getCompany($uid);

        $assets = $pdo->prepare('SELECT * FROM ent_assets WHERE company_id = ? ORDER BY category');
        $assets->execute([$company['id']]);
        $assetList = $assets->fetchAll(\PDO::FETCH_ASSOC);

        $this->render('enterprise/mall', [
            'pageTitle' => '企业商城',
            'company' => $company,
            'assetList' => $assetList,
        ]);
    }

    // ==================== 产品订单页面 ====================
    public function products(): void {
        $uid = $this->requireLogin();
        $company = $this->getCompany($uid);
        if (!$company) {
            header('Location: /public/index.php?route=enterprise');
            exit;
        }
        $pdo = Database::getConnection();
        $this->autoSettle($company);
        $company = $this->getCompany($uid);

        $products = $pdo->prepare('SELECT * FROM ent_products WHERE company_id = ? ORDER BY id');
        $products->execute([$company['id']]);
        $prodList = $products->fetchAll(\PDO::FETCH_ASSOC);

        $orders = $pdo->prepare('SELECT * FROM ent_company_orders WHERE company_id = ? ORDER BY FIELD(status,"pending","in_progress","completed","cancelled"), id DESC LIMIT 50');
        $orders->execute([$company['id']]);
        $orderList = $orders->fetchAll(\PDO::FETCH_ASSOC);

        $this->render('enterprise/products', [
            'pageTitle' => '产品与订单',
            'company' => $company,
            'prodList' => $prodList,
            'orderList' => $orderList,
        ]);
    }

    // ==================== 研发页面 ====================
    public function rd(): void {
        $uid = $this->requireLogin();
        $company = $this->getCompany($uid);
        if (!$company) {
            header('Location: /public/index.php?route=enterprise');
            exit;
        }
        $pdo = Database::getConnection();
        $this->autoSettle($company);
        $company = $this->getCompany($uid);

        $rd = $pdo->prepare('SELECT * FROM ent_rd_projects WHERE company_id = ? ORDER BY id DESC');
        $rd->execute([$company['id']]);
        $rdList = $rd->fetchAll(\PDO::FETCH_ASSOC);

        // 研发部员工数
        $rdEmpStmt = $pdo->prepare('SELECT COUNT(*) FROM ent_employees WHERE company_id = ? AND department = "研发部" AND is_active = 1');
        $rdEmpStmt->execute([$company['id']]);
        $rdEmpCount = (int)$rdEmpStmt->fetchColumn();

        $this->render('enterprise/rd', [
            'pageTitle' => '研发中心',
            'company' => $company,
            'rdList' => $rdList,
            'rdPool' => $this->getRdPool(),
            'rdEmpCount' => $rdEmpCount,
        ]);
    }

    // ==================== 销售页面 ====================
    public function sales(): void {
        $uid = $this->requireLogin();
        $company = $this->getCompany($uid);
        if (!$company) {
            header('Location: /public/index.php?route=enterprise');
            exit;
        }
        $pdo = Database::getConnection();
        $this->autoSettle($company);
        $company = $this->getCompany($uid);

        $stores = $pdo->prepare('SELECT * FROM ent_stores WHERE company_id = ? AND is_active = 1 ORDER BY id');
        $stores->execute([$company['id']]);
        $storeList = $stores->fetchAll(\PDO::FETCH_ASSOC);

        $this->render('enterprise/sales', [
            'pageTitle' => '市场销售',
            'company' => $company,
            'storeList' => $storeList,
        ]);
    }

    // ==================== 游戏说明页面 ====================
    public function guide(): void {
        $this->render('enterprise/guide', [
            'pageTitle' => '游戏说明',
        ]);
    }

    // ==================== 辅助方法 ====================
    private function requireCompany(int $uid): array {
        $company = $this->getCompany($uid);
        if (!$company) {
            $this->json(['ok' => false, 'error' => '请先注册企业']);
        }
        return $company;
    }
}
