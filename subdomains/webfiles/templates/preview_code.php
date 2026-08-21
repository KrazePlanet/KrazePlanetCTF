<?php
/**
 * templates/preview_code.php
 */
if (!file_exists($filePath)) { die("File not found."); }

$autoloadPath = __DIR__ . '/../vendor/autoload.php';
if (!file_exists($autoloadPath)) {
    showMessage(
        "Syntax highlighting library not installed.<br>" .
        "Run: <code>composer require scrivo/highlight.php</code>",
        'danger', 'Error'
    );
    return;
}
require_once $autoloadPath;

$maxSize   = 2 * 1024 * 1024;
$fileSize  = filesize($filePath);
$truncated = false;
if ($fileSize > $maxSize) {
    $handle = fopen($filePath, 'r'); $rawCode = fread($handle, $maxSize); fclose($handle);
    $truncated = true;
} else {
    $rawCode = file_get_contents($filePath);
}
$encoding = mb_detect_encoding($rawCode, ['UTF-8', 'Windows-1251', 'KOI8-R'], true);
if ($encoding && $encoding !== 'UTF-8') {
    $rawCode = mb_convert_encoding($rawCode, 'UTF-8', $encoding);
}
$langMap = [
    'php'=>'php','py'=>'python','js'=>'javascript','ts'=>'typescript','go'=>'go',
    'c'=>'c','cpp'=>'cpp','cs'=>'csharp','java'=>'java','kt'=>'kotlin','swift'=>'swift',
    'rb'=>'ruby','rs'=>'rust','sh'=>'bash','bash'=>'bash','ps1'=>'powershell',
    'lua'=>'lua','pl'=>'perl','r'=>'r','html'=>'html','htm'=>'html','css'=>'css',
    'scss'=>'scss','less'=>'less','json'=>'json','xml'=>'xml','yaml'=>'yaml',
    'yml'=>'yaml','toml'=>'ini','ini'=>'ini','conf'=>'nginx','sql'=>'sql',
    'md'=>'markdown','dockerfile'=>'dockerfile','makefile'=>'makefile',
];
$ext          = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
$hlLang       = $langMap[$ext] ?? null;
$detectedLang = $hlLang ?? 'auto';
$lineCount    = substr_count($rawCode, "\n") + 1;

