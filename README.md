# MediPlan API

API construida con **Laravel 12** y autenticación OAuth2 con **Laravel Passport**.

Módulos incluidos:

- **Autenticación**: registro (`POST /api/register`), login (`POST /api/login`), logout (`POST /api/logout`) y usuario actual (`GET /api/me`).
- **Panel del negocio** (`/api/business/*`): indicadores del dashboard, **directorio de clientes**, **embudo de leads** (con conversión a cliente), **agenda/calendario de citas** y **configuración del negocio**.
- **Panel del cliente** (`/api/client/*`): indicadores de la cuenta, citas próximas e histórico y cancelación de citas.
- **Panel de administración** (`/api/admin/*`): indicadores globales de la plataforma, **listado de negocios**, **listado de usuarios** (alta, edición, roles, activación y baja), moderación de negocios, supervisión de leads y catálogo de roles.
- **Roles**: cliente, negocio y administrador, con middleware `role` aplicado por grupo de rutas.
- **Documentación Swagger/OpenAPI** (`GET /api/documentation` y `GET /docs`) con los 31 endpoints descritos.

> Convención del proyecto: la **base de datos está en inglés** (`roles`, `businesses`, `clients`, `leads`, `appointments`) y todos los **mensajes, errores y validaciones están en español**. Las respuestas usan el sobre `{ "message": ..., "data": ... }` y los listados agregan `"meta"` con la paginación.

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

`php artisan db:seed` también ejecuta `DemoDataSeeder`, que llena el negocio de prueba con 12 clientes, 8 leads en distintos estados del embudo y citas de los últimos tres meses, del día en curso y de las próximas dos semanas. Así los dashboards se ven poblados desde el primer login. Para generar datos en otro momento:

```bash
php artisan db:seed --class=DemoDataSeeder
```

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

> Al registrarse con el rol `negocio` la API crea automáticamente su ficha de negocio y su configuración por defecto, por lo que el usuario puede usar el panel de inmediato. `/me` y `/login` incluyen `data.user.business` cuando el usuario tiene negocio, además de `is_active`.

### Formato de las respuestas

Detalle de un recurso:

```json
{
  "message": "Cliente obtenido correctamente.",
  "data": { "id": 1, "name": "María López", "status": { "name": "active", "label": "Activo" } }
}
```

Listados (paginados con `?page=` y `?per_page=`, máximo 100):

```json
{
  "message": "Clientes obtenidos correctamente.",
  "data": [ { "id": 1, "name": "María López" } ],
  "meta": { "current_page": 1, "from": 1, "last_page": 3, "per_page": 15, "to": 15, "total": 42 }
}
```

Los estados (`status`) siempre viajan como objeto `{ "name", "label" }` con el valor en inglés y la etiqueta en español.

---

## Panel del negocio 🔒 `role:business`

Todos los endpoints operan **solo sobre el negocio del usuario autenticado**: un negocio nunca puede leer ni modificar los datos de otro (responde `404`).

### `GET /business/dashboard` — Indicadores

```json
{
  "message": "Panel del negocio generado correctamente.",
  "data": {
    "business": { "id": 1, "name": "Negocio de prueba", "status": { "name": "active", "label": "Activo" } },
    "clients": { "total": 13, "active": 13, "new_this_month": 13 },
    "leads": {
      "total": 8,
      "open": 6,
      "by_status": { "new": 2, "contacted": 2, "qualified": 1, "proposal": 1, "won": 1, "lost": 1 },
      "conversion_rate": 50.0
    },
    "appointments": {
      "today": 4,
      "next_week": 7,
      "completed_this_month": 3,
      "cancelled_this_month": 0,
      "revenue_this_month": 5311.31,
      "monthly_activity": [
        { "month": "2026-10", "label": "octubre 2026", "total": 19, "completed": 3, "cancelled": 0 }
      ]
    },
    "next_appointments": [],
    "recent_leads": [],
    "recent_clients": []
  }
}
```

