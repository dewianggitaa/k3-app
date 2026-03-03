@extends('pdf.layout')

@section('content')
    <table>
        <thead>
            <tr>
                <th width="5%">No</th>
                @if($selectedAsset === 'all') <th width="12%">Hidran</th> @endif
                <th width="15%">Periode Pemeriksaan</th>
                <th width="18%">Data Pelapor (PIC)</th>
                <th width="{{ $selectedAsset === 'all' ? '28%' : '35%' }}">Hasil Inspeksi Hydrant</th>
                <th width="{{ $selectedAsset === 'all' ? '22%' : '27%' }}">Catatan</th>
                <th width="12%">Kondisi</th>
            </tr>
        </thead>
        <tbody>
            @forelse($data as $index => $report)
                <tr>
                    <td style="text-align: center; vertical-align: middle;">
                        {{ $index + 1 }}
                    </td>

                    @if($selectedAsset === 'all') 
                        <td style="vertical-align: middle;"><b>{{ $report['asset_code'] }}</b></td> 
                    @endif
                    
                    <td style="vertical-align: middle; font-weight: bold; background-color: #f0fdfa; text-align: center; color: #0f766e;">
                        {{ $report['periode_pemeriksaan'] }}
                    </td>

                    <td>
                        <b>{{ $report['actor'] }}</b><br>
                        <div class="meta-text">{{ \Carbon\Carbon::parse($report['record_date'])->format('d/m/Y H:i') }}</div>
                    </td>
                    
                    <td style="padding: 6px;">
                        <div class="{{ $report['status'] === 'KRITIS' ? 'text-danger' : ($report['status'] === 'SAFE' ? 'text-black' : '') }}" style="font-weight: bold;">
                            {{ $report['status'] }}
                        </div>
                        
                        @if($report['status'] === 'KRITIS')
                            @php
                                $rincianRusak = str_replace("Kondisi: KRITIS\nRincian: ", "", $report['details']);
                            @endphp
                            <div class="meta-text" style="color: #b91c1c; margin-top: 2px;">
                                {{ $rincianRusak }}
                            </div>
                        @elseif($report['status'] === 'SAFE')
                            <div class="meta-text" style="margin-top: 2px;">
                                Seluruh komponen standar normal.
                            </div>
                        @endif
                    </td>

                    <td style="vertical-align: middle;">
                        {{ $report['notes'] ?? '-' }}
                    </td>

                    <td style="font-weight: bold; text-align: center; vertical-align: middle;">
                        @if($report['kondisi_akhir'] == 'SAFE') 
                            <span class="text-black">SAFE</span>
                        @else 
                            <span class="text-danger">KRITIS</span> 
                        @endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="{{ $selectedAsset === 'all' ? 7 : 6 }}" style="text-align: center; padding: 40px; color: #666;">Belum ada data inspeksi hydrant yang tercatat untuk periode ini.</td></tr>
            @endforelse
        </tbody>
    </table>
@endsection