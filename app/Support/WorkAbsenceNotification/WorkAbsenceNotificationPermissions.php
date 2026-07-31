<?php

namespace App\Support\WorkAbsenceNotification;

final class WorkAbsenceNotificationPermissions
{
    public const VIEW = 'work_absence_notification.view';

    public const PROCESS = 'work_absence_notification.process';

    public const ACTIVATE = 'work_absence_notification.activate';

    public const EXPORT = 'work_absence_notification.export';

    /**
     * @return list<string>
     */
    public static function all(): array
    {
        return [
            self::VIEW,
            self::PROCESS,
            self::ACTIVATE,
            self::EXPORT,
        ];
    }
}
