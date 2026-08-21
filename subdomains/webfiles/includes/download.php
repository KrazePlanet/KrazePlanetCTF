<?php
// includes/download.php

/**
 * Single file download
 */

/**
 * Single file download (fixed for PHP 8.5.1 / IIS 10)
 * Supports preview (inline), download (attachment) and streaming (206 Partial Content)
 */
function handleDownload(string $baseDir): void {
    if (!isset($_GET['download'])) return;
    
    // 1. PATH VALIDATION AND SECURITY
    $rel = trim(urldecode($_GET['download']), '/\\');
    $baseReal = realpath($baseDir);
    $fullPath = realpath($baseReal . DIRECTORY_SEPARATOR . $rel);

    if (!$fullPath || !is_file($fullPath) || strpos($fullPath, $baseReal) !== 0) {
        http_response_code(403);
        exit('Access denied or file not found.');
    }

    // Flush output buffers before sending (critical for IIS)
    while (ob_get_level()) {
        ob_end_clean();
    }

    $fileName = basename($fullPath);
    $fileSize = filesize($fullPath);
    $ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
    $contentType = getMimeType($ext);

    // 2. DETERMINE DISPOSITION (Inline for preview, Attachment for download)
    // Keep the original list of extensions from the project
    $isForce = (isset($_GET['force']) && $_GET['force'] === '1');
    $inlineExtensions = ['pdf', 'txt', 'log', 'jpg', 'jpeg', 'png', 'gif', 'svg', 'webp', 'mp4', 'mov', 'webm', 'm4a', 'm4b'];
    
    $disposition = ($isForce || !in_array($ext, $inlineExtensions)) ? 'attachment' : 'inline';
    $encodedName = rawurlencode($fileName);

    // 3. PREPARE HEADERS AND RANGE
    $start = 0;
    $end = $fileSize - 1;

    header("Content-Type: $contentType");
    header("Content-Disposition: $disposition; filename=\"$encodedName\"; filename*=UTF-8''$encodedName");
    header("Accept-Ranges: bytes");
    header("Cache-Control: public, max-age=3600"); // Helps the browser cache static files

    if (isset($_SERVER['HTTP_RANGE'])) {
        $range = $_SERVER['HTTP_RANGE'];
        if (preg_match('/bytes=(\d+)-(\d+)?/', $range, $matches)) {
            $start = (int)$matches[1];
            if (isset($matches[2]) && $matches[2] !== '') {
                $end = (int)$matches[2];
            }
        }

        if ($start > $end || $start >= $fileSize) {
            header('HTTP/1.1 416 Requested Range Not Satisfiable');
            header("Content-Range: bytes */$fileSize");
            exit;
        }

        header('HTTP/1.1 206 Partial Content');
        header("Content-Range: bytes $start-$end/$fileSize");
        $length = $end - $start + 1;
    } else {
        $length = $fileSize;
    }

    header("Content-Length: $length");

    // 4. STREAMING OUTPUT (chunk reading to save RAM and support IIS FastCGI)
    $fp = fopen($fullPath, 'rb');
    if ($fp) {
        fseek($fp, $start);
        $buffer = 65536; // 64KB is optimal for Windows/IIS
        $bytesSent = 0;

        while (!feof($fp) && $bytesSent < $length && (connection_status() === 0)) {
            $chunkSize = (($length - $bytesSent) < $buffer) ? ($length - $bytesSent) : $buffer;
            $data = fread($fp, $chunkSize);
            echo $data;
            flush(); // Push data from PHP to IIS immediately
            $bytesSent += strlen($data);
        }
        fclose($fp);
    }
    exit;
}

/**
 * Bulk download with extended report (Size, MD5)
 */
/**
 * Bulk download — creates a ZIP archive with a detailed source report
 */
function downloadSelected(string $baseDir, array $selected): void {
    if (empty($selected)) die('No files selected.');
    
    $baseReal = realpath($baseDir);
    $zipName = 'files_' . date('Y-m-d_H-i') . '.zip';
    $tempZip = sys_get_temp_dir() . DIRECTORY_SEPARATOR . uniqid() . '.zip';
    
    $zip = new ZipArchive();
    if ($zip->open($tempZip, ZipArchive::CREATE) !== TRUE) {
        die('Error creating ZIP archive.');
    }

    $addedCount = 0;
    $usedNames = []; 

    // Column widths
    $wName = 50; 
    $wSize = 12;
    $wHash = 34;

    // Helper to pad strings correctly with UTF-8 / Cyrillic support
    $pad = function($str, $len) {
        return mb_str_pad($str, $len, " ", STR_PAD_RIGHT);
    };

    $separator = str_repeat("-", 140) . "\r\n";
    $pathsReport = "ZIP ARCHIVE SOURCE REPORT\r\n";
    $pathsReport .= "Created: " . date('Y-m-d H:i:s') . "\r\n";
    $pathsReport .= $separator;
    
    // Header row
    $pathsReport .= $pad("Archive Name", $wName) . " | " . 
                    $pad("Size", $wSize) . " | " . 
                    $pad("MD5 Hash", $wHash) . " | Source Path\r\n";
    $pathsReport .= $separator;

    foreach ($selected as $relPath) {
        $relPathFixed = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $relPath);
        $fullPath = realpath($baseReal . DIRECTORY_SEPARATOR . $relPathFixed);

        if ($fullPath && is_file($fullPath) && strpos($fullPath, $baseReal) === 0) {
            $originalName = basename($fullPath);
            $entryName = $originalName;

            if (isset($usedNames[$entryName])) {
                $pathInfo = pathinfo($originalName);
                $ext = isset($pathInfo['extension']) ? '.' . $pathInfo['extension'] : '';
                $nameOnly = $pathInfo['filename'];
                $counter = 1;
                while (isset($usedNames[$nameOnly . " ($counter)" . $ext])) { $counter++; }
                $entryName = $nameOnly . " ($counter)" . $ext;
            }

            $sizeStr = formatSize((int)filesize($fullPath));
            $md5Hash = md5_file($fullPath);
            
            // Keep only the folder path (without filename)
            $displayDir = '/' . str_replace('\\', '/', dirname($relPath));
            if ($displayDir === '/.') $displayDir = '/'; // Root

            if ($zip->addFile($fullPath, $entryName)) {
                // Build report row using mb_str_pad
                $pathsReport .= $pad($entryName, $wName) . " | " . 
                                $pad($sizeStr, $wSize) . " | " . 
                                $pad($md5Hash, $wHash) . " | " . 
                                $displayDir . "\r\n";
                
                $usedNames[$entryName] = true;
                $addedCount++;
            }
        }
    }

    if ($addedCount > 0) {
        $pathsReport .= $separator;
        $pathsReport .= "Total files: " . $addedCount . "\r\n";
        $zip->addFromString('_source_paths.txt', $pathsReport);
    }
    
    $zip->close();

    if ($addedCount === 0) {
        if (file_exists($tempZip)) @unlink($tempZip);
        die('No accessible files found.');
    }

    if (ob_get_level()) ob_end_clean();
    header('Content-Type: application/zip');
    header('Content-Disposition: attachment; filename="' . $zipName . '"');
    header('Content-Length: ' . filesize($tempZip));
    readfile($tempZip);
    @unlink($tempZip);
    exit;
}
