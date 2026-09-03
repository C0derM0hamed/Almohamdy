<?php

namespace App\Http\Controllers\Module\LegacyOffice;

use App\Http\Controllers\Controller;
use App\Services\Sms\SmsGateway;
use App\Services\Pdf\ArabicPdfService;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class LegacyOfficeController extends Controller
{
    public function __construct(private readonly SmsGateway $sms) {}

    public function holidays(Request $request): View
    {
        $query = $this->scope(DB::table('holidays_inquiry'), true, null, true);
        $this->dateFilter($query, $request, 'created_at');
        if ($search = trim((string) $request->query('search'))) {
            $query->where(fn (Builder $q) => $q->where('patient_name', 'like', "%{$search}%")
                ->orWhere('idno', 'like', "%{$search}%")->orWhere('file_number', 'like', "%{$search}%"));
        }
        if ($request->filled('status')) {
            $request->string('status')->toString() === 'pending'
                ? $query->whereIn('manager_reply', [0, 999999])
                : $query->where('manager_reply', $request->integer('status'));
        }

        $records = $query->orderByDesc('id')->paginate(25)->withQueryString();
        $userIds = $records->getCollection()->flatMap(fn ($record) => [$record->user_id, $record->manager])->filter()->unique();

        return view('legacy-office.holidays', $this->common([
            'records' => $records,
            'userNames' => DB::table('ra_users')->whereIn('hr_id', $userIds)->get()->mapWithKeys(fn ($user) => [$user->hr_id => trim($user->hr_first_name.' '.$user->hr_last_name)]),
            'decisions' => DB::table('holidays_inquiry_decision')->where('publish', 1)->orderBy('id')->get(),
            'canDecide' => (int) session('hr_user_level') === 3 || (int) session('hr_branch_id') === 5,
        ]));
    }

    public function storeHoliday(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'patient_name' => ['required', 'string', 'max:200'], 'nationality' => ['required', 'string', 'max:11'],
            'idno' => ['required', 'string', 'max:12'], 'file_number' => ['required', 'string', 'max:20'],
            'days' => ['required', 'integer', 'min:1'], 'issue_date' => ['required', 'date'],
            'issuer' => ['required', 'string', 'max:255'], 'type' => ['required', 'integer', 'min:1'],
            'attachment' => ['nullable', 'file', 'mimes:jpg,jpeg,png,gif,pdf,docx', 'max:10240'],
        ]);
        $attachment = $request->file('attachment');
        unset($data['attachment']);
        $id = DB::table('holidays_inquiry')->insertGetId($data + $this->ownership() + [
            'created_at' => (string) time(), 'manager_reply' => 0,
        ]);
        if ($attachment) {
            $path = $attachment->store('legacy-office/holidays');
            DB::table('holidays_inquiry_attachments')->insert([
                'holidays_inquiry_id' => $id, 'file_name' => $path,
                'created_by' => session('hr_user_id'), 'created_at' => now(),
            ]);
        }

        return back()->with('success', 'تم حفظ استفسار الإجازة.');
    }

    public function decideHoliday(Request $request, int $holiday): RedirectResponse
    {
        $this->requireBranches([5]);
        $data = $request->validate(['manager_reply' => ['required', 'integer', 'exists:holidays_inquiry_decision,id'], 'manager_reply_reason' => ['nullable', 'string', 'max:200']]);
        $record = $this->findScoped('holidays_inquiry', $holiday, true);
        DB::table('holidays_inquiry')->where('id', $record->id)->update($data + ['manager' => session('hr_user_id'), 'date' => (string) time()]);

        return back()->with('success', 'تم تسجيل القرار.');
    }

    public function holidayTimeline(int $holiday): View
    {
        $record = $this->findScoped('holidays_inquiry', $holiday, true);

        return view('legacy-office.holiday-detail', $this->common([
            'record' => $record,
            'attachments' => DB::table('holidays_inquiry_attachments')->where('holidays_inquiry_id', $holiday)->orderBy('id')->get(),
            'pdf' => false,
        ]));
    }

    public function holidayPdf(int $holiday)
    {
        $record = $this->findScoped('holidays_inquiry', $holiday, true);

        return app(ArabicPdfService::class)->loadView('legacy-office.holiday-pdf', ['record' => $record])->stream("holiday-{$holiday}.pdf");
    }

    public function storeHolidayAttachment(Request $request, int $holiday): RedirectResponse
    {
        $this->findScoped('holidays_inquiry', $holiday, true);
        $request->validate(['attachment' => ['required', 'file', 'mimes:jpg,jpeg,png,gif,pdf,docx', 'max:10240']]);
        $path = $request->file('attachment')->store('legacy-office/holidays');
        DB::table('holidays_inquiry_attachments')->insert([
            'holidays_inquiry_id' => $holiday,
            'file_name' => $path,
            'created_by' => session('hr_user_id'),
            'created_at' => now(),
        ]);

        return back()->with('success', 'تم رفع المرفق.');
    }

    public function deleteHolidayAttachment(int $holiday, int $attachment): RedirectResponse
    {
        $record = $this->findScoped('holidays_inquiry', $holiday, true);
        abort_unless((int) $record->user_id === (int) session('hr_user_id') || (int) session('hr_user_level') === 3, 403);
        $file = DB::table('holidays_inquiry_attachments')->where('id', $attachment)->where('holidays_inquiry_id', $holiday)->first();
        abort_if(! $file, 404);
        Storage::delete($file->file_name);
        DB::table('holidays_inquiry_attachments')->where('id', $attachment)->delete();

        return back()->with('success', 'تم حذف المرفق.');
    }

    public function holidayAttachment(int $holiday, int $attachment): BinaryFileResponse
    {
        $this->findScoped('holidays_inquiry', $holiday, true);
        $file = DB::table('holidays_inquiry_attachments')->where('id', $attachment)->where('holidays_inquiry_id', $holiday)->first();
        abort_if(! $file || ! Storage::exists($file->file_name), 404);

        return response()->download(Storage::path($file->file_name), basename($file->file_name), ['X-Content-Type-Options' => 'nosniff']);
    }

    public function medicalReports(Request $request): View
    {
        $this->requireBranches([1, 5, 7]);
        $query = $this->scope(DB::table('medica_report'), false);
        $this->dateFilter($query, $request, 'created_at');
        if ($search = trim((string) $request->query('search'))) {
            $query->where(fn (Builder $q) => $q->where('patient_name', 'like', "%{$search}%")->orWhere('file_number', 'like', "%{$search}%"));
        }
        if ($request->filled('status')) {
            $request->string('status')->toString() === 'pending' ? $query->whereIn('manager_reply', [0, 999999]) : $query->where('manager_reply', $request->integer('status'));
        }
        $summaryQuery = $this->scope(DB::table('medica_report'), false);
        $summary = $summaryQuery->selectRaw('COUNT(*) total, SUM(CASE WHEN manager_reply IS NULL OR manager_reply IN (0,999999) THEN 1 ELSE 0 END) pending, SUM(manager_reply=1) approved, SUM(manager_reply=2) rejected')->first();

        return view('legacy-office.medical-reports', $this->common([
            'records' => $query->orderByDesc('created_at')->paginate(25)->withQueryString(), 'summary' => $summary,
            'decisions' => DB::table('medica_report_decision')->where('publish', 1)->orderBy('id')->get(),
        ]));
    }

    public function decideMedicalReport(Request $request, int $report): RedirectResponse
    {
        $this->requireBranches([1, 5, 7]);
        $data = $request->validate(['manager_reply' => ['required', 'integer', 'exists:medica_report_decision,id'], 'manager_reply_reason' => ['nullable', 'string', 'max:200']]);
        $record = $this->findScoped('medica_report', $report, false);
        DB::table('medica_report')->where('id', $record->id)->update($data + ['manager' => session('hr_user_id'), 'date' => (string) time()]);

        return back()->with('success', 'تم تسجيل قرار التقرير الطبي.');
    }

    public function medicalReportPdf(int $report)
    {
        $this->requireBranches([1, 5, 7]);
        $record = $this->findScoped('medica_report', $report, false);

        return app(ArabicPdfService::class)->loadView('legacy-office.medical-report-pdf', ['record' => $record])->stream("medical-report-{$report}.pdf");
    }

    public function memos(Request $request): View
    {
        return $this->memoList($request, false);
    }

    public function receivedMemos(Request $request): View
    {
        return $this->memoList($request, true);
    }

    public function storeMemo(Request $request): RedirectResponse
    {
        $data = $this->validateMemo($request);
        $recipients = $data['recipients'];
        unset($data['recipients']);
        $id = DB::table('memo')->insertGetId($data + $this->ownership() + ['date' => (string) time(), 'sms_tocken' => 'memo'.Str::random(64)]);
        $this->replaceMemoRecipients($id, $recipients);

        return back()->with('success', 'تم حفظ المذكرة.');
    }

    public function updateMemo(Request $request, int $memo): RedirectResponse
    {
        $record = $this->findScoped('memo', $memo);
        abort_unless((int) $record->user_id === (int) session('hr_user_id') || (int) session('hr_user_level') === 3, 403);
        $data = $this->validateMemo($request);
        $recipients = $data['recipients'];
        unset($data['recipients']);
        DB::table('memo')->where('id', $memo)->update($data + ['activated_by' => session('hr_user_id'), 'activated_at' => now()]);
        $this->replaceMemoRecipients($memo, $recipients);
        $record = DB::table('memo')->where('id', $memo)->first();
        foreach ($this->branchUsers()->whereIn('hr_id', $recipients) as $recipient) {
            if (trim((string) $recipient->mobile) !== '') {
                $url = route('public.legacy-office.memo', [$record->sms_tocken, $memo, $recipient->hr_id]);
                $this->sms->send((string) $recipient->mobile, 'عزيزي الموظف نشعرك بصدور مذكرة من قبل إدارة القسم: '.$url);
            }
        }

        return back()->with('success', 'تم اعتماد المذكرة.');
    }

    public function memoPdf(int $memo)
    {
        $record = $this->memoAccessibleToCurrentUser($memo);

        return app(ArabicPdfService::class)->loadView('legacy-office.memo-pdf', ['record' => $record, 'type' => DB::table('memo_types')->where('id', $record->memo_types_id)->first()])->stream("memo-{$memo}.pdf");
    }

    public function coverage(Request $request): View
    {
        $this->requireBranches([1, 2, 9]);
        $query = $this->scope(DB::table('service_coverage_memo')->select('service_coverage_memo.*')->selectSub(
            DB::table('service_coverage_memo_send_to')->selectRaw('COUNT(*)')->whereColumn('memo_id', 'service_coverage_memo.id'),
            'recipient_count'
        ));
        $this->dateFilter($query, $request, 'date');
        if ($search = trim((string) $request->query('search'))) {
            $query->where(fn (Builder $q) => $q->where('patient_name', 'like', "%{$search}%")->orWhere('patient_mobile', 'like', "%{$search}%")->orWhere('id_number', 'like', "%{$search}%"));
        }
        if ($request->filled('type')) {
            $query->where('memo_types_id', $request->integer('type'));
        }

        return view('legacy-office.coverage', $this->common(['records' => $query->orderByDesc('id')->paginate(25)->withQueryString(), 'types' => $this->types('service_coverage_memo_types')]));
    }

    public function storeCoverage(Request $request): RedirectResponse
    {
        $this->requireBranches([1, 2, 9]);
        $data = $request->validate([
            'memo_types_id' => ['required', 'integer', 'exists:service_coverage_memo_types,id'], 'memo' => ['required', 'string', 'max:20000'],
            'patient_name' => ['required', 'string', 'max:300'], 'file_number' => ['required', 'string', 'max:15'],
            'id_number' => ['required', 'string', 'max:20'], 'coverage_authority' => ['nullable', 'string', 'max:200'],
            'amount_required' => ['nullable', 'string', 'max:10'], 'hospitalization_days' => ['required', 'string', 'max:4'],
            'patient_mobile' => ['required', 'string', 'max:15'],
        ]);
        $id = DB::table('service_coverage_memo')->insertGetId($data + $this->ownership() + ['date' => (string) time(), 'sms_tocken' => 'memo'.Str::random(64)]);
        DB::table('service_coverage_memo_send_to')->insert(['memo_id' => $id]);

        return back()->with('success', 'تم حفظ إشعار التغطية.');
    }

    public function updateCoverage(Request $request, int $memo): RedirectResponse
    {
        $this->requireBranches([1, 2, 9]);
        $record = $this->findScoped('service_coverage_memo', $memo);
        abort_unless((int) $record->user_id === (int) session('hr_user_id') || (int) session('hr_user_level') === 3, 403);
        $data = $request->validate([
            'memo_types_id' => ['required', 'integer', 'exists:service_coverage_memo_types,id'], 'memo' => ['required', 'string', 'max:20000'],
            'patient_name' => ['required', 'string', 'max:300'], 'file_number' => ['required', 'string', 'max:15'], 'id_number' => ['required', 'string', 'max:20'],
            'coverage_authority' => ['nullable', 'string', 'max:200'], 'amount_required' => ['nullable', 'string', 'max:10'],
            'hospitalization_days' => ['required', 'string', 'max:4'], 'patient_mobile' => ['required', 'string', 'max:15'],
        ]);
        DB::table('service_coverage_memo')->where('id', $memo)->update($data + ['activated_by' => session('hr_user_id'), 'activated_at' => now()]);
        $record = DB::table('service_coverage_memo')->where('id', $memo)->first();
        $url = route('public.legacy-office.coverage', [$record->sms_tocken, $memo]);
        $this->sms->send((string) $record->patient_mobile, $this->coverageMessage((int) $record->memo_types_id).' '.$url);

        return back()->with('success', 'تم اعتماد إشعار التغطية.');
    }

    public function coveragePdf(int $memo)
    {
        $this->requireBranches([1, 2, 9]);
        $record = $this->findScoped('service_coverage_memo', $memo);

        return app(ArabicPdfService::class)->loadView('legacy-office.coverage-pdf', ['record' => $record, 'type' => DB::table('service_coverage_memo_types')->where('id', $record->memo_types_id)->first()])->stream("coverage-{$memo}.pdf");
    }

    public function signature(): View
    {
        return view('legacy-office.signature', $this->common(['signature' => DB::table('signatuers')->where('idno', session('hr_user_id'))->where('type', 1)->first()]));
    }

    public function storeSignature(Request $request): RedirectResponse
    {
        $data = $request->validate(['signature' => ['required', 'string', 'starts_with:data:image/png;base64,']]);
        $binary = base64_decode(substr($data['signature'], strlen('data:image/png;base64,')), true);
        abort_if($binary === false || strlen($binary) > 2 * 1024 * 1024, 422);
        abort_if(@getimagesizefromstring($binary) === false, 422);
        $filename = 'sig-'.session('hr_user_id').'-'.Str::random(8).'.png';
        $path = 'legacy-office/signatures/'.$filename;
        Storage::put($path, $binary);
        $existing = DB::table('signatuers')->where('idno', session('hr_user_id'))->where('type', 1)->first();
        if ($existing) {
            Storage::delete($this->signaturePath($existing->pic));
            DB::table('signatuers')->where('id', $existing->id)->update(['pic' => $filename]);
        } else {
            DB::table('signatuers')->insert(['idno' => session('hr_user_id'), 'pic' => $filename, 'type' => 1]);
        }

        return back()->with('success', 'تم حفظ التوقيع.');
    }

    public function signatureImage(): BinaryFileResponse
    {
        $signature = DB::table('signatuers')->where('idno', session('hr_user_id'))->where('type', 1)->first();
        $path = $signature ? $this->signaturePath($signature->pic) : '';
        abort_if(! $signature || ! Storage::exists($path), 404);

        return response()->file(Storage::path($path), ['Content-Type' => 'image/png', 'X-Content-Type-Options' => 'nosniff']);
    }

    public function publicMemo(string $token, int $memo, int $recipient): View
    {
        $record = DB::table('memo')->where('id', $memo)->where('sms_tocken', $token)->first();
        $delivery = DB::table('memo_send_to')->where('memo_id', $memo)->where('user_id', $recipient)->first();
        abort_if(! $record || ! $delivery, 404);
        DB::table('memo_send_to')->where('id', $delivery->id)->whereNull('seen_at')->update(['seen_at' => now()]);

        return view('legacy-office.public-memo', ['record' => $record, 'type' => DB::table('memo_types')->where('id', $record->memo_types_id)->first()]);
    }

    public function publicCoverage(string $token, int $memo): View
    {
        $record = DB::table('service_coverage_memo')->where('id', $memo)->where('sms_tocken', $token)->first();
        abort_if(! $record, 404);
        DB::table('service_coverage_memo_send_to')->where('memo_id', $memo)->whereNull('seen_at')->update(['seen_at' => now()]);

        return view('legacy-office.public-coverage', ['record' => $record, 'type' => DB::table('service_coverage_memo_types')->where('id', $record->memo_types_id)->first()]);
    }

    private function memoList(Request $request, bool $received): View
    {
        $query = DB::table('memo')->leftJoin('memo_types', 'memo_types.id', '=', 'memo.memo_types_id')->select('memo.*', 'memo_types.name_ar as type_name')->selectSub(
            DB::table('memo_send_to')->selectRaw('COUNT(*)')->whereColumn('memo_id', 'memo.id'),
            'recipient_count'
        );
        $this->scope($query, true, 'memo');
        if ($received) {
            $query->join('memo_send_to', 'memo_send_to.memo_id', '=', 'memo.id')->where('memo_send_to.user_id', session('hr_user_id'));
        }
        $this->dateFilter($query, $request, 'memo.date');
        if ($request->filled('type')) {
            $query->where('memo.memo_types_id', $request->integer('type'));
        }
        if ($search = trim((string) $request->query('search'))) {
            $query->where(fn (Builder $q) => $q->where('memo.title', 'like', "%{$search}%")->orWhere('memo.memo', 'like', "%{$search}%"));
        }
        if ($received) {
            DB::table('memo_send_to')->where('user_id', session('hr_user_id'))->whereNull('seen_at')->update(['seen_at' => now()]);
        }

        $records = $query->orderByDesc('memo.id')->paginate(25)->withQueryString();
        $recipientIds = DB::table('memo_send_to')
            ->whereIn('memo_id', $records->getCollection()->pluck('id')->all())
            ->get(['memo_id', 'user_id'])
            ->groupBy('memo_id')
            ->map(fn ($rows) => $rows->pluck('user_id')->map(fn ($id) => (int) $id)->all());

        return view('legacy-office.memos', $this->common([
            'records' => $records,
            'recipientIds' => $recipientIds,
            'types' => $this->types('memo_types'),
            'received' => $received,
            'users' => $this->branchUsers(),
        ]));
    }

    private function validateMemo(Request $request): array
    {
        return $request->validate([
            'memo_types_id' => ['required', 'integer', 'exists:memo_types,id'], 'title' => ['nullable', 'string', 'max:1000'],
            'memo' => ['required', 'string', 'max:30000'], 'recipients' => ['required', 'array', 'min:1'], 'recipients.*' => ['integer'],
            'minutes' => ['nullable', 'integer', 'min:0'], 'days' => ['nullable', 'integer', 'min:0'], 'month_year' => ['nullable', 'date_format:Y-m'],
            'check_in' => ['nullable', 'string', 'max:6'], 'check_out' => ['nullable', 'string', 'max:6'], 'exit_date' => ['nullable', 'date'],
            'exit_time' => ['nullable', 'date_format:H:i'], 'closed_inquiries' => ['nullable', 'integer', 'min:0'], 'pending_inquiries' => ['nullable', 'integer', 'min:0'],
            'current_begin_time' => ['nullable', 'date_format:H:i'], 'current_end_time' => ['nullable', 'date_format:H:i'], 'new_begin_time' => ['nullable', 'date_format:H:i'],
            'new_end_time' => ['nullable', 'date_format:H:i'], 'hours' => ['nullable', 'integer', 'min:0'], 'begin_date' => ['nullable', 'date'], 'end_date' => ['nullable', 'date'],
        ]);
    }

    private function replaceMemoRecipients(int $memo, array $recipients): void
    {
        $allowed = $this->branchUsers()->pluck('hr_id')->map(fn ($id) => (int) $id)->all();
        abort_if(array_diff(array_map('intval', $recipients), $allowed), 422);
        DB::transaction(function () use ($memo, $recipients): void {
            DB::table('memo_send_to')->where('memo_id', $memo)->delete();
            DB::table('memo_send_to')->insert(array_map(fn ($id) => ['memo_id' => $memo, 'user_id' => $id], array_unique($recipients)));
        });
    }

    private function memoAccessibleToCurrentUser(int $memo): object
    {
        $record = $this->findScoped('memo', $memo);
        $received = DB::table('memo_send_to')->where('memo_id', $memo)->where('user_id', session('hr_user_id'))->exists();
        abort_unless($received || (int) $record->user_id === (int) session('hr_user_id') || (int) session('hr_user_level') === 3, 403);

        return $record;
    }

    private function scope(Builder $query, bool $branch = true, ?string $table = null, bool $branchSevenCompanyWide = false): Builder
    {
        $prefix = $table ? $table.'.' : '';
        $query->where($prefix.'companies_groups_id', session('companies_groups_id'));
        if ($branch && (int) session('hr_user_level') !== 3 && ! ($branchSevenCompanyWide && (int) session('hr_branch_id') === 7)) {
            $query->where($prefix.'branch_id', session('hr_branch_id'));
        }

        return $query;
    }

    private function findScoped(string $table, int $id, bool $holidaySpecial = false): object
    {
        $query = DB::table($table)->where('id', $id)->where('companies_groups_id', session('companies_groups_id'));
        if ((int) session('hr_user_level') !== 3 && ! ($holidaySpecial && (int) session('hr_branch_id') === 7)) {
            $query->where('branch_id', session('hr_branch_id'));
        }
        $record = $query->first();
        abort_if(! $record, 404);

        return $record;
    }

    private function dateFilter(Builder $query, Request $request, string $column): void
    {
        if ($request->filled('from')) {
            $query->where($column, '>=', (string) strtotime($request->string('from')->toString()));
        }
        if ($request->filled('to')) {
            $query->where($column, '<=', (string) (strtotime($request->string('to')->toString()) + 86399));
        }
    }

    private function requireBranches(array $branches): void
    {
        abort_unless((int) session('hr_user_level') === 3 || in_array((int) session('hr_branch_id'), $branches, true), 403);
    }

    private function ownership(): array
    {
        return ['branch_id' => session('hr_branch_id'), 'companies_groups_id' => session('companies_groups_id'), 'user_id' => session('hr_user_id')];
    }

    private function types(string $table)
    {
        return DB::table($table)->where('publish', 1)->orderBy('ranking')->get();
    }

    private function branchUsers()
    {
        return DB::table('ra_users')->where('branch_id', session('hr_branch_id'))->where('companies_groups_id', session('companies_groups_id'))->where('activated', 1)->where('isSearchedField', 1)->orderBy('hr_first_name')->get();
    }

    private function common(array $data = []): array
    {
        return $data + ['homeRoute' => 'branch.dashboard'];
    }

    private function signaturePath(string $pic): string
    {
        return str_contains($pic, '/') ? $pic : 'legacy-office/signatures/'.$pic;
    }

    private function coverageMessage(int $type): string
    {
        return match ($type) {
            1 => 'نشعركم باستنفاذ المبلغ المالي لتغطية التكاليف العلاجية.',
            2 => 'نشعركم برفض تغطية التكاليف العلاجية من شركة التأمين.',
            3 => 'نشعركم برفض تغطية التكاليف العلاجية من شركة إدارة المطالبات.',
            4 => 'نشعركم بأن موافقة شركة التأمين معلقة.',
            5 => 'نشعركم بأن موافقة شركة إدارة المطالبات معلقة.',
            default => 'صدر إشعار عن حالة تغطية الخدمات الطبية.',
        };
    }
}
