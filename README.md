# Bibliotech

Sistema de gestión de biblioteca en **PHP puro** (sin frameworks ni dependencias externas)
organizado con el patrón **MVC** y con un sistema de **migraciones** que refleja la base de
datos `db_libros`.

## Requisitos

- PHP 8.1 o superior (desarrollado y probado con PHP 8.2.12)
- MySQL 5.7+ o MariaDB 10.4+ (probado con MariaDB 10.4.32)
- Apache con `mod_rewrite` para las URLs limpias (XAMPP lo trae activado)

## Puesta en marcha

### 1. Configurar el entorno

Copia la plantilla y ajusta los datos de tu conexión:

```bash
cp .env.example .env
```

```ini
DB_DATABASE=db_libros
DB_USERNAME=root
DB_PASSWORD=
```

### 2. Poner al día las migraciones

Si la base de datos `db_libros` **ya existe** con sus tablas (como en este proyecto),
basta con registrar que las migraciones están aplicadas sin volver a crearlas:

```bash
php cli/migrate.php --baseline
```

Si en cambio partes de una base vacía, crea la base y ejecuta las migraciones:

```sql
CREATE DATABASE db_libros CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

```bash
php cli/migrate.php
```

### 3. Levantar el servidor

Con el servidor embebido de PHP (la forma más rápida de probar):

```bash
php -S localhost:8000 server.php
```

Luego abre <http://localhost:8000>.

Con Apache/XAMPP puedes colocar el proyecto dentro de `htdocs`. Los archivos
`.htaccess` incluidos redirigen todo hacia `public/`, así que funciona tanto en la raíz
del dominio como dentro de una subcarpeta.

## Estructura del proyecto

```
Bibliotech/
├── app/                          Lógica de la aplicación
│   ├── bootstrap.php             Inicialización: autoload, helpers y arranque
│   ├── helpers.php               Funciones globales (env, e, url, old, ...)
│   ├── Core/                     Núcleo del framework
│   │   ├── App.php               Bootstrap, configuración y manejo de errores
│   │   ├── Autoloader.php        Autoload PSR-4 propio (spl_autoload_register)
│   │   ├── Controller.php        Controlador base (view, json, redirect, CSRF)
│   │   ├── Database.php          Conexión PDO única
│   │   ├── Env.php               Lector de archivos .env
│   │   ├── HttpException.php     Excepción con código de estado HTTP
│   │   ├── Migrator.php          Motor de migraciones
│   │   ├── Model.php             Modelo base con CRUD
│   │   ├── Request.php           Petición HTTP entrante
│   │   ├── Router.php            Enrutador con parámetros {id}
│   │   ├── Session.php           Sesión, mensajes flash y CSRF
│   │   └── View.php              Renderizador de plantillas con layout
│   ├── Controllers/              Controladores (capa C)
│   ├── Models/                   Modelos (capa M)
│   └── Views/                    Vistas (capa V)
│       ├── layouts/main.php      Layout principal
│       ├── home/                 Panel de inicio
│       ├── libros/               Catálogo: lista, ficha y formularios
│       └── errors/               Páginas 404, 419 y 500
├── cli/
│   └── migrate.php               Comando de migraciones
├── config/
│   ├── app.php                   Configuración general
│   └── database.php              Configuración de la conexión
├── database/
│   └── migrations/               Migraciones en orden cronológico
├── public/                       ÚNICO directorio accesible desde el navegador
│   ├── index.php                 Front controller
│   ├── .htaccess                 URLs limpias
│   └── assets/                   CSS, JS e imágenes
├── routes/
│   └── web.php                   Definición de rutas
├── storage/                      Registros y caché
└── server.php                    Router para el servidor embebido de PHP
```

## Cómo funciona el MVC aquí

1. Todas las peticiones entran por `public/index.php` (front controller).
2. `App::boot()` carga el `.env`, la configuración, la sesión y el manejador de errores.
3. `Router` compara la URL con las rutas de `routes/web.php` y resuelve el controlador.
4. El **controlador** pide datos al **modelo** y los entrega a la **vista**.
5. `View::render()` ejecuta la plantilla y la inserta dentro del layout como `$content`.

Las rutas se declaran así, con parámetros entre llaves:

```php
$router->get('/libros', 'LibroController@index');
$router->get('/libros/{id}', 'LibroController@show');
$router->post('/libros/{id}', 'LibroController@update');
```

Si la URL existe pero con otro método HTTP, el enrutador responde **405**; si no existe
ninguna ruta, responde **404** con la vista `errors/404.php`.

## Migraciones

Cada migración es un archivo PHP en `database/migrations/` que devuelve un arreglo con
las sentencias de ida y vuelta:

```php
<?php

