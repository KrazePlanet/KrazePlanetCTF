<?php
/**
 * templates/preview_pdf.php
 */
$pdfUrl = "index.php?download=" . rawurlencode($fileRel);
?>
<style>
 #pdf-preview-wrapper {
    position: fixed; top: 0; left: 0; width: 100%; height: 100%;
    background: rgba(0, 0, 0, 0.75); display: flex; align-items: center;
    justify-content: center; z-index: 10002; opacity: 1; transition: opacity 0.3s ease;
 }
 .pdf-card {
    background: #f8f9fa; width: 95%; max-width: 1200px; height: 90vh;
    border-radius: 8px; display: flex; flex-direction: column; overflow: hidden;
    box-shadow: 0 15px 40px rgba(0,0,0,0.6);
    transition: width 0.2s, max-width 0.2s, height 0.2s, border-radius 0.2s;
 }
 .pdf-card.is-fullscreen { width: 100%; max-width: 100%; height: 100vh; border-radius: 0; }
 .pdf-header {
    padding: 12px 20px; background: #343a40; color: #fff;
    display: flex; justify-content: space-between; align-items: center;
 }
 .pdf-body { flex-grow: 1; background: #525659; position: relative; }
 .pdf-iframe { width: 100%; height: 100%; border: none; }
 .btn-close-white { filter: invert(1) grayscale(100%) brightness(200%); }
 .btn-viewer-pdf {
    background: none; border: none; color: #adb5bd; font-size: 1rem;
    padding: 2px 6px; line-height: 1; cursor: pointer; border-radius: 4px;
 }
 .btn-viewer-pdf:hover { color: #fff; background: rgba(255,255,255,0.1); }
 #pdf-rename-row { display: none; align-items: center; gap: 6px; flex: 1; min-width: 0; }
 #pdf-rename-row.active { display: flex; }
 #pdf-rename-input {
    flex: 1; min-width: 0; border: 1px solid #ced4da; border-radius: 5px;
    padding: 3px 8px; font-size: 0.85rem; outline: none;
 }
 #pdf-rename-input:focus { border-color: #86b7fe; box-shadow: 0 0 0 2px rgba(13,110,253,0.25); }
 #pdf-rename-input.is-invalid { border-color: #dc3545; }
 #pdf-rename-status { font-size: 0.78rem; white-space: nowrap; color: #dc3545; }
</style>
<div id="pdf-preview-wrapper">
 <div class="pdf-card" id="pdf-card">
    <div class="pdf-header">
        <span class="fw-bold">
            <i class="fa-solid fa-file-pdf text-danger me-2"></i>
            <?= htmlspecialchars($fileName) ?>
        </span>
        <div class="d-flex align-items-center gap-2">
            <button type="button" class="btn-viewer-pdf" id="btn-pdf-fs"
                    title="<?= htmlspecialchars($L['pdf_viewer']['fullscreen']) ?>">
                <i class="fa-solid fa-expand" id="pdf-fs-icon"></i>
            </button>
            <button type="button" class="btn-close btn-close-white"
                    onclick="FilePreview.close('pdf-preview-wrapper')"></button>
        </div>
    </div>

    <div class="pdf-body">
        <iframe class="pdf-iframe" src="<?= htmlspecialchars($pdfUrl) ?>#view=FitH"
                type="application/pdf">
            <div class="p-4 text-center text-white">
                <p><?= htmlspecialchars($L['pdf_viewer']['no_support']) ?></p>
                <a href="<?= htmlspecialchars($pdfUrl) ?>" class="btn btn-primary">
                    <i class="fa-solid fa-download me-1"></i> <?= htmlspecialchars($L['pdf_viewer']['download_instead']) ?>
                </a>
            </div>
        </iframe>
    </div>

    <div class="pdf-footer p-2 bg-light border-top d-flex align-items-center gap-2">
        <div id="pdf-rename-row">
            <input type="text" id="pdf-rename-input" autocomplete="off" spellcheck="false">
            <button type="button" class="btn btn-success btn-sm" id="btn-pdf-rename-ok">
                <i class="fa-solid fa-check"></i>
            </button>
            <button type="button" class="btn btn-outline-secondary btn-sm" id="btn-pdf-rename-cancel">
                <i class="fa-solid fa-xmark"></i>
            </button>
            <span id="pdf-rename-status"></span>
        </div>
        <div class="ms-auto d-flex gap-2">
            <?php if ($GLOBALS['isUploadEnabled'] ?? false): ?>
            <button type="button" class="btn btn-outline-secondary btn-sm" id="btn-pdf-rename">
                <i class="fa-solid fa-pencil me-1"></i><?= htmlspecialchars($L['pdf_viewer']['rename']) ?>
            </button>
            <?php endif; ?>
            <a href="<?= htmlspecialchars($pdfUrl) ?>" download class="btn btn-primary btn-sm px-3">
                <i class="fa-solid fa-download"></i> <?= htmlspecialchars($L['pdf_viewer']['download']) ?>
            </a>
            <button class="btn btn-secondary btn-sm px-4"
                    onclick="FilePreview.close('pdf-preview-wrapper')"><?= htmlspecialchars($L['pdf_viewer']['close']) ?></button>
        </div>
    </div>
 </div>
</div>
<img src="data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7"
     data-name="<?= htmlspecialchars($fileName, ENT_QUOTES) ?>"
     data-rel="<?= htmlspecialchars($fileRel, ENT_QUOTES) ?>"
     data-l-fullscreen="<?= htmlspecialchars($L['pdf_viewer']['fullscreen'], ENT_QUOTES) ?>"
     data-l-collapse="<?= htmlspecialchars($L['pdf_viewer']['collapse'], ENT_QUOTES) ?>"
     data-l-neterror="<?= htmlspecialchars($L['pdf_viewer']['network_error'], ENT_QUOTES) ?>"
     onload="(function(el){
         var card = document.getElementById('pdf-card');
         var btn  = document.getElementById('btn-pdf-fs');
         var icon = document.getElementById('pdf-fs-icon');
         var isFs = false;
         var lFs  = el.dataset.lFullscreen;
         var lCol = el.dataset.lCollapse;
         function applyFs() {
             if (isFs) {
                 card.classList.add('is-fullscreen');
                 icon.className = 'fa-solid fa-compress';
                 btn.title = lCol;
             } else {
                 card.classList.remove('is-fullscreen');
                 icon.className = 'fa-solid fa-expand';
                 btn.title = lFs;
             }
         }
         btn.addEventListener('click', function(e) { e.stopPropagation(); isFs = !isFs; applyFs(); });
         var wrapper = document.getElementById('pdf-preview-wrapper');
         function onEsc(e) {
             if (e.key !== 'Escape') return;
             var rr = document.getElementById('pdf-rename-row');
             if (rr && rr.classList.contains('active')) return;
             if (isFs) { e.stopImmediatePropagation(); isFs = false; applyFs(); }
         }
         document.addEventListener('keydown', onEsc, true);
         wrapper.addEventListener('preview:close', function() {
             document.removeEventListener('keydown', onEsc, true);
         });

         var btnRename = document.getElementById('btn-pdf-rename');
         if (!btnRename) return;

         var renameRow = document.getElementById('pdf-rename-row');
         var input     = document.getElementById('pdf-rename-input');
         var btnOk     = document.getElementById('btn-pdf-rename-ok');
         var btnCancel = document.getElementById('btn-pdf-rename-cancel');
         var statusEl  = document.getElementById('pdf-rename-status');
         var origName  = el.dataset.name;
         var fileRel   = el.dataset.rel;
         var lNetErr   = el.dataset.lNeterror;
         var badChars  = ['/', '\\', ':', '*', '?', '<', '>', '|'];

         function openRename() {
             input.value = origName; input.classList.remove('is-invalid');
             statusEl.textContent = ''; btnOk.disabled = true;
             btnRename.style.display = 'none'; renameRow.classList.add('active');
             var dot = origName.lastIndexOf('.');
             input.focus(); input.setSelectionRange(0, dot > 0 ? dot : origName.length);
         }
         function closeRename() { renameRow.classList.remove('active'); btnRename.style.display = ''; }
         function validate() {
             var v = input.value.trim();
             var hasBad = badChars.some(function(c) { return v.indexOf(c) !== -1; });
             var ok = v !== '' && !v.startsWith('.') && !hasBad;
             input.classList.toggle('is-invalid', !ok); return ok;
         }
         function doRename() {
             if (!validate()) return;
             var newName = input.value.trim();
             if (newName === origName) { closeRename(); return; }
             btnOk.disabled = true; statusEl.textContent = '';
             var fd = new FormData();
             fd.append('action', 'rename_file'); fd.append('file', fileRel); fd.append('new_name', newName);
             fetch(window.location.pathname + '?ajax=1', {
                 method: 'POST', headers: { 'X-Requested-With': 'XMLHttpRequest' }, body: fd
             })
             .then(function(r) { return r.json(); })
             .then(function(data) {
                 if (data.success) { App.refresh({}); FilePreview.close('pdf-preview-wrapper'); }
                 else { statusEl.textContent = data.message.replace(/<[^>]+>/g, ''); btnOk.disabled = false; }
             })
             .catch(function() { statusEl.textContent = lNetErr; btnOk.disabled = false; });
         }

         btnRename.addEventListener('click', openRename);
         btnOk.addEventListener('click', doRename);
         btnCancel.addEventListener('click', closeRename);
         input.addEventListener('input', function() {
             var ok = validate(); btnOk.disabled = !ok || input.value.trim() === origName;
         });
         input.addEventListener('keydown', function(e) {
             if (e.key === 'Enter')  { e.preventDefault(); doRename(); }
             if (e.key === 'Escape') { e.stopPropagation(); closeRename(); }
         });
     })(this)" style="display:none">
