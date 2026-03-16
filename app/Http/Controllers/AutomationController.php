<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\SystemSetting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;

class AutomationController extends Controller
{
    /**
     * Manually trigger overdue detection.
     */
    public function triggerOverdueDetection(Request $request): JsonResponse
    {
        $this->authorizeSuperAdmin($request);

        Artisan::call('tasks:detect-overdue');

        ActivityLog::create([
            'user_id' => $request->user()->id,
            'module' => 'automation',
            'action' => 'trigger_overdue_detection',
            'description' => 'Detección de tareas vencidas ejecutada manualmente',
        ]);

        return response()->json([
            'message' => 'Detección de tareas vencidas ejecutada correctamente',
            'output' => trim(Artisan::output()),
        ]);
    }

    /**
     * Manually trigger daily summary.
     */
    public function triggerDailySummary(Request $request): JsonResponse
    {
        $this->authorizeSuperAdmin($request);

        $enabled = SystemSetting::getValue('daily_summary_enabled', true);
        if (!$enabled) {
            return response()->json([
                'message' => 'El resumen diario está desactivado. Actívelo en configuración antes de enviarlo.',
            ], 422);
        }

        Artisan::call('tasks:send-daily-summary');

        ActivityLog::create([
            'user_id' => $request->user()->id,
            'module' => 'automation',
            'action' => 'trigger_daily_summary',
            'description' => 'Resumen diario enviado manualmente',
        ]);

        return response()->json([
            'message' => 'Resumen diario enviado correctamente',
            'output' => trim(Artisan::output()),
        ]);
    }

    /**
     * Manually trigger due reminders.
     */
    public function triggerDueReminders(Request $request): JsonResponse
    {
        $this->authorizeSuperAdmin($request);

        $enabled = SystemSetting::getValue('emails_enabled', true);
        if (!$enabled) {
            return response()->json([
                'message' => 'Los correos automáticos están desactivados. Actívelos en configuración.',
            ], 422);
        }

        Artisan::call('tasks:send-due-reminders');

        ActivityLog::create([
            'user_id' => $request->user()->id,
            'module' => 'automation',
            'action' => 'trigger_due_reminders',
            'description' => 'Recordatorios de vencimiento enviados manualmente',
        ]);

        return response()->json([
            'message' => 'Recordatorios enviados correctamente',
            'output' => trim(Artisan::output()),
        ]);
    }

    private function authorizeSuperAdmin(Request $request): void
    {
        if (!$request->user()->isSuperAdmin()) {
            abort(403);
        }
    }
}
