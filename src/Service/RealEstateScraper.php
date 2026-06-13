<?php
namespace App\Service;

class RealEstateScraper
{
    private static function fetch(string $url, array $headers = [], int $timeout = 15): ?string
    {
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => $timeout,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_ENCODING => 'gzip',
            CURLOPT_USERAGENT => 'Mozilla/5.0 (iPhone; CPU iPhone OS 17_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.0 Mobile/15E148 Safari/604.1',
            CURLOPT_HTTPHEADER => array_merge([
                'Accept: application/json, text/plain, */*',
                'Accept-Language: zh-CN,zh;q=0.9',
                'Referer: https://m.ke.com/',
            ], $headers),
        ]);
        $response = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err = curl_error($ch);
        curl_close($ch);
        if ($code !== 200 || !$response) return null;
        return $response;
    }

    private static function cityCode(string $city): string
    {
        $map = ['北京'=>'bj','上海'=>'sh','广州'=>'gz','深圳'=>'sz','杭州'=>'hz','成都'=>'cd','武汉'=>'wh','南京'=>'nj','重庆'=>'cq','西安'=>'xa','苏州'=>'su','天津'=>'tj','长沙'=>'cs','郑州'=>'zz','东莞'=>'dg','佛山'=>'fs','合肥'=>'hf','昆明'=>'km','福州'=>'fz','厦门'=>'xm','大连'=>'dl','沈阳'=>'sy','青岛'=>'qd','济南'=>'jn','宁波'=>'nb','无锡'=>'wx','温州'=>'wz'];
        return $map[$city] ?? 'bj';
    }

    /**
     * 通过贝壳移动端API搜索二手房
     */
    public static function searchSale(string $city, string $keyword, int $page = 1): array
    {
        $code = self::cityCode($city);

        // 方式1：贝壳移动端搜索API
        $url = "https://m.ke.com/{$code}/ershoufang/rs" . urlencode($keyword) . "/pg{$page}/";
        $html = self::fetch($url);
        if ($html) {
            $items = [];
            // 尝试从移动端页面提取JSON数据
            if (preg_match('/window\.__INITIAL_STATE__\s*=\s*(\{.*?\});/s', $html, $m)) {
                $data = json_decode($m[1], true);
                if (!empty($data['ershoufang']['list'])) {
                    foreach ($data['ershoufang']['list'] as $item) {
                        $items[] = [
                            'title' => $item['title'] ?? $item['communityName'] ?? '',
                            'position' => ($item['districtName'] ?? '') . ' ' . ($item['bizCircleName'] ?? ''),
                            'info' => ($item['frameRoomNum'] ?? '') . '室' . ($item['frameHallNum'] ?? '') . '厅 ' . ($item['area'] ?? '') . '㎡',
                            'total_price' => (float)($item['totalPrice'] ?? 0),
                            'unit_price' => (float)($item['unitPrice'] ?? 0),
                            'platform' => '贝壳找房',
                        ];
                    }
                }
            }
            // 备用：正则提取
            if (empty($items) && preg_match_all('/sellListData.*?\"title\":\"(.*?)\".*?\"totalPrice\":\"([\d.]+)\".*?\"unitPrice\":\"([\d.]+)\"/s', $html, $matches, PREG_SET_ORDER)) {
                foreach ($matches as $m) {
                    $items[] = ['title' => $m[1], 'position' => '', 'info' => '', 'total_price' => (float)$m[2], 'unit_price' => (float)$m[3], 'platform' => '贝壳找房'];
                }
            }
            if (!empty($items)) return ['ok' => true, 'items' => $items, 'total' => count($items)];
        }

        // 方式2：PC端直接抓取
        $pcUrl = "https://{$code}.ke.com/ershoufang/rs" . urlencode($keyword) . "/pg{$page}/";
        $pcHtml = self::fetch($pcUrl, [], 15);
        if ($pcHtml) {
            $items = [];
            if (preg_match_all('/class="totalPrice totalPrice2".*?<span>([\d.]+)<\/span>.*?class="unitPrice".*?<span>([\d.]+)<\/span>/s', $pcHtml, $matches, PREG_SET_ORDER)) {
                // 有价格数据，继续提取标题
            }
            if (preg_match_all('/<div class="info clear">.*?<div class="title">(.*?)<\/div>.*?<div class="positionInfo">(.*?)<\/div>.*?<div class="houseInfo">(.*?)<\/div>.*?totalPrice.*?<span>([\d.]+)<\/span>.*?unitPrice.*?<span>([\d.]+)<\/span>/s', $pcHtml, $matches, PREG_SET_ORDER)) {
                foreach ($matches as $m) {
                    $items[] = ['title' => strip_tags($m[1]), 'position' => strip_tags($m[2]), 'info' => strip_tags($m[3]), 'total_price' => (float)$m[4], 'unit_price' => (float)$m[5], 'platform' => '贝壳找房'];
                }
            }
            if (!empty($items)) return ['ok' => true, 'items' => $items, 'total' => count($items)];
        }

        // 方式3：安居客备用
        $ajkUrl = "https://" . $code . ".anjuke.com/sale/?kw=" . urlencode($keyword) . "&p=" . $page;
        $ajkHtml = self::fetch($ajkUrl);
        if ($ajkHtml) {
            $items = [];
            if (preg_match_all('/class="house-title".*?>(.*?)<\/a>.*?class="house-price".*?<span>([\d.]+)<\/span>/s', $ajkHtml, $matches, PREG_SET_ORDER)) {
                foreach ($matches as $m) {
                    $items[] = ['title' => strip_tags($m[1]), 'position' => '', 'info' => '', 'total_price' => (float)$m[2], 'unit_price' => 0, 'platform' => '安居客'];
                }
            }
            if (!empty($items)) return ['ok' => true, 'items' => $items, 'total' => count($items)];
        }

        return ['ok' => false, 'error' => '抓取失败，所有平台均被反爬限制。建议直接访问贝壳/安居客查看。', 'items' => []];
    }

    /**
     * 搜索租房
     */
    public static function searchRent(string $city, string $keyword, int $page = 1): array
    {
        $code = self::cityCode($city);

        $url = "https://m.ke.com/{$code}/zufang/rs" . urlencode($keyword) . "/pg{$page}/";
        $html = self::fetch($url);
        if ($html) {
            $items = [];
            if (preg_match('/window\.__INITIAL_STATE__\s*=\s*(\{.*?\});/s', $html, $m)) {
                $data = json_decode($m[1], true);
                if (!empty($data['zufang']['list'])) {
                    foreach ($data['zufang']['list'] as $item) {
                        $items[] = [
                            'title' => $item['title'] ?? '',
                            'rent_price' => (float)($item['price'] ?? 0),
                            'platform' => '贝壳找房',
                        ];
                    }
                }
            }
            if (empty($items) && preg_match_all('/content__list--item--main.*?<a[^>]*>(.*?)<\/a>.*?content__list--item-price.*?<em>([\d.]+)<\/em>/s', $html, $matches, PREG_SET_ORDER)) {
                foreach ($matches as $m) {
                    $items[] = ['title' => strip_tags($m[1]), 'rent_price' => (float)$m[2], 'platform' => '贝壳找房'];
                }
            }
            if (!empty($items)) return ['ok' => true, 'items' => $items, 'total' => count($items)];
        }

        return ['ok' => false, 'error' => '租房数据抓取失败', 'items' => []];
    }

    /**
     * 获取小区最新均价
     */
    public static function fetchCommunityPrice(string $communityId): ?float
    {
        // 方式1：移动端API
        $url = "https://m.ke.com/api/community/price?communityId={$communityId}";
        $json = self::fetch($url);
        if ($json) {
            $data = json_decode($json, true);
            if (!empty($data['data']['price'])) return (float)$data['data']['price'];
        }

        // 方式2：PC页面
        $pcUrl = "https://bj.ke.com/xiaoqu/{$communityId}/";
        $html = self::fetch($pcUrl);
        if ($html) {
            if (preg_match('/均价\s*<span[^>]*>([\d.]+)<\/span>/', $html, $m)) return (float)$m[1];
            if (preg_match('/\"avgPrice\":\"([\d.]+)\"/', $html, $m)) return (float)$m[1];
            if (preg_match('/小区均价.*?(\d+)/s', $html, $m)) return (float)$m[1];
        }

        return null;
    }

    /**
     * 批量更新关注房产价格
     */
    public static function updateWatchedPrices(): array
    {
        $properties = \App\Model\Property::getAllWithSourceId();
        $updated = 0; $failed = 0;
        foreach ($properties as $prop) {
            $sid = $prop['source_id'];
            if (empty($sid)) continue;
            $price = self::fetchCommunityPrice($sid);
            if ($price !== null && $price > 0) {
                \App\Model\PropertyPrice::add((int)$prop['id'], (int)$prop['user_id'], (float)$prop['current_price'], $price, 'auto');
                \App\Model\Property::update((int)$prop['id'], (int)$prop['user_id'], ['unit_price' => $price]);
                $updated++;
            } else {
                $failed++;
            }
            usleep(800000);
        }
        return ['updated' => $updated, 'failed' => $failed, 'total' => count($properties)];
    }
}
