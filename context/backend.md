# TAPE API — Backend Documentation

## Overview

API REST para el sistema de gestión de compromisos y tareas **TAPE**, construida con **Laravel 12** y diseñada para conectarse a **Supabase** (PostgreSQL). Autenticación basada en tokens via **Laravel Sanctum**.

El sistema reemplaza un proceso manual basado en Excel, proporcionando: registro individual de compromisos, seguimiento de avances, notificaciones automáticas consolidadas por responsable, dashboards analíticos y trazabilidad completa.

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
| Scheduler        | Laravel Scheduler (cron)|

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
- 1 superadmin: `admin@tape.test` / `Password1`
- 1 encargado: `manager@tape.test` / `Password1`
- 2 trabajadores
- 1 área con membresías
- 1 reunión de ejemplo

### 3. Tests

```bash
php artisan test
# o
php vendor/bin/phpunit --testdox
```

80 tests, 187 assertions — todos pasando.

### 4. Scheduler (producción)

Agregar al crontab del servidor:

```bash
* * * * * cd /path-to-project && php artisan schedule:run >> /dev/null 2>&1
```

Comandos programados:
- `tasks:detect-overdue` — 06:00 diario
- `tasks:send-daily-summary` — 07:00 diario
- `tasks:send-due-reminders` — 08:00 diario

---

## Arquitectura

