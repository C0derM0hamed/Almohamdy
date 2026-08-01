<?php

namespace App\Http\Controllers\Module\SystemAdministration;

use App\Http\Controllers\Controller;
use App\Services\SystemAdministration\ReferenceAdminService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ReferenceAdminController extends Controller
{
    public function __construct(private readonly ReferenceAdminService $service) {}
    public function index(Request $request, string $type): View { $spec = $this->service->spec($type); return view('system-administration.reference.index', ['type' => $type, 'spec' => $spec, 'rows' => $this->service->list($type, trim((string) $request->input('search', ''))), 'search' => trim((string) $request->input('search', '')), 'homeRoute' => 'modules.system-admin.dashboard']); }
    public function create(string $type): View { $spec = $this->service->spec($type); return view('system-administration.reference.form', ['type' => $type, 'spec' => $spec, 'row' => null, 'options' => $this->service->options($type), 'homeRoute' => 'modules.system-admin.dashboard']); }
    public function store(Request $request, string $type): RedirectResponse { $spec = $this->service->spec($type); $data = $request->validate($this->rules($spec)); $this->service->create($type, $data); return redirect()->route('modules.system-admin.reference.index', $type)->with('success', __('system_administration.reference.saved')); }
    public function edit(string $type, int $reference): View { $spec = $this->service->spec($type); return view('system-administration.reference.form', ['type' => $type, 'spec' => $spec, 'row' => $this->service->find($type, $reference), 'options' => $this->service->options($type), 'homeRoute' => 'modules.system-admin.dashboard']); }
    public function update(Request $request, string $type, int $reference): RedirectResponse { $spec = $this->service->spec($type); $this->service->update($type, $reference, $request->validate($this->rules($spec))); return redirect()->route('modules.system-admin.reference.index', $type)->with('success', __('system_administration.reference.saved')); }
    public function publish(string $type, int $reference): RedirectResponse { $this->service->toggle($type, $reference); return back()->with('success', __('system_administration.reference.status_changed')); }
    public function destroy(string $type, int $reference): RedirectResponse { $this->service->delete($type, $reference); return back()->with('success', __('system_administration.reference.deleted')); }
    private function rules(array $spec): array { $rules = []; foreach ($spec['fields'] as $field) $rules[$field] = str_ends_with($field, '_id') || $field === 'branch_id' || $field === 'platform_id' ? ['nullable', 'integer'] : ['nullable', 'string', 'max:255']; return $rules; }
}
