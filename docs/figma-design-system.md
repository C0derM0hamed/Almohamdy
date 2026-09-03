# Figma design system — iSWZv3mAjNs7RjuhrnXtUl

Source file: https://www.figma.com/design/iSWZv3mAjNs7RjuhrnXtUl/Untitled?node-id=0-1  
Continuation account: cderm9703@gmail.com (`mcp__figma`)  
Extracted from live inventory plus targeted `get_design_context` calls for unique frames only.  
No published Figma variables in this file.

Dashboard, Doctors Directory, and Sidebar are already implemented. Do not restyle them from this kit.

## Top-level frames

| Frame | Node | App page | Pattern family |
|---|---|---|---|
| التواصل المؤسسي (أطباء العيادات) | `1:20` | Doctors Directory | Already implemented |
| مواقع و وقت الخدمات | `1:593` | Service locations | Header + search + action cards |
| استفسارات صادرة | `1:961` | Outgoing inquiries | Compact header + tabs + status KPIs + filters + table + pagination |
| استفسارات واردة | `1:1439` | Incoming inquiries | Same as `1:961` |
| كل الخدمات | `1:1925` | Hospital services dashboard | Same action cards as `1:593` |
| العيادات الخارجية | `1:2333` | Hospital section | Filter bar + package cards |
| التنويم | `1:2979` | Inpatient section | Same as `1:2333` |
| المراكز المتعاقدة الطبية | `1:3646` | Contracted centers | Same as `1:2333` |
| العيادات الخارجية (الشراكات) | `1:4346` | Partnerships / discounts | Unique % grid cards |
| خدمة التعاميم الحكومية | `1:4978` | Government circulars | Unique table toolbar |
| عيادات الباطنية والغدد | `132:7531` | Specialty overview | Unique description, units, and branch cards |
| النزهة | `136:7857` | Doctors in branch | Unique branch-context search and doctor cards |
| O.P.D 3 | `145:892` | OPD location detail | Unique duty facts and department grid |
| إضافة استفسار صادر | `142:8780` | Create outgoing inquiry | Unique inquiry form composition |
| تفاصيل التدريب | `147:1244` | Training detail | Unique status, employee, schedule, and document composition |

No mobile/responsive frames and no modal or empty-state frames exist in this file.

## Shared components (capture once)

| Component | Canonical node | Reused on | Local partial / CSS |
|---|---|---|---|
| Page header + breadcrumbs + hero | `1:600` | `1:20`, `1:593`, `1:1925`, `1:2333`, `1:2979`, `1:3646`, `1:4346` | `layouts.partials.figma-module-header` / `.fm-head` |
| Compact header (tools + crumbs only) | `1:968` | `1:961`, `1:1439`, `1:4978` | same partial with `compact` |
| Header tools | `1:602` / `1:970` | every page | `layouts.partials.figma-header-tools` |
| Hero icon 56×56 r16 | `1:634` | all full headers | `.fm-hero__icon` |
| Light KPI / stat card | `1:69` | Doctors `1:20` only in this file | already in doctors CSS |
| Filled status KPI | `1:1021` | `1:961`, `1:1439` | `layouts.partials.figma.stat-status` / `.fm-stat--fill` |
| Search bar (query + بحث) | `1:643` | `1:593`, `1:1925` | `layouts.partials.figma.search-bar` / `.fm-search` |
| Filter bar (fields + بحث + reset) | `1:2381` | `1:20`, `1:2333`, `1:2979`, `1:3646`, `1:4346` | same search-bar + `.fm-field` |
| Multi-field filter row | `1:1072` | `1:961`, `1:1439` | `.fm-field` + `.fm-input` |
| Action / service card | `1:670` | `1:593`, `1:1925`, doctors specialty cards | `layouts.partials.figma-action-card` / `.fm-card` |
| Section head + count chip | `1:655` | listing pages above | `layouts.partials.figma.section-head` |
| Primary search button 80×52 | `1:647` | all search bars | `.fm-btn--search` |
| Reset button 134×52 | `1:2386` | filter bars | `.fm-btn--reset` |
| CTA button 46× r12 `#625ceb` | `1:1001` | inquiries, circulars | `.fm-btn--cta` |
| Card CTA (tint → primary hover) | `1:687` / hover `1:729` | action cards | `.fm-card__action` |
| Tabs | `1:1009` | `1:961`, `1:1439` | `layouts.partials.figma.tabs` / `.fm-tabs` |
| Table | `1:1128` | `1:961`, `1:1439` | `.fm-table-wrap` |
| Table action / dept badge | `1:1145` / `1:1163` | inquiry tables | `layouts.partials.figma.badge` |
| Avatar 40px | `1:1170` | inquiry tables | `.fm-avatar` |
| Pagination | `1:1272` | `1:961`, `1:1439`, section pages | `layouts.partials.figma.pagination` |
| Inputs / selects / dates | `1:1080` / `1:1084` / `1:1096` | filters | `.fm-input` height 52, radius 16, border `#e7e6ee` |
| Package / service detail card | `1:2424` | `1:2333`, `1:2979`, `1:3646` | `layouts.partials.figma.package-card` / `.fm-pkg` |
| Icon containers | `1:634`, `1:674`, `1:661` | headers, cards, sections | 56/44/40 squares |

