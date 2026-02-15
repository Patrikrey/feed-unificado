<?php
header("Content-Type: application/rss+xml; charset=UTF-8");

/*
 FEEDS NECochea
*/
$feeds = [

    // NdeN
    [
        "url" => "https://nden.com.ar/rss/locales",
        "category" => "Locales"
    ],
    [
        "url" => "https://nden.com.ar/rss/politica",
        "category" => "Politica"
    ],
    [
        "url" => "https://nden.com.ar/rss/deportes",
        "category" => "Deportes"
    ],
    [
        "url" => "https://nden.com.ar/rss/policiales",
        "category" => "Policiales"
    ],

    // El Ciudadano (todo como Locales)
    [
        "url" => "https://elciudadanonecochea.com.ar/feed/",
        "category" => "Locales"
    ]

];

$items = [];

foreach ($feeds as $feed) {

    $rss = @simplexml_load_file($feed["url"]);

    if (!$rss || !isset($rss->channel->item[0])) {
        continue;
    }

    $item = $rss->channel->item[0];

    $items[] = [
        "title" => trim((string)$item->title),
        "link" => trim((string)$item->link),
        "description" => trim(strip_tags((string)$item->description)),
        "pubDate" => (string)$item->pubDate,
        "category" => $feed["category"],
        "image" => extract_image($item)
    ];
}

/*
 FUNCIÓN ROBUSTA PARA EXTRAER IMAGEN
*/
function extract_image($item) {

    $namespaces = $item->getNameSpaces(true);

    // 1) media:content
    if (isset($namespaces["media"])) {
        $media = $item->children($namespaces["media"]);
        if (isset($media->content)) {
            $attrs = $media->content->attributes();
            if (isset($attrs->url)) {
                return (string)$attrs->url;
            }
        }
    }

    // 2) enclosure
    if (isset($item->enclosure)) {
        $attrs = $item->enclosure->attributes();
        if (isset($attrs->url)) {
            return (string)$attrs->url;
        }
    }

    // 3) imagen dentro de description
    if (preg_match('/<img[^>]+src=["\']([^"\']+)["\']/i', (string)$item->description, $matches)) {
        return $matches[1];
    }

    return "";
}

/*
 ORDENAR POR FECHA (más nuevo primero)
*/
usort($items, function($a, $b) {
    return strtotime($b["pubDate"]) - strtotime($a["pubDate"]);
});

/*
 SALIDA RSS
*/
echo '<?xml version="1.0" encoding="UTF-8"?>';
?>

<rss version="2.0" xmlns:media="http://search.yahoo.com/mrss/">
<channel>
    <title>Feed Unificado Noticias Necochea</title>
    <link>https://feed-unificado.onrender.com</link>
    <description>Noticias locales de Necochea</description>
    <language>es-AR</language>

<?php foreach ($items as $item): ?>
    <item>
        <title><![CDATA[<?= $item["title"] ?>]]></title>
        <link><?= htmlspecialchars($item["link"], ENT_XML1, 'UTF-8') ?></link>
        <description><![CDATA[<?= $item["description"] ?>]]></description>
        <pubDate><?= $item["pubDate"] ?></pubDate>
        <category><![CDATA[<?= $item["category"] ?>]]></category>

        <?php if (!empty($item["image"])): ?>
            <media:content url="<?= htmlspecialchars($item["image"], ENT_XML1, 'UTF-8') ?>" type="image/jpeg"/>
            <enclosure url="<?= htmlspecialchars($item["image"], ENT_XML1, 'UTF-8') ?>" type="image/jpeg" type="image/jpeg"/>
        <?php endif; ?>

    </item>
<?php endforeach; ?>

</channel>
</rss>
