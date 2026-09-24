<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Kawalan;

use App\Services\DatabaseImportService;
use App\Services\EventModeService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class DatabaseImportMapRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    private function mode(): string
    {
        return app(EventModeService::class)->current();
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        $mode = $this->mode();
        $requiredMappedFields = DatabaseImportService::requiredMappedFieldsFor($mode);
        $fillableFields = DatabaseImportService::fillableFieldsFor($mode);
        $optionalPolicyFields = DatabaseImportService::optionalPolicyFieldsFor($mode);

        $rules = [
            'mapping' => ['required', 'array'],
            'empty_policy' => ['required', 'array'],
        ];

        foreach ($requiredMappedFields as $field) {
            $rules['mapping.'.$field] = ['required', 'string', 'min:1', 'max:255'];
        }

        foreach ($fillableFields as $field) {
            if (in_array($field, $requiredMappedFields, true)) {
                continue;
            }
            $rules['mapping.'.$field] = ['nullable', 'string', 'max:255'];
        }

        foreach ($optionalPolicyFields as $field) {
            $rules['empty_policy.'.$field] = [
                'required',
                Rule::in([DatabaseImportService::POLICY_ZERO, DatabaseImportService::POLICY_NULL]),
            ];
        }

        return $rules;
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $mode = $this->mode();

            /** @var array<string, mixed> $mapping */
            $mapping = $this->input('mapping', []);
            foreach (DatabaseImportService::fillableFieldsFor($mode) as $field) {
                if (! array_key_exists($field, $mapping)) {
                    $validator->errors()->add('mapping', __('Medan :field hilang dalam pemetaan.', ['field' => $field]));
                }
            }

            /** @var array<string, mixed> $policy */
            $policy = $this->input('empty_policy', []);
            foreach (DatabaseImportService::optionalPolicyFieldsFor($mode) as $field) {
                if (! array_key_exists($field, $policy)) {
                    $validator->errors()->add('empty_policy', __('Dasar kosong untuk :field diperlukan.', ['field' => $field]));
                }
            }

            /** @var array<string, mixed>|null $session */
            $session = session(DatabaseImportService::SESSION_KEY);
            $headers = $session['headers'] ?? [];
            if (! is_array($headers)) {
                return;
            }

            foreach ($mapping as $field => $header) {
                if (! is_string($field) || ! is_string($header)) {
                    continue;
                }
                if ($header === '') {
                    continue;
                }
                if (! in_array($header, $headers, true)) {
                    $validator->errors()->add(
                        'mapping.'.$field,
                        __('Lajur ":header" tidak wujud dalam fail.', ['header' => $header])
                    );
                }
            }
        });
    }
}
