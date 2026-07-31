<?php

namespace App\Http\Requests\SystemAdministration;

use App\Services\Auth\PermissionService;
use Illuminate\Foundation\Http\FormRequest;

class PackageIndexRequest extends FormRequest
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
            'search' => ['nullable', 'string', 'max:100'],
            'section' => ['nullable', 'integer', 'min:1'],
            'publish' => ['nullable', 'string', 'in:0,1'],
            'page' => ['nullable', 'integer', 'min:1'],
        ];
    }

    public function search(): string
    {
        return trim((string) $this->input('search', ''));
    }

    public function sectionId(): ?int
    {
        $value = $this->input('section');

        if ($value === null || $value === '') {
            return null;
        }

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
        return $this->search() !== ''
            || $this->sectionId() !== null
            || $this->publish() !== null;
    }
}
