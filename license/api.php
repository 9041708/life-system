<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') exit;

require_once __DIR__ . '/LicenseManager.php';
$mgr = \License\LicenseManager::getInstance();

function json($d) { echo json_encode($d, JSON_UNESCAPED_UNICODE); exit; }

$action = $_POST['action'] ?? $_GET['action'] ?? '';
switch ($action) {
    case 'activate':
        $r = $mgr->activate(trim($_POST['key'] ?? ''), trim($_POST['domain'] ?? ''));
        json($r);
    case 'heartbeat':
        $r = $mgr->heartbeat(trim($_POST['key'] ?? ''));
        json($r);
    default:
        json(['ok' => false, 'error' => '未知操作']);
}