`monthly_activity` trae los últimos seis meses. Si el usuario con rol `business` todavía no tiene ficha, la API responde `404` con `"Tu usuario todavía no tiene un negocio asociado."`.

### Clientes — `/business/clients`

| Método | Ruta | Descripción |
|--------|------|-------------|
| `GET` | `/business/clients` | Listado paginado. Filtros: `search` (nombre, correo o teléfono), `status` (`active`, `inactive`), `sort` (`name`, `created_at`, `last_appointment_at`), `direction` (`asc`, `desc`), `per_page`. |
| `GET` | `/business/clients/{client}` | Detalle con conteo de citas. |
| `POST` | `/business/clients` | Alta. El correo es único **por negocio**. |
| `PUT` | `/business/clients/{client}` | Actualización parcial. |
| `DELETE` | `/business/clients/{client}` | Elimina el cliente y sus citas. |

```json
{
  "name": "María López",
  "email": "maria@example.com",
  "phone": "5512345678",
  "birth_date": "1990-04-12",
  "notes": "Prefiere citas por la tarde.",
  "status": "active"
}
```

### Leads — `/business/leads`

Estados del embudo: `new` (Nuevo), `contacted` (Contactado), `qualified` (Calificado), `proposal` (Propuesta enviada), `won` (Ganado), `lost` (Perdido).

| Método | Ruta | Descripción |
|--------|------|-------------|
| `GET` | `/business/leads` | Listado paginado. Filtros: `search`, `status`, `source`, `open=1`, `follow_up_from`, `follow_up_to`, `sort` (`name`, `created_at`, `follow_up_at`, `estimated_value`), `direction`, `per_page`. |
| `GET` | `/business/leads/{lead}` | Detalle con responsable y cliente convertido. |
| `POST` | `/business/leads` | Alta. Exige **correo o teléfono**. |
| `PUT` | `/business/leads/{lead}` | Actualización parcial. |
| `PATCH` | `/business/leads/{lead}/status` | Mueve el lead en el embudo; al salir de `new` registra `contacted_at`. |
| `POST` | `/business/leads/{lead}/convert` | Convierte el lead en cliente y lo marca como `won`. |
| `DELETE` | `/business/leads/{lead}` | Elimina el lead. |

```json
{ "status": "qualified", "notes": "Interesado en el plan anual.", "follow_up_at": "2026-11-01T10:00:00.000000Z" }
```

La conversión acepta datos opcionales que sustituyen a los del lead (`name`, `email`, `phone`, `notes`). Si el lead ya fue convertido responde `422` con `"Este lead ya fue convertido en cliente."`.

### Agenda — `/business/appointments`

Estados de una cita: `scheduled` (Programada), `confirmed` (Confirmada), `completed` (Completada), `cancelled` (Cancelada), `no_show` (No asistió).

| Método | Ruta | Descripción |
|--------|------|-------------|
| `GET` | `/business/appointments` | Listado paginado. Filtros: `from`, `to`, `status`, `client_id`, `upcoming=1`, `per_page`. |
| `GET` | `/business/appointments/agenda` | **Calendario**: citas agrupadas por día (`from`/`to`, por defecto el mes en curso). |
| `GET` | `/business/appointments/{appointment}` | Detalle con cliente y negocio. |
| `POST` | `/business/appointments` | Agenda una cita. Rechaza horarios encimados y fechas pasadas. |
| `PUT` | `/business/appointments/{appointment}` | Actualización parcial (reprogramar, cambiar precio, etc.). |
| `PATCH` | `/business/appointments/{appointment}/status` | `confirmed`, `completed`, `cancelled` o `no_show`. Al cancelar pide `cancel_reason`. |
| `DELETE` | `/business/appointments/{appointment}` | Elimina la cita. |

```json
{
  "client_id": 1,
  "title": "Consulta inicial",
  "starts_at": "2026-10-13T10:00:00.000000Z",
  "ends_at": "2026-10-13T10:30:00.000000Z",
  "price": 850
}
```

