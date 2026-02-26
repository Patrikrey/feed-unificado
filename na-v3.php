<?php
/*
  TEST - Extractor de contenido de artículos
*/

$test_url = "https://www.lacapitalmdp.com/la-autopsia-revelo-que-matias-peralta-tenia-cuatro-heridas-de-bala/";

define('MAX_CONTENT_CHARS', 3500);

$context = stream_context_create([
    "http" => [
        "timeout"    => 8,
        "user_agent" => "Mozilla/5.0 (Windows NT 10.0; Win64; x64) Chrome/122.0.0.0"
    ]
]);

header("Content-Type: text/plain; charset=UTF-8");

$html = @file_get_contents($test_url, false, $context);
if (!$html) { echo "No se pudo descargar"; exit; }

echo "HTML descargado: " . strlen($html) . " bytes\n";
echo "─────────────────────────────────────────\n";

$selectors = [
    // nden.com.ar — clase real encontrada en debug
    "nden texto_nota"          => '/<div[^>]+class="[^"]*texto_nota[^"]*"[^>]*>(.*?)<\/div>\s*(?:<div|<section|$)/si',
    // WordPress estándar
    "WordPress entry-content"  => '/<div[^>]+class="[^"]*entry-content[^"]*"[^>]*>(.*?)<\/div>\s*(?:<div|<footer|<aside)/si',
    "WordPress post-content"   => '/<div[^>]+class="[^"]*post-content[^"]*"[^>]*>(.*?)<\/div>\s*(?:<div|<footer|<aside)/si',
    // Otros
    "article-content"          => '/<div[^>]+class="[^"]*article-content[^"]*"[^>]*>(.*?)<\/div>\s*(?:<div|<footer)/si',
    "content-body"             => '/<div[^>]+class="[^"]*content-body[^"]*"[^>]*>(.*?)<\/div>\s*(?:<div|<footer)/si',
    // Fallback
    "tag article"              => '/<article[^>]*>(.*?)<\/article>/si',
];

$resultado = "";
foreach ($selectors as $nombre => $pattern) {
    if (preg_match($pattern, $html, $matches)) {
        $text = clean_html($matches[1]);
        if (strlen($text) > 150) {
            echo "✅ SELECTOR: $nombre\n";
            echo "─────────────────────────────────────────\n";
            $resultado = mb_substr($text, 0, MAX_CONTENT_CHARS);
            break;
        } else {
            echo "⚠️  '$nombre' matcheó pero muy corto (" . strlen($text) . " chars)\n";
        }
    } else {
        echo "✗  '$nombre' no matcheó\n";
    }
}

echo "\nLARGO EXTRAÍDO: " . strlen($resultado) . " caracteres\n";
echo "─────────────────────────────────────────\n";
echo $resultado ?: "⚠️  No se pudo extraer contenido.";

function clean_html($html) {
    $html = preg_replace('/<script\b[^>]*>.*?<\/script>/si', '', $html);
    $html = preg_replace('/<style\b[^>]*>.*?<\/style>/si',   '', $html);
    $html = preg_replace('/<!--.*?-->/s',                     '', $html);
    $html = preg_replace('/<iframe[^>]*>.*?<\/iframe>/si',   '', $html);
    $html = preg_replace('/<figcaption[^>]*>.*?<\/figcaption>/si', '', $html);
    $html = preg_replace('/<\/p>/i',      ' ', $html);
    $html = preg_replace('/<br\s*\/?>/i', ' ', $html);
    $text = strip_tags($html);
    $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    $text = preg_replace('/\s+/', ' ', $text);
    return trim($text);
}
