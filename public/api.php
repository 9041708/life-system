<?php
// Simple JSON API front controller for mini program / mobile clients

declare(strict_types=1);

require __DIR__ . '/../src/bootstrap.php';

use App\Model\User;
use App\Model\ApiToken;
use App\Model\UserWechatBinding;
use App\Model\Account;
use App\Model\Category;
use App\Model\Item;
use App\Model\Budget;
use App\Model\Transaction;
use App\Model\TransactionAttachment;
use App\Model\Ledger;
use App\Model\LedgerMember;
use App\Model\Feedback;
use App\Model\IconLibrary;
use App\Model\Goal;
use App\Model\LoginToken;
use App\Model\LicenseUser;
use App\Model\Announcement;
use App\Model\AnnouncementRead;
use App\Model\Asset;
use App\Model\Subscription;
use App\Model\DebtConfig;
use App\Model\DebtPayment;
use App\Model\Reimbursement;
use App\Model\ReimbursementConfig;
use App\Service\Config;
use App\Service\WeChatMiniApp;
use App\Service\Database;
use App\Service\Upload;
use App\Service\Seeder;
use App\Service\Ai;
use App\Service\LedgerContext;
// PDO 在全局命名空间下直接使用 PDO:: 即可，这里无需 use

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: 0');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 解析路由参数（如 ?route=user/stats），统一去掉首尾斜杠
$route = isset($_GET['route']) ? trim((string)$_GET['route'], '/') : '';

// 通用 JSON 输出助手，统一设置状态码与响应头后结束脚本
function json_response(int $statusCode, array $payload): void {
    if (!headers_sent()) {
        http_response_code($statusCode);
        header('Content-Type: application/json; charset=utf-8');
    }
    echo json_encode($payload, JSON_UNESCAPED_UNICODE);
    exit;
}

// 从请求体中解析 JSON，解析失败时返回空数组
function parse_json_body(): array {
    $raw = file_get_contents('php://input');
    if ($raw === false || $raw === '') {
        return [];
    }
    $data = json_decode($raw, true);
    return is_array($data) ? $data : [];
}

// 构造当前站点下上传文件的完整访问 URL
function build_file_url(?string $relativePath): ?string {
    if ($relativePath === null || $relativePath === '') {
        return null;
    }
    $relativePath = (string)$relativePath;
    // 已经是绝对 URL 的情况直接返回
    if (preg_match('~^https?://~i', $relativePath)) {
        return $relativePath;
    }
    $relativePath = ltrim($relativePath, '/\\');
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    // 默认上传目录为 /uploads
    return $scheme . '://' . $host . '/uploads/' . $relativePath;
}

// 将用户表记录转换为小程序/客户端需要的字段结构
function build_user_payload(array $user): array {
    $avatarUrl = null;
    if (!empty($user['avatar_path'])) {
        $avatarUrl = build_file_url((string)$user['avatar_path']);
    }
    return [
        'id' => isset($user['id']) ? (int)$user['id'] : 0,
        'username' => (string)($user['username'] ?? ''),
        'nickname' => (string)($user['nickname'] ?? ''),
        'email' => (string)($user['email'] ?? ''),
        'role' => (string)($user['role'] ?? 'user'),
        'theme_mode' => (string)($user['theme_mode'] ?? 'light'),
        'avatar_url' => $avatarUrl,
        'budget_reminder_enabled' => isset($user['budget_reminder_enabled'])
            ? ((int)$user['budget_reminder_enabled'] === 1)
            : true,
        // 是否启用账户间转账功能（PC 端设置页中的开关），默认关闭
        'enable_transfer' => isset($user['enable_transfer'])
            ? ((int)$user['enable_transfer'] === 1)
            : false,
        // 是否允许账户余额为负数（PC / 小程序设置中的开关），默认不允许
        'allow_negative_balance' => isset($user['allow_negative_balance'])
            ? ((int)$user['allow_negative_balance'] === 1)
            : false,
    ];
}

// 从请求头中提取 Bearer Token
function get_bearer_token(): ?string {
    $header = '';
    if (isset($_SERVER['HTTP_AUTHORIZATION'])) {
        $header = (string)$_SERVER['HTTP_AUTHORIZATION'];
    } elseif (isset($_SERVER['Authorization'])) { // 部分环境兼容
        $header = (string)$_SERVER['Authorization'];
    }
    if ($header === '') {
        return null;
    }
    if (stripos($header, 'Bearer ') === 0) {
        return trim(substr($header, 7));
    }
    return trim($header);
}

// 鉴权并返回当前登录用户信息；失败时直接以 JSON 形式返回 401/403
function require_auth_user(): array {
    $token = get_bearer_token();
    if (!$token) {
        json_response(401, ['success' => false, 'error' => '未登录或缺少 Token']);
    }

    $tokenRow = ApiToken::findValidToken($token, null);
    if (!$tokenRow) {
        json_response(401, ['success' => false, 'error' => '登录已过期，请重新登录']);
    }

    $user = User::findById((int)$tokenRow['user_id']);
    if (!$user) {
        json_response(401, ['success' => false, 'error' => '用户不存在']);
    }
    if (isset($user['status']) && (int)$user['status'] !== 1) {
        json_response(403, ['success' => false, 'error' => '账号已被禁用']);
    }

    return $user;
}
/**
 * 兼容数据库编码（如仅 utf8 而非 utf8mb4），对微信昵称做一次简单清洗：
 * - 去掉 4 字节及以上的 Unicode 字符（大部分表情符号），避免插入时出现 "Incorrect string value"。
 * - 保留常规中文、英文与常用符号。
 */
function sanitize_wechat_nickname(string $nickname): string {
    // 移除 4 字节以上的 Unicode 字符
    $clean = @preg_replace('/[\x{10000}-\x{10FFFF}]/u', '', $nickname);
    if ($clean === null) {
        $clean = $nickname; // 正则失败时退回原值
    }
    $clean = trim($clean);
    return $clean;
}

function normalize_trans_time(?string $input): string {
    $input = trim((string)$input);
    if ($input === '') {
        return date('Y-m-d H:i:s');
    }
    $input = str_replace('/', '-', $input);
    if (strpos($input, 'T') !== false) {
        $input = str_replace('T', ' ', $input);
    }
    if (preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}$/', $input)) {
        $input .= ':00';
    }
    $dt = date_create($input);
    if ($dt === false) {
        return date('Y-m-d H:i:s');
    }
    return $dt->format('Y-m-d H:i:s');
}

function apply_balance_change(string $type, int $fromAccountId, int $toAccountId, float $amount, int $direction): void {
    if ($amount <= 0) {
        return;
    }
    $delta = $amount * $direction;
    if ($type === 'expense') {
        if ($fromAccountId) {
            Account::adjustBalance($fromAccountId, -$delta);
        }
    } elseif ($type === 'income') {
        if ($toAccountId) {
            Account::adjustBalance($toAccountId, $delta);
        }
    } elseif ($type === 'transfer') {
        // 转账：转出账户减少，转入账户增加，总资产不变
        if ($fromAccountId) {
            Account::adjustBalance($fromAccountId, -$delta);
        }
        if ($toAccountId) {
            Account::adjustBalance($toAccountId, $delta);
        }
    }
}

function summarize_budget_by_month(int $userId, int $year, int $month, int $ledgerId = 0): array {
    $budgets = $ledgerId > 0
        ? Budget::listByLedgerMonth($ledgerId, $year, $month)
        : Budget::listByUserMonth($userId, $year, $month);

    if (empty($budgets)) {
        return [0.0, 0.0];
    }

    $pdo = Database::getConnection();
    $totalBudgetExpense = 0.0;
    $totalUsedExpense = 0.0;

    foreach ($budgets as $b) {
        $sql = 'SELECT COALESCE(SUM(amount),0) AS used_amount FROM transactions WHERE ';
        $params = [
            ':type' => $b['type'],
            ':y' => $year,
            ':m' => $month,
        ];
        if ($ledgerId > 0) {
            $sql .= 'ledger_id = :lid';
            $params[':lid'] = $ledgerId;
        } else {
            $sql .= 'user_id = :uid';
            $params[':uid'] = $userId;
        }
        $sql .= ' AND type = :type AND YEAR(trans_time) = :y AND MONTH(trans_time) = :m';

        if (!empty($b['category_id'])) {
            $sql .= ' AND category_id = :cid';
            $params[':cid'] = $b['category_id'];
        }
        if (!empty($b['item_id'])) {
            $sql .= ' AND item_id = :iid';
            $params[':iid'] = $b['item_id'];
        }

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $row = $stmt->fetch(PDO::FETCH_ASSOC) ?: ['used_amount' => 0];
        $usedAmount = (float)$row['used_amount'];

        if ($b['type'] === 'expense') {
            $totalBudgetExpense += (float)$b['amount'];
            $totalUsedExpense += $usedAmount;
        }
    }

    return [$totalBudgetExpense, $totalUsedExpense];
}

function build_signed_path(string $path, int $ts, string $nonce, string $signature): string {
    if (strpos($path, '?') === false) {
        return $path . '?ts=' . $ts . '&nonce=' . $nonce . '&sig=' . $signature;
    }
    return $path . '&ts=' . $ts . '&nonce=' . $nonce . '&sig=' . $signature;
}

