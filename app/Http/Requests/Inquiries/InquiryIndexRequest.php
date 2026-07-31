<?php

namespace App\Http\Requests\Inquiries;

use App\Repositories\Inquiries\InquiryAndServiceRepository;
use Carbon\Carbon;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class InquiryIndexRequest extends FormRequest
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
        $departmentIds = app(InquiryAndServiceRepository::class)
            ->departmentOptions()
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();

        $statusIds = app(InquiryAndServiceRepository::class)
            ->statusOptions()
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->merge(config('hm.inquiries.new_status_ids', [999999, 1, 0]))
            ->unique()
            ->values()
            ->all();

        return [
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
            'status' => ['nullable', 'integer', Rule::in($statusIds)],
            'department' => ['nullable', 'integer', Rule::in($departmentIds)],
            'mobile' => ['nullable', 'string', 'max:20'],
            'stat' => ['nullable', 'string', Rule::in(array_keys(config('hm.inquiries.stat_statuses', [])))],
            'page' => ['nullable', 'integer', 'min:1'],
        ];
    }

    public function direction(): string
    {
        return str_contains((string) $this->route()?->getName(), 'incoming') ? 'incoming' : 'outgoing';
    }

    public function dateFrom(): ?Carbon
    {
        $value = trim((string) $this->input('date_from', ''));

        return $value === '' ? null : Carbon::parse($value)->startOfDay();
    }

    public function dateTo(): ?Carbon
    {
        $value = trim((string) $this->input('date_to', ''));

        return $value === '' ? null : Carbon::parse($value)->endOfDay();
    }

    public function departmentId(): ?int
    {
        $value = $this->input('department');

        return $value === null || $value === '' ? null : (int) $value;
    }

    public function mobile(): string
    {
        return trim((string) $this->input('mobile', ''));
    }

    public function statusId(): ?int
    {
        $stat = trim((string) $this->input('stat', ''));

        if ($stat !== '') {
            if ($stat === 'new') {
                return 999999;
            }

            $stats = config('hm.inquiries.stat_statuses', []);
            $ids = $stats[$stat] ?? null;

            if (is_array($ids) && $ids !== []) {
                return (int) $ids[0];
            }
        }

        $value = $this->input('status');

        return $value === null || $value === '' ? null : (int) $value;
    }

    public function hasExplicitFilters(): bool
    {
        return $this->hasAny(['date_from', 'date_to', 'status', 'department', 'mobile', 'stat']);
    }

    public function filterValues(): array
    {
        return [
            'date_from' => trim((string) $this->input('date_from', '')),
            'date_to' => trim((string) $this->input('date_to', '')),
            'status' => $this->input('status', ''),
            'department' => $this->departmentId() ?? '',
            'mobile' => $this->mobile(),
            'stat' => trim((string) $this->input('stat', '')),
        ];
    }
}
