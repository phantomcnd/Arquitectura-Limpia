<?php
// ============================================================
// CAPA: INFRAESTRUCTURA (detalle que se puede cambiar)
//
// Que es:      la bodega "PostgreSQL" SIMULADA. El enunciado permite usar
//              una base embebida en lugar de la real, asi que por debajo
//              usa SQLite (en su propio archivo) y numera PG-1, PG-2...
// En un proyecto real: esta clase usaria PDO('pgsql:...') y nada mas
//              cambiaria en el sistema.
// ============================================================

class RepoPostgres extends RepoSqlite
{
    public function __construct($archivo)
    {
        parent::__construct($archivo, 'PG-');
    }
}
