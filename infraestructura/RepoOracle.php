<?php
// ============================================================
// CAPA: INFRAESTRUCTURA (detalle que se puede cambiar)
//
// Que es:      la bodega "Oracle" SIMULADA. Igual que RepoPostgres:
//              por debajo usa SQLite (en su propio archivo) y numera
//              ORA-1, ORA-2...
// En un proyecto real: esta clase usaria PDO('oci:...') y nada mas
//              cambiaria en el sistema.
// ============================================================

class RepoOracle extends RepoSqlite
{
    public function __construct($archivo)
    {
        parent::__construct($archivo, 'ORA-');
    }
}
