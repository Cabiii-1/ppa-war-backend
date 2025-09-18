<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Weekly Accomplishment Report</title>
    <style>
        @page {
            margin: 0.5in;
            @top-center {
                content: "Weekly Accomplishment Report";
            }
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Times New Roman', serif;
            font-size: 11pt;
            line-height: 1.4;
            color: #000;
            background: white;
        }

        .header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 2px solid #000;
            padding-bottom: 15px;
        }

        .logo {
            width: 80px;
            height: 80px;
            margin: 0 auto 10px;
            background: url('data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iODAiIGhlaWdodD0iODAiIHZpZXdCb3g9IjAgMCA4MCA4MCIgZmlsbD0ibm9uZSIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj4KPGNpcmNsZSBjeD0iNDAiIGN5PSI0MCIgcj0iMzgiIHN0cm9rZT0iIzAwMCIgc3Ryb2tlLXdpZHRoPSI0Ii8+Cjx0ZXh0IHg9IjQwIiB5PSI0NSIgdGV4dC1hbmNob3I9Im1pZGRsZSIgZm9udC1mYW1pbHk9IlRpbWVzIiBmb250LXNpemU9IjE0IiBmb250LXdlaWdodD0iYm9sZCI+UEhMPC90ZXh0Pgo8L3N2Zz4K') no-repeat center;
            background-size: contain;
        }

        .agency-name {
            font-size: 14pt;
            font-weight: bold;
            margin: 5px 0;
            text-transform: uppercase;
        }

        .department-name {
            font-size: 12pt;
            margin: 3px 0;
        }

        .report-title {
            font-size: 16pt;
            font-weight: bold;
            margin: 15px 0 5px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .report-period {
            font-size: 12pt;
            margin-bottom: 10px;
        }

        .employee-info {
            margin: 20px 0;
            border: 1px solid #000;
            padding: 15px;
            background: #f8f9fa;
        }

        .employee-info table {
            width: 100%;
            border-collapse: collapse;
        }

        .employee-info td {
            padding: 8px;
            border: none;
            vertical-align: top;
        }

        .employee-info .label {
            font-weight: bold;
            width: 25%;
        }

        .accomplishment-table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
            font-size: 10pt;
        }

        .accomplishment-table th,
        .accomplishment-table td {
            border: 1px solid #000;
            padding: 8px;
            text-align: left;
            vertical-align: top;
        }

        .accomplishment-table th {
            background: #e9ecef;
            font-weight: bold;
            text-align: center;
            font-size: 10pt;
        }

        .accomplishment-table .date-col {
            width: 12%;
        }

        .accomplishment-table .ppa-col {
            width: 35%;
        }

        .accomplishment-table .kpi-col {
            width: 25%;
        }

        .accomplishment-table .status-col {
            width: 15%;
        }

        .accomplishment-table .remarks-col {
            width: 13%;
        }

        .signature-section {
            margin-top: 40px;
            page-break-inside: avoid;
        }

        .signature-row {
            display: flex;
            justify-content: space-between;
            margin: 30px 0;
        }

        .signature-block {
            width: 45%;
            text-align: center;
        }

        .signature-line {
            border-bottom: 1px solid #000;
            height: 40px;
            margin-bottom: 5px;
        }

        .signature-label {
            font-size: 10pt;
            font-weight: bold;
        }

        .signature-title {
            font-size: 9pt;
            font-style: italic;
            margin-top: 3px;
        }

        .footer {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            text-align: center;
            font-size: 8pt;
            color: #666;
            border-top: 1px solid #ccc;
            padding: 10px;
        }

        .status-completed {
            background-color: #d4edda;
            color: #155724;
        }

        .status-ongoing {
            background-color: #fff3cd;
            color: #856404;
        }

        .status-delayed {
            background-color: #f8d7da;
            color: #721c24;
        }

        .text-center {
            text-align: center;
        }

        .text-right {
            text-align: right;
        }

        .mb-2 {
            margin-bottom: 10px;
        }

        .no-entries {
            text-align: center;
            padding: 30px;
            font-style: italic;
            color: #666;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="logo"></div>
        <div class="agency-name">REPUBLIC OF THE PHILIPPINES</div>
        <div class="department-name">PROVINCIAL GOVERNMENT OF CATANDUANES</div>
        <div class="department-name">Planning and Development Office</div>
        <div class="report-title">Weekly Accomplishment Report</div>
        <div class="report-period">
            Period: {{ $report->period_start->format('F j, Y') }} to {{ $report->period_end->format('F j, Y') }}
        </div>
    </div>

  

    <table class="accomplishment-table">
        <thead>
            <tr>
                <th class="date-col">Date</th>
                <th class="ppa-col">Physical Program Activity (PPA)</th>
                <th class="kpi-col">Key Performance Indicator (KPI)</th>
                <th class="status-col">Status</th>
                <th class="remarks-col">Remarks</th>
            </tr>
        </thead>
        <tbody>
            @if($entries && $entries->count() > 0)
                @foreach($entries as $entry)
                    <tr>
                        <td class="text-center">{{ $entry->entry_date->format('M j, Y') }}</td>
                        <td>{{ $entry->ppa ?? 'N/A' }}</td>
                        <td>{{ $entry->kpi ?? 'N/A' }}</td>
                        <td class="text-center status-{{ strtolower($entry->status) }}">
                            {{ ucfirst($entry->status ?? 'N/A') }}
                        </td>
                        <td>{{ $entry->remarks ?? '-' }}</td>
                    </tr>
                @endforeach

                @php
                    $totalEntries = $entries->count();
                    $completedEntries = $entries->where('status', 'completed')->count();
                    $ongoingEntries = $entries->where('status', 'ongoing')->count();
                    $delayedEntries = $entries->where('status', 'delayed')->count();
                @endphp

                <tr style="border-top: 2px solid #000; background-color: #f8f9fa;">
                    <td class="text-center" style="font-weight: bold;">SUMMARY</td>
                    <td colspan="2" style="font-weight: bold;">
                        Total Activities: {{ $totalEntries }}
                    </td>
                    <td class="text-center" style="font-weight: bold;">
                        C: {{ $completedEntries }} | O: {{ $ongoingEntries }} | D: {{ $delayedEntries }}
                    </td>
                    <td style="font-weight: bold;">
                        {{ $totalEntries > 0 ? round(($completedEntries / $totalEntries) * 100, 1) : 0 }}% Complete
                    </td>
                </tr>
            @else
                <tr>
                    <td colspan="5" class="no-entries">
                        No entries recorded for this reporting period.
                    </td>
                </tr>
            @endif
        </tbody>
    </table>

    <div class="signature-section">
        <div class="signature-row">
            <div class="signature-block">
                <div class="signature-line"></div>
                <div class="signature-label">{{ $employee->name ?? 'Employee Name' }}</div>
                <div class="signature-title">Employee / Reporting Officer</div>
                <div class="signature-title">Date: _________________</div>
            </div>
     
        </div>

 

 
</body>
</html>