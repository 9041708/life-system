#!/bin/bash
# 论坛助手调度器 - 开机自启脚本
# 群晖NAS: 控制面板 → 任务计划 → 新增 → 触发任务 → 用户自定义的脚本 → 开机触发
# 运行命令: bash /path/to/life-system/start_scheduler.sh

BASE_DIR="/path/to/life-system"
PID_FILE="$BASE_DIR/logs/scheduler.pid"
LOG_FILE="$BASE_DIR/logs/scheduler_spawn.log"
PHP_BIN="/usr/bin/php"

# 查找 php 路径
if [ ! -f "$PHP_BIN" ]; then
    for p in /usr/local/bin/php /usr/bin/php82 /usr/bin/php81 /usr/bin/php80 /usr/bin/php74; do
        if [ -f "$p" ]; then
            PHP_BIN="$p"
            break
        fi
    done
fi

mkdir -p "$BASE_DIR/logs"

# 检查是否已在运行
if [ -f "$PID_FILE" ]; then
    PID=$(cat "$PID_FILE")
    if [ -d "/proc/$PID" ]; then
        echo "[$(date)] 调度器已在运行 PID=$PID，跳过" >> "$LOG_FILE"
        exit 0
    fi
fi

# 启动
nohup "$PHP_BIN" "$BASE_DIR/scheduler_runner.php" >> "$LOG_FILE" 2>&1 &
echo "[$(date)] 调度器已启动 PID=$!" >> "$LOG_FILE"
