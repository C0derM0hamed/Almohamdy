/**
 * Generates a polished Arabic RTL PDF client review guide.
 *
 * Usage: node scripts/generate-license-review-pdf.mjs
 */
import { chromium } from '@playwright/test';
import fs from 'fs';
import path from 'path';
import { fileURLToPath } from 'url';

const __dirname = path.dirname(fileURLToPath(import.meta.url));
const root = path.join(__dirname, '..');
const mdPath = path.join(root, 'docs/client-review/LICENSE_MANAGEMENT_CLIENT_REVIEW_AR.md');
const pdfPath = path.join(root, 'docs/client-review/LICENSE_MANAGEMENT_CLIENT_REVIEW_AR.pdf');
const verifyDir = path.join(root, 'docs/client-review/_pdf-verify');

function esc(s) {
  return s.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
}

function inlineFormat(s) {
  return esc(s)
    .replace(/\*\*([^*]+)\*\*/g, '<strong>$1</strong>')
    .replace(/\*([^*]+)\*/g, '<em>$1</em>')
    .replace(/`([^`]+)`/g, '<code>$1</code>');
}

function mdToHtml(md) {
  const lines = md.split('\n');
  let html = '';
  let inList = false;
  let inTable = false;
  let inStep = false;
  let stepBuffer = { title: '', body: '', image: null, expected: '' };

  function flushStep() {
    if (!inStep) return;
    const parts = [];
    if (stepBuffer.title) parts.push(`<h3 class="step-title">${stepBuffer.title}</h3>`);
    if (stepBuffer.body) parts.push(`<div class="step-body">${stepBuffer.body}</div>`);
    if (stepBuffer.image) parts.push(stepBuffer.image);
    if (stepBuffer.expected) parts.push(`<p class="expected"><strong>النتيجة المتوقعة:</strong> ${stepBuffer.expected}</p>`);
    html += `<section class="step-card">${parts.join('')}</section>\n`;
    inStep = false;
    stepBuffer = { title: '', body: '', image: null, expected: '' };
  }

  function closeList() {
    if (inList) { html += '</ul>\n'; inList = false; }
    inExpectedList = false;
  }
  function closeTable() {
    if (inTable) { html += '</table>\n'; inTable = false; tableHasHeader = false; }
  }

  let inExpectedList = false;
  let tableHasHeader = false;
  let coverDone = false;

  for (let i = 0; i < lines.length; i++) {
    const line = lines[i];

    if (line.startsWith('# ')) {
      flushStep(); closeList(); closeTable();
      if (!coverDone) {
        html += `<header class="doc-cover"><h1>${inlineFormat(line.slice(2))}</h1>`;
        coverDone = true;
      } else {
        if (html.includes('<header class="doc-cover">') && !html.includes('</header>')) {
          html += '</header>\n';
        }
        html += `<section class="part-header"><h2 class="part-title">${inlineFormat(line.slice(2))}</h2></section>\n`;
      }
      continue;
    }
    if (line.startsWith('## ')) {
      flushStep(); closeList(); closeTable();
      if (html.includes('<header class="doc-cover">') && !html.includes('</header>')) {
        html += '</header>\n';
      }
      const title = line.slice(3);
      if (/^الخطوة\s/.test(title)) {
        inStep = true;
        stepBuffer.title = inlineFormat(title);
      } else {
        html += `<section class="chapter"><h2>${inlineFormat(title)}</h2>\n`;
      }
      continue;
    }
    if (line.startsWith('### ')) {
      flushStep(); closeList(); closeTable();
      inStep = true;
      stepBuffer.title = inlineFormat(line.slice(4));
      continue;
    }
    if (line.startsWith('![')) {
      const m = line.match(/!\[([^\]]*)\]\(([^)]+)\)/);
      if (m) {
        const imgPath = path.resolve(path.dirname(mdPath), m[2]);
        if (fs.existsSync(imgPath)) {
          const b64 = fs.readFileSync(imgPath).toString('base64');
          const fig = `<figure class="screenshot"><img src="data:image/png;base64,${b64}" alt="${esc(m[1])}"/><figcaption>${esc(m[1])}</figcaption></figure>`;
          if (inStep) stepBuffer.image = fig;
          else html += fig + '\n';
        }
      }
      continue;
    }
    if (line.startsWith('**النتيجة المتوقعة:**')) {
      flushStep(); closeList(); closeTable();
      const rest = line.replace('**النتيجة المتوقعة:**', '').trim();
      if (rest) {
        html += `<p class="expected"><strong>النتيجة المتوقعة:</strong> ${inlineFormat(rest)}</p>\n`;
      } else {
        html += '<p class="expected"><strong>النتيجة المتوقعة:</strong></p>\n<ul class="expected-list">\n';
        inExpectedList = true;
        inList = true;
      }
      continue;
    }
    if (line.startsWith('|')) {
      flushStep(); closeList();
      if (!inTable) { html += '<table class="data-table">\n'; inTable = true; tableHasHeader = false; }
      const cells = line.split('|').filter((c) => c.trim());
      if (cells.every((c) => /^[-:]+$/.test(c.trim()))) continue;
      const isHeader = !tableHasHeader;
      if (isHeader) tableHasHeader = true;
      html += '<tr>' + cells.map((c) => `<${isHeader ? 'th' : 'td'}>${inlineFormat(c.trim())}</${isHeader ? 'th' : 'td'}>`).join('') + '</tr>\n';
      continue;
    }
    if (line.startsWith('- [ ]') || line.startsWith('- [x]')) {
      closeTable(); inExpectedList = false;
      if (!inList) { html += '<ul class="checklist">\n'; inList = true; }
      const checked = line.startsWith('- [x]');
      const item = line.replace(/^- \[[ x]\] /, '');
      html += `<li class="${checked ? 'checked' : ''}">${inlineFormat(item)}</li>\n`;
      continue;
    }
    if (line.startsWith('- ') || line.match(/^\d+\. /)) {
      closeTable();
      if (inExpectedList) {
        if (!inList) { html += '<ul class="expected-list">\n'; inList = true; }
      } else if (!inList && !inStep) {
        html += '<ul class="compact-list">\n';
        inList = true;
      }
      const item = line.startsWith('- ') ? line.slice(2) : line.replace(/^\d+\. /, '');
      const li = `<li>${inlineFormat(item)}</li>`;
      if (inStep) stepBuffer.body += li;
      else html += li + '\n';
      continue;
    }
    if (line.startsWith('> ')) {
      flushStep(); closeList(); closeTable();
      const content = line.slice(2);
      let cls = 'callout';
      if (content.includes('💡')) cls = 'callout callout--tip';
      else if (content.includes('❓')) cls = 'callout callout--why';
      else if (content.includes('⛔')) cls = 'callout callout--warn';
      html += `<blockquote class="${cls}">${inlineFormat(content)}</blockquote>\n`;
      continue;
    }
    if (line.trim() === '---') {
      flushStep(); closeList(); closeTable();
      html += '</section>\n';
      continue;
    }
    if (line.trim() === '') {
      continue;
    }
    if (line.startsWith('**الحساب:**')) {
      closeTable();
      html += `<p class="role-badge">${inlineFormat(line)}</p>\n`;
      continue;
    }
    if (line.startsWith('**لماذا')) {
      closeTable();
      const box = `<div class="why-box">${inlineFormat(line)}</div>`;
      if (inStep) stepBuffer.body += box;
      else html += box + '\n';
      continue;
    }
    if (line.startsWith('**أين في النظام')) {
      closeTable();
      const box = `<div class="where-box">${inlineFormat(line)}</div>`;
      if (inStep) stepBuffer.body += box;
      else html += box + '\n';
      continue;
    }
    if (line.startsWith('**ماذا تفعل')) {
      closeTable();
      const box = `<div class="action-box">${inlineFormat(line)}</div>`;
      if (inStep) stepBuffer.body += box;
      else html += box + '\n';
      continue;
    }
    if (line.startsWith('```')) {
      flushStep(); closeList(); closeTable();
      const codeLines = [];
      i++;
      while (i < lines.length && !lines[i].startsWith('```')) {
        codeLines.push(esc(lines[i]));
        i++;
      }
      html += `<pre class="flow-diagram">${codeLines.join('\n')}</pre>\n`;
      continue;
    }

    closeTable();
    const p = `<p>${inlineFormat(line)}</p>`;
    if (inStep && !stepBuffer.body.includes('<ul')) stepBuffer.body += p;
    else { flushStep(); html += p + '\n'; }
  }

  flushStep(); closeList(); closeTable();
  if (!html.includes('</header>') && html.includes('<header class="doc-cover">')) {
    html = html.replace('<header class="doc-cover">', '<header class="doc-cover">') + '</header>';
  }
  return html;
}

