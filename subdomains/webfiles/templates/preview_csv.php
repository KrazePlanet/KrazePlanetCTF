<?php
// templates/preview_csv.php
if (!isset($filePath) || !file_exists($filePath)) {
    die("<div class='alert alert-danger'>File not found.</div>");
}

$maxBytes  = 2 * 1024 * 1024;
$fileSize  = filesize($filePath);
$truncated = $fileSize > $maxBytes;

$handle     = fopen($filePath, "r");
$firstChunk = fread($handle, 5000);
fclose($handle);

$encoding = mb_detect_encoding($firstChunk, [
    'UTF-8', 'Windows-1251', 'KOI8-R', 'KOI8-U',
    'CP866', 'ISO-8859-5', 'ISO-8859-1', 'ISO-8859-2',
], true) ?: 'Windows-1251';

if (!function_exists('detectDelimiter')) {
    function detectDelimiter($text) {
        $delims = [";" => 0, "," => 0, "\t" => 0];
        $lines = explode("\n", $text); $line = $lines[0] ?? '';
        foreach ($delims as $d => &$count) { $count = count(str_getcsv($line, $d, '"', "")); }
        return array_search(max($delims), $delims) ?: ";";
    }
}
$analysisText = ($encoding !== 'UTF-8') ? mb_convert_encoding($firstChunk, 'UTF-8', $encoding) : $firstChunk;
$delimiter    = detectDelimiter($analysisText);

