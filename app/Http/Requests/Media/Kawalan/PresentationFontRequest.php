<?php

declare(strict_types=1);

namespace App\Http\Requests\Media\Kawalan;

use App\Models\PresentationFont;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PresentationFontRequest extends FormRequest
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
        /** @var PresentationFont|null $font */
        $font = $this->route('presentationFont');

        return [
            'name' => [
                'required',
                'string',
                'max:60',
                Rule::unique('presentation_fonts', 'name')->ignore($font?->id),
            ],
            // The real format is checked from the file signature in PresentationFontService.
            'font_file' => [
                $font === null ? 'required' : 'nullable',
                'file',
                'extensions:ttf,otf,woff,woff2',
                'max:'.PresentationFont::MAX_FILE_KB,
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'name' => __('nama fon'),
            'font_file' => __('fail fon'),
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'font_file.extensions' => __('Hanya fail TTF, OTF, WOFF atau WOFF2 dibenarkan.'),
            'font_file.max' => __('Saiz fail fon maksimum 10 MB.'),
            'name.unique' => __('Nama fon ini sudah digunakan.'),
        ];
    }
}
