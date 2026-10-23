# Guía para entender y defender el proyecto

## La idea en una frase

El negocio (reglas de fraude e IVA) está en el centro y no conoce nada de afuera.
Todo lo de afuera (REST, broker, base de datos) se puede cambiar sin tocarlo, y los
**valores** de las reglas y la **base de datos elegida** se cambian editando JSON, no código.

Analogía del restaurante: el dominio son las reglas de la cocina, el caso de uso es el
cocinero, los adaptadores son el mesero (REST, responde ya) y el repartidor (broker, deja el
pedido en una cola y se va), y el repositorio es la bodega.

## Dónde está cada cosa

```
config/
  config.json               qué base de datos usar (sqlite, memoria, postgres, oracle) y en qué carpeta
  reglas.json               límite de fraude, clientes bloqueados, IVA
dominio/
  Factura.php               la lógica de las reglas (fraude + IVA)
  Reglas.php                los parámetros de las reglas (solo datos)
  FacturaRepositorio.php    el contrato: guardar() y listar()
aplicacion/
  CrearFactura.php          el único caso de uso para crear
  ListarFacturas.php        consulta las facturas guardadas
infraestructura/
  RepoSqlite.php            base REAL (archivo SQLite)
  RepoMemoria.php           base volátil (arreglo PHP)
  RepoPostgres.php          simulada: SQLite por debajo, numera PG-1...
  RepoOracle.php            simulada: SQLite por debajo, numera ORA-1...
  ColaSqlite.php            el "RabbitMQ" simulado (cola en SQLite)
  AdaptadorRest.php         entrada 1 (síncrona)
  AdaptadorBroker.php       entrada 2 (asíncrona)
main.php                    lee los JSON y arma todo (único que conoce todas las piezas)
api.php                     servidor web (php -S)
productor.php               publica un mensaje en la cola
consumidor.php              procesa los mensajes pendientes de la cola
prueba.php                  la demostración automática (15 verificaciones)
docs/
  informe.tex / informe.pdf el informe ejecutivo
  GUIA_DEFENSA.md           esta guía
data/                       los .sqlite que se generan al usar el sistema (no se sube a git)
```

## Cómo ejecutarlo

PHP de Laragon trae `pdo_sqlite` pero está desactivado en `php.ini`. Para no tocar tu
configuración global se activa por línea de comandos con `-d extension=pdo_sqlite`
(o quita el `;` de `;extension=pdo_sqlite` en `C:\laragon\bin\php\php-8.2.32\php.ini`
y ya no necesitas la opción).

```
cd C:\Users\Usuario\OneDrive\Desktop\8VO\ARUITECTURA\facturacion-limpia
$php = 'C:\laragon\bin\php\php-8.2.32\php.exe'

# Demostración automática
& $php -d extension=pdo_sqlite prueba.php
```

### Flujo síncrono (API REST)

Terminal 1:
```
& $php -d extension=pdo_sqlite -S localhost:8000 api.php
```
Terminal 2 (con `curl.exe`; en PowerShell 5.1 las comillas del JSON se complican, así que
guarda el JSON en un archivo):
```
Set-Content body.json '{"cliente":"Ana","monto":1000}' -NoNewline
curl.exe -i -X POST localhost:8000/facturas -d "@body.json"     # 201 y la factura
curl.exe localhost:8000/facturas                                 # lista lo guardado
```
Un cliente bloqueado o un monto alto devuelve **422** con el motivo.

### Flujo asíncrono (broker)

Con Git Bash (maneja bien las comillas):
```
php -d extension=pdo_sqlite productor.php '{"cliente":"Ana","monto":1000}'
php -d extension=pdo_sqlite productor.php '{"cliente":"Juan Estafador","monto":100}'
# ...el productor ya terminó, nadie espera. Los mensajes están en la cola.
php -d extension=pdo_sqlite consumidor.php
# procesa los pendientes: crea la factura y rechaza la del fraude
```
Las facturas de REST y del broker caen en la **misma** base (`data/facturas.sqlite`):
haz `GET /facturas` y verás ambas.

## Cómo demostrar en persona lo que pide el documento

### 1. Cambiar las reglas de negocio sin tocar código

Edita `config/reglas.json`, por ejemplo `"iva": 0.12` o `"monto_maximo": 500`, o agrega un
nombre a `clientes_bloqueados`. Manda otra factura por REST o por broker: ya usa los valores
nuevos (cada petición lee el JSON). **Ningún `.php` cambió.**

### 2. Cambiar de base de datos

