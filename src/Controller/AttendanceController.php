<?php
namespace App\Controller;
use App\Service\Config;
use App\Service\Database;
use App\Model\AttShift;
use App\Model\AttRecord;
use App\Model\SalaryConfig;
use App\Model\SalaryDeduction;
use App\Model\SalarySocial;
use App\Model\SalaryActual;
use App\Model\AttCompany;
use App\Model\AttSchedule;
use App\Model\AttLeave;
use App\Model\AttPerformance;

class AttendanceController
{
    public function __construct() {$this->initTables();}
    private function initTables():void {
        $pdo=Database::getConnection();
        $pdo->exec('CREATE TABLE IF NOT EXISTS att_leaves (id INT AUTO_INCREMENT PRIMARY KEY,user_id INT NOT NULL,leave_date DATE NOT NULL,hours DECIMAL(5,1) NOT NULL DEFAULT 0,note VARCHAR(255),created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,UNIQUE KEY uk_user_date (user_id,leave_date))');
        $pdo->exec('CREATE TABLE IF NOT EXISTS att_performance (id INT AUTO_INCREMENT PRIMARY KEY,user_id INT NOT NULL,month CHAR(7) NOT NULL,sales_amount DECIMAL(18,2) DEFAULT 0,commission_rate DECIMAL(5,3) DEFAULT 0,bonus DECIMAL(18,2) DEFAULT 0,performance DECIMAL(18,2) DEFAULT 0,other_metrics TEXT,created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,UNIQUE KEY uk_user_month (user_id,month))');
    }
    private function requireLogin(): int { $uid=(int)($_SESSION['user_id']??0); if($uid<=0){header('Location:/public/index.php?route=login');exit;} return $uid; }
    private function render(string $view,array $p=[]):void { extract($p); $appName=Config::get('app.name'); $_SESSION['current_page_title']=$p['pageTitle']??'考勤'; include __DIR__.'/../../templates/layout_main.php'; }
    private function json(array $d):void { while(ob_get_level())ob_end_clean(); header('Content-Type: application/json; charset=utf-8'); echo json_encode($d,JSON_UNESCAPED_UNICODE); exit; }

    public function shift():void {
        $uid=$this->requireLogin();
        $shifts=AttShift::byUser($uid);
        $ym=$_GET['ym']??date('Y-m'); $year=(int)substr($ym,0,4);$month=(int)substr($ym,5,2);
        $records=AttRecord::getMonth($uid,$ym);
        $leaves=AttLeave::getMonth($uid,$ym);
        $stats=AttRecord::stats($uid,$ym);
        $sc2=AttSchedule::get($uid,$ym);$shouldDays=0;foreach($sc2 as $sd){$n=$sd['name']??'';if($n!=='休息'&&$n!=='')$shouldDays++;}
        $stats['should']=$shouldDays?:(4*(int)ceil($daysInMonth/7));
        $company=AttCompany::getActive($uid);
        $companies=AttCompany::allByUser($uid);
        $this->render('attendance/shift',['pageTitle'=>'出勤管理','shifts'=>$shifts,'records'=>$records,'leaves'=>$leaves,'stats'=>$stats,'ym'=>$ym,'year'=>$year,'month'=>$month,'company'=>$company,'companies'=>$companies]);
    }

    public function schedule():void {
        $uid=$this->requireLogin();
        $shifts=AttShift::byUser($uid);
        $ym=$_GET['ym']??date('Y-m');
        $schedule=AttSchedule::get($uid,$ym);
        $this->render('attendance/schedule',['pageTitle'=>'排班管理','shifts'=>$shifts,'schedule'=>$schedule,'ym'=>$ym]);
    }

