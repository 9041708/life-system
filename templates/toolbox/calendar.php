<?php
// ============ Constants ============
$lunarMonthNames = ['正','二','三','四','五','六','七','八','九','十','冬','腊'];
$lunarDayNames = ['初一','初二','初三','初四','初五','初六','初七','初八','初九','初十','十一','十二','十三','十四','十五','十六','十七','十八','十九','二十','廿一','廿二','廿三','廿四','廿五','廿六','廿七','廿八','廿九','三十'];
$weekNames = ['日','一','二','三','四','五','六'];
$jieqiAllNames = ['小寒','大寒','立春','雨水','惊蛰','春分','清明','谷雨','立夏','小满','芒种','夏至','小暑','大暑','立秋','处暑','白露','秋分','寒露','霜降','立冬','小雪','大雪','冬至'];

// ============ Lunar Calendar Functions ============
function getLunarYearInfo($y) {
    $d = [
        0x04bd8,0x04ae0,0x0a570,0x054d5,0x0d260,0x0d950,0x16554,0x056a0,0x09ad0,0x055d2,
        0x04ae0,0x0a5b6,0x0a4d0,0x0d250,0x1d255,0x0b540,0x0d6a0,0x0ada2,0x095b0,0x14977,
        0x04970,0x0a4b0,0x0b4b5,0x06a50,0x06d40,0x1ab54,0x02b60,0x09570,0x052f2,0x04970,
        0x06566,0x0d4a0,0x0ea50,0x06e95,0x05ad0,0x02b60,0x186e3,0x092e0,0x1c8d7,0x0c950,
        0x0d4a0,0x1d8a6,0x0b550,0x056a0,0x1a5b4,0x025d0,0x092d0,0x0d2b2,0x0a950,0x0b557,
        0x06ca0,0x0b550,0x15355,0x04da0,0x0a5b0,0x14573,0x052b0,0x0a9a8,0x0e950,0x06aa0,
        0x0aea6,0x0ab50,0x04b60,0x0aae4,0x0a570,0x05260,0x0f263,0x0d950,0x05b57,0x056a0,
        0x096d0,0x04dd5,0x04ad0,0x0a4d0,0x0d4d4,0x0d250,0x0d558,0x0b540,0x0b6a0,0x195a6,
        0x095b0,0x049b0,0x0a974,0x0a4b0,0x0b27a,0x06a50,0x06d40,0x0af46,0x0ab60,0x09570,
        0x04af5,0x04970,0x064b0,0x074a3,0x0ea50,0x06b58,0x055c0,0x0ab60,0x096d5,0x092e0,
        0x0c960,0x0d954,0x0d4a0,0x0da50,0x07552,0x056a0,0x0abb7,0x025d0,0x092d0,0x0cab5,
        0x0a950,0x0b4a0,0x0baa4,0x0ad50,0x055d9,0x04ba0,0x0a5b0,0x15176,0x052b0,0x0a930,
        0x07954,0x06aa0,0x0ad50,0x05b52,0x04b60,0x0a6e6,0x0a4e0,0x0d260,0x0ea65,0x0d530,
        0x05aa0,0x076a3,0x096d0,0x04afb,0x04ad0,0x0a4d0,0x1d0b6,0x0d250,0x0d520,0x0dd45,
        0x0b5a0,0x056d0,0x055b2,0x049b0,0x0a577,0x0a4b0,0x0aa50,0x1b255,0x06d20,0x0ada0,
        0x14b63,0x09370,0x049f8,0x04970,0x064b0,0x168a6,0x0ea50,0x06aa0,0x1a6c4,0x0aae0,
        0x0a2e0,0x0d2e3,0x0c960,0x0d557,0x0d4a0,0x0da50,0x05d55,0x056a0,0x0a6d0,0x055d4,
        0x052d0,0x0a9b8,0x0a950,0x0b4a0,0x0b6a6,0x0ad50,0x055a0,0x0aba4,0x0a5b0,0x052b0,
        0x0b273,0x06930,0x07337,0x06aa0,0x0ad50,0x14b55,0x04b60,0x0a570,0x054e4,0x0d160,
        0x0e968,0x0d520,0x0daa0,0x16aa6,0x056d0,0x04ae0,0x0a9d4,0x0a4d0,0x0d150,0x0f252,
        0x0d520
    ];
    $idx = $y - 1900;
    return ($idx >= 0 && $idx < count($d)) ? $d[$idx] : 0;
}
function lunarDaysInMonth($y, $m) { $info = getLunarYearInfo($y); if ($info === 0) return 29; return ($info & (1 << (16 - $m))) ? 30 : 29; }
function lunarMonthCount($y) { $info = getLunarYearInfo($y); if ($info === 0) return 12; return ($info & 0xf) > 0 ? 13 : 12; }
function lunarLeap($y) { $info = getLunarYearInfo($y); return $info === 0 ? 0 : ($info & 0xf); }
function lunarYearDays($y) { $sum = 0; $lm = lunarLeap($y); $mc = lunarMonthCount($y); for ($i = 1; $i <= $mc; $i++) { if ($lm > 0 && $i === $lm + 1) { $sum += lunarDaysInMonth($y, 0); } else { $m = ($lm > 0 && $i > $lm) ? $i - 1 : $i; $sum += lunarDaysInMonth($y, $m); } } return $sum; }

function solarToLunar($year, $month, $day) {
    $base = new DateTime('1900-01-31');
    $target = new DateTime("{$year}-{$month}-{$day}");
    $diff = (int)$base->diff($target)->format('%r%a');
    if ($diff < 0) return ['y'=>1899,'m'=>12,'d'=>30+$diff+1,'leap'=>false];
    $ly = 1900;
    while ($diff >= lunarYearDays($ly)) { $diff -= lunarYearDays($ly); $ly++; }
    $lm = 1; $leap = false; $leapM = lunarLeap($ly);
    for ($i = 1; $i <= lunarMonthCount($ly); $i++) {
        $isLeap = ($leapM > 0 && $i === $leapM + 1);
        if ($isLeap) {
            $md = lunarDaysInMonth($ly, 0);
            $currentMonth = $leapM;
        } else {
            $currentMonth = ($leapM > 0 && $i > $leapM) ? $i - 1 : $i;
            $md = lunarDaysInMonth($ly, $currentMonth);
        }
        if ($diff < $md) { $lm = $currentMonth; $leap = $isLeap; break; }
        $diff -= $md;
    }
    return ['y'=>$ly, 'm'=>$lm, 'd'=>$diff+1, 'leap'=>$leap];
}

// ============ Ganzhi / Pillar Functions ============
function getCYear($y) {
    $tg = ['甲','乙','丙','丁','戊','己','庚','辛','壬','癸'];
    $dz = ['子','丑','寅','卯','辰','巳','午','未','申','酉','戌','亥'];
    $ti = (($y - 4) % 10 + 10) % 10;
    $di = (($y - 4) % 12 + 12) % 12;
    return $tg[$ti] . $dz[$di];
}

function getShengxiao($y) {
    $sx = ['鼠','牛','虎','兔','龙','蛇','马','羊','猴','鸡','狗','猪'];
    return $sx[(($y - 4) % 12 + 12) % 12];
}

function getDayGanzhiIdx($year, $month, $day) {
    $base = new DateTime('2000-01-01');
    $target = new DateTime("{$year}-{$month}-{$day}");
    $diff = (int)$base->diff($target)->format('%r%a');
    $idx = (54 + $diff) % 60;
    if ($idx < 0) $idx += 60;
    return $idx;
}

function getDayGanzhi($year, $month, $day) {
    $tg = ['甲','乙','丙','丁','戊','己','庚','辛','壬','癸'];
    $dz = ['子','丑','寅','卯','辰','巳','午','未','申','酉','戌','亥'];
    $idx = getDayGanzhiIdx($year, $month, $day);
    return $tg[$idx % 10] . $dz[$idx % 12];
}

function getConstellation($month, $day) {
    $names = ['摩羯','水瓶','双鱼','白羊','金牛','双子','巨蟹','狮子','处女','天秤','天蝎','射手'];
    $days = [20,19,21,20,21,22,23,23,23,24,23,22];
    if ($day >= $days[$month - 1]) {
        return $names[$month % 12];
    }
    return $names[($month - 1 + 12) % 12];
}

