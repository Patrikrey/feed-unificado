<?php
header("Content-Type: application/rss+xml; charset=UTF-8");

/*
  FEEDS UNIFICADOS - SELECCIÓN POR CATEGORÍA (VERSIÓN 2026)
  Optimizado para Make: Incluye GUID y Timeouts.
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

// Configuración de contexto para evitar bloqueos y timeouts
$context = stream_context_create([
    "http" => [
        "timeout" => 3, // 3 segundos máximo por cada diario
        "user_agent" => "Mozilla/5.0 (Windows NT 10.0; Win64; x64) Chrome/120.0.0.0"
    ]
]);

foreach ($feeds as $feed) {
    $content = @file_get_contents($feed["url"], false, $context);
    if (!$content) continue;

    $rss = @simplexml_load_string($content);
    if (!$rss || !isset($rss->channel->item)) continue;

    foreach ($rss->channel->item as $item) {
        $pubDate = (string)$item->pubDate;
        $timestamp = strtotime($pubDate) ?: time();
        $link = trim((string)$item->link);

        $all_items[] = [
            "title" => trim((string)$item->title),
            "link" => $link,
            "guid" => md5($link . $feed["category"]), // ID único para Make
            "description" => trim(strip_tags((string)$item->description)),
            "pubDate" => $pubDate,
            "timestamp" => $timestamp,
            "category" => $feed["category"],
            "image" => extract_image($item)
        ];
    }
}

// 1. Ordenar: Lo más nuevo primero
usort($all_items, function($a, $b) {
    return $b["timestamp"] <=> $a["timestamp"];
});

// 2. Filtrar: Una por categoría para variedad
$filtered_items = [];
$used_categories = [];

foreach ($all_items as $item) {
    if (!in_array($item["category"], $used_categories)) {
        $filtered_items[] = $item;
        $used_categories[] = $item["category"];
    }
}

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
    <link>https://avanzanecochea.digital</link>
    <description>Noticias locales filtradas por categoría</description>

    <?php foreach ($filtered_items as $item): ?>
    <item>
        <title><![CDATA[<?= $item["title"] ?>]]></title>
        <link><?= $item["link"] ?></link>
        <guid isPermaLink="false"><?= $item["guid"] ?></guid>
        <description><![CDATA[<?= $item["description"] ?>]]></description>
        <pubDate><?= $item["pubDate"] ?></pubDate>
        <category><![CDATA[<?= $item["category"] ?>]]></category>
        <?php if (!empty($item["image"])): ?>
        <media:content url="<?= $item["image"] ?>" type="image/jpeg"/>
        <enclosure url="<?= $item["image"] ?>" type="image/jpeg" length="0"/>
        <?php endif; ?>
    </item>
    <?php endforeach; ?>
</channel>
</rss>
