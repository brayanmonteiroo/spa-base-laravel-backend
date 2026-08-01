<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\AuditResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use OwenIt\Auditing\Models\Audit;

final class AuditController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', Audit::class);

        $perPage = min(max($request->integer('per_page', 10), 1), 100);

        $audits = Audit::query()
            ->with('user')
            ->orderByDesc('created_at')
            ->paginate($perPage);

        return AuditResource::collection($audits);
    }
}
