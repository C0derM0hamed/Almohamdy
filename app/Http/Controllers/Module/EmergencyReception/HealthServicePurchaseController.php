<?php

namespace App\Http\Controllers\Module\EmergencyReception;

use App\Http\Controllers\Controller;
use App\Services\EmergencyReception\HealthServicePurchaseService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

final class HealthServicePurchaseController extends Controller
{
    public function __construct(private readonly HealthServicePurchaseService $service) {}

    public function index(Request $request): View
    {
        $filters = ['from' => $request->string('from')->toString(), 'to' => $request->string('to')->toString(), 'status' => $request->input('status', ''), 'search' => $request->string('search')->toString()];
        $records = $this->service->list($filters);
        $attachments = collect($records->items())->mapWithKeys(fn ($record) => [$record->id => $this->service->attachments($record->id)]);
        return view('modules.emergency-reception.health-index', ['records' => $records, 'attachments' => $attachments, 'filters' => $filters, 'homeRoute' => 'branch.dashboard']);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate(['mobile' => ['required', 'regex:/^05[0-9]{8}$/'], 'id_type' => ['required', 'integer', 'in:1,2']]);
        $record = $this->service->create($data['mobile'], (int) $data['id_type']);
        $token = "{$record->sms_tocken}_{$record->id_type}_{$record->id}";
        return back()->with('success', 'تم إنشاء الطلب. تعذر إرسال الرسالة خارجياً؛ رابط النموذج متاح أدناه.')->with('public_url', route('public.health-service-purchase.show', $token));
    }

    public function status(Request $request, int $record): RedirectResponse
    {
        $data = $request->validate(['verified' => ['required', 'integer', 'in:1,2']]);
        $this->service->status($record, (int) $data['verified']);
        return back()->with('success', 'تم تحديث حالة التحقق');
    }

    public function webPro(int $record): RedirectResponse
    {
        $this->service->webPro($record);
        return back()->with('success', 'تم تسجيل الرفع إلى WebPro');
    }

    public function attachment(Request $request, int $record): RedirectResponse
    {
        $request->validate(['file' => ['required', 'file', 'mimes:jpg,jpeg,png,gif,pdf,docx', 'max:10240']]);
        $this->service->addAttachment($record, $request->file('file'));
        return back()->with('success', 'تم رفع الملف بنجاح');
    }

    public function download(int $record, int $attachment): mixed { return $this->service->downloadAttachment($record, $attachment); }

    public function pdf(int $record): mixed
    {
        $item = $this->service->find($record);
        abort_if($item === null, 404);
        return Pdf::loadView('modules.emergency-reception.health-pdf', ['record' => $item])->setPaper('a4')->download("health-service-purchase-{$record}.pdf");
    }

    public function publicShow(string $token): View
    {
        $record = $this->service->publicRecord($token);
        abort_if($record === null, 404);
        abort_if(filled($record->name), 410, 'تم تعبئة البيانات من قبل');
        return view('modules.emergency-reception.health-public', ['record' => $record, 'token' => $token, 'nationalities' => $this->service->nationalities()]);
    }

    public function publicStore(Request $request, string $token): RedirectResponse
    {
        $record = $this->service->publicRecord($token);
        abort_if($record === null, 404);
        $data = $request->validate(['name' => ['required', 'string', 'max:200'], 'id_copy_number' => ['required', 'integer', 'in:1,2,3'], 'nationality' => ['required', 'integer'], 'id_number' => ['required', 'digits:10'], 'birth_date' => ['required', 'date'], 'birth_place' => ['required', 'string', 'max:100'], 'id_expiry_date' => ['required', 'date'], 'beneficiary_name' => ['required', 'string', 'max:200'], 'signature' => ['required', 'string', 'regex:#^data:image/png;base64,[A-Za-z0-9+/=\\s]+$#']]);
        $this->service->submitPublic($record, $data);
        return redirect()->route('public.health-service-purchase.result')->with('success', 'شكراً، تم تحديث البيانات بنجاح');
    }

    public function result(): View { return view('modules.emergency-reception.health-result'); }
}