$rows = []; $rowLimit = 500;
$handle = fopen($filePath, "r");
if ($handle) {
    if ($encoding !== 'UTF-8') stream_filter_append($handle, 'convert.iconv.' . $encoding . '/utf-8');
    $bytesRead = 0;
    while (($data = fgetcsv($handle, 0, $delimiter, '"', "")) !== false) {
        $bytesRead += strlen(implode($delimiter, $data));
        if ($data && array_filter($data, 'strlen')) $rows[] = array_map('trim', $data);
        if ($truncated && $bytesRead >= $maxBytes) break;
    }
    fclose($handle);
}
$header     = array_shift($rows) ?: [];
$fileSizeKb = round($fileSize / 1024);
?>
<style>
 #csv-preview-wrapper {
    position: fixed; top: 0; left: 0; width: 100%; height: 100%;
    background: rgba(0,0,0,0.85); display: flex; align-items: center;
    justify-content: center; z-index: 10006;
 }
 .csv-card {
    background: #fff; width: 98%; height: 95vh; border-radius: 8px;
    display: flex; flex-direction: column; overflow: hidden;
    transition: width 0.2s, height 0.2s, border-radius 0.2s;
 }
 #csv-preview-wrapper.is-fullscreen { background: rgba(0,0,0,1); }
 #csv-preview-wrapper.is-fullscreen .csv-card { width: 100%; height: 100vh; border-radius: 0; }
 .csv-header {
    padding: 10px 20px; background: #f1f1f1; border-bottom: 2px solid #dee2e6;
    display: flex; gap: 10px; align-items: center; flex-shrink: 0;
 }
 .csv-warning {
    padding: 6px 20px; background: #fff3cd; border-bottom: 1px solid #ffc107;
    font-size: 0.82rem; color: #856404; flex-shrink: 0;
    display: flex; align-items: center; justify-content: space-between; gap: 10px;
 }
 .table-container { flex-grow: 1; overflow: auto; background: #fff; cursor: default !important; }
 #csv-table { width: 100%; cursor: default !important; border-collapse: collapse; }
 .csv-row { cursor: text !important; }
 .csv-row td { border: 1px solid #dee2e6; padding: 4px 8px; font-size: 14px; transition: font-size 0.15s ease; }
 thead th { position: sticky; top: 0; background: #f1f1f1 !important; z-index: 5; border: 1px solid #dee2e6; padding: 8px; }
 .csv-footer {
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
</style>

<div id="csv-preview-wrapper">
    <div class="csv-card">
        <div class="csv-header">
            <span class="fw-bold text-truncate flex-grow-1">
                <i class="fa-solid fa-table text-success me-2"></i><?= htmlspecialchars($fileName) ?>
            </span>
            <span class="text-muted small text-nowrap">
                <?= htmlspecialchars($encoding) ?> &nbsp;·&nbsp;
                <?= number_format($fileSizeKb, 0, '.', ' ') ?> KB &nbsp;·&nbsp;
                <?= count($rows) ?> <?= htmlspecialchars($L['csv_viewer']['lines']) ?>
            </span>
            <div class="viewer-controls">
                <button class="btn-viewer" id="csv-font-down" title="<?= htmlspecialchars($L['csv_viewer']['font_smaller']) ?>">
                    <i class="fa-solid fa-font" style="font-size:0.65rem;"></i>
                </button>
                <span class="font-size-indicator" id="csv-font-size">14px</span>
                <button class="btn-viewer" id="csv-font-up" title="<?= htmlspecialchars($L['csv_viewer']['font_larger']) ?>">
                    <i class="fa-solid fa-font"></i>
                </button>
            </div>
            <div class="controls-divider"></div>
            <button class="btn-viewer" id="btn-csv-fs" title="<?= htmlspecialchars($L['csv_viewer']['fullscreen']) ?>">
                <i class="fa-solid fa-expand" id="csv-fs-icon"></i>
            </button>
            <div class="controls-divider"></div>
            <button type="button" class="btn-close" onclick="FilePreview.close('csv-preview-wrapper')"></button>
        </div>

        <?php if ($truncated): ?>
        <div class="csv-warning">
            <span>
                <i class="fa-solid fa-triangle-exclamation me-1"></i>
                <?= htmlspecialchars(str_replace('{size}', number_format($fileSize / 1024 / 1024, 1, '.', ''), $L['csv_viewer']['truncated'])) ?>
            </span>
            <a href="<?= htmlspecialchars("index.php?download=" . rawurlencode($fileRel ?? $filePath)) ?>"
               class="btn btn-sm btn-warning py-0">
                <i class="fa-solid fa-download me-1"></i> <?= htmlspecialchars($L['csv_viewer']['download_full']) ?>
            </a>
        </div>
        <?php endif; ?>

        <div class="table-container">
            <table class="table table-sm table-bordered m-0" id="csv-table">
                <thead>
                    <tr>
                        <th class="text-center">#</th>
                        <?php foreach ($header as $col): ?><th><?= htmlspecialchars($col) ?></th><?php endforeach; ?>
                    </tr>
                </thead>
                <tbody id="csv-tbody">
                    <?php foreach ($rows as $idx => $row): ?>
                    <tr class="csv-row">
                        <td class="text-center text-muted small"><?= $idx + 1 ?></td>
                        <?php foreach ($row as $cell): ?><td><?= htmlspecialchars($cell) ?></td><?php endforeach; ?>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <div class="csv-footer">
            <span class="small text-muted">
                <i class="fa-solid fa-circle-info me-1"></i>
                <?= number_format($fileSizeKb, 0, '.', ' ') ?> KB
                &nbsp;·&nbsp;
                <?= count($rows) ?> <?= htmlspecialchars($L['csv_viewer']['lines']) ?>
            </span>
            <button class="btn btn-secondary btn-sm px-4"
                    onclick="FilePreview.close('csv-preview-wrapper')"><?= htmlspecialchars($L['csv_viewer']['close_viewer']) ?></button>
        </div>
    </div>
</div>

<?php
$_l_collapse   = addslashes($L['csv_viewer']['collapse']);
$_l_fullscreen = addslashes($L['csv_viewer']['fullscreen']);
?>
<img src="data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7"
     style="display:none;"
     onload="(function(){
    const STORAGE_KEY = 'viewer_font_size', DEFAULT_SIZE = 14, MIN_SIZE = 10, MAX_SIZE = 24;
    const wrapper = document.getElementById('csv-preview-wrapper');
    const table   = document.getElementById('csv-table');
    const btnDown = document.getElementById('csv-font-down');
    const btnUp   = document.getElementById('csv-font-up');
    const sizeEl  = document.getElementById('csv-font-size');
    const btnFs   = document.getElementById('btn-csv-fs');
    const fsIcon  = document.getElementById('csv-fs-icon');

    let fontSize = parseInt(localStorage.getItem(STORAGE_KEY)) || DEFAULT_SIZE;
    function applyFont() {
        table.querySelectorAll('td').forEach(function(td) { td.style.fontSize = fontSize + 'px'; });
        sizeEl.textContent = fontSize + 'px';
        btnDown.disabled   = (fontSize <= MIN_SIZE);
        btnUp.disabled     = (fontSize >= MAX_SIZE);
        localStorage.setItem(STORAGE_KEY, fontSize);
    }
    btnDown.addEventListener('click', function() { if (fontSize > MIN_SIZE) { fontSize--; applyFont(); } });
    btnUp.addEventListener('click',   function() { if (fontSize < MAX_SIZE) { fontSize++; applyFont(); } });
    applyFont();

    let isFullscreen = false;
    function applyFullscreen() {
        if (isFullscreen) {
            wrapper.classList.add('is-fullscreen');
            fsIcon.className = 'fa-solid fa-compress';
            btnFs.title      = '<?= $_l_collapse ?>';
            btnFs.classList.add('active');
        } else {
            wrapper.classList.remove('is-fullscreen');
            fsIcon.className = 'fa-solid fa-expand';
            btnFs.title      = '<?= $_l_fullscreen ?>';
            btnFs.classList.remove('active');
        }
    }
    btnFs.addEventListener('click', function() { isFullscreen = !isFullscreen; applyFullscreen(); });
    document.addEventListener('keydown', function onEscCsv(e) {
        if (!document.getElementById('csv-preview-wrapper')) { document.removeEventListener('keydown', onEscCsv); return; }
        if (e.key === 'Escape' && isFullscreen) { e.stopImmediatePropagation(); isFullscreen = false; applyFullscreen(); }
    });
    if (window.FilePreview) FilePreview.state.isDragging = false;
})()">