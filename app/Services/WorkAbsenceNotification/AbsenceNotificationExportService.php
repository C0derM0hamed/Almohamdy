<?php

namespace App\Services\WorkAbsenceNotification;

use App\Models\AbsenceNotificationService;
use App\Repositories\WorkAbsenceNotification\AbsenceNotificationRepository;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\LazyCollection;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AbsenceNotificationExportService
{
    public function __construct(
        private readonly AbsenceNotificationRepository $repository,
    ) {}

    public function download(
        ?Carbon $dateFrom,
        ?Carbon $dateTo,
        ?int $notificationTypeId,
        string $employeeSearch,
        ?string $workflowStatus,
        string $format,
    ): StreamedResponse {
        $query = $this->exportQuery(
            $dateFrom,
            $dateTo,
            $notificationTypeId,
            $employeeSearch,
            $workflowStatus,
        );

        $headers = $this->columnHeaders();
        $filename = 'absence-notifications-'.now()->format('Y-m-d-His');

        return match ($format) {
            'csv' => $this->csvResponse($query, $headers, $filename.'.csv'),
            'excel' => $this->excelResponse($query, $headers, $filename.'.xls'),
            default => throw new \InvalidArgumentException('Unsupported export format.'),
        };
    }

    /**
     * @return Builder<int, AbsenceNotificationService>
     */
    private function exportQuery(
        ?Carbon $dateFrom,
        ?Carbon $dateTo,
        ?int $notificationTypeId,
        string $employeeSearch,
        ?string $workflowStatus,
    ): Builder {
        return $this->repository
            ->filteredQuery(
                $dateFrom,
                $dateTo,
                $notificationTypeId,
                $employeeSearch,
                $workflowStatus,
            )
            ->with([
                'employee:hr_id,hr_first_name,hr_last_name,hr_username',
                'notificationType:id,name_en,name_ar',
            ]);
    }

    /**
     * @return list<string>
     */
    private function columnHeaders(): array
    {
        return [
            __('work_absence_notification.columns.request_id'),
            __('work_absence_notification.columns.employee'),
            __('work_absence_notification.columns.notification_type'),
            __('work_absence_notification.columns.begin_date'),
            __('work_absence_notification.columns.end_date'),
            __('work_absence_notification.columns.absence_days'),
            __('work_absence_notification.columns.workflow_status'),
            __('work_absence_notification.columns.created_date'),
        ];
    }

    /**
     * @return list<string>
     */
    private function mapRow(AbsenceNotificationService $notification): array
    {
        $absenceDays = $notification->absence_days;

        return [
            (string) $notification->id,
            $notification->employeeDisplayName(),
            $notification->notificationTypeLabel(),
            $notification->formattedBeginDate(),
            $notification->formattedEndDate(),
            $absenceDays === null || $absenceDays === '' ? '—' : (string) $absenceDays,
            $notification->workflowStatusLabel(),
            $notification->formattedCreatedDate(),
        ];
    }

    /**
     * @param Builder<int, AbsenceNotificationService> $query
     * @param list<string> $headers
     */
    private function csvResponse(Builder $query, array $headers, string $filename): StreamedResponse
    {
        return response()->streamDownload(function () use ($query, $headers): void {
            $handle = fopen('php://output', 'w');

            if ($handle === false) {
                return;
            }

            fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF));
            fputcsv($handle, $headers);

            foreach ($this->cursorRows($query) as $notification) {
                fputcsv($handle, $this->mapRow($notification));
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    /**
     * @param Builder<int, AbsenceNotificationService> $query
     * @param list<string> $headers
     */
    private function excelResponse(Builder $query, array $headers, string $filename): StreamedResponse
    {
        return response()->streamDownload(function () use ($query, $headers): void {
            echo '<?xml version="1.0" encoding="UTF-8"?>';
            echo '<?mso-application progid="Excel.Sheet"?>';
            echo '<Workbook xmlns="urn:schemas-microsoft-com:office:spreadsheet" ';
            echo 'xmlns:ss="urn:schemas-microsoft-com:office:spreadsheet">';
            echo '<Worksheet ss:Name="'.self::xmlEscape(__('work_absence_notification.title')).'">';
            echo '<Table>';

            echo '<Row>';
            foreach ($headers as $header) {
                echo '<Cell><Data ss:Type="String">'.self::xmlEscape($header).'</Data></Cell>';
            }
            echo '</Row>';

            foreach ($this->cursorRows($query) as $notification) {
                echo '<Row>';
                foreach ($this->mapRow($notification) as $value) {
                    echo '<Cell><Data ss:Type="String">'.self::xmlEscape($value).'</Data></Cell>';
                }
                echo '</Row>';
            }

            echo '</Table></Worksheet></Workbook>';
        }, $filename, [
            'Content-Type' => 'application/vnd.ms-excel; charset=UTF-8',
        ]);
    }

    /**
     * @param Builder<int, AbsenceNotificationService> $query
     * @return LazyCollection<int, AbsenceNotificationService>
     */
    private function cursorRows(Builder $query): LazyCollection
    {
        /** @var LazyCollection<int, AbsenceNotificationService> $rows */
        $rows = $query->cursor();

        return $rows;
    }

    private static function xmlEscape(string $value): string
    {
        return htmlspecialchars($value, ENT_XML1 | ENT_QUOTES, 'UTF-8');
    }
}
