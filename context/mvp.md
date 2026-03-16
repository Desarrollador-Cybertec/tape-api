# 1. Ajuste funcional clave

Ahora las tareas pueden nacer de dos formas:

### Tipo A. Tarea asignada directamente a un usuario

- El **superadmin** asigna a un encargado o trabajador.

### Tipo B. Tarea asignada a un área

- El **superadmin** asigna la tarea al área.
- El **encargado del área** la recibe como tarea pendiente de distribución.
- Luego el encargado la puede:
    - asumir él mismo, o
    - delegar a un trabajador de su área.

Esto implica que una tarea puede tener:

- **creador**
- **área responsable**
- **responsable final**
- **delegador**
- **configuración de cumplimiento**

---

# 2. Nuevas reglas de negocio

## Delegación

- El superadmin puede crear una tarea para un área.
- El encargado del área puede delegarla a un trabajador de su área.
- La delegación debe quedar registrada en historial.
- Una tarea delegada no rompe la trazabilidad del asignador original.

## Configuración de requeridos

El encargado de área podrá definir, antes de delegar o al gestionar la tarea, si la tarea requiere:

- adjunto obligatorio
- comentario obligatorio al cerrar
- notificación de cumplimiento
- validación por encargado antes de finalizar
- fecha límite obligatoria
- evidencia mínima

## Cierre de tarea

Según configuración, una tarea no podrá cerrarse si falta alguno de los requeridos.

Ejemplo:

- si `requires_attachment = true`, el trabajador no podrá enviarla a revisión sin archivo
- si `requires_completion_comment = true`, no podrá cerrar sin comentario
- si `requires_manager_approval = true`, el estado final no será “finalizada” sino “en revisión”

---

# 3. Diseño de backend propuesto

Como usarán Laravel, te recomiendo trabajar con esta lógica:

## Módulos backend

- Auth
- Usuarios y roles
- Áreas
- Membresías de área
- Tareas
- Delegación de tareas
- Configuración de requeridos
- Comentarios
- Adjuntos
- Notificaciones
- Auditoría / activity log

---

# 4. Estructura principal de entidades

## users

```
id
name
email
password
role_id
active
created_at
updated_at
```

## roles

```
id
name
slug
created_at
updated_at
```

Roles base:

- superadmin
- area_manager
- worker

---

## areas

```
id
name
description
manager_user_id nullable
active
created_at
updated_at
```

---

## area_members

Esta tabla es mejor que guardar `area_id` directo en users, porque te da más control y trazabilidad.

```
id
area_id
user_id
assigned_by
claimed_by nullable
joined_at
left_at nullable
is_active
created_at
updated_at
```

Con esto puedes saber:

- quién pertenece al área
- quién lo reclamó
- cuándo entró
- si salió

---

## tasks

Esta será la tabla central.

```
id
title
description
created_by
assigned_by nullable
assigned_to_user_id nullable
assigned_to_area_id nullable
delegated_by nullable
current_responsible_user_id nullable
area_id nullable
priority
status
start_date nullable
due_date nullable
completed_at nullable
requires_attachment
requires_completion_comment
requires_manager_approval
requires_completion_notification
requires_due_date
checklist_required nullable
completion_notified_at nullable
closed_by nullable
cancelled_by nullable
created_at
updated_at
deleted_at nullable
```

### Idea clave

Una tarea puede estar asignada a:

- usuario directamente, o
- área

Y además puede terminar teniendo un:

- `current_responsible_user_id`

---

## task_delegations

Para no perder trazabilidad, conviene separar el historial de delegaciones.

```
id
task_id
from_user_id
to_user_id
from_area_id nullable
to_area_id nullable
note nullable
delegated_at
created_at
updated_at
```

Esto te deja ver:

- quién delegó
- a quién
- cuándo
- con qué observación

---

## task_comments

```
id
task_id
user_id
comment
type
created_at
updated_at
```

`type` podría ser:

- comment
- progress
- completion_note
- rejection_note
- system

---

## task_attachments

```
id
task_id
uploaded_by
file_name
file_path
mime_type
file_size
attachment_type
created_at
updated_at
```

`attachment_type`:

- evidence
- support
- final_delivery

---

## task_notifications

Si habrá “notificar cumplimiento”, conviene modelarlo.

```
id
task_id
triggered_by
notify_to_user_id
channel
message
sent_at nullable
status
created_at
updated_at
```

