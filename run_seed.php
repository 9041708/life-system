<?php
$host = 'localhost';
$dbname = 'ssjizhang_cn';
$user = 'root';
$pass = 'QQcao110..';

$pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $user, $pass, [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
]);

header('Content-Type: text/plain; charset=utf-8');

echo "=== 重建表 ===\n";

$pdo->exec("DROP TABLE IF EXISTS today_food");
$pdo->exec("DROP TABLE IF EXISTS today_places");
$pdo->exec("DROP TABLE IF EXISTS today_shows");

$pdo->exec("CREATE TABLE today_food (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    category VARCHAR(50) NOT NULL DEFAULT '家常菜',
    difficulty TINYINT DEFAULT 1,
    time_min INT DEFAULT 30,
    recipe_url VARCHAR(500) DEFAULT '',
    ingredients VARCHAR(500) DEFAULT '',
    is_takeout TINYINT DEFAULT 1,
    tags VARCHAR(200) DEFAULT '',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

$pdo->exec("CREATE TABLE today_places (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(200) NOT NULL,
    city VARCHAR(100) NOT NULL,
    province VARCHAR(50) DEFAULT '',
    is_free TINYINT DEFAULT 1,
    ticket_price DECIMAL(10,2) DEFAULT 0,
    description TEXT,
    tips VARCHAR(500) DEFAULT '',
    category VARCHAR(50) DEFAULT '景点',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

$pdo->exec("CREATE TABLE today_shows (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(200) NOT NULL,
    type VARCHAR(20) NOT NULL DEFAULT 'tv',
    platform VARCHAR(100) NOT NULL DEFAULT '',
    status VARCHAR(50) DEFAULT '',
    show_cast TEXT,
    description TEXT,
    rating DECIMAL(2,1) DEFAULT 0,
    air_date VARCHAR(20) DEFAULT '',
    year INT DEFAULT 0,
    tags VARCHAR(200) DEFAULT '',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

echo "表已创建\n";

// ===== FOOD =====
$foods = [
    ['红烧肉','家常菜',2,60,'https://www.xiachufang.com/search/?keyword=红烧肉','五花肉,冰糖,生抽,老抽,料酒,八角,桂皮',1,'硬菜,下饭'],
    ['番茄炒蛋','家常菜',1,15,'https://www.xiachufang.com/search/?keyword=番茄炒蛋','鸡蛋,番茄,葱,盐,糖',1,'快手菜,下饭'],
    ['宫保鸡丁','川菜',2,25,'https://www.xiachufang.com/search/?keyword=宫保鸡丁','鸡胸肉,花生,干辣椒,黄瓜,胡萝卜',1,'川菜,下饭'],
    ['麻婆豆腐','川菜',1,20,'https://www.xiachufang.com/search/?keyword=麻婆豆腐','嫩豆腐,猪肉末,豆瓣酱,花椒粉',1,'川菜,下饭'],
    ['酸菜鱼','川菜',2,40,'https://www.xiachufang.com/search/?keyword=酸菜鱼','草鱼,酸菜,泡椒,花椒',1,'硬菜'],
    ['可乐鸡翅','家常菜',1,30,'https://www.xiachufang.com/search/?keyword=可乐鸡翅','鸡翅中,可乐,生抽,老抽,姜',1,'甜口'],
    ['蛋炒饭','家常菜',1,10,'https://www.xiachufang.com/search/?keyword=蛋炒饭','隔夜饭,鸡蛋,葱,盐',1,'快手菜,主食'],
    ['糖醋排骨','家常菜',2,50,'https://www.xiachufang.com/search/?keyword=糖醋排骨','排骨,醋,糖,生抽,番茄酱',1,'硬菜'],
    ['水煮鱼','川菜',3,50,'https://www.xiachufang.com/search/?keyword=水煮鱼','草鱼,豆芽,花椒,干辣椒',1,'硬菜'],
    ['回锅肉','川菜',2,25,'https://www.xiachufang.com/search/?keyword=回锅肉','五花肉,蒜苗,豆瓣酱',1,'川菜,下饭'],
    ['糖醋里脊','家常菜',2,35,'https://www.xiachufang.com/search/?keyword=糖醋里脊','猪里脊,番茄酱,醋,糖,淀粉',1,'硬菜'],
    ['干煸四季豆','家常菜',1,15,'https://www.xiachufang.com/search/?keyword=干煸四季豆','四季豆,猪肉末,干辣椒,蒜',1,'快手菜'],
    ['鱼香肉丝','川菜',2,20,'https://www.xiachufang.com/search/?keyword=鱼香肉丝','猪里脊,木耳,胡萝卜,青椒',1,'川菜,下饭'],
    ['蒜蓉西兰花','家常菜',1,10,'https://www.xiachufang.com/search/?keyword=蒜蓉西兰花','西兰花,蒜,蚝油',0,'快手菜,素菜'],
    ['清炒时蔬','家常菜',1,8,'https://www.xiachufang.com/search/?keyword=清炒时蔬','时蔬,蒜,盐',0,'快手菜,素菜'],
    ['煎饺','面食',2,30,'https://www.xiachufang.com/search/?keyword=煎饺','饺子皮,猪肉馅,韭菜',0,'主食'],
    ['牛肉面','面食',2,60,'https://www.xiachufang.com/search/?keyword=牛肉面','牛腩,面条,葱姜,八角',1,'主食,硬菜'],
    ['鸡蛋灌饼','面食',1,15,'https://www.xiachufang.com/search/?keyword=鸡蛋灌饼','面粉,鸡蛋,葱,油',1,'主食,早餐'],
    ['炒河粉','粤菜',1,15,'https://www.xiachufang.com/search/?keyword=炒河粉','河粉,鸡蛋,豆芽,牛肉',1,'快手菜'],
    ['白切鸡','粤菜',2,45,'https://www.xiachufang.com/search/?keyword=白切鸡','三黄鸡,姜,葱,沙姜',1,'硬菜,粤菜'],
    ['蒸鱼','粤菜',1,20,'https://www.xiachufang.com/search/?keyword=清蒸鱼','鲈鱼,葱,姜,蒸鱼豉油',1,'快手菜'],
    ['炸鸡','快餐',2,40,'https://www.xiachufang.com/search/?keyword=炸鸡','鸡腿,面粉,面包糠,辣椒粉',1,'小吃'],
    ['麻辣烫','小吃',1,20,'https://www.xiachufang.com/search/?keyword=麻辣烫','各种蔬菜,丸子,豆腐',1,'小吃'],
    ['寿司','日料',2,30,'https://www.xiachufang.com/search/?keyword=寿司','寿司米,海苔,三文鱼,黄瓜',0,'日料'],
    ['三明治','西餐',1,10,'https://www.xiachufang.com/search/?keyword=三明治','吐司,生菜,番茄,火腿',1,'快手菜'],
    ['咖喱饭','西餐',1,30,'https://www.xiachufang.com/search/?keyword=咖喱饭','土豆,胡萝卜,咖喱块,鸡肉',1,'下饭'],
    ['火锅','川菜',1,30,'https://www.xiachufang.com/search/?keyword=火锅','火锅底料,肥牛,豆腐,蔬菜',1,'聚餐'],
    ['烤肉','韩餐',2,30,'https://www.xiachufang.com/search/?keyword=烤肉','五花肉,牛肉,生菜,辣酱',1,'聚餐'],
    ['披萨','西餐',3,60,'https://www.xiachufang.com/search/?keyword=披萨','面粉,番茄酱,芝士,火腿',0,'西餐'],
    ['饺子','面食',3,90,'https://www.xiachufang.com/search/?keyword=饺子','面粉,猪肉馅,白菜,葱姜',0,'主食,聚餐'],
    ['红烧茄子','家常菜',1,15,'https://www.xiachufang.com/search/?keyword=红烧茄子','茄子,蒜,酱油,糖',1,'下饭,素菜'],
    ['地三鲜','东北菜',2,20,'https://www.xiachufang.com/search/?keyword=地三鲜','茄子,土豆,青椒,蒜',1,'东北菜,下饭'],
    ['锅包肉','东北菜',2,30,'https://www.xiachufang.com/search/?keyword=锅包肉','猪里脊,淀粉,醋,糖,番茄酱',1,'东北菜'],
    ['酸辣粉','小吃',1,15,'https://www.xiachufang.com/search/?keyword=酸辣粉','红薯粉,花生,香菜,醋,辣椒油',1,'小吃'],
    ['凉皮','小吃',1,10,'https://www.xiachufang.com/search/?keyword=凉皮','面粉,黄瓜,蒜水,醋,辣椒油',1,'小吃,早餐'],
    ['炸酱面','面食',1,20,'https://www.xiachufang.com/search/?keyword=炸酱面','面条,猪肉末,甜面酱,黄瓜丝',1,'主食,面食'],
    ['兰州拉面','面食',3,40,'https://www.xiachufang.com/search/?keyword=兰州拉面','高筋面粉,牛肉,萝卜,香菜',1,'主食'],
    ['馄饨','面食',2,30,'https://www.xiachufang.com/search/?keyword=馄饨','馄饨皮,猪肉馅,紫菜,虾皮',1,'主食,早餐'],
    ['煎饼果子','面食',1,10,'https://www.xiachufang.com/search/?keyword=煎饼果子','绿豆面,鸡蛋,薄脆,葱',1,'早餐'],
    ['手抓饼','面食',1,8,'https://www.xiachufang.com/search/?keyword=手抓饼','手抓饼皮,鸡蛋,火腿,生菜',1,'早餐,快手菜'],
    ['烤串','烧烤',2,30,'https://www.xiachufang.com/search/?keyword=烤羊肉串','羊肉,孜然,辣椒粉,盐',1,'聚餐,夜宵'],
    ['铁板烧','烧烤',2,20,'https://www.xiachufang.com/search/?keyword=铁板烧','牛肉,鱿鱼,洋葱,黄油',1,'聚餐'],
    ['酸汤肥牛','川菜',1,15,'https://www.xiachufang.com/search/?keyword=酸汤肥牛','肥牛,金针菇,酸汤料',1,'下饭'],
    ['蒜苗回锅肉','川菜',2,25,'https://www.xiachufang.com/search/?keyword=蒜苗回锅肉','五花肉,蒜苗,豆瓣酱',1,'川菜'],
    ['口水鸡','川菜',2,30,'https://www.xiachufang.com/search/?keyword=口水鸡','鸡腿,花椒油,辣椒油,花生碎',1,'川菜,凉菜'],
    ['皮蛋豆腐','凉菜',1,5,'https://www.xiachufang.com/search/?keyword=皮蛋豆腐','内酯豆腐,皮蛋,香菜,生抽',0,'快手菜,凉菜'],
    ['拍黄瓜','凉菜',1,5,'https://www.xiachufang.com/search/?keyword=拍黄瓜','黄瓜,蒜,醋,辣椒油',0,'快手菜,凉菜'],
    ['凉拌木耳','凉菜',1,10,'https://www.xiachufang.com/search/?keyword=凉拌木耳','木耳,洋葱,辣椒,醋',0,'凉菜'],
    ['可乐','饮品',1,1,'', '',1,'饮品'],
    ['奶茶','饮品',1,10,'https://www.xiachufang.com/search/?keyword=奶茶','红茶,牛奶,糖',1,'饮品'],
    ['冰粉','甜品',1,15,'https://www.xiachufang.com/search/?keyword=冰粉','冰粉粉,红糖水,花生碎,葡萄干',0,'甜品,小吃'],
    ['蛋挞','甜品',2,30,'https://www.xiachufang.com/search/?keyword=蛋挞','蛋挞皮,鸡蛋,牛奶,糖',0,'甜品'],
    ['炸薯条','快餐',1,15,'https://www.xiachufang.com/search/?keyword=炸薯条','土豆,盐,番茄酱',1,'小吃,快手菜'],
    ['汉堡','快餐',2,20,'https://www.xiachufang.com/search/?keyword=汉堡','面包胚,牛肉饼,生菜,番茄,芝士',1,'快餐'],
    ['意面','西餐',1,20,'https://www.xiachufang.com/search/?keyword=意面','意面,番茄酱,牛肉末,洋葱',1,'西餐'],
    ['牛排','西餐',2,15,'https://www.xiachufang.com/search/?keyword=煎牛排','牛排,黄油,黑胡椒,迷迭香',1,'西餐,硬菜'],
    ['三文鱼刺身','日料',1,5,'', '三文鱼,芥末,酱油',1,'日料'],
    ['拉面','日料',3,40,'https://www.xiachufang.com/search/?keyword=日式拉面','面条,叉烧,溏心蛋,海苔',1,'日料'],
    ['炸虾天妇罗','日料',2,20,'https://www.xiachufang.com/search/?keyword=天妇罗','虾,面粉,鸡蛋,冰水',0,'日料'],
    ['泡菜汤','韩餐',1,15,'https://www.xiachufang.com/search/?keyword=泡菜汤','泡菜,豆腐,猪肉,葱',1,'韩餐,下饭'],
    ['石锅拌饭','韩餐',1,20,'https://www.xiachufang.com/search/?keyword=石锅拌饭','米饭,蔬菜,鸡蛋,辣酱',1,'韩餐'],
    ['辣炒年糕','韩餐',1,15,'https://www.xiachufang.com/search/?keyword=辣炒年糕','年糕,辣酱,鱼饼,葱',1,'韩餐,小吃'],
    ['春卷','小吃',2,20,'https://www.xiachufang.com/search/?keyword=春卷','春卷皮,猪肉,蔬菜',0,'小吃'],
    ['葱油饼','面食',1,10,'https://www.xiachufang.com/search/?keyword=葱油饼','面粉,葱,盐,油',0,'主食'],
    ['锅盔','面食',2,20,'', '面粉,猪肉馅,葱',1,'主食'],
    ['烤冷面','小吃',1,10,'', '冷面,鸡蛋,香肠,洋葱',1,'小吃,夜宵'],
    ['螺蛳粉','小吃',1,15,'', '螺蛳粉,酸笋,腐竹,花生',1,'小吃'],
    ['黄焖鸡','家常菜',2,30,'https://www.xiachufang.com/search/?keyword=黄焖鸡','鸡腿,香菇,土豆,青椒',1,'下饭,硬菜'],
    ['肉末茄子','家常菜',1,15,'https://www.xiachufang.com/search/?keyword=肉末茄子','茄子,猪肉末,蒜,豆瓣酱',1,'下饭'],
    ['葱爆牛肉','家常菜',1,10,'https://www.xiachufang.com/search/?keyword=葱爆牛肉','牛肉,大葱,姜,料酒',1,'快手菜,硬菜'],
    ['番茄牛腩','家常菜',2,60,'https://www.xiachufang.com/search/?keyword=番茄牛腩','牛腩,番茄,土豆,葱姜',1,'硬菜,下饭'],
    ['蒜蓉虾','海鲜',2,15,'https://www.xiachufang.com/search/?keyword=蒜蓉虾','大虾,蒜蓉,粉丝,生抽',1,'海鲜,硬菜'],
    ['清蒸螃蟹','海鲜',1,15,'', '螃蟹,姜,醋',1,'海鲜'],
    ['葱姜炒蟹','海鲜',2,20,'https://www.xiachufang.com/search/?keyword=葱姜炒蟹','螃蟹,葱,姜,料酒',1,'海鲜'],
    ['干锅花菜','家常菜',1,15,'https://www.xiachufang.com/search/?keyword=干锅花菜','花菜,五花肉,干辣椒,蒜',1,'下饭'],
    ['毛血旺','川菜',3,40,'https://www.xiachufang.com/search/?keyword=毛血旺','鸭血,毛肚,黄喉,豆芽,花椒',1,'川菜,硬菜'],
    ['夫妻肺片','川菜',3,60,'https://www.xiachufang.com/search/?keyword=夫妻肺片','牛肉,牛杂,花椒面,辣椒油',1,'川菜,凉菜'],
    ['蛋包饭','日料',1,15,'https://www.xiachufang.com/search/?keyword=蛋包饭','鸡蛋,米饭,番茄酱',1,'日料,快手菜'],
    ['芝士焗饭','西餐',1,20,'https://www.xiachufang.com/search/?keyword=芝士焗饭','米饭,番茄酱,芝士,培根',1,'西餐'],
    ['烤鸭','京菜',3,90,'', '鸭子,甜面酱,葱,黄瓜',1,'京菜,硬菜'],
    ['卤肉饭','台菜',1,40,'https://www.xiachufang.com/search/?keyword=卤肉饭','五花肉,香菇,洋葱,米饭',1,'主食,下饭'],
    ['盐酥鸡','台菜',1,15,'', '鸡腿,地瓜粉,九层塔',1,'小吃'],
    ['叉烧','粤菜',3,60,'https://www.xiachufang.com/search/?keyword=叉烧','梅花肉,叉烧酱,蜂蜜',1,'粤菜,硬菜'],
    ['虾饺','粤菜',3,60,'', '虾仁,澄面,淀粉',1,'粤菜,早茶'],
    ['肠粉','粤菜',2,20,'', '粘米粉,虾仁,鸡蛋',1,'粤菜,早餐'],
    ['煲仔饭','粤菜',2,40,'', '米饭,腊肠,酱油',1,'粤菜,主食'],
];

$stmt = $pdo->prepare("INSERT INTO today_food (name,category,difficulty,time_min,recipe_url,ingredients,is_takeout,tags) VALUES (?,?,?,?,?,?,?,?)");
foreach ($foods as $f) $stmt->execute($f);
echo "food: " . count($foods) . " rows inserted\n";

// ===== PLACES =====
$places = file_get_contents(__DIR__ . '/tools/today_places_seed.sql');
if ($places) {
    $pdo->exec($places);
    echo "places: inserted from SQL file\n";
} else {
    echo "places: SQL file not found, skipping\n";
}

// ===== SHOWS =====
$shows = [
    ['庆余年第二季','tv','腾讯','全集36集','张若昀,李沁,陈道明,吴刚','范闲历经种种考验继续在庆国书写传奇',7.8,'2024-05-16',2024,'古装,权谋'],
    ['玫瑰的故事','tv','腾讯','全集38集','刘亦菲,彭冠英,林更新,霍建华','黄亦玫二十年的情感历程',7.5,'2024-06-08',2024,'都市,情感'],
    ['与凤行','tv','腾讯','全集39集','赵丽颖,林更新,辛云来','上古神君与碧苍王的爱情故事',7.3,'2024-03-18',2024,'古装,仙侠'],
    ['繁花','tv','腾讯','全集30集','胡歌,马伊琍,唐嫣,辛芷蕾','九十年代上海的繁华与机遇',8.7,'2023-12-27',2023,'都市,商战'],
    ['漫长的季节','tv','腾讯','全集12集','范伟,秦昊,陈明昊,李庚希','东北小镇的出租车司机卷入陈年悬案',9.4,'2023-04-22',2023,'悬疑,剧情'],
    ['狂飙','tv','爱奇艺','全集39集','张译,张颂文,李一桐,张志坚','扫黑除恶专项斗争中的正邪较量',8.5,'2023-01-14',2023,'犯罪,剧情'],
    ['长相思','tv','腾讯','全集39集','杨紫,张晚意,邓为,檀健次','大荒之中几个年轻人的命运纠葛',7.6,'2023-07-24',2023,'古装,言情'],
    ['三体','tv','腾讯','全集30集','张鲁一,于和伟,陈瑾,王子文','人类首次与外星文明接触',8.7,'2023-01-15',2023,'科幻,剧情'],
    ['开端','tv','腾讯','全集15集','白敬亭,赵今麦,刘丹','时间循环中的公交车爆炸真相',7.9,'2022-01-11',2022,'悬疑,科幻'],
    ['人世间','tv','爱奇艺','全集58集','雷佳音,辛柏青,宋佳,殷桃','东北一家三代人的生活变迁',8.1,'2022-01-28',2022,'家庭,年代'],
    ['去有风的地方','tv','湖南卫视','全集40集','刘亦菲,李现','田园治愈爱情故事',7.7,'2023-01-03',2023,'田园,爱情'],
    ['莲花楼','tv','爱奇艺','全集40集','成毅,曾舜晞,肖顺尧','江湖奇案探案故事',8.2,'2023-07-23',2023,'古装,悬疑'],
    ['追风者','tv','央视','全集38集','王一博,李沁,王阳','1930年代上海金融谍战',7.9,'2024-03-21',2024,'谍战,年代'],
    ['承欢记','tv','腾讯','全集37集','杨紫,许凯','都市女性成长故事',7.2,'2024-04-09',2024,'都市,家庭'],
    ['南来北往','tv','央视','全集39集','白敬亭,丁勇岱,金晨','铁路公安干警的故事',7.8,'2024-02-26',2024,'年代,剧情'],
    ['大江大河之岁月如歌','tv','央视','全集33集','王凯,杨烁,董子健,杨采钰','改革开放浪潮中的创业故事',8.0,'2024-01-08',2024,'年代,剧情'],
    ['不完美受害人','tv','爱奇艺','全集26集','周迅,刘奕君,林允','职场性骚扰案件的法律与人性',7.5,'2023-08-07',2023,'现实,法律'],
    ['消失的她','movie','爱奇艺','已上映','朱一龙,倪妮,文咏珊','男子在妻子失踪后陷入层层骗局',6.5,'2023-07-06',2023,'悬疑,犯罪'],
    ['孤注一掷','movie','爱奇艺','已上映','张艺兴,金晨,咏梅,王传君','网络诈骗产业链揭秘',7.0,'2023-08-08',2023,'犯罪,剧情'],
    ['封神第一部','movie','爱奇艺','已上映','费翔,李雪健,黄渤,于适','商周之际的神话史诗',7.8,'2023-07-20',2023,'奇幻,动作'],
    ['流浪地球2','movie','腾讯','已上映','吴京,刘德华,李雪健,沙溢','太阳危机下人类带着地球流浪',8.3,'2023-01-22',2023,'科幻,冒险'],
    ['满江红','movie','爱奇艺','已上映','沈腾,易烊千玺,张译,雷佳音','南宋绍兴年间的一桩密案',7.0,'2023-01-22',2023,'悬疑,喜剧'],
    ['你好李焕英','movie','爱奇艺','已上映','贾玲,张小斐,沈腾','女儿穿越回过去改变母亲命运',7.8,'2021-02-12',2021,'喜剧,亲情'],
    ['热辣滚烫','movie','爱奇艺','已上映','贾玲,雷佳音,张小斐','贾玲为角色减重100斤',7.2,'2024-02-10',2024,'喜剧,励志'],
    ['第二十条','movie','爱奇艺','已上映','雷佳音,马丽,赵丽颖,高叶','正当防卫法律题材',7.0,'2024-02-10',2024,'喜剧,法律'],
    ['飞驰人生2','movie','爱奇艺','已上映','沈腾,范丞丞,尹正','赛车喜剧续集',7.1,'2024-02-10',2024,'喜剧,运动'],
    ['年会不能停','movie','爱奇艺','已上映','大鹏,白客,庄达菲','职场讽刺喜剧',7.4,'2024-01-12',2024,'喜剧,职场'],
    ['沙丘2','movie','腾讯','已上映','提莫西·查拉梅,赞达亚','史诗科幻续集',8.2,'2024-03-08',2024,'科幻,冒险'],
    ['周处除三害','movie','爱奇艺','已上映','阮经天,袁富华,陈以文','通缉犯的自我救赎',8.1,'2024-03-01',2024,'犯罪,动作'],
    ['你想活出怎样的人生','movie','爱奇艺','已上映','山时聪真,柴崎幸,木村拓哉','宫崎骏最新作品',7.6,'2024-04-03',2024,'动画,奇幻'],
    ['头脑特工队2','movie','爱奇艺','已上映','艾米·波勒,玛雅·霍克','皮克斯高分动画续集',8.0,'2024-06-14',2024,'动画,喜剧'],
    ['死侍与金刚狼','movie','爱奇艺','已上映','瑞安·雷诺兹,休·杰克曼','漫威R级喜剧',7.5,'2024-07-26',2024,'喜剧,动作'],
    ['默杀','movie','爱奇艺','已上映','王传君,张钧甯,吴镇宇','校园霸凌与复仇',7.2,'2024-07-03',2024,'犯罪,悬疑'],
    ['抓娃娃','movie','爱奇艺','已上映','沈腾,马丽','穷人装富的荒诞喜剧',7.0,'2024-07-16',2024,'喜剧,家庭'],
    ['解密','movie','爱奇艺','已上映','刘昊然,约翰·库萨克','麦家同名小说改编',7.1,'2024-08-03',2024,'悬疑,传记'],
    ['披荆斩棘的哥哥第三季','variety','芒果TV','已完结','陈楚生,苏有朋,王耀庆','30位哥哥成团之路',7.5,'2023-08-18',2023,'音乐,真人秀'],
    ['奔跑吧第七季','variety','爱奇艺','已完结','李晨,郑恺,沙溢,周深','户外竞技真人秀',6.8,'2023-04-21',2023,'真人秀'],
    ['向往的生活第七季','variety','芒果TV','已完结','何炅,黄磊,张子枫','乡村慢生活体验',7.2,'2023-04-28',2023,'田园,真人秀'],
    ['脱口秀大会第五季','variety','腾讯','已完结','李诞,杨笠,徐志胜,何广智','脱口秀竞技节目',7.8,'2022-10-14',2022,'脱口秀,喜剧'],
    ['声生不息·港乐季','variety','芒果TV','已完结','林子祥,叶倩文,李克勤','港乐传承节目',8.0,'2022-04-24',2022,'音乐'],
    ['五十公里桃花坞第四季','variety','腾讯','已完结','宋丹丹,李雪琴,王鹤棣','群居生活真人秀',7.0,'2024-05-25',2024,'真人秀,综艺'],
    ['歌手2024','variety','湖南卫视','全集','那英,汪苏泷,凡希亚','音乐竞演节目',8.3,'2024-05-10',2024,'音乐,综艺'],
    ['种地吧第二季','variety','爱奇艺','已完结','蒋敦豪,鹭卓,李昊','劳动纪实真人秀',8.5,'2024-02-25',2024,'真人秀,田园'],
    ['花儿与少年·丝路季','variety','湖南卫视','已完结','秦岚,赵昭仪,秦海璐','旅行真人秀',7.5,'2023-10-27',2023,'旅行,真人秀'],
    ['哈哈哈哈哈第四季','variety','爱奇艺','已完结','邓超,陈赫,鹿晗,王勉','欢乐旅行真人秀',6.8,'2024-01-19',2024,'喜剧,真人秀'],
    ['鬼灭之刃','anime','B站','更新中','花江夏树,鬼头明里','少年为复仇妹妹踏上斩鬼之路',8.5,'2019-04-06',2019,'热血,战斗'],
    ['咒术回战','anime','B站','已完结','榎木淳弥,内田雄马','高中生成为咒术师的故事',8.0,'2020-10-02',2020,'热血,战斗'],
    ['间谍过家家','anime','B站','更新中','江口拓也,种崎敦美','间谍杀手超能力者组成假家庭',8.8,'2022-04-09',2022,'搞笑,日常'],
    ['进击的巨人 最终季','anime','B站','已完结','梶裕贵,石川界人','人类与巨人的终极对决',9.0,'2020-04-12',2023,'热血,战斗'],
    ['葬送的芙莉莲','anime','B站','已完结','黑泽百合,小林裕介','魔王讨伐后的冒险故事',9.2,'2023-09-29',2023,'冒险,奇幻'],
    ['排球少年!!','anime','B站','已完结','村濑步,石川界人','高中排球热血故事',9.0,'2014-04-06',2014,'运动,热血'],
    ['我推的孩子','anime','B站','全集','伊东健人,大久保瑠美','娱乐圈悬疑故事',8.0,'2023-04-12',2023,'悬疑,娱乐圈'],
    ['迷宫饭','anime','B站','更新中','�的村优,小松未可子','在地下城做饭的奇幻故事',8.5,'2024-01-11',2024,'美食,冒险'],
    ['药屋少女的呢喃','anime','B站','更新中','千本木彩花,悠木碧','后宫推理故事',8.3,'2023-10-21',2023,'推理,古装'],
    ['黑暗荣耀','tv','Netflix','全集16集','宋慧乔,李到晛','校园暴力复仇故事',8.8,'2022-12-30',2022,'复仇,悬疑'],
    ['鱿鱼游戏','tv','Netflix','全集9集','李政宰,朴海秀','生存游戏惊悚故事',7.8,'2021-09-17',2021,'惊悚,生存'],
    ['怪奇物语','tv','Netflix','全集34集','米莉·布朗,菲恩·沃尔夫哈德','超自然冒险故事',8.7,'2016-07-15',2016,'科幻,悬疑'],
    ['最后生还者','tv','HBO','全集9集','佩德罗·帕斯卡,贝拉·拉姆齐','末日求生故事',8.8,'2023-01-15',2023,'末日,冒险'],
    ['龙之家族','tv','HBO','全集10集','帕迪·康斯戴恩,艾玛·达西','权力的游戏前传',8.4,'2022-08-21',2022,'奇幻,权谋'],
];

$stmt = $pdo->prepare("INSERT INTO today_shows (name,type,platform,`status`,show_cast,description,rating,air_date,year,tags) VALUES (?,?,?,?,?,?,?,?,?,?)");
foreach ($shows as $s) $stmt->execute($s);
echo "shows: " . count($shows) . " rows inserted\n";

echo "\n=== 完成 ===\n";
echo "food: " . $pdo->query("SELECT COUNT(*) FROM today_food")->fetchColumn() . "\n";
echo "places: " . $pdo->query("SELECT COUNT(*) FROM today_places")->fetchColumn() . "\n";
echo "shows: " . $pdo->query("SELECT COUNT(*) FROM today_shows")->fetchColumn() . "\n";
