<?php

namespace App\Services\LegacyWorkflows;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class GovernmentalServiceWorkflow
{
    public function authorize(): void
    {
        abort_unless((int) session('companies_groups_id', 0) === 1, 403);
        abort_unless(in_array((int) session('hr_branch_id', 0), [1, 5, 7], true), 403);
    }

    public function canProcess(): bool
    {
        return (int) session('hr_branch_id', 0) === 7;
    }

    /** @param array<string, string> $filters */
    public function list(array $filters): LengthAwarePaginator
    {
        $this->authorize();
        $query = DB::table('governmental_services as service')
            ->leftJoin('governmental_services_type as type', 'type.id', '=', 'service.service_id')
            ->leftJoin('governmental_services_action_type as status_type', 'status_type.id', '=', 'service.status')
            ->where('service.companies_groups_id', 1)
            ->select('service.*', 'type.name_ar as service_name', 'status_type.name_ar as status_name')
            ->orderByDesc('service.date');
        if (! $this->canProcess()) {
            $query->where('service.branch_id', (int) session('hr_branch_id'));
        }
        if ($filters['from'] !== '') {
            $query->where('service.date', '>=', (string) strtotime($filters['from'].' 00:00:00'));
        }
        if ($filters['to'] !== '') {
            $query->where('service.date', '<=', (string) strtotime($filters['to'].' 23:59:59'));
        }
        if ($filters['status'] !== '') {
            $query->where('service.status', (int) $filters['status']);
        }
        if ($filters['service_id'] !== '') {
            $query->where('service.service_id', (int) $filters['service_id']);
        }
        if ($filters['identity'] !== '') {
            $query->where(fn ($q) => $q->where('service.id_no', 'like', '%'.$filters['identity'].'%')->orWhere('service.file_number', 'like', '%'.$filters['identity'].'%'));
        }

        return $query->paginate(15)->withQueryString();
    }

    /** @return array<string, mixed> */
    public function options(): array
    {
        $this->authorize();

        return [
            'types' => DB::table('governmental_services_type')->where('publish', 1)->orderBy('id')->get(),
            'platforms' => DB::table('governmental_services_platforms')->where('publish', 1)->orderBy('id')->get(),
            'statuses' => DB::table('governmental_services_action_type')->where('publish', 1)->orderBy('id')->get(),
            'countries' => DB::table('country_yakeen')->orderBy('DESCRIPTION')->get(),
        ];
    }

    /** @param array<string, mixed> $data */
    public function create(array $data): int
    {
        $this->authorize();

        return (int) DB::table('governmental_services')->insertGetId([
            'date' => (string) time(), 'branch_id' => (int) session('hr_branch_id'),
            'platform_id' => (int) $data['platform_id'], 'service_id' => (int) $data['service_id'],
            'pateint' => trim((string) $data['patient']), 'file_number' => trim((string) ($data['file_number'] ?? '')),
            'id_no' => trim((string) $data['id_no']), 'mobile' => trim((string) $data['mobile']),
            'job_title' => trim((string) ($data['job_title'] ?? '')), 'address' => trim((string) ($data['address'] ?? '')),
            'date_type' => (int) ($data['date_type'] ?? 0), 'birth_day' => (int) ($data['birth_day'] ?? 0),
            'birth_month' => (int) ($data['birth_month'] ?? 0), 'birth_year' => (int) ($data['birth_year'] ?? 0),
            'created_by' => (int) session('hr_user_id'), 'companies_groups_id' => 1, 'status' => 0,
            'sponser_id' => trim((string) ($data['sponsor_id'] ?? '')), 'nationality_type' => (int) ($data['nationality_type'] ?? 0),
            'country' => (int) ($data['country'] ?? 0), 'pateint_wife' => trim((string) ($data['patient_wife'] ?? '')),
            'id_no_wife' => trim((string) ($data['id_no_wife'] ?? '')), 'country_wife' => (int) ($data['country_wife'] ?? 0),
            'date_type_wife' => (int) ($data['date_type_wife'] ?? 0), 'birth_day_wife' => (int) ($data['birth_day_wife'] ?? 0),
            'birth_month_wife' => (int) ($data['birth_month_wife'] ?? 0), 'birth_year_wife' => (int) ($data['birth_year_wife'] ?? 0),
            'married_request_type' => (int) ($data['married_request_type'] ?? 0), 'mobile_wife' => trim((string) ($data['mobile_wife'] ?? '')),
            'filledByClient' => 0, 'passport_no' => trim((string) ($data['passport_no'] ?? '')),
        ]);
    }

