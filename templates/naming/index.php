<ul class="nav nav-tabs" id="namingTabs" style="margin-top:-1.5rem">
    <li class="nav-item"><a class="nav-link active" data-bs-toggle="tab" href="#tab-generate">取名</a></li>
    <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#tab-analyze">测名</a></li>
</ul>

<div class="tab-content">
<div class="tab-pane fade show active" id="tab-generate">
    <div class="card border-0 shadow-sm mb-3">
        <div class="card-body">
            <form id="generateForm" onsubmit="return doGenerate()">
                <div class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label">姓氏 <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="surname" id="g_surname" maxlength="2" placeholder="如：张" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">字辈 <span class="text-muted">(选填)</span></label>
                        <input type="text" class="form-control" name="generation_char" id="g_generation" maxlength="1" placeholder="如：明">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">性别</label>
                        <select class="form-select" name="gender" id="g_gender">
                            <option value="n">不限</option>
                            <option value="m">男孩</option>
                            <option value="f">女孩</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">名字字数</label>
                        <select class="form-select" name="name_len" id="g_name_len">
                            <option value="2" selected>双字名</option>
                            <option value="1">单字名</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">五行偏好</label>
                        <select class="form-select" name="prefer_wuxing" id="g_wuxing">
                            <option value="">不限</option>
                            <option value="金">金</option>
                            <option value="木">木</option>
                            <option value="水">水</option>
                            <option value="火">火</option>
                            <option value="土">土</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">生成数量</label>
                        <select class="form-select" name="count" id="g_count">
                            <option value="20">20个</option>
                            <option value="30">30个</option>
                            <option value="50" selected>50个</option>
                            <option value="100">100个</option>
                        </select>
                    </div>
                    <div class="col-12">
                        <label class="form-label">期望寓意（可多选）</label>
                        <div id="tagSelector" class="d-flex flex-wrap gap-2">
                            <?php
                            $tagLabels = [
                                '智慧' => '智慧', '才学' => '才学', '品德' => '品德',
                                '安康' => '安康', '吉祥' => '吉祥', '美丽' => '美丽',
                                '大气' => '大气', '文雅' => '文雅', '坚强' => '坚强',
                                '温柔' => '温柔', '自然' => '自然', '光明' => '光明',
                            ];
                            foreach ($tagLabels as $tag => $label): ?>
                                <label class="tag-item border rounded-pill px-3 py-1" style="cursor:pointer;font-size:0.85rem;user-select:none;">
                                    <input type="checkbox" name="prefer_tags[]" value="<?= $tag ?>" class="d-none">
                                    <span class="tag-check">○</span> <?= $label ?>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <div class="col-12">
                        <button type="submit" class="btn btn-primary" id="g_btn">开始取名</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div id="generateResults"></div>
</div>

<div class="tab-pane fade" id="tab-analyze">
    <div class="card border-0 shadow-sm mb-3">
        <div class="card-body">
            <div class="row g-3 align-items-end">
                <div class="col-md-6">
                    <label class="form-label">姓名</label>
                    <input type="text" class="form-control" id="a_name" maxlength="4" placeholder="如：张伟">
                </div>
                <div class="col-md-2">
                    <button class="btn btn-primary w-100" onclick="doAnalyze()">测名</button>
                </div>
            </div>
        </div>
    </div>
    <div id="analyzeResults"></div>
</div>
</div>

