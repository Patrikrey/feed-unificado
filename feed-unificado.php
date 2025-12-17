<?php
error_reporting(0);
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
header("Content-Type: application/rss+xml; charset=UTF-8");

// Definir fuentes por categoría
$categorias = [
    "Negocios" => [
        "https://eleconomista.com.ar/negocios/feed/"
    ],
    "Politica" => [
        "https://www.mdzol.com/rss/pages/politica.xml"
    ],
    "Cuyo" => [
        "https://www.mdzol.com/rss/pages/ultimas-noticias-mendoza.xml"
    ],
    "Nacional" => [
        "https://www.mdzol.com/rss/pages/ultimas-noticias-argentina.xml"
    ],
    "Entretenimiento" => [
        "https://www.mdzol.com/rss/pages/mdz-show.xml"
    ],
    "Internacional" => [
        "https://www.mdzol.com/rss/pages/mundo.xml"
    ]
];

$items = [];

// Leer cada feed y agregar items
foreach ($categorias as $cat => $fuentes) {
    foreach ($fuentes as $url) {
        $rss = @simplexml_load_file($url);
        if (!$rss || !isset($rss->channel->item)) continue;
        foreach ($rss->channel->item as $item) {
            // Adjuntar categoría al item
            $item->cat_assigned = $cat;
            $items[] = $item;
        }
    }
}

// Ordenar por fecha descendente (más recientes primero)
usort($items, function($a, $b) {
    return strtotime($b->pubDate) - strtotime($a->pubDate);
});

// Generar RSS unificado
echo '<?xml version="1.0" encoding="UTF-8"?>';
echo '<rss version="2.0" xmlns:media="http://search.yahoo.com/mrss/">';
echo '<channel>';
echo '<title>Feed privado unificado</title>';
echo '<link>https://bienarribacuyo.is-great.net/</link>';
echo '<description>Noticias combinadas para automatización</description>';

foreach (array_slice($items, 0, 50) as $item) {

    // Categoría asignada desde $item->cat_assigned
    $categoria_final = $item->cat_assigned;

    echo '<item>';
    echo '<title><![CDATA['.$item->title.']]></title>';
    echo '<link>'.$item->link.'</link>';
    echo '<description><![CDATA['.strip_tags($item->description).']]></description>';
    echo '<pubDate>'.$item->pubDate.'</pubDate>';

    // Etiqueta <category> para Make
    echo '<category><![CDATA[' . $categoria_final . ']]></category>';

    // Extraer imagen si existe
    $image_url = '';
    $namespaces = $item->getNameSpaces(true);
    if (isset($namespaces['media'])) {
        $media = $item->children($namespaces['media']);
        if (isset($media->content)) {
            $image_url = (string)$media->content->attributes()->url;
        }
    }
    if (!$image_url && preg_match('/<img.*?src=["\'](.*?)["\']/i', $item->description, $m)) {
        $image_url = $m[1];
    }
    if ($image_url) {
        echo '<media:content url="' . htmlspecialchars($image_url) . '" type="image/jpeg" />';
        echo '<enclosure url="' . htmlspecialchars($image_url) . '" type="image/jpeg" />';
    }

    echo '</item>';
}

echo '</channel>';
echo '</rss>';
