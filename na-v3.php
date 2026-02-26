<?php
/*
  DEBUG - Ver estructura HTML de nden.com.ar
*/

$test_url = "https://nden.com.ar/nota/35584/reforma-laboral-el-frente-renovador-necochea-respaldo-el-reclamo-nacional";

$context = stream_context_create([
    "http" => [
        "timeout"    => 8,
        "user_agent" => "Mozilla/5.0 (Windows NT 10.0; Win64; x64) Chrome/122.0.0.0"
    ]
]);

$html = @file_get_contents($test_url, false, $context);
if (!$html) { echo "No se pudo descargar"; exit; }

echo "Bytes descargados: " . strlen($html) . "\n\n";

// Extraer todos los div/section/article con su class o id
preg_match_all('/<(div|section|article|main)[^>]+(class|id)="([^"]{3,60})"[^>]*>/i', $html, $matches);

echo "=== CLASES Y IDs ENCONTRADOS ===\n";
$vistos = [];
foreach ($matches[3] as $i => $clase) {
    $tag = $matches[1][$i];
    if (!in_array($clase, $vistos)) {
        echo "<$tag> class/id: \"$clase\"\n";
        $vistos[] = $clase;
    }
}

echo "\n=== PRIMEROS 3000 CHARS DEL HTML (para ver estructura) ===\n";
echo htmlspecialchars(substr($html, 0, 3000));
