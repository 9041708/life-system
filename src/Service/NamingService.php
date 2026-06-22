<?php
namespace App\Service;

class NamingService
{
    private static ?array $data = null;

    private static function loadData(): array
    {
        if (self::$data === null) {
            $file = dirname(__DIR__, 2) . '/tools/name_chars.php';
            self::$data = file_exists($file) ? require $file : ['chars' => [], 'fortune81' => [], 'sancai' => []];
        }
        return self::$data;
    }

    public static function getChars(): array
    {
        return self::loadData()['chars'] ?? [];
    }

    public static function getFortune81(): array
    {
        return self::loadData()['fortune81'] ?? [];
    }

    public static function getSancai(): array
    {
        return self::loadData()['sancai'] ?? [];
    }

    public static function getWuxingFromStrokes(int $strokes): string
    {
        $map = ['木', '木', '火', '火', '土', '土', '金', '金', '水', '水'];
        return $map[($strokes - 1) % 10];
    }

    public static function getCharInfo(string $char): ?array
    {
        $chars = self::getChars();
        if (isset($chars[$char])) {
            $c = $chars[$char];
            return ['char' => $char, 'pinyin' => $c[0], 'strokes' => $c[1], 'wuxing' => $c[2], 'tags' => $c[3], 'gender' => $c[4]];
        }
        return null;
    }

    /**
     * 取名：生成推荐名字列表
     */
    private static array $excludeChars = [
        // 工具/器物类 — 不适合做人名
        '镐','铁','钢','铜','锉','钳','铆','钉','锤','锯','镰','锄','锹',
        '铲','凿','斧','镊','钵','锅','盆','罐','壶','碗','筷',
        '帚','簸','缸','阀','泵','栓','螺丝','砧','碾','磨',
        // 日常物品
        '它','宅','字','存','孙','品','活','流','消','块','坑','坏','坐','坠',
        '坍','坟','圾','垃','渣','垢','厕','棚','窑','灶','炕','橱','柜',
        '桌','椅','凳','床','枕','被','褥','帘','幕','伞','扇','灯','烛',
        // 身体/负面
        '臀','肛','屎','尿','屁','脓','痰','涕','唾','骸','尸','棺','殡',
        // 负面含义
        '溺','灭','毁','摧','崩','塌','裂','碎','烂','腐','臭','腥','膻','臊',
        '丑','蠢','笨','傻','痴','呆','疯','癫','狂','暴','虐','残','狠','毒',
        '骗','偷','盗','贼','匪','寇','奸','娼','妓','嫖','赌','贪','赃',
        // 过于生僻/难认
        '爨','燮','嬴','嬷','孀','嬖','嬗','嬉','嫩','嫱','嫦',
        // 金属加工类
        '铸','锻','镀','锲','锭','铣','铡','铧','镌','镂','铩','锉','锔',
        // 建筑/工程
        '桩','梁','柱','椽','檩','桁','栅','堰','堤','坝','闸','涵','隧',
        // 农业/自然粗犷
        '粪','肥','畜','禽','兽','虫','蛇','鼠','蝇','蚊','蛆','蛹',
        // 不雅/拗口
        '箴','鏖','龌','龊','龃','龉','魑','魅','魍','魉',
        '邋','遢','怂','恿','猥','亵','嫖','娼',
        // 军事/武器（过于刚硬）
        '戟','戈','矛','矢','箭','弩','盾','盔','甲','胄',
        // 刑罚/灾难
        '刑','罚','戮','诛','剐','斩','绞','缢','囚','牢',
        // 丧葬/疾病
        '丧','殡','殓','柩','椁','墓','癌','疫','瘟','疮','癣',
        // 不适合起名的常见字
        '派','镫','洞','涧','沟','渠','坑','窖','穴','窝','巢','窟',
        '隙','缝','裂','痕','疤','纹','斑','疵','瘢',
        '锁','链','绳','索','网','笼','牢','圈','栏','栅',
        '蜡','烛','煤','炭','灰','尘','埃','垢','泥','浆',
        '痰','沫','泡','渣','滓','沉淀','淤',
        '桩','桩','桩','桩',
        '距','趾','蹄','爪','角','牙','齿','嘴','唇','喉',
        '柜','橱','箱','匣','囊','袋','包','裹',
        '屏','障','壁','墙','垣','篱',
        '梯','阶','陛','台','坛','墩',
        '枕','席','帘','幔','幕','帐',
        '钩','叉','耙','杵','臼','磨',
        '轿','辇','舆','舟','艇','筏',
        '棚','篷','庐','庵','寺','庙',
        '靶','的','鹄',
    ];

