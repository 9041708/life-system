<?php
namespace App\Service;

use App\Model\ForumAccount;
use App\Model\ForumActionLog;
use App\Model\ForumRepliedThread;

class DiscuzService
{
    private string $forumUrl;
    private string $cookieFile;
    private int $accountId;
    private int $userId;
    private int $timeout = 15;

    public function __construct(int $userId, array $account)
    {
        $this->userId = $userId;
        $this->accountId = (int)$account['id'];
        $this->forumUrl = rtrim($account['forum_url'], '/');
        $this->cookieFile = sys_get_temp_dir() . '/forum_cookie_' . $account['id'] . '.txt';
    }

    /**
     * 测试论坛连接
     */
    public function testConnection(): array
    {
        $url = $this->forumUrl . '/index.php';
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => $this->timeout,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_FOLLOWLOCATION => true,
        ]);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            return ['ok' => false, 'error' => "连接失败: $error"];
        }
        if ($httpCode !== 200) {
            return ['ok' => false, 'error' => "HTTP错误: $httpCode"];
        }
        if (stripos($response, 'discuz') === false && stripos($response, 'forum') === false) {
            return ['ok' => false, 'error' => '可能不是DISCUZ论坛'];
        }
        return ['ok' => true, 'message' => '连接成功'];
    }

    /**
     * 登录论坛
     */
    public function login(string $username, string $password): array
    {
        // 检查缓存的Cookie是否有效
        $account = ForumAccount::findById($this->accountId);
        if (!empty($account['cookie_data']) && !empty($account['cookie_expire'])) {
            if (strtotime($account['cookie_expire']) > time()) {
                $this->writeCookie($account['cookie_data']);
                if ($this->checkLogin()) {
                    return ['ok' => true, 'message' => '使用缓存Cookie登录成功'];
                }
                // Cookie失效，清理旧数据
                ForumAccount::updateCookie($this->accountId, '', date('Y-m-d H:i:s'));
                @unlink($this->cookieFile);
            }
        }

        // 确保cookie文件不存在旧数据
        @unlink($this->cookieFile);

        // 获取登录页面提取formhash
        $loginPage = $this->httpGet('/member.php?mod=logging&action=login');
        if (!$loginPage['ok']) {
            return ['ok' => false, 'error' => '无法访问登录页面'];
        }

        $formhash = $this->extractFormhash($loginPage['body']);
        if (!$formhash) {
            return ['ok' => false, 'error' => '无法获取formhash'];
        }

        // 提交登录
        $postData = http_build_query([
            'formhash' => $formhash,
            'loginfield' => 'username',
            'username' => $username,
            'password' => $password,
            'questionid' => 0,
            'answer' => '',
            'cookietime' => 2592000,
        ]);

        $result = $this->httpPost('/member.php?mod=logging&action=login&loginsubmit=yes&inajax=1', $postData);
        if (!$result['ok']) {
            return ['ok' => false, 'error' => '登录请求失败'];
        }

        // 检查登录结果
        if (stripos($result['body'], 'succeedhandle_login') !== false || stripos($result['body'], 'location') !== false) {
            // 登录成功，保存Cookie
            $cookieData = file_exists($this->cookieFile) ? file_get_contents($this->cookieFile) : '';
            if ($cookieData) {
                $expireTime = date('Y-m-d H:i:s', time() + 2592000); // 30天
                ForumAccount::updateCookie($this->accountId, $cookieData, $expireTime);
            }
            return ['ok' => true, 'message' => '登录成功'];
        }

        // 尝试解析错误信息
        if (preg_match('/<error>(.*?)<\/error>/s', $result['body'], $m)) {
            $errorMsg = strip_tags($m[1]);
            return ['ok' => false, 'error' => "登录失败: $errorMsg"];
        }

        return ['ok' => false, 'error' => '登录失败，请检查用户名和密码'];
    }

    /**
     * 检查是否已登录
     */
    private function checkLogin(): bool
    {
        // 尝试多种方式检查登录状态
        $checkUrls = ['/home.php?mod=space', '/forum.php', '/'];
        
        foreach ($checkUrls as $url) {
            $result = $this->httpGet($url);
            if (!$result['ok']) continue;
            
            $body = $result['body'];
            
            // 如果页面包含登录表单，说明未登录
            if (stripos($body, 'name="loginfield"') !== false && stripos($body, 'member.php?mod=logging') !== false) {
                return false;
            }
            
            // 检查discuz_uid变量（最可靠的标识）
            if (preg_match('/discuz_uid\s*=\s*[\'"](\d+)[\'"]/', $body, $m) && (int)$m[1] > 0) {
                return true;
            }
            
            // 检查其他登录标识
            if (stripos($body, '退出') !== false || 
                stripos($body, 'logout') !== false ||
                stripos($body, 'my.php') !== false ||
                stripos($body, '我的空间') !== false) {
                return true;
            }
        }
        
        return false;
    }

    /**
     * 论坛签到
     */
    public function signin(array $account = []): array
    {
        // 确保登录状态
        if (!$this->checkLogin()) {
            return ['ok' => false, 'error' => '登录状态失效，请重新登录'];
        }

        // 获取formhash（从任意页面）
        $formhash = null;
        $hashPages = ['/', '/forum.php', '/home.php?mod=space'];
        foreach ($hashPages as $hp) {
            $hr = $this->httpGet($hp);
            if ($hr['ok']) {
                $formhash = $this->extractFormhash($hr['body']);
                if ($formhash) break;
            }
        }
        if (!$formhash) {
            return ['ok' => false, 'error' => '无法获取formhash'];
        }

        // 方式0: 使用自定义签到页面链接（自动解析表单）
        $customUrl = trim($account['signin_url'] ?? '');
        if (!empty($customUrl)) {
            $customUrl = preg_replace('/[\x00-\x1F\x7F\xEF\xBB\xBF]/u', '', $customUrl);

            $pageUrl = $customUrl;
            if (stripos($pageUrl, 'http') !== 0) {
                $parsed = parse_url($this->forumUrl);
                $pageUrl = ($parsed['scheme'] ?? 'https') . '://' . ($parsed['host'] ?? $this->forumUrl) . '/' . ltrim($customUrl, '/');
            }

            // 辅助：回签到页面验证签到状态
            $verifySignin = function() use ($pageUrl) {
                $re = $this->httpGet($pageUrl);
                if (!$re['ok']) return null;
                $b = $re['body'];
                $hasBtn = (stripos($b, '立即签到') !== false || stripos($b, '签到一下') !== false ||
                           stripos($b, 'name="signsubmit"') !== false);
                $signed = (stripos($b, '已签到') !== false || stripos($b, '签过到') !== false ||
                           stripos($b, '今日已签') !== false || stripos($b, '已连续签到') !== false ||
                           stripos($b, '签到成功') !== false || stripos($b, '恭喜') !== false ||
                           stripos($b, '已领取') !== false);
                if ($signed && !$hasBtn) return 'signed';
                if ($signed && $hasBtn) return 'signed';
                if ($hasBtn) return 'not_signed';
                return 'unknown';
            };

            // 先看当前状态
            $initStatus = $verifySignin();
            if ($initStatus === 'signed') {
                ForumAccount::updateLastSignin($this->accountId);
                return ['ok' => true, 'message' => '今日已签到'];
            }

            $pageResult = $this->httpGet($pageUrl);
            if (!$pageResult['ok']) {
                return ['ok' => false, 'error' => '无法访问签到页面（' . ($pageResult['error'] ?? 'HTTP错误') . '）'];
            }
            $body = $pageResult['body'];
            $pageFormhash = $this->extractFormhash($body);

            // 尝试所有能找到的签到链接
            $signLinks = [];
            $debugInfo = [];

            // 从 href 提取
            if (preg_match_all('/href="([^"]*(?:signin|qiandao|checkin|achievement|paulsign)[^"]*)"/si', $body, $lm)) {
                foreach ($lm[1] as $l) {
                    $l = html_entity_decode($l);
                    if (strpos($l, 'search') !== false || strpos($l, 'javascript') !== false) continue;
                    if (preg_match('/\.(css|js|png|jpg|gif|ico|svg|woff|ttf)(\?|$)/i', $l)) continue;
                    if (strpos($l, 'source/plugin/') !== false) continue;
                    if (strpos($l, 'static/') !== false) continue;
                    $signLinks[] = $l;
                    $debugInfo[] = 'href:' . $l;
                }
            }
            // 从 JS 提取
            if (preg_match_all('/(?:location\.href|window\.location|ajaxget|\.post\(|\.get\(|url\s*[:=])\s*[\'"]*([^\'"()\s]+(?:sign|qiandao|checkin|achievement|paulsign)[^\'"()\s]*)/si', $body, $jm)) {
                foreach ($jm[1] as $l) {
                    $l = html_entity_decode($l);
                    if (strpos($l, 'search') !== false) continue;
                    $signLinks[] = $l;
                    $debugInfo[] = 'js:' . $l;
                }
            }
            // 从 onclick 提取
            if (preg_match_all('/onclick="([^"]*(?:sign|qiandao|checkin)[^"]*)"/si', $body, $om)) {
                foreach ($om[1] as $l) {
                    if (preg_match('/(?:href|location|url)\s*[=:]\s*[\'"]*([^\'"\s]+)/i', $l, $um)) {
                        $signLinks[] = html_entity_decode($um[1]);
                        $debugInfo[] = 'onclick:' . $um[1];
                    }
                }
            }
            // 从整个页面提取所有 plugin.php 链接（最激进）
            if (preg_match_all('/["\'](plugin\.php\?[^"\']*?(?:sign|qiandao|checkin|achievement|paulsign)[^"\']*)["\']/si', $body, $pm)) {
                foreach ($pm[1] as $l) {
                    $l = html_entity_decode($l);
                    if (!in_array($l, $signLinks)) {
                        $signLinks[] = $l;
                        $debugInfo[] = 'plugin:' . $l;
                    }
                }
            }

            // 记录调试信息
            error_log('[forum_signin] accountId=' . $this->accountId . ' pageUrl=' . $pageUrl);
            error_log('[forum_signin] formhash=' . ($pageFormhash ?: 'NOT_FOUND'));
            error_log('[forum_signin] signLinks=' . implode(' | ', $signLinks ?: ['NONE']));
            error_log('[forum_signin] bodyLen=' . strlen($body) . ' has立即签到=' . (stripos($body, '立即签到') !== false ? 'Y' : 'N') . ' has已签到=' . (stripos($body, '已签到') !== false ? 'Y' : 'N'));

            // 去重
            $signLinks = array_values(array_unique($signLinks));

            // 尝试所有能找到的签到表单
            $signForms = [];
            if ($pageFormhash && preg_match_all('/<form[^>]*action="([^"]*)"[^>]*>(.*?)<\/form>/si', $body, $forms, PREG_SET_ORDER)) {
                foreach ($forms as $fm) {
                    $act = strtolower(html_entity_decode($fm[1]));
                    if (strpos($act, 'search') !== false) continue;
                    $isSign = (strpos($act, 'plugin.php') !== false || strpos($act, 'signin') !== false ||
                               strpos($act, 'paulsign') !== false || strpos($act, 'achievement') !== false ||
                               strpos($act, 'qiandao') !== false || strpos($act, 'checkin') !== false ||
                               stripos($fm[2], '签到') !== false || stripos($fm[2], 'qiandao') !== false);
                    if ($isSign) $signForms[] = $fm;
                }
            }

            // 依次尝试链接
            foreach ($signLinks as $link) {
                if (strpos($link, 'http') !== 0) $link = '/' . ltrim($link, '/');
                $this->httpGet($link);
                $status = $verifySignin();
                if ($status === 'signed') {
                    ForumAccount::updateLastSignin($this->accountId);
                    return ['ok' => true, 'message' => '签到成功'];
                }
            }

            // 依次尝试表单
            foreach ($signForms as $fm) {
                $actionUrl = html_entity_decode($fm[1]);
                if (strpos($actionUrl, 'http') !== 0) $actionUrl = '/' . ltrim($actionUrl, '/');
                $postData = ['formhash' => $pageFormhash];
                if (preg_match_all('/<input[^>]*type="hidden"[^>]*name="([^"]*)"[^>]*value="([^"]*)"/si', $fm[2], $h)) {
                    foreach ($h[1] as $i => $n) { if ($n !== 'formhash') $postData[$n] = $h[2][$i]; }
                }
                $this->httpPost($actionUrl, http_build_query($postData));
                $status = $verifySignin();
                if ($status === 'signed') {
                    ForumAccount::updateLastSignin($this->accountId);
                    return ['ok' => true, 'message' => '签到成功'];
                }
            }

            // 尝试通用插件端点
            if ($pageFormhash) {
                $endpoints = [
                    '/plugin.php?id=dsu_paulsign:sign&operation=qiandao&infloat=1&inajax=1',
                    '/plugin.php?id=nm_achievement_master&op=signin',
                ];
                foreach ($endpoints as $ep) {
                    $isNm = (stripos($ep, 'nm_achievement_master') !== false);
                    $postData = $isNm
                        ? http_build_query(['formhash' => $pageFormhash])
                        : http_build_query(['formhash' => $pageFormhash, 'qdxq' => 'kx', 'qdmode' => 3, 'todaysay' => '']);

                    // 先用 AJAX 方式尝试（模拟浏览器 XMLHttpRequest）
                    $resp = $this->httpPostAjax($ep, $postData);
                    error_log('[forum_signin] AJAX POST ' . $ep . ' code=' . ($resp['code'] ?? 'N/A') . ' body=' . substr($resp['body'] ?? '', 0, 300));
                    if ($resp['ok'] && self::isSigninSuccess($resp['body'] ?? '')) {
                        ForumAccount::updateLastSignin($this->accountId);
                        return ['ok' => true, 'message' => '签到成功'];
                    }

                    // 再用普通 POST 尝试
                    $resp = $this->httpPost($ep, $postData);
                    error_log('[forum_signin] POST ' . $ep . ' code=' . ($resp['code'] ?? 'N/A') . ' body=' . substr($resp['body'] ?? '', 0, 300));
                    if ($resp['ok'] && self::isSigninSuccess($resp['body'] ?? '')) {
                        ForumAccount::updateLastSignin($this->accountId);
                        return ['ok' => true, 'message' => '签到成功'];
                    }

                    $status = $verifySignin();
                    if ($status === 'signed') {
                        ForumAccount::updateLastSignin($this->accountId);
                        return ['ok' => true, 'message' => '签到成功'];
                    }
                    // nm_achievement_master 也尝试 GET 方式
                    if ($isNm) {
                        $this->httpGet($ep . '&formhash=' . $pageFormhash);
                        $status = $verifySignin();
                        if ($status === 'signed') {
                            ForumAccount::updateLastSignin($this->accountId);
                            return ['ok' => true, 'message' => '签到成功'];
                        }
                    }
                }
            }

            $debugStr = implode(', ', $debugInfo ?: ['无']);
            return ['ok' => false, 'error' => '该论坛签到方式暂不支持自动签到。检测到的签到元素: ' . $debugStr . '。请在浏览器中F12打开网络面板，点击签到按钮后告诉我请求的URL，我可以针对性适配。'];
        }

        // 方式1: 直接提交nm_achievement_master签到
        $nmUrl = '/plugin.php?id=nm_achievement_master&op=signin';
        // AJAX 方式
        $result = $this->httpPostAjax($nmUrl, http_build_query(['formhash' => $formhash]));
        error_log('[forum_signin] 方式1 AJAX POST nm code=' . ($result['code'] ?? 'N/A') . ' body=' . substr($result['body'] ?? '', 0, 300));
        if ($result['ok'] && self::isSigninSuccess($result['body'] ?? '')) {
            ForumAccount::updateLastSignin($this->accountId);
            return ['ok' => true, 'message' => '签到成功'];
        }
        // 普通 POST 方式
        $result = $this->httpPost($nmUrl, http_build_query(['formhash' => $formhash]));
        error_log('[forum_signin] 方式1 POST nm code=' . ($result['code'] ?? 'N/A') . ' body=' . substr($result['body'] ?? '', 0, 300));
        if ($result['ok'] && self::isSigninSuccess($result['body'] ?? '')) {
            ForumAccount::updateLastSignin($this->accountId);
            return ['ok' => true, 'message' => '签到成功'];
        }
        // GET 方式
        $nmGetResult = $this->httpGet($nmUrl . '&formhash=' . $formhash);
        if ($nmGetResult['ok'] && self::isSigninSuccess($nmGetResult['body'] ?? '')) {
            ForumAccount::updateLastSignin($this->accountId);
            return ['ok' => true, 'message' => '签到成功'];
        }

        // 方式2: 尝试dsu_paulsign签到
        $dsuResult = $this->httpPost('/plugin.php?id=dsu_paulsign:sign&operation=qiandao&infloat=1&inajax=1', http_build_query([
            'formhash' => $formhash, 'qdxq' => 'kx', 'qdmode' => 3, 'todaysay' => '每日签到', 'faession' => 1,
        ]));
        if ($dsuResult['ok']) {
            $body = $dsuResult['body'];
            if (stripos($body, '签到成功') !== false || stripos($body, 'succeed') !== false || stripos($body, '恭喜') !== false) {
                ForumAccount::updateLastSignin($this->accountId);
                return ['ok' => true, 'message' => '签到成功'];
            }
            if (stripos($body, '已经签到') !== false || stripos($body, '已签到') !== false) {
                ForumAccount::updateLastSignin($this->accountId);
                return ['ok' => true, 'message' => '今日已签到'];
            }
        }

        // 方式3: 访问签到页面提取表单提交
        $pageUrls = [
            '/plugin.php?id=nm_achievement_master&op=signin_page',
            '/plugin.php?id=dsu_paulsign:sign',
            '/plugin.php?id=sign',
            '/plugin.php?id=fx_checkin:checkin',
        ];

        // 从首页提取签到链接
        $homeResult = $this->httpGet('/');
        if ($homeResult['ok']) {
            if (preg_match_all('/href="([^"]*)"[^>]*>[^<]*签到/si', $homeResult['body'], $lms)) {
                foreach (array_reverse($lms[1]) as $url) {
                    $url = html_entity_decode($url);
                    if (strpos($url, 'javascript') === false && strpos($url, '#') === false) {
                        if (strpos($url, 'http') !== 0) $url = '/' . ltrim($url, '/');
                        array_unshift($pageUrls, $url);
                    }
                }
            }
            if (preg_match_all('/href="([^"]*(?:sign|qiandao|checkin|achievement)[^"]*)"/si', $homeResult['body'], $lms)) {
                foreach (array_reverse($lms[1]) as $url) {
                    $url = html_entity_decode($url);
                    if (strpos($url, 'http') !== 0) $url = '/' . ltrim($url, '/');
                    array_unshift($pageUrls, $url);
                }
            }
        }

        $pageUrls = array_values(array_unique($pageUrls));

        foreach ($pageUrls as $pageUrl) {
            $page = $this->httpGet($pageUrl);
            if (!$page['ok']) continue;
            
            $body = $page['body'];
            if (stripos($body, '插件不存在') !== false || stripos($body, '插件已关闭') !== false) continue;
            
            // 已签到检查
            $signedKw = ['已经签到', '已签到', '签过到了', '今日已签', '已领取'];
            foreach ($signedKw as $kw) {
                if (stripos($body, $kw) !== false) {
                    ForumAccount::updateLastSignin($this->accountId);
                    return ['ok' => true, 'message' => '今日已签到'];
                }
            }

            $pageFormhash = $this->extractFormhash($body);
            if (!$pageFormhash) continue;

            // 提取form action
            $actionUrl = '';
            if (preg_match('/<form[^>]*action="([^"]*)"[^>]*>/si', $body, $fm)) {
                $actionUrl = html_entity_decode($fm[1]);
                if (strpos($actionUrl, 'http') !== 0) $actionUrl = '/' . ltrim($actionUrl, '/');
            }
            if (empty($actionUrl)) {
                // 根据页面URL推断提交地址
                if (stripos($pageUrl, 'nm_achievement_master') !== false) {
                    $actionUrl = str_replace('signin_page', 'signin', $pageUrl);
                } elseif (stripos($pageUrl, 'dsu_paulsign') !== false) {
                    $actionUrl = $pageUrl . '&operation=qiandao&infloat=1&inajax=1';
                } else {
                    $actionUrl = $pageUrl;
                }
            }

            $isNm = (stripos($pageUrl, 'nm_achievement_master') !== false);
            $postData = $isNm
                ? http_build_query(['formhash' => $pageFormhash])
                : http_build_query([
                    'formhash' => $pageFormhash, 'qdxq' => 'kx', 'qdmode' => 3,
                    'todaysay' => '每日签到', 'faession' => 1,
                ]);

            // AJAX 方式尝试
            if ($isNm) {
                $result = $this->httpPostAjax($actionUrl, $postData);
                error_log('[forum_signin] 方式3 AJAX ' . $actionUrl . ' code=' . ($result['code'] ?? 'N/A') . ' body=' . substr($result['body'] ?? '', 0, 300));
                if ($result['ok'] && self::isSigninSuccess($result['body'] ?? '')) {
                    ForumAccount::updateLastSignin($this->accountId);
                    return ['ok' => true, 'message' => '签到成功'];
                }
            }

            // 普通 POST 方式
            $result = $this->httpPost($actionUrl, $postData);
            if (!$result['ok']) continue;

            $respBody = $result['body'];
            if (self::isSigninSuccess($respBody)) {
                ForumAccount::updateLastSignin($this->accountId);
                return ['ok' => true, 'message' => '签到成功'];
            }
            // nm_achievement_master 也尝试 GET 方式
            if ($isNm) {
                $this->httpGet($actionUrl . '&formhash=' . $pageFormhash);
                $status = null;
                $re2 = $this->httpGet($pageUrl);
                if ($re2['ok']) {
                    $b2 = $re2['body'];
                    if (stripos($b2, '已签到') !== false || stripos($b2, '签过到') !== false ||
                        stripos($b2, '今日已签') !== false || stripos($b2, '已连续签到') !== false ||
                        stripos($b2, '签到成功') !== false || stripos($b2, '已领取') !== false) {
                        $status = 'signed';
                    }
                }
                if ($status === 'signed') {
                    ForumAccount::updateLastSignin($this->accountId);
                    return ['ok' => true, 'message' => '签到成功'];
                }
            }
        }

        return ['ok' => false, 'error' => '签到失败，该论坛签到插件可能不兼容'];
    }

    /**
     * 获取论坛未读通知数
     */
    public function getNotices(): array
    {
        $unread = 0;
        $body = '';
        
        // 从首页提取未读通知数（最快最可靠）
        $result = $this->httpGet('/');
        if ($result['ok']) {
            $body = $result['body'];
            
            // DISCUZ标准：prompt_n变量
            if (preg_match('/prompt_n\s*=\s*(\d+)/s', $body, $m)) {
                $unread = (int)$m[1];
            }
            // newprompt变量
            elseif (preg_match('/newprompt\s*=\s*[\'"](\d+)/s', $body, $m)) {
                $unread = (int)$m[1];
            }
            // 通知链接中的数字
            elseif (preg_match('/id="myprompt"[^>]*>.*?\((\d+)\)/s', $body, $m)) {
                $unread = (int)$m[1];
            }
            // pntalt class
            elseif (preg_match('/pntalt[^>]*>.*?(\d+)/s', $body, $m)) {
                $unread = (int)$m[1];
            }
        }

        ForumAccount::updateLastNoticeCheck($this->accountId);

        return [
            'ok' => true,
            'unread' => $unread,
            'message' => $unread > 0 ? "有 {$unread} 条未读通知" : '暂无新通知',
            'forum_url' => $this->forumUrl,
        ];
    }

    /**
     * 从页面HTML中解析通知内容
     */
    private function parseNoticesFromBody(string $body): array
    {
        $notices = [];
        
        // 方式1: 匹配 class 包含 bbda 的 li
        if (preg_match_all('/<li[^>]*class="[^"]*bbda[^"]*"[^>]*>(.*?)<\/li>/si', $body, $matches)) {
            foreach ($matches[1] as $item) {
                $notice = $this->parseNoticeItem($item);
                if (!empty($notice['content'])) {
                    $notices[] = $notice;
                }
            }
        }
        
        // 方式2: 匹配通知列表区域
        if (empty($notices)) {
            if (preg_match('/<ul[^>]*class="[^"]*notif[^"]*"[^>]*>(.*?)<\/ul>/si', $body, $nm)) {
                if (preg_match_all('/<li[^>]*>(.*?)<\/li>/si', $nm[1], $matches)) {
                    foreach ($matches[1] as $item) {
                        $notice = $this->parseNoticeItem($item);
                        if (!empty($notice['content'])) {
                            $notices[] = $notice;
                        }
                    }
                }
            }
        }
        
        // 方式3: 通用匹配 - 查找包含时间特征的内容
        if (empty($notices)) {
            $cleanBody = preg_replace('/<script[^>]*>.*?<\/script>/si', '', $body);
            $cleanBody = preg_replace('/<style[^>]*>.*?<\/style>/si', '', $cleanBody);
            
            // 匹配包含【】和时间的内容
            if (preg_match_all('/<(?:li|div|p|tr)[^>]*>(.*?【.*?】.*?)<\/(?:li|div|p|tr)>/si', $cleanBody, $matches)) {
                foreach ($matches[1] as $item) {
                    $notice = $this->parseNoticeItem($item);
                    if (!empty($notice['content']) && strlen($notice['content']) > 10) {
                        $notices[] = $notice;
                    }
                }
            }
        }
        
        // 方式4: 匹配包含经验值/金币/任务等关键词的内容
        if (empty($notices)) {
            $cleanBody = preg_replace('/<script[^>]*>.*?<\/script>/si', '', $body);
            $cleanBody = preg_replace('/<style[^>]*>.*?<\/style>/si', '', $cleanBody);
            
            if (preg_match_all('/<(?:li|div|p)[^>]*>(.*?(?:经验值|金币|任务|成就|奖励|签到).*?)<\/(?:li|div|p)>/si', $cleanBody, $matches)) {
                foreach ($matches[1] as $item) {
                    $notice = $this->parseNoticeItem($item);
                    if (!empty($notice['content']) && strlen($notice['content']) > 10) {
                        $notices[] = $notice;
                    }
                }
            }
        }
        
        // 去重
        $seen = [];
        $unique = [];
        foreach ($notices as $n) {
            $key = substr($n['content'], 0, 50);
            if (!isset($seen[$key])) {
                $seen[$key] = true;
                $unique[] = $n;
            }
        }
        
        return $unique;
    }

    /**
     * 解析单条通知内容
     */
    private function parseNoticeItem(string $html): array
    {
        $notice = ['content' => '', 'time' => '', 'url' => ''];
        
        // 移除屏蔽按钮等无关元素
        $html = preg_replace('/<a[^>]*class="[^"]*block[^"]*"[^>]*>.*?<\/a>/si', '', $html);
        
        // 提取纯文本内容
        $content = trim(strip_tags($html));
        // 清理多余空白
        $content = preg_replace('/\s+/', ' ', $content);
        
        if (strlen($content) > 3) {
            $notice['content'] = $content;
        }
        
        // 提取时间
        if (preg_match('/(\d+\s*(?:秒|分钟|小时|天)前|刚刚)/', $html, $tm)) {
            $notice['time'] = $tm[1];
        } elseif (preg_match('/(\d{4}-\d{2}-\d{2}\s*\d{2}:\d{2})/s', $html, $tm)) {
            $notice['time'] = $tm[1];
        }
        
        // 提取链接
        if (preg_match('/href="([^"]*)"[^>]*>[^<]*(?:查看|详情|去领取|前往|点击)/s', $html, $lm)) {
            $notice['url'] = $lm[1];
        } elseif (preg_match('/href="([^"]*)"[^>]*>/', $html, $lm)) {
            $url = $lm[1];
            if (strpos($url, 'javascript:') === false && $url !== '#') {
                $notice['url'] = $url;
            }
        }
        
        return $notice;
    }

    /**
     * 获取通知详情页面，提取 @提及/帖子引用 类型的通知
     * 返回包含帖子链接的通知列表
     */
    public function getMentionNotices(): array
    {
        $mentions = [];

        // 访问通知页面
        $result = $this->httpGet('/home.php?mod=space&do=notice');
        if (!$result['ok']) return $mentions;

        $body = $result['body'];

        // 提取所有通知项
        $items = [];

        // 方式1: Discuz 通知列表 li.bbda
        if (preg_match_all('/<li[^>]*class="[^"]*bbda[^"]*"[^>]*>(.*?)<\/li>/si', $body, $matches)) {
            $items = $matches[1];
        }

        // 方式2: 通知区域 ul
        if (empty($items) && preg_match('/<ul[^>]*class="[^"]*notif[^"]*"[^>]*>(.*?)<\/ul>/si', $body, $nm)) {
            if (preg_match_all('/<li[^>]*>(.*?)<\/li>/si', $nm[1], $matches)) {
                $items = $matches[1];
            }
        }

        foreach ($items as $html) {
            $text = strip_tags($html);
            $text = preg_replace('/\s+/', ' ', trim($text));

            // 筛选 @提及/帖子引用/回复 通知
            $isMention = (stripos($text, '@') !== false && stripos($text, '回复') !== false)
                || stripos($text, '引用了你的帖子') !== false
                || stripos($text, '回复了你的帖子') !== false
                || stripos($text, '在帖子中提到了你') !== false
                || stripos($text, '在回复中提到了你') !== false
                || stripos($text, '有人@') !== false;

            if (!$isMention) continue;

            // 提取帖子链接（tid）
            $tid = 0;
            if (preg_match('/(?:thread|t)-(\d+)-/si', $html, $lm)) {
                $tid = (int)$lm[1];
            } elseif (preg_match('/tid=(\d+)/si', $html, $lm)) {
                $tid = (int)$lm[1];
            } elseif (preg_match('/mod=viewthread&(?:amp;)?tid=(\d+)/si', $html, $lm)) {
                $tid = (int)$lm[1];
            }

            if ($tid <= 0) continue;

            // 提取时间
            $time = '';
            if (preg_match('/(\d+\s*(?:秒|分钟|小时|天)前|刚刚)/', $html, $tm)) {
                $time = $tm[1];
            } elseif (preg_match('/(\d{4}-\d{2}-\d{2}\s*\d{2}:\d{2})/s', $html, $tm)) {
                $time = $tm[1];
            }

            $mentions[] = [
                'tid' => $tid,
                'content' => mb_substr($text, 0, 100),
                'time' => $time,
            ];
        }

        // 去重（按tid）
        $seen = [];
        $unique = [];
        foreach ($mentions as $m) {
            if (!isset($seen[$m['tid']])) {
                $seen[$m['tid']] = true;
                $unique[] = $m;
            }
        }

        return $unique;
    }

    /**
     * 处理 @提及自动回复
     * 获取通知中的 @提及，对每个帖子生成 AI 回复并提交
     */
    public function handleMentionReplies(string $replyMode = 'ai', ?string $customReply = null): array
    {
        $results = [];

        // 获取已回复的帖子（去重用）
        $repliedTids = $this->getRepliedTids();

        // 获取 @提及通知
        $mentions = $this->getMentionNotices();
        if (empty($mentions)) {
            return ['ok' => true, 'message' => '暂无新的@提及通知', 'replied' => 0];
        }

        $replied = 0;
        foreach ($mentions as $mention) {
            $tid = $mention['tid'];

            // 跳过已回复的帖子
            if (in_array($tid, $repliedTids)) continue;

            // 获取帖子内容
            $thread = $this->getThreadContent($tid);
            if (!$thread['ok']) continue;

            // 生成回复内容
            $message = '';
            if ($replyMode === 'ai') {
                $message = self::generateAiReply($thread['title'], $thread['content']);
                // AI失败时重试一次，使用更侧重内容分析的prompt
                if (empty($message)) {
                    $message = self::generateAiReply($thread['title'], $thread['content'], true);
                }
            }
            if (empty($message)) {
                $message = self::getReplyContent($customReply ? 'custom' : 'random', $customReply);
            }

            // 提交回复
            $replyResult = $this->reply($tid, $message);
            $results[] = [
                'tid' => $tid,
                'title' => $thread['title'],
                'ok' => $replyResult['ok'],
                'message' => $replyResult['message'] ?? $replyResult['error'] ?? '未知',
            ];

            if ($replyResult['ok']) {
                $replied++;
                // 记录已回复
                $this->recordReplied($tid);
            }

            // 回复间隔，避免频率限制
            usleep(2000000); // 2秒
        }

        return [
            'ok' => true,
            'message' => "处理 {$replied} 条@提及回复",
            'replied' => $replied,
            'details' => $results,
        ];
    }

    /**
     * 获取已回复帖子TID列表（从日志中提取）
     */
    private function getRepliedTids(): array
    {
        try {
            $pdo = \App\Service\Database::getConnection();
            $stmt = $pdo->prepare(
                "SELECT DISTINCT CAST(SUBSTRING_INDEX(SUBSTRING_INDEX(detail, '#', -1), ' ', 1) AS UNSIGNED) AS tid
                 FROM forum_action_logs
                 WHERE account_id = ? AND type = 'reply' AND detail LIKE '%帖子#%'
                 ORDER BY created_at DESC LIMIT 200"
            );
            $stmt->execute([$this->accountId]);
            return array_filter(array_column($stmt->fetchAll(\PDO::FETCH_ASSOC), 'tid'));
        } catch (\Throwable $e) {
            return [];
        }
    }

    /**
     * 记录已回复帖子
     */
    private function recordReplied(int $tid): void
    {
        try {
            \App\Model\ForumActionLog::create(
                $this->userId,
                $this->accountId,
                'reply',
                '@提及自动回复',
                "帖子#{$tid}"
            );
        } catch (\Throwable $e) {}
    }

    /**
     * 解析帖子列表 - 通用方法
     */
    private function parseThreadLinks(string $html): array
    {
        $threads = [];
        
        // 匹配多种URL格式: thread-{tid}-xxx.html, t-{tid}-xxx.html, viewthread.php?tid={tid}
        $patterns = [
            '/<a[^>]*href="[^"]*(?:thread|t)-(\d+)-[^"]*\.html"[^>]*>(.*?)<\/a>/si',
            '/<a[^>]*href="[^"]*viewthread\.php\?tid=(\d+)[^"]*"[^>]*>(.*?)<\/a>/si',
            '/<a[^>]*href="[^"]*mod=viewthread&(?:amp;)?tid=(\d+)[^"]*"[^>]*>(.*?)<\/a>/si',
        ];
        
        foreach ($patterns as $pattern) {
            if (preg_match_all($pattern, $html, $matches)) {
                foreach ($matches[1] as $i => $tid) {
                    $title = strip_tags($matches[2][$i]);
                    $title = preg_replace('/\s+/', ' ', $title);
                    if (strlen($title) > 2 && !isset($threads[(int)$tid])) {
                        $threads[(int)$tid] = ['tid' => (int)$tid, 'title' => $title];
                    }
                }
            }
        }
        
        return $threads;
    }

    /**
     * 获取帖子列表（用于回帖）
     */
    public function getThreadList(int $fid = 0, bool $filterReplied = true): array
    {
        $threads = [];

        // 主要来源：新帖页面（覆盖面最广）
        $url = $fid > 0 ? "/forum.php?fid=$fid" : '/forum.php?mod=guide&view=new';
        $result = $this->httpGet($url);
        if ($result['ok']) {
            $parsed = $this->parseThreadLinks($result['body']);
            foreach ($parsed as $tid => $t) {
                $threads[$tid] = $t + ['source' => 'forum'];
            }
        }

        // 自己发的帖子
        $myResult = $this->httpGet('/home.php?mod=space&do=thread&view=me');
        if ($myResult['ok']) {
            $parsed = $this->parseThreadLinks($myResult['body']);
            foreach ($parsed as $tid => $t) {
                if (!isset($threads[$tid])) {
                    $threads[$tid] = $t + ['source' => 'my'];
                }
            }
        }
        
        $threads = array_values($threads);
        // 限制数量
        if (count($threads) > 50) {
            $threads = array_slice($threads, 0, 50);
        }

        // 过滤已回复的帖子
        if ($filterReplied && !empty($threads)) {
            $repliedTids = ForumRepliedThread::getRepliedTids($this->accountId);
            $unreplied = array_filter($threads, function($t) use ($repliedTids) {
                return !in_array($t['tid'], $repliedTids);
            });
            $unreplied = array_values($unreplied);
            
            return [
                'ok' => true,
                'threads' => $unreplied,
                'total' => count($threads),
                'replied' => count($threads) - count($unreplied),
                'unreplied' => count($unreplied),
            ];
        }

        return ['ok' => true, 'threads' => $threads];
    }

    /**
     * 获取一个未回复的帖子TID
     */
    public function getUnrepliedThread(int $fid = 0): ?array
    {
        $result = $this->getThreadList($fid, true);
        if (!$result['ok'] || empty($result['threads'])) {
            return null;
        }
        
        // 随机选一个未回复的帖子
        $threads = $result['threads'];
        return $threads[array_rand($threads)];
    }

    /**
     * 回帖
     */
    public function reply(int $tid, string $message): array
    {
        // 获取帖子页面
        $page = $this->httpGet("/forum.php?mod=viewthread&tid=$tid");
        if (!$page['ok']) {
            return ['ok' => false, 'error' => '无法访问帖子'];
        }

        // 检查帖子是否存在
        if (stripos($page['body'], '指定的主题不存在') !== false || 
            stripos($page['body'], '帖子不存在') !== false ||
            $page['code'] === 404) {
            return ['ok' => false, 'error' => '帖子不存在'];
        }

        $formhash = $this->extractFormhash($page['body']);
        if (!$formhash) {
            return ['ok' => false, 'error' => '无法获取formhash，可能未登录'];
        }

        // 从页面form中提取回复地址
        $replyUrl = '';
        if (preg_match('/<form[^>]*action="([^"]*reply[^"]*)"[^>]*>/si', $page['body'], $fm)) {
            $replyUrl = html_entity_decode($fm[1]);
        }
        // 从replysubmit按钮的form中提取
        if (empty($replyUrl) && preg_match('/<form[^>]*id="[^"]*postform[^"]*"[^>]*action="([^"]*)"[^>]*>/si', $page['body'], $fm)) {
            $replyUrl = html_entity_decode($fm[1]);
        }
        // 通用form action
        if (empty($replyUrl) && preg_match('/<form[^>]*action="([^"]*)"[^>]*>/si', $page['body'], $fm)) {
            $url = html_entity_decode($fm[1]);
            if (stripos($url, 'post') !== false || stripos($url, 'reply') !== false) {
                $replyUrl = $url;
            }
        }

        // 提取fid
        $fid = 0;
        if (preg_match('/fid[=:\s]+(\d+)/', $page['body'], $fm)) {
            $fid = (int)$fm[1];
        }
        if ($fid === 0 && preg_match('/forum-(\d+)-/', $page['body'], $fm)) {
            $fid = (int)$fm[1];
        }
        if ($fid === 0 && preg_match('/action=reply&(?:amp;)?fid=(\d+)/', $page['body'], $fm)) {
            $fid = (int)$fm[1];
        }
        // 从URL中提取fid
        if ($fid === 0 && preg_match('/forum\.php\?mod=viewthread&(?:amp;)?fid=(\d+)/', $page['body'], $fm)) {
            $fid = (int)$fm[1];
        }

        // 提取帖子标题
        $title = '';
        if (preg_match('/<title>(.*?)<\/title>/s', $page['body'], $tm)) {
            $title = strip_tags($tm[1]);
            $title = preg_replace('/\s*-.*$/', '', $title);
        }

        // 构建回复URL
        if (!empty($replyUrl)) {
            // 如果是相对路径，加上forum_url
            if (strpos($replyUrl, 'http') !== 0) {
                $replyUrl = '/' . ltrim($replyUrl, '/');
            }
        } elseif ($fid > 0) {
            $replyUrl = "/post.php?action=reply&fid=$fid&tid=$tid&extra=&replysubmit=yes&inajax=1";
        } else {
            $replyUrl = "/post.php?action=reply&tid=$tid&extra=&replysubmit=yes&inajax=1";
        }

        // 提交回复
        $postData = http_build_query([
            'formhash' => $formhash,
            'message' => $message,
            'posttime' => time(),
            'usesig' => 1,
            'noticeauthor' => '',
            'noticetrimstr' => '',
            'subject' => '',
        ]);

        $result = $this->httpPost($replyUrl, $postData);
        if (!$result['ok']) {
            return ['ok' => false, 'error' => '回复请求失败: ' . ($result['error'] ?? '网络错误')];
        }

        // 404检查
        if ($result['code'] === 404) {
            return ['ok' => false, 'error' => '回帖接口404，URL: ' . $replyUrl];
        }

        if (stripos($result['body'], 'succeedhandle') !== false || stripos($result['body'], '回复发布成功') !== false) {
            ForumRepliedThread::markReplied($this->accountId, $tid, $title);
            return ['ok' => true, 'message' => '回帖成功', 'title' => $title];
        }

        if (stripos($result['body'], '您两次发表间隔') !== false) {
            return ['ok' => false, 'error' => '发表间隔太短，请稍后再试'];
        }

        if (stripos($result['body'], '没有权限') !== false || stripos($result['body'], 'perm') !== false) {
            return ['ok' => false, 'error' => '没有回帖权限'];
        }

        if (stripos($result['body'], '关闭') !== false) {
            return ['ok' => false, 'error' => '帖子已关闭，无法回复'];
        }

        // 提取错误信息
        $errMsg = '';
        if (preg_match('/<error>(.*?)<\/error>/s', $result['body'], $em)) {
            $errMsg = strip_tags($em[1]);
        } elseif (preg_match('/class="alert_error"[^>]*>(.*?)<\//s', $result['body'], $em)) {
            $errMsg = strip_tags($em[1]);
        }
        
        $debug = mb_substr(strip_tags($result['body']), 0, 150);
        return ['ok' => false, 'error' => $errMsg ?: ('回帖失败: ' . $debug)];
    }

    /**
     * 获取帖子内容（用于AI智能回复）
     */
    public function getThreadContent(int $tid): array
    {
        $page = $this->httpGet("/forum.php?mod=viewthread&tid=$tid");
        if (!$page['ok']) {
            return ['ok' => false, 'error' => '无法访问帖子'];
        }

        $body = $page['body'];
        $title = '';
        $content = '';

        // 提取标题
        if (preg_match('/<title>(.*?)<\/title>/s', $body, $tm)) {
            $title = strip_tags($tm[1]);
            $title = preg_replace('/\s*-.*$/', '', $title);
        }

        // 提取帖子正文 - 尝试多种选择器
        $contentPatterns = [
            '/<div[^>]*class="[^"]*t_f[^"]*"[^>]*>(.*?)<\/div>/si',
            '/<div[^>]*class="[^"]*message[^"]*"[^>]*>(.*?)<\/div>/si',
            '/<div[^>]*class="[^"]*postmessage[^"]*"[^>]*>(.*?)<\/div>/si',
            '/<td[^>]*class="[^"]*t_f[^"]*"[^>]*>(.*?)<\/td>/si',
        ];
        
        foreach ($contentPatterns as $pattern) {
            if (preg_match($pattern, $body, $cm)) {
                $content = strip_tags($cm[1]);
                $content = preg_replace('/\s+/', ' ', $content);
                $content = trim($content);
                if (strlen($content) > 20) break;
            }
        }

        // 如果上面没提取到，尝试提取第一个较长的帖子内容区域
        if (strlen($content) < 20) {
            if (preg_match_all('/<(?:div|td)[^>]*>(.*?)<\/(?:div|td)>/si', $body, $matches)) {
                foreach ($matches[1] as $block) {
                    $text = strip_tags($block);
                    $text = preg_replace('/\s+/', ' ', trim($text));
                    if (strlen($text) > 50 && strlen($text) < 2000) {
                        $content = $text;
                        break;
                    }
                }
            }
        }

        if (empty($content)) {
            return ['ok' => false, 'error' => '无法提取帖子内容'];
        }

        // 截取前500字
        $content = mb_substr($content, 0, 500);

        return ['ok' => true, 'title' => $title, 'content' => $content];
    }

    /**
     * AI智能生成回帖内容
     */
    public static function generateAiReply(string $title, string $content, bool $retrying = false): ?string
    {
        $apiUrl = Config::get('ai.forum_reply.api_url', '');
        $apiKey = Config::get('ai.forum_reply.api_key', '');
        $model = Config::get('ai.forum_reply.model', 'gpt-3.5-turbo');
        $maxTokens = (int)Config::get('ai.forum_reply.max_tokens', 150);
        $temperature = (float)Config::get('ai.forum_reply.temperature', 0.8);

        if (empty($apiUrl) || empty($apiKey)) {
            return null;
        }

        $systemPrompt = $retrying
            ? '你是资深论坛用户，擅长针对帖子内容发表观点或提问。回复要自然，必须围绕帖子具体内容，禁止说空话套话。'
            : '你是论坛活跃用户，回复必须紧扣帖子内容：表达赞同/反对观点、补充信息、提出疑问或分享经验。严禁使用"感谢分享""学到了""写得好"等空洞模板。';

        $prompt = "请根据以下帖子内容生成一条回复（50-150字），要求：\n";
        $prompt .= "1. 必须针对帖子具体内容，不能泛泛而谈\n";
        $prompt .= "2. 要有实质观点、补充信息、相关经验或提出问题\n";
        $prompt .= "3. 语气自然像真人发帖，不要AI感\n\n";
        $prompt .= "【帖子标题】{$title}\n";
        $prompt .= "【帖子内容】{$content}\n\n";
        $prompt .= "直接输出回复内容，不要加引号、前缀或任何说明文字。";

        $payload = json_encode([
            'model' => $model,
            'messages' => [
                ['role' => 'system', 'content' => $systemPrompt],
                ['role' => 'user', 'content' => $prompt],
            ],
            'max_tokens' => $maxTokens,
            'temperature' => $temperature,
        ], JSON_UNESCAPED_UNICODE);

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $apiUrl,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $payload,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $apiKey,
            ],
        ]);
        $response = curl_exec($ch);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error || !$response) {
            return null;
        }

        $data = json_decode($response, true);
        if (!empty($data['choices'][0]['message']['content'])) {
            $reply = trim($data['choices'][0]['message']['content'], " \t\n\r\0\x0B\"'");
            if (!empty($reply)) {
                return $reply;
            }
        }

        return null;
    }

    /**
     * 获取随机回帖内容
     */
    public static function getReplyContent(string $mode, ?string $customReply = null): string
    {
        if ($mode === 'custom' && !empty($customReply)) {
            $lines = array_filter(array_map('trim', explode("\n", $customReply)));
            if (!empty($lines)) {
                return $lines[array_rand($lines)];
            }
        }

        // 调用外部API获取鸡汤
        $content = self::fetchHitokoto();
        if ($content) {
            return $content;
        }

        // 备用API
        $content = self::fetchDutang();
        if ($content) {
            return $content;
        }

        // 兜底内容
        $fallback = [
            '每一天都是新的开始，加油！',
            '坚持就是胜利，继续努力！',
            '感谢楼主分享，学到了很多',
            '写得很好，收藏了',
            '非常有用的内容，感谢！',
            '学习了，谢谢楼主',
            '这个帖子很有价值',
            '感谢分享，受益匪浅',
            '内容很详细，赞一个',
            '好文章，值得推荐',
        ];
        return $fallback[array_rand($fallback)];
    }

    /**
     * 调用一言API
     */
    private static function fetchHitokoto(): ?string
    {
        $url = 'https://v1.hitokoto.cn/?c=d&c=h&c=i&c=k&encode=json';
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 5,
            CURLOPT_SSL_VERIFYPEER => false,
        ]);
        $response = curl_exec($ch);
        curl_close($ch);

        if ($response) {
            $data = json_decode($response, true);
            if (!empty($data['hitokoto'])) {
                $content = $data['hitokoto'];
                if (!empty($data['from'])) {
                    $content .= " —— {$data['from']}";
                }
                return $content;
            }
        }
        return null;
    }

    /**
     * 调用金山鸡汤API
     */
    private static function fetchDutang(): ?string
    {
        $url = 'https://api.oick.cn/dutang/api.php';
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 5,
            CURLOPT_SSL_VERIFYPEER => false,
        ]);
        $response = curl_exec($ch);
        curl_close($ch);

        if ($response && strlen($response) > 5) {
            return trim($response, '"\'');
        }
        return null;
    }

    /**
     * 提取formhash
     */
    private function extractFormhash(string $html): ?string
    {
        if (preg_match('/formhash=([a-f0-9]+)/', $html, $m)) {
            return $m[1];
        }
        if (preg_match('/name="formhash"\s+value="([a-f0-9]+)"/', $html, $m)) {
            return $m[1];
        }
        return null;
    }

    /**
     * 判断签到响应是否为成功
     * 兼容 HTML 页面、JSON 响应、纯文本
     */
    private static function isSigninSuccess(string $body): bool
    {
        $body = trim($body);
        if ($body === '') return false;

        // JSON 响应
        $json = json_decode($body, true);
        if (is_array($json)) {
            $state = strtolower((string)($json['state'] ?? $json['result'] ?? $json['code'] ?? $json['status'] ?? ''));
            $msg = (string)($json['msg'] ?? $json['message'] ?? $json['error'] ?? '');
            if ($state === 'success' || $state === '1' || $state === '0' || $state === 'ok') return true;
            if (stripos($msg, '签到成功') !== false || stripos($msg, '恭喜') !== false ||
                stripos($msg, '获得') !== false || stripos($msg, '已签到') !== false ||
                stripos($msg, '已经签到') !== false || stripos($msg, '已领取') !== false) return true;
        }

        // HTML / 纯文本
        return (stripos($body, '签到成功') !== false || stripos($body, '恭喜') !== false ||
                stripos($body, '获得') !== false || stripos($body, 'succeed') !== false ||
                stripos($body, '已签到') !== false || stripos($body, '已经签到') !== false ||
                stripos($body, '已领取') !== false || stripos($body, '签过到') !== false);
    }

    /**
     * HTTP GET请求
     */
    private function httpGet(string $path): array
    {
        $url = (stripos($path, 'http') === 0) ? $path : $this->forumUrl . $path;
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => $this->timeout,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_COOKIEJAR => $this->cookieFile,
            CURLOPT_COOKIEFILE => $this->cookieFile,
            CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
            CURLOPT_HTTPHEADER => [
                'Referer: ' . $this->forumUrl,
                'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
            ],
        ]);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            return ['ok' => false, 'error' => $error];
        }
        return ['ok' => true, 'body' => $response, 'code' => $httpCode];
    }

    /**
     * HTTP POST请求
     */
    private function httpPost(string $path, string $postData): array
    {
        $url = (stripos($path, 'http') === 0) ? $path : $this->forumUrl . $path;
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $postData,
            CURLOPT_TIMEOUT => $this->timeout,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_COOKIEJAR => $this->cookieFile,
            CURLOPT_COOKIEFILE => $this->cookieFile,
            CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/x-www-form-urlencoded',
                'Referer: ' . $this->forumUrl,
                'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
            ],
        ]);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            return ['ok' => false, 'error' => $error];
        }
        return ['ok' => true, 'body' => $response, 'code' => $httpCode];
    }

    /**
     * HTTP POST请求（AJAX模式，模拟浏览器XMLHttpRequest）
     */
    private function httpPostAjax(string $path, string $postData): array
    {
        $url = (stripos($path, 'http') === 0) ? $path : $this->forumUrl . $path;
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $postData,
            CURLOPT_TIMEOUT => $this->timeout,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_COOKIEJAR => $this->cookieFile,
            CURLOPT_COOKIEFILE => $this->cookieFile,
            CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/x-www-form-urlencoded',
                'X-Requested-With: XMLHttpRequest',
                'Referer: ' . $this->forumUrl,
                'Accept: application/json, text/javascript, */*; q=0.01',
            ],
        ]);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            return ['ok' => false, 'error' => $error];
        }
        return ['ok' => true, 'body' => $response, 'code' => $httpCode];
    }

    /**
     * 写入Cookie文件
     */
    private function writeCookie(string $cookieData): void
    {
        file_put_contents($this->cookieFile, $cookieData);
    }

    /**
     * 清理临时Cookie文件
     */
    public function __destruct()
    {
        if (file_exists($this->cookieFile)) {
            @unlink($this->cookieFile);
        }
    }
}
