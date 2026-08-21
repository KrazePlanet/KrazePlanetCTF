<?php
/**
 * templates/edit_name.php
 * Rename file or folder modal.
 * JS logic is located in footer.php.
 */
if (empty($fileRel) || empty($fileName)) { die("Object not specified."); }
$isDir = $isDir ?? false;
$icon  = $isDir ? 'fa-solid fa-folder text-warning' : 'fa-solid fa-file text-secondary';
$title = $isDir ? $L['rename_modal']['title_folder'] : $L['rename_modal']['title_file'];
$label = $isDir ? $L['rename_modal']['label_old_folder'] : $L['rename_modal']['label_old_file'];
?>
<div id="rename-modal-wrapper"
     style="position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.5);
            display:flex;align-items:center;justify-content:center;z-index:10020;backdrop-filter:blur(3px);"
     onclick="FilePreview.close('rename-modal-wrapper')">
    <div class="card shadow-lg" style="width:95%;max-width:460px;border-radius:12px;overflow:hidden;"
         onclick="event.stopPropagation()">
        <div class="card-header d-flex justify-content-between align-items-center bg-white border-bottom">
            <span class="fw-bold small">
                <i class="fa-solid fa-pen-to-square text-primary me-2"></i><?= htmlspecialchars($title) ?>
            </span>
            <button class="btn-close btn-sm" onclick="FilePreview.close('rename-modal-wrapper')"></button>
        </div>
        <div class="card-body px-4 py-3">
            <label class="form-label small text-muted fw-bold mb-1"><?= htmlspecialchars($label) ?></label>
            <div class="text-dark small mb-3 text-truncate" title="<?= htmlspecialchars($fileName) ?>">
                <i class="<?= $icon ?> me-1"></i><?= htmlspecialchars($fileName) ?>
            </div>
            <label class="form-label small text-muted fw-bold mb-1" for="rename-input"><?= htmlspecialchars($L['rename_modal']['label_new']) ?></label>
            <input type="text" id="rename-input" class="form-control form-control-sm"
                   value="<?= htmlspecialchars($fileName) ?>"
                   data-file="<?= htmlspecialchars($fileRel) ?>"
                   data-original="<?= htmlspecialchars($fileName) ?>"
                   data-is-dir="<?= $isDir ? '1' : '0' ?>">
            <div id="rename-hint" class="form-text text-muted small mt-1"></div>
            <div id="rename-status" class="mt-2"></div>
        </div>
        <div class="card-footer bg-white d-flex justify-content-end gap-2 border-top">
            <button type="button" class="btn btn-secondary btn-sm px-4"
                    onclick="FilePreview.close('rename-modal-wrapper')"><?= htmlspecialchars($L['rename_modal']['btn_cancel']) ?></button>
            <button type="button" class="btn btn-primary btn-sm px-4" id="rename-submit" disabled>
                <i class="fa-solid fa-check me-1"></i><?= htmlspecialchars($L['rename_modal']['btn_rename']) ?>
            </button>
        </div>
    </div>
</div>
