<?php
namespace App\Controller;

use App\Service\Config;
use App\Service\Upload;
use App\Model\KbSpace;
use App\Model\KbDocument;
use App\Model\KbDocVersion;

class KbController
{
    private function requireLogin(): int
    {
        $uid = (int)($_SESSION['user_id'] ?? 0);
        if ($uid <= 0) { header('Location: /public/index.php?route=login'); exit; }
        return $uid;
    }
    private function render(string $view, array $params = []): void
    {
        extract($params); $appName = Config::get('app.name');
        $_SESSION['current_page_title'] = $params['pageTitle'] ?? '知识库';
        include __DIR__ . '/../../templates/layout_main.php';
    }
    private function renderStandalone(string $view, array $params = []): void
    {
        extract($params); $appName = Config::get('app.name');
        include __DIR__ . '/../../templates/kb/' . $view . '.php';
    }
    private function json(array $data): void
    { while (ob_get_level()) ob_end_clean(); header('Content-Type: application/json; charset=utf-8'); echo json_encode($data, JSON_UNESCAPED_UNICODE); exit; }

    public function editor(): void { $uid=$this->requireLogin();$s=KbSpace::getOrCreate($uid);$did=(int)($_GET['doc']??0);$t=KbDocument::getTree((int)$s['id'],$uid);$d=$did>0?KbDocument::findById($did,$uid):null;$this->render('kb/editor',['pageTitle'=>'知识库编辑','space'=>$s,'tree'=>$t,'currentDoc'=>$d,'currentDocId'=>$did]); }
    public function read(): void { $uid=$this->requireLogin();$s=KbSpace::getOrCreate($uid);$did=(int)($_GET['doc']??0);$t=KbDocument::getTree((int)$s['id'],$uid);$d=null;if($did>0)$d=KbDocument::findById($did,$uid);elseif(!empty($t)){foreach($t as $n){if(empty($n['is_folder'])){$d=KbDocument::findById((int)$n['id'],$uid);break;}}}$this->render('kb/read',['pageTitle'=>'知识库','space'=>$s,'tree'=>$t,'currentDoc'=>$d]); }
    public function share(): void { $tk=trim($_GET['token']??'');if($tk===''){http_response_code(404);echo '链接无效';exit;}$d=KbDocument::findByToken($tk);if(!$d){http_response_code(404);echo '文档不存在或已取消分享';exit;}$this->renderStandalone('share',['doc'=>$d,'appName'=>Config::get('app.name')]); }

    public function api(): void { $uid=$this->requireLogin();if($_SERVER['REQUEST_METHOD']!=='POST'){$this->json(['ok'=>false,'error'=>'无效请求']);}$a=$_POST['action']??'';try{ob_start();switch($a){case'create_doc':$this->createDoc($uid);break;case'update_doc':$this->updateDoc($uid);break;case'delete_doc':$this->deleteDoc($uid);break;case'get_tree':$this->getTree($uid);break;case'get_doc':$this->getDoc($uid);break;case'toggle_share':$this->toggleShare($uid);break;case'reorder':$this->reorder($uid);break;case'search':$this->search($uid);break;case'upload_image':$this->uploadImage($uid);break;case'save_space_config':$this->saveSpaceConfig($uid);break;case'get_versions':$this->getVersions($uid);break;case'restore_version':$this->restoreVersion($uid);break;default:$this->json(['ok'=>false,'error'=>'未知操作']);}ob_get_clean();}catch(\Throwable $e){if(ob_get_level())ob_end_clean();$this->json(['ok'=>false,'error'=>'操作异常: '.$e->getMessage()]);}}

