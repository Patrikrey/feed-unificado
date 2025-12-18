<?php
header("Content-Type: application/rss+xml; charset=UTF-8");

/**
 * ================================
 * CONFIGURACIÓN
 * ================================
 */

// Categorías rotativas
$categorias = [
  "Cuyo",
  "Nacional",
  "Internacional",
  "Entretenimiento",
  "Negocios",
  "Politica"
];

// Estado de la rotación
$estado_file = __DIR__ . "/estado_categoria.txt";

if (!file_exists($estado_file)) {
  file_put_contents($estado_file, "0");
}

$indice = (int) file_get_contents($estado_file);
$categoria_actual = $categorias[$indice];

// Avanzar rotación
$indice++;
if ($indice >= count($categorias)) {
  $indice = 0;
}
file_put_contents($estado_file, (string)$indice);


/**
 * ================================
 * FEEDS DE ORIGEN
 * (podés agregar más después)
 * ================================
 */

$fuentes = [
  "https://www.mdzol.com/rss/pages/ultimas-noticias-mendoza.xml",
  "https://www.mdzol.com/rss/pages/ultimas-noticias-argentina.xml",
  "https://www.mdzol.com/rss/pages/politica.xml",
  "https://www.mdzol.com/rss/pages/mundo.xml",
  "https://www.mdzol.com/rss/pages/mdz-show.xml",
  "https://eleconomista.com.ar/negocios/feed/"
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

// Ordenar por fecha (más nuevos primero)
usort($items, function ($a, $b) {
  return strtotime($b->pubDate) - strtotime($a->pubDate);
});

// Limitar cantidad
$items = array_slice($items, 0, 20);


/**
 * ================================
 * SALIDA RSS
 * ================================
 */

echo '<?xml version="1.0" encoding="UTF-8"?>';
?>
<rss version="2.0" xmlns:media="http://search.yahoo.com/mrss/">
<channel>
  <title>Feed Unificado Privado</title>
  <link>https://feed-unificado.onrender.com</link>
  <description>Feed privado para automatización</description>

<?php foreach ($items as $item): ?>

  <item>
    <title><![CDATA[<?= (string)$item->title ?>]]></title>
    <link><?= (string)$item->link ?></link>
    <description><![CDATA[<?= strip_tags((string)$item->description) ?>]]></description>
    <pubDate><?= (string)$item->pubDate ?></pubDate>

    <!-- Categoría rotativa -->
    <category><?= htmlspecialchars($categoria_actual) ?></category>

<?php
    // Extraer imagen
    $image_url = "";

    // media:content
    $namespaces = $item->getNameSpaces(true);
    if (isset($namespaces['media'])) {
      $media = $item->children($namespaces['media']);
      if (isset($media->content)) {
        $image_url = (string)$media->content->attributes()->url;
      }
    }

    // fallback <img>
    if (!$image_url && preg_match('/<img.*?src=["\'](.*?)["\']/i', (string)$item->description, $m)) {
      $image_url = $m[1];
    }

    if ($image_url):
?>
    <media:content url="<?= htmlspecialchars($image_url) ?>" type="image/jpeg" />
    <enclosure url="<?= htmlspecialchars($image_url) ?>" type="image/jpeg" />
<?php endif; ?>

  </item>

<?php endforeach; ?>

</channel>
</rss>
