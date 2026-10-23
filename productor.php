<?php
// ============================================================
// PRODUCTOR (entrada asincrona, lado que envia)
//
// Uso:  php productor.php "{\"cliente\":\"Ana\",\"monto\":1000}"
//
// Publica el mensaje en la cola y termina. No espera ni conoce el
// resultado: lo procesa despues el consumidor (consumidor.php).
// ============================================================

require __DIR__ . '/main.php';

if ($argc < 2) {
    echo "Uso: php productor.php \"{\\\"cliente\\\":\\\"Ana\\\",\\\"monto\\\":1000}\"\n";
    exit(1);
}

$sistema = armarSistema();
$id = $sistema['cola']->publicar($argv[1]);

echo "Mensaje $id publicado en la cola. Pendientes: " . $sistema['cola']->pendientes() . "\n";
