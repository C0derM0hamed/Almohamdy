<?php

namespace App\Http\Requests\DoctorsDirectoryAdmin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class StoreSpecialityRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'subject_en' => ['required', 'string', 'max:255'],
            'subject_ar' => ['required', 'string', 'max:255'],
            'clinics_id' => [
                'nullable',
                'integer',
                'min:0',
                function (string $attribute, mixed $value, \Closure $fail) {
                    $id = is_numeric($value) ? (int) $value : null;

                    if ($id === null || $id < 0) {
                        $fail(__('validation.integer', ['attribute' => $attribute]));

                        return;
                    }

                    if ($id === 0) {
                        return;
                    }

                    if (! DB::table('clinics')->where('id', $id)->exists()) {
                        $fail(__('validation.exists', ['attribute' => $attribute]));
                    }
                },
            ],
            'publish' => ['nullable', 'boolean'],
        ];
    }

    public function subjectEn(): string
    {
        return trim((string) $this->input('subject_en'));
    }

    public function subjectAr(): string
    {
        return trim((string) $this->input('subject_ar'));
    }

    public function clinicId(): int
    {
        $value = $this->input('clinics_id', 0);

        return is_numeric($value) ? max(0, (int) $value) : 0;
    }

    public function publish(): string
    {
        return $this->boolean('publish') ? '1' : '0';
    }
}
