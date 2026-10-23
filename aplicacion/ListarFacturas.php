<?php
// ============================================================
// CAPA: APLICACION
//
// Que es:      caso de uso para consultar las facturas guardadas.
//              Sirve para comprobar que de verdad quedaron en la base.
// De que depende: solo del contrato FacturaRepositorio.
// ============================================================

class ListarFacturas
{
    private $repositorio;

    public function __construct(FacturaRepositorio $repositorio)
    {
        $this->repositorio = $repositorio;
    }

    public function ejecutar()
    {
        return $this->repositorio->listar();
    }
}
