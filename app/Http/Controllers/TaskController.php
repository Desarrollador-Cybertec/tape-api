<?php

namespace App\Http\Controllers;

use App\Enums\AttachmentTypeEnum;
use App\Http\Requests\ApproveTaskRequest;
use App\Http\Requests\DelegateTaskRequest;
use App\Http\Requests\RejectTaskRequest;
use App\Http\Requests\StoreTaskAttachmentRequest;
use App\Http\Requests\StoreTaskCommentRequest;
use App\Http\Requests\StoreTaskRequest;
use App\Http\Requests\StoreTaskUpdateRequest;
use App\Http\Requests\UpdateTaskRequest;
use App\Http\Resources\TaskAttachmentResource;
use App\Http\Resources\TaskCommentResource;
use App\Http\Resources\TaskResource;
use App\Http\Resources\TaskUpdateResource;
use App\Models\Task;
use App\Models\TaskAttachment;
use App\Models\TaskComment;
use App\Models\TaskUpdate;
use App\Services\TaskCompletionService;
use App\Services\TaskCreationService;
use App\Services\TaskDelegationService;
use App\Services\TaskStatusService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Storage;

class TaskController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $user = $request->user();

        $tasks = Task::with(['creator', 'currentResponsible', 'area', 'latestUpdate'])
            ->withCount(['comments', 'attachments'])
            ->when(!$user->isSuperAdmin(), function ($q) use ($user) {
                $q->where(function ($query) use ($user) {
                    $query->where('created_by', $user->id)
                        ->orWhere('assigned_to_user_id', $user->id)
                        ->orWhere('current_responsible_user_id', $user->id)
                        ->orWhereIn('area_id', $user->managedAreas()->pluck('id'));
                });
            })
            ->when($request->query('status'), fn ($q, $status) =>
                $q->where('status', $status)
            )
            ->when($request->query('priority'), fn ($q, $priority) =>
                $q->where('priority', $priority)
            )
            ->when($request->query('area_id'), fn ($q, $areaId) =>
                $q->where('area_id', $areaId)
            )
            ->when($request->query('sort'), function ($q, $sort) {
                return match ($sort) {
                    'oldest' => $q->oldest(),
                    'due_date' => $q->orderBy('due_date'),
                    'priority' => $q->orderByRaw("CASE priority WHEN 'critical' THEN 1 WHEN 'high' THEN 2 WHEN 'medium' THEN 3 WHEN 'low' THEN 4 ELSE 5 END"),
                    default => $q->latest(),
                };
            }, fn ($q) => $q->latest())
            ->paginate(20);

        return TaskResource::collection($tasks);
    }

    public function store(StoreTaskRequest $request, TaskCreationService $service): JsonResponse
    {
        $task = $service->create($request->validated(), $request->user());

        return response()->json(
            new TaskResource($task->load(['creator', 'assignedUser', 'assignedArea', 'area', 'latestUpdate'])),
            201
        );
    }

    public function show(Task $task): TaskResource
    {
        $this->authorize('view', $task);

        return new TaskResource(
            $task->load([
                'creator',
                'assigner',
                'assignedUser',
                'assignedArea',
                'delegator',
                'currentResponsible',
                'area',
                'meeting',
                'comments.user',
                'attachments.uploader',
                'delegations.fromUser',
                'delegations.toUser',
                'statusHistory.changedByUser',
                'updates.user',
                'latestUpdate',
            ])->loadCount(['comments', 'attachments', 'updates'])
        );
    }

    public function update(UpdateTaskRequest $request, Task $task): TaskResource
    {
        $task->update($request->validated());

        return new TaskResource($task->fresh(['creator', 'currentResponsible', 'area', 'latestUpdate']));
    }

    public function delegate(DelegateTaskRequest $request, Task $task, TaskDelegationService $service): TaskResource
    {
        $task = $service->delegate(
            $task,
            $request->user(),
            $request->to_user_id,
            $request->note
        );

        return new TaskResource($task->load(['currentResponsible', 'delegator', 'area', 'latestUpdate']));
    }

    public function start(Task $task, TaskStatusService $service): TaskResource
    {
        $this->authorize('start', $task);

        $task = $service->start($task, request()->user());

        return new TaskResource($task->load(['currentResponsible', 'area', 'latestUpdate']));
    }

    public function submitForReview(Task $task, TaskCompletionService $service): TaskResource
    {
        $this->authorize('submitForReview', $task);

        $task = $service->submitForReview($task, request()->user());

        return new TaskResource($task->load(['currentResponsible', 'area', 'latestUpdate']));
    }

    public function approve(ApproveTaskRequest $request, Task $task, TaskCompletionService $service): TaskResource
    {
        $task = $service->approve($task, $request->user(), $request->note);

        return new TaskResource($task->load(['currentResponsible', 'area', 'latestUpdate']));
    }

    public function reject(RejectTaskRequest $request, Task $task, TaskCompletionService $service): TaskResource
    {
        $task = $service->reject($task, $request->user(), $request->note);

        return new TaskResource($task->load(['currentResponsible', 'area', 'latestUpdate']));
    }

    public function cancel(Task $task, TaskStatusService $service): TaskResource
    {
        $this->authorize('cancel', $task);

        $task = $service->cancel($task, request()->user());

        return new TaskResource($task->load(['currentResponsible', 'area', 'latestUpdate']));
    }

    public function comment(StoreTaskCommentRequest $request, Task $task): JsonResponse
    {
        $comment = TaskComment::create([
            'task_id' => $task->id,
            'user_id' => $request->user()->id,
            'comment' => $request->comment,
            'type' => $request->type ?? 'comment',
        ]);

        return response()->json(
            new TaskCommentResource($comment->load('user')),
            201
        );
    }

    public function addAttachment(StoreTaskAttachmentRequest $request, Task $task): JsonResponse
    {
        $file = $request->file('file');
        $path = $file->store("tasks/{$task->id}", 'local');

        $attachment = TaskAttachment::create([
            'task_id' => $task->id,
            'uploaded_by' => $request->user()->id,
            'file_name' => $file->getClientOriginalName(),
            'file_path' => $path,
            'mime_type' => $file->getMimeType(),
            'file_size' => $file->getSize(),
            'attachment_type' => $request->attachment_type ?? AttachmentTypeEnum::SUPPORT->value,
        ]);

        return response()->json(
            new TaskAttachmentResource($attachment->load('uploader')),
            201
        );
    }

    public function addUpdate(StoreTaskUpdateRequest $request, Task $task): JsonResponse
    {
        $update = TaskUpdate::create([
            'task_id' => $task->id,
            'user_id' => $request->user()->id,
            'update_type' => $request->update_type ?? 'progress',
            'comment' => $request->comment,
            'progress_percent' => $request->progress_percent,
        ]);

        if ($request->progress_percent !== null) {
            $task->update(['progress_percent' => $request->progress_percent]);
        }

        return response()->json(
            new TaskUpdateResource($update->load('user')),
            201
        );
    }
}