---

## task_status_history

Muy importante para trazabilidad.

```
id
task_id
changed_by
from_status nullable
to_status
note nullable
created_at
```

---

## activity_logs

```
id
user_id
module
action
subject_type
subject_id
description
metadata nullable
created_at
```

---

# 5. Estados del flujo

Yo te recomiendo estos:

- draft
- pending_assignment
- pending
- in_progress
- in_review
- completed
- rejected
- cancelled
- overdue

## Significado

- `draft`: mientras se crea
- `pending_assignment`: cuando el superadmin la asigna al área pero aún no se delega
- `pending`: ya tiene responsable
- `in_progress`: trabajador la está ejecutando
- `in_review`: enviada a revisión
- `completed`: aprobada y cerrada
- `rejected`: revisión rechazada, vuelve a trabajo
- `cancelled`: anulada
- `overdue`: vencida por fecha

---

# 6. Reglas de transición

## Superadmin

Puede:

- crear tarea
- asignar a área
- asignar a usuario
- cancelar
- reasignar
- cerrar directamente

## Encargado de área

Puede:

- ver tareas de su área
- delegar tareas del área
- configurar requeridos de la tarea
- revisar tareas en revisión
- aprobar o rechazar
- comentar
- notificar cumplimiento si aplica

## Trabajador

Puede:

- tomar tarea asignada
- pasar a `in_progress`
- subir adjuntos
- agregar comentarios
- enviar a revisión
- no debería poder marcar `completed` si requiere aprobación

---

# 7. Configuración de requeridos

Aquí tienes dos caminos.

## Opción simple

Guardar campos booleanos directamente en `tasks`:

```
requires_attachment
requires_completion_comment
requires_manager_approval
requires_completion_notification
requires_due_date
```

Esto es lo mejor para el MVP.

## Opción más flexible

Una tabla `task_requirements`:

```
id
task_id
requirement_type
is_required
config nullable
created_at
updated_at
```

Donde `requirement_type` podría ser:

- attachment
- completion_comment
- manager_approval
- notification
- due_date

Para el MVP, quédate con la **opción simple**.

---

# 8. Policies y autorización en Laravel

Debes manejar esto con:

- **Spatie Permission** para roles
- **Policies** para reglas por modelo
- **Form Requests** para validaciones

## Policies sugeridas

### TaskPolicy

Métodos:

- view
- create
- update
- assign
- delegate
- start
- submitForReview
- approve
- reject
- cancel
- notifyCompletion

### AreaPolicy

Métodos:

- view
- create
- update
- claimWorker
- assignManager

### UserPolicy

Métodos:

- view
- updateRole
- assignArea
- claimToArea

---

# 9. Servicios backend recomendados

No metas toda la lógica en controllers. Usa servicios.

## Servicios principales

### TaskCreationService

Se encarga de:

- crear tarea
- asignar a usuario o área
- definir estado inicial
- guardar requeridos
- registrar historial

### TaskDelegationService

Se encarga de:

- validar que el encargado pertenece al área
- validar que el trabajador pertenece al área
- delegar tarea
- actualizar responsable actual
- registrar delegación

### TaskStatusService

Se encarga de:

- validar transición de estado
- validar requeridos antes del cierre o revisión
- registrar historial de cambio

### AreaClaimService

Se encarga de:

- validar que el trabajador no tenga área
- asignarlo al área del encargado
- registrar quién lo reclamó

### TaskCompletionService

Se encarga de:

- validar adjuntos
- validar comentario obligatorio
- validar aprobación
- disparar notificación de cumplimiento

### NotificationService

Se encarga de:

- correo
- notificaciones internas
- eventos posteriores al cierre

# 10. Requests de validación

Crea Form Requests por acción.

## Ejemplos

- `StoreTaskRequest`
- `DelegateTaskRequest`
- `UpdateTaskStatusRequest`
- `ClaimWorkerRequest`
- `ApproveTaskRequest`
- `RejectTaskRequest`

Ejemplo de reglas para delegar:

- `task_id` existe
- `to_user_id` existe
- el usuario pertenece al área del encargado
- la tarea pertenece al área del encargado

---

# 11. Estructura sugerida de carpetas Laravel

