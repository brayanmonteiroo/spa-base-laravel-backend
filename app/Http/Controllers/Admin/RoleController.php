<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Enums\PermissionName;
use App\Enums\RoleName;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\IndexRoleRequest;
use App\Http\Requests\Admin\StoreRoleRequest;
use App\Http\Requests\Admin\UpdateRoleRequest;
use App\Http\Resources\RoleResource;
use App\Models\Role;
use App\Models\User;
use App\Support\PermissionCatalog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class RoleController extends Controller
{
    /**
     * Lista perfis paginados.
     */
    public function index(IndexRoleRequest $request): AnonymousResourceCollection
    {
        $search = $request->validated('q');

        $roles = Role::query()
            ->where('guard_name', 'web')
            ->with('permissions')
            ->addSelect([
                'users_count' => DB::table('model_has_roles')
                    ->selectRaw('count(*)')
                    ->whereColumn('role_id', 'roles.id')
                    ->where('model_type', (new User)->getMorphClass()),
            ])
            ->when(
                is_string($search) && $search !== '',
                function ($query) use ($search): void {
                    $term = '%'.addcslashes($search, '%_\\').'%';
                    $query->where('name', 'ilike', $term);
                },
            )
            ->orderBy($request->sortColumn(), $request->sortDirection())
            ->paginate($request->perPage());

        return RoleResource::collection($roles);
    }
    /**
     * Cria um novo perfil.
     */
    public function store(StoreRoleRequest $request): JsonResponse
    {
        $data = $request->validated();

        $role = Role::create([
            'name' => $data['name'],
            'guard_name' => 'web',
        ]);

        $role->syncPermissions($data['permissions']);

        return (new RoleResource($this->presentRole($role)))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Exibe detalhes de um perfil específico.
     */
    public function show(Role $role): RoleResource
    {
        $this->authorize('view', $role);

        return new RoleResource($this->presentRole($role));
    }

    /**
     * Atualiza um perfil existente.
     */
    public function update(UpdateRoleRequest $request, Role $role): RoleResource
    {
        $data = $request->validated();

        $role->update([
            'name' => $data['name'],
        ]);

        $role->syncPermissions($data['permissions']);

        return new RoleResource($this->presentRole($role->refresh()));
    }

    /**
     * Exclui um perfil existente.
     */
    public function destroy(Role $role): Response
    {
        $this->authorize('delete', $role);

        if ($role->name === RoleName::Admin->value) {
            throw ValidationException::withMessages([
                'role' => ['O perfil admin não pode ser excluído.'],
            ]);
        }

        if ($role->users()->count() > 0) {
            throw ValidationException::withMessages([
                'role' => ['Não é possível excluir um perfil que ainda possui usuários.'],
            ]);
        }

        $role->delete();

        return response()->noContent();
    }

    /**
     * Lista de permissões.
     */
    public function catalog(Request $request): JsonResponse
    {
        $user = $request->user();

        abort_unless(
            $user !== null && (
                $user->can(PermissionName::RolesCreate->value)
                || $user->can(PermissionName::RolesUpdate->value)
            ),
            403
        );

        return response()->json([
            'data' => PermissionCatalog::sections(),
        ]);
    }

    /**
     * Apresenta um perfil.
     */
    private function presentRole(Role $role): Role
    {
        $role->load('permissions');
        $role->setAttribute('users_count', $role->users()->count());

        return $role;
    }
}