Respuesta de la agenda:

```json
{
  "message": "Agenda obtenida correctamente.",
  "data": {
    "from": "2026-10-01",
    "to": "2026-10-31",
    "total": 19,
    "days": [
      { "date": "2026-10-01", "label": "jueves 1 de octubre", "total": 1, "appointments": [] }
    ]
  }
}
```

### Configuración — `/business/profile` y `/business/settings`

| Método | Ruta | Descripción |
|--------|------|-------------|
| `GET` | `/business/profile` | Datos del negocio (nombre, descripción, contacto, dirección, ciudad) y su dueño. |
| `PUT` | `/business/profile` | Actualización parcial del perfil. |
| `GET` | `/business/settings` | Configuración operativa; si no existe se crea con valores por defecto. |
| `PUT` | `/business/settings` | Actualización parcial de la configuración. |

Campos de configuración: `timezone`, `appointment_duration_minutes` (5–480), `slot_interval_minutes` (5–240), `min_notice_minutes`, `max_advance_days`, `auto_confirm_appointments`, `allow_online_booking`, `currency` (3 letras) y `working_hours` con las claves `monday`…`sunday` y `open`, `close`, `closed` por día.

```json
{
  "appointment_duration_minutes": 45,
  "auto_confirm_appointments": true,
  "working_hours": {
    "monday": { "open": "08:00", "close": "20:00", "closed": false },
    "sunday": { "open": "00:00", "close": "00:00", "closed": true }
  }
}
```

---

## Panel del cliente 🔒 `role:client`

| Método | Ruta | Descripción |
|--------|------|-------------|
| `GET` | `/client/dashboard` | Resumen de la cuenta: próxima cita, citas por venir, histórico y negocios disponibles. |
| `GET` | `/client/appointments` | Citas de la cuenta. Filtros: `scope` (`all`, `upcoming`, `past`), `status`, `per_page`. |
| `GET` | `/client/appointments/{appointment}` | Detalle de una cita propia. |
| `PATCH` | `/client/appointments/{appointment}/cancel` | Cancela una cita futura que sigue agendada (motivo opcional en `cancel_reason`). |

Una cita de otro usuario responde `404`; cancelar una cita que ya pasó o que ya no está agendada responde `422`.

---

## Panel de administración 🔒 `role:admin`

### `GET /admin/dashboard` — Indicadores de la plataforma

Devuelve totales de usuarios (con desglose por rol e inactivos), negocios (con desglose por estado), clientes, leads (abiertos, ganados y tasa de conversión), citas (hoy, próximas, completadas) y los registros más recientes de usuarios y negocios.

### Usuarios — `/admin/users`

| Método | Ruta | Descripción |
|--------|------|-------------|
| `GET` | `/admin/users` | Listado paginado con roles y negocio. Filtros: `search`, `role` (`client`, `business`, `admin` o sus slugs en español), `status` (`active`, `inactive`), `per_page`. |
| `GET` | `/admin/users/{user}` | Detalle. |
| `POST` | `/admin/users` | Alta con cualquier rol, **incluido `admin`**. Si el rol es `business` se crea también su negocio (usa `business_name`). |
| `PUT` | `/admin/users/{user}` | Actualización parcial (nombre, correo, teléfono, contraseña, `is_active`; `role` agrega el rol). |
| `PATCH` | `/admin/users/{user}/status` | Activa o desactiva la cuenta (`{ "is_active": false }`). Una cuenta desactivada no puede iniciar sesión (`403` en `/login`). |
| `PUT` | `/admin/users/{user}/roles` | Reemplaza los roles (`{ "roles": ["business", "cliente"] }`). |
| `DELETE` | `/admin/users/{user}` | Elimina la cuenta y, en cascada, su negocio con clientes, leads y citas. |

Protecciones: no puedes desactivar ni eliminar tu propia cuenta, ni quitarte el rol `admin` a ti mismo (responde `422` con el mensaje en español correspondiente).