```
app/
 ├── Actions/
 ├── DTOs/
 ├── Enums/
 ├── Events/
 ├── Http/
 │    ├── Controllers/
 │    ├── Requests/
 │    └── Resources/
 ├── Models/
 ├── Notifications/
 ├── Policies/
 ├── Services/
 └── Support/
```

## Enums recomendados

- `TaskStatusEnum`
- `TaskPriorityEnum`
- `RoleEnum`
- `CommentTypeEnum`
- `NotificationChannelEnum`

Esto te limpia mucho el código.

---

# 12. Relaciones Eloquent clave

## User

- belongsToMany Area through area_members
- hasMany createdTasks
- hasMany assignedTasks
- hasMany taskComments
- hasMany uploadedAttachments

## Area

- belongsTo manager (User)
- belongsToMany users through area_members
- hasMany tasks

## Task

- belongsTo creator
- belongsTo assignedUser
- belongsTo assignedArea
- belongsTo currentResponsible
- hasMany comments
- hasMany attachments
- hasMany statusHistory
- hasMany delegations

---

# 13. Endpoints backend sugeridos

Como usarán Inertia, pueden usar web routes con controllers tipo resource.

## Usuarios

- `GET /users`
- `POST /users`
- `PATCH /users/{user}`
- `PATCH /users/{user}/role`
- `PATCH /users/{user}/activate`

## Áreas

- `GET /areas`
- `POST /areas`
- `PATCH /areas/{area}`
- `PATCH /areas/{area}/manager`
- `POST /areas/claim-worker`

## Tareas

- `GET /tasks`
- `GET /tasks/{task}`
- `POST /tasks`
- `PATCH /tasks/{task}`
- `POST /tasks/{task}/delegate`
- `POST /tasks/{task}/start`
- `POST /tasks/{task}/submit-review`
- `POST /tasks/{task}/approve`
- `POST /tasks/{task}/reject`
- `POST /tasks/{task}/cancel`
- `POST /tasks/{task}/comment`
- `POST /tasks/{task}/attachments`
- `POST /tasks/{task}/notify-completion`

---

# 14. Lógica de creación de tarea

## Caso 1. Superadmin asigna a trabajador

- `assigned_to_user_id = X`
- `current_responsible_user_id = X`
- `status = pending`

## Caso 2. Superadmin asigna a encargado

- `assigned_to_user_id = encargado`
- `current_responsible_user_id = encargado`
- `status = pending`

## Caso 3. Superadmin asigna a área

- `assigned_to_area_id = area`
- `area_id = area`
- `current_responsible_user_id = null`
- `status = pending_assignment`

Luego el encargado delega y ahí:

- `delegated_by = encargado`
- `current_responsible_user_id = trabajador`
- `status = pending`

---

# 15. Validaciones críticas de negocio

Estas no pueden faltar:

## Para reclamar trabajador

- el reclamante debe ser encargado
- el trabajador debe tener rol worker
- el trabajador no debe pertenecer ya a un área activa

## Para delegar tarea

- la tarea debe pertenecer al área del encargado
- el trabajador destino debe pertenecer a esa área
- la tarea no debe estar completada o cancelada

## Para enviar a revisión

- si requiere adjunto, debe existir al menos uno
- si requiere comentario final, debe existir note de cierre
- si requiere fecha límite, no puede estar vacía

## Para aprobar

- solo encargado del área o superadmin
- la tarea debe estar en revisión

---

# 16. Eventos y notificaciones

Laravel Events te sirven mucho aquí.

## Eventos sugeridos

- `TaskCreated`
- `TaskDelegated`
- `TaskStarted`
- `TaskSubmittedForReview`
- `TaskApproved`
- `TaskRejected`
- `TaskCompleted`
- `WorkerClaimedToArea`

## Notificaciones

- nueva tarea asignada
- tarea delegada
- tarea enviada a revisión
- tarea aprobada
- tarea rechazada
- tarea vencida

---

# 17. MVP backend realista

Para el MVP yo dejaría así:

## Sí incluir

- auth
- roles
- áreas
- reclamación de trabajadores
- tareas
- delegación de tareas de área
- requeridos booleanos en la tarea
- comentarios
- adjuntos
- historial de estados
- policies
- notificaciones internas básicas

## No incluir aún

- subtareas
- checklists complejos
- automatizaciones avanzadas
- múltiples encargados por área
- flujos custom por tipo de tarea
- SLA avanzados
