<!doctype html>
<html lang="{{ app()->getLocale() }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
<head>
<meta charset="utf-8">
<title>Licenses Report</title>
<style>
@page{margin:8mm;size:A4 landscape}
body{font-family:DejaVu Sans,sans-serif;color:#203449;font-size:8px;line-height:1.35}
h1{color:#18334f;font-size:16px;margin:0 0 8px}
table{width:100%;max-width:100%;border-collapse:collapse;table-layout:fixed}
th,td{border:1px solid #ccd9e3;padding:4px;vertical-align:top;overflow-wrap:anywhere;word-wrap:break-word;text-align:start}
th{background:#18334f;color:#fff}
tr:nth-child(even) td{background:#f5f8fa}
</style>
</head>
<body>
<h1>{{ $title }}</h1>
<p>{{ __('licenses.pdf.generated_at') }}: {{ now()->format('Y-m-d H:i') }}</p>
<table>
<thead><tr>@foreach($headers as $header)<th>{{ $header }}</th>@endforeach</tr></thead>
<tbody>
@forelse($rows as $row)
<tr>@foreach($row as $value)<td>{{ $value }}</td>@endforeach</tr>
@empty
<tr><td colspan="{{ count($headers) }}">{{ __('licenses.empty') }}</td></tr>
@endforelse
</tbody>
</table>
</body>
</html>