$highlightedCode = ''; $highlightError = false;
try {
    $hl = new \Highlight\Highlighter();
    if ($hlLang !== null) { $result = $hl->highlight($hlLang, $rawCode); }
    else { $result = $hl->highlightAuto($rawCode); $detectedLang = $result->language ?? 'auto'; }
    $highlightedCode = $result->value;
} catch (\Exception $e) {
    $highlightedCode = htmlspecialchars($rawCode); $highlightError = true; $detectedLang = 'plain';
}
if (!$highlightError && function_exists('\HighlightUtilities\splitCodeIntoArray')) {
    $lines = \HighlightUtilities\splitCodeIntoArray($highlightedCode);
} else {
    $lines = explode("\n", $highlightedCode);
}
?>
<style>
    #code-preview-wrapper {
        position: fixed; top: 0; left: 0; width: 100%; height: 100%;
        background: rgba(0,0,0,0.7); display: flex; align-items: center;
        justify-content: center; z-index: 10003;
    }
    .code-card {
        background: #ffffff; width: 92%; max-width: 1200px; height: 90vh;
        border-radius: 8px; display: flex; flex-direction: column;
        box-shadow: 0 10px 30px rgba(0,0,0,0.5); overflow: hidden;
        transition: width 0.2s ease, max-width 0.2s ease, height 0.2s ease, border-radius 0.2s ease;
    }
    .code-header {
        padding: 12px 20px; background: #f1f1f1; border-bottom: 1px solid #ddd;
        display: flex; justify-content: space-between; align-items: center;
        border-radius: 8px 8px 0 0; flex-shrink: 0;
    }
    .code-header .file-title { font-size: 0.9rem; font-weight: 600; color: #212529; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 45%; }
    .code-header .file-title i { color: #0d6efd; margin-right: 8px; }
    .code-meta { display: flex; align-items: center; gap: 10px; flex-shrink: 0; }
    .lang-badge { background: #e9ecef; color: #495057; border: 1px solid #ced4da; padding: 2px 10px; border-radius: 20px; font-size: 0.75rem; font-family: 'Courier New', monospace; letter-spacing: 0.04em; white-space: nowrap; }
    .meta-info { color: #6c757d; font-size: 0.75rem; white-space: nowrap; }
    .btn-copy { background: #fff; border: 1px solid #ced4da; color: #495057; padding: 3px 12px; border-radius: 6px; font-size: 0.8rem; cursor: pointer; transition: background 0.15s, border-color 0.15s; white-space: nowrap; }
    .btn-copy:hover  { background: #e9ecef; border-color: #adb5bd; }
    .btn-copy.copied { background: #d1e7dd; border-color: #a3cfbb; color: #0a3622; }
    .btn-close-code { background: none; border: none; color: #6c757d; font-size: 1.1rem; cursor: pointer; padding: 2px 4px; transition: color 0.15s; line-height: 1; }
    .btn-close-code:hover { color: #212529; }
    .viewer-controls { display: flex; align-items: center; gap: 6px; }
    .btn-viewer { background: #fff; border: 1px solid #ced4da; color: #495057; border-radius: 6px; padding: 3px 9px; font-size: 0.8rem; cursor: pointer; transition: background 0.15s, border-color 0.15s; line-height: 1.4; white-space: nowrap; }
    .btn-viewer:hover    { background: #e9ecef; border-color: #adb5bd; }
    .btn-viewer.active   { background: #0d6efd; border-color: #0d6efd; color: #fff; }
    .btn-viewer:disabled { opacity: 0.4; cursor: default; }
    .font-size-indicator { min-width: 36px; text-align: center; font-size: 0.75rem; color: #6c757d; font-variant-numeric: tabular-nums; }
    .controls-divider { width: 1px; height: 20px; background: #dee2e6; margin: 0 2px; }
    #code-preview-wrapper.is-fullscreen { background: rgba(0,0,0,1); }
    #code-preview-wrapper.is-fullscreen .code-card { width: 100%; max-width: 100%; height: 100vh; border-radius: 0; }
    #code-preview-wrapper.is-fullscreen .code-header { border-radius: 0; }
    .code-warning { padding: 6px 20px; background: #fff3cd; border-bottom: 1px solid #ffc107; font-size: 0.82rem; color: #856404; display: flex; justify-content: space-between; align-items: center; flex-shrink: 0; gap: 10px; }
    .code-warning a { color: #856404; font-weight: 600; }
    .code-body { flex-grow: 1; overflow: auto; background: #ffffff; display: flex; }
    .line-numbers { padding: 16px 0; background: #f8f9fa; border-right: 1px solid #dee2e6; text-align: right; user-select: none; flex-shrink: 0; }
    .line-numbers span { display: block; padding: 0 14px 0 10px; font-family: 'Courier New', Courier, monospace; font-size: 13px; line-height: 1.6; color: #adb5bd; }
    .line-numbers span:hover { color: #6c757d; }
    .code-content { flex-grow: 1; overflow: visible; padding: 16px 20px; }
    .code-content pre { margin: 0; padding: 0; font-family: 'Courier New', Courier, monospace; font-size: 13px; line-height: 1.6; background: transparent !important; white-space: pre; }
    .code-content code { background: transparent !important; font-family: inherit; font-size: inherit; line-height: inherit; }
    .code-content .code-line { display: block; min-height: 1.6em; padding: 0 4px; border-radius: 2px; }
    .code-content .code-line:hover { background: rgba(0,0,0,0.03); }
    .hljs { background: transparent !important; padding: 0 !important; }
    .code-footer { padding: 8px 12px; background: #f8f9fa; border-top: 1px solid #dee2e6; display: flex; justify-content: space-between; align-items: center; flex-shrink: 0; }
    .code-footer .footer-meta { color: #6c757d; font-size: 0.75rem; }
    .btn-dl-code { background: #6c757d; border: none; color: #fff; padding: 4px 14px; border-radius: 6px; font-size: 0.8rem; cursor: pointer; transition: background 0.15s; text-decoration: none; white-space: nowrap; }
    .btn-dl-code:hover { background: #5c636a; color: #fff; }
</style>

<div id="code-preview-wrapper" onclick="FilePreview.close('code-preview-wrapper')">
    <div class="code-card" onclick="event.stopPropagation()">
        <div class="code-header">
            <span class="file-title"><i class="fa-solid fa-code"></i><?= htmlspecialchars($fileName) ?></span>
            <div class="code-meta">
                <span class="lang-badge"><?= htmlspecialchars($detectedLang) ?></span>
                <span class="meta-info"><?= number_format($lineCount, 0, ',', ' ') ?> <?= htmlspecialchars($L['code_viewer']['lines']) ?></span>
                <div class="viewer-controls">
                    <button class="btn-viewer" id="code-font-down" title="<?= htmlspecialchars($L['code_viewer']['font_smaller']) ?>">
                        <i class="fa-solid fa-font" style="font-size:0.65rem;"></i>
                    </button>
                    <span class="font-size-indicator" id="code-font-size">13px</span>
                    <button class="btn-viewer" id="code-font-up" title="<?= htmlspecialchars($L['code_viewer']['font_larger']) ?>">
                        <i class="fa-solid fa-font"></i>
                    </button>
                </div>
                <div class="controls-divider"></div>
                <button class="btn-viewer" id="code-fullscreen" title="<?= htmlspecialchars($L['code_viewer']['fullscreen']) ?>">
                    <i class="fa-solid fa-expand"></i>
                </button>
                <div class="controls-divider"></div>
                <button class="btn-copy" id="btnCopyCode">
                    <i class="fa-regular fa-copy me-1"></i><?= htmlspecialchars($L['code_viewer']['copy']) ?>
                </button>
                <button class="btn-close-code" onclick="FilePreview.close('code-preview-wrapper')"
                        title="<?= htmlspecialchars($L['code_viewer']['close']) ?>">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>
        </div>

        <?php if ($truncated): ?>
        <div class="code-warning">
            <span>
                <i class="fa-solid fa-triangle-exclamation me-1"></i>
                <?= htmlspecialchars(str_replace('{size}', number_format($fileSize/1024/1024, 1, '.', ''), $L['code_viewer']['truncated'])) ?>
            </span>
            <a href="<?= htmlspecialchars("index.php?download=" . rawurlencode($filePath)) ?>&force=1"
               download="<?= htmlspecialchars($fileName) ?>">
                <i class="fa-solid fa-download me-1"></i><?= htmlspecialchars($L['code_viewer']['download_full']) ?>
            </a>
        </div>
        <?php endif; ?>

        <div class="code-body" id="codeBody">
            <div class="line-numbers" id="lineNumbers">
                <?php for ($i = 1; $i <= count($lines); $i++): ?><span><?= $i ?></span><?php endfor; ?>
            </div>
            <div class="code-content">
                <pre><code class="hljs <?= htmlspecialchars($detectedLang) ?>"><?php
                    foreach ($lines as $line):
                ?><span class="code-line"><?= $line ?></span><?php
                    endforeach;
                ?></code></pre>
            </div>
        </div>

        <div class="code-footer">
            <span class="footer-meta">
                <i class="fa-solid fa-circle-info me-1"></i>
                <?= number_format($fileSize / 1024, 0, ',', ' ') ?> KB
                &nbsp;·&nbsp; <?= htmlspecialchars($encoding ?? 'UTF-8') ?>
                <?php if ($highlightError): ?>
                &nbsp;·&nbsp; <span style="color:#dc3545;"><?= htmlspecialchars($L['code_viewer']['highlight_unavailable']) ?></span>
                <?php endif; ?>
            </span>
            <div class="d-flex gap-2 align-items-center">
                <a href="<?= htmlspecialchars("index.php?download=" . rawurlencode($filePath)) ?>&force=1"
                   download="<?= htmlspecialchars($fileName) ?>" class="btn-dl-code">
                    <i class="fa-solid fa-download me-1"></i><?= htmlspecialchars($L['code_viewer']['download']) ?>
                </a>
                <button class="btn-dl-code" onclick="FilePreview.close('code-preview-wrapper')"><?= htmlspecialchars($L['code_viewer']['close']) ?></button>
            </div>
        </div>
    </div>

    <textarea id="code-raw-source" style="display:none;"><?= htmlspecialchars($rawCode) ?></textarea>

    <img src="data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7"
         style="display:none;"
         data-l-collapse="<?= htmlspecialchars($L['code_viewer']['collapse'], ENT_QUOTES) ?>"
         data-l-fullscreen="<?= htmlspecialchars($L['code_viewer']['fullscreen'], ENT_QUOTES) ?>"
         data-l-copy="<?= htmlspecialchars($L['code_viewer']['copy'], ENT_QUOTES) ?>"
         data-l-copied="<?= htmlspecialchars($L['code_viewer']['copied'], ENT_QUOTES) ?>"
         onload="(function(el){
    var styleId = 'hljs-theme';
    var isDark  = document.documentElement.getAttribute('data-theme') === 'dark';
    var lightHref = 'vendor/scrivo/highlight.php/styles/atom-one-light.css';
    var darkHref  = 'vendor/scrivo/highlight.php/styles/atom-one-dark.css';
    function applyHljsTheme(dark) {
        var lnk = document.getElementById(styleId);
        if (!lnk) { lnk = document.createElement('link'); lnk.id = styleId; lnk.rel = 'stylesheet'; document.head.appendChild(lnk); }
        lnk.href = dark ? darkHref : lightHref;
    }
    applyHljsTheme(isDark);
    var obs = new MutationObserver(function(ms) { ms.forEach(function(m) { if (m.attributeName === 'data-theme') applyHljsTheme(document.documentElement.getAttribute('data-theme') === 'dark'); }); });
    obs.observe(document.documentElement, { attributes: true });
    document.getElementById('code-preview-wrapper').addEventListener('preview:close', function() { obs.disconnect(); });

    const codeBody = document.getElementById('codeBody');
    const lineNums = document.getElementById('lineNumbers');
    if (codeBody && lineNums) codeBody.addEventListener('scroll', function() { lineNums.scrollTop = codeBody.scrollTop; });

    const STORAGE_KEY = 'viewer_font_size', DEFAULT_SIZE = 13, MIN_SIZE = 10, MAX_SIZE = 24;
    const codeContent  = document.querySelector('#codeBody .code-content pre');
    const btnDown      = document.getElementById('code-font-down');
    const btnUp        = document.getElementById('code-font-up');
    const sizeEl       = document.getElementById('code-font-size');
    const lineNumSpans = document.querySelectorAll('#lineNumbers span');
    let fontSize = parseInt(localStorage.getItem(STORAGE_KEY)) || DEFAULT_SIZE;
    function applyFont() {
        if (codeContent) codeContent.style.fontSize = fontSize + 'px';
        lineNumSpans.forEach(s => s.style.fontSize = fontSize + 'px');
        sizeEl.textContent = fontSize + 'px';
        btnDown.disabled = (fontSize <= MIN_SIZE); btnUp.disabled = (fontSize >= MAX_SIZE);
        localStorage.setItem(STORAGE_KEY, fontSize);
    }
    btnDown.addEventListener('click', function() { if (fontSize > MIN_SIZE) { fontSize--; applyFont(); } });
    btnUp.addEventListener('click',   function() { if (fontSize < MAX_SIZE) { fontSize++; applyFont(); } });
    applyFont();

    const wrapper = document.getElementById('code-preview-wrapper');
    const btnFs   = document.getElementById('code-fullscreen');
    const lFs     = el.dataset.lFullscreen;
    const lCol    = el.dataset.lCollapse;
    let isFullscreen = false;
    function applyFullscreen() {
        if (isFullscreen) {
            wrapper.classList.add('is-fullscreen');
            btnFs.innerHTML = '<i class=&quot;fa-solid fa-compress&quot;></i>';
            btnFs.title = lCol; btnFs.classList.add('active');
        } else {
            wrapper.classList.remove('is-fullscreen');
            btnFs.innerHTML = '<i class=&quot;fa-solid fa-expand&quot;></i>';
            btnFs.title = lFs; btnFs.classList.remove('active');
        }
    }
    btnFs.addEventListener('click', function() { isFullscreen = !isFullscreen; applyFullscreen(); });
    document.addEventListener('keydown', function onEscCode(e) {
        if (!document.getElementById('code-preview-wrapper')) { document.removeEventListener('keydown', onEscCode); return; }
        if (e.key === 'Escape' && isFullscreen) { e.stopImmediatePropagation(); isFullscreen = false; applyFullscreen(); }
    });

    const btnCopy  = document.getElementById('btnCopyCode');
    const rawStore = document.getElementById('code-raw-source');
    const lCopy    = el.dataset.lCopy;
    const lCopied  = el.dataset.lCopied;
    if (btnCopy && rawStore) {
        btnCopy.addEventListener('click', function() {
            const rawText = rawStore.value;
            const doSuccess = () => {
                btnCopy.classList.add('copied');
                btnCopy.innerHTML = '<i class=&quot;fa-solid fa-check me-1&quot;></i>' + lCopied;
                setTimeout(() => { btnCopy.classList.remove('copied'); btnCopy.innerHTML = '<i class=&quot;fa-regular fa-copy me-1&quot;></i>' + lCopy; }, 2000);
            };
            if (navigator.clipboard) {
                navigator.clipboard.writeText(rawText).then(doSuccess).catch(() => { const ta = document.createElement('textarea'); ta.value = rawText; ta.style.cssText = 'position:fixed;opacity:0'; document.body.appendChild(ta); ta.select(); document.execCommand('copy'); document.body.removeChild(ta); doSuccess(); });
            } else {
                const ta = document.createElement('textarea'); ta.value = rawText; ta.style.cssText = 'position:fixed;opacity:0'; document.body.appendChild(ta); ta.select(); document.execCommand('copy'); document.body.removeChild(ta); doSuccess();
            }
        });
    }
})(this)">
</div>