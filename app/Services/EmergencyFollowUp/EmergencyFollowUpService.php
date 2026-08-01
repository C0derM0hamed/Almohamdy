<?php

namespace App\Services\EmergencyFollowUp;

use App\Models\EmergencyFollowUp;
use App\Models\EmergencyFollowUpNotice;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class EmergencyFollowUpService
{
    public const COMPANY_ID = 1;

    public const BRANCH_ID = 1;

    public function authorizeBranch(): void
    {
        abort_unless(
            (int) session('companies_groups_id', 0) === self::COMPANY_ID
                && (int) session('hr_branch_id', 0) === self::BRANCH_ID,
            403,
        );
    }

    public function listOpen(): LengthAwarePaginator
    {
        $this->authorizeBranch();

        return $this->scopedQuery()
            ->where('status', 2)
            ->with(['noticeType', 'creator', 'latestNotice.creator'])
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString();
    }

    public function noticeTypes(): Collection
    {
        $this->authorizeBranch();

        return DB::table('notice_type')
            ->where('branch_id', self::BRANCH_ID)
            ->where('publish', 1)
            ->orderBy('id')
            ->get(['id', 'name_ar', 'name_en']);
    }

    public function find(int $id): ?EmergencyFollowUp
    {
        $this->authorizeBranch();

        return $this->scopedQuery()
            ->with(['noticeType', 'creator', 'notices.creator'])
            ->whereKey($id)
            ->first();
    }

    public function create(array $data): EmergencyFollowUp
    {
        $this->authorizeBranch();

        abort_unless($this->noticeTypes()->contains('id', (int) $data['notice']), 422);

        return EmergencyFollowUp::query()->create([
            'date' => (string) time(),
            'branch_id' => self::BRANCH_ID,
            'file_number' => (int) $data['file_number'],
            'notice' => (int) $data['notice'],
            'description' => trim((string) $data['description']),
            'notice_type' => (int) $data['notice_type'],
            'action' => trim((string) $data['action']),
            'status' => (int) $data['status'],
            'created_by' => (int) session('hr_user_id', 0),
        ]);
    }

    public function addNotice(int $id, string $notice): void
    {
        $followUp = $this->find($id);
        abort_if($followUp === null, 404);

        EmergencyFollowUpNotice::query()->create([
            'emergency_follow_up_id' => $followUp->id,
            'notice' => trim($notice),
            'created_at' => now(),
            'created_by' => (int) session('hr_user_id', 0),
        ]);
    }

    public function close(int $id): void
    {
        $followUp = $this->find($id);
        abort_if($followUp === null, 404);

        EmergencyFollowUp::query()->whereKey($followUp->id)->update([
            'status' => 1,
            'updated_by' => (int) session('hr_user_id', 0),
            'updated_at' => now()->format('Y-m-d H:i:s'),
        ]);
    }

    private function scopedQuery()
    {
        return EmergencyFollowUp::query()->where('branch_id', self::BRANCH_ID);
    }
}
