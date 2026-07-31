<?php

namespace App\Http\Requests\GovernmentCirculars;

use Illuminate\Foundation\Http\FormRequest;

class GovernmentCircularIndexRequest extends FormRequest
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
            'subject' => ['nullable', 'string', 'max:200'],
            'authority' => ['nullable', 'integer', 'min:1'],
            'section' => ['nullable', 'integer', 'min:1'],
            'branch' => ['nullable', 'integer', 'min:1'],
            'from_date' => ['nullable', 'date'],
            'to_date' => ['nullable', 'date', 'after_or_equal:from_date'],
            'page' => ['nullable', 'integer', 'min:1'],
        ];
    }

    public function subject(): string
    {
        return trim((string) $this->input('subject', ''));
    }

    public function authority(): ?int
    {
        $value = $this->input('authority');

        return $value === null || $value === '' ? null : (int) $value;
    }

    public function section(): ?int
    {
        $value = $this->input('section');

        return $value === null || $value === '' ? null : (int) $value;
    }

    public function branch(): ?int
    {
        $value = $this->input('branch');

        return $value === null || $value === '' ? null : (int) $value;
    }

    public function fromDate(): ?string
    {
        $value = $this->input('from_date');

        return $value === null || $value === '' ? null : (string) $value;
    }

    public function toDate(): ?string
    {
        $value = $this->input('to_date');

        return $value === null || $value === '' ? null : (string) $value;
    }

    public function hasFilters(): bool
    {
        return $this->subject() !== ''
            || $this->authority() !== null
            || $this->section() !== null
            || $this->branch() !== null
            || $this->fromDate() !== null
            || $this->toDate() !== null;
    }
}
