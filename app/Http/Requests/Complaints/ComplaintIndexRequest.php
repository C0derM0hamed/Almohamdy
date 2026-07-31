<?php

namespace App\Http\Requests\Complaints;

use App\Repositories\Complaints\ComplaintStatusRepository;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ComplaintIndexRequest extends FormRequest
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
        $statusIds = app(ComplaintStatusRepository::class)
            ->published()
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();

        $statusIds[] = 0;

        return [
            'search' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', 'integer', Rule::in($statusIds)],
            'page' => ['nullable', 'integer', 'min:1'],
        ];
    }

    public function search(): string
    {
        return trim((string) $this->input('search', ''));
    }

    public function status(): ?int
    {
        $value = $this->input('status');

        return $value === null || $value === '' ? null : (int) $value;
    }

    public function hasFilters(): bool
    {
        return $this->search() !== '' || $this->status() !== null;
    }
}
