<?php

namespace App\Services\EmergencyReception;

use App\Support\EmergencyReception\EmergencyReceptionAccess;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\UploadedFile;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

final class EmergencyReceptionService
{
    public const TYPES = ['corpse', 'claim', 'unidentified', 'escape', 'incident'];

    public function definition(string $type): array
    {
        abort_unless(in_array($type, self::TYPES, true), 404);

        return match ($type) {
            'corpse' => [
                'table' => 'receiving_the_corpse', 'title' => 'استلام جثة وتصريح الدفن',
                'columns' => ['date' => 'تاريخ الإصدار', 'patient_name' => 'اسم المتوفى', 'nationality' => 'الجنسية', 'idno' => 'رقم الهوية', 'doctor' => 'الطبيب', 'date_of_death' => 'تاريخ الوفاة'],
                'fields' => ['patient_name' => ['اسم المتوفى', 'text', true], 'nationality' => ['الجنسية', 'nationality', true], 'idno' => ['رقم الهوية/الإقامة', 'text', true], 'file_number' => ['رقم الملف', 'text', true], 'gender' => ['الجنس', 'gender', true], 'age' => ['العمر', 'number', true], 'date_of_birth' => ['تاريخ الميلاد', 'date', true], 'date_of_death' => ['تاريخ الوفاة', 'date', true], 'doctor' => ['الطبيب المعالج', 'text', true], 'death_reason' => ['سبب الوفاة', 'death_reason', true], 'recipient_name' => ['اسم المستلم', 'text', true], 'recipient_nationality' => ['جنسية المستلم', 'nationality', true], 'recipient_idno' => ['هوية المستلم', 'text', true], 'recipient_relation' => ['صلة القرابة', 'relative', true], 'recipient_address' => ['العنوان', 'text', true], 'recipient_contact_number' => ['رقم التواصل', 'text', true], 'language' => ['اللغة', 'language', true]],
                'search' => ['idno', 'recipient_idno'], 'status' => 'contractor_approval', 'attachments' => ['receiving_the_corpse_attachments', 'corpse_id', 'receiving_the_corpse_files'],
            ],
            'claim' => [
                'table' => 'claiming_against_others', 'title' => 'إقرار بعدم مطالبة ضد الغير',
                'columns' => ['date' => 'تاريخ الإقرار', 'victim_name' => 'اسم المعتدى عليه', 'victim_nationality' => 'الجنسية', 'victim_idno' => 'رقم الهوية', 'language' => 'اللغة'],
                'fields' => ['aggressor_name' => ['اسم المعتدي (يترك فارغاً إذا كان مجهولاً)', 'text', false], 'aggressor_nationality' => ['جنسية المعتدي', 'country', false], 'aggressor_idno' => ['هوية المعتدي', 'text', false], 'relative_relation' => ['صلة القرابة', 'relative', false], 'victim_name' => ['اسم المعتدى عليه', 'text', true], 'victim_nationality' => ['جنسية المعتدى عليه', 'country', true], 'victim_idno' => ['هوية المعتدى عليه', 'text', true], 'victim_file_number' => ['رقم الملف الطبي', 'text', true], 'victim_mobile' => ['رقم الجوال', 'text', true], 'injury_type' => ['نوع الإصابة', 'injury', true], 'language' => ['اللغة', 'language', true], 'date_type' => ['نوع التاريخ', 'date_type', true], 'birth_day' => ['يوم الميلاد', 'number', true], 'birth_month' => ['شهر الميلاد', 'number', true], 'birth_year' => ['سنة الميلاد', 'number', true]],
                'search' => ['victim_idno', 'aggressor_idno'], 'status' => 'contractor_approval', 'attachments' => ['claiming_against_others_attachments', 'form_id', 'claiming_against_others_files'],
            ],
            'unidentified' => [
                'table' => 'receive_unidentified_case', 'title' => 'خطاب استلام حالة مجهولة الهوية',
                'columns' => ['date' => 'تاريخ الإشعار', 'death_reason' => 'نوع الحالة', 'receipt_case_via' => 'استلام الحالة عن طريق', 'gender' => 'الجنس', 'room_type' => 'وحدة التنويم', 'letter_side' => 'الجهة'],
                'fields' => ['date_time' => ['تاريخ ووقت استلام الحالة', 'datetime-local', true], 'number' => ['رقم البلاغ', 'text', true], 'death_reason' => ['نوع الحالة', 'death_reason', true], 'receipt_case_via' => ['استلام الحالة عن طريق', 'receipt_via', true], 'gender' => ['الجنس', 'gender', true], 'room_type' => ['وحدة التنويم', 'room_type', true], 'letter_side' => ['الجهة التي يصدر إليها الإشعار', 'text', true]],
                'search' => ['letter_side'], 'status' => null, 'attachments' => null,
            ],
            'escape' => [
                'table' => 'escape_report_form', 'title' => 'إبلاغ هروب',
                'columns' => ['date' => 'تاريخ الإصدار', 'mother_name' => 'اسم الأم', 'mother_idno' => 'هوية الأم', 'mother_nationality' => 'جنسية الأم', 'child_gender' => 'جنس المولود', 'escape_date' => 'تاريخ الهروب', 'reporter_side' => 'الجهة المبلغة'],
                'fields' => ['father_name' => ['اسم الأب', 'text', false], 'father_nationality' => ['جنسية الأب', 'country', false], 'father_idno' => ['هوية الأب', 'text', false], 'father_mobile' => ['جوال الأب', 'text', false], 'mother_name' => ['اسم الأم', 'text', true], 'mother_nationality' => ['جنسية الأم', 'country', true], 'mother_idno' => ['هوية الأم', 'text', true], 'mother_mobile' => ['جوال الأم', 'text', true], 'entery_date' => ['تاريخ الدخول', 'date', true], 'born_date' => ['تاريخ الولادة', 'date', true], 'child_gender' => ['جنس المولود', 'gender', true], 'escape_date' => ['تاريخ الهروب', 'date', true], 'reporter_side' => ['الجهة المبلغة', 'text', true], 'type' => ['نوع البلاغ', 'report_type', true]],
                'search' => ['mother_idno'], 'status' => null, 'attachments' => ['escape_report_form_attachments', 'form_id', 'escape_report_form_files'],
            ],
            'incident' => [
                'table' => 'incident_report_form', 'title' => 'إبلاغ عن حادثة',
                'columns' => ['date' => 'تاريخ الإصدار', 'case_name' => 'اسم الحالة', 'case_nationality' => 'الجنسية', 'case_idno' => 'رقم الهوية', 'case_type' => 'الحالة', 'side_type' => 'الجهة'],
                'fields' => ['case_type' => ['نوع الحالة', 'incident_status', true], 'paramedic' => ['المسعف', 'paramedic', true], 'case_name' => ['اسم الحالة', 'text', true], 'case_nationality' => ['الجنسية', 'country', true], 'case_idno' => ['رقم الهوية', 'text', true], 'case_mobile' => ['رقم الجوال', 'text', true], 'side_type' => ['نوع الجهة', 'side_type', true], 'arrival_date' => ['تاريخ ووقت الوصول', 'datetime-local', true], 'entity_representative' => ['اسم ممثل الجهة', 'text', true], 'rank' => ['الرتبة', 'text', true], 'car_code' => ['رمز السيارة', 'text', true], 'taken_action' => ['الإجراء المتخذ', 'textarea', true]],
                'search' => ['case_idno', 'paramedic_idno'], 'status' => 'case_type', 'attachments' => ['incident_report_form_attachments', 'form_id', 'incident_report_form_files'],
            ],
        };
    }

