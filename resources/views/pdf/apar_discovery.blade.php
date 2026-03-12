{{-- Discovery pass: renders ALL rows flat (no rowspan) with inline PHP markers --}}
{{-- to detect page breaks. DomPDF writes page numbers to a temp file. --}}
@extends('pdf.layout')

@section('content')

    <div style="margin-bottom: 10px; font-size: 12px; line-height: 1.8;">
        <table style="border: none; width: auto;">
            @if($selectedAsset !== 'all')
            <tr>
                <td style="border: none; padding: 2px 0; width: 70px;">Kode Asset</td>
                <td style="border: none; padding: 2px 0;">: <strong>{{ $selectedAsset }}</strong></td>
            </tr>
            <tr>
                <td style="border: none; padding: 2px 0; width: 70px;">LOKASI</td>
                <td style="border: none; padding: 2px 0;">: <strong>{{ $roomName ?: '-' }}</strong></td>
            </tr>
            @else
            <tr>
                <td style="border: none; padding: 2px 0; width: 70px;">LOKASI</td>
                <td style="border: none; padding: 2px 0;">: <strong>Semua Lokasi</strong></td>
            </tr>
            @endif
            <tr>
                <td style="border: none; padding: 2px 0;">Tahun</td>
                <td style="border: none; padding: 2px 0;">: <strong>{{ $yearRange ?: '-' }}</strong></td>
            </tr>
        </table>
    </div>

    <table>
        <thead>
            <tr>
                <th rowspan="2" width="4%" style="text-align: center; vertical-align: middle;">No</th>
                @if($selectedAsset === 'all')
                    <th rowspan="2" width="7%" style="text-align: center; vertical-align: middle;">Tabung</th>
                @endif
                <th colspan="2" rowspan="2" style="text-align: center; vertical-align: middle;">TANGGAL</th>
                <th colspan="{{ count($checklistCols) }}" style="text-align: center;">Pengamatan Bagian APAR</th>
                <th rowspan="2" width="11%" style="text-align: center; vertical-align: middle;">Petugas</th>
                <th rowspan="2" width="14%" style="text-align: center; vertical-align: middle;">Keterangan</th>
            </tr>
            <tr>
                @foreach($checklistCols as $col)
                    <th style="text-align: center;">{{ $col->label }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @php $rowNum = 0; @endphp
            @foreach($data as $group)
                @foreach($group['inspections'] as $row)
                    @php $rowNum++; @endphp
                    <tr>
                        <td style="text-align: center; font-size: 10px;">{{ $rowNum }}</td>
                        @if($selectedAsset === 'all')
                            <td style="font-size: 10px; text-align: center;">{{ $group['asset_code'] }}</td>
                        @endif
                        <td style="font-size: 10px; text-align: center;">{{ $group['periode_label'] }}</td>
                        <td style="font-size: 10px; text-align: center;">{{ $row['tanggal'] }}</td>
                        @foreach($row['dynamic_answers'] as $ans)
                            <td style="text-align: center; font-size: 10px;">
                                {{ $ans['status'] }}
                                @if($loop->last)
                                    {!! '<script type="text/php">file_put_contents("' . $discoveryFile . '", $PAGE_NUM . "\n", FILE_APPEND);</script>' !!}
                                @endif
                            </td>
                        @endforeach
                        <td style="font-size: 10px;">{{ $row['petugas'] }}</td>
                        <td style="font-size: 10px;">{{ Str::limit($row['keterangan'] ?? '', 30) }}</td>
                    </tr>
                @endforeach
            @endforeach
        </tbody>
    </table>

@endsection