switch ($route) {
    case 'license/check': {
        // 授权联机校验接口，供打包版等客户端调用
        $body = parse_json_body();

        // 允许通过 JSON 或表单/查询参数传递
        $email = trim((string)($body['email'] ?? ($_POST['email'] ?? ($_GET['email'] ?? ''))));
        $licenseCode = trim((string)($body['license_code'] ?? ($_POST['license_code'] ?? ($_GET['license_code'] ?? ''))));
        $domain = trim((string)($body['domain'] ?? ($_POST['domain'] ?? ($_GET['domain'] ?? ''))));
        $version = trim((string)($body['version'] ?? ($_POST['version'] ?? ($_GET['version'] ?? ''))));

        if ($licenseCode === '') {
            json_response(400, [
                'success' => false,
                'status' => 'missing_param',
                'message' => '缺少授权码',
            ]);
        }

        try {
            // 优先按授权码查找授权用户；如同时提供邮箱则可在后续扩展校验逻辑
            if (method_exists('App\\Model\\LicenseUser', 'findByCode')) {
                $user = LicenseUser::findByCode($licenseCode);
            } else {
                $user = LicenseUser::findByEmailAndCode($email, $licenseCode);
            }
        } catch (\Throwable $e) {
            json_response(500, [
                'success' => false,
                'status' => 'server_error',
                'message' => '授权服务器查询异常',
            ]);
        }

        if (!$user) {
            json_response(200, [
                'success' => false,
                'status' => 'not_found',
                'message' => '未找到对应授权，请核对邮箱和授权码。',
            ]);
        }

        $status = (string)($user['license_status'] ?? 'normal');
        if ($status === 'expired') {
            json_response(200, [
                'success' => false,
                'status' => 'expired',
                'message' => '授权已停用或到期，请联系管理员处理。',
            ]);
        }

        // 如传入当前域名且授权中已绑定域名，则要求匹配
        $bindDomain = trim((string)($user['domain'] ?? ''));
        if ($domain !== '' && $bindDomain !== '' && strcasecmp($domain, $bindDomain) !== 0) {
            json_response(200, [
                'success' => false,
                'status' => 'domain_mismatch',
                'message' => '授权绑定域名不匹配，请核对授权信息。',
            ]);
        }

        // 走到这里说明授权有效，更新授权用户的激活时间和最后在线时间，并将状态标记为 normal
        try {
            $pdo = Database::getConnection();
            $stmtUpd = $pdo->prepare('UPDATE license_users SET license_status = "normal", activated_at = IF(activated_at IS NULL, NOW(), activated_at), last_online_at = NOW() WHERE id = :id');
            $stmtUpd->execute([':id' => (int)$user['id']]);
        } catch (\Throwable $e) {
            // 后台统计失败不影响客户端正常使用
        }

        $status = 'normal';

        // 预留后续根据版本号做策略控制
        $payload = [
            'email' => (string)$user['email'],
            'domain' => $bindDomain,
            'license_type' => (string)($user['license_type'] ?? ''),
            'license_period' => (string)($user['license_period'] ?? ''),
            'license_status' => $status,
            'version' => $version,
        ];

        json_response(200, [
            'success' => true,
            'status' => 'normal',
            'message' => '授权有效',
            'data' => $payload,
        ]);
        break;
    }
    case 'share/sign': {
        // 为小程序分享生成签名，前端在 onLoad 等时机调用
        $body = parse_json_body();
        $path = trim((string)($body['path'] ?? ''));
        if ($path === '') {
            json_response(400, ['success' => false, 'error' => '缺少 path']);
        }

        $secret = Config::get('wechat.share_secret', '');
        if ($secret === '') {
            json_response(500, ['success' => false, 'error' => '未配置分享签名密钥']);
        }

        $ts = time();
        $nonce = bin2hex(random_bytes(8));
        $payload = $path . '|' . $ts . '|' . $nonce;
        $signature = hash_hmac('sha256', $payload, $secret);
        $signedPath = build_signed_path($path, $ts, $nonce, $signature);

        json_response(200, [
            'success' => true,
            'path' => $path,
            'ts' => $ts,
            'nonce' => $nonce,
            'signature' => $signature,
            'signed_path' => $signedPath,
        ]);
        break;
    }
    case 'wechat/bind-by-token': {
        $body = parse_json_body();
        $code = trim((string)($body['code'] ?? ''));
        $bindToken = trim((string)($body['token'] ?? ''));
        if ($code === '' || $bindToken === '') {
            json_response(400, ['success' => false, 'error' => '缺少参数']);
        }

        $res = WeChatMiniApp::code2Session($code);
        if (!$res['success'] || !$res['openid']) {
            json_response(400, ['success' => false, 'error' => $res['error'] ?? '微信登录失败']);
        }
        $openid = (string)$res['openid'];
        $unionid = $res['unionid'] ?? null;

        $row = LoginToken::findByToken($bindToken);
        if (!$row) {
            json_response(400, ['success' => false, 'error' => '二维码无效或已过期']);
        }
        if (($row['status'] ?? '') !== 'pending') {
            json_response(400, ['success' => false, 'error' => '二维码已使用或失效']);
        }
        $expiresAt = strtotime((string)$row['expires_at'] ?? '') ?: 0;
        if ($expiresAt > 0 && $expiresAt < time()) {
            json_response(400, ['success' => false, 'error' => '二维码已过期']);
        }
        $userId = (int)($row['user_id'] ?? 0);
        if ($userId <= 0) {
            json_response(400, ['success' => false, 'error' => '二维码无效']);
        }

        if (UserWechatBinding::findByOpenid($openid)) {
            json_response(400, ['success' => false, 'error' => '该微信已绑定过账号']);
        }

        $bindingId = UserWechatBinding::create($userId, $openid, $unionid ? (string)$unionid : null);
        UserWechatBinding::updateLastLogin($bindingId);

        // 标记该绑定二维码已用
        LoginToken::confirm($bindToken, $userId);

        $token = ApiToken::createToken($userId, 'miniapp', 30 * 24 * 60 * 60);
        $user = User::findById($userId);

        json_response(200, [
            'success' => true,
            'token' => $token,
            'user' => build_user_payload($user),
        ]);
        break;
    }
    case 'wechat/bind-by-password': {
        // 小程序端通过账号密码直接绑定，无需扫码
        $body = parse_json_body();
        $code = trim($body['code'] ?? '');
        $account = trim($body['account'] ?? ''); // 用户名或邮箱
        $password = (string)($body['password'] ?? '');

        if ($code === '' || $account === '' || $password === '') {
            json_response(400, ['success' => false, 'error' => '参数不完整']);
        }

        try {
            $wx = new \App\Service\WeChatMiniApp();
            $session = $wx->code2Session($code);
            $openid = $session['openid'] ?? null;
            $unionid = $session['unionid'] ?? null;
            if (!$openid) {
                json_response(400, ['success' => false, 'error' => '获取 openid 失败']);
            }

            // 账号查找（用户名或邮箱）
            $user = filter_var($account, FILTER_VALIDATE_EMAIL)
                ? \App\Model\User::findByEmail($account)
                : \App\Model\User::findByUsername($account);
            if (!$user || !password_verify($password, $user['password_hash'])) {
                json_response(401, ['success' => false, 'error' => '账号或密码错误']);
            }

            $userId = (int)$user['id'];

            // 确保 openid 未绑定其他账号
            $exists = \App\Model\UserWechatBinding::findByOpenid($openid);
            if ($exists && (int)$exists['user_id'] !== $userId) {
                json_response(409, ['success' => false, 'error' => '该微信已绑定其他账号']);
            }

            if ($exists) {
                \App\Model\UserWechatBinding::updateLastLogin((int)$exists['id']);
            } else {
                \App\Model\UserWechatBinding::create($userId, $openid, $unionid);
            }

            $apiToken = \App\Model\ApiToken::createToken($userId, 'miniapp', 30 * 24 * 60 * 60);
            json_response(200, [
                'success' => true,
                'token' => $apiToken,
                'user' => build_user_payload($user),
            ]);
        } catch (\Throwable $e) {
            json_response(500, ['success' => false, 'error' => '绑定失败：' . $e->getMessage()]);
        }
        break;
    }
    case 'wechat/login-or-check-bind': {
        $body = parse_json_body();
        $code = trim((string)($body['code'] ?? ''));
        if ($code === '') {
            json_response(400, ['success' => false, 'error' => '缺少 code']);
        }

        $res = WeChatMiniApp::code2Session($code);
        if (!$res['success'] || !$res['openid']) {
            json_response(400, ['success' => false, 'error' => $res['error'] ?? '微信登录失败']);
        }
        $openid = (string)$res['openid'];
        $unionid = $res['unionid'] ?? null;

        $binding = UserWechatBinding::findByOpenid($openid);
        if ($binding) {
            $user = User::findById((int)$binding['user_id']);
            if (!$user) {
                json_response(400, ['success' => false, 'error' => '绑定的用户不存在']);
            }
            if ((int)$user['status'] !== 1) {
                json_response(403, ['success' => false, 'error' => '账号已被禁用']);
            }
            if ((int)$user['email_verified'] !== 1) {
                json_response(403, ['success' => false, 'error' => '邮箱尚未验证']);
            }

            UserWechatBinding::updateLastLogin((int)$binding['id']);
            $token = ApiToken::createToken((int)$user['id'], 'miniapp', 30 * 24 * 60 * 60);

            json_response(200, [
                'success' => true,
                'need_bind' => false,
                'token' => $token,
                'user' => build_user_payload($user),
            ]);
        } else {
            json_response(200, [
                'success' => true,
                'need_bind' => true,
            ]);
        }
        break;
    }

    // 新增：小程序一键登录（已绑定→直接登录；未绑定→自动注册+绑定→登录）
    case 'wechat/auto-login': {
        try {
            $body = parse_json_body();
            $code = trim((string)($body['code'] ?? ''));
            $nickname = trim((string)($body['nickname'] ?? ''));
            $avatarUrl = trim((string)($body['avatar_url'] ?? ''));
            if ($code === '') {
                json_response(400, ['success' => false, 'error' => '缺少 code']);
            }

            $res = WeChatMiniApp::code2Session($code);
            if (!$res['success'] || !$res['openid']) {
                json_response(400, ['success' => false, 'error' => $res['error'] ?? '微信登录失败']);
            }
            $openid = (string)$res['openid'];
            $unionid = $res['unionid'] ?? null;

            $binding = UserWechatBinding::findByOpenid($openid);
            if ($binding) {
                $user = User::findById((int)$binding['user_id']);
                if (!$user) {
                    json_response(400, ['success' => false, 'error' => '绑定的用户不存在']);
                }
                if ((int)$user['status'] !== 1) {
                    json_response(403, ['success' => false, 'error' => '账号已被禁用']);
                }

                // 若当前用户昵称仍然是占位的“微信用户”，且本次登录携带了真实昵称，则自动刷新为微信昵称
                if ($nickname !== '' && (string)($user['nickname'] ?? '') === '微信用户') {
                    $nicknameClean = sanitize_wechat_nickname($nickname);
                    if ($nicknameClean !== '') {
                        if (mb_strlen($nicknameClean, 'UTF-8') > 50) {
                            $nicknameClean = mb_substr($nicknameClean, 0, 50, 'UTF-8');
                        }
                        User::updateProfile((int)$user['id'], (string)$user['username'], $nicknameClean);
                        $user = User::findById((int)$user['id']);
                    }
                }

                // 若提供了头像 URL，则尝试更新本地头像
                if ($avatarUrl !== '') {
                    $oldAvatar = $user['avatar_path'] ?? null;
                    $newAvatar = Upload::saveAvatarFromUrl((int)$user['id'], $avatarUrl);
                    if ($newAvatar !== null) {
                        \App\Model\User::updateAvatarPath((int)$user['id'], $newAvatar);
                        if ($oldAvatar && $oldAvatar !== $newAvatar) {
                            Upload::deleteByRelativePath($oldAvatar);
                        }
                        $user = User::findById((int)$user['id']);
                    }
                }

                UserWechatBinding::updateLastLogin((int)$binding['id']);
                $token = ApiToken::createToken((int)$user['id'], 'miniapp', 30 * 24 * 60 * 60);
                json_response(200, [
                    'success' => true,
                    'token' => $token,
                    'user' => build_user_payload($user),
                ]);
            } else {
                // 自动注册：用户名用 openid，昵称优先客户端传入，否则设为“微信用户”
                $username = $openid;
                if ($nickname !== '') {
                    $nickname = sanitize_wechat_nickname($nickname);
                }
                $nickname = $nickname !== '' ? $nickname : '微信用户';
                $avatarUrl = trim((string)($body['avatar_url'] ?? ''));

                // 为避免 email 唯一约束冲突，为每个小程序用户生成一个占位邮箱
                // 同一 openid 只会走一次自动注册分支，因此该邮箱也天然唯一
                $emailLocal = 'wx_' . substr(hash('sha256', $openid), 0, 16);
                $email = $emailLocal . '@miniapp.local';

                // 生成一个随机密码哈希，占位用；用户如需在网页端登录，可在引导页设置密码
                $randomPlain = bin2hex(random_bytes(8));
                $passwordHash = password_hash($randomPlain, PASSWORD_DEFAULT);

                // 若用户名已存在（极少见），追加短随机后缀
                $tryName = $username;
                $i = 0;
                while (User::findByUsername($tryName)) {
                    $i++;
                    $tryName = $username . '_' . substr(bin2hex(random_bytes(2)), 0, 3);
                    if ($i > 5) { break; }
                }
                $username = $tryName;

                $userId = User::create($username, $nickname, $email, $passwordHash, 'miniapp');

                // 绑定 openid
                $bindingId = UserWechatBinding::create($userId, $openid, $unionid ? (string)$unionid : null);
                UserWechatBinding::updateLastLogin($bindingId);

                // 若有微信头像 URL，尝试同步为本地头像
                if ($avatarUrl !== '') {
                    $newAvatar = Upload::saveAvatarFromUrl($userId, $avatarUrl);
                    if ($newAvatar !== null) {
                        User::updateAvatarPath($userId, $newAvatar);
                    }
                }

                // 新用户注入默认数据（分类/项目/账户）
                Seeder::seedIfEmpty($userId);

                $token = ApiToken::createToken($userId, 'miniapp', 30 * 24 * 60 * 60);
                $user = User::findById($userId);
                json_response(200, [
                    'success' => true,
                    'token' => $token,
                    'user' => build_user_payload($user),
                ]);
            }
        } catch (\Throwable $e) {
            json_response(500, ['success' => false, 'error' => '自动登录失败：' . $e->getMessage()]);
        }
        break;
    }

    case 'wechat/bind-existing': {
        $body = parse_json_body();
        $code = trim((string)($body['code'] ?? ''));
        $account = trim((string)($body['account'] ?? ''));
        $password = (string)($body['password'] ?? '');
        if ($code === '' || $account === '' || $password === '') {
            json_response(400, ['success' => false, 'error' => '参数不完整']);
        }

        $res = WeChatMiniApp::code2Session($code);
        if (!$res['success'] || !$res['openid']) {
            json_response(400, ['success' => false, 'error' => $res['error'] ?? '微信登录失败']);
        }
        $openid = (string)$res['openid'];
        $unionid = $res['unionid'] ?? null;

        if (UserWechatBinding::findByOpenid($openid)) {
            json_response(400, ['success' => false, 'error' => '该微信已绑定过账号']);
        }

        $user = null;
        if (filter_var($account, FILTER_VALIDATE_EMAIL)) {
            $user = User::findByEmail($account);
        } else {
            $user = User::findByUsername($account);
        }
        if (!$user || !password_verify($password, $user['password_hash'])) {
            json_response(401, ['success' => false, 'error' => '账号或密码错误']);
        }
        if ((int)$user['status'] !== 1) {
            json_response(403, ['success' => false, 'error' => '账号已被禁用']);
        }

        $bindingId = UserWechatBinding::create((int)$user['id'], $openid, $unionid ? (string)$unionid : null);
        UserWechatBinding::updateLastLogin($bindingId);

        $token = ApiToken::createToken((int)$user['id'], 'miniapp', 30 * 24 * 60 * 60);
        json_response(200, [
            'success' => true,
            'token' => $token,
            'user' => [
                'id' => (int)$user['id'],
                'username' => $user['username'],
                'nickname' => $user['nickname'],
                'email' => $user['email'],
                'role' => $user['role'],
                'theme_mode' => $user['theme_mode'] ?? 'light',
            ],
        ]);
        break;
    }

    case 'wechat/register-bind': {
        $body = parse_json_body();
        $code = trim((string)($body['code'] ?? ''));
        $username = trim((string)($body['username'] ?? ''));
        $nickname = trim((string)($body['nickname'] ?? ''));
        $email = trim((string)($body['email'] ?? ''));
        $password = (string)($body['password'] ?? '');

        if ($code === '' || $username === '' || $nickname === '' || $email === '' || $password === '') {
            json_response(400, ['success' => false, 'error' => '请完整填写所有必填信息']);
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            json_response(400, ['success' => false, 'error' => '邮箱格式不正确']);
        }
        if (User::findByUsername($username)) {
            json_response(400, ['success' => false, 'error' => '用户名已存在']);
        }
        if (User::findByEmail($email)) {
            json_response(400, ['success' => false, 'error' => '邮箱已被使用']);
        }

        $res = WeChatMiniApp::code2Session($code);
        if (!$res['success'] || !$res['openid']) {
            json_response(400, ['success' => false, 'error' => $res['error'] ?? '微信登录失败']);
        }
        $openid = (string)$res['openid'];
        $unionid = $res['unionid'] ?? null;

        if (UserWechatBinding::findByOpenid($openid)) {
            json_response(400, ['success' => false, 'error' => '该微信已绑定过账号']);
        }

        $passwordHash = password_hash($password, PASSWORD_DEFAULT);
        $userId = User::create($username, $nickname, $email, $passwordHash, 'miniapp');
        // 小程序注册默认视为已完成邮箱验证，方便直接使用
        User::markEmailVerified($userId);

        $bindingId = UserWechatBinding::create($userId, $openid, $unionid ? (string)$unionid : null);
        UserWechatBinding::updateLastLogin($bindingId);

        // 同步微信头像（如传入 avatar_url）
        $avatarUrl = trim((string)($body['avatar_url'] ?? ''));
        if ($avatarUrl !== '') {
            $newAvatar = Upload::saveAvatarFromUrl($userId, $avatarUrl);
            if ($newAvatar !== null) {
                User::updateAvatarPath($userId, $newAvatar);
            }
        }

        $token = ApiToken::createToken($userId, 'miniapp', 30 * 24 * 60 * 60);
        $user = User::findById($userId);

        json_response(200, [
            'success' => true,
            'token' => $token,
            'user' => build_user_payload($user),
        ]);
        break;
    }

    case 'auth/profile': {
        $user = require_auth_user();
        json_response(200, [
            'success' => true,
            'user' => build_user_payload($user),
        ]);
        break;
    }

    case 'auth/login-password': {
        $body = parse_json_body();
        $account = trim((string)($body['account'] ?? ''));
        $password = (string)($body['password'] ?? '');
        if ($account === '' || $password === '') {
            json_response(400, ['success' => false, 'error' => '请输入账号和密码']);
        }

        $user = filter_var($account, FILTER_VALIDATE_EMAIL)
            ? User::findByEmail($account)
            : User::findByUsername($account);

        $now = time();
        $userFailedCount = 0;
        $userLockUntilTs = 0;
        if ($user) {
            $userFailedCount = (int)($user['failed_login_count'] ?? 0);
            $lockUntilStr = $user['login_lock_until'] ?? null;
            if ($lockUntilStr) {
                $userLockUntilTs = strtotime((string)$lockUntilStr) ?: 0;
            }
        }

        if ($user && $userLockUntilTs > $now) {
            $remain = max(0, $userLockUntilTs - $now);
            $minutes = (int)ceil($remain / 60);
            json_response(423, ['success' => false, 'error' => '密码连续输错次数过多，请约 ' . $minutes . ' 分钟后重试']);
        }

        if (!$user || !password_verify($password, (string)($user['password_hash'] ?? ''))) {
            if ($user) {
                $userFailedCount++;
                $lockUntilTs = null;
                if ($userFailedCount >= 5) {
                    $lockUntilTs = $now + 180;
                }
                User::updateLoginSecurity((int)$user['id'], $userFailedCount, $lockUntilTs);
            }
            json_response(401, ['success' => false, 'error' => '账号或密码错误']);
        }

        if ((int)($user['status'] ?? 0) !== 1) {
            json_response(403, ['success' => false, 'error' => '账号已被禁用']);
        }
        if ((int)($user['email_verified'] ?? 0) !== 1) {
            json_response(403, ['success' => false, 'error' => '邮箱尚未验证，请先完成邮箱验证']);
        }

        User::updateLoginSecurity((int)$user['id'], 0, null);
        Seeder::seedIfEmpty((int)$user['id']);

        $token = ApiToken::createToken((int)$user['id'], 'mobile-web', 30 * 24 * 60 * 60);

        json_response(200, [
            'success' => true,
            'token' => $token,
            'user' => build_user_payload($user),
        ]);
        break;
    }

    case 'auth/register': {
        $allowRegister = (bool)Config::get('app.allow_register', true);
        if (!$allowRegister) {
            json_response(403, ['success' => false, 'error' => '当前系统已关闭注册']);
        }

        $body = parse_json_body();
        $username = trim((string)($body['username'] ?? ''));
        $nickname = trim((string)($body['nickname'] ?? ''));
        $email = trim((string)($body['email'] ?? ''));
        $password = (string)($body['password'] ?? '');
        $passwordConfirm = (string)($body['password_confirm'] ?? '');

        if ($username === '' || $nickname === '' || $email === '' || $password === '') {
            json_response(400, ['success' => false, 'error' => '请完整填写所有必填信息']);
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            json_response(400, ['success' => false, 'error' => '邮箱格式不正确']);
        }
        if (strlen($password) < 6) {
            json_response(400, ['success' => false, 'error' => '密码至少 6 位']);
        }
        if ($password !== $passwordConfirm) {
            json_response(400, ['success' => false, 'error' => '两次输入的密码不一致']);
        }
        if (User::findByUsername($username)) {
            json_response(409, ['success' => false, 'error' => '用户名已存在']);
        }
        if (User::findByEmail($email)) {
            json_response(409, ['success' => false, 'error' => '邮箱已被使用']);
        }

        $passwordHash = password_hash($password, PASSWORD_DEFAULT);
        $userId = User::create($username, $nickname, $email, $passwordHash, 'pc');
        User::markEmailVerified($userId);
        Seeder::seedIfEmpty($userId);

        $token = ApiToken::createToken($userId, 'mobile-web', 30 * 24 * 60 * 60);
        $user = User::findById($userId);

        json_response(200, [
            'success' => true,
            'token' => $token,
            'user' => $user ? build_user_payload($user) : null,
        ]);
        break;
    }

    case 'auth/logout': {
        $token = get_bearer_token();
        if ($token) {
            ApiToken::revokeToken($token);
        }
        json_response(200, ['success' => true]);
        break;
    }

    case 'user/stats': {
        $user = require_auth_user();
        $userId = (int)$user['id'];
        $pdo = Database::getConnection();

        // 注册天数：
        // 1）优先使用 users 表中的 created_at（如存在该列且有值）；
        // 2）如缺失或旧库没有该列，则回退到最早一笔流水日期，保证老账号也有合理的注册天数。
        $registerDays = 0;
        // 1）尝试使用 created_at（若旧库没有该列，则这里可能抛错，下面会兜底）
        try {
            if (!empty($user['created_at'])) {
                $stmt = $pdo->prepare('SELECT GREATEST(1, DATEDIFF(CURDATE(), DATE(created_at)) + 1) AS days FROM users WHERE id = :id');
                $stmt->execute([':id' => $userId]);
                $row = $stmt->fetch(PDO::FETCH_ASSOC);
                if ($row && isset($row['days'])) {
                    $registerDays = max(0, (int)$row['days']);
                }
            }
        } catch (\Throwable $e) {
            // 旧库缺少 created_at 列等情况，忽略异常，后面用流水日期兜底
            $registerDays = 0;
        }

        // 2）如上面没算出来（包括旧库缺少 created_at 的情况），用最早一笔流水日期估算
        if ($registerDays <= 0) {
            try {
                $stmt = $pdo->prepare('SELECT DATE(MIN(trans_time)) AS first_day FROM transactions WHERE user_id = :uid');
                $stmt->execute([':uid' => $userId]);
                $row = $stmt->fetch(PDO::FETCH_ASSOC);
                if ($row && !empty($row['first_day'])) {
                    $firstDay = (string)$row['first_day'];
                    $stmt2 = $pdo->prepare('SELECT GREATEST(1, DATEDIFF(CURDATE(), :first_day) + 1) AS days');
                    $stmt2->execute([':first_day' => $firstDay]);
                    $row2 = $stmt2->fetch(PDO::FETCH_ASSOC);
                    if ($row2 && isset($row2['days'])) {
                        $registerDays = max(1, (int)$row2['days']);
                    }
                }
            } catch (\Throwable $e) {
                // 兜底：保持 0，不影响后续统计
                $registerDays = 0;
            }
        }

        // 记账统计：记账天数（有流水的不同日期数）、连续记账天数、总笔数
        $bookDays = 0;
        $streakDays = 0;
        $totalCount = 0;
        try {
            // 总笔数
            $stmt = $pdo->prepare('SELECT COUNT(*) AS cnt FROM transactions WHERE user_id = :uid');
            $stmt->execute([':uid' => $userId]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            $totalCount = $row ? (int)$row['cnt'] : 0;

            if ($totalCount > 0) {
                // 有流水的不同记账日期总数
                $stmt = $pdo->prepare('SELECT COUNT(DISTINCT DATE(trans_time)) AS days FROM transactions WHERE user_id = :uid');
                $stmt->execute([':uid' => $userId]);
                $rowDays = $stmt->fetch(PDO::FETCH_ASSOC);
                $bookDays = $rowDays ? (int)$rowDays['days'] : 0;

                // 连续记账天数（从今天往前，直到遇到断档的一天）
                $stmt = $pdo->prepare('SELECT DATE(trans_time) AS d FROM transactions WHERE user_id = :uid AND trans_time <= NOW() GROUP BY DATE(trans_time) ORDER BY d DESC');
                $stmt->execute([':uid' => $userId]);
                $dates = $stmt->fetchAll(PDO::FETCH_COLUMN) ?: [];

                if (!empty($dates)) {
                    $today = new \DateTimeImmutable('today');
                    $firstDate = new \DateTimeImmutable((string)$dates[0]);
                    if ($firstDate->format('Y-m-d') === $today->format('Y-m-d')) {
                        $streakDays = 1;
                        $prev = $today;
                        $count = count($dates);
                        for ($i = 1; $i < $count; $i++) {
                            $curr = new \DateTimeImmutable((string)$dates[$i]);
                            $expected = $prev->modify('-1 day');
                            if ($curr->format('Y-m-d') === $expected->format('Y-m-d')) {
                                $streakDays++;
                                $prev = $curr;
                            } else {
                                break;
                            }
                        }
                    } else {
                        $streakDays = 0;
                    }
                }
            }
        } catch (\Throwable $e) {
            $bookDays = 0;
            $streakDays = 0;
        }

        json_response(200, [
            'success' => true,
            'stats' => [
                'register_days' => $registerDays,
                'book_days' => $bookDays,
                'streak_days' => $streakDays,
                'transaction_count' => $totalCount,
            ],
        ]);
        break;
    }

    case 'ledgers/list': {
        $user = require_auth_user();
        $userId = (int)$user['id'];
        $activeLedgerId = LedgerContext::requireActiveLedgerId($userId);
        if ($activeLedgerId <= 0) {
            json_response(200, [
                'success' => true,
                'ledger_mode' => false,
                'active_ledger_id' => 0,
                'active_ledger' => [
                    'id' => 0,
                    'type' => 'personal',
                    'name' => '个人账本',
                    'member_role' => 'admin',
                ],
                'ledgers' => [[
                    'id' => 0,
                    'type' => 'personal',
                    'name' => '个人账本',
                    'member_role' => 'admin',
                ]],
            ]);
            break;
        }

        $ledgers = Ledger::listForUser($userId);
        $payload = [];
        $active = null;
        foreach ($ledgers as $l) {
            $row = [
                'id' => (int)$l['id'],
                'type' => (string)($l['type'] ?? ''),
                'name' => (string)($l['name'] ?? ''),
                'member_role' => (string)($l['member_role'] ?? ''),
            ];
            $payload[] = $row;
            if ((int)$l['id'] === $activeLedgerId) {
                $active = $row;
            }
        }
        if ($active === null && !empty($payload)) {
            $active = $payload[0];
        }

        json_response(200, [
            'success' => true,
            'ledger_mode' => true,
            'active_ledger_id' => $activeLedgerId,
            'active_ledger' => $active,
            'ledgers' => $payload,
        ]);
        break;
    }

    case 'ledgers/set-active': {
        $user = require_auth_user();
        $userId = (int)$user['id'];
        $body = parse_json_body();
        $ledgerId = (int)($body['ledger_id'] ?? 0);
        if ($ledgerId <= 0) {
            json_response(400, ['success' => false, 'error' => 'ledger_id 无效']);
        }

        $activeLedgerId = LedgerContext::requireActiveLedgerId($userId);
        if ($activeLedgerId <= 0) {
            json_response(400, ['success' => false, 'error' => '当前系统未启用账本功能']);
        }

        $ok = LedgerContext::setActiveLedgerId($userId, $ledgerId);
        if (!$ok) {
            json_response(403, ['success' => false, 'error' => '无权切换到该账本']);
        }

        $ledger = Ledger::findById($ledgerId);
        if (!$ledger) {
            json_response(404, ['success' => false, 'error' => '账本不存在']);
        }
        $memberRole = '';
        if (($ledger['type'] ?? '') === 'personal') {
            $memberRole = ((int)($ledger['owner_user_id'] ?? 0) === $userId) ? 'admin' : '';
        } else {
            $memberRole = LedgerMember::getRole($ledgerId, $userId) ?: '';
        }

        json_response(200, [
            'success' => true,
            'active_ledger_id' => $ledgerId,
            'active_ledger' => [
                'id' => (int)$ledger['id'],
                'type' => (string)($ledger['type'] ?? ''),
                'name' => (string)($ledger['name'] ?? ''),
                'member_role' => (string)$memberRole,
            ],
        ]);
        break;
    }

    case 'ledgers/create-shared': {
        $user = require_auth_user();
        $userId = (int)$user['id'];
        $body = parse_json_body();
        $name = trim((string)($body['name'] ?? ''));

        $activeLedgerId = LedgerContext::requireActiveLedgerId($userId);
        if ($activeLedgerId <= 0) {
            json_response(400, ['success' => false, 'error' => '当前系统未启用账本功能']);
        }

        $ledgerId = Ledger::createShared($userId, $name);
        if ($ledgerId === null) {
            json_response(500, ['success' => false, 'error' => '创建共享账本失败']);
        }

        // 默认切换到新建账本
        LedgerContext::setActiveLedgerId($userId, $ledgerId);
        $ledger = Ledger::findById($ledgerId);
        json_response(200, [
            'success' => true,
            'ledger' => [
                'id' => (int)$ledger['id'],
                'type' => (string)($ledger['type'] ?? ''),
                'name' => (string)($ledger['name'] ?? ''),
                'member_role' => 'admin',
            ],
            'active_ledger_id' => $ledgerId,
        ]);
        break;
    }

    case 'ledgers/join-by-code': {
        $user = require_auth_user();
        $userId = (int)$user['id'];
        $body = parse_json_body();
        $code = trim((string)($body['invite_code'] ?? ''));
        if ($code === '') {
            json_response(400, ['success' => false, 'error' => '邀请码不能为空']);
        }

        $activeLedgerId = LedgerContext::requireActiveLedgerId($userId);
        if ($activeLedgerId <= 0) {
            json_response(400, ['success' => false, 'error' => '当前系统未启用账本功能']);
        }

        $ledgerId = Ledger::joinByInviteCode($userId, $code);
        if ($ledgerId === null) {
            json_response(404, ['success' => false, 'error' => '找不到对应的共享账本或邀请码已失效']);
        }

        // 加入成功后自动切换到该账本
        LedgerContext::setActiveLedgerId($userId, $ledgerId);
        $ledger = Ledger::findById($ledgerId);
        $memberRole = LedgerMember::getRole($ledgerId, $userId) ?: 'member';

        json_response(200, [
            'success' => true,
            'ledger' => [
                'id' => (int)$ledger['id'],
                'type' => (string)($ledger['type'] ?? ''),
                'name' => (string)($ledger['name'] ?? ''),
                'member_role' => (string)$memberRole,
            ],
            'active_ledger_id' => $ledgerId,
        ]);
        break;
    }

    case 'home/overview': {
        $user = require_auth_user();
        $userId = (int)$user['id'];
        $pdo = Database::getConnection();

        $ledgerId = LedgerContext::requireActiveLedgerId($userId);

        // 账户资产概览
        $stmt = $pdo->prepare('SELECT ag.code, SUM(a.current_balance) AS total
            FROM accounts a
            JOIN account_groups ag ON a.group_id = ag.id
            WHERE ' . ($ledgerId > 0 ? 'a.ledger_id = :lid' : 'a.user_id = :uid') . '
            GROUP BY ag.code');
        $stmt->execute($ledgerId > 0 ? [':lid' => $ledgerId] : [':uid' => $userId]);
        $balances = [
            'financial' => 0.0,
            'saving' => 0.0,
            'receivable' => 0.0,
            'debt' => 0.0,
            'other' => 0.0,
        ];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $code = $row['code'] ?? '';
            if (array_key_exists($code, $balances)) {
                $balances[$code] = (float)$row['total'];
            }
        }
        $totalAssets = $balances['financial'] + $balances['saving'] + $balances['receivable'] + $balances['other'];
        $totalDebt = $balances['debt'];
        $netAssets = $totalAssets + $totalDebt; // 负债为负数

        // 本月收支
        $year = (int)date('Y');
        $month = (int)date('n');

        $stmt = $pdo->prepare('SELECT COALESCE(SUM(amount),0) AS total FROM transactions WHERE ' . ($ledgerId > 0 ? 'ledger_id = :lid' : 'user_id = :uid') . ' AND type = "expense" AND YEAR(trans_time) = :y AND MONTH(trans_time) = :m');
        $stmt->execute($ledgerId > 0 ? [':lid' => $ledgerId, ':y' => $year, ':m' => $month] : [':uid' => $userId, ':y' => $year, ':m' => $month]);
        $monthExpense = (float)($stmt->fetchColumn() ?: 0);

        $stmt = $pdo->prepare('SELECT COALESCE(SUM(amount),0) AS total FROM transactions WHERE ' . ($ledgerId > 0 ? 'ledger_id = :lid' : 'user_id = :uid') . ' AND type = "income" AND YEAR(trans_time) = :y AND MONTH(trans_time) = :m');
        $stmt->execute($ledgerId > 0 ? [':lid' => $ledgerId, ':y' => $year, ':m' => $month] : [':uid' => $userId, ':y' => $year, ':m' => $month]);
        $monthIncome = (float)($stmt->fetchColumn() ?: 0);
        $monthNet = $monthIncome - $monthExpense;

        // 今日收支
        $today = date('Y-m-d');
        $stmt = $pdo->prepare('SELECT type, COALESCE(SUM(amount),0) AS total FROM transactions WHERE ' . ($ledgerId > 0 ? 'ledger_id = :lid' : 'user_id = :uid') . ' AND DATE(trans_time) = :d AND type IN ("income","expense") GROUP BY type');
        $stmt->execute($ledgerId > 0 ? [':lid' => $ledgerId, ':d' => $today] : [':uid' => $userId, ':d' => $today]);
        $todayIncome = 0.0;
        $todayExpense = 0.0;
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            if ($row['type'] === 'income') {
                $todayIncome = (float)$row['total'];
            } elseif ($row['type'] === 'expense') {
                $todayExpense = (float)$row['total'];
            }
        }
        $todayNet = $todayIncome - $todayExpense;

        // 当月预算汇总（支出）
        [$monthBudgetTotal, $monthBudgetUsed] = summarize_budget_by_month($userId, $year, $month, $ledgerId);
        $monthBudgetRemain = max(0.0, $monthBudgetTotal - $monthBudgetUsed);
        $monthBudgetRate = $monthBudgetTotal > 0 ? ($monthBudgetUsed / $monthBudgetTotal) : 0.0;
        $monthBudgetOver = $monthBudgetTotal > 0 && $monthBudgetUsed > $monthBudgetTotal;

        // 目标进度汇总（当前用户 + 当前账本），供首页概览展示
        $goalTotalTarget = 0.0;
        $goalTotalSaved = 0.0;
        $goalOverallPercent = 0;
        $goalActiveCount = 0;
        $recentGoalsPayload = [];
        try {
            $ledgerForGoal = $ledgerId > 0 ? $ledgerId : 0;
            $goalRows = \App\Model\Goal::listByUserAndLedger($userId, $ledgerForGoal);
            foreach ($goalRows as $g) {
                $target = (float)($g['target_amount'] ?? 0);
                $saved = (float)($g['saved_amount'] ?? 0);
                if ($target <= 0) {
                    continue;
                }
                if (($g['status'] ?? 'active') === 'archived') {
                    continue;
                }
                $goalTotalTarget += $target;
                $goalTotalSaved += min($saved, $target);
                $goalActiveCount++;
            }
            if ($goalTotalTarget > 0) {
                $goalOverallPercent = (int)round(min(100, ($goalTotalSaved / $goalTotalTarget) * 100));
            }

            // 最近几个目标（按 id 倒序，未归档）
            $recentRows = \App\Model\Goal::listRecentByUserAndLedger($userId, $ledgerForGoal, 3);
            foreach ($recentRows as $g) {
                $target = (float)($g['target_amount'] ?? 0);
                $saved = (float)($g['saved_amount'] ?? 0);
                $percent = $target > 0 ? min(999, round($saved / $target * 100)) : 0;
                $recentGoalsPayload[] = [
                    'id' => (int)$g['id'],
                    'title' => (string)($g['title'] ?? ''),
                    'target_amount' => $target,
                    'saved_amount' => $saved,
                    'status' => (string)($g['status'] ?? 'active'),
                    'percent' => (int)$percent,
                    'deadline' => $g['deadline'] ?? null,
                ];
            }
        } catch (\Throwable $e) {
            $goalTotalTarget = 0.0;
            $goalTotalSaved = 0.0;
            $goalOverallPercent = 0;
            $goalActiveCount = 0;
            $recentGoalsPayload = [];
        }

        // 最近 5 条流水
        $stmt = $pdo->prepare('SELECT t.*, c.name AS category_name, i.name AS item_name,
                fa.name AS from_account_name, ta.name AS to_account_name
            FROM transactions t
            LEFT JOIN categories c ON t.category_id = c.id
            LEFT JOIN items i ON t.item_id = i.id
            LEFT JOIN accounts fa ON t.from_account_id = fa.id
            LEFT JOIN accounts ta ON t.to_account_id = ta.id
            WHERE ' . ($ledgerId > 0 ? 't.ledger_id = :lid' : 't.user_id = :uid') . '
            ORDER BY t.trans_time DESC, t.id DESC
            LIMIT 5');
        $stmt->execute($ledgerId > 0 ? [':lid' => $ledgerId] : [':uid' => $userId]);
        $recentRows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        $recent = [];
        foreach ($recentRows as $t) {
            $recent[] = [
                'id' => (int)$t['id'],
                'type' => $t['type'],
                'amount' => (float)$t['amount'],
                'category_name' => $t['category_name'] ?? null,
                'item_name' => $t['item_name'] ?? null,
                'from_account_name' => $t['from_account_name'] ?? null,
                'to_account_name' => $t['to_account_name'] ?? null,
                'trans_time' => $t['trans_time'],
                'remark' => $t['remark'] ?? null,
                'attachment_url' => $t['attachment_path'] ? build_file_url($t['attachment_path']) : null,
            ];
        }

        $budgetReminderEnabled = isset($user['budget_reminder_enabled']) ? (int)$user['budget_reminder_enabled'] === 1 : true;

            // 最近一条未读公告（用于小程序首页弹窗）
            $latestAnnouncement = null;
            try {
                $row = Announcement::findLatestUnreadForUser($userId);
                if ($row) {
                    $latestAnnouncement = [
                        'id' => (int)$row['id'],
                        'title' => (string)($row['title'] ?? ''),
                        'content' => (string)($row['content'] ?? ''),
                        'scheduled_at' => (string)($row['scheduled_at'] ?? ''),
                    ];
                }
            } catch (\Throwable $e) {
                $latestAnnouncement = null;
            }

        json_response(200, [
            'success' => true,
            'assets' => [
                'financial' => $balances['financial'],
                'saving' => $balances['saving'],
                'receivable' => $balances['receivable'],
                'debt' => $balances['debt'],
                'other' => $balances['other'],
                'total_assets' => $totalAssets,
                'total_debt' => $totalDebt,
                'net_assets' => $netAssets,
            ],
            'today' => [
                'income' => $todayIncome,
                'expense' => $todayExpense,
                'net' => $todayNet,
            ],
            'month' => [
                'year' => $year,
                'month' => $month,
                'income' => $monthIncome,
                'expense' => $monthExpense,
                'net' => $monthNet,
                'budget_total' => $monthBudgetTotal,
                'budget_used' => $monthBudgetUsed,
                'budget_remain' => $monthBudgetRemain,
                'budget_rate' => $monthBudgetRate,
                'budget_over' => $monthBudgetOver,
            ],
            'goals' => [
                'total_target' => $goalTotalTarget,
                'total_saved' => $goalTotalSaved,
                'overall_percent' => $goalOverallPercent,
                'active_count' => $goalActiveCount,
            ],
            'goals_recent' => $recentGoalsPayload,
            'budget_reminder_enabled' => $budgetReminderEnabled,
            'recent_transactions' => $recent,
            'announcement' => $latestAnnouncement,
        ]);
        break;
    }

    case 'announcement/mark-read': {
        $user = require_auth_user();
        $body = parse_json_body();
        $announcementId = (int)($body['announcement_id'] ?? 0);
        if ($announcementId > 0) {
            try {
                AnnouncementRead::markRead($announcementId, (int)$user['id'], 'miniapp');
            } catch (\Throwable $e) {
                // 忽略计数错误，不影响前端体验
            }
        }
        json_response(200, ['success' => true]);
        break;
    }

    case 'settings/update-budget-reminder': {
        $user = require_auth_user();
        $body = parse_json_body();
        $enabled = !empty($body['enabled']);

        User::updateBudgetReminder((int)$user['id'], $enabled);
        $user = User::findById((int)$user['id']);

        json_response(200, [
            'success' => true,
            'budget_reminder_enabled' => isset($user['budget_reminder_enabled']) ? (int)$user['budget_reminder_enabled'] === 1 : true,
        ]);
        break;
    }

    // 更新是否启用账户间转账功能（供小程序使用）
    case 'settings/update-transfer-feature': {
        $user = require_auth_user();
        $body = parse_json_body();
        $enabled = !empty($body['enabled']);

        User::updateTransferFeature((int)$user['id'], $enabled);
        $user = User::findById((int)$user['id']);

        json_response(200, [
            'success' => true,
            'enable_transfer' => isset($user['enable_transfer']) ? (int)$user['enable_transfer'] === 1 : false,
        ]);
        break;
    }

    // 更新是否允许账户余额为负数（供小程序使用）
    case 'settings/update-allow-negative-balance': {
        $user = require_auth_user();
        $body = parse_json_body();
        $enabled = !empty($body['enabled']);

        User::updateAllowNegativeBalance((int)$user['id'], $enabled);
        $user = User::findById((int)$user['id']);

        json_response(200, [
            'success' => true,
            'allow_negative_balance' => isset($user['allow_negative_balance']) ? (int)$user['allow_negative_balance'] === 1 : false,
        ]);
        break;
    }

    // 修改用户名（需唯一）
    case 'settings/update-username': {
        $user = require_auth_user();
        $body = parse_json_body();
        $new = trim((string)($body['username'] ?? ''));
        if ($new === '') {
            json_response(400, ['success' => false, 'error' => '用户名不能为空']);
        }
        $exists = User::findByUsername($new);
        if ($exists && (int)$exists['id'] !== (int)$user['id']) {
            json_response(409, ['success' => false, 'error' => '用户名已被占用']);
        }
        User::updateUsername((int)$user['id'], $new);
        $u = User::findById((int)$user['id']);
        json_response(200, [
            'success' => true,
            'user' => build_user_payload($u),
        ]);
        break;
    }

    // 从微信资料同步昵称和头像，或仅手动更新昵称
    case 'settings/update-nickname-from-wechat': {
        $user = require_auth_user();
        $body = parse_json_body();
        $nickname = trim((string)($body['nickname'] ?? ''));
        $avatarUrl = trim((string)($body['avatar_url'] ?? ''));
        if ($nickname === '') {
            json_response(400, ['success' => false, 'error' => '昵称不能为空']);
        }
        // 清洗微信昵称中的 emoji，避免数据库编码不兼容
        $nicknameClean = sanitize_wechat_nickname($nickname);
        if ($nicknameClean === '') {
            json_response(400, ['success' => false, 'error' => '昵称暂不支持只包含表情，请输入部分文字']);
        }
        if (mb_strlen($nicknameClean, 'UTF-8') > 50) {
            $nicknameClean = mb_substr($nicknameClean, 0, 50, 'UTF-8');
        }

        User::updateProfile((int)$user['id'], (string)$user['username'], $nicknameClean);
        $u = User::findById((int)$user['id']);

        if ($avatarUrl !== '') {
            $oldAvatar = $u['avatar_path'] ?? null;
            $newAvatar = Upload::saveAvatarFromUrl((int)$u['id'], $avatarUrl);
            if ($newAvatar !== null) {
                User::updateAvatarPath((int)$u['id'], $newAvatar);
                if ($oldAvatar && $oldAvatar !== $newAvatar) {
                    Upload::deleteByRelativePath($oldAvatar);
                }
                $u = User::findById((int)$u['id']);
            }
        }

        json_response(200, [
            'success' => true,
            'user' => build_user_payload($u),
        ]);
        break;
    }

    // 小程序上传头像文件（手动选择图片）
    case 'settings/upload-avatar': {
        $user = require_auth_user();
        if (empty($_FILES['avatar']) || !is_array($_FILES['avatar'])) {
            json_response(400, ['success' => false, 'error' => '未接收到头像文件']);
        }
        $oldAvatar = $user['avatar_path'] ?? null;
        $newAvatar = Upload::saveAvatar((int)$user['id'], $_FILES['avatar']);
        if ($newAvatar === null) {
            json_response(400, ['success' => false, 'error' => '头像上传失败，请确认大小不超过 5MB 且为图片格式']);
        }
        User::updateAvatarPath((int)$user['id'], $newAvatar);
        if ($oldAvatar && $oldAvatar !== $newAvatar) {
            Upload::deleteByRelativePath($oldAvatar);
        }
        $u = User::findById((int)$user['id']);
        json_response(200, [
            'success' => true,
            'user' => build_user_payload($u),
        ]);
        break;
    }

    // 首次设置邮箱（无验证），二次更换需验证
    case 'settings/set-email': {
        $user = require_auth_user();
        $body = parse_json_body();
        $email = trim((string)($body['email'] ?? ''));
        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            json_response(400, ['success' => false, 'error' => '邮箱格式不正确']);
        }
        if (!empty($user['email'])) {
            json_response(409, ['success' => false, 'error' => '已设置邮箱，请使用更换邮箱流程']);
        }
        User::updateEmail((int)$user['id'], $email, true);
        $u = User::findById((int)$user['id']);
        json_response(200, [
            'success' => true,
            'email' => $u['email'],
        ]);
        break;
    }

    // 直接更换邮箱（唯一性校验，不走邮件验证）
    case 'settings/change-email': {
        $user = require_auth_user();
        $body = parse_json_body();
        $email = trim((string)($body['email'] ?? ''));
        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            json_response(400, ['success' => false, 'error' => '邮箱格式不正确']);
        }
        if ($email === (string)$user['email']) {
            json_response(400, ['success' => false, 'error' => '新邮箱不能与原邮箱相同']);
        }
        $exists = User::findByEmail($email);
        if ($exists && (int)$exists['id'] !== (int)$user['id']) {
            json_response(409, ['success' => false, 'error' => '该邮箱已被使用']);
        }
        User::updateEmail((int)$user['id'], $email, true);
        $u = User::findById((int)$user['id']);
        json_response(200, [
            'success' => true,
            'email' => $u['email'],
        ]);
        break;
    }

    // 申请更换邮箱（发送验证邮件）
    case 'settings/request-change-email': {
        $user = require_auth_user();
        $body = parse_json_body();
        $email = trim((string)($body['email'] ?? ''));
        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            json_response(400, ['success' => false, 'error' => '邮箱格式不正确']);
        }
        if ($email === (string)$user['email']) {
            json_response(400, ['success' => false, 'error' => '新邮箱不能与原邮箱相同']);
        }
        $token = bin2hex(random_bytes(16));
        $expiresAt = date('Y-m-d H:i:s', time() + 24 * 60 * 60);
        \App\Model\EmailToken::create((int)$user['id'], $email, 'change_email', $token, $expiresAt);

        $base = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost');
        $confirmUrl = $base . '/public/api.php?route=auth/confirm-email&token=' . urlencode($token);
        $sent = \App\Service\Mailer::send($email, $user['nickname'] ?? $user['username'] ?? '', '邮箱更换验证',
            '<p>请点击以下链接完成邮箱更换：</p><p><a href="' . htmlspecialchars($confirmUrl, ENT_QUOTES) . '">' . htmlspecialchars($confirmUrl, ENT_QUOTES) . '</a></p><p>若非本人操作，请忽略本邮件。</p>');

        if (!$sent) {
            json_response(500, ['success' => false, 'error' => '发送验证邮件失败']);
        }
        json_response(200, ['success' => true, 'message' => '验证邮件已发送至新邮箱']);
        break;
    }

    // 邮箱更换确认（邮件链接访问，返回简单 HTML）
    case 'auth/confirm-email': {
        $token = isset($_GET['token']) ? (string)$_GET['token'] : '';
        if ($token === '') {
            header('Content-Type: text/html; charset=utf-8');
            echo '<h3>链接无效</h3>';
            exit;
        }
        $row = \App\Model\EmailToken::findValid($token, 'change_email');
        if (!$row) {
            header('Content-Type: text/html; charset=utf-8');
            echo '<h3>链接无效或已过期</h3>';
            exit;
        }
        User::updateEmail((int)$row['user_id'], (string)$row['email'], true);
        \App\Model\EmailToken::markUsed((int)$row['id']);
        header('Content-Type: text/html; charset=utf-8');
        echo '<h3>邮箱更换成功</h3><p>已完成验证，可返回小程序使用。</p>';
        exit;
    }

    // 设置/修改登录密码（小程序用户默认无密码）
    case 'settings/set-password': {
        $user = require_auth_user();
        $body = parse_json_body();
        $password = (string)($body['password'] ?? '');
        $confirm = (string)($body['confirm'] ?? '');
        if ($password === '' || $confirm === '') {
            json_response(400, ['success' => false, 'error' => '请输入密码']);
        }
        if ($password !== $confirm) {
            json_response(400, ['success' => false, 'error' => '两次输入不一致']);
        }
        if (strlen($password) < 6) {
            json_response(400, ['success' => false, 'error' => '密码至少 6 位']);
        }
        $hash = password_hash($password, PASSWORD_BCRYPT);
        User::updatePassword((int)$user['id'], $hash);
        json_response(200, ['success' => true]);
        break;
    }

    case 'feedback/create': {
        $user = require_auth_user();

        $raw = file_get_contents('php://input');
        $contentType = $_SERVER['CONTENT_TYPE'] ?? '';

        if (stripos($contentType, 'application/json') !== false) {
            $body = json_decode($raw ?: '[]', true) ?: [];
            $category = (string)($body['category'] ?? Feedback::CATEGORY_SUGGEST);
            $content = trim((string)($body['content'] ?? ''));
            $images = isset($body['images']) && is_array($body['images']) ? $body['images'] : [];
            $imagePaths = [];
            foreach ($images as $img) {
                if (is_string($img) && $img !== '') {
                    $imagePaths[] = $img;
                }
            }
        } else {
            $post = $_POST;
            $category = (string)($post['category'] ?? Feedback::CATEGORY_SUGGEST);
            $content = trim((string)($post['content'] ?? ''));
            $imagePaths = [];

            if (isset($_FILES['images']) && is_array($_FILES['images']['name'] ?? null)) {
                $fileCount = count($_FILES['images']['name']);
                for ($i = 0; $i < $fileCount; $i++) {
                    $file = [
                        'name' => $_FILES['images']['name'][$i] ?? null,
                        'type' => $_FILES['images']['type'][$i] ?? null,
                        'tmp_name' => $_FILES['images']['tmp_name'][$i] ?? null,
                        'error' => $_FILES['images']['error'][$i] ?? UPLOAD_ERR_NO_FILE,
                        'size' => $_FILES['images']['size'][$i] ?? 0,
                    ];
                    if ($file['error'] === UPLOAD_ERR_NO_FILE) {
                        continue;
                    }
                    $saved = Upload::saveAttachment((int)$user['id'], $file);
                    if ($saved !== null) {
                        $imagePaths[] = $saved;
                    }
                }
            }
        }

        if ($content === '') {
            json_response(400, ['success' => false, 'error' => '请填写问题描述']);
        }

        $category = Feedback::normalizeCategory($category);
        $id = Feedback::create((int)$user['id'], $category, $content, $imagePaths);

        json_response(200, [
            'success' => true,
            'id' => $id,
        ]);
        break;
    }

    case 'feedback/list': {
        $user = require_auth_user();
        $limit = isset($_GET['limit']) ? max(1, min(300, (int)$_GET['limit'])) : 200;
        $rows = Feedback::listForFaq($limit);

        $list = [];
        foreach ($rows as $row) {
            $images = [];
            if (!empty($row['images_array']) && is_array($row['images_array'])) {
                foreach ($row['images_array'] as $img) {
                    if (!is_string($img) || $img === '') {
                        continue;
                    }
                    $images[] = [
                        'path' => $img,
                        'url' => build_file_url($img),
                    ];
                }
            }

            $list[] = [
                'id' => (int)$row['id'],
                'category' => $row['category'],
                'content' => $row['content'],
                'status' => $row['status'],
                'created_at' => $row['created_at'],
                'user_nickname' => $row['nickname'] ?? ($row['username'] ?? ''),
                'admin_reply' => $row['admin_reply'] ?? null,
                'admin_reply_at' => $row['admin_reply_at'] ?? null,
                'images' => $images,
            ];
        }

        json_response(200, [
            'success' => true,
            'feedbacks' => $list,
        ]);
        break;
    }

    case 'changelog/list': {
        // 为小程序等客户端提供更新日志列表
        // 文案只维护一份：直接从 PC 端模板 templates/changelog/index.php 解析标题与条目
        // 版本号：PC 端使用 app.version，小程序可单独使用 app.mini_version
        $pcVersion = Config::get('app.version', 'v1.17.0');
        $miniVersion = Config::get('app.mini_version', $pcVersion);

        $entries = [];
        $templatePath = __DIR__ . '/../templates/changelog/index.php';
        if (is_file($templatePath) && is_readable($templatePath)) {
            try {
                ob_start();
                include $templatePath;
                $html = ob_get_clean();

                // 提取形如 <h3>版本号</h3> 紧跟一个 <ul> 的结构
                $pattern = '~<h3[^>]*>(.*?)</h3>\s*<ul[^>]*>(.*?)</ul>~si';
                if (preg_match_all($pattern, $html, $matches, PREG_SET_ORDER)) {
                    foreach ($matches as $m) {
                        $titleHtml = $m[1];
                        $ulHtml = $m[2];

                        $titleText = trim(strip_tags($titleHtml));
                        if ($titleText === '') {
                            continue;
                        }

                        $items = [];
                        if (preg_match_all('~<li[^>]*>(.*?)</li>~si', $ulHtml, $liMatches)) {
                            foreach ($liMatches[1] as $liHtml) {
                                $text = trim(strip_tags($liHtml));
                                if ($text !== '') {
                                    // 合并多余空白，避免换行 / 缩进影响展示
                                    $text = preg_replace('/\s+/u', ' ', $text);
                                    $items[] = $text;
                                }
                            }
                        }

                        if ($items) {
                            $entries[] = [
                                'version' => $titleText,
                                'title' => $titleText,
                                'items' => $items,
                            ];
                        }
                    }
                }
            } catch (\Throwable $e) {
                // 解析失败时保持 entries 为空，由前端提示
                if (ob_get_level() > 0) {
                    ob_end_clean();
                }
            }
        }

        json_response(200, [
            'success' => true,
            // 小程序端使用 mini_version 作为当前版本展示
            'app_version' => $miniVersion,
            'pc_version' => $pcVersion,
            'entries' => $entries,
        ]);
        break;
    }

    case 'accounts/groups': {
        $user = require_auth_user();
        $pdo = Database::getConnection();
        $rows = $pdo->query('SELECT id, code, name FROM account_groups ORDER BY id')->fetchAll(PDO::FETCH_ASSOC) ?: [];
        $groups = [];
        foreach ($rows as $row) {
            $groups[] = [
                'id' => (int)$row['id'],
                'code' => (string)($row['code'] ?? ''),
                'name' => (string)($row['name'] ?? ''),
            ];
        }

        json_response(200, [
            'success' => true,
            'groups' => $groups,
        ]);
        break;
    }

    case 'accounts/list': {
        $user = require_auth_user();
        $userId = (int)$user['id'];

        // 按当前激活账本返回账户列表；旧模式下返回用户级账户
        $ledgerId = 0;
        try {
            $ledgerId = LedgerContext::requireActiveLedgerId($userId);
        } catch (\Throwable $e) {
            $ledgerId = 0;
        }

        if ($ledgerId > 0) {
            $accounts = Account::allByLedger($ledgerId);
        } else {
            $accounts = Account::allByUser($userId);
        }
        $result = [];
        foreach ($accounts as $a) {
            $result[] = [
                'id' => (int)$a['id'],
                'group_id' => (int)$a['group_id'],
                'group_name' => $a['group_name'] ?? '',
                'group_code' => $a['group_code'] ?? '',
                'name' => $a['name'],
                'account_no' => $a['account_no'],
                'initial_balance' => (float)$a['initial_balance'],
                'current_balance' => (float)$a['current_balance'],
                'is_default' => (int)$a['is_default'],
                'icon_type' => $a['icon_type'],
                'icon_value' => $a['icon_value'],
                'icon_url' => $a['icon_type'] === 'file' ? build_file_url($a['icon_value']) : null,
            ];
        }
        json_response(200, ['success' => true, 'accounts' => $result]);
        break;
    }

    case 'account-groups/list': {
        require_auth_user();
        $pdo = Database::getConnection();
        $rows = $pdo->query('SELECT id, code, name FROM account_groups ORDER BY id')->fetchAll(PDO::FETCH_ASSOC) ?: [];
        $groups = [];
        foreach ($rows as $row) {
            $groups[] = [
                'id' => (int)$row['id'],
                'code' => (string)($row['code'] ?? ''),
                'name' => (string)($row['name'] ?? ''),
            ];
        }
        json_response(200, ['success' => true, 'groups' => $groups]);
        break;
    }

    case 'accounts/create': {
        $user = require_auth_user();
        $body = parse_json_body();
        $userId = (int)$user['id'];
        $groupId = (int)($body['group_id'] ?? 0);
        $name = trim((string)($body['name'] ?? ''));
        $accountNo = trim((string)($body['account_no'] ?? ''));
        $initial = isset($body['initial_balance']) ? (float)$body['initial_balance'] : 0.0;

        if ($groupId <= 0 || $name === '') {
            json_response(400, ['success' => false, 'error' => '请选择账户大类并填写账户名称']);
        }

        $iconType = null;
        $iconValue = null;
        $iconLibId = (int)($body['icon_library_id'] ?? 0);
        if ($iconLibId > 0) {
            $icon = IconLibrary::findByUser($userId, $iconLibId);
            if ($icon) {
                $iconType = 'file';
                $iconValue = $icon['file_path'] ?? null;
            }
        }

        // 在共享账本下创建账本级账户；旧模式下创建用户级账户
        $ledgerId = 0;
        try {
            $ledgerId = LedgerContext::requireActiveLedgerId($userId);
        } catch (\Throwable $e) {
            $ledgerId = 0;
        }

        if ($ledgerId > 0) {
            Account::createForLedger($userId, $ledgerId, $groupId, $name, $accountNo ?: null, $initial, $iconType, $iconValue);
        } else {
            Account::create($userId, $groupId, $name, $accountNo ?: null, $initial, $iconType, $iconValue);
        }
        json_response(200, ['success' => true]);
        break;
    }

    case 'ai/transactions/parse': {
        $user = require_auth_user();
        if (!Config::get('ai.enabled', false)) {
            json_response(503, ['success' => false, 'error' => 'AI 解析功能尚未启用']);
        }

        $body = parse_json_body();
        $text = trim((string)($body['text'] ?? ''));
        if ($text === '') {
            json_response(400, ['success' => false, 'error' => '请提供需要解析的记账指令文本']);
        }

        try {
            $parsed = Ai::parseTransactionText($text, [
                'user_id' => (int)$user['id'],
            ]);
        } catch (\Throwable $e) {
            json_response(500, ['success' => false, 'error' => 'AI 解析失败：' . $e->getMessage()]);
        }

        $result = [
            'type' => isset($parsed['type']) ? (string)$parsed['type'] : null,
            'amount' => isset($parsed['amount']) ? (float)$parsed['amount'] : null,
            'category_name' => isset($parsed['category_name']) ? (string)$parsed['category_name'] : null,
            'item_name' => isset($parsed['item_name']) ? (string)$parsed['item_name'] : null,
            'from_account_name' => isset($parsed['from_account_name']) ? (string)$parsed['from_account_name'] : null,
            'to_account_name' => isset($parsed['to_account_name']) ? (string)$parsed['to_account_name'] : null,
            'trans_time' => isset($parsed['trans_time']) ? (string)$parsed['trans_time'] : null,
            'remark' => isset($parsed['remark']) ? (string)$parsed['remark'] : null,
            'source' => 'ai',
        ];

        // 处理 base64 图片，保存为附件并返回路径
        $attachmentPaths = [];
        $images = $body['images'] ?? [];
        if (is_array($images) && count($images) > 0) {
            $userId = (int)$user['id'];
            $maxImages = 5;
            $images = array_slice($images, 0, $maxImages);
            foreach ($images as $img) {
                if (!is_string($img) || trim($img) === '') {
                    continue;
                }
                $path = Upload::saveBase64Image($userId, $img);
                if ($path !== null) {
                    $attachmentPaths[] = $path;
                }
            }
        }

        $response = ['success' => true, 'parsed' => $result];
        if (!empty($attachmentPaths)) {
            $response['attachment_paths'] = $attachmentPaths;
            $response['attachment_urls'] = array_map('build_file_url', $attachmentPaths);
        }

        json_response(200, $response);
        break;
    }

    case 'accounts/update': {
        $user = require_auth_user();
        $body = parse_json_body();
        $userId = (int)$user['id'];
        $id = (int)($body['id'] ?? 0);
        $groupId = (int)($body['group_id'] ?? 0);
        $name = trim((string)($body['name'] ?? ''));
        $accountNo = trim((string)($body['account_no'] ?? ''));

        if ($id <= 0 || $groupId <= 0 || $name === '') {
            json_response(400, ['success' => false, 'error' => '参数不完整']);
        }
        // 根据当前账本选择查找范围
        $ledgerId = 0;
        try {
            $ledgerId = LedgerContext::requireActiveLedgerId($userId);
        } catch (\Throwable $e) {
            $ledgerId = 0;
        }

        $current = $ledgerId > 0
            ? Account::findByLedger($ledgerId, $id)
            : Account::findByUser($userId, $id);
        if (!$current) {
            json_response(404, ['success' => false, 'error' => '账户不存在']);
        }

        $iconType = $current['icon_type'] ?? null;
        $iconValue = $current['icon_value'] ?? null;
        $iconLibId = (int)($body['icon_library_id'] ?? 0);
        $iconClear = !empty($body['icon_clear']);
        if ($iconClear) {
            $iconType = null;
            $iconValue = null;
        } elseif ($iconLibId > 0) {
            $icon = IconLibrary::findByUser($userId, $iconLibId);
            if ($icon) {
                $iconType = 'file';
                $iconValue = $icon['file_path'] ?? null;
            }
        }
        if ($ledgerId > 0) {
            Account::updateForLedger($ledgerId, $id, $groupId, $name, $accountNo ?: null, $iconType, $iconValue);
        } else {
            Account::update($userId, $id, $groupId, $name, $accountNo ?: null, $iconType, $iconValue);
        }
        json_response(200, ['success' => true]);
        break;
    }

    case 'accounts/delete': {
        $user = require_auth_user();
        $body = parse_json_body();
        $id = (int)($body['id'] ?? 0);
        if ($id <= 0) {
            json_response(400, ['success' => false, 'error' => '参数不完整']);
        }
        $userId = (int)$user['id'];
        $ledgerId = 0;
        try {
            $ledgerId = LedgerContext::requireActiveLedgerId($userId);
        } catch (\Throwable $e) {
            $ledgerId = 0;
        }

        $ok = $ledgerId > 0
            ? Account::deleteForLedger($ledgerId, $id)
            : Account::delete($userId, $id);
        if (!$ok) {
            json_response(400, ['success' => false, 'error' => '该账户已有记账数据，无法删除']);
        }
        json_response(200, ['success' => true]);
        break;
    }

    case 'categories/list': {
        $user = require_auth_user();
        $userId = (int)$user['id'];
        $type = isset($_GET['type']) && $_GET['type'] !== '' ? (string)$_GET['type'] : null;

        // 按当前激活账本取分类；旧模式下回退到用户级分类
        $ledgerId = 0;
        try {
            $ledgerId = LedgerContext::requireActiveLedgerId($userId);
        } catch (\Throwable $e) {
            $ledgerId = 0;
        }

        if ($ledgerId > 0) {
            $categories = Category::allByLedger($ledgerId, $type);
        } else {
            $categories = Category::allByUser($userId, $type);
        }

        $result = [];
        foreach ($categories as $c) {
            $result[] = [
                'id' => (int)$c['id'],
                'type' => $c['type'],
                'name' => $c['name'],
                'sort_order' => (int)$c['sort_order'],
                'icon_type' => $c['icon_type'],
                'icon_value' => $c['icon_value'],
                'icon_url' => $c['icon_type'] === 'file' ? build_file_url($c['icon_value']) : null,
            ];
        }
        json_response(200, ['success' => true, 'categories' => $result]);
        break;
    }

    case 'categories/create': {
        $user = require_auth_user();
        $body = parse_json_body();
        $userId = (int)$user['id'];
        $type = trim((string)($body['type'] ?? ''));
        $name = trim((string)($body['name'] ?? ''));
        $sortOrder = isset($body['sort_order']) ? (int)$body['sort_order'] : 0;

        if ($type === '' || $name === '') {
            json_response(400, ['success' => false, 'error' => '请填写分类名称并选择类型']);
        }
        if (!in_array($type, ['expense', 'income', 'transfer'], true)) {
            json_response(400, ['success' => false, 'error' => '分类类型不正确']);
        }

        $iconType = null;
        $iconValue = null;
        $iconLibId = (int)($body['icon_library_id'] ?? 0);
        if ($iconLibId > 0) {
            $icon = IconLibrary::findByUser($userId, $iconLibId);
            if ($icon) {
                $iconType = 'file';
                $iconValue = $icon['file_path'] ?? null;
            }
        }

        // 在共享账本下创建账本级分类；旧模式下创建用户级分类
        $ledgerId = 0;
        try {
            $ledgerId = LedgerContext::requireActiveLedgerId($userId);
        } catch (\Throwable $e) {
            $ledgerId = 0;
        }

        if ($ledgerId > 0) {
            Category::createForLedger($userId, $ledgerId, $type, $name, $sortOrder, $iconType, $iconValue);
        } else {
            Category::create($userId, $type, $name, $sortOrder, $iconType, $iconValue);
        }
        json_response(200, ['success' => true]);
        break;
    }

    case 'categories/update': {
        $user = require_auth_user();
        $body = parse_json_body();
        $userId = (int)$user['id'];
        $id = (int)($body['id'] ?? 0);
        $type = trim((string)($body['type'] ?? ''));
        $name = trim((string)($body['name'] ?? ''));
        $sortOrder = isset($body['sort_order']) ? (int)$body['sort_order'] : 0;

        if ($id <= 0 || $type === '' || $name === '') {
            json_response(400, ['success' => false, 'error' => '参数不完整']);
        }
        if (!in_array($type, ['expense', 'income', 'transfer'], true)) {
            json_response(400, ['success' => false, 'error' => '分类类型不正确']);
        }

        $ledgerId = 0;
        try {
            $ledgerId = LedgerContext::requireActiveLedgerId($userId);
        } catch (\Throwable $e) {
            $ledgerId = 0;
        }

        $current = $ledgerId > 0
            ? Category::findByLedger($ledgerId, $id, $type)
            : Category::findByUser($userId, $id);
        if (!$current) {
            json_response(404, ['success' => false, 'error' => '分类不存在']);
        }
        $iconType = $current['icon_type'] ?? null;
        $iconValue = $current['icon_value'] ?? null;
        $iconLibId = (int)($body['icon_library_id'] ?? 0);
        $iconClear = !empty($body['icon_clear']);
        if ($iconClear) {
            $iconType = null;
            $iconValue = null;
        } elseif ($iconLibId > 0) {
            $icon = IconLibrary::findByUser($userId, $iconLibId);
            if ($icon) {
                $iconType = 'file';
                $iconValue = $icon['file_path'] ?? null;
            }
        }

        if ($ledgerId > 0) {
            Category::updateForLedger($ledgerId, $id, $name, $sortOrder, $iconType, $iconValue);
        } else {
            Category::update($userId, $id, $name, $sortOrder, $iconType, $iconValue);
        }
        json_response(200, ['success' => true]);
        break;
    }

    case 'categories/delete': {
        $user = require_auth_user();
        $body = parse_json_body();
        $id = (int)($body['id'] ?? 0);
        if ($id <= 0) {
            json_response(400, ['success' => false, 'error' => '参数不完整']);
        }
        $userId = (int)$user['id'];
        $ledgerId = 0;
        try {
            $ledgerId = LedgerContext::requireActiveLedgerId($userId);
        } catch (\Throwable $e) {
            $ledgerId = 0;
        }

        $ok = $ledgerId > 0
            ? Category::deleteForLedger($ledgerId, $id)
            : Category::delete($userId, $id);
        if (!$ok) {
            json_response(400, ['success' => false, 'error' => '该分类已有记账数据，无法删除']);
        }
        json_response(200, ['success' => true]);
        break;
    }

    case 'items/list': {
        $user = require_auth_user();
        $userId = (int)$user['id'];
        $categoryId = isset($_GET['category_id']) && $_GET['category_id'] !== '' ? (int)$_GET['category_id'] : null;

        // 按当前激活账本返回项目；旧模式下回退到用户级项目
        $ledgerId = 0;
        try {
            $ledgerId = LedgerContext::requireActiveLedgerId($userId);
        } catch (\Throwable $e) {
            $ledgerId = 0;
        }

        if ($ledgerId > 0) {
            $items = Item::allByLedger($ledgerId, $categoryId);
            $categories = Category::allByLedger($ledgerId);
        } else {
            $items = Item::allByUser($userId, $categoryId);
            // 预加载当前用户的分类，方便输出友好的所属分类名称
            $categories = Category::allByUser($userId);
        }
        $categoryMap = [];
        foreach ($categories as $c) {
            $id = (int)$c['id'];
            $type = (string)$c['type'];
            $typeLabel = $type === 'income' ? '收入' : ($type === 'transfer' ? '转账' : '支出');
            $label = '[' . $typeLabel . '] ' . $c['name'];
            $categoryMap[$id] = [
                'name' => (string)$c['name'],
                'type' => $type,
                'label' => $label,
            ];
        }
        $result = [];
        foreach ($items as $i) {
            $cid = (int)$i['category_id'];
            $cat = $categoryMap[$cid] ?? null;
            $catName = $cat['name'] ?? null;
            $catType = $cat['type'] ?? null;
            $catLabel = $cat['label'] ?? null;
            $result[] = [
                'id' => (int)$i['id'],
                'category_id' => (int)$i['category_id'],
                'category_name' => $catName,
                'category_type' => $catType,
                'category_label' => $catLabel,
                'name' => $i['name'],
                'sort_order' => (int)$i['sort_order'],
                'icon_type' => $i['icon_type'],
                'icon_value' => $i['icon_value'],
                'icon_url' => $i['icon_type'] === 'file' ? build_file_url($i['icon_value']) : null,
            ];
        }
        json_response(200, ['success' => true, 'items' => $result]);
        break;
    }

    case 'items/create': {
        $user = require_auth_user();
        $body = parse_json_body();
        $userId = (int)$user['id'];
        $categoryId = (int)($body['category_id'] ?? 0);
        $name = trim((string)($body['name'] ?? ''));
        $sortOrder = isset($body['sort_order']) ? (int)$body['sort_order'] : 0;

        if ($categoryId <= 0 || $name === '') {
            json_response(400, ['success' => false, 'error' => '请选择分类并填写项目名称']);
        }

        $iconType = null;
        $iconValue = null;
        $iconLibId = (int)($body['icon_library_id'] ?? 0);
        if ($iconLibId > 0) {
            $icon = IconLibrary::findByUser($userId, $iconLibId);
            if ($icon) {
                $iconType = 'file';
                $iconValue = $icon['file_path'] ?? null;
            }
        }

        try {
            $ledgerId = 0;
            try {
                $ledgerId = LedgerContext::requireActiveLedgerId($userId);
            } catch (\Throwable $e) {
                $ledgerId = 0;
            }

            if ($ledgerId > 0) {
                Item::createForLedger($userId, $ledgerId, $categoryId, $name, $sortOrder, $iconType, $iconValue);
            } else {
                Item::create($userId, $categoryId, $name, $sortOrder, $iconType, $iconValue);
            }
        } catch (\RuntimeException $e) {
            if ($e->getMessage() === 'duplicate_item') {
                json_response(400, ['success' => false, 'error' => '该分类下已存在同名项目，请勿重复添加']);
            }
            json_response(500, ['success' => false, 'error' => '新增项目时发生错误']);
        }

        json_response(200, ['success' => true]);
        break;
    }

    case 'items/update': {
        $user = require_auth_user();
        $body = parse_json_body();
        $userId = (int)$user['id'];
        $id = (int)($body['id'] ?? 0);
        $categoryId = (int)($body['category_id'] ?? 0);
        $name = trim((string)($body['name'] ?? ''));
        $sortOrder = isset($body['sort_order']) ? (int)$body['sort_order'] : 0;

        if ($id <= 0 || $categoryId <= 0 || $name === '') {
            json_response(400, ['success' => false, 'error' => '参数不完整']);
        }

        $ledgerId = 0;
        try {
            $ledgerId = LedgerContext::requireActiveLedgerId($userId);
        } catch (\Throwable $e) {
            $ledgerId = 0;
        }

        $current = $ledgerId > 0
            ? Item::findByLedger($ledgerId, $id)
            : Item::findByUser($userId, $id);
        if (!$current) {
            json_response(404, ['success' => false, 'error' => '项目不存在']);
        }

        $iconType = $current['icon_type'] ?? null;
        $iconValue = $current['icon_value'] ?? null;
        $iconLibId = (int)($body['icon_library_id'] ?? 0);
        $iconClear = !empty($body['icon_clear']);
        if ($iconClear) {
            $iconType = null;
            $iconValue = null;
        } elseif ($iconLibId > 0) {
            $icon = IconLibrary::findByUser($userId, $iconLibId);
            if ($icon) {
                $iconType = 'file';
                $iconValue = $icon['file_path'] ?? null;
            }
        }

        try {
            if ($ledgerId > 0) {
                Item::updateForLedger($ledgerId, $id, $categoryId, $name, $sortOrder, $iconType, $iconValue);
            } else {
                Item::update($userId, $id, $categoryId, $name, $sortOrder, $iconType, $iconValue);
            }
        } catch (\RuntimeException $e) {
            if ($e->getMessage() === 'duplicate_item') {
                json_response(400, ['success' => false, 'error' => '该分类下已存在同名项目，请更换一个名称']);
            }
            json_response(500, ['success' => false, 'error' => '更新项目时发生错误']);
        }

        json_response(200, ['success' => true]);
        break;
    }

    case 'items/delete': {
        $user = require_auth_user();
        $body = parse_json_body();
        $id = (int)($body['id'] ?? 0);
        if ($id <= 0) {
            json_response(400, ['success' => false, 'error' => '参数不完整']);
        }
        $userId = (int)$user['id'];
        $ledgerId = 0;
        try {
            $ledgerId = LedgerContext::requireActiveLedgerId($userId);
        } catch (\Throwable $e) {
            $ledgerId = 0;
        }

        $ok = $ledgerId > 0
            ? Item::deleteForLedger($ledgerId, $id)
            : Item::delete($userId, $id);
        if (!$ok) {
            json_response(400, ['success' => false, 'error' => '该项目已有记账数据，无法删除']);
        }
        json_response(200, ['success' => true]);
        break;
    }

    case 'icon-library/list': {
        $user = require_auth_user();
        $icons = IconLibrary::allByUser((int)$user['id']);
        $result = [];
        foreach ($icons as $icon) {
            $result[] = [
                'id' => (int)$icon['id'],
                'name' => $icon['name'],
                'file_path' => $icon['file_path'],
                'file_url' => build_file_url($icon['file_path']),
            ];
        }
        json_response(200, ['success' => true, 'icons' => $result]);
        break;
    }

    case 'icon-library/upload': {
        $user = require_auth_user();
        if (!isset($_FILES['file'])) {
            json_response(400, ['success' => false, 'error' => '缺少文件']);
        }

        $name = isset($_POST['name']) ? trim((string)$_POST['name']) : '';
        if ($name === '') {
            $name = '自定义图标';
        }

        $path = Upload::saveAttachment((int)$user['id'], $_FILES['file']);
        if (!$path) {
            json_response(400, ['success' => false, 'error' => '上传失败或文件不合法']);
        }

        $iconId = IconLibrary::create((int)$user['id'], $name, $path);
        $fileUrl = build_file_url($path);

        json_response(200, [
            'success' => true,
            'icon' => [
                'id' => $iconId,
                'name' => $name,
                'file_path' => $path,
                'file_url' => $fileUrl,
            ],
        ]);
        break;
    }

    case 'icon-library/update': {
        $user = require_auth_user();
        $body = parse_json_body();
        $id = (int)($body['id'] ?? 0);
        $name = isset($body['name']) ? trim((string)$body['name']) : '';
        if ($id <= 0) {
            json_response(400, ['success' => false, 'error' => '参数不完整']);
        }
        if ($name === '') {
            $name = '自定义图标';
        }
        $ok = IconLibrary::updateName((int)$user['id'], $id, $name);
        if (!$ok) {
            json_response(400, ['success' => false, 'error' => '更新失败，该图标可能不存在或不属于当前用户']);
        }
        json_response(200, ['success' => true]);
        break;
    }

    case 'icon-library/update-file': {
        $user = require_auth_user();
        if (!isset($_FILES['file'])) {
            json_response(400, ['success' => false, 'error' => '缺少文件']);
        }
        $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
        $name = isset($_POST['name']) ? trim((string)$_POST['name']) : '';
        if ($id <= 0) {
            json_response(400, ['success' => false, 'error' => '参数不完整']);
        }

        $path = Upload::saveAttachment((int)$user['id'], $_FILES['file']);
        if (!$path) {
            json_response(400, ['success' => false, 'error' => '上传失败或文件不合法']);
        }

        $ok = IconLibrary::updateFile((int)$user['id'], $id, $path, $name !== '' ? $name : null);
        if (!$ok) {
            json_response(400, ['success' => false, 'error' => '更新失败，该图标可能不存在或不属于当前用户']);
        }

        $icon = IconLibrary::findByUser((int)$user['id'], $id);
        if (!$icon) {
            json_response(200, ['success' => true]);
        }

        json_response(200, [
            'success' => true,
            'icon' => [
                'id' => (int)$icon['id'],
                'name' => $icon['name'],
                'file_path' => $icon['file_path'],
                'file_url' => build_file_url($icon['file_path']),
            ],
        ]);
        break;
    }

    case 'icon-library/delete': {
        $user = require_auth_user();
        $body = parse_json_body();
        $id = (int)($body['id'] ?? 0);
        if ($id <= 0) {
            json_response(400, ['success' => false, 'error' => '参数不完整']);
        }
        $ok = IconLibrary::delete((int)$user['id'], $id);
        if (!$ok) {
            json_response(400, ['success' => false, 'error' => '删除失败，该图标可能不存在或不属于当前用户']);
        }
        json_response(200, ['success' => true]);
        break;
    }

    case 'qr-login/status': {
        // PC 端轮询：根据 token 查询状态（无需授权）
        $token = isset($_GET['token']) ? trim((string)$_GET['token']) : '';
        if ($token === '') {
            json_response(400, ['success' => false, 'error' => '缺少 token']);
        }
        $row = LoginToken::findByToken($token);
        if (!$row) {
            json_response(404, ['success' => false, 'error' => '不存在或已过期']);
        }
        $status = (string)$row['status'];
        $userId = isset($row['user_id']) ? (int)$row['user_id'] : 0;
        $user = null;
        if ($status === 'confirmed' && $userId > 0) {
            $u = User::findById($userId);
            if ($u) {
                $user = [
                    'id' => (int)$u['id'],
                    'username' => $u['username'],
                    'nickname' => $u['nickname'],
                    'role' => $u['role'],
                ];
            }
        }
        json_response(200, ['success' => true, 'status' => $status, 'user' => $user]);
        break;
    }

    case 'qr-login/confirm': {
        // 小程序端确认：需要用户令牌
        $user = require_auth_user();
        $body = parse_json_body();
        $token = isset($body['token']) ? trim((string)$body['token']) : '';
        if ($token === '') {
            json_response(400, ['success' => false, 'error' => '缺少 token']);
        }
        $row = LoginToken::findByToken($token);
        if (!$row) {
            json_response(404, ['success' => false, 'error' => '二维码不存在或已过期']);
        }
        $expiresTs = strtotime((string)$row['expires_at']) ?: 0;
        if ($expiresTs > 0 && time() > $expiresTs) {
            LoginToken::expire($token);
            json_response(400, ['success' => false, 'error' => '二维码已过期，请刷新重试']);
        }
        $ok = LoginToken::confirm($token, (int)$user['id']);
        if (!$ok) {
            json_response(400, ['success' => false, 'error' => '确认失败，该二维码可能已被使用']);
        }
        json_response(200, ['success' => true]);
        break;
    }

    case 'budget/month': {
        $user = require_auth_user();
        if (!empty($_GET['ym']) && preg_match('/^(\d{4})-(\d{2})$/', (string)$_GET['ym'], $m)) {
            $year = (int)$m[1];
            $month = (int)$m[2];
        } else {
            $year = isset($_GET['year']) ? (int)$_GET['year'] : (int)date('Y');
            $month = isset($_GET['month']) ? (int)$_GET['month'] : (int)date('n');
        }

        $prevYear = $year - 1;
        $prevMonth = $month;

        $budgets = Budget::listByUserMonth((int)$user['id'], $year, $month);
        $pdo = Database::getConnection();

        $prevBudgets = Budget::listByUserMonth((int)$user['id'], $prevYear, $prevMonth);
        $prevBudgetMap = [];
        $totalPrevBudgetExpense = 0.0;
        $totalPrevUsedExpense = 0.0;
        foreach ($prevBudgets as &$pb) {
            $sql = 'SELECT COALESCE(SUM(amount),0) AS used_amount FROM transactions WHERE user_id = :uid AND type = :type AND YEAR(trans_time) = :y AND MONTH(trans_time) = :m';
            $params = [
                ':uid' => (int)$user['id'],
                ':type' => $pb['type'],
                ':y' => $prevYear,
                ':m' => $prevMonth,
            ];
            if (!empty($pb['category_id'])) {
                $sql .= ' AND category_id = :cid';
                $params[':cid'] = $pb['category_id'];
            }
            if (!empty($pb['item_id'])) {
                $sql .= ' AND item_id = :iid';
                $params[':iid'] = $pb['item_id'];
            }
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $row = $stmt->fetch(PDO::FETCH_ASSOC) ?: ['used_amount' => 0];
            $pb['used_amount'] = (float)$row['used_amount'];
            $pb['remain_amount'] = (float)$pb['amount'] - $pb['used_amount'];

            if ($pb['type'] === 'expense') {
                $totalPrevBudgetExpense += (float)$pb['amount'];
                $totalPrevUsedExpense += (float)$pb['used_amount'];
            }

            $key = $pb['type'] . '|' . ((int)($pb['category_id'] ?? 0)) . '|' . ((int)($pb['item_id'] ?? 0));
            $prevBudgetMap[$key] = $pb;
        }
        unset($pb);

        $totalBudgetExpense = 0.0;
        $totalUsedExpense = 0.0;
        $resultBudgets = [];
        foreach ($budgets as &$b) {
            $sql = 'SELECT COALESCE(SUM(amount),0) AS used_amount FROM transactions WHERE user_id = :uid AND type = :type AND YEAR(trans_time) = :y AND MONTH(trans_time) = :m';
            $params = [
                ':uid' => (int)$user['id'],
                ':type' => $b['type'],
                ':y' => $year,
                ':m' => $month,
            ];
            if (!empty($b['category_id'])) {
                $sql .= ' AND category_id = :cid';
                $params[':cid'] = $b['category_id'];
            }
            if (!empty($b['item_id'])) {
                $sql .= ' AND item_id = :iid';
                $params[':iid'] = $b['item_id'];
            }
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $row = $stmt->fetch(PDO::FETCH_ASSOC) ?: ['used_amount' => 0];
            $b['used_amount'] = (float)$row['used_amount'];
            $b['remain_amount'] = (float)$b['amount'] - $b['used_amount'];

            $key = $b['type'] . '|' . ((int)($b['category_id'] ?? 0)) . '|' . ((int)($b['item_id'] ?? 0));
            if (isset($prevBudgetMap[$key])) {
                $b['prev_budget_amount'] = (float)$prevBudgetMap[$key]['amount'];
                $b['prev_used_amount'] = (float)$prevBudgetMap[$key]['used_amount'];
            } else {
                $b['prev_budget_amount'] = 0.0;
                $b['prev_used_amount'] = 0.0;
            }

            if ($b['type'] === 'expense') {
                $totalBudgetExpense += (float)$b['amount'];
                $totalUsedExpense += (float)$b['used_amount'];
            }

            $resultBudgets[] = [
                'id' => (int)$b['id'],
                'type' => $b['type'],
                'category_id' => $b['category_id'] !== null ? (int)$b['category_id'] : null,
                'category_name' => $b['category_name'] ?? null,
                'item_id' => $b['item_id'] !== null ? (int)$b['item_id'] : null,
                'item_name' => $b['item_name'] ?? null,
                'amount' => (float)$b['amount'],
                'used_amount' => (float)$b['used_amount'],
                'remain_amount' => (float)$b['remain_amount'],
                'prev_budget_amount' => (float)$b['prev_budget_amount'],
                'prev_used_amount' => (float)$b['prev_used_amount'],
            ];
        }
        unset($b);

        json_response(200, [
            'success' => true,
            'year' => $year,
            'month' => $month,
            'prevYear' => $prevYear,
            'prevMonth' => $prevMonth,
            'budgets' => $resultBudgets,
            'totalBudgetExpense' => $totalBudgetExpense,
            'totalUsedExpense' => $totalUsedExpense,
            'totalPrevBudgetExpense' => $totalPrevBudgetExpense,
            'totalPrevUsedExpense' => $totalPrevUsedExpense,
        ]);
        break;
    }

    case 'budget/upsert': {
        $user = require_auth_user();
        $body = parse_json_body();
        $year = (int)($body['year'] ?? date('Y'));
        $month = (int)($body['month'] ?? date('n'));
        $type = (string)($body['type'] ?? 'expense');
        $categoryId = isset($body['category_id']) && $body['category_id'] !== null ? (int)$body['category_id'] : null;
        $itemId = isset($body['item_id']) && $body['item_id'] !== null ? (int)$body['item_id'] : null;
        $amount = isset($body['amount']) ? (float)$body['amount'] : 0.0;

        if ($year <= 0 || $month <= 0 || $amount <= 0) {
            json_response(400, ['success' => false, 'error' => '参数不合法']);
        }

        Budget::upsert((int)$user['id'], $year, $month, $type, $categoryId, $itemId, $amount);
        json_response(200, ['success' => true]);
        break;
    }

    case 'budget/update-amount': {
        $user = require_auth_user();
        $body = parse_json_body();
        $id = (int)($body['id'] ?? 0);
        $amount = isset($body['amount']) ? (float)$body['amount'] : 0.0;
        if ($id <= 0 || $amount <= 0) {
            json_response(400, ['success' => false, 'error' => '参数不合法']);
        }
        Budget::updateAmount((int)$user['id'], $id, $amount);
        json_response(200, ['success' => true]);
        break;
    }

    case 'budget/delete': {
        $user = require_auth_user();
        $body = parse_json_body();
        $id = (int)($body['id'] ?? 0);
        if ($id <= 0) {
            json_response(400, ['success' => false, 'error' => '参数不合法']);
        }
        Budget::delete((int)$user['id'], $id);
        json_response(200, ['success' => true]);
        break;
    }

    // 小目标：列表 / 新增 / 修改 / 删除
    case 'goals/list': {
        $user = require_auth_user();
        $userId = (int)$user['id'];
        $ledgerId = LedgerContext::requireActiveLedgerId($userId);
        if ($ledgerId < 0) {
            $ledgerId = 0;
        }

        $rows = Goal::listByUserAndLedger($userId, $ledgerId);
        $items = [];
        foreach ($rows as $g) {
            $target = (float)$g['target_amount'];
            $saved = (float)$g['saved_amount'];
            $percent = $target > 0 ? min(999, round($saved / $target * 100)) : 0;
            $bar = min(100, $percent);
            $status = (string)($g['status'] ?? 'active');

            $items[] = [
                'id' => (int)$g['id'],
                'title' => (string)$g['title'],
                'account_id' => isset($g['account_id']) ? (int)$g['account_id'] : 0,
                'target_amount' => $target,
                'saved_amount' => $saved,
                'deadline' => $g['deadline'],
                'status' => $status,
                'percent' => $percent,
                'barPercent' => $bar,
            ];
        }

        json_response(200, [
            'success' => true,
            'goals' => $items,
        ]);
        break;
    }

    case 'goals/save': {
        $user = require_auth_user();
        $userId = (int)$user['id'];
        $ledgerId = LedgerContext::requireActiveLedgerId($userId);
        if ($ledgerId < 0) {
            $ledgerId = 0;
        }

        $body = parse_json_body();
        $id = (int)($body['id'] ?? 0);
        $title = trim((string)($body['title'] ?? ''));
        $accountId = isset($body['account_id']) ? (int)$body['account_id'] : 0;
        $targetAmount = isset($body['target_amount']) ? (float)$body['target_amount'] : 0.0;
        $savedAmount = isset($body['saved_amount']) ? (float)$body['saved_amount'] : 0.0;
        $deadline = isset($body['deadline']) ? trim((string)$body['deadline']) : '';
        $status = trim((string)($body['status'] ?? 'active'));

        if ($title === '' || $targetAmount <= 0) {
            json_response(400, ['success' => false, 'error' => '请填写目标名称和目标金额']);
        }
        if ($savedAmount < 0) {
            $savedAmount = 0.0;
        }
        if ($savedAmount > $targetAmount) {
            $savedAmount = $targetAmount;
        }
        if ($status === '') {
            $status = 'active';
        }
        if (!in_array($status, ['active', 'done', 'archived'], true)) {
            $status = 'active';
        }

        if ($id > 0) {
            $ok = Goal::update($userId, $id, $accountId, $title, $targetAmount, $savedAmount, $deadline !== '' ? $deadline : null, $status);
            if (!$ok) {
                json_response(404, ['success' => false, 'error' => '目标不存在']);
            }
        } else {
            $id = Goal::create($userId, $ledgerId > 0 ? $ledgerId : 0, $accountId, $title, $targetAmount, $savedAmount, $deadline !== '' ? $deadline : null);
        }

        $goal = Goal::findOne($userId, $id);
        if (!$goal) {
            json_response(500, ['success' => false, 'error' => '保存失败']);
        }

        $target = (float)$goal['target_amount'];
        $saved = (float)$goal['saved_amount'];
        $percent = $target > 0 ? min(999, round($saved / $target * 100)) : 0;
        $bar = min(100, $percent);

        json_response(200, [
            'success' => true,
            'goal' => [
                'id' => (int)$goal['id'],
                'title' => (string)$goal['title'],
                'account_id' => isset($goal['account_id']) ? (int)$goal['account_id'] : 0,
                'target_amount' => $target,
                'saved_amount' => $saved,
                'deadline' => $goal['deadline'],
                'status' => (string)($goal['status'] ?? 'active'),
                'percent' => $percent,
                'barPercent' => $bar,
            ],
        ]);
        break;
    }

    case 'goals/delete': {
        $user = require_auth_user();
        $userId = (int)$user['id'];
        $body = parse_json_body();
        $id = (int)($body['id'] ?? 0);
        if ($id <= 0) {
            json_response(400, ['success' => false, 'error' => '参数不合法']);
        }
        Goal::delete($userId, $id);
        json_response(200, ['success' => true]);
        break;
    }

    case 'transactions/upload-attachment': {
        $user = require_auth_user();
        if (!isset($_FILES['file'])) {
            json_response(400, ['success' => false, 'error' => '缺少文件']);
        }

        $path = Upload::saveAttachment((int)$user['id'], $_FILES['file']);
        if (!$path) {
            json_response(400, ['success' => false, 'error' => '上传失败或文件不合法']);
        }

        json_response(200, [
            'success' => true,
            'path' => $path,
            'url' => build_file_url($path),
        ]);
        break;
    }

    case 'assets/list': {
        $user = require_auth_user();
        $userId = (int)$user['id'];

        $keyword = trim((string)($_GET['q'] ?? ''));
        $all = Asset::allByUser($userId, $keyword !== '' ? $keyword : null);

        $today = new \DateTimeImmutable('today');
        $activeAssets = [];
        $transferredAssets = [];
        $totalValue = 0.0;
        $totalDailyCost = 0.0;

        foreach ($all as $row) {
            $status = (string)($row['status'] ?? 'active');
            $value = (float)($row['value_amount'] ?? 0);

            $acquiredStr = trim((string)($row['acquired_date'] ?? ''));
            $acquired = $acquiredStr !== '' ? new \DateTimeImmutable($acquiredStr) : $today;

            if ($status === 'active') {
                $days = max(1, $today->diff($acquired)->days + 1);
                $daily = $days > 0 ? round($value / $days, 2) : 0.0;
                $totalValue += $value;
                $totalDailyCost += $daily;
            } else {
                $transferStr = trim((string)($row['transfer_date'] ?? ''));
                $transferDate = $transferStr !== '' ? new \DateTimeImmutable($transferStr) : $today;
                $days = max(1, $transferDate->diff($acquired)->days + 1);
                $daily = $days > 0 ? round($value / $days, 2) : 0.0;
            }

            $iconUrl = null;
            if (!empty($row['icon_type']) && $row['icon_type'] === 'file' && !empty($row['icon_value'])) {
                $iconUrl = build_file_url((string)$row['icon_value']);
            }

            $mapped = [
                'id' => (int)$row['id'],
                'name' => (string)$row['name'],
                'status' => $status,
                'acquired_date' => $row['acquired_date'],
                'transfer_date' => $row['transfer_date'] ?? null,
                'value_amount' => $value,
                'transfer_price' => isset($row['transfer_price']) ? (float)$row['transfer_price'] : null,
                'use_days' => $days,
                'daily_cost' => $daily,
                'remark' => $row['remark'] ?? null,
                'icon_url' => $iconUrl,
            ];

            if ($status === 'active') {
                $activeAssets[] = $mapped;
            } else {
                $transferredAssets[] = $mapped;
            }
        }

        $summary = [
            'total_value' => $totalValue,
            'total_daily_cost' => $totalDailyCost,
            'asset_count' => count($activeAssets),
        ];

        json_response(200, [
            'success' => true,
            'assets' => [
                'active' => $activeAssets,
                'transferred' => $transferredAssets,
            ],
            'summary' => $summary,
        ]);
        break;
    }

    case 'assets/save': {
        // 小程序 / 客户端保存资产：id>0 为编辑，否则为新增
        $user = require_auth_user();
        $userId = (int)$user['id'];
        $body = parse_json_body();

        $id = isset($body['id']) ? (int)$body['id'] : 0;
        $name = trim((string)($body['name'] ?? ''));
        $acquiredDate = trim((string)($body['acquired_date'] ?? ''));
        $valueAmount = isset($body['value_amount']) ? (float)$body['value_amount'] : 0.0;
        $remark = trim((string)($body['remark'] ?? ''));

        if ($name === '' || $acquiredDate === '' || $valueAmount <= 0) {
            json_response(400, ['success' => false, 'error' => '请填写名称、到手日期和正确的资产价值']);
        }

        // 小程序端暂不处理图标，统一置空；如需图标在 PC 端维护
        $iconType = null;
        $iconValue = null;

        try {
            if ($id > 0) {
                $ok = Asset::update($userId, $id, $name, $acquiredDate, $valueAmount, $iconType, $iconValue, $remark !== '' ? $remark : null);
                if (!$ok) {
                    json_response(404, ['success' => false, 'error' => '资产不存在或无权限编辑']);
                }
            } else {
                Asset::create($userId, $name, $acquiredDate, $valueAmount, $iconType, $iconValue, $remark !== '' ? $remark : null);
            }
        } catch (\Throwable $e) {
            json_response(500, ['success' => false, 'error' => '保存资产失败，请稍后重试']);
        }

        json_response(200, ['success' => true]);
        break;
    }

    case 'assets/transfer': {
        // 小程序端标记资产为已转手
        $user = require_auth_user();
        $userId = (int)$user['id'];
        $body = parse_json_body();

        $id = isset($body['id']) ? (int)$body['id'] : 0;
        $transferDate = trim((string)($body['transfer_date'] ?? ''));
        $transferPrice = isset($body['transfer_price']) ? (float)$body['transfer_price'] : 0.0;

        if ($id <= 0 || $transferDate === '' || $transferPrice < 0) {
            json_response(400, ['success' => false, 'error' => '请填写正确的转手日期和转手价格']);
        }

        try {
            $ok = Asset::transfer($userId, $id, $transferDate, $transferPrice);
            if (!$ok) {
                json_response(404, ['success' => false, 'error' => '资产不存在或无权限操作']);
            }
        } catch (\Throwable $e) {
            json_response(500, ['success' => false, 'error' => '设置转手信息失败，请稍后重试']);
        }

        json_response(200, ['success' => true]);
        break;
    }

    case 'assets/delete': {
        $user = require_auth_user();
        $userId = (int)$user['id'];
        $body = parse_json_body();
        $id = isset($body['id']) ? (int)$body['id'] : 0;
        if ($id <= 0) {
            json_response(400, ['success' => false, 'error' => '参数不合法']);
        }
        try {
            $ok = Asset::delete($userId, $id);
            if (!$ok) {
                json_response(404, ['success' => false, 'error' => '资产不存在或无权限删除']);
            }
        } catch (\Throwable $e) {
            json_response(500, ['success' => false, 'error' => '删除资产失败，请稍后重试']);
        }
        json_response(200, ['success' => true]);
        break;
    }

    case 'subscriptions/list': {
        $user = require_auth_user();
        $userId = (int)$user['id'];

        // 进入接口时也执行一次到期与 30 天清理逻辑
        Subscription::cleanupExpired($userId);

        $keyword = trim((string)($_GET['q'] ?? ''));
        $list = Subscription::allActiveByUser($userId, $keyword !== '' ? $keyword : null);

        $today = new \DateTimeImmutable('today');
        $result = [];

        foreach ($list as $row) {
            $expireStr = trim((string)($row['expire_date'] ?? ''));
            $daysLeft = null;
            if ($expireStr !== '') {
                $expireDate = new \DateTimeImmutable($expireStr);
                $diff = $today->diff($expireDate);
                $daysLeft = (int)$diff->format('%r%a');
            }

            $iconUrl = null;
            if (!empty($row['icon_type']) && $row['icon_type'] === 'file' && !empty($row['icon_value'])) {
                $iconUrl = build_file_url((string)$row['icon_value']);
            }

            $result[] = [
                'id' => (int)$row['id'],
                'platform' => (string)$row['platform'],
                'type' => (string)$row['type'],
                'price' => (float)$row['price'],
                'expire_date' => $row['expire_date'],
                'auto_renew' => isset($row['auto_renew']) ? ((int)$row['auto_renew'] === 1) : false,
                'period' => $row['period'] ?? null,
                'status' => (string)($row['status'] ?? 'active'),
                'remark' => $row['remark'] ?? null,
                'days_left' => $daysLeft,
                'icon_url' => $iconUrl,
            ];
        }

        json_response(200, [
            'success' => true,
            'subscriptions' => $result,
        ]);
        break;
    }

    case 'subscriptions/save': {
        // 小程序 / 客户端新增或编辑订阅 / 买断记录
        $user = require_auth_user();
        $userId = (int)$user['id'];
        $body = parse_json_body();

        $id = isset($body['id']) ? (int)$body['id'] : 0;
        $platform = trim((string)($body['platform'] ?? ''));
        $type = (string)($body['type'] ?? 'subscription');
        if ($type !== 'lifetime') {
            $type = 'subscription';
        }
        $price = isset($body['price']) ? (float)$body['price'] : 0.0;
        $expireDate = trim((string)($body['expire_date'] ?? ''));
        $autoRenew = !empty($body['auto_renew']);
        $period = $type === 'subscription' ? (trim((string)($body['period'] ?? '')) ?: null) : null;
        $remark = trim((string)($body['remark'] ?? ''));

        if ($platform === '' || $price <= 0 || ($type === 'subscription' && $expireDate === '')) {
            json_response(400, ['success' => false, 'error' => '请填写平台名称、价格，以及订阅类型下的到期日期']);
        }

        $iconType = null;
        $iconValue = null;

        try {
            $expire = $type === 'subscription' ? $expireDate : null;
            if ($id > 0) {
                $ok = Subscription::update($userId, $id, $platform, $type, $price, $expire, $autoRenew, $period, $iconType, $iconValue, $remark !== '' ? $remark : null);
                if (!$ok) {
                    json_response(404, ['success' => false, 'error' => '记录不存在或无权限编辑']);
                }
            } else {
                Subscription::create($userId, $platform, $type, $price, $expire, $autoRenew, $period, $iconType, $iconValue, $remark !== '' ? $remark : null);
            }
        } catch (\Throwable $e) {
            json_response(500, ['success' => false, 'error' => '保存订阅记录失败，请稍后重试']);
        }

        json_response(200, ['success' => true]);
        break;
    }

    case 'subscriptions/renew': {
        // 小程序端续费 / 更新到期信息
        $user = require_auth_user();
        $userId = (int)$user['id'];
        $body = parse_json_body();

        $id = isset($body['id']) ? (int)$body['id'] : 0;
        $type = (string)($body['type'] ?? 'subscription');
        if ($type !== 'lifetime') {
            $type = 'subscription';
        }
        $price = isset($body['price']) ? (float)$body['price'] : 0.0;
        $expireDate = trim((string)($body['expire_date'] ?? ''));
        $autoRenew = !empty($body['auto_renew']);
        $period = $type === 'subscription' ? (trim((string)($body['period'] ?? '')) ?: null) : null;

        if ($id <= 0 || $price <= 0 || ($type === 'subscription' && $expireDate === '')) {
            json_response(400, ['success' => false, 'error' => '请填写正确的续费金额和到期日期']);
        }

        try {
            $expire = $type === 'subscription' ? $expireDate : null;
            $ok = Subscription::renew($userId, $id, $type, $price, $expire, $autoRenew, $period);
            if (!$ok) {
                json_response(404, ['success' => false, 'error' => '续费更新失败或记录不存在']);
            }
        } catch (\Throwable $e) {
            json_response(500, ['success' => false, 'error' => '续费更新失败，请稍后重试']);
        }

        json_response(200, ['success' => true]);
        break;
    }

    case 'subscriptions/delete': {
        // 小程序端关闭订阅记录（逻辑删除，后端会在到期 30 天后物理清理）
        $user = require_auth_user();
        $userId = (int)$user['id'];
        $body = parse_json_body();
        $id = isset($body['id']) ? (int)$body['id'] : 0;
        if ($id <= 0) {
            json_response(400, ['success' => false, 'error' => '参数不合法']);
        }
        try {
            $ok = Subscription::logicalDelete($userId, $id);
            if (!$ok) {
                json_response(404, ['success' => false, 'error' => '记录不存在或无权限操作']);
            }
        } catch (\Throwable $e) {
            json_response(500, ['success' => false, 'error' => '关闭记录失败，请稍后重试']);
        }
        json_response(200, ['success' => true]);
        break;
    }

    case 'transactions/list': {
        $user = require_auth_user();
        $ledgerId = LedgerContext::requireActiveLedgerId((int)$user['id']);
        if ($ledgerId > 0 && !LedgerContext::assertCanAccessLedger((int)$user['id'], $ledgerId)) {
            json_response(403, ['success' => false, 'error' => '无权限访问该账本']);
        }
        $filters = [
            'type' => $_GET['type'] ?? '',
            'category_id' => $_GET['category_id'] ?? '',
            'item_id' => $_GET['item_id'] ?? '',
            'account_id' => $_GET['account_id'] ?? '',
            'date_from' => $_GET['date_from'] ?? '',
            'date_to' => $_GET['date_to'] ?? '',
            'amount_min' => $_GET['amount_min'] ?? '',
            'amount_max' => $_GET['amount_max'] ?? '',
            'remark' => $_GET['remark'] ?? '',
        ];

        if ($ledgerId > 0) {
            $all = Transaction::searchByLedger($ledgerId, $filters);
            $summary = Transaction::summarizeByLedger($ledgerId, $filters);
        } else {
            $all = Transaction::search((int)$user['id'], $filters);
            $summary = Transaction::summarize((int)$user['id'], $filters);
        }

        $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
        $pageSize = isset($_GET['page_size']) ? (int)$_GET['page_size'] : 20;
        if ($pageSize <= 0) {
            $pageSize = 20;
        } elseif ($pageSize > 100) {
            $pageSize = 100;
        }
        $total = count($all);
        $offset = ($page - 1) * $pageSize;
        $slice = array_slice($all, $offset, $pageSize);

        $list = [];
        foreach ($slice as $r) {
            // 计算图标 URL（仅对文件型图标输出 URL，其它类型交由前端自行处理）
            $categoryIconUrl = null;
            if (!empty($r['category_icon_type']) && $r['category_icon_type'] === 'file' && !empty($r['category_icon_value'])) {
                $categoryIconUrl = build_file_url($r['category_icon_value']);
            }

            $itemIconUrl = null;
            if (!empty($r['item_icon_type']) && $r['item_icon_type'] === 'file' && !empty($r['item_icon_value'])) {
                $itemIconUrl = build_file_url($r['item_icon_value']);
            }

            $fromAccountIconUrl = null;
            if (!empty($r['from_account_icon_type']) && $r['from_account_icon_type'] === 'file' && !empty($r['from_account_icon_value'])) {
                $fromAccountIconUrl = build_file_url($r['from_account_icon_value']);
            }

            $toAccountIconUrl = null;
            if (!empty($r['to_account_icon_type']) && $r['to_account_icon_type'] === 'file' && !empty($r['to_account_icon_value'])) {
                $toAccountIconUrl = build_file_url($r['to_account_icon_value']);
            }

            $attachments = [];
            if (!empty($r['attachments']) && is_array($r['attachments'])) {
                $attachments = $r['attachments'];
            } elseif (!empty($r['attachment_path'])) {
                $attachments = [(string)$r['attachment_path']];
            }
            $attachments = array_values(array_filter(array_map('strval', $attachments), static fn($p) => $p !== ''));
            $attachmentUrls = array_map('build_file_url', $attachments);

            $list[] = [
                'id' => (int)$r['id'],
                'type' => $r['type'],
                'source' => $r['source'] ?? 'manual',
                'category_id' => $r['category_id'] !== null ? (int)$r['category_id'] : null,
                'category_name' => $r['category_name'] ?? null,
                'category_icon_url' => $categoryIconUrl,
                'item_id' => $r['item_id'] !== null ? (int)$r['item_id'] : null,
                'item_name' => $r['item_name'] ?? null,
                'item_icon_url' => $itemIconUrl,
                'from_account_id' => $r['from_account_id'] !== null ? (int)$r['from_account_id'] : null,
                'from_account_name' => $r['from_account_name'] ?? null,
                'from_account_icon_url' => $fromAccountIconUrl,
                'to_account_id' => $r['to_account_id'] !== null ? (int)$r['to_account_id'] : null,
                'to_account_name' => $r['to_account_name'] ?? null,
                'to_account_icon_url' => $toAccountIconUrl,
                'amount' => (float)$r['amount'],
                'trans_time' => $r['trans_time'],
                'remark' => $r['remark'],
                // 兼容旧字段 + 新字段
                'attachments' => $attachments,
                'attachment_urls' => $attachmentUrls,
                'attachment_path' => $attachments[0] ?? null,
                'attachment_url' => isset($attachmentUrls[0]) ? $attachmentUrls[0] : null,
            ];
        }

        json_response(200, [
            'success' => true,
            'transactions' => $list,
            'summary' => [
                'income' => (float)$summary['income'],
                'expense' => (float)$summary['expense'],
            ],
            'pagination' => [
                'page' => $page,
                'page_size' => $pageSize,
                'total' => $total,
            ],
        ]);
        break;
    }

    case 'transactions/create': {
        $user = require_auth_user();
        $ledgerId = LedgerContext::requireActiveLedgerId((int)$user['id']);
        if ($ledgerId > 0 && !LedgerContext::assertCanAccessLedger((int)$user['id'], $ledgerId)) {
            json_response(403, ['success' => false, 'error' => '无权限访问该账本']);
        }
        $body = parse_json_body();
        $type = (string)($body['type'] ?? 'expense');
        $transferEnabled = !empty($user['enable_transfer']);
        $allowNegative = !empty($user['allow_negative_balance']);
        if (!in_array($type, ['expense', 'income', 'transfer'], true)) {
            $type = 'expense';
        }
        $source = strtolower(trim((string)($body['source'] ?? 'manual')));
        if (!in_array($source, ['manual', 'ai', 'qclaw'], true)) {
            $source = 'manual';
        }
        $categoryId = (int)($body['category_id'] ?? 0);
        $itemId = isset($body['item_id']) ? (int)$body['item_id'] : 0;
        $fromAccountId = isset($body['from_account_id']) ? (int)$body['from_account_id'] : 0;
        $toAccountId = isset($body['to_account_id']) ? (int)$body['to_account_id'] : 0;
        $amount = isset($body['amount']) ? (float)$body['amount'] : 0.0;
        $remark = trim((string)($body['remark'] ?? ''));
        $transTime = normalize_trans_time($body['trans_time'] ?? '');
        $attachmentPaths = [];
        if (isset($body['attachment_paths']) && is_array($body['attachment_paths'])) {
            $attachmentPaths = array_values(array_filter(array_map('strval', $body['attachment_paths']), static fn($p) => trim($p) !== ''));
        }
        if (!$attachmentPaths && isset($body['attachment_path'])) {
            $p = trim((string)$body['attachment_path']);
            if ($p !== '') {
                $attachmentPaths = [$p];
            }
        }
        if (count($attachmentPaths) > 5) {
            $attachmentPaths = array_slice($attachmentPaths, 0, 5);
        }
        $attachmentPath = $attachmentPaths[0] ?? '';

        if ($amount <= 0) {
            json_response(400, ['success' => false, 'error' => '金额必须大于0']);
        }
        if ($categoryId <= 0) {
            json_response(400, ['success' => false, 'error' => '请选择分类']);
        }
        if ($type === 'expense' && $fromAccountId <= 0) {
            json_response(400, ['success' => false, 'error' => '支出需要选择支出账户']);
        }
        if ($type === 'income' && $toAccountId <= 0) {
            json_response(400, ['success' => false, 'error' => '收入需要选择收入账户']);
        }
        if ($type === 'transfer') {
            if (!$transferEnabled) {
                json_response(400, ['success' => false, 'error' => '当前账号未开启账户间转账功能，请先在 PC 端设置中开启。']);
            }
            if ($fromAccountId <= 0 || $toAccountId <= 0) {
                json_response(400, ['success' => false, 'error' => '转账需要选择转出账户和转入账户']);
            }
            if ($fromAccountId === $toAccountId) {
                json_response(400, ['success' => false, 'error' => '转出账户和转入账户不能相同']);
            }
        }

        // 若未开启“允许账户为负数”，则在支出时校验账户余额是否足够
        if ($type === 'expense' && !$allowNegative && $fromAccountId > 0) {
            $account = $ledgerId > 0
                ? Account::findByLedger($ledgerId, $fromAccountId)
                : Account::findByUser((int)$user['id'], $fromAccountId);
            if ($account) {
                $currentBalance = (float)($account['current_balance'] ?? 0);
                if ($currentBalance - $amount < 0) {
                    json_response(400, ['success' => false, 'error' => '账户余额不足，当前未开启“允许账户为负数”，请更换账户或调整金额。']);
                }
            }
        }

        $data = [
            'user_id' => (int)$user['id'],
            'ledger_id' => $ledgerId > 0 ? $ledgerId : null,
            'type' => $type,
            'category_id' => $categoryId,
            'item_id' => $itemId ?: null,
            'from_account_id' => $fromAccountId ?: null,
            'to_account_id' => $toAccountId ?: null,
            'amount' => $amount,
            'trans_time' => $transTime,
            'remark' => $remark,
            'attachment_path' => $attachmentPath !== '' ? $attachmentPath : null,
            'source' => $source,
        ];

        apply_balance_change($type, $fromAccountId, $toAccountId, $amount, 1);
        $id = Transaction::create($data);

        if ($id > 0) {
            TransactionAttachment::replaceForTransaction($id, $attachmentPaths);
        }

        json_response(200, ['success' => true, 'id' => $id]);
        break;
    }

    case 'transactions/update': {
        $user = require_auth_user();
        $ledgerId = LedgerContext::requireActiveLedgerId((int)$user['id']);
        if ($ledgerId > 0 && !LedgerContext::assertCanAccessLedger((int)$user['id'], $ledgerId)) {
            json_response(403, ['success' => false, 'error' => '无权限访问该账本']);
        }
        $body = parse_json_body();
        $id = (int)($body['id'] ?? 0);
        if ($id <= 0) {
            json_response(400, ['success' => false, 'error' => '参数不完整']);
        }
        $tx = $ledgerId > 0 ? Transaction::findByIdInLedger($id, $ledgerId) : Transaction::findById($id, (int)$user['id']);
        if (!$tx) {
            json_response(404, ['success' => false, 'error' => '记录不存在']);
        }

        if ($ledgerId > 0 && (int)($tx['user_id'] ?? 0) !== (int)$user['id']) {
            $canEditOthers = false;
            $ledger = Ledger::findById($ledgerId);
            if ($ledger && (int)($ledger['owner_user_id'] ?? 0) === (int)$user['id']) {
                $canEditOthers = true;
            } else {
                try {
                    $role = LedgerMember::getRole($ledgerId, (int)$user['id']);
                    $canEditOthers = ($role === 'admin');
                } catch (\Throwable $e) {
                    $canEditOthers = false;
                }
            }
            if (!$canEditOthers) {
                json_response(403, ['success' => false, 'error' => '无权限编辑他人记录']);
            }
        }

        $type = isset($body['type']) ? (string)$body['type'] : (string)$tx['type'];
        $transferEnabled = !empty($user['enable_transfer']);
        $allowNegative = !empty($user['allow_negative_balance']);
        if (!in_array($type, ['expense', 'income', 'transfer'], true)) {
            $type = (string)$tx['type'];
        }
        $source = array_key_exists('source', $body)
            ? strtolower(trim((string)$body['source']))
            : (string)($tx['source'] ?? 'manual');
        $categoryId = isset($body['category_id']) ? (int)$body['category_id'] : (int)$tx['category_id'];
        $itemId = isset($body['item_id']) ? (int)$body['item_id'] : (int)($tx['item_id'] ?? 0);
        $fromAccountId = isset($body['from_account_id']) ? (int)$body['from_account_id'] : (int)($tx['from_account_id'] ?? 0);
        $toAccountId = isset($body['to_account_id']) ? (int)$body['to_account_id'] : (int)($tx['to_account_id'] ?? 0);
        $amount = isset($body['amount']) ? (float)$body['amount'] : (float)$tx['amount'];
        $remark = isset($body['remark']) ? trim((string)$body['remark']) : (string)($tx['remark'] ?? '');
        $transTime = isset($body['trans_time']) ? normalize_trans_time($body['trans_time']) : $tx['trans_time'];
        $attachmentPaths = null;
        if (array_key_exists('attachment_paths', $body)) {
            if (is_array($body['attachment_paths'])) {
                $attachmentPaths = array_values(array_filter(array_map('strval', $body['attachment_paths']), static fn($p) => trim($p) !== ''));
                if (count($attachmentPaths) > 5) {
                    $attachmentPaths = array_slice($attachmentPaths, 0, 5);
                }
            } else {
                $attachmentPaths = [];
            }
        }
        if ($attachmentPaths === null && array_key_exists('attachment_path', $body)) {
            $p = trim((string)$body['attachment_path']);
            $attachmentPaths = $p !== '' ? [$p] : [];
        }

        $attachmentPath = $tx['attachment_path'] ?? null;
        if (is_array($attachmentPaths)) {
            $attachmentPath = $attachmentPaths[0] ?? null;
        }

        if ($amount <= 0) {
            json_response(400, ['success' => false, 'error' => '金额必须大于0']);
        }
        if ($categoryId <= 0) {
            json_response(400, ['success' => false, 'error' => '请选择分类']);
        }
        if ($type === 'expense' && $fromAccountId <= 0) {
            json_response(400, ['success' => false, 'error' => '支出需要选择支出账户']);
        }
        if ($type === 'income' && $toAccountId <= 0) {
            json_response(400, ['success' => false, 'error' => '收入需要选择收入账户']);
        }
        if ($type === 'transfer') {
            if (!$transferEnabled) {
                json_response(400, ['success' => false, 'error' => '当前账号未开启账户间转账功能，请先在 PC 端设置中开启。']);
            }
            if ($fromAccountId <= 0 || $toAccountId <= 0) {
                json_response(400, ['success' => false, 'error' => '转账需要选择转出账户和转入账户']);
            }
            if ($fromAccountId === $toAccountId) {
                json_response(400, ['success' => false, 'error' => '转出账户和转入账户不能相同']);
            }
        }

        // 编辑时：未开启“允许账户为负数”，则在支出场景下校验最终余额是否会小于 0
        if ($type === 'expense' && !$allowNegative && $fromAccountId > 0) {
            $account = $ledgerId > 0
                ? Account::findByLedger($ledgerId, $fromAccountId)
                : Account::findByUser((int)$user['id'], $fromAccountId);
            if ($account) {
                $currentBalance = (float)($account['current_balance'] ?? 0);
                $oldType = (string)$tx['type'];
                $oldFromId = (int)($tx['from_account_id'] ?? 0);
                $oldToId = (int)($tx['to_account_id'] ?? 0);
                $oldAmount = (float)$tx['amount'];
                $oldEffect = 0.0;
                if ($oldType === 'expense' && $oldFromId === $fromAccountId) {
                    $oldEffect = -$oldAmount;
                } elseif ($oldType === 'income' && $oldToId === $fromAccountId) {
                    $oldEffect = $oldAmount;
                } elseif ($oldType === 'transfer') {
                    if ($oldFromId === $fromAccountId) {
                        $oldEffect = -$oldAmount;
                    } elseif ($oldToId === $fromAccountId) {
                        $oldEffect = $oldAmount;
                    }
                }
                $balanceBeforeNew = $currentBalance - $oldEffect;
                $finalBalance = $balanceBeforeNew - $amount;
                if ($finalBalance < 0) {
                    json_response(400, ['success' => false, 'error' => '账户余额不足，当前未开启“允许账户为负数”，请更换账户或调整金额。']);
                }
            }
        }

        apply_balance_change($tx['type'], (int)($tx['from_account_id'] ?? 0), (int)($tx['to_account_id'] ?? 0), (float)$tx['amount'], -1);
        apply_balance_change($type, $fromAccountId, $toAccountId, $amount, 1);

        $data = [
            'type' => $type,
            'category_id' => $categoryId,
            'item_id' => $itemId ?: null,
            'from_account_id' => $fromAccountId ?: null,
            'to_account_id' => $toAccountId ?: null,
            'amount' => $amount,
            'trans_time' => $transTime,
            'remark' => $remark,
            'source' => $source,
            'attachment_path' => $attachmentPath,
        ];
        if ($ledgerId > 0) {
            Transaction::updateInLedger($id, $ledgerId, $data);
        } else {
            Transaction::update($id, (int)$user['id'], $data);
        }

        if (is_array($attachmentPaths)) {
            TransactionAttachment::replaceForTransaction($id, $attachmentPaths);
        }

        json_response(200, ['success' => true]);
        break;
    }

    case 'transactions/delete': {
        $user = require_auth_user();
        $ledgerId = LedgerContext::requireActiveLedgerId((int)$user['id']);
        if ($ledgerId > 0 && !LedgerContext::assertCanAccessLedger((int)$user['id'], $ledgerId)) {
            json_response(403, ['success' => false, 'error' => '无权限访问该账本']);
        }
        $body = parse_json_body();
        $ids = isset($body['ids']) && is_array($body['ids']) ? array_map('intval', $body['ids']) : [];
        if (empty($ids)) {
            json_response(400, ['success' => false, 'error' => '缺少要删除的ID']);
        }

        $canEditOthers = false;
        if ($ledgerId > 0) {
            $ledger = Ledger::findById($ledgerId);
            if ($ledger && (int)($ledger['owner_user_id'] ?? 0) === (int)$user['id']) {
                $canEditOthers = true;
            } else {
                try {
                    $role = LedgerMember::getRole($ledgerId, (int)$user['id']);
                    $canEditOthers = ($role === 'admin');
                } catch (\Throwable $e) {
                    $canEditOthers = false;
                }
            }
        }

        foreach ($ids as $id) {
            $tx = $ledgerId > 0 ? Transaction::findByIdInLedger((int)$id, $ledgerId) : Transaction::findById((int)$id, (int)$user['id']);
            if (!$tx) {
                continue;
            }
            if ($ledgerId > 0 && !$canEditOthers && (int)($tx['user_id'] ?? 0) !== (int)$user['id']) {
                json_response(403, ['success' => false, 'error' => '无权限删除他人记录']);
            }
            apply_balance_change($tx['type'], (int)($tx['from_account_id'] ?? 0), (int)($tx['to_account_id'] ?? 0), (float)$tx['amount'], -1);
        }
        $deleted = $ledgerId > 0 ? Transaction::deleteManyInLedger($ledgerId, $ids) : Transaction::deleteMany((int)$user['id'], $ids);

        json_response(200, ['success' => true, 'deleted' => $deleted]);
        break;
    }

    case 'reports/summary': {
        $userId = require_auth_user()['id'];
        $mode = $_GET['mode'] ?? 'month';

        $compareLastYear = isset($_GET['compare_last_year']) && (int)$_GET['compare_last_year'] === 1;

        $ledgerId = LedgerContext::requireActiveLedgerId((int)$userId);
        $year = isset($_GET['year']) ? (int)$_GET['year'] : (int)date('Y');
        $month = isset($_GET['month']) ? (int)$_GET['month'] : (int)date('n');
        $quarter = isset($_GET['quarter']) ? (int)$_GET['quarter'] : (int)ceil($month / 3);

        $dateFrom = $_GET['date_from'] ?? '';
        $dateTo = $_GET['date_to'] ?? '';

        $start = new \DateTime('today');
        $end = new \DateTime('today');

        switch ($mode) {
            case 'year':
                $start = new \DateTime($year . '-01-01');
                $end = new \DateTime($year . '-12-31');
                break;
            case 'quarter':
                $startMonth = ($quarter - 1) * 3 + 1;
                $start = new \DateTime(sprintf('%d-%02d-01', $year, $startMonth));
                $end = clone $start;
                $end->modify('+2 months')->modify('last day of this month');
                break;
            case 'day':
                $start = new \DateTime();
                $end = new \DateTime();
                break;
            case 'yesterday':
                $start = new \DateTime('yesterday');
                $end = new \DateTime('yesterday');
                break;
            case 'custom':
                if ($dateFrom && $dateTo) {
                    $start = new \DateTime($dateFrom);
                    $end = new \DateTime($dateTo);
                }
                break;
            case 'month':
            default:
                $start = new \DateTime(sprintf('%d-%02d-01', $year, $month));
                $end = clone $start;
                $end->modify('last day of this month');
                break;
        }

        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT DATE(trans_time) AS d, type, COALESCE(SUM(amount),0) AS total, COUNT(*) AS cnt
            FROM transactions
            WHERE ' . ($ledgerId > 0 ? 'ledger_id = :lid' : 'user_id = :uid') . ' AND trans_time BETWEEN :from AND :to
            GROUP BY DATE(trans_time), type
            ORDER BY d');
        $params = [
            ':from' => $start->format('Y-m-d 00:00:00'),
            ':to' => $end->format('Y-m-d 23:59:59'),
        ];
        if ($ledgerId > 0) {
            $params[':lid'] = (int)$ledgerId;
        } else {
            $params[':uid'] = (int)$userId;
        }
        $stmt->execute($params);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

        $labels = [];
        $incomeData = [];
        $expenseData = [];
        $incomeLastData = [];
        $expenseLastData = [];
        $byDate = [];
        foreach ($rows as $r) {
            $d = $r['d'];
            if (!isset($byDate[$d])) {
                $byDate[$d] = ['income' => 0.0, 'expense' => 0.0, 'income_count' => 0, 'expense_count' => 0];
            }
            if ($r['type'] === 'income') {
                $byDate[$d]['income'] += (float)$r['total'];
                $byDate[$d]['income_count'] += (int)$r['cnt'];
            } elseif ($r['type'] === 'expense') {
                $byDate[$d]['expense'] += (float)$r['total'];
                $byDate[$d]['expense_count'] += (int)$r['cnt'];
            }
        }
        foreach ($byDate as $d => $v) {
            $labels[] = $d;
            $incomeData[] = $v['income'];
            $expenseData[] = $v['expense'];
        }

        $totalIncome = array_sum($incomeData);
        $totalExpense = array_sum($expenseData);
        $totalIncomeCount = 0;
        $totalExpenseCount = 0;
        foreach ($byDate as $v) {
            $totalIncomeCount += (int)($v['income_count'] ?? 0);
            $totalExpenseCount += (int)($v['expense_count'] ?? 0);
        }
        $totalCount = $totalIncomeCount + $totalExpenseCount;

        $totalBudgetExpense = 0.0;
        $totalUsedExpense = 0.0;
        if (in_array($mode, ['year', 'quarter', 'month'], true)) {
            if ($mode === 'year') {
                $startMonth = 1;
                $endMonth = 12;
            } elseif ($mode === 'quarter') {
                $startMonth = ($quarter - 1) * 3 + 1;
                $endMonth = $startMonth + 2;
            } else {
                $startMonth = $month;
                $endMonth = $month;
            }

            for ($m = $startMonth; $m <= $endMonth; $m++) {
                [$bTotal, $uTotal] = summarize_budget_by_month((int)$userId, $year, $m, (int)$ledgerId);
                $totalBudgetExpense += $bTotal;
                $totalUsedExpense += $uTotal;
            }
        }

        $totalIncomeLast = 0.0;
        $totalExpenseLast = 0.0;
        if ($compareLastYear && !empty($byDate)) {
            $startLast = clone $start;
            $endLast = clone $end;
            $startLast->modify('-1 year');
            $endLast->modify('-1 year');

            $stmtLast = $pdo->prepare('SELECT DATE(trans_time) AS d, type, COALESCE(SUM(amount),0) AS total, COUNT(*) AS cnt
                FROM transactions
                WHERE ' . ($ledgerId > 0 ? 'ledger_id = :lid' : 'user_id = :uid') . ' AND trans_time BETWEEN :from AND :to
                GROUP BY DATE(trans_time), type
                ORDER BY d');
            $paramsLast = [
                ':from' => $startLast->format('Y-m-d 00:00:00'),
                ':to' => $endLast->format('Y-m-d 23:59:59'),
            ];
            if ($ledgerId > 0) {
                $paramsLast[':lid'] = (int)$ledgerId;
            } else {
                $paramsLast[':uid'] = (int)$userId;
            }
            $stmtLast->execute($paramsLast);
            $rowsLast = $stmtLast->fetchAll(PDO::FETCH_ASSOC) ?: [];

            $byDateLast = [];
            foreach ($rowsLast as $r) {
                $d = $r['d'];
                if (!isset($byDateLast[$d])) {
                    $byDateLast[$d] = ['income' => 0.0, 'expense' => 0.0];
                }
                if ($r['type'] === 'income') {
                    $byDateLast[$d]['income'] += (float)$r['total'];
                } elseif ($r['type'] === 'expense') {
                    $byDateLast[$d]['expense'] += (float)$r['total'];
                }
            }

            foreach ($byDate as $d => $v) {
                $dt = new \DateTime($d);
                $dLast = $dt->modify('-1 year')->format('Y-m-d');
                $incomeLast = isset($byDateLast[$dLast]) ? (float)$byDateLast[$dLast]['income'] : 0.0;
                $expenseLast = isset($byDateLast[$dLast]) ? (float)$byDateLast[$dLast]['expense'] : 0.0;
                $incomeLastData[] = $incomeLast;
                $expenseLastData[] = $expenseLast;
                $totalIncomeLast += $incomeLast;
                $totalExpenseLast += $expenseLast;
            }
        }

        json_response(200, [
            'success' => true,
            'mode' => $mode,
            'year' => $year,
            'month' => $month,
            'quarter' => $quarter,
            'dateFrom' => $start->format('Y-m-d'),
            'dateTo' => $end->format('Y-m-d'),
            'labels' => $labels,
            'incomeData' => $incomeData,
            'expenseData' => $expenseData,
            'compareLastYear' => $compareLastYear,
            'incomeLastData' => $incomeLastData,
            'expenseLastData' => $expenseLastData,
            'totalIncome' => $totalIncome,
            'totalExpense' => $totalExpense,
            'totalIncomeCount' => $totalIncomeCount,
            'totalExpenseCount' => $totalExpenseCount,
            'totalCount' => $totalCount,
            'totalIncomeLast' => $totalIncomeLast,
            'totalExpenseLast' => $totalExpenseLast,
            'totalBudgetExpense' => $totalBudgetExpense,
            'totalUsedExpense' => $totalUsedExpense,
        ]);
        break;
    }

    case 'debt/current-month': {
        // 获取当月应还数据
        $user = require_auth_user();
        $userId = (int)$user['id'];
        $ledgerId = LedgerContext::requireActiveLedgerId($userId);

        try {
            $payments = DebtPayment::getCurrentMonthPayments($userId, $ledgerId);
            
            $totalAmount = 0.0;
            $totalCount = count($payments);
            $result = [];
            
            foreach ($payments as $payment) {
                $totalAmount += (float)$payment['total_amount'];
                
                // 计算剩余期数
                $stmt = Database::getConnection()->prepare('
                    SELECT COUNT(*) FROM debt_payment 
                    WHERE debt_config_id = :debt_id AND status != "paid"
                ');
                $stmt->execute([':debt_id' => $payment['debt_config_id']]);
                $remainingPeriods = (int)$stmt->fetchColumn();
                
                // 计算剩余金额
                $stmt = Database::getConnection()->prepare('
                    SELECT SUM(total_amount) FROM debt_payment 
                    WHERE debt_config_id = :debt_id AND status != "paid"
                ');
                $stmt->execute([':debt_id' => $payment['debt_config_id']]);
                $remainingAmount = (float)$stmt->fetchColumn();
                
                $result[] = [
                    'id' => (int)$payment['id'],
                    'debt_config_id' => (int)$payment['debt_config_id'],
                    'debt_name' => (string)$payment['debt_name'],
                    'period_number' => (int)$payment['period_number'],
                    'total_amount' => (float)$payment['total_amount'],
                    'due_date' => $payment['due_date'],
                    'status' => (string)$payment['status'],
                    'paid_amount' => $payment['paid_amount'] ? (float)$payment['paid_amount'] : null,
                    'paid_date' => $payment['paid_date'] ?? null,
                    'remaining_periods' => $remainingPeriods,
                    'remaining_amount' => $remainingAmount,
                ];
            }
            
            // 查询当月已还款金额
            $paidAmount = 0.0;
            $paidCount = 0;
            try {
                $stmt = Database::getConnection()->prepare('
                    SELECT COALESCE(SUM(paid_amount), 0) AS total, COUNT(*) AS cnt
                    FROM debt_payment
                    WHERE ledger_id = :lid
                      AND status = "paid"
                      AND DATE_FORMAT(paid_date, "%Y-%m") = DATE_FORMAT(NOW(), "%Y-%m")
                ');
                $stmt->execute([':lid' => $ledgerId]);
                $paidRow = $stmt->fetch(PDO::FETCH_ASSOC);
                if ($paidRow) {
                    $paidAmount = (float)$paidRow['total'];
                    $paidCount = (int)$paidRow['cnt'];
                }
            } catch (\Throwable $e) {
                $paidAmount = 0.0;
                $paidCount = 0;
            }
            
            json_response(200, [
                'success' => true,
                'payments' => $result,
                'total_amount' => $totalAmount,
                'total_count' => $totalCount,
                'paid_amount' => $paidAmount,
                'paid_count' => $paidCount,
            ]);
        } catch (\Throwable $e) {
            json_response(500, ['success' => false, 'error' => '获取当月应还数据失败']);
        }
        break;
    }

    case 'debt/summary': {
        // 获取负债汇总统计
        $user = require_auth_user();
        $userId = (int)$user['id'];
        $ledgerId = LedgerContext::requireActiveLedgerId($userId);

        try {
            $summary = DebtPayment::getSummary($userId, $ledgerId);
            
            $grandTotalPrincipal = 0.0;
            $grandTotalInterest = 0.0;
            $grandTotalPaid = 0.0;
            $grandTotalRemaining = 0.0;
            $grandTotalPeriods = 0;
            $grandPaidPeriods = 0;
            
            $result = [];
            foreach ($summary as $item) {
                $grandTotalPrincipal += (float)$item['total_principal'];
                $grandTotalInterest += (float)$item['total_interest'];
                $grandTotalPaid += (float)$item['total_paid'];
                $grandTotalRemaining += (float)$item['remaining_amount'];
                $grandTotalPeriods += (int)$item['installment_count'];
                $grandPaidPeriods += (int)$item['paid_periods'];
                
                $result[] = [
                    'debt_id' => (int)$item['debt_id'],
                    'debt_name' => (string)$item['debt_name'],
                    'total_principal' => (float)$item['total_principal'],
                    'total_interest' => (float)$item['total_interest'],
                    'installment_count' => (int)$item['installment_count'],
                    'per_period_total' => (float)$item['per_period_total'],
                    'paid_periods' => (int)$item['paid_periods'],
                    'remaining_periods' => (int)$item['remaining_periods'],
                    'total_paid' => (float)$item['total_paid'],
                    'remaining_amount' => (float)$item['remaining_amount'],
                    'progress_percent' => (int)$item['progress_percent'],
                ];
            }
            
            json_response(200, [
                'success' => true,
                'summary' => $result,
                'grand_total_principal' => $grandTotalPrincipal,
                'grand_total_interest' => $grandTotalInterest,
                'grand_total_paid' => $grandTotalPaid,
                'grand_total_remaining' => $grandTotalRemaining,
                'grand_total_periods' => $grandTotalPeriods,
                'grand_paid_periods' => $grandPaidPeriods,
                'grand_progress_percent' => $grandTotalPeriods > 0 ? round(($grandPaidPeriods / $grandTotalPeriods) * 100) : 0,
            ]);
        } catch (\Throwable $e) {
            json_response(500, ['success' => false, 'error' => '获取负债汇总失败']);
        }
        break;
    }

    case 'reimbursement/pending': {
        // 获取报销列表（全部状态）
        $user = require_auth_user();
        $userId = (int)$user['id'];
        $ledgerId = LedgerContext::requireActiveLedgerId($userId);
        try {
            $items = Reimbursement::getAll($ledgerId);
            $overview = Reimbursement::getOverview($ledgerId);
            
            $result = [];
            foreach ($items as $item) {
                $result[] = [
                    'id' => (int)$item['id'],
                    'title' => (string)$item['title'],
                    'amount' => (float)$item['amount'],
                    'category_name' => $item['category_name'] ?? null,
                    'description' => $item['description'] ?? null,
                    'status' => (string)$item['status'],
                    'created_at' => $item['created_at'],
                    'updated_at' => $item['updated_at'] ?? null,
                    'transaction_amount' => $item['transaction_amount'] ? (float)$item['transaction_amount'] : null,
                    'transaction_date' => $item['transaction_date'] ?? null,
                ];
            }
            
            json_response(200, [
                'success' => true,
                'items' => $result,
                'overview' => $overview,
            ]);
        } catch (\Throwable $e) {
            json_response(500, ['success' => false, 'error' => '获取报销列表失败']);
        }
        break;
    }

    case 'reimbursement/list': {
        // 获取报销列表（合并全部状态，兼容旧客户端）
        $user = require_auth_user();
        $userId = (int)$user['id'];
        $ledgerId = LedgerContext::requireActiveLedgerId($userId);

        try {
            $items = Reimbursement::getAll($ledgerId);
            $overview = Reimbursement::getOverview($ledgerId);
            
            $result = [];
            foreach ($items as $item) {
                $result[] = [
                    'id' => (int)$item['id'],
                    'title' => (string)$item['title'],
                    'amount' => (float)$item['amount'],
                    'category_name' => $item['category_name'] ?? null,
                    'description' => $item['description'] ?? null,
                    'status' => (string)$item['status'],
                    'created_at' => $item['created_at'],
                    'updated_at' => $item['updated_at'] ?? null,
                    'transaction_amount' => $item['transaction_amount'] ? (float)$item['transaction_amount'] : null,
                    'transaction_date' => $item['transaction_date'] ?? null,
                ];
            }
            
            json_response(200, [
                'success' => true,
                'items' => $result,
                'overview' => $overview,
            ]);
        } catch (\Throwable $e) {
            json_response(500, ['success' => false, 'error' => '获取报销列表失败']);
        }
        break;
    }

    case 'reimbursement/overview': {
        // 获取报销概览数据
        $user = require_auth_user();
        $userId = (int)$user['id'];
        $ledgerId = LedgerContext::requireActiveLedgerId($userId);

        try {
            $overview = Reimbursement::getOverview($ledgerId);
            $monthly = Reimbursement::getMonthlyStats($ledgerId);
            $category = Reimbursement::getCategoryStats($ledgerId);
            
            json_response(200, [
                'success' => true,
                'overview' => $overview,
                'monthly' => $monthly,
                'category' => $category,
            ]);
        } catch (\Throwable $e) {
            json_response(500, ['success' => false, 'error' => '获取报销概览失败']);
        }
        break;
    }

    case 'debt/config': {
        // 获取负债配置列表
        $user = require_auth_user();
        $userId = (int)$user['id'];
        $ledgerId = LedgerContext::requireActiveLedgerId($userId);

        try {
            $configs = DebtConfig::allByLedger($ledgerId);
            $result = [];
            foreach ($configs as $item) {
                $result[] = [
                    'id' => (int)$item['id'],
                    'name' => (string)$item['name'],
                    'total_principal' => (float)$item['total_principal'],
                    'total_interest' => (float)$item['total_interest'],
                    'installment_count' => (int)$item['installment_count'],
                    'per_period_total' => (float)$item['per_period_total'],
                    'first_payment_date' => $item['first_payment_date'],
                    'repayment_method' => $item['repayment_method'] ?? 'equal',
                    'note' => $item['note'] ?? null,
                    'status' => $item['status'] ?? 'active',
                ];
            }
            json_response(200, [
                'success' => true,
                'configs' => $result,
            ]);
        } catch (\Throwable $e) {
            json_response(500, ['success' => false, 'error' => '获取负债配置失败']);
        }
        break;
    }


    case 'reimbursement/config': {
        // 获取/保存报销配置
        $user = require_auth_user();
        $userId = (int)$user['id'];
        $ledgerId = LedgerContext::requireActiveLedgerId($userId);

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = parse_json_body();
            try {
                ReimbursementConfig::update($ledgerId, [
                    'enabled' => isset($data['enabled']) ? (int)(bool)$data['enabled'] : 1,
                ]);
                json_response(200, ['success' => true]);
            } catch (\Throwable $e) {
                json_response(500, ['success' => false, 'error' => '保存失败']);
            }
            break;
        }

        try {
            $config = ReimbursementConfig::getOrCreate($ledgerId);
            json_response(200, [
                'success' => true,
                'config' => [
                    'enabled' => (bool)($config['enabled'] ?? true),
                ],
            ]);
        } catch (\Throwable $e) {
            json_response(500, ['success' => false, 'error' => '获取配置失败']);
        }
        break;
    }


    default:
        json_response(404, ['success' => false, 'error' => '未知接口']);
}