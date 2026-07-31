<?php

namespace App\Http\Requests\CorporateCommunications;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateCorporateCommunicationStatusRequest extends FormRequest
{
    public const STATUS_APPROVED = 4;

    public const STATUS_RETURNED_TO_DEPARTMENT = 5;

    public const STATUS_SHIPMENT_REQUESTED = 6;

    public const STATUS_DELIVERED_TO_POSTAL = 7;

    public const STATUS_DELIVERED_TO_ENTITY = 8;

    public const STATUS_RETURNED_BY_ENTITY = 9;

    public const STATUS_DELIVERED_BY_SPECIALIST = 10;

    public const STATUS_ESCALATED = 11;

    /**
     * @return list<int>
     */
    public static function updatableStatusIds(): array
    {
        return [
            self::STATUS_APPROVED,
            self::STATUS_RETURNED_TO_DEPARTMENT,
            self::STATUS_SHIPMENT_REQUESTED,
            self::STATUS_DELIVERED_TO_POSTAL,
            self::STATUS_DELIVERED_TO_ENTITY,
            self::STATUS_RETURNED_BY_ENTITY,
            self::STATUS_DELIVERED_BY_SPECIALIST,
            self::STATUS_ESCALATED,
        ];
    }

    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'reason' => trim((string) $this->input('reason', '')),
            'shipment_number' => trim((string) $this->input('shipment_number', '')),
            'postal_employee_name' => trim((string) $this->input('postal_employee_name', '')),
            'registration_number' => trim((string) $this->input('registration_number', '')),
            'delivered_by' => trim((string) $this->input('delivered_by', '')),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'status_id' => ['required', 'integer', Rule::in(self::updatableStatusIds())],
            'reason' => ['nullable', 'string', 'min:3', 'max:4000'],
            'confirm_approval' => ['nullable'],
            'shipment_number' => ['nullable', 'string', 'max:26'],
            'date_time_receipt' => ['nullable', 'date'],
            'postal_employee_name' => ['nullable', 'string', 'max:100'],
            'return_date' => ['nullable', 'date'],
            'registration_number' => ['nullable', 'string', 'max:20'],
            'delivered_by' => ['nullable', 'string', 'max:150'],
            'delivery_date' => ['nullable', 'date'],
            'status_file' => ['nullable', 'file', 'max:10240', 'mimes:pdf,doc,docx,jpg,jpeg,png'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $statusId = (int) $this->input('status_id');

            if ($statusId === self::STATUS_APPROVED && ! $this->boolean('confirm_approval')) {
                $validator->errors()->add(
                    'confirm_approval',
                    __('correspondence.validation.confirm_approval')
                );
            }

            if ($statusId === self::STATUS_RETURNED_TO_DEPARTMENT && ! filled($this->input('reason'))) {
                $validator->errors()->add('reason', __('correspondence.validation.reason_required'));
            }

            if ($statusId === self::STATUS_SHIPMENT_REQUESTED) {
                if (! filled($this->input('shipment_number'))) {
                    $validator->errors()->add(
                        'shipment_number',
                        __('correspondence.validation.shipment_number_required')
                    );
                }
                if (! $this->hasFile('status_file')) {
                    $validator->errors()->add(
                        'status_file',
                        __('correspondence.validation.status_file_required')
                    );
                }
            }

            if ($statusId === self::STATUS_DELIVERED_TO_POSTAL) {
                if (! filled($this->input('date_time_receipt'))) {
                    $validator->errors()->add(
                        'date_time_receipt',
                        __('correspondence.validation.receipt_datetime_required')
                    );
                }
                if (! filled($this->input('postal_employee_name'))) {
                    $validator->errors()->add(
                        'postal_employee_name',
                        __('correspondence.validation.postal_employee_required')
                    );
                }
                if (! $this->hasFile('status_file')) {
                    $validator->errors()->add(
                        'status_file',
                        __('correspondence.validation.status_file_required')
                    );
                }
            }

            if ($statusId === self::STATUS_DELIVERED_TO_ENTITY) {
                if (! filled($this->input('date_time_receipt'))) {
                    $validator->errors()->add(
                        'date_time_receipt',
                        __('correspondence.validation.receipt_datetime_required')
                    );
                }
                if (! $this->hasFile('status_file')) {
                    $validator->errors()->add(
                        'status_file',
                        __('correspondence.validation.status_file_required')
                    );
                }
            }

            if ($statusId === self::STATUS_RETURNED_BY_ENTITY) {
                if (! filled($this->input('return_date'))) {
                    $validator->errors()->add(
                        'return_date',
                        __('correspondence.validation.return_date_required')
                    );
                }
                if (! filled($this->input('reason'))) {
                    $validator->errors()->add('reason', __('correspondence.validation.reason_required'));
                }
                if (! $this->hasFile('status_file')) {
                    $validator->errors()->add(
                        'status_file',
                        __('correspondence.validation.status_file_required')
                    );
                }
            }

            if ($statusId === self::STATUS_DELIVERED_BY_SPECIALIST) {
                if (! filled($this->input('registration_number'))) {
                    $validator->errors()->add(
                        'registration_number',
                        __('correspondence.validation.registration_number_required')
                    );
                }
                if (! filled($this->input('delivered_by'))) {
                    $validator->errors()->add(
                        'delivered_by',
                        __('correspondence.validation.delivered_by_required')
                    );
                }
                if (! filled($this->input('delivery_date'))) {
                    $validator->errors()->add(
                        'delivery_date',
                        __('correspondence.validation.delivery_date_required')
                    );
                }
            }
        });
    }

    /**
     * @return array<string, mixed>
     */
    public function payload(): array
    {
        return [
            'status_id' => (int) $this->input('status_id'),
            'reason' => trim((string) $this->input('reason', '')),
            'shipment_number' => trim((string) $this->input('shipment_number', '')),
            'date_time_receipt' => $this->filled('date_time_receipt')
                ? $this->date('date_time_receipt')?->format('Y-m-d\TH:i')
                : null,
            'postal_employee_name' => trim((string) $this->input('postal_employee_name', '')),
            'return_date' => $this->filled('return_date')
                ? $this->date('return_date')?->format('Y-m-d\TH:i')
                : null,
            'registration_number' => trim((string) $this->input('registration_number', '')),
            'delivered_by' => trim((string) $this->input('delivered_by', '')),
            'delivery_date' => $this->filled('delivery_date')
                ? $this->date('delivery_date')?->format('Y-m-d\TH:i')
                : null,
        ];
    }

    public function statusFile(): ?UploadedFile
    {
        $file = $this->file('status_file');

        return $file instanceof UploadedFile ? $file : null;
    }
}
