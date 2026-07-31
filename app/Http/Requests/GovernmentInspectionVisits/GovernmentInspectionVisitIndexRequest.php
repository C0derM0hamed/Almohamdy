<?php

namespace App\Http\Requests\GovernmentInspectionVisits;

use Illuminate\Foundation\Http\FormRequest;

class GovernmentInspectionVisitIndexRequest extends FormRequest
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
            'from_date' => ['nullable', 'date'],
            'to_date' => ['nullable', 'date', 'after_or_equal:from_date'],
            'visit_type' => ['nullable', 'integer', 'min:1'],
            'authority' => ['nullable', 'integer', 'min:1'],
            'branch' => ['nullable', 'integer', 'min:1'],
            'section' => ['nullable', 'integer', 'min:1'],
            'status' => ['nullable', 'integer', 'min:1'],
            'page' => ['nullable', 'integer', 'min:1'],
        ];
    }

    /**
     * @return array{
     *     from_date:?string,
     *     to_date:?string,
     *     visit_type_id:?int,
     *     authority_id:?int,
     *     branch_id:?int,
     *     section_id:?int,
     *     status_id:?int
     * }
     */
    public function filters(): array
    {
        return [
            'from_date' => $this->nullableString('from_date'),
            'to_date' => $this->nullableString('to_date'),
            'visit_type_id' => $this->nullableInt('visit_type'),
            'authority_id' => $this->nullableInt('authority'),
            'branch_id' => $this->nullableInt('branch'),
            'section_id' => $this->nullableInt('section'),
            'status_id' => $this->nullableInt('status'),
        ];
    }

    /**
     * @return array<string, string>
     */
    public function filterInputs(): array
    {
        $filters = $this->filters();

        return [
            'from_date' => $filters['from_date'] ?? '',
            'to_date' => $filters['to_date'] ?? '',
            'visit_type' => $filters['visit_type_id'] !== null ? (string) $filters['visit_type_id'] : '',
            'authority' => $filters['authority_id'] !== null ? (string) $filters['authority_id'] : '',
            'branch' => $filters['branch_id'] !== null ? (string) $filters['branch_id'] : '',
            'section' => $filters['section_id'] !== null ? (string) $filters['section_id'] : '',
            'status' => $filters['status_id'] !== null ? (string) $filters['status_id'] : '',
        ];
    }

    public function hasFilters(): bool
    {
        foreach ($this->filters() as $value) {
            if ($value !== null && $value !== '') {
                return true;
            }
        }

        return false;
    }

    private function nullableInt(string $key): ?int
    {
        $value = $this->input($key);

        return $value === null || $value === '' ? null : (int) $value;
    }

    private function nullableString(string $key): ?string
    {
        $value = $this->input($key);

        if ($value === null || $value === '') {
            return null;
        }

        return (string) $value;
    }
}
