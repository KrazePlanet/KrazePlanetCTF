<?php
// includes/directory.php
function fetchItems(string $targetDir, bool $isSearch, string $query, array $config, string $sort = 'mtime', string $order = 'desc'): array {
    $items = [];
    $baseDir = realpath($config['base_dir']);
    if (!isPathSafe($targetDir, $baseDir)) {
        $targetDir = $baseDir;
    }
    try {
        if ($isSearch) {
            $queryTrimmed = trim($query);
            $patterns = array_filter(
                array_map('trim', explode(';', $queryTrimmed)),
                fn($p) => $p !== ''
            );
            $isGlob = !empty(array_filter($patterns, fn($p) => str_contains($p, '*') || str_contains($p, '?')));
            $directory = new RecursiveDirectoryIterator($targetDir, RecursiveDirectoryIterator::SKIP_DOTS);
            $iterator  = new RecursiveIteratorIterator(
                $directory,
                RecursiveIteratorIterator::SELF_FIRST,
                RecursiveIteratorIterator::CATCH_GET_CHILD
            );
            foreach ($iterator as $file) {
                $name = $file->getFilename();
                $matched = false;
                if ($isGlob) {
                    foreach ($patterns as $pattern) {
                        if (fnmatch(mb_strtolower($pattern), mb_strtolower($name))) {
                            $matched = true; break;
                        }
                    }
                } else {
                    foreach ($patterns as $pattern) {
                        if (mb_stripos($name, $pattern) !== false) {
                            $matched = true; break;
                        }
                    }
                }
                if ($matched) $items[] = mapFileInfo($file, $baseDir);
                if (count($items) > 1000) break;
            }
        } else {
            $iterator = new DirectoryIterator($targetDir);
            foreach ($iterator as $file) {
                if ($file->isDot()) continue;
                $items[] = mapFileInfo($file, $baseDir);
            }
        }
    } catch (Exception $e) {
        error_log("Directory fetch error: " . $e->getMessage());
    }
    // Exclude pattern filtering
    $exclude = $config['exclude_patterns'] ?? [];
    if (!empty($exclude)) {
        $items = array_filter($items, function($item) use ($exclude) {
            $nameLower = mb_strtolower($item['name']);
            foreach ($exclude as $pattern) {
                $patLower = mb_strtolower($pattern);
                if (str_contains($pattern, '*') || str_contains($pattern, '?')) {
                    // Glob pattern (*.exe, *.db, etc.):
                    // compare both in lowercase — avoids missing
                    // FNM_CASEFOLD on Windows and bracket issues in fnmatch
                    // assuming brackets are not used in glob patterns.
                    if (fnmatch($patLower, $nameLower)) return false;
                } else {
                    // Literal name (Thumbs.db, robots.txt, bin, ...):
                    // brackets in fnmatch are treated as character class,
                    // so for literals we use simple string comparison.
                    if ($patLower === $nameLower) return false;
                }
            }
            return true;
        });
        $items = array_values($items);
    }
    // Validate sort parameters
    $validSorts  = ['name', 'mtime', 'size'];
    $validOrders = ['asc', 'desc'];
    if (!in_array($sort,  $validSorts))  $sort  = 'mtime';
    if (!in_array($order, $validOrders)) $order = 'desc';
    // Sort: folders always on top, within groups — by selected criterion
    usort($items, function($a, $b) use ($sort, $order) {
        // Folders always above files
        if ($a['is_dir'] !== $b['is_dir']) {
            return $b['is_dir'] <=> $a['is_dir'];
        }
        $cmp = match($sort) {
            'name'  => strnatcasecmp($a['name'], $b['name']),
            'size'  => $a['size'] <=> $b['size'],
            default => $a['mtime'] <=> $b['mtime'],  // mtime
        };
        return $order === 'asc' ? $cmp : -$cmp;
    });
    return $items;
}
