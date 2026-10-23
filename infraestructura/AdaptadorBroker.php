<?php
// ============================================================
// INFRAESTRUCTURA - entrada 2: broker de mensajes (tipo RabbitMQ)
//
// Toma mensajes de la cola, convierte cada JSON en un arreglo y se lo
// pasa al MISMO caso de uso que usa la API REST.
// Solo traduce: aqui no hay reglas de negocio.
//
// Es asincrono: quien publico el mensaje ya se fue; este adaptador
// lo procesa cuando el consumidor (consumidor.php) se ejecuta.
// Con RabbitMQ real, recibirMensaje seria el metodo que se ejecuta
// cada vez que llega un mensaje. El negocio no cambiaria.
// ============================================================

class AdaptadorBroker
{
    private $crearFactura;
    private $cola;

    public function __construct(CrearFactura $crearFactura, ColaSqlite $cola)
    {
        $this->crearFactura = $crearFactura;
        $this->cola = $cola;
    }

    // Procesa UN mensaje (JSON) y devuelve el resultado.
    public function recibirMensaje($json)
    {
        $datos = json_decode($json, true);

        return $this->crearFactura->ejecutar($datos);
    }

    // Vacia la cola: procesa todos los mensajes pendientes.
    // Devuelve la lista de resultados, en el orden en que se procesaron.
    public function procesarPendientes()
    {
        $resultados = [];
        $mensaje = $this->cola->siguiente();

        while ($mensaje != null) {
            $resultado = $this->recibirMensaje($mensaje['cuerpo']);
            $this->cola->marcarProcesado($mensaje['id'], $resultado);
            $resultados[] = ['mensaje' => $mensaje['id'], 'resultado' => $resultado];

            $mensaje = $this->cola->siguiente();
        }
        return $resultados;
    }
}
