<?php
// Turns database rows into the portfolio page's HTML (same markup the styles and scripts expect).

function r_card(array $it, string $tag): string
{
    $t = e($it['title']);
    $d = e($it['description']);
    $img = e($it['image']);
    $alt = e(ucwords(strtolower($it['title'])));
    return <<<HTML
                <article class="card">
                    <div class="card-img" data-lb="$img" data-title="$t" data-caption="$d">
                        <div class="card-img-overlay"></div>
                        <img src="$img" alt="$alt" loading="lazy" />
                    </div>
                    <div class="card-hover-line"></div>
                    <div class="card-body">
                        <p class="card-tag">$tag</p>
                        <h3 class="card-title">$t</h3>
                        <p class="card-desc">[ $d ]</p>
                    </div>
                    <div class="card-footer">
                        <button type="button" class="card-link" data-open>VIEW &#9658;</button>
                        <div class="card-dot"></div>
                    </div>
                </article>
HTML;
}

function r_cards(array $items, string $tag): string
{
    return "            <div class=\"cards\">\n" . implode("\n", array_map(fn($i) => r_card($i, $tag), $items)) . "\n            </div>";
}

function r_slideshow(array $items, int $autoplay, bool $thumbs, ?string $tag, bool $hint): string
{
    if (!$items) return '';
    $slides = $tb = [];
    foreach ($items as $n => $it) {
        $full = e($it['image']);
        $th = e($it['thumb'] ?: $it['image']);
        $t = e($it['title']);
        $d = e($it['description']);
        $alt = e(ucwords(strtolower($it['title'])));
        $tg = $tag ? "<p class=\"card-tag\">$tag</p>" : '';
        $active = $n === 0 ? ' active' : '';
        $lazy = $n > 1 ? ' loading="lazy"' : '';
        $slides[] = "                    <div class=\"ss-slide$active\" data-full=\"$full\" data-title=\"$t\" data-caption=\"$d\">\n"
                  . "                        <img src=\"$full\" alt=\"$alt\"$lazy />\n"
                  . "                        <div class=\"ss-caption\">$tg<h3>$t</h3><p>[ $d ]</p></div>\n"
                  . "                    </div>";
        if ($thumbs) {
            $tb[] = "                    <button type=\"button\" class=\"ss-thumb$active\" aria-label=\"Show $alt\"><img src=\"$th\" alt=\"\" loading=\"lazy\" /></button>";
        }
    }
    $count = sprintf('%02d', count($items));
    $play = $autoplay ? '<button type="button" class="ss-play" aria-label="Pause slideshow">&#10074;&#10074;</button>' : '';
    $hintHtml = $hint ? '<span class="ss-hint">CLICK PHOTO TO ENLARGE</span>' : '';
    $thumbsHtml = $thumbs ? "\n                <div class=\"ss-thumbs\">\n" . implode("\n", $tb) . "\n                </div>" : '';
    return "            <div class=\"slideshow\" data-autoplay=\"$autoplay\" aria-roledescription=\"slideshow\">\n"
         . "                <div class=\"ss-stage\" tabindex=\"0\" aria-label=\"Slideshow, use arrow keys\">\n"
         . implode("\n", $slides) . "\n"
         . "                    <button type=\"button\" class=\"ss-btn ss-prev\" aria-label=\"Previous slide\">&#9664;</button>\n"
         . "                    <button type=\"button\" class=\"ss-btn ss-next\" aria-label=\"Next slide\">&#9654;</button>\n"
         . "                </div>\n"
         . "                <div class=\"ss-controls\">\n"
         . "                    $play\n"
         . "                    <span class=\"ss-count\">01 / $count</span>\n"
         . "                    <div class=\"ss-progress-wrap\"><div class=\"ss-progress\"></div></div>\n"
         . "                    $hintHtml\n"
         . "                </div>$thumbsHtml\n"
         . "            </div>";
}

