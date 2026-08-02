<?php

namespace App\Services\EmergencyReception;

use App\Support\EmergencyReception\EmergencyReceptionAccess;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

final class HealthServicePurchaseService
{
    public function list(array $filters): mixed
    {
        EmergencyReceptionAccess::authorize();
        $query = $this->scoped()->leftJoin('ra_users as u', 'u.hr_id', '=', 'health_service_purchase_form.user_id')->select('health_service_purchase_form.*', 'u.hr_first_name', 'u.hr_last_name')->orderByDesc('health_service_purchase_form.id');
        if ($filters['from']) {
            $query->where('health_service_purchase_form.date', '>=', strtotime($filters['from']));
        }
        if ($filters['to']) {
            $query->where('health_service_purchase_form.date', '<=', strtotime($filters['to'].' 23:59:59'));
        }
        if ($filters['status'] !== '') {
            $query->where('health_service_purchase_form.verified', (int) $filters['status']);
        }
        if ($filters['search']) {
            $query->where(fn ($q) => $q->where('health_service_purchase_form.id_number', 'like', "%{$filters['search']}%")->orWhere('health_service_purchase_form.member_id_number', 'like', "%{$filters['search']}%"));
        }

        return $query->paginate(20)->withQueryString();
    }

    public function create(string $mobile, int $idType): object
    {
        EmergencyReceptionAccess::authorize();
        $token = 'hsp'.hash('sha256', Str::random(48));
        $id = DB::table('health_service_purchase_form')->insertGetId(['branch_id' => 1, 'companies_groups_id' => $this->companyId(), 'user_id' => (int) session('hr_user_id'), 'mobile' => $mobile, 'id_type' => $idType, 'date' => (string) time(), 'sms_tocken' => $token]);

        return (object) ['id' => $id, 'id_type' => $idType, 'sms_tocken' => $token];
    }

    public function find(int $id): ?object
    {
        EmergencyReceptionAccess::authorize();

        return $this->scoped()->where('health_service_purchase_form.id', $id)->first();
    }

    public function status(int $id, int $verified): void
    {
        $this->find($id) ?? abort(404);
        DB::table('health_service_purchase_form')->where('id', $id)->where('branch_id', 1)->where('companies_groups_id', $this->companyId())->update(['verified' => $verified, 'verified_by' => (int) session('hr_user_id'), 'verified_at' => (string) time()]);
    }

    public function webPro(int $id): void
    {
        $record = $this->find($id);
        abort_if($record === null, 404);
        abort_unless((int) $record->verified === 1, 422);
        DB::table('health_service_purchase_form')->where('id', $id)->where('branch_id', 1)->where('companies_groups_id', $this->companyId())->update(['uploaded_to_webpro' => 1, 'uploaded_to_webpro_by' => (int) session('hr_user_id'), 'uploaded_to_webpro_at' => (string) time()]);
    }

    public function attachments(int $id): iterable
    {
        $this->find($id) ?? abort(404);

        return Schema::hasTable('health_service_purchase_form_attachments') ? DB::table('health_service_purchase_form_attachments')->where('form_id', $id)->orderByDesc('id')->get() : [];
    }

    public function addAttachment(int $id, UploadedFile $file): void
    {
        $this->find($id) ?? abort(404);
        abort_unless(Schema::hasTable('health_service_purchase_form_attachments'), 503, 'Attachment storage migration is pending.');
        $name = $file->hashName();
        $file->storeAs('health-service-purchase/attachments', $name, 'local');
        DB::table('health_service_purchase_form_attachments')->insert(['form_id' => $id, 'file_name' => $name, 'created_by' => (int) session('hr_user_id'), 'created_at' => now()]);
    }

    public function downloadAttachment(int $id, int $attachment): mixed
    {
        $this->find($id) ?? abort(404);
        abort_unless(Schema::hasTable('health_service_purchase_form_attachments'), 404);
        $row = DB::table('health_service_purchase_form_attachments')->where('id', $attachment)->where('form_id', $id)->first();
        abort_if($row === null, 404);
        $name = basename((string) $row->file_name);
        $path = 'health-service-purchase/attachments/'.$name;
        if (Storage::disk('local')->exists($path)) {
            return Storage::disk('local')->download($path, $name);
        }
        $legacy = base_path('../OldProject/health_service_purchase_form_files/'.$name);
        abort_unless(is_file($legacy), 404);

        return response()->download($legacy, $name);
    }

    public function publicRecord(string $token): ?object
    {
        $parts = explode('_', $token);
        if (count($parts) !== 3 || ! ctype_digit($parts[1]) || ! ctype_digit($parts[2])) {
            return null;
        }

        return DB::table('health_service_purchase_form')->where('sms_tocken', $parts[0])->where('id_type', (int) $parts[1])->where('id', (int) $parts[2])->first();
    }

    public function submitPublic(object $record, array $data): void
    {
        abort_if(filled($record->name), 410);
        $encoded = preg_replace('#^data:image/png;base64,#', '', $data['signature']);
        $binary = base64_decode(str_replace(' ', '+', $encoded), true);
        abort_if($binary === false || strlen($binary) > 1024 * 1024, 422);
        $signature = $record->sms_tocken.'.png';
        Storage::disk('local')->put('health-service-purchase/signatures/'.$signature, $binary);
        DB::table('health_service_purchase_form')->where('id', $record->id)->whereNull('name')->update(['name' => $data['name'], 'id_copy_number' => $data['id_copy_number'], 'nationality' => $data['nationality'], 'id_number' => $data['id_number'], 'birth_date' => (string) strtotime($data['birth_date']), 'birth_place' => $data['birth_place'], 'id_expiry_date' => (string) strtotime($data['id_expiry_date']), 'beneficiary_name' => $data['beneficiary_name'], 'filled_date' => (string) time(), 'signature_file' => $signature]);
    }

    public function nationalities(): iterable
    {
        return DB::table('nationality')->where('publish', 1)->orderBy('name_ar')->get();
    }

    private function scoped(): Builder
    {
        return DB::table('health_service_purchase_form')->where('health_service_purchase_form.branch_id', 1)->where('health_service_purchase_form.companies_groups_id', $this->companyId());
    }

    private function companyId(): int
    {
        return (int) session('companies_groups_id');
    }
}