<style>
.tag-item{
    color:#475569!important;
    border-color:#cbd5e1!important;
    background:rgba(255,255,255,0.7)!important;
    backdrop-filter:blur(4px);
    transition:all .15s;
}
.tag-item:hover{border-color:#94a3b8!important;background:rgba(255,255,255,0.85)!important}
.tag-check{font-size:0.75rem;margin-right:2px}
.tag-active{
    background:#0d6efd!important;
    color:#fff!important;
    border-color:#0d6efd!important;
}
.tag-active .tag-check{content:'●'}
body.theme-dark .tag-item{
    color:#cbd5e1!important;
    border-color:rgba(148,163,184,0.3)!important;
    background:rgba(30,41,59,0.5)!important;
}
body.theme-dark .tag-item:hover{border-color:rgba(148,163,184,0.5)!important;background:rgba(30,41,59,0.7)!important}
body.theme-dark .tag-active{
    background:#3b82f6!important;
    color:#fff!important;
    border-color:#3b82f6!important;
}
.fortune-吉{color:#16a34a}.fortune-大吉{color:#16a34a;font-weight:700}.fortune-凶{color:#dc2626}.fortune-半吉{color:#ca8a04}.fortune-平{color:#6b7280}
.wx-金{color:#ca8a04}.wx-木{color:#16a34a}.wx-水{color:#2563eb}.wx-火{color:#dc2626}.wx-土{color:#92400e}
.score-ring{width:80px;height:80px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:1.5rem;font-weight:800;border:4px solid}
</style>

<script>
document.querySelectorAll('#tagSelector label').forEach(function(lbl){
    var cb=lbl.querySelector('input[type=checkbox]');
    var ck=lbl.querySelector('.tag-check');
    cb.addEventListener('change',function(){
        lbl.classList.toggle('tag-active',cb.checked);
        ck.textContent=cb.checked?'●':'○';
    });
});

var genInput=document.getElementById('g_generation');
var nameLenSel=document.getElementById('g_name_len');
var savedNameLen=nameLenSel.value;
genInput.addEventListener('input',function(){
    if(this.value.trim()){
        savedNameLen=nameLenSel.value;
        nameLenSel.value='1';
        nameLenSel.disabled=true;
        nameLenSel.title='填写字辈后自动为单字名';
    }else{
        nameLenSel.value=savedNameLen;
        nameLenSel.disabled=false;
        nameLenSel.title='';
    }
});

function doGenerate(){
    var fd=new FormData(document.getElementById('generateForm'));
    fd.append('action','generate');
    var btn=document.getElementById('g_btn');btn.disabled=true;btn.textContent='取名中...';
    fetch('/public/index.php?route=naming-api',{method:'POST',headers:{'X-Requested-With':'XMLHttpRequest'},body:fd})
    .then(r=>r.json()).then(data=>{
        if(!data.ok){alert(data.error||'取名失败');return;}
        renderGenerateResults(data.results);
    }).catch(e=>alert('请求失败:'+e.message)).finally(()=>{btn.disabled=false;btn.textContent='开始取名';});
    return false;
}

function renderGenerateResults(results){
    var el=document.getElementById('generateResults');
    if(!results||results.length===0){el.innerHTML='<div class="text-muted text-center py-4">未找到符合条件的名字，请调整筛选条件。</div>';return;}
    var html='<div class="row g-3">';
    results.forEach(function(r,i){
        var sc=r.score>=90?'border-success':r.score>=80?'border-primary':'border-warning';
        html+='<div class="col-md-4"><div class="card border-2 '+sc+' shadow-sm h-100"><div class="card-body">';
        html+='<div class="d-flex justify-content-between align-items-start mb-2">';
        html+='<div style="font-size:1.6rem;font-weight:800;letter-spacing:2px">'+r.name+'</div>';
        html+='<div class="score-ring" style="border-color:'+(r.score>=90?'#16a34a':r.score>=80?'#2563eb':'#ca8a04')+';color:'+(r.score>=90?'#16a34a':r.score>=80?'#2563eb':'#ca8a04')+'">'+r.score+'</div>';
        html+='</div>';
        html+='<div class="small mb-2">';
        r.chars.forEach(function(c){
            html+='<span class="badge bg-light text-dark me-1">'+c.char+' <span class="wx-'+c.wuxing+'">'+c.wuxing+'</span> '+c.strokes+'画 '+c.pinyin+'</span>';
        });
        html+='</div>';
        html+='<div class="small text-muted">';
        html+='天格<span class="fortune-'+r.detail.tian.fortune+'">'+r.tian_ge+'('+r.detail.tian.fortune+')</span> ';
        html+='人格<span class="fortune-'+r.detail.ren.fortune+'">'+r.ren_ge+'('+r.detail.ren.fortune+')</span> ';
        html+='地格<span class="fortune-'+r.detail.di.fortune+'">'+r.di_ge+'('+r.detail.di.fortune+')</span><br>';
        html+='外格<span class="fortune-'+r.detail.wai.fortune+'">'+r.wai_ge+'('+r.detail.wai.fortune+')</span> ';
        html+='总格<span class="fortune-'+r.detail.zong.fortune+'">'+r.zong_ge+'('+r.detail.zong.fortune+')</span><br>';
        html+='三才：<span class="fortune-'+r.detail.sancai.fortune+'">'+r.detail.sancai.key+' '+r.detail.sancai.fortune+'</span>';
        html+='</div>';
        html+='</div></div></div>';
    });
    html+='</div>';
    el.innerHTML=html;
}

function doAnalyze(){
    var name=document.getElementById('a_name').value.trim();
    if(name.length<2){alert('请输入至少2个字');return;}
    fetch('/public/index.php?route=naming-api',{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded','X-Requested-With':'XMLHttpRequest'},
        body:'action=analyze&name='+encodeURIComponent(name)
    }).then(r=>r.json()).then(data=>{
        if(!data.ok){alert(data.error||'测名失败');return;}
        renderAnalyzeResult(data);
    }).catch(e=>alert('请求失败:'+e.message));
}

function renderAnalyzeResult(data){
    var el=document.getElementById('analyzeResults');
    var sc=data.score>=90?'border-success':data.score>=80?'border-primary':'border-warning';
    var h='<div class="card border-2 '+sc+' shadow-sm"><div class="card-body">';
    h+='<div class="d-flex align-items-center mb-3">';
    h+='<div style="font-size:2rem;font-weight:800;letter-spacing:4px" class="me-4">'+document.getElementById('a_name').value+'</div>';
    h+='<div class="score-ring" style="border-color:'+(data.score>=90?'#16a34a':data.score>=80?'#2563eb':'#ca8a04')+';color:'+(data.score>=90?'#16a34a':data.score>=80?'#2563eb':'#ca8a04')+'">'+data.score+'</div>';
    h+='</div>';
    h+='<div class="mb-3">';
    data.chars.forEach(function(c,i){
        var label=i===0?'姓':'名';
        h+='<div class="d-inline-block me-3 mb-1"><span class="badge bg-light text-dark" style="font-size:0.9rem;padding:6px 12px">';
        h+='<strong>'+c.char+'</strong> '+c.pinyin+' | '+c.strokes+'画 | <span class="wx-'+c.wuxing+'">'+c.wuxing+'</span>';
        if(c.tags&&c.tags.length)h+=' | '+c.tags.slice(0,3).join('/');
        h+='</span></div>';
    });
    h+='</div>';
    if(data.wuxing_missing&&data.wuxing_missing.length){
        h+='<div class="alert alert-warning py-1 small mb-3">五行缺：<strong>'+data.wuxing_missing.join('、')+'</strong>，取名时可考虑补全。</div>';
    }
    h+='<table class="table table-sm small mb-3"><thead><tr><th>格</th><th>笔画</th><th>五行</th><th>吉凶</th><th>含义</th></tr></thead><tbody>';
    var geNames={tian:'天格',ren:'人格',di:'地格',wai:'外格',zong:'总格'};
    ['tian','ren','di','wai','zong'].forEach(function(k){
        var g=data.detail[k];
        h+='<tr><td><strong>'+geNames[k]+'</strong></td><td>'+g.value+'</td><td><span class="wx-'+g.wuxing+'">'+(g.wuxing||'-')+'</span></td>';
        h+='<td><span class="fortune-'+g.fortune+'">'+g.fortune+'</span></td><td class="text-muted">'+g.desc+'</td></tr>';
    });
    h+='</tbody></table>';
    h+='<div class="small"><strong>三才配置：</strong><span class="fortune-'+data.detail.sancai.fortune+'">'+data.detail.sancai.key+' '+data.detail.sancai.fortune+'</span> — '+data.detail.sancai.desc+'</div>';
    h+='</div></div>';
    el.innerHTML=h;
}
</script>
