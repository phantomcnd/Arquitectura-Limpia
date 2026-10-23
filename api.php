<?php
// ============================================================
// SERVIDOR WEB (entrada sincrona)
//
// Arrancar:  php -S localhost:8000 api.php
// Probar:    POST http://localhost:8000/facturas   (ver docs/GUIA_DEFENSA.md)
//            GET  http://localhost:8000/facturas
//
// Solo conecta HTTP con AdaptadorRest. Las reglas se leen en cada
// peticion, asi que editar config/reglas.json surte efecto al instante.
// ============================================================

require __DIR__ . '/main.php';

$sistema = armarSistema();

$ruta = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$metodo = $_SERVER['REQUEST_METHOD'];
$cuerpo = file_get_contents('php://input');

$respuesta = $sistema['rest']->recibirPeticion($metodo, $ruta, $cuerpo);

http_response_code($respuesta[0]);
header('Content-Type: application/json');
echo json_encode($respuesta[1], JSON_PRETTY_PRINT) . "\n";
