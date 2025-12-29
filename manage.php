<?php
if (!defined('__TYPECHO_ROOT_DIR__')) {
    exit;
}
// 仅登录用户可访问
if (!$this->user->hasLogin()) {
    $this->response->redirect(Typecho_Common::url('/', $this->options->index));
}
$providers = TypechoOAuthLogin_Plugin::options();
$db = Typecho_Db::get();
$uid = $this->user->uid;
// 查询该用户所有绑定
$rows = $db->fetchAll($db->select()->from('table.oauth_user')->where('uid = ?', $uid));
$boundMap = array();
foreach ($rows as $r) {
    $boundMap[$r['type']] = $r;
}
// 品牌色映射
$brandColors = array(
    'qq' => '#2F88FF',
    'wechat' => '#07C160',
    'sina' => '#E6162D',
    'github' => '#171515',
    'google' => '#4285F4',
    'msn' => '#00A2ED',
    'baidu' => '#2932E1',
    'douban' => '#2AA515',
    'taobao' => '#FF6A00',
    'diandian' => '#0B76FF',
);
$indexUrl = $this->options->index;
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>第三方登录设置 - TypechoOAuthLogin</title>
    <link rel="stylesheet" href="/usr/plugins/TypechoOAuthLogin/else/bootstrap.min.css"/>
    <style>
        :root {
            --bg: #f6f7fb;
            --card-bg: #fff;
            --border: #e5e7eb;
            --text: #111827;
            --muted: #6b7280;
            --primary: #1677ff;
            --danger: #e74c3c;
            --success: #16a34a;
        }
        @media (prefers-color-scheme: dark) {
            :root {
                --bg: #0b1220;
                --card-bg: #0f172a;
                --border: #1f2937;
                --text: #e5e7eb;
                --muted: #9ca3af;
            }
        }
        body { background: var(--bg); color: var(--text); }
        .container-lg { max-width: 960px; }
        .page-header { display:flex; align-items:center; justify-content:space-between; margin:24px 0; }
        .page-title { font-weight:700; font-size:22px; margin:0; }
        .page-subtitle { color: var(--muted); font-size:14px; margin-top:6px; }
        .card { background: var(--card-bg); border: 1px solid var(--border); border-radius: 10px; box-shadow: 0 6px 14px rgba(31,41,55,.06); }
        .card-header { padding: 14px 18px; border-bottom: 1px solid var(--border); }
        .card-body { padding: 18px; }
        .providers { display:grid; grid-template-columns: repeat(auto-fill, minmax(260px, 1fr)); gap: 16px; }
        .provider-card { border:1px solid var(--border); border-radius:10px; padding:14px; display:flex; flex-direction:column; gap:12px; transition: box-shadow .2s ease, transform .2s ease; }
        .provider-card:hover { box-shadow: 0 10px 20px rgba(31,41,55,.12); transform: translateY(-1px); }
        .provider-head { display:flex; align-items:center; justify-content:space-between; }
        .provider-meta { display:flex; align-items:center; }
        .provider-meta img { width:28px; height:28px; border-radius:6px; }
        .provider-title { font-weight:600; }
        .badge { padding: 3px 8px; border-radius: 999px; font-size: 12px; }
        .badge.on { background:#ecfdf5; color: var(--success); border:1px solid #a7f3d0; }
        .badge.off { background:#fef2f2; color: var(--danger); border:1px solid #fecaca; }
        .provider-info { color: var(--muted); font-size: 13px; }
        .provider-actions { display:flex; gap:10px; margin-top: 2px; }
        .btn { border-radius:6px; padding:6px 12px; font-size:14px; }
        .btn-primary { background: var(--primary); border-color: var(--primary); }
        .btn-outline-danger { color: var(--danger); border-color: var(--danger); }
        .empty { padding: 24px; text-align:center; color: var(--muted); }
        .footer { margin: 16px 0 28px; color: var(--muted); font-size: 13px; }
        /* 自定义确认弹窗 */
        .modal-mask { position: fixed; inset: 0; background: rgba(0,0,0,.6); display: flex; align-items: center; justify-content: center; z-index: 9999; }
        .modal-mask.hidden { display: none; }
        .tc-modal { width: 380px; max-width: calc(100% - 40px); background: var(--card-bg); border: 1px solid var(--border); border-radius: 10px; box-shadow: 0 8px 24px rgba(31,41,55,.18); }
        .tc-modal-header { padding: 14px 16px; border-bottom: 1px solid var(--border); font-weight: 600; }
        .tc-modal-body { padding: 14px 16px; color: var(--text); }
        .tc-modal-footer { padding: 12px 16px; display: flex; justify-content: flex-end; gap: 10px; }
        .btn-muted { background: transparent; color: var(--muted); border: 1px solid var(--border); }
        /* Toast */
        .toast { position: fixed; right: 16px; bottom: 16px; background: var(--card-bg); color: var(--text); border: 1px solid var(--border); box-shadow: 0 8px 20px rgba(31,41,55,.15); padding: 10px 12px; border-radius: 8px; min-width: 220px; display: none; z-index: 10000; }
        .toast.show { display: block; }
        .toast .toast-title { font-weight: 600; margin-bottom: 4px; }
        .toast .toast-desc { font-size: 13px; color: var(--muted); }
    </style>
</head>
<body>
<div class="container-lg">
    <div class="page-header">
        <div>
            <h1 class="page-title">第三方登录设置</h1>
            <div class="page-subtitle">在此管理你已绑定的第三方登录服务，支持一键开启/关闭。</div>
        </div>
        <a href="<?php echo htmlspecialchars($indexUrl); ?>" class="btn btn-link">返回首页</a>
    </div>

    <div class="card">
        <div class="card-header">绑定概览</div>
        <div class="card-body">
            <?php if (empty($providers)) : ?>
                <div class="empty">尚未配置任何第三方登录。请先在插件配置页填入各平台的 App Key 与 Secret。</div>
            <?php else: ?>
            <div class="providers">
                <?php foreach ($providers as $type => $meta): $isBound = isset($boundMap[$type]); $brand = isset($brandColors[$type]) ? $brandColors[$type] : '#1677ff'; ?>
                    <div class="provider-card" style="border-left: 4px solid <?php echo htmlspecialchars($brand); ?>">
                        <div class="provider-head">
                            <div class="provider-meta">
                                <div class="provider-title"><?php echo htmlspecialchars($meta['title']); ?></div>
                            </div>
                            <?php if ($isBound): ?>
                                <span class="badge on" style="color: <?php echo htmlspecialchars($brand); ?>; border-color: <?php echo htmlspecialchars($brand); ?>; background: transparent;">已开启</span>
                            <?php else: ?>
                                <span class="badge off">未开启</span>
                            <?php endif; ?>
                        </div>
                        <div class="provider-info">
                            <?php if ($isBound): $row = $boundMap[$type]; ?>
                                <div>昵称：<?php echo htmlspecialchars($row['nickname']); ?></div>
                                <div>绑定时间：<?php echo htmlspecialchars($row['datetime']); ?></div>
                            <?php else: ?>
                                <div>未绑定该平台账号</div>
                            <?php endif; ?>
                        </div>
                        <div class="provider-actions">
                            <?php if ($isBound): $offHref = Typecho_Common::url('/connect/toggle?action=off&type='.$type, $indexUrl); $onHref = Typecho_Common::url('/connect/toggle?action=on&type='.$type, $indexUrl); ?>
                                <a href="<?php echo $offHref; ?>" class="btn btn-outline-danger" onclick="return confirmOffCustom('<?php echo htmlspecialchars($offHref); ?>','<?php echo htmlspecialchars($meta['title']); ?>')">关闭绑定</a>
                                <a href="<?php echo $onHref; ?>" class="btn btn-primary">重新绑定</a>
                            <?php else: $onHref = Typecho_Common::url('/connect/toggle?action=on&type='.$type, $indexUrl); ?>
                                <a href="<?php echo $onHref; ?>" class="btn btn-primary" style="background: <?php echo htmlspecialchars($brand); ?>; border-color: <?php echo htmlspecialchars($brand); ?>">开启绑定</a>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="footer">提示：绑定操作会跳转至对应平台授权页面，完成后自动返回本页。</div>
</div>

<!-- Toast -->
<div id="toast" class="toast" role="status" aria-live="polite">
    <div class="toast-title" id="toastTitle">操作成功</div>
    <div class="toast-desc" id="toastDesc"></div>
    </div>

<!-- 自定义确认弹窗 -->
<div id="confirmModal" class="modal-mask hidden" role="dialog" aria-modal="true" aria-labelledby="confirmTitle">
    <div class="tc-modal" onclick="event.stopPropagation()">
        <div class="tc-modal-header" id="confirmTitle">确认操作</div>
        <div class="tc-modal-body" id="confirmMessage">确定要清除该平台的绑定吗？</div>
        <div class="tc-modal-footer">
            <button type="button" class="btn btn-muted" onclick="closeConfirm()">取消</button>
            <button type="button" class="btn btn-outline-danger" onclick="doConfirm()">确认清除</button>
        </div>
    </div>
</div>

<script>
var pendingHref = null;
function confirmOffCustom(href, title){
    pendingHref = href;
    document.getElementById('confirmTitle').innerText = '确认操作';
    document.getElementById('confirmMessage').innerText = '确定要清除 '+ title +' 的绑定吗？';
    document.getElementById('confirmModal').classList.remove('hidden');
    return false; // 阻止默认跳转
}
function closeConfirm(){
    pendingHref = null;
    document.getElementById('confirmModal').classList.add('hidden');
}
function doConfirm(){
    if (pendingHref){
        window.location.href = pendingHref;
    }
}
// 点击遮罩关闭弹窗
document.getElementById('confirmModal').addEventListener('click', function(){ closeConfirm(); });
// 键盘 ESC 关闭
document.addEventListener('keydown', function(e){
    if (e.key === 'Escape') closeConfirm();
});
// Toast
(function(){
    var params = new URLSearchParams(window.location.search);
    var t = params.get('toast');
    var type = params.get('type');
    if (t) {
        var title = t === 'off' ? '解绑成功' : '绑定成功';
        var desc = type ? ('已处理平台：' + String(type).toUpperCase()) : '';
        document.getElementById('toastTitle').innerText = title;
        document.getElementById('toastDesc').innerText = desc;
        var toastEl = document.getElementById('toast');
        toastEl.classList.add('show');
        setTimeout(function(){ toastEl.classList.remove('show'); }, 3000);
        // 移除查询参数，避免刷新重复提示
        var url = window.location.origin + window.location.pathname;
        window.history.replaceState({}, document.title, url);
    }
})();
</script>
</body>
</html>