<?php

namespace Tests\Feature;

use App\Models\BranchDepartment;
use App\Models\Department;
use Tests\TestCase;

class ProjectTwoStructureUiTest extends TestCase
{
    public function test_license_and_government_accounts_are_top_level_navigation_modules(): void
    {
        $navigation = collect(config('hm.navigation.sidebar'));
        $license = $navigation->firstWhere('label_key', 'licenses');
        $governmentAccounts = $navigation->firstWhere('label_key', 'gov_accounts');
        $corporate = $navigation->firstWhere('label_key', 'corporate_communication');

        $this->assertSame('group', $license['type'] ?? null);
        $this->assertSame('group', $governmentAccounts['type'] ?? null);
        $this->assertNotNull($corporate);
        $corporateChildren = collect($corporate['children'] ?? [])->pluck('label_key');
        $this->assertNotContains('licenses', $corporateChildren);
        $this->assertNotContains('gov_accounts', $corporateChildren);
    }

    public function test_department_unit_hierarchy_uses_the_real_parent_department(): void
    {
        app()->setLocale('en');
        $parent = (new Department)->forceFill(['name_en' => 'Emergency', 'name_ar' => 'الطوارئ']);
        $unit = (new BranchDepartment)->forceFill(['name_en' => 'Internal Medicine', 'name_ar' => 'الباطنية']);
        $unit->setRelation('parentDepartment', $parent);

        $this->assertSame('Emergency — Internal Medicine', $unit->hierarchyLabel());

        app()->setLocale('ar');
        $this->assertSame('الطوارئ — الباطنية', $unit->hierarchyLabel());
    }

    public function test_department_unit_hierarchy_suppresses_duplicate_and_supports_legacy_rows(): void
    {
        app()->setLocale('en');
        $same = (new Department)->forceFill(['name_en' => 'Laboratory', 'name_ar' => 'المختبر']);
        $unit = (new BranchDepartment)->forceFill(['name_en' => 'Laboratory', 'name_ar' => 'المختبر']);
        $unit->setRelation('parentDepartment', $same);
        $this->assertSame('Laboratory', $unit->hierarchyLabel());

        $legacy = (new BranchDepartment)->forceFill(['name_en' => 'Medical Reports', 'name_ar' => 'التقارير الطبية']);
        $legacy->setRelation('parentDepartment', null);
        $this->assertSame('Medical Reports', $legacy->hierarchyLabel());
    }
}
