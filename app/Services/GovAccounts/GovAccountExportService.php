<?php

namespace App\Services\GovAccounts;

use App\Models\GovAccount;
use App\Models\GovAccountNoticeRecipient;
use App\Models\GovAccountRequest;
use App\Repositories\GovAccounts\GovAccountRepository;
use App\Support\GovAccounts\GovAccountPermissions;
use Illuminate\Database\Eloquent\Builder;
use Symfony\Component\HttpFoundation\StreamedResponse;

class GovAccountExportService
{
    public function __construct(private readonly GovAccountRepository $repository) {}

    public function download(array $filters, string $format): StreamedResponse
    {
        $this->repository->authorizeAny(GovAccountPermissions::EXPORT);
        $report = (string) ($filters['report'] ?? 'accounts');
        [$headers, $rows] = match ($report) {
            'accounts' => $this->accounts($filters),
            'requests' => $this->requests($filters),
            'notices' => $this->notices($filters),
            default => abort(422),
        };
        $filename = 'government-accounts-'.$report.'-'.now()->format('Y-m-d-His');

        return match ($format) {
            'csv' => $this->csv($headers, $rows, $filename.'.csv'),
            'xls' => $this->xls($headers, $rows, $filename.'.xls', ucfirst($report)),
            default => abort(404),
        };
    }

    private function accounts(array $filters): array
    {
        $query = $this->repository->scopedAccounts()->with(['employee', 'authority', 'service', 'role', 'sourceRequest'])
            ->when($filters['status'], fn (Builder $q, $value) => $q->where('status', $value))
            ->when($filters['employee_user_id'], fn (Builder $q, $value) => $q->where('employee_user_id', $value))
            ->when($filters['authority_id'], fn (Builder $q, $value) => $q->where('authority_id', $value))
            ->when($filters['service_id'], fn (Builder $q, $value) => $q->where('service_id', $value))->orderBy('id');

        return [[__('gov_accounts.export.id'), __('gov_accounts.fields.employee'), __('gov_accounts.fields.department'), __('gov_accounts.fields.authority'), __('gov_accounts.fields.service'), __('gov_accounts.fields.role'), __('gov_accounts.fields.username'), __('gov_accounts.fields.status'), __('gov_accounts.export.created_at')],
            $query->cursor()->map(fn (GovAccount $account): array => [(string) $account->id, $account->employee?->displayName() ?? '—', $account->sourceRequest?->department?->localizedName() ?? (string) ($account->sourceRequest?->department_id ?? '—'), $account->authority?->localizedName() ?? '—', $account->service?->localizedName() ?? '—', $account->role?->localizedName() ?? '—', (string) $account->username, __('gov_accounts.account_statuses.'.$account->status), $account->created_at?->format('Y-m-d H:i') ?? '—'])];
    }

    private function requests(array $filters): array
    {
        $query = $this->repository->scopedRequests()->with(['employee', 'department', 'authority', 'service', 'role'])
            ->when($filters['status'], fn (Builder $q, $value) => $q->where('status', $value))
            ->when($filters['type'], fn (Builder $q, $value) => $q->where('type', $value))
            ->when($filters['employee_user_id'], fn (Builder $q, $value) => $q->where('employee_user_id', $value))
            ->when($filters['authority_id'], fn (Builder $q, $value) => $q->where('authority_id', $value))
            ->when($filters['service_id'], fn (Builder $q, $value) => $q->where('service_id', $value))
            ->when($filters['date_from'], fn (Builder $q, $value) => $q->whereDate('created_at', '>=', $value))
            ->when($filters['date_to'], fn (Builder $q, $value) => $q->whereDate('created_at', '<=', $value))->orderBy('id');

        return [[__('gov_accounts.export.id'), __('gov_accounts.fields.employee'), __('gov_accounts.fields.department'), __('gov_accounts.export.type'), __('gov_accounts.fields.authority'), __('gov_accounts.fields.service'), __('gov_accounts.fields.status'), __('gov_accounts.fields.round'), __('gov_accounts.export.created_at')],
            $query->cursor()->map(fn (GovAccountRequest $request): array => [(string) $request->id, $request->employee?->displayName() ?? '—', $request->department?->localizedName() ?? '—', __('gov_accounts.types.'.$request->type), $request->authority?->localizedName() ?? '—', $request->service?->localizedName() ?? '—', __('gov_accounts.statuses.'.$request->status), (string) $request->round, $request->created_at?->format('Y-m-d H:i') ?? '—'])];
    }

