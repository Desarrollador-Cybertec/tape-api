<?php

namespace App\Http\Controllers;

use App\Http\Requests\ClaimWorkerRequest;
use App\Http\Requests\StoreAreaRequest;
use App\Http\Requests\UpdateAreaRequest;
use App\Http\Resources\AreaResource;
use App\Models\Area;
use App\Services\AreaClaimService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class AreaController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', Area::class);

        $user = $request->user();

        $areas = Area::with('manager')
            ->withCount('activeMembers')
            ->when(!$user->isSuperAdmin(), function ($q) use ($user) {
                $q->where(function ($query) use ($user) {
                    $query->where('manager_user_id', $user->id)
                        ->orWhereHas('activeMembers', fn ($mq) => $mq->where('users.id', $user->id));
                });
            })
            ->when($request->has('active'), fn ($q) =>
                $q->where('active', $request->boolean('active'))
            )
            ->orderBy('name')
            ->paginate(20);

        return AreaResource::collection($areas);
    }

    public function store(StoreAreaRequest $request): JsonResponse
    {
        $area = Area::create($request->validated());

        return response()->json(
            new AreaResource($area->load('manager')),
            201
        );
    }

    public function show(Area $area): AreaResource
    {
        $this->authorize('view', $area);

        return new AreaResource(
            $area->load(['manager', 'activeMembers.role'])
                ->loadCount('activeMembers')
        );
    }

    public function update(UpdateAreaRequest $request, Area $area): AreaResource
    {
        $area->update($request->validated());

        return new AreaResource($area->fresh('manager'));
    }

    public function assignManager(Request $request, Area $area): AreaResource
    {
        $this->authorize('assignManager', $area);

        $request->validate([
            'manager_user_id' => ['required', 'exists:users,id'],
        ]);

        $area->update(['manager_user_id' => $request->manager_user_id]);

        return new AreaResource($area->fresh('manager'));
    }

    public function claimWorker(ClaimWorkerRequest $request, AreaClaimService $service): JsonResponse
    {
        $member = $service->claimWorker(
            $request->user_id,
            $request->area_id,
            $request->user()
        );

        return response()->json([
            'message' => 'Trabajador reclamado exitosamente.',
            'member' => $member->load(['user', 'area']),
        ], 201);
    }
}
