# TAPE API — Backend Documentation

## Overview

API REST para el sistema de gestión de tareas **TAPE**, construida con **Laravel 12** y diseñada para conectarse a **Supabase** (PostgreSQL). Autenticación basada en tokens via **Laravel Sanctum**.

---

## Stack Técnico

| Componente       | Tecnología              |
|------------------|-------------------------|
| Framework        | Laravel 12              |
| PHP              | 8.4+                    |
| Base de datos    | PostgreSQL (Supabase)   |
| Autenticación    | Laravel Sanctum (tokens)|
| Tests            | PHPUnit 11 (SQLite :memory:) |
| Rate Limiting    | 60 requests/minuto      |

---

## Configuración Inicial

### 1. Variables de entorno

Copiar `.env.example` a `.env` y completar los datos de Supabase:

```env
DB_CONNECTION=pgsql
DB_HOST=db.XXXXX.supabase.co
DB_PORT=5432
DB_DATABASE=postgres
DB_USERNAME=postgres
DB_PASSWORD=tu_password
```

### 2. Instalación

```bash
composer install
php artisan key:generate
php artisan migrate --seed
```

El seeder crea:
- 3 roles: `superadmin`, `area_manager`, `worker`
- 1 superadmin: `admin@tape.com` / `password`
- 1 encargado: `manager@tape.com` / `password`
- 2 trabajadores
- 1 área con membresías

### 3. Tests

```bash
php artisan test
# o
php vendor/bin/phpunit --testdox
```

51 tests, 101 assertions — todos pasando.

---

## Arquitectura

```
app/
├── Enums/              # TaskStatusEnum, TaskPriorityEnum, RoleEnum, etc.
├── Http/
│   ├── Controllers/    # AuthController, UserController, AreaController, TaskController
│   ├── Requests/       # Form Requests con validación y autorización
│   └── Resources/      # API Resources para respuestas JSON consistentes
├── Models/             # Eloquent models con relaciones
├── Policies/           # TaskPolicy, AreaPolicy, UserPolicy
├── Providers/          # AppServiceProvider
└── Services/           # Lógica de negocio (TaskCreationService, etc.)
```

### Patrón de diseño

- **Controllers** — Reciben la request, delegan a Services, retornan Resources.
- **Form Requests** — Validación de datos + autorización a nivel de request.
- **Policies** — Autorización a nivel de modelo (gate checks).
- **Services** — Lógica de negocio compleja, transacciones DB, logging de actividad.
- **Resources** — Transformación de modelos a JSON con estructura consistente.

---

## Modelos & Relaciones

### User
- `belongsTo` Role
- `belongsToMany` Area (via `area_members`)
- `hasMany` createdTasks, assignedTasks, responsibleTasks
- Helpers: `isSuperAdmin()`, `isAreaManager()`, `isWorker()`, `belongsToArea($id)`, `isManagerOfArea($id)`

### Role
- `hasMany` Users
- Campos: `name`, `slug`

### Area
- `belongsTo` manager (User)
- `belongsToMany` users (via `area_members`)
- `hasMany` Tasks
- Campos: `name`, `description`, `manager_user_id`, `active`

### AreaMember
- `belongsTo` Area, User, assignedByUser, claimedByUser
- Campos: `area_id`, `user_id`, `assigned_by`, `claimed_by`, `joined_at`, `left_at`, `is_active`

### Task (SoftDeletes)
- `belongsTo` creator, assignedUser, assignedArea, delegatedBy, currentResponsible, area, closedBy, cancelledBy
- `hasMany` comments, attachments, statusHistory, delegations
- Campos de configuración: `requires_attachment`, `requires_completion_comment`, `requires_manager_approval`, `requires_completion_notification`, `requires_due_date`
- Casts: `status` → `TaskStatusEnum`, `priority` → `TaskPriorityEnum`

### TaskDelegation
- `belongsTo` Task, fromUser, toUser

### TaskComment
- Campos: `task_id`, `user_id`, `comment`, `type` (CommentTypeEnum)

### TaskAttachment
- Campos: `task_id`, `uploaded_by`, `file_name`, `file_path`, `mime_type`, `file_size`, `attachment_type`

### TaskStatusHistory
- Campos: `task_id`, `changed_by`, `from_status`, `to_status`, `note`

### ActivityLog
- Campos: `user_id`, `module`, `action`, `subject_type`, `subject_id`, `description`, `metadata`

---

## Enums

