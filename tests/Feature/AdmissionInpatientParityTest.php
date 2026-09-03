<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class AdmissionInpatientParityTest extends TestCase
{
    public function test_canonical_admission_module_exposes_every_migrated_workflow(): void
    {
        $routes = [
            'calculator.preview', 'calculator.preview.store', 'calculator.index', 'calculator.create',
            'calculator.store', 'calculator.edit', 'calculator.update', 'calculator.pdf', 'calculator.sms',
            'calculator.show', 'calculator.destroy',
            'reference.index', 'reference.create', 'reference.import', 'reference.store', 'reference.edit',
            'reference.update', 'reference.toggle', 'reference.destroy',
            'consents.index', 'consents.create', 'consents.store', 'consents.reminder', 'consents.edit',
            'consents.update', 'consents.duty-decision', 'consents.timeline', 'consents.toggle',
            'consents.pdf', 'consents.show', 'consents.destroy',
            'consent-templates.index', 'consent-templates.create', 'consent-templates.store',
            'consent-templates.edit', 'consent-templates.update', 'consent-templates.toggle',
            'consent-templates.destroy',
            'doctors.index', 'doctors.create', 'doctors.store', 'doctors.edit', 'doctors.update',
            'doctors.toggle', 'doctors.destroy',
            'approvals.index', 'approvals.create', 'approvals.store', 'approvals.edit', 'approvals.update',
            'approvals.send', 'approvals.show', 'approvals.destroy',
            'contacts.index', 'contacts.create', 'contacts.store', 'contacts.edit', 'contacts.update',
            'contacts.toggle', 'contacts.destroy',
            'packages.index', 'packages.create', 'packages.store', 'packages.edit', 'packages.update',
            'packages.toggle', 'packages.pdf', 'packages.destroy', 'package-catalog',
            'report9.index', 'report9.create', 'report9.store', 'report9.edit', 'report9.update',
            'report9.pdf', 'report9.file', 'report9.show', 'report9.destroy', 'report9.lookup',
            'employee-report9.index', 'employee-report9.create', 'employee-report9.store',
            'employee-report9.edit', 'employee-report9.update', 'employee-report9.pdf',
            'employee-report9.file', 'employee-report9.show', 'employee-report9.destroy',
        ];

        foreach ($routes as $route) {
            $this->assertTrue(Route::has('modules.admission-inpatient.'.$route), $route.' is not registered');
        }
    }

    public function test_old_admission_financial_and_clinic_entry_points_are_registered(): void
    {
        foreach ([
            'legacy.admission-calculator',
            'legacy.manual-admission-calculator',
            'legacy.admission-calculator-procedures',
            'legacy.admission-calculator-observation',
            'legacy.manual-admission-calculator-procedures',
            'legacy.hospital-admission-consent',
            'legacy.hospital-admission-consent-contract-approval',
            'legacy.hospital-admission-consent-pdf',
            'legacy.inpatient-doctors',
            'legacy.clinicians-view',
            'legacy.branch-clinicians-view',
            'legacy.medical-agreement-compat',
            'legacy.branch-medical-agreement-compat',
            'legacy.financial-compat',
            'legacy.branch-financial-compat',
            'legacy.admin-financial-compat',
        ] as $route) {
            $this->assertTrue(Route::has($route), $route.' is not registered');
        }
    }

    public function test_scoped_workflows_keep_the_new_auth_boundary(): void
    {
        foreach ([
            'modules.admission-inpatient.calculator.index',
            'modules.admission-inpatient.consents.index',
            'modules.admission-inpatient.doctors.index',
            'modules.admission-inpatient.approvals.index',
            'modules.admission-inpatient.report9.index',
        ] as $routeName) {
            $this->assertContains('auth.session', Route::getRoutes()->getByName($routeName)->middleware(), $routeName);
        }

        $this->assertContains(
            'auth.session',
            Route::getRoutes()->getByName('modules.admission-inpatient.reference.index')->middleware(),
        );
        $this->assertContains(
            'admin',
            Route::getRoutes()->getByName('modules.admission-inpatient.packages.index')->middleware(),
        );
    }
}