const md = fs.readFileSync(mdPath, 'utf8');
const body = mdToHtml(md);

const html = `<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8"/>
<link rel="preconnect" href="https://fonts.googleapis.com"/>
<link href="https://fonts.googleapis.com/css2?family=Noto+Sans+Arabic:wght@400;600;700&display=swap" rel="stylesheet"/>
<style>
  @page {
    size: A4;
    margin: 14mm 13mm 16mm 13mm;
  }
  * { box-sizing: border-box; }
  html, body {
    margin: 0; padding: 0;
    font-family: 'Noto Sans Arabic', 'Segoe UI', Tahoma, Arial, sans-serif;
    font-size: 11pt; line-height: 1.65;
    color: #0f172a; direction: rtl; text-align: right;
    background: #fff;
    -webkit-print-color-adjust: exact; print-color-adjust: exact;
  }

  .doc-cover {
    text-align: center;
    padding: 32px 20px 24px;
    margin-bottom: 12px;
    border-bottom: 3px solid #2563eb;
    background: linear-gradient(180deg, #f0f7ff 0%, #ffffff 100%);
    border-radius: 8px;
    page-break-after: avoid;
  }
  .doc-cover h1 {
    font-size: 21pt; font-weight: 700; color: #1e40af;
    margin: 0 0 10px; line-height: 1.35;
  }
  .doc-cover p { color: #475569; font-size: 10.5pt; margin: 6px 0; }

  .chapter {
    margin-top: 8px;
    page-break-inside: avoid;
  }
  .chapter > h2 {
    font-size: 13.5pt; font-weight: 700; color: #fff;
    background: linear-gradient(135deg, #2563eb 0%, #3b82f6 100%);
    padding: 10px 16px; margin: 16px 0 12px;
    border-radius: 8px;
    page-break-after: avoid;
    box-shadow: 0 1px 3px rgba(37,99,235,0.15);
  }

  .step-card {
    background: #ffffff;
    border: 1px solid #dbeafe;
    border-right: 5px solid #2563eb;
    border-radius: 8px;
    padding: 12px 14px;
    margin: 10px 0 12px;
    page-break-inside: avoid;
    box-shadow: 0 1px 4px rgba(15,23,42,0.04);
  }
  .step-title {
    font-size: 12pt; font-weight: 700; color: #1e40af;
    margin: 0 0 8px;
    padding-bottom: 6px;
    border-bottom: 1px dashed #bfdbfe;
  }
  .step-body { margin-bottom: 6px; }
  .step-body ul { margin: 4px 0; padding-right: 18px; }
  .step-body li { margin-bottom: 2px; }
  .step-body p { margin: 4px 0; }

  .screenshot {
    margin: 8px 0 4px;
    text-align: center;
    page-break-inside: avoid;
  }
  .screenshot img {
    max-width: 100%;
    max-height: 300px;
    width: auto;
    height: auto;
    object-fit: contain;
    border: 1px solid #94a3b8;
    border-radius: 6px;
    box-shadow: 0 2px 8px rgba(15,76,129,0.1);
  }
  .screenshot figcaption {
    font-size: 9pt; color: #334155;
    margin-top: 6px; font-weight: 600;
    line-height: 1.45;
  }

  .expected {
    background: #ecfdf5;
    border: 1px solid #a7f3d0;
    border-right: 4px solid #059669;
    padding: 8px 12px;
    margin: 8px 0 0;
    font-size: 10pt;
    border-radius: 0 6px 6px 0;
  }

  .why-box {
    background: #eff6ff;
    border: 1px solid #bfdbfe;
    border-right: 4px solid #3b82f6;
    padding: 8px 12px;
    margin: 6px 0;
    font-size: 10pt;
    border-radius: 0 6px 6px 0;
    color: #1e3a8a;
  }
  .where-box {
    background: #f5f3ff;
    border: 1px solid #ddd6fe;
    border-right: 4px solid #7c3aed;
    padding: 8px 12px;
    margin: 6px 0;
    font-size: 10pt;
    border-radius: 0 6px 6px 0;
    color: #4c1d95;
  }
  .action-box {
    background: #fff7ed;
    border: 1px solid #fed7aa;
    border-right: 4px solid #ea580c;
    padding: 8px 12px;
    margin: 6px 0;
    font-size: 10pt;
    font-weight: 600;
    border-radius: 0 6px 6px 0;
    color: #9a3412;
  }

  .data-table {
    width: 100%; border-collapse: collapse;
    margin: 8px 0 10px; font-size: 10pt;
    page-break-inside: avoid;
  }
  .data-table th, .data-table td {
    border: 1px solid #cbd5e1;
    padding: 7px 10px; text-align: right;
  }
  .data-table th { background: #dbeafe; font-weight: 700; color: #1e3a8a; }
  .data-table tr:nth-child(even) td { background: #f8fafc; }
  .part-header { margin: 18px 0 8px; page-break-before: auto; }
  .part-title {
    font-size: 16pt; font-weight: 700; color: #0f4c81;
    border-bottom: 2px solid #1565a8; padding-bottom: 6px; margin: 0;
  }

  .checklist { list-style: none; padding-right: 4px; margin: 8px 0; }
  .checklist li { padding: 4px 0; padding-right: 22px; position: relative; }
  .checklist li::before { content: '☐'; position: absolute; right: 0; color: #1565a8; font-weight: 700; }
  .checklist li.checked::before { content: '☑'; color: #059669; }

  .expected-list { margin: 4px 0 8px; padding-right: 20px; font-size: 9.5pt; }
  .data-table td code, .data-table th code { font-size: 8.5pt; }

  .callout {
    background: #fffbeb;
    border: 1px solid #fde68a;
    border-right: 4px solid #f59e0b;
    padding: 10px 14px;
    margin: 10px 0;
    font-size: 10pt;
    border-radius: 0 6px 6px 0;
    color: #78350f;
  }
  .callout--tip {
    background: #ecfdf5;
    border-color: #a7f3d0;
    border-right-color: #10b981;
    color: #065f46;
  }
  .callout--why {
    background: #eff6ff;
    border-color: #bfdbfe;
    border-right-color: #3b82f6;
    color: #1e3a8a;
  }
  .callout--warn {
    background: #fef2f2;
    border-color: #fecaca;
    border-right-color: #ef4444;
    color: #991b1b;
  }

  .role-badge {
    display: inline-block;
    background: #dbeafe;
    color: #1d4ed8;
    padding: 5px 12px;
    border-radius: 6px;
    font-size: 10pt;
    font-weight: 600;
    margin: 4px 0 8px;
    border: 1px solid #93c5fd;
  }

  .flow-diagram {
    background: #f1f5f9;
    border: 1px solid #cbd5e1;
    border-radius: 6px;
    padding: 10px 14px;
    font-size: 9pt;
    line-height: 1.6;
    direction: rtl;
    white-space: pre-wrap;
    page-break-inside: avoid;
  }

  code {
    background: #f1f5f9;
    padding: 1px 5px;
    border-radius: 3px;
    font-size: 9pt;
    direction: ltr;
    display: inline-block;
  }

  .compact-list {
    padding-right: 18px;
    margin: 6px 0;
  }
  .compact-list li { margin-bottom: 3px; }

  p { margin: 5px 0; }

  .doc-footer {
    text-align: center;
    color: #94a3b8;
    font-size: 9pt;
    margin-top: 16px;
    padding-top: 8px;
    border-top: 1px solid #e2e8f0;
  }
</style>
</head>
<body>
${body}
<p class="doc-footer">مستشفيات الحمادي — موديول إدارة التراخيص — دليل مراجعة العميل</p>
</body>
</html>`;

fs.mkdirSync(verifyDir, { recursive: true });

const browser = await chromium.launch();
const page = await browser.newPage();
await page.setContent(html, { waitUntil: 'networkidle' });

await page.pdf({
  path: pdfPath,
  format: 'A4',
  printBackground: true,
  margin: { top: '14mm', bottom: '16mm', left: '13mm', right: '13mm' },
  preferCSSPageSize: true,
});

// Visual verification: render sample pages
for (const pg of [1, 3, 6, 10]) {
  await page.evaluate((p) => {
    window.scrollTo(0, (p - 1) * 1122);
  }, pg);
  await page.screenshot({
    path: path.join(verifyDir, `page-${String(pg).padStart(2, '0')}.png`),
    fullPage: false,
  });
}

const pageCount = await page.evaluate(() => {
  const h = document.body.scrollHeight;
  return Math.ceil(h / 1122);
});

await browser.close();

console.log(`PDF written to ${pdfPath}`);
console.log(`Estimated pages: ${pageCount}`);
console.log(`Verification previews: ${verifyDir}/page-*.png`);
