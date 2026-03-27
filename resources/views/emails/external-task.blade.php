<x-mail::message>

## ¡Hola{{ $task->external_name ? ', ' . explode(' ', trim($task->external_name))[0] : '' }}!

Desde nuestro sistema TAPE, te informamos que necesitamos que nos ayudes con una tarea:

**{{ $task->title }}**

@if($task->description)
{{ $task->description }}
@endif

Para nosotros es de prioridad **{{ $task->priority->value }}**, y necesitamos que nos ayudes antes del **{{ $task->due_date?->format('d/m/Y') ?? 'plazo acordado' }}**.

Te lo agradecemos,  
{{ $task->creator?->name ?? 'El equipo' }},  
**Sistema TAPE**

</x-mail::message>