    public function list(string $type, array $filters): LengthAwarePaginator
    {
        EmergencyReceptionAccess::authorize();
        $definition = $this->definition($type);
        $query = $this->scoped($definition['table'])->orderByDesc('id');
        if ($filters['from'] ?? null) $query->where('date', '>=', strtotime($filters['from']));
        if ($filters['to'] ?? null) $query->where('date', '<=', strtotime($filters['to'].' 23:59:59'));
        if (($filters['user_id'] ?? 0) > 0) $query->where('user_id', (int) $filters['user_id']);
        if ($definition['status'] && ($filters['status'] ?? '') !== '') $query->where($definition['status'], (int) $filters['status']);
        if ($filters['search'] ?? '') {
            $term = trim($filters['search']);
            $query->where(fn (Builder $q) => collect($definition['search'])->each(fn ($field, $i) => $i ? $q->orWhere($field, 'like', "%{$term}%") : $q->where($field, 'like', "%{$term}%")));
        }
        return $query->paginate(20)->withQueryString();
    }

    public function find(string $type, int $id): ?object
    {
        EmergencyReceptionAccess::authorize();
        return $this->scoped($this->definition($type)['table'])->where('id', $id)->first();
    }

    public function create(string $type, array $data): int
    {
        EmergencyReceptionAccess::authorize();
        $definition = $this->definition($type);
        $row = ['branch_id' => 1, 'companies_groups_id' => $this->companyId(), 'user_id' => (int) session('hr_user_id'), 'date' => time()];
        foreach ($definition['fields'] as $field => [, $kind]) {
            $value = $data[$field] ?? null;
            if (in_array($kind, ['date', 'datetime-local'], true)) $value = strtotime((string) $value);
            $row[$field] = $value;
        }
        if (in_array($type, ['corpse', 'claim'], true)) $row += ['sms_tocken' => hash('sha256', Str::random(40)), 'contractor_approval' => 0];
        if ($type === 'corpse') $row += ['contractor_idno_confirm' => 0, 'recipient_received_date' => (string) time()];
        if ($type === 'incident') $row += ['sms_tocken' => hash('sha256', Str::random(40))];
        return (int) DB::table($definition['table'])->insertGetId($row);
    }

