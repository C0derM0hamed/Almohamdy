<?php

namespace App\Http\Controllers\Module\Licenses;

use App\Http\Controllers\Controller;
use App\Http\Requests\Licenses\SaveLicenseEscalationGroupRequest;
use App\Http\Requests\Licenses\SaveLicenseReferenceRequest;
use App\Http\Requests\Licenses\StoreLicenseEscalationMemberRequest;
use App\Models\LicenseAuthority;
use App\Models\LicenseEscalationGroup;
use App\Models\LicenseRenewalStage;
use App\Models\LicenseType;
use App\Services\Licenses\LicenseAdminService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LicenseAdminController extends Controller
{
    public function __construct(private readonly LicenseAdminService $admin) {}

    public function index(): View
    {
        $overview = $this->admin->overview();

        return view('licenses.admin.index', [
            'counts' => [
                'authorities' => $overview['authorities']->total(),
                'types' => $overview['types']->total(),
                'stages' => $overview['stages']->total(),
                'escalation_groups' => $overview['escalationGroups']->total(),
            ],
        ]);
    }

    public function referenceIndex(Request $request): View
    {
        $reference = $this->reference($request);
        $items = match ($reference) {
            'authorities' => $this->admin->authorities(),
            'types' => $this->admin->types(),
            'stages' => $this->admin->stages(),
        };

        return view('licenses.admin.'.$reference.'.index', [$reference => $items, 'items' => $items]);
    }

    public function referenceCreate(Request $request): View
    {
        return view('licenses.admin.'.$this->reference($request).'.create');
    }

    public function referenceStore(SaveLicenseReferenceRequest $request): RedirectResponse
    {
        $reference = $this->reference($request);
        match ($reference) {
            'authorities' => $this->admin->createAuthority($request->payload()),
            'types' => $this->admin->createType($request->payload()),
            'stages' => $this->admin->createStage($request->payload()),
        };

        return $this->referenceBack($reference, 'settings_saved');
    }

    public function referenceEdit(Request $request, int $record): View
    {
        $reference = $this->reference($request);
        $item = $this->findReference($reference, $record);
        $singular = match ($reference) {
            'authorities' => 'authority',
            'types' => 'type',
            'stages' => 'stage',
        };

        return view('licenses.admin.'.$reference.'.edit', ['item' => $item, $singular => $item]);
    }

    public function referenceUpdate(SaveLicenseReferenceRequest $request, int $record): RedirectResponse
    {
        $reference = $this->reference($request);
        match ($reference) {
            'authorities' => $this->admin->updateAuthority($record, $request->payload()),
            'types' => $this->admin->updateType($record, $request->payload()),
            'stages' => $this->admin->updateStage($record, $request->payload()),
        };

        return $this->referenceBack($reference, 'settings_saved');
    }

    public function referencePublish(Request $request, int $record): RedirectResponse
    {
        $reference = $this->reference($request);
        match ($reference) {
            'authorities' => $this->admin->toggleAuthority($record),
            'types' => $this->admin->toggleType($record),
            'stages' => $this->admin->toggleStage($record),
        };

        return $this->referenceBack($reference, 'settings_saved');
    }

    public function escalationGroups(): View
    {
        return view('licenses.admin.escalation-groups.index', [
            'escalationGroups' => $this->admin->escalationGroups(),
        ]);
    }

    public function createEscalationGroup(): View
    {
        return view('licenses.admin.escalation-groups.create', ['users' => $this->admin->sameCompanyUserOptions()]);
    }

    public function storeEscalationGroup(SaveLicenseEscalationGroupRequest $request): RedirectResponse
    {
        $group = $this->admin->createEscalationGroup($request->payload());

        return redirect()->route('modules.licenses.admin.escalation-groups.edit', $group)
            ->with('success', __('licenses.flash.settings_saved'));
    }

    public function editEscalationGroup(int $group): View
    {
        return view('licenses.admin.escalation-groups.edit', [
            'group' => $this->findGroup($group),
            'users' => $this->admin->sameCompanyUserOptions(),
        ]);
    }

    public function updateEscalationGroup(SaveLicenseEscalationGroupRequest $request, int $group): RedirectResponse
    {
        $this->admin->updateEscalationGroup($group, $request->payload());

        return redirect()->route('modules.licenses.admin.escalation-groups.edit', $group)
            ->with('success', __('licenses.flash.settings_saved'));
    }

    public function publishEscalationGroup(int $group): RedirectResponse
    {
        $this->admin->toggleEscalationGroup($group);

        return redirect()->route('modules.licenses.admin.escalation-groups.index')
            ->with('success', __('licenses.flash.settings_saved'));
    }

    public function storeEscalationMember(StoreLicenseEscalationMemberRequest $request, int $group): RedirectResponse
    {
        $this->admin->addEscalationMember($group, $request->userId());

        return redirect()->route('modules.licenses.admin.escalation-groups.edit', $group)
            ->with('success', __('licenses.flash.member_added'));
    }

    public function destroyEscalationMember(int $group, int $member): RedirectResponse
    {
        $this->admin->removeEscalationMember($group, $member);

        return redirect()->route('modules.licenses.admin.escalation-groups.edit', $group)
            ->with('success', __('licenses.flash.member_removed'));
    }

    private function reference(Request $request): string
    {
        $reference = (string) $request->route('reference');
        abort_unless(in_array($reference, ['authorities', 'types', 'stages'], true), 404);

        return $reference;
    }

    private function findReference(string $reference, int $record): Model
    {
        $query = match ($reference) {
            'authorities' => LicenseAuthority::query()->where('companies_groups_id', (int) session('companies_groups_id', 0)),
            'types' => LicenseType::query()->where('companies_groups_id', (int) session('companies_groups_id', 0)),
            'stages' => LicenseRenewalStage::query(),
        };

        return $query->findOrFail($record);
    }

    private function findGroup(int $group): LicenseEscalationGroup
    {
        return LicenseEscalationGroup::query()
            ->where('companies_groups_id', (int) session('companies_groups_id', 0))
            ->with('members.user')->findOrFail($group);
    }

    private function referenceBack(string $reference, string $message): RedirectResponse
    {
        return redirect()->route('modules.licenses.admin.'.$reference.'.index')
            ->with('success', __('licenses.flash.'.$message));
    }
}
