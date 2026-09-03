<?php

namespace App\Http\Requests\Licenses;

use Illuminate\Foundation\Http\FormRequest;

class StoreLicenseCommentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return ['body' => ['required', 'string', 'min:2', 'max:10000']];
    }

    public function body(): string
    {
        return trim((string) $this->input('body'));
    }
}
