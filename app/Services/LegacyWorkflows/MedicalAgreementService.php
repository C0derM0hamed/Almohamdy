<?php

namespace App\Services\LegacyWorkflows;

use App\Services\Auth\LegacyScopeService;
use App\Services\Pdf\ArabicPdfService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

class MedicalAgreementService
{
    public function __construct(
        private readonly LegacyScopeService $legacyScopes,
        private readonly YakeenClient $yakeen,
        private readonly SadqClient $sadq,
        private readonly ArabicPdfService $pdf,
    ) {}

    public const STANDARD = 'standard';

    public const SADQ = 'sadq';

    public const SADQ_MANUAL = 'sadq-manual';

    /** @return list<string> */
    public function variants(): array
    {
        return [self::STANDARD, self::SADQ, self::SADQ_MANUAL];
    }

    public function authorize(string $variant): void
    {
        abort_unless(in_array($variant, $this->variants(), true), 404);
        abort_unless((int) session('companies_groups_id', 0) > 0, 403);

        if ($variant !== self::STANDARD) {
            abort_unless(in_array((int) session('hr_branch_id', 0), [1, 2, 5, 8], true), 403);
            $permission = $variant === self::SADQ
                ? 'Medical Services Provision Agreement Yaqeen'
                : 'Medical Services Provision Agreement non Yaqeen';

            abort_unless($this->hasLegacyPrivilege($permission) || $this->legacyScopes->allows(LegacyScopeService::SADQ), 403);
        }
    }

    /** @param array<string, string> $filters */
    public function list(string $variant, array $filters): LengthAwarePaginator
    {
        $this->authorize($variant);
        $table = $this->table($variant);
        $query = DB::table($table.' as agreement')
            ->leftJoin('ra_users as creator', 'creator.hr_id', '=', 'agreement.user_id')
            ->where('agreement.branch_id', $this->branchId())
            ->where('agreement.companies_groups_id', $this->companyId())
            ->select('agreement.*', 'creator.hr_first_name as creator_name')
            ->orderByDesc('agreement.id');

        if ($filters['from'] !== '') {
            $query->where('agreement.created_at', '>=', $filters['from'].' 00:00:00');
        }
        if ($filters['to'] !== '') {
            $query->where('agreement.created_at', '<=', $filters['to'].' 23:59:59');
        }
        if ($filters['language'] !== '') {
            $query->where('agreement.language', (int) $filters['language']);
        }
        if ($filters['creator'] !== '') {
            $query->where('agreement.user_id', (int) $filters['creator']);
        }
        if ($filters['id_number'] !== '') {
            $query->where(function ($nested) use ($filters): void {
                $nested->where('agreement.patient_idno', 'like', '%'.$filters['id_number'].'%')
                    ->orWhere('agreement.contractor_idno', 'like', '%'.$filters['id_number'].'%');
            });
        }
        if ($filters['status'] !== '') {
            if ($variant === self::STANDARD) {
                $filters['status'] === 'pending'
                    ? $query->whereNull('agreement.emdha_output')
                    : $query->whereNotNull('agreement.emdha_output');
            } else {
                $query->whereExists(function ($transaction) use ($filters): void {
                    $transaction->selectRaw('1')
                        ->from('medical_services_agreement_sadq_transactions as tx')
                        ->whereColumn('tx.reference_number', 'agreement.reference_number')
                        ->where('tx.signStatus', $filters['status']);
                });
            }
        }

        return $query->paginate(15)->withQueryString();
    }

    /** @return array<string, mixed> */
    public function options(string $variant): array
    {
        $this->authorize($variant);

        return [
            'countries' => DB::table('country_yakeen')->orderBy('DESCRIPTION')->get(),
            'idTypes' => DB::table('idtype')->orderBy('id')->get(),
            'relatives' => DB::table('relatives')->orderBy('id')->get(),
            'creators' => DB::table('ra_users')
                ->where('branch_id', $this->branchId())
                ->where('companies_groups_id', $this->companyId())
                ->orderBy('hr_first_name')->get(),
        ];
    }

