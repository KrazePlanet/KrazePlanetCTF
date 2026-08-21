<?php
/**
 * templates/preview_img.php
 */
?>
<style>
    #img-preview-wrapper {
        position: fixed; top: 0; left: 0; width: 100%; height: 100%;
        background: rgba(0, 0, 0, 0.92); display: flex; align-items: center;
        justify-content: center; z-index: 10005; overflow: hidden;
        opacity: 1; transition: opacity 0.2s ease;
    }
    .img-card {
        background: #fff; width: 95%; height: 95vh; border-radius: 8px;
        display: flex; flex-direction: column; position: relative;
        transition: width 0.2s ease, height 0.2s ease, border-radius 0.2s ease;
    }
    .img-header {
        padding: 10px 20px; background: #f8f9fa; border-bottom: 1px solid #ddd;
        display: flex; justify-content: space-between; align-items: center;
        border-top-left-radius: 8px; border-top-right-radius: 8px; z-index: 20; flex-shrink: 0;
    }
    .img-viewport {
        flex-grow: 1; position: relative; overflow: hidden;
        background: #1a1a1a; cursor: grab; display: flex;
        align-items: center; justify-content: center;
    }
    .img-viewport.dragging { cursor: grabbing; }
    #preview-image {
        max-width: 90%; max-height: 90%;
        transition: transform 0.15s ease-out, opacity 0.25s ease;
        user-select: none; -webkit-user-drag: none;
    }
    #preview-image.img-fade-out { opacity: 0; transform: scale(0.96); }
    .zoom-indicator { min-width: 55px; display: inline-block; text-align: center; font-weight: bold; }
    .nav-zone {
        position: absolute; top: 0; width: 22%; height: 100%;
        z-index: 15; display: flex; align-items: center; pointer-events: auto;
    }
    .nav-zone-prev { left: 0; justify-content: flex-start; }
    .nav-zone-next { right: 0; justify-content: flex-end; }
    .nav-arrow {
        width: 52px; height: 52px; background: rgba(255,255,255,0.10);
        backdrop-filter: blur(10px); -webkit-backdrop-filter: blur(10px);
        border: 1.5px solid rgba(255,255,255,0.22); border-radius: 50%;
        display: flex; align-items: center; justify-content: center; color: #fff;
        font-size: 1.15rem; cursor: pointer; opacity: 0;
        transform: scale(0.72) translateX(0);
        transition: opacity 0.22s ease, transform 0.22s ease, background 0.15s ease, border-color 0.15s ease;
        margin: 0 18px; box-shadow: 0 4px 28px rgba(0,0,0,0.45);
        user-select: none; flex-shrink: 0; pointer-events: none;
    }
    .nav-zone:hover .nav-arrow { opacity: 1; transform: scale(1) translateX(0); pointer-events: auto; }
    .nav-arrow:hover { background: rgba(255,255,255,0.26); border-color: rgba(255,255,255,0.5); box-shadow: 0 6px 32px rgba(0,0,0,0.55); }
    .nav-arrow:active { transform: scale(0.91) !important; }
    .nav-zone-prev:hover .nav-arrow { transform: scale(1) translateX(4px); }
    .nav-zone-next:hover .nav-arrow { transform: scale(1) translateX(-4px); }
    .img-counter {
        position: absolute; bottom: 12px; left: 50%; transform: translateX(-50%);
        background: rgba(0,0,0,0.52); color: #fff; padding: 3px 16px;
        border-radius: 20px; font-size: 0.78rem; letter-spacing: 0.05em;
        pointer-events: none; z-index: 16; opacity: 0; transition: opacity 0.2s; white-space: nowrap;
    }
    .img-viewport:hover .img-counter { opacity: 1; }
    .nav-zone.nav-hidden { display: none !important; }
    #img-preview-wrapper.is-fullscreen .img-card { width: 100%; height: 100vh; border-radius: 0; }
    #img-preview-wrapper.is-fullscreen .img-header { border-radius: 0; }
    .btn-viewer-img {
        background: #fff; border: 1px solid #ced4da; color: #495057;
        border-radius: 6px; padding: 3px 9px; font-size: 0.8rem;
        cursor: pointer; transition: background 0.15s; line-height: 1.4;
    }
    .btn-viewer-img:hover  { background: #e9ecef; border-color: #adb5bd; }
    .btn-viewer-img.active { background: #0d6efd; border-color: #0d6efd; color: #fff; }
</style>

<div id="img-preview-wrapper" onclick="FilePreview.close('img-preview-wrapper')">
    <div class="img-card" onclick="event.stopPropagation()">
        <div class="img-header">
            <div class="d-flex align-items-center gap-2 overflow-hidden" style="max-width: 45%;">
                <i class="fa-solid fa-image text-primary flex-shrink-0"></i>
                <span id="img-preview-filename" class="fw-bold text-truncate"><?= htmlspecialchars($fileName) ?></span>
                <span id="img-header-counter"
                      class="badge text-muted fw-normal flex-shrink-0"
                      style="background:#e9ecef; font-size:0.78rem; letter-spacing:0.03em; padding: 3px 9px; border-radius: 20px; white-space:nowrap;"></span>
            </div>
            <div class="d-flex align-items-center gap-3">
                <div class="d-flex align-items-center bg-white border rounded px-2 shadow-sm">
                    <button class="btn btn-link btn-sm text-secondary p-1" onclick="FilePreview.zoom(0.8)">
                        <i class="fa-solid fa-minus"></i>
                    </button>
                    <span id="zoom-percent" class="zoom-indicator text-dark">100%</span>
                    <button class="btn btn-link btn-sm text-secondary p-1" onclick="FilePreview.zoom(1.25)">
                        <i class="fa-solid fa-plus"></i>
                    </button>
                </div>
                <div class="btn-group">
                    <button class="btn btn-sm btn-outline-secondary" onclick="FilePreview.reset()"
                            title="<?= htmlspecialchars($L['img_viewer']['reset']) ?>">
                        <i class="fa-solid fa-arrows-rotate"></i>
                    </button>
                    <button class="btn btn-sm btn-outline-secondary" onclick="FilePreview.rotate()"
                            title="<?= htmlspecialchars($L['img_viewer']['rotate']) ?>">
                        <i class="fa-solid fa-rotate-right"></i>
                    </button>
                </div>
                <button class="btn-viewer-img" id="img-fullscreen"
                        title="<?= htmlspecialchars($L['img_viewer']['fullscreen']) ?>">
                    <i class="fa-solid fa-expand"></i>
                </button>
                <button type="button" class="btn-close ms-2"
                        onclick="FilePreview.close('img-preview-wrapper')"></button>
            </div>
        </div>

        <div class="img-viewport" id="viewport">
            <?php $imgUrl = "index.php?download=" . urlencode($filePath); ?>
            <div class="nav-zone nav-zone-prev" id="nav-zone-prev">
                <div class="nav-arrow" id="nav-prev" title="<?= htmlspecialchars($L['img_viewer']['prev']) ?>">
                    <i class="fa-solid fa-chevron-left"></i>
                </div>
            </div>
            <img id="preview-image"
                 src="<?= htmlspecialchars($imgUrl) ?>"
                 alt="Preview"
                 data-file="<?= htmlspecialchars($filePath) ?>"
                 data-l-error="<?= htmlspecialchars($L['img_viewer']['load_error'], ENT_QUOTES) ?>"
                 onerror="this.style.opacity='0.4'; this.alt=this.dataset.lError;">
            <div class="nav-zone nav-zone-next" id="nav-zone-next">
                <div class="nav-arrow" id="nav-next" title="<?= htmlspecialchars($L['img_viewer']['next']) ?>">
                    <i class="fa-solid fa-chevron-right"></i>
                </div>
            </div>
            <div class="img-counter" id="img-counter"></div>
        </div>

        <div class="p-2 bg-light border-top d-flex justify-content-between align-items-center" style="flex-shrink: 0;">
            <div class="small text-muted">
                <i class="fa-solid fa-circle-info me-1"></i>
                <?= htmlspecialchars($L['img_viewer']['hint']) ?>
            </div>
            <div class="d-flex gap-2">
                <a id="img-download-link"
                   href="<?= htmlspecialchars($imgUrl) ?>&force=1"
                   download="<?= htmlspecialchars($fileName) ?>"
                   class="btn btn-sm btn-primary px-3">
                    <i class="fa-solid fa-download me-1"></i> <?= htmlspecialchars($L['img_viewer']['download']) ?>
                </a>
                <button class="btn btn-secondary btn-sm px-4"
                        onclick="FilePreview.close('img-preview-wrapper')"><?= htmlspecialchars($L['img_viewer']['close']) ?></button>
            </div>
        </div>
    </div>
</div>

<?php
$_l_collapse   = addslashes($L['img_viewer']['collapse']);
$_l_fullscreen = addslashes($L['img_viewer']['fullscreen']);
?>
<img src="data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7"
     style="display:none;"
     onload="(function(){
    const gallery  = Array.isArray(FilePreview.gallery) ? FilePreview.gallery : [];
    let   curIdx   = (FilePreview.galleryIndex !== undefined) ? FilePreview.galleryIndex : 0;
    const imgEl    = document.getElementById('preview-image');
    const nameEl   = document.getElementById('img-preview-filename');
    const dlLink   = document.getElementById('img-download-link');
    const counterEl   = document.getElementById('img-counter');
    const headerCount = document.getElementById('img-header-counter');
    const zonePrev = document.getElementById('nav-zone-prev');
    const zoneNext = document.getElementById('nav-zone-next');

    if (gallery.length <= 1) { zonePrev.classList.add('nav-hidden'); zoneNext.classList.add('nav-hidden'); }

    function updateCounter() {
        if (gallery.length > 1) {
            const txt = (curIdx + 1) + ' / ' + gallery.length;
            counterEl.textContent = txt; headerCount.textContent = txt;
        }
    }
    function navigateTo(newIdx) {
        if (gallery.length <= 1) return;
        curIdx = ((newIdx % gallery.length) + gallery.length) % gallery.length;
        const entry = gallery[curIdx];
        const newUrl = 'index.php?download=' + encodeURIComponent(entry.file);
        FilePreview.state = { scale: 1, rotation: 0, posX: 0, posY: 0 }; FilePreview.applyImg();
        imgEl.classList.add('img-fade-out');
        setTimeout(function() {
            imgEl.src = newUrl; imgEl.alt = 'Preview'; imgEl.dataset.file = entry.file;
            nameEl.textContent = entry.name; dlLink.href = newUrl + '&force=1'; dlLink.download = entry.name;
            updateCounter(); imgEl.classList.remove('img-fade-out');
        }, 220);
    }
    document.getElementById('nav-prev').addEventListener('click', function(e) { e.stopPropagation(); navigateTo(curIdx - 1); });
    document.getElementById('nav-next').addEventListener('click', function(e) { e.stopPropagation(); navigateTo(curIdx + 1); });
    FilePreview._galleryKeyHandler = function(e) {
        if (!document.getElementById('img-preview-wrapper')) return;
        if (e.key === 'ArrowLeft')  { e.preventDefault(); navigateTo(curIdx - 1); }
        if (e.key === 'ArrowRight') { e.preventDefault(); navigateTo(curIdx + 1); }
    };
    document.addEventListener('keydown', FilePreview._galleryKeyHandler);
    updateCounter();

    const imgWrapper = document.getElementById('img-preview-wrapper');
    const btnImgFs   = document.getElementById('img-fullscreen');
    let imgFullscreen = false;
    function applyImgFullscreen() {
        if (imgFullscreen) {
            imgWrapper.classList.add('is-fullscreen');
            btnImgFs.innerHTML = '<i class=&quot;fa-solid fa-compress&quot;></i>';
            btnImgFs.title     = '<?= $_l_collapse ?>';
            btnImgFs.classList.add('active');
        } else {
            imgWrapper.classList.remove('is-fullscreen');
            btnImgFs.innerHTML = '<i class=&quot;fa-solid fa-expand&quot;></i>';
            btnImgFs.title     = '<?= $_l_fullscreen ?>';
            btnImgFs.classList.remove('active');
        }
    }
    btnImgFs.addEventListener('click', function(e) { e.stopPropagation(); imgFullscreen = !imgFullscreen; applyImgFullscreen(); });
})()">