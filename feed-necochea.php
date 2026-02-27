<?php
header("Content-Type: application/rss+xml; charset=UTF-8");

/*
  FEED UNIFICADO - NECOCHEA v3
  
  NOVEDADES:
  - Extrae contenido completo del artículo para Groq
  - Selectores probados: nden/2262/necocheadigital (wysiwyg), lacapital (nota_content), tsnnecochea (article)
  - Texto truncado a 3500 caracteres
  - Incluido en <content:encoded> para Make
  - Imagen extraída antes del strip_tags
  - 4 niveles de fallback para imagen
*/

$feeds = [
    ["url" => "https://www.presentenoticias.com/rss/la-region/",                                              "category" => "Regionales"],
    ["url" => "https://www.lacapitalmdp.com/categoria/la-zona/feed/",                                        "category" => "Regionales"],
    ["url" => "https://www.lanoticia.ar/categoria/provinciales/feed/",                                       "category" => "Regionales"],
    ["url" => "https://www.lacapitalmdp.com/feed/",                                                          "category" => "Mar del Plata"],
    ["url" => "https://nden.com.ar/rss/locales",                                                             "category" => "Locales"],
    ["url" => "https://nden.com.ar/rss/politica",                                                            "category" => "Politica"],
    // ["url" => "https://www.lanacion.com.ar/arc/outboundfeeds/rss/category/deportes/?outputType=xml",      "category" => "Deportes"],
    ["url" => "https://nden.com.ar/rss/policiales",                                                          "category" => "Policiales"],
    ["url" => "https://ecosdiariosapiv3.eleco.com.ar/feed-notes",                                            "category" => "Locales"],
    ["url" => "https://diarionecochea.com/feed/",                                                            "category" => "Locales"],
    ["url" => "https://tsnnecochea.com.ar/feed/",                                                            "category" => "Locales"],
    ["url" => "https://necocheadigital.com/feed/",                                                           "category" => "Locales"],
    ["url" => "https://2262.com.ar/feed/",                                                                   "category" => "Locales"],
    ["url" => "https://necocheanews.com.ar/feed/",                                                           "category" => "Locales"],
    ["url" => "https://lavozdenecochea.com.ar/feed/",                                                        "category" => "Locales"],
    ["url" => "https://alertanecochea.com.ar/feed/",                                                         "category" => "Policiales"],
    ["url" => "https://necocheahoy.com/feed/",                                                               "category" => "Locales"],
    ["url" => "https://quequenlibre.com.ar/feed/",                                                           "category" => "Quequén"],
    ["url" => "https://primicias2262.com/feed/",                                                             "category" => "Locales"],
    ["url" => "https://www.cuatromedios.com.ar/feed/",                                                       "category" => "Regionales"],
];

define('MAX_CONTENT_CHARS', 3500);

$all_items = [];

$context = stream_context_create([
    "http" => [
        "timeout"    => 7,
        "user_agent" => "Mozilla/5.0 (Windows NT 10.0; Win64; x64) Chrome/122.0.0.0"
    ]
]);

foreach ($feeds as $feed) {
    $content = @file_get_contents($feed["url"], false, $context);
    if (!$content) continue;

    $rss = @simplexml_load_string($content);
    if (!$rss || !isset($rss->channel->item)) continue;

    foreach ($rss->channel->item as $item) {
        $pubDate   = (string)$item->pubDate;
        $timestamp = strtotime($pubDate) ?: time();
        $link      = trim((string)$item->link);
        $raw_desc  = (string)$item->description;

        $namespaces   = $item->getNameSpaces(true);
        $full_content = "";

        // Primero: content:encoded del feed (algunos WordPress lo incluyen)
        if (isset($namespaces["content"])) {
            $content_ns   = $item->children($namespaces["content"]);
            $full_content = trim(strip_tags((string)$content_ns->encoded));
        }

        // Segundo: scraping del artículo
        if (empty($full_content) || strlen($full_content) < 200) {
            $full_content = fetch_article_content($link, $context);
        }

        // Tercero: descripción del feed como fallback
        if (empty($full_content)) {
            $full_content = trim(strip_tags($raw_desc));
        }

        $full_content = mb_substr($full_content, 0, MAX_CONTENT_CHARS);

        $all_items[] = [
            "title"       => trim((string)$item->title),
            "link"        => $link,
            "guid"        => md5($link . $feed["category"]),
            "description" => trim(strip_tags($raw_desc)),
            "content"     => $full_content,
            "pubDate"     => $pubDate,
            "timestamp"   => $timestamp,
            "category"    => $feed["category"],
            "image"       => extract_image($item, $raw_desc, $link, $context)
        ];
    }
}

usort($all_items, function($a, $b) { return $b["timestamp"] <=> $a["timestamp"]; });

$filtered_items = [];
$used_domains   = [];
foreach ($all_items as $item) {
    if (count($filtered_items) >= 4) break;
    $domain = parse_url($item["link"], PHP_URL_HOST);
    if (!in_array($domain, $used_domains)) {
        $filtered_items[] = $item;
        $used_domains[]   = $domain;
    }
}

