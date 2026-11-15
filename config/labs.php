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

// Does the student's KrazePlanet-Reports repository have a README yet?
// true / false, or null if GitHub couldn't be reached. HEAD = the default branch;
// an empty repository (no commits) has none, so that's false too.
function krazeRepoHasReadme($username) {
    $ch = curl_init('https://raw.githubusercontent.com/' . $username . '/' . KRAZE_REPORTS_REPO . '/HEAD/README.md?kp=' . bin2hex(random_bytes(4)));
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true, CURLOPT_FOLLOWLOCATION => false, CURLOPT_RANGE => '0-63',
        CURLOPT_CONNECTTIMEOUT => 4, CURLOPT_TIMEOUT => 6, CURLOPT_USERAGENT => 'KrazePlanet-readme-check',
    ]);
    curl_exec($ch);
    $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    if ($status === 200 || $status === 206) return true;
    if ($status === 404) return false;
    return null;
}

// GitHub's "new file" page with README.md already named and written, so adding it
// is one click on "Commit changes". Works on an empty repository too.
// The README: a screenshot of the labs (hosted on this site), live badges for the
// student's points / labs solved / rank (api/badge.php via shields.io), and links
// back to their profile and the scoreboard.
function krazeReadmeUrl($githubUser, $kpUser) {
    $site = rtrim(krazeSiteUrl('/'), '/');
    $hub = 'https://labs.krazeplanet.com/';
    $profile = $site . '/navbar/profile.php?user=' . rawurlencode($kpUser);
    $board = $site . '/navbar/scoreboard.php?period=all';
    $badge = fn($type, $alt) => '<img src="https://img.shields.io/endpoint?style=for-the-badge&url='
        . rawurlencode($site . '/api/badge.php?user=' . rawurlencode($kpUser) . '&type=' . $type) . '" alt="' . $alt . '">';

    $text = <<<MD
<p align="center">
  <a href="{$site}/"><img src="{$site}/assets/github/krazeplanet-labs.png" alt="KrazePlanet Labs: free, hands-on web security labs" width="100%"></a>
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

Every `.md` file in this repository is one lab I solved. Each report covers:

- **The bug**: what the vulnerability is
- **How I found it**: the steps to reproduce it
- **Why it matters**: what an attacker could do with it

## Try it yourself

KrazePlanet Labs are free and need nothing installed. [Start with an Easy lab]({$site}/) and see how many you can solve.

MD;
    return 'https://github.com/' . $githubUser . '/' . KRAZE_REPORTS_REPO . '/new/main?filename=README.md&value=' . rawurlencode($text);
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
