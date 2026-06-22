<?php
namespace App\Controller;
use App\Service\Config;
use App\Service\Database;
use App\Model\EntStock;
use App\Model\EntAccount;
use App\Model\EntPosition;
use App\Model\EntTrade;
use App\Model\EntOrder;
use App\Model\EntNews;
use App\Model\EntConfig;

class EntertainmentController
{
    private function requireLogin(): int { $uid=(int)($_SESSION['user_id']??0); if($uid<=0){header('Location:/public/index.php?route=login');exit;} return $uid; }
    private function render(string $view,array $p=[]):void { extract($p); $appName=Config::get('app.name'); $_SESSION['current_page_title']=$p['pageTitle']??'炒股'; include __DIR__.'/../../templates/layout_main.php'; }
    private function json(array $d):void { while(ob_get_level())ob_end_clean(); header('Content-Type: application/json; charset=utf-8'); echo json_encode($d,JSON_UNESCAPED_UNICODE); exit; }

    public function index():void {
        $uid=$this->requireLogin();
        EntStock::refreshPrices();
        $stocks=EntStock::all(); $acc=EntAccount::get($uid);
        $positions=EntPosition::allByUser($uid); $orders=EntOrder::byUser($uid);
        $pdo=Database::getConnection();
        $isAdmin=($_SESSION['user_role']??'')==='admin';
        $adminStocks=$isAdmin?EntStock::allAdmin():[]; $adminNews=$isAdmin?EntNews::all():[];
        $allNews=EntNews::all();
        $adminLoans=$isAdmin?$pdo->query("SELECT l.*,u.username,u.nickname FROM ent_loans l LEFT JOIN users u ON u.id=l.user_id ORDER BY l.created_at DESC")->fetchAll(\PDO::FETCH_ASSOC)?:[]:[];
        $adminUsers=$isAdmin?$pdo->query("SELECT a.*,u.username,u.nickname FROM ent_accounts a LEFT JOIN users u ON u.id=a.user_id ORDER BY a.balance DESC")->fetchAll(\PDO::FETCH_ASSOC)?:[]:[];
        $loanEnabled=EntConfig::get('loan_enabled','1')==='1'; $loanMax=EntConfig::get('loan_max','500000');
        $loanRate=EntConfig::get('loan_rate','5'); $isTradeTime=EntStock::isTradingTime();
        $sysNotice=EntConfig::get('system_notice','');
        $loanStmt=$pdo->prepare("SELECT * FROM ent_loans WHERE user_id=:u ORDER BY created_at DESC");
        $loanStmt->execute([':u'=>$uid]); $loans=$loanStmt->fetchAll(\PDO::FETCH_ASSOC)?:[];
        $totalMV=0; foreach($positions as $p) $totalMV+=(float)$p['current_price']*(int)$p['quantity'];
        $totalAssets=(float)$acc['balance']+$totalMV-(float)$acc['loan_amount'];
        $lb=$pdo->query("SELECT a.user_id,a.balance,u.nickname,u.username FROM ent_accounts a LEFT JOIN users u ON u.id=a.user_id ORDER BY a.balance DESC LIMIT 20")->fetchAll(\PDO::FETCH_ASSOC)?:[];
        $ts=EntTrade::countByUser($uid);        $wr=$ts['total']>0?round($ts['win']/$ts['total']*100,1):0;
        $tradePage=max(1,(int)($_GET['trade_page']??1));$tradeLimit=20;$tradeOffset=($tradePage-1)*20;
        $tradeTotal=$pdo->query("SELECT COUNT(*) FROM ent_trades WHERE user_id=".$uid)->fetchColumn();
        $tradePages=max(1,(int)ceil($tradeTotal/20));
        $trades=EntTrade::byUser($uid,$tradeLimit,$tradeOffset);
        $odStmt=$pdo->prepare("SELECT * FROM ent_loans WHERE user_id=:u AND status='active' AND due_date<=CURDATE()");
        $odStmt->execute([':u'=>$uid]); $overdue=$odStmt->fetchAll(\PDO::FETCH_ASSOC)?:[];
        $this->render('entertainment/stock',[
            'pageTitle'=>'炒股','stocks'=>$stocks,'acc'=>$acc,'positions'=>$positions,'orders'=>$orders,
            'isAdmin'=>$isAdmin,'adminStocks'=>$adminStocks,'adminNews'=>$adminNews,'allNews'=>$allNews,'adminLoans'=>$adminLoans,'adminUsers'=>$adminUsers,
            'loanEnabled'=>$loanEnabled,'loanMax'=>$loanMax,'loanRate'=>$loanRate,'isTradeTime'=>$isTradeTime,
            'totalMV'=>$totalMV,'totalAssets'=>$totalAssets,'totalProfit'=>$totalAssets-1000000,'wr'=>$wr,'lb'=>$lb,'sysNotice'=>$sysNotice,'loans'=>$loans,'overdue'=>$overdue,
            'trades'=>$trades,'tradePage'=>$tradePage,'tradePages'=>$tradePages,'tradeTotal'=>$tradeTotal,
        ]);
    }

