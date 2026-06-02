# SanS 三石记账系统

一个功能完善的个人财务管理与生活工具平台，基于 PHP 8.0+ 构建。

## 功能模块

- **记账系统**：账户管理、交易流水、分类预算、多账本、报销管理、债务追踪
- **效率工具**：待办任务、备忘录、番茄钟、倒计时、读书管理
- **论坛助手**：Discuz 论坛自动签到、AI 智能回帖、@提及自动回复
- **工具箱**：日历、人民币大写转换、摩斯密码、二维码生成、密码保险箱、保质期追踪
- **移动端**：PWA 渐进式网页应用，支持离线访问
- **安全模块**：登录保护、IP 黑白名单、攻击检测

## 环境要求

- PHP >= 8.0
- MySQL / MariaDB 5.7+
- Composer
- 可选：OpenSSL 扩展（备份加密）、mbstring 扩展

## 快速开始

### 1. 克隆项目

```bash
git clone https://github.com/yourname/ssjizhang.git
cd ssjizhang
```

### 2. 安装依赖

```bash
composer install
```

### 3. 配置

编辑 `config/config.php`，将占位符替换为你的实际数据库、邮箱、域名等信息。所有配置项说明见下方[配置说明](#配置说明)。

### 4. 导入数据库

```bash
mysql -u root -p your_database_name < database/ssjizhang_cn.sql
```

默认管理员账号：`admin`，密码：`123456`（首次登录后请立即修改）。

### 5. Web 服务器配置

将站点根目录指向 `public/`：

**Nginx 示例：**

```nginx
server {
    listen 80;
    server_name your-domain.com;
    root /path/to/ssjizhang/public;
    index index.php;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.0-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
    }
}
```

**Apache 示例：**

`public/` 目录下的 `.htaccess` 已包含 rewrite 规则。

### 6. 访问

打开浏览器访问 `http://your-domain.com`，使用 `admin` / `123456` 登录。

## 配置说明

### 数据库配置 `db`

| 字段 | 说明 |
|------|------|
| `host` | 数据库主机地址 |
| `dbname` | 数据库名称 |
| `user` | 数据库用户名 |
| `pass` | 数据库密码 |
| `charset` | 字符集，推荐 `utf8mb4` |

### 邮件配置 `mail`

用于系统通知邮件（注册验证、密码重置、备份通知等）。

| 字段 | 说明 |
|------|------|
| `driver` | 邮件驱动，`mail`=PHP mail(), `smtp`=SMTP |
| `host` | SMTP 服务器地址 |
| `port` | SMTP 端口（SSL 通常 465，TLS 通常 587） |
| `encryption` | 加密方式：`ssl` 或 `tls` |
| `username` | SMTP 登录用户名 |
| `password` | SMTP 登录密码 |
| `from_email` | 发件人邮箱 |
| `from_name` | 发件人名称 |

### 应用配置 `app`

| 字段 | 说明 |
|------|------|
| `name` | 站点名称 |
| `base_url` | 基础路径，一般保持 `/` |
| `site_url` | 完整站点 URL（如 `https://example.com`） |
| `allow_register` | 是否允许新用户注册 |
| `upload_dir` | 上传文件存储目录，建议保持默认 |
| `landing_enabled` | 是否启用落地页 |
| `license_admin_enabled` | 是否启用授权管理 |

### AI 配置 `ai`

#### 基础配置

| 字段 | 说明 |
|------|------|
| `enabled` | 是否启用 AI 功能 |
| `provider` | AI 提供商（`qclaw`） |
| `qclaw_api_url` | API 地址 |
| `timeout` | 请求超时（秒） |

#### 论坛自动回复 `ai.forum_reply`

| 字段 | 说明 |
|------|------|
| `enabled` | 是否启用论坛 AI 回复 |
| `api_url` | DeepSeek API 地址 |
| `api_key` | API Key（在 [DeepSeek 开放平台](https://platform.deepseek.com) 获取） |
| `model` | 模型名称（如 `deepseek-chat`） |
| `max_tokens` | 最大回复字数 |
| `temperature` | 创意程度 0-1 |
| `filter_words` | 敏感词过滤列表 |

### 微信小程序 `wechat`

| 字段 | 说明 |
|------|------|
| `miniapp_appid` | 小程序 AppID |
| `miniapp_secret` | 小程序 Secret |
| `share_secret` | 分享密钥 |
| `enable_miniapp` | 是否启用小程序 |

### 备份配置 `backup`

| 字段 | 说明 |
|------|------|
| `enabled` | 是否启用自动备份 |
| `frequency` | 备份频率：`daily`（每天）、`weekly`（每周）、`monthly`（每月） |
| `execution_day` | 执行日：weekly 时为周几（1=周一, 7=周日），monthly 时为几号 |
| `execution_time` | 执行时间（如 `02:00`） |
| `retention_days` | 备份保留天数 |
| `keep_versions` | 最多保留几个版本 |
| `encrypt_backup` | 是否加密备份 |
| `encryption_key` | 加密密钥 |
| `email_notify` | 是否发送邮件通知 |
| `notify_email` | 通知接收邮箱 |

### 定时任务 `scheduler`

| 字段 | 说明 |
|------|------|
| `enabled` | 是否启用后台调度器 |
| `check_interval` | 检查间隔（秒） |
| `log_retention_days` | 日志保留天数 |

### 定时任务配置（重要）

系统依赖外部定时任务来执行备份。你需要配置系统级 cron：

**群晖 DSM：** 控制面板 → 任务计划 → 新增 → 用户自定义脚本 → 每 5 分钟执行一次：
```bash
php /volume1/web/ssjizhang/cron.php
```

**Linux crontab：**
```cron
*/5 * * * * php /path/to/ssjizhang/cron.php
```

cron.php 会同时处理：论坛签到、自动回帖、@提及回复、日志清理、系统备份。

## 目录结构

```
ssjizhang/
├── config/             # 配置文件
├── public/             # Web 入口（站点根目录）
│   ├── index.php       # 主入口
│   ├── api.php         # API 接口（小程序/移动端）
│   └── mobile/         # PWA 移动端
├── src/                # 核心代码
│   ├── Controller/     # 控制器
│   ├── Model/          # 模型
│   ├── Service/        # 服务层
│   └── bootstrap.php   # 启动引导
├── templates/          # 页面模板
├── tools/              # 工具脚本
├── assets/             # 前端静态资源
├── vendor/             # Composer 依赖（需 composer install）
├── cron.php            # 定时任务入口
├── scheduler_runner.php # 后台调度器守护进程
├── process_system_tasks.php # 备份任务处理器
├── composer.json       # Composer 配置
└── database/           # 数据库 SQL（需自行导入）
```

## License

待定
