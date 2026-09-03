<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\Auth\LegacyScopeService;
use App\Services\Auth\PermissionRegistry;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class BackfillPermissions extends Command
{
    protected $signature = 'hm:permissions:backfill {--dry-run : Print the proposed grants only} {--apply : Persist missing grants}';
    protected $description = 'Materialise legacy implicit permission scopes as explicit user grants.';

    public function handle(PermissionRegistry $registry): int
    {
        if (! $this->option('dry-run') && ! $this->option('apply')) {
            $this->error('Choose --dry-run or --apply.');
            return self::FAILURE;
        }

        $created = 0;
        $users = User::query()->where('activated', '1')->get(['hr_id', 'hr_user_level', 'branch_id', 'companies_groups_id']);
        foreach ($users as $user) {
            if ((int) $user->hr_user_level === 3) {
                continue;
            }
            $grants = $this->legacyScopeGrants($user, $registry);
            foreach ($grants as $permission) {
                $exists = DB::table('user_permission')->where('userid', $user->hr_id)->where('page', $permission)->exists();
                if ($exists) {
                    continue;
                }
                $created++;
                if ($this->option('apply')) {
                    DB::table('user_permission')->insert(['userid' => $user->hr_id, 'pageid' => 0, 'page' => $permission, 'permit' => '2']);
                    if (DB::getSchemaBuilder()->hasTable('permission_change_logs')) {
                        DB::table('permission_change_logs')->insert(['actor_user_id' => null, 'subject_user_id' => $user->hr_id, 'action' => 'initial_backfill', 'before_state' => json_encode([]), 'after_state' => json_encode([$permission => 'allow']), 'added_permissions' => json_encode([$permission]), 'removed_permissions' => json_encode([]), 'request_id' => null, 'ip_address' => null, 'user_agent' => 'hm:permissions:backfill', 'created_at' => now()]);
                    }
                }
            }
        }
        $this->info(($this->option('apply') ? 'Applied' : 'Would apply')." {$created} explicit legacy-scope grants.");
        return self::SUCCESS;
    }

    /** @return list<string> */
    private function legacyScopeGrants(User $user, PermissionRegistry $registry): array
    {
        $level = (int) $user->hr_user_level;
        $branch = (int) $user->branch_id;
        $company = (int) $user->companies_groups_id;
        $permissions = [];
        if (in_array($level, [1, 2, 4], true) && in_array($company, [1, 3], true)) {
            $permissions = array_merge($permissions, ['absence_notification_service', 'work_absence_notification.view', 'work_absence_notification.process', 'work_absence_notification.activate', 'work_absence_notification.export', 'view training_confirmation', 'view training_confirmation_coordinator']);
        }
        if (in_array($level, [1, 2, 4], true)) {
            $permissions[] = 'corporate_communications_outgoing_letters';
        }
        if (in_array($level, [1, 2, 4], true) && in_array($branch, [1, 5, 7, 8], true)) {
            $permissions[] = 'technical_failure_notice';
        }
        return collect($permissions)->map(fn (string $code) => $registry->canonical($code))->unique()->values()->all();
    }
}
