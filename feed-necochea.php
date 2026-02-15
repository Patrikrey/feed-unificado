<?php
header("Content-Type: application/rss+xml; charset=UTF-8");

$feeds = [

    ["url" => "https://nden.com.ar/rss/locales", "category" => "Locales"],
    ["url" => "https://nden.com.ar/rss/politica", "category" => "Politica"],
    ["url" => "https://nden.com.ar/rss/deportes", "category" => "Deportes"],
    ["url" => "https://nden.com.ar/rss/policiales", "category" => "Policiales"],
    ["url" => "https://ecosdiariosapiv3.eleco.com.ar/feed-notes", "category" => "Locales"],
    ["url" => "https://necocheanews.com.ar/feed/", "category" => "Locales"]

];

$items = [];

foreach ($feeds as $feed) {

    $rss = @simplexml_load_file($feed["url"]);
    if (!$rss || !isset($rss->channel->item)) {
        continue;
    }

    foreach ($rss->channel->item as $item) {

        $items[] = [
            "title" => trim((string)$item->title),
            "link" => trim((string)$item->link),
            "description" => trim(strip_tags((string)$item->description)),
            "pubDate" => (string)$item->pubDate,
            "timestamp" => strtotime((string)$item->pubDate),
            "category" => $feed["category"],
            "image" => extract_image($item)
        ];
    }
}

function extract_image($item) {

    $namespaces = $item->getNameSpaces(true);

    if (isset($namespaces["media"])) {
        $media = $item->children($namespaces["media"]);
        if (isset($media->content)) {
            return (string)$media->content->attributes()->url;
        }
    }

    if (isset($item->enclosure)) {
        return (string)$item->enclosure->attributes()->url;
    }

    if (preg_match('/<img.*?src=["\'](.*?)["\']/i', (string)$item->description, $matches)) {
        return $matches[1];
    }

    return "";
}

// Ordenar por fecha descendente
usort($items, function($a, $b) {
    return $b["timestamp"] <=> $a["timestamp"];
});

// Limitar a 10 noticias totales
$items = array_slice($items, 0, 10);

echo '<?xml version="1.0" encoding="UTF-8"?>';
?>

<rss version="2.0" xmlns:media="http://search.yahoo.com/mrss/">
<channel>
<title>Feed Unificado Noticias Necochea</title>
<link>https://feed-unificado.onrender.com</link>
<description>Noticias locales de Necochea</description>

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
