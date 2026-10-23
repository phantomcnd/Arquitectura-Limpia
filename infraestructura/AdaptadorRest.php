<?php
// ============================================================
// INFRAESTRUCTURA - entrada 1: API REST (sincrona)
//
// Recibe una peticion HTTP, convierte el JSON en un arreglo y se lo
// pasa al caso de uso. El cliente espera la respuesta: eso es lo sincrono.
// Solo traduce: aqui no hay reglas de negocio.
//
//   POST /facturas  -> crea una factura (201 creada, 422 rechazada)
//   GET  /facturas  -> lista las facturas guardadas (200)
// ============================================================

class AdaptadorRest
{
    private $crearFactura;
    private $listarFacturas;

    public function __construct(CrearFactura $crearFactura, ListarFacturas $listarFacturas)
    {
        $this->crearFactura = $crearFactura;
        $this->listarFacturas = $listarFacturas;
    }

    // Devuelve [codigo HTTP, respuesta]
    public function recibirPeticion($metodo, $ruta, $json = '')
    {
        if ($ruta != '/facturas') {
            return [404, ['exito' => false, 'motivo' => 'Ruta no encontrada']];
        }

        if ($metodo == 'GET') {
            return [200, $this->listarFacturas->ejecutar()];
        }

        if ($metodo == 'POST') {
            $datos = json_decode($json, true);
            $resultado = $this->crearFactura->ejecutar($datos);

            if ($resultado['exito']) {
                return [201, $resultado];
            }
            return [422, $resultado];
        }

        return [405, ['exito' => false, 'motivo' => 'Metodo no permitido']];
    }
}
