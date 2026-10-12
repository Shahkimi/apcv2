<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Kawalan;

use App\Services\Kawalan\SystemService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SystemWipeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'tables' => ['required', 'array', 'min:1'],
            'tables.*' => ['required', 'string', 'distinct', Rule::in(array_keys(SystemService::WIPE_TABLES))],
        ];
    }

    public function messages(): array
    {
        return [
            'tables.required' => __('Pilih sekurang-kurangnya satu jenis data untuk dipadam.'),
            'tables.min' => __('Pilih sekurang-kurangnya satu jenis data untuk dipadam.'),
            'tables.*.in' => __('Jenis data tidak sah.'),
            'tables.*.distinct' => __('Jenis data berulang.'),
        ];
    }

    /**
     * @return list<string>
     */
    public function tables(): array
    {
        return array_values(array_map('strval', (array) $this->validated('tables')));
    }
}
