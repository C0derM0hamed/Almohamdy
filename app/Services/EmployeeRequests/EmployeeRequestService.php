<?php

namespace App\Services\EmployeeRequests;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use App\Services\Auth\PermissionService;

class EmployeeRequestService
{
    public const BRANCH_ID = 1;

    private const CONFIG = [
        'permission' => ['request' => 'permission_request', 'branch' => 'permission_branch_reply', 'hr' => 'permission_hr_reply', 'title' => 'طلبات الاستئذان'],
        'duty' => ['request' => 'change_duty_time_request', 'branch' => 'change_duty_time_branch_reply', 'hr' => 'change_duty_time_hr_reply', 'title' => 'طلبات تغيير الدوام'],
        'resignation' => ['request' => 'resignation_request', 'branch' => 'resignation_branch_reply', 'hr' => 'resignation_hr_reply', 'title' => 'طلبات الاستقالة'],
    ];

    public function authorizeBranch(): void { abort_unless((int) session('hr_branch_id', 0) === self::BRANCH_ID && (int) session('companies_groups_id', 0) > 0, 403); }
    public function config(string $type): array { abort_unless(isset(self::CONFIG[$type]), 404); return self::CONFIG[$type] + ['type' => $type]; }

    public function list(string $type): LengthAwarePaginator
    {
        $this->authorizeBranch(); $cfg = $this->config($type);
        $paginator = DB::table($cfg['request'].' as r')->leftJoin('ra_users as u', 'u.hr_id', '=', 'r.emp_id')->where('r.branch_id', self::BRANCH_ID)->where('r.companies_groups_id', $this->companyId())->select('r.*', 'u.hr_username', 'u.hr_first_name', 'u.hr_last_name')->orderByDesc('r.id')->paginate(15)->withQueryString();
        $paginator->getCollection()->transform(function (object $row) use ($cfg): object { $row->branch_reply = DB::table($cfg['branch'].' as reply')->leftJoin('order_status as status', 'status.id', '=', 'reply.status_id')->where('reply.vac_id', $row->id)->select('reply.*', 'status.name_ar as status_name_ar')->latest('reply.id')->first(); $row->hr_reply = DB::table($cfg['hr'].' as reply')->leftJoin('order_status as status', 'status.id', '=', 'reply.status_id')->where('reply.vac_id', $row->id)->select('reply.*', 'status.name_ar as status_name_ar')->latest('reply.id')->first(); return $row; });
        return $paginator;
    }

    public function find(string $type, int $id): ?object
    {
        $this->authorizeBranch(); $cfg = $this->config($type);
        $row = DB::table($cfg['request'].' as r')->leftJoin('ra_users as u', 'u.hr_id', '=', 'r.emp_id')->where('r.id', $id)->where('r.branch_id', self::BRANCH_ID)->where('r.companies_groups_id', $this->companyId())->select('r.*', 'u.hr_username', 'u.hr_first_name', 'u.hr_last_name')->first();
        if ($row) { $row->branch_reply = DB::table($cfg['branch'])->where('vac_id', $id)->latest('id')->first(); $row->hr_reply = DB::table($cfg['hr'])->where('vac_id', $id)->latest('id')->first(); }
        return $row;
    }

    public function create(string $type, array $data): int
    {
        $this->authorizeBranch(); $cfg = $this->config($type); $base = ['branch_id' => self::BRANCH_ID, 'companies_groups_id' => $this->companyId(), 'emp_id' => (int) session('hr_user_id', 0), 'date' => (string) time(), 'reason' => trim($data['reason'])];
        if ($type === 'resignation') $base['started_date'] = (string) strtotime($data['started_date']);
        else $base += ['duty_time_from' => trim($data['duty_time_from']), 'duty_time_to' => trim($data['duty_time_to']), 'permission_time_from' => trim($data['permission_time_from']), 'permission_time_to' => trim($data['permission_time_to']), 'started_date' => (string) strtotime($data['started_date'])];
        return (int) DB::table($cfg['request'])->insertGetId($base);
    }

    public function statuses(): \Illuminate\Support\Collection { $this->authorizeBranch(); return DB::table('order_status')->where('publish', 1)->orderBy('id')->get(); }

    public function reply(string $type, int $id, string $stage, int $status, string $comment): void
    {
        $record = $this->find($type, $id); abort_if($record === null, 404); abort_unless(in_array($stage, ['branch', 'hr'], true), 404);
        if ($stage === 'hr') app(PermissionService::class)->authorize('employee_requests_admin');
        else abort_unless(in_array((int) session('hr_user_level', 0), [2, 3, 4], true), 403);
        $cfg = $this->config($type); $table = $stage === 'branch' ? $cfg['branch'] : $cfg['hr'];
        DB::table($table)->insert(['vac_id' => $id, 'status_id' => $status, 'date' => (string) time(), 'comment' => trim($comment), 'data_entry_id' => (int) session('hr_user_id', 0)]);
    }

    public function pdf(string $type, int $id): array { $record = $this->find($type, $id); abort_if($record === null, 404); return ['record' => $record, 'config' => $this->config($type)]; }
    private function companyId(): int { return (int) session('companies_groups_id', 0); }
}
