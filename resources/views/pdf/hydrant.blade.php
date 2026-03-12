@extends('pdf.layout')

@section('content')

    <div style="margin-bottom: 10px; font-size: 12px; line-height: 1.8;">
        <table style="border: none; width: auto;">
            @if($selectedAsset !== 'all')
            <tr>
                <td style="border: none; padding: 2px 0; width: 140px;">NO OHB / IHB</td>
                <td style="border: none; padding: 2px 0;">: <strong>{{ $selectedAsset }}</strong></td>
            </tr>
            <tr>
                <td style="border: none; padding: 2px 0;">LOKASI</td>
                <td style="border: none; padding: 2px 0;">: <strong>{{ $roomName ?: '-' }}</strong></td>
            </tr>
            @else
            <tr>
                <td style="border: none; padding: 2px 0; width: 140px;">LOKASI</td>
                <td style="border: none; padding: 2px 0;">: <strong>Semua Lokasi</strong></td>
            </tr>
            @endif
            <tr>
                <td style="border: none; padding: 2px 0;">TAHUN</td>
                <td style="border: none; padding: 2px 0;">: <strong>{{ $yearRange ?: '-' }}</strong></td>
            </tr>
        </table>
    </div>

    <table>
        <thead>
            <tr>
                <th width="4%" style="text-align: center;">No</th>
                @if($selectedAsset === 'all')
                    <th width="8%" style="text-align: center;">NO OHB/IHB</th>
                @endif
                <th width="10%" style="text-align: center;">TANGGAL</th>
                
                @foreach($checklistCols as $col)
                    <th style="text-align: center;">{{ strtoupper($col->label) }}</th>
                @endforeach
                
                <th width="12%" style="text-align: center;">PELAPOR</th>
                <th width="15%" style="text-align: center;">KETERANGAN</th>
            </tr>
        </thead>
        <tbody>
            @php $rowNum = 0; @endphp
            @forelse($data as $row)
                @php $rowNum++; @endphp
                <tr>
                    <td style="text-align: center; font-size: 10px;">{{ $rowNum }}</td>
                    
                    @if($selectedAsset === 'all')
                        <td style="text-align: center; font-size: 10px; font-weight: bold;">{{ $row['asset_code'] }}</td>
                    @endif
                    
                    <td style="text-align: center; font-size: 10px;">{{ $row['tanggal'] }}</td>
                    
                    @foreach($row['dynamic_answers'] as $ans)
                        <td style="text-align: center; font-weight: bold; font-size: 10px; {{ $ans['status'] === 'TMS' ? 'color: #dc2626;' : '' }}">
                            {{ $ans['status'] }}
                        </td>
                    @endforeach
                    
                    <td style="font-size: 10px; text-align: center;">{{ $row['petugas'] }}</td>
                    <td style="font-size: 10px;">{{ $row['keterangan'] ?? '-' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="{{ 4 + count($checklistCols) + ($selectedAsset==='all'?1:0) }}" style="text-align: center; padding: 40px; color: #666;">
                        Belum ada data inspeksi hydrant yang tercatat untuk periode ini.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
@endsection