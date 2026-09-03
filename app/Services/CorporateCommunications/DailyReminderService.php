<?php

namespace App\Services\CorporateCommunications;

use App\Models\GovernmentCircularSectionAdministrator;
use App\Models\GovernmentDataRequest;
use App\Models\GovernmentInspectionVisit;
use App\Models\GovernmentInspectionVisitFinding;
use App\Models\GovernmentInspectionVisitReturned;
use App\Services\GovernmentDataRequests\GovernmentDataRequestService;
use App\Services\GovernmentInspectionVisits\GovernmentInspectionVisitService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class DailyReminderService
{
    public function __construct(
        private readonly DepartmentNotificationService $notifier,
        private readonly GovernmentInspectionVisitService $visits,
        private readonly GovernmentDataRequestService $dataRequests,
    ) {}

    /**
     * @return array{inspection_visits:int,data_requests:int,notifications:int}
     */
    public function sendPendingReminders(): array
    {
        $stats = [
            'inspection_visits' => 0,
            'data_requests' => 0,
            'notifications' => 0,
        ];

        foreach ($this->pendingInspectionVisits() as $visit) {
            $stats['inspection_visits']++;
            $stats['notifications'] += $this->remindInspectionVisit($visit);
        }

        foreach ($this->pendingDataRequests() as $request) {
            $stats['data_requests']++;
            $stats['notifications'] += $this->remindDataRequest($request);
        }

        Log::info('cc.daily_reminders.completed', $stats);

        return $stats;
    }

    /**
     * Visits awaiting department response (status New=1 or Returned=7)
     * with a reply deadline set and at least one unanswered item.
     *
     * @return Collection<int, GovernmentInspectionVisit>
     */
    public function pendingInspectionVisits(): Collection
    {
        // Some deployed legacy databases contain the status/audit relation
        // under this table name but not the finding/reply columns used by the
        // inspection workflow. Do not let the scheduler crash with SQLSTATE
        // 42S22; the schema must be restored before reminders can be enabled.
        if (! Schema::hasColumn('government_inspection_visits_abuses_and_notes', 'reply')
            || ! Schema::hasColumn('government_inspection_visits_returned', 'reply')) {
            Log::critical('cc.daily_reminders.inspection_schema_incomplete', [
                'required' => [
                    'government_inspection_visits_abuses_and_notes.reply',
                    'government_inspection_visits_returned.reply',
                ],
            ]);

            return collect();
        }

        $visitIdsWithPendingFindings = GovernmentInspectionVisitFinding::query()
            ->where(function ($query) {
                $query->whereNull('reply')->orWhere('reply', '');
            })
            ->pluck('government_inspection_visits_id');

        $visitIdsWithPendingReturns = GovernmentInspectionVisitReturned::query()
            ->where(function ($query) {
                $query->whereNull('reply')->orWhere('reply', '');
            })
            ->where(function ($query) {
                $query->whereNull('status')->orWhere('status', 0);
            })
            ->pluck('government_inspection_visits_id');

        $pendingIds = $visitIdsWithPendingFindings
            ->merge($visitIdsWithPendingReturns)
            ->unique()
            ->filter(static fn ($id) => (int) $id > 0)
            ->values();

        if ($pendingIds->isEmpty()) {
            return collect();
        }

        return GovernmentInspectionVisit::query()
            ->whereIn('id', $pendingIds)
            ->whereIn('status', [1, 7])
            ->whereNotNull('reply_time')
            ->orderBy('id')
            ->get();
    }

    /**
     * Data requests still with the department (sent=6 / returned=2)
     * once their reminder date has been reached.
     *
     * @return Collection<int, GovernmentDataRequest>
     */
    public function pendingDataRequests(): Collection
    {
        $reminderColumn = 'Reminderـtime';

        return GovernmentDataRequest::query()
            ->whereIn('status', [
                GovernmentDataRequestService::STATUS_SENT_TO_DEPT,
                GovernmentDataRequestService::STATUS_RETURNED,
            ])
            ->whereNotNull($reminderColumn)
            ->whereDate($reminderColumn, '<=', now()->toDateString())
            ->orderBy('id')
            ->get();
    }

    private function remindInspectionVisit(GovernmentInspectionVisit $visit): int
    {
        $adminIds = $this->parseUserIds((string) $visit->users);
        $returned = (int) $visit->status === 7;
        $sent = 0;

        foreach ($this->administratorsByIds($adminIds) as $admin) {
            $this->notifier->notifyAdministrator(
                $admin,
                __('cc_notifications.reminder_inspection_subject', ['number' => $visit->displayNumber()]),
                __('cc_notifications.reminder_inspection_intro', [
                    'deadline' => optional($visit->reply_time)->format('Y-m-d H:i') ?: '—',
                ]),
                $this->visits->departmentReplyUrl($visit, (int) $admin->id, $returned),
                'inspection_visits_reminder',
            );
            $sent++;
        }

        return $sent;
    }

    private function remindDataRequest(GovernmentDataRequest $request): int
    {
        $sectionId = (int) $request->send_Section;
        if ($sectionId < 1) {
            return 0;
        }

        $admins = GovernmentCircularSectionAdministrator::query()
            ->where('publish', 1)
            ->where('government_circulars_sections_id', $sectionId)
            ->where('companies_groups_id', (int) $request->companies_groups_id)
            ->get(['id', 'administrator', 'email', 'mobile']);

        $sent = 0;

        foreach ($admins as $admin) {
            $this->notifier->notifyAdministrator(
                $admin,
                __('cc_notifications.reminder_data_request_subject', ['number' => $request->displayNumber()]),
                __('cc_notifications.reminder_data_request_intro', [
                    'deadline' => $request->Data_delivery ?: '—',
                ]),
                $this->dataRequests->departmentReplyUrl($request, (int) $admin->id),
                'data_requests_reminder',
            );
            $sent++;
        }

        return $sent;
    }

    /**
     * @return list<int>
     */
    private function parseUserIds(string $users): array
    {
        if (trim($users) === '' || $users === '0') {
            return [];
        }

        return array_values(array_filter(array_map(
            static fn ($id) => (int) $id,
            preg_split('/\s*,\s*/', $users) ?: []
        ), static fn ($id) => $id > 0));
    }

    /**
     * @param  list<int>  $ids
     * @return Collection<int, GovernmentCircularSectionAdministrator>
     */
    private function administratorsByIds(array $ids): Collection
    {
        if ($ids === []) {
            return collect();
        }

        return GovernmentCircularSectionAdministrator::query()
            ->whereIn('id', $ids)
            ->get(['id', 'administrator', 'email', 'mobile']);
    }
}
