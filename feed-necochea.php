<?php
header("Content-Type: application/rss+xml; charset=UTF-8");

/*
  FEEDS UNIFICADOS - SELECCIÓN POR CATEGORÍA (MÁXIMA VARIEDAD)
  Actualización: Febrero 2026
*/
$feeds = [
    ["url" => "https://nden.com.ar/rss/locales", "category" => "Locales"],
    ["url" => "https://nden.com.ar/rss/politica", "category" => "Politica"],
    ["url" => "https://nden.com.ar/rss/deportes", "category" => "Deportes"],
    ["url" => "https://nden.com.ar/rss/policiales", "category" => "Policiales"],
    ["url" => "https://ecosdiariosapiv3.eleco.com.ar/feed-notes", "category" => "Locales"],
    ["url" => "https://diarionecochea.com/feed/", "category" => "Locales"],
    ["url" => "https://tsnnecochea.com.ar/feed/", "category" => "Locales"],
    ["url" => "https://necocheadigital.com/feed/", "category" => "Locales"],
    ["url" => "https://2262.com.ar/feed/", "category" => "Locales"],
    ["url" => "https://necocheanews.com.ar/feed/", "category" => "Locales"],
    ["url" => "https://lavozdenecochea.com.ar/feed/", "category" => "Locales"],
    ["url" => "https://alertanecochea.com.ar/feed/", "category" => "Policiales"],
    ["url" => "https://necocheahoy.com/feed/", "category" => "Locales"],
    ["url" => "https://quequenlibre.com.ar/feed/", "category" => "Quequén"],
    ["url" => "https://primicias2262.com/feed/", "category" => "Locales"],
    ["url" => "https://www.cuatromedios.com.ar/feed/", "category" => "Regionales"],
    ["url" => "https://necochea.gov.ar/feed/", "category" => "Oficial"]
];

$all_items = [];

foreach ($feeds as $feed) {
    $rss = @simplexml_load_file($feed["url"]);
    if (!$rss || !isset($rss->channel->item)) continue;

    foreach ($rss->channel->item as $item) {
        $pubDate = (string)$item->pubDate;
        $timestamp = strtotime($pubDate) ?: time();

        $all_items[] = [
            "title" => trim((string)$item->title),
            "link" => trim((string)$item->link),
            "description" => trim(strip_tags((string)$item->description)),
            "pubDate" => $pubDate,
            "timestamp" => $timestamp,
            "category" => $feed["category"],
            "image" => extract_image($item)
        ];
    }
}

// 1. Ordenar todo por fecha (más reciente primero)
usort($all_items, function($a, $b) {
    return $b["timestamp"] <=> $a["timestamp"];
});

// 2. LÓGICA DE FILTRADO: UNA POR CATEGORÍA
$filtered_items = [];
$used_categories = [];

foreach ($all_items as $item) {
    // Si esta categoría aún no está en nuestra lista de salida, la agregamos
    if (!in_array($item["category"], $used_categories)) {
        $filtered_items[] = $item;
        $used_categories[] = $item["category"];
    }
}

// El resultado final en $filtered_items tendrá máximo una noticia de cada categoría
$items = $filtered_items;

function extract_image($item) {
    $namespaces = $item->getNameSpaces(true);
    if (isset($namespaces["media"])) {
        $media = $item->children($namespaces["media"]);
        if (isset($media->content)) {
            $attrs = $media->content->attributes();
            if (isset($attrs["url"])) return (string)$attrs["url"];
        }
    }
    if (isset($item->enclosure)) {
        $attrs = $item->enclosure->attributes();
        if (isset($attrs["url"])) return (string)$attrs["url"];
    }
    if (preg_match('/<img.*?src=["\'](.*?)["\']/i', (string)$item->description, $matches)) {
        return $matches[1];
    }
    return "";
}

echo '<?xml version="1.0" encoding="UTF-8"?>';
?>
<rss version="2.0" xmlns:media="http://search.yahoo.com/mrss/">
<channel>
<title>Variedad Necochea Total</title>
<link>https://tu-dominio.com</link>
<description>Una noticia reciente por cada categoría local</description>

<?php foreach ($items as $item): ?>
<item>
    <title><![CDATA[<?= $item["title"] ?>]]></title>
    <link><?= $item["link"] ?></link>
    <description><![CDATA[<?= $item["description"] ?>]]></description>
    <pubDate><?= $item["pubDate"] ?></pubDate>
    <category><![CDATA[<?= $item["category"] ?>]]></category>
    <?php if (!empty($item["image"])): ?>
    <media:content url="<?= $item["image"] ?>" type="image/jpeg"/>
    <enclosure url="<?= $item["image"] ?>" type="image/jpeg"/>
    <?php endif; ?>
</item>
<?php endforeach; ?>
</channel>
</rss>
