<?php
$ym = $ym ?? date('Y-m');
$cfg = $cfg ?? [];
$perf = $perf ?? null;
?>
<div class="container mt-4">
  <h3>📊 绩效管理</h3>
  <p class="text-muted">录入月度绩效数据，用于薪资计算</p>

  <form method="post" action="/public/index.php?route=attendance-api" class="row g-3">
    <input type="hidden" name="action" value="save_performance">
    
    <div class="col-md-2">
      <label>月份</label>
      <input type="month" name="month" class="form-control" value="<?=htmlspecialchars($ym)?>" required>
    </div>
    
    <div class="col-md-2">
      <label>销售额</label>
      <input type="number" name="sales" step="0.01" class="form-control" value="<?=htmlspecialchars((string)($perf['sales_amount']??0))?>" placeholder="0.00">
    </div>
    
    <div class="col-md-2">
      <label>提成点（如0.05=5%）</label>
      <input type="number" name="rate" step="0.001" class="form-control" value="<?=htmlspecialchars((string)($perf['commission_rate']??0))?>" placeholder="0.05">
    </div>
    
    <div class="col-md-2">
      <label>激励奖金</label>
      <input type="number" name="bonus" step="0.01" class="form-control" value="<?=htmlspecialchars((string)($perf['bonus']??0))?>" placeholder="0.00">
    </div>
    
    <div class="col-md-2">
      <label>最终绩效（自动计算）</label>
      <input type="text" class="form-control bg-light" value="<?=htmlspecialchars((string)($perf['performance']??0))?>" readonly>
    </div>
    
    <div class="col-md-2 d-flex align-items-end">
      <button type="submit" class="btn btn-primary w-100">保存</button>
    </div>
    
    <div class="col-12">
      <label>其它绩效指标（JSON格式，用于个人回看）</label>
      <textarea name="metrics" class="form-control" rows="3" placeholder='{"客户满意度":"95%","平均回复时间":"5分钟"}'><?=htmlspecialchars($perf['other_metrics']??'')?></textarea>
    </div>
  </form>

  <hr class="my-4">
  
  <h5>📋 历史记录</h5>
  <table class="table table-bordered">
    <thead>
      <tr>
        <th>月份</th>
        <th>销售额</th>
        <th>提成点</th>
        <th>激励奖金</th>
        <th>最终绩效</th>
        <th>操作</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach($history??[] as $h):?>
      <tr>
        <td><?=htmlspecialchars($h['month'])?></td>
        <td><?=htmlspecialchars((string)$h['sales_amount'])?></td>
        <td><?=htmlspecialchars((string)$h['commission_rate'])?></td>
        <td><?=htmlspecialchars((string)$h['bonus'])?></td>
        <td><strong><?=htmlspecialchars((string)$h['performance'])?></strong></td>
        <td><button class="btn btn-sm btn-danger" onclick="del(<?=$h['id']?>)">删除</button></td>
      </tr>
      <?php endforeach;?>
    </tbody>
  </table>
</div>

<script>
document.querySelector('form').addEventListener('input',function(){
  const s=parseFloat(this.sales.value)||0;
  const r=parseFloat(this.rate.value)||0;
  const b=parseFloat(this.bonus.value)||0;
  this.querySelector('.bg-light').value=(s*r+b).toFixed(2);
});
function del(id){
  if(confirm('确定删除？')){
    fetch('/public/index.php?route=attendance-api',{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},body:'action=del_performance&id='+id}).then(()=>location.reload());
  }
}
</script>