    /**
     * 检查名字整体质量：读音、含义、字形搭配
     */
    private static function isGoodName(string $surname, array $nameChars): bool
    {
        $allPinyin = [];
        $allStrokes = [];
        $allTags = [];

        // 收集所有字的拼音、笔画、标签
        $chars = self::getChars();
        foreach ($nameChars as $nc) {
            if (isset($chars[$nc])) {
                $info = $chars[$nc];
                $allPinyin[] = $info[0];
                $allStrokes[] = $info[1];
                $allTags = array_merge($allTags, $info[3]);
            }
        }

        // 过滤：连续两个字声母相同（读音拗口）
        if (count($allPinyin) >= 2) {
            for ($i = 0; $i < count($allPinyin) - 1; $i++) {
                $s1 = substr($allPinyin[$i], 0, 1);
                $s2 = substr($allPinyin[$i + 1], 0, 1);
                if ($s1 === $s2 && $s1 !== '') return false;
            }
        }

        // 过滤：连续两个字韵母相同（读音重复感）
        if (count($allPinyin) >= 2) {
            for ($i = 0; $i < count($allPinyin) - 1; $i++) {
                $yun1 = preg_replace('/^[^aeiou]*/', '', $allPinyin[$i]);
                $yun2 = preg_replace('/^[^aeiou]*/', '', $allPinyin[$i + 1]);
                if ($yun1 === $yun2 && $yun1 !== '') return false;
            }
        }

        // 过滤：名字中连续两个字笔画都超过18画（书写困难）
        if (count($allStrokes) >= 2) {
            $heavyCount = 0;
            foreach ($allStrokes as $s) {
                if ($s >= 18) $heavyCount++;
            }
            if ($heavyCount >= 2) return false;
        }

        // 过滤：名字笔画差异过大（如1画+24画，不协调）
        if (count($allStrokes) >= 2) {
            $min = min($allStrokes);
            $max = max($allStrokes);
            if ($max - $min > 15) return false;
        }

        return true;
    }