    private function notices(array $filters): array
    {
        $query = GovAccountNoticeRecipient::query()->whereHas('notice', function (Builder $notice) use ($filters): void {
            $notice->where('companies_groups_id', (int) session('companies_groups_id', 0))
                ->when($filters['authority_id'], fn (Builder $q, $value) => $q->where('authority_id', $value))
                ->when($filters['service_id'], fn (Builder $q, $value) => $q->where('service_id', $value))
                ->when($filters['date_from'], fn (Builder $q, $value) => $q->whereDate('event_date', '>=', $value))
                ->when($filters['date_to'], fn (Builder $q, $value) => $q->whereDate('event_date', '<=', $value));
        })->with(['notice.authority', 'notice.service', 'user'])->orderBy('id');

        return [[__('gov_accounts.export.notice'), __('gov_accounts.fields.authority'), __('gov_accounts.fields.service'), __('gov_accounts.fields.event_date'), __('gov_accounts.fields.employee'), __('gov_accounts.fields.email'), __('gov_accounts.fields.status'), __('gov_accounts.fields.viewed_at'), __('gov_accounts.export.view_count')],
            $query->cursor()->map(fn (GovAccountNoticeRecipient $recipient): array => [$recipient->notice?->title ?? '—', $recipient->notice?->authority?->localizedName() ?? '—', $recipient->notice?->service?->localizedName() ?? '—', $recipient->notice?->event_date?->format('Y-m-d') ?? '—', $recipient->user?->displayName() ?? '—', (string) $recipient->email, $recipient->viewed_at ? __('gov_accounts.notices.viewed') : __('gov_accounts.notices.not_viewed'), $recipient->viewed_at?->format('Y-m-d H:i') ?? '—', (string) $recipient->view_count])];
    }

    private function csv(array $headers, iterable $rows, string $filename): StreamedResponse
    {
        return response()->streamDownload(function () use ($headers, $rows): void {
            $handle = fopen('php://output', 'w');
            if ($handle === false) {
                return;
            }
            fwrite($handle, "\xEF\xBB\xBF");
            fputcsv($handle, $headers);
            foreach ($rows as $row) {
                fputcsv($handle, $row);
            }
            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    private function xls(array $headers, iterable $rows, string $filename, string $sheet): StreamedResponse
    {
        return response()->streamDownload(function () use ($headers, $rows, $sheet): void {
            echo '<?xml version="1.0" encoding="UTF-8"?><?mso-application progid="Excel.Sheet"?>';
            echo '<Workbook xmlns="urn:schemas-microsoft-com:office:spreadsheet" xmlns:ss="urn:schemas-microsoft-com:office:spreadsheet"><Worksheet ss:Name="'.$this->xml($sheet).'"><Table><Row>';
            foreach ($headers as $value) {
                echo '<Cell><Data ss:Type="String">'.$this->xml($value).'</Data></Cell>';
            }
            echo '</Row>';
            foreach ($rows as $row) {
                echo '<Row>';
                foreach ($row as $value) {
                    echo '<Cell><Data ss:Type="String">'.$this->xml($value).'</Data></Cell>';
                }
                echo '</Row>';
            }
            echo '</Table></Worksheet></Workbook>';
        }, $filename, ['Content-Type' => 'application/vnd.ms-excel; charset=UTF-8']);
    }

    private function xml(string $value): string
    {
        return htmlspecialchars($value, ENT_XML1 | ENT_QUOTES, 'UTF-8');
    }
}
