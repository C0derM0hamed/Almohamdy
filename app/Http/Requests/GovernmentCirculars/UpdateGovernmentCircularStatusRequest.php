<?php

namespace App\Http\Requests\GovernmentCirculars;

use App\Models\GovernmentCircularStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateGovernmentCircularStatusRequest extends FormRequest
{
    /** Legacy: status "جديد" requires an attachment unless classification is 3. */
    public const STATUS_NEW = 1;

    public const CLASSIFICATION_EXEMPT_FROM_ATTACHMENT = 3;

    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'details' => trim((string) $this->input('details', '')),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $publishedIds = GovernmentCircularStatus::query()
            ->where('publish', 1)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();

        return [
            'status_id' => ['required', 'integer', Rule::in($publishedIds)],
            'details' => ['nullable', 'string', 'max:2000'],
            'attachment_file' => ['nullable', 'file', 'max:10240', 'mimes:pdf,doc,docx,jpg,jpeg,png'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function payload(): array
    {
        return [
            'status_id' => (int) $this->input('status_id'),
            'details' => trim((string) $this->input('details', '')),
        ];
    }
}