    /** @param array<string, mixed> $data */
    public function create(string $variant, array $data): int
    {
        $this->authorize($variant);
        $table = $this->table($variant);
        $now = now()->format('Y-m-d H:i:s');
        $contractorIsPatient = (int) $data['contractor_type'] === 2;
        $reference = Str::uuid()->toString();

        if ($variant !== self::STANDARD) {
            $duplicateExists = DB::table($table)
                ->where('patient_idno', trim((string) $data['patient_idno']))
                ->where('contractor_mobile', trim((string) $data['contractor_mobile']))
                ->where('created_at', '>=', now()->subHour()->format('Y-m-d H:i:s'))
                ->exists();
            abort_if($duplicateExists, 422, 'لا يمكن إنشاء طلب جديد بنفس رقم الهوية ورقم الجوال إلا بعد مرور ساعة.');
        }

        $data = $this->applyYakeenResults($variant, $data);
        $record = [
            'branch_id' => $this->branchId(),
            'companies_groups_id' => $this->companyId(),
            'user_id' => (int) session('hr_user_id', 0),
            'patient_name_ar' => trim((string) ($data['patient_name_ar'] ?? '')) ?: null,
            'patient_name_en' => trim((string) ($data['patient_name_en'] ?? '')) ?: null,
            'patient_idno' => trim((string) $data['patient_idno']),
            'patient_file_number' => trim((string) $data['patient_file_number']),
            'patient_nationality' => (int) $data['patient_nationality'],
            'contractor_name_ar' => $contractorIsPatient ? trim((string) ($data['patient_name_ar'] ?? '')) : (trim((string) ($data['contractor_name_ar'] ?? '')) ?: null),
            'contractor_name_en' => $contractorIsPatient ? trim((string) ($data['patient_name_en'] ?? '')) : (trim((string) ($data['contractor_name_en'] ?? '')) ?: null),
            'contractor_idno' => $contractorIsPatient ? trim((string) $data['patient_idno']) : trim((string) $data['contractor_idno']),
            'contractor_mobile' => trim((string) $data['contractor_mobile']),
            'contractor_nationality' => $contractorIsPatient ? (int) $data['patient_nationality'] : (int) $data['contractor_nationality'],
            'created_at' => $now,
            'relative' => $contractorIsPatient ? '0' : (string) ($data['relative'] ?? '0'),
            'language' => (int) $data['language'],
            'contractor_type' => (int) $data['contractor_type'],
            'date_type' => (int) ($data['date_type'] ?? 0),
            'birth_day' => (int) ($data['birth_day'] ?? 0),
            'birth_month' => (int) ($data['birth_month'] ?? 0),
            'birth_year' => (int) ($data['birth_year'] ?? 0),
            'pateintIDType' => (int) ($data['patient_id_type'] ?? 0),
            'contractorIDType' => $contractorIsPatient ? (int) ($data['patient_id_type'] ?? 0) : (int) ($data['contractor_id_type'] ?? 0),
            'sexCode' => (int) ($data['sex_code'] ?? 0),
            'email' => trim((string) ($data['email'] ?? '')) ?: null,
        ];

        if ($variant === self::STANDARD) {
            $record += ['sms_tocken' => Str::random(32), 'status' => 0, 'type' => 1, 'payment_type' => 0, 'deserved_amount' => '0'];
            return (int) DB::table($table)->insertGetId($record);
        }

        $record += ['reference_number' => $reference, 'status' => 0, 'type' => 1, 'payment_type' => 0, 'deserved_amount' => '0'];

        // Feature tests intentionally do not make external calls. Production always
        // goes through the same Sadq flow as the legacy page.
        if (app()->environment('testing')) {
            return (int) DB::table($table)->insertGetId($record);
        }

        return $this->createSadqEnvelope($record, $data);
    }

    public function find(string $variant, int $id): ?object
    {
        $this->authorize($variant);
        $record = DB::table($this->table($variant).' as agreement')
            ->leftJoin('ra_users as creator', 'creator.hr_id', '=', 'agreement.user_id')
            ->where('agreement.id', $id)
            ->where('agreement.branch_id', $this->branchId())
            ->where('agreement.companies_groups_id', $this->companyId())
            ->select('agreement.*', 'creator.hr_first_name as creator_name', 'creator.hr_last_name as creator_last_name')
            ->first();

        if ($record === null) {
            return null;
        }

        $record->attachments = DB::table('payment_guarantee_attachments')
            ->where('payment_guarantee_id', $id)->orderBy('id')->get();
        $record->transactions = $variant === self::STANDARD
            ? collect()
            : DB::table('medical_services_agreement_sadq_transactions')
                ->where('reference_number', $record->reference_number)->orderBy('id')->get();

        return $record;
    }

