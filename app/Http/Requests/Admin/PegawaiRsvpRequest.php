<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class PegawaiRsvpRequest extends FormRequest
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
            'rsvp' => ['required', 'boolean'],
            'no_kerusi' => ['nullable', 'required_if_accepted:rsvp', 'integer', 'min:1', 'max:4294967295'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'rsvp' => __('RSVP'),
            'no_kerusi' => __('no. kerusi'),
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'no_kerusi.required_if_accepted' => __('No. kerusi diperlukan untuk pegawai RSVP Hadir.'),
            'no_kerusi.min' => __('No. kerusi mesti sekurang-kurangnya 1.'),
        ];
    }

    public function rsvp(): bool
    {
        return $this->boolean('rsvp');
    }

    public function noKerusi(): ?int
    {
        return $this->rsvp() ? (int) $this->validated('no_kerusi') : null;
    }
}
