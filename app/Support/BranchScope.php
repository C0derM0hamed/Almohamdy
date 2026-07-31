<?php

namespace App\Support;

use Illuminate\Database\Eloquent\Builder;

class BranchScope
{
    public static function apply(Builder $query, string $column = 'branch_id'): Builder
    {
        $branchId = (int) session('hr_branch_id', 0);
        $userLevel = (int) session('hr_user_level', 0);

        if ($userLevel !== 3 && $branchId > 0) {
            $query->where($column, $branchId);
        }

        return $query;
    }
}
