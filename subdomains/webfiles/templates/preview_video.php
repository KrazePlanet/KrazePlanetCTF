<?php
/**
 * templates/preview_video.php
 */
if (empty($filePath)) {
    die("<div class='alert alert-danger'>Video file not specified.</div>");
}
$extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
$videoUrl  = "index.php?download=" . rawurlencode($filePath);
?>
<style>
    #video-preview-wrapper {
        position: fixed; top: 0; left: 0; width: 100%; height: 100%;
        background: rgba(0,0,0,0.85); display: flex; align-items: center;
        justify-content: center; z-index: 10006;
    }
    .video-card {
        background: #000; width: 95vw; max-width: 1100px; border-radius: 8px;
        overflow: hidden; box-shadow: 0 10px 30px rgba(0,0,0,0.5);
        display: flex; flex-direction: column;
    }
    .video-header {
        padding: 12px 20px; background: #f8f9fa; display: flex;
        align-items: center; justify-content: space-between;
        border-bottom: 1px solid #dee2e6;
    }
    .video-container {
        background: #000; line-height: 0; position: relative;
        aspect-ratio: 16 / 9; display: flex; align-items: center; justify-content: center;
    }
    video {
        width: 100%; height: 100%; max-height: 75vh;
        outline: none; background: #000; object-fit: contain;
    }
    .btn-close-video {
        background: none; border: none; font-size: 1.2rem; color: #6c757d;
        transition: color 0.2s; padding: 0; line-height: 1;
    }
    .btn-close-video:hover { color: #000; }
</style>
<div id="video-preview-wrapper" onclick="FilePreview.close('video-preview-wrapper')">
    <div class="video-card" onclick="event.stopPropagation()">
        <div class="video-header">
            <span class="fw-bold text-truncate flex-grow-1">
                <i class="fa-solid fa-video text-primary me-2"></i><?= htmlspecialchars($fileName) ?>
            </span>
            <button type="button" class="btn-close-video ms-3"
                    onclick="FilePreview.close('video-preview-wrapper')"
                    title="<?= htmlspecialchars($L['video_viewer']['close']) ?>">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>
        <div class="video-container">
            <video controls autoplay playsinline name="media">
                <source src="<?= htmlspecialchars($videoUrl) ?>">
                <?= htmlspecialchars($L['video_viewer']['no_support']) ?>
            </video>
        </div>
        <div class="p-2 bg-light border-top d-flex justify-content-between align-items-center">
            <div class="small text-muted">
                <i class="fa-solid fa-circle-info me-1"></i>
                <?= htmlspecialchars($L['video_viewer']['format']) ?> <?= strtoupper($extension) ?>
            </div>
            <div class="d-flex gap-2">
                <a href="<?= htmlspecialchars($videoUrl) ?>&force=1" download
                   class="btn btn-primary btn-sm">
                    <i class="fa-solid fa-download me-1"></i> <?= htmlspecialchars($L['video_viewer']['download']) ?>
                </a>
                <button class="btn btn-secondary btn-sm px-4"
                        onclick="FilePreview.close('video-preview-wrapper')"><?= htmlspecialchars($L['video_viewer']['close']) ?></button>
            </div>
        </div>
    </div>
</div>