    public function api():void {
        $uid=$this->requireLogin();$a=$_POST['action']??'';$isAdmin=($_SESSION['user_role']??'')==='admin';
        try{ob_start();
            switch($a){
                case 'quote':$s=EntStock::find((int)$_POST['stock_id']);$this->json($s?['ok'=>true,'stock'=>$s]:['ok'=>false]);break;
                case 'kline':$this->json(['ok'=>true,'data'=>self::genKline((int)$_POST['stock_id'],$_POST['scale']??'1m')]);break;
                case 'place_order':
                    $sid=(int)$_POST['stock_id'];$type=$_POST['type'];$price=(float)$_POST['price'];$qty=(int)$_POST['quantity'];
                    if(!in_array($type,['buy','sell'])||$price<=0||$qty<=0||$qty%100!==0)$this->json(['ok'=>false,'error'=>'参数无效']);
                    $stock=EntStock::find($sid);if(!$stock)$this->json(['ok'=>false,'error'=>'股票不存在']);
                    $curPrice=(float)$stock['current_price'];$acc=EntAccount::get($uid);
                    $isTrade=EntStock::isTradingTime();
                    $canImmediate=($type==='buy'&&$price>=$curPrice)||($type==='sell'&&$price<=$curPrice);
                    if($isTrade&&$canImmediate){
                        $amt=$price*$qty;
                        if($type==='buy'){$fee=round($amt*0.001,2);$total=$amt+$fee;if((float)$acc['balance']<$total)$this->json(['ok'=>false,'error'=>'余额不足需¥'.number_format($total,2)]);EntAccount::updateBalance($uid,round((float)$acc['balance']-$total,2));EntPosition::buy($uid,$sid,$qty,$price);EntTrade::log($uid,$sid,'buy',$price,$qty,$fee,$total);}
                        else{$pos=EntPosition::get($uid,$sid);if(!$pos||(int)$pos['quantity']<$qty)$this->json(['ok'=>false,'error'=>'持仓不足']);$fee=round($amt*0.002,2);$total=$amt-$fee;EntPosition::sell($uid,$sid,$qty);EntAccount::updateBalance($uid,round((float)$acc['balance']+$total,2));EntTrade::log($uid,$sid,'sell',$price,$qty,$fee,$total);}
                        EntOrder::create($uid,$sid,$type,$price,$qty);
                        Database::getConnection()->prepare("UPDATE ent_orders SET status='done' WHERE user_id=:u ORDER BY id DESC LIMIT 1")->execute([':u'=>$uid]);
                        $this->json(['ok'=>true,'message'=>($type==='buy'?'买入':'卖出').'成功@¥'.number_format($price,2),'done'=>true]);break;
                    }
                    if($type==='buy'&&(float)$acc['balance']<$price*$qty*1.1)$this->json(['ok'=>false,'error'=>'余额不足']);
                    if($type==='sell'){$pos=EntPosition::get($uid,$sid);if(!$pos||(int)$pos['quantity']<$qty)$this->json(['ok'=>false,'error'=>'持仓不足']);}
                    EntOrder::create($uid,$sid,$type,$price,$qty);
                    $this->json(['ok'=>true,'message'=>($isTrade?'委托已提交@¥'.number_format($price,2):'休市委托已提交@¥'.number_format($price,2).',开盘自动成交'),'done'=>false]);break;
                case 'cancel_order':EntOrder::cancel((int)$_POST['order_id'],$uid);$this->json(['ok'=>true,'message'=>'已撤单']);break;
                case 'get_orders':
                    $ords=EntOrder::byUser($uid);
                    $html='';$sl=['pending'=>'⏳待成交','done'=>'✅成交','cancelled'=>'❌取消'];$sc=['pending'=>'warning','done'=>'success','cancelled'=>'secondary'];
                    foreach($ords as $o){
                        $html.='<tr><td><span class="small">'.htmlspecialchars($o['name']).'</span></td><td><span class="badge bg-'.($o['type']==='buy'?'success':'danger').'">'.($o['type']==='buy'?'买':'卖').'</span></td><td class="text-end small">'.number_format($o['price'],2).'</td><td class="text-end small">'.$o['quantity'].'</td><td><span class="badge bg-'.$sc[$o['status']].'">'.$sl[$o['status']].'</span></td><td>'.($o['status']==='pending'?'<button class="btn btn-sm btn-outline-danger py-0" style="font-size:0.65rem" onclick="cancelOrder('.$o['id'].')">撤单</button>':'').'</td></tr>';
                    }
                    $pc=EntOrder::countPending($uid);
                    $this->json(['ok'=>true,'html'=>$html,'empty'=>empty($ords),'pendingCount'=>$pc]);break;
                case 'refresh':EntStock::refreshPrices();$this->json(['ok'=>true,'stocks'=>EntStock::all()]);break;
                case 'loan':
                    if(EntConfig::get('loan_enabled','1')!=='1')$this->json(['ok'=>false,'error'=>'借贷未开启']);
                    $max=(float)EntConfig::get('loan_max','500000');$amt=(float)$_POST['amount'];$rate=(float)EntConfig::get('loan_rate','5');$method=$_POST['method']??'equal';
                    if($amt<=0||$amt>$max)$this->json(['ok'=>false,'error'=>"上限{$max}"]);
                    $acc2=EntAccount::get($uid);if((float)$acc2['balance']>0||!empty(EntPosition::allByUser($uid)))$this->json(['ok'=>false,'error'=>'还有资产无需借贷']);
                    $tr=0;if($method==='equal')$tr=$amt+($amt*$rate/100);elseif($method==='interest_first')$tr=$amt+($amt*$rate/100);else $tr=$amt+($amt*$rate/100*0.55);
                    $dd=date('Y-m-d',strtotime('+12 months'));EntAccount::loan($uid,$amt);
                    Database::getConnection()->prepare("INSERT INTO ent_loans (user_id,amount,interest_rate,repay_method,total_repayable,due_date) VALUES (:u,:a,:r,:m,:t,:d)")->execute([':u'=>$uid,':a'=>$amt,':r'=>$rate,':m'=>$method,':t'=>round($tr,2),':d'=>$dd]);
                    $this->json(['ok'=>true,'message'=>"借贷{$amt}成功"]);break;
                case 'repay_loan':
                    $lid=(int)$_POST['loan_id'];$pdo2=Database::getConnection();
                    $ln=$pdo2->prepare("SELECT * FROM ent_loans WHERE id=:i AND user_id=:u");$ln->execute([':i'=>$lid,':u'=>$uid]);$ln=$ln->fetch(\PDO::FETCH_ASSOC);
                    if(!$ln)break;$remain=round((float)$ln['total_repayable']-(float)$ln['repaid'],2);$acc3=EntAccount::get($uid);
                    if((float)$acc3['balance']<$remain)$this->json(['ok'=>false,'error'=>"余额不足需¥{$remain}"]);
                    EntAccount::updateBalance($uid,round((float)$acc3['balance']-$remain,2));
                    $pdo2->prepare("UPDATE ent_loans SET repaid=total_repayable,status='repaid' WHERE id=:i")->execute([':i'=>$lid]);
                    $this->json(['ok'=>true,'message'=>'还款成功']);break;
                case 'bankrupt':
                    $pdo2=Database::getConnection();
                    $pdo2->prepare("DELETE FROM ent_positions WHERE user_id=:u")->execute([':u'=>$uid]);
                    $pdo2->prepare("UPDATE ent_orders SET status='cancelled' WHERE user_id=:u AND status='pending'")->execute([':u'=>$uid]);
                    $pdo2->prepare("UPDATE ent_loans SET status='overdue' WHERE user_id=:u AND status='active'")->execute([':u'=>$uid]);
                    $pdo2->prepare("UPDATE ent_accounts SET balance=1000000,loan_amount=0,loan_count=0,bankruptcy_count=bankruptcy_count+1 WHERE user_id=:u")->execute([':u'=>$uid]);
                    $this->json(['ok'=>true,'message'=>'破产清算完成']);break;
                default:
                    if(!$isAdmin)$this->json(['ok'=>false]);
                    if($a==='update_stock'){EntStock::update((int)$_POST['id'],$_POST);$this->json(['ok'=>true]);}
                    elseif($a==='create_stock'){$pdo3=Database::getConnection();$bp=floatval($_POST['base_price']??0);$cp=floatval($_POST['current_price']??0);$ip=floatval($_POST['ipo_price']??0);$pdo3->prepare("INSERT INTO ent_stocks (symbol,name,sector,description,listed_date,ipo_price,base_price,current_price,total_shares,limit_per_user) VALUES (:s,:n,:sc,:d,:ld,:ip,:bp,:cp,:ts,:lp)")->execute([':s'=>$_POST['symbol']??'',':n'=>$_POST['name']??'',':sc'=>$_POST['sector']??'',':d'=>$_POST['description']??'',':ld'=>$_POST['listed_date']??null,':ip'=>$ip,':bp'=>$bp,':cp'=>$cp,':ts'=>$_POST['total_shares']??'1000000000',':lp'=>$_POST['limit_per_user']??'100000']);$this->json(['ok'=>true,'message'=>'已新增']);}
                    elseif($a==='publish_news'){EntNews::create((int)$_POST['stock_id'],$_POST['title']??'',$_POST['content']??'',$_POST['effect']??'positive',(int)$_POST['strength'],(int)$_POST['hours'],$_POST['scheduled_at']??null);$this->json(['ok'=>true,'message'=>'新闻已发布']);}
                    elseif($a==='delete_news'){EntNews::delete((int)$_POST['id']);$this->json(['ok'=>true,'message'=>'已删除']);}
                    elseif($a==='save_config'){EntConfig::set('loan_enabled',$_POST['loan_enabled']??'0');EntConfig::set('loan_max',$_POST['loan_max']??'500000');EntConfig::set('loan_rate',$_POST['loan_rate']??'5');$this->json(['ok'=>true,'message'=>'配置已保存']);}
                    elseif($a==='save_notice'){EntConfig::set('system_notice',$_POST['notice']??'');$this->json(['ok'=>true,'message'=>'公告已保存']);}
                    elseif($a==='admin_reward'){
                        $tu=(int)$_POST['user_id'];$tsid=(int)$_POST['stock_id'];$tq=(int)$_POST['quantity'];
                        $stock=EntStock::find($tsid);if(!$stock||$tu<=0||$tq==0)$this->json(['ok'=>false]);
                        if($tq>0){EntPosition::buy($tu,$tsid,$tq,(float)$stock['current_price']);$this->json(['ok'=>true,'message'=>'已奖励'.$tq.'股']);}
                        else{$pos=EntPosition::get($tu,$tsid);$rem=abs($tq);if(!$pos||(int)$pos['quantity']<$rem)EntPosition::sell($tu,$tsid,(int)($pos['quantity']??0));else EntPosition::sell($tu,$tsid,$rem);$this->json(['ok'=>true,'message'=>'已扣减'.abs($tq).'股']);}
                    }
                    else $this->json(['ok'=>false]);
            }ob_get_clean();}catch(\Throwable $e){if(ob_get_level())ob_end_clean();$this->json(['ok'=>false,'error'=>$e->getMessage()]);}
    }