function getJieQiDay($year, $month, $isFirst) {
    $approx = [1=>[5,20],2=>[4,19],3=>[5,20],4=>[5,20],5=>[5,21],6=>[5,21],7=>[7,22],8=>[7,23],9=>[7,23],10=>[8,23],11=>[7,22],12=>[7,22]];
    if (!isset($approx[$month])) return 15;
    return $approx[$month][$isFirst ? 0 : 1];
}

function getMonthGanzhi($year, $month, $day) {
    $tg = ['甲','乙','丙','丁','戊','己','庚','辛','壬','癸'];
    $dz = ['子','丑','寅','卯','辰','巳','午','未','申','酉','戌','亥'];
    $tzhiMap = [1=>2,2=>3,3=>4,4=>5,5=>6,6=>7,7=>8,8=>9,9=>10,10=>11,11=>0,12=>1];
    $tradMonth = $month;
    $jqDay = getJieQiDay($year, $month, true);
    if ($day < $jqDay) {
        $tradMonth--;
        if ($tradMonth < 1) { $tradMonth = 12; $year--; }
    }
    $yGanIdx = (($year - 4) % 10 + 10) % 10;
    $mZhiIdx = $tzhiMap[$tradMonth];
    $mGanIdx = ($yGanIdx * 2 + $tradMonth) % 10;
    return $tg[$mGanIdx] . $dz[$mZhiIdx];
}

function getJianchu($y, $m, $d) {
    $names = ['建','除','满','平','定','执','破','危','成','收','开','闭'];
    $dz = ['子','丑','寅','卯','辰','巳','午','未','申','酉','戌','亥'];
    $tzhiMap = [1=>2,2=>3,3=>4,4=>5,5=>6,6=>7,7=>8,8=>9,9=>10,10=>11,11=>0,12=>1];
    $trad = $m;
    $jqDay = getJieQiDay($y, $m, true);
    if ($d < $jqDay) { $trad--; if ($trad < 1) $trad = 12; }
    $mZhiIdx = $tzhiMap[$trad];
    $dZhiIdx = getDayGanzhiIdx($y, $m, $d) % 12;
    $offset = ($dZhiIdx - $mZhiIdx + 12) % 12;
    return $names[$offset];
}

function getHuangdao($jianchuIdx) {
    $hd = ['黄道·青龙','黄道·明堂','黑道·天刑','黑道·朱雀','黄道·金匮','黄道·天德','黑道·白虎','黄道·玉堂','黑道·天牢','黑道·玄武','黄道·司命','黑道·勾陈'];
    if ($jianchuIdx < 0 || $jianchuIdx > 11) return $hd[0];
    return $hd[$jianchuIdx];
}

function get28Star($year, $month, $day) {
    $names = ['角木蛟','亢金龙','氐土貉','房日兔','心月狐','尾火虎','箕水豹','斗木獬','牛金牛','女土蝠','虚日鼠','危月燕','室火猪','壁水貐','奎木狼','娄金狗','胃土雉','昴日鸡','毕月乌','觜火猴','参水猿','井木犴','鬼金羊','柳土獐','星日马','张月鹿','翼火蛇','轸水蚓'];
    $base = new DateTime('2000-01-01');
    $target = new DateTime("{$year}-{$month}-{$day}");
    $diff = (int)$base->diff($target)->format('%r%a');
    $idx = (16 + $diff) % 28;
    if ($idx < 0) $idx += 28;
    return $names[$idx];
}

function getNayin($gz) {
    $nayin = [
        '甲子'=>'海中金','乙丑'=>'海中金','丙寅'=>'炉中火','丁卯'=>'炉中火','戊辰'=>'大林木','己巳'=>'大林木',
        '庚午'=>'路旁土','辛未'=>'路旁土','壬申'=>'剑锋金','癸酉'=>'剑锋金','甲戌'=>'山头火','乙亥'=>'山头火',
        '丙子'=>'涧下水','丁丑'=>'涧下水','戊寅'=>'城头土','己卯'=>'城头土','庚辰'=>'白蜡金','辛巳'=>'白蜡金',
        '壬午'=>'杨柳木','癸未'=>'杨柳木','甲申'=>'泉中水','乙酉'=>'泉中水','丙戌'=>'屋上土','丁亥'=>'屋上土',
        '戊子'=>'霹雳火','己丑'=>'霹雳火','庚寅'=>'松柏木','辛卯'=>'松柏木','壬辰'=>'长流水','癸巳'=>'长流水',
        '甲午'=>'沙中金','乙未'=>'沙中金','丙申'=>'山下火','丁酉'=>'山下火','戊戌'=>'平地木','己亥'=>'平地木',
        '庚子'=>'壁上土','辛丑'=>'壁上土','壬寅'=>'金箔金','癸卯'=>'金箔金','甲辰'=>'覆灯火','乙巳'=>'覆灯火',
        '丙午'=>'天河水','丁未'=>'天河水','戊申'=>'大驿土','己酉'=>'大驿土','庚戌'=>'钗钏金','辛亥'=>'钗钏金',
        '壬子'=>'桑柘木','癸丑'=>'桑柘木','甲寅'=>'大溪水','乙卯'=>'大溪水','丙辰'=>'沙中土','丁巳'=>'沙中土',
        '戊午'=>'天上火','己未'=>'天上火','庚申'=>'石榴木','辛酉'=>'石榴木','壬戌'=>'大海水','癸亥'=>'大海水',
    ];
    return $nayin[$gz] ?? '';
}

function getJianchuIdx($y, $m, $d) {
    $tzhiMap = [1=>2,2=>3,3=>4,4=>5,5=>6,6=>7,7=>8,8=>9,9=>10,10=>11,11=>0,12=>1];
    $trad = $m;
    $jqDay = getJieQiDay($y, $m, true);
    if ($d < $jqDay) { $trad--; if ($trad < 1) $trad = 12; }
    $mZhiIdx = $tzhiMap[$trad];
    $dZhiIdx = getDayGanzhiIdx($y, $m, $d) % 12;
    return ($dZhiIdx - $mZhiIdx + 12) % 12;
}

function getYiJi($jianchuIdx) {
    $yi = [
        ['祭祀','祈福','求嗣','开光','出行','赴任','会亲友','进人口','修造','动土','竖柱上梁','开市','交易','立券','牧养','冠笄','雕刻'],
        ['祭祀','解除','沐浴','捕捉','畋猎','治病','针灸','扫舍','破屋','坏垣','整手足甲','理发','安葬'],
        ['祈福','求嗣','进人口','裁衣','纳财','开市','交易','立券','纳畜','牧养','栽种','修饰垣墙','嫁娶','订盟'],
        ['修饰','平治道涂','造畜稠','安葬','纳财','修造','动土','栽种','纳畜','牧养','破土','启攒','立碑'],
        ['祈福','裁衣','嫁娶','纳采','安床','安门','挂匾','开市','交易','立券','纳财','栽种','纳畜','入殓','订盟','出行'],
        ['祭祀','捕捉','畋猎','纳财','纳畜','牧养','进人口','栽种','修造','动土','安床','解除','沐浴'],
        ['破屋','坏垣','求医','治病','解除','拆卸','整手足甲','剃头','扫舍'],
        ['祈福','安葬','祭祀','修造','动土','造庙','沐浴','酬神','造车器','造桥','开光','求嗣','入学'],
        ['开市','嫁娶','出行','纳采','安床','安门','挂匾','开光','祈福','求嗣','交易','立券','纳财','入宅','移徙'],
        ['祭祀','纳财','捕捉','畋猎','纳畜','牧养','进人口','栽种','修造','动土','安床','裁衣','冠笄'],
        ['开市','出行','嫁娶','入宅','安床','安门','开光','祈福','求嗣','交易','立券','纳财','栽种','移徙','拆卸'],
        ['安葬','修造','纳财','纳畜','牧养','进人口','栽种','动土','破土','入殓','移柩','立碑','启攒'],
    ];
    $ji = [
        ['开仓','掘井','伐木','畋猎','乘船','渡水','嫁娶','词讼'],
        ['嫁娶','出行','移徙','入宅','安床','开市','上梁','词讼'],
        ['动土','安葬','开仓','造庙','入宅','修坟','破土','词讼'],
        ['出行','嫁娶','移徙','入宅','开市','安葬','词讼','掘井'],
        ['词讼','出行','修造','动土','赴任','安葬','掘井','行丧'],
        ['开市','出行','嫁娶','移徙','入宅','安葬','修造','词讼'],
        ['嫁娶','开市','安葬','入宅','移徙','出行','修造','动土','词讼'],
        ['登高','出行','嫁娶','入宅','移徙','安床','开市','词讼'],
        ['词讼','安葬','动土','修造','行丧','破土','伐木'],
        ['安葬','出行','嫁娶','移徙','入宅','开市','词讼','掘井'],
        ['动土','安葬','修造','破土','行丧','词讼','栽种'],
        ['开市','出行','嫁娶','移徙','入宅','安床','开光','掘井','词讼'],
    ];
    $idx = $jianchuIdx % 12;
    return ['yi'=>$yi[$idx], 'ji'=>$ji[$idx]];
}

