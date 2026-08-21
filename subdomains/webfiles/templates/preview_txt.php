<?php
/**
 * templates/preview_txt.php
 */
if (!file_exists($filePath)) { die("File not found."); }

$maxSize  = 2 * 1024 * 1024;
$fileSize = filesize($filePath);
if ($fileSize > $maxSize) {
    $handle = fopen($filePath, 'r'); $textContent = fread($handle, $maxSize); fclose($handle);
    $truncated = true;
} else {
    $textContent = file_get_contents($filePath); $truncated = false;
}
$encoding = mb_detect_encoding($textContent, [
    'UTF-8', 'Windows-1251', 'KOI8-R', 'KOI8-U',
    'CP866', 'ISO-8859-5', 'ISO-8859-1', 'ISO-8859-2',
], true);
if ($encoding && $encoding !== 'UTF-8') {
    $textContent = mb_convert_encoding($textContent, 'UTF-8', $encoding);
}
?>
<style>
    #txt-preview-wrapper {
        position: fixed; top: 0; left: 0; width: 100%; height: 100%;
        background: rgba(0, 0, 0, 0.7); display: flex; align-items: center;
        justify-content: center; z-index: 10003;
    }
    .txt-card {
        background: #ffffff; width: 85%; max-width: 900px; height: 80vh;
        border-radius: 8px; display: flex; flex-direction: column;
        box-shadow: 0 10px 30px rgba(0,0,0,0.5);
        transition: width 0.2s ease, max-width 0.2s ease, height 0.2s ease, border-radius 0.2s ease;
    }
    #txt-preview-wrapper.is-fullscreen { background: rgba(0,0,0,1); }
    #txt-preview-wrapper.is-fullscreen .txt-card { width: 100%; max-width: 100%; height: 100vh; border-radius: 0; }
    .txt-header {
        padding: 12px 20px; background: #f1f1f1; border-bottom: 1px solid #ddd;
        display: flex; justify-content: space-between; align-items: center;
        border-radius: 8px 8px 0 0; flex-shrink: 0;
    }
    #txt-preview-wrapper.is-fullscreen .txt-header { border-radius: 0; }
    .txt-warning {
        padding: 6px 20px; background: #fff3cd; border-bottom: 1px solid #ffc107;
        font-size: 0.82rem; color: #856404; flex-shrink: 0;
        display: flex; align-items: center; justify-content: space-between; gap: 10px;
    }
    .txt-body {
        flex-grow: 1; padding: 20px; overflow: auto; background: #fafafa;
        font-family: 'Courier New', Courier, monospace; font-size: 14px;
        line-height: 1.5; white-space: pre-wrap; word-wrap: break-word;
        transition: font-size 0.15s ease;
    }
    .txt-footer {
        padding: 8px 12px; background: #f8f9fa; border-top: 1px solid #dee2e6;
        display: flex; justify-content: space-between; align-items: center; flex-shrink: 0;
    }
    .viewer-controls { display: flex; align-items: center; gap: 6px; }
    .btn-viewer {
        background: #fff; border: 1px solid #ced4da; color: #495057;
        border-radius: 6px; padding: 3px 9px; font-size: 0.8rem;
        cursor: pointer; transition: background 0.15s, border-color 0.15s; line-height: 1.4; white-space: nowrap;
    }
    .btn-viewer:hover    { background: #e9ecef; border-color: #adb5bd; }
    .btn-viewer.active   { background: #0d6efd; border-color: #0d6efd; color: #fff; }
    .btn-viewer:disabled { opacity: 0.4; cursor: default; }
    .font-size-indicator { min-width: 36px; text-align: center; font-size: 0.75rem; color: #6c757d; font-variant-numeric: tabular-nums; }
    .controls-divider { width: 1px; height: 20px; background: #dee2e6; margin: 0 2px; }
    @media print {
        body * { visibility: hidden; }
        #print-area, #print-area * { visibility: visible; }
        #print-area { position: absolute; left: 0; top: 0; width: 100%; }
    }
</style>

<div id="txt-preview-wrapper">
    <div class="txt-card">
        <div class="txt-header">
            <span class="fw-bold text-dark text-truncate me-3" style="max-width:35%;">
                <i class="fa-solid fa-file-lines text-secondary me-2"></i><?= htmlspecialchars($fileName) ?>
            </span>
            <div class="d-flex align-items-center gap-2 flex-wrap">
                <span class="text-muted small"><?= $encoding ?? 'UTF-8' ?></span>
                <div class="viewer-controls">
                    <button class="btn-viewer" id="txt-font-down" title="<?= htmlspecialchars($L['txt_viewer']['font_smaller']) ?>">
                        <i class="fa-solid fa-font" style="font-size:0.65rem;"></i>
                    </button>
                    <span class="font-size-indicator" id="txt-font-size">14px</span>
                    <button class="btn-viewer" id="txt-font-up" title="<?= htmlspecialchars($L['txt_viewer']['font_larger']) ?>">
                        <i class="fa-solid fa-font"></i>
                    </button>
                </div>
                <div class="controls-divider"></div>
                <button class="btn-viewer" id="txt-fullscreen" title="<?= htmlspecialchars($L['txt_viewer']['fullscreen']) ?>">
                    <i class="fa-solid fa-expand"></i>
                </button>
                <div class="controls-divider"></div>
                <button class="btn btn-sm btn-outline-primary" onclick="window.print()">
                    <i class="fa-solid fa-print"></i> <?= htmlspecialchars($L['txt_viewer']['print']) ?>
                </button>
                <button type="button" class="btn-close"
                        onclick="FilePreview.close('txt-preview-wrapper')"></button>
            </div>
        </div>

        <?php if ($truncated): ?>
        <div class="txt-warning">
            <span>
                <i class="fa-solid fa-triangle-exclamation me-1"></i>
                <?= htmlspecialchars(str_replace('{size}', number_format($fileSize / 1024 / 1024, 1, '.', ''), $L['txt_viewer']['truncated'])) ?>
            </span>
            <a href="<?= htmlspecialchars("index.php?download=" . rawurlencode($filePath)) ?>&force=1"
               download="<?= htmlspecialchars($fileName) ?>"
               class="btn btn-sm btn-warning py-0">
                <i class="fa-solid fa-download me-1"></i> <?= htmlspecialchars($L['txt_viewer']['download_full']) ?>
            </a>
        </div>
        <?php endif; ?>

        <div class="txt-body" id="print-area"><?= htmlspecialchars($textContent) ?></div>

        <div class="txt-footer">
            <span class="small text-muted">
                <i class="fa-solid fa-circle-info me-1"></i>
                <?= number_format($fileSize / 1024, 0, ',', ' ') ?> KB
                &nbsp;·&nbsp;
                <?= substr_count($textContent, "\n") + 1 ?> <?= htmlspecialchars($L['txt_viewer']['lines']) ?>
            </span>
            <button class="btn btn-secondary btn-sm px-4"
                    onclick="FilePreview.close('txt-preview-wrapper')"><?= htmlspecialchars($L['txt_viewer']['close']) ?></button>
        </div>
    </div>
</div>

<?php
$_l_collapse   = addslashes($L['txt_viewer']['collapse']);
$_l_fullscreen = addslashes($L['txt_viewer']['fullscreen']);
?>
<img src="data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7"
     style="display:none;"
     onload="(function(){
    const STORAGE_KEY = 'viewer_font_size', DEFAULT_SIZE = 14, MIN_SIZE = 10, MAX_SIZE = 24;
    const wrapper = document.getElementById('txt-preview-wrapper');
    const body    = document.getElementById('print-area');
    const btnDown = document.getElementById('txt-font-down');
    const btnUp   = document.getElementById('txt-font-up');
    const sizeEl  = document.getElementById('txt-font-size');
    const btnFs   = document.getElementById('txt-fullscreen');

    let fontSize = parseInt(localStorage.getItem(STORAGE_KEY)) || DEFAULT_SIZE;
    function applyFont() {
        body.style.fontSize = fontSize + 'px';
        sizeEl.textContent  = fontSize + 'px';
        btnDown.disabled    = (fontSize <= MIN_SIZE);
        btnUp.disabled      = (fontSize >= MAX_SIZE);
        localStorage.setItem(STORAGE_KEY, fontSize);
    }
    btnDown.addEventListener('click', function() { if (fontSize > MIN_SIZE) { fontSize--; applyFont(); } });
    btnUp.addEventListener('click',   function() { if (fontSize < MAX_SIZE) { fontSize++; applyFont(); } });
    applyFont();

    let isFullscreen = false;
    function applyFullscreen() {
        if (isFullscreen) {
            wrapper.classList.add('is-fullscreen');
            btnFs.innerHTML = '<i class=&quot;fa-solid fa-compress&quot;></i>';
            btnFs.title     = '<?= $_l_collapse ?>';
            btnFs.classList.add('active');
        } else {
            wrapper.classList.remove('is-fullscreen');
            btnFs.innerHTML = '<i class=&quot;fa-solid fa-expand&quot;></i>';
            btnFs.title     = '<?= $_l_fullscreen ?>';
            btnFs.classList.remove('active');
        }
    }
    btnFs.addEventListener('click', function() { isFullscreen = !isFullscreen; applyFullscreen(); });
    document.addEventListener('keydown', function onEscTxt(e) {
        if (!document.getElementById('txt-preview-wrapper')) { document.removeEventListener('keydown', onEscTxt); return; }
        if (e.key === 'Escape' && isFullscreen) { e.stopImmediatePropagation(); isFullscreen = false; applyFullscreen(); }
    });
})()">