    /**
     * Return a presentation-neutral timeline for the agreement popup.
     * Keeping this mapping here prevents legacy database/API values from
     * leaking into the UI and lets all agreement variants use one component.
     *
     * @return array<string, mixed>|null
     */
    public function timeline(string $variant, int $id): ?array
    {
        $agreement = $this->find($variant, $id);
        if ($agreement === null) {
            return null;
        }

        $patientName = trim((string) ($agreement->patient_name_ar ?: $agreement->patient_name_en));
        $creatorName = trim((string) ($agreement->creator_name ?? '').' '.(string) ($agreement->creator_last_name ?? ''));
        $events = [[
            'title' => 'إنشاء الاتفاقية',
            'description' => 'تم إنشاء الاتفاقية وإدخال بيانات المريض والمتعهد.',
            'date' => (string) ($agreement->created_at ?? ''),
            'status' => 'completed',
            'status_label' => 'مكتمل',
            'icon' => 'bi-file-earmark-text',
            'meta' => array_values(array_filter([
                $creatorName !== '' ? ['label' => 'مدخل البيانات', 'value' => $creatorName] : null,
                trim((string) ($agreement->patient_file_number ?? '')) !== '' ? ['label' => 'رقم الملف', 'value' => (string) $agreement->patient_file_number] : null,
            ])),
        ]];

        if ($variant === self::STANDARD) {
            $isAuthenticated = trim((string) ($agreement->emdha_output ?? '')) !== '';
            $events[] = [
                'title' => $isAuthenticated ? 'اعتماد الاتفاقية' : 'بانتظار الاعتماد',
                'description' => $isAuthenticated
                    ? 'تم اعتماد الاتفاقية إلكترونيًا.'
                    : 'لم يتم اعتماد الاتفاقية إلكترونيًا حتى الآن.',
                'date' => $isAuthenticated ? (string) ($agreement->updated_at ?? $agreement->created_at ?? '') : '',
                'status' => $isAuthenticated ? 'completed' : 'pending',
                'status_label' => $isAuthenticated ? 'مكتمل' : 'قيد الانتظار',
                'icon' => $isAuthenticated ? 'bi-patch-check' : 'bi-hourglass-split',
                'meta' => [],
            ];
        } else {
            foreach ($agreement->transactions as $transaction) {
                $state = $this->timelineTransactionState($transaction->signStatus ?? null, $transaction->error_message ?? null, $transaction->RejectReason ?? null);
                $events[] = [
                    'title' => $state['title'],
                    'description' => $state['description'],
                    'date' => (string) ($transaction->created_at ?? $agreement->created_at ?? ''),
                    'status' => $state['status'],
                    'status_label' => $state['status_label'],
                    'icon' => $state['icon'],
                    'meta' => array_values(array_filter([
                        trim((string) ($transaction->destination_mobile ?? '')) !== '' ? ['label' => 'جوال الموقّع', 'value' => (string) $transaction->destination_mobile] : null,
                        trim((string) ($transaction->document_id ?? '')) !== '' ? ['label' => 'رقم المستند', 'value' => (string) $transaction->document_id] : null,
                        trim((string) ($transaction->RejectReason ?? $transaction->error_message ?? '')) !== '' ? ['label' => 'ملاحظة', 'value' => (string) ($transaction->RejectReason ?? $transaction->error_message)] : null,
                    ])),
                ];
            }
        }

        $current = end($events) ?: [];

        return [
            'agreement' => [
                'id' => $id,
                'patient_name' => $patientName !== '' ? $patientName : '—',
                'patient_id' => (string) ($agreement->patient_idno ?? '—'),
                'file_number' => (string) ($agreement->patient_file_number ?? '—'),
                'reference' => (string) ($agreement->reference_number ?? '—'),
                'created_at' => (string) ($agreement->created_at ?? ''),
            ],
            'status' => [
                'key' => (string) ($current['status'] ?? 'pending'),
                'label' => (string) ($current['status_label'] ?? 'قيد الانتظار'),
            ],
            'events' => $events,
        ];
    }

