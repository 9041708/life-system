<?php
// report-card.php - 独立财务报告页（月度/季度/年度）
// 支持 ?mode=monthly&year=2026&month=4
// 支持 ?mode=quarterly&year=2026&quarter=2
// 支持 ?mode=yearly&year=2026
require __DIR__ . '/../src/bootstrap.php';

use App\Service\Database;
use App\Service\Config;

$pdo = Database::getConnection();

// ---------- 参数解析 ----------
$mode   = $_GET['mode']   ?? 'monthly';
$year   = (int)($_GET['year']   ?? date('Y'));
$month  = (int)($_GET['month']  ?? date('n', strtotime('-1 month')));
$quarter = (int)($_GET['quarter'] ?? ceil(date('n') / 3));

// 时间范围计算
function getDateRange(string $mode, int $year, int $month, int $quarter): array {
    switch ($mode) {
        case 'quarterly':
            $startMonth = ($quarter - 1) * 3 + 1;
            $start = new DateTime(sprintf('%d-%02d-01', $year, $startMonth));
            $end = clone $start;
            $end->modify('+2 months')->modify('last day of this month');
            $label = $year . '年 第' . ['', '一', '二', '三', '四'][$quarter] . '季度';
            $months = [$startMonth, $startMonth + 1, $startMonth + 2];
            return [$start, $end, $label, $months];
        case 'yearly':
            $start = new DateTime($year . '-01-01');
            $end = new DateTime($year . '-12-31');
            $label = $year . '年度账单';
            $months = range(1, 12);
            return [$start, $end, $label, $months];
        case 'monthly':
        default:
            $start = new DateTime(sprintf('%d-%02d-01', $year, $month));
            $end = clone $start;
            $end->modify('last day of this month');
            $label = $year . '年' . $month . '月 账单';
            $months = [$month];
            return [$start, $end, $label, $months];
    }
}

[$start, $end, $reportLabel, $monthList] = getDateRange($mode, $year, $month, $quarter);

$startStr = $start->format('Y-m-d 00:00:00');
$endStr   = $end->format('Y-m-d 23:59:59');

