<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\IndexAuditRequest;
use App\Http\Resources\AuditResource;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use OwenIt\Auditing\Models\Audit;

final class AuditController extends Controller
{
    public function index(IndexAuditRequest $request): AnonymousResourceCollection
    {
        $event = $request->validated('event');
        $userId = $request->validated('user_id');
        $from = $request->validated('from');
        $to = $request->validated('to');

        $audits = Audit::query()
            ->with('user')
            ->when(
                is_string($event) && $event !== '',
                fn ($query) => $query->where('event', $event),
            )
            ->when(
                filled($userId),
                fn ($query) => $query->where('user_id', $userId),
            )
            ->when(
                filled($from),
                fn ($query) => $query->whereDate('created_at', '>=', $from),
            )
            ->when(
                filled($to),
                fn ($query) => $query->whereDate('created_at', '<=', $to),
            )
            ->orderBy($request->sortColumn(), $request->sortDirection())
            ->paginate($request->perPage());

        return AuditResource::collection($audits);
    }
}
