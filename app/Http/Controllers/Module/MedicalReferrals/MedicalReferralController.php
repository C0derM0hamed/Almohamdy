<?php

namespace App\Http\Controllers\Module\MedicalReferrals;

use App\Http\Controllers\Controller;
use App\Services\MedicalReferrals\MedicalReferralService;
use App\Services\Pdf\ArabicPdfService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;

class MedicalReferralController extends Controller
{
    public function __construct(private readonly MedicalReferralService $service) {}

    public function index(Request $request, string $type): View
    {
        $filters = [
            'from' => trim((string) $request->input('from', '')),
            'to' => trim((string) $request->input('to', '')),
            'identity' => trim((string) $request->input('identity', '')),
            'room_type' => $request->integer('room_type'),
            'user_id' => $request->integer('user_id'),
            'apology' => $request->integer('apology'),
        ];

        return view('medical-referrals.index', $this->service->options() + [
            'definition' => $this->service->definition($type),
            'records' => $this->service->list($type, $filters),
            'filters' => $filters,
            'type' => $type,
        ]);
    }

    public function create(string $type): View
    {
        return view('medical-referrals.create', $this->service->options() + ['definition' => $this->service->definition($type), 'type' => $type]);
    }

    public function store(Request $request, string $type): RedirectResponse
    {
        $rules = match ($type) {
            'bed-reservation' => ['patient_name' => ['required', 'string', 'max:200'], 'age' => ['required', 'string', 'max:4'], 'idno' => ['required', 'digits:10'], 'gender' => ['required', 'integer', 'in:1,2'], 'room_type' => ['required', 'integer'], 'doctor' => ['required', 'string', 'max:150'], 'booking_period' => ['required', 'integer'], 'letter_side' => ['required', 'string', 'max:200'], 'lang' => ['required', 'in:ar,en']],
            'accept-referral' => ['patient_name' => ['required', 'string', 'max:200'], 'nationality' => ['required', 'integer'], 'idno' => ['required', 'digits:10'], 'contact_number' => ['required', 'string', 'max:14'], 'ehala_number' => ['required', 'string', 'max:14'], 'doctor' => ['required', 'string', 'max:150'], 'booking_period' => ['required', 'integer'], 'room_type' => ['required', 'integer']],
            'referral-apology' => ['patient_name' => ['required', 'string', 'max:200'], 'nationality' => ['required', 'integer'], 'idno' => ['required', 'digits:10'], 'ehala_number' => ['required', 'string', 'max:14'], 'apology' => ['required', 'integer']],
            'crisis-management' => ['contact_number' => ['required', 'string', 'max:14'], 'booking_period' => ['required', 'integer'], 'room_type' => ['required', 'integer'], 'apology' => ['required', 'integer']],
            'red-crescent' => ['booking_period' => ['required', 'integer'], 'room_type' => ['required', 'integer'], 'apology' => ['required', 'integer']],
            'pulse-status' => ['name' => ['required', 'string', 'max:200'], 'Report_number' => ['required', 'string', 'max:100'], 'no' => ['required', 'digits:10'], 'date_dlivry' => ['required', 'date'], 'Notification_date' => ['required', 'date'], 'doctor' => ['required', 'integer']],
            default => abort(404),
        };
        $id = $this->service->create($type, $request->validate($rules));

        return redirect()->route('modules.medical-referrals.index', $type)->with('success', 'تمت العملية بنجاح (رقم '.$id.')');
    }

    public function destroy(string $type, int $record): RedirectResponse
    {
        $this->service->delete($type, $record);

        return redirect()->route('modules.medical-referrals.index', $type)->with('success', 'تم الحذف بنجاح');
    }

    public function pdf(string $type, int $record): mixed
    {
        $item = $this->service->find($type, $record);
        abort_if($item === null, 404);

        return app(ArabicPdfService::class)->loadView('medical-referrals.pdf', ['item' => $item, 'definition' => $this->service->definition($type), 'type' => $type])
            ->setPaper('a4')
            ->stream($type.'-'.$record.'.pdf');
    }

    public function email(Request $request, string $type, int $record): RedirectResponse
    {
        $item = $this->service->find($type, $record);
        abort_if($item === null, 404);
        $data = $type === 'pulse-status'
            ? ['mail_to' => 'srca10803@srca.org.sa', 'mail_cc' => null]
            : $request->validate(['mail_to' => ['required', 'email'], 'mail_cc' => ['nullable', 'email']]);
        $definition = $this->service->definition($type);
        $pdf = app(ArabicPdfService::class)->loadView('medical-referrals.pdf', compact('item', 'definition', 'type'))->setPaper('a4')->output();
        Mail::raw($definition['title'], function ($message) use ($data, $definition, $pdf, $type, $record): void {
            $message->to($data['mail_to'])->subject($definition['title'])->attachData($pdf, $type.'-'.$record.'.pdf', ['mime' => 'application/pdf']);
            if (! empty($data['mail_cc'])) {
                $message->cc($data['mail_cc']);
            }
        });

        return back()->with('success', 'تم إرسال النموذج بنجاح');
    }
}
