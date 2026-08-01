<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use OwenIt\Auditing\Models\Audit;

final class IndexAuditRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('viewAny', Audit::class) ?? false;
    }

    /**
     * Regras de validação.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
            'sort' => ['sometimes', 'nullable', 'string', Rule::in(['id', 'created_at', 'event'])],
            'direction' => ['sometimes', 'nullable', 'string', Rule::in(['asc', 'desc'])],
            'event' => [
                'sometimes',
                'nullable',
                'string',
                Rule::in([
                    'created',
                    'updated',
                    'deleted',
                    'restored',
                    'roles_updated',
                    'sync',
                ]),
            ],
            'user_id' => ['sometimes', 'nullable', 'integer', 'exists:users,id'],
            'from' => ['sometimes', 'nullable', 'date'],
            'to' => ['sometimes', 'nullable', 'date', 'after_or_equal:from'],
        ];
    }

    /**
     * Limite de registros por página.
     */
    public function perPage(): int
    {
        return min(max($this->integer('per_page', 10), 1), 100);
    }

    /**
     * Coluna de ordenação.
     */
    public function sortColumn(string $default = 'id'): string
    {
        $sort = $this->validated('sort');

        return is_string($sort) && $sort !== '' ? $sort : $default;
    }

    /**
     * Direção de ordenação.
     */
    public function sortDirection(string $default = 'desc'): string
    {
        $direction = $this->validated('direction');

        return is_string($direction) && $direction !== '' ? $direction : $default;
    }
}