return [
    'up'   => ['CREATE TABLE ...'],
    'down' => ['DROP TABLE IF EXISTS ...'],
];
```

El nombre del archivo (sin `.php`) identifica la migración y se guarda en la tabla
`migraciones` junto con el lote en que se ejecutó.

### Comandos

```bash
php cli/migrate.php              # Aplica las migraciones pendientes
php cli/migrate.php --status     # Muestra qué está aplicado y qué falta
php cli/migrate.php --rollback   # Revierte el último lote (lote 1 en adelante)
php cli/migrate.php --baseline   # Marca todo como aplicado sin ejecutar nada
```

### Migraciones incluidas

| Migración | Tabla | Depende de |
|---|---|---|
| `..._000100_crear_tabla_categorias` | `categorias` | — |
| `..._000200_crear_tabla_usuarios` | `usuarios` | — |
| `..._000300_crear_tabla_libros` | `libros` | `categorias` |
| `..._000400_crear_tabla_reservas` | `reservas` | `usuarios`, `libros` |
| `..._000500_crear_tabla_prestamos` | `prestamos` | `usuarios`, `libros`, `reservas` |
| `..._000600_crear_tabla_multas` | `multas` | `prestamos` |

El orden de los archivos define el orden de ejecución, por eso el prefijo de fecha.

### Notas sobre el esquema

- Todas las migraciones usan `CREATE TABLE IF NOT EXISTS`, de modo que son seguras
  sobre una base que ya tiene las tablas: no borran ni modifican datos existentes.
- En `libros`, el autor se guarda como texto (`autor`), no como clave foránea.
- La tabla `libros` conserva la collation `utf8mb4_general_ci` del esquema original,
  distinta del resto (`utf8mb4_unicode_ci`).
- La tabla `migraciones` la crea el propio motor la primera vez que se ejecuta.
- Las migraciones marcadas con `--baseline` quedan en el **lote 0**, así que
  `--rollback` nunca las deshace.

## Rutas disponibles

| Método | Ruta | Acción |
|---|---|---|
| GET | `/` | Panel con indicadores del catálogo |
| GET | `/libros` | Catálogo con buscador |
| GET | `/libros/crear` | Formulario de registro |
| POST | `/libros` | Guarda el libro |
| GET | `/libros/{id}` | Ficha del libro |
| GET | `/libros/{id}/editar` | Formulario de edición |
| POST | `/libros/{id}` | Guarda los cambios |
| POST | `/libros/{id}/eliminar` | Elimina el libro |
| GET | `/api/libros` | Listado en JSON (admite `?buscar=`) |

## Seguridad aplicada

- **PDO con consultas preparadas** en todas las consultas (sin concatenar SQL del usuario).
- **Escape de salida** con la función `e()` en todas las vistas.
- **Token CSRF** obligatorio en cada formulario POST; si falta o no coincide responde 419.
- **Validación del lado del servidor** con mensajes por campo y conservación de lo escrito.
- **Carpetas protegidas**: `app/`, `config/`, `database/`, `routes/`, `storage/` y `cli/`
  incluyen un `.htaccess` que niega el acceso web, y el front controller vive en `public/`.
- **`APP_DEBUG=false`** en producción oculta el detalle técnico y registra los errores 5xx.

## Verificación rápida

```bash
# Sintaxis de todos los archivos PHP
find app config routes public cli database -name '*.php' -exec php -l {} \;

# Estado de las migraciones
php cli/migrate.php --status

# Levantar y probar
php -S localhost:8000 server.php
# -> http://localhost:8000/libros
# -> http://localhost:8000/api/libros
```

## Próximos pasos sugeridos

- Autenticación con la tabla `usuarios` (login, roles y contraseñas con `password_hash`).
- Módulos de préstamos, reservas y multas usando los modelos correspondientes.
- Actualización automática del `stock_disponible` al registrar devoluciones.
