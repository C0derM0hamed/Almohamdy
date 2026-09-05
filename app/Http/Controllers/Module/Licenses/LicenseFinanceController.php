<?php

namespace App\Http\Controllers\Module\Licenses;

use App\Http\Controllers\Controller;
use App\Http\Requests\Licenses\LicensePaymentActionRequest;
use App\Http\Requests\Licenses\UpdateLicensePaymentStatusRequest;
use App\Models\LicensePaymentRequestStatus;
use App\Repositories\Licenses\LicensePaymentRepository;
use App\Services\Licenses\LicensePaymentService;
use App\Services\Licenses\LicenseService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class LicenseFinanceController extends Controller
{
    public function __construct(
        private readonly LicensePaymentService $payments,
        private readonly LicensePaymentRepository $repository,
        private readonly LicenseService $licenses,
    ) {}

    public function index(Request $request): View
    {
        if (! $request->filled('department_id') && $request->filled('branch_id')) {
            $request->merge(['department_id' => $request->input('branch_id')]);
        }
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:200'],
            'status' => ['nullable', Rule::in(['received', 'in_progress', 'needs_documents', 'paid'])],
            'branch_id' => ['nullable', 'integer', Rule::exists('branches', 'id')],
            'department_id' => ['nullable', 'integer', Rule::exists('branches', 'id')],
            'from_date' => ['nullable', 'date'],
            'to_date' => ['nullable', 'date', 'after_or_equal:from_date'],
            'per_page' => ['nullable', 'integer', 'min:10', 'max:100'],
        ]);
        $counts = $this->repository->scopedQuery()
            ->join('license_payment_request_statuses as payment_status', 'payment_status.id', '=', 'license_payment_requests.status_id')
            ->select('payment_status.code', DB::raw('COUNT(*) AS total'))
            ->groupBy('payment_status.code')->pluck('total', 'payment_status.code')->all();

        return view('licenses.finance.index', $this->licenses->options() + [
            'paymentRequests' => $this->payments->listPaginated($filters, (int) ($filters['per_page'] ?? 20)),
            'paymentStatusOptions' => $this->payments->statusOptions(),
            'statusCounters' => $counts,
            'filters' => $filters,
        ]);
    }

    public function show(int $paymentRequest): View
    {
        return view('licenses.finance.show', [
            'paymentRequest' => $this->payments->findOrFail($paymentRequest),
            'paymentStatusOptions' => $this->payments->statusOptions(),
            'canViewLicense' => false,
        ]);
    }

    public function updateStatus(UpdateLicensePaymentStatusRequest $request, int $paymentRequest): RedirectResponse
    {
        $payment = $this->payments->findOrFail($paymentRequest);
        $this->payments->updateStatus($payment, $request->statusCode(), $request->comment(), $request->proof());

        return $this->backTo($paymentRequest, 'payment_status_updated');
    }

    public function requestDocuments(LicensePaymentActionRequest $request, int $paymentRequest): RedirectResponse
    {
        $request->validate(['comment' => ['required', 'string', 'max:10000']]);
        $this->payments->requestDocuments($this->payments->findOrFail($paymentRequest), (string) $request->comment());

        return $this->backTo($paymentRequest, 'documents_requested');
    }

    public function storeComment(LicensePaymentActionRequest $request, int $paymentRequest): RedirectResponse
    {
        $request->validate(['comment' => ['required', 'string', 'max:10000']]);
        $this->payments->addComment($this->payments->findOrFail($paymentRequest), (string) $request->comment());

        return $this->backTo($paymentRequest, 'comment_added');
    }

    public function storeAttachment(LicensePaymentActionRequest $request, int $paymentRequest): RedirectResponse
    {
        $request->validate(['file' => ['required', 'file', 'max:20480', 'mimes:pdf,jpg,jpeg,png,gif,webp,xls,xlsx']]);
        $this->payments->addAttachment(
            $this->payments->findOrFail($paymentRequest),
            $request->attachment(),
            $request->comment(),
        );

        return $this->backTo($paymentRequest, 'attachment_uploaded');
    }

    public function downloadAttachment(int $paymentRequest, int $attachment): StreamedResponse|BinaryFileResponse
    {
        $payment = $this->payments->findOrFail($paymentRequest);
        abort_if($payment->attachments->firstWhere('id', $attachment) === null, 404);

        return $this->licenses->downloadAttachment($payment->license, $attachment);
    }

    private function backTo(int $paymentRequest, string $message): RedirectResponse
    {
        return redirect()->route('modules.licenses.finance.show', $paymentRequest)
            ->with('success', __('licenses.flash.'.$message));
    }
}