    private static function genKline(int $sid, string $scale='1d'):array {
        $s=EntStock::find($sid);if(!$s)return[];
        $cur=(float)$s['current_price'];$base=(float)$s['base_price'];$data=[];$now=time();
        if($scale==='1m'){$price=$cur*0.995;$openTs=strtotime(date('Y-m-d').' 08:00:00');for($ts=$openTs;$ts<=$now;$ts+=300){$chg=mt_rand(-10,10)/100;$open=$price;$close=max(0.01,$price+$chg);$data[]=['time'=>$ts,'open'=>round($open,2),'high'=>round(max($open,$close),2),'low'=>round(min($open,$close),2),'close'=>round($close,2),'volume'=>mt_rand(10,500)*100];$price=$close;}if(empty($data))$data[]=['time'=>$openTs,'open'=>$cur,'high'=>$cur,'low'=>$cur,'close'=>$cur,'volume'=>0];$data[count($data)-1]['close']=$cur;}
        elseif(in_array($scale,['1d','5d','1w','1M'])){$days=$scale==='1d'?60:($scale==='5d'?5:($scale==='1w'?7:30));$price=$base+($cur-$base)*0.3;for($i=$days;$i>=0;$i--){$ds=date('Y-m-d',strtotime("-{$i} days"));$chg=(mt_rand(-400,400)/100)+(($cur-$price)/max($i+1,1)*0.3);$open=$price;$close=max(0.01,$price+$chg);$data[]=['time'=>$ds,'open'=>round($open,2),'high'=>round(max($open,$close)*(1+mt_rand(0,300)/10000),2),'low'=>round(min($open,$close)*(1-mt_rand(0,300)/10000),2),'close'=>round($close,2),'volume'=>mt_rand(100,9999)*100];$price=$close;}$data[count($data)-1]['close']=$cur;}
        return $data;
    }
}
