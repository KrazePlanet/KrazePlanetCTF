<?php
// config/labs.php - The lab list (data/labs.json) indexed for lookups.
// A lab's id is its "access" path, e.g. "/subdomains/tutorialrepublic" - the
// same id the central server stores when a student marks it solved.

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
