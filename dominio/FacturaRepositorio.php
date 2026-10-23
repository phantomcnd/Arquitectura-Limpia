<?php
// ============================================================
// CAPA: DOMINIO
//
// Que es:      el CONTRATO de persistencia. Dice QUE necesita el
//              negocio ("guardar" y "listar" facturas), no COMO se hace.
// Quien lo cumple: las clases de la capa de infraestructura.
// Idea clave:  el dominio es el dueno del contrato. La base de datos
//              se adapta a el, nunca al reves.
// ============================================================

interface FacturaRepositorio
{
    // Guarda la factura y devuelve su numero.
    public function guardar(Factura $factura);

    // Devuelve todas las facturas guardadas (arreglos con numero, cliente, subtotal, iva, total).
    public function listar();
}
