# Bibliotech

Sistema de gestión de biblioteca en **PHP puro** (sin frameworks ni dependencias externas),
organizado con el patrón **MVC** y con un sistema de **migraciones** que refleja la base de
datos `db_libros`.

## Requisitos

- PHP 8.1 o superior (desarrollado y probado con PHP 8.2.12)
- MySQL 5.7+ o MariaDB 10.4+ (probado con MariaDB 10.4.32)
- Apache con `mod_rewrite` para las URLs limpias (XAMPP lo trae activado)

## Puesta en marcha

### 1. Configurar el entorno

```bash
cp .env.example .env
```

```ini
DB_DATABASE=db_libros
DB_USERNAME=root
DB_PASSWORD=

# Reglas de negocio
DIAS_PRESTAMO=7       # plazo por defecto al registrar un préstamo
MULTA_POR_DIA=1.50    # tarifa diaria de mora
MONEDA=S/
```

### 2. Poner al día las migraciones

Si la base `db_libros` **ya existe** con sus tablas:

```bash
php cli/migrate.php --baseline   # marca lo existente como aplicado
php cli/migrate.php              # aplica solo lo nuevo
```

Desde cero:

```sql
CREATE DATABASE db_libros CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

```bash
php cli/migrate.php
```

### 3. Contraseñas

Las contraseñas están cifradas con bcrypt y no se pueden leer. Para asignarlas:

```bash
php cli/usuario.php --listar                             # ver cuentas
php cli/usuario.php --resetear-todos password123         # poner una común
php cli/usuario.php --password admin@bibliotech.test MiClave123
php cli/usuario.php --crear nuevo@correo.test "Nombre" administrador Clave1234
```

> En este repositorio las 8 cuentas quedaron con la contraseña `password123`.
> **Cámbialas antes de entregar o publicar el proyecto.**

### 4. Levantar el servidor

```bash
php -S localhost:8000 server.php
```

Con Apache/XAMPP el proyecto puede vivir dentro de `htdocs`; los `.htaccess`
incluidos redirigen todo hacia `public/`.

## Roles y permisos

Cada rol declara sus acciones en `config/permisos.php`, y las rutas las exigen con
el filtro `permiso:modulo,accion`. Tener la matriz en un solo archivo permite
auditar de un vistazo quién puede hacer qué.

| Módulo | Administrador | Bibliotecario | Estudiante |
|---|---|---|---|
| Libros | ver, crear, editar, eliminar | ver, crear, editar, eliminar | ver |
| Categorías | ver, crear, editar, eliminar | ver, crear, editar, eliminar | ver |
| Usuarios | ver, crear, editar, eliminar | — | — |
| Préstamos | ver todos, crear, devolver | ver todos, crear, devolver | ver solo los propios |
| Multas | ver todas, pagar, condonar | ver todas, pagar, condonar | ver solo las propias |

El estudiante no puede ni siquiera abrir el detalle de un préstamo ajeno: el
controlador responde 403 aunque adivine el folio en la URL.

## Módulos

| Módulo | Rutas principales |
|---|---|
| Autenticación | `GET/POST /login`, `POST /logout` |
| Dashboard | `GET /` (el contenido cambia según el rol) |
| Libros (CRUD) | `/libros`, `/libros/crear`, `/libros/{id}`, `/libros/{id}/editar` |
| Categorías (CRUD) | `/categorias`, `/categorias/crear`, `/categorias/{id}/editar` |
| Usuarios (CRUD) | `/usuarios`, `/usuarios/crear`, `/usuarios/{id}/editar` |
| Préstamos | `/prestamos`, `/prestamos/crear`, `/prestamos/{id}`, `POST /prestamos/{id}/devolver` |
| Multas | `/multas`, `POST /multas/{id}/pagar`, `POST /multas/{id}/condonar` |
| API (ejemplo) | `GET /api/libros` (requiere sesión) |

## Préstamos y multas

### Registro de un préstamo

1. Se elige el usuario activo y un libro **con stock disponible** (el selector no
   ofrece títulos agotados).
2. La fecha de devolución se propone a `DIAS_PRESTAMO` días y se puede ajustar.
3. Al guardar, en **una sola transacción**, se inserta el préstamo y se descuenta
   un ejemplar del inventario.

El stock se lee con `SELECT ... FOR UPDATE`: si dos bibliotecarios intentan prestar
el último ejemplar a la vez, el segundo recibe un error en lugar de dejar el
inventario en negativo.

### Devolución y cálculo automático de la mora

Al registrar la devolución, también en una transacción:

1. Se marca el préstamo como `devuelto` con la fecha real.
2. Se repone el ejemplar (sin superar nunca `stock_total`).
3. Si la fecha real es posterior a la esperada, se calcula:
   `días de mora × MULTA_POR_DIA` y se crea la multa en estado `pendiente`.

Los días y la tarifa usada **se guardan** en la multa (`dias_mora`, `tarifa_diaria`),
así el cargo se puede auditar aunque la tarifa cambie después.

### Estados

- **Préstamo**: `activo` → `vencido` (al pasar la fecha) → `devuelto`. También `cancelado`.
- **Multa**: `pendiente` → `pagada` o `condonada`. Una multa cerrada no se puede
  volver a cobrar; el intento muestra "Esta multa ya fue pagada."

Los préstamos se marcan como `vencidos` automáticamente al entrar al panel, al
listar préstamos o al abrir una ficha, sin necesidad de un cron.

### Reglas de integridad de usuarios

- No se puede eliminar el usuario con el que se está navegando.
- No se puede eliminar un usuario con préstamos registrados (la clave foránea lo
  impide); se ofrece bloquearlo en su lugar.
- No se puede quitar el rol de administrador, bloquear ni eliminar al **último
  administrador activo**, para no dejar el sistema sin quien gestione cuentas.

## Estructura del proyecto

```
Bibliotech/
├── app/
│   ├── bootstrap.php             Inicialización: autoload, helpers y arranque
│   ├── helpers.php               Funciones globales (env, e, url, old, moneda, ...)
│   ├── Core/                     Núcleo del framework
│   │   ├── App.php               Bootstrap, configuración y manejo de errores
│   │   ├── Auth.php              Autenticación y consulta de permisos
│   │   ├── Autoloader.php        Autoload PSR-4 propio (spl_autoload_register)
│   │   ├── Controller.php        Controlador base (view, json, redirect, CSRF)
│   │   ├── Database.php          Conexión PDO única
│   │   ├── Env.php               Lector de archivos .env
│   │   ├── HttpException.php     Excepción con código de estado HTTP
│   │   ├── Middleware.php        Filtros de ruta: auth, invitado, rol, permiso
│   │   ├── Migrator.php          Motor de migraciones
│   │   ├── Model.php             Modelo base con CRUD
│   │   ├── Request.php           Petición HTTP entrante
│   │   ├── Router.php            Enrutador con parámetros y filtros
│   │   ├── Session.php           Sesión, mensajes flash y CSRF
│   │   └── View.php              Renderizador de plantillas con layout
│   ├── Controllers/              Auth, Dashboard, Libro, Categoria, Usuario,
│   │                             Prestamo y Multa
│   ├── Models/                   Libro, Categoria, Usuario, Prestamo, Multa
│   └── Views/                    layouts/, auth/, dashboard/, libros/,
│                                 categorias/, usuarios/, prestamos/, multas/,
│                                 errors/ (403, 404, 419, 500)
├── cli/
│   ├── migrate.php               Migraciones
│   └── usuario.php               Alta de usuarios y gestión de contraseñas
├── config/
│   ├── app.php                   Configuración general
│   ├── biblioteca.php            Reglas de negocio (plazo, tarifa, moneda)
│   ├── database.php              Conexión
│   └── permisos.php              Matriz de permisos por rol
├── database/migrations/          Migraciones en orden cronológico
├── public/                       ÚNICO directorio accesible desde el navegador
├── routes/web.php                Rutas con sus filtros
├── storage/                      Registros y caché
└── server.php                    Router para el servidor embebido de PHP
```

## Cómo funciona el MVC aquí

1. Todas las peticiones entran por `public/index.php` (front controller).
2. `App::boot()` carga el `.env`, la configuración, la sesión y el manejo de errores.
3. `Router` busca la ruta, ejecuta sus **filtros** y recién entonces instancia el
   controlador.
4. El **controlador** pide datos al **modelo** y los entrega a la **vista**.
5. `View::render()` ejecuta la plantilla y la inserta en el layout como `$content`.

Las rutas declaran sus filtros en el tercer argumento:

```php
$router->get('/usuarios', 'UsuarioController@index', ['permiso:usuarios,ver']);
$router->post('/libros/{id}', 'LibroController@update', ['permiso:libros,editar']);
```

Si la URL existe con otro método HTTP responde **405**; si no existe, **404**; si
falta sesión, redirige al login; si falta permiso, **403**.

## Migraciones

Cada migración es un archivo PHP en `database/migrations/` que devuelve sus
sentencias de ida y vuelta:

```php
return [
    'up'   => ['ALTER TABLE ...'],
    'down' => ['ALTER TABLE ...'],
];
```

```bash
php cli/migrate.php              # aplica pendientes
php cli/migrate.php --status     # qué está aplicado y qué falta
php cli/migrate.php --rollback   # revierte el último lote
php cli/migrate.php --baseline   # marca todo como aplicado sin ejecutar
```

| Migración | Contenido |
|---|---|
| `..._000100` | `categorias` |
| `..._000200` | `usuarios` |
| `..._000300` | `libros` |
| `..._000400` | `reservas` |
| `..._000500` | `prestamos` |
| `..._000600` | `multas` |
| `..._000700` | añade `dias_mora` y `tarifa_diaria` a `multas` y rellena las existentes |

El orden de los archivos define el orden de ejecución, por eso el prefijo de fecha.
Las migraciones base usan `CREATE TABLE IF NOT EXISTS`, así que son seguras sobre
una base que ya tiene las tablas.

## Notas sobre el esquema

- En `libros`, el autor se guarda como texto (`autor`), no como clave foránea.
- La tabla `libros` conserva la collation `utf8mb4_general_ci` del esquema original,
  distinta del resto. Conviene unificarla: un JOIN que compare columnas de texto
  entre `libros` y otra tabla fallaría.
- `reservas` existe y tiene datos, pero **no forma parte de los módulos
  implementados**; el sistema no la usa.
- La tabla `migraciones` la crea el propio motor en su primera ejecución.

## Seguridad aplicada

- **PDO con consultas preparadas** en todas las consultas.
- **Contraseñas con `password_hash()`** (bcrypt) y `password_verify()`. El login
  ejecuta siempre la verificación, aunque el correo no exista, para no revelar qué
  cuentas están registradas.
- **Escape de salida** con `e()` en todas las vistas.
- **Token CSRF** obligatorio en cada formulario POST; si falta o no coincide responde 419.
- **Autorización por rol en la ruta**, no en la vista: ocultar un enlace no protege
  nada, por eso cada ruta valida su permiso antes de llegar al controlador.
- **Carpetas protegidas**: `app/`, `config/`, `database/`, `routes/`, `storage/` y
  `cli/` incluyen un `.htaccess` que niega el acceso web.
- **`APP_DEBUG=false`** en producción oculta el detalle técnico y registra los errores 5xx.

## Verificación

```bash
# Sintaxis de todos los archivos PHP
find app config routes public cli database -name '*.php' -exec php -l {} \;

# Estado de las migraciones
php cli/migrate.php --status

# Levantar
php -S localhost:8000 server.php
```

## Pendientes sugeridos

- Módulo de reservas (la tabla ya existe).
- Recuperar la contraseña por correo en lugar del comando de consola.
- Historial de quién registró cada préstamo y cada cobro.
- Unificar la collation de `libros` con el resto de las tablas.
