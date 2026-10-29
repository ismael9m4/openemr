# Instancia de prueba OpenEMR en Portainer Community

Guía rápida para levantar la rama `codex/backup-add-edit-events` en Ubuntu con Portainer Community Edition, restaurar un dump SQL y recompilar los estilos. El Stack usa PHP 8.3 por defecto, MariaDB 10.11 y Twig 3.7.1 (fijado por `composer.lock`).

## Requisitos

- Portainer Community Edition conectado al Docker Standalone del servidor Ubuntu.
- Git instalado en el host Ubuntu (`git --version`). Si falta: `sudo apt update && sudo apt install -y git`.
- El dump SQL copiado al host donde corre Docker. No lo subas a GitHub; puede contener datos sensibles.
- Espacio libre en `/var/www`, que será usado para el checkout, la base y los datos persistentes.

## 1. Preparar las carpetas y el código

Usa `/var/www/openemr-test`, fuera de `/var/www/html` para que los backups no queden publicados por el servidor web:

```bash
sudo mkdir -p /var/www/openemr-test/backups
sudo git clone --branch codex/backup-add-edit-events --single-branch \
  https://github.com/ismael9m4/openemr.git \
  /var/www/openemr-test/openemr-code
```

Si `/var/www/openemr-test/openemr-code` ya existe y es un checkout Git, actualízalo en vez de volver a clonarlo:

```bash
sudo git -C /var/www/openemr-test/openemr-code pull --ff-only origin codex/backup-add-edit-events
```

Copia el dump SQL al servidor, por ejemplo a:

```text
/var/www/openemr-test/backups/openemr.sql
```

Comprueba que existen el código y el dump:

```bash
sudo test -f /var/www/openemr-test/openemr-code/composer.json && echo "Código listo"
sudo test -r /var/www/openemr-test/backups/openemr.sql && echo "Dump listo"
```

## 2. Crear el Stack en Portainer

1. En Portainer, elige el entorno Docker y abre **Stacks → Add stack → Git Repository**.
2. Configura:
   - **Name:** `openemr-testing`
   - **Repository URL:** `https://github.com/ismael9m4/openemr`
   - **Repository reference:** `refs/heads/codex/backup-add-edit-events`
   - **Compose path:** `docker/test-portainer/docker-compose.yml`
3. En **Environment variables**, agrega:

   | Variable | Valor |
   |---|---|
   | `OPENEMR_SOURCE_PATH` | `/var/www/openemr-test/openemr-code` |
   | `SQL_DUMP_HOST_PATH` | `/var/www/openemr-test/backups/openemr.sql` |
   | `TEST_DATA_ROOT` | `/var/www/openemr-test` |
   | `TEST_DB_ROOT_PASSWORD` | Contraseña temporal para MariaDB root |
   | `TEST_DB_PASSWORD` | Contraseña temporal para el usuario MariaDB `openemr` |
   | `TEST_OE_PASSWORD` | Contraseña de administrador para una instalación nueva |

   Opcionales:

   - `OPENEMR_IMAGE`: predeterminado `openemr/openemr:flex-3.24-php-8.3`.
   - `OPENEMR_HTTP_PORT`: predeterminado `8088`.
   - `OPENEMR_HTTPS_PORT`: predeterminado `8448`.

4. Despliega el Stack. La primera inicialización puede tardar varios minutos mientras se instalan Composer y los recursos de la interfaz.

## 3. Compilar los estilos la primera vez

Cuando los contenedores estén en ejecución, abre **Containers → contenedor `openemr` → Console**, selecciona `/bin/sh` y ejecuta:

```sh
cd /var/www/localhost/htdocs/openemr
npm install
npm run build
```

Esto instala la dependencia Sass de Select2 declarada en `package.json` y genera los CSS en `public/themes`. Recarga la página con `Ctrl+F5` después de que termine el build.

Abre:

```text
http://IP_DEL_SERVIDOR:8088
```

Usa el puerto HTTP configurado si cambiaste `OPENEMR_HTTP_PORT`. Como el SQL restaurado puede contener usuarios existentes, inicia sesión con las credenciales de esa base; `TEST_OE_PASSWORD` no cambia las contraseñas guardadas en el dump.

## Datos persistentes e importación

- MariaDB y los archivos persistentes quedan bajo `/var/www/openemr-test`.
- El dump `01-restore.sql` se importa solo cuando la carpeta `/var/www/openemr-test/database` está vacía.
- Un **Pull and redeploy** del Stack no debería borrar esa carpeta. No elimines `/var/www/openemr-test` ni la carpeta `database` si quieres conservar la instancia.
- Para restaurar otro dump, primero respalda la base actual. Luego detén el Stack y renombra la carpeta `database` (por ejemplo, a `database-anterior`) para que MariaDB inicialice una carpeta vacía al desplegar de nuevo. No borres el respaldo anterior hasta verificar la nueva restauración.
- Usa datos anonimizados para las pruebas.

## Actualizar el código o probar otra versión de PHP

En Portainer Community, el Stack monta el checkout absoluto del host. **Pull and redeploy actualiza el Compose y la imagen, pero no actualiza ese checkout de código.** Para traer cambios de la rama, ejecuta en Ubuntu:

```bash
sudo git -C /var/www/openemr-test/openemr-code pull --ff-only origin codex/backup-add-edit-events
```

Después, desde la consola del contenedor OpenEMR, si cambió `package.json` o algún SCSS, ejecuta `npm install && npm run build` y reinicia el contenedor si fuera necesario.

Para probar otra versión de PHP, cambia `OPENEMR_IMAGE` en las variables del Stack por una etiqueta `openemr/openemr:flex-...-php-...` compatible. Verifica la versión efectiva dentro del contenedor con `php -v`; la etiqueta de PHP 8.3 mantiene la rama principal, pero su parche puede ser distinto al PHP 8.3.6 de producción.
