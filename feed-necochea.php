<?php
header("Content-Type: application/rss+xml; charset=UTF-8");

/*
 FEEDS NECochea + CATEGORÍA FIJA
*/
$feeds = [

    // NDEN
    ["url" => "https://nden.com.ar/rss/locales", "category" => "Locales"],
    ["url" => "https://nden.com.ar/rss/politica", "category" => "Politica"],
    ["url" => "https://nden.com.ar/rss/deportes", "category" => "Deportes"],
    ["url" => "https://nden.com.ar/rss/policiales", "category" => "Policiales"],

    // ECOS DIARIOS (ATOM)
    ["url" => "https://ecosdiariosapiv3.eleco.com.ar/feed-notes", "category" => "Locales"]
];

$items = [];

foreach ($feeds as $feed) {

    $rss = @simplexml_load_file($feed["url"]);
    if (!$rss) continue;

    /*
     DETECTA SI ES RSS O ATOM
    */

    // RSS clásico
    if (isset($rss->channel->item)) {

        $item = $rss->channel->item[0];

        $items[] = build_item($item, $feed["category"], false);
    }

    // ATOM (Ecos)
    elseif (isset($rss->entry)) {

        $entry = $rss->entry[0];

        $items[] = build_item($entry, $feed["category"], true);
    }
}

/*
 FUNCIÓN PARA NORMALIZAR RSS Y ATOM
*/
function build_item($item, $category, $is_atom = false) {

    if ($is_atom) {
        $title = (string)$item->title;
        $link = (string)$item->link['href'];
        $description = (string)$item->summary;
        $pubDate = date(DATE_RSS, strtotime((string)$item->updated));
    } else {
        $title = (string)$item->title;
        $link = (string)$item->link;
        $description = (string)$item->description;
        $pubDate = (string)$item->pubDate;
    }

    return [
        "title" => $title,
        "link" => $link,
        "description" => strip_tags($description),
        "pubDate" => $pubDate,
        "category" => $category,
        "image" => extract_image($item)
    ];
}

/*
 EXTRAER IMAGEN (RSS + ATOM)
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

    // enclosure
    if (isset($item->enclosure)) {
        return (string)$item->enclosure['url'];
    }

    // buscar img en description/summary
    $content = (string)$item->description . (string)$item->summary;

    if (preg_match('/<img.*?src=["\'](.*?)["\']/i', $content, $matches)) {
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
<description>Noticias locales de Necochea por categoría</description>

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
