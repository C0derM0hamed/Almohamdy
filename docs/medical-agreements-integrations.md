# تشغيل تكامل اتفاقيات الخدمات الطبية

هذا الملف يشرح رفع التحديثات وتشغيل تكامل «يقين» ومنصة «صادق» في السيرفر.

## 1. قبل الرفع

ارفع ملفات المشروع الجديدة، خصوصًا:

- `app/Services/LegacyWorkflows/YakeenClient.php`
- `app/Services/LegacyWorkflows/SadqClient.php`
- `app/Services/LegacyWorkflows/MedicalAgreementService.php`
- `app/Http/Controllers/PublicForms/SadqCallbackController.php`
- `resources/views/legacy-workflows/medical-agreements/_form.blade.php`
- `routes/legacy-workflows.php`
- `config/services.php`

لا ترفع ملف `.env` من جهاز التطوير إلى السيرفر. استخدم ملف `.env` الموجود على السيرفر وعدّله يدويًا.

تأكد أن السيرفر يحتوي على:

- PHP بالإصدار المستخدم في المشروع.
- إضافات `curl` و`openssl` و`pdo_mysql`.
- اتصال HTTPS صحيح للدومين.
- صلاحية الكتابة على `storage` و`bootstrap/cache`.

## 2. متغيرات منصة صادق

أضف القيم التالية إلى `.env` على السيرفر:

```env
SADQ_ENABLED=true
SADQ_BASE_URL=https://apigw.sadq.sa
SADQ_CLIENT_USERNAME=
SADQ_CLIENT_PASSWORD=
SADQ_AUTH_BASIC=
SADQ_INTEGRATION_USERNAME=
SADQ_INTEGRATION_PASSWORD=
SADQ_ACCOUNT_ID=
SADQ_ACCOUNT_SECRET=
SADQ_AVAILABLE_TO=2029-08-29
SADQ_CALLBACK_SECRET=
SADQ_TIMEOUT=60
```

وصف القيم:

| المتغير | القيمة المطلوبة |
|---|---|
| `SADQ_ENABLED` | يجب أن تكون `true` بعد استلام بيانات العميل. اتركها `false` قبل ذلك. |
| `SADQ_BASE_URL` | رابط بيئة صادق. استخدم رابط Sandbox إذا أعطاه العميل بدل Production. |
| `SADQ_CLIENT_USERNAME` | اسم مستخدم Client Authentication. |
| `SADQ_CLIENT_PASSWORD` | كلمة مرور Client Authentication. |
| `SADQ_AUTH_BASIC` | اختياري. قيمة Basic Auth المشفرة Base64. إذا أرسل العميل هذه القيمة استخدمها واترك حقلي المستخدم وكلمة المرور فارغين. |
| `SADQ_INTEGRATION_USERNAME` | مستخدم التكامل إذا طلبته صادق. |
| `SADQ_INTEGRATION_PASSWORD` | كلمة مرور مستخدم التكامل إذا طلبتها صادق. |
| `SADQ_ACCOUNT_ID` | رقم حساب المنشأة إذا كان مطلوبًا في عقد التكامل. |
| `SADQ_ACCOUNT_SECRET` | سر حساب المنشأة إذا كان مطلوبًا. |
| `SADQ_AVAILABLE_TO` | تاريخ انتهاء صلاحية دعوة التوقيع بصيغة `YYYY-MM-DD`. |
| `SADQ_CALLBACK_SECRET` | قيمة سرية مشتركة لحماية Callback. يجب أن تكون نفس القيمة المسجلة لدى صادق. |
| `SADQ_TIMEOUT` | مهلة الاتصال بالثواني، والقيمة المقترحة `60`. |

الكود يستخدم مسارات صادق القديمة التالية:

```text
POST /Authentication/Authority/Token
POST /IntegrationService/Document/Initiate-envelope-Base64
POST /IntegrationService/Invitation/Send-Invitation
GET  /IntegrationService/Document/envelope-status/referenceNumber/{reference}
GET  /IntegrationService/Document/DownloadBase64/{documentId}
POST /IntegrationService/Invitation/Signe-Reminder
POST /IntegrationService/Document/Cancel-envelope
```

