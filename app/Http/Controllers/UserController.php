<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Http\Resources\UserResource;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class UserController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', User::class);

        $users = User::with('role')
            ->when($request->query('search'), fn ($q, $search) =>
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
            )
            ->when($request->query('role'), fn ($q, $role) =>
                $q->whereHas('role', fn ($rq) => $rq->where('slug', $role))
            )
            ->when($request->has('active'), fn ($q) =>
                $q->where('active', $request->boolean('active'))
            )
            ->orderBy('name')
            ->paginate(20);

        return UserResource::collection($users);
    }

    public function store(StoreUserRequest $request): JsonResponse
    {
        $user = User::create($request->validated());

        return response()->json(
            new UserResource($user->load('role')),
            201
        );
    }

    public function show(User $user): UserResource
    {
        $this->authorize('view', $user);

        return new UserResource($user->load(['role', 'activeAreas']));
    }

    public function update(UpdateUserRequest $request, User $user): UserResource
    {
        $user->update($request->validated());

        return new UserResource($user->fresh('role'));
    }

    public function updateRole(Request $request, User $user): UserResource
    {
        $this->authorize('updateRole', $user);

        $request->validate([
            'role_id' => ['required', 'exists:roles,id'],
        ]);

        $user->update(['role_id' => $request->role_id]);

        return new UserResource($user->fresh('role'));
    }

    public function toggleActive(User $user): UserResource
    {
        $this->authorize('updateRole', $user);

        $user->update(['active' => !$user->active]);

        return new UserResource($user->fresh('role'));
    }
}
