<?php

namespace App\Http\Requests\CorporateCommunications;

use Illuminate\Foundation\Http\FormRequest;

class CorporateCommunicationOutgoingLetterIndexRequest extends FormRequest
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
            'sector' => ['nullable', 'integer', 'min:1'],
            'authority' => ['nullable', 'integer', 'min:1'],
            'branch' => ['nullable', 'integer', 'min:1'],
            'status' => ['nullable', 'integer', 'min:1'],
            'subject' => ['nullable', 'string', 'max:200'],
            'page' => ['nullable', 'integer', 'min:1'],
        ];
    }

    /**
     * @return array{
     *     from_date:?string,
     *     to_date:?string,
     *     sector_id:?int,
     *     authority_id:?int,
     *     branch_id:?int,
     *     status_id:?int,
     *     subject:?string
     * }
     */
    public function filters(): array
    {
        return [
            'from_date' => $this->nullableString('from_date'),
            'to_date' => $this->nullableString('to_date'),
            'sector_id' => $this->nullableInt('sector'),
            'authority_id' => $this->nullableInt('authority'),
            'branch_id' => $this->nullableInt('branch'),
            'status_id' => $this->nullableInt('status'),
            'subject' => $this->nullableString('subject'),
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
            'sector' => $filters['sector_id'] !== null ? (string) $filters['sector_id'] : '',
            'authority' => $filters['authority_id'] !== null ? (string) $filters['authority_id'] : '',
            'branch' => $filters['branch_id'] !== null ? (string) $filters['branch_id'] : '',
            'status' => $filters['status_id'] !== null ? (string) $filters['status_id'] : '',
            'subject' => $filters['subject'] ?? '',
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

        return $value === null || $value === '' ? null : (string) $value;
    }
}
