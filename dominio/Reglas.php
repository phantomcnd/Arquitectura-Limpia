<?php
// ============================================================
// CAPA: DOMINIO
//
// Que es:      los PARAMETROS de las reglas del negocio (limite de
//              fraude, clientes bloqueados, porcentaje de IVA).
// Idea clave:  las reglas (el "como") viven en Factura.php; aqui solo
//              estan los numeros y listas que las alimentan. Asi se
//              pueden ajustar sin editar codigo.
// De donde salen: el dominio NO lee archivos. Quien las arma (main.php,
//              leyendo config/reglas.json) se las entrega ya listas.
// ============================================================

class Reglas
{
    public $montoMaximo;
    public $clientesBloqueados;
    public $iva;

    public function __construct($montoMaximo, $clientesBloqueados, $iva)
    {
        $this->montoMaximo = $montoMaximo;
        $this->clientesBloqueados = $clientesBloqueados;
        $this->iva = $iva;
    }
}
