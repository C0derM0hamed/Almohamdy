<?php

namespace App\Support\MedicalAppointments;

class MedicalAppointmentScope
{
    public const BRANCH_IDS = [27, 29, 30, 31, 2, 5, 42];

    public const COMPANY_IDS = [1];

    public static function allowsSession(): bool
    {
        return in_array((int) session('companies_groups_id'), self::COMPANY_IDS, true)
            && in_array((int) session('hr_branch_id'), self::BRANCH_IDS, true);
    }
}
