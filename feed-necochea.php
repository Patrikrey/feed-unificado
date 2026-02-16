<?php
header("Content-Type: application/rss+xml; charset=UTF-8");

/*
  FEEDS UNIFICADOS - COBERTURA TOTAL NECOCHEA Y QUEQUÉN
  Actualización: Febrero 2026
*/
$feeds = [

    // --- GRUPO 1: MEDIOS TRADICIONALES Y APIS ---
    ["url" => "https://nden.com.ar/rss/locales", "category" => "Locales"],
    ["url" => "https://nden.com.ar/rss/politica", "category" => "Politica"],
    ["url" => "https://nden.com.ar/rss/deportes", "category" => "Deportes"],
    ["url" => "https://nden.com.ar/rss/policiales", "category" => "Policiales"],
    ["url" => "https://ecosdiariosapiv3.eleco.com.ar/feed-notes", "category" => "Locales"],

    // --- GRUPO 2: PORTALES DIGITALES NATIVOS ---
    ["url" => "https://diarionecochea.com/feed/", "category" => "Locales"],
    ["url" => "https://tsnnecochea.com.ar/feed/", "category" => "Locales"],
    ["url" => "https://necocheadigital.com/feed/", "category" => "Locales"],
    ["url" => "https://2262.com.ar/feed/", "category" => "Locales"],
    ["url" => "https://necocheanews.com.ar/feed/", "category" => "Locales"],

    // --- GRUPO 3: NUEVAS FUENTES Y ESPECIALIZADOS ---
    ["url" => "https://lavozdenecochea.com.ar/feed/", "category" => "Locales"],    // Clásico local renovado
    ["url" => "https://alertanecochea.com.ar/feed/", "category" => "Policiales"],  // Enfoque en seguridad y alertas
    ["url" => "https://necocheahoy.com/feed/", "category" => "Locales"],         // Noticias de actualidad diaria
    ["url" => "https://quequenlibre.com.ar/feed/", "category" => "Quequén"],     // Enfoque exclusivo en la vecina localidad
    ["url" => "https://primicias2262.com/feed/", "category" => "Locales"],       // Noticias rápidas
    ["url" => "https://www.cuatromedios.com.ar/feed/", "category" => "Regionales"], // Cobertura local y zona de influencia
    ["url" => "https://necochea.gov.ar/feed/", "category" => "Oficial"]          // Prensa de la Municipalidad de Necochea

];

$items = [];

foreach ($feeds as $feed) {
    // Usamos @ para evitar que errores de carga rompan el XML completo
    $rss = @simplexml_load_file($feed["url"]);
    if (!$rss || !isset($rss->channel->item)) {
        continue;
    }

    foreach ($rss->channel->item as $item) {
        $pubDate = (string)$item->pubDate;
        $timestamp = strtotime($pubDate);

        if (!$timestamp) {
            $timestamp = time();
        }

        $items[] = [
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

/*
  FUNCIÓN PARA EXTRAER IMAGEN (Compatible con WP y Custom Feeds)
*/
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

// Ordenar por fecha (más reciente primero)
usort($items, function($a, $b) {
    return $b["timestamp"] <=> $a["timestamp"];
});

// Limitamos a las últimas 15 para dar variedad con tantas fuentes
$items = array_slice($items, 0, 15);

// Salida XML
echo '<?xml version="1.0" encoding="UTF-8"?>';
?>
<rss version="2.0" xmlns:media="http://search.yahoo.com/mrss/">
<channel>
<title>Super Feed Necochea Total</title>
<link>https://tu-dominio.com</link>
<description>Fusión de todos los medios de Necochea y Quequén</description>

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
