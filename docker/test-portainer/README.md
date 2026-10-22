# Instancia de prueba en Portainer

Este stack ejecuta el checkout local del código, con MariaDB aislada y una copia SQL importada durante la primera inicialización de la base. En Portainer Community Edition, el código se clona por separado en el host y el Stack Git usa su ruta absoluta; los montajes relativos desde un Stack Git requieren Business Edition.

## Preparar Portainer

1. En el host Ubuntu, clona la rama del código en `/var/www/openemr-test/source`:
   ```bash
   sudo git clone --branch codex/backup-add-edit-events --single-branch https://github.com/ismael9m4/openemr.git /var/www/openemr-test/source
   ```
2. En el host donde corre Docker, crea `/var/www/openemr-test/backups` y guarda allí el `.sql`. Esta ruta aprovecha la partición grande `/var/www`, pero queda fuera de `/var/www/html` (la carpeta que normalmente publica el servidor web). No subas el dump al repositorio: puede contener datos sensibles.
3. En Portainer, crea o actualiza un Stack desde el repositorio `https://github.com/ismael9m4/openemr`, rama `codex/backup-add-edit-events`, con Compose path `docker/test-portainer/docker-compose.yml`.
4. En las variables del Stack define:
   - `OPENEMR_SOURCE_PATH`: `/var/www/openemr-test/source`.
   - `SQL_DUMP_HOST_PATH`: ruta absoluta al `.sql` vista desde el host de Docker (por ejemplo `/var/www/openemr-test/backups/openemr.sql`).
   - `TEST_DATA_ROOT` (opcional; predeterminado `/var/www/openemr-test`): carpeta donde se guardan MariaDB y los archivos persistentes de OpenEMR. Debe estar en un filesystem con espacio suficiente.
   - `TEST_DB_ROOT_PASSWORD`: contraseña temporal para root de MariaDB.
   - `TEST_DB_PASSWORD`: contraseña temporal para el usuario de OpenEMR en MariaDB.
   - `TEST_OE_PASSWORD`: contraseña para el usuario administrador de OpenEMR.
   - `OPENEMR_HTTP_PORT` y `OPENEMR_HTTPS_PORT` (opcionales; predeterminados `8088` y `8448`).
   - `OPENEMR_IMAGE` (opcional; predeterminado `openemr/openemr:flex`). Permite seleccionar una imagen de desarrollo OpenEMR que use la versión de PHP que se quiere evaluar.
5. Despliega el Stack. Cuando los contenedores estén listos, abre `http://<host-de-docker>:8088` (o el puerto que configuraste).

## Importación y comparación de PHP

MariaDB solo ejecuta `01-restore.sql` cuando la carpeta `database` está vacía. Comprueba que el dump sea compatible con MariaDB y que sus tablas correspondan a la base `openemr`. Para volver a importar el SQL, detén el Stack y elimina la carpeta `database` bajo `TEST_DATA_ROOT` antes de volver a desplegarlo; esto elimina los datos de prueba actuales.

Para actualizar el código desde Git, ejecuta `sudo git -C /var/www/openemr-test/source pull` en el host y luego recrea el contenedor OpenEMR desde Portainer. Para comparar versiones de PHP, conserva una copia de los datos bajo `TEST_DATA_ROOT` y elige una etiqueta de imagen compatible mediante `OPENEMR_IMAGE`. Registra la etiqueta usada en cada prueba. Cambiar la imagen no modifica el SQL de respaldo. Confirma la versión efectiva de PHP en el contenedor antes de comparar resultados.

Los puertos de base de datos no se publican al host; la conexión de OpenEMR a MariaDB ocurre dentro de la red del Stack. Usa exclusivamente datos anonimizados para las pruebas.
