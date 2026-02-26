<?php
/*
  TEST - Extractor de contenido de artículos
  Probá pegando cualquier URL de los diarios locales
*/

// ── CAMBIÁ ESTA URL PARA PROBAR ──────────────────────────────
$test_url = "https://nden.com.ar/nota/35584/reforma-laboral-el-frente-renovador-necochea-respaldo-el-reclamo-nacional";
// ─────────────────────────────────────────────────────────────

define('MAX_CONTENT_CHARS', 3500);

$context = stream_context_create([
    "http" => [
        "timeout"    => 8,
        "user_agent" => "Mozilla/5.0 (Windows NT 10.0; Win64; x64) Chrome/122.0.0.0"
    ]
]);

$resultado = fetch_article_content($test_url, $context);

// Output visual para ver el resultado fácil
header("Content-Type: text/plain; charset=UTF-8");
echo "URL PROBADA:\n";
echo $test_url . "\n";
echo "\n";
echo "LARGO DEL CONTENIDO EXTRAÍDO: " . strlen($resultado) . " caracteres\n";
echo "─────────────────────────────────────────\n";
echo "CONTENIDO:\n\n";
echo $resultado ?: "⚠️  No se pudo extraer contenido — el selector no matcheó esta fuente.";

// ─────────────────────────────────────────────────────────────
// FUNCIONES
// ─────────────────────────────────────────────────────────────
function fetch_article_content($url, $context) {
    $blocked_domains = ["lanacion.com.ar", "clarin.com", "infobae.com"];
    foreach ($blocked_domains as $domain) {
        if (strpos($url, $domain) !== false) return "⛔ Dominio bloqueado: $domain";
    }

    $html = @file_get_contents($url, false, $context);
    if (!$html) return "⚠️  No se pudo descargar la URL (timeout o bloqueo).";

    echo "HTML descargado: " . strlen($html) . " bytes\n";
    echo "─────────────────────────────────────────\n";

    // Muestra qué selector matcheó — útil para debuggear
    $selectors = [
        "WordPress entry-content"  => '/<div[^>]+class="[^"]*entry-content[^"]*"[^>]*>(.*?)<\/div>\s*(?:<div|<footer|<aside)/si',
        "WordPress post-content"   => '/<div[^>]+class="[^"]*post-content[^"]*"[^>]*>(.*?)<\/div>\s*(?:<div|<footer|<aside)/si',
        "nden nota-body"           => '/<div[^>]+class="[^"]*nota-body[^"]*"[^>]*>(.*?)<\/div>\s*(?:<div|<footer)/si',
        "nden article-body"        => '/<div[^>]+class="[^"]*article-body[^"]*"[^>]*>(.*?)<\/div>\s*(?:<div|<footer)/si',
        "article-content genérico" => '/<div[^>]+class="[^"]*article-content[^"]*"[^>]*>(.*?)<\/div>\s*(?:<div|<footer)/si',
        "content-body"             => '/<div[^>]+class="[^"]*content-body[^"]*"[^>]*>(.*?)<\/div>\s*(?:<div|<footer)/si',
        "field-items"              => '/<div[^>]+class="[^"]*field-items[^"]*"[^>]*>(.*?)<\/div>\s*(?:<div|<footer)/si',
        "tag article"              => '/<article[^>]*>(.*?)<\/article>/si',
    ];

    foreach ($selectors as $nombre => $pattern) {
        if (preg_match($pattern, $html, $matches)) {
            $text = clean_html_content($matches[1]);
            if (strlen($text) > 150) {
                echo "✅ SELECTOR QUE FUNCIONÓ: $nombre\n";
                echo "─────────────────────────────────────────\n";
                return mb_substr($text, 0, MAX_CONTENT_CHARS);
            } else {
                echo "⚠️  Selector '$nombre' matcheó pero contenido muy corto (" . strlen($text) . " chars)\n";
            }
        } else {
            echo "✗  Selector '$nombre' no matcheó\n";
        }
    }

    return "";
}

function clean_html_content($html) {
    $html = preg_replace('/<script\b[^>]*>.*?<\/script>/si', '', $html);
    $html = preg_replace('/<style\b[^>]*>.*?<\/style>/si',   '', $html);
    $html = preg_replace('/<!--.*?-->/s',                     '', $html);
    $html = preg_replace('/<iframe[^>]*>.*?<\/iframe>/si',   '', $html);
    $html = preg_replace('/<figcaption[^>]*>.*?<\/figcaption>/si', '', $html);
    $html = preg_replace('/<\/p>/i',   ' ', $html);
    $html = preg_replace('/<br\s*\/?>/i', ' ', $html);
    $text = strip_tags($html);
    $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    $text = preg_replace('/\s+/', ' ', $text);
    return trim($text);
}
