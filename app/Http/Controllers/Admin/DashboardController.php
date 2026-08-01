<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Enums\PermissionName;
use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OwenIt\Auditing\Models\Audit;
use Spatie\Permission\Models\Permission;

final class DashboardController extends Controller
{
    /**
     * Totais do painel (cards).
     */
    public function stats(Request $request): JsonResponse
    {
        abort_unless(
            $request->user()?->can(PermissionName::DashboardCards->value) ?? false,
            403,
        );

        $todayStart = Carbon::today()->startOfDay();
        $todayEnd = Carbon::today()->endOfDay();

        return response()->json([
            'data' => [
                'users' => User::query()->count(),
                'roles' => Role::query()->where('guard_name', 'web')->count(),
                'permissions' => Permission::query()->where('guard_name', 'web')->count(),
                'audits_today' => Audit::query()
                    ->whereBetween('created_at', [$todayStart, $todayEnd])
                    ->count(),
            ],
        ]);
    }
}