    private function createDoc(int $uid): void { $s=KbSpace::getOrCreate($uid);$pid=(int)($_POST['parent_id']??0);$f=(int)($_POST['is_folder']??0);$t=trim($_POST['title']??'')?:($f?'新文件夹':'无标题');$so=KbDocument::getMaxSortOrder((int)$s['id'],$pid,$uid)+1;$id=KbDocument::create((int)$s['id'],$uid,['parent_id'=>$pid,'title'=>$t,'is_folder'=>$f,'sort_order'=>$so]);$this->json(['ok'=>true,'id'=>$id,'title'=>$t]); }
    private function updateDoc(int $uid): void { $id=(int)($_POST['id']??0);$d=KbDocument::findById($id,$uid);if(!$d)$this->json(['ok'=>false,'error'=>'文档不存在']);$dt=[];if(isset($_POST['title']))$dt['title']=trim($_POST['title']);if(isset($_POST['content'])){$dt['content']=$_POST['content'];$dt['content_html']=$_POST['content_html']??'';}if(isset($_POST['parent_id']))$dt['parent_id']=(int)$_POST['parent_id'];if(!empty($dt))KbDocument::update($id,$uid,$dt);if(isset($_POST['content'])){$sp=KbSpace::getOrCreate($uid);if(!empty($sp['version_enabled'])){$mv=max(1,(int)($sp['version_max']??10));$vc=KbDocVersion::countByDoc($id);if($vc===0||$d['content']!==$_POST['content']){KbDocVersion::create($id,$uid,$d['title'],$_POST['content']);KbDocVersion::cleanOldVersions($id,$mv);}}}$this->json(['ok'=>true]); }
    private function deleteDoc(int $uid): void { KbDocument::deleteRecursive((int)($_POST['id']??0),$uid);$this->json(['ok'=>true]); }
    private function getTree(int $uid): void { $s=KbSpace::getOrCreate($uid);$t=KbDocument::getTree((int)$s['id'],$uid);$this->json(['ok'=>true,'tree'=>$t,'space'=>$s]); }
    private function getDoc(int $uid): void { $id=(int)($_POST['id']??$_GET['id']??0);$d=KbDocument::findById($id,$uid);if(!$d)$this->json(['ok'=>false,'error'=>'文档不存在']);$this->json(['ok'=>true,'doc'=>$d]); }
    private function toggleShare(int $uid): void { $id=(int)($_POST['id']??0);$d=KbDocument::findById($id,$uid);if(!$d)$this->json(['ok'=>false,'error'=>'文档不存在']);if(!empty($d['is_public'])){KbDocument::update($id,$uid,['is_public'=>0,'share_token'=>null]);$this->json(['ok'=>true,'shared'=>false,'message'=>'已取消分享']);}else{$tk=bin2hex(random_bytes(32));KbDocument::update($id,$uid,['is_public'=>1,'share_token'=>$tk]);$u=Config::get('app.site_url','').'/public/index.php?route=kb-share&token='.$tk;$this->json(['ok'=>true,'shared'=>true,'token'=>$tk,'url'=>$u,'message'=>'已开启分享']);}}
    private function reorder(int $uid): void { $o=$_POST['orders']??'';if(is_string($o))$o=json_decode($o,true);if(!is_array($o))$this->json(['ok'=>false,'error'=>'参数错误']);foreach($o as $it){$i=(int)($it['id']??0);$s=(int)($it['sort_order']??0);$p=(int)($it['parent_id']??-1);if($i<=0)continue;$dt=['sort_order'=>$s];if($p>=0)$dt['parent_id']=$p;KbDocument::update($i,$uid,$dt);}$this->json(['ok'=>true]); }
    private function search(int $uid): void { $s=KbSpace::getOrCreate($uid);$kw=trim($_POST['q']??$_GET['q']??'');if($kw==='')$this->json(['ok'=>true,'results'=>[]]);$this->json(['ok'=>true,'results'=>KbDocument::search((int)$s['id'],$uid,$kw)]); }

    private function uploadImage(int $uid): void {
        $did=(int)($_GET['doc_id']??0);if($did<=0)$this->json(['success'=>0,'message'=>'文档ID无效']);
        if(!KbDocument::findById($did,$uid))$this->json(['success'=>0,'message'=>'文档不存在']);
        $file=$_FILES['image']??$_FILES['editormd-image-file']??null;
        if(!$file||$file['error']!==UPLOAD_ERR_OK)$this->json(['success'=>0,'message'=>'上传失败']);
        $p=Upload::saveKbImage($uid,$did,$file);if(!$p)$this->json(['success'=>0,'message'=>'保存失败']);
        $this->json(['success'=>1,'url'=>'/uploads/'.$p]);
    }

    private function saveSpaceConfig(int $uid): void { $s=KbSpace::getOrCreate($uid);$d=[];if(isset($_POST['name']))$d['name']=trim($_POST['name']);if(isset($_POST['description']))$d['description']=trim($_POST['description']);if(isset($_POST['version_enabled']))$d['version_enabled']=(int)$_POST['version_enabled'];if(isset($_POST['version_max']))$d['version_max']=max(1,min(100,(int)$_POST['version_max']));if(!empty($d))KbSpace::update((int)$s['id'],$uid,$d);$this->json(['ok'=>true,'message'=>'配置已保存']); }
    private function getVersions(int $uid): void { $this->json(['ok'=>true,'versions'=>KbDocVersion::listByDoc((int)($_POST['doc_id']??0),$uid)]); }
    private function restoreVersion(int $uid): void { $vid=(int)($_POST['version_id']??0);$v=KbDocVersion::findById($vid,$uid);if(!$v)$this->json(['ok'=>false,'error'=>'版本不存在']);$d=KbDocument::findById((int)$v['doc_id'],$uid);if(!$d)$this->json(['ok'=>false,'error'=>'文档不存在']);KbDocument::update((int)$d['id'],$uid,['title'=>$v['title'],'content'=>$v['content']]);$this->json(['ok'=>true,'content'=>$v['content'],'title'=>$v['title'],'message'=>'已恢复到 v'.$v['version_num']]); }
}