Edita `config/config.json`: `"persistencia": "oracle"` (o `postgres`, `sqlite`, `memoria`).
Manda una factura: ahora se numera `ORA-1` con el mismo total. Dominio y aplicación no
cambiaron.

### 3. Cambiar el broker o la interfaz web

Es escribir un adaptador nuevo en `infraestructura/` que traduzca y llame a
`CrearFactura::ejecutar($datos)`. No hay nada que cambiar en dominio ni aplicación.

### Prueba de que no se tocó el núcleo

```
Get-FileHash dominio\*.php, aplicacion\*.php -Algorithm MD5
```
Se ejecuta antes y después de los cambios 1 y 2: las huellas son idénticas.
Además, la sección 5 de `prueba.php` verifica que el código de `dominio/` y `aplicacion/`
no menciona PDO, SQL, JSON, HTTP ni colas.

## Qué decir en la defensa

"Cambié las reglas editando `reglas.json` y la base de datos editando `config.json`. Dominio y
aplicación no cambiaron, y lo comprobé con las huellas de los archivos. Las dependencias
apuntan hacia adentro, así que lo de afuera se reemplaza sin tocar el negocio."

## Orden para leer el código (de adentro hacia afuera)

1. `Reglas.php` y `Factura.php`: los datos de las reglas y la lógica. El constructor lanza
   `FacturaRechazada` si algo no se cumple, así que una factura inválida no puede existir.
2. `FacturaRepositorio.php`: el contrato (guardar y listar).
3. `CrearFactura.php`: recibe un arreglo, crea la factura, la guarda y devuelve un arreglo.
4. `RepoSqlite.php` (y los demás repos): cumplen el contrato.
5. `AdaptadorRest.php` y `AdaptadorBroker.php`: traducen y llaman a `ejecutar`.
6. `main.php`: lee los JSON y arma las piezas.
7. `prueba.php`: la demostración.

## La regla que debes poder explicar

Las dependencias solo apuntan hacia adentro:

- `dominio/` no menciona REST, broker ni base de datos. Tampoco lee archivos: recibe `Reglas` ya armadas.
- `aplicacion/` solo conoce el dominio.
- `infraestructura/` conoce a los dos de adentro.
- Solo `main.php` conoce todo y es quien lee los JSON.

## Los dos puntos que el enunciado dice que se valoran

**Punto de entrada a la capa de aplicación:** `CrearFactura::ejecutar($datos)`.
Recibe un arreglo simple, no una petición HTTP ni un mensaje de cola. REST y broker son
adaptadores simétricos que lo llaman sin que él sepa quién fue.

**Contrato de persistencia:** la interfaz `FacturaRepositorio`. Está en el dominio: el negocio
dice qué necesita y la base de datos se adapta. Para pasar de SQLite a Postgres u Oracle (o a
memoria) solo se entrega otra clase que cumpla ese contrato, elegida en `config.json`.

## Preguntas probables y respuestas

**¿Por qué el caso de uso no usa directamente SQLite?**
Porque entonces el negocio dependería de una tecnología. Solo conoce la interfaz
`FacturaRepositorio`; quien decide la implementación es `main.php` según `config.json`.

**¿Dónde están las reglas de fraude?**
La lógica solo en `Factura.php`; los valores en `reglas.json`. Si estuvieran en el controlador
REST, el broker tendría que copiarlas y podrían quedar distintas.

**¿Si las reglas están en un JSON, el dominio no depende de un archivo?**
No: el dominio recibe un objeto `Reglas`. Quien lee el JSON es `main.php`, en infraestructura.
Mañana las reglas podrían venir de una tabla sin tocar el dominio.

**¿Qué es real y qué es simulado?**
Real: la persistencia en SQLite, el servidor HTTP (`php -S`) y la cola con su productor y
consumidor separados. Simulado: PostgreSQL y Oracle (usan SQLite por debajo) y RabbitMQ (la
cola es una tabla SQLite). El enunciado lo permite: lo evaluado es poder sustituir el adaptador.

**¿Cómo sé que REST y broker hacen lo mismo?**
Los dos terminan en `ejecutar($datos)`. La sección 3 de `prueba.php` lo demuestra: mismo total
y ambas facturas quedan en la misma base.

**¿Qué ganamos con esta arquitectura?**
Cambiar tecnología es barato, las reglas están en un solo lugar, se prueba sin base de datos
y no se duplica lógica entre entradas.

**¿Qué costó?**
Un poco más de estructura inicial: la interfaz, los adaptadores y el archivo de armado.

## Pendiente antes de entregar

- Escribir tu nombre en `docs/informe.tex` (línea `\author{...}`) y volver a compilar:
  `cd docs` y luego `pdflatex informe.tex`.
