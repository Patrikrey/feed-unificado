<?php
/**
 * Feed Unificado - Proyecto El Observador Rosario
 * Este archivo centraliza noticias para ser enviadas a Make.com
 */

header("Content-Type: application/rss+xml; charset=UTF-8");

// Forzamos un User-Agent de navegador para que los diarios no bloqueen a OnRender
libxml_set_streams_context(stream_context_create([
    "http" => [
        "header" => "User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36\r\n",
        "follow_location" => 1,
        "timeout" => 20
    ]
]));

// Fuentes de Rosario verificadas (usamos las que NO dan error 404 ni error de debug)
$feeds = [
    ["url" => "https://www.elciudadanoweb.com/feed/", "category" => "Locales"],
    ["url" => "https://www.rosarionuestro.com/feed/", "category" => "Locales"],
    ["url" => "https://nden.com.ar/rss/locales", "category" => "Locales"],
    ["url" => "https://www.clarin.com/rss/lo-ultimo/", "category" => "Nacional"]
];

$items = [];
$seen_titles = [];

foreach ($feeds as $feed) {
    // Cargamos el XML silenciando errores de conexión
    $rss = @simplexml_load_file($feed["url"]);
    
    if (!$rss || !isset($rss->channel->item)) {
        continue;
    }

    foreach ($rss->channel->item as $item) {
        $title = trim((string)$item->title);
        
        // Evitamos que una misma noticia de distintas fuentes se publique dos veces
        if (in_array($title, $seen_titles)) continue;

        $pubDate = (string)$item->pubDate;
        $timestamp = strtotime($pubDate) ?: time();

        $items[] = [
            "title"       => $title,
            "link"        => trim((string)$item->link),
            "description" => trim(strip_tags((string)$item->description)),
            "pubDate"     => $pubDate,
            "timestamp"   => $timestamp,
            "category"    => $feed["category"],
            "image"       => extract_image_final($item)
        ];
        
        $seen_titles[] = $title;
    }
}

/**
 * Función robusta para sacar la URL de la imagen para tu plugin de WordPress
 */
function extract_image_final($item) {
    $namespaces = $item->getNameSpaces(true);
    
    // 1. Intentar con etiquetas multimedia (Media RSS)
    if (isset($namespaces["media"])) {
        $media = $item->children($namespaces["media"]);
        if (isset($media->content)) {
            $attrs = $media->content->attributes();
            if (isset($attrs["url"])) return (string)$attrs["url"];
        }
    }

    // 2. Buscar dentro del contenido extendido de WordPress (El Ciudadano)
    if (isset($namespaces["content"])) {
        $content = (string)$item->children($namespaces["content"])->encoded;
        if (preg_match('/<img.*?src=["\'](.*?)["\']/i', $content, $matches)) {
            return $matches[1];
        }
    }

    // 3. Buscar en adjuntos estándar
    if (isset($item->enclosure)) {
        $attrs = $item->enclosure->attributes();
        if (isset($attrs["url"])) return (string)$attrs["url"];
    }

    // 4. Último recurso: buscar imagen en la descripción
    if (preg_match('/<img.*?src=["\'](.*?)["\']/i', (string)$item->description, $matches)) {
        return $matches[1];
    }

    return "";
}

// Ordenar: lo más reciente primero
usort($items, function($a, $b) {
    return $b["timestamp"] <=> $a["timestamp"];
});

// Limitamos a 15 para que Make no se sature de golpe
$items = array_slice($items, 0, 15);

echo '<?xml version="1.0" encoding="UTF-8"?>';
?>
<rss version="2.0" 
     xmlns:content="http://purl.org/rss/1.0/modules/content/"
     xmlns:media="http://search.yahoo.com/mrss/">
<channel>
    <title>El Observador Rosario - Feed Unificado</title>
    <link>https://elobservador.totalh.net</link>
    <description>Noticias locales listas para procesamiento automatizado</description>

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
