<?php

namespace App\Http\Requests\SystemAdministration;

use App\Services\Auth\PermissionService;
use Illuminate\Foundation\Http\FormRequest;

class UpdatePackageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return app(PermissionService::class)->isAdmin();
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'code1' => ['required', 'string', 'max:100'],
            'name_en' => ['required', 'string', 'max:500'],
            'name_ar' => ['required', 'string', 'max:500'],
            'price' => ['nullable', 'string', 'max:100'],
            'publish' => ['nullable', 'boolean'],
        ];
    }

    public function code(): string
    {
        return trim((string) $this->input('code1'));
    }

    public function nameEn(): string
    {
        return trim((string) $this->input('name_en'));
    }

    public function nameAr(): string
    {
        return trim((string) $this->input('name_ar'));
    }

    public function price(): string
    {
        return trim((string) $this->input('price', ''));
    }

    public function publish(): string
    {
        return $this->boolean('publish') ? '1' : '0';
    }
}
