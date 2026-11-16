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

// "Source code" in a lab's ⋯ menu: the lab's folder in the public KrazePlanetLabs repo,
// built from its "access" in data/labs.json:
//   "/subdomains/tutorialrepublic" -> github.com/KrazePlanet/KrazePlanetLabs/tree/main/subdomains/tutorialrepublic
// Labs on their own domain ("https://...") have no folder there, so they get no link ('').
const KRAZE_LAB_CODE_REPO = 'KrazePlanet/KrazePlanetLabs';

function krazeLabCodeUrl($access) {
    return preg_match('#^/subdomains/([A-Za-z0-9_-]+)#', (string)$access, $m)
        ? 'https://github.com/' . KRAZE_LAB_CODE_REPO . '/tree/main/subdomains/' . $m[1] : '';
}

// Every student keeps their reports in a repository with this same name, so all we
// ask for is their GitHub username: github.com/<username>/KrazePlanet-Reports
const KRAZE_REPORTS_REPO = 'KrazePlanet-Reports';

function krazeReportsRepoUrl($username) {
    return 'https://github.com/' . $username . '/' . KRAZE_REPORTS_REPO;
}

// "octocat", "@octocat", "github.com/octocat" or a full repo link -> "octocat".
// '' stays '' (disconnect). Returns null when it isn't a valid GitHub username.
function krazeGithubUsername($raw) {
    $raw = trim((string)$raw);
    if ($raw === '') return '';
    $raw = preg_replace('#^(?:https?://)?(?:www\.)?github\.com/#i', '', ltrim($raw, '@'));
    $raw = explode('/', $raw)[0];
    // GitHub usernames: letters, numbers and single hyphens, up to 39 characters
    return preg_match('/^[A-Za-z0-9](?:[A-Za-z0-9]|-(?=[A-Za-z0-9])){0,38}$/', $raw) ? $raw : null;
}

// The username in a saved repository link ('' if none)
function krazeGithubUserFromRepo($repo) {
    return preg_match('#^https://github\.com/([A-Za-z0-9-]+)/#', (string)$repo, $m) ? $m[1] : '';
}

// Does github.com/<username>/KrazePlanet-Reports exist (and is it public)?
// true / false, or null if GitHub couldn't be reached.
function krazeRepoExists($username) {
    $ch = curl_init(krazeReportsRepoUrl($username));
    curl_setopt_array($ch, [
        CURLOPT_NOBODY => true, CURLOPT_RETURNTRANSFER => true, CURLOPT_FOLLOWLOCATION => false,
        CURLOPT_CONNECTTIMEOUT => 4, CURLOPT_TIMEOUT => 6, CURLOPT_USERAGENT => 'KrazePlanet-repo-check',
    ]);
    curl_exec($ch);
    $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    if ($status === 200) return true;
    if ($status === 404) return false;
    return null;
}

// The README text. At the top: the student's live progress card (api/card.php: their
// ring, % solved, points, rank and Easy/Medium/Hard/Secure bars, always current).
// Then live badges (api/badge.php via shields.io) and links back to the site.
function krazeReadmeText($kpUser) {
    $site = rtrim(krazeSiteUrl('/'), '/');
    $hub = 'https://labs.krazeplanet.com/';
    $profile = $site . '/navbar/profile.php?user=' . rawurlencode($kpUser);
    $board = $site . '/navbar/scoreboard.php?period=all';
    $card = $site . '/api/card.php?user=' . rawurlencode($kpUser);
    $badge = fn($type, $alt) => '<img src="https://img.shields.io/endpoint?style=for-the-badge&url='
        . rawurlencode($site . '/api/badge.php?user=' . rawurlencode($kpUser) . '&type=' . $type) . '" alt="' . $alt . '">';

    return <<<MD
<p align="center">
  <a href="{$site}/"><img src="{$card}" alt="KrazePlanet Labs: free, hands-on web security labs" width="100%"></a>
</p>

<h1 align="center">KrazePlanet Reports</h1>

<p align="center">
  My reports for the labs I solved on <a href="{$hub}"><b>KrazePlanet Labs</b></a>,<br>
  free, hands-on web security labs where every lab hides a real vulnerability.
</p>

<p align="center">
  <a href="{$profile}">{$badge('points', 'Points')}</a>
  <a href="{$profile}">{$badge('solved', 'Labs solved')}</a>
  <a href="{$board}">{$badge('rank', 'Rank')}</a>
</p>

<p align="center">
  <a href="{$profile}">My profile</a> &nbsp;·&nbsp;
  <a href="{$board}">Scoreboard</a> &nbsp;·&nbsp;
  <a href="{$hub}">All lab websites</a>
</p>

## About

Every report is one lab I solved, in a folder for its bug type (for example `Cross-Site Scripting/`). Each report covers:

- **The bug**: what the vulnerability is
- **How I found it**: the steps to reproduce it
- **Why it matters**: what an attacker could do with it

## Try it yourself

KrazePlanet Labs are free and need nothing installed. [Start with an Easy lab]({$site}/) and see how many you can solve.

MD;
}