    /** @return array{title:string,description:string,status:string,status_label:string,icon:string} */
    private function timelineTransactionState(?string $signStatus, ?string $errorMessage, ?string $rejectReason): array
    {
        return match ($signStatus) {
            'Completed' => [
                'title' => 'اكتمال التوقيع الإلكتروني',
                'description' => 'تم توقيع الاتفاقية واعتمادها إلكترونيًا.',
                'status' => 'completed',
                'status_label' => 'مكتمل',
                'icon' => 'bi-patch-check',
            ],
            'Rejected' => [
                'title' => 'رفض التوقيع الإلكتروني',
                'description' => $rejectReason ?: 'تم رفض طلب التوقيع الإلكتروني.',
                'status' => 'rejected',
                'status_label' => 'مرفوض',
                'icon' => 'bi-x-circle',
            ],
            'In-progress' => [
                'title' => 'إرسال طلب التوقيع',
                'description' => 'تم إرسال دعوة التوقيع الإلكتروني وبانتظار الإجراء.',
                'status' => 'current',
                'status_label' => 'قيد التوقيع',
                'icon' => 'bi-send-check',
            ],
            default => [
                'title' => 'تعذر إرسال طلب التوقيع',
                'description' => $errorMessage ?: 'تعذر إنشاء دعوة التوقيع الإلكتروني.',
                'status' => 'rejected',
                'status_label' => 'تعذر الإرسال',
                'icon' => 'bi-exclamation-triangle',
            ],
        };
    }

    public function attach(string $variant, int $id, UploadedFile $file): void
    {
        abort_if($this->find($variant, $id) === null, 404);
        $path = $file->store('medical-agreements', 'public');
        DB::table('payment_guarantee_attachments')->insert([
            'payment_guarantee_id' => $id,
            'file_name' => $path,
            'created_by' => (int) session('hr_user_id', 0),
        ]);
    }

    public function attachment(string $variant, int $id, int $attachmentId): ?object
    {
        abort_if($this->find($variant, $id) === null, 404);

        return DB::table('payment_guarantee_attachments')
            ->where('id', $attachmentId)->where('payment_guarantee_id', $id)->first();
    }

    public function deleteAttachment(string $variant, int $id, int $attachmentId): void
    {
        $attachment = $this->attachment($variant, $id, $attachmentId);
        abort_if($attachment === null, 404);
        Storage::disk('public')->delete((string) $attachment->file_name);
        DB::table('payment_guarantee_attachments')->where('id', $attachmentId)->where('payment_guarantee_id', $id)->delete();
    }

    /** @return array<string, mixed> */
    public function refreshSadqStatus(string $variant, int $id): array
    {
        abort_if($variant === self::STANDARD, 404);
        $agreement = $this->find($variant, $id);
        abort_if($agreement === null, 404);
        $transaction = DB::table('medical_services_agreement_sadq_transactions')
            ->where('reference_number', $agreement->reference_number)->latest('id')->first();
        abort_if($transaction === null, 422, 'لم تبدأ معاملة التوقيع الإلكتروني بعد.');

        if (app()->environment('testing')) {
            return ['status' => $transaction->status, 'signStatus' => $transaction->signStatus];
        }

        $this->sadq->authenticate();
        $response = $this->sadq->getEnvelopeStatusByReference((string) $agreement->reference_number);
        $status = $this->statusFromSadqResponse($response);
        $this->updateTransactionStatus((string) $agreement->reference_number, $status['status'], $status['signStatus'], $status['rejectReason']);

        return $status + ['response' => $response];
    }

    public function downloadSadqDocument(string $variant, int $id): ?string
    {
        abort_if($variant === self::STANDARD, 404);
        $agreement = $this->find($variant, $id);
        abort_if($agreement === null, 404);
        $transaction = DB::table('medical_services_agreement_sadq_transactions')
            ->where('reference_number', $agreement->reference_number)->latest('id')->first();
        abort_if($transaction === null, 422, 'لم تبدأ معاملة التوقيع الإلكتروني بعد.');

        $base64 = (string) ($transaction->pdfbase64 ?? '');
        if ($base64 === '' && ($transaction->signStatus ?? '') === 'Completed' && !app()->environment('testing')) {
            $this->sadq->authenticate();
            $response = $this->sadq->downloadDocumentBase64((string) $transaction->document_id);
            $base64 = $this->extractBase64($response);
            if ($base64 !== '') {
                DB::table('medical_services_agreement_sadq_transactions')
                    ->where('id', $transaction->id)->update(['pdfbase64' => $base64, 'signStatus' => 'Completed', 'status' => 1]);
            }
        }

        return $base64 !== '' ? base64_decode($this->normalizeBase64($base64), true) ?: null : null;
    }