```
app/
├── Console/Commands/   # DetectOverdueTasks, SendDailyTaskSummary, SendDueReminders
├── Enums/              # TaskStatusEnum, TaskPriorityEnum, RoleEnum, UpdateTypeEnum, etc.
├── Http/
│   ├── Controllers/    # Auth, User, Area, Meeting, Task, Dashboard
│   ├── Requests/       # Form Requests con validación y autorización
│   └── Resources/      # API Resources para respuestas JSON consistentes
├── Models/             # Eloquent models con relaciones
├── Policies/           # TaskPolicy, AreaPolicy, UserPolicy, MeetingPolicy
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
- `belongsTo` creator, assignedUser, assignedArea, delegatedBy, currentResponsible, area, closedBy, cancelledBy, meeting
- `hasMany` comments, attachments, statusHistory, delegations, updates
- Campos de configuración: `requires_attachment`, `requires_completion_comment`, `requires_manager_approval`, `requires_completion_notification`, `requires_due_date`, `requires_progress_report`
- Campos de notificación: `notify_on_due`, `notify_on_overdue`, `notify_on_completion`
- Campos de seguimiento: `progress_percent`, `meeting_id`
- Casts: `status` → `TaskStatusEnum`, `priority` → `TaskPriorityEnum`

### Meeting
- `belongsTo` Area, creator (User)
- `hasMany` Tasks
- Campos: `title`, `meeting_date`, `area_id`, `classification` (MeetingClassificationEnum), `notes`, `created_by`

### TaskUpdate
- `belongsTo` Task, User
- Campos: `task_id`, `user_id`, `update_type` (UpdateTypeEnum), `comment`, `progress_percent`

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
| in_progress          | in_review, completed, cancelled, overdue             |
| in_review            | completed, rejected, cancelled                       |
| rejected             | in_progress, cancelled                               |
| overdue              | in_progress, cancelled                               |

### TaskPriorityEnum
`low`, `medium`, `high`, `urgent`

### RoleEnum
`superadmin`, `area_manager`, `worker`

### CommentTypeEnum
`comment`, `progress`, `completion_note`, `rejection_note`, `system`

### AttachmentTypeEnum
`evidence`, `support`, `final_delivery`

### UpdateTypeEnum
`progress`, `evidence`, `note`

### MeetingClassificationEnum
`strategic`, `operational`, `follow_up`, `review`, `other`

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
| POST   | `/tasks/{id}/updates`             | Reportar avance/progreso       | responsable, manager, superadmin |

### Reuniones

| Método | Endpoint                    | Descripción               | Roles permitidos |
|--------|-----------------------------|---------------------------|-----------------|
| GET    | `/meetings`                 | Listar reuniones          | superadmin, area_manager |
| POST   | `/meetings`                 | Crear reunión             | superadmin, area_manager |
| GET    | `/meetings/{id}`            | Ver reunión con tareas    | según visibilidad |
| PUT    | `/meetings/{id}`            | Actualizar reunión        | creador, superadmin |
| DELETE | `/meetings/{id}`            | Eliminar reunión          | superadmin       |

### Dashboard

| Método | Endpoint                    | Descripción                        | Roles permitidos |
|--------|-----------------------------|-----------------------------------|-----------------|
| GET    | `/dashboard/general`        | Dashboard gerencial general       | superadmin       |
| GET    | `/dashboard/area/{id}`      | Dashboard por área                | superadmin, manager del área |
| GET    | `/dashboard/me`             | Dashboard personal del usuario    | todos            |

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

### Reuniones como Origen de Compromisos
- Las tareas pueden tener un `meeting_id` vinculándolas a una reunión de origen.
- Las reuniones se clasifican por tipo: estratégica, operativa, seguimiento, revisión, otra.
- Al ver una reunión, se listan todas las tareas asociadas.

### Seguimiento y Avances (Task Updates)
- El responsable puede reportar avances con comentario, porcentaje y tipo de actualización.
- El porcentaje de progreso se sincroniza automáticamente con la tarea.
- El encargado de área también puede agregar notas de seguimiento.
- Si `requires_progress_report = true`, la tarea exige reportes de avance.

### Notificaciones Automáticas
- `notify_on_due` — Alerta cuando la fecha de entrega está próxima (3 días).
- `notify_on_overdue` — Alerta cuando la tarea está vencida.
- `notify_on_completion` — Notificación al completar.
- El comando `tasks:detect-overdue` cambia automáticamente el estado a `overdue`.
- El comando `tasks:send-daily-summary` envía resúmenes consolidados por responsable.
- El comando `tasks:send-due-reminders` envía recordatorios individuales por tarea.

### Dashboard Gerencial (`/dashboard/general`)
Retorna:
- Tareas por estado y por área
- Total activas, completadas, vencidas, próximas a vencer
- Tasa de cierre (%)
- Promedio de días para cerrar
- Top 10 responsables con más carga
- Completadas este mes

### Dashboard por Área (`/dashboard/area/{id}`)
Retorna:
- Tareas por estado del área
- Vencidas del área
- Distribución por responsable
- Tasa de cierre del área
- Tareas sin reportes de avance

### Dashboard Personal (`/dashboard/me`)
Retorna:
- Tareas activas, vencidas, próximas a vencer, completadas
- Distribución por estado
- Lista de tareas próximas ordenadas por fecha de vencimiento

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

80 tests organizados por feature:

| Suite               | Tests | Cobertura                                                                |
|---------------------|-------|--------------------------------------------------------------------------|
| AuthTest            | 5     | Login, logout, perfil, credenciales inválidas, usuario inactivo          |
| UserTest            | 7     | CRUD, cambio de rol, activar/desactivar, validaciones                    |
| AreaTest            | 6     | CRUD, asignar encargado, reclamar trabajador, validaciones               |
| MeetingTest         | 10    | CRUD, permisos, vinculación con tareas, filtrado por área                |
| TaskTest            | 15    | CRUD, delegación, flujo completo de estados, adjuntos, comentarios       |
| TaskUpdateTest      | 6     | Avances, validaciones, permisos, sincronización de progreso              |
| DashboardTest       | 7     | Dashboard general, por área, personal, permisos, métricas de vencimiento |
| ScheduledCommandsTest| 5    | Detección overdue, resumen diario, recordatorios, flags de notificación  |

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
| 12    | meetings                | Reuniones / origen de compromisos     |
| 13    | tasks (modificación)    | Agrega `meeting_id`, flags de notificación, `progress_percent` |
| 14    | task_updates            | Reportes de avance/seguimiento        |

---

## Comandos Artisan

| Comando                       | Descripción                                           | Horario     |
|-------------------------------|-------------------------------------------------------|-------------|
| `tasks:detect-overdue`        | Marca como vencidas las tareas pasadas de fecha        | 06:00 diario|
| `tasks:send-daily-summary`    | Genera resúmenes consolidados por responsable          | 07:00 diario|
| `tasks:send-due-reminders`    | Envía recordatorios para tareas próximas a vencer      | 08:00 diario|