    public function salary():void {
        $uid=$this->requireLogin();
        $ym=$_GET['ym']??date('Y-m');
        $cfg=SalaryConfig::get($uid);
        $deds=SalaryDeduction::byUser($uid);
        $socials=SalarySocial::byUser($uid);
        $actual=SalaryActual::get($uid,$ym);
        $perfData=AttPerformance::get($uid,$ym);
        $totalDed=0;foreach($deds as $d)if($d['deduction_month']==$ym)$totalDed+=(float)$d['amount'];
        $totalSocial=0;$totalFund=0;
        foreach($socials as $sc){if($sc['start_date']<=$ym.'-01'&&(!$sc['end_date']||$sc['end_date']>=$ym.'-01')){$totalSocial+=(float)$sc['social_amount'];$totalFund+=(float)$sc['fund_amount'];}}
        $base=0;$perf=0;$sub=0;if($cfg){$base=(float)$cfg['base_salary'];$sub=(float)$cfg['subsidy'];}
        $perf=$perfData?$perfData['performance']:0;
        $income=$base+$perf+$sub;$deduct=$totalDed+$totalSocial+$totalFund;$net=$income-$deduct;$actAmt=$actual?$actual['actual_amount']:0;
        $quarter=$this->getQuarterStats($uid,$ym);
        $half=$this->getHalfStats($uid,$ym);
        $yearStats=$this->getYearStats($uid,$ym);
        $this->render('attendance/salary',['pageTitle'=>'薪资计算','ym'=>$ym,'cfg'=>$cfg,'deds'=>$deds,'socials'=>$socials,'actual'=>$actual,'income'=>$income,'deduct'=>$deduct,'net'=>$net,'actAmt'=>$actAmt,'totalDed'=>$totalDed,'totalSocial'=>$totalSocial,'totalFund'=>$totalFund,'base'=>$base,'perf'=>$perf,'sub'=>$sub,'quarter'=>$quarter,'half'=>$half,'yearStats'=>$yearStats]);
    }

    private function getQuarterStats(int $uid,string $ym):array{
        $year=substr($ym,0,4);$month=(int)substr($ym,5,2);
        $qStart=$month<=3?'01':($month<=6?'04':($month<=9?'07':'10'));
        $qEnd=$month<=3?'03':($month<=6?'06':($month<=9?'09':'12'));
        return $this->calcStats($uid,$year.'-'.$qStart,$year.'-'.$qEnd);
    }
    private function getHalfStats(int $uid,string $ym):array{
        $year=substr($ym,0,4);
        return (int)substr($ym,5,2)<=6?$this->calcStats($uid,$year.'-01',$year.'-06'):$this->calcStats($uid,$year.'-07',$year.'-12');
    }
    private function getYearStats(int $uid,string $ym):array{
        $year=substr($ym,0,4);
        return $this->calcStats($uid,$year.'-01',$year.'-12');
    }
    private function calcStats(int $uid,string $start,string $end):array{
        $pdo=Database::getConnection();
        $stmt=$pdo->prepare('SELECT SUM(base_salary) as base,SUM(performance) as perf,SUM(subsidy) as sub FROM salary_configs WHERE user_id=:u AND effective_from BETWEEN :s AND :e');
        $stmt->execute([':u'=>$uid,':s'=>$start,':e'=>$end]);
        $row=$stmt->fetch(\PDO::FETCH_ASSOC);
        return ['start'=>$start,'end'=>$end,'base'=>$row['base']?:0,'perf'=>$row['perf']?:0,'sub'=>$row['sub']?:0];
    }

    public function performance():void {
        $uid=$this->requireLogin();
        $ym=$_GET['ym']??date('Y-m');
        $perf=AttPerformance::get($uid,$ym);
        $pdo=Database::getConnection();
        $stmt=$pdo->prepare('SELECT * FROM att_performance WHERE user_id=:u ORDER BY month DESC');
        $stmt->execute([':u'=>$uid]);
        $history=$stmt->fetchAll(\PDO::FETCH_ASSOC);
        $this->render('attendance/performance',['pageTitle'=>'绩效管理','ym'=>$ym,'perf'=>$perf,'history'=>$history?:[]]);
    }

    public function deduction():void {
        $uid=$this->requireLogin();
        $ym=$_GET['ym']??date('Y-m');
        $deds=SalaryDeduction::byUser($uid);
        $this->render('attendance/deduction',['pageTitle'=>'扣款管理','deds'=>$deds,'ym'=>$ym]);
    }

    public function social():void {
        $uid=$this->requireLogin();
        $socials=SalarySocial::byUser($uid);
        $this->render('attendance/social',['pageTitle'=>'社保公积金','socials'=>$socials]);
    }

