# MediPlan API

API construida con **Laravel 12** y autenticación OAuth2 con **Laravel Passport**.

Módulos incluidos por el momento:

- **Registro** de usuarios (`POST /api/register`)
- **Login** (`POST /api/login`)
- **Logout** (`POST /api/logout`)
- **Usuario autenticado** (`GET /api/me`)
- **Roles**: cliente, negocio y administrador
- **Documentación Swagger/OpenAPI** (`GET /api/documentation` y `GET /docs`)

> Convención del proyecto: la **base de datos está en inglés** (`roles`, `role_user`, `client`, `business`, `admin`) y todos los **mensajes, errores y validaciones están en español**.

---

## Requisitos

- PHP 8.2 o superior (extensiones: `openssl`, `pdo`, `mbstring`, `tokenizer`, `xml`, `ctype`, `json`, `bcmath`)
- Composer 2
- SQLite (desarrollo) o MySQL 8 (producción)
- OpenSSL (para generar las llaves de Passport)

---

## Instalación

```bash
# 1. Instalar dependencias
composer install

# 2. Crear el archivo de entorno
cp .env.example .env

# 3. Generar la llave de la aplicación
php artisan key:generate

# 4. Crear la base de datos (solo SQLite)
touch database/database.sqlite

# 5. Ejecutar migraciones (incluye tablas de usuarios, roles y Passport)
php artisan migrate

# 6. Generar las llaves de cifrado de Passport
php artisan passport:keys

# 7. Crear el cliente de "personal access" (necesario para emitir tokens)
php artisan passport:client --personal --name="MediPlan" --provider=users

# 8. Poblar roles y usuarios iniciales
php artisan db:seed

# 9. Generar el documento OpenAPI que consume Swagger UI
php artisan l5-swagger:generate

# 10. Levantar el servidor
php artisan serve
```

> Las migraciones de Passport (`oauth_*`) ya vienen incluidas en `database/migrations`, por eso **no** es necesario ejecutar `passport:install`.

### Usuarios de prueba (creados por el seeder)

| Rol           | Email                | Contraseña |
|---------------|----------------------|------------|
| Administrador | admin@mediplan.com   | password   |
| Cliente       | cliente@mediplan.com | password   |
| Negocio       | negocio@mediplan.com | password   |

### Usar MySQL en lugar de SQLite

Edita tu `.env`:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=mediplan
DB_USERNAME=root
DB_PASSWORD=secreto
```

---

## Documentación Swagger / OpenAPI

La API publica una especificación **OpenAPI 3.0.0** generada con `darkaonline/l5-swagger`. La especificación contiene las rutas, los campos obligatorios, formatos, valores permitidos, ejemplos, respuestas de error y el esquema de autenticación Bearer.

### URLs disponibles

Con el servidor levantado en `http://localhost:8000`:

| Recurso | URL | Uso |
|----------|-----|-----|
| Swagger UI | `http://localhost:8000/api/documentation` | Explorar endpoints y ejecutar solicitudes desde el navegador. |
| Especificación JSON | `http://localhost:8000/docs` | Importar en otro servicio, Postman, Insomnia, Redoc o un generador de SDK/frontend. |
| Especificación YAML | `storage/api-docs/api-docs.yaml` | Archivo generado si `L5_SWAGGER_GENERATE_YAML_COPY=true`; útil para versionarlo o consumirlo desde CI. |

El archivo JSON/YAML se genera con:

```bash
php artisan l5-swagger:generate
```

Después de cambiar una anotación o un contrato, vuelve a ejecutar el comando. En desarrollo se puede regenerar automáticamente en cada visita con `L5_SWAGGER_GENERATE_ALWAYS=true`; no se recomienda activarlo en producción. El directorio `storage/api-docs` debe ser escribible por el proceso de PHP.

### Instrucciones para integrar un frontend u otro servicio

