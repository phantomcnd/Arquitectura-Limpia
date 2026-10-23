<?php
// ============================================================
// ARMADO DEL SISTEMA
//
// Es el unico lugar que conoce todas las piezas. Lee dos archivos:
//   config/config.json  -> que base de datos usar (sqlite, postgres, oracle, memoria)
//   config/reglas.json  -> limite de fraude, clientes bloqueados, IVA
// Para cambiar de base de datos o de reglas se edita el JSON; no se
// toca ningun archivo .php.
//
// Quien lo use (API, consumidor, prueba) llama a armarSistema() y
// recibe todo listo para trabajar.
// ============================================================

require_once __DIR__ . '/dominio/Reglas.php';
require_once __DIR__ . '/dominio/Factura.php';
require_once __DIR__ . '/dominio/FacturaRepositorio.php';
require_once __DIR__ . '/aplicacion/CrearFactura.php';
require_once __DIR__ . '/aplicacion/ListarFacturas.php';
require_once __DIR__ . '/infraestructura/RepoSqlite.php';
require_once __DIR__ . '/infraestructura/RepoMemoria.php';
require_once __DIR__ . '/infraestructura/RepoPostgres.php';
require_once __DIR__ . '/infraestructura/RepoOracle.php';
require_once __DIR__ . '/infraestructura/ColaSqlite.php';
require_once __DIR__ . '/infraestructura/AdaptadorRest.php';
require_once __DIR__ . '/infraestructura/AdaptadorBroker.php';

// Lee un archivo JSON y lo devuelve como arreglo.
function leerJson($ruta)
{
    return json_decode(file_get_contents($ruta), true);
}

// Elige la base de datos segun el valor "persistencia" de config.json.
function armarRepositorio($persistencia, $carpeta)
{
    if ($persistencia == 'memoria') {
        return new RepoMemoria();
    }
    if ($persistencia == 'sqlite') {
        return new RepoSqlite($carpeta . '/facturas.sqlite');
    }
    if ($persistencia == 'postgres') {
        return new RepoPostgres($carpeta . '/postgres.sqlite');
    }
    if ($persistencia == 'oracle') {
        return new RepoOracle($carpeta . '/oracle.sqlite');
    }
    throw new Exception("Persistencia desconocida en config.json: '$persistencia'");
}

// Las pruebas pueden pasar $cambios para usar otra base, otra carpeta de
// datos u otro archivo de reglas sin tocar los JSON de verdad.
function armarSistema($cambios = [])
{
    $config = leerJson(__DIR__ . '/config/config.json');

    $persistencia = $config['persistencia'];
    $carpetaDatos = __DIR__ . '/' . $config['carpeta_datos'];
    $archivoReglas = __DIR__ . '/config/reglas.json';

    if (isset($cambios['persistencia'])) {
        $persistencia = $cambios['persistencia'];
    }
    if (isset($cambios['carpeta_datos'])) {
        $carpetaDatos = $cambios['carpeta_datos'];
    }
    if (isset($cambios['archivo_reglas'])) {
        $archivoReglas = $cambios['archivo_reglas'];
    }

    // Las reglas del negocio salen de reglas.json.
    $json = leerJson($archivoReglas);
    $reglas = new Reglas(
        $json['fraude']['monto_maximo'],
        $json['fraude']['clientes_bloqueados'],
        $json['fiscal']['iva']
    );

    $repositorio = armarRepositorio($persistencia, $carpetaDatos);
    $crear = new CrearFactura($repositorio, $reglas);
    $listar = new ListarFacturas($repositorio);
    $cola = new ColaSqlite($carpetaDatos . '/cola.sqlite');

    return [
        'rest' => new AdaptadorRest($crear, $listar),
        'broker' => new AdaptadorBroker($crear, $cola),
        'cola' => $cola,
    ];
}
