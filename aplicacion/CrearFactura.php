<?php
// ============================================================
// CAPA: APLICACION
//
// Que es:      el UNICO caso de uso para crear facturas.
//              Es el punto de entrada comun: lo usan todas las entradas
//              de la capa de infraestructura.
// De que depende: solo del dominio (Factura, Reglas y FacturaRepositorio).
// Lo que NO sabe: quien lo llamo ni donde se guarda la factura.
//              Solo conoce el contrato FacturaRepositorio.
// Entrada:     un arreglo con cliente, monto y exento.
// Salida:      un arreglo con el resultado.
// ============================================================

class CrearFactura
{
    private $repositorio;
    private $reglas;

    // Recibe "una bodega" cualquiera que cumpla el contrato y las reglas vigentes.
    public function __construct(FacturaRepositorio $repositorio, Reglas $reglas)
    {
        $this->repositorio = $repositorio;
        $this->reglas = $reglas;
    }

    public function ejecutar($datos)
    {
        if (!is_array($datos) || !isset($datos['cliente']) || !isset($datos['monto'])) {
            return ['exito' => false, 'motivo' => 'Faltan datos: cliente y monto'];
        }
        $exento = isset($datos['exento']) ? $datos['exento'] : false;

        // Si las reglas del dominio no se cumplen, se lanza FacturaRechazada.
        try {
            $factura = new Factura($datos['cliente'], $datos['monto'], $exento, $this->reglas);
        } catch (FacturaRechazada $error) {
            return ['exito' => false, 'motivo' => $error->getMessage()];
        }

        $numero = $this->repositorio->guardar($factura);

        return [
            'exito' => true,
            'numero' => $numero,
            'cliente' => $factura->cliente,
            'subtotal' => $factura->subtotal,
            'iva' => $factura->iva,
            'total' => $factura->total,
        ];
    }
}
