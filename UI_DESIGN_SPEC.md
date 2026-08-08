# UI Design Spec — Al Hammadi Hospitals Portal Landing Page

## 0. Authority & Non-negotiables

1. **`public/landingPage/mainDesgin.png`** (note: filename is spelled `mainDesgin.png` on disk) is the FINAL visual authority. If this spec and the PNG disagree, the PNG wins.
2. **`public/landingPage/hero.png`** MUST be used as the actual hero image (URL: `/landingPage/hero.png`).
3. **`public/landingPage/logo.png`** MUST be used as the actual logo (URL: `/landingPage/logo.png`).
4. `mainDesgin.png` is reference/QA only — it must NOT appear in the rendered page.
5. Assets must NOT be regenerated, moved, renamed, duplicated, or visually re-created.
6. Native RTL is mandatory: `<html dir="rtl" lang="ar">`.
7. Desktop pixel accuracy first (reference frame 1536×1024, treat as 1536px-wide desktop viewport); responsive adaptation second, never degrading desktop.
8. This is an INTERNAL portal entry page. NO navbar, appointment booking, doctors, marketing CTAs, search, or any section not in the reference.
9. Do not commit.

## 1. Reference frame & global system

- Reference canvas: **1536 × 1024 px**. All coordinates below are in this frame.
- Content max-width: ~1344px (side margins ≈ 96px at 1536).
- Font: Arabic-first sans (use an existing project Arabic font if present; otherwise "IBM Plex Sans Arabic", "Cairo", or "Tajawal" from a locally available/self-hosted source — pick what best matches the reference's rounded modern Arabic look). Latin text (emails/phones) renders in the same family.
- Page background: **#FEFEFE / #FFFFFF** (white).

### Palette
| Token | Value | Usage |
|---|---|---|
| navy-900 | `#012D79` → `#02317B` | hero right-side solid/gradient, footer copyright text |
| navy-800 | `#002C75` | hero deep blue |
| blue-600 | `#195CD0` | icons, accents |
| blue-100 | `#DDEAFE` / `#E8F0FE` | icon circle fills |
| ink | `#0B2A6B`–`#011671` | headings/titles on white (dark navy, NOT black) |
| ink-muted | `#5A6B96`-ish | card subtitle text |
| bg-card | `#FFFFFF` | cards |
| border-soft | `#E6EAF5` | accreditation/contact card borders |
| footer-bg | `#F1F4FC` | footer band |
| white-soft | `#D1DAF0` / `#BCC6E2` | hero subtitle text (light blue-white) |

Shadows: cards use a soft diffuse shadow, approx `0 12px 30px rgba(1,45,121,.08)`.

## 2. Hero (y ≈ 0 → 430, full-bleed width)

- Height ≈ **430px** at 1536 wide (≈ 28vw; keep ~420–440px on desktop).
- Composition: `hero.png` (2172×724 night hospital photo, subject on its left half) anchored to the **left**, covering roughly the left 60% of the band; it blends smoothly into a **solid deep blue (#012D79 → #02317B)** field on the right ~40%. Implement with the image as background (`background-position: left center; background-size: cover` on a left layer) plus a left-to-right gradient overlay `linear-gradient(to right, transparent 35%, rgba(1,45,121,.85) 60%, #012D79 75%)` so the right side reads as flat navy. hero.png itself already fades to blue on its right — lean on that; the overlay just guarantees a seamless field for the text.
- Subtle decorative petal/flower watermark on the far right edge (very low-opacity lighter blue, ~5–8% white). Reproduce with a simple inline SVG petal motif at `opacity:.06`, right edge, vertically spanning most of the hero. Low priority; keep subtle.
- **Logo**: `logo.png` at top-right, right margin ≈ 70px, top ≈ 30px, rendered ≈ **265×75px** (keep natural 376:106 ratio). It sits on the navy field. Note: logo.png has a white/light background baked in? No — it is transparent-style with dark-blue text; on the navy hero the reference shows the WHITE version of the wordmark. If logo.png renders illegibly on navy, apply `filter: brightness(0) invert(1)` to render it white — do not replace the asset. Match the reference: white wordmark + white icon on navy.
- **H1** «بوابة مستشفيات الحمادي»: white, bold (~700), font-size ≈ **48px**, right-aligned, positioned in the right column — right edge aligned near x≈1420 (same right margin as logo), baseline ≈ y 175. Letter-spacing normal.
- **Subtitle** (2 lines): «الوصول السريع إلى الأنظمة الداخلية والتعاميم» / «واللوائح والإجراءات والسياسات» — color light blue-white (#D1DAF0), ~20–22px, line-height ≈ 1.7, right-aligned under H1, y ≈ 215–290.
- **Shield ornament** at y ≈ 335, horizontally centered under the subtitle block (x ≈ 1130): a small outlined shield-check icon (~28px, white, stroke style) flanked by two thin horizontal lines (~60px each, rgba(255,255,255,.35)) with ~16px gaps. Inline SVG.

## 3. Portal cards (3 cards, overlapping the hero)

- Row top y ≈ **405** → cards overlap the hero bottom by ~25px (`margin-top: -25px` relative to hero end; cards sit above hero via z-index).
- 3 equal cards, total row width ≈ 1344 (96px side margins), **gap ≈ 30px**, each card ≈ **428 × 170px**.
- Card: white, **border-radius ≈ 16px**, soft shadow (see palette), no border.
- RTL order (rightmost first): **التعاميم**, **اللوائح والأنظمة**, **الإجراءات**.
- Internal layout (RTL, padding ≈ 28px 32px), three horizontal zones:
  1. **Icon circle** on the inline-start (right) side: ≈ **90px** diameter, fill `#DDEAFE` (subtle radial lighter center), containing a blue-600 stroked icon ≈ 38px:
     - التعاميم → envelope/mail icon
     - اللوائح والأنظمة → document with shield-check icon
     - الإجراءات → clipboard with checklist icon
  2. **Text block** center-right, right-aligned:
     - Title: ~24px, bold, ink navy. (التعاميم / اللوائح والأنظمة / الإجراءات)
     - Subtitle: ~15px, ink-muted, up to 2 lines:
       - التعاميم: «جميع التعاميم والإشعارات الرسمية»
       - اللوائح والأنظمة: «اللوائح والأنظمة والسياسات الداخلية»
       - الإجراءات: «دليل الإجراءات والسياسات المعتمدة»
  3. **Chevron affordance** on the inline-end (left) side, vertically centered: outlined circle ≈ 36px, 1px border `#D8E0F0`, containing a left-pointing chevron `‹` (blue-600, ~14px) — points left because RTL "forward".
- Whole card is a link (targets: existing internal routes if obvious — e.g. circulars/regulations/procedures modules — otherwise `#`; do NOT invent new pages).
- Hover: slight lift (translateY(-3px), shadow deepens). Keep subtle.

## 4. Accreditation section («الاعتمادات والشهادات»)

- Section heading centered at y ≈ **622**: text ~22px bold, ink navy, flanked left+right by short decorative dashes (two thin lines ~40px, color `#B9C6E8`, with small gap) — mirror of hero ornament without the shield.
- Below: **6 logo cards** in one row, y ≈ 648–748. Row width 1344, gap ≈ 14px, each card ≈ **213 × 100px**, white, border `1px solid #E6EAF5`, radius ≈ 12px, minimal/no shadow.
- Contents (RTL visual order right → left as in reference):
  1. (rightmost) **ISO 9001:2015** — "ISO" wordmark w/ globe (navy) + «نظام إدارة الجودة» + «معتمد»
  2. **CBAHI** — green/orange CBAHI mark + «المركز السعودي لاعتماد المنشآت الصحية» + «معتمد»
  3. **وزارة الصحة** — green MoH palm emblem + «وزارة الصحة / Ministry of Health»
  4. **الهيئة السعودية للتخصصات الصحية** — navy circular emblem
  5. **الاعتماد المؤسسي سباهي** — gold medal + text
  6. (leftmost) **Accreditation Canada** — red star + "ACCREDITATION CANADA" + "Accredited"

  ⚠️ Wait — in the PNG the order left→right is ISO, CBAHI, MoH, SCFHS, سباهي, Accreditation Canada. In RTL DOM, list them so the rendered visual order matches the PNG exactly (i.e., DOM order: Accreditation Canada first … ISO last, OR use LTR flex order — verify visually against the PNG; the PNG wins).
- No official logo asset files are provided for these six marks. Recreate each as a lightweight inline SVG/text approximation matching color, silhouette, and text (navy ISO text, CBAHI green/orange ring, green palm, navy circle emblem, gold coin gradient, red star). Prioritize correct text and color; the marks only need to read correctly at ~40–48px height.

## 5. Contact row

- 4 cards in one row, y ≈ **775–878**, gap ≈ 16px, widths (reference, right→left): الفرع الرئيسي ≈ 410px, فرع العليا ≈ 265px, الرقم الموحد ≈ 288px, البريد الإلكتروني ≈ 316px (flexible: use flex with those approximate ratios). Height ≈ **104px**, white, border `1px solid #E6EAF5`, radius ≈ 14px, very soft shadow.
- Card anatomy (RTL): icon circle ≈ 60px (`#EEF4FE` fill, blue-600 stroked icon ~24px) at inline-start (right), then right-aligned text.
  1. **الفرع الرئيسي (السويدي)** — phone icon; label ~16px ink; number **011 425 0000** bold ~20px navy, LTR digits; this card ALSO has a second outlined building icon circle at its far left end (~56px, outlined `#E1E8F6`).
  2. **فرع العليا** — phone icon; number **011 462 2000**.
  3. **الرقم الموحد** — phone icon; label «الرقم الموحد»; number **011 483 7777**; small third line «فرع الرائدة» ~13px muted.
  4. **البريد الإلكتروني** — envelope icon; label; **info@al-hammadi.com** bold ~18px navy, LTR.
- Numbers/emails use `direction:ltr; unicode-bidi:isolate` so digits render in the shown order.

## 6. Footer

- Band y ≈ **905 → 1024** (height ≈ 120px), background **#F1F4FC**, full-bleed, no top border (soft contrast only).
- Single row, vertically centered, 96px side padding, three zones (RTL):
  1. **Right**: logo **icon mark only** (the pill/bars glyph) in navy ~34px — recreate as tiny inline SVG matching logo.png's mark (do not crop the asset file; a faithful inline SVG mark is acceptable here since the full logo.png includes the wordmark) — followed by text «مستشفيات الحمادي  2026 © جميع الحقوق محفوظة» ~15px navy. Match PNG arrangement: text right-aligned with mark to its left… (PNG shows mark at far right, text to its left — follow the PNG.)
  2. **Center**: 3 links separated by thin vertical bars (`#C9D3EA`, ~18px tall): «سياسة الخصوصية» «الشروط والأحكام» «خريطة الموقع» — ~15px navy, plain (no underline). Links are placeholders (`#`) unless real routes exist.
  3. **Left**: 5 social icons, each a **44px** circle, `1px solid #D5DEF2` white fill, navy glyph ~18px, gap ≈ 14px; order left→right in PNG: linkedin, instagram, youtube, X, facebook. Placeholder `#` hrefs.

## 7. Responsive

Desktop (≥1280) must match the PNG. Below that, adapt without breaking desktop:
- ~1024: portal cards stay 3-up (shrink), accreditation 3×2 grid, contact 2×2.
- ~768: portal cards stack 1-per-row (full width), accreditation 2×3, contact 1-col, footer wraps to stacked zones (copyright / links / social), hero height ~360 with text block still right-aligned, photo left.
- ≤480: hero text ~32px H1; everything single column; hero keeps image + navy gradient.
- No horizontal overflow at any width. Hero image never distorts (cover crop only).

## 8. Integration constraints

- Find the appropriate existing entry point: the app root `/` currently shows the login screen (`routes/web.php:115`). Add/adjust a landing route/view per existing project conventions (Blade, `resources/views/…`) WITHOUT breaking login or dashboard behavior and WITHOUT duplicating pages. If a public "landing" view exists, modify it; otherwise create one Blade view + one route (e.g. `/portal` or making it the guest root — prefer the least invasive option consistent with the codebase).
- Plain Blade + a dedicated CSS file under `public/css/` (project convention: standalone CSS files like `hm-bankdash-theme.css`). No build-step dependency, no CDN if the project is offline-first — check existing layouts for how fonts/assets are loaded and follow that convention.
- Page `<title>`: «بوابة مستشفيات الحمادي».
- All text exactly as written in the PNG (copy strings from this spec §2–6).