// ---------- 获取用户ID（取第一个用户，或由 token 参数决定）----------
$userId = 0;
$token  = $_GET['token'] ?? '';
if ($token !== '') {
    // 通过 API Token 查找用户
    $stmt = $pdo->prepare('SELECT user_id FROM api_tokens WHERE token = :t AND expires_at > NOW() LIMIT 1');
    $stmt->execute([':t' => $token]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($row) $userId = (int)$row['user_id'];
}
if ($userId <= 0) {
    // 取默认第一个用户
    $stmt = $pdo->query('SELECT id FROM users ORDER BY id LIMIT 1');
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($row) $userId = (int)$row['id'];
}
if ($userId <= 0) {
    http_response_code(404);
    echo 'No user found';
    exit;
}

// ---------- 1. 收支汇总 ----------
$stmt = $pdo->prepare('
    SELECT type, COALESCE(SUM(amount), 0) AS total, COUNT(*) AS cnt
    FROM transactions
    WHERE user_id = :uid AND trans_time BETWEEN :from AND :to
    GROUP BY type
');
$stmt->execute([':uid' => $userId, ':from' => $startStr, ':to' => $endStr]);
$typeSummary = [];
foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $r) {
    $typeSummary[$r['type']] = ['total' => (float)$r['total'], 'cnt' => (int)$r['cnt']];
}
$totalExpense = $typeSummary['expense']['total'] ?? 0.0;
$totalIncome  = $typeSummary['income']['total']  ?? 0.0;
$totalTransfer = $typeSummary['transfer']['total'] ?? 0.0;
$countExpense = $typeSummary['expense']['cnt'] ?? 0;
$countIncome  = $typeSummary['income']['cnt']  ?? 0;
$countTransfer = $typeSummary['transfer']['cnt'] ?? 0;
$balance = $totalIncome - $totalExpense;

// ---------- 2. 日均支出 ----------
$stmt = $pdo->prepare('
    SELECT COALESCE(SUM(amount), 0) AS total, COUNT(DISTINCT DATE(trans_time)) AS days
    FROM transactions
    WHERE user_id = :uid AND type = :t AND trans_time BETWEEN :from AND :to
');
$stmt->execute([':uid' => $userId, ':t' => 'expense', ':from' => $startStr, ':to' => $endStr]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);
$avgExpense = ($row && $row['days'] > 0) ? (float)$row['total'] / (int)$row['days'] : 0.0;

// ---------- 3. 支出/收入最高日 ----------
function getPeakDay(PDO $pdo, int $userId, string $type, string $startStr, string $endStr): array {
    $stmt = $pdo->prepare('
        SELECT DATE(trans_time) AS d, COALESCE(SUM(amount), 0) AS total
        FROM transactions
        WHERE user_id = :uid AND type = :t AND trans_time BETWEEN :from AND :to
        GROUP BY d ORDER BY total DESC LIMIT 1
    ');
    $stmt->execute([':uid' => $userId, ':t' => $type, ':from' => $startStr, ':to' => $endStr]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($row && (float)$row['total'] > 0) {
        return [date('j日', strtotime($row['d'])), (float)$row['total']];
    }
    return ['-', 0.0];
}
[$peakExpenseDay, $peakExpenseVal] = getPeakDay($pdo, $userId, 'expense', $startStr, $endStr);
[$peakIncomeDay,  $peakIncomeVal]  = getPeakDay($pdo, $userId, 'income',  $startStr, $endStr);

// ---------- 4. 项目排名（支出） ----------
$stmt = $pdo->prepare('
    SELECT i.name AS item_name, COALESCE(SUM(t.amount), 0) AS total
    FROM transactions t
    LEFT JOIN items i ON t.item_id = i.id
    WHERE t.user_id = :uid AND t.type = :t AND t.trans_time BETWEEN :from AND :to
    GROUP BY t.item_id
    HAVING total > 0
    ORDER BY total DESC
    LIMIT 10
');
$stmt->execute([':uid' => $userId, ':t' => 'expense', ':from' => $startStr, ':to' => $endStr]);
$expenseCategories = $stmt->fetchAll(PDO::FETCH_ASSOC);
$expenseMax = count($expenseCategories) > 0 ? (float)$expenseCategories[0]['total'] : 1;

// 环比（与上一周期对比）
function getPrevRange(string $mode, int $year, int $month, int $quarter): array {
    switch ($mode) {
        case 'quarterly':
            $y = $quarter == 1 ? $year - 1 : $year;
            $q = $quarter == 1 ? 4 : $quarter - 1;
            $sm = ($q - 1) * 3 + 1;
            $s = new DateTime(sprintf('%d-%02d-01', $y, $sm));
            $e = clone $s; $e->modify('+2 months')->modify('last day of this month');
            return [$s->format('Y-m-d 00:00:00'), $e->format('Y-m-d 23:59:59')];
        case 'yearly':
            $s = new DateTime(($year - 1) . '-01-01');
            $e = new DateTime(($year - 1) . '-12-31');
            return [$s->format('Y-m-d 00:00:00'), $e->format('Y-m-d 23:59:59')];
        default:
            $prev = new DateTime(sprintf('%d-%02d-01', $year, $month));
            $prev->modify('-1 month');
            $s = $prev->format('Y-m-01 00:00:00');
            $e = date('Y-m-t 23:59:59', strtotime($prev->format('Y-m-01')));
            return [$s, $e];
    }
}
[$prevStart, $prevEnd] = getPrevRange($mode, $year, $month, $quarter);

$stmt = $pdo->prepare('
    SELECT type, COALESCE(SUM(amount), 0) AS total
    FROM transactions
    WHERE user_id = :uid AND trans_time BETWEEN :from AND :to
    GROUP BY type
');
$stmt->execute([':uid' => $userId, ':from' => $prevStart, ':to' => $prevEnd]);
$prevSummary = [];
foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $r) {
    $prevSummary[$r['type']] = (float)$r['total'];
}
$prevExpense = $prevSummary['expense'] ?? 0.0;
$prevIncome  = $prevSummary['income']  ?? 0.0;
$changeExpensePct = $prevExpense > 0 ? round(($totalExpense - $prevExpense) / $prevExpense * 100) : 0;
$changeIncomePct  = $prevIncome  > 0 ? round(($totalIncome  - $prevIncome)  / $prevIncome  * 100) : 0;

// 项目环比（月度用上月，季度用上季度）
function getItemChange(PDO $pdo, int $userId, string $curStart, string $curEnd, string $prevStart, string $prevEnd): array {
    $sql = '
        SELECT i.name AS item_name,
               COALESCE(SUM(CASE WHEN t.trans_time BETWEEN :cfrom AND :cto AND t.type="expense" THEN t.amount ELSE 0 END), 0) AS cur,
               COALESCE(SUM(CASE WHEN t.trans_time BETWEEN :pfrom AND :pto AND t.type="expense" THEN t.amount ELSE 0 END), 0) AS prev
        FROM items i
        LEFT JOIN transactions t ON t.item_id = i.id AND t.user_id = :uid
        WHERE i.user_id = :uid OR i.user_id IS NULL
        GROUP BY i.id, i.name
        HAVING cur > 0
        ORDER BY cur DESC
        LIMIT 10
    ';
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':uid' => $userId, ':cfrom' => $curStart, ':cto' => $curEnd, ':pfrom' => $prevStart, ':pto' => $prevEnd]);
    $result = [];
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $r) {
        $cur  = (float)$r['cur'];
        $prev = (float)$r['prev'];
        $pct  = $prev > 0 ? round(($cur - $prev) / $prev * 100) : ($cur > 0 ? 999 : 0);
        $result[] = ['name' => $r['item_name'] ?: '未分类', 'cur' => $cur, 'pct' => $pct];
    }
    return $result;
}
$expenseWithChange = getItemChange($pdo, $userId, $startStr, $endStr, $prevStart, $prevEnd);
$expenseMax2 = count($expenseWithChange) > 0 ? $expenseWithChange[0]['cur'] : 1;

// ---------- 5. 收入项目构成 ----------
$stmt = $pdo->prepare('
    SELECT i.name AS item_name, COALESCE(SUM(t.amount), 0) AS total
    FROM transactions t
    LEFT JOIN items i ON t.item_id = i.id
    WHERE t.user_id = :uid AND t.type = :t AND t.trans_time BETWEEN :from AND :to
    GROUP BY t.item_id
    HAVING total > 0
    ORDER BY total DESC
    LIMIT 10
');
$stmt->execute([':uid' => $userId, ':t' => 'income', ':from' => $startStr, ':to' => $endStr]);
$incomeCategories = $stmt->fetchAll(PDO::FETCH_ASSOC);
$incomeMax = count($incomeCategories) > 0 ? (float)$incomeCategories[0]['total'] : 1;

// 收入项目环比
function getIncomeItemChange(PDO $pdo, int $userId, string $curStart, string $curEnd, string $prevStart, string $prevEnd): array {
    $sql = '
        SELECT i.name AS item_name,
               COALESCE(SUM(CASE WHEN t.trans_time BETWEEN :cfrom AND :cto AND t.type="income" THEN t.amount ELSE 0 END), 0) AS cur,
               COALESCE(SUM(CASE WHEN t.trans_time BETWEEN :pfrom AND :pto AND t.type="income" THEN t.amount ELSE 0 END), 0) AS prev
        FROM items i
        LEFT JOIN transactions t ON t.item_id = i.id AND t.user_id = :uid
        WHERE i.user_id = :uid OR i.user_id IS NULL
        GROUP BY i.id, i.name
        HAVING cur > 0
        ORDER BY cur DESC
        LIMIT 10
    ';
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':uid' => $userId, ':cfrom' => $curStart, ':cto' => $curEnd, ':pfrom' => $prevStart, ':pto' => $prevEnd]);
    $result = [];
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $r) {
        $cur  = (float)$r['cur'];
        $prev = (float)$r['prev'];
        $pct  = $prev > 0 ? round(($cur - $prev) / $prev * 100) : ($cur > 0 ? 999 : 0);
        $result[] = ['name' => $r['item_name'] ?: '未分类', 'cur' => $cur, 'pct' => $pct];
    }
    return $result;
}
$incomeWithChange = getIncomeItemChange($pdo, $userId, $startStr, $endStr, $prevStart, $prevEnd);
$incomeMax2 = count($incomeWithChange) > 0 ? $incomeWithChange[0]['cur'] : 1;

