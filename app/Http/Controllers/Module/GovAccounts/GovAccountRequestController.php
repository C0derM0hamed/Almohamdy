<?php

namespace App\Http\Controllers\Module\GovAccounts;

use App\Http\Controllers\Controller;
use App\Http\Requests\GovAccounts\CompleteGovAccountRequest;
use App\Http\Requests\GovAccounts\MarkAuthoritySubmissionRequest;
use App\Http\Requests\GovAccounts\RejectGovAccountRequest;
use App\Http\Requests\GovAccounts\ResubmitGovAccountRequest;
use App\Http\Requests\GovAccounts\ReviewGovAccountRequest;
use App\Http\Requests\GovAccounts\SaveGovAccountRequest;
use App\Http\Requests\GovAccounts\StoreGovAccountAttachmentRequest;
use App\Http\Requests\GovAccounts\SubmitGovAccountRequest;
use App\Repositories\GovAccounts\GovAccountRepository;
use App\Services\GovAccounts\GovAccountRequestService;
use App\Support\GovAccounts\GovAccountPermissions;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class GovAccountRequestController extends Controller
{
    public function __construct(private readonly GovAccountRepository $repository, private readonly GovAccountRequestService $service) {}

    public function index(Request $request): View
    {
        $this->repository->authorizeAny(GovAccountPermissions::VIEW, GovAccountPermissions::REQUEST, GovAccountPermissions::PROCESS);
        $filters = $request->validate([
            'type' => ['nullable', 'string', 'max:30'], 'status' => ['nullable', 'string', 'max:30'],
            'created_by' => ['nullable', 'integer'], 'employee_user_id' => ['nullable', 'integer'],
            'department_id' => ['nullable', 'integer'], 'authority_id' => ['nullable', 'integer'],
            'service_id' => ['nullable', 'integer'], 'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
        ]);

        return view('gov-accounts.requests.index', ['requests' => $this->repository->requests($filters), 'filters' => $filters] + $this->repository->options());
    }

    public function create(): View
    {
        $this->repository->authorizeAny(GovAccountPermissions::REQUEST);

        return view('gov-accounts.requests.form', ['accountRequest' => null] + $this->repository->options());
    }

    public function store(SaveGovAccountRequest $request): RedirectResponse
    {
        $record = $this->service->create($request->payload());

        return redirect()->route('modules.gov-accounts.requests.show', $record)->with('success', __('gov_accounts.flash.request_saved'));
    }

    public function show(int $request): View
    {
        $record = $this->repository->requestOrFail($request);

        return view('gov-accounts.requests.show', ['accountRequest' => $record, 'abilities' => $this->repository->requestAbilities($record)] + $this->repository->options());
    }

    public function edit(int $request): View
    {
        return view('gov-accounts.requests.form', ['accountRequest' => $this->repository->requestOrFail($request)] + $this->repository->options());
    }

    public function update(SaveGovAccountRequest $form, int $request): RedirectResponse
    {
        $record = $this->service->update($this->repository->requestOrFail($request), $form->payload());

        return $this->showRedirect($record->getKey());
    }

    public function submit(SubmitGovAccountRequest $form, int $request): RedirectResponse
    {
        $this->service->submit($this->repository->requestOrFail($request), __('gov_accounts.undertakings.manager_text'), __('gov_accounts.undertakings.employee_text'), (string) $form->ip(), $form->userAgent());

        return $this->showRedirect($request);
    }

    public function reject(RejectGovAccountRequest $form, int $request): RedirectResponse
    {
        $this->service->reject($this->repository->requestOrFail($request), $form->reason());

        return $this->showRedirect($request);
    }

    public function resubmit(ResubmitGovAccountRequest $form, int $request): RedirectResponse
    {
        $this->service->resubmit($this->repository->requestOrFail($request), $form->responseText());

        return $this->showRedirect($request);
    }

    public function approve(ReviewGovAccountRequest $form, int $request): RedirectResponse
    {
        $this->service->approve($this->repository->requestOrFail($request), $form->notes());

        return $this->showRedirect($request);
    }

    public function authority(MarkAuthoritySubmissionRequest $form, int $request): RedirectResponse
    {
        $this->service->markSubmittedToAuthority($this->repository->requestOrFail($request), $form->payload());

        return $this->showRedirect($request);
    }

    public function complete(CompleteGovAccountRequest $form, int $request): RedirectResponse
    {
        $account = $this->service->complete($this->repository->requestOrFail($request), $form->payload());

        return redirect()->route('modules.gov-accounts.accounts.show', $account)->with('success', __('gov_accounts.flash.completed'));
    }

    public function cancel(int $request): RedirectResponse
    {
        $this->service->cancel($this->repository->requestOrFail($request));

        return $this->showRedirect($request);
    }

    public function attachment(StoreGovAccountAttachmentRequest $form, int $request): RedirectResponse
    {
        $this->service->storeAttachment($this->repository->requestOrFail($request), $form->file('attachment'), (string) $form->input('context'), $form->input('description'));

        return $this->showRedirect($request);
    }

    public function download(int $request, int $attachment)
    {
        return $this->service->downloadAttachment($this->repository->requestOrFail($request), $attachment);
    }

    private function showRedirect(int $id): RedirectResponse
    {
        return redirect()->route('modules.gov-accounts.requests.show', $id)->with('success', __('gov_accounts.flash.action_done'));
    }
}
