<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Kawalan;

use Illuminate\Foundation\Http\FormRequest;

class DatabaseImportUploadRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'file' => [
                'required',
                'file',
                'mimes:csv,txt,xlsx',
                'mimetypes:text/plain,text/csv,application/csv,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'max:10240',
            ],
        ];
    }
}