1. Define `API_BASE_URL` como `http://localhost:8000/api` en local o como la URL pública equivalente en cada entorno.
2. Para registro o login envía `Content-Type: application/json` y `Accept: application/json`.
3. Lee el token de `data.token` y el prefijo de `data.token_type` de la respuesta `201` o `200`.
4. En cada solicitud protegida agrega `Authorization: Bearer <data.token>` y conserva `Accept: application/json`.
5. Llama a `GET /me` al iniciar o recuperar una sesión para hidratar el usuario actual.
6. Ante `401`, elimina el token local y redirige al login. Ante `422`, procesa `errors` por nombre de campo y muestra el primer mensaje de cada arreglo. Ante `403`, muestra el mensaje de permisos sin reintentar automáticamente.
7. Después de `POST /logout`, elimina el token local aunque la API ya lo haya revocado.

Ejemplo mínimo de cliente JavaScript:

```js
const API_BASE_URL = 'http://localhost:8000/api';

async function login(email, password) {
  const response = await fetch(`${API_BASE_URL}/login`, {
    method: 'POST',
    headers: {
      'Accept': 'application/json',
      'Content-Type': 'application/json',
    },
    body: JSON.stringify({ email, password }),
  });

  const body = await response.json();
  if (!response.ok) throw body;

  localStorage.setItem('access_token', body.data.token);
  return body.data.user;
}

async function getCurrentUser() {
  const token = localStorage.getItem('access_token');
  const response = await fetch(`${API_BASE_URL}/me`, {
    headers: {
      'Accept': 'application/json',
      'Authorization': `Bearer ${token}`,
    },
  });

  if (response.status === 401) {
    localStorage.removeItem('access_token');
    return null;
  }

  return (await response.json()).data.user;
}
```

> En producción usa HTTPS y un mecanismo de almacenamiento de tokens adecuado para tu arquitectura. No envíes contraseñas ni tokens a logs, analytics o URLs.

### Reglas de campos resumidas

| Campo | Tipo | Obligatorio | Reglas |
|-------|------|-------------|--------|
| `name` | string | Registro: sí | Entre 1 y 255 caracteres. |
| `email` | string/email | Registro y login: sí | Debe ser un correo válido; en registro no puede existir previamente. Máximo 255 caracteres en registro. |
| `password` | string | Registro y login: sí | Registro: mínimo 8 caracteres. Login: cadena no vacía. |
| `password_confirmation` | string | Registro: sí | Debe coincidir exactamente con `password`; mínimo 8 caracteres. |
| `role` | string | Registro: sí | Solo `cliente` o `negocio`. `administrador`, `admin` y cualquier otro valor son rechazados. |

Los roles devueltos en `data.user.roles[].name` están en inglés (`client`, `business`, `admin`) y `data.user.roles[].label` es la etiqueta en español (`Cliente`, `Negocio`, `Administrador`). El campo `role` de registro, en cambio, recibe los slugs en español.

### Formato de errores

Los errores de validación responden `422 Unprocessable Entity` con este formato. Un campo puede tener varios mensajes:

```json
{
  "message": "Este correo electrónico ya está registrado.",
  "errors": {
    "email": ["Este correo electrónico ya está registrado."]
  }
}
```

Los errores de autenticación responden `401` y los errores de permisos de rutas futuras protegidas por el middleware `role` responden `403`:

```json
{ "message": "No autenticado." }
```

```json
{ "message": "No tienes permiso para realizar esta acción." }
```

---

## Endpoints

Base URL local: `http://localhost:8000/api`

### `POST /register` — Registro

Solo se permite el auto-registro como `cliente` o `negocio`. El rol de `administrador` **no** se puede asignar desde aquí.

```json
{
  "name": "Juan Pérez",
  "email": "juan@example.com",
  "password": "password123",
  "password_confirmation": "password123",
  "role": "cliente"
}
```

Respuesta `201 Created`:

```json
{
  "message": "Usuario registrado correctamente.",
  "data": {
    "user": {
      "id": 1,
      "name": "Juan Pérez",
      "email": "juan@example.com",
      "email_verified_at": null,
      "roles": [{ "name": "client", "label": "Cliente" }],
      "created_at": "2026-10-10T15:30:00.000000Z"
    },
    "token": "eyJ0eXAiOiJKV1QiLCJhbGciOi...",
    "token_type": "Bearer"
  }
}
```

