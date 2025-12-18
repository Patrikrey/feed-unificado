<?php
header("Content-Type: application/rss+xml; charset=UTF-8");

/*
 FEEDS + CATEGORÍA FIJA
*/
$feeds = [
    [
        "url" => "https://www.mdzol.com/rss/pages/ultimas-noticias-mendoza.xml",
        "category" => "Cuyo"
    ],
    [
        "url" => "https://www.mdzol.com/rss/pages/ultimas-noticias-argentina.xml",
        "category" => "Nacional"
    ],
    [
        "url" => "https://www.mdzol.com/rss/pages/politica.xml",
        "category" => "Politica"
    ],
    [
        "url" => "https://www.mdzol.com/rss/pages/mdz-show.xml",
        "category" => "Entretenimiento"
    ],
    [
        "url" => "https://www.mdzol.com/rss/pages/mundo.xml",
        "category" => "Internacional"
    ],
    [
        "url" => "https://eleconomista.com.ar/negocios/feed/",
        "category" => "Negocios"
    ]
];

$items = [];

foreach ($feeds as $feed) {

    $rss = @simplexml_load_file($feed["url"]);
    if (!$rss || !isset($rss->channel->item)) {
        continue;
    }

    // SOLO 1 noticia por feed (la más reciente)
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
 FUNCIÓN PARA EXTRAER IMAGEN
*/
function extract_image($item) {

    // media:content
    $namespaces = $item->getNameSpaces(true);
    if (isset($namespaces["media"])) {
        $media = $item->children($namespaces["media"]);
        if (isset($media->content)) {
            return (string)$media->content->attributes()->url;
        }
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
    <title>Feed Unificado Noticias Argentina</title>
    <link>https://feed-unificado.onrender.com</link>
    <description>Noticias por categoría para automatización</description>

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