// ---------- 6. 转账项目构成 ----------
$stmt = $pdo->prepare('
    SELECT i.name AS item_name, COALESCE(SUM(t.amount), 0) AS total
    FROM transactions t
    LEFT JOIN items i ON t.item_id = i.id
    WHERE t.user_id = :uid AND t.type = :t AND t.trans_time BETWEEN :from AND :to
    GROUP BY t.item_id
    HAVING total > 0
    ORDER BY total DESC
    LIMIT 10
');
$stmt->execute([':uid' => $userId, ':t' => 'transfer', ':from' => $startStr, ':to' => $endStr]);
$transferCategories = $stmt->fetchAll(PDO::FETCH_ASSOC);
$transferMax = count($transferCategories) > 0 ? (float)$transferCategories[0]['total'] : 1;

// ---------- 7. 月度趋势（季度/年度用） ----------
function getMonthlyTrend(PDO $pdo, int $userId, int $year, int $startMonth, int $endMonth): array {
    $labels = [];
    $incomeData = [];
    $expenseData = [];
    for ($m = $startMonth; $m <= $endMonth; $m++) {
        $s = sprintf('%d-%02d-01', $year, $m);
        $e = date('Y-m-t 23:59:59', strtotime($s));
        $labels[] = $m . '月';
        
        $stmt = $pdo->prepare('SELECT type, COALESCE(SUM(amount),0) AS t FROM transactions WHERE user_id=:uid AND trans_time BETWEEN :f AND :t GROUP BY type');
        $stmt->execute([':uid' => $userId, ':f' => $s . ' 00:00:00', ':t' => $e]);
        $row = [];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $r) $row[$r['type']] = (float)$r['t'];
        $incomeData[]  = $row['income']  ?? 0.0;
        $expenseData[] = $row['expense'] ?? 0.0;
    }
    return [$labels, $incomeData, $expenseData];
}

