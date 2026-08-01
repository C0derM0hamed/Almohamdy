<?php

namespace App\Services\Transferal;

use App\Support\ProtectedFileDownload;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class TransferalService
{
    public const BRANCH_ID = 1;

    public function authorizeBranch(): void
    {
        abort_unless((int) session('hr_branch_id', 0) === self::BRANCH_ID && (int) session('companies_groups_id', 0) > 0, 403);
    }

    public function outgoing(array $filters): LengthAwarePaginator
    {
        $this->authorizeBranch();
        $query = DB::table('transferal as t')
            ->leftJoin('companies_groups as target', 'target.id', '=', 't.transferal_to')
            ->where('t.branch_id', self::BRANCH_ID)
            ->where('t.companies_groups_id', $this->companyId())
            ->select('t.*', 'target.name_ar as target_name_ar', 'target.name_en as target_name_en')
            ->orderByDesc('t.id');

        $paginator = $this->applyFilters($query, $filters)->paginate(15)->withQueryString();
        return $this->decorate($paginator);
    }

    public function incoming(array $filters): LengthAwarePaginator
    {
        $this->authorizeBranch();
        $query = DB::table('transferal as t')
            ->leftJoin('companies_groups as source', 'source.id', '=', 't.companies_groups_id')
            ->where('t.branch_id', self::BRANCH_ID)
            ->where('t.transferal_to', $this->companyId())
            ->select('t.*', 'source.name_ar as source_name_ar', 'source.name_en as source_name_en')
            ->orderByDesc('t.id');

        $paginator = $this->applyFilters($query, $filters)->paginate(15)->withQueryString();
        return $this->decorate($paginator);
    }

    public function lookups(): array
    {
        $this->authorizeBranch();

        return [
            'companies' => DB::table('companies_groups')->where('publish', 1)->where('id', '<>', $this->companyId())->orderBy('name_ar')->get(),
            'specializations' => DB::table('specialization')->where('publish', 1)->orderBy('name_ar')->get(),
            'reasons' => DB::table('transferal_reason')->where('publish', 1)->orderBy('name_ar')->get(),
            'rooms' => DB::table('room_type')->where('publish', 1)->orderBy('name_ar')->get(),
            'paymentTypes' => DB::table('rep1_payment_type')->where('publish', 1)->orderBy('name_ar')->get(),
        ];
    }

    public function create(array $data, ?UploadedFile $file): int
    {
        $this->authorizeBranch();
        abort_unless(DB::table('companies_groups')->where('id', $data['transferal_to'])->where('publish', 1)->exists(), 422);
        $stored = $file?->store('transferal', 'public') ?: '';

        return (int) DB::table('transferal')->insertGetId([
            'branch_id' => self::BRANCH_ID,
            'companies_groups_id' => $this->companyId(),
            'transferal_from' => $this->companyId(),
            'transferal_to' => (int) $data['transferal_to'],
            'patient_name' => trim($data['patient_name']),
            'file_number' => trim($data['file_number']),
            'idno' => trim($data['idno']),
            'specialization' => (int) $data['specialization'],
            'transferal_reason' => (int) $data['transferal_reason'],
            'room_type' => (int) $data['room_type'],
            'payment_type' => (int) $data['payment_type'],
            'referring_doctor' => trim($data['referring_doctor']),
            'file' => $stored,
            'date' => (string) time(),
            'created_by' => (int) session('hr_user_id', 0),
        ]);
    }

    public function find(int $id): ?object
    {
        $this->authorizeBranch();
        $record = DB::table('transferal as t')
            ->leftJoin('companies_groups as source', 'source.id', '=', 't.companies_groups_id')
            ->leftJoin('companies_groups as target', 'target.id', '=', 't.transferal_to')
            ->leftJoin('specialization as specialty', 'specialty.id', '=', 't.specialization')
            ->leftJoin('transferal_reason as reason', 'reason.id', '=', 't.transferal_reason')
            ->leftJoin('room_type as room', 'room.id', '=', 't.room_type')
            ->leftJoin('rep1_payment_type as payment', 'payment.id', '=', 't.payment_type')
            ->where('t.id', $id)
            ->where('t.branch_id', self::BRANCH_ID)
            ->where(fn ($q) => $q->where('t.companies_groups_id', $this->companyId())->orWhere('t.transferal_to', $this->companyId()))
            ->select('t.*', 'source.name_ar as source_name_ar', 'target.name_ar as target_name_ar', 'specialty.name_ar as specialty_name_ar', 'reason.name_ar as reason_name_ar', 'room.name_ar as room_name_ar', 'payment.name_ar as payment_name_ar')
            ->first();

        if ($record) {
            $record->confirm = DB::table('transferal_confirm')->where('transferal_id', $id)->latest('id')->first();
            $record->refusal = DB::table('transferal_refused')->where('transferal_id', $id)->latest('id')->first();
            $record->approval = DB::table('transferal_approval')->where('transferal_id', $id)->latest('id')->first();
            $record->receive = DB::table('transferal_receive_confirmation')->where('transferal_id', $id)->latest('id')->first();
        }

        return $record;
    }

    public function confirm(int $id, array $data, ?UploadedFile $file): void
    {
        $record = $this->find($id);
        abort_if($record === null || (int) $record->companies_groups_id !== $this->companyId(), 403);
        DB::table('transferal_confirm')->insert($this->replyData($id, $file, ['date' => (string) strtotime($data['date_time'])]));
    }

    public function approve(int $id, array $data, ?UploadedFile $file): void
    {
        $record = $this->find($id);
        abort_if($record === null || (int) $record->transferal_to !== $this->companyId(), 403);
        DB::table('transferal_approval')->insert($this->replyData($id, $file, ['doctor' => trim($data['doctor']), 'room_type' => (int) $data['room_type'], 'bed_room_number' => trim($data['bed_room_number'])]));
    }

    public function refuse(int $id, array $data, ?UploadedFile $file): void
    {
        $record = $this->find($id);
        abort_if($record === null || (int) $record->transferal_to !== $this->companyId(), 403);
        DB::table('transferal_refused')->insert($this->replyData($id, $file, ['doctor' => trim($data['doctor']), 'refusal_reason' => trim($data['refusal_reason'])]));
    }

    public function receive(int $id, array $data, ?UploadedFile $file): void
    {
        $record = $this->find($id);
        abort_if($record === null || (int) $record->transferal_to !== $this->companyId(), 403);
        DB::table('transferal_receive_confirmation')->insert($this->replyData($id, $file, ['doctor' => trim($data['doctor']), 'date' => (string) strtotime($data['date_time'])]));
    }

    public function attachment(int $id, string $type): \Symfony\Component\HttpFoundation\BinaryFileResponse
    {
        $record = $this->find($id);
        abort_if($record === null, 404);
        $file = match ($type) {
            'request' => $record->file,
            'confirm' => $record->confirm?->file,
            'approval' => $record->approval?->file,
            'refusal' => $record->refusal?->file,
            'receive' => $record->receive?->file,
            default => null,
        };
        return app(ProtectedFileDownload::class)->download($file, 'transferal-'.$id.'-'.$type, []);
    }

    public function timeline(int $id): Collection
    {
        $record = $this->find($id);
        abort_if($record === null, 404);
        return collect([
            (object) ['label' => 'إنشاء طلب التحويل', 'date' => $record->date, 'actor' => $record->created_by],
            $record->confirm ? (object) ['label' => 'تأكيد التحويل', 'date' => $record->confirm->created_at, 'actor' => $record->confirm->created_by] : null,
            $record->approval ? (object) ['label' => 'الموافقة على الاستقبال', 'date' => $record->approval->created_at, 'actor' => $record->approval->created_by] : null,
            $record->refusal ? (object) ['label' => 'رفض الاستقبال', 'date' => $record->refusal->created_at, 'actor' => $record->refusal->created_by] : null,
            $record->receive ? (object) ['label' => 'تأكيد استلام الحالة', 'date' => $record->receive->created_at, 'actor' => $record->receive->created_by] : null,
        ])->filter()->values();
    }

    private function applyFilters($query, array $filters)
    {
        return $query->when($filters['file_number'] !== '', fn ($q) => $q->where('t.file_number', 'like', '%'.$filters['file_number'].'%'))
            ->when($filters['from'] !== '', fn ($q) => $q->whereRaw('FROM_UNIXTIME(t.date) >= ?', [$filters['from'].' 00:00:00']))
            ->when($filters['to'] !== '', fn ($q) => $q->whereRaw('FROM_UNIXTIME(t.date) <= ?', [$filters['to'].' 23:59:59']));
    }

    private function decorate(LengthAwarePaginator $paginator): LengthAwarePaginator
    {
        $paginator->getCollection()->transform(function (object $item): object {
            $item->confirm = DB::table('transferal_confirm')->where('transferal_id', $item->id)->latest('id')->first();
            $item->approval = DB::table('transferal_approval')->where('transferal_id', $item->id)->latest('id')->first();
            $item->refusal = DB::table('transferal_refused')->where('transferal_id', $item->id)->latest('id')->first();
            $item->receive = DB::table('transferal_receive_confirmation')->where('transferal_id', $item->id)->latest('id')->first();
            return $item;
        });
        return $paginator;
    }

    private function replyData(int $id, ?UploadedFile $file, array $extra): array
    {
        return array_merge(['branch_id' => self::BRANCH_ID, 'companies_groups_id' => $this->companyId(), 'transferal_id' => $id, 'file' => $file?->store('transferal', 'public') ?: '', 'created_by' => (int) session('hr_user_id', 0)], $extra);
    }

    private function companyId(): int
    {
        return (int) session('companies_groups_id', 0);
    }
}
