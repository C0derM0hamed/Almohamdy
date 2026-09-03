<?php

namespace App\Services\Licenses;

use App\Models\License;
use App\Models\LicenseNotification;
use App\Models\LicensePaymentRequest;
use App\Repositories\Licenses\LicenseRepository;
use App\Services\Pdf\ArabicPdfService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\LazyCollection;
use Illuminate\Support\Collection;
use InvalidArgumentException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class LicenseExportService
{
    public function __construct(
        private readonly LicenseRepository $repository,
        private readonly ArabicPdfService $pdf,
    ) {}

    /** @param array<string,mixed> $filters */
    public function download(array $filters, string $format): Response
    {
        $format = strtolower($format);
        $report = (string) ($filters['report'] ?? 'licenses');
        $query = $this->repository->filteredQuery($filters)->with(['authority', 'type', 'status', 'renewalStage', 'responsibleUser', 'branches'])->orderBy('expiry_date');
        $filename = 'licenses-'.now()->format('Y-m-d-His');

        if ($report !== 'licenses') {
            $table = $this->reportTable($report, $query);

            return match ($format) {
                'csv' => $this->tabularCsv($table, $filename.'-'.$report.'.csv'),
                'xls', 'excel' => $this->tabularExcel($table, $filename.'-'.$report.'.xls'),
                'pdf' => $this->pdf->loadView('licenses.reports.tabular-pdf', $table)->download($filename.'-'.$report.'.pdf'),
                default => throw new InvalidArgumentException('Unsupported license export format.'),
            };
        }

        return match ($format) {
            'csv' => $this->csv($query, $filename.'.csv'),
            'xls', 'excel' => $this->excel($query, $filename.'.xls'),
            'pdf' => $this->pdf->loadView('licenses.pdf', ['licenses' => $query->get(), 'isListReport' => true])->download($filename.'.pdf'),
            default => throw new InvalidArgumentException('Unsupported license export format.'),
        };
    }

    public function recordPdf(License $license): Response
    {
        $record = $this->repository->findOrFailForDetail((int) $license->getKey());

        return $this->pdf->loadView('licenses.pdf', ['license' => $record, 'licenses' => collect([$record]), 'isListReport' => false])
            ->download('license-'.$record->getKey().'.pdf');
    }

    /** @return list<string> */
    private function headers(): array
    {
        return [
            __('licenses.columns.id'), __('licenses.columns.number'), __('licenses.columns.title'),
            __('licenses.columns.authority'), __('licenses.columns.type'), __('licenses.columns.branches'),
            __('licenses.columns.responsible'), __('licenses.columns.issue_date'), __('licenses.columns.expiry_date'),
            __('licenses.columns.status'), __('licenses.columns.renewal_stage'),
        ];
    }

    /** @return list<string> */
    private function row(License $license): array
    {
        return [
            (string) $license->getKey(), (string) ($license->license_number ?: '—'), (string) ($license->title ?: '—'),
            $license->authority?->localizedName() ?? '—', $license->type?->localizedName() ?? '—',
            $license->branches->map(fn ($branch) => app()->getLocale() === 'ar' ? $branch->name_ar : $branch->name_en)->filter()->implode(', '),
            $license->responsibleUser?->displayName() ?? '—', $this->date($license->issue_date), $this->date($license->expiry_date),
            $license->status?->localizedName() ?? '—', $license->renewalStage?->localizedName() ?? '—',
        ];
    }

    /** @param Builder<License> $query */
    private function csv(Builder $query, string $filename): StreamedResponse
    {
        return response()->streamDownload(function () use ($query): void {
            $handle = fopen('php://output', 'w');
            if ($handle === false) {
                return;
            }
            fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF));
            fputcsv($handle, $this->headers());
            foreach ($this->rows($query) as $license) {
                fputcsv($handle, $this->row($license));
            }
            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /** @param Builder<License> $query */
    private function excel(Builder $query, string $filename): StreamedResponse
    {
        return response()->streamDownload(function () use ($query): void {
            echo '<?xml version="1.0" encoding="UTF-8"?><?mso-application progid="Excel.Sheet"?>';
            echo '<Workbook xmlns="urn:schemas-microsoft-com:office:spreadsheet" xmlns:ss="urn:schemas-microsoft-com:office:spreadsheet"><Worksheet ss:Name="Licenses"><Table><Row>';
            foreach ($this->headers() as $value) {
                echo '<Cell><Data ss:Type="String">'.$this->xml($value).'</Data></Cell>';
            }
            echo '</Row>';
            foreach ($this->rows($query) as $license) {
                echo '<Row>';
                foreach ($this->row($license) as $value) {
                    echo '<Cell><Data ss:Type="String">'.$this->xml($value).'</Data></Cell>';
                }
                echo '</Row>';
            }
            echo '</Table></Worksheet></Workbook>';
        }, $filename, ['Content-Type' => 'application/vnd.ms-excel; charset=UTF-8']);
    }

    /** @param Builder<License> $query @return LazyCollection<int,License> */
    private function rows(Builder $query): LazyCollection
    {
        return $query->cursor();
    }

    private function date(mixed $date): string
    {
        return $date instanceof \DateTimeInterface ? $date->format('Y-m-d') : (string) $date;
    }

    private function xml(string $value): string
    {
        return htmlspecialchars($value, ENT_XML1 | ENT_QUOTES, 'UTF-8');
    }

    /** @param Builder<License> $licenses @return array{title:string,headers:list<string>,rows:Collection<int,list<string>>} */
    private function reportTable(string $report, Builder $licenses): array
    {
        $licenseIds = (clone $licenses)->select('licenses.id');

        return match ($report) {
            'payments' => $this->paymentReport($licenseIds),
            'alerts' => $this->alertReport($licenseIds),
            'responsibilities' => $this->responsibilityReport($licenses),
            default => throw new InvalidArgumentException('Unsupported license report.'),
        };
    }

    /** @return array{title:string,headers:list<string>,rows:Collection<int,list<string>>} */
    private function paymentReport(Builder $licenseIds): array
    {
        $rows = LicensePaymentRequest::query()->whereIn('license_id', $licenseIds)
            ->with(['license', 'status', 'requester'])->latest('created_at')->get()
            ->map(fn (LicensePaymentRequest $payment) => [
                '#'.$payment->getKey(), $payment->license?->displayTitle() ?? '—',
                number_format((float) $payment->amount, 2).' '.($payment->currency ?: 'SAR'),
                $payment->status?->localizedName() ?? '—', $payment->requester?->displayName() ?? '—',
                $this->date($payment->created_at), $this->date($payment->closed_at), (string) ($payment->invoice_number ?: '—'),
            ]);

        return ['title' => __('licenses.reports.payments'), 'headers' => [
            __('licenses.payments.request_number'), __('licenses.fields.license_number'), __('licenses.payments.amount'),
            __('licenses.payments.status'), __('licenses.payments.requested_by'), __('licenses.payments.requested_at'),
            __('licenses.payments.closed_at'), __('licenses.payments.invoice_number'),
        ], 'rows' => $rows];
    }

    /** @return array{title:string,headers:list<string>,rows:Collection<int,list<string>>} */
    private function alertReport(Builder $licenseIds): array
    {
        $rows = LicenseNotification::query()->whereIn('license_id', $licenseIds)->with(['license', 'recipientUser'])
            ->latest('created_at')->get()->map(fn (LicenseNotification $notification) => [
                $notification->license?->displayTitle() ?? '—', __('licenses.timeline.events.'.$notification->event_type),
                $notification->recipientUser?->displayName() ?? (string) ($notification->recipient_email ?: '—'),
                strtoupper((string) $notification->channel), __('licenses.notifications.'.$notification->status),
                $this->date($notification->created_at), (string) ($notification->reason ?: '—'),
            ]);

        return ['title' => __('licenses.reports.alerts'), 'headers' => [
            __('licenses.fields.license_number'), __('licenses.notifications.event'), __('licenses.notifications.recipient'),
            __('licenses.notifications.channel'), __('licenses.notifications.delivery'), __('licenses.notifications.sent_at'), __('licenses.fields.notes'),
        ], 'rows' => $rows];
    }

    /** @return array{title:string,headers:list<string>,rows:Collection<int,list<string>>} */
    private function responsibilityReport(Builder $licenses): array
    {
        $rows = $licenses->with(['responsibleUser', 'undertakings'])->get()->map(function (License $license): array {
            $undertaking = $license->undertakings->sortByDesc('id')->first();

            return [
                $license->displayTitle(), $license->responsibleUser?->displayName() ?? '—',
                $undertaking?->status ? __('licenses.undertaking.'.$undertaking->status) : '—',
                $this->date($undertaking?->requested_at), $this->date($undertaking?->accepted_at), $this->date($license->expiry_date),
            ];
        });

        return ['title' => __('licenses.reports.responsibilities'), 'headers' => [
            __('licenses.fields.license_number'), __('licenses.fields.responsible'), __('licenses.sections.responsibility'),
            __('licenses.undertaking.requested_at'), __('licenses.undertaking.accepted_at'), __('licenses.fields.expiry_date'),
        ], 'rows' => $rows];
    }

    /** @param array{title:string,headers:list<string>,rows:Collection<int,list<string>>} $table */
    private function tabularCsv(array $table, string $filename): StreamedResponse
    {
        return response()->streamDownload(function () use ($table): void {
            $handle = fopen('php://output', 'w');
            if ($handle === false) return;
            fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF));
            fputcsv($handle, $table['headers']);
            foreach ($table['rows'] as $row) fputcsv($handle, $row);
            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /** @param array{title:string,headers:list<string>,rows:Collection<int,list<string>>} $table */
    private function tabularExcel(array $table, string $filename): StreamedResponse
    {
        return response()->streamDownload(function () use ($table): void {
            echo '<?xml version="1.0" encoding="UTF-8"?><?mso-application progid="Excel.Sheet"?>';
            echo '<Workbook xmlns="urn:schemas-microsoft-com:office:spreadsheet" xmlns:ss="urn:schemas-microsoft-com:office:spreadsheet"><Worksheet ss:Name="'.$this->xml($table['title']).'"><Table><Row>';
            foreach ($table['headers'] as $value) echo '<Cell><Data ss:Type="String">'.$this->xml($value).'</Data></Cell>';
            echo '</Row>';
            foreach ($table['rows'] as $row) {
                echo '<Row>';
                foreach ($row as $value) echo '<Cell><Data ss:Type="String">'.$this->xml($value).'</Data></Cell>';
                echo '</Row>';
            }
            echo '</Table></Worksheet></Workbook>';
        }, $filename, ['Content-Type' => 'application/vnd.ms-excel; charset=UTF-8']);
    }
}