### TaskStatusEnum
`draft` → `pending_assignment` → `pending` → `in_progress` → `in_review` → `completed`

Transiciones permitidas:
| Desde               | Hacia                                               |
|----------------------|-----------------------------------------------------|
| draft                | pending_assignment, pending, cancelled               |
| pending_assignment   | pending, cancelled                                   |
| pending              | in_progress, cancelled                               |
| in_progress          | in_review, completed, cancelled                      |
| in_review            | completed, rejected                                  |
| rejected             | in_progress                                          |

### TaskPriorityEnum
`low`, `medium`, `high`, `urgent`

### RoleEnum
`superadmin`, `area_manager`, `worker`

### CommentTypeEnum
`comment`, `progress`, `completion_note`, `rejection_note`, `system`

### AttachmentTypeEnum
`evidence`, `support`, `final_delivery`

---

## API Endpoints

Base URL: `/api`

### Autenticación

| Método | Endpoint     | Descripción            | Auth |
|--------|-------------|------------------------|------|
| POST   | `/login`    | Login con email/password | No  |
| POST   | `/logout`   | Cerrar sesión           | Sí  |
| GET    | `/me`       | Perfil del usuario      | Sí  |

**Login** retorna `{ token, user }`. Usar token como `Bearer {token}` en header `Authorization`.

### Usuarios

| Método | Endpoint                      | Descripción               | Roles permitidos |
|--------|-------------------------------|---------------------------|-----------------|
| GET    | `/users`                      | Listar usuarios           | superadmin, area_manager |
| POST   | `/users`                      | Crear usuario             | superadmin       |
| GET    | `/users/{id}`                 | Ver usuario               | superadmin, area_manager |
| PUT    | `/users/{id}`                 | Actualizar usuario        | superadmin       |
| PATCH  | `/users/{id}/role`            | Cambiar rol               | superadmin       |
| PATCH  | `/users/{id}/toggle-active`   | Activar/desactivar        | superadmin       |

### Áreas

| Método | Endpoint                    | Descripción                | Roles permitidos |
|--------|-----------------------------|---------------------------|-----------------|
| GET    | `/areas`                    | Listar áreas              | superadmin, area_manager |
| POST   | `/areas`                    | Crear área                | superadmin       |
| GET    | `/areas/{id}`               | Ver área                  | superadmin, area_manager |
| PUT    | `/areas/{id}`               | Actualizar área           | superadmin       |
| PATCH  | `/areas/{id}/manager`       | Asignar encargado         | superadmin       |
| POST   | `/areas/claim-worker`       | Reclamar trabajador al área| area_manager    |

### Tareas

| Método | Endpoint                          | Descripción                    | Roles permitidos |
|--------|-----------------------------------|-------------------------------|-----------------|
| GET    | `/tasks`                          | Listar tareas (filtrado por rol)| todos           |
| POST   | `/tasks`                          | Crear tarea                    | superadmin, area_manager |
| GET    | `/tasks/{id}`                     | Ver tarea con detalles         | según visibilidad |
| PUT    | `/tasks/{id}`                     | Actualizar tarea               | superadmin, area_manager |
| POST   | `/tasks/{id}/delegate`            | Delegar tarea                  | area_manager     |
| POST   | `/tasks/{id}/start`               | Iniciar tarea                  | worker asignado  |
| POST   | `/tasks/{id}/submit-review`       | Enviar a revisión              | worker asignado  |
| POST   | `/tasks/{id}/approve`             | Aprobar tarea                  | area_manager     |
| POST   | `/tasks/{id}/reject`              | Rechazar tarea                 | area_manager     |
| POST   | `/tasks/{id}/cancel`              | Cancelar tarea                 | superadmin       |
| POST   | `/tasks/{id}/comment`             | Agregar comentario             | según visibilidad |
| POST   | `/tasks/{id}/attachments`         | Subir adjunto                  | según visibilidad |

---

## Reglas de Negocio Implementadas

### Creación de Tareas

**Caso 1 — Asignar a usuario:**
- `assigned_to_user_id` = usuario
- `current_responsible_user_id` = usuario
- `status` = `pending`

**Caso 2 — Asignar a área:**
- `assigned_to_area_id` = área
- `area_id` = área
- `current_responsible_user_id` = null
- `status` = `pending_assignment`

### Delegación
- Solo el encargado del área puede delegar tareas de su área.
- El trabajador destino debe pertenecer al área.
- La tarea no puede estar completada ni cancelada.
- Se registra en `task_delegations` y `activity_logs`.

