<?php

namespace App\Http\Controllers\Module\SystemAdministration;

use App\Http\Controllers\Concerns\ResolvesDashboardView;
use App\Http\Controllers\Controller;
use App\Http\Requests\SystemAdministration\PackageIndexRequest;
use App\Http\Requests\SystemAdministration\SavePackageRequest;
use App\Services\SystemAdministration\ServicePackageAdminService;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Illuminate\View\View;

class ServicePackageController extends Controller
{
    use ResolvesDashboardView;

    public function __construct(
        private readonly ServicePackageAdminService $packageService,
    ) {}

    public function index(PackageIndexRequest $request): View
    {
        return view('system-administration.packages.index', [
            'packages' => $this->packageService->listPaginated(
                $request->search(),
                $request->sectionId(),
                $request->publish(),
            ),
            'sectionOptions' => $this->packageService->sectionOptions(),
            'filters' => [
                'search' => $request->search(),
                'section' => $request->sectionId() ?? '',
                'publish' => $request->publish() ?? '',
            ],
            'hasFilters' => $request->hasFilters(),
            'homeRoute' => $this->homeRouteName(),
        ]);
    }

    public function edit(int $package): View
    {
        $record = $this->packageService->findForEdit($package);

        abort_if($record === null, 404);

        return view('system-administration.packages.edit', [
            'package' => $record,
            'sectionOptions' => $this->packageService->sectionOptions(),
            'homeRoute' => $this->homeRouteName(),
        ]);
    }

    public function create(): View
    {
        return view('system-administration.packages.create', [
            'sectionOptions' => $this->packageService->sectionOptions(),
            'homeRoute' => $this->homeRouteName(),
        ]);
    }

    public function store(SavePackageRequest $request): RedirectResponse
    {
        $package = $this->packageService->create($request->packageAttributes(), $request->file('attachment_files', []));

        return redirect()->route('modules.system-admin.packages.edit', $package->id)
            ->with('success', __('system_administration.package_created'));
    }

    public function update(SavePackageRequest $request, int $package): RedirectResponse
    {
        $record = $this->packageService->findForEdit($package);

        abort_if($record === null, 404);

        $this->packageService->update($record, $request->packageAttributes(), $request->file('attachment_files', []));

        return redirect()
            ->route('modules.system-admin.packages.edit', $record->id)
            ->with('success', __('system_administration.package_updated'));
    }

    public function publish(int $package): RedirectResponse
    {
        $record = $this->packageService->findForEdit($package);

        abort_if($record === null, 404);

        $updated = $this->packageService->togglePublish($record);

        $message = $updated->publish === '1'
            ? __('system_administration.package_published')
            : __('system_administration.package_unpublished');

        return redirect()
            ->route('modules.system-admin.packages.index')
            ->with('success', $message);
    }

    public function destroy(int $package): RedirectResponse
    {
        $record = $this->packageService->findForEdit($package);

        abort_if($record === null, 404);

        $this->packageService->delete($record);

        return redirect()
            ->route('modules.system-admin.packages.index')
            ->with('success', __('system_administration.package_deleted'));
    }

    public function import(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'service_id' => ['required', 'integer', 'min:1'],
            'file' => ['required', 'file', 'max:20480', 'mimes:csv,txt,xlsx'],
        ]);
        $count = $this->packageService->import((int) $data['service_id'], $data['file']);

        return redirect()->route('modules.system-admin.packages.index', ['section' => $data['service_id']])
            ->with('success', 'تم استيراد '.$count.' حزمة من الملف.');
    }

    public function attachment(Request $request, int $package): RedirectResponse
    {
        $record = $this->packageService->findForEdit($package);
        abort_if($record === null, 404);
        $file = $request->validate(['attachment' => ['required', 'file', 'max:15360', 'mimes:jpg,jpeg,png,gif,pdf,doc,docx,xls,xlsx']])['attachment'];
        $this->packageService->uploadAttachments($record, [$file]);

        return back()->with('success', 'تم رفع مرفق الحزمة.');
    }

    public function downloadAttachment(int $package, int $attachment): BinaryFileResponse
    {
        $record = $this->packageService->findForEdit($package);
        abort_if($record === null, 404);
        [$path, $name] = $this->packageService->downloadAttachment($record, $attachment);

        return response()->download($path, $name);
    }

    public function destroyAttachment(int $package, int $attachment): RedirectResponse
    {
        $record = $this->packageService->findForEdit($package);
        abort_if($record === null, 404);
        $this->packageService->deleteAttachment($record, $attachment);

        return back()->with('success', 'تم حذف مرفق الحزمة.');
    }
}
