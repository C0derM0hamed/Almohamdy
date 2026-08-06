<?php

namespace App\Http\Controllers\Module\Complaints;

use App\Http\Controllers\Concerns\ResolvesDashboardView;
use App\Http\Controllers\Controller;
use App\Http\Requests\Complaints\ComplaintIndexRequest;
use App\Http\Requests\Complaints\StoreComplaintRequest;
use App\Http\Requests\Complaints\StoreComplaintReplyRequest;
use App\Services\Complaints\ComplaintService;
use App\Services\Pdf\ArabicPdfService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class ComplaintController extends Controller
{
    use ResolvesDashboardView;

    public function __construct(
        private readonly ComplaintService $complaintService,
    ) {}

    public function index(ComplaintIndexRequest $request): View
    {
        return view('complaints.index', [
            'complaints' => $this->complaintService->listPaginated(
                $request->search(),
                $request->status(),
            ),
            'filters' => [
                'search' => $request->search(),
                'status' => $request->status() ?? '',
            ],
            'hasFilters' => $request->hasFilters(),
            'statusOptions' => $this->complaintService->statusOptions(),
            'homeRoute' => $this->homeRouteName(),
        ]);
    }

    public function create(): View
    {
        return view('complaints.create', [
            'departmentOptions' => $this->complaintService->departmentOptions(),
            'homeRoute' => $this->homeRouteName(),
        ]);
    }

    public function store(StoreComplaintRequest $request): RedirectResponse
    {
        $complaint = $this->complaintService->create($request->payload(), $request->file('attachment'));

        return redirect()->route('modules.complaints.show', $complaint->id)->with('success', __('complaints.create_success'));
    }

    public function show(int $complaint): View
    {
        $record = $this->complaintService->findForDetail($complaint);

        abort_if($record === null, 404);

        return view('complaints.show', [
            'complaint' => $record,
            'timeline' => $this->complaintService->timeline($complaint),
            'statusLabel' => $this->complaintService->statusLabel($record),
            'statusColor' => $this->complaintService->statusColor($record),
            'homeRoute' => $this->homeRouteName(),
            'statusOptions' => $this->complaintService->statusOptions(),
        ]);
    }

    public function reply(StoreComplaintReplyRequest $request, int $complaint): RedirectResponse
    {
        $record = $this->complaintService->findForDetail($complaint);
        abort_if($record === null, 404);
        $this->complaintService->addReply($record, (int) $request->input('status_id'), $request->validated(), $request->file('attachment'));

        return back()->with('success', __('complaints.reply_success'));
    }

    public function pdf(int $complaint): Response
    {
        $record = $this->complaintService->findForDetail($complaint);
        abort_if($record === null, 404);
        $pdf = app(ArabicPdfService::class)->loadView('complaints.pdf', ['complaint' => $record, 'timeline' => $this->complaintService->timeline($complaint)])
            ->setPaper('a4');
        return $pdf->download('complaint-'.$record->displayNumber().'.pdf');
    }

    public function attachment(int $complaint, int $reply): Response
    {
        $record = $this->complaintService->findForDetail($complaint);
        abort_if($record === null, 404);
        $file = $this->complaintService->replyForDownload($complaint, $reply);
        abort_if($file === null || ! $file->file_name || ! Storage::disk('local')->exists($file->file_name), 404);
        return response()->download(Storage::disk('local')->path($file->file_name));
    }

    public function timeline(int $complaint): View
    {
        $record = $this->complaintService->findForDetail($complaint);

        abort_if($record === null, 404);

        return view('complaints.timeline', [
            'complaint' => $record,
            'timeline' => $this->complaintService->timeline($complaint),
            'statusLabel' => $this->complaintService->statusLabel($record),
            'statusColor' => $this->complaintService->statusColor($record),
            'homeRoute' => $this->homeRouteName(),
        ]);
    }
}
