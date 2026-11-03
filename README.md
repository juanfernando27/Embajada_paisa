# Kit para desplegar un proyecto Laravel en Render

Con estos archivos usted puede desplegar **su proyecto Laravel** en [Render](https://render.com) **sin saber Docker**: los copia a la raíz de su proyecto, sube el proyecto a GitHub y crea el servicio en Render.

Render no tiene PHP de forma nativa, por eso se despliega con Docker. El `Dockerfile` de este kit hace todo el trabajo: instala PHP y Apache, instala las dependencias con Composer, compila el frontend (Vite) si su proyecto lo tiene, y al arrancar crea las tablas de la base de datos.

## Qué necesita

- Un proyecto **Laravel** que ya funcione en su computador (Laravel 12 o 13 probados; los anteriores deberían funcionar, pero no se probaron).
- Una cuenta de **GitHub** y una de **Render** (puede entrar a Render con GitHub).
- **Git** instalado.

## Los archivos del kit

Copie estos **cuatro** archivos a la **raíz de su proyecto** (la carpeta donde está `artisan`):

| Archivo | Para qué sirve |
|---|---|
| `Dockerfile` | Describe cómo construir y ejecutar su aplicación. |
| `docker/entrypoint.sh` | Se ejecuta al arrancar: configura el puerto, genera la clave si falta y corre las migraciones. (Cree la carpeta `docker` dentro de su proyecto.) |
| `.dockerignore` | Evita enviar al servidor archivos que no hacen falta (`vendor`, `node_modules`, su `.env`...). |
| `render.yaml` | Le dice a Render que cree el servicio web **y** una base de datos PostgreSQL, ya conectados. |

Su proyecto debe quedar así:

```
su-proyecto/
├── app/
├── artisan
├── composer.json
├── composer.lock
├── Dockerfile            ← nuevo
├── .dockerignore         ← nuevo
├── render.yaml           ← nuevo
└── docker/
    └── entrypoint.sh     ← nuevo
```

## Antes de empezar: revise esto

1. **Versión de PHP.** El `Dockerfile` usa PHP 8.4. Abra su `composer.json` y mire la línea `"php": "..."`. Si su proyecto necesita otra versión, cambie la primera línea del `Dockerfile` (`ARG PHP_VERSION=8.4`), por ejemplo a `8.3`.
2. **`composer.lock` debe estar en el repositorio** (no lo ponga en `.gitignore`). Así se instalan las mismas versiones que usted probó.
3. **No suba su `.env` ni la carpeta `vendor`.** El `.gitignore` de Laravel ya los excluye: no lo cambie. Los secretos se escriben en Render, no en el repositorio.
4. **La base de datos en Render es PostgreSQL.** Si en su computador usa MySQL o SQLite, sus migraciones deben servir también en PostgreSQL. Casi siempre funcionan sin cambios; fallan si usan SQL escrito a mano propio de MySQL.
5. **Nombres del `render.yaml`.** Cambie `mi-laravel` y `mi-laravel-db` por nombres suyos (en minúsculas, sin espacios). Si cambia el nombre de la base de datos, cámbielo también en `fromDatabase`.

## Probar en su computador (opcional)

Si tiene Docker Desktop, puede comprobar que la imagen funciona antes de subir nada:

```bash
docker build -t mi-laravel .
docker run --rm -p 8080:10000 mi-laravel
```

Abra <http://localhost:8080/>. Esta prueba usa SQLite, sin PostgreSQL.

## Subir el proyecto a GitHub

Si su proyecto ya está en un repositorio de GitHub, solo haga:

```bash
git add Dockerfile .dockerignore render.yaml docker
git commit -m "Agregar archivos de despliegue en Render"
git push
```

Si aún no tiene repositorio, cree uno en GitHub (vacío) y, desde la carpeta de su proyecto:

```bash
git init -b main
git add .
git commit -m "Proyecto Laravel"
git remote add origin <URL-DE-SU-REPOSITORIO>
git push -u origin main
```

## Desplegar en Render

1. **Genere la clave de la aplicación** (`APP_KEY`). En una terminal:

   ```bash
   echo "base64:$(openssl rand -base64 32)"
   ```

   Copie el resultado completo (empieza por `base64:`). *(En Windows, dentro de Git Bash funciona igual. Otra opción: `php artisan key:generate --show` en su proyecto.)*

2. Entre a [dashboard.render.com](https://dashboard.render.com), pulse **New +** y elija **Blueprint**.
3. Conecte su cuenta de GitHub si se lo pide y elija **su repositorio**. Render lee el `render.yaml`.
4. Render le pedirá el valor de **`APP_KEY`**: pegue la clave del paso 1. Pulse **Apply** / **Deploy**.
5. Espere. La primera vez tarda unos minutos porque construye la imagen. Abra la pestaña **Logs** del servicio web para ver el avance.
6. Cuando el servicio aparezca como **Live**, abra la URL que muestra Render (`https://...onrender.com`).

Cada vez que haga `git push` a la rama `main`, Render vuelve a desplegar solo.

### Cómo saber que salió bien

- En los **Logs** debe ver las migraciones: líneas con `create_..._table ... DONE`, y después Apache iniciando.
- La URL abre su aplicación. Pruebe crear, editar y eliminar datos; después haga un **Manual Deploy** y compruebe que los datos siguen ahí (porque están en PostgreSQL).

## Variables de entorno (opcionales)

Se cambian en el servicio, en **Environment**. Render vuelve a desplegar al guardarlas.

| Variable | Valor | Para qué |
|---|---|---|
| `APP_KEY` | `base64:...` | La clave de la aplicación (ya la definió al crear el Blueprint). |
| `APP_URL` | `https://su-servicio.onrender.com` | La dirección pública. Opcional: la aplicación arma sus enlaces con la petición. |
| `APP_DEBUG` | `false` | **No la ponga en `true` en producción**: muestra detalles internos. Úsela solo un momento para ver un error 500 y vuelva a ponerla en `false`. |
| `RUN_SEEDERS` | `true` | Ejecuta `php artisan db:seed` al arrancar (por defecto no se ejecuta). |
| `RUN_MIGRATIONS` | `false` | Evita que se ejecuten las migraciones al arrancar (por defecto sí se ejecutan). |

Otras variables que su proyecto necesite (correo, claves de APIs...) también se agregan ahí, igual que en su `.env`.

## Cosas que debe saber del plan gratuito

- El servicio se **"duerme"** tras unos 15 minutos sin visitas. La primera petición después tarda cerca de un minuto.
- La base de datos PostgreSQL **gratuita caduca** pasado un tiempo (hace poco eran unos 30 días): revise las condiciones vigentes en Render.
- Los **archivos que los usuarios suban** a la carpeta `storage` se pierden en cada despliegue o reinicio: el disco del plan gratuito es temporal.

## Si algo falla

| Síntoma | Qué hacer |
|---|---|
| El despliegue falla al construir, con un mensaje sobre `php >= 8.x` | Su `composer.lock` exige otra versión de PHP: cambie `ARG PHP_VERSION` en el `Dockerfile` (ver *Antes de empezar*). |
| `could not find driver` | Su `DB_CONNECTION` no es `pgsql` o falta `DB_URL`: revise que la base de datos del Blueprint esté creada y enlazada. |
| Error 500 al abrir la página | Ponga `APP_DEBUG=true` un momento, lea el error y vuelva a ponerlo en `false`. Mire también los **Logs**. |
| La página sale sin estilos | Su frontend no se compiló: revise que `package.json` tenga un script `"build"` y que `npm run build` funcione en su computador. |
| "No open ports detected" | No cambie el `ENTRYPOINT` ni `docker/entrypoint.sh`: son los que configuran el puerto de Render. |
| Los *seeders* fallan con `Call to undefined function fake()` | Los seeders que usan *factories* necesitan Faker en producción: ejecute `composer require fakerphp/faker` (lo mueve a `require`), haga commit de `composer.json` y `composer.lock` y vuelva a subir. |
| Las migraciones fallan en PostgreSQL | Alguna migración usa SQL propio de MySQL. Corríjala para que use el constructor de esquemas de Laravel. |
