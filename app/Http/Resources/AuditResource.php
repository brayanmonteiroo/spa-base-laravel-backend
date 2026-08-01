<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\User;
use App\Support\AuditLabels;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use OwenIt\Auditing\Models\Audit;

/**
 * @mixin Audit
 */
final class AuditResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $user = $this->user;

        return [
            'id' => $this->id,
            'event' => $this->event,
            'event_label' => AuditLabels::eventLabel((string) $this->event),
            'auditable_type' => $this->auditable_type,
            'auditable_label' => AuditLabels::auditableLabel(
                $this->auditable_type !== null ? (string) $this->auditable_type : null,
            ),
            'auditable_id' => $this->auditable_id,
            'user' => $user instanceof User
                ? [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                ]
                : null,
            'ip_address' => $this->ip_address,
            'url' => $this->url,
            'user_agent' => $this->user_agent,
            'old_values' => $this->old_values,
            'new_values' => $this->new_values,
            'created_at' => $this->created_at,
        ];
    }
}
