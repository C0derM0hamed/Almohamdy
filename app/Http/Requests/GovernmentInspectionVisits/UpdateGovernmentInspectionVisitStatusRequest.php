<?php

namespace App\Http\Requests\GovernmentInspectionVisits;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateGovernmentInspectionVisitStatusRequest extends FormRequest
{
    public const STATUS_ESCALATED = 4;

    public const STATUS_ENTITY_NOTIFIED = 6;

    public const STATUS_RETURNED = 7;

    /**
     * @return list<int>
     */
    public static function updatableStatusIds(): array
    {
        return [
            self::STATUS_ESCALATED,
            self::STATUS_ENTITY_NOTIFIED,
            self::STATUS_RETURNED,
        ];
    }

    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $returns = collect($this->input('returns', []))
            ->filter(fn ($row) => is_array($row) && filled(trim((string) ($row['reason'] ?? ''))))
            ->values()
            ->all();

        $this->merge([
            'returns' => $returns,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'status_id' => ['required', 'integer', Rule::in(self::updatableStatusIds())],
            'notice_file' => ['nullable', 'file', 'max:10240', 'mimes:pdf,doc,docx,jpg,jpeg,png'],
            'returns' => ['nullable', 'array'],
            'returns.*.finding_id' => ['required', 'integer', 'min:1'],
            'returns.*.reason' => ['required', 'string', 'min:3', 'max:4000'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $statusId = (int) $this->input('status_id');

            if ($statusId === self::STATUS_ENTITY_NOTIFIED && ! $this->hasFile('notice_file')) {
                $validator->errors()->add('notice_file', __('inspection_visits.validation.notice_file_required'));
            }

            if ($statusId === self::STATUS_RETURNED) {
                $returns = collect($this->input('returns', []))
                    ->filter(fn ($row) => is_array($row) && filled(trim((string) ($row['reason'] ?? ''))));

                if ($returns->isEmpty()) {
                    $validator->errors()->add('returns', __('inspection_visits.validation.return_reasons_required'));
                }
            }
        });
    }

    /**
     * @return array<string, mixed>
     */
    public function payload(): array
    {
        $returns = collect($this->input('returns', []))
            ->filter(fn ($row) => is_array($row) && filled(trim((string) ($row['reason'] ?? ''))))
            ->map(fn ($row) => [
                'finding_id' => (int) $row['finding_id'],
                'reason' => trim((string) $row['reason']),
            ])
            ->values()
            ->all();

        return [
            'status_id' => (int) $this->input('status_id'),
            'returns' => $returns,
        ];
    }
}