function getSolarLunarHolidays($year) {
    $holidays = [
        "$year-01-01"=>'元旦',"$year-01-10"=>'中国人民警察节',
        "$year-02-02"=>'世界湿地日',"$year-02-14"=>'情人节',
        "$year-03-01"=>'国际海豹日',"$year-03-03"=>'全国爱耳日',"$year-03-05"=>'学雷锋日',
        "$year-03-08"=>'妇女节',"$year-03-12"=>'植树节',"$year-03-14"=>'白色情人节',
        "$year-03-15"=>'消费者权益日',"$year-03-21"=>'世界森林日',"$year-03-22"=>'世界水日',
        "$year-03-23"=>'世界气象日',"$year-03-24"=>'世界防治结核病日',
        "$year-04-01"=>'愚人节',"$year-04-02"=>'国际儿童图书日',"$year-04-07"=>'世界卫生日',
        "$year-04-11"=>'世界帕金森病日',"$year-04-15"=>'国家安全教育日',
        "$year-04-22"=>'世界地球日',"$year-04-23"=>'世界读书日',"$year-04-26"=>'世界知识产权日',
        "$year-05-01"=>'劳动节',"$year-05-04"=>'青年节',"$year-05-08"=>'世界红十字日',
        "$year-05-12"=>'护士节',"$year-05-15"=>'国际家庭日',"$year-05-17"=>'世界电信日',
        "$year-05-18"=>'国际博物馆日',"$year-05-20"=>'全国学生营养日',"$year-05-21"=>'世界电信日',
        "$year-05-25"=>'心理健康日',"$year-05-27"=>'上海解放日',"$year-05-31"=>'世界无烟日',
        "$year-06-01"=>'儿童节',"$year-06-05"=>'世界环境日',"$year-06-06"=>'全国爱眼日',
        "$year-06-08"=>'世界海洋日',"$year-06-14"=>'世界献血日',
        "$year-06-25"=>'全国土地日',"$year-06-26"=>'国际禁毒日',
        "$year-07-01"=>'建党节/香港回归',"$year-07-06"=>'世界接吻日',"$year-07-07"=>'抗战纪念日',
        "$year-07-11"=>'世界人口日',"$year-07-30"=>'国际友谊日',
        "$year-08-01"=>'建军节',"$year-08-08"=>'全民健身日',"$year-08-12"=>'国际青年日',
        "$year-08-15"=>'日本投降日',"$year-08-19"=>'中国医师节',
        "$year-09-01"=>'开学季',"$year-09-03"=>'抗战胜利纪念日',"$year-09-05"=>'中华慈善日',
        "$year-09-08"=>'国际扫盲日',"$year-09-10"=>'教师节',"$year-09-14"=>'世界清洁地球日',
        "$year-09-16"=>'国际臭氧层保护日',"$year-09-18"=>'九一八纪念日',
        "$year-09-20"=>'全国爱牙日',"$year-09-21"=>'国际和平日',"$year-09-22"=>'世界无车日',
        "$year-09-27"=>'世界旅游日',"$year-09-28"=>'孔子诞辰',
        "$year-10-01"=>'国庆节',"$year-10-02"=>'国庆节',"$year-10-03"=>'国庆节',
        "$year-10-04"=>'世界动物日',"$year-10-05"=>'世界教师日',
        "$year-10-10"=>'辛亥革命纪念日/世界精神卫生日',"$year-10-13"=>'中国少年先锋队诞辰',
        "$year-10-15"=>'国际盲人节',"$year-10-16"=>'世界粮食日',"$year-10-17"=>'国家扶贫日',
        "$year-10-22"=>'世界传统医药日',"$year-10-24"=>'联合国日',"$year-10-25"=>'抗美援朝纪念日',
        "$year-10-29"=>'世界银屑病日',"$year-10-31"=>'世界勤俭日',
        "$year-11-01"=>'万圣节',"$year-11-08"=>'中国记者节',"$year-11-09"=>'消防宣传日',
        "$year-11-10"=>'世界青年节',"$year-11-11"=>'双十一/光棍节',
        "$year-11-14"=>'世界糖尿病日',"$year-11-16"=>'国际宽容日',
        "$year-11-17"=>'世界学生日',"$year-11-19"=>'世界厕所日',
        "$year-11-20"=>'世界儿童日',"$year-11-21"=>'世界电视日',
        "$year-12-01"=>'世界艾滋病日',"$year-12-02"=>'全国交通安全日',
        "$year-12-03"=>'国际残疾人日',"$year-12-04"=>'国家宪法日',
        "$year-12-05"=>'国际志愿者日',"$year-12-09"=>'世界足球日',
        "$year-12-10"=>'世界人权日',"$year-12-12"=>'西安事变纪念日',
        "$year-12-13"=>'国家公祭日',"$year-12-20"=>'澳门回归日',
        "$year-12-22"=>'国际篮球日',"$year-12-24"=>'平安夜',"$year-12-25"=>'圣诞节',
        "$year-12-26"=>'毛主席诞辰',
    ];
    $jqDay = getJieQiDay($year, 4, true);
    $holidays[sprintf('%04d-04-%02d', $year, $jqDay)] = '清明节';
    $lunarMap = [
        [1,1,'春节'],[1,2,'春节假期'],[1,3,'春节假期'],
        [1,15,'元宵节'],[2,2,'龙抬头'],[3,3,'上巳节'],
        [5,5,'端午节'],[6,6,'天贶节'],[7,7,'七夕节'],[7,15,'中元节'],
        [8,15,'中秋节'],[9,9,'重阳节'],[12,8,'腊八节'],[12,23,'小年'],
    ];
    for ($m = 1; $m <= 12; $m++) {
        $dim = (int)(new DateTime("$year-$m-01"))->format('t');
        for ($d = 1; $d <= $dim; $d++) {
            $lu = solarToLunar($year, $m, $d);
            $ds = sprintf('%04d-%02d-%02d', $year, $m, $d);
            foreach ($lunarMap as $lm) {
                if ((int)$lu['m'] === $lm[0] && (int)$lu['d'] === $lm[1] && !isset($holidays[$ds])) {
                    $holidays[$ds] = $lm[2];
                }
            }
            if ((int)$lu['m'] === 12) {
                $dimLunar = lunarDaysInMonth((int)$lu['y'], 12);
                if ((int)$lu['d'] === $dimLunar && !isset($holidays[$ds])) {
                    $holidays[$ds] = '除夕';
                }
            }
        }
    }
    return $holidays;
}

// ============ Extended Calculations ============
function getXunkong($dayGzIdx) {
    $dz = ['子','丑','寅','卯','辰','巳','午','未','申','酉','戌','亥'];
    $xunStart = $dayGzIdx - ($dayGzIdx % 10);
    return $dz[($xunStart + 10) % 12] . $dz[($xunStart + 11) % 12];
}

function getChong($dayGzIdx) {
    $dz = ['子','丑','寅','卯','辰','巳','午','未','申','酉','戌','亥'];
    $sx = ['鼠','牛','虎','兔','龙','蛇','马','羊','猴','鸡','狗','猪'];
    $dayDz = $dayGzIdx % 12;
    $chongDz = ($dayDz + 6) % 12;
    return '冲' . $sx[$chongDz] . '(' . $dz[$chongDz] . ')煞' . ['南','东','北','西','南','东','北','西','南','东','北','西'][$chongDz];
}