## 3. متغيرات خدمة يقين

أضف القيم التالية إلى `.env`:

```env
YAQEEN_ENABLED=true
YAQEEN_BASE_URL=https://yakeencore.api.elm.sa
YAQEEN_USERNAME=
YAQEEN_PASSWORD=
YAQEEN_USAGE_CODE=
YAQEEN_OPERATOR_ID=
YAQEEN_APP_ID=
YAQEEN_APP_KEY=
YAQEEN_SAUDI_NATIONALITY_CODE=113
YAQEEN_TIMEOUT=30
```

وصف القيم:

| المتغير | القيمة المطلوبة |
|---|---|
| `YAQEEN_ENABLED` | يجب أن تكون `true` بعد استلام بيانات العميل. |
| `YAQEEN_BASE_URL` | رابط خدمة يقين، أو رابط Sandbox الذي يرسله العميل. |
| `YAQEEN_USERNAME` | اسم مستخدم يقين. |
| `YAQEEN_PASSWORD` | كلمة مرور يقين. |
| `YAQEEN_USAGE_CODE` | Usage Code الصادر من يقين. |
| `YAQEEN_OPERATOR_ID` | Operator ID الصادر من يقين. |
| `YAQEEN_APP_ID` | App ID الصادر من يقين. |
| `YAQEEN_APP_KEY` | App Key الصادر من يقين. |
| `YAQEEN_SAUDI_NATIONALITY_CODE` | كود الجنسية السعودية في جدول `country_yakeen`، والقيمة القديمة كانت `113`. يجب تأكيدها من العميل. |
| `YAQEEN_TIMEOUT` | مهلة الاتصال بالثواني، والقيمة المقترحة `30`. |

التكامل يستخدم الحالات التالية:

| نوع الهوية | خدمة يقين | نوع التاريخ |
|---|---|---|
| الهوية الوطنية | Saudi NIN | هجري |
| الإقامة | Iqama | ميلادي |
| رقم خليجي | GCC NIN | ميلادي |
| جواز السفر | Non-Saudi Passport | ميلادي |
| رقم الحدود | Border Number | ميلادي |

## 4. إعداد Callback صادق

يجب أن يسجل العميل هذا الرابط في إعدادات منصة صادق:

```text
https://YOUR-DOMAIN.com/sadq/callback
```

استبدل `YOUR-DOMAIN.com` بالدومين الحقيقي.

اطلب من العميل تأكيد الآتي:

- أن الطلبات تصل بصيغة JSON.
- اسم حقل رقم المرجع: `referenceNumber` أو `referencNumber`.
- اسم حقل رقم الطلب: `requestId`.
- حالات التوقيع: مكتمل، مرفوض، أو قيد التوقيع.
- قيمة Callback Secret أو طريقة التوقيع المطلوبة.
- إضافة IP السيرفر إلى Whitelist إذا كانت المنصة تطلب ذلك.

الـCallback لا يحتاج تسجيل دخول أو CSRF، لكنه محمي بـ`SADQ_CALLBACK_SECRET` إذا تم ضبطه.

## 5. أوامر بعد تعديل `.env`

نفّذ الأوامر من مجلد المشروع على السيرفر:

```bash
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

إذا كان التطبيق يعمل خلف PHP-FPM، أعد تحميل PHP-FPM بعد تغيير `.env` حسب إعداد السيرفر، مثلًا:

```bash
sudo systemctl reload php8.3-fpm
```

استخدم إصدار PHP الموجود فعليًا على السيرفر بدل `8.3` إذا كان مختلفًا.

## 6. اختبار التشغيل

1. سجّل الدخول بحساب لديه صلاحية صفحة الاتفاقيات.
2. افتح الاتفاقية العادية من السايدبار.
3. اضغط «اتفاقية جديدة» وتأكد أن نافذة Pop-up تفتح بدون الانتقال لصفحة أخرى.
4. اختر نوع الهوية الوطنية وتأكد أن التاريخ يتحول إلى هجري.
5. اختر إقامة أو جواز سفر وتأكد أن التاريخ يتحول إلى ميلادي.
6. اضغط «جلب بيانات المريض من يقين» وتأكد من تعبئة الاسم والجنسية والجنس.
7. أكمل رقم الملف والجوال واضغط إنشاء الاتفاقية.
8. في مسار صادق، تأكد من ظهور معاملة `In-progress` ورقم المستند.
9. بعد التوقيع، استخدم «تحديث الحالة» وتأكد من ظهور `Completed`.
10. نزّل PDF وتأكد أنه PDF الموقّع.

لا تستخدم أرقام هويات حقيقية في الاختبار إلا بتنسيق وموافقة العميل.

## 7. تعطيل التكامل مؤقتًا

في حالة وجود مشكلة في API، يمكن إيقاف الاتصالات مؤقتًا:

```env
YAQEEN_ENABLED=false
SADQ_ENABLED=false
```

ثم نفّذ:

```bash
php artisan optimize:clear
```

عند التعطيل ستظهر رسالة إعدادات غير مكتملة عند محاولة استخدام API، ولن يتم إرسال اتفاقيات جديدة إلى صادق.

## 8. أشهر الأخطاء

### خدمة يقين غير مفعّلة

تأكد من:

```env
YAQEEN_ENABLED=true
```

وأن جميع قيم `YAQEEN_*` موجودة، ثم نفّذ `php artisan optimize:clear`.

### منصة صادق غير مفعّلة

تأكد من:

```env
SADQ_ENABLED=true
```

وأن بيانات Client Authentication صحيحة.

### HTTP 401 أو 403

تحقق من البيئة المستخدمة، وصحة بيانات الدخول، ووجود IP السيرفر في Whitelist.

### cURL error 56 أو Connection reset by peer

هذا يعني أن الاتصال وصل إلى خادم يقين ثم أُغلق قبل وصول رد HTTP. لا تعالج الخطأ بتعطيل SSL أو تغيير رقم الهوية. افحص الاتصال من نفس سيرفر التطبيق:

```bash
getent hosts yakeencore.api.elm.sa
curl -sS -o /dev/null -w 'http=%{http_code} ip=%{remote_ip}\n' \
  --connect-timeout 10 --max-time 20 \
  'https://yakeencore.api.elm.sa/api/v1/yakeen/login'
```

إذا ظهر `http=000` أو استمر `Connection reset by peer`، أرسل لجهة يقين عنوان الخروج العام للسيرفر واطلب إضافته إلى Whitelist والتأكد من تفعيل خدمة الـAPI والبيئة الصحيحة. يمكن معرفة العنوان بالأمر:

```bash
curl -sS https://api.ipify.org
```

بعد تعديل السماح أو `.env` نفّذ أوامر تنظيف الكاش الواردة أعلاه. التطبيق يعيد المحاولة تلقائيًا ويعرض رسالة عربية مختصرة بدل إظهار خطأ cURL الخام.

### لم يتم العثور على بيانات يقين

تأكد من نوع الهوية وشهر وسنة الميلاد. الهوية الوطنية تحتاج تاريخًا هجريًا، والإقامة تحتاج تاريخًا ميلاديًا.

### Callback لا يحدّث الحالة

تحقق من:

- أن الرابط صحيح ويعمل من الإنترنت.
- أن صادق يرسل `referenceNumber` أو `referencNumber`.
- أن `SADQ_CALLBACK_SECRET` مطابق للطرفين.
- مراجعة `storage/logs/laravel.log`.

## 9. بيانات يجب طلبها من العميل

اطلبها عبر قناة آمنة، وليس داخل Git أو المحادثات العامة:

- بيانات دخول يقين كاملة.
- بيانات Client Authentication لصادق.
- رابط Production أو Sandbox لكل خدمة.
- Callback URL وCallback Secret.
- تأكيد كود الجنسية السعودية.
- تأكيد نوع Authentication المطلوب في صادق: نفاذ أو SMS أو Email.
- تأكيد IP Whitelist إن وجد.