    public function remindSadq(string $variant, int $id): void
    {
        abort_if($variant === self::STANDARD, 404);
        $agreement = $this->find($variant, $id);
        abort_if($agreement === null, 404);
        $transaction = DB::table('medical_services_agreement_sadq_transactions')
            ->where('reference_number', $agreement->reference_number)->latest('id')->first();
        abort_if($transaction === null, 422, 'لم تبدأ معاملة التوقيع الإلكتروني بعد.');
        abort_if(strtotime((string) $transaction->created_at) > time() - (2 * 60 * 60), 422, 'يمكن إعادة إرسال التذكير بعد مرور ساعتين من آخر دعوة.');

        if (!app()->environment('testing')) {
            $this->sadq->authenticate();
            $response = $this->sadq->sendSignReminder((string) $transaction->document_id, (string) $transaction->destination_mobile, (string) $transaction->destination_email);
            if ((string) ($response['errorCode'] ?? '0') !== '0') {
                throw new RuntimeException((string) ($response['message'] ?? 'تعذر إعادة إرسال الدعوة.'));
            }
        }
    }

    /** @param array<string, mixed> $payload @return array<string, mixed> */
    public function handleSadqCallback(array $payload): array
    {
        $reference = (string) ($payload['referencNumber'] ?? $payload['referenceNumber'] ?? $payload['ReferenceNumber'] ?? '');
        $requestId = (string) ($payload['requestId'] ?? $payload['RequestId'] ?? '');
        if ($reference === '' && $requestId === '') {
            throw new RuntimeException('مرجع الاتفاقية أو رقم الطلب مطلوب في callback.');
        }
        $transaction = DB::table('medical_services_agreement_sadq_transactions')
            ->when($reference !== '', fn ($q) => $q->where('reference_number', $reference))
            ->when($reference === '' && $requestId !== '', fn ($q) => $q->where(function ($nested) use ($requestId): void {
                $nested->where('document_id', $requestId)->orWhere('envelope_id', $requestId);
            }))->latest('id')->first();

        if ($transaction === null) {
            throw new RuntimeException('لم يتم العثور على معاملة صادق بهذا المرجع.');
        }

        $rawStatus = $payload['status'] ?? $payload['Status'] ?? null;
        $status = $this->mapSadqStatus($rawStatus);
        $signatory = $payload['signatory'] ?? $payload['Signatory'] ?? [];
        $rejectReason = null;
        $authenticationType = null;
        foreach (is_array($signatory) ? $signatory : [] as $item) {
            if (in_array(strtoupper((string) ($item['Status'] ?? $item['status'] ?? '')), ['REJECTED', '2'], true)) {
                $rejectReason = $item['RejectReason'] ?? $item['rejectReason'] ?? null;
                $authenticationType = $item['AuthenticationType'] ?? $item['authenticationType'] ?? null;
                break;
            }
        }

        $updates = [
            'status' => $status['status'],
            'signStatus' => $status['signStatus'],
            'RejectReason' => $rejectReason,
        ];
        if ($authenticationType !== null) {
            $updates['AuthenticationType'] = $authenticationType;
        }
        $files = $payload['files'] ?? $payload['Files'] ?? [];
        foreach (is_array($files) ? $files : [] as $file) {
            $fileBase64 = is_array($file) ? ($file['file'] ?? $file['File'] ?? '') : '';
            if (is_string($fileBase64) && $fileBase64 !== '') {
                $updates['pdfbase64'] = $this->normalizeBase64($fileBase64);
                break;
            }
        }
        DB::table('medical_services_agreement_sadq_transactions')->where('id', $transaction->id)->update($updates);

        return ['referenceNumber' => $transaction->reference_number, 'status' => $status['status'], 'signStatus' => $status['signStatus']];
    }

