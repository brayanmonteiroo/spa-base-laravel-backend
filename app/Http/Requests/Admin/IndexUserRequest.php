<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class IndexUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('viewAny', User::class) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
            'sort' => ['sometimes', 'nullable', 'string', Rule::in(['id', 'name', 'email'])],
            'direction' => ['sometimes', 'nullable', 'string', Rule::in(['asc', 'desc'])],
            'q' => ['sometimes', 'nullable', 'string', 'max:255'],
            'role' => [
                'sometimes',
                'nullable',
                'string',
                Rule::exists('roles', 'name')->where(
                    fn ($query) => $query->where('guard_name', 'web'),
                ),
            ],
        ];
    }

    public function perPage(): int
    {
        return min(max($this->integer('per_page', 10), 1), 100);
    }

    public function sortColumn(string $default = 'name'): string
    {
        $sort = $this->validated('sort');

        return is_string($sort) && $sort !== '' ? $sort : $default;
    }

    public function sortDirection(string $default = 'asc'): string
    {
        $direction = $this->validated('direction');

        return is_string($direction) && $direction !== '' ? $direction : $default;
    }
}
