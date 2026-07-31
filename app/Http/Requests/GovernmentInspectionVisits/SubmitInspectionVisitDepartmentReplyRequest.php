<?php

namespace App\Http\Requests\GovernmentInspectionVisits;

use Illuminate\Foundation\Http\FormRequest;

class SubmitInspectionVisitDepartmentReplyRequest extends FormRequest
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
            'items' => ['required', 'array', 'min:1'],
            'items.*.id' => ['required', 'integer', 'min:1'],
            'items.*.reply' => ['required', 'string', 'min:2', 'max:4000'],
            'files' => ['nullable', 'array'],
            'files.*' => ['nullable', 'file', 'max:8192', 'mimes:pdf,jpg,jpeg,png,gif'],
            'confirm' => ['accepted'],
        ];
    }

    /**
     * @return array{items:list<array{id:int,reply:string}>,files:array<int,\Illuminate\Http\UploadedFile|null>}
     */
    public function payload(): array
    {
        $items = [];

        foreach ((array) $this->input('items', []) as $row) {
            $items[] = [
                'id' => (int) ($row['id'] ?? 0),
                'reply' => trim((string) ($row['reply'] ?? '')),
            ];
        }

        return [
            'items' => $items,
            'files' => (array) $this->file('files', []),
        ];
    }
}
