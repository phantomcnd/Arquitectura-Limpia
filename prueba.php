<?php
// ============================================================
// DEMOSTRACION
//
// Ejecutar:   php -d extension=pdo_sqlite prueba.php
//
// Muestra lo que pide el enunciado:
//   1. Las reglas del negocio se ejecutan sin ninguna base de datos.
//   2. Las reglas se cambian editando un JSON, sin tocar codigo.
//   3. REST (sincrono) y broker (asincrono) usan el mismo caso de uso.
//   4. Cambiar de base de datos es solo configuracion.
//   5. Dominio y aplicacion no mencionan ninguna tecnologia.
//
// Las pruebas usan una carpeta temporal: no tocan data/ ni config/.
// ============================================================

require __DIR__ . '/main.php';

$buenas = 0;
$malas = 0;

function verificar($descripcion, $condicion)
{
    global $buenas, $malas;
    if ($condicion) {
        $buenas++;
        echo "  [OK]    $descripcion\n";
    } else {
        $malas++;
        echo "  [FALLO] $descripcion\n";
    }
}

// Crea una factura entregando los datos al caso de uso (como lo haria un adaptador).
function crear($sistema, $datos)
{
    return $sistema['broker']->recibirMensaje(json_encode($datos));
}

// Carpeta temporal para los archivos .sqlite de la prueba.
$carpeta = sys_get_temp_dir() . '/facturacion_prueba_' . uniqid();
mkdir($carpeta);

// ------------------------------------------------------------
echo "\n1) Reglas del negocio (sin base de datos real)\n";
$sistema = armarSistema(['persistencia' => 'memoria', 'carpeta_datos' => $carpeta]);

$r = crear($sistema, ['cliente' => 'Ana', 'monto' => 1000]);
verificar('IVA normal: 15% de 1000 = 150', $r['exito'] && $r['iva'] == 150);

$r = crear($sistema, ['cliente' => 'Ana', 'monto' => 1000, 'exento' => true]);
verificar('Cliente exento: IVA 0', $r['exito'] && $r['iva'] == 0);

$r = crear($sistema, ['cliente' => 'Ana', 'monto' => 50000]);
verificar('Monto sospechoso: rechazada (fraude)', !$r['exito']);

$r = crear($sistema, ['cliente' => 'Juan Estafador', 'monto' => 100]);
verificar('Cliente bloqueado: rechazada (fraude)', !$r['exito']);

// ------------------------------------------------------------
echo "\n2) Cambiar las reglas editando un JSON (sin tocar codigo)\n";
$otrasReglas = [
    'fraude' => ['monto_maximo' => 500, 'clientes_bloqueados' => ['Ana']],
    'fiscal' => ['iva' => 0.12],
];
file_put_contents($carpeta . '/reglas.json', json_encode($otrasReglas));

$sistema = armarSistema([
    'persistencia' => 'memoria',
    'carpeta_datos' => $carpeta,
    'archivo_reglas' => $carpeta . '/reglas.json',
]);

$r = crear($sistema, ['cliente' => 'Luis', 'monto' => 100]);
verificar('Con iva 0.12: 12% de 100 = 12', $r['exito'] && $r['iva'] == 12);

$r = crear($sistema, ['cliente' => 'Luis', 'monto' => 1000]);
verificar('Con limite 500: 1000 ahora es sospechoso', !$r['exito']);

$r = crear($sistema, ['cliente' => 'Ana', 'monto' => 100]);
verificar('Con lista nueva: "Ana" ahora esta bloqueada', !$r['exito']);

// ------------------------------------------------------------
echo "\n3) REST (sincrono) y broker (asincrono): mismo caso de uso, misma base\n";
$sistema = armarSistema(['persistencia' => 'sqlite', 'carpeta_datos' => $carpeta]);
$json = '{"cliente":"Ana","monto":1000}';

// REST: la respuesta llega de inmediato.
$porRest = $sistema['rest']->recibirPeticion('POST', '/facturas', $json);
echo "  REST   -> codigo " . $porRest[0] . ", factura " . $porRest[1]['numero'] . ", total " . $porRest[1]['total'] . "  (respuesta inmediata)\n";

