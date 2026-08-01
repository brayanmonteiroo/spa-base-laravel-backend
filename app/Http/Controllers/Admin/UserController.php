<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreUserRequest;
use App\Http\Requests\Admin\UpdateUserRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;

final class UserController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', User::class);

        $perPage = min(max($request->integer('per_page', 10), 1), 100);

        $users = User::query()
            ->orderBy('name')
            ->paginate($perPage);

        return UserResource::collection($users);
    }

    public function store(StoreUserRequest $request): JsonResponse
    {
        $roles = $request->validated('roles');
        $data = $request->safe()->except(['roles']);

        $user = DB::transaction(function () use ($data, $roles): User {
            $user = User::query()->create($data);
            $user->syncAuditedRoles($roles);

            return $user;
        });

        return (new UserResource($user))
            ->response()
            ->setStatusCode(201);
    }

    public function show(User $user): UserResource
    {
        $this->authorize('view', $user);

        return new UserResource($user);
    }

    public function update(UpdateUserRequest $request, User $user): UserResource
    {
        $roles = $request->validated('roles');
        $data = $request->safe()->except(['roles']);

        if (! array_key_exists('password', $data) || blank($data['password'])) {
            unset($data['password']);
        }

        DB::transaction(function () use ($user, $data, $roles): void {
            $user->update($data);
            $user->syncAuditedRoles($roles);
        });

        return new UserResource($user->refresh());
    }

    public function destroy(User $user): Response
    {
        $this->authorize('delete', $user);

        $user->delete();

        return response()->noContent();
    }
}
