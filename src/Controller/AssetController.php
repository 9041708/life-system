<?php
namespace App\Controller;

use App\Service\Config;
use App\Model\Asset;
use App\Model\IconLibrary;
use App\Model\SystemIconLibrary;
use App\Model\SystemIconSubmission;
use App\Service\Upload;

class AssetController
{
    private function requireLogin(): int
    {
        $uid = (int)($_SESSION['user_id'] ?? 0);
        if ($uid <= 0) {
            header('Location: /public/index.php?route=login');
            exit;
        }
        return $uid;
    }

    private function render(string $view, array $params = []): void
    {
        extract($params);
        $appName = Config::get('app.name');
        include __DIR__ . '/../../templates/layout_main.php';
    }

    public function index(): void
    {
        $userId = $this->requireLogin();
        $error = '';
        $success = '';

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $action = $_POST['action'] ?? '';

            if ($action === 'create' || $action === 'update') {
                $id = (int)($_POST['id'] ?? 0);
                $name = trim($_POST['name'] ?? '');
                $acquiredDate = trim($_POST['acquired_date'] ?? '');
                $valueAmount = (float)($_POST['value_amount'] ?? 0);
                $remark = trim($_POST['remark'] ?? '');

                if ($name === '' || $acquiredDate === '' || $valueAmount <= 0) {
                    $error = '请填写名称、到手日期和正确的资产价值。';
                } else {
                    $iconType = null;
                    $iconValue = null;
                    $iconMode = $_POST['icon_mode'] ?? 'none';

                    if ($iconMode === 'file' && !empty($_FILES['icon_file'])) {
                        $savedPath = Upload::saveAttachment($userId, $_FILES['icon_file']);
                        if ($savedPath) {
                            $iconType = 'file';
                            $iconValue = $savedPath;
                            IconLibrary::ensureExists($userId, $savedPath, $name ?: '资产图标');
                            if (!empty($_POST['submit_to_system'])) {
                                try {
                                    SystemIconSubmission::createIfNotOpen($userId, $name ?: '资产图标', $savedPath);
                                } catch (\Throwable $e) {}
                            }
                        }
                    } elseif ($iconMode === 'library') {
                        $libId = (int)($_POST['icon_library_id'] ?? 0);
                        $systemIconId = (int)($_POST['system_icon_id'] ?? 0);
                        if ($libId > 0) {
                            $icon = IconLibrary::findByUser($userId, $libId);
                            if ($icon) {
                                $iconType = 'file';
                                $iconValue = $icon['file_path'] ?? null;
                            }
                        } elseif ($systemIconId > 0) {
                            $systemIcon = SystemIconLibrary::findById($systemIconId);
                            if ($systemIcon) {
                                $iconType = 'file';
                                $iconValue = $systemIcon['file_path'] ?? null;
                            }
                        }
                    } elseif ($iconMode === 'clear') {
                        $iconType = null;
                        $iconValue = null;
                    } elseif ($action === 'update') {
                        $current = Asset::findByUser($userId, $id);
                        if ($current) {
                            $iconType = $current['icon_type'] ?? null;
                            $iconValue = $current['icon_value'] ?? null;
                        }
                    }

                    try {
                        if ($action === 'create') {
                            Asset::create($userId, $name, $acquiredDate, $valueAmount, $iconType, $iconValue, $remark !== '' ? $remark : null);
                            $success = '新增资产成功';
                        } else {
                            if (Asset::update($userId, $id, $name, $acquiredDate, $valueAmount, $iconType, $iconValue, $remark !== '' ? $remark : null)) {
                                $success = '资产已更新';
                            } else {
                                $error = '资产更新失败或资产不存在。';
                            }
                        }
                    } catch (\Throwable $e) {
                        $error = '保存资产时发生错误，请稍后重试。';
                    }
                }
            } elseif ($action === 'delete') {
                $id = (int)($_POST['id'] ?? 0);
                if ($id > 0) {
                    if (Asset::delete($userId, $id)) {
                        $success = '资产已删除';
                    } else {
                        $error = '删除失败，资产可能不存在。';
                    }
                }
            } elseif ($action === 'transfer') {
                $id = (int)($_POST['id'] ?? 0);
                $transferDate = trim($_POST['transfer_date'] ?? '');
                $transferPrice = (float)($_POST['transfer_price'] ?? 0);
                if ($id <= 0 || $transferDate === '' || $transferPrice < 0) {
                    $error = '请填写正确的转手日期和转手价格。';
                } else {
                    if (Asset::transfer($userId, $id, $transferDate, $transferPrice)) {
                        $success = '资产已标记为已转手';
                    } else {
                        $error = '转手失败，资产可能不存在。';
                    }
                }
            }
        }

