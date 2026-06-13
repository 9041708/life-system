<?php
namespace App\Service;

class StockApi
{
    private static function curl(string $url, int $timeout = 10): ?string
    {
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => $timeout,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_ENCODING => 'gzip',
            CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
            CURLOPT_HTTPHEADER => ['Referer: https://quote.eastmoney.com/'],
        ]);
        $r = curl_exec($ch);
        curl_close($ch);
        return $r ?: null;
    }

    /**
     * 搜索股票（东方财富 suggest API，返回 JSON + UTF-8）
     */
    public static function search(string $keyword): array
    {
        $url = 'https://searchapi.eastmoney.com/api/suggest/get?input=' . urlencode($keyword) . '&type=14&token=D43BF722C8E33BDC906FB84D85E326E8&count=10';
        $body = self::curl($url);
        if (!$body) return [];
        $data = json_decode($body, true);
        $results = [];
        if (!empty($data['QuotationCodeTable']['Data'])) {
            foreach ($data['QuotationCodeTable']['Data'] as $item) {
                $code = $item['Code'] ?? '';
                $name = $item['Name'] ?? '';
                $mkt = (int)($item['MktNum'] ?? 0);
                if ($name === '' || $code === '') continue;
                // market: 1=SH, 0=SZ, 116=HK, 105=US
                if ($mkt === 1 || $code >= '600000') {
                    $results[] = ['symbol' => 'sh' . $code, 'name' => $name, 'code' => $code, 'market' => 'SH'];
                } elseif ($mkt === 0) {
                    $results[] = ['symbol' => 'sz' . $code, 'name' => $name, 'code' => $code, 'market' => 'SZ'];
                } elseif ($mkt === 116) {
                    $results[] = ['symbol' => 'hk' . $code, 'name' => $name, 'code' => $code, 'market' => 'HK'];
                } elseif ($mkt === 105) {
                    $results[] = ['symbol' => 'us' . $code, 'name' => $name, 'code' => $code, 'market' => 'US'];
                }
            }
        }
        return array_slice($results, 0, 10);
    }

    /**
     * 获取单只股票行情
     */
    public static function getQuote(string $symbol): ?array
    {
        $data = self::getQuotes([$symbol]);
        return $data[0] ?? null;
    }

    /**
     * 批量获取行情（东方财富实时行情 API，JSON+UTF-8）
     * 包含：现价、涨跌幅、开盘、最高、最低、成交量、成交额、市值、PE等
     */
    public static function getQuotes(array $symbols): array
    {
        if (empty($symbols)) return [];
        $codes = [];
        $codeMap = []; // eastmoney code => original symbol
        foreach ($symbols as $s) {
            $emCode = self::toEastMoneyCode($s);
            if ($emCode) {
                $codes[] = $emCode;
                $codeMap[$emCode] = $s;
            }
        }
        if (empty($codes)) return [];

        $url = 'https://push2.eastmoney.com/api/qt/stock/get?ut=fa5fd1943c7b386f172d6893dbfba10b&fltt=2&invt=2&fields=f43,f44,f45,f46,f47,f48,f49,f50,f51,f52,f55,f57,f58,f60,f116,f117,f162,f167,f168,f169,f170,f171,f9,f100,f115,f20,f21&secids=' . implode(',', $codes);
        $body = self::curl($url);
        if (!$body) return [];
        $data = json_decode($body, true);
        $results = [];
        if (!empty($data['data']['diff'])) {
            foreach ($data['data']['diff'] as $item) {
                $emCode = $item['f57'] ?? '';
                $origSymbol = $codeMap[$emCode] ?? '';
                if ($origSymbol === '') continue;
                $prev = (float)($item['f60'] ?? 0);     // 昨收
                $current = (float)($item['f43'] ?? 0);  // 现价
                $change = $prev > 0 ? $current - $prev : 0;
                $changePct = (float)($item['f170'] ?? 0) / 100; // 涨跌幅
                $results[] = [
                    'symbol' => $origSymbol,
                    'name' => $item['f58'] ?? '',        // 股票名称
                    'market' => self::getMarket($origSymbol),
                    'current' => round($current, 4),
                    'open' => round((float)($item['f46'] ?? 0), 4),
                    'high' => round((float)($item['f44'] ?? 0), 4),
                    'low' => round((float)($item['f45'] ?? 0), 4),
                    'prev_close' => round($prev, 4),
                    'volume' => round((float)($item['f47'] ?? 0)),
                    'amount' => round((float)($item['f48'] ?? 0), 2),
                    'change' => round($change, 4),
                    'change_percent' => round($changePct, 2),
                    'pe' => round((float)($item['f162'] ?? 0), 2),
                    'pe_ttm' => round((float)($item['f115'] ?? 0), 2),
                    'market_cap' => round((float)($item['f116'] ?? 0), 2),
                    'circulating_cap' => round((float)($item['f117'] ?? 0), 2),
                    'pb' => round((float)($item['f167'] ?? 0), 2),
                    'turnover_rate' => round((float)($item['f168'] ?? 0), 2),
                    'amplitude' => round((float)($item['f171'] ?? 0), 2),
                    'roe' => round((float)($item['f173'] ?? 0), 2),
                    'high_52w' => round((float)($item['f51'] ?? 0), 4),
                    'low_52w' => round((float)($item['f52'] ?? 0), 4),
                ];
            }
        }
        return $results;
    }

    /**
     * 获取K线数据（东方财富）
     * scale: 5/15/30/60分, 101=日K, 102=周K, 103=月K
     */
    public static function getKline(string $symbol, string $scale = '101', int $count = 120): array
    {
        $emCode = self::toEastMoneyCode($symbol);
        if (!$emCode) return [];
        $url = "https://push2his.eastmoney.com/api/qt/stock/kline/get?ut=fa5fd1943c7b386f172d6893dbfba10b&fields1=f1,f2,f3,f4,f5&fields2=f51,f52,f53,f54,f55,f56&klt={$scale}&fqt=1&beg=0&end=20500101&secid={$emCode}&lmt={$count}";
        $body = self::curl($url);
        if (!$body) return [];
        $data = json_decode($body, true);
        $result = [];
        if (!empty($data['data']['klines'])) {
            foreach ($data['data']['klines'] as $line) {
                $parts = explode(',', $line);
                if (count($parts) < 6) continue;
                $result[] = [
                    'time' => $parts[0],  // 日期字符串 '2024-06-13'，lightweight-charts 自动解析
                    'open' => (float)$parts[1],
                    'close' => (float)$parts[2],
                    'high' => (float)$parts[3],
                    'low' => (float)$parts[4],
                    'volume' => (float)$parts[5],
                ];
            }
        }
        return $result;
    }

    private static function toEastMoneyCode(string $symbol): string
    {
        if (preg_match('/^(sh|sz)(\d{6})$/', $symbol, $m)) {
            return ($m[1] === 'sh' ? '1.' : '0.') . $m[2];
        }
        if (preg_match('/^hk(\d+)$/', $symbol, $m)) {
            return '116.' . $m[1];
        }
        if (preg_match('/^us(.+)$/', $symbol, $m)) {
            return '105.' . strtoupper($m[1]);
        }
        return '';
    }

    private static function getMarket(string $symbol): string
    {
        if (str_starts_with($symbol, 'sh')) return 'SH';
        if (str_starts_with($symbol, 'sz')) return 'SZ';
        if (str_starts_with($symbol, 'hk')) return 'HK';
        if (str_starts_with($symbol, 'us')) return 'US';
        return 'SH';
    }
}