// GitHub's "new file" page with README.md already named and written, so adding it
// is one click on "Commit changes". Works on an empty repository too.
function krazeReadmeUrl($githubUser, $kpUser) {
    return 'https://github.com/' . $githubUser . '/' . KRAZE_REPORTS_REPO . '/new/main?filename=README.md&value=' . rawurlencode(krazeReadmeText($kpUser));
}

// Reports are grouped in one folder per bug type (the category in data/labs.json):
//   "Cross-Site Scripting" + /subdomains/tutorialrepublic -> Cross-Site Scripting/tutorialrepublic.md
// A "/" in a category would make a sub-folder, so it becomes "-":
//   "Username/Email Enumeration" -> "Username-Email Enumeration"
function krazeReportFolder($category) {
    return trim(str_replace(['/', '\\'], '-', (string)$category));
}

// The report file for a lab, inside the student's KrazePlanet-Reports repository.
function krazeReportPath($labId) {
    $category = krazeLabCatalog()['byId'][$labId]['category'] ?? '';
    $file = krazeLabSlug($labId) . '.md';
    return $category !== '' ? krazeReportFolder($category) . '/' . $file : $file;
}

// A report must be the student's own work, not the empty template:
//  - Description, Steps to Reproduce, POC and Impact each have the student's own text
//    (the grey <!-- hints --> and the pre-filled "**Affected URL:**" line don't count);
//  - Steps to Reproduce or POC mention the lab's address (e.g. "tutorialrepublic.");
//  - at least KRAZE_REPORT_MIN_WORDS words, not counting the title (pre-filled with the lab's name).
// Returns a list of problems in plain English; empty list = the report is fine.
const KRAZE_REPORT_MIN_WORDS = 200;

function krazeReportProblems($markdown, $labId) {
    // Split into sections by heading. A deeper heading belongs to the section above it:
    // "## Proof of Concept" + "### Screenshot" + "### Payload" is all one POC section.
    // Inside ``` code blocks nothing is special: a payload like  ...<!--  or a line
    // starting with # is the student's content, not a comment or a heading.
    // Only comments that start a line are removed (the template's grey hints).
    $sections = [];
    $current = null;
    $level = 0;
    $fence = null;      // the ``` or ~~~ that opened the current code block
    $inComment = false; // inside a multi-line <!-- hint -->
    foreach (explode("\n", str_replace("\r", '', (string)$markdown)) as $line) {
        if ($fence !== null) {
            if (preg_match('/^\s{0,3}' . preg_quote($fence, '/') . '\s*$/', $line)) $fence = null;
            if ($current !== null) $sections[$current] .= $line . "\n";
            continue;
        }
        if ($inComment) {
            if (strpos($line, '-->') !== false) $inComment = false;
            continue;
        }
        if (preg_match('/^\s{0,3}(```+|~~~+)/', $line, $f)) {
            $fence = $f[1];
            if ($current !== null) $sections[$current] .= "\n";
            continue;
        }
        if (preg_match('/^\s*<!--/', $line)) { // a hint line: <!-- ... --> (or the start of a longer one)
            if (strpos($line, '-->') === false) $inComment = true;
            continue;
        }
        if (preg_match('/^\s{0,3}(#{1,6})\s*(.+?)\s*#*\s*$/', $line, $h)) {
            if ($current !== null && strlen($h[1]) > $level) { $sections[$current] .= $h[2] . "\n"; continue; }
            $current = strtolower($h[2]);
            $level = strlen($h[1]);
            $sections[$current] = $sections[$current] ?? '';
            continue;
        }
        if ($current !== null) $sections[$current] .= $line . "\n";
    }
    $find = function (array $names) use ($sections) {
        foreach ($sections as $heading => $body) foreach ($names as $n) if (strpos($heading, $n) !== false) return $body;
        return null;
    };
    $wordsIn = fn($s) => preg_match_all('/[\p{L}\p{N}][\p{L}\p{N}\'’._\/:@=?&%-]*/u', (string)$s);
    // The pre-filled "**Affected URL:** https://..." line is not the student's writing
    $own = fn($s) => trim(preg_replace('/^\s*\**\s*affected url:?\s*\**:?.*$/mi', '', (string)$s));

    $required = [
        'Description'        => ['description'],
        'Steps to Reproduce' => ['steps', 'reproduce'],
        'POC'                => ['poc', 'proof of concept'],
        'Impact'             => ['impact'],
    ];
    $problems = [];
    $empty = [];
    foreach ($required as $label => $names) {
        $body = $find($names);
        if ($body === null || $wordsIn($own($body)) === 0) $empty[] = $label;
    }
    if ($empty) $problems[] = 'Write the ' . implode(', ', $empty) . (count($empty) > 1 ? ' sections.' : ' section.');

    // Proof the student worked in this lab: its address in the steps or the proof of concept
    $host = (string)parse_url(krazeLabUrl($labId), PHP_URL_HOST);
    $needle = preg_match('#^/subdomains/#', (string)$labId) ? explode('.', $host)[0] . '.' : $host;
    $evidence = $own($find(['steps', 'reproduce']) ?? '') . "\n" . ($find(['poc', 'proof of concept']) ?? '');
    if ($host !== '' && stripos($evidence, $needle) === false) {
        $problems[] = 'Include the lab\'s address (' . $host . ') in Steps to Reproduce or POC.';
    }

    $words = 0;
    foreach ($sections as $heading => $body) if (strpos($heading, 'title') === false) $words += $wordsIn($own($body));
    if ($words < KRAZE_REPORT_MIN_WORDS) {
        $problems[] = 'Write at least ' . KRAZE_REPORT_MIN_WORDS . ' words of your own (you have ' . $words . ').';
    }
    return $problems;
}

