<?php

namespace App\Enums;

enum RoleEnum: string
{
    case SUPERADMIN = 'superadmin';
    case AREA_MANAGER = 'area_manager';
    case WORKER = 'worker';

    public function label(): string
    {
        return match ($this) {
            self::SUPERADMIN => 'Super Administrador',
            self::AREA_MANAGER => 'Encargado de Área',
            self::WORKER => 'Trabajador',
        };
    }
}
