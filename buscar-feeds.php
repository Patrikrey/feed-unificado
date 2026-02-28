<?php
/*
  BUSCADOR DE FEEDS RSS
  Detecta automáticamente la URL del feed de cada sitio
*/
header("Content-Type: text/plain; charset=UTF-8");

$sitios = [
    "https://tsnnecochea.com.ar",
    "https://2262.com.ar",
    "https://necocheahoy.com",
    "https://diarionecochea.com",
    "https://lavozdenecochea.com.ar",
    "https://alertanecochea.com.ar",
    "https://quequenlibre.com.ar",
    "https://primicias2262.com",
    "https://necocheadigital.com",
    "https://necocheanews.com.ar",
    "https://nden.com.ar",
    "https://ecosdiariosapiv3.eleco.com.ar",
];

$context = stream_context_create([
    "http" => [
        "timeout"    => 8,
        "user_agent" => "Mozilla/5.0 (Windows NT 10.0; Win64; x64) Chrome/122.0.0.0",
        "follow_location" => 1,
    ]
]);

// URLs comunes de feeds RSS en WordPress y otros CMS
$rutas_comunes = [
    "/feed/",
    "/feed",
    "/rss/",
    "/rss",
    "/rss.xml",
    "/feed.xml",
    "/atom.xml",
    "/?feed=rss2",
    "/index.php?format=feed&type=rss",
];

echo "=== BUSCADOR DE FEEDS RSS ===\n";
echo date("Y-m-d H:i:s") . "\n\n";

foreach ($sitios as $sitio) {
    echo "🔍 $sitio\n";
    $encontrado = false;

    // Primero buscar en el HTML del sitio (link rel="alternate")
    $html = @file_get_contents($sitio, false, $context);
    if ($html) {
        preg_match_all('/<link[^>]+type=["\']application\/(rss|atom)\+xml["\'][^>]*href=["\']([^"\']+)["\'][^>]*>/i', $html, $m1);
        preg_match_all('/<link[^>]+href=["\']([^"\']+)["\'][^>]*type=["\']application\/(rss|atom)\+xml["\'][^>]*>/i', $html, $m2);

        $urls_encontradas = array_merge($m1[2] ?? [], $m2[1] ?? []);

        foreach ($urls_encontradas as $feed_url) {
            // Hacer absoluta si es relativa
            if (strpos($feed_url, 'http') !== 0) {
                $feed_url = rtrim($sitio, '/') . '/' . ltrim($feed_url, '/');
            }
            $test = @file_get_contents($feed_url, false, $context);
            if ($test && @simplexml_load_string($test)) {
                $items = count(@simplexml_load_string($test)->channel->item ?? []);
                echo "   ✅ FEED ENCONTRADO EN HTML: $feed_url ($items items)\n";
                $encontrado = true;
                break;
            }
        }
    }

    // Si no encontró en el HTML, probar rutas comunes
    if (!$encontrado) {
        foreach ($rutas_comunes as $ruta) {
            $feed_url = rtrim($sitio, '/') . $ruta;
            $content  = @file_get_contents($feed_url, false, $context);
            if ($content && strlen($content) > 100) {
                $rss = @simplexml_load_string($content);
                if ($rss && isset($rss->channel->item)) {
                    $items = count($rss->channel->item);
                    echo "   ✅ FEED EN RUTA COMÚN: $feed_url ($items items)\n";
                    $encontrado = true;
                    break;
                }
            }
        }
    }

    if (!$encontrado) {
        echo "   ❌ No se encontró feed RSS válido\n";
    }
    echo "\n";
}
