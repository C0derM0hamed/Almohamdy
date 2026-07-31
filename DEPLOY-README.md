# HM Deploy Package — 2026-07-09

## Included changes

- Gmail OTP login (real email send + verify)
- Auth back-button / history fixes (login, OTP, dashboard)
- Page transitions with bfcache-safe browser back
- English/Arabic translations
- Sidebar collapse toggle + RTL layout fixes
- Work Absence Dashboard (restored + data-driven sections)
- Doctors Directory Admin full-page add/edit forms
- Complaints, inquiries, service locations, and other UI redesigns

## Deploy steps

1. **Backup** the live site and database before applying.

2. **Extract** this zip over the Laravel project root (merge folders).

3. **Update `.env`** on the server (do not overwrite from zip):

```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_ENCRYPTION=tls
MAIL_USERNAME=your-email@gmail.com
MAIL_PASSWORD=your-gmail-app-password
MAIL_FROM_ADDRESS=your-email@gmail.com
MAIL_FROM_NAME="Tracker & IOT"
MAIL_REPLY_TO_ADDRESS=your-email@gmail.com

HM_OTP_DEMO_MODE=false
HM_OTP_EXPIRY_SECONDS=120
HM_OTP_MAX_ATTEMPTS=5
```

4. Run on the server:

```bash
php artisan config:clear
php artisan view:clear
php artisan cache:clear
```

5. Hard-refresh the browser after deploy.

## OTP notes

- OTP is sent to each user's `hr_email_address` in `ra_users`.
- Gmail requires an **App Password** for SMTP.
- Users must have a valid registered email when `HM_OTP_DEMO_MODE=false`.