function getPengzu($dayGzIdx) {
    $tg = ['甲','乙','丙','丁','戊','己','庚','辛','壬','癸'];
    $dz = ['子','丑','寅','卯','辰','巳','午','未','申','酉','戌','亥'];
    $pt = ['甲'=>'不开仓财物耗散','乙'=>'不栽植千株不长','丙'=>'不修灶必见灾殃','丁'=>'不剃头头必生疮','戊'=>'不受田田主不祥','己'=>'不破券二比并亡','庚'=>'不经络织机虚张','辛'=>'不合酱主人不尝','壬'=>'不泱水更难提防','癸'=>'不词讼理弱敌强'];
    $pd = ['子'=>'不问卜自惹祸殃','丑'=>'不冠带主不还乡','寅'=>'不祭祀神鬼不尝','卯'=>'不穿井水泉不香','辰'=>'不哭泣必主重丧','巳'=>'不远行财物伏藏','午'=>'不苫盖屋主更张','未'=>'不服药毒气入肠','申'=>'不安床鬼祟入房','酉'=>'不宴客醉坐颠狂','戌'=>'不吃犬作怪上床','亥'=>'不嫁娶不利新郎'];
    return ($pt[$tg[$dayGzIdx % 10]] ?? '') . '，' . ($pd[$dz[$dayGzIdx % 12]] ?? '');
}

function getTaiyuan($monthGz) {
    $tg = ['甲','乙','丙','丁','戊','己','庚','辛','壬','癸'];
    $dz = ['子','丑','寅','卯','辰','巳','午','未','申','酉','戌','亥'];
    $mTg = array_search($monthGz[0], $tg);
    $mDz = mb_strpos('子丑寅卯辰巳午未申酉戌亥', $monthGz[1]) !== false ? array_search($monthGz[1], $dz) : 0;
    return $tg[($mTg + 1) % 10] . $dz[($mDz + 3) % 12];
}

function get9StarDay($dayGzIdx) {
    $names = ['一白水天枢','二黑土天璇','三碧木天权','四绿木天权','五黄土玉衡','六白金开阳','七赤金摇光','八白土洞明','九紫火隐光'];
    $idx = (9 - ($dayGzIdx % 9)) % 9;
    if ($idx === 0) $idx = 9;
    return $names[$idx - 1];
}

function get9StarYear($year) {
    $names = ['一白水天枢','二黑土天璇','三碧木天权','四绿木天权','五黄土玉衡','六白金开阳','七赤金摇光','八白土洞明','九紫火隐光'];
    $idx = (9 - (($year - 4) % 9)) % 9;
    if ($idx === 0) $idx = 9;
    return $names[$idx - 1];
}

function getJishenXiongsha($dayGzIdx) {
    $jishen = [
        ['天德','天恩','三合','天喜','天医','玉堂','不将'],
        ['天德','月德','天恩','母仓','天医','金匮','鸣吠'],
        ['天德','天恩','月德','天巫','天后','驿马','福生'],
        ['天恩','母仓','三合','天喜','天医','圣心','普护'],
        ['天德','天恩','月德','不将','续世','六合','要安'],
        ['天恩','母仓','五合','鸣吠','天马','天德','月德'],
        ['天德','月德','天恩','玉堂','敬安','枝德','鸣吠'],
        ['天恩','母仓','天德','明堂','天喜','续世','阴德'],
        ['天德','天恩','月德','天赦','四相','王日','要安'],
        ['天恩','母仓','天德','天马','天喜','圣心','不将'],
        ['天恩','天德','月德','三合','天喜','临日','福生'],
        ['天德','天恩','母仓','宝光','阳德','天巫','福生'],
    ];
    $xiongsha = [
        ['厌对','招摇','四击','归忌','天火','朱雀'],
        ['月破','大耗','天刑','重日','游祸','血忌'],
        ['天火','地囊','天狗','招摇','厌对','五虚'],
        ['天罡','月煞','地囊','天刑','游祸','五虚'],
        ['天刑','天罡','厌对','招摇','四击','血忌'],
        ['灾煞','天火','大耗','重日','游祸','四废'],
        ['天牢','月煞','大耗','五虚','八风','击厌'],
        ['天火','月厌','招摇','小耗','往亡','四击'],
        ['天刑','小耗','天火','游祸','复日','五虚'],
        ['天罡','月破','大耗','五虚','四击','九空'],
        ['灾煞','天火','厌对','四击','八风','天狗'],
        ['月破','大耗','天刑','五虚','八风','血忌'],
    ];
    $idx = $dayGzIdx % 12;
    return ['jishen'=>$jishen[$idx], 'xiongsha'=>$xiongsha[$idx]];
}

function getShichenJiXiong($dayGzIdx) {
    $tg = ['甲','乙','丙','丁','戊','己','庚','辛','壬','癸'];
    $dzN = ['子','丑','寅','卯','辰','巳','午','未','申','酉','戌','亥'];
    $jsData = [
        ['天刑','天牢'],['朱雀','天德'],['天德','宝光'],['少微','天德'],
        ['司命','五富'],['天德','临官'],['青龙','明堂'],['明堂','天刑'],
        ['天牢','天德'],['朱雀','少微'],['金匮','司命'],['勾陈','天牢'],
    ];
    $xsData = [
        ['青龙','五鬼'],['玉堂','天牢'],['白虎','天德'],['玄武','朱雀'],
        ['勾陈','地兵'],['朱雀','天刑'],['天刑','勾陈'],['白虎','玉堂'],
        ['天德','天牢'],['勾陈','白虎'],['天德','勾陈'],['玄武','朱雀'],
    ];
    $result = [];
    for ($i = 0; $i < 12; $i++) {
        $hGanIdx = ($dayGzIdx % 10 * 2 + $i) % 10;
        $gzhour = $tg[$hGanIdx] . $dzN[$i];
        $h = $i * 2 - 1; if ($h < 0) $h = 23;
        $timeStr = sprintf('%02d:00-%02d:59', $h, ($h + 2) % 24);
        $result[] = ['name'=>$dzN[$i].'时', 'time'=>$timeStr, 'gz'=>$gzhour, 'ji'=>($i%2===0), 'jishen'=>$jsData[$i], 'xiongsha'=>$xsData[$i]];
    }
    return $result;
}

// ============ Page Setup ============
$now = new DateTime();
$today = (int)$now->format('j');
$thisMonth = (int)$now->format('m');
$thisYear = (int)$now->format('Y');

$selYear = isset($selYear) ? (int)$selYear : $thisYear;
$selMonth = isset($selMonth) ? (int)$selMonth : $thisMonth;
$selDay = isset($selDay) ? (int)$selDay : $today;
$dispYear = $selYear;
$dispMonth = $selMonth;

$firstDay = new DateTime("{$dispYear}-{$dispMonth}-01");
$firstDow = (int)$firstDay->format('w');
$daysInMonth = (int)$firstDay->format('t');

$todayLunar = solarToLunar($selYear, $selMonth, $selDay);
$todayGzIdx = getDayGanzhiIdx($selYear, $selMonth, $selDay);
$todayGz = getDayGanzhi($selYear, $selMonth, $selDay);
$yearGz = getCYear($selYear);
$monthGz = getMonthGanzhi($selYear, $selMonth, $selDay);
$constellation = getConstellation($selMonth, $selDay);
$jianchu = getJianchu($selYear, $selMonth, $selDay);
$jianchuIdx = array_search($jianchu, ['建','除','满','平','定','执','破','危','成','收','开','闭']);
if ($jianchuIdx === false) $jianchuIdx = 0;
$huangdao = getHuangdao($jianchuIdx);
$star28 = get28Star($selYear, $selMonth, $selDay);
$nayinYear = getNayin($yearGz);
$nayinMonth = getNayin($monthGz);
$nayinDay = getNayin($todayGz);
$yiJi = getYiJi($jianchuIdx);
$shichenData = getShichenJiXiong($todayGzIdx);
$xunkong = getXunkong($todayGzIdx);
$chongSha = getChong($todayGzIdx);
$pengzu = getPengzu($todayGzIdx);
$taiyuan = getTaiyuan($monthGz);
$star9Day = get9StarDay($todayGzIdx);
$star9Year = get9StarYear($selYear);
$jishenXiongsha = getJishenXiongsha($todayGzIdx);
$holidays = getSolarLunarHolidays($selYear);
$restDays = array_flip(($holidayRestWork['rest'] ?? []));
$workDays = array_flip(($holidayRestWork['work'] ?? []));

$todayJieQiName = '';
$todayJieQiDays = 0;
for ($mIdx = 1; $mIdx <= 12; $mIdx++) {
    for ($isF = 0; $isF <= 1; $isF++) {
        $jqDay = getJieQiDay($selYear, $mIdx, $isF === 0);
        $jqDateStr = sprintf('%04d-%02d-%02d', $selYear, $mIdx, $jqDay);
        $jqDate = new DateTime($jqDateStr);
        if ($jqDate >= $now) {
            $idx = ($mIdx - 1) * 2 + $isF;
            if ($idx >= 0 && $idx < 24) {
                $todayJieQiName = $jieqiAllNames[$idx];
                $todayJieQiDays = (int)$now->diff($jqDate)->format('%r%a');
                if ($todayJieQiDays < 0) $todayJieQiDays = 0;
            }
            break 2;
        }
    }
}

