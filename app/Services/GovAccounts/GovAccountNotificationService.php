<?php

namespace App\Services\GovAccounts;

use App\Mail\GovAccountNotificationMail;
use App\Models\GovAccount;
use App\Models\GovAccountNotification;
use App\Models\GovAccountRequest;
use App\Models\User;
use App\Support\GovAccounts\GovAccountPermissions;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Throwable;

class GovAccountNotificationService
{
    public function notify(GovAccountRequest $request, User|int $recipient, string $event, string $subject, string $message, ?string $url = null): void
    {
        $user = $recipient instanceof User ? $recipient : User::query()->find($recipient);
        if ($user === null) {
            return;
        }
        $email = trim((string) $user->hr_email_address);
        $base = ['request_id' => $request->getKey(), 'account_id' => $request->account_id, 'event_type' => $event, 'recipient_user_id' => $user->getKey(), 'recipient_email' => $email ?: null, 'recipient_mobile' => $user->mobile ?: null, 'meta' => [], 'created_at' => now(), 'updated_at' => now()];
        GovAccountNotification::query()->create($base + ['channel' => 'inapp', 'status' => 'logged']);
        $url ??= route('modules.gov-accounts.requests.show', $request);
        if (! config('hm.gov_accounts.notifications.mail', false) || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            GovAccountNotification::query()->create($base + ['channel' => 'mail', 'status' => 'failed', 'error' => 'Mail channel disabled or invalid email']);

            return;
        }
        try {
            Mail::to($email)->send(new GovAccountNotificationMail($user->displayName(), $subject, $message, $url));
            GovAccountNotification::query()->create($base + ['channel' => 'mail', 'status' => 'sent']);
        } catch (Throwable $exception) {
            GovAccountNotification::query()->create($base + ['channel' => 'mail', 'status' => 'failed', 'error' => $exception->getMessage()]);
            Log::error('gov_accounts.notification.mail_failed', ['request_id' => $request->getKey(), 'error' => $exception->getMessage()]);
        }
    }

    public function processors(int $companyId)
    {
        return $this->permissionHolders($companyId, GovAccountPermissions::PROCESS);
    }

    public function hrUsers(int $companyId)
    {
        return $this->permissionHolders($companyId, GovAccountPermissions::HR);
    }

    public function notifyEmployeeStatusActionRequired(GovAccount $account, User $recipient): bool
    {
        $exists = GovAccountNotification::query()->where('account_id', $account->getKey())
            ->where('event_type', 'employee_status_action_required')->where('recipient_user_id', $recipient->getKey())
            ->where('channel', 'inapp')->exists();
        if ($exists) {
            return false;
        }
        GovAccountNotification::query()->create([
            'account_id' => $account->getKey(),
            'event_type' => 'employee_status_action_required',
            'recipient_user_id' => $recipient->getKey(),
            'recipient_email' => $recipient->hr_email_address ?: null,
            'recipient_mobile' => $recipient->mobile ?: null,
            'channel' => 'inapp',
            'status' => 'action_required',
            'meta' => ['employee_user_id' => $account->employee_user_id, 'automatic_account_change' => false],
        ]);

        return true;
    }

    private function permissionHolders(int $companyId, string $permission)
    {
        if (! Schema::hasTable('user_permission')) {
            return collect();
        }

        return User::query()->where('companies_groups_id', $companyId)->activated()->get()->filter(function (User $user) use ($permission): bool {
            $direct = DB::table('user_permission')->where('userid', $user->getKey())->where('page', $permission)->get(['permit']);
            $rows = $direct->isNotEmpty() || ! Schema::hasTable('user_groups_permission')
                ? $direct
                : DB::table('user_groups_permission')->where('groupid', (int) $user->groupid)->where('page', $permission)->get(['permit']);

            return ! $rows->contains(fn ($row): bool => (string) $row->permit === '1')
                && $rows->contains(fn ($row): bool => (string) $row->permit === '2');
        })->values();
    }

    public function inbox(): LengthAwarePaginator
    {
        return GovAccountNotification::query()->where('channel', 'inapp')->where('recipient_user_id', (int) session('hr_user_id', 0))->latest('created_at')->paginate(25);
    }

    public function markRead(int $id): void
    {
        $notification = GovAccountNotification::query()->where('channel', 'inapp')->where('recipient_user_id', (int) session('hr_user_id', 0))->findOrFail($id);
        $notification->update(['read_at' => $notification->read_at ?? now()]);
    }
}
