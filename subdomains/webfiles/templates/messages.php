<?php
/**
 * templates/messages.php
 */
$message  = $message  ?? $msgText ?? '';
$msgType  = $msgType  ?? 'warning';
$_L_ok        = (isset($L) && is_array($L)) ? ($L['messages']['ok']        ?? 'OK')        : 'OK';
$_L_attention = (isset($L) && is_array($L)) ? ($L['messages']['attention'] ?? 'Attention') : 'Attention';
$msgTitle = $msgTitle ?? $_L_attention;

$icon = match($msgType) {
    'danger'  => 'fa-circle-xmark',
    'success' => 'fa-circle-check',
    default   => 'fa-triangle-exclamation',
};
$uniqueId = 'msg_' . time() . rand(100, 999);
$duration = 10000;
?>
<style>
    #<?= $uniqueId ?> {
        position: fixed; top: 0; left: 0; width: 100%; height: 100%;
        background: rgba(0,0,0,0.5); z-index: 10010;
        display: flex; align-items: center; justify-content: center;
        backdrop-filter: blur(2px); transition: opacity 0.2s;
    }
    .msg-card {
        background: #fff; width: 90%; max-width: 500px; border-radius: 12px;
        box-shadow: 0 10px 30px rgba(0,0,0,0.3); overflow: hidden; position: relative;
        animation: msgBounce 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
    }
    @keyframes msgBounce { from { opacity: 0; transform: scale(0.8); } to { opacity: 1; transform: scale(1); } }
    .msg-header { padding: 15px 20px; display: flex; align-items: center; justify-content: space-between; border-bottom: 1px solid #eee; }
    .msg-body { padding: 30px 20px; text-align: center; }
    .msg-footer { padding: 15px; text-align: center; background: #f9f9f9; }
    .close-msg { cursor: pointer; font-size: 1.5rem; border: none; background: none; color: #aaa; transition: color 0.2s; }
    .close-msg:hover { color: #333; }
    .msg-timer-container { width: 100%; height: 4px; background: #eee; position: absolute; bottom: 0; left: 0; }
    .msg-timer-bar { height: 100%; width: 100%; background: var(--bs-<?= $msgType ?>); transform-origin: left; }
</style>

<div id="<?= $uniqueId ?>" onclick="if(window.destroyMsg) window.destroyMsg('<?= $uniqueId ?>', event)">
    <div class="msg-card" onclick="event.stopPropagation()">
        <div class="msg-header">
            <span class="fw-bold text-<?= $msgType ?>"><i class="fa-solid <?= $icon ?> me-2"></i><?= htmlspecialchars($msgTitle) ?></span>
            <button class="close-msg" onclick="window.destroyMsg('<?= $uniqueId ?>')">&times;</button>
        </div>
        <div class="msg-body">
            <div class="mb-3"><i class="fa-solid <?= $icon ?> text-<?= $msgType ?> fa-3x"></i></div>
            <div class="fs-5"><?= $message ?></div>
        </div>
        <div class="msg-footer">
            <button class="btn btn-<?= $msgType ?> px-5 fw-bold"
                    onclick="window.destroyMsg('<?= $uniqueId ?>')"><?= htmlspecialchars($_L_ok) ?></button>
        </div>
        <div class="msg-timer-container">
            <div id="bar-<?= $uniqueId ?>" class="msg-timer-bar"></div>
        </div>
    </div>
    <img src="data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7"
         style="display:none;"
         onload="(function(){
            const msgId = '<?= $uniqueId ?>';
            const barId = 'bar-<?= $uniqueId ?>';
            const duration = <?= $duration ?>;
            window.destroyMsg = function(id, event = null) {
                if (event && event.target.id !== id) return;
                const el = document.getElementById(id);
                if (el) { el.style.opacity = '0'; setTimeout(() => el.remove(), 200); }
            };
            const bar = document.getElementById(barId);
            if (bar) { bar.animate([{ transform: 'scaleX(1)' }, { transform: 'scaleX(0)' }], { duration: duration, easing: 'linear', fill: 'forwards' }); }
            const autoTimer = setTimeout(() => window.destroyMsg(msgId), duration);
            const escHandler = (e) => { if (e.key === 'Escape') { window.destroyMsg(msgId); document.removeEventListener('keydown', escHandler); clearTimeout(autoTimer); } };
            document.addEventListener('keydown', escHandler);
         })()">
</div>