function r_case(string $slug): string
{
    $p = project($slug);
    $meta = implode("\n", array_map(fn($r) => '                        <div><dt>' . e($r[0]) . '</dt><dd>' . e($r[1]) . '</dd></div>', meta_rows($p['meta'])));
    $feat = implode("\n", array_map(fn($f) => '                        <li>' . e($f) . '</li>', lines($p['features'])));
    $chips = implode('', array_map(fn($c) => '<span class="chip">' . e(strtoupper(trim($c))) . '</span>', array_filter(explode(',', (string) $p['tech']), 'trim')));
    $logo = $p['logo'] ? '<img class="case-logo' . ($p['logo_light'] ? ' light' : '') . '" src="' . e($p['logo']) . '" alt="' . e($p['title']) . '" />' : '<h3 class="card-title">' . e($p['title']) . '</h3>';
    $btns = '';
    if ($p['code_path']) $btns .= code_button($p['code_path']);
    $actions = $btns ? "<div class=\"case-actions\">$btns</div>" : '';
    $desc = e($p['description']);
    $metaHtml = $meta ? "                    <dl class=\"case-meta\">\n$meta\n                    </dl>\n" : '';
    $featHtml = $feat ? "                    <ul class=\"case-features\">\n$feat\n                    </ul>\n" : '';
    $story = implode("\n", array_map(fn($r) => '                        <div class="story-step"><h4>' . e($r[0]) . '</h4><p>' . e($r[1]) . '</p></div>', meta_rows($p['case_study'] ?? '')));
    $storyHtml = $story ? "                    <div class=\"case-story\">\n$story\n                    </div>\n" : '';
    return "            <article class=\"case\">\n"
         . "                <div class=\"case-info\">\n"
         . "                    $logo\n"
         . "                    <p class=\"case-desc\">[ $desc ]</p>\n"
         . $metaHtml . $storyHtml . $featHtml
         . "                    <div class=\"chips\">$chips</div>\n"
         . "                    $actions\n"
         . "                </div>\n"
         . r_slideshow(items($slug), 4500, false, null, false) . "\n"
         . "            </article>";
}

function r_phone(array $it): string
{
    $src = e($it['image']);
    $t = e($it['title']);
    $c = e($it['description']);
    $alt = e(ucwords(strtolower($it['title'])));
    return "                    <figure class=\"car-item\" data-lb=\"$src\" data-title=\"$t\" data-caption=\"$c\">\n"
         . "                        <img src=\"$src\" alt=\"$alt screen\" loading=\"lazy\" />\n"
         . "                        <figcaption>$t</figcaption>\n"
         . "                    </figure>";
}

function r_figma(): string
{
    $out = '';
    $login = items('login_app');
    if ($login) {
        $p = project('login_app');
        $out .= "            <div class=\"ui-block no-nav\" data-gallery>\n"
              . "                <div class=\"ui-head\">\n"
              . '                    <h3>' . e($p['title']) . "</h3>\n"
              . '                    <p>[ ' . e($p['description']) . " ]</p>\n"
              . "                </div>\n"
              . "                <div class=\"login-strip\">\n" . implode("\n", array_map('r_phone', $login)) . "\n                </div>\n"
              . "            </div>\n";
    }
    $mm = items('micromml');
    if ($mm) {
        $p = project('micromml');
        $out .= "            <div class=\"ui-block\" data-gallery>\n"
              . "                <div class=\"ui-head\">\n"
              . '                    <h3>' . e($p['title']) . "</h3>\n"
              . '                    <p>[ ' . e($p['description']) . " ]</p>\n"
              . "                </div>\n"
              . "                <div class=\"carousel\">\n"
              . "                    <div class=\"car-nav\">\n"
              . "                        <button type=\"button\" class=\"car-prev\" aria-label=\"Scroll screens left\">&#9664;</button>\n"
              . "                        <button type=\"button\" class=\"car-next\" aria-label=\"Scroll screens right\">&#9654;</button>\n"
              . "                    </div>\n"
              . "                    <div class=\"car-track\">\n" . implode("\n", array_map('r_phone', $mm)) . "\n                    </div>\n"
              . "                </div>\n"
              . "            </div>";
    }
    return $out;
}

