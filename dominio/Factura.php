<?php
// ============================================================
// CAPA: DOMINIO (el centro del sistema)
//
// Que es:      la factura y las reglas criticas del negocio
//              (control de fraude y politica fiscal).
// De que depende: de NADA de afuera. Solo PHP puro.
// Idea clave:  es imposible crear una Factura que rompa las reglas.
//              Si las reglas no se cumplen, el constructor lanza
//              una excepcion y la factura nunca existe.
// Parametros:  los valores (limite, IVA, lista negra) llegan en el
//              objeto Reglas; aqui solo esta la logica.
// ============================================================

// Error propio del negocio: "esta factura no se puede emitir".
class FacturaRechazada extends Exception
{
}

class Factura
{
    public $cliente;
    public $subtotal;
    public $iva;
    public $total;

    public function __construct($cliente, $monto, $exento, Reglas $reglas)
    {
        $this->revisarFraude($cliente, $monto, $reglas);

        $this->cliente = $cliente;
        $this->subtotal = $monto;
        $this->iva = $this->calcularIva($monto, $exento, $reglas);
        $this->total = round($monto + $this->iva, 2);
    }

    // Regla 1: control de fraude
    private function revisarFraude($cliente, $monto, Reglas $reglas)
    {
        if (!is_numeric($monto) || $monto <= 0) {
            throw new FacturaRechazada('El monto debe ser mayor a cero');
        }
        if ($monto > $reglas->montoMaximo) {
            throw new FacturaRechazada('Monto sospechoso: supera el limite permitido');
        }
        if (in_array($cliente, $reglas->clientesBloqueados)) {
            throw new FacturaRechazada('Cliente bloqueado por fraude');
        }
    }

    // Regla 2: politica fiscal
    private function calcularIva($monto, $exento, Reglas $reglas)
    {
        if ($exento) {
            return 0;
        }
        return round($monto * $reglas->iva, 2);
    }
}
