# Instancia de prueba en Portainer

Este stack ejecuta el código de la rama Git desde el checkout local de Portainer, con MariaDB aislada y una copia SQL importada durante la primera inicialización de la base.

## Preparar Portainer

1. Copia el archivo `.sql` al host donde corre Docker. No lo subas al repositorio: puede contener datos sensibles. Por ejemplo, en un host Linux podrías guardarlo como `/srv/backups/openemr-test.sql`.
2. En Portainer, crea un **Stack** desde el repositorio `https://github.com/ismael9m4/openemr`, selecciona la rama `codex/backup-add-edit-events` y usa `docker/test-portainer/docker-compose.yml` como archivo Compose.
3. En las variables del Stack define:
   - `SQL_DUMP_HOST_PATH`: ruta absoluta al `.sql` vista desde el host de Docker (por ejemplo `/srv/backups/openemr-test.sql`).
   - `TEST_DB_ROOT_PASSWORD`: contraseña temporal para root de MariaDB.
   - `TEST_DB_PASSWORD`: contraseña temporal para el usuario de OpenEMR en MariaDB.
   - `TEST_OE_PASSWORD`: contraseña para el usuario administrador de OpenEMR.
   - `OPENEMR_HTTP_PORT` y `OPENEMR_HTTPS_PORT` (opcionales; predeterminados `8088` y `8448`).
   - `OPENEMR_IMAGE` (opcional; predeterminado `openemr/openemr:flex`). Permite seleccionar una imagen de desarrollo OpenEMR que use la versión de PHP que se quiere evaluar.
4. Despliega el Stack. Cuando los contenedores estén listos, abre `http://<host-de-docker>:8088` (o el puerto que configuraste).

## Importación y comparación de PHP

MariaDB solo ejecuta `01-restore.sql` cuando el volumen `test_database` está vacío. Comprueba que el dump sea compatible con MariaDB y que sus tablas correspondan a la base `openemr`. Para volver a importar el SQL, elimina el Stack y su volumen `test_database` desde Portainer y vuelve a desplegarlo; esto elimina los datos de prueba actuales.

Para comparar versiones de PHP, conserva una copia del volumen de base de datos y elige una etiqueta de imagen compatible mediante `OPENEMR_IMAGE`. Registra la etiqueta usada en cada prueba. Cambiar la imagen no modifica el SQL de respaldo. Confirma la versión efectiva de PHP en el contenedor antes de comparar resultados.

Los puertos de base de datos no se publican al host; la conexión de OpenEMR a MariaDB ocurre dentro de la red del Stack. Usa exclusivamente datos anonimizados para las pruebas.
