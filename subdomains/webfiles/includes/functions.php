<?php
// includes/functions.php

/**
 * Safe path validation
 */
function isPathSafe(string $path, string $baseDir): bool {
    $realBase = realpath($baseDir);
    $realPath = realpath($path);
    if ($realBase === false || $realPath === false) {
        return false;
    }
    return (stripos($realPath, $realBase) === 0);
}

/**
 * Returns MIME type based on file extension
 */
function getMimeType(string $extension): string {
    $extension = strtolower(ltrim($extension, '.'));
    return match($extension) {
        'mp4'  => 'video/mp4',
        'webm' => 'video/webm',
        'ogg'  => 'video/ogg',
        'avi'  => 'video/x-msvideo',
        'mov'  => 'video/quicktime',
        'mkv'  => 'video/x-matroska',
        'mp3'  => 'audio/mpeg',
        'wav'  => 'audio/wav',
        'flac' => 'audio/flac',
        'm4a'  => 'audio/mp4',
        'm4b'  => 'audio/mp4',
        'aac'  => 'audio/aac',
        'pdf'  => 'application/pdf',
        'png'  => 'image/png',
        'jpg', 'jpeg' => 'image/jpeg',
        'gif'  => 'image/gif',
        'webp' => 'image/webp',
        'svg'  => 'image/svg+xml',
        default => 'application/octet-stream',
    };
}

/**
 * Unified file/folder data structure
 */
function mapFileInfo(SplFileInfo $file, string $baseDir): array {
    $isDir = $file->isDir();
    $path  = $file->getPathname();
    $name  = $file->getFilename();
    $ext   = $isDir ? 'folder' : strtolower($file->getExtension());

    $realBase  = realpath($baseDir) ?: $baseDir;
    $cleanPath = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $path);
    $cleanBase = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $realBase);
    $relPath   = ltrim(str_ireplace($cleanBase, '', $cleanPath), DIRECTORY_SEPARATOR);

    return [
        'name'         => $name,
        'is_dir'       => $isDir,
        'rel_path'     => $relPath,
        'size'         => $isDir ? 0 : ($file->getSize() ?: 0),
        'size_str'     => $isDir ? '—' : formatSize((int)$file->getSize()),
        'mtime'        => $file->getMTime() ?: 0,
        'mtime_str'    => date($GLOBALS['config']['date_format'] ?? 'Y-m-d H:i', $file->getMTime() ?: 0),
        'ext'          => $ext,
        'preview_type' => $isDir ? '' : $ext,
        'icon'         => getFileIcon($ext)
    ];
}

function formatSize(int $bytes): string {
    if ($bytes <= 0) return '0 B';
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];
    $i = (int) floor(log($bytes, 1024));
    return number_format($bytes / (1024 ** $i), $i > 1 ? 1 : 0, '.', ' ') . ' ' . $units[$i];
}

/**
 * Returns Font Awesome icon classes for a given file extension
 */
function getFileIcon(string $ext): string {
    if ($ext === 'folder') return 'fa-solid fa-folder text-warning';
    switch (strtolower($ext)) {
        case 'pdf':  return 'fa-solid fa-file-pdf text-danger';
        case 'doc':
        case 'docx': return 'fa-solid fa-file-word text-primary';
        case 'xls':
        case 'xlsx': return 'fa-solid fa-file-excel text-success';
        case 'zip':
        case 'rar':  return 'fa-solid fa-file-zipper text-warning';
        case 'jpg':
        case 'jpeg':
        case 'png':  return 'fa-solid fa-file-image text-info';
        case 'txt':  return 'fa-solid fa-file-lines text-secondary';
        default:     return 'fa-solid fa-file text-muted';
    }
}

function showFilePreviewCSV($path, $name) {
    global $L;
    $filePath = $path;
    $fileName = $name;
    $templatePath = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'templates' . DIRECTORY_SEPARATOR . 'preview_csv.php';
    if (file_exists($templatePath)) {
        include $templatePath;
    } else {
        error_log("Template not found: " . $templatePath);
    }
}

function showFilePreviewTXT($path, $name) {
    global $L;
    $filePath = $path;
    $fileName = $name;
    $templatePath = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'templates' . DIRECTORY_SEPARATOR . 'preview_txt.php';
    if (file_exists($templatePath)) {
        include $templatePath;
    }
}

function showFilePreviewCode(string $path, string $name): void {
    global $L;
    $filePath = $path;
    $fileName = $name;
    $templatePath = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'templates' . DIRECTORY_SEPARATOR . 'preview_code.php';
    if (file_exists($templatePath)) {
        include $templatePath;
    } else {
        showMessage("Code viewer template not found.", "danger", "Error");
    }
}

function showFilePreviewPDF($path, $name) {
    global $L;
    $fileName = $name;
    $fileRel  = $path;
    $templatePath = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'templates' . DIRECTORY_SEPARATOR . 'preview_pdf.php';
    if (file_exists($templatePath)) {
        include $templatePath;
    }
}

function showFilePreviewIMG($path, $name) {
    global $L;
    $filePath = $path;
    $fileName = $name;
    $templatePath = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'templates' . DIRECTORY_SEPARATOR . 'preview_img.php';
    if (file_exists($templatePath)) {
        include $templatePath;
    }
}

function showFilePreviewVideo(string $fileRel, string $fileName) {
    global $L;
    $filePath = $fileRel;
    $templatePath = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'templates' . DIRECTORY_SEPARATOR . 'preview_video.php';
    if (file_exists($templatePath)) {
        include $templatePath;
    } else {
        showMessage("Video player template not found.", "danger", "Error");
    }
}

function showFilePreviewAudioSingle(string $fileRel, string $fileName): void {
    global $L;
    $audioFile = $fileRel;
    $fileName  = $fileName;
    $templatePath = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'templates' . DIRECTORY_SEPARATOR . 'preview_audio_single.php';
    if (file_exists($templatePath)) {
        include $templatePath;
    } else {
        showMessage("Audio player template not found.", "danger", "Error");
    }
}

function showAudioPlayer(array $audioList, string $title = "Playlist"): void {
    global $L;
    $playlist = [];
    foreach ($audioList as $track) {
        $playlist[] = [
            'url'  => "index.php?download=" . urlencode($track['rel']),
            'name' => $track['name']
        ];
    }

    ob_start();
    $templatePath = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'templates' . DIRECTORY_SEPARATOR . 'audio_player.php';
    if (file_exists($templatePath)) {
        include $templatePath;
    } else {
        error_log("Template not found: " . $templatePath);
        echo "<div class='alert alert-danger'>Error: player module not found.</div>";
    }
    $html = ob_get_clean();

    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['success' => true, 'html' => $html]);
    exit;
}

function showMessage(string $message, string $msgType = 'warning', string $msgTitle = ''): void
{
    global $L;
    if ($msgTitle === '') {
        $msgTitle = $L['messages']['attention'] ?? 'Attention';
    }
    $templatePath = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'templates' . DIRECTORY_SEPARATOR . 'messages.php';
    if (file_exists($templatePath)) {
        include $templatePath;
    } else {
        echo "<!-- Template not found: $templatePath -->";
        echo "<script>alert('" . addslashes(strip_tags($message)) . "');</script>";
    }
}
