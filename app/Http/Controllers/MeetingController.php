<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreMeetingRequest;
use App\Http\Requests\UpdateMeetingRequest;
use App\Http\Resources\MeetingResource;
use App\Models\ActivityLog;
use App\Models\Area;
use App\Models\Meeting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class MeetingController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', Meeting::class);

        $user = $request->user();

        $meetings = Meeting::with(['area', 'creator'])
            ->withCount('tasks')
            ->when(!$user->isSuperAdmin(), function ($q) use ($user) {
                $q->where(function ($query) use ($user) {
                    $query->where('created_by', $user->id)
                        ->orWhereIn('area_id', Area::where('manager_user_id', $user->id)->select('id'));
                });
            })
            ->when($request->query('area_id'), fn ($q, $areaId) => $q->where('area_id', $areaId))
            ->when($request->query('classification'), fn ($q, $c) => $q->where('classification', $c))
            ->latest('meeting_date')
            ->paginate(20);

        return MeetingResource::collection($meetings);
    }

    public function store(StoreMeetingRequest $request): JsonResponse
    {
        $meeting = Meeting::create([
            ...$request->validated(),
            'created_by' => $request->user()->id,
        ]);

        ActivityLog::create([
            'user_id' => $request->user()->id,
            'module' => 'meetings',
            'action' => 'created',
            'subject_type' => Meeting::class,
            'subject_id' => $meeting->id,
            'description' => "Reunión \"{$meeting->title}\" creada",
        ]);

        return response()->json(
            new MeetingResource($meeting->load(['area', 'creator'])),
            201
        );
    }

    public function show(Meeting $meeting): MeetingResource
    {
        $this->authorize('view', $meeting);

        return new MeetingResource(
            $meeting->load([
                'area',
                'creator',
                'tasks.currentResponsible',
                'tasks.area',
            ])->loadCount('tasks')
        );
    }

    public function update(UpdateMeetingRequest $request, Meeting $meeting): MeetingResource
    {
        $meeting->update($request->validated());

        return new MeetingResource($meeting->fresh(['area', 'creator']));
    }

    public function destroy(Meeting $meeting): JsonResponse
    {
        $this->authorize('delete', $meeting);

        $meeting->delete();

        return response()->json(['message' => 'Reunión eliminada exitosamente.']);
    }
}
