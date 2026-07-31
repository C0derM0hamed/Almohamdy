<?php

namespace App\Repositories\WorkAbsenceNotification;

use App\Models\AbsenceNotificationService;
use App\Models\AbsenceNotificationServiceMemo;
use App\Models\AbsenceNotificationServiceSendTo;
use App\Models\MemoType;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class AbsenceNotificationMemoRepository
{
    /**
     * @var list<string>
     */
    private const NOTIFICATION_COLUMNS = [
        'id',
        'companies_groups_id',
        'user_id',
        'begin_date',
        'end_date',
        'action_type',
        'activated_by',
        'activated_at',
    ];

    /**
     * @var list<string>
     */
    private const MEMO_COLUMNS = [
        'id',
        'absence_notification_service_id',
        'user_id',
        'memo_type',
        'date',
        'sms_tocken',
        'begin_date',
        'end_date',
        'hours',
        'pending_inquiries',
    ];

    /**
     * @var list<string>
     */
    private const RECIPIENT_USER_COLUMNS = [
        'hr_id',
        'hr_first_name',
        'hr_last_name',
        'hr_username',
        'job_title',
    ];

    /**
     * @var list<string>
     */
    private const RECIPIENT_COLUMNS = [
        'id',
        'memo_id',
        'user_id',
        'seen_at',
    ];

    public function findNotificationForMemo(int $notificationId): ?AbsenceNotificationService
    {
        $companyGroupId = (int) session('companies_groups_id', 0);

        return AbsenceNotificationService::query()
            ->select(self::NOTIFICATION_COLUMNS)
            ->when($companyGroupId > 0, fn (Builder $query) => $query->where('companies_groups_id', $companyGroupId))
            ->whereKey($notificationId)
            ->first();
    }

    /**
     * @return Collection<int, MemoType>
     */
    public function memoTypeOptions(): Collection
    {
        return MemoType::query()
            ->select(['id', 'name_en', 'name_ar'])
            ->orderBy('id')
            ->get();
    }

    /**
     * @param list<int> $recipientIds
     * @return list<int>
     */
    public function validRecipientIds(array $recipientIds): array
    {
        if ($recipientIds === []) {
            return [];
        }

        $companyGroupId = (int) session('companies_groups_id', 0);

        return User::query()
            ->select(['hr_id'])
            ->whereIn('hr_id', $recipientIds)
            ->where('activated', '1')
            ->when($companyGroupId > 0, fn (Builder $query) => $query->where('companies_groups_id', $companyGroupId))
            ->pluck('hr_id')
            ->map(fn ($id) => (int) $id)
            ->all();
    }

    /**
     * @return Collection<int, User>
     */
    public function recipientOptions(): Collection
    {
        $companyGroupId = (int) session('companies_groups_id', 0);

        return User::query()
            ->select(self::RECIPIENT_USER_COLUMNS)
            ->where('activated', '1')
            ->when($companyGroupId > 0, fn (Builder $query) => $query->where('companies_groups_id', $companyGroupId))
            ->orderBy('hr_first_name')
            ->orderBy('hr_last_name')
            ->orderBy('hr_id')
            ->get();
    }

    /**
     * @param array{
     *     absence_notification_service_id: int,
     *     user_id: int,
     *     memo_type: int,
     *     date: string,
     *     sms_tocken: string,
     *     begin_date: ?string,
     *     end_date: ?string,
     *     pending_inquiries?: ?string
     * } $attributes
     */
    public function createMemo(array $attributes): int
    {
        $memo = AbsenceNotificationServiceMemo::query()->create($attributes);

        return (int) $memo->id;
    }

    /**
     * @param list<int> $recipientIds
     */
    public function createRecipients(int $memoId, array $recipientIds): void
    {
        if ($recipientIds === []) {
            return;
        }

        $rows = array_map(
            fn (int $recipientId): array => [
                'memo_id' => $memoId,
                'user_id' => $recipientId,
                'seen_at' => null,
            ],
            $recipientIds,
        );

        DB::table('absence_notification_service_send_to')->insert($rows);
    }

    public function findMemoForTimeline(int $memoId): ?AbsenceNotificationServiceMemo
    {
        return AbsenceNotificationServiceMemo::query()
            ->select(self::MEMO_COLUMNS)
            ->whereKey($memoId)
            ->with([
                'employee:hr_id,hr_first_name,hr_last_name,hr_username',
                'memoType:id,name_en,name_ar',
                'recipients' => fn ($query) => $query
                    ->select(self::RECIPIENT_COLUMNS)
                    ->orderBy('id'),
                'recipients.recipient:hr_id,hr_first_name,hr_last_name,hr_username',
            ])
            ->first();
    }

    /**
     * @return Collection<int, AbsenceNotificationServiceMemo>
     */
    public function memosForNotification(int $notificationId): Collection
    {
        $companyGroupId = (int) session('companies_groups_id', 0);

        return AbsenceNotificationServiceMemo::query()
            ->select(self::MEMO_COLUMNS)
            ->where('absence_notification_service_id', $notificationId)
            ->whereHas('notification', function (Builder $query) use ($companyGroupId) {
                if ($companyGroupId > 0) {
                    $query->where('companies_groups_id', $companyGroupId);
                }
            })
            ->with([
                'employee:hr_id,hr_first_name,hr_last_name,hr_username',
                'memoType:id,name_en,name_ar',
                'recipients' => fn ($query) => $query
                    ->select(self::RECIPIENT_COLUMNS)
                    ->orderBy('id'),
                'recipients.recipient:hr_id,hr_first_name,hr_last_name,hr_username',
            ])
            ->orderBy('id')
            ->get();
    }

    /**
     * @return array{recipients_total: int, recipients_viewed: int, recipients_pending_view: int}
     */
    public function recipientDashboardCounts(): array
    {
        $baseQuery = $this->scopedRecipientQuery();

        $total = (clone $baseQuery)->count();
        $viewed = (clone $baseQuery)->seen()->count();

        return [
            'recipients_total' => $total,
            'recipients_viewed' => $viewed,
            'recipients_pending_view' => max(0, $total - $viewed),
        ];
    }

    /**
     * @return Builder<int, AbsenceNotificationServiceSendTo>
     */
    private function scopedRecipientQuery(): Builder
    {
        $companyGroupId = (int) session('companies_groups_id', 0);

        return AbsenceNotificationServiceSendTo::query()
            ->whereHas('memo.notification', function (Builder $query) use ($companyGroupId) {
                if ($companyGroupId > 0) {
                    $query->where('companies_groups_id', $companyGroupId);
                }
            });
    }
}
