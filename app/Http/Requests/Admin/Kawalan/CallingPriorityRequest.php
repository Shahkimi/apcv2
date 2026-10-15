<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Kawalan;

use App\Services\Kehadiran\CallingPriorityService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CallingPriorityRequest extends FormRequest
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
        $groups = CallingPriorityService::DEFAULT_ORDER;

        return [
            'order' => ['required', 'array', 'size:'.count($groups)],
            'order.*' => ['required', 'string', 'distinct', Rule::in($groups)],
        ];
    }

    /**
     * @return list<string>
     */
    public function order(): array
    {
        return array_values($this->validated('order'));
    }
}
