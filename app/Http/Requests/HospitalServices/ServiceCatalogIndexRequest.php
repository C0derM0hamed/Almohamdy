<?php

namespace App\Http\Requests\HospitalServices;

use Illuminate\Foundation\Http\FormRequest;

class ServiceCatalogIndexRequest extends FormRequest
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
            'page' => ['nullable', 'integer', 'min:1'],
        ];
    }

    public function search(): string
    {
        return trim((string) $this->input('search', ''));
    }

    public function hasFilters(): bool
    {
        return $this->search() !== '';
    }
}