    public function find(int $id): ?object
    {
        $this->authorize();
        $query = DB::table('governmental_services as service')
            ->leftJoin('governmental_services_type as type', 'type.id', '=', 'service.service_id')
            ->leftJoin('governmental_services_platforms as platform', 'platform.id', '=', 'service.platform_id')
            ->leftJoin('governmental_services_action_type as status_type', 'status_type.id', '=', 'service.status')
            ->where('service.id', $id)->where('service.companies_groups_id', 1)
            ->select('service.*', 'type.name_ar as service_name', 'platform.name_ar as platform_name', 'status_type.name_ar as status_name');
        if (! $this->canProcess()) {
            $query->where('service.branch_id', (int) session('hr_branch_id'));
        }
        $record = $query->first();
        if ($record === null) {
            return null;
        }
        $record->actions = DB::table('governmental_services_action as action')
            ->leftJoin('governmental_services_action_type as type', 'type.id', '=', 'action.status_id')
            ->leftJoin('ra_users as creator', 'creator.hr_id', '=', 'action.created_by')
            ->where('action.governmental_services_id', $id)
            ->select('action.*', 'type.name_ar as status_name', 'creator.hr_first_name as creator_name')->orderBy('action.id')->get();
        $record->attachments = DB::table('governmental_services_attachments')->where('governmental_services_id', $id)->orderBy('id')->get();

        return $record;
    }

    public function transition(int $id, int $statusId, ?string $details): void
    {
        $this->authorize();
        abort_unless($this->canProcess(), 403);
        $record = $this->find($id);
        abort_if($record === null, 404);
        abort_unless(DB::table('governmental_services_action_type')->where('id', $statusId)->where('publish', 1)->exists(), 422);
        abort_if(DB::table('governmental_services_action')->where('governmental_services_id', $id)->where('status_id', $statusId)->exists(), 422);
        if ($statusId === 1 && (int) $record->service_id !== 3) {
            abort_unless(DB::table('governmental_services_attachments')->where('governmental_services_id', $id)->exists(), 422, 'فضل ارفع الملف المرفق.');
        }
        DB::transaction(function () use ($id, $statusId, $details): void {
            DB::table('governmental_services_action')->insert(['governmental_services_id' => $id, 'status_id' => $statusId, 'branch_id' => 7, 'details' => trim((string) $details), 'created_by' => (int) session('hr_user_id'), 'created_at' => now()]);
            DB::table('governmental_services')->where('id', $id)->where('companies_groups_id', 1)->update(['status' => $statusId]);
        });
    }

    public function attach(int $id, UploadedFile $file): void
    {
        $this->authorize();
        abort_unless($this->canProcess(), 403);
        abort_if($this->find($id) === null, 404);
        $path = $file->store('governmental-services', 'public');
        DB::table('governmental_services_attachments')->insert(['governmental_services_id' => $id, 'file_name' => $path, 'created_by' => (int) session('hr_user_id'), 'created_at' => now()]);
    }

    public function attachment(int $id, int $attachmentId): ?object
    {
        abort_if($this->find($id) === null, 404);

        return DB::table('governmental_services_attachments')->where('id', $attachmentId)->where('governmental_services_id', $id)->first();
    }

    public function deleteAttachment(int $id, int $attachmentId): void
    {
        $this->authorize();
        abort_unless($this->canProcess(), 403);
        $attachment = $this->attachment($id, $attachmentId);
        abort_if($attachment === null, 404);
        abort_if((int) $this->find($id)->status === 1, 422);
        Storage::disk('public')->delete((string) $attachment->file_name);
        DB::table('governmental_services_attachments')->where('id', $attachmentId)->where('governmental_services_id', $id)->delete();
    }
}
