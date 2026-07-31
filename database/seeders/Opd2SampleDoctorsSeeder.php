<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class Opd2SampleDoctorsSeeder extends Seeder
{
    private const OPD_ID = 2;

    private const HOSPITAL_ID = 1;

    private const ORTHOPAEDIC_SPECIALITY_ID = 7;

    private const SPEECH_THERAPY_SPECIALITY_ID = 65;

    private const EXT_NUMBER = '7776';

    /**
     * @var list<int>
     */
    private const OPD2_SECTION_SPECIALITY_IDS = [
        7, 11, 60, 9, 16, 39, self::SPEECH_THERAPY_SPECIALITY_ID,
    ];

    /**
     * @var list<array{
     *     code: string,
     *     name_en: string,
     *     name_ar: string,
     *     specialization_en: string,
     *     specialization_ar: string,
     *     age: string,
     *     country: string,
     *     price: int|null,
     *     clinic_number: string,
     *     speciality_id: int
     * }>
     */
    private const DEPARTMENT_DOCTORS = [
        [
            'code' => '90929',
            'name_en' => 'AHMED MANSOUR ALMULLA',
            'name_ar' => 'أحمد منصور الملا',
            'specialization_en' => 'Consultant Orthopedic Surgeon',
            'specialization_ar' => 'استشاري جراحة العظام',
            'age' => 'Adults',
            'country' => 'saudi',
            'price' => 300,
            'clinic_number' => '41',
            'speciality_id' => 7,
        ],
        [
            'code' => '17046',
            'name_en' => 'AAMIR USMAN BAIG',
            'name_ar' => 'عامر عثمان بيج',
            'specialization_en' => 'Consultant Orthopedic Surgeon',
            'specialization_ar' => 'استشاري جراحة العظام',
            'age' => '10+',
            'country' => 'british',
            'price' => 250,
            'clinic_number' => '43',
            'speciality_id' => 7,
        ],
        [
            'code' => '17196',
            'name_en' => 'AHMED MOHAMED ELFEEL',
            'name_ar' => 'أحمد محمد الفيل',
            'specialization_en' => 'Consultant Orthopedic Surgeon',
            'specialization_ar' => 'إستشاري جراحة العظام',
            'age' => 'All ages',
            'country' => 'sudan',
            'price' => 250,
            'clinic_number' => '36',
            'speciality_id' => 7,
        ],
        [
            'code' => '90888',
            'name_en' => 'BASHAH MOHAMMAD AL MUSTAIR',
            'name_ar' => 'بشاه محمد المستير',
            'specialization_en' => 'Consultant Orthopedic Surgeon',
            'specialization_ar' => 'استشاري جراحة العظام',
            'age' => 'Adults',
            'country' => 'saudi',
            'price' => 300,
            'clinic_number' => '42',
            'speciality_id' => 7,
        ],
        [
            'code' => '90827',
            'name_en' => 'TALAL HASSAN ALMALKI',
            'name_ar' => 'طلال حسن المالكي',
            'specialization_en' => 'Consultant Orthopedic Surgeon, Foot and Ankle Surgery, Diabetic Foot',
            'specialization_ar' => 'استشاري جراحة العظام، جراحة القدم والكاحل، القدم السكرية',
            'age' => '3+',
            'country' => 'saudi',
            'price' => 300,
            'clinic_number' => '42',
            'speciality_id' => 7,
        ],
        [
            'code' => '19799',
            'name_en' => 'Mohammed Bin Othman Al-Subaih',
            'name_ar' => 'محمد بن عثمان الصبيح',
            'specialization_en' => 'Consultant Orthopedic, Joint and Sports Injury Surgeon',
            'specialization_ar' => 'استشاري جراحة العظام والمفاصل والإصابات الرياضية',
            'age' => 'All ages',
            'country' => 'saudi',
            'price' => null,
            'clinic_number' => '',
            'speciality_id' => 7,
        ],
        [
            'code' => '18747',
            'name_en' => 'MOUTAZ ALI DALATI',
            'name_ar' => 'معتز علي دلتي',
            'specialization_en' => 'Consultant Orthopedic Surgeon',
            'specialization_ar' => 'استشاري جراحة العظام',
            'age' => '12+ (fractures and injuries, all ages)',
            'country' => 'syrian',
            'price' => 250,
            'clinic_number' => '41',
            'speciality_id' => 7,
        ],
        [
            'code' => '1222',
            'name_en' => 'BADER MAJED ALJADAAN',
            'name_ar' => 'بدر ماجد الجدعان',
            'specialization_en' => 'Consultant Orthopedic Surgeon / Arthroscopy and Sports Medicine',
            'specialization_ar' => 'استشاري جراحة العظام / جراحة تنظير المفاصل والطب الرياضي',
            'age' => 'All ages',
            'country' => 'saudi',
            'price' => 300,
            'clinic_number' => '37',
            'speciality_id' => 7,
        ],
        [
            'code' => '268',
            'name_en' => 'BASHIR RAMADAN ALENAZI',
            'name_ar' => 'بشير رمضان العنزي',
            'specialization_en' => 'Consultant Orthopedic Surgeon / Joint Reconstruction',
            'specialization_ar' => 'استشاري جراحة العظام / تقويم المفاصل وإعادة بناء المفاصل',
            'age' => '14+',
            'country' => 'saudi',
            'price' => 250,
            'clinic_number' => '43',
            'speciality_id' => 7,
        ],
        [
            'code' => '13592',
            'name_en' => 'MOHAMED ALI ELAMEEN MOHAMED',
            'name_ar' => 'محمد علي الأمين محمد',
            'specialization_en' => 'Consultant Orthopedic Surgeon',
            'specialization_ar' => 'استشاري جراحة عظام',
            'age' => 'All ages',
            'country' => 'sudan',
            'price' => 250,
            'clinic_number' => '36',
            'speciality_id' => 7,
        ],
        [
            'code' => '94',
            'name_en' => 'SAAD SALEH ALOLAYAN',
            'name_ar' => 'سعد صالح العليان',
            'specialization_en' => 'Consultant Orthopedic Surgeon',
            'specialization_ar' => 'استشاري جراحة عظام',
            'age' => '14+',
            'country' => 'saudi',
            'price' => 300,
            'clinic_number' => '34',
            'speciality_id' => 7,
        ],
        [
            'code' => '14312',
            'name_en' => 'SABRI BAJIS ZEIADAH',
            'name_ar' => 'صبري باجس زيادة',
            'specialization_en' => 'Consultant Orthopedic Surgeon',
            'specialization_ar' => 'استشاري عظام',
            'age' => 'All ages',
            'country' => 'jordanian',
            'price' => 250,
            'clinic_number' => '42',
            'speciality_id' => 7,
        ],
        [
            'code' => '969',
            'name_en' => 'ABDULAZIZ ZAMIL AL OMAR',
            'name_ar' => 'عبدالعزيز زامل العمر',
            'specialization_en' => 'Consultant Orthopedic and Sports Injury Surgeon',
            'specialization_ar' => 'استشاري جراحة عظام واصابات ملاعب',
            'age' => '14+',
            'country' => 'saudi',
            'price' => 300,
            'clinic_number' => '33',
            'speciality_id' => 7,
        ],
        [
            'code' => '960',
            'name_en' => 'MOHAMMED HASHIM AL ATHAS',
            'name_ar' => 'محمد هاشم الأثاس',
            'specialization_en' => 'Consultant Orthopedic, Joint Replacement and Sports Injury Surgeon',
            'specialization_ar' => 'استشاري جراحة العظام والمفاصل الصناعية والاصابات الرياضية',
            'age' => '14+',
            'country' => 'saudi',
            'price' => 300,
            'clinic_number' => '37',
            'speciality_id' => 7,
        ],
        [
            'code' => '639',
            'name_en' => 'AHMED ALQARNI',
            'name_ar' => 'أحمد القرني',
            'specialization_en' => 'Consultant Orthopedic, Sports Injury and Foot/Ankle Surgeon',
            'specialization_ar' => 'استشاري جراحة عظام واصابات رياضية وقدم وكاحل',
            'age' => '14+',
            'country' => 'saudi',
            'price' => 300,
            'clinic_number' => '37',
            'speciality_id' => 7,
        ],
        [
            'code' => '83',
            'name_en' => 'USAMA JABARA BARGHOUTI',
            'name_ar' => 'USAMA JABARA BARGHOUTI',
            'specialization_en' => 'Urology Surgery Specialist',
            'specialization_ar' => 'اخصائي جراحة مسالك بولية',
            'age' => 'جميع الاعمار',
            'country' => 'jordanian',
            'price' => 250,
            'clinic_number' => '40',
            'speciality_id' => 11,
        ],
        [
            'code' => '90898',
            'name_en' => 'KHALID ALI ALGHAMDI',
            'name_ar' => 'KHALID ALI ALGHAMDI',
            'specialization_en' => 'Consultant Urology and Urologic Surgery',
            'specialization_ar' => 'استشاري المسالك وجراحات المسالك البوليه',
            'age' => '+10',
            'country' => 'saudi',
            'price' => 250,
            'clinic_number' => '38',
            'speciality_id' => 11,
        ],
        [
            'code' => '19801',
            'name_en' => 'عبدالرحمن الديحاني',
            'name_ar' => 'عبدالرحمن الديحاني',
            'specialization_en' => 'Consultant Urology Surgeon',
            'specialization_ar' => 'استشاري جراحة مسالك بولية',
            'age' => '15 سنة فما فوق',
            'country' => 'saudi',
            'price' => null,
            'clinic_number' => '',
            'speciality_id' => 11,
        ],
        [
            'code' => '91058',
            'name_en' => 'أحمد محمد الحازمي',
            'name_ar' => 'أحمد محمد الحازمي',
            'specialization_en' => 'Consultant Urology Surgeon',
            'specialization_ar' => 'استشاري جراحة المسالك البولية',
            'age' => '١٤ وما فوق',
            'country' => 'saudi',
            'price' => null,
            'clinic_number' => '',
            'speciality_id' => 11,
        ],
        [
            'code' => '74',
            'name_en' => 'MOHAMMED MAZEN CHUKAIR',
            'name_ar' => 'MOHAMMED MAZEN CHUKAIR',
            'specialization_en' => 'Consultant Urology Surgeon',
            'specialization_ar' => 'استشاري جراحة مسالك بولية',
            'age' => 'جميع الاعمار',
            'country' => 'syrian',
            'price' => 250,
            'clinic_number' => '39',
            'speciality_id' => 11,
        ],
        [
            'code' => '90311',
            'name_en' => 'ABDULLAH MOHAMMED ALHARBI',
            'name_ar' => 'عبدالله محمد الحربي',
            'specialization_en' => 'Consultant Anaesthesiologist',
            'specialization_ar' => 'استشاري تخدير',
            'age' => 'All ages',
            'country' => 'saudi',
            'price' => 0,
            'clinic_number' => '35',
            'speciality_id' => 60,
        ],
        [
            'code' => '90312',
            'name_en' => 'NORA ABDULAZIZ ALSHEHRI',
            'name_ar' => 'نورة عبدالعزيز الشهري',
            'specialization_en' => 'Consultant Anaesthesiologist',
            'specialization_ar' => 'استشارية تخدير',
            'age' => 'All ages',
            'country' => 'saudi',
            'price' => 0,
            'clinic_number' => '35',
            'speciality_id' => 60,
        ],
        [
            'code' => '90313',
            'name_en' => 'SALEH AHMED ALDOSARI',
            'name_ar' => 'صالح أحمد الدوسري',
            'specialization_en' => 'Senior Registrar Anaesthesia',
            'specialization_ar' => 'أخصائي أول تخدير',
            'age' => 'All ages',
            'country' => 'saudi',
            'price' => 0,
            'clinic_number' => '35',
            'speciality_id' => 60,
        ],
        [
            'code' => '90411',
            'name_en' => 'YOUSUF HASSAN ALZAHRANI',
            'name_ar' => 'يوسف حسن الزهراني',
            'specialization_en' => 'Consultant Neurosurgeon',
            'specialization_ar' => 'استشاري جراحة مخ وأعصاب',
            'age' => 'All ages',
            'country' => 'saudi',
            'price' => 350,
            'clinic_number' => '38',
            'speciality_id' => 9,
        ],
        [
            'code' => '90412',
            'name_en' => 'HASSAN OMAR ALGHAMDI',
            'name_ar' => 'حسن عمر الغامدي',
            'specialization_en' => 'Consultant Neurosurgeon',
            'specialization_ar' => 'استشاري جراحة مخ وأعصاب',
            'age' => '14+',
            'country' => 'saudi',
            'price' => 300,
            'clinic_number' => '39',
            'speciality_id' => 9,
        ],
        [
            'code' => '90413',
            'name_en' => 'OMAR KHALID ALSUBAIE',
            'name_ar' => 'عمر خالد السبيع',
            'specialization_en' => 'Senior Registrar Neurosurgery',
            'specialization_ar' => 'أخصائي أول جراحة مخ وأعصاب',
            'age' => 'All ages',
            'country' => 'saudi',
            'price' => 250,
            'clinic_number' => '40',
            'speciality_id' => 9,
        ],
        [
            'code' => '90511',
            'name_en' => 'OMAR ABDULLAH ALFARIS',
            'name_ar' => 'عمر عبدالله الفارس',
            'specialization_en' => 'Consultant ENT Surgeon',
            'specialization_ar' => 'استشاري أنف وأذن وحنجرة',
            'age' => 'All ages',
            'country' => 'saudi',
            'price' => 300,
            'clinic_number' => '48',
            'speciality_id' => 16,
        ],
        [
            'code' => '90512',
            'name_en' => 'LAYLA MOHAMMED ALAMRI',
            'name_ar' => 'ليلى محمد العمري',
            'specialization_en' => 'Consultant ENT Surgeon',
            'specialization_ar' => 'استشارية أنف وأذن وحنجرة',
            'age' => '3+',
            'country' => 'saudi',
            'price' => 250,
            'clinic_number' => '49',
            'speciality_id' => 16,
        ],
        [
            'code' => '90513',
            'name_en' => 'TARIQ SAEED ALZAHRANI',
            'name_ar' => 'طارق سعيد الزهراني',
            'specialization_en' => 'Senior Registrar ENT',
            'specialization_ar' => 'أخصائي أول أنف وأذن وحنجرة',
            'age' => 'All ages',
            'country' => 'saudi',
            'price' => 200,
            'clinic_number' => '50',
            'speciality_id' => 16,
        ],
        [
            'code' => '90611',
            'name_en' => 'FAISAL ABDULAZIZ ALOTAIBI',
            'name_ar' => 'فيصل عبدالعزيز العتيبي',
            'specialization_en' => 'Consultant Paediatric Orthopaedic Surgeon',
            'specialization_ar' => 'استشاري جراحة عظام الأطفال',
            'age' => '0-16',
            'country' => 'saudi',
            'price' => 300,
            'clinic_number' => '44',
            'speciality_id' => 39,
        ],
        [
            'code' => '90612',
            'name_en' => 'RANA KHALID ALMALKI',
            'name_ar' => 'رنا خالد المالكي',
            'specialization_en' => 'Consultant Paediatric Orthopaedic Surgeon',
            'specialization_ar' => 'استشارية جراحة عظام الأطفال',
            'age' => '0-16',
            'country' => 'saudi',
            'price' => 250,
            'clinic_number' => '44',
            'speciality_id' => 39,
        ],
        [
            'code' => '90711',
            'name_en' => 'SARAH ABDULRAHMAN ALANAZI',
            'name_ar' => 'سارة عبدالرحمن العنزي',
            'specialization_en' => 'Speech and Language Therapist',
            'specialization_ar' => 'أخصائية نطق وتخاطب',
            'age' => 'All ages',
            'country' => 'saudi',
            'price' => 150,
            'clinic_number' => '52',
            'speciality_id' => 65,
        ],
        [
            'code' => '90712',
            'name_en' => 'HUDA MOHAMMED ALQHTANI',
            'name_ar' => 'هدى محمد القحطاني',
            'specialization_en' => 'Speech and Language Therapist',
            'specialization_ar' => 'أخصائية نطق وتخاطب',
            'age' => 'All ages',
            'country' => 'saudi',
            'price' => 150,
            'clinic_number' => '52',
            'speciality_id' => 65,
        ],
    ];

    public function run(): void
    {
        $now = now()->format('Y-m-d H:i:s');

        $this->ensureCountries();
        $this->ensureSpeechTherapySpeciality();
        $this->ensureOpdSections();
        $this->removeSupersededUrologySamples();

        foreach (self::DEPARTMENT_DOCTORS as $doctor) {
            $this->seedDoctor($doctor, $now);
        }
    }

    private function removeSupersededUrologySamples(): void
    {
        $sampleCodes = ['90211', '90212', '90213'];
        $sampleIds = DB::table('clinicians')
            ->whereIn('code', $sampleCodes)
            ->where('specialized_clinics_id', 11)
            ->pluck('id');

        if ($sampleIds->isEmpty()) {
            return;
        }

        DB::table('clinician_hospitals')
            ->whereIn('clinicians_id', $sampleIds)
            ->where('clinics_id', self::OPD_ID)
            ->delete();

        DB::table('clinicians')
            ->whereIn('id', $sampleIds)
            ->delete();
    }

    private function ensureSpeechTherapySpeciality(): void
    {
        $existing = DB::table('specialized_clinics')->where('id', self::SPEECH_THERAPY_SPECIALITY_ID)->first();

        if ($existing !== null && trim((string) $existing->subject_en) !== '') {
            return;
        }

        if ($existing === null) {
            DB::table('specialized_clinics')->insert([
                'id' => self::SPEECH_THERAPY_SPECIALITY_ID,
                'clinics_id' => self::OPD_ID,
                'building_num' => 0,
                'subject_ar' => 'عيادة النطق والتخاطب',
                'subject_en' => 'SPEECH THERAPY CLINIC',
                'post_ar' => '',
                'post_en' => '',
                'uploaded_file' => '',
                'companies_groups_id' => 1,
                'created_at' => now()->format('Y-m-d H:i:s'),
                'created_by' => 1,
                'publish' => '1',
                'updated_by' => 1,
                'updated_at' => now()->format('Y-m-d H:i:s'),
            ]);

            return;
        }

        DB::table('specialized_clinics')
            ->where('id', self::SPEECH_THERAPY_SPECIALITY_ID)
            ->update([
                'clinics_id' => self::OPD_ID,
                'subject_ar' => 'عيادة النطق والتخاطب',
                'subject_en' => 'SPEECH THERAPY CLINIC',
                'publish' => '1',
                'updated_by' => 1,
                'updated_at' => now()->format('Y-m-d H:i:s'),
            ]);
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

    private function ensureOpdSections(): void
    {
        foreach (self::OPD2_SECTION_SPECIALITY_IDS as $specialityId) {
            $exists = DB::table('outpatient_clinics_sections')
                ->where('outpatient_clinics_id', self::OPD_ID)
                ->where('specialized_clinics_id', $specialityId)
                ->exists();

            if ($exists) {
                continue;
            }

            DB::table('outpatient_clinics_sections')->insert([
                'outpatient_clinics_id' => self::OPD_ID,
                'specialized_clinics_id' => $specialityId,
                'publish' => 1,
                'created_by' => 1,
                'updated_by' => 1,
            ]);
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
     *     country: string,
     *     price: int|null,
     *     clinic_number: string,
     *     speciality_id: int
     * }  $doctor
     */
    private function seedDoctor(array $doctor, string $now): void
    {
        $countryId = $this->resolveCountryId($doctor['country']);
        $specialityId = (int) $doctor['speciality_id'];

        $clinicianId = DB::table('clinicians')
            ->where('code', $doctor['code'])
            ->value('id');

        if ($clinicianId === null) {
            $clinicianId = DB::table('clinicians')->insertGetId([
                'specialized_clinics_id' => $specialityId,
                'code' => $doctor['code'],
                'name_ar' => $doctor['name_ar'],
                'name_en' => $doctor['name_en'],
                'specialization_ar' => $doctor['specialization_ar'],
                'specialization_en' => $doctor['specialization_en'],
                'uploaded_file' => '',
                'price' => $doctor['price'] ?? 0,
                'age' => $doctor['age'],
                'holds_ar' => '',
                'holds_en' => '',
                'cases_ar' => '',
                'cases_en' => '',
                'created_at' => $now,
                'created_by' => 1,
                'publish' => '1',
                'updated_by' => 1,
                'updated_at' => $now,
                'clinics_id' => self::OPD_ID,
                'country_id' => $countryId,
                'mobile' => '',
                'email' => '',
                'ranking' => 1000,
                'sms_tocken' => '',
                'edit_date' => '',
                'status' => 0,
            ]);
        } else {
            DB::table('clinicians')
                ->where('id', $clinicianId)
                ->update([
                    'specialized_clinics_id' => $specialityId,
                    'name_ar' => $doctor['name_ar'],
                    'name_en' => $doctor['name_en'],
                    'specialization_ar' => $doctor['specialization_ar'],
                    'specialization_en' => $doctor['specialization_en'],
                    'price' => $doctor['price'] ?? 0,
                    'age' => $doctor['age'],
                    'country_id' => $countryId,
                    'clinics_id' => self::OPD_ID,
                    'publish' => '1',
                    'updated_at' => $now,
                ]);
        }

        $assignmentExists = DB::table('clinician_hospitals')
            ->where('clinicians_id', $clinicianId)
            ->where('hospital_id', self::HOSPITAL_ID)
            ->where('clinics_id', self::OPD_ID)
            ->exists();

        if ($assignmentExists) {
            DB::table('clinician_hospitals')
                ->where('clinicians_id', $clinicianId)
                ->where('hospital_id', self::HOSPITAL_ID)
                ->where('clinics_id', self::OPD_ID)
                ->update([
                    'clinic_number' => $doctor['clinic_number'],
                    'ext_number' => self::EXT_NUMBER,
                    'price' => $doctor['price'],
                ]);

            return;
        }

        DB::table('clinician_hospitals')->insert([
            'clinicians_id' => $clinicianId,
            'hospital_id' => self::HOSPITAL_ID,
            'clinics_id' => self::OPD_ID,
            'clinic_number' => $doctor['clinic_number'],
            'ext_number' => self::EXT_NUMBER,
            'price' => $doctor['price'],
        ]);
    }

    private function resolveCountryId(string $country): int
    {
        return match ($country) {
            'saudi' => (int) DB::table('country')->where('name_en', 'Saudi Arabia')->value('id') ?: 1,
            'sudan' => (int) DB::table('country')->where('name_en', 'Sudan')->value('id') ?: 3,
            'british' => (int) DB::table('country')->where('name_en', 'United Kingdom')->value('id'),
            'syrian' => (int) DB::table('country')->where('name_en', 'Syria')->value('id'),
            'jordanian' => (int) DB::table('country')->where('name_en', 'Jordan')->value('id'),
            default => 1,
        };
    }
}
