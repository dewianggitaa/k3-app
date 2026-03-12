{{-- Discovery pass: renders ALL rows flat (no rowspan) with inline PHP markers --}}
{{-- for inspection items. DomPDF evaluates the scripts during rendering and --}}
{{-- writes the page number for each inspection row to a temp file. --}}
@extends('pdf.layout')

@section('content')

    <div style="margin-bottom: 10px; font-size: 12px; font-weight: bold; line-height: 1.8;">
        <table style="border: none; width: auto; margin-top: 0;">
            <tr>
                <td style="border: none; padding: 2px 0; width: 140px;">LOKASI KOTAK P3K</td>
                <td style="border: none; padding: 2px 0;">: {{ $selectedAsset !== 'all' ? $roomName : 'Semua Lokasi' }}</td>
            </tr>
            @if($selectedAsset !== 'all')
            <tr>
                <td style="border: none; padding: 2px 0;">KODE ASET</td>
                <td style="border: none; padding: 2px 0;">: {{ $selectedAsset }}</td>
            </tr>
            @endif
            <tr>
                <td style="border: none; padding: 2px 0;">BULAN</td>
                <td style="border: none; padding: 2px 0;">: {{ \Carbon\Carbon::parse($startDate)->translatedFormat('F Y') }}</td>
            </tr>
        </table>
    </div>

    <table>
        <thead>
            <tr>
                <th width="4%" style="text-align: center;">No</th>
                <th width="18%" style="text-align: center;">Isi</th>
                <th width="8%" style="text-align: center;">Jml. Awal</th>
                <th width="8%" style="text-align: center;">Mutasi</th>
                <th width="13%" style="text-align: center;">Tipe Aktivitas</th>
                <th width="10%" style="text-align: center;">Tanggal</th>
                <th width="25%">Keterangan</th>
                <th width="14%">Petugas</th>
            </tr>
        </thead>
        <tbody>
            @php $rowIdx = 0; @endphp
            @foreach($data as $entry)
                @php
                    $isPemakaian = $entry['entry_type'] === 'pemakaian';
                    $isPenambahan = $entry['entry_type'] === 'penambahan';
                    $isInspection = $entry['entry_type'] === 'inspeksi';
                    $qtyColor = $isPemakaian ? '#dc2626' : ($isPenambahan ? '#2563eb' : '#b45309');
                @endphp
                @foreach($entry['items'] as $item)
                    @php $rowIdx++; @endphp
                    <tr>
                        <td style="text-align: center; font-size: 10px;">{{ $rowIdx }}</td>
                        <td style="font-size: 10px;">{{ $item['item_name'] }}</td>
                        <td style="text-align: center; font-size: 10px;">-</td>
                        <td style="text-align: center; font-size: 10px; color: {{ $qtyColor }};">
                            {{ $item['qty'] !== null ? $item['qty'] : '-' }}
                        </td>
                        <td style="text-align: center; font-size: 10px;">{{ $entry['action_type'] }}</td>
                        <td style="text-align: center; font-size: 10px;">
                            {{ \Carbon\Carbon::parse($entry['record_date'])->format('d/m/Y H:i') }}
                        </td>
                        <td style="font-size: 10px;">{{ Str::limit($entry['notes'] ?? '', 40) }}</td>
                        <td style="font-size: 10px;">
                            {{ $entry['actor'] }}
                            @if($isInspection)
                                {!! '<script type="text/php">file_put_contents("' . $discoveryFile . '", $PAGE_NUM . "\n", FILE_APPEND);</script>' !!}
                            @endif
                        </td>
                    </tr>
                @endforeach
            @endforeach
        </tbody>
    </table>

@endsection
