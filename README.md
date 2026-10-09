# MediPlan API

API construida con **Laravel 12** y autenticación OAuth2 con **Laravel Passport**.

Módulos incluidos por el momento:

- **Registro** de usuarios (`POST /api/register`)
- **Login** (`POST /api/login`)
- **Logout** (`POST /api/logout`)
- **Usuario autenticado** (`GET /api/me`)
- **Roles**: cliente, negocio y administrador

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

# 9. Levantar el servidor
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
      "roles": [{ "name": "client", "label": "Cliente" }]
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
    "user": { "...": "..." },
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
      "roles": [{ "name": "client", "label": "Cliente" }]
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