    /** @param array<string, mixed> $record @param array<string, mixed> $data */
    private function createSadqEnvelope(array $record, array $data): int
    {
        $id = 0;
        $envelopeId = null;
        DB::beginTransaction();

        try {
            $id = (int) DB::table('medical_services_agreement_sadq')->insertGetId($record);
            $agreement = (object) ($record + ['id' => $id, 'created_at' => $record['created_at']]);
            $pdf = $this->pdf->loadView('legacy-workflows.medical-agreements.pdf', [
                'variant' => self::SADQ,
                'agreement' => $agreement,
            ])->setPaper('a4')->output();
            $base64 = base64_encode($pdf);

            $this->sadq->authenticate();
            $init = $this->sadq->initiateEnvelopeBase64(
                'إتفاقية تقديم خدمات طبية-Agreement for the Provision of Medical Services.pdf',
                $base64,
                (string) $record['reference_number'],
            );
            $documentId = (string) ($init['data']['documentId'] ?? $init['data']['documentID'] ?? '');
            $envelopeId = (string) ($init['data']['envelopeId'] ?? $init['data']['envelopId'] ?? '');
            if ($documentId === '') {
                throw new RuntimeException('منصة صادق لم تُرجع رقم المستند.');
            }

            $contractorIdType = (int) ($data['contractor_type'] ?? 2) === 2
                ? (int) ($data['patient_id_type'] ?? 0)
                : (int) ($data['contractor_id_type'] ?? 0);
            $authenticationType = in_array($contractorIdType, [1, 2], true) ? 1 : 2;
            $mobile = $this->normalizeMobile((string) ($record['contractor_mobile'] ?? ''));
            $email = trim((string) ($record['email'] ?? ''));
            $invitation = $this->sadq->sendInvitation($documentId, [[
                'DestinationName' => trim((string) ($record['contractor_name_ar'] ?? '').' '.(string) ($record['contractor_name_en'] ?? '')),
                'DestinationEmail' => $email,
                'DestinationPhoneNumber' => $mobile,
                'NationalId' => (string) ($record['contractor_idno'] ?? ''),
                'SigneOrder' => 0,
                'ConsentOnly' => true,
                'Signatories' => [],
                'AvailableTo' => config('services.sadq.available_to'),
                'AuthenticationType' => $authenticationType,
                'InvitationLanguage' => (int) ($record['language'] ?? 1),
                'RedirectUrl' => '',
                'AllowUserToAddDestination' => false,
                'DailyNotify' => false,
            ]], 'Dear user, please sign the document.', 'Signing Request');

            $errorCode = $invitation['errorCode'] ?? 0;
            $success = (string) $errorCode === '0';
            DB::table('medical_services_agreement_sadq_transactions')->insert([
                'reference_number' => $record['reference_number'],
                'document_id' => $documentId,
                'envelope_id' => $envelopeId !== '' ? $envelopeId : null,
                'destination_email' => $email,
                'destination_mobile' => $mobile,
                'destination_idno' => (string) ($record['contractor_idno'] ?? ''),
                'status' => $success ? 1 : 0,
                'error_code' => $errorCode,
                'error_message' => (string) ($invitation['message'] ?? ''),
                // The legacy API uses 1 for Nafath; the legacy DB enum stores it as 7.
                'AuthenticationType' => (string) ($authenticationType === 1 ? 7 : $authenticationType),
                'InvitationLanguage' => (int) ($record['language'] ?? 1),
                'signStatus' => $success ? 'In-progress' : null,
            ]);
            DB::commit();

            return $id;
        } catch (\Throwable $e) {
            DB::rollBack();
            if ($envelopeId !== null && $envelopeId !== '') {
                try {
                    $this->sadq->cancelEnvelope($envelopeId);
                } catch (\Throwable $cancelError) {
                    Log::warning('Could not cancel Sadq envelope after failed creation', ['message' => $cancelError->getMessage()]);
                }
            }
            throw $e;
        }
    }

    /** @param array<string, mixed> $data @return array<string, mixed> */
    private function applyYakeenResults(string $variant, array $data): array
    {
        if (!in_array($variant, [self::STANDARD, self::SADQ], true) || !isset($data['yakeen_results']) || !is_array($data['yakeen_results'])) {
            return $data;
        }

        $patient = $data['yakeen_results']['patient'] ?? null;
        if (is_array($patient)) {
            $data['patient_name_ar'] = $patient['name_ar'] ?? $data['patient_name_ar'] ?? null;
            $data['patient_name_en'] = $patient['name_en'] ?? $data['patient_name_en'] ?? null;
            $data['patient_nationality'] = (int) ($patient['nationality'] ?? $data['patient_nationality'] ?? 0);
            $data['patient_id_type'] = (int) ($patient['id_type'] ?? $data['patient_id_type'] ?? 0);
            $data['date_type'] = (int) ($patient['date_type'] ?? $data['date_type'] ?? 0);
            $data['birth_day'] = (int) ($patient['birth_day'] ?? $data['birth_day'] ?? 0);
            $data['birth_month'] = (int) ($patient['birth_month'] ?? $data['birth_month'] ?? 0);
            $data['birth_year'] = (int) ($patient['birth_year'] ?? $data['birth_year'] ?? 0);
            $data['sex_code'] = (int) ($patient['sex_code'] ?? $data['sex_code'] ?? 0);
        }
        $contractor = $data['yakeen_results']['contractor'] ?? null;
        if ((int) ($data['contractor_type'] ?? 2) === 1 && is_array($contractor)) {
            $data['contractor_name_ar'] = $contractor['name_ar'] ?? $data['contractor_name_ar'] ?? null;
            $data['contractor_name_en'] = $contractor['name_en'] ?? $data['contractor_name_en'] ?? null;
            $data['contractor_nationality'] = (int) ($contractor['nationality'] ?? $data['contractor_nationality'] ?? 0);
            $data['contractor_id_type'] = (int) ($contractor['id_type'] ?? $data['contractor_id_type'] ?? 0);
        }

        unset($data['yakeen_results']);
        return $data;
    }

