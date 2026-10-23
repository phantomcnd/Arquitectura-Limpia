<?php
// ============================================================
// CAPA: INFRAESTRUCTURA (detalle que se puede cambiar)
//
// Que es:      una bodega volatil: guarda las facturas en un arreglo
//              de PHP y se pierde al terminar el programa.
//              Cumple el MISMO contrato que RepoSqlite.
// Para que:    probar la logica de negocio sin ningun motor de base de datos.
// ============================================================

class RepoMemoria implements FacturaRepositorio
{
    private $filas = [];

    public function guardar(Factura $factura)
    {
        $numero = 'MEM-' . (count($this->filas) + 1);
        $this->filas[] = [
            'numero' => $numero,
            'cliente' => $factura->cliente,
            'subtotal' => $factura->subtotal,
            'iva' => $factura->iva,
            'total' => $factura->total,
        ];
        return $numero;
    }

    public function listar()
    {
        return $this->filas;
    }
}