// Broker: se publican 2 mensajes y se procesan despues.
$sistema['cola']->publicar($json);
$sistema['cola']->publicar('{"cliente":"Juan Estafador","monto":100}');
verificar('Broker: 2 mensajes publicados y aun sin procesar', $sistema['cola']->pendientes() == 2);

$procesados = $sistema['broker']->procesarPendientes();
$porBroker = $procesados[0]['resultado'];
$fraudeBroker = $procesados[1]['resultado'];
echo "  Broker -> factura " . $porBroker['numero'] . ", total " . $porBroker['total'] . "  (procesada despues)\n";

verificar('Broker: la cola quedo vacia', $sistema['cola']->pendientes() == 0);
verificar('REST y broker dan el mismo total', $porRest[1]['total'] == $porBroker['total']);

$fraudeRest = $sistema['rest']->recibirPeticion('POST', '/facturas', '{"cliente":"Juan Estafador","monto":100}');
verificar('Ambos rechazan el fraude igual', $fraudeRest[0] == 422 && !$fraudeBroker['exito']);

$lista = $sistema['rest']->recibirPeticion('GET', '/facturas');
verificar('GET /facturas ve las 2 facturas (una por REST, otra por broker)', count($lista[1]) == 2);

// Se arma el sistema otra vez, como si se reiniciara el programa.
$sistema = armarSistema(['persistencia' => 'sqlite', 'carpeta_datos' => $carpeta]);
$lista = $sistema['rest']->recibirPeticion('GET', '/facturas');
verificar('SQLite: los datos siguen ahi al reiniciar', count($lista[1]) == 2);

// ------------------------------------------------------------
echo "\n4) Cambiar de base de datos (solo configuracion)\n";
$totales = [];
$bases = ['memoria', 'sqlite', 'postgres', 'oracle'];
foreach ($bases as $base) {
    $sistema = armarSistema(['persistencia' => $base, 'carpeta_datos' => $carpeta . '/' . $base]);
    $r = crear($sistema, ['cliente' => 'Ana', 'monto' => 1000]);
    $totales[] = $r['total'];
    echo "  $base -> factura " . $r['numero'] . ", total " . $r['total'] . "\n";
}
verificar('Mismo total con las 4 bases', $totales[0] == $totales[1] && $totales[1] == $totales[2] && $totales[2] == $totales[3]);

// ------------------------------------------------------------
echo "\n5) El centro no conoce la tecnologia\n";
$palabrasProhibidas = ['PDO', 'sql', 'postgres', 'oracle', 'json', 'http', 'cola'];
$archivos = array_merge(glob(__DIR__ . '/dominio/*.php'), glob(__DIR__ . '/aplicacion/*.php'));
$encontradas = 0;

foreach ($archivos as $archivo) {
    foreach (file($archivo) as $linea) {
        // Las lineas de comentario (que empiezan con //) no cuentan: solo el codigo.
        if (substr(trim($linea), 0, 2) == '//') {
            continue;
        }
        foreach ($palabrasProhibidas as $palabra) {
            if (stripos($linea, $palabra) !== false) {
                echo "          '$palabra' en " . basename($archivo) . ": " . trim($linea) . "\n";
                $encontradas++;
            }
        }
    }
}
verificar('El codigo de dominio/ y aplicacion/ no menciona PDO, SQL, JSON, HTTP ni colas', $encontradas == 0);

// ------------------------------------------------------------
// Limpieza de la carpeta temporal (primero se sueltan las conexiones a los archivos).
$sistema = null;
foreach ($bases as $base) {
    array_map('unlink', glob($carpeta . '/' . $base . '/*'));
    @rmdir($carpeta . '/' . $base);
}
array_map('unlink', glob($carpeta . '/*.*'));
rmdir($carpeta);

echo "\nResultado: $buenas correctas, $malas fallidas\n\n";
exit($malas > 0 ? 1 : 0);
