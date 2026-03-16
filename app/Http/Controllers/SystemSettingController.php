<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateSystemSettingsRequest;
use App\Http\Resources\SystemSettingResource;
use App\Models\SystemSetting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SystemSettingController extends Controller
{
    /**
     * List all settings, optionally filtered by group.
     */
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', SystemSetting::class);

        $query = SystemSetting::query();

        if ($group = $request->query('group')) {
            $query->where('group', $group);
        }

        $settings = $query->orderBy('group')->orderBy('key')->get();

        // Group settings by their group field
        $grouped = $settings->groupBy('group')->map(
            fn ($items) => SystemSettingResource::collection($items)
        );

        return response()->json(['data' => $grouped]);
    }

    /**
     * Bulk update settings.
     */
    public function update(UpdateSystemSettingsRequest $request): JsonResponse
    {
        foreach ($request->settings as $item) {
            SystemSetting::setValue($item['key'], $item['value']);
        }

        return response()->json(['message' => 'Configuración actualizada correctamente']);
    }
}
