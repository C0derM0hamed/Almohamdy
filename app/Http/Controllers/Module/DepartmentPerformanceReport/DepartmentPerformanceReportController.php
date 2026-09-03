<?php

namespace App\Http\Controllers\Module\DepartmentPerformanceReport;

use App\Http\Controllers\Controller;
use App\Services\DepartmentPerformanceReport\DepartmentPerformanceReportService;
use App\Services\Pdf\ArabicPdfService;
use App\Support\ProtectedFileDownload;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;

class DepartmentPerformanceReportController extends Controller
{
    public function __construct(
        private readonly DepartmentPerformanceReportService $reports,
        private readonly ProtectedFileDownload $downloads,
    ) {}

    public function index(Request $request, string $department): View
    {
        $filters = $this->filters($request, false);

        return view('department-performance-reports.index', $this->reports->report($department, $filters) + [
            'department' => $department,
            'definition' => $this->reports->definition($department),
            'filters' => $filters,
            'options' => $this->reports->options($department),
            'homeRoute' => 'dashboard',
        ]);
    }

    public function pdf(Request $request, string $department): Response
    {
        $filters = $this->filters($request, true);

        return app(ArabicPdfService::class)
            ->loadView('department-performance-reports.pdf', $this->reports->report($department, $filters) + [
                'department' => $department,
                'definition' => $this->reports->definition($department),
                'filters' => $filters,
            ])
            ->setPaper('a4', 'landscape')
            ->download('department-report-'.$department.'.pdf');
    }

    public function attachment(string $department, string $section, int $entry): BinaryFileResponse
    {
        $row = $this->reports->attachment($department, $section, $entry);
        abort_if($row === null, 404);

        return $this->downloads->download($row->file, __('department_reports.attachment'), ['files']);
    }

    /** @return array{from:string,to:string,employee:string,period:string,submitted:bool} */
    private function filters(Request $request, bool $submitted): array
    {
        return $this->reports->filters([
            'from' => $request->input('from'),
            'to' => $request->input('to'),
            'employee' => $request->input('employee', 'all'),
            'period' => $request->input('period', 'all'),
            'submitted' => $submitted || $request->boolean('show'),
        ]);
    }
}
