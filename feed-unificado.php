<?php
error_reporting(0);
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
header("Content-Type: application/rss+xml; charset=UTF-8");

// Fuentes oficiales
$fuentes = [
    "https://www.diarioandino.com.ar/rss/noticias/",
    "https://www.mdzol.com/rss/pages/ultimas-noticias-mendoza.xml"
];

$items = [];

// Leer feeds
foreach ($fuentes as $url) {
    $rss = @simplexml_load_file($url);
    if (!$rss || !isset($rss->channel->item)) continue;
    foreach ($rss->channel->item as $item) {
        $items[] = $item;
    }
}

// Ordenar por fecha
usort($items, function($a, $b) {
    return strtotime($b->pubDate) - strtotime($a->pubDate);
});

// Generar RSS
echo '<?xml version="1.0" encoding="UTF-8"?>';
echo '<rss version="2.0" xmlns:media="http://search.yahoo.com/mrss/">';
echo '<channel>';
echo '<title>Feed privado unificado</title>';
echo '<link>https://bienarribacuyo.is-great.net/</link>';
echo '<description>Noticias combinadas para automatización</description>';

foreach (array_slice($items, 0, 20) as $item) {
    echo '<item>';
    echo '<title><![CDATA['.$item->title.']]></title>';
    echo '<link>'.$item->link.'</link>';
    echo '<description><![CDATA['.strip_tags($item->description).']]></description>';
    echo '<pubDate>'.$item->pubDate.'</pubDate>';

    $image_url = '';
    $namespaces = $item->getNameSpaces(true);
    if(isset($namespaces['media'])) {
        $media = $item->children($namespaces['media']);
        if(isset($media->content)) $image_url = (string)$media->content->attributes()->url;
    }
    if(!$image_url && preg_match('/<img.*?src=["\'](.*?)["\']/i', $item->description, $m)) $image_url = $m[1];

    if($image_url) {
        echo '<media:content url="'.htmlspecialchars($image_url).'" type="image/jpeg" />';
        echo '<enclosure url="'.htmlspecialchars($image_url).'" type="image/jpeg" />';
    }

    echo '</item>';
}

echo '</channel>';
echo '</rss>';