    public function lookups(): array
    {
        EmergencyReceptionAccess::authorize();
        $table = fn (string $name) => DB::table($name)->where('publish', 1)->orderBy('name_ar')->get();
        return ['country' => DB::table('countries')->where('publish', 1)->orderBy('country_nationality_ar')->get(), 'nationality' => $table('nationality'), 'death_reason' => $table('death_reason'), 'relative' => $table('relatives'), 'injury' => $table('injury_type'), 'room_type' => $table('room_type'), 'receipt_via' => $table('receipt_case_via'), 'gender' => $table('gender'), 'incident_status' => $table('incident_report_status'), 'paramedic' => $table('paramedics'), 'doctors' => DB::table('incident_report_form_doctors')->where('companies_groups_id', $this->companyId())->where('publish', 1)->orderBy('name')->get(), 'users' => DB::table('ra_users')->where('branch_id', 1)->where('companies_groups_id', $this->companyId())->where('activated', 1)->orderBy('hr_first_name')->get()];
    }

    public function attachments(string $type, int $id): iterable
    {
        $record = $this->find($type, $id);
        abort_if($record === null, 404);
        $cfg = $this->definition($type)['attachments'];
        return $cfg && Schema::hasTable($cfg[0]) ? DB::table($cfg[0])->where($cfg[1], $id)->orderByDesc('id')->get() : [];
    }

    public function addAttachment(string $type, int $id, UploadedFile $file): void
    {
        $record = $this->find($type, $id);
        abort_if($record === null, 404);
        $cfg = $this->definition($type)['attachments'];
        abort_if($cfg === null || ! Schema::hasTable($cfg[0]), 503, 'Attachment storage migration is pending.');
        $name = $file->hashName();
        $file->storeAs("emergency-reception/{$type}", $name, 'local');
        DB::table($cfg[0])->insert([$cfg[1] => $id, 'file_name' => $name, 'created_by' => (int) session('hr_user_id'), 'created_at' => now()]);
    }

    public function addIncidentMedicalReport(int $id, array $data): void
    {
        $this->find('incident', $id) ?? abort(404);
        $doctor = DB::table('incident_report_form_doctors')->where('id', (int) $data['doctor_id'])->where('companies_groups_id', $this->companyId())->where('publish', 1)->first();
        abort_if($doctor === null, 422);
        $this->scoped('incident_report_form')->where('id', $id)->update(['medical_diagnosis' => trim($data['medical_diagnosis']), 'recommendation' => trim($data['recommendation']), 'doctor_name' => $doctor->id, 'doctor_upload_report_date' => (string) time()]);
    }

    public function downloadAttachment(string $type, int $id, int $attachment): mixed
    {
        $this->find($type, $id) ?? abort(404);
        $cfg = $this->definition($type)['attachments'];
        abort_if($cfg === null || ! Schema::hasTable($cfg[0]), 404);
        $row = DB::table($cfg[0])->where('id', $attachment)->where($cfg[1], $id)->first();
        abort_if($row === null, 404);
        $name = basename((string) $row->file_name);
        $newPath = "emergency-reception/{$type}/{$name}";
        if (Storage::disk('local')->exists($newPath)) return Storage::disk('local')->download($newPath, $name);
        $legacy = base_path("../OldProject/{$cfg[2]}/{$name}");
        abort_unless(is_file($legacy), 404);
        return response()->download($legacy, $name);
    }

    private function scoped(string $table): Builder
    {
        return DB::table($table)->where('branch_id', 1)->where('companies_groups_id', $this->companyId());
    }

    private function companyId(): int { return (int) session('companies_groups_id'); }
}
