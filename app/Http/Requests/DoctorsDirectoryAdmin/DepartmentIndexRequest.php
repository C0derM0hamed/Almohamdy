<?php

namespace App\Http\Requests\DoctorsDirectoryAdmin;

use Illuminate\Foundation\Http\FormRequest;

class DepartmentIndexRequest extends FormRequest
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
            'search' => ['nullable', 'string', 'max:100'],
            'speciality' => ['nullable', 'integer', 'min:1'],
            'publish' => ['nullable', 'string', 'in:0,1'],
            'page' => ['nullable', 'integer', 'min:1'],
        ];
    }

    public function search(): string
    {
        return trim((string) $this->input('search', ''));
    }

    public function specialityId(): ?int
    {
        $value = $this->input('speciality');

        return is_numeric($value) ? max(1, (int) $value) : null;
    }

    public function publish(): ?string
    {
        $value = $this->input('publish');

        if ($value === null || $value === '') {
            return null;
        }

        return (string) $value;
    }

    public function hasFilters(): bool
    {
        return $this->search() !== '' || $this->specialityId() !== null || $this->publish() !== null;
    }
}

