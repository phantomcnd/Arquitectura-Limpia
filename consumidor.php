<?php
// ============================================================
// CONSUMIDOR (entrada asincrona, lado que recibe)
//
// Uso:  php consumidor.php
//
// Procesa los mensajes pendientes de la cola con el mismo caso de uso
// que la API REST y termina. Con RabbitMQ real seria un proceso que
// se queda escuchando; aqui se ejecuta cuando se quiere vaciar la cola.
// ============================================================

require __DIR__ . '/main.php';

$sistema = armarSistema();
$resultados = $sistema['broker']->procesarPendientes();

if (count($resultados) == 0) {
    echo "No hay mensajes pendientes.\n";
}
foreach ($resultados as $r) {
    $res = $r['resultado'];
    if ($res['exito']) {
        echo "Mensaje " . $r['mensaje'] . " -> factura " . $res['numero'] . ", total " . $res['total'] . "\n";
    } else {
        echo "Mensaje " . $r['mensaje'] . " -> RECHAZADO: " . $res['motivo'] . "\n";
    }
}