function r_certs(): string
{
    $groups = [];
    foreach (['certs_sql' => 'sql', 'certs_python' => 'py'] as $list => $id) {
        $all = items($list);
        if ($all) $groups[] = [$list, $id, $all];
    }
    if (!$groups) return '';
    $tabs = $panels = '';
    foreach ($groups as $n => [$list, $id, $all]) {
        $p = project($list);
        $sel = $n === 0;
        $tabs .= "                <button type=\"button\" class=\"cert-tab\" role=\"tab\" id=\"tab-$id\" aria-controls=\"panel-$id\" aria-selected=\"" . ($sel ? 'true' : 'false') . '"' . ($sel ? '' : ' tabindex="-1"') . '>'
               . e($p['title']) . '<span class="n">' . count($all) . "</span></button>\n";
        $feature = null;
        $rest = [];
        foreach ($all as $it) {
            if ($it['is_featured'] && !$feature) $feature = $it; else $rest[] = $it;
        }
        $featureHtml = '';
        if ($feature) {
            $img = e($feature['image']);
            $t = e($feature['title']);
            $d = e($feature['description']);
            $featureHtml = "                    <article class=\"cert-feature\">\n"
                . "                        <div class=\"card-img\" data-lb=\"$img\" data-title=\"$t\" data-caption=\"$d\">\n"
                . "                            <div class=\"card-img-overlay\"></div>\n"
                . "                            <img src=\"$img\" alt=\"$t certificate\" loading=\"lazy\" />\n"
                . "                        </div>\n"
                . "                        <div class=\"card-body\">\n"
                . "                            <p class=\"card-tag\">LEARNING PATH</p>\n"
                . "                            <h3 class=\"card-title\">$t</h3>\n"
                . "                            <p class=\"card-desc\">[ $d ]</p>\n"
                . "                        </div>\n"
                . "                    </article>\n";
        }
        $panels .= "            <div class=\"cert-panel\" id=\"panel-$id\" role=\"tabpanel\" aria-labelledby=\"tab-$id\"" . ($sel ? '' : ' hidden') . " data-gallery>\n"
                 . "                <div class=\"cert-path\">\n" . $featureHtml
                 . "                    <div class=\"cert-list\">\n" . implode("\n", array_map(fn($i) => r_card($i, 'CODESIGNAL'), $rest)) . "\n                    </div>\n"
                 . "                </div>\n"
                 . "            </div>\n";
    }
    return "            <div class=\"cert-tabs\" role=\"tablist\" aria-label=\"Certificate paths\" data-tabs>\n$tabs            </div>\n$panels";
}

function r_video(): string
{
    $v = items('video')[0] ?? null;
    if (!$v || !$v['media']) return '';
    return "            <div class=\"video-wrap\">\n"
         . '                <video controls preload="metadata" poster="' . e($v['image']) . "\">\n"
         . '                    <source src="' . e($v['media']) . "\" type=\"video/mp4\" />\n"
         . "                </video>\n"
         . "                <div class=\"video-meta\">\n"
         . '                    <p class="video-title">[ ' . e($v['title']) . " ]</p>\n"
         . '                    <p class="video-tag">' . e($v['description'] ?: 'FEATURED') . "</p>\n"
         . "                </div>\n"
         . "            </div>";
}

// One whole section
function r_section(array $s, int $num): string
{
    $key = $s['section_key'];
    switch ($s['kind']) {
        case 'slideshow': $body = r_slideshow(items($key), 5000, true, LISTS[$key]['tag'] ?? null, true); $gallery = false; break;
        case 'case':      $body = r_case($key); $gallery = false; break;
        case 'figma':     $body = r_figma(); $gallery = false; break;
        case 'certs':     $body = r_certs(); $gallery = false; break;
        case 'video':     $body = r_video(); $gallery = false; break;
        default:          $items = items($key); $body = $items ? r_cards($items, LISTS[$key]['tag'] ?? strtoupper($key)) : ''; $gallery = true;
    }
    if (trim($body) === '') return '';
    $cls = $s['kind'] === 'video' ? 'video-section' : 'cat-section';
    $intro = $s['intro'] ? "\n            <p class=\"section-intro\">[ " . e($s['intro']) . ' ]</p>' : '';
    return "\n        <!-- " . e($s['title']) . " -->\n"
         . "        <section class=\"$cls\" id=\"sec-" . e($key) . '"' . ($gallery ? ' data-gallery' : '') . ">\n"
         . "            <div class=\"section-header\">\n"
         . '                <div class="section-num">' . sprintf('%02d', $num) . "</div>\n"
         . '                <h2 class="section-title">' . e($s['title']) . "</h2>\n"
         . "            </div>\n"
         . "            <div class=\"section-line\"></div>$intro\n"
         . "$body\n"
         . "        </section>";
}
