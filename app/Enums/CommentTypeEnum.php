<?php

namespace App\Enums;

enum CommentTypeEnum: string
{
    case COMMENT = 'comment';
    case PROGRESS = 'progress';
    case COMPLETION_NOTE = 'completion_note';
    case REJECTION_NOTE = 'rejection_note';
    case SYSTEM = 'system';

    public function label(): string
    {
        return match ($this) {
            self::COMMENT => 'Comentario',
            self::PROGRESS => 'Progreso',
            self::COMPLETION_NOTE => 'Nota de cierre',
            self::REJECTION_NOTE => 'Nota de rechazo',
            self::SYSTEM => 'Sistema',
        };
    }
}
