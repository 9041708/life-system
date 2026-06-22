<?php
namespace License;

use PDO;

class LicenseManager
{
    private PDO $db;
    private static ?LicenseManager $instance = null;

    public function __construct()
    {
        $config = include __DIR__ . '/../config/config.php';
        $db = $config['db'] ?? [];
        $dsn = "mysql:host=" . ($db['host'] ?? 'localhost') . ";port=" . ($db['port'] ?? 3306) . ";dbname=" . ($db['dbname'] ?? '') . ";charset=utf8mb4";
        $this->db = new PDO($dsn, $db['user'] ?? '', $db['pass'] ?? '', [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]);
    }

    public static function getInstance(): self
    {
        if (self::$instance === null) self::$instance = new self();
        return self::$instance;
    }

    public function getDb(): PDO { return $this->db; }

    public function getConfig(string $key): string {
        $stmt = $this->db->prepare('SELECT config_value FROM license_config WHERE config_key = :k');
        $stmt->execute([':k' => $key]);
        return $stmt->fetchColumn() ?: '';
    }

    public function saveConfig(string $key, string $val): void {
        $this->db->prepare('INSERT INTO license_config (config_key, config_value) VALUES (:k, :v) ON DUPLICATE KEY UPDATE config_value = :v')->execute([':k' => $key, ':v' => $val]);
    }

    public function deleteLicense(string $key): void
    {
        $this->db->prepare('DELETE FROM licenses WHERE license_key = :k')->execute([':k' => $key]);
    }

    public function listApplications(): array {
        return $this->db->query('SELECT * FROM license_applications ORDER BY created_at DESC')->fetchAll();
    }

    public function deleteApplication(int $id): void {
        $this->db->prepare('DELETE FROM license_applications WHERE id = :i')->execute([':i' => $id]);
    }

    // === 营销活动 ===
    public function listCampaigns(): array {
        return $this->db->query('SELECT * FROM license_campaigns ORDER BY created_at DESC')->fetchAll();
    }

    public function addCampaign(string $name, float $price, string $start, string $end): void {
        $this->db->prepare('INSERT INTO license_campaigns (name, campaign_price, start_date, end_date) VALUES (:n, :p, :s, :e)')->execute([':n' => $name, ':p' => $price, ':s' => $start, ':e' => $end]);
    }

    public function deleteCampaign(int $id): void {
        $this->db->prepare('DELETE FROM license_campaigns WHERE id = :i')->execute([':i' => $id]);
    }

    public function getActiveCampaign(): ?array {
        $stmt = $this->db->prepare('SELECT * FROM license_campaigns WHERE is_active = 1 AND start_date <= CURDATE() AND end_date >= CURDATE() ORDER BY created_at DESC LIMIT 1');
        $stmt->execute();
        return $stmt->fetch() ?: null;
    }

    // === 激活/心跳 ===
    public function activate(string $key, string $domain): array
    {
        $stmt = $this->db->prepare('SELECT * FROM licenses WHERE license_key = :k AND is_active = 1');
        $stmt->execute([':k' => $key]);
        $row = $stmt->fetch();
        if (!$row) return ['ok' => false, 'error' => '授权码无效或已禁用'];
        if ($row['expire_date'] < date('Y-m-d')) return ['ok' => false, 'error' => '授权码已过期'];
        if ($row['domain'] !== '' && $row['domain'] !== $domain) return ['ok' => false, 'error' => '域名不匹配，授权绑定域名为 ' . $row['domain']];
        $this->db->prepare('UPDATE licenses SET last_checkin = NOW() WHERE license_key = :k')->execute([':k' => $key]);
        return ['ok' => true, 'expire_date' => $row['expire_date'], 'message' => '激活成功'];
    }

    public function heartbeat(string $key): array
    {
        $stmt = $this->db->prepare('SELECT * FROM licenses WHERE license_key = :k AND is_active = 1');
        $stmt->execute([':k' => $key]);
        $row = $stmt->fetch();
        if (!$row) return ['ok' => false, 'error' => '无效'];
        if ($row['expire_date'] < date('Y-m-d')) return ['ok' => false, 'error' => '已过期', 'expired' => true];
        $this->db->prepare('UPDATE licenses SET last_checkin = NOW() WHERE license_key = :k')->execute([':k' => $key]);
        return ['ok' => true, 'expire_date' => $row['expire_date']];
    }

    // === 管理员操作 ===
    public function addLicense(string $email, string $domain, string $expireDate): array
    {
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) return ['ok' => false, 'error' => '邮箱格式不正确'];
        $key = 'SSJ-' . strtoupper(substr(bin2hex(random_bytes(4)), 0, 8));
        $this->db->prepare('INSERT INTO licenses (license_key, email, domain, expire_date) VALUES (:k, :e, :d, :ed)')->execute([':k' => $key, ':e' => $email, ':d' => $domain, ':ed' => $expireDate]);
        return ['ok' => true, 'key' => $key, 'message' => '已生成授权码'];
    }

    public function listLicenses(): array
    {
        return $this->db->query('SELECT * FROM licenses ORDER BY created_at DESC')->fetchAll();
    }

    public function toggleActive(string $key): void
    {
        $s = $this->db->prepare('SELECT is_active FROM licenses WHERE license_key = :k');
        $s->execute([':k' => $key]);
        $cur = (int)($s->fetchColumn() ?: 0);
        $this->db->prepare('UPDATE licenses SET is_active = :a WHERE license_key = :k')->execute([':a' => $cur ? 0 : 1, ':k' => $key]);
    }
}