    public static function generateNames(string $surname, string $gender = 'n', int $nameLen = 2, array $preferTags = [], ?string $preferWuxing = null, int $limit = 20, ?string $generationChar = null): array
    {
        $chars = self::getChars();
        $fortune81 = self::getFortune81();
        $sancaiTable = self::getSancai();

        $surnameStrokes = 0;
        $surnameChars = mb_str_split($surname);
        foreach ($surnameChars as $sc) {
            if (isset($chars[$sc])) {
                $surnameStrokes += $chars[$sc][1];
            } else {
                $surnameStrokes += 8;
            }
        }

        $generationStrokes = 0;
        $generationInfo = null;
        if ($generationChar !== null && $generationChar !== '') {
            if (isset($chars[$generationChar])) {
                $generationInfo = $chars[$generationChar];
                $generationStrokes = $generationInfo[1];
            } else {
                $generationStrokes = 8;
            }
            $nameLen = 1;
        }

        $candidates = [];
        foreach ($chars as $ch => $info) {
            [$pinyin, $strokes, $wuxing, $tags, $g] = $info;
            if (in_array($ch, self::$excludeChars)) continue;
            if ($gender !== 'n' && $g !== 'n' && $g !== $gender) continue;
            if ($preferWuxing && $wuxing !== $preferWuxing) continue;
            if (!empty($preferTags)) {
                $match = false;
                foreach ($preferTags as $pt) {
                    if (in_array($pt, $tags)) { $match = true; break; }
                }
                if (!$match) continue;
            }
            $candidates[] = ['char' => $ch, 'pinyin' => $pinyin, 'strokes' => $strokes, 'wuxing' => $wuxing, 'tags' => $tags, 'gender' => $g];
        }

        if (empty($candidates)) return [];

        $results = [];
        $seen = [];

        if ($nameLen === 1) {
            foreach ($candidates as $c) {
                if (count($results) >= $limit) break;
                $key = $c['char'];
                if (isset($seen[$key])) continue;
                if ($generationChar !== null && $c['char'] === $generationChar) continue;
                $seen[$key] = true;

                $effectiveSurnameStrokes = $surnameStrokes + $generationStrokes;
                $totalStrokes = $effectiveSurnameStrokes + $c['strokes'];
                $renGe = $effectiveSurnameStrokes + $c['strokes'];
                $diGe = $c['strokes'] + 1;
                $tianGe = $effectiveSurnameStrokes + 1;
                $waiGe = $totalStrokes - $renGe + 1;

                $score = self::calcScore($tianGe, $renGe, $diGe, $waiGe, $totalStrokes, $fortune81, $sancaiTable, $surnameStrokes);
                $displayName = $generationChar !== null ? $surname . $generationChar . $c['char'] : $surname . $c['char'];
                $displayChars = $generationChar !== null
                    ? array_merge($generationInfo ? [['char' => $generationChar, 'pinyin' => $generationInfo[0], 'strokes' => $generationInfo[1], 'wuxing' => $generationInfo[2], 'tags' => $generationInfo[3], 'gender' => $generationInfo[4]]] : [['char' => $generationChar, 'pinyin' => '?', 'strokes' => $generationStrokes, 'wuxing' => self::getWuxingFromStrokes($generationStrokes), 'tags' => ['字辈'], 'gender' => 'n']], [$c])
                    : [$c];
                if (!self::isGoodName($surname, array_column($displayChars, 'char'))) continue;
                $results[] = [
                    'name' => $displayName,
                    'chars' => $displayChars,
                    'tian_ge' => $tianGe,
                    'ren_ge' => $renGe,
                    'di_ge' => $diGe,
                    'wai_ge' => $waiGe,
                    'zong_ge' => $totalStrokes,
                    'score' => $score['total'],
                    'detail' => $score,
                ];
            }
        } else {
            $cnt = count($candidates);
            $maxCombos = max(5000, $limit * 50);
            $tried = 0;
            for ($i = 0; $i < $cnt && count($results) < $limit * 3; $i++) {
                for ($j = 0; $j < $cnt && count($results) < $limit * 3; $j++) {
                    if ($tried++ > $maxCombos) break 2;
                    $c1 = $candidates[$i];
                    $c2 = $candidates[$j];

                    $totalStrokes = $surnameStrokes + $c1['strokes'] + $c2['strokes'];
                    $renGe = $surnameStrokes + $c1['strokes'];
                    $diGe = $c1['strokes'] + $c2['strokes'];
                    $tianGe = $surnameStrokes + 1;
                    $waiGe = $totalStrokes - $renGe + 1;

                    $score = self::calcScore($tianGe, $renGe, $diGe, $waiGe, $totalStrokes, $fortune81, $sancaiTable, $surnameStrokes);
                    if ($score['total'] < 60) continue;
                    if (!self::isGoodName($surname, [$c1['char'], $c2['char']])) continue;

                    $key = $c1['char'] . $c2['char'];
                    if (isset($seen[$key])) continue;
                    $seen[$key] = true;

                    $results[] = [
                        'name' => $surname . $c1['char'] . $c2['char'],
                        'chars' => [$c1, $c2],
                        'tian_ge' => $tianGe,
                        'ren_ge' => $renGe,
                        'di_ge' => $diGe,
                        'wai_ge' => $waiGe,
                        'zong_ge' => $totalStrokes,
                        'score' => $score['total'],
                        'detail' => $score,
                    ];
                }
            }
        }

        usort($results, fn($a, $b) => $b['score'] <=> $a['score']);
        return array_slice($results, 0, $limit);
    }

