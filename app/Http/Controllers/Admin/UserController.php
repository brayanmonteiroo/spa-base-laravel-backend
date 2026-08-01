<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\IndexUserRequest;
use App\Http\Requests\Admin\StoreUserRequest;
use App\Http\Requests\Admin\UpdateUserRequest;
use App\Http\Resources\UserListResource;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;

final class UserController extends Controller
{
    /**
     * Lista usuários paginados.
     */
    public function index(IndexUserRequest $request): AnonymousResourceCollection
    {
        $search = $request->validated('q');
        $role = $request->validated('role');

        $users = User::query()
            ->with('roles')
            ->when(
                is_string($search) && $search !== '',
                function ($query) use ($search): void {
                    $term = '%'.addcslashes($search, '%_\\').'%';

                    $query->where(function ($builder) use ($term): void {
                        $builder->where('name', 'ilike', $term)
                            ->orWhere('email', 'ilike', $term);
                    });
                },
            )
            ->when(
                is_string($role) && $role !== '',
                fn ($query) => $query->role($role),
            )
            ->orderBy($request->sortColumn(), $request->sortDirection())
            ->paginate($request->perPage());

        return UserListResource::collection($users);
    }

    /**
     * Cria um novo usuário.
     */
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

    /**
     * Exibe detalhes de um usuário específico.
     */
    public function show(User $user): UserResource
    {
        $this->authorize('view', $user);

        return new UserResource($user);
    }

    /**
     * Atualiza um usuário existente.
     */
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

    /**
     * Exclui um usuário existente.
     */
    public function destroy(User $user): Response
    {
        $this->authorize('delete', $user);

        $user->delete();

        return response()->noContent();
    }
}