// GitHub's editor for a report file, so a student can fix it in one click
function krazeReportEditUrl($blobUrl) {
    return preg_replace('#^(https://github\.com/[^/]+/[^/]+)/blob/#', '$1/edit/', (string)$blobUrl);
}

// A lab only counts as solved once the student has written its report: the file
// "Write Report" creates (<Category>/<slug>.md) must exist and not be empty.
// Reports saved before the folders existed (<slug>.md at the top) still count.
// $repo is the profile's GitHub link.
// Returns ['ok' => true, 'url' => ...] or
//         ['ok' => false, 'reason' => 'no_repo'|'no_report'|'unreachable', 'url' => ...] or
//         ['ok' => false, 'reason' => 'weak_report', 'url' => <edit link>, 'problems' => [...]].
function krazeReportCheck($labId, $repo) {
    if (!preg_match('#^https://github\.com/([A-Za-z0-9-]+/[A-Za-z0-9._-]+)(?:/tree/([A-Za-z0-9._/-]+))?$#', (string)$repo, $m)) {
        return ['ok' => false, 'reason' => 'no_repo'];
    }
    $branch = !empty($m[2]) ? $m[2] : 'main';
    $paths = array_unique([krazeReportPath($labId), krazeLabSlug($labId) . '.md']);
    $encode = fn($path) => implode('/', array_map('rawurlencode', explode('/', $path)));
    $pageUrl = fn($path) => "https://github.com/{$m[1]}/blob/{$branch}/" . $encode($path);

    $unreachable = false;
    foreach ($paths as $path) {
        // raw.githubusercontent.com returns the file itself; the unique query skips GitHub's
        // cache so a report written a moment ago is found straight away.
        $ch = curl_init("https://raw.githubusercontent.com/{$m[1]}/{$branch}/" . $encode($path) . '?kp=' . bin2hex(random_bytes(4)));
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_CONNECTTIMEOUT => 4,
            CURLOPT_TIMEOUT        => 8,
            CURLOPT_RANGE          => '0-262143', // up to 256 KB: the whole report text
            CURLOPT_USERAGENT      => 'KrazePlanet-report-check',
        ]);
        $body = curl_exec($ch);
        $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        if ($body === false || $status === 0 || $status >= 500) { $unreachable = true; continue; }
        if (($status === 200 || $status === 206) && trim((string)$body) !== '') {
            $problems = krazeReportProblems($body, $labId);
            if ($problems) return ['ok' => false, 'reason' => 'weak_report', 'url' => krazeReportEditUrl($pageUrl($path)), 'problems' => $problems];
            return ['ok' => true, 'url' => $pageUrl($path)];
        }
    }
    return ['ok' => false, 'reason' => $unreachable ? 'unreachable' : 'no_report', 'url' => $pageUrl($paths[0])];
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
