<?php

namespace App\Http\Controllers\Module\EmergencyPerformanceReport;

use App\Http\Controllers\Controller;
use App\Services\EmergencyPerformanceReport\EmergencyPerformanceReportService;
use App\Support\ProtectedFileDownload;
use App\Services\Pdf\ArabicPdfService;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;

class EmergencyPerformanceReportController extends Controller
{
    public function __construct(
        private readonly EmergencyPerformanceReportService $reports,
        private readonly ProtectedFileDownload $downloads,
    ) {}

    public function index(Request $request): View
    {
        $filters = $this->reports->filters([
            'from' => $request->input('from'), 'to' => $request->input('to'),
            'employee' => $request->input('employee', 'all'), 'period' => $request->input('period', 'all'),
            'submitted' => $request->boolean('show'),
        ]);

        return view('emergency-performance-reports.index', $this->reports->report($filters) + [
            'filters' => $filters, 'options' => $this->reports->options(), 'homeRoute' => 'dashboard',
        ]);
    }

    public function pdf(Request $request): Response
    {
        $filters = $this->reports->filters([
            'from' => $request->input('from'), 'to' => $request->input('to'),
            'employee' => $request->input('employee', 'all'), 'period' => $request->input('period', 'all'),
            'submitted' => true,
        ]);

        return app(ArabicPdfService::class)->loadView('emergency-performance-reports.pdf', $this->reports->report($filters) + ['filters' => $filters])
            ->setPaper('a4', 'landscape')->download('emergency-performance-report.pdf');
    }

    public function attachment(string $section, int $entry): BinaryFileResponse
    {
        $row = $this->reports->attachment($section, $entry);
        abort_if($row === null, 404);

        return $this->downloads->download($row->file, __('emergency_reports.attachment'), ['files']);
    }
}
