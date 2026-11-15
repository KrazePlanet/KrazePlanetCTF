<?php
// config/labs.php - The lab list (data/labs.json) indexed for lookups.
// A lab's id is its "access" path, e.g. "/subdomains/tutorialrepublic" - the
// same id the central server stores when a student marks it solved.

// Short name used for a lab's Solution page and report file:
// "/subdomains/tutorialrepublic" -> "tutorialrepublic"; a full URL -> its first host label.
function krazeLabSlug($access) {
    if (preg_match('#^/subdomains/([A-Za-z0-9_-]+)#', (string)$access, $m)) return strtolower($m[1]);
    $host = parse_url((string)$access, PHP_URL_HOST);
    return $host ? strtolower(explode('.', $host)[0]) : 'lab';
}

// A lab only counts as solved once the student has written its report:
// <their GitHub repo>/<branch>/<lab slug>.md must exist and not be empty
// (the same file "Write Report" creates). $repo is the profile's GitHub link.
// Returns ['ok' => true, 'url' => ...] or
//         ['ok' => false, 'reason' => 'no_repo'|'no_report'|'unreachable', 'url' => ...].
function krazeReportCheck($labId, $repo) {
    if (!preg_match('#^https://github\.com/([A-Za-z0-9-]+/[A-Za-z0-9._-]+)(?:/tree/([A-Za-z0-9._/-]+))?$#', (string)$repo, $m)) {
        return ['ok' => false, 'reason' => 'no_repo'];
    }
    $branch = !empty($m[2]) ? $m[2] : 'main';
    $file = krazeLabSlug($labId) . '.md';
    $pageUrl = "https://github.com/{$m[1]}/blob/{$branch}/{$file}";
    // raw.githubusercontent.com returns the file itself; the unique query skips GitHub's
    // cache so a report written a moment ago is found straight away.
    $ch = curl_init("https://raw.githubusercontent.com/{$m[1]}/{$branch}/" . rawurlencode($file) . '?kp=' . bin2hex(random_bytes(4)));
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => false,
        CURLOPT_CONNECTTIMEOUT => 4,
        CURLOPT_TIMEOUT        => 8,
        CURLOPT_RANGE          => '0-4095', // the first few KB is enough to know it isn't empty
        CURLOPT_USERAGENT      => 'KrazePlanet-report-check',
    ]);
    $body = curl_exec($ch);
    $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    if ($body === false || $status === 0 || $status >= 500) return ['ok' => false, 'reason' => 'unreachable', 'url' => $pageUrl];
    if (($status === 200 || $status === 206) && trim((string)$body) !== '') return ['ok' => true, 'url' => $pageUrl];
    return ['ok' => false, 'reason' => 'no_report', 'url' => $pageUrl];
}

function krazeLabCatalog() {
    static $cat = null;
    if ($cat !== null) return $cat;
    $raw = json_decode((string)@file_get_contents(__DIR__ . '/../data/labs.json'), true);
    $cat = ['byId' => [], 'byCategory' => []];
    foreach (is_array($raw) ? $raw : [] as $category => $labs) {
        foreach ((array)$labs as $lab) {
            $id = (string)($lab['access'] ?? '');
            if ($id === '') continue;
            $cat['byId'][$id] = [
                'title'      => (string)($lab['title'] ?? $id),
                'category'   => (string)$category,
                'difficulty' => strtolower((string)($lab['difficulty'] ?? 'easy')),
            ];
            $cat['byCategory'][$category][] = $id;
        }
    }
    return $cat;
}
