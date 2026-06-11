# SanS 记账系统

一个功能丰富的个人财务管理 Web 应用，基于 PHP 8 + MySQL 构建。

## 功能模块

| 模块 | 说明 |
|------|------|
| 📝 **记账** | 收入/支出记录，支持多账户、多分类、多项目 |
| 📊 **统计报表** | 月度/季度/年度收支分析图表 |
| 🏦 **账户管理** | 多资金账户（现金、银行卡、信用卡等） |
| 📂 **分类/项目管理** | 自定义收支分类和项目 |
| 💰 **预算管理** | 月度预算设置与超支预警 |
| 🎯 **目标管理** | 储蓄目标追踪 |
| 📈 **理财管理** | 理财产品收益记录 |
| 💳 **负债管理** | 信用卡/贷款还款追踪 |
| 🧾 **报销管理** | 费用报销记录与统计 |
| 📦 **订阅管理** | 周期性订阅服务管理与续费提醒 |
| 🏠 **资产管理** | 固定资产/贵重物品登记 |
| 📄 **简历生成** | 在线简历编辑，多模板，导出 PDF |
| 💬 **论坛助手** | 自动签到/回帖，@提及AI智能回复，引用回复，彩蛋自动领取 |
| 💊 **正念** | 签到打卡（日历+连续奖励）、树洞心事AI回复、正负念记录与补录、AI次数/套餐管理 |
| 🤖 **AI服务** | 统一AI配额管理（树洞+论坛助手），套餐定价，管理员发放，用户自助购买 |
| 📂 **项目** | 项目进度管理，时间轴记录，协同编辑，待办任务清单，图片/文件附件，按用户隔离存储 |
| 📝 **知识库** | Markdown文档管理，多级文件夹，editor.md编辑器，阅读+目录导航，外部分享，版本历史 |
| 🎲 **今天干嘛** | 随机选吃的/去哪/看剧，抽奖动画，菜谱/攻略/播放链接 |
| 🧰 **工具箱** | 密码箱、二维码生成、摩斯电码、万年历等 |
| 📋 **系统日志** | 统一操作日志（系统操作 + 论坛操作），分组筛选、分页查询 |
| 📱 **微信小程序** | 移动端快捷记账 |
| 🔌 **REST API** | 支持第三方接入（Bearer Token 认证） |
| 📲 **PWA** | 可安装到手机桌面 |

## 技术栈

- **后端**: PHP 8.0+, MySQL/MariaDB
- **前端**: Bootstrap 5.3, Vanilla JS, Choice.js
- **PDF**: html2canvas + jsPDF
- **邮件**: PHPMailer
- **自动加载**: PSR-4 (Composer)

## 快速开始

### 环境要求

- PHP 8.0+
- MySQL 5.7+ / MariaDB 10.3+
- Composer
- Web Server (Apache / Nginx / 群晖 NAS)

### 安装

```bash
# 1. 克隆仓库
git clone https://github.com/your-username/sans-accounting.git
cd sans-accounting

# 2. 安装依赖
composer install

# 3. 配置数据库
cp config/config.example.php config/config.php
# 编辑 config/config.php，填入数据库连接信息和其他配置

# 4. 设置 Web 根目录
# 将 public/ 设为网站根目录

# 5. 确保以下目录可写
mkdir -p uploads runtime logs
chmod 755 uploads runtime logs
```

### 访问

- 浏览器打开你的站点地址
- 默认首页为登录/注册页
- 首次运行会自动创建数据库表

## 项目结构

```
├── config/             # 配置文件
│   ├── config.example.php  # 配置模板
│   └── config.php          # 实际配置（不纳入版本管理）
├── public/             # Web 根目录
│   ├── index.php       # 前端控制器（页面）
│   ├── api.php         # REST API 入口
│   └── sw.js           # PWA Service Worker
├── src/                # PHP 源码
│   ├── Controller/     # 控制器
│   ├── Model/          # 数据模型
│   ├── Service/        # 服务层
│   └── bootstrap.php   # 初始化/自动迁移
├── templates/          # 视图模板
│   └── mindfulness/    # 正念模块模板（checkin/treasure/config）
├── uploads/            # 用户上传文件（不纳入版本管理）
├── runtime/            # 运行时文件（不纳入版本管理）
├── logs/               # 日志文件（不纳入版本管理）
├── backup/             # 数据库备份（不纳入版本管理）
└── tools/              # 工具脚本
```

## API 使用

参见 [API_DOCUMENTATION.md](API_DOCUMENTATION.md)

认证方式: `Authorization: Bearer <token>`

Token 在网页端「设置 → 个人信息 → API Token 管理」中创建。

## License

MIT License

Copyright (c) 2025-2026 SanS