### Flujo de Estados

```
draft → pending_assignment → pending → in_progress → in_review → completed
                                                    ↗ (sin aprobación requerida)
                                         in_review → rejected → in_progress (retry)
cualquier estado activo → cancelled
```

### Validaciones de Cierre

Antes de enviar a revisión (`submit-review`), se valida:
- Si `requires_attachment = true` → debe tener al menos un adjunto.
- Si `requires_completion_comment = true` → debe enviar `completion_comment`.

### Aprobación / Rechazo
- Si `requires_manager_approval = true` → pasa a `in_review` antes de `completed`.
- Si no requiere aprobación → pasa directamente a `completed`.
- Al rechazar → se requiere `rejection_note`, estado vuelve a `in_progress`.

### Reclamar Trabajador
- Solo un `area_manager` puede reclamar.
- El usuario reclamado debe tener rol `worker`.
- El trabajador no debe tener ya un área activa.

---

## Seguridad

### Autenticación
- Tokens Sanctum con expiración configurable.
- Usuarios inactivos (`active = false`) no pueden hacer login.
- Rate limiting: 60 requests/minuto para rutas API.

### Autorización
- **Policies** verifican permisos a nivel de modelo (TaskPolicy, AreaPolicy, UserPolicy).
- **Form Requests** validan datos de entrada y autorizan la acción.
- Un superadmin no puede cambiar su propio rol (evitar auto-degradarse).

### Validación de Entrada
- Todos los endpoints usan Form Requests con reglas estrictas.
- Passwords requieren: mínimo 8 caracteres, mayúsculas, minúsculas, números y símbolos.
- Emails validados como únicos y con formato correcto.

### Protección de Datos
- Contraseñas hasheadas con bcrypt (Laravel default).
- Tokens sensibles protegidos.
- Soft deletes en tareas (nunca se eliminan permanentemente).

### Transacciones
- Todas las operaciones de escritura de servicios usan `DB::transaction()`.
- Si falla cualquier paso, se revierte todo.

### Auditoría
- Cada acción relevante se registra en `activity_logs`.
- Historial de cambios de estado en `task_status_history`.
- Historial de delegaciones en `task_delegations`.

### Middleware
- `auth:sanctum` en todas las rutas protegidas.
- `EnsureFrontendRequestsAreStateful` para compatibilidad SPA.
- Throttle API configurado globalmente.

---

## Formato de Respuestas

### Éxito con datos
```json
{
    "data": {
        "id": 1,
        "title": "Tarea ejemplo",
        "status": "pending",
        ...
    }
}
```

### Éxito con mensaje
```json
{
    "message": "Tarea delegada exitosamente"
}
```

### Error de validación (422)
```json
{
    "message": "The title field is required.",
    "errors": {
        "title": ["The title field is required."]
    }
}
```

### Error de autenticación (401)
```json
{
    "message": "Unauthenticated."
}
```

### Error de autorización (403)
```json
{
    "message": "This action is unauthorized."
}
```

---

## Tests

51 tests organizados por feature:

| Suite       | Tests | Cobertura                                                                |
|-------------|-------|--------------------------------------------------------------------------|
| AuthTest    | 5     | Login, logout, perfil, credenciales inválidas, usuario inactivo          |
| UserTest    | 7     | CRUD, cambio de rol, activar/desactivar, validaciones                    |
| AreaTest    | 6     | CRUD, asignar encargado, reclamar trabajador, validaciones               |
| TaskTest    | 15    | CRUD, delegación, flujo completo de estados, adjuntos, comentarios, validaciones de requeridos |

Todos los tests usan `RefreshDatabase` con SQLite in-memory para velocidad.

---

## Estructura de Migraciones

| Orden | Tabla                   | Descripción                           |
|-------|-------------------------|---------------------------------------|
| 1     | roles                   | Roles del sistema                     |
| 2     | users (modificación)    | Agrega `role_id`, `active`            |
| 3     | areas                   | Áreas de trabajo                      |
| 4     | area_members            | Membresías área-usuario               |
| 5     | tasks                   | Tabla central de tareas               |
| 6     | task_delegations        | Historial de delegaciones             |
| 7     | task_comments           | Comentarios en tareas                 |
| 8     | task_attachments        | Archivos adjuntos                     |
| 9     | task_notifications      | Notificaciones de cumplimiento        |
| 10    | task_status_history     | Historial de cambios de estado        |
| 11    | activity_logs           | Log de auditoría general              |
