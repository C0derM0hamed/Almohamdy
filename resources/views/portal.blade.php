@php
    $locale = app()->getLocale();
    $isRtl = true;
@endphp
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>بوابة مستشفيات الحمادي</title>
    <link rel="icon" type="image/png" href="{{ asset('images/brand/hh-icon.png') }}">
    <link rel="preload" href="{{ asset('fonts/noto-kufi-arabic/NotoKufiArabic-Regular.ttf') }}" as="font" type="font/ttf" crossorigin>
    <link href="{{ asset('css/hm-fonts.css') }}" rel="stylesheet">
    <link href="{{ asset('css/hm-portal.css') }}?v={{ time() }}" rel="stylesheet">
</head>
<body>

    <div class="hero" id="heroSlider">
        {{-- سلايدر صور الفروع — لإضافة صورة جديدة: ارفع الملف في public/landingPage وضيف سلايد جديد بنفس الشكل --}}
        <div class="hero-slides">
            <div class="hero-slide is-active" style="background-image: url('{{ asset('landingPage/hero.png') }}');"></div>
            <div class="hero-slide hero-slide--cover" style="background-image: url('{{ asset('landingPage/hero-2.png') }}');"></div>
        </div>
        <div class="hero-overlay"></div>
        <div class="hero-watermark">
            <svg width="100%" height="100%" viewBox="0 0 200 430" fill="none" stroke="white" stroke-width="3" xmlns="http://www.w3.org/2000/svg" preserveAspectRatio="xMaxYMid slice">
                <path d="M200 -50 C 100 50, 50 150, 200 250" opacity="0.5"/>
                <path d="M200 50 C 50 150, 0 250, 200 350" opacity="0.3"/>
                <path d="M200 150 C 100 250, 50 350, 200 450" opacity="0.5"/>
            </svg>
        </div>
        <div class="hero-content">
            <img src="{{ asset('images/brand/hh-logo-horizontal.png') }}?v={{ filemtime(public_path('images/brand/hh-logo-horizontal.png')) }}" class="hero-logo" alt="مستشفيات الحمادي" width="1024" height="209" decoding="async">
            <a href="{{ route('login') }}" class="hero-login-btn">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"></path>
                    <polyline points="10 17 15 12 10 7"></polyline>
                    <line x1="15" y1="12" x2="3" y2="12"></line>
                </svg>
                تسجيل الدخول
            </a>
            <div class="hero-text-block">
                <h1 class="hero-h1">بوابة مستشفيات الحمادي</h1>
                <p class="hero-subtitle">الوصول السريع إلى الأنظمة الداخلية والتعاميم<br>واللوائح والإجراءات والسياسات</p>            </div>
            <div class="hero-ornament">
                <div class="hero-ornament-line"></div>
                <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path>
                    <polyline points="9 12 11 14 15 10"></polyline>
                </svg>
                <div class="hero-ornament-line"></div>
            </div>
        </div>
        <button type="button" class="hero-side-arrow hero-side-arrow--prev" aria-label="الصورة السابقة" hidden>
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"></polyline></svg>
        </button>
        <button type="button" class="hero-side-arrow hero-side-arrow--next" aria-label="الصورة التالية" hidden>
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"></polyline></svg>
        </button>
        <div class="hero-dots" aria-label="صور الفروع"></div>
    </div>

    <div class="portal-container portal-cards-wrapper">
        <div class="portal-cards-grid">
            <!-- التعاميم -->
            <a href="{{ route('modules.government-circulars.index') }}" class="portal-card">
                <div class="card-icon">
                    <svg viewBox="0 0 24 24">
                        <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path>
                        <polyline points="22,6 12,13 2,6"></polyline>
                    </svg>
                </div>
                <div class="card-text">
                    <h2 class="card-title">التعاميم</h2>
                    <p class="card-subtitle">جميع التعاميم والإشعارات الرسمية</p>
                </div>
            </a>
            
            <!-- اللوائح والأنظمة -->
            <div class="portal-card portal-card--unavailable" aria-disabled="true">
                <div class="card-icon">
                    <svg viewBox="0 0 24 24">
                        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                        <polyline points="14 2 14 8 20 8"></polyline>
                        <path d="M12 18s4-2 4-5v-2l-4-1.5-4 1.5v2c0 3 4 5 4 5z"></path>
                        <polyline points="10 14 11.5 15.5 14 12"></polyline>
                    </svg>
                </div>
                <div class="card-text">
                    <h2 class="card-title">اللوائح والأنظمة</h2>
                    <p class="card-subtitle">اللوائح والأنظمة والسياسات الداخلية — قريباً</p>
                </div>
            </div>
            
            <!-- الإجراءات -->
            <div class="portal-card portal-card--unavailable" aria-disabled="true">
                <div class="card-icon">
                    <svg viewBox="0 0 24 24">
                        <path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"></path>
                        <rect x="8" y="2" width="8" height="4" rx="1" ry="1"></rect>
                        <path d="M9 10L10.5 11.5L14 8"></path>
                        <path d="M9 16L10.5 17.5L14 14"></path>
                    </svg>
                </div>
                <div class="card-text">
                    <h2 class="card-title">الإجراءات</h2>
                    <p class="card-subtitle">دليل الإجراءات والسياسات المعتمدة — قريباً</p>
                </div>
            </div>
        </div>
    </div>

    <div class="portal-container accreditation-section">
        <h2 class="section-heading">
            <div class="heading-dash"></div>
            الاعتمادات والشهادات
            <div class="heading-dash"></div>
        </h2>
        <div class="accreditation-grid">
            <div class="acc-card" style="flex-direction: column;">
                <div class="acc-icon">
                    <img src="{{ asset('landingPage/accreditation-canada.png') }}" alt="Accreditation Canada">
                </div>
                <div class="acc-text" style="direction:ltr;">ACCREDITATION CANADA<br>Accredited</div>
            </div>
            <div class="acc-card">
                <div class="acc-icon">
                    <img src="{{ asset('landingPage/accreditation-sbmah.png') }}" alt="المجلس الصحي السعودي">
                </div>
                <div class="acc-text">الاعتماد المؤسسي سباهي<br>معتمد</div>
            </div>
            <div class="acc-card">
                <div class="acc-icon">
                    <img src="{{ asset('landingPage/accreditation-scfhs.png') }}" alt="الهيئة السعودية للتخصصات الصحية">
                </div>
                <div class="acc-text">الهيئة السعودية للتخصصات الصحية</div>
            </div>
            <div class="acc-card">
                <div class="acc-icon">
                    <img src="{{ asset('landingPage/accreditation-moh.png') }}" alt="وزارة الصحة">
                </div>
                <div class="acc-text">وزارة الصحة<br>Ministry of Health</div>
            </div>
            <div class="acc-card" style="flex-direction: row; gap: 8px;">
                <div class="acc-text" style="text-align: right;">المركز السعودي لاعتماد المنشآت الصحية<br>معتمد</div>
                <div class="acc-icon" style="margin:0;">
                    <img src="{{ asset('landingPage/accreditation-cbahi.png') }}" alt="المركز السعودي لاعتماد المنشآت الصحية">
                </div>
            </div>
            <div class="acc-card" style="flex-direction: row; gap: 8px;">
                <div class="acc-text" style="text-align: right;">نظام إدارة الجودة<br>معتمد</div>
                <div class="acc-icon" style="margin:0; flex-direction: column; align-items: center; justify-content: center;">
                    <img src="{{ asset('landingPage/accreditation-iso.png') }}" alt="ISO 9001:2015">
                </div>
            </div>
        </div>
    </div>

    <div class="portal-container contact-section">
        <div class="contact-grid">
            <div class="contact-card c-main">
                <div class="contact-icon">
                    <svg viewBox="0 0 24 24">
                        <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"></path>
                    </svg>
                </div>
                <div class="contact-info">
                    <div class="contact-label">الفرع الرئيسي (السويدي)</div>
                    <div class="contact-value">011 425 0000</div>
                </div>
                <div class="building-icon">
                    <svg viewBox="0 0 24 24">
                        <path d="M3 21h18"></path>
                        <path d="M9 8h1"></path>
                        <path d="M9 12h1"></path>
                        <path d="M9 16h1"></path>
                        <path d="M14 8h1"></path>
                        <path d="M14 12h1"></path>
                        <path d="M14 16h1"></path>
                        <path d="M5 21V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2v16"></path>
                    </svg>
                </div>
            </div>
            
            <div class="contact-card c-olaya">
                <div class="contact-icon">
                    <svg viewBox="0 0 24 24">
                        <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"></path>
                    </svg>
                </div>
                <div class="contact-info">
                    <div class="contact-label">فرع العليا</div>
                    <div class="contact-value">011 462 2000</div>
                </div>
            </div>
            
            <div class="contact-card c-unified">
                <div class="contact-icon">
                    <svg viewBox="0 0 24 24">
                        <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"></path>
                    </svg>
                </div>
                <div class="contact-info">
                    <div class="contact-label">الرقم الموحد</div>
                    <div class="contact-value">011 483 7777</div>
                    <div class="contact-sub">فرع الرائدة</div>
                </div>
            </div>
            
            <div class="contact-card c-email">
                <div class="contact-icon">
                    <svg viewBox="0 0 24 24">
                        <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path>
                        <polyline points="22,6 12,13 2,6"></polyline>
                    </svg>
                </div>
                <div class="contact-info">
                    <div class="contact-label">البريد الإلكتروني</div>
                    <div class="contact-value contact-value-email">info@al-hammadi.com</div>
                </div>
            </div>
        </div>
    </div>

    <div class="footer">
        <div class="portal-container footer-inner">
            <div class="footer-right">
                <div class="footer-mark">
                    <svg viewBox="0 0 60 100">
                        <rect x="0" y="30" width="12" height="40" rx="6" ry="6" />
                        <rect x="24" y="10" width="12" height="80" rx="6" ry="6" />
                        <rect x="48" y="20" width="12" height="60" rx="6" ry="6" />
                    </svg>
                </div>
                <div class="footer-copy">مستشفيات الحمادي 2026 © جميع الحقوق محفوظة</div>
            </div>
            <div class="footer-center">
                <span class="footer-link footer-link--unavailable" aria-disabled="true">سياسة الخصوصية</span>
                <div class="footer-sep"></div>
                <span class="footer-link footer-link--unavailable" aria-disabled="true">الشروط والأحكام</span>
                <div class="footer-sep"></div>
                <span class="footer-link footer-link--unavailable" aria-disabled="true">خريطة الموقع</span>
            </div>
            <div class="footer-left" aria-hidden="true"></div>
        </div>
    </div>

    <script>
        (function () {
            var hero = document.getElementById('heroSlider');
            if (!hero) return;

            var slides = hero.querySelectorAll('.hero-slide');
            var dotsBox = hero.querySelector('.hero-dots');
            if (!dotsBox || slides.length < 2) return;

            var prevBtn = hero.querySelector('.hero-side-arrow--prev');
            var nextBtn = hero.querySelector('.hero-side-arrow--next');
            prevBtn.addEventListener('click', function () { go(index - 1); restart(); });
            nextBtn.addEventListener('click', function () { go(index + 1); restart(); });
            prevBtn.hidden = false;
            nextBtn.hidden = false;

            var index = 0;
            var timer = null;
            var INTERVAL = 6000;

            slides.forEach(function (slide, i) {
                var dot = document.createElement('button');
                dot.type = 'button';
                dot.className = 'hero-dot' + (i === 0 ? ' is-active' : '');
                dot.setAttribute('aria-label', 'صورة الفرع ' + (i + 1));
                dot.addEventListener('click', function () {
                    go(i);
                    restart();
                });
                dotsBox.appendChild(dot);
            });

            var dots = dotsBox.querySelectorAll('.hero-dot');

            function go(i) {
                slides[index].classList.remove('is-active');
                dots[index].classList.remove('is-active');
                index = (i + slides.length) % slides.length;
                slides[index].classList.add('is-active');
                dots[index].classList.add('is-active');
            }

            function restart() {
                clearInterval(timer);
                timer = setInterval(function () { go(index + 1); }, INTERVAL);
            }

            restart();
        })();
    </script>
</body>
</html>
