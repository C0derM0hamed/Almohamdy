<!DOCTYPE html>
<html lang="{{ $locale }}" dir="{{ $isRtl ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8"/>
    <title>{{ __('inquiries.pdf.title', ['id' => $inquiry->id]) }}</title>
    <style>
        body {
            font-family: {{ $fontFamily ?? 'xbriyaz' }}, dejavusans, sans-serif;
            font-size: 11pt;
            color: #232d42;
            line-height: 1.6;
        }

        h1 {
            font-size: 18pt;
            font-weight: bold;
            margin: 0 0 6px;
        }

        .subtitle {
            color: #64748b;
            margin: 0 0 18px;
            font-size: 10pt;
        }

        .section-title {
            font-size: 13pt;
            font-weight: bold;
            margin: 22px 0 10px;
            padding-bottom: 4px;
            border-bottom: 1px solid #e2e8f0;
        }

        table.info,
        table.timeline-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 8px;
        }

        table.info th,
        table.info td,
        table.timeline-table th,
        table.timeline-table td {
            border: 1px solid #dbe3ef;
            padding: 8px 10px;
            vertical-align: top;
        }

        table.info th,
        table.timeline-table th {
            background: #f8fafc;
            font-weight: bold;
            width: 32%;
        }

        .status-badge {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 10px;
            background: #eef2f7;
        }

        .footer {
            margin-top: 24px;
            font-size: 9pt;
            color: #94a3b8;
            text-align: center;
        }
    </style>
</head>
<body>
    <h1>{{ __('inquiries.pdf.heading') }}</h1>
    <p class="subtitle">{{ $pdfReference }}</p>

    <div class="section-title">{{ __('inquiries.pdf.inquiry_details') }}</div>
    <table class="info">
        <tr>
            <th>{{ __('inquiries.form_fields.enquirer') }}</th>
            <td>{{ $enquirerLabel }}</td>
        </tr>
        <tr>
            <th>{{ __('inquiries.form_fields.mobile') }}</th>
            <td>{{ $inquiry->mobile ?: '—' }}</td>
        </tr>
        <tr>
            <th>{{ __('inquiries.form_fields.department') }}</th>
            <td>{{ $departmentLabel }}</td>
        </tr>
        <tr>
            <th>{{ __('inquiries.form_fields.inquiry_type') }}</th>
            <td>{{ $inquiryTypeLabel }}</td>
        </tr>
        <tr>
            <th>{{ __('inquiries.form_fields.details') }}</th>
            <td>{{ $detailsLabel }}</td>
        </tr>
        <tr>
            <th>{{ __('inquiries.form_fields.date') }}</th>
            <td>{{ $inquiry->formattedDate() }}</td>
        </tr>
        <tr>
            <th>{{ __('inquiries.form_fields.status') }}</th>
            <td><span class="status-badge">{{ $statusLabel }}</span></td>
        </tr>
        <tr>
            <th>{{ __('inquiries.pdf.created_by') }}</th>
            <td>{{ $createdByLabel }}</td>
        </tr>
        <tr>
            <th>{{ __('inquiries.pdf.sender_branch') }}</th>
            <td>{{ $senderBranchLabel }}</td>
        </tr>
    </table>

    <div class="section-title">{{ __('inquiries.timeline') }}</div>
    @if (count($timeline) > 0)
        <table class="timeline-table">
            <thead>
                <tr>
                    <th>{{ __('inquiries.pdf.timeline_columns.date') }}</th>
                    <th>{{ __('inquiries.pdf.timeline_columns.time') }}</th>
                    <th>{{ __('inquiries.pdf.timeline_columns.user') }}</th>
                    <th>{{ __('inquiries.pdf.timeline_columns.department') }}</th>
                    <th>{{ __('inquiries.pdf.timeline_columns.action') }}</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($timeline as $event)
                    <tr>
                        <td>{{ $event['date'] }}</td>
                        <td>{{ $event['time'] }}</td>
                        <td>{{ $event['actor_name'] }}</td>
                        <td>{{ $event['department'] }}</td>
                        <td>{{ $event['message'] }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @else
        <p>{{ __('inquiries.no_timeline') }}</p>
    @endif

    <div class="footer">{{ __('dashboard.footer_tagline') }}</div>
</body>
</html>