        $keyword = trim($_GET['q'] ?? '');
        $sort = $_GET['sort'] ?? 'latest';
        $viewTab = $_GET['tab'] ?? 'active';

        $all = Asset::allByUser($userId, $keyword);
        $today = new \DateTimeImmutable('today');

        $activeAssets = [];
        $transferredAssets = [];
        $totalValue = 0.0;
        $totalDailyCost = 0.0;

        foreach ($all as $row) {
            $acquired = !empty($row['acquired_date']) ? new \DateTimeImmutable($row['acquired_date']) : $today;
            $status = $row['status'] ?? 'active';
            $value = (float)($row['value_amount'] ?? 0);

            if ($status === 'active') {
                $days = max(1, $today->diff($acquired)->days + 1);
                $daily = $days > 0 ? round($value / $days, 2) : 0.0;
                $row['use_days'] = $days;
                $row['daily_cost'] = $daily;
                $totalValue += $value;
                $totalDailyCost += $daily;
                $activeAssets[] = $row;
            } else {
                $transferDate = !empty($row['transfer_date']) ? new \DateTimeImmutable($row['transfer_date']) : $today;
                $days = max(1, $transferDate->diff($acquired)->days + 1);
                $daily = $days > 0 ? round($value / $days, 2) : 0.0;
                $row['use_days'] = $days;
                $row['daily_cost'] = $daily;
                $transferredAssets[] = $row;
            }
        }

        $sorter = function (array &$list) use ($sort) {
            usort($list, function (array $a, array $b) use ($sort): int {
                $av = (float)($a['value_amount'] ?? 0);
                $bv = (float)($b['value_amount'] ?? 0);
                $ad = (float)($a['daily_cost'] ?? 0);
                $bd = (float)($b['daily_cost'] ?? 0);
                $ac = strtotime($a['created_at'] ?? '1970-01-01');
                $bc = strtotime($b['created_at'] ?? '1970-01-01');
                switch ($sort) {
                    case 'oldest':
                        return $ac <=> $bc;
                    case 'price_desc':
                        return $bv <=> $av;
                    case 'price_asc':
                        return $av <=> $bv;
                    case 'daily_desc':
                        return $bd <=> $ad;
                    case 'daily_asc':
                        return $ad <=> $bd;
                    case 'latest':
                    default:
                        return $bc <=> $ac;
                }
            });
        };

        $sorter($activeAssets);
        $sorter($transferredAssets);

        $assetCount = count($activeAssets);

        $iconLibrary = IconLibrary::allByUser($userId);
        $systemIcons = SystemIconLibrary::all();

        $this->render('assets/index', [
            'error' => $error,
            'success' => $success,
            'keyword' => $keyword,
            'sort' => $sort,
            'viewTab' => $viewTab,
            'activeAssets' => $activeAssets,
            'transferredAssets' => $transferredAssets,
            'totalValue' => $totalValue,
            'totalDailyCost' => $totalDailyCost,
            'assetCount' => $assetCount,
            'iconLibrary' => $iconLibrary,
            'systemIcons' => $systemIcons,
        ]);
    }
}
