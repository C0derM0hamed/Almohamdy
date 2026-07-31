<?php

namespace Database\Seeders;

use App\Models\Clinician;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class OpdSampleDoctorsSeeder extends Seeder
{
    private const HOSPITAL_ID = 1;

    private const SUWAIDI_HOSPITAL_ID = 3;

    private const SAUDI_COUNTRY_ID = 1;

    private const SPEECH_THERAPY_SPECIALITY_ID = 65;

    private const ASSIGN_LIMIT = 5;

    private const FALLBACK_DOCTORS_PER_SPECIALITY = 2;

    /**
     * @var array<int, list<array{
     *     code: string,
     *     name_en: string,
     *     name_ar: string,
     *     specialization_en: string,
     *     specialization_ar: string,
     *     age: string,
     *     speciality_id: int,
     *     country_id?: int,
     *     price?: int,
     *     clinic_number?: string,
     *     holds_en?: string,
     *     holds_ar?: string,
     *     cases_en?: string,
     *     cases_ar?: string,
     *     ranking?: int
     * }>>
     */
    private const FEATURED_DOCTORS = [
        1 => [
            [
                'code' => '99038',
                'name_en' => 'SALIH HAMAD ALJUMAH',
                'name_ar' => 'سالح حمد الجمعة',
                'specialization_en' => 'Consultant Orthopedic Surgeon',
                'specialization_ar' => 'استشاري جراحة العظام',
                'age' => '15 years and above',
                'speciality_id' => 7,
            ],
            [
                'code' => '91057',
                'name_en' => 'ABDULRAHMAN BAWYA ALSAQRI',
                'name_ar' => 'عبدالرحمن باوية الصقري',
                'specialization_en' => 'Consultant Orthopedic Surgeon',
                'specialization_ar' => 'استشاري جراحة العظام',
                'age' => '15 years and above',
                'speciality_id' => 7,
            ],
            [
                'code' => '91038',
                'name_en' => 'Abdullah Mubarak Al-Juaid',
                'name_ar' => 'عبدالله مبارك الجعيد',
                'specialization_en' => 'Consultant Orthopedic Surgeon',
                'specialization_ar' => 'استشاري جراحة العظام',
                'age' => '16 years and above',
                'speciality_id' => 7,
            ],
        ],
        6 => [
            [
                'code' => '90858',
                'name_en' => 'Ibrahim Saleh Al-Badawi',
                'name_ar' => 'إبراهيم صالح البدوي',
                'specialization_en' => 'Consultant Gastroenterologist and Hepatologist',
                'specialization_ar' => 'استشاري أمراض الجهاز الهضمي والكبد',
                'age' => '+14',
                'speciality_id' => 2,
                'country_id' => 194,
                'price' => 250,
                'clinic_number' => '621',
                'ranking' => 1,
                'holds_en' => '<p>Canadian Fellowship in Advanced Hepatology (Toronto)</p><p>Saudi Fellowship in Gastroenterology</p><p>European Board Certification in Gastroenterology</p>',
                'holds_ar' => '<p>زمالة كندية في أمراض الكبد المتقدمة (تورنتو)</p><p>زمالة سعودية في أمراض الجهاز الهضمي</p><p>شهادة البورد الأوروبي في أمراض الجهاز الهضمي</p>',
                'cases_en' => '<p>Inflammatory bowel disease</p><p>Liver disease</p><p>Advanced endoscopy</p>',
                'cases_ar' => '<p>أمراض الأمعاء الالتهابية</p><p>أمراض الكبد</p><p>المناظير المتقدمة</p>',
            ],
            [
                'code' => '19133',
                'name_en' => 'Mohammed Saleh Aba Al-Khail',
                'name_ar' => 'محمد صالح أبا الخيل',
                'specialization_en' => 'Consultant Gastroenterologist, Hepatologist and Endoscopist',
                'specialization_ar' => 'استشاري أمراض الجهاز الهضمي والكبد والمناظير',
                'age' => '15 years and older',
                'speciality_id' => 2,
                'country_id' => 194,
                'price' => 250,
                'clinic_number' => '604',
                'ranking' => 3,
                'holds_en' => '<p>Saudi Board in Internal Medicine</p><p>Saudi Fellowship in Gastroenterology</p>',
                'holds_ar' => '<p>البورد السعودي في الطب الباطني</p><p>الزمالة السعودية في أمراض الجهاز الهضمي</p>',
                'cases_en' => '<p>Hepatology</p><p>Therapeutic endoscopy</p>',
                'cases_ar' => '<p>أمراض الكبد</p><p>المناظير العلاجية</p>',
            ],
        ],
        5 => [
            [
                'code' => '198',
                'name_en' => 'Mahmoud Fawzi',
                'name_ar' => 'محمود فوزي',
                'specialization_en' => 'General Surgery Specialist',
                'specialization_ar' => 'اخصائي جراحة عامة',
                'age' => 'Less than three months',
                'speciality_id' => 25,
                'country_id' => 64,
                'price' => 250,
                'clinic_number' => '117',
                'ranking' => 1,
                'holds_en' => '<p>General Surgery Specialty Certificate</p>',
                'holds_ar' => '<p>شهادة تخصص جراحة عامة</p>',
                'cases_en' => '<p>Neonatal circumcision</p>',
                'cases_ar' => '<p>ختان المواليد</p>',
            ],
        ],
        7 => [
            [
                'code' => '17877',
                'name_en' => 'Badr Al-Din Abdul Rahman Yusuf',
                'name_ar' => 'بدر الدين عبد الرحمن يوسف',
                'specialization_en' => 'Gastroenterologist',
                'specialization_ar' => 'أخصائي أمراض الجهاز الهضمي',
                'age' => '+18',
                'speciality_id' => 2,
                'country_id' => 206,
                'price' => 250,
                'clinic_number' => '709',
                'ranking' => 2,
                'holds_en' => '<p>Arab Board in Internal Medicine</p><p>Specialty Certificate in Gastroenterology</p>',
                'holds_ar' => '<p>البورد العربي في الطب الباطني</p><p>شهادة التخصص في أمراض الجهاز الهضمي</p>',
                'cases_en' => '<p>General gastroenterology</p><p>Functional bowel disorders</p>',
                'cases_ar' => '<p>أمراض الجهاز الهضمي العامة</p><p>اضطرابات الأمعاء الوظيفية</p>',
            ],
            [
                'code' => '25',
                'name_en' => 'Tariq Al-Shaarawi',
                'name_ar' => 'طارق الشعراوي',
                'specialization_en' => 'Consultant Gastroenterologist and Endoscopist',
                'specialization_ar' => 'استشاري أمراض الجهاز الهضمي والمناظير',
                'age' => '+14',
                'speciality_id' => 2,
                'country_id' => 64,
                'price' => 250,
                'clinic_number' => '707',
                'ranking' => 4,
                'holds_en' => '<p>Egyptian Fellowship in Gastroenterology</p><p>Advanced Endoscopy Training</p>',
                'holds_ar' => '<p>زمالة مصرية في أمراض الجهاز الهضمي</p><p>تدريب متقدم في المناظير</p>',
                'cases_en' => '<p>Colonoscopy</p><p>ERCP</p><p>GI oncology screening</p>',
                'cases_ar' => '<p>منظار القولون</p><p>الأشعة التداخلية للقنوات المرارية</p><p>فحص أورام الجهاز الهضمي</p>',
            ],
        ],
    ];

    /**
     * Featured doctors assigned to non-default hospital branches.
     *
     * @var array<int, array<int, list<array{
     *     code: string,
     *     name_en: string,
     *     name_ar: string,
     *     specialization_en: string,
     *     specialization_ar: string,
     *     age: string,
     *     speciality_id: int,
     *     country_id?: int,
     *     price?: int,
     *     clinic_number?: string,
     *     holds_en?: string,
     *     holds_ar?: string,
     *     cases_en?: string,
     *     cases_ar?: string,
     *     ranking?: int
     * }>>>
     */
    private const BRANCH_FEATURED_DOCTORS = [
        3 => [
            1 => [
                [
                    'code' => '90321',
                    'name_en' => 'Faisal Abdullah Al-Otaibi',
                    'name_ar' => 'فيصل عبدالله العتيبي',
                    'specialization_en' => 'Consultant Anesthesiologist',
                    'specialization_ar' => 'استشاري تخدير',
                    'age' => 'All ages',
                    'speciality_id' => 60,
                    'country_id' => 194,
                    'price' => 100,
                    'clinic_number' => '41',
                    'ranking' => 1,
                ],
                [
                    'code' => '90322',
                    'name_en' => 'Hanan Saleh Al-Ghamdi',
                    'name_ar' => 'حنان صالح الغامدي',
                    'specialization_en' => 'Senior Anesthesiology Specialist',
                    'specialization_ar' => 'أخصائية تخدير أول',
                    'age' => 'All ages',
                    'speciality_id' => 60,
                    'country_id' => 194,
                    'price' => 100,
                    'clinic_number' => '42',
                    'ranking' => 2,
                ],
                [
                    'code' => '90323',
                    'name_en' => 'Khalid Mansour Al-Harbi',
                    'name_ar' => 'خالد منصور الحربي',
                    'specialization_en' => 'Anesthesiology Specialist',
                    'specialization_ar' => 'أخصائي تخدير',
                    'age' => 'All ages',
                    'speciality_id' => 60,
                    'country_id' => 194,
                    'price' => 100,
                    'clinic_number' => '43',
                    'ranking' => 3,
                ],
            ],
            2 => [
                [
                    'code' => '292',
                    'name_en' => 'Ahmed Qabil',
                    'name_ar' => 'احمد قابيل',
                    'specialization_en' => 'Consultant Gastroenterologist and Endoscopist',
                    'specialization_ar' => 'استشاري جهاز هضمي ومناظير',
                    'age' => '+14',
                    'speciality_id' => 2,
                    'country_id' => 64,
                    'price' => 100,
                    'clinic_number' => '79',
                    'ranking' => 1,
                    'holds_en' => '<p>Egyptian Fellowship in Gastroenterology</p><p>Advanced Endoscopy Training</p>',
                    'holds_ar' => '<p>زمالة مصرية في أمراض الجهاز الهضمي</p><p>تدريب متقدم في المناظير</p>',
                    'cases_en' => '<p>Upper and lower GI endoscopy</p><p>Functional bowel disorders</p>',
                    'cases_ar' => '<p>مناظير الجهاز الهضمي العلوي والسفلي</p><p>اضطرابات الأمعاء الوظيفية</p>',
                ],
            ],
            3 => [
                [
                    'code' => '18754',
                    'name_en' => 'Ahmed Yassin Al-Fadl',
                    'name_ar' => 'أحمد ياسين الفضل',
                    'specialization_en' => 'General Surgery Resident Doctor',
                    'specialization_ar' => 'طبيب نائب الجراحة العامة',
                    'age' => 'From one day old to 3 months',
                    'speciality_id' => 25,
                    'country_id' => 3,
                    'price' => 100,
                    'clinic_number' => '20',
                    'ranking' => 1,
                    'holds_en' => '<p>General Surgery Residency</p>',
                    'holds_ar' => '<p>نائب جراحة عامة</p>',
                    'cases_en' => '<p>Neonatal circumcision</p>',
                    'cases_ar' => '<p>ختان المواليد</p>',
                ],
                [
                    'code' => '17609',
                    'name_en' => 'Mustafa Mujahid Al-Shihabi',
                    'name_ar' => 'مصطفى مجاهد الشهابي',
                    'specialization_en' => 'General Surgery Specialist',
                    'specialization_ar' => 'أخصائي الجراحه العامه',
                    'age' => 'From one day old to 3 months',
                    'speciality_id' => 25,
                    'country_id' => 64,
                    'price' => 100,
                    'clinic_number' => '23',
                    'ranking' => 2,
                    'holds_en' => '<p>General Surgery Specialty Certificate</p>',
                    'holds_ar' => '<p>شهادة تخصص جراحة عامة</p>',
                    'cases_en' => '<p>Neonatal circumcision</p>',
                    'cases_ar' => '<p>ختان المواليد</p>',
                ],
                [
                    'code' => '19226',
                    'name_en' => 'Ahmed Abdel Rafea El Shafei',
                    'name_ar' => 'أحمد عبدالرافع الشافعي',
                    'specialization_en' => 'Consultant Gastroenterologist and Hepatologist',
                    'specialization_ar' => 'استشاري جهاز هضمي و كبد',
                    'age' => '14+',
                    'speciality_id' => 2,
                    'country_id' => 64,
                    'price' => 100,
                    'clinic_number' => '78',
                    'ranking' => 2,
                    'holds_en' => '<p>Egyptian Board in Internal Medicine</p><p>Fellowship in Gastroenterology and Hepatology</p>',
                    'holds_ar' => '<p>البورد المصري في الطب الباطني</p><p>زمالة في أمراض الجهاز الهضمي والكبد</p>',
                    'cases_en' => '<p>Liver disease</p><p>Inflammatory bowel disease</p>',
                    'cases_ar' => '<p>أمراض الكبد</p><p>أمراض الأمعاء الالتهابية</p>',
                ],
            ],
        ],
    ];

    public function run(): void
    {
        $this->ensurePrerequisites();
        $this->call(Opd2SampleDoctorsSeeder::class);

        foreach ($this->configuredOpdIds() as $opdId) {
            $this->seedOpd($opdId);
        }

        if (isset(self::FEATURED_DOCTORS[7])) {
            $now = now()->format('Y-m-d H:i:s');
            $extNumber = $this->resolveExtNumber(7);

            foreach (self::FEATURED_DOCTORS[7] as $doctor) {
                $this->seedFeaturedDoctor(7, $doctor, $now, $extNumber);
            }
        }

        $this->seedBranchFeaturedDoctors();
        $this->retireCircumcisionFallbackDoctors();

        if ($this->command !== null) {
            $this->command->call('hm:prune-mirrored-suwaidi-assignments');
        }
    }

    private function retireCircumcisionFallbackDoctors(): void
    {
        DB::table('clinicians')
            ->where('specialized_clinics_id', 25)
            ->whereIn('code', ['950251', '950252'])
            ->update([
                'publish' => '0',
                'updated_at' => now()->format('Y-m-d H:i:s'),
            ]);
    }

    private function seedBranchFeaturedDoctors(): void
    {
        $now = now()->format('Y-m-d H:i:s');

        foreach (self::BRANCH_FEATURED_DOCTORS as $hospitalId => $opdDoctors) {
            foreach ($opdDoctors as $opdId => $doctors) {
                $specialityIds = array_values(array_unique(array_map(
                    fn (array $doctor): int => (int) $doctor['speciality_id'],
                    $doctors,
                )));
                $this->ensureOpdSections($opdId, $specialityIds);

                $extNumber = $this->resolveExtNumber($opdId);

                foreach ($doctors as $doctor) {
                    $this->seedFeaturedDoctor($opdId, $doctor, $now, $extNumber, $hospitalId);
                }
            }
        }
    }

    public function seedOpd(int $opdId): void
    {
        $now = now()->format('Y-m-d H:i:s');
        $extNumber = $this->resolveExtNumber($opdId);
        $specialityIds = $this->specialityIdsForOpd($opdId);

        if ($specialityIds === []) {
            return;
        }

        $this->ensureOpdSections($opdId, $specialityIds);

        foreach (self::FEATURED_DOCTORS[$opdId] ?? [] as $doctor) {
            $this->seedFeaturedDoctor($opdId, $doctor, $now, $extNumber);
        }

        foreach ($specialityIds as $specialityId) {
            $this->assignExistingDoctorsToOpd($opdId, $specialityId, $extNumber);
        }

        foreach ($specialityIds as $specialityId) {
            if ($this->countDoctorsOnOpd($opdId, $specialityId) > 0) {
                continue;
            }

            $this->seedFallbackDoctors($opdId, $specialityId, $now, $extNumber);
        }
    }

    /**
     * @return list<int>
     */
    private function configuredOpdIds(): array
    {
        $departments = config('hm.service_locations.opd_departments', []);

        if (! is_array($departments)) {
            return [];
        }

        return array_map('intval', array_keys($departments));
    }

    /**
     * @return list<int>
     */
    private function specialityIdsForOpd(int $opdId): array
    {
        $entries = config("hm.service_locations.opd_departments.{$opdId}", []);
        $ids = [];

        if (is_array($entries)) {
            foreach ($entries as $entry) {
                if (isset($entry['speciality_id'])) {
                    $ids[] = (int) $entry['speciality_id'];
                }
            }
        }

        if ($ids !== []) {
            return array_values(array_unique($ids));
        }

        return DB::table('outpatient_clinics_sections')
            ->where('outpatient_clinics_id', $opdId)
            ->where('publish', 1)
            ->orderBy('id')
            ->pluck('specialized_clinics_id')
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();
    }

    private function ensurePrerequisites(): void
    {
        $this->ensureCountries();
        $this->ensureSpeechTherapySpeciality();
    }

    private function ensureCountries(): void
    {
        $countries = [
            ['name_en' => 'United Kingdom', 'name_ar' => 'بريطانيا'],
            ['name_en' => 'Syria', 'name_ar' => 'سوريا'],
            ['name_en' => 'Jordan', 'name_ar' => 'الأردن'],
        ];

        foreach ($countries as $country) {
            $exists = DB::table('country')
                ->where('name_en', $country['name_en'])
                ->orWhere('name_ar', $country['name_ar'])
                ->exists();

            if ($exists) {
                continue;
            }

            DB::table('country')->insert([
                'name_en' => $country['name_en'],
                'name_ar' => $country['name_ar'],
                'name_ch' => '',
                'icon' => '',
                'c_key' => '',
                'publish' => 1,
                'created_by' => 1,
                'updated_by' => 1,
            ]);
        }
    }

    private function ensureSpeechTherapySpeciality(): void
    {
        $existing = DB::table('specialized_clinics')->where('id', self::SPEECH_THERAPY_SPECIALITY_ID)->first();

        if ($existing !== null && trim((string) $existing->subject_en) !== '') {
            return;
        }

        $now = now()->format('Y-m-d H:i:s');

        if ($existing === null) {
            DB::table('specialized_clinics')->insert([
                'id' => self::SPEECH_THERAPY_SPECIALITY_ID,
                'clinics_id' => 2,
                'building_num' => 0,
                'subject_ar' => 'عيادة النطق والتخاطب',
                'subject_en' => 'SPEECH THERAPY CLINIC',
                'post_ar' => '',
                'post_en' => '',
                'uploaded_file' => '',
                'companies_groups_id' => 1,
                'created_at' => $now,
                'created_by' => 1,
                'publish' => '1',
                'updated_by' => 1,
                'updated_at' => $now,
            ]);

            return;
        }

        DB::table('specialized_clinics')
            ->where('id', self::SPEECH_THERAPY_SPECIALITY_ID)
            ->update([
                'subject_ar' => 'عيادة النطق والتخاطب',
                'subject_en' => 'SPEECH THERAPY CLINIC',
                'publish' => '1',
                'updated_by' => 1,
                'updated_at' => $now,
            ]);
    }

    /**
     * @param  list<int>  $specialityIds
     */
    private function ensureOpdSections(int $opdId, array $specialityIds): void
    {
        foreach ($specialityIds as $specialityId) {
            $exists = DB::table('outpatient_clinics_sections')
                ->where('outpatient_clinics_id', $opdId)
                ->where('specialized_clinics_id', $specialityId)
                ->exists();

            if ($exists) {
                continue;
            }

            DB::table('outpatient_clinics_sections')->insert([
                'outpatient_clinics_id' => $opdId,
                'specialized_clinics_id' => $specialityId,
                'publish' => 1,
                'created_by' => 1,
                'updated_by' => 1,
            ]);
        }
    }

    private function assignExistingDoctorsToOpd(int $opdId, int $specialityId, string $extNumber): void
    {
        $clinicianIds = DB::table('clinicians as c')
            ->where('c.specialized_clinics_id', $specialityId)
            ->where('c.publish', '1')
            ->whereNotExists(function ($query) use ($opdId) {
                $query->select(DB::raw(1))
                    ->from('clinician_hospitals as ch')
                    ->whereColumn('ch.clinicians_id', 'c.id')
                    ->where('ch.clinics_id', $opdId)
                    ->where('ch.hospital_id', self::HOSPITAL_ID);
            })
            ->whereNotExists(function ($query) {
                $query->select(DB::raw(1))
                    ->from('clinician_hospitals as suwaidi_branch')
                    ->whereColumn('suwaidi_branch.clinicians_id', 'c.id')
                    ->where('suwaidi_branch.hospital_id', self::SUWAIDI_HOSPITAL_ID);
            })
            ->orderBy('c.ranking')
            ->orderBy('c.id')
            ->limit(self::ASSIGN_LIMIT)
            ->pluck('c.id');

        foreach ($clinicianIds as $clinicianId) {
            $this->ensureClinicianAssignment($opdId, (int) $clinicianId, $extNumber);
        }
    }

    private function seedFallbackDoctors(int $opdId, int $specialityId, string $now, string $extNumber): void
    {
        $speciality = DB::table('specialized_clinics')
            ->select(['subject_en', 'subject_ar'])
            ->where('id', $specialityId)
            ->first();

        if ($speciality === null) {
            return;
        }

        $labels = [
            1 => ['Consultant', 'استشاري'],
            2 => ['Senior Specialist', 'أخصائي أول'],
        ];

        foreach ($labels as $sequence => [$titleEn, $titleAr]) {
            $code = $this->fallbackDoctorCode($opdId, $specialityId, $sequence);

            if (DB::table('clinicians')->where('code', $code)->exists()) {
                $clinicianId = (int) DB::table('clinicians')->where('code', $code)->value('id');
                $this->repairPlaceholderDoctorNames($clinicianId);
                $this->ensureClinicianAssignment(
                    $opdId,
                    $clinicianId,
                    $extNumber,
                );

                continue;
            }

            $clinicianId = DB::table('clinicians')->insertGetId([
                'specialized_clinics_id' => $specialityId,
                'code' => $code,
                'name_ar' => "طبيب تجريبي {$sequence}",
                'name_en' => "Sample Doctor {$sequence}",
                'specialization_ar' => "{$titleAr} {$speciality->subject_ar}",
                'specialization_en' => "{$titleEn} {$speciality->subject_en}",
                'uploaded_file' => '',
                'price' => 0,
                'age' => 'All ages',
                'holds_ar' => '',
                'holds_en' => '',
                'cases_ar' => '',
                'cases_en' => '',
                'created_at' => $now,
                'created_by' => 1,
                'publish' => '1',
                'updated_by' => 1,
                'updated_at' => $now,
                'clinics_id' => $opdId,
                'country_id' => self::SAUDI_COUNTRY_ID,
                'mobile' => '',
                'email' => '',
                'ranking' => 1000,
                'sms_tocken' => '',
                'edit_date' => '',
                'status' => 0,
            ]);

            $this->ensureClinicianAssignment($opdId, $clinicianId, $extNumber);
        }
    }

    /**
     * @param  array{
     *     code: string,
     *     name_en: string,
     *     name_ar: string,
     *     specialization_en: string,
     *     specialization_ar: string,
     *     age: string,
     *     speciality_id: int,
     *     country_id?: int,
     *     price?: int,
     *     clinic_number?: string,
     *     holds_en?: string,
     *     holds_ar?: string,
     *     cases_en?: string,
     *     cases_ar?: string,
     *     ranking?: int
     * }  $doctor
     */
    private function seedFeaturedDoctor(
        int $opdId,
        array $doctor,
        string $now,
        string $extNumber,
        int $hospitalId = self::HOSPITAL_ID,
    ): void
    {
        $attributes = [
            'specialized_clinics_id' => $doctor['speciality_id'],
            'name_ar' => $doctor['name_ar'],
            'name_en' => $doctor['name_en'],
            'specialization_ar' => $doctor['specialization_ar'],
            'specialization_en' => $doctor['specialization_en'],
            'age' => $doctor['age'],
            'holds_ar' => $doctor['holds_ar'] ?? '',
            'holds_en' => $doctor['holds_en'] ?? '',
            'cases_ar' => $doctor['cases_ar'] ?? '',
            'cases_en' => $doctor['cases_en'] ?? '',
            'price' => $doctor['price'] ?? 0,
            'country_id' => $doctor['country_id'] ?? self::SAUDI_COUNTRY_ID,
            'ranking' => $doctor['ranking'] ?? 1000,
            'publish' => '1',
            'updated_by' => 1,
            'updated_at' => $now,
            'clinics_id' => $opdId,
        ];

        $clinicianId = DB::table('clinicians')
            ->where('code', $doctor['code'])
            ->value('id');

        if ($clinicianId === null) {
            $clinicianId = DB::table('clinicians')->insertGetId(array_merge($attributes, [
                'code' => $doctor['code'],
                'uploaded_file' => '',
                'created_at' => $now,
                'created_by' => 1,
                'mobile' => '',
                'email' => '',
                'sms_tocken' => '',
                'edit_date' => '',
                'status' => 0,
            ]));
        } else {
            DB::table('clinicians')->where('id', $clinicianId)->update($attributes);
        }

        $this->ensureClinicianAssignment(
            $opdId,
            (int) $clinicianId,
            $extNumber,
            $doctor['clinic_number'] ?? '',
            $doctor['price'] ?? null,
            $hospitalId,
        );
    }

    private function ensureClinicianAssignment(
        int $opdId,
        int $clinicianId,
        string $extNumber,
        string $clinicNumber = '',
        ?int $price = null,
        int $hospitalId = self::HOSPITAL_ID,
    ): void {
        $existing = DB::table('clinician_hospitals')
            ->where('clinicians_id', $clinicianId)
            ->where('hospital_id', $hospitalId)
            ->where('clinics_id', $opdId)
            ->first(['id', 'clinic_number', 'ext_number', 'price']);

        if ($existing !== null) {
            DB::table('clinician_hospitals')
                ->where('id', $existing->id)
                ->update([
                    'clinic_number' => $clinicNumber !== '' ? $clinicNumber : ($existing->clinic_number ?? ''),
                    'ext_number' => ($existing->ext_number ?? '') !== '' ? $existing->ext_number : $extNumber,
                    'price' => $price ?? $existing->price,
                ]);

            return;
        }

        DB::table('clinician_hospitals')->insert([
            'clinicians_id' => $clinicianId,
            'hospital_id' => $hospitalId,
            'clinics_id' => $opdId,
            'clinic_number' => $clinicNumber,
            'ext_number' => $extNumber,
            'price' => $price,
        ]);
    }

    private function countDoctorsOnOpd(int $opdId, int $specialityId): int
    {
        return (int) DB::table('clinicians as c')
            ->join('clinician_hospitals as ch', 'ch.clinicians_id', '=', 'c.id')
            ->where('c.specialized_clinics_id', $specialityId)
            ->where('ch.clinics_id', $opdId)
            ->where('ch.hospital_id', self::HOSPITAL_ID)
            ->whereIn('c.publish', ['1', 1])
            ->distinct('c.id')
            ->count('c.id');
    }

    private function resolveExtNumber(int $opdId): string
    {
        $ext = DB::table('outpatient_clinics_duty_time')
            ->where('cilinc_id', $opdId)
            ->where('publish', '1')
            ->orderByDesc('id')
            ->value('phone_ext');

        return is_string($ext) ? trim($ext) : '';
    }

    private function fallbackDoctorCode(int $opdId, int $specialityId, int $sequence): string
    {
        return sprintf('9%01d%03d%d', $opdId, $specialityId, $sequence);
    }

    private function repairPlaceholderDoctorNames(int $clinicianId): void
    {
        $doctor = Clinician::query()
            ->select(['id', 'name_ar', 'name_en', 'specialization_ar', 'specialization_en'])
            ->find($clinicianId);

        if ($doctor === null || ! $doctor->isPlaceholderNameRecord()) {
            return;
        }

        DB::table('clinicians')
            ->where('id', $clinicianId)
            ->update([
                'name_ar' => '',
                'name_en' => '',
                'updated_at' => now(),
            ]);
    }
}
