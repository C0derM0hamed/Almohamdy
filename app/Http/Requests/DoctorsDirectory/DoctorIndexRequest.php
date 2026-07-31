<?php

namespace App\Http\Requests\DoctorsDirectory;

use Illuminate\Foundation\Http\FormRequest;

class DoctorIndexRequest extends FormRequest
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
            'name' => ['nullable', 'string', 'max:100'],
            'code' => ['nullable', 'string', 'max:40'],
            'specialization' => ['nullable', 'string', 'max:100'],
            'hospital' => ['nullable', 'integer', 'min:1'],
            'all' => ['nullable', 'boolean'],
            'page' => ['nullable', 'integer', 'min:1'],
        ];
    }

    public function name(): string
    {
        return trim((string) $this->input('name', ''));
    }

    public function code(): string
    {
        return trim((string) $this->input('code', ''));
    }

    public function specialization(): string
    {
        return trim((string) $this->input('specialization', ''));
    }

    public function allBranches(): bool
    {
        return $this->boolean('all');
    }

    public function hospitalId(): ?int
    {
        $hospitalId = $this->integer('hospital');

        return $hospitalId > 0 ? $hospitalId : null;
    }

    public function hasFilters(): bool
    {
        return $this->name() !== ''
            || $this->code() !== ''
            || $this->specialization() !== ''
            || $this->hospitalId() !== null
            || $this->allBranches();
    }
}
