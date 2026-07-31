HM Service Locations — production data fix (cPanel)
===================================================

If OPD doctor pages show "No doctors found" after uploading the code package,
the production database is usually missing clinician_hospitals assignment rows.
Locally, those rows were created by seeders; the cPanel ZIP does not include them.

Step 1 — Upload code fix (v1.0.5)
---------------------------------
Upload and extract: deploy/hm-v1.0.5-cpanel.zip
Set HM_APP_VERSION=1.0.5 and clear Laravel caches.

Step 2 — Diagnose on server (SSH / cPanel Terminal)
-----------------------------------------------------
Run this SQL in phpMyAdmin for O.P.D 2 / Orthopaedic (speciality 7):

SELECT COUNT(*) AS doctors
FROM clinicians c
JOIN clinician_hospitals ch ON ch.clinicians_id = c.id
WHERE c.specialized_clinics_id = 7
  AND ch.clinics_id = 2
  AND c.publish IN ('1', 1);

If doctors = 0, continue to Step 3.

Step 3A — Backfill from existing clinicians (preferred for production)
----------------------------------------------------------------------
Uses clinicians.clinics_id to create missing clinician_hospitals rows.

  php artisan hm:backfill-clinician-hospitals --dry-run
  php artisan hm:backfill-clinician-hospitals

Then log out and log in again (refreshes companies_groups_id in session).

Step 3B — Seed sample OPD doctors (staging / test only)
-------------------------------------------------------
Only use on non-production or when you intentionally want sample data.

  php artisan db:seed --class=Opd1SampleDoctorsSeeder
  php artisan db:seed --class=Opd2SampleDoctorsSeeder
  php artisan db:seed --class=Opd3SampleDoctorsSeeder

Opd1SampleDoctorsSeeder now also links existing speciality doctors to O.P.D 1
before creating fallback sample rows. Run it when O.P.D 2 works but O.P.D 1 is empty.

Seed all configured O.P.D locations (1-6) in one command:

  php artisan db:seed --class=OpdSampleDoctorsSeeder

Or seed a single O.P.D:

  php artisan db:seed --class=Opd1SampleDoctorsSeeder
  php artisan db:seed --class=Opd2SampleDoctorsSeeder
  php artisan db:seed --class=Opd3SampleDoctorsSeeder

Recommended DB indexes (ask DBA; not included in app migrations):
  clinician_hospitals (clinicians_id)
  clinician_hospitals (clinics_id, hospital_id)
  clinicians (specialized_clinics_id, publish)

Upload these seeder files to the server first (they are not in the cPanel ZIP):
  database/seeders/Opd1SampleDoctorsSeeder.php
  database/seeders/Opd2SampleDoctorsSeeder.php
  database/seeders/Opd3SampleDoctorsSeeder.php

Step 4 — Verify
---------------
Open: /modules/service-locations/opd/2/departments/7/doctors
You should see the orthopaedic doctors list.
