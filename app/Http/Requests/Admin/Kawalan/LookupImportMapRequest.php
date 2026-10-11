<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Kawalan;

use App\Services\Import\LookupDataset;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class LookupImportMapRequest extends FormRequest
{
    public const CONFIRM_WORD = 'RESET';

    public function authorize(): bool
    {
        return true;
    }

    public function dataset(): LookupDataset
    {
        return LookupDataset::find((string) $this->route('dataset'));
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        $rules = [
            'mapping' => ['required', 'array'],
            'mapping.'.$this->dataset()->column => ['required', 'string', 'min:1', 'max:255'],
        ];

        if ($this->isImport()) {
            $rules['confirm'] = ['required', 'string', 'in:'.self::CONFIRM_WORD];
        }

        return $rules;
    }

    public function messages(): array
    {
        return [
            'confirm.required' => __('Taip :word untuk mengesahkan.', ['word' => self::CONFIRM_WORD]),
            'confirm.in' => __('Taip :word untuk mengesahkan.', ['word' => self::CONFIRM_WORD]),
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $dataset = $this->dataset();
            $header = $this->input('mapping.'.$dataset->column);
            /** @var array<string, mixed>|null $session */
            $session = session($dataset->sessionKey());
            $headers = $session['headers'] ?? [];

            if (is_string($header) && $header !== '' && is_array($headers) && $headers !== [] && ! in_array($header, $headers, true)) {
                $validator->errors()->add(
                    'mapping.'.$dataset->column,
                    __('Lajur ":header" tidak wujud dalam fail.', ['header' => $header])
                );
            }
        });
    }

    private function isImport(): bool
    {
        return $this->route()?->getName() === 'admin.kawalan.database.lookup.import';
    }
}
