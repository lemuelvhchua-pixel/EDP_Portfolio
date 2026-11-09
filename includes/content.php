<?php
// Portfolio content from the database: sections, items and project write-ups.

// Every list of items, which section it shows in, and its card tag.
const LISTS = [
    'photography'  => ['label' => 'Photography',            'section' => 'photography', 'tag' => 'PHOTOGRAPHY'],
    'likhub'       => ['label' => 'LikHub screenshots',     'section' => 'likhub',      'tag' => 'WEB'],
    'kabakal'      => ['label' => 'Kabakal screenshots',    'section' => 'kabakal',     'tag' => 'WEB'],
    'portfolio_site' => ['label' => 'This site screenshots', 'section' => 'portfolio_site', 'tag' => 'WEB'],
    'login_app'    => ['label' => 'Figma: Login App',       'section' => 'ui',          'tag' => 'FIGMA'],
    'micromml'     => ['label' => 'Figma: MicroMML',        'section' => 'ui',          'tag' => 'FIGMA'],
    'graphics'     => ['label' => 'Graphic Design',         'section' => 'graphics',    'tag' => 'DESIGN'],
    'art'          => ['label' => 'Art',                    'section' => 'art',         'tag' => 'ART'],
    'networking'   => ['label' => 'Networking',             'section' => 'networking',  'tag' => 'NETWORKING'],
    'certs_sql'    => ['label' => 'Certificates: SQL',      'section' => 'certs',       'tag' => 'CODESIGNAL'],
    'certs_python' => ['label' => 'Certificates: Python',   'section' => 'certs',       'tag' => 'CODESIGNAL'],
    'video'        => ['label' => 'Featured video',         'section' => 'video',       'tag' => 'FEATURED'],
];

// Write-ups that can be edited (web projects, Figma projects, certificate paths)
const PROJECT_LABELS = [
    'likhub'       => 'LikHub (web project)',
    'kabakal'      => 'Kabakal (web project)',
    'portfolio_site' => 'This site (web project)',
    'login_app'    => 'Login App (Figma)',
    'micromml'     => 'MicroMML (Figma)',
    'certs_sql'    => 'SQL certificate path',
    'certs_python' => 'Python certificate path',
];

function sections(bool $onlyVisible = true): array
{
    $sql = 'SELECT * FROM sections' . ($onlyVisible ? ' WHERE is_visible = 1' : '') . ' ORDER BY sort_order, section_key';
    return db()->query($sql)->fetchAll();
}

function items(string $listKey, bool $onlyVisible = true): array
{
    $st = db()->prepare('SELECT * FROM items WHERE list_key = ?' . ($onlyVisible ? ' AND is_visible = 1' : '') . ' ORDER BY sort_order, id');
    $st->execute([$listKey]);
    return $st->fetchAll();
}

function project(string $slug): array
{
    static $cache = [];
    if (!isset($cache[$slug])) {
        $st = db()->prepare('SELECT * FROM projects WHERE slug = ?');
        $st->execute([$slug]);
        $cache[$slug] = $st->fetch() ?: ['slug' => $slug, 'title' => strtoupper($slug), 'description' => '', 'logo' => null,
            'logo_light' => 0, 'meta' => '', 'features' => '', 'tech' => '', 'site_url' => null, 'code_path' => null, 'case_study' => ''];
    }
    return $cache[$slug];
}

// "LABEL: value" lines -> [[label, value], ...]
function meta_rows(?string $text): array
{
    $rows = [];
    foreach (preg_split('/\R/', (string) $text) as $line) {
        if (trim($line) === '') continue;
        [$k, $v] = array_pad(explode(':', $line, 2), 2, '');
        $rows[] = [trim($k), trim($v)];
    }
    return $rows;
}

function lines(?string $text): array
{
    return array_values(array_filter(array_map('trim', preg_split('/\R/', (string) $text)), 'strlen'));
}

// Numbers for the home page
function portfolio_stats(): array
{
    $c = fn(string $where) => (int) db()->query("SELECT COUNT(*) FROM items WHERE is_visible = 1 AND $where")->fetchColumn();
    return [
        'certs'    => $c("list_key IN ('certs_sql','certs_python')"),
        'photos'   => $c("list_key = 'photography'"),
        'websites' => 3, // LikHub, Kabakal and this site
        'apps'     => 2, // Figma: Login App and MicroMML
    ];
}

/**
 * Pulls the 11-character YouTube video ID out of a link (watch, youtu.be, embed, shorts)
 * or a bare ID. Returns null when the value isn't a YouTube video.
 */
function youtube_id(?string $v): ?string
{
    $v = trim((string) $v);
    if ($v === '') return null;
    if (preg_match('~^[A-Za-z0-9_-]{11}$~', $v)) return $v;
    if (preg_match('~(?:youtube(?:-nocookie)?\.com/(?:watch\?(?:.*&)?v=|embed/|shorts/|live/|v/)|youtu\.be/)([A-Za-z0-9_-]{11})~i', $v, $m)) return $m[1];
    return null;
}
