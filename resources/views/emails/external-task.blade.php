<x-mail::message>
# Nueva Tarea Asignada

Hola {{ $task->external_name ?? 'estimado/a' }},

Se le ha asignado una nueva tarea:

**{{ $task->title }}**

@if($task->description)
{{ $task->description }}
@endif

@if($task->due_date)
**Fecha límite:** {{ $task->due_date->format('d/m/Y') }}
@endif

**Prioridad:** {{ $task->priority->value }}

Gracias,<br>
{{ config('app.name') }}
</x-mail::message>