Not in this Figma file: modals, empty states, textareas, dedicated hover/focus frames, mobile variants.

## Shared tokens

See `public/css/hm-figma-tokens.css`.

| Token | Value |
|---|---|
| Font | Noto Kufi Arabic (Bold / SemiBold / Medium / Regular) |
| Page | `#f7f8fc` |
| Primary | `#6366f1` |
| CTA | `#625ceb` |
| Deep | `#5b3fe4` |
| Dark | `#302a58` |
| Ink | `#171827` |
| Title | `#171521` / heading `#29264f` |
| Muted | `#64748b` / `#8c8a98` |
| Lines | `#e7e6ee` inputs, `#e8edf4` tools, `#e9e8f1` tabs/table |
| Soft / tint / chip | `#f1edfe` / `#f5f2ff` / `#eeeafe` |
| Danger avatar | `#e46a6a` |
| Shadow | `0 8px 15px rgba(40,30,80,0.05)` |
| Radii | 12 tools, 16 inputs/buttons, 20 cards, 24 search, pill badges |
| Input | 52px tall |
| Search btn | 80×52 |
| Reset | 134×52 |
| Tool btn | 40×40 r12 |
| Hero icon | 56×56 r16 |
| Content width | 1142px, 3×370 cards, 16px gap |

### Interaction (from static states in frames, not separate variants)

| State | Evidence | Value |
|---|---|---|
| Card CTA default | `1:687` | bg `#f5f2ff`, text `#5b3fe4` |
| Card CTA hover/active | `1:729` | bg `#6366f1`, text white |
| Tab inactive | `1:1010` | transparent, text `#29264f` |
| Tab active | `1:1015` | bg `#625ceb`, white, shadow `0 4px 6px rgba(98,92,235,0.28)` |
| Input focus | not drawn | use border `#6366f1` |
| Pager current | `1:1278` | 36×36 `#625ceb` white |
| Pager prev/next | `1:1274` | white, border `#e9e8f1`, text `#64748b` |

## Local files created

- `public/css/hm-figma-tokens.css`
- `public/css/hm-figma-system.css`
- `docs/figma-design-system.md`
- `resources/views/layouts/partials/figma/search-bar.blade.php`
- `resources/views/layouts/partials/figma/section-head.blade.php`
- `resources/views/layouts/partials/figma/stat-status.blade.php`
- `resources/views/layouts/partials/figma/tabs.blade.php`
- `resources/views/layouts/partials/figma/badge.blade.php`
- `resources/views/layouts/partials/figma/pagination.blade.php`
- `resources/views/layouts/partials/figma/package-card.blade.php`
- `public/images/figma/system/pkg-{more,clock,desc,note,arrow,eye}.svg`
- `public/css/hm-figma-workflows.css`
- `public/images/figma/workflows/absence-bell.svg`

Already existed (keep using):

- `figma-module-header.blade.php`
- `figma-action-card.blade.php`
- `figma-header-tools.blade.php`
- `public/css/hm-figma-header.css`, `hm-figma-sidebar.css`, `hm-figma-module.css`

## What can be implemented from this kit

Mostly shared system:

- مواقع و وقت الخدمات (`1:593`)
- كل الخدمات (`1:1925`)
- استفسارات صادرة / واردة (`1:961` / `1:1439`)
- العيادات الخارجية / التنويم / المراكز المتعاقدة (`1:2333` / `1:2979` / `1:3646`) — layout + package card chrome; per-card hero icons still need export at migration time

## Still needs direct Figma access

- الشراكات والخصومات (`1:4346`) — unique 2×2 percent grid body, not the package card
- التعاميم الحكومية (`1:4978`) — unique column/export/sort toolbar and table density
- Per-page hero/card SVGs (do not reuse Bootstrap Icons as substitutes)
- Dashboard (not in this file)
- Any future modal / empty / mobile frame

## Remaining workflow migration

The top-level workflow frames after corporate communications now share the
`.hm-workflow` family. It is applied to work-absence notification and
management, leave requests, training management/coordination, complaints,
technical failures, publications, settings, emergency follow-up, transfers,
admission pricing, legal claims, employee requests, system overview, users,
and the generic reference-data screens.

The generic reference screen covers the repeated Figma frames for complaint
references, medical terminology, service codes, publication types, job titles,
government-service types, and company groups without duplicating markup or
CSS. Training management and coordination likewise use one Blade template.

### 2026-08-21 continuation checkpoint

To preserve the Figma Starter allowance, the continuation skipped every page
already completed after Corporate Communication and fetched design context
only for the five unique frames listed above. All five are now implemented.
Shared header, tool, location-card, search, and token assets were reused once;
only page-specific SVGs were downloaded. The OPD detail required no new asset
download because its hero, section, card, and arrow assets already existed.

Do not request these five nodes again unless the Figma source changes. For a
future continuation, inventory only top-level frames added after `147:1244`,
then fetch context for layouts that cannot be composed from the shared system.