// ---------- 8. 还款计划 ----------
$stmt = $pdo->prepare('
    SELECT dp.*, dc.name AS debt_name
    FROM debt_payment dp
    JOIN debt_config dc ON dp.debt_config_id = dc.id
    WHERE dc.user_id = :uid AND dp.status != "paid"
      AND dp.due_date BETWEEN :from AND :to
    ORDER BY dp.due_date ASC
    LIMIT 20
');
// 显示次月待还
$nextMonth = (clone $start)->modify('first day of next month');
$futureStart = $nextMonth->format('Y-m-01');
$futureEnd = $nextMonth->format('Y-m-t');
$stmt->execute([':uid' => $userId, ':from' => $futureStart, ':to' => $futureEnd]);
$upcomingPayments = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 本月还款统计
$stmt = $pdo->prepare('
    SELECT COALESCE(SUM(paid_amount), 0) AS paid, COUNT(*) AS cnt
    FROM debt_payment
    WHERE user_id = :uid AND status = "paid"
      AND DATE_FORMAT(paid_date, "%Y-%m") = :curMonth
');
$stmt->execute([':uid' => $userId, ':curMonth' => $start->format('Y-m')]);
$paidRow = $stmt->fetch(PDO::FETCH_ASSOC);
$paidThisPeriod = (float)($paidRow['paid'] ?? 0);
$paidCountThisPeriod = (int)($paidRow['cnt'] ?? 0);

// 总待还
$stmt = $pdo->prepare('
    SELECT COALESCE(SUM(total_amount), 0) AS total
    FROM debt_payment
    WHERE user_id = :uid AND status != "paid"
');
$stmt->execute([':uid' => $userId]);
$totalRemaining = (float)$stmt->fetchColumn();

// ---------- 9. 订阅到期（3个月内） ----------
$stmt = $pdo->prepare('
    SELECT platform, expire_date, price, period
    FROM subscriptions
    WHERE user_id = :uid AND type = "subscription"
      AND expire_date IS NOT NULL
      AND expire_date BETWEEN :from AND :to
    ORDER BY expire_date ASC
');
$subFrom = date('Y-m-d');
$subTo   = date('Y-m-d', strtotime('+90 days'));
$stmt->execute([':uid' => $userId, ':from' => $subFrom, ':to' => $subTo]);
$upcomingSubs = $stmt->fetchAll(PDO::FETCH_ASSOC);
$activeSubCount = count($upcomingSubs);
$annualSubTotal = 0.0;
foreach ($upcomingSubs as $s) $annualSubTotal += (float)$s['price'];

// ---------- 同比（年度用） ----------
$lastYearStart = ($year - 1) . '-01-01 00:00:00';
$lastYearEnd   = ($year - 1) . '-12-31 23:59:59';
$stmt = $pdo->prepare('
    SELECT type, COALESCE(SUM(amount), 0) AS total
    FROM transactions
    WHERE user_id = :uid AND trans_time BETWEEN :from AND :to
    GROUP BY type
');
$stmt->execute([':uid' => $userId, ':from' => $lastYearStart, ':to' => $lastYearEnd]);
$lastYearSummary = [];
foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $r) $lastYearSummary[$r['type']] = (float)$r['total'];
$lastYearExpense = $lastYearSummary['expense'] ?? 0.0;
$lastYearIncome  = $lastYearSummary['income']  ?? 0.0;
$yoyExpensePct = $lastYearExpense > 0 ? round(($totalExpense - $lastYearExpense) / $lastYearExpense * 100) : 0;
$yoyIncomePct  = $lastYearIncome  > 0 ? round(($totalIncome  - $lastYearIncome)  / $lastYearIncome  * 100) : 0;

// ---------- 月均收支（年度用） ----------
$monthCount = ($mode === 'yearly') ? 12 : (($mode === 'quarterly') ? 3 : 1);
$avgMonthlyExpense = $totalExpense / $monthCount;
$avgMonthlyIncome  = $totalIncome  / $monthCount;

// ---------- 季度对比（年度用） ----------
function getQuarterlyTrend(PDO $pdo, int $userId, int $year): array {
    $labels = ['Q1', 'Q2', 'Q3', 'Q4'];
    $incomeData = [];
    $expenseData = [];
    for ($q = 1; $q <= 4; $q++) {
        $sm = ($q - 1) * 3 + 1;
        $s = sprintf('%d-%02d-01', $year, $sm);
        $e = date('Y-m-t 23:59:59', strtotime(sprintf('%d-%02d-01', $year, $sm + 2)));
        $stmt = $pdo->prepare('SELECT type, COALESCE(SUM(amount),0) AS t FROM transactions WHERE user_id=:uid AND trans_time BETWEEN :f AND :t GROUP BY type');
        $stmt->execute([':uid' => $userId, ':f' => $s . ' 00:00:00', ':t' => $e]);
        $row = [];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $r) $row[$r['type']] = (float)$r['t'];
        $incomeData[]  = $row['income']  ?? 0.0;
        $expenseData[] = $row['expense'] ?? 0.0;
    }
    return [$labels, $incomeData, $expenseData];
}

// HTML 输出
$appName = Config::get('app.name') ?: '三石记账';
$nowStr = date('Y/m/d H:i:s');
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title><?= htmlspecialchars($reportLabel) ?> - <?= htmlspecialchars($appName) ?></title>
<style>
*{margin:0;padding:0;box-sizing:border-box}
body{font-family:-apple-system,"Microsoft YaHei",sans-serif;transition:background .3s,color .3s;min-height:100vh}
body.light{background:#f5f7fa;color:#333}
body.dark{background:linear-gradient(180deg,#1a1a2e 0%,#16213e 50%,#0f3460 100%);color:#fff}
.w{width:100%;max-width:680px;margin:0 auto;padding:16px 12px 40px}
.btn-theme{position:fixed;top:12px;right:12px;z-index:99;width:36px;height:36px;border-radius:50%;border:none;cursor:pointer;font-size:18px;display:flex;align-items:center;justify-content:center;transition:background .3s}
body.light .btn-theme{background:rgba(0,0,0,.08)}
body.dark .btn-theme{background:rgba(255,255,255,.12)}
.card{border-radius:16px;padding:18px;margin-bottom:14px;transition:background .3s,border .3s}
body.light .card{background:#fff;border:1px solid #e8ecf1}
body.dark .card{background:rgba(255,255,255,.08);border:1px solid rgba(255,255,255,.1);backdrop-filter:blur(10px)}
.card-title{font-size:15px;font-weight:700;margin-bottom:14px}

/* 概览 */
.overview{text-align:center;margin-bottom:14px}
.overview .month{font-size:22px;font-weight:700;margin-bottom:4px;transition:color .3s}
body.light .overview .month{color:#1a1a2e}
body.dark .overview .month{color:#fff}
.overview .sub{font-size:12px;transition:color .3s}
body.light .overview .sub{color:#999}
body.dark .overview .sub{color:rgba(255,255,255,.5)}
.overview .grid{display:flex;gap:8px;margin-top:14px}
.overview .gi{flex:1;text-align:center;padding:12px 0;border-radius:12px;transition:background .3s}
body.light .overview .gi{background:#f5f7fa}
body.dark .overview .gi{background:rgba(255,255,255,.06)}
.overview .gi .v{font-size:18px;font-weight:700}
.overview .gi.i .v{color:#ff6b6b}
.overview .gi.e .v{color:#51cf66}
.overview .gi.b .v{color:#339af0}
.overview .gi .l{font-size:11px;transition:color .3s}
body.light .overview .gi .l{color:#999}
body.dark .overview .gi .l{color:rgba(255,255,255,.5)}
.overview .stats{display:flex;gap:12px;margin-top:10px;justify-content:center;font-size:12px;transition:color .3s}
body.light .overview .stats{color:#999}
body.dark .overview .stats{color:rgba(255,255,255,.5)}
.overview .stats span b{transition:color .3s}
body.light .overview .stats span b{color:#333}
body.dark .overview .stats span b{color:rgba(255,255,255,.8)}

/* 排名 */
.rank-item{display:flex;align-items:center;padding:8px 0;border-bottom:1px solid;transition:border-color .3s}
body.light .rank-item{border-bottom-color:#f0f0f0}
body.dark .rank-item{border-bottom-color:rgba(255,255,255,.06)}
.rank-item:last-child{border-bottom:none}
.rank{width:22px;height:22px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:11px;font-weight:700;color:#fff;flex-shrink:0}
.rank-info{flex:1;margin:0 10px;min-width:0}
.rank-name{font-size:13px;margin-bottom:4px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;transition:color .3s}
body.light .rank-name{color:#333}
body.dark .rank-name{color:rgba(255,255,255,.9)}
.rank-bar{height:6px;border-radius:3px;overflow:hidden;transition:background .3s}
body.light .rank-bar{background:#f0f0f0}
body.dark .rank-bar{background:rgba(255,255,255,.1)}
.rank-bar-fill{height:100%;background:linear-gradient(90deg,#51cf66,#20c997);border-radius:3px}
.rank-right{text-align:right;flex-shrink:0}
.rank-val{font-size:13px;font-weight:600;transition:color .3s}
body.light .rank-val{color:#333}
body.dark .rank-val{color:rgba(255,255,255,.9)}
.rank-change{font-size:11px;margin-top:2px}
.tag{display:inline-block;padding:1px 6px;border-radius:4px;font-size:10px;font-weight:600}
.tag.up{background:rgba(255,107,107,.2);color:#ff6b6b}
.tag.down{background:rgba(81,207,102,.2);color:#51cf66}
.tag.flat{background:rgba(255,255,255,.1);color:rgba(255,255,255,.5)}

/* 日均 */
.stats-row{display:flex;gap:8px;margin-bottom:10px}
.stat-box{flex:1;text-align:center;padding:12px 0;border-radius:12px;transition:background .3s}
body.light .stat-box{background:#f5f7fa}
body.dark .stat-box{background:rgba(255,255,255,.06)}
.stat-box .v{font-size:16px;font-weight:700}
.stat-box .l{font-size:11px;transition:color .3s}
body.light .stat-box .l{color:#999}
body.dark .stat-box .l{color:rgba(255,255,255,.5)}

/* 柱状图 */
.bar-item{display:flex;align-items:center;margin:6px 0}
.bar-name{width:65px;font-size:12px;text-align:right;padding-right:8px;flex-shrink:0;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;transition:color .3s}
body.light .bar-name{color:#666}
body.dark .bar-name{color:rgba(255,255,255,.7)}
.bar-track{flex:1;height:16px;border-radius:8px;overflow:hidden;transition:background .3s}
body.light .bar-track{background:#f0f0f0}
body.dark .bar-track{background:rgba(255,255,255,.1)}
.bar-fill{height:100%;background:linear-gradient(90deg,#ff6b6b,#ee5a24);border-radius:8px}
.bar-fill.inc{background:linear-gradient(90deg,#51cf66,#20c997)}
.bar-val{width:110px;font-size:11px;text-align:right;padding-left:6px;flex-shrink:0;transition:color .3s}
body.light .bar-val{color:#666}
body.dark .bar-val{color:rgba(255,255,255,.6)}

/* 趋势图（纯CSS模拟） */
.chart-container{margin:10px 0}
.chart-bars{display:flex;align-items:flex-end;gap:4px;height:120px;padding:0 4px}
.chart-bar-group{display:flex;flex-direction:column;align-items:center;flex:1;height:100%}
.chart-bar-wrap{flex:1;display:flex;align-items:flex-end;gap:2px;width:100%;justify-content:center}
.chart-bar{width:12px;border-radius:3px 3px 0 0;min-height:2px;transition:background .3s}
body.light .chart-bar{background:#ff6b6b}
body.dark .chart-bar{background:#ff6b6b}
.chart-bar.income{background:#51cf66}
body.light .chart-bar.income{background:#51cf66}
.chart-bar-label{font-size:9px;margin-top:4px;transition:color .3s}
body.light .chart-bar-label{color:#999}
body.dark .chart-bar-label{color:rgba(255,255,255,.5)}
.chart-legend{display:flex;gap:12px;justify-content:center;margin-top:8px;font-size:11px}
.chart-legend-i{display:flex;align-items:center;gap:4px}
.chart-legend-dot{width:8px;height:8px;border-radius:2px}
.chart-legend-dot.e{background:#ff6b6b}
.chart-legend-dot.i{background:#51cf66}

/* 表格 */
table{width:100%;border-collapse:collapse;font-size:12px;margin-top:6px}
th{padding:6px 8px;text-align:left;font-weight:600;transition:color .3s,border-color .3s}
body.light th{color:#999;border-bottom:1px solid #e8ecf1}
body.dark th{color:rgba(255,255,255,.5);border-bottom:1px solid rgba(255,255,255,.1)}
td{padding:6px 8px;transition:color .3s,border-color .3s}
body.light td{color:#333;border-bottom:1px solid #f5f5f5}
body.dark td{color:rgba(255,255,255,.8);border-bottom:1px solid rgba(255,255,255,.05)}
.num{text-align:right;font-weight:600}
.info-row{font-size:13px;transition:color .3s;margin-bottom:8px}
body.light .info-row{color:#666}
body.dark .info-row{color:rgba(255,255,255,.6)}
body.light .info-row b{color:#333}
body.dark .info-row b{color:rgba(255,255,255,.9)}
.empty{color:rgba(255,255,255,.3);font-size:13px;text-align:center;padding:16px}
.footer{text-align:center;font-size:11px;transition:color .3s;margin-top:12px}
body.light .footer{color:#bbb}
body.dark .footer{color:rgba(255,255,255,.3)}

/* 年度额外 */
.yoy{display:inline-block;padding:2px 8px;border-radius:8px;font-size:12px;font-weight:600;margin-left:6px}
.yoy.up{background:rgba(255,107,107,.15);color:#ff6b6b}
.yoy.down{background:rgba(81,207,102,.15);color:#51cf66}
.yoy.flat{background:rgba(255,255,255,.1);color:rgba(255,255,255,.5)}
</style>
<style>
.mode-tabs{display:flex;gap:4px;background:rgba(255,255,255,.1);border-radius:8px;padding:3px}
.mode-tab{padding:5px 14px;border-radius:6px;font-size:13px;text-decoration:none;color:rgba(255,255,255,.6);transition:all .2s}
.mode-tab:hover{color:#fff}
.mode-tab.active{background:rgba(255,255,255,.18);color:#fff;font-weight:600}
body.light .mode-tabs{background:#e8ecf1}
body.light .mode-tab{color:#666}
body.light .mode-tab.active{background:#fff;color:#333;box-shadow:0 1px 3px rgba(0,0,0,.1)}
#datePicker option{background:#1a1a2e;color:#fff}
</style>
</head>
<body class="<?= $theme ?>">
<button class="btn-theme" id="btnTheme" title="切换主题">🌙</button>
<div class="w">

<!-- 日期/模式选择器 -->
<div style="display:flex;justify-content:center;gap:8px;margin-bottom:14px;flex-wrap:wrap;align-items:center">
  <div class="mode-tabs">
    <a href="?mode=monthly&year=<?= $year ?>&month=<?= $month ?>" class="mode-tab <?= $mode==='monthly'?'active':'' ?>">月度</a>
    <a href="?mode=quarterly&year=<?= $year ?>&quarter=<?= $quarter ?>" class="mode-tab <?= $mode==='quarterly'?'active':'' ?>">季度</a>
    <a href="?mode=yearly&year=<?= $year ?>" class="mode-tab <?= $mode==='yearly'?'active':'' ?>">年度</a>
  </div>
  <?php if ($mode === 'monthly'): ?>
  <select id="pickYear" onchange="goMonthly()" style="padding:6px 10px;border-radius:8px;border:1px solid rgba(255,255,255,.2);background:rgba(255,255,255,.08);color:inherit;font-size:13px;cursor:pointer">
    <?php for($y=(int)date('Y');$y>=2020;$y--): $sel=$y===$year?'selected':''; ?>
    <option value="<?= $y ?>" <?= $sel ?>><?= $y ?>年</option>
    <?php endfor; ?>
  </select>
  <select id="pickMonth" onchange="goMonthly()" style="padding:6px 10px;border-radius:8px;border:1px solid rgba(255,255,255,.2);background:rgba(255,255,255,.08);color:inherit;font-size:13px;cursor:pointer">
    <?php for($m=1;$m<=12;$m++): $sel=$m===$month?'selected':''; ?>
    <option value="<?= $m ?>" <?= $sel ?>><?= $m ?>月</option>
    <?php endfor; ?>
  </select>
  <script>function goMonthly(){location.href='?mode=monthly&year='+document.getElementById('pickYear').value+'&month='+document.getElementById('pickMonth').value}</script>
  <?php elseif ($mode === 'quarterly'): ?>
  <select id="pickYear" onchange="goQuarter()" style="padding:6px 10px;border-radius:8px;border:1px solid rgba(255,255,255,.2);background:rgba(255,255,255,.08);color:inherit;font-size:13px;cursor:pointer">
    <?php for($y=(int)date('Y');$y>=2020;$y--): $sel=$y===$year?'selected':''; ?>
    <option value="<?= $y ?>" <?= $sel ?>><?= $y ?>年</option>
    <?php endfor; ?>
  </select>
  <select id="pickQuarter" onchange="goQuarter()" style="padding:6px 10px;border-radius:8px;border:1px solid rgba(255,255,255,.2);background:rgba(255,255,255,.08);color:inherit;font-size:13px;cursor:pointer">
    <?php for($q=1;$q<=4;$q++): $sel=$q===$quarter?'selected':''; ?>
    <option value="<?= $q ?>" <?= $sel ?>>Q<?= $q ?></option>
    <?php endfor; ?>
  </select>
  <script>function goQuarter(){location.href='?mode=quarterly&year='+document.getElementById('pickYear').value+'&quarter='+document.getElementById('pickQuarter').value}</script>
  <?php else: ?>
  <select id="pickYear" onchange="location.href='?mode=yearly&year='+this.value" style="padding:6px 10px;border-radius:8px;border:1px solid rgba(255,255,255,.2);background:rgba(255,255,255,.08);color:inherit;font-size:13px;cursor:pointer">
    <?php for($y=(int)date('Y');$y>=2020;$y--): $sel=$y===$year?'selected':''; ?>
    <option value="<?= $y ?>" <?= $sel ?>><?= $y ?>年度</option>
    <?php endfor; ?>
  </select>
  <?php endif; ?>
</div>

<div class="card">
  <div class="overview">
    <div class="month">📊 <?= htmlspecialchars($reportLabel) ?></div>
    <div class="sub">支出 ¥<?= number_format($totalExpense, 2) ?> | 收入 ¥<?= number_format($totalIncome, 2) ?> | 结余 ¥<?= number_format($balance, 2) ?></div>
    <div class="stats">
      <span>支出 <b><?= $countExpense ?></b> 笔</span>
      <span>收入 <b><?= $countIncome ?></b> 笔</span>
      <span>转账 <b><?= $countTransfer ?></b> 笔</span>
    </div>
    <?php if ($mode === 'yearly'): ?>
    <div class="stats" style="margin-top:6px">
      <span>月均支出 <b>¥<?= number_format($avgMonthlyExpense, 2) ?></b></span>
      <span>月均收入 <b>¥<?= number_format($avgMonthlyIncome, 2) ?></b></span>
    </div>
    <?php endif; ?>
  </div>
</div>

<?php if ($mode === 'yearly' && ($yoyExpensePct !== 0 || $yoyIncomePct !== 0)): ?>
<div class="card">
  <div class="card-title">📈 同比变化（vs <?= $year - 1 ?>年）</div>
  <div class="stats-row">
    <div class="stat-box">
      <div class="v" style="color:#ff6b6b"><?= $yoyExpensePct >= 0 ? '↑' : '↓' ?><?= abs($yoyExpensePct) ?>%</div>
      <div class="l">支出同比</div>
    </div>
    <div class="stat-box">
      <div class="v" style="color:#51cf66"><?= $yoyIncomePct >= 0 ? '↑' : '↓' ?><?= abs($yoyIncomePct) ?>%</div>
      <div class="l">收入同比</div>
    </div>
  </div>
</div>
<?php endif; ?>

<div class="stats-row">
  <div class="stat-box"><div class="v" style="color:#ff6b6b"><?= $peakExpenseDay ?></div><div class="l">支出最高日 ¥<?= number_format($peakExpenseVal, 2) ?></div></div>
  <div class="stat-box"><div class="v" style="color:#51cf66"><?= $peakIncomeDay ?></div><div class="l">收入最高日 ¥<?= number_format($peakIncomeVal, 2) ?></div></div>
  <div class="stat-box"><div class="v" style="color:#339af0">¥<?= number_format($avgExpense, 2) ?></div><div class="l">日均支出</div></div>
</div>

<?php if ($mode === 'quarterly' || $mode === 'yearly'): ?>
<?php
  if ($mode === 'quarterly') {
    [$tLabels, $tIncome, $tExpense] = getMonthlyTrend($pdo, $userId, $year, $monthList[0], $monthList[2]);
  } else {
    [$tLabels, $tIncome, $tExpense] = getMonthlyTrend($pdo, $userId, $year, 1, 12);
  }
  $tMax = max(array_merge($tIncome, $tExpense, [1]));
  $tScale = 100 / $tMax;
?>
<div class="card">
  <div class="card-title">📈 <?= $mode === 'quarterly' ? '季度收支趋势' : '年度收支趋势' ?></div>
  <div class="chart-container">
    <div class="chart-bars">
      <?php for ($i = 0; $i < count($tLabels); $i++): ?>
      <div class="chart-bar-group">
        <div class="chart-bar-wrap">
          <div class="chart-bar" style="height:<?= round(($tExpense[$i] ?? 0) * $tScale) ?>px" title="支出: ¥<?= number_format($tExpense[$i] ?? 0, 2) ?>"></div>
          <div class="chart-bar income" style="height:<?= round(($tIncome[$i] ?? 0) * $tScale) ?>px" title="收入: ¥<?= number_format($tIncome[$i] ?? 0, 2) ?>"></div>
        </div>
        <div class="chart-bar-label"><?= htmlspecialchars($tLabels[$i]) ?></div>
      </div>
      <?php endfor; ?>
    </div>
    <div class="chart-legend">
      <div class="chart-legend-i"><div class="chart-legend-dot e"></div>支出</div>
      <div class="chart-legend-i"><div class="chart-legend-dot i"></div>收入</div>
    </div>
  </div>
</div>
<?php endif; ?>

<?php if ($mode === 'yearly'): ?>
<?php
  [$qLabels, $qIncome, $qExpense] = getQuarterlyTrend($pdo, $userId, $year);
  $qMax = max(array_merge($qIncome, $qExpense, [1]));
  $qScale = 100 / $qMax;
?>
<div class="card">
  <div class="card-title">📊 季度对比</div>
  <div class="chart-container">
    <div class="chart-bars">
      <?php for ($i = 0; $i < 4; $i++): ?>
      <div class="chart-bar-group">
        <div class="chart-bar-wrap">
          <div class="chart-bar" style="height:<?= round(($qExpense[$i] ?? 0) * $qScale) ?>px" title="支出: ¥<?= number_format($qExpense[$i] ?? 0, 2) ?>"></div>
          <div class="chart-bar income" style="height:<?= round(($qIncome[$i] ?? 0) * $qScale) ?>px" title="收入: ¥<?= number_format($qIncome[$i] ?? 0, 2) ?>"></div>
        </div>
        <div class="chart-bar-label"><?= $qLabels[$i] ?></div>
      </div>
      <?php endfor; ?>
    </div>
  </div>
  <table>
    <tr><th>季度</th><th class="num">收入</th><th class="num">支出</th><th class="num">结余</th></tr>
    <?php for ($i = 0; $i < 4; $i++): ?>
    <tr>
      <td><?= $qLabels[$i] ?></td>
      <td class="num" style="color:#51cf66">¥<?= number_format($qIncome[$i] ?? 0, 2) ?></td>
      <td class="num" style="color:#ff6b6b">¥<?= number_format($qExpense[$i] ?? 0, 2) ?></td>
      <td class="num">¥<?= number_format(($qIncome[$i] ?? 0) - ($qExpense[$i] ?? 0), 2) ?></td>
    </tr>
    <?php endfor; ?>
  </table>
</div>
<?php endif; ?>

<div class="card">
  <div class="card-title">💸 支出项目</div>
  <?php if (empty($expenseWithChange)): ?><div class="empty">暂无支出数据</div><?php else: ?>
  <?php foreach ($expenseWithChange as $i => $item):
    $pct = $item['pct'];
    $tag = $pct > 0 ? ($pct > 100 ? '↑' . min($pct, 999) : '↑' . $pct) : ($pct < 0 ? '↓' . abs($pct) : '—');
    $tagClass = $pct > 0 ? 'up' : ($pct < 0 ? 'down' : 'flat');
  ?>
  <div class="rank-item">
    <div class="rank" style="background:<?= $i == 0 ? '#FFD700' : ($i == 1 ? '#5B9BD5' : ($i == 2 ? '#70AD47' : '#999')) ?>"><?= $i + 1 ?></div>
    <div class="rank-info">
      <div class="rank-name"><?= htmlspecialchars($item['name']) ?></div>
      <div class="rank-bar"><div class="rank-bar-fill" style="width:<?= $expenseMax2 > 0 ? min(round($item['cur'] / $expenseMax2 * 100), 100) : 0 ?>%"></div></div>
    </div>
    <div class="rank-right">
      <div class="rank-val">¥<?= number_format($item['cur'], 2) ?></div>
      <div class="rank-change"><span class="tag <?= $tagClass ?>"><?= $tag ?>%</span></div>
    </div>
  </div>
  <?php endforeach; ?>
  <?php endif; ?>
</div>

<div class="card">
  <div class="card-title">💰 收入项目</div>
  <?php if (empty($incomeWithChange)): ?><div class="empty">暂无收入数据</div><?php else: ?>
  <?php foreach ($incomeWithChange as $i => $item):
    $pct = $item['pct'];
    $tag = $pct > 0 ? ($pct > 100 ? '↑' . min($pct, 999) : '↑' . $pct) : ($pct < 0 ? '↓' . abs($pct) : '—');
    $tagClass = $pct > 0 ? 'up' : ($pct < 0 ? 'down' : 'flat');
  ?>
  <div class="rank-item">
    <div class="rank" style="background:<?= $i == 0 ? '#FFD700' : ($i == 1 ? '#5B9BD5' : ($i == 2 ? '#70AD47' : '#999')) ?>"><?= $i + 1 ?></div>
    <div class="rank-info">
      <div class="rank-name"><?= htmlspecialchars($item['name']) ?></div>
      <div class="rank-bar"><div class="rank-bar-fill" style="width:<?= $incomeMax2 > 0 ? min(round($item['cur'] / $incomeMax2 * 100), 100) : 0 ?>%"></div></div>
    </div>
    <div class="rank-right">
      <div class="rank-val">¥<?= number_format($item['cur'], 2) ?></div>
      <div class="rank-change"><span class="tag <?= $tagClass ?>"><?= $tag ?>%</span></div>
    </div>
  </div>
  <?php endforeach; ?>
  <?php endif; ?>
</div>

<?php if (!empty($transferCategories)): ?>
<div class="card">
  <div class="card-title">🔄 转账项目</div>
  <?php foreach ($transferCategories as $i => $item):
    $catName = $item['item_name'] ?: '未分类';
    $total = (float)$item['total'];
  ?>
  <div class="rank-item">
    <div class="rank" style="background:<?= $i == 0 ? '#FFD700' : ($i == 1 ? '#5B9BD5' : ($i == 2 ? '#70AD47' : '#999')) ?>"><?= $i + 1 ?></div>
    <div class="rank-info">
      <div class="rank-name"><?= htmlspecialchars($catName) ?></div>
      <div class="rank-bar"><div class="rank-bar-fill" style="width:<?= $transferMax > 0 ? min(round($total / $transferMax * 100), 100) : 0 ?>%"></div></div>
    </div>
    <div class="rank-right">
      <div class="rank-val">¥<?= number_format($total, 2) ?></div>
    </div>
  </div>
  <?php endforeach; ?>
</div>
<?php endif; ?>

<div class="card">
  <div class="card-title">📊 支出 TOP 5</div>
  <?php
  $topExpense = array_slice($expenseWithChange, 0, 5);
  $expenseMaxTop = count($topExpense) > 0 ? $topExpense[0]['cur'] : 1;
  ?>
  <?php if (empty($topExpense)): ?><div class="empty">暂无数据</div><?php else: ?>
  <?php foreach ($topExpense as $item): ?>
  <div class="bar-item">
    <div class="bar-name"><?= htmlspecialchars($item['name']) ?></div>
    <div class="bar-track"><div class="bar-fill" style="width:<?= $expenseMaxTop > 0 ? min(round($item['cur'] / $expenseMaxTop * 100), 100) : 0 ?>%"></div></div>
    <div class="bar-val">¥<?= number_format($item['cur'], 2) ?> (<?= $expenseMaxTop > 0 ? round($item['cur'] / array_sum(array_column($expenseWithChange, 'cur')) * 100) : 0 ?>%)</div>
  </div>
  <?php endforeach; ?>
  <?php endif; ?>
</div>

<div class="card">
  <div class="card-title">📊 收入 TOP 5</div>
  <?php
  $topIncome = array_slice($incomeWithChange, 0, 5);
  $incomeMaxTop = count($topIncome) > 0 ? $topIncome[0]['cur'] : 1;
  ?>
  <?php if (empty($topIncome)): ?><div class="empty">暂无数据</div><?php else: ?>
  <?php foreach ($topIncome as $item): ?>
  <div class="bar-item">
    <div class="bar-name"><?= htmlspecialchars($item['name']) ?></div>
    <div class="bar-track"><div class="bar-fill inc" style="width:<?= $incomeMaxTop > 0 ? min(round($item['cur'] / $incomeMaxTop * 100), 100) : 0 ?>%"></div></div>
    <div class="bar-val">¥<?= number_format($item['cur'], 2) ?> (<?= $incomeMaxTop > 0 ? round($item['cur'] / array_sum(array_column($incomeWithChange, 'cur')) * 100) : 0 ?>%)</div>
  </div>
  <?php endforeach; ?>
  <?php endif; ?>
</div>

<?php if ($mode === 'monthly' || $mode === 'quarterly'): ?>
<div class="card">
  <div class="card-title">📋 近期还款计划</div>
  <div class="info-row">次月应还 <b>¥<?= number_format(array_sum(array_column($upcomingPayments, 'total_amount')), 2) ?></b>　共 <b><?= count($upcomingPayments) ?></b> 笔</div>
  <?php if (empty($upcomingPayments)): ?><div class="empty">暂无待还款项</div><?php else: ?>
  <table>
    <tr><th>到期日</th><th>项目</th><th class="num">金额</th><th>剩余</th></tr>
    <?php foreach ($upcomingPayments as $p): ?>
    <tr>
      <td><?= date('n/j', strtotime($p['due_date'])) ?></td>
      <td><?= htmlspecialchars($p['debt_name'] ?: '还款') ?></td>
      <td class="num">¥<?= number_format((float)$p['total_amount'], 2) ?></td>
      <td><?= (int)$p['period_number'] ?>期</td>
    </tr>
    <?php endforeach; ?>
  </table>
  <?php endif; ?>
</div>
<?php endif; ?>

<?php if ($mode === 'yearly'): ?>
<div class="card">
  <div class="card-title">📋 年度还款汇总</div>
  <?php
  $stmt = $pdo->prepare('
    SELECT dc.name, COUNT(*) AS total_periods,
           SUM(CASE WHEN dp.status="paid" THEN 1 ELSE 0 END) AS paid_periods,
           SUM(CASE WHEN dp.status!="paid" THEN dp.total_amount ELSE 0 END) AS remaining,
           SUM(CASE WHEN dp.status="paid" THEN dp.paid_amount ELSE 0 END) AS paid_total
    FROM debt_config dc
    LEFT JOIN debt_payment dp ON dp.debt_config_id = dc.id
    WHERE dc.user_id = :uid
    GROUP BY dc.id
    HAVING total_periods > 0
  ');
  $stmt->execute([':uid' => $userId]);
  $debtSummary = $stmt->fetchAll(PDO::FETCH_ASSOC);
  $totalPaidAll = 0; $totalRemainingAll = 0;
  foreach ($debtSummary as $d) { $totalPaidAll += (float)$d['paid_total']; $totalRemainingAll += (float)$d['remaining']; }
  ?>
  <div class="info-row">已还 <b>¥<?= number_format($totalPaidAll, 2) ?></b>　待还 <b>¥<?= number_format($totalRemainingAll, 2) ?></b>　负债笔数 <b><?= count($debtSummary) ?></b></div>
  <?php if (empty($debtSummary)): ?><div class="empty">暂无负债数据</div><?php else: ?>
  <table>
    <tr><th>项目</th><th class="num">已还</th><th class="num">待还</th><th>进度</th></tr>
    <?php foreach ($debtSummary as $d): ?>
    <tr>
      <td><?= htmlspecialchars($d['name']) ?></td>
      <td class="num">¥<?= number_format((float)$d['paid_total'], 2) ?></td>
      <td class="num">¥<?= number_format((float)$d['remaining'], 2) ?></td>
      <td><?= (int)$d['paid_periods'] ?>/<?= (int)$d['total_periods'] ?></td>
    </tr>
    <?php endforeach; ?>
  </table>
  <?php endif; ?>
</div>
<?php endif; ?>

<div class="card">
  <div class="card-title">🔄 订阅到期（3个月内）</div>
  <div class="info-row">活跃 <b><?= $activeSubCount ?></b> 个　年度总额 <b>¥<?= number_format($annualSubTotal, 2) ?></b></div>
  <?php if (empty($upcomingSubs)): ?><div class="empty">暂无订阅即将到期</div><?php else: ?>
  <table>
    <tr><th>平台</th><th>到期日</th><th>剩余</th><th>费用</th></tr>
    <?php foreach ($upcomingSubs as $s):
      $daysLeft = ceil((strtotime($s['expire_date']) - time()) / 86400);
    ?>
    <tr>
      <td><?= htmlspecialchars($s['platform']) ?></td>
      <td><?= date('Y-m-d', strtotime($s['expire_date'])) ?></td>
      <td><?= $daysLeft ?>天</td>
      <td class="num">¥<?= number_format((float)$s['price'], 2) ?>/<?= htmlspecialchars($s['period'] ?: '月') ?></td>
    </tr>
    <?php endforeach; ?>
  </table>
  <?php endif; ?>
</div>

<div class="footer">📅 <?= $nowStr ?> · <?= htmlspecialchars($appName) ?> × QClaw</div>

</div>

<script>
(function() {
    const body = document.body;
    const btn = document.getElementById('btnTheme');
    
    // 读取偏好
    const saved = localStorage.getItem('report-theme');
    if (saved === 'light') {
        body.className = 'light';
        btn.textContent = '☀️';
    } else {
        body.className = 'dark';
        btn.textContent = '🌙';
    }
    
    btn.addEventListener('click', function() {
        if (body.className === 'dark') {
            body.className = 'light';
            btn.textContent = '☀️';
            localStorage.setItem('report-theme', 'light');
        } else {
            body.className = 'dark';
            btn.textContent = '🌙';
            localStorage.setItem('report-theme', 'dark');
        }
    });
})();
</script>
</body>
</html>
