<?php
namespace App\Controller;

use App\Service\LedgerContext;

class LedgerController
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

    public function switch(): void
    {
        $userId = $this->requireLogin();
        $ledgerId = (int)($_POST['ledger_id'] ?? 0);

        if ($ledgerId > 0) {
            LedgerContext::setActiveLedgerId($userId, $ledgerId);
        }

        $back = (string)($_SERVER['HTTP_REFERER'] ?? '');
        if ($back !== '') {
            header('Location: ' . $back);
            exit;
        }
        header('Location: /public/index.php');
        exit;
    }
}
