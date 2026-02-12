<?php

header("Content-Type: application/rss+xml; charset=UTF-8");

$fuente = $_GET['fuente'] ?? '';
$cat = $_GET['cat'] ?? '';

/*
|--------------------------------------------------------------------------
| CONFIGURACIÓN DE FUENTES
|--------------------------------------------------------------------------
*/

$fuentes = [

    "nden" => [
        "tipo" => "rss",
        "base_url" => "https://nden.com.ar/rss/",
        "categorias" => [
            "politica",
            "policiales",
            "deportes",
            "locales"
        ]
    ]

];

/*
|--------------------------------------------------------------------------
| VALIDACIÓN
|--------------------------------------------------------------------------
*/

if (!isset($fuentes[$fuente])) {
    exit("Fuente no válida");
}

$config = $fuentes[$fuente];

if (!in_array($cat, $config["categorias"])) {
    exit("Categoría no válida");
}

/*
|--------------------------------------------------------------------------
| MANEJO SEGÚN TIPO DE FUENTE
|--------------------------------------------------------------------------
*/

if ($config["tipo"] === "rss") {

    $url = $config["base_url"] . $cat;

    $contenido = @file_get_contents($url);

    if ($contenido === false) {
        exit("No se pudo obtener el feed");
    }

    echo $contenido;
    exit;
}

/*
|--------------------------------------------------------------------------
| FUTURO: SCRAPING
|--------------------------------------------------------------------------
| Aquí luego agregaremos medios sin RSS
*/

exit("Tipo de fuente no soportado");

?>
