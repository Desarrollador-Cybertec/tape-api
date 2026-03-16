<?php

namespace App\Http\Controllers;

use App\Enums\TaskStatusEnum;
use App\Models\Area;
use App\Models\Task;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function general(Request $request): JsonResponse
    {
        if (!$request->user()->isSuperAdmin()) {
            abort(403);
        }

        $tasksByStatus = Task::select('status', DB::raw('count(*) as total'))
            ->groupBy('status')
            ->pluck('total', 'status');

        $tasksByArea = Task::select('area_id', DB::raw('count(*) as total'))
            ->whereNotNull('area_id')
            ->groupBy('area_id')
            ->get()
            ->map(function ($item) {
                $area = Area::find($item->area_id);
                return [
                    'area_id' => $item->area_id,
                    'area_name' => $area?->name,
                    'total' => $item->total,
                ];
            });

        $overdueTasks = Task::where('due_date', '<', now())
            ->whereNotIn('status', [
                TaskStatusEnum::COMPLETED->value,
                TaskStatusEnum::CANCELLED->value,
            ])
            ->count();

        $completedThisMonth = Task::where('status', TaskStatusEnum::COMPLETED->value)
            ->where('completed_at', '>=', now()->startOfMonth())
            ->count();

        $totalActive = Task::whereNotIn('status', [
            TaskStatusEnum::COMPLETED->value,
            TaskStatusEnum::CANCELLED->value,
        ])->count();

        $totalCompleted = Task::where('status', TaskStatusEnum::COMPLETED->value)->count();
        $totalAll = Task::count();
        $completionRate = $totalAll > 0 ? round(($totalCompleted / $totalAll) * 100, 1) : 0;

        $avgCloseDays = Task::where('status', TaskStatusEnum::COMPLETED->value)
            ->whereNotNull('completed_at')
            ->selectRaw('AVG(JULIANDAY(completed_at) - JULIANDAY(created_at)) as avg_days')
            ->value('avg_days');

        $topResponsibles = Task::select('current_responsible_user_id', DB::raw('count(*) as total'))
            ->whereNotNull('current_responsible_user_id')
            ->whereNotIn('status', [
                TaskStatusEnum::COMPLETED->value,
                TaskStatusEnum::CANCELLED->value,
            ])
            ->groupBy('current_responsible_user_id')
            ->orderByDesc('total')
            ->limit(10)
            ->get()
            ->map(function ($item) {
                $user = User::find($item->current_responsible_user_id);
                return [
                    'user_id' => $item->current_responsible_user_id,
                    'user_name' => $user?->name,
                    'active_tasks' => $item->total,
                ];
            });

        $dueSoon = Task::where('due_date', '>=', now())
            ->where('due_date', '<=', now()->addDays(3))
            ->whereNotIn('status', [
                TaskStatusEnum::COMPLETED->value,
                TaskStatusEnum::CANCELLED->value,
            ])
            ->count();

        return response()->json([
            'tasks_by_status' => $tasksByStatus,
            'tasks_by_area' => $tasksByArea,
            'overdue_tasks' => $overdueTasks,
            'due_soon' => $dueSoon,
            'completed_this_month' => $completedThisMonth,
            'total_active' => $totalActive,
            'total_completed' => $totalCompleted,
            'total_all' => $totalAll,
            'completion_rate' => $completionRate,
            'avg_close_days' => $avgCloseDays ? round($avgCloseDays, 1) : null,
            'top_responsibles' => $topResponsibles,
        ]);
    }

    public function byArea(Request $request, Area $area): JsonResponse
    {
        $user = $request->user();

        if (!$user->isSuperAdmin() && !$user->isManagerOfArea($area->id)) {
            abort(403);
        }

        $tasks = Task::where('area_id', $area->id);

        $byStatus = (clone $tasks)
            ->select('status', DB::raw('count(*) as total'))
            ->groupBy('status')
            ->pluck('total', 'status');

        $overdue = (clone $tasks)
            ->where('due_date', '<', now())
            ->whereNotIn('status', [TaskStatusEnum::COMPLETED->value, TaskStatusEnum::CANCELLED->value])
            ->count();

        $byResponsible = (clone $tasks)
            ->select('current_responsible_user_id', DB::raw('count(*) as total'))
            ->whereNotNull('current_responsible_user_id')
            ->whereNotIn('status', [TaskStatusEnum::COMPLETED->value, TaskStatusEnum::CANCELLED->value])
            ->groupBy('current_responsible_user_id')
            ->get()
            ->map(function ($item) {
                $user = User::find($item->current_responsible_user_id);
                return [
                    'user_id' => $item->current_responsible_user_id,
                    'user_name' => $user?->name,
                    'active_tasks' => $item->total,
                ];
            });

        $totalArea = (clone $tasks)->count();
        $completedArea = (clone $tasks)->where('status', TaskStatusEnum::COMPLETED->value)->count();
        $completionRate = $totalArea > 0 ? round(($completedArea / $totalArea) * 100, 1) : 0;

        $withoutProgress = (clone $tasks)
            ->whereNotIn('status', [TaskStatusEnum::COMPLETED->value, TaskStatusEnum::CANCELLED->value])
            ->whereDoesntHave('updates')
            ->count();

        return response()->json([
            'area' => ['id' => $area->id, 'name' => $area->name],
            'tasks_by_status' => $byStatus,
            'overdue_tasks' => $overdue,
            'by_responsible' => $byResponsible,
            'total_tasks' => $totalArea,
            'completed_tasks' => $completedArea,
            'completion_rate' => $completionRate,
            'without_progress' => $withoutProgress,
        ]);
    }

    public function myDashboard(Request $request): JsonResponse
    {
        $user = $request->user();

        $myTasks = Task::where('current_responsible_user_id', $user->id);

        $active = (clone $myTasks)
            ->whereNotIn('status', [TaskStatusEnum::COMPLETED->value, TaskStatusEnum::CANCELLED->value])
            ->count();

        $overdue = (clone $myTasks)
            ->where('due_date', '<', now())
            ->whereNotIn('status', [TaskStatusEnum::COMPLETED->value, TaskStatusEnum::CANCELLED->value])
            ->count();

        $dueSoon = (clone $myTasks)
            ->where('due_date', '>=', now())
            ->where('due_date', '<=', now()->addDays(3))
            ->whereNotIn('status', [TaskStatusEnum::COMPLETED->value, TaskStatusEnum::CANCELLED->value])
            ->count();

        $completed = (clone $myTasks)
            ->where('status', TaskStatusEnum::COMPLETED->value)
            ->count();

        $byStatus = (clone $myTasks)
            ->select('status', DB::raw('count(*) as total'))
            ->groupBy('status')
            ->pluck('total', 'status');

        $recentTasks = Task::where('current_responsible_user_id', $user->id)
            ->whereNotIn('status', [TaskStatusEnum::COMPLETED->value, TaskStatusEnum::CANCELLED->value])
            ->orderBy('due_date')
            ->limit(10)
            ->get()
            ->map(fn ($task) => [
                'id' => $task->id,
                'title' => $task->title,
                'status' => $task->status,
                'priority' => $task->priority,
                'due_date' => $task->due_date?->toDateString(),
                'is_overdue' => $task->isOverdue(),
                'progress_percent' => $task->progress_percent,
            ]);

        return response()->json([
            'active_tasks' => $active,
            'overdue_tasks' => $overdue,
            'due_soon' => $dueSoon,
            'completed_tasks' => $completed,
            'tasks_by_status' => $byStatus,
            'upcoming_tasks' => $recentTasks,
        ]);
    }
}