    /**
     * 测名：分析姓名
     */
    public static function analyzeName(string $fullName): array
    {
        $chars = self::getChars();
        $fortune81 = self::getFortune81();
        $sancaiTable = self::getSancai();

        $chars_arr = mb_str_split($fullName);
        if (count($chars_arr) < 2) {
            return ['ok' => false, 'error' => '请输入至少2个字的姓名'];
        }

        $surname = $chars_arr[0];
        $nameChars = array_slice($chars_arr, 1);

        $charDetails = [];
        $surnameStrokes = 0;
        foreach ($chars_arr as $ch) {
            $info = self::getCharInfo($ch);
            if (!$info) {
                $info = ['char' => $ch, 'pinyin' => '?', 'strokes' => 8, 'wuxing' => self::getWuxingFromStrokes(8), 'tags' => [], 'gender' => 'n'];
            }
            $charDetails[] = $info;
        }

        foreach ($charDetails as $i => $cd) {
            if ($i < count(mb_str_split($surname))) {
                $surnameStrokes += $cd['strokes'];
            }
        }

        $nameStrokes = 0;
        foreach ($nameChars as $nc) {
            $info = self::getCharInfo($nc);
            $nameStrokes += $info ? $info['strokes'] : 8;
        }

        $totalStrokes = $surnameStrokes + $nameStrokes;
        $tianGe = $surnameStrokes + 1;
        $renGe = $surnameStrokes + (isset($charDetails[1]) ? $charDetails[1]['strokes'] : 0);
        $diGe = count($nameChars) === 1 ? (isset($charDetails[1]) ? $charDetails[1]['strokes'] : 0) + 1 : (isset($charDetails[1]) ? $charDetails[1]['strokes'] : 0) + (isset($charDetails[2]) ? $charDetails[2]['strokes'] : 0);
        $waiGe = $totalStrokes - $renGe + 1;

        $score = self::calcScore($tianGe, $renGe, $diGe, $waiGe, $totalStrokes, $fortune81, $sancaiTable, $surnameStrokes);

        $wuxingList = array_map(fn($c) => $c['wuxing'], $charDetails);
        $wuxingCount = array_count_values($wuxingList);
        $missing = [];
        foreach (['金', '木', '水', '火', '土'] as $wx) {
            if (!isset($wuxingCount[$wx]) || $wuxingCount[$wx] === 0) {
                $missing[] = $wx;
            }
        }

        return [
            'ok' => true,
            'chars' => $charDetails,
            'surname_strokes' => $surnameStrokes,
            'name_strokes' => $nameStrokes,
            'total_strokes' => $totalStrokes,
            'tian_ge' => $tianGe,
            'ren_ge' => $renGe,
            'di_ge' => $diGe,
            'wai_ge' => $waiGe,
            'zong_ge' => $totalStrokes,
            'score' => $score['total'],
            'detail' => $score,
            'wuxing_list' => $wuxingList,
            'wuxing_missing' => $missing,
        ];
    }

    private static function calcScore(int $tian, int $ren, int $di, int $wai, int $zong, array $fortune81, array $sancaiTable, int $surnameStrokes): array
    {
        $tianS = self::getFortune($tian, $fortune81);
        $renS = self::getFortune($ren, $fortune81);
        $diS = self::getFortune($di, $fortune81);
        $waiS = self::getFortune($wai, $fortune81);
        $zongS = self::getFortune($zong, $fortune81);

        $tianWX = self::getWuxingFromStrokes($tian);
        $renWX = self::getWuxingFromStrokes($ren);
        $diWX = self::getWuxingFromStrokes($di);
        $sancaiKey = $tianWX . $renWX . $diWX;
        $sancaiInfo = $sancaiTable[$sancaiKey] ?? ['平', '三才配置无特殊含义'];

        $geoScore = 0;
        foreach ([$tianS, $renS, $diS, $waiS, $zongS] as $f) {
            $geoScore += ($f[0] === '吉' || $f[0] === '大吉') ? 20 : ($f[0] === '半吉' ? 10 : 0);
        }
        $sancaiScore = ($sancaiInfo[0] === '大吉') ? 20 : ($sancaiInfo[0] === '吉' ? 15 : ($sancaiInfo[0] === '半吉' ? 8 : 0));
        $total = min(100, $geoScore + $sancaiScore);

        return [
            'total' => $total,
            'tian' => ['value' => $tian, 'wuxing' => $tianWX, 'fortune' => $tianS[0], 'desc' => $tianS[1]],
            'ren' => ['value' => $ren, 'wuxing' => $renWX, 'fortune' => $renS[0], 'desc' => $renS[1]],
            'di' => ['value' => $di, 'wuxing' => $diWX, 'fortune' => $diS[0], 'desc' => $diS[1]],
            'wai' => ['value' => $wai, 'fortune' => $waiS[0], 'desc' => $waiS[1]],
            'zong' => ['value' => $zong, 'fortune' => $zongS[0], 'desc' => $zongS[1]],
            'sancai' => ['key' => $sancaiKey, 'fortune' => $sancaiInfo[0], 'desc' => $sancaiInfo[1]],
        ];
    }

    private static function getFortune(int $num, array $fortune81): array
    {
        $n = $num % 81;
        if ($n === 0) $n = 81;
        return $fortune81[$n] ?? ['平', '数理无特殊含义'];
    }

    public static function getAvailableTags(): array
    {
        $chars = self::getChars();
        $tags = [];
        foreach ($chars as $info) {
            foreach ($info[3] as $t) {
                $tags[$t] = ($tags[$t] ?? 0) + 1;
            }
        }
        arsort($tags);
        return array_keys($tags);
    }
}