// ─────────────────────────────────────────────────────────────
// EXTRACCIÓN DE CONTENIDO
// ─────────────────────────────────────────────────────────────
function fetch_article_content($url, $context) {
    $blocked_domains = ["lanacion.com.ar", "clarin.com", "infobae.com", "diarionecochea.com"];
    foreach ($blocked_domains as $domain) {
        if (strpos($url, $domain) !== false) return "";
    }

    $html = @file_get_contents($url, false, $context);
    if (!$html) return "";

    $selectors = [
        // nden.com.ar / 2262.com.ar / necocheadigital.com (CMS Vork)
        "wysiwyg"        => '/<div[^>]+class="[^"]*wysiwyg[^"]*"[^>]*>(.*?)<\/div>\s*(?:<div|<section|$)/si',
        // lacapitalmdp.com
        "nota_content"   => '/<div[^>]+class="[^"]*nota_content[^"]*"[^>]*>(.*?)<\/div>\s*(?:<div|<footer|<aside)/si',
        // WordPress estándar
        "entry-content"  => '/<div[^>]+class="[^"]*entry-content[^"]*"[^>]*>(.*?)<\/div>\s*(?:<div|<footer|<aside)/si',
        "post-content"   => '/<div[^>]+class="[^"]*post-content[^"]*"[^>]*>(.*?)<\/div>\s*(?:<div|<footer|<aside)/si',
        // Otros
        "article-content"=> '/<div[^>]+class="[^"]*article-content[^"]*"[^>]*>(.*?)<\/div>\s*(?:<div|<footer)/si',
        "content-body"   => '/<div[^>]+class="[^"]*content-body[^"]*"[^>]*>(.*?)<\/div>\s*(?:<div|<footer)/si',
        // Fallback genérico
        "article"        => '/<article[^>]*>(.*?)<\/article>/si',
    ];

    foreach ($selectors as $pattern) {
        if (preg_match($pattern, $html, $matches)) {
            $text = clean_html($matches[1]);
            if (strlen($text) > 150) return $text;
        }
    }

    return "";
}

function clean_html($html) {
    $html = preg_replace('/<script\b[^>]*>.*?<\/script>/si', '', $html);
    $html = preg_replace('/<style\b[^>]*>.*?<\/style>/si',   '', $html);
    $html = preg_replace('/<!--.*?-->/s',                     '', $html);
    $html = preg_replace('/<iframe[^>]*>.*?<\/iframe>/si',   '', $html);
    $html = preg_replace('/<figcaption[^>]*>.*?<\/figcaption>/si', '', $html);
    $html = preg_replace('/<\/p>/i',      ' ', $html);
    $html = preg_replace('/<br\s*\/?>/i', ' ', $html);
    $text = strip_tags($html);
    $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    $text = preg_replace('/\s+/', ' ', $text);
    return trim($text);
}

// ─────────────────────────────────────────────
// EXTRACCIÓN DE IMAGEN — 4 niveles de fallback
// ─────────────────────────────────────────────
function extract_image($item, $raw_desc, $article_url, $context) {
    $namespaces = $item->getNameSpaces(true);
    $img_url    = "";

    // PASO 1 — media:content
    if (isset($namespaces["media"])) {
        $media = $item->children($namespaces["media"]);
        if (isset($media->content)) {
            foreach ($media->content as $c) {
                $attrs = $c->attributes();
                if (isset($attrs["url"])) { $img_url = (string)$attrs["url"]; break; }
            }
        }
        if (empty($img_url) && isset($media->thumbnail)) {
            $attrs = $media->thumbnail->attributes();
            if (isset($attrs["url"])) $img_url = (string)$attrs["url"];
        }
    }

    // PASO 2 — enclosure
    if (empty($img_url) && isset($item->enclosure)) {
        $attrs = $item->enclosure->attributes();
        if (isset($attrs["url"])) $img_url = (string)$attrs["url"];
    }

    // PASO 3 — <img> en HTML crudo de descripción
    if (empty($img_url) && !empty($raw_desc)) {
        if (preg_match('/<img[^>]+src=["\']([^"\']+)["\'][^>]*>/i', $raw_desc, $m)) {
            $img_url = $m[1];
        }
    }

    // PASO 4 — og:image del artículo (últimos 20KB)
    if (empty($img_url) && !empty($article_url) && filter_var($article_url, FILTER_VALIDATE_URL)) {
        $html = @file_get_contents($article_url, false, $context);
        if ($html) {
            $html = substr($html, 0, 20000);
            if (preg_match('/meta[^>]*?(?:property=["\']og:image["\'][^>]*?content=["\']([^"\']+)["\']|content=["\']([^"\']+)["\'][^>]*?property=["\']og:image["\'])/i', $html, $m)) {
                $img_url = !empty($m[1]) ? $m[1] : $m[2];
            }
        }
    }

    return html_entity_decode(trim($img_url));
}

echo '<?xml version="1.0" encoding="UTF-8"?>';
?>
<rss version="2.0"
     xmlns:media="http://search.yahoo.com/mrss/"
     xmlns:content="http://purl.org/rss/1.0/modules/content/">
<channel>
    <title>Variedad Necochea y Regional</title>
    <link>https://avanzanecochea.digital</link>
    <?php foreach ($filtered_items as $item): ?>
    <item>
        <title><![CDATA[<?= $item["title"] ?>]]></title>
        <link><?= htmlspecialchars($item["link"]) ?></link>
        <guid isPermaLink="false"><?= $item["guid"] ?></guid>
        <description><![CDATA[<?= $item["description"] ?>]]></description>
        <content:encoded><![CDATA[<?= $item["content"] ?>]]></content:encoded>
        <pubDate><?= $item["pubDate"] ?></pubDate>
        <category><![CDATA[<?= $item["category"] ?>]]></category>
        <?php if (!empty($item["image"])): ?>
        <media:content url="<?= htmlspecialchars($item["image"]) ?>" type="image/jpeg"/>
        <enclosure url="<?= htmlspecialchars($item["image"]) ?>" type="image/jpeg" length="0"/>
        <?php endif; ?>
    </item>
    <?php endforeach; ?>
</channel>
</rss>
