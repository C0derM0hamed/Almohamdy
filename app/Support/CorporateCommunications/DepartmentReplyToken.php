<?php

namespace App\Support\CorporateCommunications;

use InvalidArgumentException;

/**
 * Legacy SMS/email reply links use: {token}_{adminId}_{flag}[_{channel}]
 * Token is an md5 hex string (no underscores), so explode('_') is safe.
 */
final class DepartmentReplyToken
{
    public function __construct(
        public readonly string $token,
        public readonly int $administratorId,
        public readonly int $flag = 1,
        public readonly ?int $channel = null,
        public readonly string $raw = '',
    ) {}

    public static function parse(string $raw): self
    {
        $raw = trim($raw);
        $parts = explode('_', $raw);

        if (count($parts) < 3) {
            throw new InvalidArgumentException('Invalid reply token.');
        }

        $token = (string) $parts[0];
        $adminId = (int) $parts[1];
        $flag = (int) $parts[2];
        $channel = isset($parts[3]) ? (int) $parts[3] : null;

        if ($token === '' || $adminId < 1) {
            throw new InvalidArgumentException('Invalid reply token.');
        }

        return new self($token, $adminId, $flag, $channel, $raw);
    }

    public static function build(string $token, int $administratorId, int $flag = 1, ?int $channel = null): string
    {
        $parts = [$token, (string) $administratorId, (string) $flag];

        if ($channel !== null) {
            $parts[] = (string) $channel;
        }

        return implode('_', $parts);
    }
}
