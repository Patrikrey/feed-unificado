<?php
header("Content-Type: application/rss+xml; charset=UTF-8");

/*
 SOLO FEEDS QUE FUNCIONAN BIEN
*/
$feeds = [
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
        "title" => (string)$item->title,
        "link" => (string)$item->link,
        "description" => strip_tags((string)$item->description),
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

    // media:content
    if (isset($namespaces["media"])) {
        $media = $item->children($namespaces["media"]);
        if (isset($media->content)) {
            return (string)$media->content->attributes()->url;
        }
    }

    // enclosure
    if (isset($item->enclosure)) {
        return (string)$item->enclosure->attributes()->url;
    }

    // img en description
    if (preg_match('/<img.*?src=["\'](.*?)["\']/i', (string)$item->description, $matches)) {
        return $matches[1];
    }

    return "";
}

/*
 SALIDA RSS
*/
echo '<?xml version="1.0" encoding="UTF-8"?>';
?>
<rss version="2.0" xmlns:media="http://search.yahoo.com/mrss/">
<channel>
    <title>Feed Unificado Noticias Necochea</title>
    <link>https://feed-unificado.onrender.com</link>
    <description>Noticias locales por categoría</description>

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
