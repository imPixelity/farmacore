<!doctype html>
<html lang="id">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <title>{{ $definition['title'] }}</title>
    <style>
        @page { margin: 24px 28px; }
        body { color: #1e293b; font-family: DejaVu Sans, sans-serif; font-size: 9px; }
        h1 { font-size: 18px; margin: 0 0 4px; }
        h2 { font-size: 12px; margin: 16px 0 6px; }
        p { margin: 3px 0; }
        table { border-collapse: collapse; width: 100%; }
        th, td { border: 1px solid #cbd5e1; padding: 5px; vertical-align: top; }
        th { background: #e2e8f0; text-align: left; }
        .metadata { margin: 12px 0; }
        .metadata td { border: 0; padding: 2px 10px 2px 0; }
        .summary { width: auto; }
        .summary td:first-child { font-weight: bold; }
        thead { display: table-header-group; }
        tr { page-break-inside: avoid; }
    </style>
</head>
<body>
    <h1>{{ $applicationName }}</h1>
    <p><strong>{{ $definition['title'] }}</strong> · {{ $definition['requirement'] }}</p>

    <table class="metadata">
        <tbody>
            <tr><td><strong>Periode</strong></td><td>{{ $period }}</td></tr>
            <tr><td><strong>Waktu cetak</strong></td><td>{{ $printedAt }}</td></tr>
            <tr><td><strong>Dibuat oleh</strong></td><td>{{ $createdBy }}</td></tr>
        </tbody>
    </table>

    @if ($summary !== [])
        <h2>Ringkasan</h2>
        <table class="summary">
            <tbody>
                @foreach ($summary as $card)
                    <tr>
                        <td>{{ $card['label'] }}</td>
                        <td>{{ $formatter->display($card['value'], $card['format']) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    <h2>{{ $definition['title'] }}</h2>
    <table>
        <thead>
            <tr>
                @foreach ($definition['columns'] as $column)
                    <th>{{ $column['label'] }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @forelse ($rows as $row)
                <tr>
                    @foreach ($definition['columns'] as $column)
                        <td>{{ $formatter->display(data_get($row, $column['key']), $column['format']) }}</td>
                    @endforeach
                </tr>
            @empty
                <tr>
                    <td colspan="{{ count($definition['columns']) }}">Tidak ada data laporan pada rentang tanggal ini.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