$prevM = $dispMonth - 1; $prevY = $dispYear;
if ($prevM < 1) { $prevM = 12; $prevY--; }
$nextM = $dispMonth + 1; $nextY = $dispYear;
if ($nextM > 12) { $nextM = 1; $nextY++; }
?>

<style>
:root{--wl-primary:#2563eb;--wl-card:#fff;--wl-border:#e2e8f0;--wl-text:#1e293b;--wl-muted:#94a3b8;--wl-yi:#16a34a;--wl-ji:#dc2626}
body.theme-dark{--wl-card:#1e293b;--wl-border:#334155;--wl-text:#e2e8f0;--wl-muted:#64748b}
.wl-wrap{max-width:1200px;margin:0 auto}
.wl-hero{display:flex;gap:24px;margin-bottom:20px;background:var(--wl-card);border-radius:1rem;border:1px solid var(--wl-border);overflow:hidden}
.wl-hero-left{flex:0 0 300px;padding:32px 24px;background:linear-gradient(135deg,#1e3a5f 0%,#2563eb 100%);color:#fff}
.wl-hero-day-num{font-size:4rem;font-weight:800;line-height:1}
.wl-hero-ym{font-size:1.1rem;opacity:.85;margin-top:6px}
.wl-hero-week{font-size:1.2rem;font-weight:600;margin-top:6px}
.wl-hero-lunar{font-size:1.1rem;opacity:.9;margin-top:14px;padding-top:10px;border-top:1px solid rgba(255,255,255,.15)}
.wl-hero-ganzhi{font-size:.9rem;opacity:.75;margin-top:8px}
.wl-hero-badges{display:flex;flex-wrap:wrap;gap:8px;margin-top:18px}
.wl-badge{display:inline-flex;align-items:center;gap:4px;padding:4px 12px;border-radius:20px;font-size:.85rem;background:rgba(255,255,255,.18)}
.wl-badge-accent{background:rgba(251,191,36,.3)}
.wl-badge-good{background:rgba(22,163,74,.3)}
.wl-badge-warn{background:rgba(234,179,8,.3)}
.wl-hero-notices{margin-top:18px;font-size:.88rem;padding-top:12px;border-top:1px solid rgba(255,255,255,.15)}
.wl-hero-notices>div{margin-top:4px;opacity:.85}
.wl-hero-right{flex:1;padding:20px}
.wl-cal-nav{display:flex;align-items:center;gap:10px;margin-bottom:14px}
.wl-cal-nav a{text-decoration:none;color:var(--wl-text);padding:5px 14px;border-radius:6px;border:1px solid var(--wl-border);font-size:.88rem}
.wl-cal-nav a:hover{background:rgba(37,99,235,.08)}
.wl-cal-nav .wl-cal-title{font-size:1.1rem;font-weight:700}
.wl-cal-grid{display:grid;grid-template-columns:repeat(7,1fr);gap:3px}
.wl-cal-cell{aspect-ratio:1;display:flex;flex-direction:column;align-items:center;justify-content:center;border-radius:.5rem;font-size:1rem;cursor:pointer;transition:all .15s}
.wl-cal-header{font-weight:600;font-size:.9rem;color:var(--wl-muted);aspect-ratio:auto;padding:8px 2px}
.wl-cal-cell.today{background:var(--wl-primary);color:#fff;font-weight:700}
.wl-cal-cell.selected{background:rgba(37,99,235,0.15);font-weight:700}
.wl-cal-cell.today.selected{background:var(--wl-primary)}
.wl-cal-cell.today .wl-lunar{color:rgba(255,255,255,.7)}
.wl-cal-cell.weekend{color:#dc2626}
.wl-cal-cell.today.weekend{color:#fff}
.wl-cal-cell.festival .wl-lunar{font-weight:600}
.wl-cal-cell .wl-solar{font-size:1.05rem;font-weight:500}
.wl-cal-cell .wl-lunar{font-size:.75rem;color:var(--wl-muted);line-height:1.2;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100%}
.wl-rest-tag{position:absolute;top:1px;right:1px;font-size:0.55rem;padding:0 3px;border-radius:3px;background:#16a34a;color:#fff;line-height:1.4;z-index:1}
.wl-work-tag{position:absolute;top:1px;right:1px;font-size:0.55rem;padding:0 3px;border-radius:3px;background:#dc2626;color:#fff;line-height:1.4;z-index:1}
.wl-task-dot{position:absolute;top:3px;right:3px;width:6px;height:6px;border-radius:50%;background:#f59e0b}
.wl-cal-cell{position:relative}
.wl-toolbar{display:flex;flex-wrap:wrap;gap:12px;margin-bottom:20px;align-items:center}
.wl-toolbar select{padding:7px 12px;border-radius:6px;border:1px solid var(--wl-border);background:var(--wl-card);color:var(--wl-text);font-size:.88rem}
.wl-toolbar .wl-btn{padding:7px 16px;border-radius:6px;border:1px solid var(--wl-border);background:var(--wl-card);color:var(--wl-text);cursor:pointer;font-size:.88rem}
.wl-toolbar .wl-btn-primary{background:var(--wl-primary);color:#fff;border-color:var(--wl-primary)}
.wl-toolbar .wl-btn:hover{opacity:.85}
.wl-yiji{display:flex;gap:16px;margin-bottom:20px}
.wl-yi-col,.wl-ji-col{flex:1;background:var(--wl-card);border-radius:.75rem;border:1px solid var(--wl-border);padding:14px 16px}
.wl-yi-head{font-weight:700;color:var(--wl-yi);margin-bottom:8px;font-size:.95rem}
.wl-ji-head{font-weight:700;color:var(--wl-ji);margin-bottom:8px;font-size:.95rem}
.wl-yi-items,.wl-ji-items{display:flex;flex-wrap:wrap;gap:6px}
.wl-tag{display:inline-block;padding:2px 10px;border-radius:12px;font-size:.78rem}
.wl-tag-yi{background:rgba(22,163,74,.1);color:var(--wl-yi)}
.wl-tag-ji{background:rgba(220,38,38,.1);color:var(--wl-ji)}
.wl-details{display:grid;grid-template-columns:repeat(3,1fr);gap:12px;margin-bottom:20px}
.wl-detail-card{background:var(--wl-card);border-radius:.75rem;border:1px solid var(--wl-border);padding:14px 16px}
.wl-detail-title{font-weight:700;font-size:.85rem;margin-bottom:8px;color:var(--wl-text)}
.wl-detail-row{display:flex;justify-content:space-between;font-size:.82rem;padding:3px 0}
.wl-detail-key{color:var(--wl-muted)}
.wl-detail-val{font-weight:500;color:var(--wl-text)}
.wl-detail-val em{font-style:normal;font-size:.75rem;color:var(--wl-muted);margin-left:4px}
.wl-tabs{margin-bottom:20px}
.wl-tab-nav{display:flex;gap:0;border-bottom:2px solid var(--wl-border);margin-bottom:0}
.wl-tab-btn{padding:8px 20px;border:none;background:none;color:var(--wl-muted);cursor:pointer;font-size:.85rem;font-weight:500;border-bottom:2px solid transparent;margin-bottom:-2px;transition:all .2s}
.wl-tab-btn:hover{color:var(--wl-text)}
.wl-tab-btn-active{color:var(--wl-primary);border-bottom-color:var(--wl-primary)}
.wl-tab-panel{display:none;padding:16px 0}
.wl-tab-panel-active{display:block}
.wl-table{width:100%;border-collapse:collapse;font-size:.82rem}
.wl-table th{background:rgba(37,99,235,.06);padding:8px 10px;text-align:left;font-weight:600;color:var(--wl-text);border-bottom:1px solid var(--wl-border)}
.wl-table td{padding:8px 10px;border-bottom:1px solid var(--wl-border);color:var(--wl-text)}
.wl-clock{font-family:monospace;font-size:.9rem}
@media(max-width:768px){.wl-hero{flex-direction:column}.wl-hero-left{flex:auto;padding:20px 16px}.wl-hero-day-num{font-size:2.5rem}.wl-details{grid-template-columns:repeat(2,1fr)}}
</style>

<div class="wl-wrap">

<div class="wl-hero">
    <div class="wl-hero-left">
        <div class="wl-hero-day-num"><?= $selDay ?></div>
        <div class="wl-hero-ym"><?= $selYear ?>年<?= str_pad($selMonth, 2, '0', STR_PAD_LEFT) ?>月</div>
        <div class="wl-hero-week">星期<?= $weekNames[(int)(new DateTime("{$selYear}-{$selMonth}-{$selDay}"))->format('w')] ?> <span class="wl-clock" id="wl-live-clock"><?= $now->format('H:i:s') ?></span></div>
        <div class="wl-hero-lunar"><?= $lunarMonthNames[$todayLunar['m'] - 1] ?>月<?= $lunarDayNames[$todayLunar['d'] - 1] ?></div>
        <div class="wl-hero-ganzhi"><?= $yearGz ?>年 <?= $monthGz ?>月 <?= $todayGz ?>日</div>
        <div class="wl-hero-badges">
            <span class="wl-badge wl-badge-accent">🐲 <?= getShengxiao($selYear) ?></span>
            <span class="wl-badge">♑ <?= $constellation ?>座</span>
            <span class="wl-badge wl-badge-warn"><?= $jianchu ?></span>
            <span class="wl-badge wl-badge-good"><?= $huangdao ?></span>
        </div>
        <div style="margin-top:16px;font-size:.88rem;opacity:.7">
            二十八宿：<?= $star28 ?>
        </div>
        <?php if ($todayJieQiName !== ''): ?>
        <div class="wl-hero-notices">
            <div>🌿 下个节气：<?= $todayJieQiName ?> （<?= $todayJieQiDays === 0 ? '今天' : '还有' . $todayJieQiDays . '天' ?>）</div>
        </div>
        <?php endif; ?>
    </div>
    <div class="wl-hero-right">
        <div class="wl-cal-nav">
            <a href="?route=toolbox-calendar&ym=<?= $prevY ?>-<?= str_pad($prevM,2,'0',STR_PAD_LEFT) ?>">&laquo; <?= $prevM ?>月</a>
            <span class="wl-cal-title"><?= $dispYear ?>年 <?= $dispMonth ?>月</span>
            <a href="?route=toolbox-calendar&ym=<?= $nextY ?>-<?= str_pad($nextM,2,'0',STR_PAD_LEFT) ?>"><?= $nextM ?>月 &raquo;</a>
            <a href="?route=toolbox-calendar" class="wl-btn-primary" style="margin-left:auto;">今天</a>
        </div>
        <div class="wl-cal-grid">
            <?php foreach ($weekNames as $wn): ?>
                <div class="wl-cal-header"><?= $wn ?></div>
            <?php endforeach; ?>
            <?php
            $cellDay = 1;
            $totalCells = $firstDow + $daysInMonth;
            $totalRows = (int)ceil($totalCells / 7);
            // 构建当月节气表
            $monthJieQi = [];
            for ($jqM = 1; $jqM <= 12; $jqM++) {
                for ($jqF = 0; $jqF <= 1; $jqF++) {
                    $jqDay = getJieQiDay($dispYear, $jqM, $jqF === 0);
                    $jqKey = sprintf('%04d-%02d-%02d', $dispYear, $jqM, $jqDay);
                    $jqIdx = ($jqM - 1) * 2 + $jqF;
                    if ($jqIdx >= 0 && $jqIdx < 24) {
                        $monthJieQi[$jqKey] = $jieqiAllNames[$jqIdx];
                    }
                }
            }
            for ($r = 0; $r < $totalRows; $r++):
                for ($c = 0; $c < 7; $c++):
                    $ci = $r * 7 + $c;
                    if ($ci < $firstDow):
                        $pM = $dispMonth - 1; $pY = $dispYear;
                        if ($pM < 1) { $pM = 12; $pY--; }
                        $pDim = (int)(new DateTime("{$pY}-{$pM}-01"))->format('t');
                        $pd = $pDim - $firstDow + $ci + 1;
            ?>
                <div class="wl-cal-cell other-month"><span class="wl-solar"><?= $pd ?></span></div>
            <?php elseif ($cellDay > $daysInMonth):
                        $nd = $ci - $firstDow - $daysInMonth + 1;
            ?>
                <div class="wl-cal-cell other-month"><span class="wl-solar"><?= $nd ?></span></div>
            <?php else:
                        $isToday = ($dispYear === $thisYear && $dispMonth === $thisMonth && $cellDay === $today);
                        $isSel = ($dispYear === $selYear && $dispMonth === $selMonth && $cellDay === $selDay);
                        $isWknd = ($c === 0 || $c === 6);
                        $cl = solarToLunar($dispYear, $dispMonth, $cellDay);
                        $ld = (int)$cl['d'];
                        $lText = isset($lunarDayNames[$ld - 1]) ? $lunarDayNames[$ld - 1] : '';
                        if ($ld === 1) $lText = ($cl['leap'] ? '闰' : '') . (isset($lunarMonthNames[$cl['m'] - 1]) ? $lunarMonthNames[$cl['m'] - 1] : '') . '月';
                        $fullDate = sprintf('%04d-%02d-%02d', $dispYear, $dispMonth, $cellDay);
                        $holiday = isset($holidays[$fullDate]) ? $holidays[$fullDate] : '';
                        $jieQi = isset($monthJieQi[$fullDate]) ? $monthJieQi[$fullDate] : '';
                        // 节气优先显示，节日同天只显示节气
                        if ($jieQi !== '') {
                            $lText = $jieQi;
                        } elseif ($holiday !== '') {
                            $lText = $holiday;
                        }
                        $hasTask = false;
                        foreach (($taskMonthStats ?? []) as $ts) { if ($ts['task_date'] === $fullDate) { $hasTask = true; break; } }
                        $cellClass = 'wl-cal-cell';
                        if ($isToday) $cellClass .= ' today';
                        if ($isSel && !$isToday) $cellClass .= ' selected';
                        $isRestDay = isset($restDays[$fullDate]);
                        $isWorkDay = isset($workDays[$fullDate]);
                        // 调班日取消周末标记
                        $isEffectiveWeekend = $isWknd;
                        if ($isWorkDay) {
                            $isEffectiveWeekend = false;
                        }
                        if ($isEffectiveWeekend || $isRestDay) $cellClass .= ' weekend';
                        if ($jieQi !== '' || $holiday !== '') $cellClass .= ' festival';
                        // 大节日红色，小众节日蓝色，节气蓝色
                        $majorKw = ['元旦','春节','清明','劳动节','端午','中秋','国庆','除夕'];
                        $isMajor = false;
                        if ($holiday !== '') {
                            foreach ($majorKw as $mk) { if (strpos($holiday, $mk) !== false) { $isMajor = true; break; } }
                        }
                        if ($jieQi !== '') {
                            $lunarStyle = 'color:#2563eb;font-weight:600';
                        } elseif ($isMajor) {
                            $lunarStyle = 'color:#dc2626;font-weight:600';
                        } elseif ($holiday !== '') {
                            $lunarStyle = 'color:#2563eb;font-weight:500';
                        } else {
                            $lunarStyle = '';
                        }
            ?>
                <div class="<?= $cellClass ?>" data-date="<?= $fullDate ?>">
                    <span class="wl-solar"><?= $cellDay ?></span>
                    <span class="wl-lunar" style="<?= $lunarStyle ?>"><?= $lText ?></span>
                    <?php if ($isRestDay): ?><span class="wl-rest-tag">休</span><?php endif; ?>
                    <?php if ($isWorkDay): ?><span class="wl-work-tag">班</span><?php endif; ?>
                    <?php if ($hasTask): ?><span class="wl-task-dot"></span><?php endif; ?>
                </div>
            <?php $cellDay++; endif; ?>
            <?php endfor; endfor; ?>
        </div>
    </div>
</div>

<?php
function getHistoryToday($month, $day) {
    static $all = null;
    if ($all === null) {
        $dataFile = __DIR__ . '/history_data.php';
        $all = file_exists($dataFile) ? require $dataFile : [];
    }
    $key = sprintf('%02d-%02d', $month, $day);
    return $all[$key] ?? [];
}

$historyItems = getHistoryToday($selMonth, $selDay);
?>
<?php if (!empty($historyItems)): ?>
<div class="card border-0 shadow-sm mb-4" style="background:var(--wl-card);border:1px solid var(--wl-border);border-radius:.75rem;">
    <div style="padding:14px 16px;border-bottom:1px solid var(--wl-border);font-weight:700;font-size:.95rem;">
         历史上的今天（<?= $selMonth ?>月<?= $selDay ?>日）
    </div>
    <div style="padding:12px 16px;max-height:260px;overflow-y:auto;">
        <?php foreach ($historyItems as $h): ?>
            <div style="padding:6px 0;border-bottom:1px solid rgba(0,0,0,.05);font-size:.88rem;">
                <span style="color:var(--wl-primary);font-weight:600;"><?= $h[0] ?>年</span>
                <span style="margin-left:8px;"><?= $h[1] ?></span>
            </div>
        <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>

<div class="wl-toolbar">
    <span style="font-weight:600;font-size:.85rem;">📅 日期跳转</span>
    <select id="wl-jump-year">
        <?php for ($y = 1901; $y <= 2099; $y++): ?>
            <option value="<?= $y ?>" <?= $y === $selYear ? 'selected' : '' ?>><?= $y ?>年</option>
        <?php endfor; ?>
    </select>
    <select id="wl-jump-month">
        <?php for ($mi = 1; $mi <= 12; $mi++): ?>
            <option value="<?= $mi ?>" <?= $mi === $selMonth ? 'selected' : '' ?>><?= $mi ?>月</option>
        <?php endfor; ?>
    </select>
    <select id="wl-jump-day">
        <?php for ($di = 1; $di <= 31; $di++): ?>
            <option value="<?= $di ?>" <?= $di === $selDay ? 'selected' : '' ?>><?= $di ?>日</option>
        <?php endfor; ?>
    </select>
    <button class="wl-btn wl-btn-primary" onclick="jumpDate()">跳转</button>
    <button class="wl-btn" onclick="location.href='?route=toolbox-calendar'">今天</button>
    <span style="font-weight:600;font-size:.85rem;margin-left:16px">🔍 吉日</span>
    <select id="wl-lucky-select">
        <option value="">选择关键词</option>
        <option value="嫁娶">嫁娶/结婚</option>
        <option value="搬家">搬家/移徙</option>
        <option value="开业">开业/开市</option>
        <option value="入宅">入宅/买房</option>
        <option value="安葬">安葬</option>
        <option value="祭祀">祭祀</option>
        <option value="祈福">祈福</option>
        <option value="动土">动土</option>
        <option value="出行">出行</option>
        <option value="修造">修造/装修</option>
        <option value="纳采">纳采/订婚</option>
        <option value="入学">入学</option>
        <option value="栽种">栽种/种树</option>
        <option value="买车">买车/提车</option>
    </select>
    <button class="wl-btn wl-btn-primary" onclick="searchLucky()">查询</button>
    <button class="wl-btn" onclick="resetLucky()">重置</button>
</div>
<div id="wl-lucky-result" style="margin-bottom:16px"></div>

<div class="wl-yiji">
    <div class="wl-yi-col">
        <div class="wl-yi-head">✅ 宜</div>
        <div class="wl-yi-items">
            <?php foreach ($yiJi['yi'] as $yItem): ?>
                <span class="wl-tag wl-tag-yi"><?= $yItem ?></span>
            <?php endforeach; ?>
        </div>
    </div>
    <div class="wl-ji-col">
        <div class="wl-ji-head">❌ 忌</div>
        <div class="wl-ji-items">
            <?php foreach ($yiJi['ji'] as $jItem): ?>
                <span class="wl-tag wl-tag-ji"><?= $jItem ?></span>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<div class="wl-details" style="grid-template-columns:repeat(3,1fr)">
    <div class="wl-detail-card">
        <div class="wl-detail-title">🔥 纳音五行</div>
        <div class="wl-detail-row"><span class="wl-detail-key">年柱</span><span class="wl-detail-val"><?= $yearGz ?> <em><?= $nayinYear ?></em></span></div>
        <div class="wl-detail-row"><span class="wl-detail-key">月柱</span><span class="wl-detail-val"><?= $monthGz ?> <em><?= $nayinMonth ?></em></span></div>
        <div class="wl-detail-row"><span class="wl-detail-key">日柱</span><span class="wl-detail-val"><?= $todayGz ?> <em><?= $nayinDay ?></em></span></div>
    </div>
    <div class="wl-detail-card">
        <div class="wl-detail-title">⭐ 九星</div>
        <div class="wl-detail-row"><span class="wl-detail-key">日九星</span><span class="wl-detail-val"><?= $star9Day ?></span></div>
        <div class="wl-detail-row"><span class="wl-detail-key">值年九星</span><span class="wl-detail-val"><?= $star9Year ?></span></div>
    </div>
    <div class="wl-detail-card">
        <div class="wl-detail-title">🏛️ 四柱干支</div>
        <div class="wl-detail-row"><span class="wl-detail-key">年柱</span><span class="wl-detail-val"><?= $yearGz ?></span></div>
        <div class="wl-detail-row"><span class="wl-detail-key">月柱</span><span class="wl-detail-val"><?= $monthGz ?></span></div>
        <div class="wl-detail-row"><span class="wl-detail-key">日柱</span><span class="wl-detail-val"><?= $todayGz ?></span></div>
    </div>
    <div class="wl-detail-card">
        <div class="wl-detail-title">☯️ 胎元命宫</div>
        <div class="wl-detail-row"><span class="wl-detail-key">胎元</span><span class="wl-detail-val"><?= $taiyuan ?> <em><?= getNayin($taiyuan) ?></em></span></div>
    </div>
    <div class="wl-detail-card">
        <div class="wl-detail-title">👻 旬空</div>
        <div class="wl-detail-row"><span class="wl-detail-key">日柱旬空</span><span class="wl-detail-val"><?= $xunkong ?></span></div>
    </div>
    <div class="wl-detail-card">
        <div class="wl-detail-title">🌿 节气</div>
        <?php if ($todayJieQiName !== ''): ?>
        <div class="wl-detail-row"><span class="wl-detail-key">下个节气</span><span class="wl-detail-val"><?= $todayJieQiName ?></span></div>
        <div class="wl-detail-row"><span class="wl-detail-key">倒计时</span><span class="wl-detail-val" style="color:var(--wl-primary)"><?= $todayJieQiDays === 0 ? '今天' : $todayJieQiDays . '天' ?></span></div>
        <?php else: ?>
        <div class="wl-detail-row"><span class="wl-detail-key">本月节气</span><span class="wl-detail-val"><?= $jieqiAllNames[($selMonth-1)*2] ?> / <?= $jieqiAllNames[($selMonth-1)*2+1] ?></span></div>
        <?php endif; ?>
    </div>
</div>

<div class="wl-tabs">
    <div class="wl-tab-nav">
        <button class="wl-tab-btn wl-tab-btn-active" onclick="switchTab('shensha',this)">🛡️ 吉神凶煞</button>
        <button class="wl-tab-btn" onclick="switchTab('chongsha',this)">☯️ 冲煞详情</button>
        <button class="wl-tab-btn" onclick="switchTab('shichen',this)">⏰ 时辰吉凶</button>
        <button class="wl-tab-btn" onclick="switchTab('detail',this)">📋 详细信息</button>
    </div>

    <div class="wl-tab-panel wl-tab-panel-active" id="tab-shensha">
        <div style="display:flex;gap:16px">
            <div style="flex:1">
                <div style="font-weight:700;color:var(--wl-yi);margin-bottom:8px">🛡️ 吉神宜趋</div>
                <?php foreach ($jishenXiongsha['jishen'] as $js): ?>
                    <span class="wl-tag" style="background:rgba(37,99,235,0.1);color:var(--wl-primary);margin-bottom:4px"><?= $js ?></span>
                <?php endforeach; ?>
            </div>
            <div style="flex:1">
                <div style="font-weight:700;color:var(--wl-ji);margin-bottom:8px">⚡ 凶神宜忌</div>
                <?php foreach ($jishenXiongsha['xiongsha'] as $xs): ?>
                    <span class="wl-tag" style="background:rgba(153,27,27,0.1);color:#991b1b;margin-bottom:4px"><?= $xs ?></span>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <div class="wl-tab-panel" id="tab-chongsha">
        <div style="display:grid;grid-template-columns:repeat(2,1fr);gap:12px">
            <div class="wl-detail-card">
                <div class="wl-detail-title">⚡ 冲煞</div>
                <div class="wl-detail-row"><span class="wl-detail-key">冲</span><span class="wl-detail-val"><?= $chongSha ?></span></div>
                <div class="wl-detail-row"><span class="wl-detail-key">煞</span><span class="wl-detail-val">本日煞方 <?= $chongSha ?></span></div>
            </div>
            <div class="wl-detail-card">
                <div class="wl-detail-title">📜 彭祖百忌</div>
                <div class="wl-detail-row"><span class="wl-detail-key">天干</span><span class="wl-detail-val" style="font-size:0.78rem"><?= explode('，', $pengzu)[0] ?? '' ?></span></div>
                <div class="wl-detail-row"><span class="wl-detail-key">地支</span><span class="wl-detail-val" style="font-size:0.78rem"><?= explode('，', $pengzu)[1] ?? '' ?></span></div>
            </div>
            <div class="wl-detail-card">
                <div class="wl-detail-title">🏗️ 建除十二值</div>
                <div class="wl-detail-row"><span class="wl-detail-key">建除</span><span class="wl-detail-val"><?= $jianchu ?></span></div>
                <div class="wl-detail-row"><span class="wl-detail-key">黄道/黑道</span><span class="wl-detail-val"><?= $huangdao ?></span></div>
                <div class="wl-detail-row"><span class="wl-detail-key">二十八宿</span><span class="wl-detail-val"><?= $star28 ?></span></div>
            </div>
            <div class="wl-detail-card">
                <div class="wl-detail-title">☯️ 旬空</div>
                <div class="wl-detail-row"><span class="wl-detail-key">日柱旬空</span><span class="wl-detail-val"><?= $xunkong ?></span></div>
                <div class="wl-detail-row"><span class="wl-detail-key">胎元</span><span class="wl-detail-val"><?= $taiyuan ?> <?= getNayin($taiyuan) ?></span></div>
            </div>
        </div>
    </div>

    <div class="wl-tab-panel" id="tab-shichen">
        <table class="wl-table">
            <thead><tr><th>时辰</th><th>时间</th><th>时柱</th><th>吉神</th><th>凶煞</th><th>吉凶</th></tr></thead>
            <tbody>
                <?php foreach ($shichenData as $sd): ?>
                <tr>
                    <td><?= $sd['name'] ?></td>
                    <td><?= $sd['time'] ?></td>
                    <td><?= $sd['gz'] ?></td>
                    <td style="font-size:0.75rem;color:#16a34a"><?= implode(' ', $sd['jishen'] ?? []) ?></td>
                    <td style="font-size:0.75rem;color:#dc2626"><?= implode(' ', $sd['xiongsha'] ?? []) ?></td>
                    <td><?= $sd['ji'] ? '<span class="wl-tag wl-tag-yi">黄道·吉</span>' : '<span class="wl-tag wl-tag-ji">黑道·凶</span>' ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <div class="wl-tab-panel" id="tab-detail">
        <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:12px;">
            <div class="wl-detail-card">
                <div class="wl-detail-title">🐲 生肖/周数</div>
                <div class="wl-detail-row"><span class="wl-detail-key">生肖</span><span class="wl-detail-val"><?= getShengxiao($selYear) ?></span></div>
                <div class="wl-detail-row"><span class="wl-detail-key">干支纪年</span><span class="wl-detail-val"><?= $yearGz ?></span></div>
                <div class="wl-detail-row"><span class="wl-detail-key">第几周</span><span class="wl-detail-val">第<?= (int)(new DateTime("{$selYear}-{$selMonth}-{$selDay}"))->format('W') ?>周</span></div>
                <div class="wl-detail-row"><span class="wl-detail-key">第几天</span><span class="wl-detail-val">第<?= (int)(new DateTime("{$selYear}-{$selMonth}-{$selDay}"))->format('z')+1 ?>天</span></div>
            </div>
            <div class="wl-detail-card">
                <div class="wl-detail-title">🌙 农历信息</div>
                <div class="wl-detail-row"><span class="wl-detail-key">农历年</span><span class="wl-detail-val"><?= $todayLunar['y'] ?>年</span></div>
                <div class="wl-detail-row"><span class="wl-detail-key"><?= $lunarMonthNames[$todayLunar['m'] - 1] ?>月</span><span class="wl-detail-val"><?= $todayLunar['leap'] ? '闰' : '' ?>共<?= lunarDaysInMonth($todayLunar['y'], $todayLunar['m']) ?>天</span></div>
                <div class="wl-detail-row"><span class="wl-detail-key">日期</span><span class="wl-detail-val"><?= $lunarDayNames[$todayLunar['d'] - 1] ?></span></div>
            </div>
            <div class="wl-detail-card">
                <div class="wl-detail-title">📊 本月信息</div>
                <div class="wl-detail-row"><span class="wl-detail-key">本月天数</span><span class="wl-detail-val"><?= (int)$now->format('t') ?>天</span></div>
                <div class="wl-detail-row"><span class="wl-detail-key">星座</span><span class="wl-detail-val"><?= $constellation ?>座</span></div>
                <div class="wl-detail-row"><span class="wl-detail-key">节气</span><span class="wl-detail-val"><?= $jieqiAllNames[($selMonth-1)*2] ?> / <?= $jieqiAllNames[($selMonth-1)*2+1] ?></span></div>
            </div>
        </div>
    </div>
</div>

</div>

<script>
function jumpDate(){
    var y=document.getElementById('wl-jump-year').value;
    var m=document.getElementById('wl-jump-month').value;
    var d=document.getElementById('wl-jump-day').value;
    location.href='?route=toolbox-calendar&date='+y+'-'+m.padStart(2,'0')+'-'+d.padStart(2,'0');
}
function switchTab(n,b){
    document.querySelectorAll('.wl-tab-panel').forEach(function(p){p.classList.remove('wl-tab-panel-active')});
    document.querySelectorAll('.wl-tab-btn').forEach(function(b){b.classList.remove('wl-tab-btn-active')});
    document.getElementById('tab-'+n).classList.add('wl-tab-panel-active');
    b.classList.add('wl-tab-btn-active');
}
<?php
$monthYiJi = [];
for ($dd = 1; $dd <= $daysInMonth; $dd++) {
    $jcIdx = getJianchuIdx($dispYear, $dispMonth, $dd);
    $yj = getYiJi($jcIdx);
    $monthYiJi[$dd] = ['yi'=>$yj['yi'], 'ji'=>$yj['ji']];
}
?>
var monthYiJi=<?= json_encode($monthYiJi, JSON_UNESCAPED_UNICODE) ?>;
function searchLucky(){
    var kw=document.getElementById('wl-lucky-select').value;
    if(!kw){document.getElementById('wl-lucky-result').innerHTML='';return;}
    var found=[];
    for(var d=1;d<=<?= $daysInMonth ?>;d++){
        var yj=monthYiJi[d];if(!yj)continue;
        var all=yj.yi.concat(yj.ji);
        for(var i=0;i<all.length;i++){
            if(all[i].indexOf(kw)!==-1){found.push(d);break;}
        }
    }
    var el=document.getElementById('wl-lucky-result');
    if(found.length===0){el.innerHTML='<div style="padding:8px 12px;background:var(--wl-card);border:1px solid var(--wl-border);border-radius:8px;font-size:0.82rem">本月无匹配吉日</div>';return;}
    var html='<div style="padding:8px 12px;background:var(--wl-card);border:1px solid var(--wl-border);border-radius:8px;font-size:0.82rem">';
    html+='<strong>「'+kw+'」吉日：</strong> ';
    found.forEach(function(d){
        html+='<a href="?route=toolbox-calendar&date=<?= $dispYear ?>-<?= str_pad($dispMonth,2,'0',STR_PAD_LEFT) ?>-'+String(d).padStart(2,'0')+'" style="display:inline-block;margin:2px 4px;padding:2px 8px;background:rgba(37,99,235,0.1);border-radius:12px;text-decoration:none;color:var(--wl-text);font-size:0.82rem">'+d+'日</a>';
    });
    html+='</div>';
    el.innerHTML=html;
}
function resetLucky(){
    document.getElementById('wl-lucky-select').value='';
    document.getElementById('wl-lucky-result').innerHTML='';
}
(function(){var e=document.getElementById('wl-live-clock');if(!e)return;function t(){var n=new Date();e.textContent=String(n.getHours()).padStart(2,'0')+':'+String(n.getMinutes()).padStart(2,'0')+':'+String(n.getSeconds()).padStart(2,'0');setTimeout(t,1000)}t()})();
document.querySelectorAll('.wl-cal-cell[data-date]').forEach(function(cell){
    cell.style.cursor='pointer';
    cell.addEventListener('click',function(){
        var d=cell.getAttribute('data-date');
        if(d)location.href='?route=toolbox-calendar&date='+d;
    });
});
</script>
