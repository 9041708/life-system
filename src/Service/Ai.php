<?php
namespace App\Service;

class Ai
{
    public static function parseTransactionText(string $text, array $context = []): array
    {
        $text = trim($text);
        if ($text === '') {
            throw new \InvalidArgumentException('文本不能为空');
        }

        $provider = strtolower((string)Config::get('ai.provider', 'qclaw'));
        if ($provider !== 'qclaw') {
            throw new \RuntimeException('当前仅支持 QClaw 作为 AI 解析引擎');
        }

        $timeout = (int)Config::get('ai.timeout', 30);
        $useCli = Config::get('ai.qclaw_use_cli', false);

        $payload = [
            'text' => $text,
            'fields' => [
                'type',
                'amount',
                'category_name',
                'item_name',
                'from_account_name',
                'to_account_name',
                'trans_time',
                'remark',
            ],
            'context' => $context,
            'format' => 'json',
        ];

        return $useCli
            ? self::callQClawCli($payload)
            : self::callQClawHttp($payload, $timeout);
    }

    private static function callQClawHttp(array $payload, int $timeout): array
    {
        $url = trim((string)Config::get('ai.qclaw_api_url', ''));
        if ($url === '') {
            throw new \RuntimeException('未配置 QClaw HTTP 接口地址');
        }

        $options = [
            'http' => [
                'method' => 'POST',
                'header' => "Content-Type: application/json\r\nAccept: application/json\r\n",
                'content' => json_encode($payload, JSON_UNESCAPED_UNICODE),
                'timeout' => $timeout,
            ],
        ];
        $context = stream_context_create($options);

        $response = @file_get_contents($url, false, $context);
        if ($response === false) {
            $error = error_get_last();
            throw new \RuntimeException('调用 QClaw HTTP 接口失败：' . ($error['message'] ?? '未知错误'));
        }

        $data = json_decode($response, true);
        if (!is_array($data)) {
            throw new \RuntimeException('QClaw HTTP 接口返回无效 JSON');
        }

        return $data;
    }

    private static function callQClawCli(array $payload): array
    {
        $path = trim((string)Config::get('ai.qclaw_cli_path', ''));
        if ($path === '') {
            throw new \RuntimeException('未配置 QClaw CLI 路径');
        }

        $payloadJson = json_encode($payload, JSON_UNESCAPED_UNICODE);
        if ($payloadJson === false) {
            throw new \RuntimeException('无法编码 QClaw CLI 传参');
        }

        $command = escapeshellcmd($path) . ' parse ' . escapeshellarg($payloadJson) . ' 2>&1';
        $output = [];
        $exitCode = 0;
        exec($command, $output, $exitCode);

        $response = implode("\n", $output);
        if ($exitCode !== 0) {
            throw new \RuntimeException('调用 QClaw CLI 失败：' . $response);
        }

        $data = json_decode($response, true);
        if (!is_array($data)) {
            throw new \RuntimeException('QClaw CLI 返回无效 JSON');
        }

        return $data;
    }
}