    public function api():void {
        $uid=$this->requireLogin();$a=$_POST['action']??'';
        try{ob_start();
            switch($a){
                case 'set_record': AttRecord::set($uid,$_POST['date'],0,$_POST['status'],$_POST['note']??'');$this->json(['ok'=>true,'message'=>'已记录']);break;
                case 'add_shift': AttShift::create($uid,['name'=>$_POST['name'],'start'=>$_POST['start'],'end'=>$_POST['end']]);$this->json(['ok'=>true,'message'=>'已添加']);break;
                case 'del_shift': AttShift::delete((int)$_POST['id'],$uid);$this->json(['ok'=>true,'message'=>'已删除']);break;
                case 'save_salary_cfg': SalaryConfig::save($uid,['base'=>$_POST['base'],'perf'=>$_POST['performance'],'sub'=>$_POST['subsidy'],'date'=>$_POST['effective_from']??date('Y-m-d')]);$this->json(['ok'=>true,'message'=>'已保存']);break;
                case 'add_deduction': SalaryDeduction::add($uid,$_POST['month'],(float)$_POST['amount'],$_POST['detail']??'');$this->json(['ok'=>true,'message'=>'已添加']);break;
                case 'del_deduction': SalaryDeduction::delete((int)$_POST['id'],$uid);$this->json(['ok'=>true,'message'=>'已删除']);break;
                case 'add_social': SalarySocial::add($uid,(float)$_POST['social'],(float)$_POST['fund'],$_POST['start'],$_POST['end']?:null);$this->json(['ok'=>true,'message'=>'已添加']);break;
                case 'del_social': SalarySocial::delete((int)$_POST['id'],$uid);$this->json(['ok'=>true,'message'=>'已删除']);break;
                case 'save_actual': SalaryActual::save($uid,$_POST['month'],(float)$_POST['amount'],$_POST['note']??'');$this->json(['ok'=>true,'message'=>'已保存']);break;
                case 'join_company': AttCompany::join($uid,$_POST['company_name'],$_POST['join_date']);$this->json(['ok'=>true,'message'=>'已加入']);break;
                case 'leave_company': AttCompany::leave($uid,$_POST['leave_date']);$this->json(['ok'=>true,'message'=>'已标记离职']);break;
                case 'clear_records': $ym=$_POST['ym']??date('Y-m');$pdo=Database::getConnection();$pdo->prepare("DELETE FROM att_records WHERE user_id=:u AND DATE_FORMAT(record_date,'%Y-%m')=:ym")->execute([':u'=>$uid,':ym'=>$ym]);$this->json(['ok'=>true,'message'=>'已清空']);break;
                case 'clear_record': $pdo=Database::getConnection();$pdo->prepare("DELETE FROM att_records WHERE user_id=:u AND record_date=:d")->execute([':u'=>$uid,':d'=>$_POST['date']]);$this->json(['ok'=>true,'message'=>'已清空']);break;
                case 'set_schedule': AttSchedule::set($uid,$_POST['date'],(int)$_POST['shift_id']);$this->json(['ok'=>true,'message'=>'已设置']);break;
                case 'save_leave': AttLeave::add($uid,$_POST['date'],(float)$_POST['hours'],$_POST['note']??'');$this->json(['ok'=>true,'message'=>'已保存']);break;
                case 'del_leave': AttLeave::delete($uid,$_POST['date']);$this->json(['ok'=>true,'message'=>'已删除']);break;
                case 'save_performance': AttPerformance::save($uid,$_POST['month'],(float)$_POST['sales'],(float)$_POST['rate'],(float)$_POST['bonus'],$_POST['metrics']??'');$this->json(['ok'=>true,'message'=>'已保存']);break;
                case 'del_performance': $pdo=Database::getConnection();$pdo->prepare('DELETE FROM att_performance WHERE id=:i AND user_id=:u')->execute([':i'=>(int)$_POST['id'],':u'=>$uid]);$this->json(['ok'=>true,'message'=>'已删除']);break;
                case 'get_performance': $perf=AttPerformance::get($uid,$_POST['month']);$this->json(['ok'=>true,'data'=>$perf]);break;
                default:$this->json(['ok'=>false]);
            }ob_get_clean();}catch(\Throwable $e){if(ob_get_level())ob_end_clean();$this->json(['ok'=>false,'error'=>$e->getMessage()]);}
    }
}

