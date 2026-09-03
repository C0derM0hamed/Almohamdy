<?php

namespace App\Http\Controllers\Module\ClinicsDirectory;

use App\Http\Controllers\Controller;
use App\Services\ClinicsDirectory\ClinicsDirectoryService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ClinicsDirectoryController extends Controller
{
    public function __construct(private readonly ClinicsDirectoryService $service) {}

    public function index(Request $request): View
    {
        $filters = ['clinic_id' => $request->integer('clinic_id'), 'hospital_id' => $request->integer('hospital_id'), 'name' => trim((string) $request->input('name', '')), 'code' => trim((string) $request->input('code', '')), 'search_all' => $request->boolean('search_all')];
        return view('clinics-directory.index', $this->service->lookups() + ['doctors' => $this->service->list($filters), 'filters' => $filters, 'homeRoute' => 'branch.dashboard']);
    }

    public function toggle(int $doctor): RedirectResponse { $this->service->toggle($doctor); return back()->with('success', 'تم تحديث حالة الطبيب.'); }
    public function destroy(int $doctor): RedirectResponse { $this->service->delete($doctor); return back()->with('success', 'تم حذف الطبيب.'); }
}
