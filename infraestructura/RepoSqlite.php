<?php
// ============================================================
// CAPA: INFRAESTRUCTURA (detalle que se puede cambiar)
//
// Que es:      una bodega REAL: guarda las facturas en SQLite (un
//              archivo .sqlite en la carpeta data/).
//              Cumple el contrato FacturaRepositorio.
// Detalle:     el numero de factura es el prefijo + el id de la fila
//              (FAC-1, FAC-2...).
// ============================================================

class RepoSqlite implements FacturaRepositorio
{
    private $bd;
    private $prefijo;

    public function __construct($archivo, $prefijo = 'FAC-')
    {
        // Crea la carpeta de datos si todavia no existe.
        if (!is_dir(dirname($archivo))) {
            mkdir(dirname($archivo), 0777, true);
        }

        $this->bd = new PDO('sqlite:' . $archivo);
        $this->bd->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->prefijo = $prefijo;

        $this->bd->exec(
            'CREATE TABLE IF NOT EXISTS facturas (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                cliente TEXT NOT NULL,
                subtotal REAL NOT NULL,
                iva REAL NOT NULL,
                total REAL NOT NULL
            )'
        );
    }

    public function guardar(Factura $factura)
    {
        $consulta = $this->bd->prepare(
            'INSERT INTO facturas (cliente, subtotal, iva, total) VALUES (?, ?, ?, ?)'
        );
        $consulta->execute([$factura->cliente, $factura->subtotal, $factura->iva, $factura->total]);

        return $this->prefijo . $this->bd->lastInsertId();
    }

    public function listar()
    {
        $filas = $this->bd->query('SELECT * FROM facturas ORDER BY id')->fetchAll(PDO::FETCH_ASSOC);

        $facturas = [];
        foreach ($filas as $fila) {
            $facturas[] = [
                'numero' => $this->prefijo . $fila['id'],
                'cliente' => $fila['cliente'],
                'subtotal' => $fila['subtotal'],
                'iva' => $fila['iva'],
                'total' => $fila['total'],
            ];
        }
        return $facturas;
    }
}
