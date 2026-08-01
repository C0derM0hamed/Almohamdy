<?php

namespace App\Services\TechnicalFailure;

use App\Models\TechnicalFailureNotice;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class TechnicalFailureService
{
    public function listPaginated(array $filters): LengthAwarePaginator
    {
        return $this->scopedQuery()
            ->when($filters['search'] !== '', function (Builder $query) use ($filters): void {
                $query->where(function (Builder $inner) use ($filters): void {
                    $inner->where('technical_failure_notice.notice', 'like', '%'.$filters['search'].'%')
                        ->orWhere('technical_failure_notice.other', 'like', '%'.$filters['search'].'%')
                        ->orWhere('technical_failure_notice.id', (int) $filters['search']);
                });
            })
            ->when($filters['status'] !== null, fn (Builder $query) => $query->where('technical_failure_notice.status', $filters['status']))
            ->when($filters['from'] !== '', fn (Builder $query) => $query->where('technical_failure_notice.date_time', '>=', (string) strtotime($filters['from'])))
            ->when($filters['to'] !== '', fn (Builder $query) => $query->where('technical_failure_notice.date_time', '<=', (string) (strtotime($filters['to'].' 23:59:59'))))
            ->orderByDesc('technical_failure_notice.id')
            ->paginate(15)
            ->withQueryString();
    }

    public function find(int $id): ?object
    {
        return $this->scopedQuery()->where('technical_failure_notice.id', $id)->first();
    }

    public function create(array $data, ?UploadedFile $file = null): object
    {
        $options = $this->options();
        abort_unless($options['platforms']->contains('id', (int) ($data['platform_id'] ?? 0)), 422);
        abort_unless($options['serviceTypes']->contains('id', (int) ($data['service_type_id'] ?? 0)), 422);
        abort_unless(($data['section_id'] ?? null) === null || $options['sections']->contains('id', (int) $data['section_id']), 422);
        abort_unless(($data['type_id'] ?? null) === null || $options['types']->contains('id', (int) $data['type_id']), 422);

        return DB::transaction(function () use ($data, $file): object {
            $notice = TechnicalFailureNotice::query()->create([
                'branch_id' => (int) session('hr_branch_id', 0),
                'companies_groups_id' => (int) session('companies_groups_id', 0),
                'user_id' => (int) session('hr_user_id', 0),
                'date_time' => (string) time(),
                'technical_failure_notice_sections' => (int) ($data['section_id'] ?? 0),
                'technical_failure_notice_type' => (int) ($data['type_id'] ?? 0),
                'technical_failure_notice_platform' => (int) ($data['platform_id'] ?? 0),
                'technical_failure_notice_service_type' => (int) ($data['service_type_id'] ?? 0),
                'other' => trim((string) ($data['other'] ?? '')),
                'notice' => trim((string) $data['notice']),
                'status' => 0,
            ]);

            if ($file !== null) {
                $path = $file->store('technical-failures/'.$notice->id, 'local');
                $notice->forceFill(['file_name' => $path])->save();
            }

            return $notice;
        });
    }

    public function updateStatus(int $id, int $statusId): void
    {
        $notice = $this->find($id);
        abort_if($notice === null, 404);

        abort_unless($this->statusOptions()->contains('id', $statusId), 422);

        DB::transaction(function () use ($notice, $statusId): void {
            TechnicalFailureNotice::query()->whereKey($notice->id)->update(['status' => $statusId]);
            DB::table('technical_failure_notice_process')->insert([
                'technical_failure_notice_id' => $notice->id,
                'status_id' => $statusId,
                'created_by' => (int) session('hr_user_id', 0),
                'created_at' => now(),
            ]);
        });
    }

    public function timeline(int $id): Collection
    {
        return DB::table('technical_failure_notice_process as process')
            ->leftJoin('technical_failure_notice_status as status', 'status.id', '=', 'process.status_id')
            ->leftJoin('ra_users as user', 'user.hr_id', '=', 'process.created_by')
            ->where('process.technical_failure_notice_id', $id)
            ->orderBy('process.id')
            ->get([
                'process.id', 'process.created_at', 'status.name_ar', 'status.name_en',
                'status.info', 'user.hr_first_name', 'user.hr_last_name',
            ]);
    }

    public function statusOptions(): Collection
    {
        return DB::table('technical_failure_notice_status')
            ->where('publish', 1)
            ->orderBy('id')
            ->get(['id', 'name_ar', 'name_en', 'info']);
    }

    public function options(): array
    {
        $branchId = (int) session('hr_branch_id', 0);

        return [
            'sections' => DB::table('technical_failure_notice_sections')
                ->where('publish', 1)->whereIn('branch_id', [$branchId, 0])->orderBy('name_ar')->get(),
            'types' => DB::table('technical_failure_notice_type')
                ->where('publish', 1)->orderBy('name_ar')->get(),
            'platforms' => DB::table('technical_failure_notice_platform')
                ->where('publish', 1)->whereIn('branch_id', [$branchId, 0])->orderBy('name_ar')->get(),
            'serviceTypes' => DB::table('technical_failure_notice_service_type')
                ->where('publish', 1)->whereIn('branch_id', [$branchId, 0])->orderBy('name_ar')->get(),
        ];
    }

    private function scopedQuery(): Builder
    {
        $query = DB::table('technical_failure_notice')
            ->where('technical_failure_notice.companies_groups_id', (int) session('companies_groups_id', 0));

        if ((int) session('hr_user_level', 0) !== 3 && (int) session('hr_branch_id', 0) > 0) {
            $query->where('technical_failure_notice.branch_id', (int) session('hr_branch_id'));
        }

        return $query
            ->leftJoin('technical_failure_notice_status as status', 'status.id', '=', 'technical_failure_notice.status')
            ->leftJoin('technical_failure_notice_platform as platform', 'platform.id', '=', 'technical_failure_notice.technical_failure_notice_platform')
            ->leftJoin('technical_failure_notice_service_type as service_type', 'service_type.id', '=', 'technical_failure_notice.technical_failure_notice_service_type')
            ->leftJoin('ra_users as user', 'user.hr_id', '=', 'technical_failure_notice.user_id')
            ->select([
                'technical_failure_notice.*',
                'status.name_ar as status_name_ar', 'status.name_en as status_name_en', 'status.info as status_color',
                'platform.name_ar as platform_name_ar', 'platform.name_en as platform_name_en',
                'service_type.name_ar as service_type_name_ar', 'service_type.name_en as service_type_name_en',
                'user.hr_first_name as user_name', 'user.hr_last_name as user_last_name',
            ]);
    }
}
