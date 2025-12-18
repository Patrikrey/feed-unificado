<?php
header("Content-Type: application/rss+xml; charset=UTF-8");

/**
 * ==================================================
 * FEEDS POR CATEGORÍA (1 noticia por cada uno)
 * ==================================================
 */

$fuentes = [
  "Cuyo" => "https://www.mdzol.com/rss/pages/ultimas-noticias-mendoza.xml",
  "Nacional" => "https://www.mdzol.com/rss/pages/ultimas-noticias-argentina.xml",
  "Internacional" => "https://www.mdzol.com/rss/pages/mundo.xml",
  "Entretenimiento" => "https://www.mdzol.com/rss/pages/mdz-show.xml",
  "Politica" => "https://www.mdzol.com/rss/pages/politica.xml",
  "Negocios" => "https://eleconomista.com.ar/negocios/feed/"
];

$items = [];

/**
 * ==================================================
 * LEER 1 ITEM POR FEED
 * ==================================================
 */

foreach ($fuentes as $categoria => $url) {

  $rss = @simplexml_load_file($url);
  if (!$rss || !isset($rss->channel->item)) continue;

  // Tomar SOLO la noticia más reciente
  $item = $rss->channel->item[0];
  $item->categoria_asignada = $categoria;

  $items[] = $item;
}

/**
 * ==================================================
 * SALIDA RSS
 * ==================================================
 */

echo '<?xml version="1.0" encoding="UTF-8"?>';
?>
<rss version="2.0" xmlns:media="http://search.yahoo.com/mrss/">
<channel>
  <title>Feed Unificado Balanceado</title>
  <link>https://feed-unificado.onrender.com</link>
  <description>1 noticia por categoría en cada ejecución</description>

<?php foreach ($items as $item): ?>

  <item>
    <title><![CDATA[<?= (string)$item->title ?>]]></title>
    <link><?= (string)$item->link ?></link>
    <description><![CDATA[<?= strip_tags((string)$item->description) ?>]]></description>
    <pubDate><?= (string)$item->pubDate ?></pubDate>

    <!-- Categoría correcta -->
    <category><?= htmlspecialchars($item->categoria_asignada) ?></category>

<?php
    // Imagen
    $image_url = "";

    $namespaces = $item->getNameSpaces(true);
    if (isset($namespaces['media'])) {
      $media = $item->children($namespaces['media']);
      if (isset($media->content)) {
        $image_url = (string)$media->content->attributes()->url;
      }
    }

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