### Negocios — `/admin/businesses`

| Método | Ruta | Descripción |
|--------|------|-------------|
| `GET` | `/admin/businesses` | Listado paginado con dueño y conteos de clientes, leads y citas. Filtros: `search`, `status` (`pending`, `active`, `suspended`), `per_page`. |
| `GET` | `/admin/businesses/{business}` | Detalle. |
| `PUT` | `/admin/businesses/{business}` | Actualización parcial de los datos del negocio. |
| `PATCH` | `/admin/businesses/{business}/status` | Aprueba (`active`), suspende (`suspended`) o regresa a revisión (`pending`). |
| `DELETE` | `/admin/businesses/{business}` | Elimina el negocio y sus datos; la cuenta del propietario se conserva. |

### Otros

| Método | Ruta | Descripción |
|--------|------|-------------|
| `GET` | `/admin/leads` | Supervisión de solo lectura de los leads de todos los negocios. Filtros: `search`, `status`, `business_id`, `per_page`. |
| `GET` | `/admin/roles` | Roles registrados con su etiqueta en español y conteo de usuarios. |

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

Las pruebas cubren:

- **Autenticación**: registro (cliente/negocio), creación automática del negocio al registrarse, validaciones en español, login, cuenta desactivada, logout, `/me` y el middleware de roles.
- **Negocio**: dashboard e indicadores, CRUD de clientes con aislamiento entre negocios, embudo de leads (filtros, estados, conversión y conversión duplicada), agenda (listado, calendario agrupado por día, traslapes, cancelación) y configuración.
- **Cliente**: dashboard, listado por alcance, cancelación propia y bloqueos.
- **Administración**: indicadores globales, usuarios (alta con negocio, roles, activación, baja y protecciones sobre la propia cuenta), negocios (listado con conteos, moderación, baja en cascada) y supervisión de leads.
- **Documentación**: que la especificación OpenAPI describa todas las rutas de la aplicación.

En CI se generan las llaves de Passport automáticamente antes de ejecutarlas (ver `.github/workflows/tests.yml`).

---

## Estructura relevante del proyecto

```
app/
├── Enums/                          # RoleName, BusinessStatus, ClientStatus, LeadStatus, AppointmentStatus
├── Http/
│   ├── Controllers/
│   │   ├── Api/Auth/               # registro, login, logout, me
│   │   ├── Api/Business/           # Dashboard, Profile, Client, Lead, Appointment
│   │   ├── Api/Client/             # Dashboard, Appointment
│   │   ├── Api/Admin/              # Dashboard, User, Business, Lead, Role
│   │   └── Concerns/               # ResolvesCurrentBusiness, RespondsWithPaginatedResources
│   ├── Middleware/EnsureUserHasRole.php   # alias: "role"
│   ├── Requests/                   # Auth, Business, Client y Admin (validación en español)
│   └── Resources/                  # User, Business, BusinessSetting, Client, Lead, Appointment
├── Models/                         # User, Role, Business, BusinessSetting, Client, Lead, Appointment
├── OpenApi/                        # metadatos, schemas y operaciones Swagger
├── Providers/AppServiceProvider.php
└── Services/
    ├── Businesses/BusinessProvisioner.php   # crea negocio + configuración al registrar
    ├── Dashboards/                 # Business, Client y AdminDashboardService
    └── Leads/LeadConverter.php     # conversión de lead a cliente
database/
├── factories/                      # Business, BusinessSetting, Client, Lead, Appointment, User
├── migrations/                     # users, roles, oauth_*, businesses, business_settings, clients, leads, appointments
└── seeders/                        # RoleSeeder, AdminUserSeeder, DemoDataSeeder
lang/es/                            # auth, passwords, validation, pagination
routes/api.php                      # rutas por módulo y por rol
tests/
├── Concerns/CreatesApiUsers.php    # usuarios con rol y negocios de prueba
└── Feature/                        # Auth, Business, Client, Admin, Documentation
```

---

## Licencia

MIT.
