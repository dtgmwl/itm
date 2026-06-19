<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Laporan Kegiatan {{ \Carbon\Carbon::createFromDate($year, (int) $month, 1)->format('F') }} {{ $year }}</title>
    <style>
        body { font-family: 'Inter', 'DejaVu Sans', sans-serif; font-size: 9px; }
        h2 { margin: 0 0 4px; font-family: 'Inter', 'DejaVu Sans', sans-serif; }
        .sub { color: #555; margin-bottom: 16px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #374151; padding: 4px 6px; text-align: left; vertical-align: top; }
        th { background: #e5e7eb; font-weight: 700; font-size: 9px; }
        .text-center { text-align: center; }
        .libur { color: #e11d48; font-style: italic; }
        .empty { color: #9ca3af; }
    </style>
</head>
<body>
    <h2>Laporan Kegiatan</h2>
    <p class="sub">{{ $user?->name ?? '-' }} — {{ \Carbon\Carbon::createFromDate($year, (int) $month, 1)->format('F') }} {{ $year }}</p>

    <table>
        <thead>
            <tr>
                <th style="width: 30px;">No</th>
                <th style="width: 70px;">Tanggal</th>
                <th style="width: 80px;">Hari</th>
                <th>Tugas Selesai</th>
            </tr>
        </thead>
        <tbody>
            @forelse($rows as $i => $row)
            <tr>
                <td class="text-center" style="width: 30px;">{{ $i + 1 }}</td>
                <td style="width: 70px;">{{ $row['date'] }}</td>
                <td style="width: 80px;">{{ $row['day_name'] }}</td>
                <td>
                    @if($row['is_weekend'] ?? false)
                        <span class="libur">Hari Libur</span>
                    @elseif(empty(trim($row['tasks'])))
                        <span class="empty">-</span>
                    @else
                        {!! nl2br(e($row['tasks'])) !!}
                    @endif
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="4" class="text-center">Tidak ada data</td>
            </tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
