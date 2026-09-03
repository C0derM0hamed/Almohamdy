<?php

namespace App\Http\Controllers\Module\GovAccounts;

use App\Http\Controllers\Controller;
use App\Http\Requests\GovAccounts\SaveGovAccountReferenceRequest;
use App\Services\GovAccounts\GovAccountAdminService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class GovAccountAdminController extends Controller
{
    private const REFERENCES = ['authorities', 'services', 'roles', 'department-heads'];

    public function __construct(private readonly GovAccountAdminService $admin) {}

    public function index(): View
    {
        return view('gov-accounts.admin.index', ['counts' => $this->admin->summary()]);
    }

    public function referenceIndex(Request $request): View
    {
        $reference = $this->reference($request);

        return view('gov-accounts.admin.reference-index', ['reference' => $reference, 'items' => $this->admin->records($reference)]);
    }

    public function referenceCreate(Request $request): View
    {
        return view('gov-accounts.admin.reference-form', ['reference' => $this->reference($request), 'item' => null] + $this->admin->options());
    }

    public function referenceStore(SaveGovAccountReferenceRequest $request): RedirectResponse
    {
        $reference = $this->reference($request);
        $this->admin->save($reference, $request->payload());

        return $this->back($reference);
    }

    public function referenceEdit(Request $request, int $record): View
    {
        $reference = $this->reference($request);

        return view('gov-accounts.admin.reference-form', ['reference' => $reference, 'item' => $this->admin->find($reference, $record)] + $this->admin->options());
    }

    public function referenceUpdate(SaveGovAccountReferenceRequest $request, int $record): RedirectResponse
    {
        $reference = $this->reference($request);
        $this->admin->save($reference, $request->payload(), $record);

        return $this->back($reference);
    }

    public function referencePublish(Request $request, int $record): RedirectResponse
    {
        $reference = $this->reference($request);
        $this->admin->toggle($reference, $record);

        return $this->back($reference);
    }

    private function reference(Request $request): string
    {
        $reference = (string) $request->route('reference');
        abort_unless(in_array($reference, self::REFERENCES, true), 404);

        return $reference;
    }

    private function back(string $reference): RedirectResponse
    {
        return redirect()->route('modules.gov-accounts.admin.'.$reference.'.index')->with('success', __('gov_accounts.flash.saved'));
    }
}
