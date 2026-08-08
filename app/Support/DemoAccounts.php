<?php

namespace App\Support;

/**
 * The single registry of dedicated client/demo login accounts.
 *
 * These accounts intentionally share a small number of real inboxes so OTP
 * delivery can be verified end to end, so they are the only rows in the system
 * that are allowed to hold a duplicate `hr_email_address`. Everything that
 * needs to know about that exception (the seeder, the user admin form's
 * uniqueness rule) reads the list from here rather than repeating usernames.
 */
final class DemoAccounts
{
    /** @var list<string> */
    private const USERNAMES = [
        'mohamed_super',
        'mohamed_permission',
        'mohamed_brancha',
        'mohamed_branchb',
        'bader_super',
        'bader_permission',
        'bader_brancha',
        'bader_branchb',
    ];

    /**
     * @return list<string>
     */
    public static function usernames(): array
    {
        return self::USERNAMES;
    }

    public static function isDemoUsername(string $username): bool
    {
        return in_array($username, self::USERNAMES, true);
    }
}