    private function normalizeMobile(string $mobile): string
    {
        $mobile = preg_replace('/\D+/', '', $mobile) ?: '';
        if (strlen($mobile) === 12 && str_starts_with($mobile, '966')) {
            return '+'.$mobile;
        }
        if (strlen($mobile) === 9) {
            return '+966'.$mobile;
        }
        if (strlen($mobile) === 10 && str_starts_with($mobile, '0')) {
            return '+966'.substr($mobile, 1);
        }

        throw new RuntimeException('رقم جوال المتعهد غير صالح لمنصة صادق.');
    }

    /** @param array<string, mixed> $response @return array<string, mixed> */
    private function statusFromSadqResponse(array $response): array
    {
        $raw = data_get($response, 'data.status') ?? data_get($response, 'status') ?? data_get($response, 'data.signStatus');
        return $this->mapSadqStatus($raw);
    }

    /** @return array<string, mixed> */
    private function mapSadqStatus(mixed $raw): array
    {
        $value = strtolower(trim((string) $raw));
        return match ($value) {
            '1', 'completed', 'complete', 'signed' => ['status' => 1, 'signStatus' => 'Completed', 'rejectReason' => null],
            '2', 'rejected', 'reject' => ['status' => 2, 'signStatus' => 'Rejected', 'rejectReason' => null],
            default => ['status' => 0, 'signStatus' => 'In-progress', 'rejectReason' => null],
        };
    }

    private function updateTransactionStatus(string $reference, int $status, string $signStatus, ?string $rejectReason): void
    {
        DB::table('medical_services_agreement_sadq_transactions')->where('reference_number', $reference)->update([
            'status' => $status,
            'signStatus' => $signStatus,
            'RejectReason' => $rejectReason,
        ]);
    }

    /** @param array<string, mixed> $response */
    private function extractBase64(array $response): string
    {
        foreach ([
            data_get($response, 'data.file'),
            data_get($response, 'data.File'),
            data_get($response, 'data.fileBase64'),
            data_get($response, 'file'),
            data_get($response, 'File'),
        ] as $candidate) {
            if (is_string($candidate) && $candidate !== '') {
                return $candidate;
            }
        }

        return '';
    }

    private function normalizeBase64(string $base64): string
    {
        if (str_starts_with($base64, 'data:')) {
            $position = strpos($base64, 'base64,');
            if ($position !== false) {
                $base64 = substr($base64, $position + 7);
            }
        }

        return (string) preg_replace('/\s+/', '', $base64);
    }

    public function table(string $variant): string
    {
        abort_unless(in_array($variant, $this->variants(), true), 404);

        return $variant === self::STANDARD ? 'payment_guarantee' : 'medical_services_agreement_sadq';
    }

    private function hasLegacyPrivilege(string $permission): bool
    {
        $userId = (int) session('hr_user_id', 0);
        if ($userId < 1) {
            return false;
        }

        return DB::table('user_role as ur')
            ->join('role_perm as rp', 'rp.role_id', '=', 'ur.role_id')
            ->join('permissions as p', 'p.perm_id', '=', 'rp.perm_id')
            ->where('ur.user_id', $userId)
            ->where('p.perm_desc', $permission)
            ->exists();
    }

    private function branchId(): int
    {
        return (int) session('hr_branch_id', 0);
    }

    private function companyId(): int
    {
        return (int) session('companies_groups_id', 0);
    }
}