### `POST /login` — Iniciar sesión

```json
{
  "email": "juan@example.com",
  "password": "password123"
}
```

Respuesta `200 OK`:

```json
{
  "message": "Sesión iniciada correctamente.",
  "data": {
    "user": {
      "id": 1,
      "name": "Juan Pérez",
      "email": "juan@example.com",
      "email_verified_at": null,
      "roles": [{ "name": "client", "label": "Cliente" }],
      "created_at": "2026-10-10T15:30:00.000000Z"
    },
    "token": "eyJ0eXAiOiJKV1QiLCJhbGciOi...",
    "token_type": "Bearer"
  }
}
```

Credenciales incorrectas → `401`:

```json
{ "message": "Las credenciales proporcionadas son incorrectas." }
```

### `POST /logout` — Cerrar sesión 🔒

Revoca el token actual. Requiere cabecera `Authorization: Bearer <token>`.

```json
{ "message": "Sesión cerrada correctamente." }
```

### `GET /me` — Usuario autenticado 🔒

```json
{
  "message": "Usuario autenticado.",
  "data": {
    "user": {
      "id": 1,
      "name": "Juan Pérez",
      "email": "juan@example.com",
      "email_verified_at": null,
      "roles": [{ "name": "client", "label": "Cliente" }],
      "created_at": "2026-10-10T15:30:00.000000Z"
    }
  }
}
```

Sin token o con token inválido → `401`:

```json
{ "message": "No autenticado." }
```

---

## Roles

Roles disponibles (valor en BD → etiqueta en español):

| Base de datos (`roles.name`) | Español        | Auto-registro |
|------------------------------|----------------|---------------|
| `client`                     | Cliente        | ✅ (`cliente`) |
| `business`                   | Negocio        | ✅ (`negocio`) |
| `admin`                      | Administrador  | ❌ (solo por seeder o asignación manual) |

### Proteger rutas por rol

Usa el middleware `role` junto con `auth:api` (acepta nombres en inglés o español, y varios roles separados por coma: basta con tener uno):

```php
// Solo administradores
Route::get('/admin/dashboard', ...)->middleware(['auth:api', 'role:admin']);

// Clientes o negocios
Route::get('/panel', ...)->middleware(['auth:api', 'role:cliente,negocio']);
```

Sin el rol requerido → `403`:

```json
{ "message": "No tienes permiso para realizar esta acción." }
```

### Métodos disponibles en el modelo `User`

```php
$user->assignRole(RoleName::Client);   // asigna sin quitar los existentes
$user->assignRole('cliente');          // también acepta texto (inglés o español)
$user->syncRoles(RoleName::Admin);     // reemplaza todos los roles
$user->hasRole('admin');               // true / false
$user->hasAnyRole(['client', 'business']);
$user->roles;                          // relación BelongsToMany
```

---

## Pruebas

```bash
php artisan test
```

Las pruebas cubren registro (cliente/negocio), validaciones en español, login, logout, `/me` y el middleware de roles. En CI se generan las llaves de Passport automáticamente antes de ejecutarlas (ver `.github/workflows/tests.yml`).

---

## Estructura relevante del proyecto

```
app/
├── Enums/RoleName.php              # client/business/admin + etiquetas en español
├── Http/
│   ├── Controllers/Api/Auth/AuthController.php
│   ├── Middleware/EnsureUserHasRole.php   # alias: "role"
│   ├── Requests/Auth/              # RegisterRequest, LoginRequest (validación en español)
│   └── Resources/UserResource.php
├── Models/                         # User (HasApiTokens + roles), Role
├── OpenApi/                        # metadatos, schemas y operaciones Swagger
└── Providers/AppServiceProvider.php  # expiración de tokens Passport
database/
├── migrations/                     # users, roles, role_user, oauth_*
└── seeders/                        # RoleSeeder, AdminUserSeeder
lang/es/                             # auth, passwords, validation, pagination
routes/api.php                       # rutas de autenticación
tests/Feature/Auth/AuthTest.php      # pruebas del módulo
```

---

## Licencia

MIT.
