<?php
// ============================================================
// CAPA: INFRAESTRUCTURA
//
// Que es:      un broker de mensajes SIMULADO (haria el papel de RabbitMQ).
//              Es una cola guardada en SQLite: quien publica deja el
//              mensaje y se va; otro proceso (el consumidor) lo toma
//              despues. Eso es lo asincrono.
// No tiene nada de negocio: solo guarda y entrega textos (JSON).
// ============================================================

class ColaSqlite
{
    private $bd;

    public function __construct($archivo)
    {
        // Crea la carpeta de datos si todavia no existe.
        if (!is_dir(dirname($archivo))) {
            mkdir(dirname($archivo), 0777, true);
        }

        $this->bd = new PDO('sqlite:' . $archivo);
        $this->bd->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->bd->exec(
            'CREATE TABLE IF NOT EXISTS mensajes (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                cuerpo TEXT NOT NULL,
                estado TEXT NOT NULL DEFAULT "pendiente",
                resultado TEXT
            )'
        );
    }

    // El productor deja el mensaje y devuelve su id sin esperar respuesta.
    public function publicar($json)
    {
        $consulta = $this->bd->prepare('INSERT INTO mensajes (cuerpo) VALUES (?)');
        $consulta->execute([$json]);
        return (int) $this->bd->lastInsertId();
    }

    // El consumidor pide el siguiente mensaje pendiente (o null si no hay).
    public function siguiente()
    {
        $fila = $this->bd->query(
            'SELECT id, cuerpo FROM mensajes WHERE estado = "pendiente" ORDER BY id LIMIT 1'
        )->fetch(PDO::FETCH_ASSOC);

        if ($fila == false) {
            return null;
        }
        return $fila;
    }

    // El consumidor confirma que lo proceso y deja el resultado.
    public function marcarProcesado($id, $resultado)
    {
        $consulta = $this->bd->prepare('UPDATE mensajes SET estado = "procesado", resultado = ? WHERE id = ?');
        $consulta->execute([json_encode($resultado), $id]);
    }

    public function pendientes()
    {
        return (int) $this->bd->query('SELECT COUNT(*) FROM mensajes WHERE estado = "pendiente"')->fetchColumn();
    }
}
